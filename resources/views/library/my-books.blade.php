@extends('layouts.admin')

@section('title', 'My Library')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8 space-y-6">

    <div>
        <h1 class="text-2xl font-black text-gray-950">My Library</h1>
        <p class="mt-1 text-sm font-semibold text-gray-500">Books issued to you and your outstanding fines.</p>
    </div>

    {{-- Notifications --}}
    @if($notifications->isNotEmpty())
    <div class="space-y-2">
        <p class="text-xs font-black uppercase tracking-widest text-gray-400">Recent Library Notifications</p>
        @foreach($notifications as $notif)
            @php
                $bg = match($notif->type) {
                    'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
                    'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
                    'danger'  => 'bg-red-50 border-red-200 text-red-800',
                    default   => 'bg-blue-50 border-blue-200 text-blue-800',
                };
            @endphp
            <div class="flex items-start gap-3 rounded-xl border px-4 py-3 {{ $bg }}">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-black">{{ $notif->title }}</p>
                    <p class="text-xs font-semibold mt-0.5 opacity-80">{{ $notif->message }}</p>
                </div>
                <span class="text-[10px] font-bold opacity-60 shrink-0">{{ $notif->created_at->diffForHumans() }}</span>
            </div>
        @endforeach
    </div>
    @endif

    {{-- Active allocations summary --}}
    @php
        $activeLoans   = $loans->getCollection()->where('status', 'issued');
        $overdueLoans  = $activeLoans->filter(fn($l) => $l->due_date && $l->due_date->isPast());
        $totalFineOwed = $loans->getCollection()->sum(fn($l) => max(0, $l->fine_amount - $l->fine_paid));
    @endphp
    @if($activeLoans->isNotEmpty())
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Borrowed</p>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ $activeLoans->count() }}</p>
        </div>
        <div class="rounded-xl border {{ $overdueLoans->isNotEmpty() ? 'border-red-200 bg-red-50' : 'border-slate-200 bg-white' }} p-4">
            <p class="text-xs font-black uppercase tracking-widest {{ $overdueLoans->isNotEmpty() ? 'text-red-500' : 'text-slate-400' }}">Overdue</p>
            <p class="text-2xl font-black {{ $overdueLoans->isNotEmpty() ? 'text-red-600' : 'text-slate-900' }} mt-1">{{ $overdueLoans->count() }}</p>
        </div>
        <div class="rounded-xl border {{ $totalFineOwed > 0 ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white' }} p-4">
            <p class="text-xs font-black uppercase tracking-widest {{ $totalFineOwed > 0 ? 'text-amber-600' : 'text-slate-400' }}">Fine Owed</p>
            <p class="text-2xl font-black {{ $totalFineOwed > 0 ? 'text-amber-700' : 'text-slate-900' }} mt-1">Rs. {{ number_format($totalFineOwed, 2) }}</p>
        </div>
    </div>
    @endif

    {{-- Book search link --}}
    <div class="flex items-center gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3">
        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
        <span class="text-sm font-semibold text-slate-500">Looking for a book?</span>
        <a href="{{ route('library.public.search') }}" class="text-sm font-black text-emerald-700 hover:underline">Search the library catalog →</a>
    </div>

    {{-- Loans table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-gray-50 text-xs font-black uppercase tracking-widest text-gray-500">
                <tr>
                    <th class="px-5 py-3">Book</th>
                    <th class="px-5 py-3">Accession</th>
                    <th class="px-5 py-3">Issued</th>
                    <th class="px-5 py-3">Due Date</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Outstanding Fine</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($loans as $loan)
                    @php
                        $isOverdue    = $loan->status === 'issued' && $loan->due_date && $loan->due_date->isPast();
                        $outstanding  = max(0, $loan->fine_amount - $loan->fine_paid);
                        $daysLate     = $isOverdue ? $loan->due_date->diffInDays(now()) : 0;
                    @endphp
                    <tr class="{{ $isOverdue ? 'bg-red-50/40' : '' }}">
                        <td class="px-5 py-3">
                            <p class="font-black text-gray-950">{{ $loan->copy?->book?->title }}</p>
                            <p class="text-xs font-semibold text-gray-500">{{ $loan->copy?->book?->author ?: 'Unknown author' }}</p>
                        </td>
                        <td class="px-5 py-3 font-bold text-gray-700">{{ $loan->copy?->accession_no }}</td>
                        <td class="px-5 py-3 font-semibold text-gray-600">{{ $loan->issued_at?->format('d M Y') }}</td>
                        <td class="px-5 py-3 font-semibold {{ $isOverdue ? 'text-red-600' : 'text-gray-600' }}">
                            {{ $loan->due_date?->format('d M Y') }}
                            @if($isOverdue)
                                <span class="block text-xs font-black text-red-500">{{ $daysLate }} days late</span>
                            @elseif($loan->status === 'issued' && $loan->due_date)
                                @php $daysLeft = now()->diffInDays($loan->due_date, false); @endphp
                                @if($daysLeft <= 3 && $daysLeft >= 0)
                                    <span class="block text-xs font-black text-amber-600">{{ $daysLeft }}d left</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="rounded-full px-3 py-1 text-xs font-black
                                {{ $loan->status === 'issued' ? ($isOverdue ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700') : 'bg-gray-100 text-gray-600' }}">
                                {{ $isOverdue ? 'Overdue' : ucfirst($loan->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right font-black {{ $outstanding > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ $outstanding > 0 ? 'Rs. ' . number_format($outstanding, 2) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center font-bold text-gray-400">No library books are issued to your account.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($loans->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">{{ $loans->links() }}</div>
        @endif
    </div>
</div>
@endsection
