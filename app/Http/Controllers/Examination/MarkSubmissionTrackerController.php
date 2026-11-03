<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationMarkSubmission;
use App\Models\Examination\ExaminationSubject;
use App\Models\TeachingLearning\RoutineLessonGroup;
use Illuminate\Http\Request;

class MarkSubmissionTrackerController extends Controller
{
    /** Show every teacher's required mark submission for the selected examination. */
    public function index(Request $request)
    {
        $exams = Examination::with('academicYear')->latest('starts_on')->latest('id')->get();
        $exam = $exams->firstWhere('id', $request->integer('exam'))
            ?? $exams->firstWhere('status', 'ongoing')
            ?? $exams->first();

        $entries = collect();
        $unassignedCount = 0;

        if ($exam) {
            $subjects = ExaminationSubject::with(['offering.subject', 'offering.department'])
                ->where('examination_id', $exam->id)->get();
            $subjectsByOffering = $subjects->keyBy('subject_offering_id');
            $submissions = ExaminationMarkSubmission::whereIn('examination_subject_id', $subjects->pluck('id'))
                ->get()->keyBy(fn ($row) => $row->examination_subject_id.':'.$row->teacher_id.':'.$row->section_id);

            $groups = RoutineLessonGroup::query()
                ->whereIn('subject_offering_id', $subjectsByOffering->keys())
                ->whereHas('lesson', fn ($lessons) => $lessons->whereIn('section_id', $exam->sections()->pluck('sections.id'))
                    ->whereHas('plan', fn ($plans) => $plans->where('academic_year_id', $exam->academic_year_id)
                        ->where('organization_id', $exam->organization_id)))
                ->with(['lesson.section:id,name', 'teacher:id,name', 'teachers:id,name'])
                ->get();

            $entries = $groups->flatMap(function ($group) use ($subjectsByOffering, $submissions) {
                $subject = $subjectsByOffering->get($group->subject_offering_id);
                if (! $subject) return collect();

                $components = $group->lesson->mode === 'practical_split'
                    ? ((float) $subject->practical_full_marks > 0 ? ['practical'] : [])
                    : array_filter(['theory', (float) $subject->practical_full_marks > 0 ? 'practical' : null]);
                $teachers = $group->teachers->isNotEmpty() ? $group->teachers : collect([$group->teacher])->filter();

                return $teachers->map(function ($teacher) use ($subject, $group, $components, $submissions) {
                    $submission = $submissions->get($subject->id.':'.$teacher->id.':'.$group->lesson->section_id);
                    return (object) [
                        'subject' => $subject,
                        'teacher' => $teacher,
                        'section' => $group->lesson->section,
                        'components' => collect($components),
                        'submission' => $submission,
                    ];
                });
            })->unique(fn ($entry) => $entry->subject->id.':'.$entry->teacher->id.':'.$entry->section->id)
                ->sortBy(fn ($entry) => $entry->teacher->name.' '.$entry->subject->offering->subject->name.' '.$entry->section->name)
                ->values();

            $assignedPairs = $entries->map(fn ($entry) => $entry->subject->id.':'.$entry->section->id)->unique();
            $expectedPairs = $subjects->flatMap(fn ($subject) => $exam->sections
                ->filter(fn ($section) => (int) $section->department_id === (int) $subject->offering->department_id
                    && (! $subject->offering->group_name || $subject->offering->group_name === $section->group_name))
                ->map(fn ($section) => $subject->id.':'.$section->id))->unique();
            $unassignedCount = $expectedPairs->diff($assignedPairs)->count();
        }

        $status = $request->string('status')->toString();
        if ($status === 'submitted') $entries = $entries->filter(fn ($entry) => $entry->submission?->is_locked);
        if ($status === 'pending') $entries = $entries->filter(fn ($entry) => ! $entry->submission?->is_locked);

        $summary = [
            'submitted' => $entries->filter(fn ($entry) => $entry->submission?->is_locked)->count(),
            'pending' => $entries->filter(fn ($entry) => ! $entry->submission?->is_locked)->count(),
            'unassigned' => $unassignedCount,
        ];

        return view('examinations.mark-submissions.index', compact('exams', 'exam', 'entries', 'status', 'summary'));
    }
}
