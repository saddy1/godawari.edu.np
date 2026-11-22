{{--
    Shared "whole academic year, every month, one page" attendance calendar.
    Expects: $academicYear, $academicYears, $months, $summary, $monthNames,
    $currentMonth, $currentMonthBsYear, $yearFormAction.
    Optional: $eyebrow (default 'Student Portal'), $heading (default 'Attendance').

    Deliberately no horizontally-scrolling strip anywhere on this page — day
    cells wrap onto multiple lines instead. A nested overflow-x container
    kept trapping the page's own vertical scroll on some browsers/input
    devices no matter how its overflow-y was set, so removing it outright is
    the one fix that can't regress.
--}}
@php
    $eyebrow = $eyebrow ?? 'Student Portal';
    $heading = $heading ?? 'Attendance';
    $statusStyles = [
        'present' => ['bg-emerald-500 text-white', 'P'],
        'late' => ['bg-amber-400 text-white', 'L'],
        'absent' => ['bg-red-500 text-white', 'A'],
        'holiday' => ['bg-violet-100 text-violet-500', 'H'],
        'weekend' => ['bg-sky-100 text-sky-500', 'W'],
        'upcoming' => ['bg-gray-50 text-gray-300', ''],
        'pending' => ['bg-gray-100 text-gray-400', '·'],
    ];
@endphp

<div class="space-y-6">
    <section class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-4 text-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-[#e2a024]">{{ $eyebrow }}</p>
                <h1 class="mt-1 text-xl font-extrabold">{{ $heading }}</h1>
                <p class="mt-0.5 text-[11px] font-semibold text-white/50">{{ $academicYear->name }}</p>
            </div>

            <form method="GET" action="{{ $yearFormAction }}">
                <label class="sr-only" for="attendance-academic-year">Academic Year</label>
                <select id="attendance-academic-year" name="academic_year_id" onchange="this.form.submit()" class="rounded-lg border-0 bg-white px-2.5 py-1.5 text-xs font-extrabold text-gray-800 shadow-sm focus:ring-2 focus:ring-[#e2a024]">
                    @foreach($academicYears as $availableYear)
                        <option value="{{ $availableYear->id }}" @selected($academicYear->id === $availableYear->id)>{{ $availableYear->name }}{{ $availableYear->is_active ? ' · Active' : '' }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="mt-3 grid grid-cols-4 gap-2">
            <div class="rounded-lg border border-white/10 bg-white/10 px-2 py-2 text-center backdrop-blur-sm">
                <p class="text-base font-extrabold">{{ $summary['present'] }}</p>
                <p class="text-[9px] font-bold uppercase tracking-wide text-white/55">Present</p>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/10 px-2 py-2 text-center backdrop-blur-sm">
                <p class="text-base font-extrabold">{{ $summary['absent'] }}</p>
                <p class="text-[9px] font-bold uppercase tracking-wide text-white/55">Absent</p>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/10 px-2 py-2 text-center backdrop-blur-sm">
                <p class="text-base font-extrabold">{{ $summary['holidays'] }}</p>
                <p class="text-[9px] font-bold uppercase tracking-wide text-white/55">Holidays</p>
            </div>
            <div class="rounded-lg border border-white/10 px-2 py-2 text-center backdrop-blur-sm bg-[#e2a024]/20">
                <p class="text-base font-extrabold text-[#e2a024]">{{ number_format($summary['rate'], 1) }}%</p>
                <p class="text-[9px] font-bold uppercase tracking-wide text-white/55">Rate</p>
            </div>
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-gray-200 bg-white p-3 text-[10px] font-bold text-gray-500 shadow-sm">
        <span>Small dot under each day = notebook check:</span>
        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Checked</span>
        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-red-500"></span> Not checked</span>
        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-gray-200"></span> Not recorded</span>
    </div>

    {{-- Jump to month --}}
    <nav class="flex flex-wrap gap-1.5 rounded-2xl border border-gray-200 bg-white p-2 shadow-sm">
        @foreach($months as $m)
            <a href="#month-{{ $m['bs_year'] }}-{{ $m['number'] }}"
               class="shrink-0 rounded-xl px-3 py-2 text-xs font-extrabold transition
                      {{ $m['number'] === $currentMonth && $m['bs_year'] === $currentMonthBsYear ? 'bg-[#1a5632] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                {{ $monthNames[$m['number']] }}
            </a>
        @endforeach
    </nav>

    <div class="space-y-4">
        @foreach($months as $m)
            <section id="month-{{ $m['bs_year'] }}-{{ $m['number'] }}" class="scroll-mt-20 rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-extrabold text-gray-950">{{ $monthNames[$m['number']] }} {{ $m['bs_year'] }} BS</h2>
                    <div class="flex flex-wrap gap-1.5 text-[10px] font-extrabold">
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">{{ $m['summary']['present'] }} Present</span>
                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-red-700">{{ $m['summary']['absent'] }} Absent</span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-500">{{ number_format($m['summary']['rate'], 0) }}%</span>
                        @if($m['summary']['notebook_checked'] + $m['summary']['notebook_not_checked'] > 0)
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">📓 {{ $m['summary']['notebook_checked'] }} checked</span>
                            @if($m['summary']['notebook_not_checked'] > 0)
                                <span class="rounded-full bg-red-50 px-2.5 py-1 text-red-700">📓 {{ $m['summary']['notebook_not_checked'] }} not checked</span>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap gap-1.5 p-3">
                    @foreach($m['days'] as $day)
                        @php
                            [$cellClass, $letter] = $statusStyles[$day['status']] ?? ['bg-gray-50 text-gray-300', ''];
                            $notebookCellClass = match ($day['notebook_status']) {
                                'checked' => 'bg-emerald-500',
                                'not_checked' => 'bg-red-500',
                                default => 'bg-gray-200',
                            };
                            $tooltip = $day['ad_date'].' ('.$day['weekday'].') — '.$day['status_label'];
                            if ($day['periods']->isNotEmpty()) {
                                $tooltip .= ': '.$day['periods']->map(fn ($p) => $p['subject'].' ('.ucfirst($p['status']).')')->implode(', ');
                            }
                            $tooltip .= ' · Notebook: '.($day['notebook_status'] === 'checked' ? 'Checked' : ($day['notebook_status'] === 'not_checked' ? 'Not checked' : 'Not recorded'));
                        @endphp
                        <div class="flex w-9 flex-col items-center gap-1" title="{{ $tooltip }}">
                            <span class="text-[9px] font-bold text-gray-400">{{ $day['number'] }}</span>
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg text-[11px] font-black {{ $cellClass }}">{{ $letter }}</span>
                            <span class="h-2 w-2 rounded-full {{ $notebookCellClass }}"></span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
