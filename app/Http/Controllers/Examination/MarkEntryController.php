<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Card\Student;
use App\Models\Examination\ExaminationMark;
use App\Models\Examination\ExaminationMarkSubmission;
use App\Models\Examination\ExaminationSubject;
use App\Services\ExamTeacherAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkEntryController extends Controller
{
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
        $sections = $examinationSubject->examination->sections->whereIn('id', $sectionIds)->values();
        $students = $this->eligibleStudents($examinationSubject, $sectionIds)->get();
        $marks = ExaminationMark::where('examination_subject_id', $examinationSubject->id)->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
        $markSubmission = ExaminationMarkSubmission::where('examination_subject_id', $examinationSubject->id)
            ->where('teacher_id', $request->user()->id)->first();
        $submissionProgress = $this->submissionProgress($examinationSubject, $theorySectionIds, $practicalSectionIds);
        return view('examinations.marks', compact(
            'examinationSubject', 'students', 'marks', 'component', 'sections',
            'theorySectionIds', 'practicalSectionIds', 'markSubmission', 'submissionProgress'
        ));
    }

    public function update(Request $request, ExaminationSubject $examinationSubject, ExamTeacherAssignmentService $teacherAssignments)
    {
        $component = $this->component($request, $examinationSubject);
        $examinationSubject->load('examination.academicYear');
        $this->ensureTeacherSubmissionIsOpen($examinationSubject, $request->user()->id);
        $sectionIds = $teacherAssignments->sectionIdsFor($examinationSubject, auth()->user(), $component);
        abort_if($sectionIds->isEmpty(), 403, 'This subject and section are not assigned to you in Routine Builder.');
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
        $this->ensureTeacherSubmissionIsOpen($examinationSubject, $request->user()->id);
        $sectionIds = $teacherAssignments->sectionIdsFor($examinationSubject, $request->user(), $component);
        abort_if($sectionIds->isEmpty(), 403, 'This subject and section are not assigned to you in Routine Builder.');
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
        $this->ensureTeacherSubmissionIsOpen($examinationSubject, $request->user()->id);
        abort_if($examinationSubject->examination->is_locked, 422, 'This examination is already locked.');
        if ($message = $this->entryWindowMessage($examinationSubject, $request)) {
            throw ValidationException::withMessages(['submission' => $message]);
        }

        $theorySections = (float) $examinationSubject->theory_full_marks > 0
            ? $teacherAssignments->sectionIdsFor($examinationSubject, $request->user(), 'theory') : collect();
        $practicalSections = (float) $examinationSubject->practical_full_marks > 0
            ? $teacherAssignments->sectionIdsFor($examinationSubject, $request->user(), 'practical') : collect();
        abort_if($theorySections->merge($practicalSections)->isEmpty(), 403, 'This subject is not assigned to you.');
        $progress = $this->submissionProgress($examinationSubject, $theorySections, $practicalSections);
        $missing = collect($progress)->sum('missing');
        if ($missing > 0) {
            $detail = collect($progress)->filter(fn ($row) => $row['missing'] > 0)
                ->map(fn ($row, $name) => ucfirst($name).': '.$row['missing'].' missing')->implode(', ');
            throw ValidationException::withMessages(['submission' => 'Complete every student before locking. '.$detail.'.']);
        }

        ExaminationMarkSubmission::updateOrCreate(
            ['examination_subject_id' => $examinationSubject->id, 'teacher_id' => $request->user()->id],
            ['section_ids' => $theorySections->merge($practicalSections)->unique()->values()->all(),
                'locked_at' => now(), 'unlock_requested_at' => null, 'unlock_reason' => null,
                'unlocked_at' => null, 'unlocked_by' => null]
        );

        return redirect()->route('admin.examinations.index', ['exam' => $examinationSubject->examination_id])
            ->with('success', 'All marks were submitted and locked. Request an admin unlock if a correction is needed.');
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

        DB::transaction(function () use ($rows, $eligibleIds, $examinationSubject, $component, $marksColumn, $absentColumn, $fullMarks) {
            foreach ($rows as $studentId => $row) {
                $studentId = (int) $studentId;
                if (! $eligibleIds->contains($studentId)) throw ValidationException::withMessages(['marks' => 'A submitted student is outside this subject and section.']);
                $absent = filter_var($row['is_absent'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $score = filled($row['score'] ?? null) ? (float) $row['score'] : null;
                if ($score !== null && $score > $fullMarks) throw ValidationException::withMessages(["marks.{$studentId}.score" => ucfirst($component).' marks must be within full marks.']);

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

    private function ensureTeacherSubmissionIsOpen(ExaminationSubject $subject, int $teacherId): void
    {
        $locked = ExaminationMarkSubmission::where('examination_subject_id', $subject->id)
            ->where('teacher_id', $teacherId)->whereNotNull('locked_at')->exists();
        abort_if($locked, 423, 'Your marks are submitted and locked. Request an admin unlock before making corrections.');
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
            ->orderByRaw('roll_number IS NULL')->orderBy('roll_number')->orderBy('first_name');
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
