<?php

use App\Http\Controllers\Card\BulkCardController;
use App\Http\Controllers\Card\CardController;
use App\Http\Controllers\Card\SettingsController;
use App\Http\Controllers\Card\StudentAuthController;
use App\Http\Controllers\Card\StudentController;
use App\Http\Controllers\Card\StudentPortalController;
use App\Http\Controllers\Card\StudentVerificationController;
use App\Http\Controllers\Hr\CertificateController;
use Illuminate\Support\Facades\Route;

// Public card verification — no auth required
Route::get('/verify/student/{student}', [StudentVerificationController::class, 'show'])
    ->middleware('module.enabled:card')
    ->name('student.verify');

Route::prefix('admin/students')->middleware(['auth', 'admin', 'module.enabled:card'])->group(function () {
    Route::get('/', function () {
        $user = auth()->user();

        if ($user->canAccess('students.view')) {
            return redirect()->route('students.index');
        }

        if ($user->canAccess(['cards.view', 'cards.print'])) {
            return redirect()->route('bulk.index');
        }

        if ($user->canAccess('students.card-request')) {
            return redirect()->route('admin.card-requests');
        }

        if ($user->canAccess('card-settings.view')) {
            return redirect()->route('settings.index');
        }

        abort(403, 'You do not have permission to access the Student module. Please ask Super Admin for permission.');
    })->name('card.dashboard');

    Route::get('/students', [StudentController::class, 'index'])->middleware('permission:students.view')->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->middleware('permission:students.create')->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->middleware('permission:students.create')->name('students.store');
    Route::get('/students/{student}', [StudentController::class, 'show'])->middleware('permission:students.view')->name('students.show');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->middleware('permission:students.edit')->name('students.edit');
    Route::match(['put', 'patch'], '/students/{student}', [StudentController::class, 'update'])->middleware('permission:students.edit')->name('students.update');
    Route::get('/api/suggestions', [StudentController::class, 'suggestions'])->middleware('permission:students.view')->name('api.suggestions');
    Route::get('/api/member-form-options', [StudentController::class, 'formOptions'])->middleware('permission:students.create,students.edit')->name('api.member-form-options');
    Route::post('/students/bulk-valid-till', [StudentController::class, 'bulkValidTill'])->middleware('permission:students.edit')->name('students.bulk-valid-till');
    Route::post('/bulk-valid-till', [StudentController::class, 'bulkValidTill'])->middleware('permission:students.edit')->name('students.bulk-valid-till.alias');
    Route::post('/students/bulk-learning-accounts', [StudentController::class, 'bulkLearningAccounts'])->middleware('permission:students.edit')->name('students.bulk-learning-accounts');

    // Certificates
    Route::get('/certificates', [CertificateController::class, 'index'])->middleware('permission:hr.certificates.view')->name('certificates.index');
    Route::get('/certificates/students/search', [CertificateController::class, 'searchStudents'])->middleware('permission:hr.certificates.create')->name('certificates.students.search');
    Route::get('/certificates/create', [CertificateController::class, 'create'])->middleware('permission:hr.certificates.create')->name('certificates.create');
    Route::post('/certificates', [CertificateController::class, 'store'])->middleware('permission:hr.certificates.create')->name('certificates.store');
    Route::get('/certificates/{certificate}/print', [CertificateController::class, 'print'])->middleware('permission:hr.certificates.view')->name('certificates.print');
    Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->middleware('permission:hr.certificates.view')->name('certificates.show');
    Route::delete('/certificates/{certificate}', [CertificateController::class, 'destroy'])->middleware('permission:hr.certificates.delete')->name('certificates.destroy');


    Route::prefix('cards/{student}')->name('cards.')->middleware('permission:cards.view,cards.print')->group(function () {
        Route::get('preview/{type}', [CardController::class, 'preview'])->name('preview');
        Route::get('render/{type}', [CardController::class, 'render'])->name('render');
        Route::get('print/{type}', [CardController::class, 'printSingle'])->name('print');
        Route::get('print-all', [CardController::class, 'printAll'])->name('print-all');
        Route::get('download/{type}', [CardController::class, 'download'])->name('download');
        Route::get('download-all', [CardController::class, 'downloadAll'])->name('all');
    });

    Route::prefix('bulk')->name('bulk.')->middleware('permission:cards.view,cards.print')->group(function () {
        Route::get('/', [BulkCardController::class, 'index'])->name('index');
        Route::get('search', [BulkCardController::class, 'search'])->name('search');
        Route::post('preview', [BulkCardController::class, 'preview'])->name('preview');
        Route::post('generate', [BulkCardController::class, 'generate'])->name('generate');
        Route::post('mark-printed', [BulkCardController::class, 'markPrinted'])->name('mark-printed');
    });

    Route::prefix('requests')->name('admin.')->middleware('permission:students.card-request')->group(function () {
        Route::get('/card-requests', [StudentPortalController::class, 'adminCardRequests'])->name('card-requests');
        Route::patch('/card-requests/{cardRequest}', [StudentPortalController::class, 'adminUpdateCardRequest'])->name('card-requests.update');
        Route::get('/update-requests', [StudentPortalController::class, 'adminUpdateRequests'])->name('update-requests');
        Route::post('/update-requests/{updateRequest}', [StudentPortalController::class, 'adminReviewUpdateRequest'])
            ->withoutMiddleware('permission:students.card-request')
            ->middleware('permission:students.card-request,hr.members.edit,students.edit')
            ->name('update-requests.review');
    });

    Route::middleware('permission:card-settings.view,card-settings.edit,card-settings.create,card-settings.delete')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/api/departments/{organization}', [SettingsController::class, 'departments'])->name('api.departments');
        Route::get('/api/sections/{department}', [SettingsController::class, 'sectionsForDept'])->name('api.sections');
        Route::post('/organizations', [SettingsController::class, 'storeOrganization'])->middleware('permission:card-settings.create')->name('organizations.store');
        Route::patch('/organizations/{organization}', [SettingsController::class, 'updateOrganization'])->middleware('permission:card-settings.edit')->name('organizations.update');
        Route::delete('/organizations/{organization}', [SettingsController::class, 'destroyOrganization'])->middleware('permission:card-settings.delete')->name('organizations.destroy');
        Route::post('/departments', [SettingsController::class, 'storeDepartment'])->middleware('permission:card-settings.create')->name('departments.store');
        Route::patch('/departments/{department}', [SettingsController::class, 'updateDepartment'])->middleware('permission:card-settings.edit')->name('departments.update');
        Route::delete('/departments/{department}', [SettingsController::class, 'destroyDepartment'])->middleware('permission:card-settings.delete')->name('departments.destroy');
        Route::post('/sections', [SettingsController::class, 'storeSection'])->middleware('permission:card-settings.create')->name('sections.store');
        Route::patch('/sections/{section}', [SettingsController::class, 'updateSection'])->middleware('permission:card-settings.edit')->name('sections.update');
        Route::delete('/sections/{section}', [SettingsController::class, 'destroySection'])->middleware('permission:card-settings.delete')->name('sections.destroy');
        Route::post('/member-types', [SettingsController::class, 'storeMemberType'])->middleware('permission:card-settings.create')->name('member-types.store');
        Route::patch('/member-types/{memberType}', [SettingsController::class, 'updateMemberType'])->middleware('permission:card-settings.edit')->name('member-types.update');
        Route::delete('/member-types/{memberType}', [SettingsController::class, 'destroyMemberType'])->middleware('permission:card-settings.delete')->name('member-types.destroy');
        Route::post('/assets', [SettingsController::class, 'storeAsset'])->middleware('permission:card-settings.create')->name('assets.store');
        Route::delete('/assets/{orgAsset}', [SettingsController::class, 'destroyAsset'])->middleware('permission:card-settings.delete')->name('assets.destroy');
        Route::post('/backgrounds', [SettingsController::class, 'storeBackground'])->middleware('permission:card-settings.create')->name('backgrounds.store');
        Route::post('/backgrounds/{cardBackground}/activate', [SettingsController::class, 'activateBackground'])->middleware('permission:card-settings.edit')->name('backgrounds.activate');
        Route::delete('/backgrounds/{cardBackground}', [SettingsController::class, 'destroyBackground'])->middleware('permission:card-settings.delete')->name('backgrounds.destroy');
    });

});

