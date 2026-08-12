@extends('library-admin.layouts.app')

@section('title', 'My Books')

@section('library-content')

<div class="mx-auto max-w-4xl space-y-6">

    {{-- Header + stats --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-950">My Books</h1>
            <p class="text-sm font-semibold text-slate-400 mt-0.5">Books currently issued to your account</p>
        </div>
        <div class="flex gap-3">
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center shadow-sm">
                <p class="text-xl font-black text-slate-900">{{ $activeLoans->count() }}</p>
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Issued</p>
            </div>
            @if($overdueCount > 0)
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-center shadow-sm">
                <p class="text-xl font-black text-red-600">{{ $overdueCount }}</p>
                <p class="text-[11px] font-bold uppercase tracking-widest text-red-400">Overdue</p>
            </div>
            @endif
            @if($fineOwed > 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-center shadow-sm">
                <p class="text-xl font-black text-amber-600">Rs.{{ number_format($fineOwed, 2) }}</p>
                <p class="text-[11px] font-bold uppercase tracking-widest text-amber-400">Fine Owed</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Active loans --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-base font-black text-slate-950">Currently Issued</h2>
        </div>

        @if($activeLoans->isEmpty())
            <div class="px-5 py-12 text-center">
                <svg class="mx-auto w-10 h-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                <p class="font-bold text-slate-400">No books currently issued to your account.</p>
                <p class="text-sm text-slate-400 mt-1">Ask a librarian to issue a book to your name.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($activeLoans as $loan)
                    @php
                        $isOverdue  = $loan->due_date < now()->toDateString();
                        $daysLeft   = now()->startOfDay()->diffInDays($loan->due_date, false);
                        $dueSoon    = !$isOverdue && $daysLeft <= 3;
                        $fine       = $loan->fine_balance ?? 0;
                    @endphp
                    <div class="flex items-start gap-4 px-5 py-4 {{ $isOverdue ? 'bg-red-50/50' : ($dueSoon ? 'bg-amber-50/40' : '') }}">

                        {{-- Book icon --}}
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $isOverdue ? 'bg-red-100' : 'bg-slate-100' }}">
                            <svg class="w-5 h-5 {{ $isOverdue ? 'text-red-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="font-black text-slate-900 leading-tight">
                                {{ $loan->copy?->book?->title ?? $loan->book_title ?? '—' }}
                            </p>
                            @if($loan->copy?->book?->author)
                                <p class="text-xs text-slate-500 mt-0.5">{{ $loan->copy->book->author }}</p>
                            @endif
                            <div class="flex flex-wrap gap-3 mt-1.5 text-xs font-semibold">
                                <span class="text-slate-500">
                                    Issued: {{ \Carbon\Carbon::parse($loan->issued_at)->format('d M Y') }}
                                </span>
                                <span class="{{ $isOverdue ? 'text-red-600 font-black' : ($dueSoon ? 'text-amber-600 font-black' : 'text-slate-500') }}">
                                    Due: {{ \Carbon\Carbon::parse($loan->due_date)->format('d M Y') }}
                                    @if($isOverdue)
                                        ({{ abs($daysLeft) }} day{{ abs($daysLeft) !== 1 ? 's' : '' }} overdue)
                                    @elseif($dueSoon)
                                        ({{ $daysLeft }} day{{ $daysLeft !== 1 ? 's' : '' }} left)
                                    @endif
                                </span>
                                @if($loan->copy)
                                    <span class="text-slate-400">Acc# {{ $loan->copy->accession_no }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Badges --}}
                        <div class="flex flex-col items-end gap-1.5 shrink-0">
                            @if($isOverdue)
                                <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-black text-red-700">Overdue</span>
                                @if($fine > 0)
                                    <span class="rounded-full bg-red-50 border border-red-200 px-2.5 py-0.5 text-xs font-black text-red-600">
                                        Fine Rs.{{ number_format($fine, 2) }}
                                    </span>
                                @endif
                            @elseif($dueSoon)
                                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-black text-amber-700">Due Soon</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-black text-emerald-700">Active</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- History --}}
    @if($historyLoans->total() > 0)
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-base font-black text-slate-950">Return History</h2>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($historyLoans as $loan)
            <div class="flex items-center gap-4 px-5 py-3">
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-slate-700 leading-tight text-sm">
                        {{ $loan->copy?->book?->title ?? $loan->book_title ?? '—' }}
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Issued {{ \Carbon\Carbon::parse($loan->issued_at)->format('d M Y') }}
                        &rarr; Returned {{ $loan->returned_at ? \Carbon\Carbon::parse($loan->returned_at)->format('d M Y') : '—' }}
                    </p>
                </div>
                @if($loan->fine_amount > 0)
                    <span class="text-xs font-bold text-slate-500 shrink-0">
                        Fine Rs.{{ number_format($loan->fine_amount, 2) }}
                        @if($loan->fine_paid >= $loan->fine_amount)
                            <span class="text-emerald-600">(paid)</span>
                        @endif
                    </span>
                @endif
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-black text-slate-500 shrink-0">Returned</span>
            </div>
            @endforeach
        </div>

        @if($historyLoans->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">
                {{ $historyLoans->links() }}
            </div>
        @endif
    </section>
    @endif

</div>

@endsection
