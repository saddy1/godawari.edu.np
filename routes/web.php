<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\AdmissionsController;
use App\Http\Controllers\AcademicsController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\NoticesController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Backend\AnnouncementController;
use App\Http\Controllers\Backend\MediaController;
use App\Http\Controllers\Backend\SeoController;
use App\Http\Controllers\Backend\AdmissionController;
use App\Http\Controllers\Backend\PopupNoticeController;
use App\Http\Controllers\Backend\TestimonialController;
use App\Http\Controllers\Backend\ContactMessageController;
use App\Http\Controllers\Backend\FacultyController;
use App\Http\Controllers\Backend\HomeBannerController;
use App\Http\Controllers\Backend\HomeContentController;
use App\Http\Controllers\Backend\CmsMenuController;
use App\Http\Controllers\Backend\CmsPageController as BackendCmsPageController;
use App\Http\Controllers\Backend\BillingController;
use App\Http\Controllers\Backend\PayrollController;
use App\Http\Controllers\Backend\StoreController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\Backend\AdminUserController;
use App\Http\Controllers\VacancyController;
use App\Http\Controllers\Backend\VacancyController as BackendVacancyController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ApplicantLoginController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\AccountController;
use App\Http\Controllers\Backend\ModuleController;
use App\Http\Controllers\Work\WorkChecklistController;
use App\Http\Controllers\Work\WorkGroupController;
use App\Http\Controllers\Work\WorkTaskController;
use App\Http\Controllers\Backend\KeyPersonController;
use App\Http\Controllers\Backend\QuickLinkController;
/*

|--------------------------------------------------------------------------
| Web Routes — Godawari College
|--------------------------------------------------------------------------
*/

Route::get('/language/{locale}', function (string $locale, \Illuminate\Http\Request $request) {
    abort_unless(in_array($locale, ['en', 'ne'], true), 404);

    session(['locale' => $locale]);
    cookie()->queue(cookie('locale', $locale, 60 * 24 * 365));

    $redirect = $request->query('redirect', url()->previous());
    if (! is_string($redirect) || ! str_starts_with($redirect, url('/'))) {
        $redirect = route('home');
    }

    return redirect()->to($redirect ?: route('home'));
})->name('language.switch');

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');

// About
Route::get('/about', [AboutController::class, 'index'])->name('about');

// Admissions
Route::get('/admissions', [App\Http\Controllers\AdmissionsController::class, 'index'])->middleware('module.enabled:admissions')->name('admissions');
Route::post('/admissions', [App\Http\Controllers\AdmissionsController::class, 'storeAdmission'])->middleware(['module.enabled:admissions', 'throttle:5,1'])->name('admissions.store');

// Preserve authority from the retired school-era academic URLs.
Route::permanentRedirect('/academics/elementary', '/pages/bsc-csit')->name('academics.elementary');
Route::permanentRedirect('/academics/primary', '/pages/bbs')->name('academics.primary');
Route::permanentRedirect('/academics/secondary', '/')->name('academics.secondary');

// Gallery
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');

// Events
Route::get('/events', [EventsController::class, 'index'])->name('events');
Route::get('/events/{event:slug}', [EventsController::class, 'show'])->name('events.show');

// Notices
Route::get('/notices', [NoticesController::class, 'index'])->name('notices');
Route::get('/notices/{notice:slug}', [NoticesController::class, 'show'])->name('notices.show');

//news and events
Route::get('/news', [EventsController::class, 'news'])->name('news');
Route::get('/news/{news:slug}', [NoticesController::class, 'show'])->name('news.show');
// Contact
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'storeContact'])->middleware('throttle:5,1')->name('contact.submit');

// Vacancies (public listing, apply requires verified auth)
Route::get('/vacancies', [VacancyController::class, 'index'])->middleware('module.enabled:vacancy')->name('vacancies');
Route::get('/vacancies/{vacancy}/apply', [VacancyController::class, 'createApplication'])
    ->middleware(['auth', 'verified', 'module.enabled:vacancy'])
    ->name('vacancy.apply.create');
