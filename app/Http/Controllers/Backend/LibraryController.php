<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\LibraryActivityLog;
use App\Models\LibraryBook;
use App\Models\LibraryBookCopy;
use App\Models\LibraryCategory;
use App\Models\LibraryLoan;
use App\Models\LibraryNotification;
use App\Models\LibraryPatronCategory;
use App\Models\LibraryRule;
use App\Models\User;
use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\Card\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibraryController extends Controller
{
    // ──────────────────────────────────────────────────────────────────
    // DASHBOARD
    // ──────────────────────────────────────────────────────────────────

    public function dashboard(): View
    {
        // Daily trend: last 30 days
        $dailyDays     = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());
        $dailyIssued   = $dailyDays->map(fn ($d) => LibraryLoan::whereDate('issued_at', $d)->count());
        $dailyReturned = $dailyDays->map(fn ($d) => LibraryLoan::where('status', 'returned')->whereDate('returned_at', $d)->count());

        // Low stock: books with 0 or 1 available copies
        $lowStockBooks = LibraryBook::with('category')
            ->withCount([
                'copies',
                'copies as available_copies_count' => fn ($q) => $q->where('status', 'available'),
            ])
            ->having('available_copies_count', '<=', 1)
            ->orderBy('available_copies_count')
            ->limit(10)
            ->get();

        return view('library-admin.dashboard', [
            'summary' => [
                'patrons'   => Student::whereIn('member_type', ['student', 'teacher', 'staff'])->count(),
                'books'     => LibraryBook::count(),
                'copies'    => LibraryBookCopy::count(),
                'issued'    => LibraryLoan::where('status', 'issued')->count(),
                'returned'  => LibraryLoan::where('status', 'returned')->count(),
                'overdue'   => LibraryLoan::where('status', 'issued')->whereDate('due_date', '<', now()->toDateString())->count(),
                'fine_due'  => LibraryLoan::sum(DB::raw('GREATEST(fine_amount - fine_paid, 0)')),
            ],
            'dailyDays'     => $dailyDays,
            'dailyIssued'   => $dailyIssued,
            'dailyReturned' => $dailyReturned,
            'lowStockBooks' => $lowStockBooks,
            'recentLoans'   => LibraryLoan::with(['copy.book', 'user', 'student'])->latest()->limit(8)->get(),
            'overdueLoans'  => LibraryLoan::with(['copy.book', 'user', 'student'])
                ->where('status', 'issued')
                ->whereDate('due_date', '<', now()->toDateString())
                ->oldest('due_date')
                ->limit(8)
                ->get(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // BOOKS
    // ──────────────────────────────────────────────────────────────────

    public function books(Request $request): View
    {
        $books = LibraryBook::with('category')
            ->withCount([
                'copies',
                'copies as available_copies_count' => fn ($q) => $q->where('status', 'available'),
                'copies as issued_copies_count'    => fn ($q) => $q->where('status', 'issued'),
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->query('search'));
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%")
                        ->orWhereHas('copies', fn ($cq) => $cq->where('accession_no', 'like', "%{$search}%")->orWhere('barcode', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('library-admin.books.index', ['books' => $books]);
    }

    public function createBook(): View
    {
        return view('library-admin.books.form', [
            'book'           => new LibraryBook(),
            'categories'     => LibraryCategory::orderBy('name')->get(),
            'nextAccessionNo' => $this->nextAccessionNo(),
        ]);
    }

    public function storeBook(Request $request): RedirectResponse
    {
        $validated = $this->validateBook($request);

        DB::transaction(function () use ($validated) {
            $book = LibraryBook::create(collect($validated)
                ->except(['copies_count'])
                ->merge(['created_by' => auth()->id()])
                ->all());

            $count = $this->createCopyCount($book, (int) ($validated['copies_count'] ?? 0));

            $this->logActivity('book_added', [
                'book_id'    => $book->id,
                'book_title' => $book->title,
                'details'    => "Added book \"{$book->title}\" with {$count} copies.",
            ]);
        });

        return redirect()->route('admin.library.books.index')->with('success', 'Book added successfully.');
    }

    public function showBook(LibraryBook $book): View
    {
        $book->load('category');

        return view('library-admin.books.show', [
            'book'           => $book,
            'items'          => $book->copies()
                ->with(['activeLoan.user', 'activeLoan.student'])
                ->orderByRaw('CAST(accession_no AS UNSIGNED), accession_no')
                ->paginate(20),
            'nextAccessionNo' => $this->nextAccessionNo(),
        ]);
    }

    public function editBook(LibraryBook $book): View
    {
        return view('library-admin.books.form', [
            'book'           => $book,
            'categories'     => LibraryCategory::orderBy('name')->get(),
            'nextAccessionNo' => $this->nextAccessionNo(),
        ]);
    }

    public function updateBook(Request $request, LibraryBook $book): RedirectResponse
    {
        $validated = $this->validateBook($request, $book);

        DB::transaction(function () use ($book, $validated) {
            $book->update(collect($validated)->except(['copies_count'])->all());
            $this->createCopyCount($book, (int) ($validated['copies_count'] ?? 0));
        });

        return redirect()->route('admin.library.books.show', $book)->with('success', 'Book updated successfully.');
    }

    public function destroyBook(LibraryBook $book): RedirectResponse
    {
        if ($book->copies()->exists()) {
            return back()->with('error', 'Cannot delete a book that has copies. Delete copies first.');
        }

        $book->delete();

        return redirect()->route('admin.library.books.index')->with('success', 'Book deleted successfully.');
    }

    public function addCopies(Request $request, LibraryBook $book): RedirectResponse
    {
        $validated = $request->validate([
            'copies_count' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $created = DB::transaction(fn () => $this->createCopyCount($book, (int) $validated['copies_count']));

        $this->logActivity('copies_added', [
            'book_id'    => $book->id,
            'book_title' => $book->title,
            'details'    => "Added {$created} copies to \"{$book->title}\".",
        ]);

        return back()->with('success', "{$created} book copy/copies added successfully.");
    }

    public function destroyCopy(LibraryBookCopy $copy): RedirectResponse
    {
        $loan = $copy->activeLoan()->with('user')->first();
        if ($loan) {
            return back()->with('error', "Cannot delete. Copy is issued to {$loan->borrower_name}.");
        }

        $copy->delete();

        return back()->with('success', 'Book copy deleted successfully.');
    }

    // ──────────────────────────────────────────────────────────────────
    // CIRCULATION: ISSUE & RETURN
    // ──────────────────────────────────────────────────────────────────

    public function issueReturn(Request $request): View
    {
        $borrower    = null;
        $activeLoans = collect();

        if ($request->filled('borrower')) {
            $borrower = $this->findBorrower((string) $request->query('borrower'));
            if ($borrower) {
                $activeLoans = LibraryLoan::with('copy.book')
                    ->where('status', 'issued')
                    ->where('student_id', $borrower->id)
                    ->latest('issued_at')
                    ->get();
            }
        }

        return view('library-admin.issue.index', [
            'borrower'    => $borrower,
            'activeLoans' => $activeLoans,
            'rules'       => $borrower ? $this->rulesForBorrower($borrower) : $this->rulesForBorrower(new Student()),
        ]);
    }

    public function issue(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'borrower_student_id' => ['required', 'exists:students,id'],
            'barcode'          => ['required', 'string'],
            'issued_at'        => ['nullable', 'date'],
            'due_date'         => ['nullable', 'date', 'after_or_equal:issued_at'],
            'remarks'          => ['nullable', 'string'],
        ]);

        $result = DB::transaction(function () use ($validated): array {
            $copy = LibraryBookCopy::with('book')
                ->where('barcode', $validated['barcode'])
                ->orWhere('accession_no', $validated['barcode'])
                ->lockForUpdate()
                ->first();

            if (! $copy) {
                throw ValidationException::withMessages(['barcode' => 'Book copy not found.']);
            }

            if ($copy->status !== 'available') {
                throw ValidationException::withMessages(['barcode' => 'This book copy is already issued or unavailable.']);
            }

            $borrower      = Student::findOrFail($validated['borrower_student_id']);
            $patronRules   = $this->rulesForBorrower($borrower, $copy->book);
            $activeCount   = LibraryLoan::where('student_id', $borrower->id)->where('status', 'issued')->count();

            if ($activeCount >= (int) $patronRules['max_active_books']) {
                throw ValidationException::withMessages(['borrower_student_id' => "This borrower already has the maximum allowed {$patronRules['max_active_books']} active books (based on their patron category)."]);
            }

            if ((bool) $patronRules['block_same_title']) {
                $hasSameTitle = LibraryLoan::where('student_id', $borrower->id)
                    ->where('status', 'issued')
                    ->whereHas('copy', fn ($q) => $q->where('library_book_id', $copy->library_book_id))
                    ->exists();

                if ($hasSameTitle) {
                    throw ValidationException::withMessages(['barcode' => 'This borrower already has this book title.']);
                }
            }

            $issuedAt = Carbon::parse($validated['issued_at'] ?? now()->toDateString());
            $dueDate  = ! empty($validated['due_date'])
                ? Carbon::parse($validated['due_date'])
                : $issuedAt->copy()->addDays((int) $patronRules['loan_days']);

            $loan = LibraryLoan::create([
                'library_book_copy_id' => $copy->id,
                'user_id'              => $borrower->user_id,
                'student_id'           => $borrower->id,
                'borrower_name'        => $borrower->full_name,
                'borrower_identifier'  => $borrower->roll_number ?: $borrower->registration_no ?: $borrower->email,
                'borrower_type'        => ucfirst($borrower->member_type),
                'issued_at'            => $issuedAt->toDateString(),
                'due_date'             => $dueDate->toDateString(),
                'status'               => 'issued',
                'remarks'              => $validated['remarks'] ?? null,
                'issued_by'            => auth()->id(),
            ]);

            $copy->update(['status' => 'issued']);

            $bookTitle    = $copy->book?->title ?? $validated['barcode'];
            $borrowerName = $borrower->full_name;

            // Activity log
            $this->logActivity('book_issued', [
                'loan_id'             => $loan->id,
                'book_copy_id'        => $copy->id,
                'book_id'             => $copy->library_book_id,
                'user_id'             => $borrower->user_id,
                'borrower_name'       => $borrowerName,
                'borrower_identifier' => $loan->borrower_identifier,
                'borrower_type'       => $loan->borrower_type,
                'book_title'          => $bookTitle,
                'accession_no'        => $copy->accession_no,
                'details'             => "Issued \"{$bookTitle}\" (Acc# {$copy->accession_no}) to {$borrowerName}. Due: {$dueDate->toDateString()}.",
            ]);

            // Notification
            if ($borrower->user_id) {
                LibraryNotification::create([
                    'user_id' => $borrower->user_id,
                    'title'   => 'Book Issued',
                    'message' => "Book \"{$bookTitle}\" has been issued to you. Please return it by {$dueDate->format('d M Y')}.",
                    'type'    => 'info',
                    'loan_id' => $loan->id,
                ]);
            }

            return [
                'title'    => $bookTitle,
                'borrower' => $borrowerName,
                'due_date' => $dueDate->toDateString(),
            ];
        });

        return back()->with('success', "Book \"{$result['title']}\" issued to {$result['borrower']}. Due: {$result['due_date']}.");
    }

    public function returnByBarcode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'barcode'        => ['required', 'string'],
            'returned_at'    => ['nullable', 'date'],
            'fine_paid'      => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:cash,esewa,khalti,bank,other'],
            'payment_txn'    => ['nullable', 'string', 'max:100'],
            'remarks'        => ['nullable', 'string'],
        ]);

        $copy = LibraryBookCopy::where('barcode', $validated['barcode'])
            ->orWhere('accession_no', $validated['barcode'])
            ->first();

        if (! $copy) {
            throw ValidationException::withMessages(['barcode' => 'Book copy not found.']);
        }

        $loan = LibraryLoan::with('student')->where('library_book_copy_id', $copy->id)
            ->where('status', 'issued')
            ->first();

        if (! $loan) {
            throw ValidationException::withMessages(['barcode' => 'No active allocation found for this book copy.']);
        }

        return $this->returnLoan($request, $loan);
    }

    public function loanLookup(Request $request): JsonResponse
    {
        $barcode = trim((string) $request->query('barcode', ''));
        if (! $barcode) {
            return response()->json(null);
        }

        $copy = LibraryBookCopy::with('book')
            ->where('barcode', $barcode)
            ->orWhere('accession_no', $barcode)
            ->first();

        if (! $copy) {
            return response()->json(['error' => 'Book copy not found.']);
        }

        $loan = LibraryLoan::where('library_book_copy_id', $copy->id)
            ->where('status', 'issued')
            ->first();

        if (! $loan) {
            return response()->json(['error' => 'No active loan found for this barcode.']);
        }

        $borrower = $loan->user ?: $loan->student;
        $patronRules = $this->rulesForBorrower($borrower ?: new Student(), $copy->book ?? null);
        $returnedAt   = now();
        $daysLate     = max(0, Carbon::parse($loan->due_date)->diffInDays($returnedAt, false));
        $fine         = $daysLate * (float) $patronRules['fine_per_day'];

        return response()->json([
            'book_title'    => $copy->book?->title,
            'borrower_name' => $loan->borrower_name,
            'issued_at'     => $loan->issued_at?->format('Y-m-d'),
            'due_date'      => $loan->due_date?->format('Y-m-d'),
            'days_late'     => $daysLate,
            'fine'          => $fine,
            'fine_per_day'  => (float) $patronRules['fine_per_day'],
        ]);
    }

    public function returnLoan(Request $request, LibraryLoan $loan): RedirectResponse
    {
        $validated = $request->validate([
            'returned_at'    => ['nullable', 'date'],
            'fine_paid'      => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:cash,esewa,khalti,bank,other'],
            'payment_txn'    => ['nullable', 'string', 'max:100'],
            'remarks'        => ['nullable', 'string'],
        ]);

        if ($loan->status !== 'issued') {
            return back()->with('info', 'This allocation is already closed.');
        }

        $loan->loadMissing(['copy.book', 'user', 'student']);
        $borrower = $loan->user ?: $loan->student;
        $patronRules = $this->rulesForBorrower($borrower ?: new Student(), $loan->copy?->book ?? null);

        $returnedAt = Carbon::parse($validated['returned_at'] ?? now()->toDateString());
        $daysLate   = max(0, Carbon::parse($loan->due_date)->diffInDays($returnedAt, false));
        $fine       = $daysLate * (float) $patronRules['fine_per_day'];
        $previouslyPaid = (float) $loan->fine_paid;
        $outstandingFine = max(0, $fine - $previouslyPaid);
        $paymentNow = (float) ($validated['fine_paid'] ?? 0);

        if ($outstandingFine > 0 && $paymentNow <= 0) {
            return back()
                ->withErrors(['fine_paid' => "Fine of Rs. {$outstandingFine} is outstanding. Collect the fine to proceed."])
                ->withInput();
        }

        if ($paymentNow > $outstandingFine) {
            return back()
                ->withErrors(['fine_paid' => "Amount cannot exceed outstanding fine of Rs. {$outstandingFine}."])
                ->withInput();
        }

        if ($paymentNow > 0 && empty($validated['payment_method'])) {
            return back()
                ->withErrors(['payment_method' => 'Select the payment method used to collect the fine.'])
                ->withInput();
        }

        $newFinePaid = $previouslyPaid + $paymentNow;

        DB::transaction(function () use ($loan, $validated, $returnedAt, $fine, $paymentNow, $newFinePaid) {
            $loan->update([
                'returned_at'    => $returnedAt->toDateString(),
                'status'         => 'returned',
                'fine_amount'    => $fine,
                'fine_paid'      => $newFinePaid,
                'payment_method' => $paymentNow > 0 ? $validated['payment_method'] : $loan->payment_method,
                'payment_txn'    => $paymentNow > 0 ? ($validated['payment_txn'] ?? null) : $loan->payment_txn,
                'remarks'        => $validated['remarks'] ?? $loan->remarks,
                'returned_by'    => auth()->id(),
            ]);

            $loan->copy()->update(['status' => 'available']);

            $bookTitle    = $loan->copy?->book?->title ?? 'Unknown Book';
            $borrowerName = $loan->borrower_name;
            $accessionNo  = $loan->copy?->accession_no;

            // Activity log
            $this->logActivity('book_returned', [
                'loan_id'             => $loan->id,
                'book_copy_id'        => $loan->library_book_copy_id,
                'book_id'             => $loan->copy?->library_book_id,
                'user_id'             => $loan->user_id,
                'borrower_name'       => $borrowerName,
                'borrower_identifier' => $loan->borrower_identifier,
                'borrower_type'       => $loan->borrower_type,
                'book_title'          => $bookTitle,
                'accession_no'        => $accessionNo,
                'fine_amount'         => $fine,
                'details'             => "Returned \"{$bookTitle}\" (Acc# {$accessionNo}) by {$borrowerName}."
                    . ($fine > 0 ? " Fine: Rs. {$fine}." : ''),
            ]);

            // Notification
            if ($loan->user_id) {
                $msg = "Book \"{$bookTitle}\" has been returned successfully.";
                if ($fine > 0) {
                    $msg .= " A fine of Rs. {$fine} was collected.";
                }
                LibraryNotification::create([
                    'user_id' => $loan->user_id,
                    'title'   => 'Book Returned',
                    'message' => $msg,
                    'type'    => $fine > 0 ? 'warning' : 'success',
                    'loan_id' => $loan->id,
                ]);

                if ($fine > 0) {
                    $this->logActivity('fine_collected', [
                        'loan_id'      => $loan->id,
                        'user_id'      => $loan->user_id,
                        'borrower_name' => $borrowerName,
                        'book_title'   => $bookTitle,
                        'fine_amount'  => $fine,
                        'details'      => "Fine of Rs. {$fine} collected from {$borrowerName} for late return of \"{$bookTitle}\".",
                    ]);
                }
            }
        });

        $bookTitle = $loan->copy?->book?->title ?? 'Unknown Book';
        $fineNote  = $fine > 0 ? " Fine of Rs. {$fine} collected." : '';

        return back()->with('success', "Book \"{$bookTitle}\" returned by {$loan->borrower_name} successfully.{$fineNote}");
    }

    public function payFine(Request $request, LibraryLoan $loan): RedirectResponse
    {
        $validated = $request->validate([
            'fine_paid'      => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:cash,esewa,khalti,bank,other'],
            'payment_txn'    => ['nullable', 'string', 'max:100'],
        ]);

        $loan->loadMissing(['copy.book', 'user', 'student']);
        $outstanding = $loan->status === 'issued'
            ? $loan->accrued_fine
            : $loan->outstanding_fine;

        if ($outstanding <= 0) {
            return back()->with('info', 'No outstanding fine on this record.');
        }

        if ((float) $validated['fine_paid'] > $outstanding) {
            return back()
                ->withErrors(['fine_paid' => "Amount cannot exceed outstanding fine of Rs. {$outstanding}."])
                ->withInput();
        }

        $newFinePaid = (float) $loan->fine_paid + (float) $validated['fine_paid'];

        DB::transaction(function () use ($loan, $validated, $newFinePaid) {
            $loan->update([
                'fine_paid'      => $newFinePaid,
                'payment_method' => $validated['payment_method'],
                'payment_txn'    => $validated['payment_txn'] ?? null,
            ]);

            $bookTitle    = $loan->copy?->book?->title ?? 'Unknown Book';
            $borrowerName = $loan->borrower_name;

            $this->logActivity('fine_collected', [
                'loan_id'       => $loan->id,
                'user_id'       => $loan->user_id,
                'borrower_name' => $borrowerName,
                'book_title'    => $bookTitle,
                'fine_amount'   => $validated['fine_paid'],
                'details'       => "Fine payment of Rs. {$validated['fine_paid']} via {$validated['payment_method']} from {$borrowerName}."
                    . ($validated['payment_txn'] ? " Txn: {$validated['payment_txn']}." : ''),
            ]);

            if ($loan->user_id) {
                LibraryNotification::create([
                    'user_id' => $loan->user_id,
                    'title'   => 'Fine Payment Recorded',
                    'message' => "Fine payment of Rs. {$validated['fine_paid']} for \"{$bookTitle}\" has been recorded.",
                    'type'    => 'success',
                    'loan_id' => $loan->id,
                ]);
            }
        });

        return back()->with('success', "Fine payment of Rs. {$validated['fine_paid']} recorded successfully.");
    }

    public function renewLoan(Request $request, LibraryLoan $loan): RedirectResponse
    {
        if ($loan->status !== 'issued') {
            return back()->with('info', 'This book has already been returned.');
        }

        $loan->loadMissing(['copy.book', 'user', 'student']);

        $accruedFine = $loan->accrued_fine;
        if ($accruedFine > 0) {
            return back()->withErrors([
                'renew' => "Cannot renew: an outstanding fine of Rs. {$accruedFine} must be paid first.",
            ]);
        }

        $maxRenewals = 2;
        if ((int) $loan->renewal_count >= $maxRenewals) {
            return back()->withErrors([
                'renew' => "Maximum renewals ({$maxRenewals}) reached for this loan.",
            ]);
        }

        $borrower = $loan->user ?: $loan->student;
        $patronRules = $this->rulesForBorrower($borrower ?: new Student(), $loan->copy?->book ?? null);

        $loanDays   = max(1, (int) $patronRules['loan_days']);
        $newDueDate = Carbon::parse($loan->due_date)->addDays($loanDays);

        DB::transaction(function () use ($loan, $newDueDate, $loanDays) {
            $loan->update([
                'due_date'      => $newDueDate->toDateString(),
                'renewal_count' => (int) $loan->renewal_count + 1,
                'renewed_at'    => now()->toDateString(),
                'renewed_by'    => auth()->id(),
            ]);

            $bookTitle    = $loan->copy?->book?->title ?? 'Unknown Book';
            $borrowerName = $loan->borrower_name;
            $accessionNo  = $loan->copy?->accession_no;

            $this->logActivity('book_renewed', [
                'loan_id'       => $loan->id,
                'user_id'       => $loan->user_id,
                'borrower_name' => $borrowerName,
                'book_title'    => $bookTitle,
                'accession_no'  => $accessionNo,
                'details'       => "Renewed \"{$bookTitle}\" (Acc# {$accessionNo}) for {$borrowerName}. New due date: {$newDueDate->toDateString()} (+{$loanDays} days).",
            ]);

            if ($loan->user_id) {
                LibraryNotification::create([
                    'user_id' => $loan->user_id,
                    'title'   => 'Book Renewed',
                    'message' => "Your loan for \"{$bookTitle}\" has been renewed. New due date: {$newDueDate->format('d M Y')}.",
                    'type'    => 'info',
                    'loan_id' => $loan->id,
                ]);
            }
        });

        $bookTitle = $loan->copy?->book?->title ?? 'Unknown Book';
        return back()->with('success', "Loan renewed for \"{$bookTitle}\". New due date: {$newDueDate->format('d M Y')}.");
    }

    // ──────────────────────────────────────────────────────────────────
    // PATRONS
    // ──────────────────────────────────────────────────────────────────

    public function patrons(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $patrons = Student::query()
            ->withCount([
                'libraryLoans',
                'libraryLoans as active_loans_count' => fn ($query) => $query->where('status', 'issued'),
            ])
            ->whereIn('member_type', ['student', 'teacher', 'staff'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) LIKE ?", ["%{$search}%"])
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('registration_no', 'like', "%{$search}%")
                        ->orWhere('roll_number', 'like', "%{$search}%")
                        ->orWhere('library_id', 'like', "%{$search}%")
                        ->orWhere('stream', 'like', "%{$search}%")
                        ->orWhere('program', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhere('batch', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('member_type', $request->query('type')))
            ->when($request->filled('organization'), fn ($query) => $query->where('organization', $request->query('organization')))
            ->when($request->filled('stream'), fn ($query) => $query->where('stream', $request->query('stream')))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        $organizations = Organization::query()->where('is_active', true)->orderBy('name')->get(['name', 'slug']);
        $classes = Student::query()
            ->whereIn('member_type', ['student', 'teacher', 'staff'])
            ->whereNotNull('stream')
            ->where('stream', '!=', '')
            ->distinct()
            ->orderBy('stream')
            ->pluck('stream');

        return view('library-admin.patrons.index', compact('patrons', 'organizations', 'classes'));
    }

    // ──────────────────────────────────────────────────────────────────
    // FINES (ADMIN)
    // ──────────────────────────────────────────────────────────────────

    public function fines(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $filter = $request->query('filter', 'outstanding');   // outstanding | all | paid

        $query = LibraryLoan::with(['copy.book', 'user', 'student'])
            ->where(function ($q) {
                $q->where('fine_amount', '>', 0)->orWhere('status', 'issued');
            });

        if ($filter === 'outstanding') {
            $query->whereRaw('GREATEST(fine_amount - fine_paid, 0) > 0')
                ->orWhere(fn ($q) => $q->where('status', 'issued')->whereDate('due_date', '<', now()->toDateString()));
        } elseif ($filter === 'paid') {
            $query->where('fine_amount', '>', 0)->whereColumn('fine_paid', '>=', 'fine_amount');
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('borrower_name', 'like', "%{$search}%")
                    ->orWhere('borrower_identifier', 'like', "%{$search}%")
                    ->orWhereHas('copy.book', fn ($bq) => $bq->where('title', 'like', "%{$search}%"));
            });
        }

        $fines = $query->latest('issued_at')->paginate(25)->withQueryString();

        $totalOutstanding = LibraryLoan::sum(DB::raw('GREATEST(fine_amount - fine_paid, 0)'));
        $overdueCount     = LibraryLoan::where('status', 'issued')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        return view('library-admin.fines.index', compact('fines', 'totalOutstanding', 'overdueCount', 'filter'));
    }

    // ──────────────────────────────────────────────────────────────────
    // STATISTICS
    // ──────────────────────────────────────────────────────────────────

    public function statistics(): View
    {
        // Daily trend: last 30 days (for line chart)
        $dailyDays     = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->startOfDay());
        $dailyIssued   = $dailyDays->map(fn ($d) => LibraryLoan::whereDate('issued_at', $d)->count());
        $dailyReturned = $dailyDays->map(fn ($d) => LibraryLoan::where('status', 'returned')->whereDate('returned_at', $d)->count());

        // Monthly trend: last 6 months (bar chart)
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));
        $monthlyIssued   = $months->map(fn ($m) => LibraryLoan::whereYear('issued_at', $m->year)->whereMonth('issued_at', $m->month)->count());
        $monthlyReturned = $months->map(fn ($m) => LibraryLoan::whereYear('returned_at', $m->year)->whereMonth('returned_at', $m->month)->where('status', 'returned')->count());

        // Top borrowers
        $topBorrowers = LibraryLoan::select('borrower_name', 'borrower_type', DB::raw('COUNT(*) as total'))
            ->groupBy('borrower_name', 'borrower_type')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Category distribution
        $categoryStats = LibraryCategory::withCount('books')->get();

        // Fine stats
        $fineStats = [
            'total_fined'     => LibraryLoan::where('fine_amount', '>', 0)->count(),
            'total_collected' => LibraryLoan::sum('fine_paid'),
            'total_pending'   => LibraryLoan::sum(DB::raw('GREATEST(fine_amount - fine_paid, 0)')),
        ];

        // Summary counts for report cards
        $totalBooks   = LibraryBook::count();
        $totalCopies  = LibraryBookCopy::count();
        $totalIssued  = LibraryLoan::where('status', 'issued')->count();
        $totalOverdue = LibraryLoan::where('status', 'issued')->whereDate('due_date', '<', now()->toDateString())->count();

        return view('library-admin.reports.statistics', compact(
            'dailyDays', 'dailyIssued', 'dailyReturned',
            'months', 'monthlyIssued', 'monthlyReturned',
            'topBorrowers', 'categoryStats', 'fineStats',
            'totalBooks', 'totalCopies', 'totalIssued', 'totalOverdue'
        ));
    }

    // ──────────────────────────────────────────────────────────────────
    // REPORT DOWNLOADS (CSV)
    // ──────────────────────────────────────────────────────────────────

    public function downloadReport(Request $request): StreamedResponse
    {
        $type = $request->query('type', 'books_by_category');
        $from = $request->query('from', now()->subMonth()->toDateString());
        $to   = $request->query('to', now()->toDateString());

        return match ($type) {
            'books_by_category' => $this->csvBooksByCategory(),
            'daily_issues'      => $this->csvDailyIssues($from, $to),
            'daily_returns'     => $this->csvDailyReturns($from, $to),
            'overdue'           => $this->csvOverdue(),
            'fines'             => $this->csvFines($from, $to),
            'all_books'         => $this->csvAllBooks(),
            default             => abort(404, 'Unknown report type'),
        };
    }

    private function csvStream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvBooksByCategory(): StreamedResponse
    {
        $rows = LibraryCategory::withCount('books')
            ->with(['books' => fn ($q) => $q->withCount('copies')])
            ->orderBy('name')
            ->get()
            ->flatMap(fn ($cat) => $cat->books->map(fn ($b) => [
                $cat->name,
                $b->title,
                $b->author ?? '',
                $b->isbn ?? '',
                $b->copies_count,
            ]));

        return $this->csvStream('books_by_category_' . now()->format('Ymd') . '.csv',
            ['Category', 'Title', 'Author', 'ISBN', 'Total Copies'], $rows);
    }

    private function csvAllBooks(): StreamedResponse
    {
        $rows = LibraryBook::with('category')
            ->withCount([
                'copies',
                'copies as available_copies_count' => fn ($q) => $q->where('status', 'available'),
                'copies as issued_copies_count'    => fn ($q) => $q->where('status', 'issued'),
            ])
            ->orderBy('title')
            ->get()
            ->map(fn ($b) => [
                $b->title,
                $b->author ?? '',
                $b->isbn ?? '',
                $b->category?->name ?? '',
                $b->copies_count,
                $b->available_copies_count,
                $b->issued_copies_count,
            ]);

        return $this->csvStream('all_books_' . now()->format('Ymd') . '.csv',
            ['Title', 'Author', 'ISBN', 'Category', 'Total Copies', 'Available', 'Issued'], $rows);
    }

    private function csvDailyIssues(string $from, string $to): StreamedResponse
    {
        $rows = LibraryLoan::with(['copy.book', 'user'])
            ->whereBetween('issued_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderBy('issued_at')
            ->get()
            ->map(fn ($l) => [
                Carbon::parse($l->issued_at)->format('Y-m-d'),
                $l->borrower_name,
                $l->borrower_type,
                $l->borrower_identifier ?? '',
                $l->book_title ?? $l->copy?->book?->title ?? '',
                $l->copy?->accession_no ?? '',
                Carbon::parse($l->due_date)->format('Y-m-d'),
            ]);

        return $this->csvStream("daily_issues_{$from}_{$to}.csv",
            ['Date', 'Borrower', 'Type', 'ID/Code', 'Book Title', 'Accession No', 'Due Date'], $rows);
    }

    private function csvDailyReturns(string $from, string $to): StreamedResponse
    {
        $rows = LibraryLoan::with(['copy.book', 'user'])
            ->where('status', 'returned')
            ->whereBetween('returned_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderBy('returned_at')
            ->get()
            ->map(fn ($l) => [
                Carbon::parse($l->returned_at)->format('Y-m-d'),
                $l->borrower_name,
                $l->borrower_type,
                $l->borrower_identifier ?? '',
                $l->book_title ?? $l->copy?->book?->title ?? '',
                $l->copy?->accession_no ?? '',
                number_format((float) $l->fine_amount, 2),
                number_format((float) $l->fine_paid, 2),
            ]);

        return $this->csvStream("daily_returns_{$from}_{$to}.csv",
            ['Date Returned', 'Borrower', 'Type', 'ID/Code', 'Book Title', 'Accession No', 'Fine Amount', 'Fine Paid'], $rows);
    }

    private function csvOverdue(): StreamedResponse
    {
        $today = now()->toDateString();
        $rows  = LibraryLoan::with(['copy.book', 'user'])
            ->where('status', 'issued')
            ->whereDate('due_date', '<', $today)
            ->oldest('due_date')
            ->get()
            ->map(fn ($l) => [
                $l->borrower_name,
                $l->borrower_type,
                $l->borrower_identifier ?? '',
                $l->book_title ?? $l->copy?->book?->title ?? '',
                $l->copy?->accession_no ?? '',
                Carbon::parse($l->issued_at)->format('Y-m-d'),
                Carbon::parse($l->due_date)->format('Y-m-d'),
                now()->startOfDay()->diffInDays($l->due_date, false) * -1,
                number_format((float) $l->fine_amount, 2),
            ]);

        return $this->csvStream('overdue_books_' . now()->format('Ymd') . '.csv',
            ['Borrower', 'Type', 'ID/Code', 'Book Title', 'Accession No', 'Issued Date', 'Due Date', 'Days Overdue', 'Fine Accrued'], $rows);
    }

    private function csvFines(string $from, string $to): StreamedResponse
    {
        $rows = LibraryLoan::with(['copy.book', 'user'])
            ->where('fine_amount', '>', 0)
            ->whereBetween('issued_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderByDesc('fine_amount')
            ->get()
            ->map(fn ($l) => [
                $l->borrower_name,
                $l->borrower_type,
                $l->book_title ?? $l->copy?->book?->title ?? '',
                Carbon::parse($l->issued_at)->format('Y-m-d'),
                $l->returned_at ? Carbon::parse($l->returned_at)->format('Y-m-d') : 'Not returned',
                number_format((float) $l->fine_amount, 2),
                number_format((float) $l->fine_paid, 2),
                number_format(max(0, (float) $l->fine_amount - (float) $l->fine_paid), 2),
                $l->status,
            ]);

        return $this->csvStream("fines_{$from}_{$to}.csv",
            ['Borrower', 'Type', 'Book Title', 'Issue Date', 'Return Date', 'Fine Amount', 'Fine Paid', 'Balance', 'Status'], $rows);
    }

    // ──────────────────────────────────────────────────────────────────
    // ACTIVITY LOGS
    // ──────────────────────────────────────────────────────────────────

    public function activityLogs(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $action = $request->query('action');

        $logs = LibraryActivityLog::with(['performer'])
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('borrower_name', 'like', "%{$search}%")
                    ->orWhere('book_title', 'like', "%{$search}%")
                    ->orWhere('borrower_identifier', 'like', "%{$search}%")
                    ->orWhere('details', 'like', "%{$search}%");
            }))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $actions = LibraryActivityLog::select('action')->distinct()->pluck('action');

        return view('library-admin.reports.activity-logs', compact('logs', 'actions'));
    }

    // ──────────────────────────────────────────────────────────────────
    // RULES & PATRON CATEGORIES
    // ──────────────────────────────────────────────────────────────────

    public function rulesIndex(): View
    {
        return view('library-admin.rules.index', [
            'categories'       => LibraryCategory::orderBy('name')->get(),
            'patronCategories' => LibraryPatronCategory::with('catalogCategory')->orderBy('sort_order')->get(),
            'classOptions'     => $this->libraryClassOptions(),
            'departmentOptions' => $this->libraryDepartmentOptions(),
        ]);
    }

    public function updateRules(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rules'   => ['required', 'array'],
            'rules.*' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated['rules'] as $key => $value) {
            LibraryRule::where('key', $key)->update(['value' => $value]);
        }

        $this->logActivity('rule_updated', ['details' => 'Global library rules updated.']);

        return back()->with('success', 'Library rules updated.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120', 'unique:library_categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        LibraryCategory::create($validated);

        return back()->with('success', 'Catalog category added.');
    }

    public function destroyCategory(LibraryCategory $category): RedirectResponse
    {
        if ($category->books()->exists()) {
            return back()->with('error', 'Cannot delete a category used by books.');
        }

        $category->delete();

        return back()->with('success', 'Catalog category deleted.');
    }

    public function storePatronCategory(Request $request): RedirectResponse
    {
        $classGrades = $this->libraryClassOptions()->pluck('value')->all();
        $departmentScopes = $this->libraryDepartmentOptions()->pluck('value')->all();

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'patron_type'         => ['nullable', 'in:student,teacher,staff'],
            'library_category_id' => ['nullable', 'exists:library_categories,id'],
            'department_scope'    => ['nullable', Rule::in($departmentScopes)],
            'class_from'          => ['nullable', 'integer', Rule::in($classGrades)],
            'class_to'            => ['nullable', 'integer', Rule::in($classGrades), 'gte:class_from'],
            'max_active_books'    => ['required', 'integer', 'min:1', 'max:50'],
            'loan_days'           => ['required', 'integer', 'min:1', 'max:365'],
            'fine_per_day'        => ['required', 'numeric', 'min:0'],
            'block_same_title'    => ['nullable', 'boolean'],
            'description'         => ['nullable', 'string', 'max:255'],
        ]);

        $validated['block_same_title'] = (bool) ($validated['block_same_title'] ?? false);
        $this->applyLibraryDepartmentScope($validated);
        $validated['sort_order']       = LibraryPatronCategory::where('slug', '!=', 'default-all')->max('sort_order') + 1;
        $validated['slug']             = $this->generateUniqueSlug($validated['name']);

        LibraryPatronCategory::create($validated);

        $this->logActivity('patron_category_updated', ['details' => "Patron category \"{$validated['name']}\" created."]);

        return back()->with('success', 'Patron category added.');
    }

    public function updatePatronCategory(Request $request, LibraryPatronCategory $patronCategory): RedirectResponse
    {
        $classGrades = $this->libraryClassOptions()->pluck('value')->all();
        $departmentScopes = $this->libraryDepartmentOptions()->pluck('value')->all();

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'patron_type'         => ['nullable', 'in:student,teacher,staff'],
            'library_category_id' => ['nullable', 'exists:library_categories,id'],
            'department_scope'    => ['nullable', Rule::in($departmentScopes)],
            'class_from'          => ['nullable', 'integer', Rule::in($classGrades)],
            'class_to'            => ['nullable', 'integer', Rule::in($classGrades), 'gte:class_from'],
            'max_active_books'    => ['required', 'integer', 'min:1', 'max:50'],
            'loan_days'           => ['required', 'integer', 'min:1', 'max:365'],
            'fine_per_day'        => ['required', 'numeric', 'min:0'],
            'block_same_title'    => ['nullable', 'boolean'],
            'description'         => ['nullable', 'string', 'max:255'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $validated['block_same_title'] = (bool) ($validated['block_same_title'] ?? false);
        $validated['is_active']        = (bool) ($validated['is_active'] ?? false);
        $this->applyLibraryDepartmentScope($validated);

        $patronCategory->update($validated);

        $this->logActivity('patron_category_updated', ['details' => "Patron category \"{$patronCategory->name}\" updated."]);

        return back()->with('success', 'Patron category updated.');
    }

    /**
     * Build the library grade choices from active school class masters.
     * Multiple streams such as "11 Science" and "11 Management" are
     * grouped under the same numeric grade because library rules store a
     * grade range rather than an individual department/stream.
     */
    private function libraryClassOptions()
    {
        return Department::query()
            ->where('is_active', true)
            ->whereHas('organization', fn ($query) => $query
                ->where('is_active', true)
                ->where('type', 'school'))
            ->orderBy('name')
            ->pluck('name')
            ->map(function (string $name): ?array {
                if (! preg_match('/(?<!\d)(\d{1,2})(?!\d)/', $name, $matches)) {
                    return null;
                }

                return [
                    'value' => (int) $matches[1],
                    'name'  => $name,
                ];
            })
            ->filter()
            ->groupBy('value')
            ->map(function ($classes, $grade): array {
                return [
                    'value' => (int) $grade,
                    'label' => 'Class '.$grade,
                ];
            })
            ->sortBy('value')
            ->values();
    }

    private function libraryDepartmentOptions()
    {
        return Department::query()
            ->with('organization:id,name,slug,type')
            ->where('is_active', true)
            ->whereHas('organization', fn ($query) => $query->where('is_active', true))
            ->get(['id', 'organization_id', 'name'])
            ->sortBy(fn (Department $department) => $department->organization->name.'|'.$department->name)
            ->map(fn (Department $department) => [
                'value' => $department->organization->slug.'::'.$department->name,
                'organization' => $department->organization->name,
                'organization_slug' => $department->organization->slug,
                'name' => $department->name,
                'label' => $department->name,
            ])
            ->values();
    }

    private function applyLibraryDepartmentScope(array &$validated): void
    {
        $scope = $validated['department_scope'] ?? null;
        unset($validated['department_scope']);

        if (! $scope) {
            $validated['organization_slug'] = null;
            $validated['department_name'] = null;
            return;
        }

        [$organization, $department] = explode('::', $scope, 2);
        $validated['organization_slug'] = $organization;
        $validated['department_name'] = $department;
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        $slug = $base;
        $i    = 2;
        while (LibraryPatronCategory::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    public function destroyPatronCategory(LibraryPatronCategory $patronCategory): RedirectResponse
    {
        $patronCategory->delete();

        return back()->with('success', 'Patron category deleted.');
    }

    // ──────────────────────────────────────────────────────────────────
    // MY LIBRARY (patron self-view)
    // ──────────────────────────────────────────────────────────────────

    public function myLibrary(Request $request): View|RedirectResponse
    {
        // Admins have a dedicated view inside the library admin panel
        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.library.my-books.index');
        }

        $user = $request->user()->loadMissing('student');

        $loans = LibraryLoan::with('copy.book')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);
                if ($user->student) {
                    $query->orWhere('student_id', $user->student->id);
                }
            })
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        $notifications = LibraryNotification::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        LibraryNotification::where('user_id', $user->id)->where('is_read', false)->update(['is_read' => true]);

        return view('library.my-books', ['loans' => $loans, 'notifications' => $notifications]);
    }

    // ──────────────────────────────────────────────────────────────────
    // ADMIN: MY BOOKS (books issued to the currently logged-in admin)
    // ──────────────────────────────────────────────────────────────────

    public function adminMyBooks(Request $request): View
    {
        $user = $request->user();

        $activeLoans = LibraryLoan::with('copy.book')
            ->where('user_id', $user->id)
            ->where('status', 'issued')
            ->orderBy('due_date')
            ->get();

        $historyLoans = LibraryLoan::with('copy.book')
            ->where('user_id', $user->id)
            ->where('status', 'returned')
            ->latest('returned_at')
            ->paginate(10)
            ->withQueryString();

        $overdueCount = $activeLoans->filter(fn ($l) => $l->due_date < now()->toDateString())->count();
        $fineOwed     = $activeLoans->sum(fn ($l) => $l->fine_balance);

        return view('library-admin.my-books.index', compact('activeLoans', 'historyLoans', 'overdueCount', 'fineOwed'));
    }

    public function markNotificationRead(Request $request): JsonResponse
    {
        LibraryNotification::where('user_id', auth()->id())
            ->when($request->filled('id'), fn ($q) => $q->where('id', $request->input('id')))
            ->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }

    // ──────────────────────────────────────────────────────────────────
    // PUBLIC BOOK SEARCH
    // ──────────────────────────────────────────────────────────────────

    public function publicSearch(Request $request): View|RedirectResponse
    {
        // Admin/staff → go to admin library book search
        if (auth()->user()?->isAdmin()) {
            $q = $request->query('q');
            return redirect()->route('admin.library.books.index', $q ? ['search' => $q] : []);
        }

        // Student portal users → show search within student portal layout
        $search = trim((string) $request->query('q'));
        $books  = collect();

        if (strlen($search) >= 2) {
            $books = LibraryBook::with('category')
                ->withCount([
                    'copies',
                    'copies as available_copies_count' => fn ($q) => $q->where('status', 'available'),
                    'copies as issued_copies_count'    => fn ($q) => $q->where('status', 'issued'),
                ])
                ->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%")
                        ->orWhere('publisher', 'like', "%{$search}%");
                })
                ->where('is_active', true)
                ->orderBy('title')
                ->paginate(20)
                ->withQueryString();
        }

        return view('library.search', compact('books', 'search'));
    }

    // ──────────────────────────────────────────────────────────────────
    // NOTIFICATIONS
    // ──────────────────────────────────────────────────────────────────

    public function unreadNotificationsCount(): JsonResponse
    {
        $count = LibraryNotification::where('user_id', auth()->id())->where('is_read', false)->count();
        return response()->json(['count' => $count]);
    }

    // ──────────────────────────────────────────────────────────────────
    // SEARCH APIs
    // ──────────────────────────────────────────────────────────────────

    public function searchPeople(Request $request): JsonResponse
    {
        $search = trim((string) ($request->query('q') ?: $request->query('query', '')));
        if (strlen($search) < 1) {
            return response()->json([]);
        }

        $members = Student::query()
            ->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) LIKE ?", ["%{$search}%"])
                    ->orWhere('roll_number', 'like', "%{$search}%")
                    ->orWhere('registration_no', 'like', "%{$search}%")
                    ->orWhere('library_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('stream', 'like', "%{$search}%")
                    ->orWhere('program', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('organization', 'like', "%{$search}%")
                    ->orWhere('batch', 'like', "%{$search}%");
            })
            ->orderByRaw(
                "CASE WHEN roll_number = ? OR registration_no = ? OR library_id = ? THEN 0 WHEN TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) = ? THEN 1 ELSE 2 END",
                [$search, $search, $search, $search]
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(20)
            ->get();

        $organizations = Organization::query()->pluck('name', 'slug');

        return response()->json($members->map(fn (Student $member) => [
            'id'           => $member->id,
            'selection'    => 'student:'.$member->id,
            'name'         => $member->full_name,
            'identifier'   => $member->roll_number ?: $member->registration_no ?: $member->library_id ?: $member->email,
            'type'         => ucfirst($member->member_type),
            'organization' => $organizations->get($member->organization, ucfirst((string) $member->organization)),
            'class'        => $member->stream ?: $member->program,
            'faculty'      => $member->member_type === 'student'
                ? preg_replace('/^\s*\d+\s*/', '', (string) ($member->stream ?: $member->program))
                : ($member->designation ?: $member->stream),
            'section'      => $member->section,
            'batch'        => $member->batch,
            'mobile'       => $member->mobile,
            'email'        => $member->email,
        ]));
    }

    public function searchBooks(Request $request): JsonResponse
    {
        $search = trim((string) ($request->query('q') ?: $request->query('query', '')));
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        return response()->json(
            LibraryBook::with('copies')
                ->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%")
                        ->orWhereHas('copies', fn ($cq) => $cq->where('accession_no', 'like', "%{$search}%")->orWhere('barcode', 'like', "%{$search}%"));
                })
                ->limit(10)
                ->get()
                ->map(fn (LibraryBook $book) => [
                    'id'            => $book->id,
                    'title'         => $book->title,
                    'author'        => $book->author,
                    'isbn'          => $book->isbn,
                    'matched_barcode' => $book->copies->first()?->barcode,
                ])
        );
    }

    public function searchBookField(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('query', ''));
        $field  = (string) $request->query('field', '');

        if (strlen($search) < 2 || ! in_array($field, ['title', 'author', 'publisher'], true)) {
            return response()->json([]);
        }

        return response()->json(
            LibraryBook::where($field, 'like', "%{$search}%")
                ->whereNotNull($field)
                ->select($field)
                ->distinct()
                ->limit(8)
                ->pluck($field)
        );
    }

    // ──────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────

    private function rulesForBorrower(User|Student $borrower, ?LibraryBook $book = null): array
    {
        $hardDefault = [
            'loan_days'        => 14,
            'max_active_books' => 3,
            'fine_per_day'     => 2,
            'block_same_title' => 1,
            '_category'        => 'System default',
        ];

        $categories = LibraryPatronCategory::active()->orderBy('sort_order')->get();

        foreach ($categories as $category) {
            $matches = $borrower instanceof Student
                ? $category->matchesMember($borrower, $book)
                : $category->matchesBorrower($borrower, $book);
            if ($matches) {
                return [
                    'loan_days'        => $category->loan_days,
                    'max_active_books' => $category->max_active_books,
                    'fine_per_day'     => (float) $category->fine_per_day,
                    'block_same_title' => $category->block_same_title ? 1 : 0,
                    '_category'        => $category->name,
                ];
            }
        }

        return $hardDefault;
    }

    private function logActivity(string $action, array $data = []): void
    {
        LibraryActivityLog::create(array_merge([
            'action'       => $action,
            'performed_by' => auth()->id(),
        ], $data));
    }

    private function validateBook(Request $request, ?LibraryBook $book = null): array
    {
        $validated = $request->validate([
            'library_category_id' => ['nullable', 'exists:library_categories,id'],
            'title'               => ['required', 'string', 'max:255'],
            'author'              => ['required', 'string', 'max:255'],
            'isbn'                => ['nullable', 'string', 'max:80', Rule::unique('library_books', 'isbn')->ignore($book)],
            'publisher'           => ['nullable', 'string', 'max:255'],
            'publication_year'    => ['nullable', 'integer', 'between:1800,' . ((int) date('Y') + 1)],
            'edition'             => ['nullable', 'string', 'max:80'],
            'price'               => ['nullable', 'numeric', 'min:0'],
            'pages'               => ['nullable', 'integer', 'min:1'],
            'source'              => ['nullable', 'string', 'max:255'],
            'shelf_location'      => ['nullable', 'string', 'max:120'],
            'description'         => ['nullable', 'string'],
            'copies_count'        => [$book ? 'nullable' : 'required', 'integer', 'min:1', 'max:500'],
        ]);

        $validated['isbn'] = $validated['isbn'] ?: null;

        return $validated;
    }

    private function createCopyCount(LibraryBook $book, int $count): int
    {
        if ($count < 1) {
            return 0;
        }

        $next = $this->nextAccessionNo(true);
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $number = (string) ($next + $i);
            $rows[] = $this->copyRow($book, $number);
        }

        LibraryBookCopy::insert($rows);

        return count($rows);
    }

    private function copyRow(LibraryBook $book, string $number): array
    {
        return [
            'library_book_id' => $book->id,
            'accession_no'    => $number,
            'barcode'         => $number,
            'status'          => 'available',
            'created_at'      => now(),
            'updated_at'      => now(),
        ];
    }

    private function nextAccessionNo(bool $forUpdate = false): int
    {
        $query = LibraryBookCopy::orderByRaw('CAST(accession_no AS UNSIGNED) DESC');

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        $max = $query->value('accession_no');

        return $max ? ((int) $max + 1) : 100001;
    }

    private function findBorrower(string $query): ?Student
    {
        if (preg_match('/^student:(\d+)$/', $query, $match)) {
            return Student::find((int) $match[1]);
        }

        return Student::query()
            ->where(function ($memberQuery) use ($query) {
                $memberQuery->where('roll_number', $query)
                    ->orWhere('registration_no', $query)
                    ->orWhere('library_id', $query)
                    ->orWhere('email', $query)
                    ->orWhereRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) LIKE ?", ["%{$query}%"])
                    ->orWhere('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%");
            })
            ->first();
    }
}
