<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Learning\LearningClass;
use App\Models\Learning\LearningSubject;
use App\Models\Learning\LearningTeacherClassMap;
use App\Models\User;
use App\Services\LearningClassSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminTeacherMapController extends Controller
{
    public function index(Request $request)
    {
        app(LearningClassSyncService::class)->syncFromCardDepartments();

        $classes = LearningClass::query()
            ->where('is_active', true)
            ->withCount('teacherMaps')
            ->with([
                'teachers' => fn ($q) => $q->orderBy('name'),
                'subjects' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $teachers = User::query()
            ->role('teacher')
            ->with([
                'assignedLearningClasses'  => fn ($q) => $q->orderBy('sort_order')->orderBy('name'),
                'assignedLearningSubjects' => fn ($q) => $q->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        return view('learning.admin.teacher-maps.index', compact('classes', 'teachers'));
    }

    public function updateAllocation(Request $request, User $teacher)
    {
        abort_unless($teacher->isTeacher(), 404);

        $data = $request->validate([
            'learning_class_id' => ['required', 'integer', 'exists:learning_classes,id'],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'distinct', 'exists:learning_subjects,id'],
        ]);

        $class = LearningClass::query()->where('is_active', true)->findOrFail($data['learning_class_id']);
        $subjectIds = collect($data['subject_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $validSubjects = LearningSubject::query()
            ->where('learning_class_id', $class->id)
            ->where('is_active', true)
            ->whereIn('id', $subjectIds)
            ->get();

        if ($validSubjects->count() !== $subjectIds->count()) {
            throw ValidationException::withMessages([
                'subject_ids' => 'Every selected subject must belong to the selected faculty/program.',
            ]);
        }

        DB::transaction(function () use ($request, $teacher, $class, $subjectIds) {
            $teacher->assignedLearningClasses()->syncWithoutDetaching([
                $class->id => ['assigned_by' => $request->user()->id],
            ]);

            $classSubjectIds = LearningSubject::query()
                ->where('learning_class_id', $class->id)
                ->pluck('id');

            $teacher->assignedLearningSubjects()->detach($classSubjectIds);
            $teacher->assignedLearningSubjects()->attach(
                $subjectIds->mapWithKeys(fn ($subjectId) => [
                    $subjectId => ['assigned_by' => $request->user()->id],
                ])->all()
            );
        });

        return back()->with(
            'success',
            "{$teacher->name} allocated to {$class->name}: {$validSubjects->pluck('name')->implode(', ')}."
        );
    }

    public function update(Request $request, User $teacher)
    {
        abort_unless($teacher->isTeacher(), 404);

        $data = $request->validate([
            'class_ids'   => ['array'],
            'class_ids.*' => ['integer', 'exists:learning_classes,id'],
        ]);

        $classIds = collect($data['class_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($request, $teacher, $classIds) {
            $removedClassIds = $teacher->assignedLearningClasses()
                ->pluck('learning_classes.id')
                ->diff($classIds);
            $removedSubjectIds = LearningSubject::query()
                ->whereIn('learning_class_id', $removedClassIds)
                ->pluck('id');

            $teacher->assignedLearningSubjects()->detach($removedSubjectIds);
            $teacher->assignedLearningClasses()->sync(
                $classIds->mapWithKeys(fn ($classId) => [
                    $classId => ['assigned_by' => $request->user()->id],
                ])->all()
            );
        });

        return back()->with('success', "Class access updated for {$teacher->name}.");
    }

    public function updateByClass(Request $request, LearningClass $class)
    {
        $data = $request->validate([
            'user_ids'   => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $userIds = collect($data['user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($request, $class, $userIds) {
            $removedUserIds = $class->teachers()->pluck('users.id')->diff($userIds);
            $classSubjectIds = $class->subjects()->pluck('id');

            if ($removedUserIds->isNotEmpty() && $classSubjectIds->isNotEmpty()) {
                DB::table('learning_teacher_subject_maps')
                    ->whereIn('user_id', $removedUserIds)
                    ->whereIn('learning_subject_id', $classSubjectIds)
                    ->delete();
            }

            $class->teachers()->sync(
                $userIds->mapWithKeys(fn ($userId) => [
                    $userId => ['assigned_by' => $request->user()->id],
                ])->all()
            );
        });

        return redirect()
            ->route('admin.learning.teacher-maps.index', ['tab' => $class->id])
            ->with('success', "Teacher assignments updated for {$class->name}.");
    }

    public function updateBySubject(Request $request, LearningSubject $subject)
    {
        $data = $request->validate([
            'user_ids'   => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $userIds = collect($data['user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($request, $subject, $userIds) {
            $subject->assignedTeachers()->sync(
                $userIds->mapWithKeys(fn ($userId) => [
                    $userId => ['assigned_by' => $request->user()->id],
                ])->all()
            );

            foreach ($userIds as $userId) {
                DB::table('learning_teacher_class_maps')->updateOrInsert(
                    ['user_id' => $userId, 'learning_class_id' => $subject->learning_class_id],
                    ['assigned_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        });

        return redirect()
            ->route('admin.learning.teacher-maps.index', ['tab' => $subject->learning_class_id])
            ->with('success', "Subject assignments updated for {$subject->name}.");
    }

    public function destroy(User $teacher, LearningClass $class)
    {
        abort_unless($teacher->isTeacher(), 404);

        DB::transaction(function () use ($teacher, $class) {
            $subjectIds = LearningSubject::query()
                ->where('learning_class_id', $class->id)
                ->pluck('id');

            $teacher->assignedLearningSubjects()->detach($subjectIds);
            LearningTeacherClassMap::query()
                ->where('user_id', $teacher->id)
                ->where('learning_class_id', $class->id)
                ->delete();
        });

        return back()->with('success', "{$class->name} allocation removed from {$teacher->name}.");
    }
}