Route::post('/vacancies/{vacancy}/apply', [VacancyController::class, 'apply'])
    ->middleware(['auth', 'verified', 'module.enabled:vacancy'])
    ->name('vacancy.apply');

// Legal pages
Route::view('/privacy-policy', 'pages.privacy')->name('privacy');
Route::view('/terms-of-service', 'pages.terms')->name('terms');
Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');
Route::get('/pages/{slug}', [CmsPageController::class, 'show'])->name('cms.pages.show');

//faculty and staff

Route::get('/Frontend/faculty', [App\Http\Controllers\FacultyController::class, 'index'])->name('frontend.faculty');

Route::get('/staff', [HomeController::class, 'staff'])->name('staff');

// Admin — routes to be added later when admin dashboard is built
// Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
//     Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
//     // ... more admin routes
// });




// ── Admin login (admin-only users) ───────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Applicant registration & login ───────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->middleware('module.enabled:vacancy')->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('module.enabled:vacancy');
    Route::get('/applicant/login', [ApplicantLoginController::class, 'showLoginForm'])->middleware('module.enabled:vacancy')->name('applicant.login');
    Route::post('/applicant/login', [ApplicantLoginController::class, 'login'])->middleware('module.enabled:vacancy')->name('applicant.login.submit');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});
Route::post('/applicant/logout', [ApplicantLoginController::class, 'logout'])
    ->name('applicant.logout')
    ->middleware('auth');
Route::middleware('auth')->group(function () {
    Route::get('/account/profile', [AccountController::class, 'editProfile'])->name('account.profile.edit');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::get('/account/password', [AccountController::class, 'editPassword'])->name('account.password.edit');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::get('/account/applications', [VacancyController::class, 'myApplications'])->middleware('module.enabled:vacancy')->name('account.applications.index');
    Route::get('/account/applications/{application}', [VacancyController::class, 'showApplication'])->middleware('module.enabled:vacancy')->name('account.applications.show');
});

Route::prefix('admin/work-tasks')
    ->name('admin.work-tasks.')
    ->middleware(['auth', 'module.enabled:work_tasks'])
    ->group(function () {
        Route::get('/', [WorkTaskController::class, 'index'])->middleware('permission:work-tasks.view')->name('index');
        Route::post('/', [WorkTaskController::class, 'store'])->middleware('permission:work-tasks.create')->name('store');

        Route::get('/checklists', [WorkChecklistController::class, 'index'])->middleware('permission:work-checklists.manage')->name('checklists.index');
        Route::post('/checklists', [WorkChecklistController::class, 'store'])->middleware('permission:work-checklists.manage')->name('checklists.store');
        Route::delete('/checklists/{checklist}', [WorkChecklistController::class, 'destroy'])->middleware('permission:work-checklists.manage')->name('checklists.destroy');
        Route::get('/groups', [WorkGroupController::class, 'index'])->middleware('permission:work-groups.manage')->name('groups.index');
        Route::post('/groups', [WorkGroupController::class, 'store'])->middleware('permission:work-groups.manage')->name('groups.store');
        Route::patch('/groups/{group}', [WorkGroupController::class, 'update'])->middleware('permission:work-groups.manage')->name('groups.update');
        Route::delete('/groups/{group}', [WorkGroupController::class, 'destroy'])->middleware('permission:work-groups.manage')->name('groups.destroy');
        Route::get('/review-queue', [WorkTaskController::class, 'reviewQueue'])->middleware('permission:work-tasks.review')->name('review-queue.index');

        Route::delete('/{task}', [WorkTaskController::class, 'destroy'])->middleware('permission:work-tasks.create')->name('destroy');
        Route::get('/{task}', [WorkTaskController::class, 'show'])->middleware('permission:work-tasks.view')->name('show');
        Route::post('/{task}/submit', [WorkTaskController::class, 'submit'])->middleware('permission:work-tasks.submit')->name('submit');
        Route::post('/{task}/submissions/{submission}/review', [WorkTaskController::class, 'review'])->middleware('permission:work-tasks.review')->name('review');
    });

