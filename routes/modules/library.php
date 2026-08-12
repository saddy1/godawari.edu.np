<?php

use App\Http\Controllers\Backend\LibraryController;
use Illuminate\Support\Facades\Route;

// Book search — requires login, redirects based on user role
Route::get('/library/search', [LibraryController::class, 'publicSearch'])
    ->middleware(['auth', 'module.enabled:library'])
    ->name('library.public.search');

// Patron self-view (logged-in ERP users)
Route::middleware(['auth', 'module.enabled:library'])->group(function () {
    Route::get('/my-library', [LibraryController::class, 'myLibrary'])->name('library.my-books');
    Route::post('/my-library/notifications/read', [LibraryController::class, 'markNotificationRead'])->name('library.notifications.read');
    Route::get('/my-library/notifications/count', [LibraryController::class, 'unreadNotificationsCount'])->name('library.notifications.count');
});

// Admin routes
Route::prefix('admin/library')
    ->name('admin.library.')
    ->middleware(['auth', 'admin', 'module.enabled:library'])
    ->group(function () {

        // Dashboard
        Route::get('/', [LibraryController::class, 'dashboard'])->middleware('permission:library.view')->name('dashboard');

        // Books
        Route::get('/books', [LibraryController::class, 'books'])->middleware('permission:library.view')->name('books.index');
        Route::get('/books/create', [LibraryController::class, 'createBook'])->middleware('permission:library.create')->name('books.create');
        Route::post('/books', [LibraryController::class, 'storeBook'])->middleware('permission:library.create')->name('books.store');
        Route::get('/books/search', [LibraryController::class, 'searchBooks'])->middleware('permission:library.view')->name('books.search');
        Route::get('/books/search/ajax', [LibraryController::class, 'searchBookField'])->middleware('permission:library.view')->name('books.search-field');
        Route::get('/books/{book}', [LibraryController::class, 'showBook'])->middleware('permission:library.view')->name('books.show');
        Route::get('/books/{book}/edit', [LibraryController::class, 'editBook'])->middleware('permission:library.edit')->name('books.edit');
        Route::put('/books/{book}', [LibraryController::class, 'updateBook'])->middleware('permission:library.edit')->name('books.update');
        Route::delete('/books/{book}', [LibraryController::class, 'destroyBook'])->middleware('permission:library.edit')->name('books.destroy');
        Route::post('/books/{book}/copies', [LibraryController::class, 'addCopies'])->middleware('permission:library.edit')->name('books.copies.store');
        Route::delete('/copies/{copy}', [LibraryController::class, 'destroyCopy'])->middleware('permission:library.edit')->name('copies.destroy');

        // Circulation
        Route::get('/issue-return', [LibraryController::class, 'issueReturn'])->middleware('permission:library.issue')->name('issue.index');
        Route::post('/issue', [LibraryController::class, 'issue'])->middleware('permission:library.issue')->name('issue.store');
        Route::post('/return', [LibraryController::class, 'returnByBarcode'])->middleware('permission:library.issue')->name('return.store');
        Route::get('/loans/lookup', [LibraryController::class, 'loanLookup'])->middleware('permission:library.issue')->name('loans.lookup');
        Route::post('/loans/{loan}/return', [LibraryController::class, 'returnLoan'])->middleware('permission:library.issue')->name('loans.return');
        Route::post('/loans/{loan}/renew', [LibraryController::class, 'renewLoan'])->middleware('permission:library.issue')->name('loans.renew');
        Route::post('/loans/{loan}/pay-fine', [LibraryController::class, 'payFine'])->middleware('permission:library.issue')->name('loans.pay-fine');

        // Patrons
        Route::get('/patrons', [LibraryController::class, 'patrons'])->middleware('permission:library.view')->name('patrons.index');

        // Fines
        Route::get('/fines', [LibraryController::class, 'fines'])->middleware('permission:library.view')->name('fines.index');

        // Rules & Categories
        Route::get('/rules', [LibraryController::class, 'rulesIndex'])->middleware('permission:library.edit')->name('rules.index');
        Route::put('/rules', [LibraryController::class, 'updateRules'])->middleware('permission:library.edit')->name('rules.update');
        Route::post('/categories', [LibraryController::class, 'storeCategory'])->middleware('permission:library.edit')->name('categories.store');
        Route::delete('/categories/{category}', [LibraryController::class, 'destroyCategory'])->middleware('permission:library.edit')->name('categories.destroy');

        // Patron Categories
        Route::post('/patron-categories', [LibraryController::class, 'storePatronCategory'])->middleware('permission:library.edit')->name('patron-categories.store');
        Route::put('/patron-categories/{patronCategory}', [LibraryController::class, 'updatePatronCategory'])->middleware('permission:library.edit')->name('patron-categories.update');
        Route::delete('/patron-categories/{patronCategory}', [LibraryController::class, 'destroyPatronCategory'])->middleware('permission:library.edit')->name('patron-categories.destroy');

        // My Books (admin's own issued books)
        Route::get('/my-books', [LibraryController::class, 'adminMyBooks'])->middleware('permission:library.view')->name('my-books.index');

        // Reports
        Route::get('/activity-logs', [LibraryController::class, 'activityLogs'])->middleware('permission:library.reports')->name('activity-logs.index');
        Route::get('/reports', [LibraryController::class, 'statistics'])->middleware('permission:library.reports')->name('reports.index');
        Route::get('/reports/download', [LibraryController::class, 'downloadReport'])->middleware('permission:library.reports')->name('reports.download');
        // backward compat alias
        Route::get('/statistics', [LibraryController::class, 'statistics'])->middleware('permission:library.reports')->name('statistics.index');

        // Search APIs
        Route::get('/people/search', [LibraryController::class, 'searchPeople'])->middleware('permission:library.view,library.issue')->name('people.search');
    });
