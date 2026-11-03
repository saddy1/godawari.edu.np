<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationMarkSubmission;
use App\Models\Examination\ExaminationSubject;
use App\Models\Card\Organization;
use App\Models\TeachingLearning\RoutineLessonGroup;
use Illuminate\Http\Request;

class MarkSubmissionTrackerController extends Controller
{
    /** Show every teacher's required mark submission for the selected examination. */
    public function index(Request $request)
    {
        $organizationId = $request->integer('organization_id');
        $exams = Examination::with('academicYear')->when($organizationId, fn ($query) => $query->where('organization_id', $organizationId))
            ->latest('starts_on')->latest('id')->get();
        $exam = $exams->firstWhere('id', $request->integer('exam'))
            ?? $exams->firstWhere('status', 'ongoing')
            ?? $exams->first();

        $entries = collect();
        $unassignedCount = 0;
        $faculties = $exam?->departments ?? collect();
        $facultyId = $request->integer('faculty_id');
        $sections = $exam?->sections->when($facultyId, fn ($items) => $items->where('department_id', $facultyId))->values() ?? collect();
        $sectionId = $request->integer('section_id');

        if ($exam) {
            $subjects = ExaminationSubject::with(['offering.subject', 'offering.department', 'marks.student'])
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
                    $sectionMarks = $subject->marks->filter(fn ($mark) => $mark->student
                        && ((int) $mark->student->section_id === (int) $group->lesson->section_id
                            || ($mark->student->section_id === null && $mark->student->section === $group->lesson->section->name)));
                    $marksEntered = collect($components)->contains(fn ($component) => $sectionMarks->contains(
                        fn ($mark) => $mark->{$component.'_marks'} !== null || $mark->{$component.'_is_absent'}
                    ));
                    return (object) [
                        'subject' => $subject,
                        'teacher' => $teacher,
                        'section' => $group->lesson->section,
                        'components' => collect($components),
                        'submission' => $submission,
                        'marks_entered' => $marksEntered,
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
            if ($facultyId) {
                $entries = $entries->filter(fn ($entry) => (int) $entry->subject->offering->department_id === $facultyId)->values();
                $facultySectionIds = $sections->pluck('id')->map(fn ($id) => (int) $id)->all();
                $expectedPairs = $expectedPairs->filter(fn ($pair) => in_array((int) substr(strrchr($pair, ':'), 1), $facultySectionIds, true));
            }
            if ($sectionId) {
                $entries = $entries->filter(fn ($entry) => (int) $entry->section->id === $sectionId)->values();
                $expectedPairs = $expectedPairs->filter(fn ($pair) => str_ends_with($pair, ':'.$sectionId));
            }
            $unassignedCount = $expectedPairs->diff($assignedPairs)->count();
        }

        $summary = [
            'submitted' => $entries->filter(fn ($entry) => $entry->submission?->is_locked)->count(),
            'entered' => $entries->filter(fn ($entry) => ! $entry->submission?->is_locked && $entry->marks_entered)->count(),
            'pending' => $entries->filter(fn ($entry) => ! $entry->submission?->is_locked && ! $entry->marks_entered)->count(),
            'unassigned' => $unassignedCount,
        ];
        $status = $request->string('status')->toString();
        if ($status === 'submitted') $entries = $entries->filter(fn ($entry) => $entry->submission?->is_locked);
        if ($status === 'entered') $entries = $entries->filter(fn ($entry) => ! $entry->submission?->is_locked && $entry->marks_entered);
        if ($status === 'pending') $entries = $entries->filter(fn ($entry) => ! $entry->submission?->is_locked && ! $entry->marks_entered);

        $organizations = Organization::where('is_active', true)->orderBy('name')->get();
        return view('examinations.mark-submissions.index', compact('organizations', 'organizationId', 'exams', 'exam', 'faculties', 'facultyId', 'sections', 'sectionId', 'entries', 'status', 'summary'));
    }
}
