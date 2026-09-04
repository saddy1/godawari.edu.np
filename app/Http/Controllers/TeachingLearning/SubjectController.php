<?php

namespace App\Http\Controllers\TeachingLearning;

use App\Http\Controllers\Controller;
use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\Card\Section;
use App\Models\Card\Subject;
use App\Models\Card\SubjectOffering;
use App\Services\SubjectEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function dashboard()
    {
        $organizations = Organization::withCount('departments')->orderBy('name')->get();
        $recentSubjects = Subject::withCount('offerings')->latest()->take(6)->get();

        $stats = [
            'subjects' => Subject::count(),
            'allocations' => SubjectOffering::count(),
            'faculties' => Department::count(),
            'practical' => Subject::where('has_practical', true)->count(),
            'electives' => SubjectOffering::where('is_elective', true)->count(),
        ];

        return view('teaching_learning.dashboard', compact('organizations', 'recentSubjects', 'stats'));
    }

    public function index(Request $request)
    {
        $organizations = Organization::with('departments.sections')->orderBy('name')->get();
        $subjects      = Subject::orderBy('name')->get();

        $orgId  = $request->integer('org') ?: null;
        $deptId = $request->integer('dept') ?: null;

        $selectedOrg = $orgId ? $organizations->firstWhere('id', $orgId) : null;
        $selectedDept = $deptId
            ? Department::with(['organization', 'sections'])->find($deptId)
            : null;

        if ($selectedDept && $selectedOrg && (int) $selectedDept->organization_id !== (int) $selectedOrg->id) {
            $selectedDept = null;
            $deptId = null;
        } elseif ($selectedDept && ! $selectedOrg) {
            $selectedOrg = $organizations->firstWhere('id', (int) $selectedDept->organization_id);
            $orgId = $selectedOrg?->id;
        }

        $deptGroups = $selectedDept
            ? $selectedDept->sections->pluck('group_name')->filter()->unique()->sort()->values()
            : collect();

        $offerings = $selectedDept
            ? SubjectOffering::with('subject')
                ->where('department_id', $selectedDept->id)
                ->orderByRaw('semester IS NULL, semester')
                ->orderByRaw('year_level IS NULL, year_level')
                ->orderByRaw('group_name IS NULL, group_name')
                ->orderBy('elective_group')
                ->get()
            : collect();

        return view('teaching_learning.subjects.index', compact(
            'organizations',
            'subjects',
            'orgId',
            'deptId',
            'selectedOrg',
            'selectedDept',
            'deptGroups',
            'offerings',
        ));
    }

    // ── Subject Offerings (which subjects a faculty/class/group takes) ─────

    public function updateSectionGroup(Request $request, SubjectEnrollmentService $enrollments)
    {
        $request->merge(['group_name' => trim(preg_replace('/\s+/', ' ', (string) $request->input('group_name')))]);
        $data = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'group_name' => ['required', 'string', 'max:100'],
            'section_ids' => ['required_unless:intent,remove', 'array'],
            'section_ids.*' => ['integer', 'exists:sections,id'],
            'intent' => ['required', 'in:save,remove'],
        ]);

        $department = Department::with('organization')->findOrFail($data['department_id']);
        $sectionIds = collect($data['section_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $matchedSections = Section::where('department_id', $department->id)->whereIn('id', $sectionIds)->get();

        if ($data['intent'] === 'save' && $matchedSections->count() !== $sectionIds->count()) {
            return back()->withErrors(['section_ids' => 'Every selected section must belong to this class or department.'])->withInput();
        }

        DB::transaction(function () use ($data, $department, $sectionIds) {
            $existingGroup = Section::where('department_id', $department->id)
                ->where('group_name', $data['group_name']);

            if ($data['intent'] === 'remove') {
                $existingGroup->update(['group_name' => null]);
                return;
            }

            $existingGroup->whereNotIn('id', $sectionIds)->update(['group_name' => null]);
            Section::where('department_id', $department->id)
                ->whereIn('id', $sectionIds)
                ->update(['group_name' => $data['group_name']]);
        });

        $academicYear = $enrollments->currentWritableAcademicYear();
        $synced = $academicYear ? $enrollments->syncDepartment($department, $academicYear) : 0;

        $message = $data['intent'] === 'remove'
            ? "Section group {$data['group_name']} removed."
            : "{$data['group_name']} saved with {$sectionIds->count()} section(s).";
        if ($academicYear) $message .= " {$synced} individual compulsory subject enrollment record(s) synchronized.";

        return back()->with('success', $message);
    }

    public function storeSubjectOffering(Request $request, SubjectEnrollmentService $enrollments)
    {
        $subjectCode = strtoupper(preg_replace('/\s+/', '', (string) $request->input('subject_code')));
        $practicalCode = strtoupper(preg_replace('/\s+/', '', (string) $request->input('practical_code')));
        $request->merge([
            'subject_code' => $subjectCode,
            'practical_code' => $practicalCode ?: null,
        ]);
        $existingSubject = Subject::where('code', $subjectCode)->first();

        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'group_name'    => 'nullable|string|max:100',
            'semester'      => 'nullable|integer|min:1|max:8',
            'year_level'    => 'nullable|integer|min:1|max:6',
            'subject_code'  => 'required|string|max:50',
            'subject_name'  => [Rule::requiredIf(! $existingSubject), 'nullable', 'string', 'max:150'],
            'credit_hours'  => 'nullable|integer|min:0|max:255',
            'has_practical' => 'nullable|boolean',
            'practical_code' => 'nullable|string|max:50',
            'is_elective'   => 'nullable|boolean',
            'elective_group' => 'nullable|string|max:50',
        ]);
        $department = Department::findOrFail($data['department_id']);
        $data['semester'] = $department->academic_system === 'semester' ? ($data['semester'] ?? null) : null;
        $data['year_level'] = $department->academic_system === 'year' ? ($data['year_level'] ?? null) : null;
        $data['group_name']    = $data['group_name'] ?? null;
        $data['is_elective']   = $request->boolean('is_elective');
        $data['elective_group'] = $data['is_elective']
            ? ($data['elective_group'] ?? null)
            : null;

        $offering = DB::transaction(function () use ($data, $request, $existingSubject) {
            $subject = $existingSubject;

            if (! $subject) {
                $hasPractical = $request->boolean('has_practical');
                $subject = Subject::create([
                    'code' => $data['subject_code'],
                    'name' => $data['subject_name'],
                    'credit_hours' => $data['credit_hours'] ?? null,
                    'has_practical' => $hasPractical,
                    'practical_code' => $hasPractical
                        ? ($data['practical_code'] ?: $data['subject_code'])
                        : null,
                ]);
            }

            return SubjectOffering::updateOrCreate(
                [
                    'department_id' => $data['department_id'],
                    'group_name' => $data['group_name'],
                    'semester' => $data['semester'] ?? null,
                    'year_level' => $data['year_level'] ?? null,
                    'subject_id' => $subject->id,
                ],
                [
                    'is_elective' => $data['is_elective'],
                    'elective_group' => $data['elective_group'],
                ]
            );
        });
        $assignedCount = $enrollments->syncOffering($offering);

        $message = $existingSubject
            ? "Existing subject {$subjectCode} assigned to the faculty."
            : "New subject {$subjectCode} created and assigned to the faculty.";
        if (! $offering->is_elective) $message .= " {$assignedCount} matching student(s) received it automatically.";

        return back()->with('success', $message);
    }

    public function destroySubjectOffering(SubjectOffering $subjectOffering)
    {
        $subjectOffering->loadMissing('department');
        $deptId = $subjectOffering->department_id;
        $orgId = $subjectOffering->department?->organization_id;
        $subjectOffering->delete();
        return redirect()->route('admin.teaching-learning.subjects.index', ['org' => $orgId, 'dept' => $deptId])
            ->with('success', "Subject removed from the class.");
    }

    public function updateSubjectOffering(Request $request, SubjectOffering $subjectOffering, SubjectEnrollmentService $enrollments)
    {
        $subjectOffering->loadMissing(['subject', 'department']);

        $subjectCode = strtoupper(preg_replace('/\s+/', '', (string) $request->input('subject_code')));
        $practicalCode = strtoupper(preg_replace('/\s+/', '', (string) $request->input('practical_code')));
        $request->merge([
            'subject_code' => $subjectCode,
            'practical_code' => $practicalCode ?: null,
        ]);

        $data = $request->validate([
            'subject_name' => ['required', 'string', 'max:150'],
            'subject_code' => ['required', 'string', 'max:50', Rule::unique('subjects', 'code')->ignore($subjectOffering->subject_id)],
            'credit_hours' => ['nullable', 'integer', 'min:0', 'max:255'],
            'has_practical' => ['nullable', 'boolean'],
            'practical_code' => ['nullable', 'string', 'max:50'],
            'group_name' => ['nullable', 'string', 'max:100'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:8'],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:6'],
            'is_elective' => ['nullable', 'boolean'],
            'elective_group' => ['nullable', 'string', 'max:50'],
        ]);

        $hasPractical = $request->boolean('has_practical');
        $isElective = $request->boolean('is_elective');
        $department = $subjectOffering->department;

        DB::transaction(function () use ($data, $subjectOffering, $department, $hasPractical, $isElective) {
            $subjectOffering->subject->update([
                'name' => $data['subject_name'],
                'code' => $data['subject_code'],
                'credit_hours' => $data['credit_hours'] ?? null,
                'has_practical' => $hasPractical,
                'practical_code' => $hasPractical
                    ? ($data['practical_code'] ?: $data['subject_code'])
                    : null,
            ]);

            $subjectOffering->update([
                'semester' => $department->academic_system === 'semester' ? ($data['semester'] ?? null) : null,
                'year_level' => $department->academic_system === 'year' ? ($data['year_level'] ?? null) : null,
                'group_name' => $data['group_name'] ?? null,
                'is_elective' => $isElective,
                'elective_group' => $isElective ? ($data['elective_group'] ?? null) : null,
            ]);
        });
        $assignedCount = $enrollments->syncOffering($subjectOffering->fresh());

        return back()->with('success', $isElective
            ? 'Subject allocation updated. Elective assignment remains manual.'
            : "Subject allocation updated and synchronized to {$assignedCount} matching student(s).");
    }
}
