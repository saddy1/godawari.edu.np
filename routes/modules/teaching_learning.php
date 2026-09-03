<?php

use App\Http\Controllers\TeachingLearning\SubjectController;
use App\Http\Controllers\TeachingLearning\RoutineConfigurationController;
use App\Http\Controllers\TeachingLearning\SubjectAssignmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/teaching-learning')
    ->name('admin.teaching-learning.')
    ->middleware(['auth', 'admin', 'module.enabled:teaching_learning'])
    ->group(function () {
        Route::get('/', [SubjectController::class, 'dashboard'])->middleware('permission:teaching-learning.subjects.view')->name('dashboard');
        Route::get('/subjects', [SubjectController::class, 'index'])->middleware('permission:teaching-learning.subjects.view')->name('subjects.index');
        Route::post('/subject-offerings', [SubjectController::class, 'storeSubjectOffering'])->middleware('permission:teaching-learning.subjects.create')->name('subject-offerings.store');
        Route::patch('/subject-offerings/{subjectOffering}', [SubjectController::class, 'updateSubjectOffering'])->middleware('permission:teaching-learning.subjects.create')->name('subject-offerings.update');
        Route::delete('/subject-offerings/{subjectOffering}', [SubjectController::class, 'destroySubjectOffering'])->middleware('permission:teaching-learning.subjects.delete')->name('subject-offerings.destroy');
        Route::get('/subject-assignments', [SubjectAssignmentController::class, 'index'])->middleware('permission:teaching-learning.subjects.view')->name('subject-assignments.index');
        Route::post('/subject-assignments/sync-fixed', [SubjectAssignmentController::class, 'syncFixed'])->middleware('permission:teaching-learning.subjects.create')->name('subject-assignments.sync-fixed');
        Route::put('/subject-assignments/electives', [SubjectAssignmentController::class, 'updateElective'])->middleware('permission:teaching-learning.subjects.create')->name('subject-assignments.electives');

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
