<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Learning\LearningCourse;
use App\Models\Learning\LearningProgress;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function show(Request $request, LearningCourse $course)
    {
        abort_unless($course->status === 'published' || $request->user()?->isAdmin(), 404);

        $course->load([
            'learningClass',
            'subject',
            'chapters' => fn ($q) => $q->orderBy('sort_order'),
            'chapters.lessons' => fn ($q) => $q->where('is_published', true)
                ->orderBy('sort_order')
                ->with(['quiz' => fn ($quiz) => $quiz
                    ->where('is_published', true)
                    ->withCount('questions')
                    ->with([
                        'questions',
                        'attempts' => fn ($attempt) => $attempt->where('user_id', $request->user()->id),
                    ])]),
        ]);

        LearningProgress::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'learning_course_id' => $course->id,
                'learning_lesson_id' => null,
            ],
            ['started_at' => now()]
        );

        $completedLessonIds = LearningProgress::where('user_id', $request->user()->id)
            ->where('learning_course_id', $course->id)
            ->whereNotNull('learning_lesson_id')
            ->whereNotNull('completed_at')
            ->pluck('learning_lesson_id')
            ->all();

        // Build flat ordered lesson list across all chapters
        $flatLessons = $course->chapters->flatMap(fn ($ch) => $ch->lessons)->values();

        // Sequential unlock: first lesson always unlocked; subsequent lessons unlock when previous is completed
        $unlockedLessonIds = [];
        foreach ($flatLessons as $i => $lesson) {
            if ($lesson->is_free || $i === 0 || in_array($flatLessons[$i - 1]->id, $completedLessonIds)) {
                $unlockedLessonIds[] = $lesson->id;
            }
        }

        $quizzes = $course->quizzes()
            ->where('is_published', true)
            ->whereNull('learning_lesson_id')
            ->orderBy('sort_order')
            ->withCount('questions')
            ->with(['questions', 'attempts' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->get();

        return view('learning.courses.show', compact('course', 'completedLessonIds', 'unlockedLessonIds', 'quizzes'));
    }

    public function startLesson(Request $request, LearningCourse $course, int $lesson)
    {
        $lessonModel = $course->lessons()
            ->where('id', $lesson)
            ->where('is_published', true)
            ->firstOrFail();

        // Enforce sequential unlock — build flat lesson list and compute unlock status
        $completedLessonIds = LearningProgress::where('user_id', $request->user()->id)
            ->where('learning_course_id', $course->id)
            ->whereNotNull('learning_lesson_id')
            ->whereNotNull('completed_at')
            ->pluck('learning_lesson_id')
            ->all();

        $flatLessons = $course->chapters()
            ->orderBy('sort_order')
            ->with(['lessons' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order')])
            ->get()
            ->flatMap(fn ($ch) => $ch->lessons)
            ->values();

        $unlockedLessonIds = [];
        foreach ($flatLessons as $i => $l) {
            if ($l->is_free || $i === 0 || in_array($flatLessons[$i - 1]->id, $completedLessonIds)) {
                $unlockedLessonIds[] = $l->id;
            }
        }

        abort_unless(in_array($lessonModel->id, $unlockedLessonIds), 403);

        $lessonProgress = LearningProgress::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'learning_course_id' => $course->id,
                'learning_lesson_id' => $lessonModel->id,
            ],
            ['started_at' => now()]
        );

        // Find next lesson in flat order
        $currentIndex = $flatLessons->search(fn ($l) => $l->id === $lessonModel->id);
        $nextLesson = $currentIndex !== false && isset($flatLessons[$currentIndex + 1])
            ? $flatLessons[$currentIndex + 1]
            : null;

        return view('learning.lessons.show', [
            'course'             => $course->load(['learningClass', 'subject', 'chapters.lessons']),
            'lesson'             => $lessonModel->load('quiz.questions.options'),
            'nextLesson'         => $nextLesson,
            'completedLessonIds' => $completedLessonIds,
            'unlockedLessonIds'  => $unlockedLessonIds,
            'lessonProgress'     => $lessonProgress,
        ]);
    }
}
