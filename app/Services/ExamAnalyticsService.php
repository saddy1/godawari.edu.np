<?php

namespace App\Services;

use App\Models\Card\StudentSubjectEnrollment;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationMark;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ExamAnalyticsService
{
    /**
     * This recomputes a full pass/fail/topper/gender breakdown by looping
     * every mark against every student's expected subjects — on a large
     * exam (9,000+ enrollments, 9,000+ marks here) that's 2+ seconds of
     * pure PHP on every single page view, independent of query count.
     * Cached per exam, keyed off the latest mark/subject update so a
     * newly-saved mark busts it immediately — never serves stale results.
     */
    public function forExam(Examination $examination): array
    {
        $examination->loadMissing('subjects');
        $marksVersion = ExaminationMark::whereIn('examination_subject_id', $examination->subjects->pluck('id'))->max('updated_at');
        $subjectsVersion = $examination->subjects->max('updated_at');
        $cacheKey = "exam_analytics:{$examination->id}:{$marksVersion}:{$subjectsVersion}";

        return Cache::remember($cacheKey, now()->addHours(6), fn () => $this->computeForExam($examination));
    }

    /**
     * Only 9 scalar fields from this result are read anywhere in the
     * codebase (grepped: the 8-tile "Result overview" strip on the exam
     * page, plus `students`). The richer per-student/topper/gender/subject
     * breakdown this used to also build was dead computation — and, worse,
     * held ~1,500 full Student models nested in arrays, which is what blew
     * the cache's serialize() past the PHP memory limit. Dropped entirely;
     * if a future screen needs per-student exam analytics, it should query
     * for that specific need rather than revive this unbounded shape.
     */
    private function computeForExam(Examination $examination): array
    {
        $examination->loadMissing('academicYear', 'sections', 'subjects.marks');
        $marksByStudent = $examination->subjects->flatMap(function ($subject) {
            return $subject->marks->each->setRelation('examinationSubject', $subject);
        })->groupBy('student_id');
        $examSubjects = $examination->subjects->keyBy('subject_offering_id');
        $sectionIds = $examination->sections->pluck('id');
        $sectionNames = $examination->sections->pluck('name');
        $expectedByStudent = StudentSubjectEnrollment::query()
            ->where('academic_year', $examination->academicYear->name)
            ->whereIn('subject_offering_id', $examSubjects->keys())
            ->whereHas('student', fn ($q) => $q->where(fn ($q) => $q
                ->whereIn('section_id', $sectionIds)
                ->orWhere(fn ($q) => $q->whereNull('section_id')->whereIn('section', $sectionNames))))
            ->get(['student_id', 'subject_offering_id'])->groupBy('student_id');

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
                'percentage' => $full > 0 ? round($obtained / $full * 100, 2) : 0,
                'passed' => ! $incomplete && $failedSubjects->isEmpty(),
                'incomplete' => $incomplete,
                'absent' => $studentMarks->contains(fn ($mark) => $mark->theory_is_absent || $mark->practical_is_absent),
            ];
        })->values();

        $completed = $studentResults->where('incomplete', false);
        $passed = $completed->where('passed', true);
        $failed = $completed->where('passed', false);

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
