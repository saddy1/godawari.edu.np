<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Card\Student;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationMark;
use App\Models\Examination\ExaminationSymbolNumber;
use App\Services\ExamRosterService;
use App\Services\GradeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class MarksheetController extends Controller
{
    public function print(Examination $examination, ExamRosterService $roster, GradeService $grades)
    {
        return view('examinations.marksheets.print', [
            'examination' => $examination,
            'roster' => $this->rosterWithGrades($examination, $roster, $grades),
        ]);
    }

    public function downloadAll(Examination $examination, ExamRosterService $roster, GradeService $grades)
    {
        $pdf = Pdf::loadView('examinations.marksheets.pdf', [
            'examination' => $examination,
            'roster' => $this->rosterWithGrades($examination, $roster, $grades),
        ])->setPaper('a4', 'portrait');

        return $pdf->download(str($examination->name)->slug('_').'_marksheets.pdf');
    }

    public function downloadOne(Examination $examination, Student $student, ExamRosterService $roster, GradeService $grades)
    {
        $this->eagerLoadBranding($examination);
        $symbolNo = ExaminationSymbolNumber::where('examination_id', $examination->id)->where('student_id', $student->id)->value('symbol_no');
        abort_if(! $symbolNo, 404, 'Assign a symbol number to this student first.');
        $row = $this->buildRow($examination, $student, $symbolNo, $roster, $grades);

        $pdf = Pdf::loadView('examinations.marksheets.pdf', [
            'examination' => $examination,
            'roster' => collect([$row]),
        ])->setPaper('a4', 'portrait');

        return $pdf->download(str($student->full_name)->slug('_').'_marksheet.pdf');
    }

    private function eagerLoadBranding(Examination $examination): void
    {
        $examination->loadMissing(['organization.logoAsset', 'organization.signatureAsset', 'organization.stampAsset', 'academicYear']);
    }

    private function rosterWithGrades(Examination $examination, ExamRosterService $roster, GradeService $grades): Collection
    {
        $this->eagerLoadBranding($examination);
        $students = $roster->studentsForExam($examination);
        $symbolNumbers = ExaminationSymbolNumber::where('examination_id', $examination->id)->pluck('symbol_no', 'student_id');

        return $students->filter(fn ($student) => $symbolNumbers->has($student->id))
            ->map(fn ($student) => $this->buildRow($examination, $student, $symbolNumbers->get($student->id), $roster, $grades))
            ->values();
    }

    private function buildRow(Examination $examination, Student $student, int $symbolNo, ExamRosterService $roster, GradeService $grades): array
    {
        $subjects = $roster->subjectsForStudent($examination, $student);
        $marks = ExaminationMark::whereIn('examination_subject_id', $subjects->pluck('id'))
            ->where('student_id', $student->id)->get()->keyBy('examination_subject_id');

        $subjectRows = $subjects->map(function ($subject) use ($marks, $grades) {
            $mark = $marks->get($subject->id);
            $hasPractical = (float) $subject->practical_full_marks > 0;
            $totalFull = $subject->total_full_marks;
            $absent = $mark && ($mark->theory_is_absent || ($hasPractical && $mark->practical_is_absent));
            $graded = $mark && ! $absent && $totalFull > 0
                && ($mark->theory_marks !== null || $mark->theory_is_absent)
                && (! $hasPractical || $mark->practical_marks !== null || $mark->practical_is_absent);

            $percentage = $graded ? round($mark->obtained_marks / $totalFull * 100, 2) : null;
            $gradeInfo = $absent ? ['grade' => 'Abs', 'point' => 0.00] : ($percentage !== null ? $grades->gradeFor($percentage) : null);
            $creditHour = (float) ($subject->offering->subject->credit_hours ?? 0);

            return [
                'code' => $subject->offering->subject->code,
                'name' => $subject->offering->subject->name,
                'label' => $hasPractical ? 'TH + IN' : 'TH',
                'credit_hour' => $creditHour,
                'grade' => $gradeInfo['grade'] ?? null,
                'point' => $gradeInfo['point'] ?? null,
                'remarks' => $mark?->remarks ?: ($absent ? 'Absent' : ($graded ? null : 'Incomplete')),
                'is_elective' => $subject->offering->is_elective,
                'countable' => $absent || $graded,
            ];
        });

        $mainSubjects = $subjectRows->where('is_elective', false)->values();
        $extraSubjects = $subjectRows->where('is_elective', true)->values();
        $gpaRows = $mainSubjects->where('countable', true)->map(fn ($row) => ['credit_hour' => $row['credit_hour'], 'point' => $row['point'] ?? 0]);
        $gpa = $gpaRows->isNotEmpty() ? $grades->gpaFor($gpaRows) : null;

        return [
            'student' => $student,
            'symbol_no' => $symbolNo,
            'department' => $subjects->first()?->offering?->department,
            'subjectRows' => $mainSubjects,
            'extraSubjects' => $extraSubjects,
            'gpa' => $gpa,
        ];
    }
}
