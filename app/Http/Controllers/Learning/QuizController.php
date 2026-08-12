<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Learning\LearningProgress;
use App\Models\Learning\LearningQuiz;
use App\Models\Learning\LearningQuizAttempt;
use App\Models\Learning\LearningQuizAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function show(LearningQuiz $quiz)
    {
        abort_unless($quiz->is_published, 404);
        $quiz->load(['questions.options', 'lesson']);
        $this->ensureAttachedLessonIsCompleted($quiz);

        $attemptsUsed = LearningQuizAttempt::where('user_id', Auth::id())
            ->where('learning_quiz_id', $quiz->id)
            ->whereNotNull('completed_at')
            ->count();

        $lastAttempt = LearningQuizAttempt::where('user_id', Auth::id())
            ->where('learning_quiz_id', $quiz->id)
            ->whereNotNull('completed_at')
            ->latest()
            ->first();

        $canAttempt = $attemptsUsed < $quiz->max_attempts;

        // Shuffle questions and options — persist order in session so refresh keeps same layout
        $sessionKey = 'quiz_shuffle_' . $quiz->id . '_' . Auth::id();
        if (!session()->has($sessionKey)) {
            session([$sessionKey => [
                'questions' => $quiz->questions->pluck('id')->shuffle()->values()->all(),
                'options'   => $quiz->questions->mapWithKeys(fn ($q) => [
                    $q->id => $q->options->pluck('id')->shuffle()->values()->all(),
                ])->all(),
            ]]);
        }

        $shuffle    = session($sessionKey);
        $questions  = collect($shuffle['questions'])
            ->map(fn ($id) => $quiz->questions->find($id))
            ->filter()
            ->map(function ($question) use ($shuffle) {
                if ($question->type === 'mcq' && isset($shuffle['options'][$question->id])) {
                    $ordered = collect($shuffle['options'][$question->id])
                        ->map(fn ($id) => $question->options->find($id))
                        ->filter()
                        ->values();
                    $question->setRelation('options', $ordered);
                }
                return $question;
            })
            ->values();

        return view('learning.quizzes.show', compact('quiz', 'questions', 'attemptsUsed', 'lastAttempt', 'canAttempt'));
    }

    public function submit(Request $request, LearningQuiz $quiz)
    {
        abort_unless($quiz->is_published, 404);
        $quiz->load('lesson');
        $this->ensureAttachedLessonIsCompleted($quiz);

        $attemptsUsed = LearningQuizAttempt::where('user_id', Auth::id())
            ->where('learning_quiz_id', $quiz->id)
            ->whereNotNull('completed_at')
            ->count();

        abort_if($attemptsUsed >= $quiz->max_attempts, 403, 'No attempts remaining.');

        $quiz->load(['questions.options']);

        $attempt = LearningQuizAttempt::create([
            'user_id'           => Auth::id(),
            'learning_quiz_id'  => $quiz->id,
            'score'             => 0,
            'total_marks'       => $quiz->totalMarks(),
            'passed'            => false,
            'started_at'        => now()->subSeconds(5),
            'completed_at'      => now(),
        ]);

        $totalScore = 0;

        foreach ($quiz->questions as $question) {
            $answer = null;
            $marksAwarded = 0;
            $isCorrect = null;

            if ($question->type === 'mcq') {
                $selectedId = $request->input("answers.{$question->id}");
                $selectedOption = $question->options->find($selectedId);
                $isCorrect = $selectedOption?->is_correct ?? false;
                $marksAwarded = $isCorrect ? $question->marks : 0;
                $totalScore += $marksAwarded;

                $answer = [
                    'selected_option_id' => $selectedId,
                    'is_correct'         => $isCorrect,
                    'marks_awarded'      => $marksAwarded,
                ];
            } else {
                $textAnswer = $request->input("answers.{$question->id}");
                $answer = [
                    'text_answer'   => $textAnswer,
                    'is_correct'    => null,
                    'marks_awarded' => null,
                ];
            }

            LearningQuizAnswer::create(array_merge($answer, [
                'learning_quiz_attempt_id'  => $attempt->id,
                'learning_quiz_question_id' => $question->id,
            ]));
        }

        $totalMarks = $quiz->totalMarks();
        $passed = $totalMarks > 0
            ? (($totalScore / $totalMarks) * 100) >= $quiz->pass_percentage
            : false;

        $attempt->update(['score' => $totalScore, 'passed' => $passed]);

        // Clear shuffle so next attempt gets fresh random order
        session()->forget('quiz_shuffle_' . $quiz->id . '_' . Auth::id());

        return redirect()->route('learning.quizzes.result', [$quiz, $attempt])
            ->with('success', 'Quiz submitted!');
    }

    public function result(LearningQuiz $quiz, LearningQuizAttempt $attempt)
    {
        abort_unless($attempt->user_id === Auth::id(), 403);
        $attempt->load(['answers.question.options', 'answers.selectedOption']);
        $quiz->load('questions.options');

        if ($attempt->total_marks > 0) {
            $shouldPass = (($attempt->score / $attempt->total_marks) * 100) >= $quiz->pass_percentage;

            if ($attempt->passed !== $shouldPass) {
                $attempt->forceFill(['passed' => $shouldPass])->save();
                $attempt->refresh();
            }
        }

        return view('learning.quizzes.result', compact('quiz', 'attempt'));
    }

    private function ensureAttachedLessonIsCompleted(LearningQuiz $quiz): void
    {
        if (! $quiz->learning_lesson_id) {
            return;
        }

        $completed = LearningProgress::where('user_id', Auth::id())
            ->where('learning_course_id', $quiz->learning_course_id)
            ->where('learning_lesson_id', $quiz->learning_lesson_id)
            ->whereNotNull('completed_at')
            ->exists();

        abort_unless($completed, 403, 'Complete the lesson before taking this quiz.');
    }
}
