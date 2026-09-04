<?php

namespace App\Services;

use App\Models\TeachingLearning\RoutineLesson;
use App\Models\TeachingLearning\RoutinePeriod;
use App\Models\TeachingLearning\RoutinePlan;
use App\Models\TeachingLearning\RoutineRoom;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RoutineCollisionService
{
    public function ensureAvailable(RoutinePlan $plan, int $sectionId, string $day, RoutinePeriod $startPeriod, RoutinePeriod $endPeriod, Collection $groups, ?int $exceptLessonId = null): void
    {
        $teacherIds = $groups->pluck('teacher_ids')->flatten()->map(fn ($id) => (int) $id);
        if ($teacherIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['groups' => 'One teacher cannot handle both parallel practical groups.']);
        }
        $roomIds = $groups->pluck('routine_room_id')->filter()->map(fn ($id) => (int) $id);
        if ($roomIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['groups' => 'Parallel practical groups must use different rooms or laboratories.']);
        }

        $overlapping = RoutineLesson::query()
            ->with(['plan.department', 'section', 'period', 'endPeriod', 'groups.teacher', 'groups.teachers', 'groups.room'])
            ->where('day_of_week', $day)
            ->when($exceptLessonId, fn ($query) => $query->whereKeyNot($exceptLessonId))
            ->whereHas('plan', fn ($query) => $query
                ->where('academic_year_id', $plan->academic_year_id)
                ->where('status', '!=', 'archived'))
            ->whereHas('period', fn ($query) => $query
                ->where('starts_at', '<', $endPeriod->ends_at))
            ->whereHas('endPeriod', fn ($query) => $query
                ->where('ends_at', '>', $startPeriod->starts_at))
            ->get();

        if ($conflict = $overlapping->firstWhere('section_id', $sectionId)) {
            throw ValidationException::withMessages(['section_id' => "Section {$conflict->section->name} already has a class during this time."]);
        }

        foreach ($teacherIds as $teacherId) {
            if ($conflict = $overlapping->first(fn ($lesson) => $lesson->groups->contains(fn ($group) => (int) $group->teacher_id === $teacherId || $group->teachers->contains('id', $teacherId)))) {
                $teacher = User::find($teacherId)?->name ?? 'The selected teacher';
                throw ValidationException::withMessages(['groups' => "{$teacher} is already teaching {$conflict->plan->department->name} · {$conflict->section->name} during this time."]);
            }
        }
        foreach ($roomIds as $roomId) {
            if ($conflict = $overlapping->first(fn ($lesson) => $lesson->groups->contains('routine_room_id', $roomId))) {
                $room = RoutineRoom::find($roomId)?->name ?? 'The selected room';
                throw ValidationException::withMessages(['groups' => "{$room} is already occupied by {$conflict->plan->department->name} · {$conflict->section->name} during this time."]);
            }
        }
    }
}
