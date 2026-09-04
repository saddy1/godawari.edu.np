<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Card\Student;
use App\Models\Examination\ExaminationMark;
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
        $sectionIds = $teacherAssignments->sectionIdsFor($examinationSubject, auth()->user(), $component);
        if ($sectionIds->isEmpty() && ! $request->has('component') && (float) $examinationSubject->practical_full_marks > 0) {
            $component = 'practical';
            $sectionIds = $teacherAssignments->sectionIdsFor($examinationSubject, auth()->user(), $component);
        }
        abort_if($sectionIds->isEmpty(), 403, 'This subject and section are not assigned to you in Routine Builder.');
        $this->ensureEntryWindow($examinationSubject, $request);
        $sections = $examinationSubject->examination->sections->whereIn('id', $sectionIds)->values();
        $students = $this->eligibleStudents($examinationSubject, $sectionIds)->get();
        $marks = ExaminationMark::where('examination_subject_id', $examinationSubject->id)->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
        return view('examinations.marks', compact('examinationSubject', 'students', 'marks', 'component', 'sections'));
    }

    public function update(Request $request, ExaminationSubject $examinationSubject, ExamTeacherAssignmentService $teacherAssignments)
    {
        $component = $this->component($request, $examinationSubject);
        $examinationSubject->load('examination.academicYear');
        $sectionIds = $teacherAssignments->sectionIdsFor($examinationSubject, auth()->user(), $component);
        abort_if($sectionIds->isEmpty(), 403, 'This subject and section are not assigned to you in Routine Builder.');
        abort_if($examinationSubject->examination->is_locked, 422, 'Marks are locked because this exam is completed or published.');
        $this->ensureEntryWindow($examinationSubject, $request);
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
        $sectionIds = $teacherAssignments->sectionIdsFor($examinationSubject, $request->user(), $component);
        abort_if($sectionIds->isEmpty(), 403, 'This subject and section are not assigned to you in Routine Builder.');
        abort_if($examinationSubject->examination->is_locked, 422, 'Marks are locked because this exam is completed or published.');
        $this->ensureEntryWindow($examinationSubject, $request);
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

    private function ensureEntryWindow(ExaminationSubject $subject, Request $request): void
    {
        if ($request->user()->canAccess(['examinations.manage', 'examinations.marks.verify'])) return;
        $exam = $subject->examination;
        abort_unless($exam->status === 'ongoing', 422, 'Mark entry opens when the exam is started.');
        $today = now()->startOfDay();
        abort_if($exam->starts_on && $today->lt($exam->starts_on->startOfDay()), 422, 'Mark entry has not opened yet.');
        abort_if($exam->ends_on && $today->gt($exam->ends_on->endOfDay()), 422, 'The mark-entry period has ended.');
    }

}