Route::get('admin/id-card/{path?}', function (?string $path = null) {
    return redirect('admin/students' . ($path ? '/' . $path : ''));
})->where('path', '.*')->middleware(['auth', 'admin', 'module.enabled:card']);

Route::get('/student/card/login', [StudentAuthController::class, 'showLogin'])->middleware('module.enabled:card')->name('student.login');
Route::post('/student/card/login', [StudentAuthController::class, 'login'])->middleware('module.enabled:card')->name('student.login.post');
Route::post('/student/card/logout', [StudentAuthController::class, 'logout'])->middleware('module.enabled:card')->name('student.logout');

Route::middleware(['card.student', 'module.enabled:card'])->prefix('student/card')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/attendance', [StudentPortalController::class, 'attendance'])->name('attendance');
    Route::get('/learning', [StudentPortalController::class, 'learning'])->name('learning');
    Route::get('/card-status', [StudentPortalController::class, 'cardStatus'])->name('card-status');
    Route::post('/request-card', [StudentPortalController::class, 'requestCard'])->name('request-card');
    Route::get('/profile', [StudentPortalController::class, 'editProfile'])->name('profile.edit');
    Route::match(['post', 'patch'], '/profile', [StudentPortalController::class, 'updateProfile'])->name('profile.update');
    Route::post('/photo', [StudentPortalController::class, 'uploadPhoto'])->name('upload-photo');
    Route::get('/request-update', [StudentPortalController::class, 'requestUpdateForm'])->name('request-update');
    Route::post('/request-update', [StudentPortalController::class, 'submitUpdate'])->name('submit-update');
    Route::get('/books/search', [\App\Http\Controllers\Backend\LibraryController::class, 'publicSearch'])->name('books.search');
    Route::get('/library', [StudentPortalController::class, 'myLibrary'])->name('library');
    Route::get('/change-password', [StudentPortalController::class, 'changePasswordForm'])->name('change-password');
    Route::post('/change-password', [StudentPortalController::class, 'changePassword'])->name('change-password.update');
});
