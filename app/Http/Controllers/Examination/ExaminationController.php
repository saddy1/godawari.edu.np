<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\Card\Section;
use App\Models\Card\SubjectOffering;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationSubject;
use App\Models\Examination\ExaminationMarkSubmission;
use App\Models\TeachingLearning\AcademicYear;
use App\Services\ExamAnalyticsService;
use App\Services\ExamTeacherAssignmentService;
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
        if ($user->isTeacher()) {
            $selectedExam = $request->filled('exam')
                ? $examinations->firstWhere('id', $request->integer('exam'))
                : ($examinations->firstWhere('status', 'ongoing') ?? $examinations->first());
            $entries = collect();
            if ($selectedExam) {
                $subjects = ExaminationSubject::with(['examination.sections', 'offering.subject', 'offering.department', 'marks'])
                    ->where('examination_id', $selectedExam->id)->get();
                foreach ($subjects as $subject) {
                    $theorySections = (float) $subject->theory_full_marks > 0
                        ? $teacherAssignments->sectionIdsFor($subject, $user, 'theory') : collect();
                    $practicalSections = (float) $subject->practical_full_marks > 0
                        ? $teacherAssignments->sectionIdsFor($subject, $user, 'practical') : collect();
                    $sectionIds = $theorySections->merge($practicalSections)->unique()->values();
                    if ($sectionIds->isEmpty()) continue;
                    $entries->push((object) [
                        'subject' => $subject,
                        'sections' => $subject->examination->sections->whereIn('id', $sectionIds)->pluck('name')->implode(', '),
                        'has_theory' => $theorySections->isNotEmpty(),
                        'has_practical' => $practicalSections->isNotEmpty(),
                        'theory_entered' => $subject->marks->filter(fn ($mark) => $mark->theory_marks !== null || $mark->theory_is_absent)->count(),
                        'practical_entered' => $subject->marks->filter(fn ($mark) => $mark->practical_marks !== null || $mark->practical_is_absent)->count(),
                        'submission' => ExaminationMarkSubmission::where('examination_subject_id', $subject->id)
                            ->where('teacher_id', $user->id)->first(),
                    ]);
                }
            }
            $today = now()->startOfDay();
            $entryOpen = $selectedExam && $selectedExam->status === 'ongoing'
                && (! $selectedExam->starts_on || $today->gte($selectedExam->starts_on))
                && (! $selectedExam->ends_on || $today->lte($selectedExam->ends_on));

            return view('examinations.teacher-index', compact('examinations', 'selectedExam', 'entries', 'entryOpen'));
        }
        $selectedExam = $request->filled('exam') ? $examinations->firstWhere('id', $request->integer('exam')) : null;
        $workspace = $selectedExam ? $this->workspaceData($selectedExam, $analytics, $teacherAssignments) : [];

        return view('examinations.index', [
            'examinations' => $examinations,
            'selectedExam' => $selectedExam,
            'snapshot' => $workspace['analytics'] ?? null,
            'organizations' => Organization::with(['departments' => fn ($query) => $query->where('is_active', true)->orderBy('name')
                ->with(['sections' => fn ($sections) => $sections->where('is_active', true)->orderBy('name')])])
                ->where('is_active', true)->orderBy('name')->get(),
            'academicYears' => AcademicYear::latest('starts_on')->get(),
            'canManage' => $canManage,
        ] + $workspace);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'organization_id' => ['required', 'exists:organizations,id'],
            'scope_type' => ['required', 'in:organization,departments'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:80'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:8'],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:6'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'theory_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'practical_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $organization = Organization::findOrFail($data['organization_id']);
        $organizationDepartments = Department::where('organization_id', $organization->id)->where('is_active', true)->get();
        $departments = $data['scope_type'] === 'organization'
            ? $organizationDepartments->pluck('id')
            : $organizationDepartments->whereIn('id', collect($data['department_ids'] ?? [])->map(fn ($id) => (int) $id))->pluck('id');
        if ($departments->isEmpty()) throw ValidationException::withMessages(['department_ids' => 'Choose at least one faculty/class, or select the whole organization.']);
        $sections = Section::whereIn('department_id', $departments)->where('is_active', true)->pluck('id');
        if ($sections->isEmpty()) throw ValidationException::withMessages(['department_ids' => 'The selected exam scope has no active sections.']);
        $department = $organizationDepartments->firstWhere('id', $departments->first());
        $data['department_id'] = $department->id;
        $data['semester'] = $departments->count() === 1 && $department->academic_system === 'semester' ? ($data['semester'] ?? null) : null;
        $data['year_level'] = $departments->count() === 1 && $department->academic_system === 'year' ? ($data['year_level'] ?? null) : null;
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();
        unset($data['department_ids']);

        $exam = DB::transaction(function () use ($data, $sections, $departments) {
            $exam = Examination::create($data);
            $exam->departments()->sync($departments);
            $exam->sections()->sync($sections);
            return $exam;
        });

        return redirect()->route('admin.examinations.index', ['exam' => $exam->id])->with('success', 'Exam created. Configure each unique subject below.');
    }

    public function show(Examination $examination, ExamAnalyticsService $analytics, ExamTeacherAssignmentService $teacherAssignments)
    {
        $this->authorizeExamView($examination, $teacherAssignments);
        return redirect()->route('admin.examinations.index', ['exam' => $examination->id]);
    }

    private function workspaceData(Examination $examination, ExamAnalyticsService $analytics, ExamTeacherAssignmentService $teacherAssignments): array
    {
        $examination->load(['academicYear', 'organization', 'department.sections', 'departments.sections', 'sections', 'subjects.offering.subject', 'subjects.offering.department', 'subjects.marks', 'subjects.markSubmissions.teacher']);
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
        $teacherLabels = $teacherAssignments->labelsByOffering($examination, $offerings->pluck('id'));
        $subjectRows = $offerings->groupBy('subject_id')->map(function ($subjectOfferings) use ($examination, $teacherLabels) {
            $first = $subjectOfferings->first();
            return [
                'subject' => $first->subject,
                'offerings' => $subjectOfferings,
                'configured' => $examination->subjects->whereIn('subject_offering_id', $subjectOfferings->pluck('id')),
                'is_elective' => $subjectOfferings->every(fn ($offering) => $offering->is_elective),
                'theory_labels' => $subjectOfferings->flatMap(fn ($offering) => $teacherLabels->get($offering->id)['theory'] ?? collect())->unique()->sort()->values(),
                'practical_labels' => $subjectOfferings->flatMap(fn ($offering) => $teacherLabels->get($offering->id)['practical'] ?? collect())->unique()->sort()->values(),
            ];
        })->values();
        $markAccess = $examination->subjects->mapWithKeys(fn ($subject) => [$subject->id => [
            'theory' => $teacherAssignments->sectionIdsFor($subject, auth()->user(), 'theory')->count(),
            'practical' => (float) $subject->practical_full_marks > 0
                ? $teacherAssignments->sectionIdsFor($subject, auth()->user(), 'practical')->count() : 0,
        ]]);
        $unlockRequests = ExaminationMarkSubmission::with(['teacher', 'examinationSubject.offering.subject'])
            ->whereHas('examinationSubject', fn ($query) => $query->where('examination_id', $examination->id))
            ->whereNotNull('unlock_requested_at')->latest('unlock_requested_at')->get();

        return [
            'examination' => $examination,
            'offerings' => $offerings,
            'subjectRows' => $subjectRows,
            'markAccess' => $markAccess,
            'unlockRequests' => $unlockRequests,
            'analytics' => $analytics->forExam($examination),
            'markEntryOpen' => $examination->status === 'ongoing'
                && (! $examination->starts_on || now()->startOfDay()->gte($examination->starts_on))
                && (! $examination->ends_on || now()->startOfDay()->lte($examination->ends_on)),
            'canManage' => auth()->user()->canAccess('examinations.manage'),
        ];
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

    public function saveSubjects(Request $request, Examination $examination)
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
            $upserts = [];
            $enabledOfferingIds = collect();
            $now = now();
            foreach ($validOfferings->groupBy('subject_id') as $subjectId => $subjectOfferings) {
                $row = $rows->get((string) $subjectId, $rows->get($subjectId, []));
                if (! filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }
                $theoryFull = (float) ($row['theory_full_marks'] ?? 0); $theoryPass = (float) ($row['theory_pass_marks'] ?? 0);
                $practicalFull = (float) ($row['practical_full_marks'] ?? 0); $practicalPass = (float) ($row['practical_pass_marks'] ?? 0);
                if ($theoryPass > $theoryFull || $practicalPass > $practicalFull || $theoryFull + $practicalFull <= 0) {
                    throw ValidationException::withMessages(["subjects.{$subjectId}" => 'Pass marks cannot exceed full marks, and total full marks must be greater than zero.']);
                }
                $hasPractical = (bool) $subjectOfferings->first()->subject->has_practical;
                foreach ($subjectOfferings as $offering) {
                    $enabledOfferingIds->push($offering->id);
                    $upserts[] = ['examination_id' => $examination->id, 'subject_offering_id' => $offering->id,
                        'theory_full_marks' => $theoryFull, 'theory_pass_marks' => $theoryPass,
                        'practical_full_marks' => $hasPractical ? $practicalFull : 0, 'practical_pass_marks' => $hasPractical ? $practicalPass : 0,
                        'created_at' => $now, 'updated_at' => $now];
                }
            }
            if ($upserts) ExaminationSubject::upsert($upserts, ['examination_id', 'subject_offering_id'], [
                'theory_full_marks', 'theory_pass_marks', 'practical_full_marks', 'practical_pass_marks', 'updated_at',
            ]);
            ExaminationSubject::where('examination_id', $examination->id)->whereIn('subject_offering_id', $validOfferings->pluck('id'))
                ->when($enabledOfferingIds->isNotEmpty(), fn ($query) => $query->whereNotIn('subject_offering_id', $enabledOfferingIds))
                ->whereDoesntHave('marks')->delete();
        });
        return back()->with('success', 'Exam subjects and theory/practical FM/PM saved. Teacher access follows Routine Builder assignments.');
    }

    public function destroy(Examination $examination)
    {
        if ($examination->subjects()->whereHas('marks')->exists()) {
            return back()->with('error', 'Remove entered marks before deleting this exam.');
        }
        $examination->delete();
        return redirect()->route('admin.examinations.index')->with('success', 'Exam deleted.');
    }

    private function authorizeExamView(Examination $exam, ExamTeacherAssignmentService $teacherAssignments): void
    {
        $user = auth()->user();
        abort_unless($teacherAssignments->teachesExam($exam, $user), 403);
    }
}
