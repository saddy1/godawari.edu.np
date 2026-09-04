<?php

namespace App\Services;

use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationSubject;
use App\Models\TeachingLearning\RoutineLesson;
use App\Models\User;
use Illuminate\Support\Collection;

class ExamTeacherAssignmentService
{
    public function sectionIdsFor(ExaminationSubject $subject, User $user, string $component): Collection
    {
        $subject->loadMissing(['examination.sections']);
        if ($user->canAccess(['examinations.manage', 'examinations.marks.verify'])) {
            return $subject->examination->sections->pluck('id')->map(fn ($id) => (int) $id)->values();
        }

        return $this->lessonsFor($subject->examination, $subject->subject_offering_id, $component)
            ->whereHas('groups', fn ($groups) => $groups
                ->where('subject_offering_id', $subject->subject_offering_id)
                ->where(fn ($teachers) => $teachers->where('teacher_id', $user->id)
                    ->orWhereHas('teachers', fn ($query) => $query->whereKey($user->id))))
            ->pluck('section_id')->map(fn ($id) => (int) $id)->unique()->values();
    }

    public function teachesExam(Examination $exam, User $user): bool
    {
        if ($user->canAccess(['examinations.manage', 'examinations.reports', 'examinations.marks.verify'])) return true;

        $offeringIds = $exam->subjects()->pluck('subject_offering_id');
        if ($offeringIds->isEmpty()) return false;

        return $this->baseLessons($exam)
            ->whereHas('groups', fn ($groups) => $groups->whereIn('subject_offering_id', $offeringIds)
                ->where(fn ($teachers) => $teachers->where('teacher_id', $user->id)
                    ->orWhereHas('teachers', fn ($query) => $query->whereKey($user->id))))
            ->exists();
    }

    public function labelsFor(Examination $exam, int $offeringId, string $component): Collection
    {
        return $this->lessonsFor($exam, $offeringId, $component)
            ->with(['section:id,name', 'groups' => fn ($groups) => $groups
                ->where('subject_offering_id', $offeringId)->with('teachers:id,name')])
            ->get()->flatMap(function ($lesson) {
                return $lesson->groups->flatMap(function ($group) use ($lesson) {
                    $teachers = $group->teachers->isNotEmpty()
                        ? $group->teachers
                        : ($group->teacher_id ? collect([$group->teacher])->filter() : collect());
                    return $teachers->map(fn ($teacher) => $teacher->name.' · '.$lesson->section->name);
                });
            })->unique()->sort()->values();
    }

    public function labelsByOffering(Examination $exam, Collection $offeringIds): Collection
    {
        $labels = $offeringIds->mapWithKeys(fn ($id) => [(int) $id => [
            'theory' => collect(), 'practical' => collect(),
        ]]);
        if ($offeringIds->isEmpty()) return $labels;

        $this->baseLessons($exam)
            ->whereHas('groups', fn ($groups) => $groups->whereIn('subject_offering_id', $offeringIds))
            ->with(['section:id,name', 'groups' => fn ($groups) => $groups
                ->whereIn('subject_offering_id', $offeringIds)->with(['teacher:id,name', 'teachers:id,name'])])
            ->get()->each(function ($lesson) use ($labels) {
                $component = $lesson->mode === 'practical_split' ? 'practical' : 'theory';
                foreach ($lesson->groups as $group) {
                    $teachers = $group->teachers->isNotEmpty() ? $group->teachers : collect([$group->teacher])->filter();
                    foreach ($teachers as $teacher) {
                        $labels[$group->subject_offering_id][$component]->push($teacher->name.' · '.$lesson->section->name);
                        if ($component === 'theory') {
                            $labels[$group->subject_offering_id]['practical']->push($teacher->name.' · '.$lesson->section->name);
                        }
                    }
                }
            });

        return $labels->map(fn ($components) => collect($components)->map(fn ($items) => $items->unique()->sort()->values())->all());
    }

    private function lessonsFor(Examination $exam, int $offeringId, string $component)
    {
        return $this->baseLessons($exam)
            // The subject teacher is responsible for practical marks too.
            // A practical-only lab teacher still does not gain theory access.
            ->whereIn('mode', $component === 'practical'
                ? ['single', 'practical_split']
                : ['single'])
            ->whereHas('groups', fn ($groups) => $groups->where('subject_offering_id', $offeringId));
    }

    private function baseLessons(Examination $exam)
    {
        $exam->loadMissing(['sections', 'departments']);
        $departmentIds = $exam->departments->pluck('id');
        if ($departmentIds->isEmpty()) $departmentIds = collect([$exam->department_id]);
        return RoutineLesson::query()
            ->whereIn('section_id', $exam->sections->pluck('id'))
            ->whereHas('plan', fn ($plans) => $plans
                ->where('academic_year_id', $exam->academic_year_id)
                ->where('organization_id', $exam->organization_id)
                ->whereIn('department_id', $departmentIds));
    }
}
