<?php

namespace App\Services;

use App\Models\Card\Student;
use App\Models\Card\StudentSubjectEnrollment;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationMark;
use Illuminate\Support\Collection;

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

        return $this->buildResult($examination, $student, $symbolNo, $subjects, $marks);
    }

    /**
     * Same grading as resultFor(), but for an entire roster in 2 queries
     * total instead of 2 queries PER student — resultFor() in a loop over
     * 1,500+ students (the marks ledger's original approach) was 3,000+
     * queries and several seconds on its own.
     */
    public function resultsForRoster(Examination $examination, Collection $students, Collection $symbolNumbers): Collection
    {
        $examination->loadMissing(['academicYear', 'subjects.offering.subject']);
        $studentIds = $students->pluck('id');
        $subjectsByOffering = $examination->subjects->groupBy('subject_offering_id');

        $offeringIdsByStudent = StudentSubjectEnrollment::query()
            ->where('academic_year', $examination->academicYear->name)
            ->whereIn('student_id', $studentIds)
            ->whereIn('subject_offering_id', $subjectsByOffering->keys())
            ->get(['student_id', 'subject_offering_id'])
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->pluck('subject_offering_id')->unique());

        $marksByStudent = ExaminationMark::whereIn('examination_subject_id', $examination->subjects->pluck('id'))
            ->whereIn('student_id', $studentIds)
            ->get()->groupBy('student_id');

        return $students->map(function ($student) use ($examination, $offeringIdsByStudent, $marksByStudent, $subjectsByOffering, $symbolNumbers) {
            $offeringIds = $offeringIdsByStudent->get($student->id, collect());
            $subjects = $offeringIds->flatMap(fn ($offeringId) => $subjectsByOffering->get($offeringId, collect()))
                ->sortBy(fn ($subject) => $subject->offering->subject->name)->values();
            $marks = $marksByStudent->get($student->id, collect())->keyBy('examination_subject_id');

            return $this->buildResult($examination, $student, (int) ($symbolNumbers->get($student->id) ?: 0), $subjects, $marks);
        });
    }

    private function buildResult(Examination $examination, Student $student, int $symbolNo, Collection $subjects, Collection $marks): array
    {
        $subjectRows = $subjects->flatMap(function ($subject) use ($marks, $examination) {
            $mark = $marks->get($subject->id);
            $model = $subject->offering->subject;
            $components = ['theory' => ['TH', $model->code, (float) $subject->theory_full_marks]];
            if ($examination->practical_enabled && (float) $subject->practical_full_marks > 0) {
                // The NEB grade-sheet calls this assessment component Internal (IN).
                $components['practical'] = ['IN', $model->practical_code ?: $model->code, (float) $subject->practical_full_marks];
            }
            $totalFull = array_sum(array_column($components, 2));
            $componentResults = collect($components)->map(function ($component, $key) use ($mark, $subject) {
                [, , $full] = $component;
                $passMarks = (float) $subject->{$key.'_pass_marks'};
                $absent = (bool) $mark?->{$key.'_is_absent'};
                $obtained = $mark?->{$key.'_marks'};
                $graded = ! $absent && $obtained !== null;
                // A component below its OWN configured pass mark is a fail —
                // even if the raw percentage would otherwise land on a
                // passing-looking letter (e.g. 26/75 is "D" territory on the
                // universal scale but still fails a 27-mark pass threshold).
                $clearedPassMark = $graded && (float) $obtained >= $passMarks;

                return [
                    'absent' => $absent,
                    'graded' => $graded,
                    'cleared_pass_mark' => $clearedPassMark,
                    'obtained' => $obtained,
                    'full' => $full,
                    'grade' => $absent ? ['grade' => 'Abs', 'point' => null]
                        : ($graded ? ($clearedPassMark ? $this->grades->gradeFor(round((float) $obtained / $full * 100, 2)) : ['grade' => 'NG', 'point' => null]) : null),
                ];
            });
            $allGraded = $componentResults->every(fn ($result) => $result['graded']);
            $anyComponentFailed = $componentResults->contains(fn ($result) => $result['graded'] && ! $result['cleared_pass_mark']);
            // NEB defines final grade as the weighted result of Theory and Internal —
            // but failing either component on its own pass mark fails the subject
            // outright, regardless of what the blended percentage looks like.
            $finalGrade = $allGraded
                ? ($anyComponentFailed ? 'NG' : $this->grades->gradeFor(round($componentResults->sum('obtained') / $totalFull * 100, 2))['grade'])
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
                    'cleared_pass_mark' => $result['cleared_pass_mark'],
                    'obtained' => $result['obtained'], 'full' => $full, 'absent' => $result['absent'],
                ];
            }
            return $rows;
        })->values();

        // An NEB GPA is issued only when every component is graded, credit-bearing,
        // graded (not NG), and actually cleared its own pass mark. Never turn
        // absence, NG, or an under-pass-mark score into a numeric GPA.
        $anySubjectFailed = $subjectRows->contains(fn ($row) => $row['countable'] && ! $row['cleared_pass_mark']);
        $gpa = $subjectRows->isNotEmpty() && ! $anySubjectFailed
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
            'overall_grade' => $percentage === null ? null : ($anySubjectFailed ? 'NG' : $this->grades->gradeFor($percentage)['grade']),
        ];
    }
}
