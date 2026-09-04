<?php

use App\Http\Controllers\Examination\ExaminationController;
use App\Http\Controllers\Examination\MarkEntryController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/examinations')->name('admin.examinations.')->middleware(['auth', 'module.enabled:examinations'])->group(function () {
    Route::get('/', [ExaminationController::class, 'index'])->middleware('permission:examinations.view')->name('index');
    Route::post('/', [ExaminationController::class, 'store'])->middleware('permission:examinations.manage')->name('store');
    Route::get('/{examination}', [ExaminationController::class, 'show'])->middleware('permission:examinations.view')->name('show');
    Route::patch('/{examination}', [ExaminationController::class, 'update'])->middleware('permission:examinations.manage')->name('update');
    Route::put('/{examination}/subjects', [ExaminationController::class, 'saveSubjects'])->middleware('permission:examinations.manage')->name('subjects.update');
    Route::delete('/{examination}', [ExaminationController::class, 'destroy'])->middleware('permission:examinations.manage')->name('destroy');
    Route::get('/marks/{examinationSubject}', [MarkEntryController::class, 'edit'])->middleware('permission:examinations.marks.enter')->name('marks.edit');
    Route::put('/marks/{examinationSubject}', [MarkEntryController::class, 'update'])->middleware('permission:examinations.marks.enter')->name('marks.update');
    Route::put('/marks/{examinationSubject}/autosave', [MarkEntryController::class, 'autosave'])->middleware('permission:examinations.marks.enter')->name('marks.autosave');
});
