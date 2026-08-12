@extends('library-admin.layouts.app')

@section('title', 'Reports')

@section('library-content')

@php
    // SVG line chart — 30-day
    $svgW  = 600; $svgH = 100; $padX = 8; $padY = 8;
    $pts   = $dailyIssued->count();
    $maxV  = max(1, $dailyIssued->max(), $dailyReturned->max());
    $xStep = ($svgW - 2 * $padX) / max(1, $pts - 1);
    $yScale = ($svgH - 2 * $padY) / $maxV;

    $issuedPts   = $dailyIssued->values()->map(fn ($v, $i) =>
        round($padX + $i * $xStep, 2) . ',' . round($svgH - $padY - $v * $yScale, 2)
    )->implode(' ');
    $returnedPts = $dailyReturned->values()->map(fn ($v, $i) =>
        round($padX + $i * $xStep, 2) . ',' . round($svgH - $padY - $v * $yScale, 2)
    )->implode(' ');
    $firstX = $padX; $lastX = round($padX + ($pts - 1) * $xStep, 2);
    $issuedArea = "M {$firstX},{$svgH} L " . $issuedPts . " L {$lastX},{$svgH} Z";

    $today    = now()->toDateString();
    $monthAgo = now()->subMonth()->toDateString();
@endphp

<div class="mx-auto max-w-7xl space-y-6">

    {{-- ── PAGE HEADER ── --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-950">Reports</h1>
            <p class="text-sm font-semibold text-slate-400 mt-0.5">Analytics overview and downloadable report exports</p>
        </div>
        <a href="{{ route('admin.library.activity-logs.index') }}"
           class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">
            Activity Logs →
        </a>
    </div>

    {{-- ── SUMMARY STAT CARDS ── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @php $statCards = [
            ['label' => 'Total Books',   'value' => $totalBooks,   'color' => 'text-slate-900', 'bg' => 'bg-slate-100'],
            ['label' => 'Total Copies',  'value' => $totalCopies,  'color' => 'text-blue-700',   'bg' => 'bg-blue-100'],
            ['label' => 'Currently Out', 'value' => $totalIssued,  'color' => 'text-emerald-700','bg' => 'bg-emerald-100'],
            ['label' => 'Overdue Now',   'value' => $totalOverdue, 'color' => $totalOverdue > 0 ? 'text-red-700' : 'text-slate-400', 'bg' => $totalOverdue > 0 ? 'bg-red-100' : 'bg-slate-100'],
        ]; @endphp
        @foreach($statCards as $sc)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">{{ $sc['label'] }}</p>
                <p class="mt-1 text-3xl font-black {{ $sc['color'] }}">{{ $sc['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ── LINE GRAPH: 30-DAY ── --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-base font-black text-slate-950">30-Day Issue & Return Trend</h2>
            </div>
            <div class="flex items-center gap-4 text-xs font-black text-slate-500">
                <span class="flex items-center gap-1.5"><span class="w-7 h-0.5 rounded bg-blue-500 inline-block"></span> Issued</span>
                <span class="flex items-center gap-1.5"><span class="w-7 h-0.5 rounded bg-emerald-500 inline-block"></span> Returned</span>
            </div>
        </div>
        <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" class="w-full" style="height: 120px;" preserveAspectRatio="none">
            @for($gl = 0; $gl <= 3; $gl++)
                @php $gy = round($padY + ($svgH - 2*$padY) * $gl / 3, 2); @endphp
                <line x1="{{ $padX }}" y1="{{ $gy }}" x2="{{ $svgW - $padX }}" y2="{{ $gy }}" stroke="#f1f5f9" stroke-width="1"/>
            @endfor
            <path d="{{ $issuedArea }}" fill="#3b82f6" opacity="0.07"/>
            @if($issuedPts)
            <polyline points="{{ $issuedPts }}" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
            @endif
            @if($returnedPts)
            <polyline points="{{ $returnedPts }}" fill="none" stroke="#10b981" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
            @endif
        </svg>
        <div class="flex justify-between mt-1">
            @foreach($dailyDays as $i => $day)
                @if($i === 0 || $i % 7 === 0 || $i === 29)
                    <span class="text-[10px] font-bold text-slate-400">{{ $day->format('d M') }}</span>
                @endif
            @endforeach
        </div>
    </section>

    <div class="grid gap-5 lg:grid-cols-2">

        {{-- ── MONTHLY BAR CHART ── --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-black text-slate-950">Monthly Trend (6 Months)</h2>
            <div class="flex items-end gap-3 h-36">
                @foreach($months as $i => $month)
                    @php
                        $iss = $monthlyIssued[$i];
                        $ret = $monthlyReturned[$i];
                        $maxM = max(1, $monthlyIssued->max(), $monthlyReturned->max());
                    @endphp
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="flex items-end gap-0.5 w-full justify-center" style="height: 100px;">
                            <div class="rounded-t-sm bg-blue-400 flex-1 transition-all" title="{{ $iss }} issued"
                                 style="height: {{ round(($iss / $maxM) * 100) }}px; min-height: {{ $iss > 0 ? 3 : 0 }}px;"></div>
                            <div class="rounded-t-sm bg-emerald-400 flex-1 transition-all" title="{{ $ret }} returned"
                                 style="height: {{ round(($ret / $maxM) * 100) }}px; min-height: {{ $ret > 0 ? 3 : 0 }}px;"></div>
                        </div>
                        <p class="text-[10px] font-black text-slate-500">{{ $month->format('M') }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-3 flex items-center gap-4 text-xs font-black text-slate-500">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-blue-400 inline-block"></span> Issued</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-emerald-400 inline-block"></span> Returned</span>
            </div>
        </section>

        {{-- ── FINE SUMMARY ── --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-black text-slate-950">Fine Summary</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-sm font-black text-slate-700">Books with fines</p>
                    <p class="text-lg font-black text-slate-900">{{ $fineStats['total_fined'] }}</p>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3">
                    <p class="text-sm font-black text-emerald-700">Total collected</p>
                    <p class="text-lg font-black text-emerald-700">Rs. {{ number_format($fineStats['total_collected'], 2) }}</p>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-red-50 border border-red-100 px-4 py-3">
                    <p class="text-sm font-black text-red-700">Still pending</p>
                    <p class="text-lg font-black text-red-700">Rs. {{ number_format($fineStats['total_pending'], 2) }}</p>
                </div>
            </div>
            <a href="{{ route('admin.library.fines.index') }}"
               class="mt-3 inline-block text-xs font-black hover:underline" style="color: var(--theme-primary,#1a5632)">
                Manage fines →
            </a>
        </section>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">

        {{-- ── TOP BORROWERS ── --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-black text-slate-950">Top 10 Borrowers</h2>
            @forelse($topBorrowers as $i => $borrower)
                <div class="flex items-center gap-3 py-1.5">
                    <span class="w-5 text-xs font-black text-slate-400 text-right shrink-0">{{ $i + 1 }}.</span>
                    <div class="flex-1 min-w-0">
                        <p class="font-black text-slate-900 text-sm truncate">{{ $borrower->borrower_name }}</p>
                        <p class="text-xs text-slate-400">{{ $borrower->borrower_type }}</p>
                    </div>
                    <span class="text-sm font-black text-slate-900 shrink-0">{{ $borrower->total }}</span>
                </div>
            @empty
                <p class="py-6 text-center font-bold text-slate-400">No data yet.</p>
            @endforelse
        </section>

        {{-- ── CATEGORY DISTRIBUTION ── --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-black text-slate-950">Books by Category</h2>
            @php $maxCat = max(1, $categoryStats->max('books_count') ?? 1); @endphp
            @forelse($categoryStats as $cat)
                <div class="mb-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-black text-slate-800">{{ $cat->name }}</span>
                        <span class="text-sm font-black text-slate-500">{{ $cat->books_count }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ round(($cat->books_count / $maxCat) * 100) }}%; background: var(--theme-primary,#1a5632);"></div>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center font-bold text-slate-400">No categories yet.</p>
            @endforelse
        </section>
    </div>

    {{-- ── DOWNLOAD REPORTS ── --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-black text-slate-950 mb-1">Download Reports</h2>
        <p class="text-xs font-semibold text-slate-400 mb-5">All exports are CSV files you can open in Excel or Google Sheets.</p>

        {{-- Date range picker (shared by issue/return/fine exports) --}}
        <form id="dlForm" method="GET" action="{{ route('admin.library.reports.download') }}" target="_blank">
            <input type="hidden" name="type" id="dlType" value="daily_issues">

            <div class="flex flex-wrap gap-3 items-end mb-5">
                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    From
                    <input type="date" name="from" value="{{ $monthAgo }}"
                           class="mt-1 block rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
                </label>
                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    To
                    <input type="date" name="to" value="{{ $today }}"
                           class="mt-1 block rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
                </label>
                <p class="text-xs font-semibold text-slate-400 pb-2.5">← Date range applies to issue, return & fine exports</p>
            </div>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $exports = [
                    ['type' => 'all_books',         'label' => 'All Books List',          'desc' => 'Title, author, ISBN, category, total & available copies', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253', 'date' => false],
                    ['type' => 'books_by_category', 'label' => 'Books by Category',       'desc' => 'All books grouped under each catalog category', 'icon' => 'M4 7l8-4 8 4-8 4-8-4zm0 5l8 4 8-4M4 17l8 4 8-4', 'date' => false],
                    ['type' => 'daily_issues',      'label' => 'Daily Issue Log',          'desc' => 'Every book issue record in selected date range', 'icon' => 'M7 7h11m0 0l-4-4m4 4l-4 4', 'date' => true],
                    ['type' => 'daily_returns',     'label' => 'Daily Return Log',         'desc' => 'Every return record with fine info in date range', 'icon' => 'M17 17H6m0 0l4 4m-4-4l4-4', 'date' => true],
                    ['type' => 'overdue',           'label' => 'Overdue Books (Current)',  'desc' => 'All currently overdue loans with days late & fine', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0', 'date' => false],
                    ['type' => 'fines',             'label' => 'Fine Report',              'desc' => 'Fined loans in date range: collected vs. pending', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1', 'date' => true],
                ];
            @endphp

            @foreach($exports as $exp)
                <div class="rounded-xl border border-slate-200 p-4 flex items-start gap-3 hover:border-slate-300 hover:bg-slate-50 transition-colors">
                    <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $exp['icon'] }}"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-black text-slate-900 text-sm">{{ $exp['label'] }}</p>
                        <p class="text-xs text-slate-400 mt-0.5 leading-snug">{{ $exp['desc'] }}</p>
                        @if($exp['date'])
                            <p class="text-[10px] font-black text-amber-600 mt-1">Uses date range above</p>
                        @endif
                    </div>
                    <button onclick="downloadReport('{{ $exp['type'] }}', {{ $exp['date'] ? 'true' : 'false' }})"
                            class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-700 hover:bg-white hover:border-slate-300 flex items-center gap-1.5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        CSV
                    </button>
                </div>
            @endforeach
        </div>
    </section>

</div>

@push('scripts')
<script>
function downloadReport(type, needsDate) {
    const form = document.getElementById('dlForm');
    document.getElementById('dlType').value = type;
    if (!needsDate) {
        // Temporarily clear dates so they don't confuse the request
        const url = new URL(form.action);
        url.searchParams.set('type', type);
        window.open(url.toString(), '_blank');
    } else {
        form.submit();
    }
}
</script>
@endpush

@endsection
