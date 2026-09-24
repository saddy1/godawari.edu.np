<?php

namespace App\Services;

use App\Models\Card\Student;
use App\Models\TeachingLearning\RoutineLesson;
use Illuminate\Support\Facades\DB;

class RoutineLessonRosterService
{
    // The "which students belong to this lesson's group(s)" roster is stored as a
    // snapshot (routine_lesson_group_students), written once when the lesson is
    // saved in the Routine Builder. Nothing re-runs it afterward, so a student
    // added to the section — or moved into it — later in HR never appears in
    // attendance or the builder's roster panel. Every read path must call this
    // first to recompute the buckets from CURRENT section/lab-group/subject
    // enrollment data and refresh the snapshot before reading it.
    public function sync(RoutineLesson $lesson): void
    {
        $lesson->loadMissing('plan.academicYear', 'section', 'groups.offering');
        $plan = $lesson->plan;
        $offeringIds = $lesson->groups->pluck('subject_offering_id')->unique()->values();
        $section = $lesson->section;
        // Compulsory subjects apply to every student in the section automatically —
        // no per-student enrollment record is needed, so a newly added or moved-in
        // student is included right away. Electives still require the explicit
        // StudentSubjectEnrollment made via Subject Assignments.
        $electiveOfferingIds = $lesson->groups->filter(fn ($group) => (bool) $group->offering?->is_elective)
            ->pluck('subject_offering_id')->unique()->values();
        $isEligibleFor = fn ($student, $offeringId) => ! $electiveOfferingIds->contains($offeringId)
            || $student->subjectEnrollments->contains('subject_offering_id', $offeringId);

        $students = Student::with(['subjectEnrollments' => fn ($query) => $query
                ->where('academic_year', $plan->academicYear->name)
                ->whereIn('subject_offering_id', $electiveOfferingIds), 'labGroup'])
            ->where('member_type', 'student')
            ->where(fn ($query) => $query->where('section_id', $section->id)
                ->orWhere(fn ($query) => $query->whereNull('section_id')->where('section', $section->name)))
            ->orderByRaw('roll_number IS NULL')->orderBy('roll_number')->orderBy('first_name')->get()
            ->filter(fn ($student) => $offeringIds->contains(fn ($offeringId) => $isEligibleFor($student, $offeringId)))->values();

        $buckets = $lesson->groups->mapWithKeys(fn ($group) => [$group->id => collect()]);
        if ($lesson->mode === 'single' || $lesson->groups->count() === 1) {
            // A practical lesson with only one lab group (the section has
            // just one Lab Group defined) has nowhere else for anyone to go —
            // every eligible student attends that one lab.
            $group = $lesson->groups->first();
            $buckets[$group->id] = $students->filter(fn ($student) => $isEligibleFor($student, $group->subject_offering_id))->values();
        } elseif ($offeringIds->count() === 1 && $lesson->groups->count() === 2) {
            // group_label holds the real Lab Group's name (e.g. "A"), except
            // on lessons saved before this used real names, which stored the
            // literal "Group A"/"Group B" — accept either form here.
            $labelFor = fn ($group) => preg_replace('/^Group\s+/i', '', (string) $group->group_label);
            [$groupOne, $groupTwo] = $lesson->groups->values()->all();
            $nameOne = $labelFor($groupOne);
            $nameTwo = $labelFor($groupTwo);
            $assignedOne = $students->filter(fn ($student) => $student->labGroup?->name === $nameOne)->values();
            $assignedTwo = $students->filter(fn ($student) => $student->labGroup?->name === $nameTwo)->values();

            // Only trust declared Lab Groups when the section actually has
            // real students in both groups — e.g. if a section only ever
            // defined one group, every unassigned student would otherwise
            // get dumped entirely into the always-empty second group below.
            if (filled($nameOne) && filled($nameTwo) && $assignedOne->isNotEmpty() && $assignedTwo->isNotEmpty()) {
                // Balance anyone left unassigned across whichever group is
                // currently smaller.
                $unassigned = $students->filter(fn ($student) => blank($student->labGroup?->name))->values();

                foreach ($unassigned as $student) {
                    $assignedOne->count() <= $assignedTwo->count() ? $assignedOne->push($student) : $assignedTwo->push($student);
                }

                $buckets[$groupOne->id] = $assignedOne;
                $buckets[$groupTwo->id] = $assignedTwo;
            } else {
                $half = (int) ceil($students->count() / 2);
                $buckets[$groupOne->id] = $students->take($half)->values();
                $buckets[$groupTwo->id] = $students->slice($half)->values();
            }
        } else {
            foreach ($students as $student) {
                $eligible = $lesson->groups->filter(fn ($group) => $isEligibleFor($student, $group->subject_offering_id));
                if ($eligible->isEmpty()) continue;
                $group = $eligible->sortBy(fn ($candidate) => $buckets[$candidate->id]->count())->first();
                $buckets[$group->id]->push($student);
            }
        }

        $now = now();
        $rows = $buckets->flatMap(fn ($assigned, $groupId) => $assigned->map(fn ($student) => [
            'routine_lesson_id' => $lesson->id,
            'routine_lesson_group_id' => $groupId,
            'student_id' => $student->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]))->values()->all();

        DB::table('routine_lesson_group_students')->where('routine_lesson_id', $lesson->id)->delete();
        if ($rows) DB::table('routine_lesson_group_students')->insert($rows);
    }

    public function syncMany(iterable $lessons): void
    {
        foreach ($lessons as $lesson) {
            $this->sync($lesson);
        }
    }
}
