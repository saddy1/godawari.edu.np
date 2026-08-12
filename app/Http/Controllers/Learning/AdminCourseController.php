<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Learning\LearningClass;
use App\Models\Learning\LearningCourse;
use App\Models\Learning\LearningQuiz;
use App\Models\Learning\LearningSubject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AdminCourseController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isTeacherScoped = $this->isTeacherScoped($user);
        $classIds = $isTeacherScoped
            ? $user->assignedLearningClasses()->pluck('learning_classes.id')
            : collect();
        $subjectIds = $isTeacherScoped
            ? $user->assignedLearningSubjects()->pluck('learning_subjects.id')
            : collect();

        $classes = LearningClass::where('is_active', true)
            ->when($isTeacherScoped, fn ($query) => $query->whereIn('id', $classIds))
            ->orderBy('sort_order')->orderBy('name')->get();
        $subjects = LearningSubject::with('learningClass')->where('is_active', true)
            ->when($isTeacherScoped, fn ($query) => $query->whereIn('id', $subjectIds)->whereIn('learning_class_id', $classIds))
            ->orderBy('name')->get();

        $query = LearningCourse::with(['learningClass', 'subject', 'creator'])->orderByDesc('created_at');

        if ($isTeacherScoped) {
            $query->whereIn('learning_class_id', $classIds)
                ->where(fn ($q) => $q->whereNull('learning_subject_id')
                    ->orWhereIn('learning_subject_id', $subjectIds));

            // Courses this teacher can open the manage page for
            $manageableCourseIds = $query->pluck('id')->all();
        } else {
            $manageableCourseIds = null; // null = all (admin)
        }

        $courses = $query->paginate(20);

        return view('learning.admin.courses.index', compact('classes', 'subjects', 'courses', 'manageableCourseIds'));
    }

    public function manage(LearningCourse $course)
    {
        abort_unless(Auth::user()->canManageLearningCourse($course), 403);

        $course->load(['learningClass', 'subject', 'chapters.lessons.quiz.questions']);

        $availableQuizzes = LearningQuiz::where('learning_course_id', $course->id)
            ->withCount('questions')
            ->orderBy('title')
            ->get(['id', 'title', 'learning_lesson_id', 'pass_percentage', 'is_published', 'learning_course_id']);

        return view('learning.admin.courses.manage', compact('course', 'availableQuizzes'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->authorizeTarget($request->user(), $data);
        $data['created_by'] = $request->user()->id;
        $data['slug'] = $this->uniqueSlug($data['title']);

        LearningCourse::create($data);

        return back()->with('success', 'Course added.');
    }

    public function update(Request $request, LearningCourse $course)
    {
        abort_unless($request->user()->canManageLearningCourse($course), 403);
        $data = $this->validated($request);
        $this->authorizeTarget($request->user(), $data);
        $data['slug'] = $course->title === $data['title'] ? $course->slug : $this->uniqueSlug($data['title'], $course->id);

        $course->update($data);

        return back()->with('success', 'Course updated.');
    }

    public function destroy(Request $request, LearningCourse $course)
    {
        abort_unless($request->user()->canManageLearningCourse($course), 403);
        $course->delete();

        return back()->with('success', 'Course deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'learning_class_id' => ['required', 'exists:learning_classes,id'],
            'learning_subject_id' => ['nullable', 'exists:learning_subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! empty($data['learning_subject_id']) && ! LearningSubject::query()
            ->whereKey($data['learning_subject_id'])
            ->where('learning_class_id', $data['learning_class_id'])
            ->exists()) {
            throw ValidationException::withMessages([
                'learning_subject_id' => 'The selected subject does not belong to the selected class.',
            ]);
        }

        return $data;
    }

    private function isTeacherScoped(User $user): bool
    {
        return $user->isTeacher()
            && ! $user->isSuperAdmin()
            && ! $user->isPrincipal()
            && ! $user->hasRole('administrator');
    }

    private function authorizeTarget(User $user, array $data): void
    {
        if (! $this->isTeacherScoped($user)) {
            return;
        }

        $classAllowed = $user->assignedLearningClasses()
            ->where('learning_classes.id', (int) $data['learning_class_id'])
            ->exists();
        $subjectAllowed = empty($data['learning_subject_id']) || $user->assignedLearningSubjects()
            ->where('learning_subjects.id', (int) $data['learning_subject_id'])
            ->exists();

        if (! $classAllowed || ! $subjectAllowed) {
            throw ValidationException::withMessages([
                'learning_class_id' => 'You can manage courses only for your assigned class and subject.',
            ]);
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'course';
        $slug = $base;
        $i = 2;

        while (LearningCourse::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
