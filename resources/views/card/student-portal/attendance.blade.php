@extends('card.student-portal.layout')
@section('title', 'My Attendance')

@section('content')
@php
    $statusClasses = [
        'present' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
        'absent' => 'bg-red-50 text-red-700 ring-red-600/15',
        'holiday' => 'bg-violet-50 text-violet-700 ring-violet-600/15',
        'weekend' => 'bg-sky-50 text-sky-700 ring-sky-600/15',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/15',
        'upcoming' => 'bg-gray-100 text-gray-500 ring-gray-500/10',
    ];
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-[#e2a024]">Student Portal</p>
                <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">My Attendance</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-white/65">
                    Review your daily biometric check-in and check-out records. This page is private to your student account.
                </p>
            </div>

            <form method="GET" action="{{ route('student.attendance') }}" class="grid grid-cols-[1fr_1fr_auto] gap-2 rounded-2xl border border-white/10 bg-white/10 p-2 backdrop-blur-sm">
                <label class="sr-only" for="attendance-month">Month</label>
                <select id="attendance-month" name="month" class="rounded-xl border-0 bg-white px-3 py-2.5 text-xs font-extrabold text-gray-800 shadow-sm focus:ring-2 focus:ring-[#e2a024]">
                    @foreach($monthNames as $number => $name)
                        <option value="{{ $number }}" @selected($month === $number)>{{ $name }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="attendance-year">Year</label>
                <select id="attendance-year" name="year" class="rounded-xl border-0 bg-white px-3 py-2.5 text-xs font-extrabold text-gray-800 shadow-sm focus:ring-2 focus:ring-[#e2a024]">
                    @foreach(array_reverse($supportedYears) as $availableYear)
                        <option value="{{ $availableYear }}" @selected($year === $availableYear)>{{ $availableYear }} BS</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-[#e2a024] px-4 py-2.5 text-xs font-extrabold text-[#0b2415] transition hover:bg-[#efb43f]">View</button>
            </form>
        </div>
    </section>

    @if(blank($deviceId))
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-amber-950">Attendance device not linked</h2>
                    <p class="mt-1 text-sm font-medium leading-6 text-amber-800">Your biometric Device ID has not been assigned to this student account. Please ask the administration to add it from your HR member record.</p>
                </div>
            </div>
        </section>
    @else
        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Present</p>
                <p class="mt-2 text-3xl font-black text-emerald-600">{{ $summary['present'] }}</p>
                <p class="mt-1 text-xs font-semibold text-gray-400">Recorded days</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Absent</p>
                <p class="mt-2 text-3xl font-black text-red-600">{{ $summary['absent'] }}</p>
                <p class="mt-1 text-xs font-semibold text-gray-400">Past working days</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Holiday</p>
                <p class="mt-2 text-3xl font-black text-violet-600">{{ $summary['holidays'] }}</p>
                <p class="mt-1 text-xs font-semibold text-gray-400">Holidays & weekends</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Attendance Rate</p>
                <p class="mt-2 text-3xl font-black text-[#1a5632]">{{ number_format($summary['rate'], 1) }}%</p>
                <p class="mt-1 text-xs font-semibold text-gray-400">Completed working days</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-gray-950">{{ $monthNames[$month] }} {{ $year }} BS</h2>
                    <p class="mt-0.5 text-xs font-medium text-gray-400">Device ID {{ $deviceId }} · Times shown in local time</p>
                </div>
                <div class="flex flex-wrap gap-2 text-[10px] font-extrabold uppercase tracking-wide">
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">Present</span>
                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-red-700">Absent</span>
                    <span class="rounded-full bg-violet-50 px-2.5 py-1 text-violet-700">Holiday</span>
                </div>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-gray-100 text-left">
                    <thead class="bg-gray-50/80">
                        <tr class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">
                            <th class="px-5 py-3">Date (BS)</th>
                            <th class="px-5 py-3">Date (AD)</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Check In</th>
                            <th class="px-5 py-3">Check Out</th>
                            <th class="px-5 py-3 text-right">Punches</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($rows as $row)
                            <tr class="text-sm hover:bg-gray-50/70">
                                <td class="whitespace-nowrap px-5 py-3.5 font-extrabold text-gray-900">{{ $row['bs_date'] }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5"><span class="font-semibold text-gray-700">{{ $row['ad_date'] }}</span><span class="ml-2 text-xs font-medium text-gray-400">{{ $row['weekday'] }}</span></td>
                                <td class="whitespace-nowrap px-5 py-3.5"><span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-extrabold ring-1 ring-inset {{ $statusClasses[$row['status']] }}">{{ $row['status_label'] }}</span></td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-bold text-gray-700">{{ $row['check_in'] }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-bold text-gray-700">{{ $row['check_out'] }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right font-extrabold text-gray-500">{{ $row['punches'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 md:hidden">
                @foreach($rows as $row)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-extrabold text-gray-900">{{ $row['bs_date'] }} BS</p>
                                <p class="mt-0.5 text-xs font-medium text-gray-400">{{ $row['ad_date'] }} · {{ $row['weekday'] }}</p>
                            </div>
                            <span class="inline-flex max-w-[150px] rounded-full px-2.5 py-1 text-center text-[10px] font-extrabold ring-1 ring-inset {{ $statusClasses[$row['status']] }}">{{ $row['status_label'] }}</span>
                        </div>
                        @if($row['punches'])
                            <div class="mt-3 grid grid-cols-3 rounded-xl bg-gray-50 px-3 py-2.5 text-center">
                                <div><p class="text-[9px] font-extrabold uppercase tracking-wider text-gray-400">Check in</p><p class="mt-1 text-xs font-extrabold text-gray-800">{{ $row['check_in'] }}</p></div>
                                <div class="border-x border-gray-200"><p class="text-[9px] font-extrabold uppercase tracking-wider text-gray-400">Check out</p><p class="mt-1 text-xs font-extrabold text-gray-800">{{ $row['check_out'] }}</p></div>
                                <div><p class="text-[9px] font-extrabold uppercase tracking-wider text-gray-400">Punches</p><p class="mt-1 text-xs font-extrabold text-gray-800">{{ $row['punches'] }}</p></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
