<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Card\Student;
use App\Models\Examination\ExaminationMark;
use App\Models\Examination\ExaminationAttendance;
use App\Models\Examination\ExaminationMarkSubmission;
use App\Models\Examination\ExaminationSubject;
use App\Models\Examination\Examination;
use App\Services\ExamTeacherAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkEntryController extends Controller
{
    public function dashboard(Request $request)
    {
        abort_unless($request->user()->canAccess('examinations.manage'), 403);
        $exams = Examination::with(['academicYear', 'departments', 'sections'])->latest('starts_on')->get();
        $exam = $exams->firstWhere('id', $request->integer('exam'));
        $faculties = $exam?->departments ?? collect();
        $facultyId = $request->integer('faculty');
        $sections = $exam?->sections->when($facultyId, fn ($items) => $items->where('department_id', $facultyId))->values() ?? collect();
        $section = $sections->firstWhere('id', $request->integer('section'));
        $subjects = $exam ? ExaminationSubject::with(['offering.subject', 'offering.department', 'marks'])
            ->where('examination_id', $exam->id)->when($facultyId, fn ($q) => $q->whereHas('offering', fn ($o) => $o->where('department_id', $facultyId)))
            ->when($section, fn ($q) => $q->whereHas('offering.studentEnrollments', fn ($enrollments) => $enrollments
                ->where('academic_year', $exam->academicYear->name)
                ->whereHas('student', fn ($students) => $students->where('section_id', $section->id)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('section_id')->where('section', $section->name)))))
            ->get()->sortBy('offering.subject.name')->values() : collect();

        return view('examinations.marks-dashboard', compact('exams', 'exam', 'faculties', 'facultyId', 'sections', 'section', 'subjects'));
    }

    public function edit(Request $request, ExaminationSubject $examinationSubject, ExamTeacherAssignmentService $teacherAssignments)
    {
        $component = $this->component($request, $examinationSubject);
        $examinationSubject->load(['examination.academicYear', 'examination.departments', 'examination.sections', 'offering.subject', 'offering.department']);
        $theorySectionIds = (float) $examinationSubject->theory_full_marks > 0
            ? $teacherAssignments->sectionIdsFor($examinationSubject, auth()->user(), 'theory')
            : collect();
        $practicalSectionIds = (float) $examinationSubject->practical_full_marks > 0
            ? $teacherAssignments->sectionIdsFor($examinationSubject, auth()->user(), 'practical')
            : collect();
        $sectionIds = $theorySectionIds->merge($practicalSectionIds)->unique()->values();
        abort_if($sectionIds->isEmpty(), 403, 'This subject and section are not assigned to you in Routine Builder.');
        if ($message = $this->entryWindowMessage($examinationSubject, $request)) {
            $route = $request->user()->isTeacher()
                ? route('admin.teacher.workspace')
                : route('admin.examinations.index', ['exam' => $examinationSubject->examination_id]);
            return redirect()->to($route)->with('error', $message);
        }
        $sections = $examinationSubject->examination->sections->whereIn('id', $sectionIds)
            ->filter(function ($section) use ($examinationSubject) {
                return Student::query()->where('member_type', 'student')
                    ->whereHas('subjectEnrollments', fn ($enrollments) => $enrollments
                        ->where('subject_offering_id', $examinationSubject->subject_offering_id)
                        ->where('academic_year', $examinationSubject->examination->academicYear->name))
                    ->where(fn ($students) => $students->where('section_id', $section->id)
                        ->orWhere(fn ($legacy) => $legacy->whereNull('section_id')->where('section', $section->name)))
                    ->exists();
            })->values();
        abort_if($sections->isEmpty(), 422, 'No enrolled students study this subject in your assigned sections.');
        $selectedSection = $this->selectedSection($request, $sections);
        $selectedSectionIds = $selectedSection ? collect([(int) $selectedSection->id]) : collect();
        $students = $selectedSection
            ? $this->eligibleStudents($examinationSubject, $selectedSectionIds)->get()
            : collect();
        $attendanceAbsentIds = ExaminationAttendance::where('examination_subject_id', $examinationSubject->id)
            ->where('status', 'absent')->whereIn('student_id', $students->pluck('id'))->pluck('student_id')->map(fn ($id) => (int) $id);
        $this->syncAttendanceAbsences($examinationSubject, $attendanceAbsentIds);
        $marks = ExaminationMark::where('examination_subject_id', $examinationSubject->id)->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
        $markSubmission = $selectedSection ? ExaminationMarkSubmission::where('examination_subject_id', $examinationSubject->id)
            ->where('teacher_id', $request->user()->id)->where('section_id', $selectedSection->id)->first() : null;
        $submissionProgress = $this->submissionProgress($examinationSubject,
            $selectedSection ? $theorySectionIds->intersect($selectedSectionIds)->values() : $theorySectionIds,
            $selectedSection ? $practicalSectionIds->intersect($selectedSectionIds)->values() : $practicalSectionIds);
        return view('examinations.marks', compact(
            'examinationSubject', 'students', 'marks', 'component', 'sections', 'selectedSection', 'attendanceAbsentIds',
            'theorySectionIds', 'practicalSectionIds', 'markSubmission', 'submissionProgress'
        ));
    }

    public function update(Request $request, ExaminationSubject $examinationSubject, ExamTeacherAssignmentService $teacherAssignments)
    {
        $component = $this->component($request, $examinationSubject);
        $examinationSubject->load('examination.academicYear');
        $sectionIds = $this->selectedSectionIds($request, $examinationSubject, $teacherAssignments, $component);
        $this->ensureTeacherSubmissionIsOpen($examinationSubject, $request->user()->id, $sectionIds->first());
        abort_if($examinationSubject->examination->is_locked, 422, 'Marks are locked because this exam is completed or published.');
        if ($message = $this->entryWindowMessage($examinationSubject, $request)) {
            throw ValidationException::withMessages(['exam' => $message]);
        }
        $rows = $request->validate([
            'component' => ['required', 'in:theory,practical'],
            'marks' => ['nullable', 'array'], 'marks.*' => ['array'],
            'marks.*.score' => ['nullable', 'numeric', 'min:0'],
            'marks.*.is_absent' => ['nullable', 'boolean'],
            'marks.*.remarks' => ['nullable', 'string', 'max:255'],
        ])['marks'] ?? [];
        $this->saveRows($rows, $examinationSubject, $sectionIds, $component);
        return back()->with('success', ucfirst($component).' marks saved for '.count($rows).' student row(s).');
    }

    public function autosave(Request $request, ExaminationSubject $examinationSubject, ExamTeacherAssignmentService $teacherAssignments)
    {
        $component = $this->component($request, $examinationSubject);
        $examinationSubject->load('examination.academicYear');
        $sectionIds = $this->selectedSectionIds($request, $examinationSubject, $teacherAssignments, $component);
        $this->ensureTeacherSubmissionIsOpen($examinationSubject, $request->user()->id, $sectionIds->first());
        abort_if($examinationSubject->examination->is_locked, 422, 'Marks are locked because this exam is completed or published.');
        if ($message = $this->entryWindowMessage($examinationSubject, $request)) {
            throw ValidationException::withMessages(['exam' => $message]);
        }
        $data = $request->validate([
            'component' => ['required', 'in:theory,practical'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'is_absent' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);
        $this->saveRows([$data['student_id'] => $data], $examinationSubject, $sectionIds, $component);

        return response()->json(['saved' => true, 'saved_at' => now()->format('h:i:s A')]);
    }

    public function submit(Request $request, ExaminationSubject $examinationSubject, ExamTeacherAssignmentService $teacherAssignments)
    {
        $examinationSubject->load(['examination.academicYear', 'examination.sections']);
        abort_if($examinationSubject->examination->is_locked, 422, 'This examination is already locked.');
        if ($message = $this->entryWindowMessage($examinationSubject, $request)) {
            throw ValidationException::withMessages(['submission' => $message]);
        }

        $theorySections = (float) $examinationSubject->theory_full_marks > 0
            ? $teacherAssignments->sectionIdsFor($examinationSubject, $request->user(), 'theory') : collect();
        $practicalSections = (float) $examinationSubject->practical_full_marks > 0
            ? $teacherAssignments->sectionIdsFor($examinationSubject, $request->user(), 'practical') : collect();
        $allowedSections = $theorySections->merge($practicalSections)->unique()->values();
        abort_if($allowedSections->isEmpty(), 403, 'This subject is not assigned to you.');
        $section = $this->selectedSection($request, $examinationSubject->examination->sections->whereIn('id', $allowedSections));
        abort_unless($section, 422, 'Choose a section before submitting marks.');
        $this->ensureTeacherSubmissionIsOpen($examinationSubject, $request->user()->id, $section->id);
        $sectionIds = collect([(int) $section->id]);
        $progress = $this->submissionProgress($examinationSubject,
            $theorySections->intersect($sectionIds)->values(), $practicalSections->intersect($sectionIds)->values());
        $missing = collect($progress)->sum('missing');
        if ($missing > 0) {
            $detail = collect($progress)->filter(fn ($row) => $row['missing'] > 0)
                ->map(fn ($row, $name) => ucfirst($name).': '.$row['missing'].' missing')->implode(', ');
            throw ValidationException::withMessages(['submission' => 'Complete every student before locking. '.$detail.'.']);
        }

        ExaminationMarkSubmission::updateOrCreate(
            ['examination_subject_id' => $examinationSubject->id, 'teacher_id' => $request->user()->id, 'section_id' => $section->id],
            ['section_ids' => [$section->id],
                'locked_at' => now(), 'unlock_requested_at' => null, 'unlock_reason' => null,
                'unlocked_at' => null, 'unlocked_by' => null]
        );

        return redirect()->route('admin.examinations.marks.edit', [$examinationSubject, 'component' => $request->input('component', 'theory'), 'section' => $section->id])
            ->with('success', 'Marks for '.$section->name.' were submitted and locked. Request an admin unlock for corrections.');
    }

    public function requestUnlock(Request $request, ExaminationMarkSubmission $markSubmission)
    {
        abort_unless((int) $markSubmission->teacher_id === (int) $request->user()->id, 403);
        abort_unless($markSubmission->is_locked, 422, 'These marks are not locked.');
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $markSubmission->update(['unlock_requested_at' => now(), 'unlock_reason' => $data['reason']]);

        return back()->with('success', 'Correction request sent to the examination admin.');
    }

    public function unlock(Request $request, ExaminationMarkSubmission $markSubmission)
    {
        abort_unless($request->user()->canAccess('examinations.manage'), 403);
        $markSubmission->update([
            'locked_at' => null, 'unlock_requested_at' => null,
            'unlocked_at' => now(), 'unlocked_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Marks unlocked for '.$markSubmission->teacher?->name.'.');
    }

    private function saveRows(array $rows, ExaminationSubject $examinationSubject, $sectionIds, string $component): void
    {
        $eligibleIds = $this->eligibleStudents($examinationSubject, $sectionIds)->pluck('id')->map(fn ($id) => (int) $id);
        $marksColumn = $component.'_marks';
        $absentColumn = $component.'_is_absent';
        $fullMarks = (float) $examinationSubject->{$component.'_full_marks'};
        $attendanceAbsentIds = ExaminationAttendance::where('examination_subject_id', $examinationSubject->id)
            ->where('status', 'absent')->whereIn('student_id', $eligibleIds)->pluck('student_id')->map(fn ($id) => (int) $id);

        DB::transaction(function () use ($rows, $eligibleIds, $attendanceAbsentIds, $examinationSubject, $component, $marksColumn, $absentColumn, $fullMarks) {
            foreach ($rows as $studentId => $row) {
                $studentId = (int) $studentId;
                if (! $eligibleIds->contains($studentId)) throw ValidationException::withMessages(['marks' => 'A submitted student is outside this subject and section.']);
                $absent = $attendanceAbsentIds->contains($studentId) || filter_var($row['is_absent'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $score = filled($row['score'] ?? null) ? (float) $row['score'] : null;
                if ($score !== null && $score > $fullMarks) {
                    $passMarks = (float) $examinationSubject->{$component.'_pass_marks'};
                    throw ValidationException::withMessages(["marks.{$studentId}.score" => ucfirst($component).' marks cannot exceed full marks ('.number_format($fullMarks, 2).'). Pass marks: '.number_format($passMarks, 2).'.']);
                }

                $mark = ExaminationMark::firstOrNew(['examination_subject_id' => $examinationSubject->id, 'student_id' => $studentId]);
                $mark->{$marksColumn} = $absent ? null : $score;
                $mark->{$absentColumn} = $absent;
                $mark->remarks = array_key_exists('remarks', $row) ? $row['remarks'] : $mark->remarks;
                $mark->entered_by = auth()->id();
                $mark->submitted_at = now();
                $otherComponent = $component === 'theory' ? 'practical' : 'theory';
                $theoryAbsent = (float) $examinationSubject->theory_full_marks <= 0 || (bool) $mark->theory_is_absent;
                $practicalAbsent = (float) $examinationSubject->practical_full_marks <= 0 || (bool) $mark->practical_is_absent;
                $mark->is_absent = $theoryAbsent && $practicalAbsent;

                if (! $absent && $score === null && blank($mark->remarks)
                    && $mark->{$otherComponent.'_marks'} === null && ! $mark->{$otherComponent.'_is_absent'}) {
                    if ($mark->exists) $mark->delete();
                    continue;
                }
                $mark->save();
            }
        });
    }

    /** Attendance is authoritative: an admin-marked absence applies to both components. */
    private function syncAttendanceAbsences(ExaminationSubject $subject, $studentIds): void
    {
        foreach ($studentIds as $studentId) {
            ExaminationMark::updateOrCreate(
                ['examination_subject_id' => $subject->id, 'student_id' => $studentId],
                [
                    'theory_marks' => null, 'practical_marks' => null,
                    'theory_is_absent' => (float) $subject->theory_full_marks > 0,
                    'practical_is_absent' => (float) $subject->practical_full_marks > 0,
                    'is_absent' => true, 'submitted_at' => now(),
                ]
            );
        }
    }

    private function ensureTeacherSubmissionIsOpen(ExaminationSubject $subject, int $teacherId, int $sectionId): void
    {
        $locked = ExaminationMarkSubmission::where('examination_subject_id', $subject->id)
            ->where('teacher_id', $teacherId)->where('section_id', $sectionId)->whereNotNull('locked_at')->exists();
        abort_if($locked, 423, 'Marks for this section are submitted and locked. Request an admin unlock before making corrections.');
    }

    private function submissionProgress(ExaminationSubject $subject, $theorySectionIds, $practicalSectionIds): array
    {
        $progress = [];
        foreach (['theory' => $theorySectionIds, 'practical' => $practicalSectionIds] as $component => $sectionIds) {
            if ($sectionIds->isEmpty()) continue;
            $eligibleIds = $this->eligibleStudents($subject, $sectionIds)->pluck('id');
            $entered = ExaminationMark::where('examination_subject_id', $subject->id)
                ->whereIn('student_id', $eligibleIds)
                ->where(fn ($query) => $query->whereNotNull($component.'_marks')->orWhere($component.'_is_absent', true))
                ->count();
            $progress[$component] = [
                'expected' => $eligibleIds->count(), 'entered' => $entered,
                'missing' => max(0, $eligibleIds->count() - $entered),
            ];
        }
        return $progress;
    }

    private function eligibleStudents(ExaminationSubject $subject, $allowedSectionIds)
    {
        $subject->loadMissing(['examination.academicYear', 'examination.sections', 'offering']);
        $sectionIds = $subject->examination->sections->pluck('id')->intersect($allowedSectionIds)->values();
        $sectionNames = $subject->examination->sections->whereIn('id', $sectionIds)->pluck('name');
        return Student::query()->where('member_type', 'student')
            ->whereHas('subjectEnrollments', fn ($q) => $q->where('subject_offering_id', $subject->subject_offering_id)->where('academic_year', $subject->examination->academicYear->name))
            ->where(fn ($q) => $q->whereIn('section_id', $sectionIds)->orWhere(fn ($q) => $q->whereNull('section_id')->whereIn('section', $sectionNames)))
            ->orderBy('first_name')->orderBy('middle_name')->orderBy('last_name')->orderBy('id');
    }

    /** Restrict a mark-entry request to exactly one section visible to the user. */
    private function selectedSectionIds(Request $request, ExaminationSubject $subject, ExamTeacherAssignmentService $teacherAssignments, string $component)
    {
        $subject->loadMissing('examination.sections');
        $allowedIds = $teacherAssignments->sectionIdsFor($subject, $request->user(), $component);
        abort_if($allowedIds->isEmpty(), 403, 'This subject and section are not assigned to you in Routine Builder.');
        $section = $this->selectedSection($request, $subject->examination->sections->whereIn('id', $allowedIds));
        abort_unless($section, 422, 'Choose a section before entering marks.');

        return collect([(int) $section->id]);
    }

    private function selectedSection(Request $request, $sections): ?object
    {
        if (! $request->filled('section')) return null;
        $section = $sections->firstWhere('id', $request->integer('section'));
        abort_unless($section, 403, 'That section is not available for this subject.');

        return $section;
    }

    private function component(Request $request, ExaminationSubject $subject): string
    {
        $component = $request->input('component', $request->query('component', 'theory'));
        if (! in_array($component, ['theory', 'practical'], true)) {
            throw ValidationException::withMessages(['component' => 'Choose theory or practical marks.']);
        }
        if ($component === 'practical' && (float) $subject->practical_full_marks <= 0) {
            throw ValidationException::withMessages(['component' => 'Practical marks are not configured for this subject.']);
        }

        return $component;
    }

    private function entryWindowMessage(ExaminationSubject $subject, Request $request): ?string
    {
        if ($request->user()->canAccess(['examinations.manage', 'examinations.marks.verify'])) return null;
        $exam = $subject->examination;
        if ($exam->status !== 'ongoing') return 'Marks cannot be entered yet. An examination manager must change this exam from Draft to Ongoing.';
        $today = now()->startOfDay();
        if ($exam->starts_on && $today->lt($exam->starts_on->copy()->startOfDay())) {
            return 'Mark entry opens on '.$exam->starts_on->format('M d, Y').'.';
        }
        if ($exam->ends_on && $today->gt($exam->ends_on->copy()->endOfDay())) {
            return 'Mark entry closed on '.$exam->ends_on->format('M d, Y').'. Ask an examination manager to update the exam dates.';
        }
        return null;
    }

}