// ── Email verification ────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('vacancies')->with('status', 'Email verified! You can now apply for vacancies.');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            return back()->with('mail_error', 'Verification email could not be sent: ' . $exception->getMessage());
        }

        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');
});

// ── Google OAuth ──────────────────────────────────────────────────────────────
Route::get('/auth/google', [SocialiteController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::get('/my-store-items', [StoreController::class, 'myIssuedItems'])
    ->middleware(['auth', 'verified'])
    ->name('store.my-items');
Route::middleware(['auth', 'module.enabled:billing'])->group(function () {
    Route::get('/my-payslips', [PayrollController::class, 'myIndex'])->name('payroll.mine');
    Route::get('/my-payslips/{payslip}', [PayrollController::class, 'myShow'])->name('payroll.mine.show');
});
// backend dashboard route
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {

    // Dashboard Route: matches /admin/dashboard
    // The controller keeps Website Dashboard data permission-protected and
    // redirects module-only admins to the first area they can access.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::prefix('billing')->name('admin.billing.')->middleware('module.enabled:billing')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->middleware('permission:billing.view')->name('index');
        Route::get('/create', [BillingController::class, 'create'])->middleware('permission:billing.create')->name('create');
        Route::post('/', [BillingController::class, 'store'])->middleware('permission:billing.create')->name('store');
        Route::get('/people/search', [BillingController::class, 'searchPeople'])->middleware('permission:billing.create')->name('people.search');
        Route::get('/items/search', [BillingController::class, 'searchItems'])->middleware('permission:billing.create')->name('items.search');
        Route::prefix('payroll')->name('payroll.')->group(function () {
            Route::get('/', [PayrollController::class, 'index'])->middleware('permission:billing.view')->name('index');
            Route::get('/template', [PayrollController::class, 'downloadTemplate'])->middleware('permission:billing.create')->name('template');
            Route::get('/upload', [PayrollController::class, 'upload'])->middleware('permission:billing.create')->name('upload');
            Route::post('/preview', [PayrollController::class, 'preview'])->middleware('permission:billing.create')->name('preview');
            Route::post('/commit', [PayrollController::class, 'commit'])->middleware('permission:billing.create')->name('commit');
            Route::get('/batch/{batch}', [PayrollController::class, 'batch'])->middleware('permission:billing.view')->name('batch');
            Route::get('/batch/{batch}/print', [PayrollController::class, 'printBatch'])->middleware('permission:billing.view')->name('batch.print');
            Route::get('/payslip/{payslip}', [PayrollController::class, 'show'])->middleware('permission:billing.view')->name('show');
        });
        Route::get('/{bill}', [BillingController::class, 'show'])->middleware('permission:billing.view')->name('show');
        Route::delete('/{bill}', [BillingController::class, 'destroy'])->middleware('permission:billing.delete')->name('destroy');
    });

    Route::prefix('store')->name('admin.store.')->middleware('module.enabled:store')->group(function () {
        Route::get('/', [StoreController::class, 'dashboard'])->middleware('permission:store.view')->name('dashboard');
        Route::get('/hr-members/search', [StoreController::class, 'searchHrMembers'])->middleware('permission:store.view,store.create')->name('hr-members.search');
        Route::get('/items', [StoreController::class, 'itemsIndex'])->middleware('permission:store.view')->name('items.index');
        Route::get('/items/search', [StoreController::class, 'searchItems'])->middleware('permission:store.view,store.create')->name('items.search');
        Route::get('/requisitions/search', [StoreController::class, 'searchRequisitions'])->middleware('permission:store.view,store.create')->name('requisitions.search');
        Route::get('/purchase-orders/search', [StoreController::class, 'searchPurchaseOrders'])->middleware('permission:store.view,store.create')->name('purchase-orders.search');
        Route::get('/suppliers', [StoreController::class, 'suppliersIndex'])->middleware('permission:store.view')->name('suppliers.index');
        Route::post('/suppliers', [StoreController::class, 'storeSupplier'])->middleware('permission:store.create')->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit', [StoreController::class, 'editSupplier'])->middleware('permission:store.edit')->name('suppliers.edit');
        Route::patch('/suppliers/{supplier}', [StoreController::class, 'updateSupplier'])->middleware('permission:store.edit')->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [StoreController::class, 'destroySupplier'])->middleware('permission:store.delete')->name('suppliers.destroy');
        Route::get('/categories', [StoreController::class, 'categoriesIndex'])->middleware('permission:store.view')->name('categories.index');
        Route::post('/categories', [StoreController::class, 'storeCategory'])->middleware('permission:store.create')->name('categories.store');
        Route::get('/categories/{category}/edit', [StoreController::class, 'editCategory'])->middleware('permission:store.edit')->name('categories.edit');
        Route::patch('/categories/{category}', [StoreController::class, 'updateCategory'])->middleware('permission:store.edit')->name('categories.update');
        Route::delete('/categories/{category}', [StoreController::class, 'destroyCategory'])->middleware('permission:store.delete')->name('categories.destroy');
        Route::get('/brands', [StoreController::class, 'brandsIndex'])->middleware('permission:store.view')->name('brands.index');
        Route::post('/brands', [StoreController::class, 'storeBrand'])->middleware('permission:store.create')->name('brands.store');
        Route::get('/brands/{brand}/edit', [StoreController::class, 'editBrand'])->middleware('permission:store.edit')->name('brands.edit');
        Route::patch('/brands/{brand}', [StoreController::class, 'updateBrand'])->middleware('permission:store.edit')->name('brands.update');
        Route::delete('/brands/{brand}', [StoreController::class, 'destroyBrand'])->middleware('permission:store.delete')->name('brands.destroy');
        Route::get('/units', [StoreController::class, 'unitsIndex'])->middleware('permission:store.view')->name('units.index');
        Route::post('/units', [StoreController::class, 'storeUnit'])->middleware('permission:store.create')->name('units.store');
        Route::get('/units/{unit}/edit', [StoreController::class, 'editUnit'])->middleware('permission:store.edit')->name('units.edit');
        Route::patch('/units/{unit}', [StoreController::class, 'updateUnit'])->middleware('permission:store.edit')->name('units.update');
        Route::delete('/units/{unit}', [StoreController::class, 'destroyUnit'])->middleware('permission:store.delete')->name('units.destroy');
        Route::post('/fiscal-year', [StoreController::class, 'storeFiscalYear'])->middleware('permission:store.create')->name('fiscal-year.store');
        Route::get('/requisitions', [StoreController::class, 'requisitionsIndex'])->middleware('permission:store.view')->name('requisitions.index');
        Route::post('/requisitions', [StoreController::class, 'storeRequisition'])->middleware('permission:store.create')->name('requisitions.store');
        Route::get('/purchase-orders', [StoreController::class, 'purchaseOrdersIndex'])->middleware('permission:store.view')->name('purchase-orders.index');
        Route::post('/purchase-orders', [StoreController::class, 'storePurchaseOrder'])->middleware('permission:store.create')->name('purchase-orders.store');
        Route::get('/receipts', [StoreController::class, 'receiptsIndex'])->middleware('permission:store.view')->name('receipts.index');
        Route::post('/receipts', [StoreController::class, 'storeReceipt'])->middleware('permission:store.create')->name('receipts.store');
        Route::get('/issues', [StoreController::class, 'issuesIndex'])->middleware('permission:store.view')->name('issues.index');
        Route::post('/issues', [StoreController::class, 'storeIssue'])->middleware('permission:store.create')->name('issues.store');
        Route::post('/issue-items/{issueItem}/return', [StoreController::class, 'returnIssueItem'])->middleware('permission:store.edit')->name('issue-items.return');
        Route::get('/slips', [StoreController::class, 'slipsIndex'])->middleware('permission:store.view')->name('slips.index');
        Route::get('/reports', [StoreController::class, 'reportsIndex'])->middleware('permission:store.view,store.reports')->name('reports.index');
        Route::get('/documents/{type}/{id}/edit', [StoreController::class, 'edit'])->middleware('permission:store.edit')->name('documents.edit');
        Route::patch('/documents/{type}/{id}', [StoreController::class, 'update'])->middleware('permission:store.edit')->name('documents.update');
        Route::delete('/documents/{type}/{id}', [StoreController::class, 'destroy'])->middleware('permission:store.delete')->name('documents.destroy');
        Route::get('/forms/{type}/{id?}', [StoreController::class, 'form'])->middleware('permission:store.view,store.reports')->name('forms.show');
    });

    // Staff roles and module access — super-admin only
    Route::middleware('super_admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->middleware('permission:users.view')->name('admin.users.index');
        Route::get('/users/hr-members/search', [AdminUserController::class, 'searchHrMembers'])->middleware('permission:users.create')->name('admin.users.hr-members.search');
        Route::post('/users', [AdminUserController::class, 'store'])->middleware('permission:users.create')->name('admin.users.store');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->middleware('permission:users.edit')->name('admin.users.update-role');
        Route::patch('/users/{user}/password', [AdminUserController::class, 'resetPassword'])->middleware('permission:users.edit')->name('admin.users.reset-password');
        Route::patch('/users/{user}/super-admin', [AdminUserController::class, 'toggleSuperAdmin'])->name('admin.users.toggle-super-admin');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->middleware('permission:users.delete')->name('admin.users.destroy');
        Route::get('/users/{user}/permissions', [AdminUserController::class, 'permissions'])->name('admin.users.permissions');
        Route::patch('/users/{user}/permissions', [AdminUserController::class, 'updatePermissions'])->name('admin.users.permissions.update');

        Route::get('/modules', [ModuleController::class, 'index'])->name('admin.modules.index');
        Route::post('/modules/{key}/toggle', [ModuleController::class, 'toggle'])->name('admin.modules.toggle');
    });

    Route::resource('announcements', AnnouncementController::class, ['as' => 'admin'])
        ->only(['index'])
        ->middleware('permission:announcements.view');
    Route::resource('announcements', AnnouncementController::class, ['as' => 'admin'])
        ->only(['create', 'store'])
        ->middleware('permission:announcements.create');
    Route::resource('announcements', AnnouncementController::class, ['as' => 'admin'])
        ->only(['edit', 'update'])
        ->middleware('permission:announcements.edit');
    Route::resource('announcements', AnnouncementController::class, ['as' => 'admin'])
        ->only(['destroy'])
        ->middleware('permission:announcements.delete');
    // Route to force inline PDF viewing
    Route::get('/announcements/{announcement}/view-file', [App\Http\Controllers\Backend\AnnouncementController::class, 'viewFile'])->middleware('permission:announcements.view')->name('admin.announcements.view_file');;

    Route::prefix('admin')->name('admin.')->group(function () {
        // ... your other admin routes ...

        Route::post('/faculty/groups', [FacultyController::class, 'storeGroup'])->middleware('permission:faculty.create')->name('faculty.groups.store');
        Route::get('/faculty/groups/{group}', [FacultyController::class, 'showGroup'])->middleware('permission:faculty.view')->name('faculty.groups.show');
        Route::get('/faculty/groups/{group}/edit', [FacultyController::class, 'editGroup'])->middleware('permission:faculty.edit')->name('faculty.groups.edit');
        Route::put('/faculty/groups/{group}', [FacultyController::class, 'updateGroup'])->middleware('permission:faculty.edit')->name('faculty.groups.update');
        Route::delete('/faculty/groups/{group}', [FacultyController::class, 'destroyGroup'])->middleware('permission:faculty.delete')->name('faculty.groups.destroy');

        Route::resource('faculty', FacultyController::class)
            ->only(['index'])
            ->middleware('permission:faculty.view');
        Route::resource('faculty', FacultyController::class)
            ->only(['create', 'store'])
            ->middleware('permission:faculty.create');
        Route::resource('faculty', FacultyController::class)
            ->only(['edit', 'update'])
            ->middleware('permission:faculty.edit');
        Route::resource('faculty', FacultyController::class)
            ->only(['destroy'])
            ->middleware('permission:faculty.delete');

        Route::get('/media', [MediaController::class, 'index'])
            ->middleware('permission:media.view,announcements.create,announcements.edit')
            ->name('media.index');
        Route::post('/media', [MediaController::class, 'store'])
            ->middleware('permission:media.create,announcements.create,announcements.edit')
            ->name('media.store');
        Route::get('/seo', [SeoController::class, 'index'])->middleware(['super_admin', 'permission:settings.view'])->name('seo.index');
        Route::post('/seo/generate', [SeoController::class, 'generate'])->middleware(['super_admin', 'permission:settings.edit'])->name('seo.generate');
        Route::post('/seo/store', [SeoController::class, 'store'])->middleware(['super_admin', 'permission:settings.edit'])->name('seo.store');



        Route::middleware('module.enabled:admissions')->group(function () {
            Route::get('/admissions', [AdmissionController::class, 'index'])->middleware('permission:students.admission')->name('admissions.index');
            Route::get('/admissions/{admission}', [AdmissionController::class, 'show'])->middleware('permission:students.admission')->name('admissions.show');
            Route::put('/admissions/{admission}', [AdmissionController::class, 'update'])->middleware('permission:students.edit')->name('admissions.update');
            Route::delete('/admissions/{admission}', [AdmissionController::class, 'destroy'])->middleware('permission:students.delete')->name('admissions.destroy');
        });

        // Popup Management
    Route::get('/popups', [PopupNoticeController::class, 'index'])->middleware('permission:popups.view')->name('popups.index');
    Route::post('/popups', [PopupNoticeController::class, 'store'])->middleware('permission:popups.create')->name('popups.store');
    Route::get('/popups/{popup}/edit', [PopupNoticeController::class, 'edit'])->middleware('permission:popups.edit')->name('popups.edit'); // NEW
    Route::put('/popups/{popup}', [PopupNoticeController::class, 'update'])->middleware('permission:popups.edit')->name('popups.update'); // NEW
    Route::delete('/popups/{popup}', [PopupNoticeController::class, 'destroy'])->middleware('permission:popups.delete')->name('popups.destroy');
    Route::patch('/popups/{popup}/toggle', [PopupNoticeController::class, 'toggle'])->middleware('permission:popups.edit')->name('popups.toggle');



// Testimonial Management
    Route::get('/testimonials', [TestimonialController::class, 'index'])->middleware('permission:testimonials.view')->name('testimonials.index');
    Route::post('/testimonials', [TestimonialController::class, 'store'])->middleware('permission:testimonials.create')->name('testimonials.store');
    Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy'])->middleware('permission:testimonials.delete')->name('testimonials.destroy');
    Route::patch('/testimonials/{testimonial}/toggle', [TestimonialController::class, 'toggle'])->middleware('permission:testimonials.edit')->name('testimonials.toggle');



    // Global Settings — controlled by permissions
    Route::get('/settings', [\App\Http\Controllers\Backend\SettingController::class, 'index'])->middleware(['super_admin', 'permission:settings.view'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\Backend\SettingController::class, 'update'])->middleware(['super_admin', 'permission:settings.edit'])->name('settings.update');
    Route::post('/settings/test-mail', [\App\Http\Controllers\Backend\SettingController::class, 'testMail'])->middleware(['super_admin', 'permission:settings.edit'])->name('settings.test-mail');

    Route::get('/principal', [\App\Http\Controllers\Backend\PrincipalController::class, 'index'])->middleware(['super_admin', 'permission:settings.view'])->name('principal.index');
    Route::put('/principal', [\App\Http\Controllers\Backend\PrincipalController::class, 'update'])->middleware(['super_admin', 'permission:settings.edit'])->name('principal.update');

    Route::get('/home-content', [HomeContentController::class, 'index'])->middleware(['super_admin', 'permission:settings.view'])->name('home-content.index');
    Route::get('/home-content/create', [HomeContentController::class, 'create'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-content.create');
    Route::post('/home-content', [HomeContentController::class, 'store'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-content.store');
    Route::get('/home-content/{homeContent}/edit', [HomeContentController::class, 'edit'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-content.edit');
    Route::put('/home-content/{homeContent}', [HomeContentController::class, 'update'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-content.update');
    Route::delete('/home-content/{homeContent}', [HomeContentController::class, 'destroy'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-content.destroy');
    Route::patch('/home-content/{homeContent}/toggle', [HomeContentController::class, 'toggle'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-content.toggle');

    Route::get('/home-banners', [HomeBannerController::class, 'index'])->middleware(['super_admin', 'permission:settings.view'])->name('home-banners.index');
    Route::get('/home-banners/create', [HomeBannerController::class, 'create'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-banners.create');
    Route::post('/home-banners', [HomeBannerController::class, 'store'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-banners.store');
    Route::get('/home-banners/{homeBanner}/edit', [HomeBannerController::class, 'edit'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-banners.edit');
    Route::put('/home-banners/{homeBanner}', [HomeBannerController::class, 'update'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-banners.update');
    Route::delete('/home-banners/{homeBanner}', [HomeBannerController::class, 'destroy'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-banners.destroy');
    Route::patch('/home-banners/{homeBanner}/toggle', [HomeBannerController::class, 'toggle'])->middleware(['super_admin', 'permission:settings.edit'])->name('home-banners.toggle');

    Route::prefix('cms')->name('cms.')->middleware(['super_admin', 'permission:settings.view'])->group(function () {
        Route::get('/pages', [BackendCmsPageController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [BackendCmsPageController::class, 'create'])->middleware('permission:settings.edit')->name('pages.create');
        Route::post('/pages', [BackendCmsPageController::class, 'store'])->middleware('permission:settings.edit')->name('pages.store');
        Route::get('/pages/{page}/edit', [BackendCmsPageController::class, 'edit'])->middleware('permission:settings.edit')->name('pages.edit');
        Route::put('/pages/{page}', [BackendCmsPageController::class, 'update'])->middleware('permission:settings.edit')->name('pages.update');
        Route::delete('/pages/{page}', [BackendCmsPageController::class, 'destroy'])->middleware('permission:settings.edit')->name('pages.destroy');

        Route::get('/menus', [CmsMenuController::class, 'index'])->name('menus.index');
        Route::post('/menus', [CmsMenuController::class, 'store'])->middleware('permission:settings.edit')->name('menus.store');
        Route::put('/menus/{menu}', [CmsMenuController::class, 'update'])->middleware('permission:settings.edit')->name('menus.update');
        Route::post('/menus/{menu}/items', [CmsMenuController::class, 'storeItem'])->middleware('permission:settings.edit')->name('menus.items.store');
        Route::put('/menus/items/{item}', [CmsMenuController::class, 'updateItem'])->middleware('permission:settings.edit')->name('menus.items.update');
        Route::delete('/menus/items/{item}', [CmsMenuController::class, 'destroyItem'])->middleware('permission:settings.edit')->name('menus.items.destroy');
    });


Route::get('/contacts', [ContactMessageController::class, 'index'])->middleware('permission:contacts.view')->name('contacts.index');
Route::patch('/contacts/{message}/read', [ContactMessageController::class, 'markAsRead'])->middleware('permission:contacts.edit')->name('contacts.read');
Route::delete('/contacts/{message}', [ContactMessageController::class, 'destroy'])->middleware('permission:contacts.delete')->name('contacts.destroy');

        // Vacancy Management
        Route::middleware('module.enabled:vacancy')->group(function () {
            Route::get('/vacancies', [BackendVacancyController::class, 'index'])->middleware('permission:vacancies.view')->name('vacancies.index');
            Route::get('/vacancies/create', [BackendVacancyController::class, 'create'])->middleware('permission:vacancies.create')->name('vacancies.create');
            Route::post('/vacancies', [BackendVacancyController::class, 'store'])->middleware('permission:vacancies.create')->name('vacancies.store');
            Route::get('/vacancies/{vacancy}/edit', [BackendVacancyController::class, 'edit'])->middleware('permission:vacancies.edit')->name('vacancies.edit');
            Route::put('/vacancies/{vacancy}', [BackendVacancyController::class, 'update'])->middleware('permission:vacancies.edit')->name('vacancies.update');
            Route::delete('/vacancies/{vacancy}', [BackendVacancyController::class, 'destroy'])->middleware('permission:vacancies.delete')->name('vacancies.destroy');
            Route::patch('/vacancies/{vacancy}/toggle', [BackendVacancyController::class, 'toggle'])->middleware('permission:vacancies.edit')->name('vacancies.toggle');
            Route::get('/vacancies/{vacancy}/applications', [BackendVacancyController::class, 'applications'])->middleware('permission:vacancies.applications')->name('vacancies.applications');
            Route::get('/vacancy-applications/{application}', [BackendVacancyController::class, 'showApplication'])->middleware('permission:vacancies.applications')->name('vacancy-applications.show');
            Route::put('/vacancy-applications/{application}', [BackendVacancyController::class, 'updateApplication'])->middleware('permission:vacancies.applications')->name('vacancy-applications.update');
            Route::delete('/vacancy-applications/{application}', [BackendVacancyController::class, 'destroyApplication'])->middleware('permission:vacancies.applications')->name('vacancy-applications.destroy');
        });

    });

    // Key Personnel (Aadaksha section)
    Route::resource('key-persons', KeyPersonController::class, ['as' => 'admin'])->except('show', 'create', 'edit');
    Route::patch('key-persons/{keyPerson}/toggle', [KeyPersonController::class, 'toggleActive'])->name('admin.key-persons.toggle');

    // Quick Links (Footer)
    Route::resource('quick-links', QuickLinkController::class, ['as' => 'admin'])->except('show', 'create', 'edit');
    Route::patch('quick-links/{quickLink}/toggle', [QuickLinkController::class, 'toggleActive'])->name('admin.quick-links.toggle');

    Route::get('/gallery', [MediaController::class, 'gallery'])->middleware('permission:media.view')->name('admin.gallery.index');
    Route::post('/gallery/upload', [MediaController::class, 'uploadGallery'])->middleware('permission:media.create')->name('admin.gallery.upload');
    Route::post('/gallery/select', [MediaController::class, 'selectForGallery'])->middleware('permission:media.edit')->name('admin.gallery.select');
    Route::get('/media-library', [MediaController::class, 'library'])->middleware('permission:media.view')->name('admin.media-library.index');
    Route::post('/media-library/upload', [MediaController::class, 'uploadMultiple'])->middleware('permission:media.create')->name('admin.media-library.upload');
    Route::patch('/media/{media}', [MediaController::class, 'update'])->middleware('permission:media.edit')->name('admin.media.update');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->middleware('permission:media.delete')->name('admin.media.destroy');



    // Future Routes will go here:
    // Route::resource('seo', SeoController::class);
    // Route::resource('notices', NoticeController::class);

});

require __DIR__.'/modules/card.php';
require __DIR__.'/modules/hr.php';
require __DIR__.'/modules/hajiri.php';
require __DIR__.'/modules/learning.php';
require __DIR__.'/modules/library.php';
require __DIR__.'/modules/teaching_learning.php';
require __DIR__.'/modules/examinations.php';
