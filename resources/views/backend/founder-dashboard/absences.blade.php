{{-- resources/views/backend/founder-dashboard/absences.blade.php --}}
@extends('layouts.admin')

@section('title', 'Absence Explorer')

@section('content')
@php
    $baseParams = array_filter([
        'date' => $date->toDateString(),
        'organization_id' => $organization?->id,
        'department_id' => $department?->id,
        'section_id' => $section?->id,
    ]);
@endphp
<div class="max-w-6xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-black text-gray-900">Absence Explorer</h2>
            <p class="text-sm font-semibold text-gray-500">{{ $date->format('l, d M Y') }}</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            @foreach(array_filter(['organization_id' => $organization?->id, 'department_id' => $department?->id, 'section_id' => $section?->id, 'min_streak' => $minStreak]) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()"
                   class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-semibold">
            <button class="rounded-xl bg-[#1a5632] px-4 py-2 text-sm font-extrabold text-white hover:bg-[#0b2415]">Go</button>
        </form>
    </div>

    {{-- Breadcrumb --}}
    <div class="flex flex-wrap items-center gap-1.5 text-sm font-bold text-gray-500">
        <a href="{{ route('admin.founder.absences', ['date' => $date->toDateString()]) }}"
           class="{{ !$organization ? 'text-[#1a5632]' : 'hover:underline' }}">All Organizations</a>
        @if($organization)
            <span>/</span>
            <a href="{{ route('admin.founder.absences', ['date' => $date->toDateString(), 'organization_id' => $organization->id]) }}"
               class="{{ !$department ? 'text-[#1a5632]' : 'hover:underline' }}">{{ $organization->name }}</a>
        @endif
        @if($department)
            <span>/</span>
            <a href="{{ route('admin.founder.absences', ['date' => $date->toDateString(), 'organization_id' => $organization->id, 'department_id' => $department->id]) }}"
               class="{{ !$section ? 'text-[#1a5632]' : 'hover:underline' }}">{{ $department->name }}</a>
        @endif
        @if($section)
            <span>/</span>
            <span class="text-[#1a5632]">{{ $section->name }}</span>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Level 1: Organizations --}}
    @if(!$organization)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($orgSummaries as $row)
                <a href="{{ route('admin.founder.absences', ['date' => $date->toDateString(), 'organization_id' => $row->organization->id]) }}"
                   class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-[#1a5632] hover:shadow-md">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">{{ $row->organization->type ? ucfirst($row->organization->type) : 'Organization' }}</p>
                    <h3 class="mt-1 text-lg font-black text-gray-900">{{ $row->organization->name }}</h3>
                    <p class="mt-3 text-3xl font-black {{ $row->count > 0 ? 'text-red-600' : 'text-gray-300' }}">{{ $row->count }}</p>
                    <p class="text-xs font-bold text-gray-400">absent today</p>
                </a>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed bg-white py-10 text-center text-sm text-gray-400">No organizations found.</p>
            @endforelse
        </div>
    @endif

    {{-- Level 2: Classes / Departments --}}
    @if($organization && !$department)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($deptSummaries as $row)
                <a href="{{ route('admin.founder.absences', ['date' => $date->toDateString(), 'organization_id' => $organization->id, 'department_id' => $row->department->id]) }}"
                   class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-[#1a5632] hover:shadow-md">
                    <h3 class="text-lg font-black text-gray-900">{{ $row->department->name }}</h3>
                    <p class="mt-3 text-3xl font-black {{ $row->count > 0 ? 'text-red-600' : 'text-gray-300' }}">{{ $row->count }}</p>
                    <p class="text-xs font-bold text-gray-400">absent today</p>
                </a>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed bg-white py-10 text-center text-sm text-gray-400">No classes found for this organization.</p>
            @endforelse
        </div>
    @endif

    {{-- Level 3: Sections --}}
    @if($department && !$section)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($sectionSummaries as $row)
                <a href="{{ route('admin.founder.absences', ['date' => $date->toDateString(), 'organization_id' => $organization->id, 'department_id' => $department->id, 'section_id' => $row->section->id]) }}"
                   class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-[#1a5632] hover:shadow-md">
                    <h3 class="text-lg font-black text-gray-900">Section {{ $row->section->name }}</h3>
                    <p class="mt-3 text-3xl font-black {{ $row->count > 0 ? 'text-red-600' : 'text-gray-300' }}">{{ $row->count }}</p>
                    <p class="text-xs font-bold text-gray-400">absent today</p>
                </a>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed bg-white py-10 text-center text-sm text-gray-400">No sections found for this class.</p>
            @endforelse
        </div>
    @endif

    {{-- Level 4: Student list --}}
    @if($section)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
                <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">{{ $studentRows->count() }} absent student{{ $studentRows->count() === 1 ? '' : 's' }}</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach(['' => 'All', 3 => '3+ days', 5 => '5+ days'] as $value => $label)
                        <a href="{{ route('admin.founder.absences', array_filter(array_merge($baseParams, ['min_streak' => $value, 'has_reason' => $hasReason ?: null]))) }}"
                           class="rounded-full border px-3 py-1.5 text-[11px] font-extrabold {{ (int) $minStreak === (int) $value ? 'border-[#1a5632] bg-[#1a5632] text-white' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                    <a href="{{ route('admin.founder.absences', array_filter(array_merge($baseParams, ['min_streak' => $minStreak, 'has_reason' => $hasReason ? null : '1']))) }}"
                       class="rounded-full border px-3 py-1.5 text-[11px] font-extrabold {{ $hasReason ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">
                        With Reason
                    </a>
                </div>
            </div>

            <div class="divide-y divide-gray-50">
                @forelse($studentRows as $row)
                    @php $student = $row->student; @endphp
                    <div class="p-4 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $student->photo_url }}" class="h-10 w-10 shrink-0 rounded-full object-cover bg-gray-100">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.founder.students.show', $student) }}" class="font-extrabold text-gray-900 hover:underline">{{ $student->full_name }}</a>
                                    <p class="text-xs font-semibold text-gray-400">{{ $student->roll_number ?: '—' }}</p>
                                </div>
                                @if($row->streak_days >= 3)
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $row->streak_days >= 5 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $row->streak_days }}-day streak
                                    </span>
                                @endif
                            </div>
                            <a href="{{ route('admin.founder.students.show', $student) }}" class="shrink-0 text-xs font-extrabold text-[#1a5632] hover:underline">View full history →</a>
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            {{-- Guardian contact --}}
                            <div>
                                @if($student->guardian_contact)
                                    <p class="text-xs font-semibold text-gray-500">Guardian contact: <span class="font-extrabold text-gray-800">{{ $student->guardian_contact }}</span></p>
                                @else
                                    <form method="POST" action="{{ route('admin.founder.students.contact', $student) }}" class="flex gap-2">
                                        @csrf @method('PATCH')
                                        <input type="text" name="guardian_contact" placeholder="Add guardian contact no." required
                                               class="w-full rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold">
                                        <button class="shrink-0 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-extrabold text-white hover:bg-amber-700">Save</button>
                                    </form>
                                @endif
                            </div>

                            {{-- Absence remark --}}
                            <div>
                                <form method="POST" action="{{ route('admin.founder.students.remark', $student) }}" class="flex gap-2">
                                    @csrf
                                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                                    <input type="text" name="remark" value="{{ $row->remark_today }}" list="reason-suggestions" placeholder="Reason for absence (optional)" maxlength="255"
                                           class="w-full rounded-lg border px-3 py-1.5 text-xs font-semibold {{ $row->remark_today ? 'border-emerald-300 bg-emerald-50 text-emerald-900' : 'border-gray-200' }}">
                                    <button class="shrink-0 rounded-lg border px-3 py-1.5 text-xs font-extrabold hover:bg-gray-50 {{ $row->remark_today ? 'border-emerald-300 text-emerald-700' : 'border-gray-200 text-gray-600' }}">Save</button>
                                </form>
                                @if($row->was_absent_yesterday)
                                    <p class="mt-1 text-[10px] font-semibold text-gray-400">
                                        Also absent yesterday{{ $row->remark_yesterday ? ' · Reason: '.$row->remark_yesterday : ' · no reason recorded' }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="p-10 text-center text-sm text-gray-400">No absent students match this filter.</p>
                @endforelse
            </div>
        </div>
    @endif

    <datalist id="reason-suggestions">
        @foreach($reasonSuggestions as $reason)
            <option value="{{ $reason }}">
        @endforeach
    </datalist>
</div>
@endsection
