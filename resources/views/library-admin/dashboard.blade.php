@extends('library-admin.layouts.app')

@section('title', 'Dashboard')

@section('library-content')

@php
    // SVG line chart — 30-day daily issued vs returned
    $svgW  = 600; $svgH = 110; $padX = 8; $padY = 10;
    $pts   = $dailyIssued->count(); // 30
    $maxV  = max(1, $dailyIssued->max(), $dailyReturned->max());
    $xStep = ($svgW - 2 * $padX) / max(1, $pts - 1);
    $yScale = ($svgH - 2 * $padY) / $maxV;

    $issuedPts   = $dailyIssued->values()->map(fn ($v, $i) =>
        round($padX + $i * $xStep, 2) . ',' . round($svgH - $padY - $v * $yScale, 2)
    )->implode(' ');

    $returnedPts = $dailyReturned->values()->map(fn ($v, $i) =>
        round($padX + $i * $xStep, 2) . ',' . round($svgH - $padY - $v * $yScale, 2)
    )->implode(' ');

    // Build area-fill path (issued)
    $firstX = $padX; $lastX = round($padX + ($pts - 1) * $xStep, 2);
    $issuedArea = "M {$firstX},{$svgH} L " . $issuedPts . " L {$lastX},{$svgH} Z";
@endphp

