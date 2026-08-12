<?php

use App\Http\Controllers\Learning\AdminChapterController;
use App\Http\Controllers\Learning\AdminQuizController;
use App\Http\Controllers\Learning\QuizController;
use App\Http\Controllers\Learning\AdminClassController;
use App\Http\Controllers\Learning\AdminCourseController;
use App\Http\Controllers\Learning\AdminLessonController;
use App\Http\Controllers\Learning\AdminResourceController;
use App\Http\Controllers\Learning\AdminSubjectController;
use App\Http\Controllers\Learning\AdminTeacherMapController;
use App\Http\Controllers\Learning\CourseController;
use App\Http\Controllers\Learning\DashboardController;
use App\Http\Controllers\Learning\LessonProgressController;
use App\Http\Controllers\Learning\StudentAccountController;
use App\Http\Middleware\EnsureLearningTeacherAssigned;
use Illuminate\Support\Facades\Route;

Route::prefix('learning')
    ->name('learning.')
    ->middleware(['auth', 'module.enabled:learning', 'permission:learning.courses.view'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'student'])->name('dashboard');
        Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
        Route::get('/courses/{course:slug}/lessons/{lesson}', [CourseController::class, 'startLesson'])->name('lessons.show');
        Route::post('/courses/{course:slug}/lessons/{lesson}/progress', [LessonProgressController::class, 'progress'])->name('lessons.progress');
        Route::post('/courses/{course:slug}/lessons/{lesson}/complete', [LessonProgressController::class, 'complete'])->name('lessons.complete');
        // Student quiz routes
        Route::get('/quiz/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
        Route::post('/quiz/{quiz}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');
        Route::get('/quiz/{quiz}/result/{attempt}', [QuizController::class, 'result'])->name('quizzes.result');
    });

