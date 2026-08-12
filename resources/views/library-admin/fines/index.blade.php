@extends('library-admin.layouts.app')

@section('title', 'Student Fines')

@section('library-content')
<div class="mx-auto max-w-7xl space-y-5" x-data="finesPage()" @keydown.escape.window="closeModal()">

    {{-- Flash / errors --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700 space-y-1">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    {{-- Header stats --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Total Outstanding</p>
            <p class="mt-1 text-2xl font-black text-red-600">Rs. {{ number_format($totalOutstanding, 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Overdue Books</p>
            <p class="mt-1 text-2xl font-black text-amber-600">{{ $overdueCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm col-span-2 sm:col-span-1">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Fine Records</p>
            <p class="mt-1 text-2xl font-black text-slate-900">{{ $fines->total() }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('admin.library.fines.index') }}" class="flex flex-1 gap-2">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search borrower or book…"
                   class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold outline-none focus:border-emerald-700 min-w-0">
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white hover:bg-slate-700">Search</button>
        </form>

        <div class="flex rounded-xl border border-slate-200 overflow-hidden text-sm font-black">
            @foreach(['outstanding' => 'Outstanding', 'all' => 'All', 'paid' => 'Paid'] as $val => $label)
                <a href="{{ route('admin.library.fines.index', array_merge(request()->query(), ['filter' => $val])) }}"
                   class="px-4 py-2.5 {{ $filter === $val ? 'bg-emerald-700 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs font-black uppercase tracking-widest text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Borrower</th>
                    <th class="px-4 py-3 text-left">Book</th>
                    <th class="px-4 py-3 text-center">Issued</th>
                    <th class="px-4 py-3 text-center">Due / Returned</th>
                    <th class="px-4 py-3 text-right">Fine</th>
                    <th class="px-4 py-3 text-right">Paid</th>
                    <th class="px-4 py-3 text-right">Outstanding</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($fines as $loan)
                    @php
                        $outstanding = max(0, (float)$loan->fine_amount - (float)$loan->fine_paid);
                        $isOverdue   = $loan->status === 'issued' && $loan->due_date && $loan->due_date->lt(now());
                        $daysLate    = $isOverdue ? (int)$loan->due_date->diffInDays(now()) : 0;
                        $liveFine    = $loan->accrued_fine;
                        $liveFineTotal = $liveFine + (float) $loan->fine_paid;
                        $canRenew    = $loan->status === 'issued' && $liveFine <= 0 && (int)$loan->renewal_count < 2;
                    @endphp
                    <tr class="{{ $outstanding > 0 || $isOverdue ? 'bg-red-50/40' : '' }} hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="font-black text-slate-900">{{ $loan->borrower_name }}</p>
                            <p class="text-xs text-slate-400">{{ $loan->borrower_identifier }} · {{ $loan->borrower_type }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-bold text-slate-800 leading-snug">{{ $loan->copy?->book?->title ?? '—' }}</p>
                            <p class="text-xs text-slate-400">Acc# {{ $loan->copy?->accession_no }}</p>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $loan->issued_at?->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($loan->status === 'issued')
                                <span class="{{ $isOverdue ? 'text-red-600 font-black' : 'text-slate-600' }}">
                                    {{ $loan->due_date?->format('d M Y') }}
                                </span>
                                @if($isOverdue)
                                    <span class="block text-xs font-black text-red-500">{{ $daysLate }}d late · Rs. {{ number_format($liveFine, 2) }}</span>
                                @endif
                            @else
                                <span class="text-slate-500">{{ $loan->returned_at?->format('d M Y') ?? '—' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-black {{ ($loan->status==='issued' ? $liveFineTotal : $loan->fine_amount) > 0 ? 'text-red-600' : 'text-slate-400' }}">
                            @php $displayFine = $loan->status === 'issued' ? $liveFineTotal : $loan->fine_amount; @endphp
                            {{ $displayFine > 0 ? 'Rs. ' . number_format($displayFine, 2) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-black text-emerald-700">
                            {{ $loan->fine_paid > 0 ? 'Rs. ' . number_format($loan->fine_paid, 2) : '—' }}
                            @if($loan->payment_method)
                                <span class="block text-xs font-semibold text-slate-400 capitalize">{{ $loan->payment_method }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-black {{ $outstanding > 0 || ($loan->status==='issued' && $liveFine > 0) ? 'text-red-600' : 'text-slate-400' }}">
                            @if($loan->status === 'issued' && $liveFine > 0)
                                Rs. {{ number_format($liveFine, 2) }}
                            @elseif($outstanding > 0)
                                Rs. {{ number_format($outstanding, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($loan->status === 'issued' && $isOverdue)
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-black text-red-700">Overdue</span>
                            @elseif($loan->status === 'issued')
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-black text-blue-700">Active</span>
                            @elseif($outstanding > 0)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-black text-amber-700">Fine Due</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-black text-emerald-700">Cleared</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-flex flex-wrap items-center justify-center gap-1.5">
                                {{-- Renew (for active loans without fine) --}}
                                @if($canRenew)
                                    <form method="POST" action="{{ route('admin.library.loans.renew', $loan) }}"
                                          onsubmit="return confirm('Renew loan for {{ addslashes($loan->borrower_name) }}? Due date will be extended.')">
                                        @csrf
                                        <button class="rounded-lg bg-emerald-100 px-2.5 py-1.5 text-xs font-black text-emerald-800 hover:bg-emerald-200">Renew</button>
                                    </form>
                                @endif

                                {{-- Pay Fine (for overdue active loans OR returned with outstanding fine) --}}
                                @if(($loan->status === 'issued' && $liveFine > 0) || ($loan->status === 'returned' && $outstanding > 0))
                                    <button type="button"
                                            @click="openPayModal(
                                                {{ $loan->id }},
                                                '{{ addslashes($loan->copy?->book?->title ?? 'Unknown') }}',
                                                '{{ addslashes($loan->borrower_name) }}',
                                                {{ $loan->status === 'issued' ? $liveFine : $outstanding }},
                                                '{{ route('admin.library.loans.pay-fine', $loan) }}'
                                            )"
                                            class="rounded-lg bg-amber-100 px-2.5 py-1.5 text-xs font-black text-amber-800 hover:bg-amber-200">
                                        Pay Fine
                                    </button>
                                @endif

                                {{-- Return (for overdue active loans) --}}
                                @if($loan->status === 'issued')
                                    <button type="button"
                                            @click="openReturnModal(
                                                {{ $loan->id }},
                                                '{{ addslashes($loan->copy?->book?->title ?? 'Unknown') }}',
                                                '{{ addslashes($loan->borrower_name) }}',
                                                {{ $liveFine }},
                                                '{{ route('admin.library.loans.return', $loan) }}'
                                            )"
                                            class="rounded-lg bg-sky-100 px-2.5 py-1.5 text-xs font-black text-sky-800 hover:bg-sky-200">
                                        Return
                                    </button>
                                @endif

                                @if($loan->status === 'returned' && $outstanding <= 0)
                                    <span class="text-xs text-slate-400">Closed</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-16 text-center font-bold text-slate-400">No fine records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $fines->withQueryString()->links() }}

    {{-- ── Pay Fine Modal ── --}}
    <div x-show="payModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeModal()">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-2xl p-6 space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-black text-slate-950">Collect Fine Payment</h3>
                    <p class="text-xs font-semibold text-slate-500 mt-0.5" x-text="payModal.bookTitle"></p>
                    <p class="text-xs font-semibold text-slate-500">Borrower: <span x-text="payModal.borrowerName"></span></p>
                </div>
                <button @click="closeModal()" class="shrink-0 rounded-lg p-1 text-slate-400 hover:bg-slate-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm">
                <p class="font-black text-red-700">Outstanding Fine: Rs. <span x-text="payModal.outstanding.toFixed(2)"></span></p>
            </div>

            <form :action="payModal.url" method="POST" class="space-y-4" @submit="return validatePayModal(event)">
                @csrf
                <label class="text-xs font-black uppercase tracking-widest text-slate-600">Amount Collected (Rs.) <span class="text-red-500">*</span>
                    <input id="payFineAmount" name="fine_paid" type="number" step="0.01" min="0.01"
                           :value="payModal.outstanding.toFixed(2)"
                           :max="payModal.outstanding"
                           required
                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-amber-500">
                </label>

                <label class="text-xs font-black uppercase tracking-widest text-slate-600">Payment Method <span class="text-red-500">*</span>
                    <select id="payMethodSelect" name="payment_method" required
                            x-on:change="payModal.showTxn = ($event.target.value !== 'cash' && $event.target.value !== '')"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-amber-500">
                        <option value="">— Select Method —</option>
                        <option value="cash">Cash</option>
                        <option value="esewa">eSewa</option>
                        <option value="khalti">Khalti</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="other">Other</option>
                    </select>
                </label>

                <div x-show="payModal.showTxn">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">Transaction / Reference ID
                        <input name="payment_txn" type="text" placeholder="Txn ID or reference" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-amber-500">
                    </label>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 rounded-xl bg-amber-600 px-4 py-3 text-sm font-black text-white hover:bg-amber-700">
                        Record Payment
                    </button>
                    <button type="button" @click="closeModal()" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600 hover:bg-slate-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Return Modal (for fines page) ── --}}
    <div x-show="returnModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeModal()">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-2xl p-6 space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-black text-slate-950">Return Book</h3>
                    <p class="text-xs font-semibold text-slate-500 mt-0.5" x-text="returnModal.bookTitle"></p>
                    <p class="text-xs font-semibold text-slate-500">Borrower: <span x-text="returnModal.borrowerName"></span></p>
                </div>
                <button @click="closeModal()" class="shrink-0 rounded-lg p-1 text-slate-400 hover:bg-slate-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div x-show="returnModal.fine > 0" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm">
                <p class="font-black text-red-700">Fine: Rs. <span x-text="returnModal.fine.toFixed(2)"></span></p>
                <p class="text-xs text-red-500 mt-0.5">Collect fine and select payment method to proceed.</p>
            </div>

            <form :action="returnModal.url" method="POST" class="space-y-4" @submit="return validateReturnModal(event)">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">Returned At
                        <input name="returned_at" type="date" value="{{ now()->toDateString() }}" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-bold outline-none cursor-default text-slate-500">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">
                        <span x-show="returnModal.fine > 0">Fine Collected <span class="text-red-500">*</span></span>
                        <span x-show="returnModal.fine <= 0">Fine (Rs.)</span>
                        <input id="retModalFinePaid" name="fine_paid" type="number" step="0.01" min="0"
                               :value="returnModal.fine > 0 ? returnModal.fine.toFixed(2) : ''"
                               :placeholder="returnModal.fine > 0 ? returnModal.fine.toFixed(2) : '0.00'"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-sky-600">
                    </label>
                </div>

                <div x-show="returnModal.fine > 0" class="space-y-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-black uppercase tracking-widest text-amber-800">Payment Details</p>
                    <label class="text-xs font-black uppercase tracking-widest text-slate-600">Payment Method <span class="text-red-500">*</span>
                        <select id="retModalPayMethod" name="payment_method"
                                x-on:change="returnModal.showTxn = ($event.target.value !== 'cash' && $event.target.value !== '')"
                                class="mt-1 w-full rounded-xl border border-amber-200 bg-white px-3 py-2.5 text-sm font-bold outline-none focus:border-amber-500">
                            <option value="">— Select Method —</option>
                            <option value="cash">Cash</option>
                            <option value="esewa">eSewa</option>
                            <option value="khalti">Khalti</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </label>
                    <div x-show="returnModal.showTxn">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-600">Transaction / Reference ID
                            <input name="payment_txn" type="text" placeholder="Txn ID or reference" class="mt-1 w-full rounded-xl border border-amber-200 bg-white px-3 py-2.5 text-sm font-bold outline-none focus:border-amber-500">
                        </label>
                    </div>
                </div>

                <textarea name="remarks" rows="2" placeholder="Return remarks (optional)" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-sky-600"></textarea>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 rounded-xl bg-sky-700 px-4 py-3 text-sm font-black text-white hover:bg-sky-800">Confirm Return</button>
                    <button type="button" @click="closeModal()" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600 hover:bg-slate-50">Cancel</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function finesPage() {
    return {
        payModal: { open: false, bookTitle: '', borrowerName: '', outstanding: 0, url: '', showTxn: false },
        returnModal: { open: false, bookTitle: '', borrowerName: '', fine: 0, url: '', showTxn: false },

        openPayModal(loanId, bookTitle, borrowerName, outstanding, url) {
            this.returnModal.open = false;
            this.payModal = { open: true, bookTitle, borrowerName, outstanding, url, showTxn: false };
            this.$nextTick(() => {
                const sel = document.getElementById('payMethodSelect');
                if (sel) sel.value = '';
            });
        },
        openReturnModal(loanId, bookTitle, borrowerName, fine, url) {
            this.payModal.open = false;
            this.returnModal = { open: true, bookTitle, borrowerName, fine, url, showTxn: false };
            this.$nextTick(() => {
                const sel = document.getElementById('retModalPayMethod');
                if (sel) sel.value = '';
            });
        },
        closeModal() {
            this.payModal.open    = false;
            this.returnModal.open = false;
        },
        validatePayModal(event) {
            const amount = parseFloat(document.getElementById('payFineAmount').value) || 0;
            const method = document.getElementById('payMethodSelect').value;
            if (amount <= 0) { alert('Enter a valid payment amount.'); event.preventDefault(); return false; }
            if (!method)     { alert('Select the payment method used.'); event.preventDefault(); return false; }
            return true;
        },
        validateReturnModal(event) {
            if (this.returnModal.fine > 0) {
                const finePaid = parseFloat(document.getElementById('retModalFinePaid').value) || 0;
                const method   = document.getElementById('retModalPayMethod').value;
                if (finePaid <= 0) { alert('Enter the fine amount collected (Rs. ' + this.returnModal.fine.toFixed(2) + ').'); event.preventDefault(); return false; }
                if (!method)       { alert('Select the payment method used.'); event.preventDefault(); return false; }
            }
            return true;
        },
    };
}
</script>
@endpush
