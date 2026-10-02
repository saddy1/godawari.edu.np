<?php

use App\Http\Controllers\Counselling\CounsellingController;
use Illuminate\Support\Facades\Route;

// No blanket permission at the group level — index()/store()/etc. each decide
// per-request whether the viewer is an admin (counselling.manage) or a
// counsellor seeing only their own caseload, since both need access here.
Route::prefix('admin/counselling')
    ->name('admin.counselling.')
    ->middleware(['auth', 'module.enabled:counselling'])
    ->group(function () {
        Route::get('/', [CounsellingController::class, 'index'])->name('index');
        Route::post('/', [CounsellingController::class, 'store'])->name('store');
        Route::get('/{counsellingSession}', [CounsellingController::class, 'show'])->name('show');
        Route::patch('/{counsellingSession}/assign', [CounsellingController::class, 'assign'])->name('assign');
        Route::patch('/{counsellingSession}/cancel', [CounsellingController::class, 'cancel'])->name('cancel');
        Route::put('/{counsellingSession}/report', [CounsellingController::class, 'saveReport'])->name('report.save');
    });
