<?php

namespace App\Services;

use App\Models\Card\StudentSubjectEnrollment;
use App\Models\Examination\Examination;
use Illuminate\Support\Collection;

class ExamAnalyticsService
{
    public function forExam(Examination $examination): array
    {
        $examination->loadMissing('academicYear', 'sections', 'subjects.offering.subject', 'subjects.marks.student');
        $marks = $examination->subjects->flatMap(function ($subject) {
            return $subject->marks->each->setRelation('examinationSubject', $subject);
        });
        $marksByStudent = $marks->groupBy('student_id');
        $examSubjects = $examination->subjects->keyBy('subject_offering_id');
        $sectionIds = $examination->sections->pluck('id');
        $sectionNames = $examination->sections->pluck('name');
        $expectedByStudent = StudentSubjectEnrollment::with('student')
            ->where('academic_year', $examination->academicYear->name)
            ->whereIn('subject_offering_id', $examSubjects->keys())
            ->whereHas('student', fn ($q) => $q->where(fn ($q) => $q
                ->whereIn('section_id', $sectionIds)
                ->orWhere(fn ($q) => $q->whereNull('section_id')->whereIn('section', $sectionNames))))
            ->get()->groupBy('student_id');

        $studentResults = $expectedByStudent->map(function (Collection $enrollments, $studentId) use ($marksByStudent, $examSubjects) {
            $studentMarks = $marksByStudent->get($studentId, collect());
            $expectedSubjects = $enrollments->pluck('subject_offering_id')->unique()
                ->map(fn ($offeringId) => $examSubjects->get($offeringId))->filter();
            $obtained = $studentMarks->sum(fn ($mark) => $mark->obtained_marks);
            $full = $expectedSubjects->sum(fn ($subject) => $subject->total_full_marks);
            $failedSubjects = $studentMarks->filter(fn ($mark) => $this->markFailed($mark));
            $incomplete = $studentMarks->count() < $expectedSubjects->count() || $studentMarks->contains(fn ($mark) =>
                ((float) $mark->examinationSubject->theory_full_marks > 0 && $mark->theory_marks === null && ! $mark->theory_is_absent)
                || ((float) $mark->examinationSubject->practical_full_marks > 0 && $mark->practical_marks === null && ! $mark->practical_is_absent));

            return [
                'student' => $enrollments->first()->student,
                'obtained' => $obtained,
                'full' => $full,
                'percentage' => $full > 0 ? round($obtained / $full * 100, 2) : 0,
                'passed' => ! $incomplete && $failedSubjects->isEmpty(),
                'incomplete' => $incomplete,
                'absent' => $studentMarks->contains(fn ($mark) => $mark->theory_is_absent || $mark->practical_is_absent),
                'failed_subjects' => $failedSubjects->count(),
            ];
        })->values();

        $completed = $studentResults->where('incomplete', false);
        $passed = $completed->where('passed', true);
        $failed = $completed->where('passed', false);
        $topper = $completed->sortByDesc('percentage')->first();

        $gender = collect(['male', 'female'])->mapWithKeys(function ($gender) use ($completed) {
            $rows = $completed->filter(fn ($row) => strtolower((string) $row['student']?->gender) === $gender);
            return [$gender => [
                'students' => $rows->count(),
                'average' => round((float) $rows->avg('percentage'), 2),
                'pass_rate' => $rows->count() ? round($rows->where('passed', true)->count() / $rows->count() * 100, 2) : 0,
                'topper' => $rows->sortByDesc('percentage')->first(),
            ]];
        });

        $subjectStats = $examination->subjects->map(function ($subject) {
            $entered = $subject->marks->filter(fn ($mark) => $mark->theory_is_absent || $mark->practical_is_absent || $mark->theory_marks !== null || $mark->practical_marks !== null);
            $failed = $entered->filter(fn ($mark) => $this->markFailed($mark));
            $percentages = $entered->map(fn ($mark) => $subject->total_full_marks > 0 ? $mark->obtained_marks / $subject->total_full_marks * 100 : 0);
            return [
                'subject' => $subject,
                'entered' => $entered->count(),
                'passed' => $entered->count() - $failed->count(),
                'failed' => $failed->count(),
                'pass_rate' => $entered->count() ? round(($entered->count() - $failed->count()) / $entered->count() * 100, 2) : 0,
                'average' => round((float) $percentages->avg(), 2),
                'highest' => round((float) $percentages->max(), 2),
            ];
        })->sortByDesc('failed')->values();

        return [
            'students' => $studentResults->count(),
            'completed' => $completed->count(),
            'passed' => $passed->count(),
            'failed' => $failed->count(),
            'incomplete' => $studentResults->where('incomplete', true)->count(),
            'absent' => $studentResults->where('absent', true)->count(),
            'pass_rate' => $completed->count() ? round($passed->count() / $completed->count() * 100, 2) : 0,
            'fail_rate' => $completed->count() ? round($failed->count() / $completed->count() * 100, 2) : 0,
            'average' => round((float) $completed->avg('percentage'), 2),
            'topper' => $topper,
            'gender' => $gender,
            'subject_stats' => $subjectStats,
            'max_failed_subject' => $subjectStats->firstWhere('failed', $subjectStats->max('failed')),
            'student_results' => $studentResults->sortByDesc('percentage')->values(),
        ];
    }

    private function markFailed($mark): bool
    {
        $subject = $mark->examinationSubject;
        if ((float) $subject->theory_full_marks > 0 && ($mark->theory_is_absent || $mark->theory_marks === null || (float) $mark->theory_marks < (float) $subject->theory_pass_marks)) return true;
        return (float) $subject->practical_full_marks > 0
            && ($mark->practical_is_absent || $mark->practical_marks === null || (float) $mark->practical_marks < (float) $subject->practical_pass_marks);
    }
}
