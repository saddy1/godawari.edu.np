<?php

use App\Http\Controllers\TeachingLearning\SubjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/teaching-learning')
    ->name('admin.teaching-learning.')
    ->middleware(['auth', 'admin', 'module.enabled:teaching_learning'])
    ->group(function () {
        Route::get('/', [SubjectController::class, 'dashboard'])->middleware('permission:teaching-learning.subjects.view')->name('dashboard');
        Route::get('/subjects', [SubjectController::class, 'index'])->middleware('permission:teaching-learning.subjects.view')->name('subjects.index');
        Route::post('/subject-offerings', [SubjectController::class, 'storeSubjectOffering'])->middleware('permission:teaching-learning.subjects.create')->name('subject-offerings.store');
        Route::delete('/subject-offerings/{subjectOffering}', [SubjectController::class, 'destroySubjectOffering'])->middleware('permission:teaching-learning.subjects.delete')->name('subject-offerings.destroy');
    });
