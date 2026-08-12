@extends('card.student-portal.layout')
@section('title', 'Dashboard')

@section('content')
@php
    $cardStatus = $cardRequest?->status ?? 'not_requested';
    $statusStyles = [
        'not_requested' => ['No request', 'bg-gray-100 text-gray-600'],
        'pending' => ['Pending', 'bg-amber-100 text-amber-700'],
        'approved' => ['Approved', 'bg-green-100 text-green-700'],
        'collected' => ['Collected', 'bg-blue-100 text-blue-700'],
        'rejected' => ['Rejected', 'bg-red-100 text-red-700'],
    ];
    [$statusLabel, $statusClass] = $statusStyles[$cardStatus] ?? $statusStyles['not_requested'];
@endphp

<div class="space-y-6">
    <section class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-widest text-[#e2a024]">Student Portal</p>
                <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">Welcome, {{ $student->first_name }}</h1>
                <p class="mt-2 text-sm font-medium text-white/65">
                    {{ $student->stream ?: 'Class not set' }}{{ $student->section ? ' · Section '.$student->section : '' }} · Roll {{ $student->roll_number }}
                </p>
            </div>
            <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/8 p-3">
                <div class="h-16 w-14 overflow-hidden rounded-xl bg-white/10">
                    @if($student->photo)
                        <img src="{{ $student->photo_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-xl font-extrabold">{{ strtoupper(substr($student->first_name, 0, 1)) }}</div>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-extrabold">{{ $student->full_name }}</p>
                    <p class="text-xs font-semibold text-white/50">{{ $student->user?->student_code ?? $student->roll_number }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats grid: 2×2 on mobile, up to 5 across on md+ --}}
    <section class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-5">
        {{-- Card Status --}}
        <a href="{{ route('student.card-status') }}"
           class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm hover:border-[#1a5632]/40 hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/>
                    </svg>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-[#1a5632] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
            <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">ID Card</p>
            <span class="mt-1.5 inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold {{ $statusClass }}">{{ $statusLabel }}</span>
        </a>

        {{-- Courses --}}
        <a href="{{ route('student.learning') }}"
           class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm hover:border-[#1a5632]/40 hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-[#1a5632] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
            <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Courses</p>
            <p class="mt-0.5 text-2xl font-extrabold text-gray-900 leading-none">{{ $courseCount }}</p>
        </a>

        {{-- Resources --}}
        <a href="{{ route('student.learning') }}"
           class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm hover:border-[#1a5632]/40 hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-[#1a5632] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
            <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Resources</p>
            <p class="mt-0.5 text-2xl font-extrabold text-gray-900 leading-none">{{ $resourceCount }}</p>
        </a>

        {{-- Profile --}}
        <a href="{{ route('student.request-update') }}"
           class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm hover:border-[#1a5632]/40 hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-[#1a5632] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
            <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Profile</p>
            <span class="mt-1.5 inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold {{ $updateRequest ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">
                {{ $updateRequest ? 'Pending' : 'Ready' }}
            </span>
        </a>

        {{-- Library --}}
        @if(\App\Services\ModuleService::enabled('library'))
        <a href="{{ route('student.library') }}"
           class="group relative overflow-hidden rounded-2xl border {{ $libraryOverdueCount > 0 ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-white' }} p-4 shadow-sm hover:shadow-md transition-all">
            <div class="flex items-center justify-between mb-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $libraryOverdueCount > 0 ? 'bg-red-100 text-red-600' : 'bg-teal-50 text-teal-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                @if($libraryNotifCount > 0)
                    <span class="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-extrabold text-white">{{ $libraryNotifCount }}</span>
                @else
                    <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-[#1a5632] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                @endif
            </div>
            <p class="text-[10px] font-extrabold uppercase tracking-widest {{ $libraryOverdueCount > 0 ? 'text-red-500' : 'text-gray-400' }}">Library</p>
            @if($libraryOverdueCount > 0)
                <p class="mt-0.5 text-sm font-extrabold text-red-600 leading-none">{{ $libraryOverdueCount }} overdue</p>
            @elseif($libraryIssuedCount > 0)
                <p class="mt-0.5 text-2xl font-extrabold text-gray-900 leading-none">{{ $libraryIssuedCount }}</p>
            @else
                <p class="mt-0.5 text-xs font-bold text-gray-400">No books</p>
            @endif
        </a>
        @endif
    </section>

    {{-- Library Active Loans Widget --}}
    @if(\App\Services\ModuleService::enabled('library') && ($libraryIssuedCount > 0 || $libraryFineOwed > 0))
    <section class="rounded-2xl border {{ $libraryOverdueCount > 0 ? 'border-red-200 bg-red-50' : 'border-teal-100 bg-teal-50/40' }} overflow-hidden shadow-sm">
        <div class="flex items-center justify-between px-5 py-4 border-b {{ $libraryOverdueCount > 0 ? 'border-red-200' : 'border-teal-100' }}">
            <div>
                <h2 class="text-sm font-extrabold {{ $libraryOverdueCount > 0 ? 'text-red-800' : 'text-teal-900' }}">
                    {{ $libraryOverdueCount > 0 ? '⚠ Overdue Library Books' : 'My Library Books' }}
                </h2>
                @if($libraryFineOwed > 0)
                    <p class="text-xs font-bold text-red-600 mt-0.5">Outstanding fine: Rs. {{ number_format($libraryFineOwed, 2) }}</p>
                @endif
            </div>
            <a href="{{ route('student.library') }}"
               class="text-xs font-extrabold {{ $libraryOverdueCount > 0 ? 'text-red-700 hover:text-red-900' : 'text-teal-700 hover:text-teal-900' }}">
                View all →
            </a>
        </div>
        <div class="divide-y {{ $libraryOverdueCount > 0 ? 'divide-red-100' : 'divide-teal-100' }}">
            @foreach($libraryActiveLoans as $loan)
                @php
                    $isOverdue = $loan->due_date && $loan->due_date->isPast();
                    $daysLeft  = $loan->due_date ? now()->diffInDays($loan->due_date, false) : null;
                @endphp
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-8 h-10 rounded-md flex items-center justify-center shrink-0"
                         style="background: var(--theme-primary, #1a5632);">
                        <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-extrabold text-gray-900 truncate">{{ $loan->copy?->book?->title }}</p>
                        <p class="text-xs font-semibold text-gray-400">Acc# {{ $loan->copy?->accession_no }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        @if($isOverdue)
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-extrabold text-red-700">
                                {{ abs((int)$daysLeft) }}d overdue
                            </span>
                        @elseif($daysLeft !== null && $daysLeft <= 3)
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-extrabold text-amber-700">
                                {{ $daysLeft }}d left
                            </span>
                        @else
                            <span class="text-xs font-semibold text-gray-400">
                                Due {{ $loan->due_date?->format('d M') }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
            @if($libraryIssuedCount > 3)
                <div class="px-5 py-2.5 text-center">
                    <a href="{{ route('student.library') }}" class="text-xs font-extrabold text-teal-700 hover:underline">
                        +{{ $libraryIssuedCount - 3 }} more book{{ $libraryIssuedCount - 3 > 1 ? 's' : '' }} →
                    </a>
                </div>
            @endif
        </div>
    </section>
    @endif

    <section class="grid gap-5 lg:grid-cols-[1fr_340px]">
        {{-- Services --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-extrabold text-gray-950">Student Services</h2>
                <p class="text-xs font-medium text-gray-400 mt-0.5">Quick access to all your services</p>
            </div>
            <div class="grid grid-cols-2 gap-0 divide-x divide-y divide-gray-100">
                @php
                    $services = [
                        ['route' => 'student.attendance',    'title' => 'My Attendance',   'desc' => 'Monthly check-in and check-out',   'icon' => 'M9 12l2 2 4-4m5-4v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h3.5a2.5 2.5 0 015 0H18a2 2 0 012 2z', 'iconBg' => 'bg-orange-50 text-orange-600'],
                        ['route' => 'student.learning',      'title' => 'E-Learning',      'desc' => 'Courses, lessons & mock tests',    'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'iconBg' => 'bg-blue-50 text-blue-600'],
                        ['route' => 'student.card-status',   'title' => 'ID Card',          'desc' => 'Apply & check card status',       'icon' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0', 'iconBg' => 'bg-emerald-50 text-emerald-600'],
                        ['route' => 'student.request-update','title' => 'Update Profile',   'desc' => 'Submit profile changes for approval', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 'iconBg' => 'bg-amber-50 text-amber-600'],
                    ...(\App\Services\ModuleService::enabled('library') ? [
                        ['route' => 'student.library',       'title' => 'My Library',       'desc' => 'Issued books, fines & search',     'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'iconBg' => 'bg-teal-50 text-teal-600'],
                        ['route' => 'student.books.search',  'title' => 'Book Search',  'desc' => 'Search & check book availability', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0', 'iconBg' => 'bg-sky-50 text-sky-600'],
                    ] : []),
                    ];
                @endphp
                @foreach($services as $item)
                <a href="{{ route($item['route']) }}"
                   class="group flex items-start gap-3 p-4 hover:bg-gray-50 transition-colors">
                    <div class="shrink-0 flex h-9 w-9 items-center justify-center rounded-xl {{ $item['iconBg'] }} mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-extrabold text-gray-900 group-hover:text-[#1a5632] transition-colors leading-tight">{{ $item['title'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5 leading-snug">{{ $item['desc'] }}</p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        {{-- Current Details --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-3.5">
                    <h2 class="text-sm font-extrabold text-gray-950">Current Details</h2>
                </div>
                <dl class="divide-y divide-gray-50 text-sm">
                    @foreach([
                        'Name'    => $student->full_name,
                        'User ID' => $student->user?->student_code ?? $student->roll_number,
                        'Class'   => $student->stream,
                        'Section' => $student->section,
                        'Mobile'  => $student->mobile,
                        'Email'   => $student->email,
                    ] as $label => $value)
                    <div class="flex items-center justify-between gap-3 px-5 py-2.5">
                        <dt class="text-xs font-bold text-gray-400 shrink-0">{{ $label }}</dt>
                        <dd class="text-xs font-semibold text-gray-800 text-right truncate">{{ $value ?: '—' }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>

            @if($updateRequest)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3.5">
                <p class="text-sm font-extrabold text-amber-900">Update request pending</p>
                <p class="mt-1 text-xs font-semibold leading-5 text-amber-700">
                    Fields: {{ implode(', ', array_keys($updateRequest->requested_changes ?? [])) }}
                </p>
            </div>
            @endif
        </div>
    </section>
</div>
@endsection
