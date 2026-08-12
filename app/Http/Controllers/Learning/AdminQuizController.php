<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Learning\LearningClass;
use App\Models\Learning\LearningCourse;
use App\Models\Learning\LearningLesson;
use App\Models\Learning\LearningQuiz;
use App\Models\Learning\LearningQuizOption;
use App\Models\Learning\LearningQuizQuestion;
use App\Models\Learning\LearningSubject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminQuizController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $teacherScoped = $this->isTeacherScoped($user);
        $classIds = collect();
        $subjectIds = collect();

        if ($teacherScoped) {
            $classIds = $user->assignedLearningClasses()->pluck('learning_classes.id');
            $subjectIds = $user->assignedLearningSubjects()->pluck('learning_subjects.id');
        }

        $query = LearningQuiz::with(['course.learningClass', 'course.subject', 'creator'])
            ->withCount('questions')
            ->withCount('attempts')
            ->orderBy('sort_order')
            ->orderByDesc('created_at');

        if ($teacherScoped) {
            $query->whereHas('course', fn ($q) => $q
                ->whereIn('learning_class_id', $classIds)
                ->whereIn('learning_subject_id', $subjectIds));
        }

        if ($request->filled('course')) {
            $query->where('learning_course_id', $request->input('course'));
        }

        if ($request->filled('class')) {
            $query->whereHas('course', fn ($q) => $q->where('learning_class_id', $request->input('class')));
        }

        if ($request->filled('subject')) {
            $query->whereHas('course', fn ($q) => $q->where('learning_subject_id', $request->input('subject')));
        }

        $quizzes = $query->paginate(20)->withQueryString();
        $courses = LearningCourse::query()
            ->when($teacherScoped, fn ($q) => $q
                ->whereIn('learning_class_id', $classIds)
                ->whereIn('learning_subject_id', $subjectIds))
            ->orderBy('title')
            ->get(['id', 'title', 'learning_class_id', 'learning_subject_id']);
        $classes = LearningClass::where('is_active', true)
            ->when($teacherScoped, fn ($q) => $q->whereIn('id', $classIds))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $subjects = LearningSubject::where('is_active', true)
            ->when($teacherScoped, fn ($q) => $q->whereIn('id', $subjectIds)->whereIn('learning_class_id', $classIds))
            ->orderBy('name')
            ->get();

        return view('learning.admin.quizzes.index', compact('quizzes', 'courses', 'classes', 'subjects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'learning_course_id'  => 'required|exists:learning_courses,id',
            'learning_lesson_id'  => 'nullable|exists:learning_lessons,id',
            'time_limit_minutes'  => 'nullable|integer|min:1|max:180',
            'pass_percentage'     => 'required|integer|min:1|max:100',
            'max_attempts'        => 'required|integer|min:1|max:10',
            'is_published'        => 'boolean',
            'sort_order'          => 'nullable|integer|min:0',
            'first_question'                      => 'nullable|array',
            'first_question.question_text'        => 'nullable|string',
            'first_question.type'                 => 'nullable|required_with:first_question.question_text|in:mcq,short_answer',
            'first_question.marks'                => 'nullable|required_with:first_question.question_text|integer|min:1|max:100',
            'first_question.explanation'          => 'nullable|string',
            'first_question.options'              => 'nullable|array',
            'first_question.options.*'            => 'nullable|string',
            'first_question.correct_option'       => 'nullable|integer',
        ]);

        $course = LearningCourse::findOrFail($data['learning_course_id']);
        if (! $this->canManageQuizCourse($request->user(), $course)) {
            throw ValidationException::withMessages([
                'learning_course_id' => 'You can create quizzes only for your assigned class and subject.',
            ]);
        }

        if (! empty($data['learning_lesson_id']) && ! LearningLesson::whereKey($data['learning_lesson_id'])->where('learning_course_id', $course->id)->exists()) {
            throw ValidationException::withMessages([
                'learning_lesson_id' => 'Select a lesson from this course.',
            ]);
        }

        $firstQuestion = $data['first_question'] ?? [];
        $quizData = collect($data)->except('first_question')->all();

        $quizData['created_by']   = Auth::id();
        $quizData['is_published'] = $request->boolean('is_published');

        if (! empty($quizData['learning_lesson_id'])) {
            LearningQuiz::where('learning_lesson_id', $quizData['learning_lesson_id'])->update(['learning_lesson_id' => null]);
        }

        $quiz = LearningQuiz::create($quizData);

        if (filled($firstQuestion['question_text'] ?? null)) {
            $this->createQuestionFromData($quiz, $firstQuestion, true);

            return redirect()->route('admin.learning.quizzes.manage', $quiz)
                ->with('success', 'Quiz created with the first question. You can manage more questions here.');
        }

        if ($request->boolean('_redirect_manage')) {
            return redirect()->route('admin.learning.quizzes.manage', $quiz)
                ->with('success', 'Quiz created. You can add questions here.');
        }

        return back()->with('success', 'Quiz created.');
    }

    public function manage(LearningQuiz $quiz)
    {
        $this->authorizeQuizAccess(Auth::user(), $quiz);

        $quiz->load(['questions.options', 'course.chapters.lessons']);

        // Lessons belonging to this quiz's course (for the lesson-link picker)
        $courseLessons = $quiz->course
            ? $quiz->course->chapters->flatMap(fn ($ch) => $ch->lessons)->values()
            : collect();

        return view('learning.admin.quizzes.manage', compact('quiz', 'courseLessons'));
    }

    public function update(Request $request, LearningQuiz $quiz)
    {
        $this->authorizeQuizAccess($request->user(), $quiz);

        $data = $request->validate([
            'title'                => 'required|string|max:255',
            'description'          => 'nullable|string',
            'learning_course_id'   => 'nullable|exists:learning_courses,id',
            'learning_lesson_id'   => 'nullable|exists:learning_lessons,id',
            'time_limit_minutes'   => 'nullable|integer|min:1|max:180',
            'pass_percentage'      => 'required|integer|min:1|max:100',
            'max_attempts'         => 'required|integer|min:1|max:10',
            'is_published'         => 'boolean',
            'sort_order'         => 'nullable|integer|min:0',
        ]);

        if (! empty($data['learning_course_id'])) {
            $course = LearningCourse::findOrFail($data['learning_course_id']);
            if (! $this->canManageQuizCourse($request->user(), $course)) {
                throw ValidationException::withMessages([
                    'learning_course_id' => 'You can move quizzes only to your assigned class and subject.',
                ]);
            }
        }

        $courseId = $data['learning_course_id'] ?? $quiz->learning_course_id;
        if (! empty($data['learning_lesson_id']) && ! LearningLesson::whereKey($data['learning_lesson_id'])->where('learning_course_id', $courseId)->exists()) {
            throw ValidationException::withMessages([
                'learning_lesson_id' => 'Select a lesson from the quiz course.',
            ]);
        }

        $data['is_published'] = $request->boolean('is_published');
        $quiz->update($data);

        return back()->with('success', 'Quiz updated.');
    }

    public function destroy(LearningQuiz $quiz)
    {
        $this->authorizeQuizAccess(Auth::user(), $quiz);

        $quiz->delete();
        return redirect()->route('admin.learning.quizzes.index')->with('success', 'Quiz deleted.');
    }

    public function storeQuestion(Request $request, LearningQuiz $quiz)
    {
        $this->authorizeQuizAccess($request->user(), $quiz);

        $data = $request->validate([
            'question_text' => 'required|string',
            'type'          => 'required|in:mcq,short_answer',
            'marks'         => 'required|integer|min:1|max:100',
            'sort_order'    => 'nullable|integer|min:0',
            'explanation'   => 'nullable|string',
            // MCQ options
            'options'       => 'required_if:type,mcq|array|min:2',
            'options.*'     => 'required_if:type,mcq|string',
            'correct_option' => 'required_if:type,mcq|integer',
        ]);

        $this->createQuestionFromData($quiz, $data);

        return back()->with('success', 'Question added.');
    }

    public function updateQuestion(Request $request, LearningQuiz $quiz, LearningQuizQuestion $question)
    {
        $this->authorizeQuizAccess($request->user(), $quiz);
        abort_unless((int) $question->learning_quiz_id === (int) $quiz->id, 404);

        $data = $request->validate([
            'question_text' => 'required|string',
            'marks'         => 'required|integer|min:1',
            'sort_order'    => 'nullable|integer|min:0',
            'explanation'   => 'nullable|string',
        ]);

        $question->update($data);

        return back()->with('success', 'Question updated.');
    }

    public function destroyQuestion(LearningQuiz $quiz, LearningQuizQuestion $question)
    {
        $this->authorizeQuizAccess(Auth::user(), $quiz);
        abort_unless((int) $question->learning_quiz_id === (int) $quiz->id, 404);

        $question->delete();
        return back()->with('success', 'Question deleted.');
    }

    public function storeOption(Request $request, LearningQuiz $quiz, LearningQuizQuestion $question)
    {
        $this->authorizeQuizAccess($request->user(), $quiz);
        abort_unless((int) $question->learning_quiz_id === (int) $quiz->id, 404);

        $request->validate([
            'option_text' => 'required|string',
            'is_correct'  => 'boolean',
        ]);

        if ($request->boolean('is_correct')) {
            $question->options()->update(['is_correct' => false]);
        }

        $question->options()->create([
            'option_text' => $request->option_text,
            'is_correct'  => $request->boolean('is_correct'),
            'sort_order'  => $question->options()->max('sort_order') + 1,
        ]);

        return back()->with('success', 'Option added.');
    }

    public function destroyOption(LearningQuiz $quiz, LearningQuizQuestion $question, LearningQuizOption $option)
    {
        $this->authorizeQuizAccess(Auth::user(), $quiz);
        abort_unless((int) $question->learning_quiz_id === (int) $quiz->id, 404);
        abort_unless((int) $option->learning_quiz_question_id === (int) $question->id, 404);

        $option->delete();
        return back()->with('success', 'Option deleted.');
    }

    private function isTeacherScoped(User $user): bool
    {
        return $user->isTeacher()
            && ! $user->isSuperAdmin()
            && ! $user->isPrincipal()
            && ! $user->hasAnyRole(['administrator']);
    }

    private function canManageQuizCourse(User $user, LearningCourse $course): bool
    {
        if (! $this->isTeacherScoped($user)) {
            return true;
        }

        if (! $course->learning_subject_id) {
            return false;
        }

        return $user->assignedLearningClasses()
            ->where('learning_classes.id', $course->learning_class_id)
            ->exists()
            && $user->assignedLearningSubjects()
                ->where('learning_subjects.id', $course->learning_subject_id)
                ->exists();
    }

    private function authorizeQuizAccess(User $user, LearningQuiz $quiz): void
    {
        $quiz->loadMissing('course');
        abort_unless($quiz->course && $this->canManageQuizCourse($user, $quiz->course), 403);
    }

    private function createQuestionFromData(LearningQuiz $quiz, array $data, bool $deleteQuizOnFailure = false): LearningQuizQuestion
    {
        $question = $quiz->questions()->create([
            'question_text' => $data['question_text'],
            'type'          => $data['type'],
            'marks'         => $data['marks'],
            'sort_order'    => $data['sort_order'] ?? ($quiz->questions()->max('sort_order') + 1),
            'explanation'   => $data['explanation'] ?? null,
        ]);

        if ($data['type'] === 'mcq') {
            $options = collect($data['options'] ?? [])
                ->map(fn ($option) => trim((string) $option))
                ->filter();

            if ($options->count() < 2) {
                if ($deleteQuizOnFailure) {
                    $quiz->delete();
                }

                throw ValidationException::withMessages([
                    'first_question.options' => 'Add at least two answer options for the first MCQ question.',
                ]);
            }

            foreach ($options as $index => $optionText) {
                $question->options()->create([
                    'option_text' => $optionText,
                    'is_correct'  => (int) ($data['correct_option'] ?? -1) === $index,
                    'sort_order'  => $index,
                ]);
            }
        }

        return $question;
    }
}