Route::prefix('admin/learning')
    ->name('admin.learning.')
    ->middleware(['auth', 'module.enabled:learning', EnsureLearningTeacherAssigned::class])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'admin'])->middleware('permission:learning.courses.view')->name('dashboard');

        Route::get('/classes', [AdminClassController::class, 'index'])->middleware('permission:learning.courses.view')->name('classes.index');
        Route::post('/classes', [AdminClassController::class, 'store'])->middleware('permission:learning.courses.create')->name('classes.store');
        Route::patch('/classes/{class}', [AdminClassController::class, 'update'])->middleware('permission:learning.courses.edit')->name('classes.update');
        Route::delete('/classes/{class}', [AdminClassController::class, 'destroy'])->middleware('permission:learning.courses.delete')->name('classes.destroy');

        Route::get('/subjects', [AdminSubjectController::class, 'index'])->middleware('permission:learning.courses.view')->name('subjects.index');
        Route::post('/subjects', [AdminSubjectController::class, 'store'])->middleware('permission:learning.courses.create')->name('subjects.store');
        Route::patch('/subjects/{subject}', [AdminSubjectController::class, 'update'])->middleware('permission:learning.courses.edit')->name('subjects.update');
        Route::delete('/subjects/{subject}', [AdminSubjectController::class, 'destroy'])->middleware('permission:learning.courses.delete')->name('subjects.destroy');

        Route::get('/courses', [AdminCourseController::class, 'index'])->middleware('permission:learning.courses.view')->name('courses.index');
        Route::post('/courses', [AdminCourseController::class, 'store'])->middleware('permission:learning.courses.create')->name('courses.store');
        // Manage page: permission check is in controller (teachers use learning.lessons.edit, admins use learning.courses.edit)
        Route::get('/courses/{course:id}/manage', [AdminCourseController::class, 'manage'])->middleware('permission:learning.courses.view')->name('courses.manage');
        Route::patch('/courses/{course:id}', [AdminCourseController::class, 'update'])->middleware('permission:learning.courses.edit')->name('courses.update');
        Route::delete('/courses/{course:id}', [AdminCourseController::class, 'destroy'])->middleware('permission:learning.courses.delete')->name('courses.destroy');

        // Chapter & lesson management — teachers have learning.lessons.edit; controller enforces class/subject access
        Route::post('/courses/{course:id}/chapters', [AdminChapterController::class, 'store'])->middleware('permission:learning.lessons.edit')->name('chapters.store');
        Route::patch('/courses/{course:id}/chapters/{chapter}', [AdminChapterController::class, 'update'])->middleware('permission:learning.lessons.edit')->name('chapters.update');
        Route::delete('/courses/{course:id}/chapters/{chapter}', [AdminChapterController::class, 'destroy'])->middleware('permission:learning.lessons.edit')->name('chapters.destroy');

        Route::post('/courses/{course:id}/chapters/{chapter}/lessons', [AdminLessonController::class, 'store'])->middleware('permission:learning.lessons.edit')->name('lessons.store');
        Route::patch('/courses/{course:id}/lessons/{lesson}', [AdminLessonController::class, 'update'])->middleware('permission:learning.lessons.edit')->name('lessons.update');
        Route::post('/courses/{course:id}/lessons/{lesson}/attach-quiz', [AdminLessonController::class, 'attachQuiz'])->middleware('permission:learning.lessons.edit')->name('lessons.attach-quiz');
        Route::delete('/courses/{course:id}/lessons/{lesson}', [AdminLessonController::class, 'destroy'])->middleware('permission:learning.lessons.edit')->name('lessons.destroy');

        Route::get('/resources', [AdminResourceController::class, 'index'])->middleware('permission:learning.resources.view')->name('resources.index');
        Route::post('/resources', [AdminResourceController::class, 'store'])->middleware('permission:learning.resources.create')->name('resources.store');
        Route::patch('/resources/{resource}', [AdminResourceController::class, 'update'])->middleware('permission:learning.resources.edit')->name('resources.update');
        Route::delete('/resources/{resource}', [AdminResourceController::class, 'destroy'])->middleware('permission:learning.resources.delete')->name('resources.destroy');

        Route::get('/students', [StudentAccountController::class, 'index'])->middleware('permission:learning.students.view')->name('students.index');
        Route::post('/students', [StudentAccountController::class, 'store'])->middleware('permission:learning.students.create')->name('students.store');
        Route::patch('/students/{student}/password', [StudentAccountController::class, 'resetPassword'])->middleware('permission:learning.students.edit')->name('students.password');

        // Quizzes — admin manages, teachers can create for their courses
        Route::get('/quizzes', [AdminQuizController::class, 'index'])->middleware('permission:learning.courses.view')->name('quizzes.index');
        Route::post('/quizzes', [AdminQuizController::class, 'store'])->middleware('permission:learning.lessons.edit')->name('quizzes.store');
        Route::get('/quizzes/{quiz}', [AdminQuizController::class, 'manage'])->middleware('permission:learning.courses.view')->name('quizzes.manage');
        Route::patch('/quizzes/{quiz}', [AdminQuizController::class, 'update'])->middleware('permission:learning.lessons.edit')->name('quizzes.update');
        Route::delete('/quizzes/{quiz}', [AdminQuizController::class, 'destroy'])->middleware('permission:learning.lessons.edit')->name('quizzes.destroy');
        Route::post('/quizzes/{quiz}/questions', [AdminQuizController::class, 'storeQuestion'])->middleware('permission:learning.lessons.edit')->name('quizzes.questions.store');
        Route::patch('/quizzes/{quiz}/questions/{question}', [AdminQuizController::class, 'updateQuestion'])->middleware('permission:learning.lessons.edit')->name('quizzes.questions.update');
        Route::delete('/quizzes/{quiz}/questions/{question}', [AdminQuizController::class, 'destroyQuestion'])->middleware('permission:learning.lessons.edit')->name('quizzes.questions.destroy');
        Route::post('/quizzes/{quiz}/questions/{question}/options', [AdminQuizController::class, 'storeOption'])->middleware('permission:learning.lessons.edit')->name('quizzes.options.store');
        Route::delete('/quizzes/{quiz}/questions/{question}/options/{option}', [AdminQuizController::class, 'destroyOption'])->middleware('permission:learning.lessons.edit')->name('quizzes.options.destroy');

        Route::get('/teacher-maps', [AdminTeacherMapController::class, 'index'])->middleware('permission:learning.teacher.assign')->name('teacher-maps.index');
        Route::patch('/teacher-maps/{teacher}/allocation', [AdminTeacherMapController::class, 'updateAllocation'])->middleware('permission:learning.teacher.assign')->name('teacher-maps.allocation.update');
        Route::patch('/teacher-maps/{teacher}', [AdminTeacherMapController::class, 'update'])->middleware('permission:learning.teacher.assign')->name('teacher-maps.update');
        Route::patch('/teacher-maps/class/{class}', [AdminTeacherMapController::class, 'updateByClass'])->middleware('permission:learning.teacher.assign')->name('teacher-maps.updateByClass');
        Route::patch('/teacher-maps/subject/{subject}', [AdminTeacherMapController::class, 'updateBySubject'])->middleware('permission:learning.teacher.assign')->name('teacher-maps.updateBySubject');
        Route::delete('/teacher-maps/{teacher}/{class}', [AdminTeacherMapController::class, 'destroy'])->middleware('permission:learning.teacher.assign')->name('teacher-maps.destroy');
    });
