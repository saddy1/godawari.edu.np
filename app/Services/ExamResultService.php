<?php

namespace App\Services;

use App\Models\Card\Student;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationMark;

class ExamResultService
{
    public function __construct(private ExamRosterService $roster, private GradeService $grades)
    {
    }

    // Shared by the marksheet/grade-sheet builder and the marks ledger export —
    // both need the same per-subject grading, just displayed differently.
    public function resultFor(Examination $examination, Student $student, int $symbolNo): array
    {
        $subjects = $this->roster->subjectsForStudent($examination, $student);
        $marks = ExaminationMark::whereIn('examination_subject_id', $subjects->pluck('id'))
            ->where('student_id', $student->id)->get()->keyBy('examination_subject_id');

        $subjectRows = $subjects->flatMap(function ($subject) use ($marks, $examination) {
            $mark = $marks->get($subject->id);
            $model = $subject->offering->subject;
            $components = ['theory' => ['TH', $model->code, (float) $subject->theory_full_marks]];
            if ($examination->practical_enabled && (float) $subject->practical_full_marks > 0) {
                // The NEB grade-sheet calls this assessment component Internal (IN).
                $components['practical'] = ['IN', $model->practical_code ?: $model->code, (float) $subject->practical_full_marks];
            }
            $totalFull = array_sum(array_column($components, 2));
            $componentResults = collect($components)->map(function ($component, $key) use ($mark) {
                [, , $full] = $component;
                $absent = (bool) $mark?->{$key.'_is_absent'};
                $obtained = $mark?->{$key.'_marks'};
                $graded = ! $absent && $obtained !== null;

                return [
                    'absent' => $absent,
                    'graded' => $graded,
                    'obtained' => $obtained,
                    'full' => $full,
                    'grade' => $absent ? ['grade' => 'Abs', 'point' => null]
                        : ($graded ? $this->grades->gradeFor(round((float) $obtained / $full * 100, 2)) : null),
                ];
            });
            // NEB defines final grade as the weighted result of Theory and Internal.
            $finalGrade = $componentResults->every(fn ($result) => $result['graded'])
                ? $this->grades->gradeFor(round($componentResults->sum('obtained') / $totalFull * 100, 2))['grade']
                : null;
            $rows = [];
            foreach ($components as $component => [$label, $code, $full]) {
                if ($full <= 0) continue;
                $result = $componentResults->get($component);
                $rows[] = [
                    'subject_id' => $subject->id, 'component' => $component,
                    'code' => $code, 'name' => $model->name, 'label' => $label,
                    // Prefer configured NEB TH/IN credits. Older subjects fall back
                    // to a proportional split of their legacy total credit value.
                    'credit_hour' => $component === 'theory' && $model->theory_credit_hours !== null
                        ? (float) $model->theory_credit_hours
                        : ($component === 'practical' && $model->practical_credit_hours !== null
                            ? (float) $model->practical_credit_hours
                            : (float) $model->credit_hours * $full / $totalFull),
                    'grade' => $result['grade']['grade'] ?? null, 'point' => $result['grade']['point'] ?? null,
                    'final_grade' => $component === 'theory' ? $finalGrade : null,
                    'remarks' => $mark?->remarks ?: ($result['absent'] ? 'Absent' : ($result['graded'] ? null : 'Incomplete')),
                    'countable' => $result['graded'],
                    'obtained' => $result['obtained'], 'full' => $full, 'absent' => $result['absent'],
                ];
            }
            return $rows;
        })->values();

        // An NEB GPA is issued only when every component is graded, credit-bearing,
        // and graded (not NG). Never turn absence or NG into a numeric GPA.
        $gpa = $subjectRows->isNotEmpty()
            && $subjectRows->every(fn ($row) => $row['countable'] && $row['credit_hour'] > 0 && $row['point'] !== null)
            ? $this->grades->gpaFor($subjectRows) : null;

        $totalObtained = $subjectRows->sum(fn ($row) => $row['obtained'] ?? 0);
        $totalFull = $subjectRows->sum('full');
        $complete = $subjectRows->isNotEmpty() && $subjectRows->every(fn ($row) => $row['countable'] || $row['absent']);
        $percentage = $complete && $totalFull > 0 ? round($totalObtained / $totalFull * 100, 2) : null;

        return [
            'student' => $student,
            'symbol_no' => $symbolNo,
            'department' => $subjects->first()?->offering?->department,
            'subjectRows' => $subjectRows,
            'extraSubjects' => collect(),
            'gpa' => $gpa,
            'total_obtained' => $totalObtained,
            'total_full' => $totalFull,
            'percentage' => $percentage,
            'complete' => $complete,
            'overall_grade' => $percentage !== null ? $this->grades->gradeFor($percentage)['grade'] : null,
        ];
    }
}