<div class="mx-auto max-w-7xl space-y-6">

    {{-- ── STAT CARDS ── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @php
            $cards = [
                ['label' => 'Total Books',  'value' => $summary['books'],   'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253', 'color' => 'text-slate-700', 'bg' => 'bg-slate-100'],
                ['label' => 'Total Copies', 'value' => $summary['copies'],  'icon' => 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2', 'color' => 'text-blue-700', 'bg' => 'bg-blue-100'],
                ['label' => 'Issued Now',   'value' => $summary['issued'],  'icon' => 'M7 7h11m0 0l-4-4m4 4l-4 4M17 17H6m0 0l4 4m-4-4l4-4', 'color' => 'text-emerald-700', 'bg' => 'bg-emerald-100'],
                ['label' => 'Returned',     'value' => $summary['returned'],'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0', 'color' => 'text-indigo-700', 'bg' => 'bg-indigo-100'],
                ['label' => 'Overdue',      'value' => $summary['overdue'], 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0', 'color' => $summary['overdue'] > 0 ? 'text-red-700' : 'text-slate-400', 'bg' => $summary['overdue'] > 0 ? 'bg-red-100' : 'bg-slate-100'],
                ['label' => 'Fine Pending', 'value' => 'Rs.'.number_format($summary['fine_due'],0), 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0', 'color' => $summary['fine_due'] > 0 ? 'text-amber-700' : 'text-slate-400', 'bg' => $summary['fine_due'] > 0 ? 'bg-amber-100' : 'bg-slate-100'],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg {{ $card['bg'] }} flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 {{ $card['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 truncate">{{ $card['label'] }}</p>
                    <p class="text-xl font-black {{ $card['color'] }} leading-tight">{{ $card['value'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── LINE GRAPH: 30-DAY TREND ── --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-base font-black text-slate-950">Issue & Return Trend</h2>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Last 30 days</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-black text-slate-500">
                <span class="flex items-center gap-1.5">
                    <span class="w-8 h-0.5 rounded bg-blue-500 inline-block"></span> Issued
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-8 h-0.5 rounded bg-emerald-500 inline-block"></span> Returned
                </span>
            </div>
        </div>

        <div class="relative">
            <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" class="w-full" style="height: 130px;" preserveAspectRatio="none">
                {{-- Grid lines --}}
                @for($gl = 0; $gl <= 4; $gl++)
                    @php $gy = round($padY + ($svgH - 2*$padY) * $gl / 4, 2); @endphp
                    <line x1="{{ $padX }}" y1="{{ $gy }}" x2="{{ $svgW - $padX }}" y2="{{ $gy }}"
                          stroke="#e2e8f0" stroke-width="0.5"/>
                @endfor

                {{-- Area fill (issued) --}}
                <path d="{{ $issuedArea }}" fill="#3b82f6" opacity="0.08"/>

                {{-- Issued line --}}
                @if($issuedPts)
                <polyline points="{{ $issuedPts }}" fill="none" stroke="#3b82f6" stroke-width="2"
                          stroke-linejoin="round" stroke-linecap="round"/>
                @endif

                {{-- Returned line --}}
                @if($returnedPts)
                <polyline points="{{ $returnedPts }}" fill="none" stroke="#10b981" stroke-width="2"
                          stroke-linejoin="round" stroke-linecap="round"/>
                @endif
            </svg>

            {{-- X-axis labels: show every 7th day --}}
            <div class="flex justify-between mt-1 px-1">
                @foreach($dailyDays as $i => $day)
                    @if($i % 7 === 0 || $i === 29)
                        <span class="text-[10px] font-bold text-slate-400" style="flex: none; width: {{ round(100 / 30, 2) }}%">
                            {{ $day->format('d M') }}
                        </span>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Daily totals summary --}}
        <div class="mt-3 flex gap-6 text-sm">
            <span class="font-bold text-slate-600">
                This month: <span class="font-black text-blue-600">{{ $dailyIssued->sum() }} issued</span>
            </span>
            <span class="font-bold text-slate-600">
                <span class="font-black text-emerald-600">{{ $dailyReturned->sum() }} returned</span>
            </span>
        </div>
    </section>

    <div class="grid gap-5 lg:grid-cols-2">

        {{-- ── LOW STOCK ALERT ── --}}
        @if($lowStockBooks->isNotEmpty())
        <section class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-black text-amber-900">Low Stock Alert</h2>
                    <p class="text-xs font-semibold text-amber-600 mt-0.5">{{ $lowStockBooks->count() }} book{{ $lowStockBooks->count() !== 1 ? 's' : '' }} with 0–1 available copies</p>
                </div>
            </div>
            <div class="space-y-2">
                @foreach($lowStockBooks as $book)
                    <div class="flex items-center gap-3 rounded-lg bg-white/70 border border-amber-100 px-3 py-2">
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.library.books.show', $book) }}"
                               class="font-black text-slate-900 text-sm hover:underline truncate block">
                                {{ $book->title }}
                            </a>
                            <p class="text-xs text-slate-500">{{ $book->category?->name ?? 'Uncategorised' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            @if($book->available_copies_count == 0)
                                <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-black text-red-700">Out of stock</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-black text-amber-700">{{ $book->available_copies_count }} left</span>
                            @endif
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $book->copies_count }} total copies</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('admin.library.books.index') }}"
               class="mt-3 inline-block text-xs font-black text-amber-700 hover:underline">
                View all books →
            </a>
        </section>
        @else
        <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm flex items-center gap-3">
            <svg class="w-8 h-8 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
            </svg>
            <div>
                <p class="font-black text-emerald-800">All books are well stocked</p>
                <p class="text-xs font-semibold text-emerald-600 mt-0.5">No titles have 0 or 1 available copies</p>
            </div>
        </section>
        @endif

        {{-- ── OVERDUE LIST ── --}}
        <section class="rounded-xl border border-red-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-black text-slate-950">
                    Overdue Books
                    @if($summary['overdue'] > 0)
                        <span class="ml-1.5 rounded-full bg-red-500 px-2 py-0.5 text-xs font-black text-white">{{ $summary['overdue'] }}</span>
                    @endif
                </h2>
                @if($summary['overdue'] > 0)
                    <a href="{{ route('admin.library.fines.index') }}" class="text-xs font-black text-red-600 hover:underline">View all →</a>
                @endif
            </div>
            @if($overdueLoans->isEmpty())
                <p class="py-6 text-center font-bold text-slate-400">No overdue books.</p>
            @else
                <div class="divide-y divide-slate-100 -mx-5">
                    @foreach($overdueLoans as $loan)
                        @php $daysLate = now()->startOfDay()->diffInDays($loan->due_date, false) * -1; @endphp
                        <div class="flex items-center gap-3 px-5 py-2.5">
                            <div class="flex-1 min-w-0">
                                <p class="font-black text-slate-900 text-sm truncate">{{ $loan->borrower_name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $loan->book_title ?? $loan->copy?->book?->title }}</p>
                            </div>
                            <span class="text-xs font-black text-red-600 shrink-0">{{ $daysLate }}d late</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- ── RECENT LOANS ── --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h2 class="text-base font-black text-slate-950">Recent Issues</h2>
            <a href="{{ route('admin.library.issue.index') }}" class="text-xs font-black hover:underline" style="color: var(--theme-primary, #1a5632)">Issue / Return →</a>
        </div>
        @if($recentLoans->isEmpty())
            <p class="px-5 py-10 text-center font-bold text-slate-400">No loans recorded yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] font-black uppercase tracking-widest text-slate-400">
                        <tr>
                            <th class="px-5 py-3 text-left">Borrower</th>
                            <th class="px-5 py-3 text-left">Book</th>
                            <th class="px-5 py-3 text-left">Issued</th>
                            <th class="px-5 py-3 text-left">Due</th>
                            <th class="px-5 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentLoans as $loan)
                            @php
                                $isOverdue = $loan->status === 'issued' && $loan->due_date < now()->toDateString();
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-bold text-slate-900">{{ $loan->borrower_name }}</td>
                                <td class="px-5 py-3 text-slate-600 max-w-xs truncate">{{ $loan->book_title ?? $loan->copy?->book?->title }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ \Carbon\Carbon::parse($loan->issued_at)->format('d M Y') }}</td>
                                <td class="px-5 py-3 {{ $isOverdue ? 'text-red-600 font-black' : 'text-slate-500' }}">
                                    {{ \Carbon\Carbon::parse($loan->due_date)->format('d M Y') }}
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($loan->status === 'returned')
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-black text-slate-500">Returned</span>
                                    @elseif($isOverdue)
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-black text-red-700">Overdue</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-black text-emerald-700">Active</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

</div>
@endsection
