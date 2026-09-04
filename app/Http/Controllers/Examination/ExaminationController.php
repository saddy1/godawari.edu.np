<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\Card\Section;
use App\Models\Card\SubjectOffering;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationSubject;
use App\Models\TeachingLearning\AcademicYear;
use App\Services\ExamAnalyticsService;
use App\Services\ExamTeacherAssignmentService;
use App\Services\SubjectEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExaminationController extends Controller
{
    public function index(Request $request, ExamAnalyticsService $analytics, ExamTeacherAssignmentService $teacherAssignments)
    {
        $user = auth()->user();
        $canManage = $user->canAccess('examinations.manage');
        $query = Examination::with(['academicYear', 'organization', 'department', 'departments', 'sections'])->withCount(['subjects', 'subjects as marks_count' => fn ($q) => $q->join('examination_marks', 'examination_subjects.id', '=', 'examination_marks.examination_subject_id')]);
        $query->when($request->filled('year'), fn ($q) => $q->where('academic_year_id', $request->integer('year')))
            ->when($request->filled('org'), fn ($q) => $q->where('organization_id', $request->integer('org')))
            ->when($request->filled('status'), function ($q) use ($request) {
                return $request->status === 'conducted'
                    ? $q->whereIn('status', ['completed', 'published'])
                    : $q->where('status', $request->status);
            });
        $examinations = $query->latest('starts_on')->latest('id')->get();
        if (! $canManage && ! $user->canAccess('examinations.reports')) {
            $examinations = $examinations->filter(fn ($exam) => $teacherAssignments->teachesExam($exam, $user))->values();
        }
        $selectedExam = $examinations->firstWhere('id', $request->integer('exam')) ?? $examinations->first();
        $snapshot = $selectedExam ? $analytics->forExam($selectedExam) : null;

        return view('examinations.index', [
            'examinations' => $examinations,
            'selectedExam' => $selectedExam,
            'snapshot' => $snapshot,
            'organizations' => Organization::with('departments.sections')->where('is_active', true)->orderBy('name')->get(),
            'academicYears' => AcademicYear::latest('starts_on')->get(),
            'canManage' => $canManage,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'organization_id' => ['required', 'exists:organizations,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:80'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:8'],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:6'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'theory_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'practical_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['integer', 'exists:sections,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $organization = Organization::findOrFail($data['organization_id']);
        $department = Department::findOrFail($data['department_id']);
        if ((int) $department->organization_id !== (int) $data['organization_id']) throw ValidationException::withMessages(['department_id' => 'The class/department does not belong to the selected organization.']);
        $departments = $organization->type === 'school'
            ? Department::where('organization_id', $organization->id)->where('is_active', true)->pluck('id')
            : collect([$department->id]);
        $sections = $organization->type === 'school'
            ? Section::whereIn('department_id', $departments)->pluck('id')
            : Section::where('department_id', $department->id)->whereIn('id', $data['section_ids'])->pluck('id');
        if ($organization->type !== 'school' && $sections->count() !== count(array_unique($data['section_ids']))) throw ValidationException::withMessages(['section_ids' => 'Every exam section must belong to the selected class/department.']);
        $data['semester'] = $organization->type !== 'school' && $department->academic_system === 'semester' ? ($data['semester'] ?? null) : null;
        $data['year_level'] = $organization->type !== 'school' && $department->academic_system === 'year' ? ($data['year_level'] ?? null) : null;
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();
        unset($data['section_ids']);

        $exam = DB::transaction(function () use ($data, $sections, $departments) {
            $exam = Examination::create($data);
            $exam->departments()->sync($departments);
            $exam->sections()->sync($sections);
            return $exam;
        });

        return redirect()->route('admin.examinations.show', $exam)->with('success', 'Exam created. Now select subjects and set theory/practical FM and PM.');
    }

    public function show(Examination $examination, ExamAnalyticsService $analytics, ExamTeacherAssignmentService $teacherAssignments)
    {
        $this->authorizeExamView($examination, $teacherAssignments);
        $examination->load(['academicYear', 'organization', 'department.sections', 'departments.sections', 'sections', 'subjects.offering.subject', 'subjects.offering.department', 'subjects.marks']);
        $departmentIds = $examination->departments->pluck('id');
        if ($departmentIds->isEmpty()) $departmentIds = collect([$examination->department_id]);
        $sectionIds = $examination->sections->pluck('id');
        $sectionNames = $examination->sections->pluck('name');
        $sectionGroups = $examination->sections->pluck('group_name')->filter()->unique();
        $offerings = SubjectOffering::with(['subject', 'department'])->whereIn('department_id', $departmentIds)
            ->when($examination->semester, fn ($q) => $q->where(fn ($q) => $q->whereNull('semester')->orWhere('semester', $examination->semester)))
            ->when($examination->year_level, fn ($q) => $q->where(fn ($q) => $q->whereNull('year_level')->orWhere('year_level', $examination->year_level)))
            ->where(fn ($q) => $q->whereNull('group_name')->orWhereIn('group_name', $sectionGroups))
            ->where(fn ($q) => $q->where('is_elective', false)->orWhereHas('studentEnrollments', fn ($enrollments) => $enrollments
                ->where('academic_year', $examination->academicYear->name)
                ->where('assignment_source', 'manual')
                ->whereHas('student', fn ($students) => $students->whereIn('section_id', $sectionIds)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('section_id')->whereIn('section', $sectionNames)))))
            ->orderBy('is_elective')->get();
        $subjectRows = $offerings->groupBy('subject_id')->map(function ($subjectOfferings) use ($examination, $teacherAssignments) {
            $first = $subjectOfferings->first();
            return [
                'subject' => $first->subject,
                'offerings' => $subjectOfferings,
                'configured' => $examination->subjects->whereIn('subject_offering_id', $subjectOfferings->pluck('id')),
                'is_elective' => $subjectOfferings->every(fn ($offering) => $offering->is_elective),
                'theory_labels' => $subjectOfferings->flatMap(fn ($offering) => $teacherAssignments->labelsFor($examination, $offering->id, 'theory'))->unique()->sort()->values(),
                'practical_labels' => $subjectOfferings->flatMap(fn ($offering) => $teacherAssignments->labelsFor($examination, $offering->id, 'practical'))->unique()->sort()->values(),
            ];
        })->values();
        $markAccess = $examination->subjects->mapWithKeys(fn ($subject) => [$subject->id => [
            'theory' => $teacherAssignments->sectionIdsFor($subject, auth()->user(), 'theory')->count(),
            'practical' => (float) $subject->practical_full_marks > 0
                ? $teacherAssignments->sectionIdsFor($subject, auth()->user(), 'practical')->count() : 0,
        ]]);

        return view('examinations.show', [
            'examination' => $examination,
            'offerings' => $offerings,
            'subjectRows' => $subjectRows,
            'markAccess' => $markAccess,
            'analytics' => $analytics->forExam($examination),
            'canManage' => auth()->user()->canAccess('examinations.manage'),
        ]);
    }

    public function update(Request $request, Examination $examination)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'], 'category' => ['required', 'string', 'max:80'],
            'starts_on' => ['nullable', 'date'], 'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'theory_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'practical_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'status' => ['required', 'in:draft,ongoing,completed,published'], 'notes' => ['nullable', 'string', 'max:2000'],
            'section_ids' => ['required', 'array', 'min:1'], 'section_ids.*' => ['integer', 'exists:sections,id'],
        ]);
        $departmentIds = $examination->departments()->pluck('departments.id');
        if ($departmentIds->isEmpty()) $departmentIds = collect([$examination->department_id]);
        $sectionIds = $examination->organization->type === 'school'
            ? Section::whereIn('department_id', $departmentIds)->pluck('id')
            : Section::whereIn('department_id', $departmentIds)->whereIn('id', $data['section_ids'])->pluck('id');
        if ($examination->organization->type !== 'school' && $sectionIds->count() !== count(array_unique($data['section_ids']))) throw ValidationException::withMessages(['section_ids' => 'Invalid section selected.']);
        unset($data['section_ids']);
        DB::transaction(function () use ($examination, $data, $sectionIds) { $examination->update($data); $examination->sections()->sync($sectionIds); });
        return back()->with('success', 'Exam details updated.');
    }

    public function saveSubjects(Request $request, Examination $examination, SubjectEnrollmentService $enrollments)
    {
        abort_if($examination->is_locked, 422, 'Completed/published exams are locked.');
        $data = $request->validate([
            'subjects' => ['nullable', 'array'], 'subjects.*.enabled' => ['nullable', 'boolean'],
            'subjects.*.theory_full_marks' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'subjects.*.theory_pass_marks' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'subjects.*.practical_full_marks' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'subjects.*.practical_pass_marks' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ]);
        $examination->loadMissing(['academicYear', 'sections', 'departments']);
        $departmentIds = $examination->departments->pluck('id');
        if ($departmentIds->isEmpty()) $departmentIds = collect([$examination->department_id]);
        $sectionIds = $examination->sections->pluck('id');
        $sectionNames = $examination->sections->pluck('name');
        $sectionGroups = $examination->sections->pluck('group_name')->filter()->unique();
        $validOfferings = SubjectOffering::with('subject')->whereIn('department_id', $departmentIds)
            ->when($examination->semester, fn ($q) => $q->where(fn ($q) => $q->whereNull('semester')->orWhere('semester', $examination->semester)))
            ->when($examination->year_level, fn ($q) => $q->where(fn ($q) => $q->whereNull('year_level')->orWhere('year_level', $examination->year_level)))
            ->where(fn ($q) => $q->whereNull('group_name')->orWhereIn('group_name', $sectionGroups))
            ->where(fn ($q) => $q->where('is_elective', false)->orWhereHas('studentEnrollments', fn ($enrollments) => $enrollments
                ->where('academic_year', $examination->academicYear->name)
                ->where('assignment_source', 'manual')
                ->whereHas('student', fn ($students) => $students->whereIn('section_id', $sectionIds)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('section_id')->whereIn('section', $sectionNames)))))
            ->get();
        $rows = collect($data['subjects'] ?? []);

        DB::transaction(function () use ($rows, $validOfferings, $examination) {
            foreach ($validOfferings->groupBy('subject_id') as $subjectId => $subjectOfferings) {
                $row = $rows->get((string) $subjectId, $rows->get($subjectId, []));
                if (! filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    ExaminationSubject::where('examination_id', $examination->id)->whereIn('subject_offering_id', $subjectOfferings->pluck('id'))
                        ->whereDoesntHave('marks')->delete();
                    continue;
                }
                $theoryFull = (float) ($row['theory_full_marks'] ?? 0); $theoryPass = (float) ($row['theory_pass_marks'] ?? 0);
                $practicalFull = (float) ($row['practical_full_marks'] ?? 0); $practicalPass = (float) ($row['practical_pass_marks'] ?? 0);
                if ($theoryPass > $theoryFull || $practicalPass > $practicalFull || $theoryFull + $practicalFull <= 0) {
                    throw ValidationException::withMessages(["subjects.{$subjectId}" => 'Pass marks cannot exceed full marks, and total full marks must be greater than zero.']);
                }
                $hasPractical = (bool) $subjectOfferings->first()->subject->has_practical;
                foreach ($subjectOfferings as $offering) ExaminationSubject::updateOrCreate(
                    ['examination_id' => $examination->id, 'subject_offering_id' => $offering->id],
                    ['theory_full_marks' => $theoryFull, 'theory_pass_marks' => $theoryPass, 'practical_full_marks' => $hasPractical ? $practicalFull : 0, 'practical_pass_marks' => $hasPractical ? $practicalPass : 0]);
            }
        });
        if (! $examination->academicYear->is_locked) Department::with('organization')->whereIn('id', $departmentIds)->get()
            ->each(fn ($department) => $enrollments->syncDepartment($department, $examination->academicYear));
        return back()->with('success', 'Exam subjects and theory/practical FM/PM saved. Teacher access follows Routine Builder assignments.');
    }

    public function destroy(Examination $examination)
    {
        abort_if($examination->subjects()->whereHas('marks')->exists(), 422, 'Remove entered marks before deleting this exam.');
        $examination->delete();
        return redirect()->route('admin.examinations.index')->with('success', 'Draft exam deleted.');
    }

    private function authorizeExamView(Examination $exam, ExamTeacherAssignmentService $teacherAssignments): void
    {
        $user = auth()->user();
        abort_unless($teacherAssignments->teachesExam($exam, $user), 403);
    }
}
