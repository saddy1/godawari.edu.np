@extends('hajiri.layouts.app')

@section('content')
@php
    $weekendDays     = $setting->weekendDays();
    $isEmployee      = !auth()->user()?->isAdmin() && !auth()->user()?->hasRole('admin');
    $checkInInvalid  = isset($attendanceLogs['in']['at']) && array_key_exists('in_valid', $attendanceLogs) && ! $attendanceLogs['in_valid'];
    $checkOutInvalid = isset($attendanceLogs['out']['at']) && array_key_exists('out_valid', $attendanceLogs) && ! $attendanceLogs['out_valid'];
@endphp

@if($isEmployee)
{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- EMPLOYEE DASHBOARD                                             --}}
{{-- ══════════════════════════════════════════════════════════════ --}}

{{-- Compact welcome banner --}}
<div class="bg-[#1a5632] rounded-2xl px-5 py-4 text-white shadow-md mb-4 relative overflow-hidden">
    <div class="absolute -top-8 -right-8 w-36 h-36 bg-[#e2a024] rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    <div class="relative z-10 flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="text-[10px] font-bold text-[#e2a024] uppercase tracking-widest mb-0.5">Staff Portal</p>
            <h2 class="text-lg font-extrabold leading-tight">Welcome, {{ auth()->user()->name }}</h2>
            <p class="text-green-200 text-xs mt-0.5">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('hajiri.my-leaves') }}"
               class="inline-flex items-center gap-1.5 bg-white/10 border border-white/20 hover:bg-white/20 transition-colors rounded-xl px-3 py-2 text-xs font-bold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Apply Leave
            </a>
            <a href="{{ route('hajiri.staff-card-request.index') }}"
               class="inline-flex items-center gap-1.5 {{ $pendingCardReq ? 'bg-amber-400/30 border-amber-300/50' : 'bg-white/10 border-white/20 hover:bg-white/20' }} border transition-colors rounded-xl px-3 py-2 text-xs font-bold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                {{ $pendingCardReq ? 'Card Requested' : 'ID Card' }}
            </a>
        </div>
    </div>
</div>

