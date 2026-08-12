@extends('library-admin.layouts.app')

@section('title', 'Issue & Return')

@section('library-content')
<div class="mx-auto max-w-7xl space-y-5" x-data="issueReturnPage()" @keydown.escape.window="closeModal()">

    {{-- Flash / errors --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-bold text-blue-800">{{ session('info') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700 space-y-1">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">

        {{-- Issue Section --}}
        <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-emerald-950">Issue Book</h2>
            <form method="GET" action="{{ route('admin.library.issue.index') }}" class="relative mb-4 flex gap-2">
                <input id="borrowerSearch" name="borrower" value="{{ $borrower?->roll_number ?: request('borrower') }}" autocomplete="off" placeholder="Search by name, roll number, registration, mobile..." class="min-w-0 flex-1 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:border-emerald-700">
                <button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-black text-white">Search</button>
                <div id="borrowerResult" class="absolute left-0 top-full z-50 mt-1 w-full"></div>
            </form>

            @if($borrower)
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-100 p-4">
                    <p class="font-black text-emerald-900">{{ $borrower->full_name }}</p>
                    <p class="text-sm font-semibold text-emerald-700">{{ $borrower->roll_number ?: $borrower->registration_no ?: $borrower->email }} · {{ ucfirst($borrower->member_type) }}</p>
                    <p class="mt-1 text-xs font-bold text-emerald-700">
                        {{ ucwords(str_replace(['-', '_'], ' ', $borrower->organization)) }}
                        · {{ $borrower->stream ?: $borrower->program ?: 'Class/faculty not assigned' }}
                        @if($borrower->section) · Section {{ $borrower->section }} @endif
                        @if($borrower->batch) · Batch {{ $borrower->batch }} @endif
                    </p>
                    <p class="mt-1 text-xs font-bold text-emerald-700">Active books: {{ $activeLoans->count() }} / {{ $rules['max_active_books'] }}</p>
                </div>

                <form method="POST" action="{{ route('admin.library.issue.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="borrower_student_id" value="{{ $borrower->id }}">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">Book Barcode / Accession No
                        <input id="barcodeInput" name="barcode" required autofocus class="mt-1.5 w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm font-bold normal-case tracking-normal outline-none focus:border-emerald-700">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">Issued At
                        <input name="issued_at" type="date" value="{{ now()->toDateString() }}" readonly class="mt-1.5 w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold normal-case tracking-normal outline-none cursor-default text-slate-500">
                    </label>
                    <textarea name="remarks" rows="2" placeholder="Remarks" class="w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:border-emerald-700"></textarea>
                    <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white hover:bg-emerald-800">Issue Book</button>
                </form>
            @else
                <div class="rounded-xl border border-dashed border-emerald-300 px-5 py-12 text-center font-bold text-emerald-500">Search a borrower from HR data to issue a book.</div>
            @endif
        </section>

        {{-- Return by Barcode Section --}}
        <section class="rounded-xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-black text-sky-950">Return Book (Barcode Scan)</h2>
            <form method="POST" action="{{ route('admin.library.return.store') }}" class="space-y-3">
                @csrf
                <label class="text-xs font-black uppercase tracking-widest text-slate-600">Book Barcode / Accession No
                    <input id="returnBarcodeInput" name="barcode" required autocomplete="off" class="mt-1.5 w-full rounded-xl border border-sky-200 bg-white px-4 py-3 text-sm font-bold normal-case tracking-normal outline-none focus:border-sky-600">
                </label>

                <div id="returnLoanInfo" class="hidden rounded-xl border p-4 text-sm"></div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">Returned At
                        <input name="returned_at" type="date" value="{{ now()->toDateString() }}" readonly class="mt-1.5 w-full rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-bold normal-case tracking-normal outline-none cursor-default text-slate-500">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">Fine Collected (Rs.)
                        <input id="returnFinePaid" name="fine_paid" type="number" step="0.01" min="0" placeholder="0.00" class="mt-1.5 w-full rounded-xl border border-sky-200 bg-white px-4 py-3 text-sm font-bold normal-case tracking-normal outline-none focus:border-sky-600">
                    </label>
                </div>

                {{-- Payment fields (shown when fine > 0) --}}
                <div id="returnPaymentSection" class="hidden space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-black uppercase tracking-widest text-amber-800">Payment Details</p>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">Payment Method <span class="text-red-500">*</span>
                        <select id="returnPaymentMethod" name="payment_method" class="mt-1.5 w-full rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm font-bold normal-case tracking-normal outline-none focus:border-amber-500">
                            <option value="">— Select —</option>
                            <option value="cash">Cash</option>
                            <option value="esewa">eSewa</option>
                            <option value="khalti">Khalti</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </label>
                    <div id="returnTxnWrap" class="hidden">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-600">Transaction / Reference ID
                            <input name="payment_txn" type="text" placeholder="Txn ID or reference" class="mt-1.5 w-full rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm font-bold normal-case tracking-normal outline-none focus:border-amber-500">
                        </label>
                    </div>
                </div>

                <textarea name="remarks" rows="2" placeholder="Return remarks" class="w-full rounded-xl border border-sky-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:border-sky-600"></textarea>
                <button class="rounded-xl bg-sky-700 px-5 py-3 text-sm font-black text-white hover:bg-sky-800">Return Book</button>
            </form>
        </section>
    </div>

    {{-- Active Loans Table --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-black text-slate-950">Borrower Active Books</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-widest text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Book</th>
                        <th class="px-5 py-3">Barcode</th>
                        <th class="px-5 py-3">Issued</th>
                        <th class="px-5 py-3">Due</th>
                        <th class="px-5 py-3 text-center">Renewals</th>
                        <th class="px-5 py-3">Fine</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($activeLoans as $loan)
                        @php
                            $calcFine   = $loan->accrued_fine;
                            $canRenew   = $calcFine <= 0 && (int) $loan->renewal_count < 2;
                            $isOverdue  = $loan->due_date && $loan->due_date->isPast();
                        @endphp
                        <tr class="{{ $calcFine > 0 ? 'bg-red-50' : '' }}">
                            <td class="px-5 py-3 font-black text-slate-950">{{ $loan->copy?->book?->title }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $loan->copy?->barcode ?? $loan->copy?->accession_no }}</td>
                            <td class="px-5 py-3 font-semibold text-slate-600">{{ $loan->issued_at?->format('Y-m-d') }}</td>
                            <td class="px-5 py-3 font-semibold {{ $isOverdue ? 'text-red-600' : 'text-slate-600' }}">
                                {{ $loan->due_date?->format('Y-m-d') }}
                                @if($isOverdue)
                                    <span class="block text-xs text-red-500">{{ now()->diffInDays($loan->due_date) }}d late</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-xs font-bold {{ (int)$loan->renewal_count > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
                                    {{ $loan->renewal_count }}/2
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                @if($calcFine > 0)
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-black text-red-700">Rs. {{ number_format($calcFine, 2) }}</span>
                                @else
                                    <span class="text-xs font-semibold text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    {{-- Renew --}}
                                    @if($canRenew)
                                        <form method="POST" action="{{ route('admin.library.loans.renew', $loan) }}"
                                              onsubmit="return confirm('Renew this loan? The due date will be extended.')">
                                            @csrf
                                            <button class="rounded-lg bg-emerald-100 px-3 py-2 text-xs font-black text-emerald-800 hover:bg-emerald-200">Renew</button>
                                        </form>
                                    @elseif($calcFine > 0)
                                        <span class="rounded-lg bg-amber-100 px-2 py-1.5 text-xs font-semibold text-amber-700" title="Pay fine first to renew">No Renew (Fine)</span>
                                    @else
                                        <span class="text-xs font-semibold text-slate-400">Max Renewals</span>
                                    @endif

                                    {{-- Return (opens modal) --}}
                                    <button type="button"
                                            @click="openModal({{ $loan->id }}, '{{ addslashes($loan->copy?->book?->title ?? 'Unknown') }}', '{{ $loan->borrower_name }}', {{ $calcFine }}, '{{ route('admin.library.loans.return', $loan) }}')"
                                            class="rounded-lg bg-sky-700 px-3 py-2 text-xs font-black text-white hover:bg-sky-800">
                                        Return
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center font-bold text-slate-400">No active books for selected borrower.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Return Modal --}}
    <div x-show="modal.open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @click.self="closeModal()">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-2xl p-6 space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-black text-slate-950" x-text="modal.bookTitle"></h3>
                    <p class="text-xs font-semibold text-slate-500 mt-0.5">Borrower: <span x-text="modal.borrowerName"></span></p>
                </div>
                <button @click="closeModal()" class="shrink-0 rounded-lg p-1 text-slate-400 hover:bg-slate-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Fine alert --}}
            <div x-show="modal.fine > 0" class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="font-black text-red-700 text-sm">Outstanding Fine: Rs. <span x-text="modal.fine.toFixed(2)"></span></p>
                <p class="text-xs text-red-500 mt-0.5">Collect fine and select payment method to proceed with return.</p>
            </div>

            <form :action="modal.returnUrl" method="POST" class="space-y-4" @submit="return validateReturnModal(event)">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">Returned At
                        <input name="returned_at" type="date" value="{{ now()->toDateString() }}" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-bold normal-case tracking-normal outline-none cursor-default text-slate-500">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">
                        <span x-show="modal.fine > 0">Fine Collected (Rs.) <span class="text-red-500">*</span></span>
                        <span x-show="modal.fine <= 0">Fine (Rs.)</span>
                        <input id="modalFinePaid" name="fine_paid" type="number" step="0.01" min="0"
                               :value="modal.fine > 0 ? modal.fine.toFixed(2) : ''"
                               :placeholder="modal.fine > 0 ? modal.fine.toFixed(2) : '0.00'"
                               class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold normal-case tracking-normal outline-none focus:border-sky-600">
                    </label>
                </div>

                {{-- Payment method (shown when fine > 0) --}}
                <div x-show="modal.fine > 0" class="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-black uppercase tracking-widest text-amber-800">Payment Details</p>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">Payment Method <span class="text-red-500">*</span>
                        <select id="modalPaymentMethod" name="payment_method"
                                x-on:change="modal.showTxn = ($event.target.value !== 'cash' && $event.target.value !== '')"
                                class="mt-1 w-full rounded-xl border border-amber-200 bg-white px-3 py-2.5 text-sm font-bold normal-case tracking-normal outline-none focus:border-amber-500">
                            <option value="">— Select Method —</option>
                            <option value="cash">Cash</option>
                            <option value="esewa">eSewa</option>
                            <option value="khalti">Khalti</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </label>
                    <div x-show="modal.showTxn">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-600">Transaction / Reference ID
                            <input name="payment_txn" type="text" placeholder="Txn ID or reference" class="mt-1 w-full rounded-xl border border-amber-200 bg-white px-3 py-2.5 text-sm font-bold normal-case tracking-normal outline-none focus:border-amber-500">
                        </label>
                    </div>
                </div>

                <textarea name="remarks" rows="2" placeholder="Return remarks (optional)" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold outline-none focus:border-sky-600"></textarea>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 rounded-xl bg-sky-700 px-4 py-3 text-sm font-black text-white hover:bg-sky-800">
                        Confirm Return
                    </button>
                    <button type="button" @click="closeModal()" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600 hover:bg-slate-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function issueReturnPage() {
    return {
        modal: {
            open: false,
            loanId: null,
            bookTitle: '',
            borrowerName: '',
            fine: 0,
            returnUrl: '',
            showTxn: false,
        },
        openModal(loanId, bookTitle, borrowerName, fine, returnUrl) {
            this.modal.loanId      = loanId;
            this.modal.bookTitle   = bookTitle;
            this.modal.borrowerName = borrowerName;
            this.modal.fine        = fine;
            this.modal.returnUrl   = returnUrl;
            this.modal.showTxn     = false;
            this.modal.open        = true;
            this.$nextTick(() => {
                document.getElementById('modalPaymentMethod').value = '';
            });
        },
        closeModal() {
            this.modal.open = false;
        },
        validateReturnModal(event) {
            if (this.modal.fine > 0) {
                const finePaid = parseFloat(document.getElementById('modalFinePaid').value) || 0;
                const method   = document.getElementById('modalPaymentMethod').value;
                if (finePaid <= 0) {
                    alert('Please enter the fine amount collected (Rs. ' + this.modal.fine.toFixed(2) + ').');
                    event.preventDefault(); return false;
                }
                if (!method) {
                    alert('Please select the payment method used.');
                    event.preventDefault(); return false;
                }
            }
            return true;
        },
    };
}

// ── Borrower search ──────────────────────────────────────────────────────────
const borrowerInput  = document.getElementById('borrowerSearch');
const borrowerResult = document.getElementById('borrowerResult');
let borrowerDebounce;
const escapeBorrowerHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
}[char]));

if (borrowerInput && borrowerResult) {
    borrowerInput.addEventListener('input', function () {
        clearTimeout(borrowerDebounce);
        const query = this.value.trim();
        if (query.length < 2) { borrowerResult.innerHTML = ''; return; }
        borrowerDebounce = setTimeout(() => {
            fetch(`{{ route('admin.library.people.search') }}?query=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.length) {
                        borrowerResult.innerHTML = `<div class="rounded-xl border border-slate-200 bg-white p-3 text-sm font-bold text-slate-400 shadow-xl">No HR member found</div>`;
                        return;
                    }
                    borrowerResult.innerHTML = `<div class="max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">`
                        + data.map(p => `
                            <button type="button" data-borrower-selection="${escapeBorrowerHtml(p.selection)}" class="block w-full border-b border-slate-100 px-4 py-3 text-left hover:bg-emerald-50">
                                <span class="block text-sm font-black text-slate-900">${escapeBorrowerHtml(p.name)}</span>
                                <span class="block text-xs font-bold text-slate-600">Roll/ID: ${escapeBorrowerHtml(p.identifier || 'Not assigned')} · ${escapeBorrowerHtml(p.type)} · ${escapeBorrowerHtml(p.organization)}</span>
                                <span class="mt-0.5 block text-xs font-semibold text-slate-500">Faculty: ${escapeBorrowerHtml(p.faculty || 'Not assigned')}${p.class ? ' · Class: ' + escapeBorrowerHtml(p.class) : ''}${p.section ? ' · Section ' + escapeBorrowerHtml(p.section) : ''}${p.batch ? ' · Batch ' + escapeBorrowerHtml(p.batch) : ''}</span>
                                ${(p.mobile || p.email) ? `<span class="mt-0.5 block text-[11px] font-semibold text-slate-400">${escapeBorrowerHtml(p.mobile || '')}${p.mobile && p.email ? ' · ' : ''}${escapeBorrowerHtml(p.email || '')}</span>` : ''}
                            </button>
                        `).join('')
                        + `</div>`;
                    borrowerResult.querySelectorAll('[data-borrower-selection]').forEach(button => {
                        button.addEventListener('click', () => selectBorrower(button.dataset.borrowerSelection));
                    });
                });
        }, 250);
    });

    borrowerInput.addEventListener('keydown', e => { if (e.key === 'Enter') borrowerResult.innerHTML = ''; });
    document.addEventListener('click', e => {
        if (!borrowerInput.contains(e.target) && !borrowerResult.contains(e.target)) borrowerResult.innerHTML = '';
    });
}

function selectBorrower(value) {
    borrowerInput.value = value;
    borrowerResult.innerHTML = '';
    borrowerInput.form.submit();
}

// ── Return barcode AJAX lookup ───────────────────────────────────────────────
const returnBarcodeInput   = document.getElementById('returnBarcodeInput');
const returnLoanInfo       = document.getElementById('returnLoanInfo');
const returnFinePaid       = document.getElementById('returnFinePaid');
const returnPaymentSection = document.getElementById('returnPaymentSection');
const returnPaymentMethod  = document.getElementById('returnPaymentMethod');
const returnTxnWrap        = document.getElementById('returnTxnWrap');
let returnDebounce;

if (returnPaymentMethod) {
    returnPaymentMethod.addEventListener('change', function () {
        if (returnTxnWrap) {
            returnTxnWrap.classList.toggle('hidden', this.value === 'cash' || this.value === '');
        }
    });
}

if (returnFinePaid) {
    returnFinePaid.addEventListener('input', function () {
        const amount = parseFloat(this.value) || 0;
        if (returnPaymentSection) returnPaymentSection.classList.toggle('hidden', amount <= 0);
    });
}

if (returnBarcodeInput) {
    returnBarcodeInput.addEventListener('input', function () {
        clearTimeout(returnDebounce);
        const barcode = this.value.trim();
        if (barcode.length < 2) { hideLoanInfo(); return; }
        returnDebounce = setTimeout(() => lookupReturnBarcode(barcode), 400);
    });
    returnBarcodeInput.addEventListener('blur', function () {
        if (this.value.trim().length >= 2) lookupReturnBarcode(this.value.trim());
    });
}

function hideLoanInfo() {
    returnLoanInfo.classList.add('hidden');
    returnLoanInfo.innerHTML = '';
    if (returnPaymentSection) returnPaymentSection.classList.add('hidden');
}

function lookupReturnBarcode(barcode) {
    fetch(`{{ route('admin.library.loans.lookup') }}?barcode=${encodeURIComponent(barcode)}`)
        .then(r => r.json())
        .then(data => {
            if (!data) { hideLoanInfo(); return; }
            if (data.error) {
                returnLoanInfo.className = 'rounded-xl border border-red-200 bg-red-50 p-4 text-sm';
                returnLoanInfo.classList.remove('hidden');
                returnLoanInfo.innerHTML = `<p class="font-bold text-red-700">${data.error}</p>`;
                if (returnPaymentSection) returnPaymentSection.classList.add('hidden');
                if (returnFinePaid) returnFinePaid.value = '';
                return;
            }

            const hasFine = (data.fine || 0) > 0;
            returnLoanInfo.className = `rounded-xl border p-4 text-sm ${hasFine ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50'}`;
            returnLoanInfo.classList.remove('hidden');

            let html = `<p class="font-black ${hasFine ? 'text-red-900' : 'text-emerald-900'}">${data.book_title ?? 'Unknown Book'}</p>
                <p class="mt-1 text-xs font-semibold ${hasFine ? 'text-red-700' : 'text-emerald-700'}">Borrower: <strong>${data.borrower_name}</strong></p>
                <p class="text-xs font-semibold ${hasFine ? 'text-red-700' : 'text-emerald-700'}">Issued: ${data.issued_at} · Due: ${data.due_date}</p>`;

            if (hasFine) {
                html += `<p class="mt-2 font-black text-red-700">Fine: Rs. ${data.fine.toFixed(2)} <span class="text-xs font-semibold">(${data.days_late} day${data.days_late !== 1 ? 's' : ''} × Rs. ${data.fine_per_day}/day)</span></p>
                    <p class="mt-1 text-xs font-bold text-red-600">⚠ Collect fine and select payment method before returning.</p>`;
                if (returnFinePaid) {
                    returnFinePaid.value = data.fine.toFixed(2);
                    returnFinePaid.classList.add('border-red-300', 'bg-red-50');
                    returnFinePaid.classList.remove('border-sky-200', 'bg-white');
                }
                if (returnPaymentSection) returnPaymentSection.classList.remove('hidden');
            } else {
                html += `<p class="mt-1 text-xs font-bold text-emerald-600">No fine due.</p>`;
                if (returnFinePaid) {
                    returnFinePaid.value = '';
                    returnFinePaid.classList.remove('border-red-300', 'bg-red-50');
                    returnFinePaid.classList.add('border-sky-200', 'bg-white');
                }
                if (returnPaymentSection) returnPaymentSection.classList.add('hidden');
            }

            returnLoanInfo.innerHTML = html;
        })
        .catch(() => hideLoanInfo());
}

document.addEventListener('DOMContentLoaded', function () {
    const barcode = document.getElementById('barcodeInput');
    if (barcode) barcode.focus();
});
</script>
@endpush
