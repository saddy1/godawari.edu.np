@extends('card.student-portal.layout')
@section('title', 'My Library')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">

    {{-- Header --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-widest text-[#1a5632]">Library</p>
                <h1 class="mt-1 text-2xl font-extrabold text-gray-950">My Library</h1>
                <p class="mt-1 text-sm font-medium text-gray-500">Books currently issued to you and your borrowing history.</p>
            </div>
            <a href="{{ route('student.books.search') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                Search Books
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    @if($activeLoans->isNotEmpty() || $fineOwed > 0)
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm text-center">
            <p class="text-2xl font-extrabold text-gray-950">{{ $activeLoans->count() }}</p>
            <p class="mt-1 text-xs font-extrabold uppercase tracking-widest text-gray-400">Borrowed</p>
        </div>
        <div class="rounded-2xl border {{ $overdueCount > 0 ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-white' }} p-4 shadow-sm text-center">
            <p class="text-2xl font-extrabold {{ $overdueCount > 0 ? 'text-red-600' : 'text-gray-950' }}">{{ $overdueCount }}</p>
            <p class="mt-1 text-xs font-extrabold uppercase tracking-widest {{ $overdueCount > 0 ? 'text-red-500' : 'text-gray-400' }}">Overdue</p>
        </div>
        <div class="rounded-2xl border {{ $fineOwed > 0 ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-white' }} p-4 shadow-sm text-center">
            <p class="text-lg font-extrabold {{ $fineOwed > 0 ? 'text-amber-700' : 'text-gray-950' }}">Rs. {{ number_format($fineOwed, 0) }}</p>
            <p class="mt-1 text-xs font-extrabold uppercase tracking-widest {{ $fineOwed > 0 ? 'text-amber-600' : 'text-gray-400' }}">Fine Owed</p>
        </div>
    </div>
    @endif

    {{-- Currently Borrowed --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-extrabold uppercase tracking-widest text-gray-500">Currently Borrowed</h2>
        </div>

        @if($activeLoans->isEmpty())
            <div class="py-14 text-center">
                <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                </svg>
                <p class="font-extrabold text-gray-400">No books currently issued to you</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($activeLoans as $loan)
                    @php
                        $isOverdue = $loan->due_date && $loan->due_date->isPast();
                        $daysLate  = $isOverdue ? now()->diffInDays($loan->due_date) : 0;
                        $daysLeft  = !$isOverdue && $loan->due_date ? now()->diffInDays($loan->due_date, false) : null;
                        $fine      = $loan->accrued_fine;
                    @endphp
                    <div class="flex items-start gap-4 px-5 py-4 {{ $isOverdue ? 'bg-red-50/50' : '' }}">
                        <div class="flex h-10 w-8 shrink-0 items-center justify-center rounded-lg text-white text-xs font-extrabold"
                             style="background: {{ $isOverdue ? '#ef4444' : 'var(--sp-primary, #1a5632)' }}">
                            {{ strtoupper(substr($loan->copy?->book?->title ?? 'B', 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-extrabold text-gray-950 leading-tight">{{ $loan->copy?->book?->title ?? '—' }}</p>
                            <p class="text-xs font-semibold text-gray-500 mt-0.5">{{ $loan->copy?->book?->author ?? '' }}</p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs font-semibold">
                                <span class="text-gray-400">Accession: <span class="text-gray-700">{{ $loan->copy?->accession_no ?? '—' }}</span></span>
                                <span class="text-gray-300">·</span>
                                <span class="text-gray-400">Issued: <span class="text-gray-700">{{ $loan->issued_at?->format('d M Y') ?? '—' }}</span></span>
                            </div>
                        </div>
                        <div class="shrink-0 text-right space-y-1">
                            @if($isOverdue)
                                <span class="inline-block rounded-full bg-red-100 px-2.5 py-1 text-xs font-extrabold text-red-700">
                                    {{ $daysLate }}d overdue
                                </span>
                                @if($fine > 0)
                                    <p class="text-xs font-extrabold text-red-600">Fine: Rs. {{ number_format($fine, 0) }}</p>
                                @endif
                            @elseif($daysLeft !== null && $daysLeft <= 3)
                                <span class="inline-block rounded-full bg-amber-100 px-2.5 py-1 text-xs font-extrabold text-amber-700">
                                    Due in {{ $daysLeft }}d
                                </span>
                            @else
                                <span class="inline-block rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-extrabold text-emerald-700">
                                    Due {{ $loan->due_date?->format('d M') ?? '—' }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Borrowing History --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-extrabold uppercase tracking-widest text-gray-500">Borrowing History</h2>
        </div>

        @if($historyLoans->isEmpty())
            <div class="py-10 text-center">
                <p class="font-extrabold text-gray-300">No returned books yet</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm text-left">
                    <thead class="bg-gray-50 text-xs font-extrabold uppercase tracking-widest text-gray-400">
                        <tr>
                            <th class="px-5 py-3">Book</th>
                            <th class="px-5 py-3">Issued</th>
                            <th class="px-5 py-3">Returned</th>
                            <th class="px-5 py-3 text-right">Fine Paid</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($historyLoans as $loan)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-5 py-3">
                                    <p class="font-extrabold text-gray-900">{{ $loan->copy?->book?->title ?? '—' }}</p>
                                    <p class="text-xs text-gray-400">{{ $loan->copy?->accession_no }}</p>
                                </td>
                                <td class="px-5 py-3 font-semibold text-gray-600">{{ $loan->issued_at?->format('d M Y') ?? '—' }}</td>
                                <td class="px-5 py-3 font-semibold text-gray-600">{{ $loan->returned_at?->format('d M Y') ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-bold {{ ($loan->fine_paid ?? 0) > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                    {{ ($loan->fine_paid ?? 0) > 0 ? 'Rs. ' . number_format($loan->fine_paid, 0) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($historyLoans->hasPages())
                <div class="border-t border-gray-100 bg-gray-50 px-5 py-3">
                    {{ $historyLoans->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