{{-- Main 2-col layout --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-4 items-start">

    {{-- LEFT — Big Calendar --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        {{-- Calendar header --}}
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Monthly Calendar</p>
                <h3 class="text-xl font-extrabold text-gray-900 leading-tight">{{ $nowData['nmonthBS'] }} {{ $nowData['yearBS'] }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $nowData['nmonthAD_A'] }} / {{ $nowData['nmonthAD_B'] }} {{ $nowData['yearAD'] }}</p>
            </div>
            <div class="flex gap-2">
                <select id="yearCal" class="changeCalendarDate text-xs border border-gray-200 rounded-xl px-2.5 py-1.5 bg-gray-50 font-semibold focus:outline-none focus:border-[#1a5632]">
                    @foreach($npCal->bs as $bsDate)
                        <option {{ $nowData['yearBS'] == $bsDate[0] ? 'selected' : '' }} value="{{ $bsDate[0] }}">{{ $bsDate[0] }}</option>
                    @endforeach
                </select>
                <select id="monthCal" class="changeCalendarDate text-xs border border-gray-200 rounded-xl px-2.5 py-1.5 bg-gray-50 font-semibold focus:outline-none focus:border-[#1a5632]">
                    @for($i = 1; $i <= 12; $i++)
                        <option {{ $nowData['monthBS'] == $i ? 'selected' : '' }} value="{{ $i }}">{{ $npCal->get_nepali_month($i) }}</option>
                    @endfor
                </select>
            </div>
        </div>

        {{-- Day headers --}}
        <div class="grid grid-cols-7 gap-1 mb-1">
            @foreach(['आइत','सोम','मंगल','बुध','बिहि','शुक्र','शनि'] as $d)
                <div class="text-center text-[9px] font-extrabold text-gray-400 uppercase py-1">{{ $d }}</div>
            @endforeach
        </div>

        {{-- Day cells --}}
        <div class="grid grid-cols-7 gap-1">
            @for($i = 1; $i <= $nowData['firstDay'] - 1; $i++)
                <div class="min-h-14 sm:min-h-16 rounded-xl bg-gray-50"></div>
            @endfor

            @for($i = $nowData['firstBS'], $index = 0; $i <= $nowData['lastBS']; $i++, $index++)
            @php
                $dateObj   = $nowData['periodAD'][$index];
                $dateKey   = $dateObj->format('Y-m-d');
                $isToday   = $dateObj->isSameDay(\Carbon\Carbon::now());
                $isPresent = $attendancePerDay[$index] ?? false;
                $isWeekend = in_array($dateObj->dayOfWeek, $weekendDays, true);
                $holiday   = $holidayRows[$dateKey] ?? null;
                $onLeave   = $leaveRowsByDate[$dateKey] ?? null;

                $dayClass = 'bg-white border border-gray-200 hover:border-[#1a5632] hover:shadow-sm';
                if ($isToday)                    $dayClass = 'bg-[#1a5632] border-[#1a5632]';
                elseif ($holiday || $isWeekend)  $dayClass = 'bg-red-600 border-red-700';
                elseif ($onLeave)                $dayClass = 'bg-purple-600 border-purple-700';
                elseif ($isPresent)              $dayClass = 'bg-green-50 border-green-200';
            @endphp
            <div class="min-h-14 sm:min-h-16 rounded-xl border p-1.5 flex flex-col transition-all {{ $dayClass }} {{ $isToday ? 'ring-2 ring-[#e2a024] ring-offset-1' : '' }}">
                <span class="text-base sm:text-lg font-extrabold leading-none date-text
                    {{ ($isToday || $holiday || $isWeekend || $onLeave) ? 'text-white' : ($isPresent ? 'text-green-700' : 'text-gray-800') }}">{{ $i }}</span>
                <span class="text-[8px] {{ ($holiday || $isWeekend || $onLeave) ? 'text-white/70' : ($isToday ? 'text-green-200' : 'text-gray-400') }} leading-none mt-0.5">{{ $dateObj->format('M d') }}</span>
                @if($holiday)
                    <span class="mt-auto text-[8px] font-bold text-white leading-tight truncate">{{ Str::limit($holiday->label, 6, '') }}</span>
                @elseif($onLeave)
                    <span class="mt-auto text-[8px] font-bold text-white">{{ strtoupper($onLeave->policy->short_code ?? 'L') }}</span>
                @elseif($isToday)
                    <span class="mt-auto text-[8px] font-bold text-white/80">Today</span>
                @elseif($isWeekend)
                    <span class="mt-auto text-[8px] font-bold text-white/80">Off</span>
                @elseif($isPresent)
                    <span class="mt-auto text-[8px] font-bold text-green-600">P</span>
                @endif
            </div>
            @endfor

            @for($i = 1; $i <= 7 - $nowData['lastDay']; $i++)
                <div class="min-h-14 sm:min-h-16 rounded-xl bg-gray-50"></div>
            @endfor
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap gap-3 mt-3 pt-3 border-t border-gray-100">
            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded bg-[#1a5632]"></div>Today</div>
            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded bg-green-100 border border-green-300"></div>Present</div>
            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded bg-red-600"></div>Off/Holiday</div>
            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded bg-purple-600"></div>Leave</div>
            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded bg-white border border-gray-300"></div>No record</div>
        </div>
    </div>

    {{-- RIGHT Sidebar --}}
    <div class="flex flex-col gap-4">

        @if($showPersonalAttendance)
        {{-- ① Today's Attendance — Check In + Check Out in one card --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Today's Attendance</p>
            <div class="grid grid-cols-2 gap-2">
                {{-- Check In --}}
                <div class="flex flex-col gap-1 {{ $checkInInvalid ? 'bg-gray-100' : 'bg-green-50' }} border {{ $checkInInvalid ? 'border-gray-300' : 'border-green-200' }} rounded-xl p-3">
                    <div class="flex items-center gap-1.5 mb-1">
                        <div class="w-6 h-6 {{ $checkInInvalid ? 'bg-gray-800 text-white' : 'bg-green-500 text-white' }} rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14"/></svg>
                        </div>
                        <span class="text-[10px] font-bold {{ $checkInInvalid ? 'text-gray-600' : 'text-green-700' }} uppercase tracking-wide">In</span>
                    </div>
                    @isset($attendanceLogs['in']['at'])
                        <p class="text-base font-black text-gray-900 leading-none">{{ \Carbon\Carbon::parse($attendanceLogs['in']['at'])->format('h:i') }}</p>
                        <p class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($attendanceLogs['in']['at'])->format('A') }}</p>
                        @if($checkInInvalid)
                            <p class="text-[9px] font-bold text-gray-700 mt-0.5 leading-tight">{{ $attendanceLogs['in_rule'] }}</p>
                        @endif
                    @else
                        <p class="text-xs font-bold text-gray-400">—</p>
                    @endisset
                </div>

                {{-- Check Out --}}
                <div class="flex flex-col gap-1 {{ $checkOutInvalid ? 'bg-gray-100' : 'bg-orange-50' }} border {{ $checkOutInvalid ? 'border-gray-300' : 'border-orange-200' }} rounded-xl p-3">
                    <div class="flex items-center gap-1.5 mb-1">
                        <div class="w-6 h-6 {{ $checkOutInvalid ? 'bg-gray-800 text-white' : 'bg-orange-500 text-white' }} rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
                        </div>
                        <span class="text-[10px] font-bold {{ $checkOutInvalid ? 'text-gray-600' : 'text-orange-700' }} uppercase tracking-wide">Out</span>
                    </div>
                    @isset($attendanceLogs['out']['at'])
                        <p class="text-base font-black text-gray-900 leading-none">{{ \Carbon\Carbon::parse($attendanceLogs['out']['at'])->format('h:i') }}</p>
                        <p class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($attendanceLogs['out']['at'])->format('A') }}</p>
                        @if($checkOutInvalid)
                            <p class="text-[9px] font-bold text-gray-700 mt-0.5 leading-tight">{{ $attendanceLogs['out_rule'] }}</p>
                        @endif
                    @else
                        <p class="text-xs font-bold text-gray-400">—</p>
                    @endisset
                </div>
            </div>
        </div>
        @endif

        {{-- ② Leave Balances — compact --}}
        @if($leaveBalances->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">My Leaves</p>
                <a href="{{ route('hajiri.my-leaves') }}" class="text-[10px] font-bold text-[#1a5632] hover:underline">Apply →</a>
            </div>
            <div class="space-y-2">
                @foreach($leaveBalances as $lb)
                @php
                    $pct = $lb['pct'];
                    $barColor = $pct >= 80 ? 'bg-red-500' : ($pct >= 50 ? 'bg-amber-400' : 'bg-green-500');
                @endphp
                <div class="flex items-center gap-2">
                    <div class="shrink-0 w-8 h-8 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center">
                        <span class="text-[9px] font-extrabold text-[#1a5632] leading-none text-center">{{ $lb['policy']->short_code }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-0.5">
                            <span class="text-[11px] font-semibold text-gray-700 truncate">{{ $lb['policy']->name }}</span>
                            <span class="text-[11px] font-bold text-gray-900 ml-1 shrink-0">{{ $lb['remaining'] }}<span class="text-gray-400 font-normal">/{{ $lb['policy']->days_allowed }}</span></span>
                        </div>
                        <div class="w-full h-1 bg-gray-100 rounded-full overflow-hidden">
                            <div class="{{ $barColor }} h-full rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ③ Notices — compact list --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Notices</p>
                <a href="{{ url('/news') }}" target="_blank" class="text-[10px] font-bold text-[#1a5632] hover:underline">All →</a>
            </div>
            @if($latestNotices->isEmpty())
                <p class="text-xs text-gray-400 text-center py-6">No notices</p>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($latestNotices->take(5) as $notice)
                    <a href="{{ route('news.show', $notice->slug) }}" target="_blank"
                       class="flex items-start gap-2.5 px-4 py-2.5 hover:bg-gray-50 transition-colors group">
                        <div class="w-1.5 h-1.5 rounded-full bg-[#e2a024] shrink-0 mt-1.5"></div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-700 group-hover:text-[#1a5632] transition-colors leading-snug line-clamp-2">{{ $notice->title }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $notice->created_at->diffForHumans() }}</p>
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>

    </div>{{-- /right sidebar --}}
</div>{{-- /grid --}}


@else
{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- ADMIN DASHBOARD                                                --}}
{{-- ══════════════════════════════════════════════════════════════ --}}

{{-- Compact admin hero --}}
<div class="bg-[#1a5632] rounded-2xl px-5 py-4 text-white shadow-md mb-4 relative overflow-hidden">
    <div class="absolute -top-8 -right-8 w-36 h-36 bg-[#e2a024] rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    <div class="relative z-10 flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="text-[10px] font-bold text-[#e2a024] uppercase tracking-widest mb-0.5">Attendance Module</p>
            <h2 class="text-lg font-extrabold leading-tight">Today's Overview</h2>
            <p class="text-green-200 text-xs mt-0.5">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <div class="bg-white/10 border border-white/20 rounded-xl px-4 py-2 text-center">
                <span class="block text-2xl font-black text-green-300 leading-none">{{ $empolyeeData['present'] }}</span>
                <span class="text-[10px] font-bold text-green-100 mt-0.5 block">Present</span>
            </div>
            <div class="bg-white/10 border border-white/20 rounded-xl px-4 py-2 text-center">
                <span class="block text-2xl font-black text-red-300 leading-none">{{ $empolyeeData['total'] - $empolyeeData['present'] }}</span>
                <span class="text-[10px] font-bold text-green-100 mt-0.5 block">Absent</span>
            </div>
            <div class="bg-white/10 border border-white/20 rounded-xl px-4 py-2 text-center">
                <span class="block text-2xl font-black text-white leading-none">{{ $empolyeeData['total'] }}</span>
                <span class="text-[10px] font-bold text-green-100 mt-0.5 block">Total</span>
            </div>
        </div>
    </div>
</div>

{{-- Main 2-col layout --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_280px] gap-4 items-start">

    {{-- LEFT — Big Calendar --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Monthly Calendar</p>
                <h3 class="text-xl font-extrabold text-gray-900 leading-tight">{{ $nowData['nmonthBS'] }} {{ $nowData['yearBS'] }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $nowData['nmonthAD_A'] }} / {{ $nowData['nmonthAD_B'] }} {{ $nowData['yearAD'] }}</p>
            </div>
            <div class="flex gap-2">
                <select id="yearCal" class="changeCalendarDate text-xs border border-gray-200 rounded-xl px-2.5 py-1.5 bg-gray-50 font-semibold focus:outline-none focus:border-[#1a5632]">
                    @foreach($npCal->bs as $bsDate)
                        <option {{ $nowData['yearBS'] == $bsDate[0] ? 'selected' : '' }} value="{{ $bsDate[0] }}">{{ $bsDate[0] }}</option>
                    @endforeach
                </select>
                <select id="monthCal" class="changeCalendarDate text-xs border border-gray-200 rounded-xl px-2.5 py-1.5 bg-gray-50 font-semibold focus:outline-none focus:border-[#1a5632]">
                    @for($i = 1; $i <= 12; $i++)
                        <option {{ $nowData['monthBS'] == $i ? 'selected' : '' }} value="{{ $i }}">{{ $npCal->get_nepali_month($i) }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-1 mb-1">
            @foreach(['आइत','सोम','मंगल','बुध','बिहि','शुक्र','शनि'] as $d)
                <div class="text-center text-[9px] font-extrabold text-gray-400 uppercase py-1">{{ $d }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 gap-1">
            @for($i = 1; $i <= $nowData['firstDay'] - 1; $i++)
                <div class="min-h-14 sm:min-h-16 rounded-xl bg-gray-50"></div>
            @endfor

            @for($i = $nowData['firstBS'], $index = 0; $i <= $nowData['lastBS']; $i++, $index++)
            @php
                $dateObj   = $nowData['periodAD'][$index];
                $dateKey   = $dateObj->format('Y-m-d');
                $isToday   = $dateObj->isSameDay(\Carbon\Carbon::now());
                $isPresent = $attendancePerDay[$index] ?? false;
                $isWeekend = in_array($dateObj->dayOfWeek, $weekendDays, true);
                $holiday   = $holidayRows[$dateKey] ?? null;
                $onLeave   = $leaveRowsByDate[$dateKey] ?? null;

                $dayClass = 'bg-white border border-gray-200 hover:border-[#1a5632] hover:shadow-sm';
                if ($isToday)                    $dayClass = 'bg-[#1a5632] border-[#1a5632]';
                elseif ($holiday || $isWeekend)  $dayClass = 'bg-red-600 border-red-700';
                elseif ($onLeave)                $dayClass = 'bg-purple-600 border-purple-700';
                elseif ($isPresent)              $dayClass = 'bg-green-50 border-green-200';
            @endphp
            <div class="min-h-14 sm:min-h-16 rounded-xl border p-1.5 flex flex-col transition-all {{ $dayClass }} {{ $isToday ? 'ring-2 ring-[#e2a024] ring-offset-1' : '' }}">
                <span class="text-base sm:text-lg font-extrabold leading-none date-text
                    {{ ($isToday || $holiday || $isWeekend || $onLeave) ? 'text-white' : ($isPresent ? 'text-green-700' : 'text-gray-800') }}">{{ $i }}</span>
                <span class="text-[8px] {{ ($holiday || $isWeekend || $onLeave) ? 'text-white/70' : ($isToday ? 'text-green-200' : 'text-gray-400') }} leading-none mt-0.5">{{ $dateObj->format('M d') }}</span>
                @if($holiday)
                    <span class="mt-auto text-[8px] font-bold text-white leading-tight truncate">{{ Str::limit($holiday->label, 6, '') }}</span>
                @elseif($onLeave)
                    <span class="mt-auto text-[8px] font-bold text-white">{{ strtoupper($onLeave->policy->short_code ?? 'L') }}</span>
                @elseif($isToday)
                    <span class="mt-auto text-[8px] font-bold text-white/80">Today</span>
                @elseif($isWeekend)
                    <span class="mt-auto text-[8px] font-bold text-white/80">Off</span>
                @elseif($isPresent)
                    <span class="mt-auto text-[8px] font-bold text-green-600">P</span>
                @endif
            </div>
            @endfor

            @for($i = 1; $i <= 7 - $nowData['lastDay']; $i++)
                <div class="min-h-14 sm:min-h-16 rounded-xl bg-gray-50"></div>
            @endfor
        </div>

        <div class="flex flex-wrap gap-3 mt-3 pt-3 border-t border-gray-100">
            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded bg-[#1a5632]"></div>Today</div>
            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded bg-green-100 border border-green-300"></div>Present</div>
            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded bg-red-600"></div>Off/Holiday</div>
            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded bg-purple-600"></div>On Leave</div>
            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded bg-white border border-gray-200"></div>No record</div>
        </div>
    </div>

    {{-- RIGHT Sidebar --}}
    <div class="flex flex-col gap-4">

        @if($showPersonalAttendance)
        {{-- ① Personal Check In/Out — one combined card --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Your Attendance Today</p>
            <div class="grid grid-cols-2 gap-2">
                {{-- Check In --}}
                <div class="flex flex-col gap-1 {{ $checkInInvalid ? 'bg-gray-100' : 'bg-green-50' }} border {{ $checkInInvalid ? 'border-gray-300' : 'border-green-200' }} rounded-xl p-3">
                    <div class="flex items-center gap-1.5 mb-1">
                        <div class="w-6 h-6 {{ $checkInInvalid ? 'bg-gray-800 text-white' : 'bg-green-500 text-white' }} rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14"/></svg>
                        </div>
                        <span class="text-[10px] font-bold {{ $checkInInvalid ? 'text-gray-600' : 'text-green-700' }} uppercase">In</span>
                    </div>
                    @isset($attendanceLogs['in']['at'])
                        <p class="text-base font-black text-gray-900 leading-none">{{ \Carbon\Carbon::parse($attendanceLogs['in']['at'])->format('h:i') }}</p>
                        <p class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($attendanceLogs['in']['at'])->format('A') }}</p>
                        @if($checkInInvalid)
                            <p class="text-[9px] font-bold text-gray-700 leading-tight">{{ $attendanceLogs['in_rule'] }}</p>
                        @endif
                    @else
                        <p class="text-xs font-bold text-gray-400">—</p>
                    @endisset
                </div>

                {{-- Check Out --}}
                <div class="flex flex-col gap-1 {{ $checkOutInvalid ? 'bg-gray-100' : 'bg-orange-50' }} border {{ $checkOutInvalid ? 'border-gray-300' : 'border-orange-200' }} rounded-xl p-3">
                    <div class="flex items-center gap-1.5 mb-1">
                        <div class="w-6 h-6 {{ $checkOutInvalid ? 'bg-gray-800 text-white' : 'bg-orange-500 text-white' }} rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
                        </div>
                        <span class="text-[10px] font-bold {{ $checkOutInvalid ? 'text-gray-600' : 'text-orange-700' }} uppercase">Out</span>
                    </div>
                    @isset($attendanceLogs['out']['at'])
                        <p class="text-base font-black text-gray-900 leading-none">{{ \Carbon\Carbon::parse($attendanceLogs['out']['at'])->format('h:i') }}</p>
                        <p class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($attendanceLogs['out']['at'])->format('A') }}</p>
                        @if($checkOutInvalid)
                            <p class="text-[9px] font-bold text-gray-700 leading-tight">{{ $attendanceLogs['out_rule'] }}</p>
                        @endif
                    @else
                        <p class="text-xs font-bold text-gray-400">—</p>
                    @endisset
                </div>
            </div>
        </div>
        @endif

        {{-- ② Quick links --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Quick Links</p>
            <div class="space-y-1.5">
                @foreach([
                    ['route' => 'hajiri.logs.index', 'label' => 'View All Logs', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    ['route' => 'hajiri.leave-requests.index', 'label' => 'Leave Requests', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ['route' => 'hajiri.report.modal', 'label' => 'Reports', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ] as $link)
                @continue(! \Illuminate\Support\Facades\Route::has($link['route']))
                <a href="{{ route($link['route']) }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-gray-50 text-gray-600 hover:text-[#1a5632] transition-colors group">
                    <div class="w-6 h-6 rounded-lg bg-gray-100 group-hover:bg-[#1a5632]/10 flex items-center justify-center transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/></svg>
                    </div>
                    <span class="text-xs font-semibold">{{ $link['label'] }}</span>
                    <svg class="w-3 h-3 ml-auto opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endforeach
            </div>
        </div>

    </div>{{-- /right sidebar --}}
</div>{{-- /grid --}}

@endif

@endsection

@push('scripts')
<script>
    function translateNepali(s) {
        var o = 2406 - 48;
        return s.toString().split('').map(function(c) {
            var cc = c.charCodeAt(0);
            return (cc >= 48 && cc <= 57) ? String.fromCharCode(cc + o) : c;
        }).join('');
    }

    $(document).ready(function() {
        $('.date-text').each(function() { $(this).text(translateNepali($(this).text())); });

        $('select.changeCalendarDate').on('change', function() {
            var y = $('#yearCal').val(), m = $('#monthCal').val();
            document.location = "{{ url('/admin/hajiri/calendar') }}/" + y + '/' + m;
        });
    });
</script>
@endpush
