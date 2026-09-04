<?php

use App\Http\Controllers\TeachingLearning\SubjectController;
use App\Http\Controllers\TeachingLearning\RoutineConfigurationController;
use App\Http\Controllers\TeachingLearning\RoutineBuilderController;
use App\Http\Controllers\TeachingLearning\SubjectAssignmentController;
use App\Http\Controllers\TeachingLearning\TeacherWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/teaching-learning')
    ->name('admin.teaching-learning.')
    ->middleware(['auth', 'admin', 'module.enabled:teaching_learning'])
    ->group(function () {
        Route::get('/', [SubjectController::class, 'dashboard'])->middleware('permission:teaching-learning.subjects.view')->name('dashboard');
        Route::get('/subjects', [SubjectController::class, 'index'])->middleware('permission:teaching-learning.subjects.view')->name('subjects.index');
        Route::put('/section-groups', [SubjectController::class, 'updateSectionGroup'])->middleware('permission:teaching-learning.subjects.create')->name('section-groups.update');
        Route::post('/subject-offerings', [SubjectController::class, 'storeSubjectOffering'])->middleware('permission:teaching-learning.subjects.create')->name('subject-offerings.store');
        Route::patch('/subject-offerings/{subjectOffering}', [SubjectController::class, 'updateSubjectOffering'])->middleware('permission:teaching-learning.subjects.create')->name('subject-offerings.update');
        Route::delete('/subject-offerings/{subjectOffering}', [SubjectController::class, 'destroySubjectOffering'])->middleware('permission:teaching-learning.subjects.delete')->name('subject-offerings.destroy');
        Route::get('/subject-assignments', [SubjectAssignmentController::class, 'index'])->middleware('permission:teaching-learning.subjects.view')->name('subject-assignments.index');
        Route::post('/subject-assignments/sync-fixed', [SubjectAssignmentController::class, 'syncFixed'])->middleware('permission:teaching-learning.subjects.create')->name('subject-assignments.sync-fixed');
        Route::put('/subject-assignments/electives', [SubjectAssignmentController::class, 'updateElective'])->middleware('permission:teaching-learning.subjects.create')->name('subject-assignments.electives');

        Route::prefix('routine-builder')->name('routine-builder.')->group(function () {
            Route::get('/', [RoutineBuilderController::class, 'index'])->middleware('permission:teaching-learning.routine.view')->name('index');
            Route::post('/plans', [RoutineBuilderController::class, 'storePlan'])->middleware('permission:teaching-learning.routine.manage')->name('plans.store');
            Route::post('/rooms', [RoutineBuilderController::class, 'storeRoom'])->middleware('permission:teaching-learning.routine.manage')->name('rooms.store');
            Route::patch('/{routinePlan}', [RoutineBuilderController::class, 'updatePlan'])->middleware('permission:teaching-learning.routine.manage')->name('plans.update');
            Route::get('/{routinePlan}', [RoutineBuilderController::class, 'show'])->middleware('permission:teaching-learning.routine.view')->name('show');
            Route::get('/{routinePlan}/teachers', [RoutineBuilderController::class, 'teacherOptions'])->middleware('permission:teaching-learning.routine.manage')->name('teachers');
            Route::put('/{routinePlan}/lesson', [RoutineBuilderController::class, 'saveLesson'])->middleware('permission:teaching-learning.routine.manage')->name('lessons.save');
            Route::post('/{routinePlan}/publish', [RoutineBuilderController::class, 'togglePublish'])->middleware('permission:teaching-learning.routine.publish')->name('publish');
            Route::get('/{routinePlan}/print', [RoutineBuilderController::class, 'print'])->middleware('permission:teaching-learning.routine.view')->name('print');
            Route::delete('/{routinePlan}', [RoutineBuilderController::class, 'destroy'])->middleware('permission:teaching-learning.routine.manage')->name('destroy');
        });

        Route::prefix('routine-configuration')->name('routine-configuration.')->group(function () {
            Route::get('/', [RoutineConfigurationController::class, 'index'])->middleware('permission:teaching-learning.subjects.view')->name('index');
            Route::post('/academic-years', [RoutineConfigurationController::class, 'storeAcademicYear'])->middleware('permission:teaching-learning.subjects.create')->name('academic-years.store');
            Route::patch('/academic-years/{academicYear}', [RoutineConfigurationController::class, 'updateAcademicYear'])->middleware('permission:teaching-learning.subjects.create')->name('academic-years.update');
            Route::post('/academic-years/{academicYear}/activate', [RoutineConfigurationController::class, 'activateAcademicYear'])->middleware('permission:teaching-learning.subjects.create')->name('academic-years.activate');
            Route::post('/academic-years/{academicYear}/lock', [RoutineConfigurationController::class, 'toggleAcademicYearLock'])->middleware('permission:teaching-learning.subjects.create')->name('academic-years.lock');
            Route::delete('/academic-years/{academicYear}', [RoutineConfigurationController::class, 'destroyAcademicYear'])->middleware('permission:teaching-learning.subjects.delete')->name('academic-years.destroy');
            Route::post('/shifts', [RoutineConfigurationController::class, 'storeShift'])->middleware('permission:teaching-learning.subjects.create')->name('shifts.store');
            Route::patch('/shifts/{routineShift}', [RoutineConfigurationController::class, 'updateShift'])->middleware('permission:teaching-learning.subjects.create')->name('shifts.update');
            Route::post('/shifts/{routineShift}/lock', [RoutineConfigurationController::class, 'toggleShiftLock'])->middleware('permission:teaching-learning.subjects.create')->name('shifts.lock');
            Route::delete('/shifts/{routineShift}', [RoutineConfigurationController::class, 'destroyShift'])->middleware('permission:teaching-learning.subjects.delete')->name('shifts.destroy');
            Route::put('/shifts/{routineShift}/departments', [RoutineConfigurationController::class, 'assignDepartments'])->middleware('permission:teaching-learning.subjects.create')->name('shifts.departments');
            Route::patch('/periods/{routinePeriod}', [RoutineConfigurationController::class, 'updatePeriod'])->middleware('permission:teaching-learning.subjects.create')->name('periods.update');
        });
    });

Route::prefix('admin/teacher')->name('admin.teacher.')->middleware(['auth', 'module.enabled:teaching_learning'])->group(function () {
    Route::get('/workspace', [TeacherWorkspaceController::class, 'index'])->name('workspace');
    Route::get('/attendance/{routineLesson}', [TeacherWorkspaceController::class, 'attendance'])->name('attendance');
    Route::put('/attendance/{routineLesson}', [TeacherWorkspaceController::class, 'saveAttendance'])->name('attendance.save');
    Route::post('/attendance/{routineLesson}/finish', [TeacherWorkspaceController::class, 'finishAttendance'])->name('attendance.finish');
});
