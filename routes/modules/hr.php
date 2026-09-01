<?php

use App\Http\Controllers\Card\PromoteController;
use App\Http\Controllers\Hr\DesignationController as HrDesignationController;
use App\Http\Controllers\Hr\MemberController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/hr')
    ->name('admin.hr.')
    ->middleware(['auth', 'admin', 'module.enabled:hr'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.hr.members.index'))->name('dashboard');

        Route::get('/members', [MemberController::class, 'index'])->middleware('permission:hr.members.view')->name('members.index');
        Route::get('/members/import', [MemberController::class, 'importForm'])->middleware('permission:hr.members.create')->name('members.import');
        Route::post('/members/import/csv/preview', [MemberController::class, 'importCsvPreview'])->middleware('permission:hr.members.create')->name('members.import.csv.preview');
        Route::post('/members/import/xlsx/preview', [MemberController::class, 'importXlsxPreview'])->middleware('permission:hr.members.create')->name('members.import.xlsx.preview');
        Route::post('/members/import/class-list/preview', [MemberController::class, 'importClassListPreview'])->middleware('permission:hr.members.create')->name('members.import.class-list.preview');
        Route::post('/members/import/confirm', [MemberController::class, 'confirmImport'])->middleware('permission:hr.members.create')->name('members.import.confirm');
        Route::post('/members/import/photos/preview', [MemberController::class, 'importPhotosPreview'])->middleware('permission:hr.members.create')->name('members.import.photos.preview');
        Route::post('/members/import/photos/confirm', [MemberController::class, 'importPhotosConfirm'])->middleware('permission:hr.members.create')->name('members.import.photos.confirm');
        Route::get('/members/template', [MemberController::class, 'template'])->middleware('permission:hr.members.create')->name('members.template');
        Route::get('/members/create', [MemberController::class, 'create'])->middleware('permission:hr.members.create')->name('members.create');
        Route::post('/members', [MemberController::class, 'store'])->middleware('permission:hr.members.create')->name('members.store');
        Route::get('/members/bulk-edit', [MemberController::class, 'bulkEdit'])->middleware('permission:hr.members.edit')->name('members.bulk-edit.index');
        Route::get('/members/bulk-edit/search', [MemberController::class, 'bulkEditSearch'])->middleware('permission:hr.members.edit')->name('members.bulk-edit.search');
        Route::post('/members/bulk-update', [MemberController::class, 'bulkUpdate'])->middleware('permission:hr.members.edit')->name('members.bulk-update');
        Route::post('/members/bulk-destroy', [MemberController::class, 'bulkDestroy'])->middleware('permission:hr.members.delete')->name('members.bulk-destroy');
        Route::delete('/members/orphan-users/{user}', [MemberController::class, 'destroyOrphanUser'])->middleware('permission:hr.members.delete')->name('members.orphan-users.destroy');

        // Promote students
        Route::get('/members/promote', [PromoteController::class, 'index'])->middleware('permission:hr.members.edit')->name('members.promote.index');
        Route::get('/members/promote/students', [PromoteController::class, 'students'])->middleware('permission:hr.members.edit')->name('members.promote.students');
        Route::post('/members/promote', [PromoteController::class, 'promote'])->middleware('permission:hr.members.edit')->name('members.promote.apply');

        Route::get('/members/{member}', [MemberController::class, 'show'])->middleware('permission:hr.members.view')->name('members.show');
        Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->middleware('permission:hr.members.edit')->name('members.edit');
        Route::put('/members/{member}', [MemberController::class, 'update'])->middleware('permission:hr.members.edit')->name('members.update');
        Route::delete('/members/{member}', [MemberController::class, 'destroy'])->middleware('permission:hr.members.delete')->name('members.destroy');

        // Designations (moved from Hajiri)
        Route::get('/designations',                          [HrDesignationController::class, 'index'])->middleware('permission:settings.view')->name('designations.index');
        Route::post('/designations',                         [HrDesignationController::class, 'store'])->middleware('permission:settings.edit')->name('designations.store');
        Route::put('/designations/{designation}',            [HrDesignationController::class, 'update'])->middleware('permission:settings.edit')->name('designations.update');
        Route::delete('/designations/{designation}',         [HrDesignationController::class, 'destroy'])->middleware('permission:settings.edit')->name('designations.destroy');

    });
