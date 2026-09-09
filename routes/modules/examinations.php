<?php

use App\Http\Controllers\Examination\AdmitCardController;
use App\Http\Controllers\Examination\ExaminationController;
use App\Http\Controllers\Examination\MarkEntryController;
use App\Http\Controllers\Examination\MarksheetController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/examinations')->name('admin.examinations.')->middleware(['auth', 'module.enabled:examinations'])->group(function () {
    Route::get('/', [ExaminationController::class, 'index'])->middleware('permission:examinations.view')->name('index');
    Route::post('/', [ExaminationController::class, 'store'])->middleware('permission:examinations.manage')->name('store');
    Route::get('/{examination}', [ExaminationController::class, 'show'])->middleware('permission:examinations.view')->name('show');
    Route::patch('/{examination}', [ExaminationController::class, 'update'])->middleware('permission:examinations.manage')->name('update');
    Route::put('/{examination}/subjects', [ExaminationController::class, 'saveSubjects'])->middleware('permission:examinations.manage')->name('subjects.update');
    Route::get('/{examination}/subject-dates', [ExaminationController::class, 'subjectDates'])->middleware('permission:examinations.manage')->name('subject-dates.index');
    Route::put('/{examination}/subject-dates', [ExaminationController::class, 'saveSubjectDates'])->middleware('permission:examinations.manage')->name('subject-dates.update');
    Route::delete('/{examination}', [ExaminationController::class, 'destroy'])->middleware('permission:examinations.manage')->name('destroy');

    Route::get('/{examination}/admit-cards', [AdmitCardController::class, 'index'])->middleware('permission:examinations.manage')->name('admit-cards.index');
    Route::get('/{examination}/admit-cards/search', [AdmitCardController::class, 'index'])->middleware('permission:examinations.manage')->name('admit-cards.search');
    Route::post('/{examination}/admit-cards/assign', [AdmitCardController::class, 'assignSymbolNumbers'])->middleware('permission:examinations.manage')->name('admit-cards.assign');
    Route::get('/{examination}/admit-cards/print', [AdmitCardController::class, 'print'])->middleware('permission:examinations.manage,examinations.reports')->name('admit-cards.print');
    Route::get('/{examination}/admit-cards/download', [AdmitCardController::class, 'downloadAll'])->middleware('permission:examinations.manage,examinations.reports')->name('admit-cards.download');
    Route::get('/{examination}/admit-cards/export', [AdmitCardController::class, 'exportExcel'])->middleware('permission:examinations.manage,examinations.reports')->name('admit-cards.export');
    Route::get('/{examination}/admit-cards/{student}/print', [AdmitCardController::class, 'printOne'])->middleware('permission:examinations.manage,examinations.reports')->name('admit-cards.print-one');
    Route::get('/{examination}/admit-cards/{student}/download', [AdmitCardController::class, 'downloadOne'])->middleware('permission:examinations.manage,examinations.reports')->name('admit-cards.download-one');

    Route::get('/{examination}/marksheets', [MarksheetController::class, 'index'])->middleware('permission:examinations.manage,examinations.reports')->name('marksheets.index');
    Route::get('/{examination}/marksheets/print', [MarksheetController::class, 'print'])->middleware('permission:examinations.manage,examinations.reports')->name('marksheets.print');
    Route::get('/{examination}/marksheets/download', [MarksheetController::class, 'downloadAll'])->middleware('permission:examinations.manage,examinations.reports')->name('marksheets.download');
    Route::get('/{examination}/marksheets/{student}/download', [MarksheetController::class, 'downloadOne'])->middleware('permission:examinations.manage,examinations.reports')->name('marksheets.download-one');

    Route::get('/marks/{examinationSubject}', [MarkEntryController::class, 'edit'])->middleware('permission:examinations.marks.enter')->name('marks.edit');
    Route::put('/marks/{examinationSubject}', [MarkEntryController::class, 'update'])->middleware('permission:examinations.marks.enter')->name('marks.update');
    Route::put('/marks/{examinationSubject}/autosave', [MarkEntryController::class, 'autosave'])->middleware('permission:examinations.marks.enter')->name('marks.autosave');
    Route::post('/marks/{examinationSubject}/submit', [MarkEntryController::class, 'submit'])->middleware('permission:examinations.marks.enter')->name('marks.submit');
    Route::post('/mark-submissions/{markSubmission}/request-unlock', [MarkEntryController::class, 'requestUnlock'])->middleware('permission:examinations.marks.enter')->name('marks.request-unlock');
    Route::patch('/mark-submissions/{markSubmission}/unlock', [MarkEntryController::class, 'unlock'])->middleware('permission:examinations.manage')->name('marks.unlock');
});
