<?php

use App\Http\Controllers\Hajiri\CalendarSettingController;
use App\Http\Controllers\Hajiri\DepartmentController;
use App\Http\Controllers\Hajiri\DeviceController;
use App\Http\Controllers\Hajiri\HolidayController;
use App\Http\Controllers\Hajiri\HomeController as HajiriHomeController;
use App\Http\Controllers\Hajiri\LeaveController;
use App\Http\Controllers\Hajiri\LeavePolicyController;
use App\Http\Controllers\Hajiri\LeaveRequestController;
use App\Http\Controllers\Hajiri\LogController;
use App\Http\Controllers\Hajiri\ReportController;
use App\Http\Controllers\Hajiri\StaffCardRequestController;
use App\Http\Controllers\Hajiri\UserController as HajiriUserController;
use Illuminate\Support\Facades\Route;

// ── Employee-accessible routes (any authenticated staff user) ─────────────────
Route::prefix('admin/hajiri')->name('hajiri.')->middleware(['auth', 'module.enabled:hajiri'])->group(function () {
    Route::get('/', fn () => redirect()->route('hajiri.home'));
    Route::get('/home', [HajiriHomeController::class, 'index'])->name('home');
    Route::get('/calendar/{year}/{month}', [HajiriHomeController::class, 'index'])->name('calendar-yy-mm');

    // Employee self-service leaves
    Route::middleware('module.enabled:hajiri_leave')->group(function () {
        Route::get('my-leaves',       [LeaveRequestController::class, 'myLeaves'])->name('my-leaves');
        Route::post('leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    });

    // Staff ID card requests (employee self-service)
    Route::get('staff-card-request',       [StaffCardRequestController::class, 'index'])->name('staff-card-request.index');
    Route::post('staff-card-request',      [StaffCardRequestController::class, 'store'])->name('staff-card-request.store');

    // Reports – own attendance (non-admins will only see their own data)
    Route::get('reporting/modal',                                           [ReportController::class, 'index'])->middleware('permission:attendance.report,reports.view')->name('report.modal');
    Route::get('reporting/type-{apd}/user/{userid}',                        [ReportController::class, 'report_user'])->middleware('permission:attendance.report,reports.view')->name('report.all_user');
    Route::get('reporting/type-{apd}/user/{userid}/{year}/{month}',         [ReportController::class, 'report_user'])->middleware('permission:attendance.report,reports.view')->name('report.month_user');
    Route::get('reporting/user/ajax/fetch',                                 [ReportController::class, 'getUserLogData'])->middleware('permission:attendance.report,reports.view')->name('report.fetch');
    Route::get('reporting/user/ajax/fetch-ap',                              [ReportController::class, 'getUserLogDataAP'])->middleware('permission:attendance.report,reports.view')->name('report.ap.fetch');
});

// ── Admin-only routes ─────────────────────────────────────────────────────────
Route::prefix('admin/hajiri')->name('hajiri.')->middleware(['auth', 'admin', 'module.enabled:hajiri'])->group(function () {

    Route::resource('users', HajiriUserController::class)
        ->only(['index', 'show'])
        ->middleware('permission:users.view');
    Route::resource('users', HajiriUserController::class)
        ->only(['create', 'store'])
        ->middleware('permission:users.create');
    Route::resource('users', HajiriUserController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:users.edit');
    Route::resource('users', HajiriUserController::class)
        ->only(['destroy'])
        ->middleware('permission:users.delete');
    Route::get('users/type/{typeid}',         [HajiriUserController::class, 'index_custom'])->middleware('permission:users.view')->name('users.customs');
    Route::get('users/status/inactive',       [HajiriUserController::class, 'index_inactive'])->middleware('permission:users.view')->name('users.inactive');
    Route::get('users/action/sort',           [HajiriUserController::class, 'index_sorting'])->middleware('permission:users.edit')->name('users.indexsort');
    Route::post('users/sort',                 [HajiriUserController::class, 'sorting'])->middleware('permission:users.edit')->name('users.savesort');
    Route::post('users/filter',               [HajiriUserController::class, 'filter'])->middleware('permission:users.view')->name('users.filter');

    Route::resource('logs', LogController::class)
        ->only(['index', 'show'])
        ->middleware('permission:attendance.view');
    Route::resource('logs', LogController::class)
        ->only(['create', 'store'])
        ->middleware('permission:attendance.create');
    Route::resource('logs', LogController::class)
        ->only(['edit', 'update', 'destroy'])
        ->middleware('permission:attendance.edit');
    Route::get('logs/type/{type}',                            [LogController::class, 'showlogs'])->middleware('permission:attendance.view')->name('logs.showlogs');
    Route::get('logs/type/{type}/custom/{year}/{month}',      [LogController::class, 'showlogs'])->middleware('permission:attendance.view')->name('logs.showlogs_y_m');

    Route::get('reporting/all',                                                [ReportController::class, 'report'])->middleware('permission:attendance.report')->name('report.all');
    Route::get('reporting/custom/{year}/{month}',                              [ReportController::class, 'report'])->middleware('permission:attendance.report')->name('report.month');
    Route::get('reporting/ap/all',                                             [ReportController::class, 'report_ap'])->middleware('permission:attendance.report')->name('report.ap.all');
    Route::get('reporting/ap/custom/{year}/{month}',                           [ReportController::class, 'report_ap'])->middleware('permission:attendance.report')->name('report.ap.month');
    Route::get('reporting/type-{apd}/{typeid}/print',                          [ReportController::class, 'report_type'])->middleware('permission:attendance.report')->name('report.all_type');
    Route::get('reporting/type-{apd}/{typeid}/print/{year}/{month}',           [ReportController::class, 'report_type'])->middleware('permission:attendance.report')->name('report.month_type');
    Route::get('reporting/type-{apd}/department/{typeid}/print',               [ReportController::class, 'report_department'])->middleware('permission:attendance.report')->name('report.all_department');
    Route::get('reporting/type-{apd}/department/{typeid}/print/{year}/{month}',[ReportController::class, 'report_department'])->middleware('permission:attendance.report')->name('report.month_department');
    Route::get('holidays/custom/{year}/{month}',  [HolidayController::class, 'index'])->middleware('permission:attendance.view')->name('holiday-yy-mm');
    Route::post('holidays/add/new',               [HolidayController::class, 'addHoliday'])->middleware('permission:attendance.create')->name('holidays.addholiday');
    Route::post('holidays/settings',              [HolidayController::class, 'settings'])->middleware('permission:settings.edit')->name('holidays.settings');
    Route::resource('holidays', HolidayController::class)
        ->only(['index', 'show'])
        ->middleware('permission:attendance.view');
    Route::resource('holidays', HolidayController::class)
        ->only(['create', 'store'])
        ->middleware('permission:attendance.create');
    Route::resource('holidays', HolidayController::class)
        ->only(['edit', 'update', 'destroy'])
        ->middleware('permission:attendance.edit');

    Route::middleware('super_admin')->group(function () {
        Route::get('calendar-settings',  [CalendarSettingController::class, 'index'])->name('calendar-settings.index');
        Route::post('calendar-settings', [CalendarSettingController::class, 'store'])->name('calendar-settings.store');
    });

    Route::resource('leaves', LeaveController::class)
        ->only(['index', 'show'])
        ->middleware('permission:leaves.view');
    Route::resource('leaves', LeaveController::class)
        ->only(['create', 'store'])
        ->middleware('permission:leaves.create');
    Route::resource('leaves', LeaveController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:leaves.approve');
    Route::resource('leaves', LeaveController::class)
        ->only(['destroy'])
        ->middleware('permission:leaves.reject');
    Route::get('leaves/custom/{year}/{month}',  [LeaveController::class, 'index'])->middleware('permission:leaves.view')->name('leaves-yy-mm');
    Route::post('leaves/add/new',               [LeaveController::class, 'addHoliday'])->middleware('permission:leaves.create')->name('leaves.addleave');

    // Leave sub-module (can be disabled independently)
    Route::middleware('module.enabled:hajiri_leave')->group(function () {
        Route::resource('leave-policies', LeavePolicyController::class)
            ->only(['index', 'show'])
            ->middleware('permission:settings.view')
            ->names('leave-policies');
        Route::resource('leave-policies', LeavePolicyController::class)
            ->only(['create', 'store', 'edit', 'update', 'destroy'])
            ->middleware('permission:settings.edit')
            ->names('leave-policies');
        Route::post('leave-policies/{leavePolicy}/toggle', [LeavePolicyController::class, 'toggle'])->middleware('permission:settings.edit')->name('leave-policies.toggle');

        Route::get('leave-requests',                [LeaveRequestController::class, 'index'])->middleware('permission:leaves.view')->name('leave-requests.index');
        Route::post('leave-requests/{id}/approve',  [LeaveRequestController::class, 'approve'])->middleware('permission:leaves.approve')->name('leave-requests.approve');
        Route::post('leave-requests/{id}/reject',   [LeaveRequestController::class, 'reject'])->middleware('permission:leaves.reject')->name('leave-requests.reject');
        Route::delete('leave-requests/{id}',        [LeaveRequestController::class, 'destroy'])->middleware('permission:leaves.reject')->name('leave-requests.destroy');
    });

    // Staff ID card requests (admin management)
    Route::get('staff-card-requests',              [StaffCardRequestController::class, 'adminIndex'])->middleware('permission:students.card-request')->name('staff-card-request.admin');
    Route::patch('staff-card-requests/{staffCardRequest}', [StaffCardRequestController::class, 'updateStatus'])->middleware('permission:students.card-request')->name('staff-card-request.status');

    Route::resource('department', DepartmentController::class)
        ->only(['index', 'show'])
        ->middleware('permission:settings.view');
    Route::resource('department', DepartmentController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('permission:settings.edit');
    Route::get('devices/index', [DeviceController::class, 'index'])->middleware('permission:settings.view')->name('device.index');
    Route::get('devices/sync',  [DeviceController::class, 'sync_online'])->middleware('permission:settings.edit')->name('device.sync_online');
});
