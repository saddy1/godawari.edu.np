{{-- resources/views/backend/founder-dashboard/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Founder Dashboard')

@php
    $initials = fn (string $name) => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
    $teacherNamesFor = fn ($lesson) => $lesson->groups->flatMap->teachers->unique('id')->pluck('name')->implode(', ') ?: 'Unassigned';
    $sectionLabelFor = fn ($lesson) => trim(($lesson->section->department->name ?? '').' - '.($lesson->section->name ?? ''), ' -');
    $phoneFor = fn ($student) => $student->guardian_contact ?: ($student->parent_contact ?: $student->emergency_contact_phone);
    $severityStyle = [
        'yellow' => ['border' => 'border-l-yellow-400', 'chip' => 'bg-yellow-100 text-yellow-800', 'badge' => null],
        'orange' => ['border' => 'border-l-orange-400', 'chip' => 'bg-orange-100 text-orange-800', 'badge' => null],
        'red'    => ['border' => 'border-l-red-500', 'chip' => 'bg-red-100 text-red-700', 'badge' => 'URGENT'],
    ];
    $streaksBySeverity = $streaks->groupBy('severity');
@endphp

@section('content')

<div x-data="{ absentModalOpen: false, presentModalOpen: false, allClassesModalOpen: false, takenModalOpen: false, notTakenModalOpen: false }">

    {{-- Range selector --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm font-bold text-gray-500">Teaching &amp; learning — real-time oversight for {{ now()->format('l, jS F Y') }}</p>
        </div>
        <div class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white p-1 shadow-sm" data-founder-range>
            @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $value => $label)
                <a href="{{ route('admin.founder.dashboard', ['range' => $value]) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-extrabold transition-colors {{ $range === $value ? 'bg-[#1a5632] text-white' : 'text-gray-500 hover:bg-gray-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- KPI strip --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-6">
        <button type="button" @click="allClassesModalOpen = true"
                class="rounded-2xl border border-gray-100 bg-white p-4 text-left shadow-sm transition-colors hover:bg-gray-50">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Classes Scheduled Today</p>
            <p class="mt-1 text-2xl font-black text-gray-900">{{ $kpis['scheduled'] }}</p>
            @if($kpis['scheduled'] > 0)<p class="mt-1 text-[10px] font-bold text-gray-500 underline">View list →</p>@endif
        </button>
        <button type="button" @click="takenModalOpen = true"
                class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-left shadow-sm transition-colors hover:bg-emerald-100">
            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Classes Taken</p>
            <p class="mt-1 text-2xl font-black text-emerald-800">{{ $kpis['taken'] }}</p>
            @if($kpis['taken'] > 0)<p class="mt-1 text-[10px] font-bold text-emerald-700 underline">View list →</p>@endif
        </button>
        <button type="button" @click="notTakenModalOpen = true"
                class="rounded-2xl border border-red-100 bg-red-50 p-4 text-left shadow-sm transition-colors hover:bg-red-100">
            <p class="text-[10px] font-bold uppercase tracking-wider text-red-700">Classes NOT Taken</p>
            <p class="mt-1 text-2xl font-black text-red-700">{{ $kpis['not_taken'] }}</p>
            @if($kpis['not_taken'] > 0)<p class="mt-1 text-[10px] font-bold text-red-600 underline">View list →</p>@endif
        </button>
        <button type="button" @click="allClassesModalOpen = true"
                class="rounded-2xl border border-gray-100 bg-white p-4 text-left shadow-sm transition-colors hover:bg-gray-50">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Attendance Compliance</p>
            <p class="mt-1 text-2xl font-black text-gray-900">{{ $kpis['compliance'] }}%</p>
            <p class="mt-1 text-[10px] font-bold text-gray-500 underline">View list →</p>
        </button>
        <button type="button" @click="presentModalOpen = true"
                class="rounded-2xl border border-gray-100 bg-white p-4 text-left shadow-sm transition-colors hover:bg-gray-50">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Students Present</p>
            <p class="mt-1 text-2xl font-black text-gray-900">{{ $kpis['students_present'] }}</p>
            @if($kpis['students_present'] > 0)<p class="mt-1 text-[10px] font-bold text-gray-500 underline">View list →</p>@endif
        </button>
        <button type="button" @click="absentModalOpen = true"
                class="rounded-2xl border border-amber-100 bg-amber-50 p-4 text-left shadow-sm transition-colors hover:bg-amber-100">
            <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Students Absent Today</p>
            <p class="mt-1 text-2xl font-black text-amber-700">{{ $kpis['students_absent'] }}</p>
            @if($kpis['students_absent'] > 0)
                <p class="mt-1 text-[10px] font-bold text-amber-600 underline">View list →</p>
            @endif
        </button>
    </div>

    {{-- All classes / compliance modal --}}
    <div x-show="allClassesModalOpen" x-cloak
         class="fixed inset-0 z-10000 flex items-start justify-center overflow-y-auto bg-slate-950/60 p-4 pt-10 backdrop-blur-sm sm:pt-16"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click.self="allClassesModalOpen = false" @keydown.escape.window="allClassesModalOpen = false">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                <div>
                    <p class="text-sm font-black text-gray-900">All Classes Today</p>
                    <p class="text-[11px] font-semibold text-gray-400">{{ $kpis['scheduled'] }} scheduled · {{ $kpis['compliance'] }}% compliance so far · {{ now()->format('l, j M Y') }}</p>
                </div>
                <button type="button" @click="allClassesModalOpen = false" class="grid h-8 w-8 place-items-center rounded-lg bg-gray-100 text-lg font-black text-gray-500 hover:bg-gray-200">×</button>
            </div>
            <div class="max-h-[70vh] overflow-y-auto p-4">
                @forelse($allClassesDetail as $row)
                    @php $badge = match($row->status) { 'taken' => ['Taken', 'bg-emerald-100 text-emerald-700'], 'pending' => ['Pending', 'bg-amber-100 text-amber-800'], 'not_taken' => ['Not Taken', 'bg-red-100 text-red-700'], default => ['Upcoming', 'bg-gray-100 text-gray-500'] }; @endphp
                    <div class="mb-2 flex items-center gap-3 rounded-xl bg-gray-50 px-3 py-2.5 last:mb-0">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-extrabold text-gray-900">{{ $row->section_label }} <span class="font-semibold text-gray-400">· {{ $row->period_label }}</span></p>
                            <p class="truncate text-[10px] font-semibold text-gray-500">{{ $row->subject_names }} · {{ $row->teacher_names }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[9px] font-black {{ $badge[1] }}">{{ $badge[0] }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs font-bold text-gray-400">No classes scheduled today.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Classes taken modal --}}
    <div x-show="takenModalOpen" x-cloak
         class="fixed inset-0 z-10000 flex items-start justify-center overflow-y-auto bg-slate-950/60 p-4 pt-10 backdrop-blur-sm sm:pt-16"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click.self="takenModalOpen = false" @keydown.escape.window="takenModalOpen = false">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                <div>
                    <p class="text-sm font-black text-gray-900">Classes Taken</p>
                    <p class="text-[11px] font-semibold text-gray-400">{{ $kpis['taken'] }} of {{ $kpis['scheduled'] }} · {{ now()->format('l, j M Y') }}</p>
                </div>
                <button type="button" @click="takenModalOpen = false" class="grid h-8 w-8 place-items-center rounded-lg bg-gray-100 text-lg font-black text-gray-500 hover:bg-gray-200">×</button>
            </div>
            <div class="max-h-[70vh] overflow-y-auto p-4">
                @forelse($takenClassesDetail as $row)
                    <div class="mb-2 flex items-center gap-3 rounded-xl border-l-4 border-l-emerald-400 bg-gray-50 px-3 py-2.5 last:mb-0">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-extrabold text-gray-900">{{ $row->section_label }} <span class="font-semibold text-gray-400">· {{ $row->period_label }}</span></p>
                            <p class="truncate text-[10px] font-semibold text-gray-500">{{ $row->subject_names }} · {{ $row->teacher_names }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-[9px] font-black text-emerald-700">Taken</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs font-bold text-gray-400">No classes have submitted attendance yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Classes not taken modal --}}
    <div x-show="notTakenModalOpen" x-cloak
         class="fixed inset-0 z-10000 flex items-start justify-center overflow-y-auto bg-slate-950/60 p-4 pt-10 backdrop-blur-sm sm:pt-16"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click.self="notTakenModalOpen = false" @keydown.escape.window="notTakenModalOpen = false">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                <div>
                    <p class="text-sm font-black text-gray-900">Classes NOT Taken</p>
                    <p class="text-[11px] font-semibold text-gray-400">{{ $kpis['not_taken'] }} class{{ $kpis['not_taken'] === 1 ? '' : 'es' }} overdue · {{ now()->format('l, j M Y') }}</p>
                </div>
                <button type="button" @click="notTakenModalOpen = false" class="grid h-8 w-8 place-items-center rounded-lg bg-gray-100 text-lg font-black text-gray-500 hover:bg-gray-200">×</button>
            </div>
            <div class="max-h-[70vh] overflow-y-auto p-4">
                @forelse($notTakenClassesDetail as $row)
                    <div class="mb-2 flex items-center gap-3 rounded-xl border-l-4 border-l-red-500 bg-gray-50 px-3 py-2.5 last:mb-0">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-extrabold text-gray-900">{{ $row->section_label }} <span class="font-semibold text-gray-400">· {{ $row->period_label }}</span></p>
                            <p class="truncate text-[10px] font-semibold text-gray-500">{{ $row->subject_names }} · {{ $row->teacher_names }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[9px] font-black {{ $row->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700' }}">{{ $row->status === 'pending' ? 'Pending' : 'Not Taken' }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs font-bold text-gray-400">All started classes have marked attendance ✓</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Present students modal --}}
    <div x-show="presentModalOpen" x-cloak
         class="fixed inset-0 z-10000 flex items-start justify-center overflow-y-auto bg-slate-950/60 p-4 pt-10 backdrop-blur-sm sm:pt-16"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click.self="presentModalOpen = false" @keydown.escape.window="presentModalOpen = false">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                <div>
                    <p class="text-sm font-black text-gray-900">Students Present Today</p>
                    <p class="text-[11px] font-semibold text-gray-400">{{ $kpis['students_present'] }} student{{ $kpis['students_present'] === 1 ? '' : 's' }} · {{ now()->format('l, j M Y') }}</p>
                </div>
                <button type="button" @click="presentModalOpen = false" class="grid h-8 w-8 place-items-center rounded-lg bg-gray-100 text-lg font-black text-gray-500 hover:bg-gray-200">×</button>
            </div>
            <div class="max-h-[70vh] overflow-y-auto p-4">
                @forelse($presentStudentsToday as $group)
                    <div class="mb-4 last:mb-0">
                        <p class="mb-2 text-[10px] font-black uppercase tracking-wide text-gray-400">{{ $group->label }} <span class="opacity-60">({{ $group->students->count() }})</span></p>
                        <div class="space-y-2">
                            @foreach($group->students as $student)
                                <a href="{{ route('students.show', $student) }}" target="_blank" rel="noopener"
                                   class="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 px-3 py-2.5 transition-colors hover:bg-gray-100">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gray-200 text-[10px] font-black text-gray-600">{{ $initials($student->full_name ?? 'S') }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-extrabold text-gray-900">{{ $student->full_name ?? 'Student' }} <span class="font-semibold text-gray-400">· {{ $student->roll_number ?? '—' }}</span></p>
                                        <p class="truncate text-[10px] font-semibold text-gray-500">{{ $student->address_en ?: trim(collect([$student->permanent_tole, $student->permanent_municipality, $student->permanent_district])->filter()->implode(', ')) ?: 'No address on file' }}</p>
                                        <p class="truncate text-[10px] font-semibold text-gray-400">{{ $phoneFor($student) ?: 'No contact on file' }}</p>
                                    </div>
                                    <svg class="h-4 w-4 shrink-0 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs font-bold text-gray-400">No students present today.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Absent students modal --}}
    <div x-show="absentModalOpen" x-cloak
         class="fixed inset-0 z-10000 flex items-start justify-center overflow-y-auto bg-slate-950/60 p-4 pt-10 backdrop-blur-sm sm:pt-16"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click.self="absentModalOpen = false" @keydown.escape.window="absentModalOpen = false">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                <div>
                    <p class="text-sm font-black text-gray-900">Students Absent Today</p>
                    <p class="text-[11px] font-semibold text-gray-400">{{ $kpis['students_absent'] }} student{{ $kpis['students_absent'] === 1 ? '' : 's' }} · {{ now()->format('l, j M Y') }}</p>
                </div>
                <button type="button" @click="absentModalOpen = false" class="grid h-8 w-8 place-items-center rounded-lg bg-gray-100 text-lg font-black text-gray-500 hover:bg-gray-200">×</button>
            </div>
            <div class="max-h-[70vh] overflow-y-auto p-4">
                @forelse($absentStudentsToday as $group)
                    <div class="mb-4 last:mb-0">
                        <p class="mb-2 text-[10px] font-black uppercase tracking-wide text-gray-400">{{ $group->label }} <span class="opacity-60">({{ $group->students->count() }})</span></p>
                        <div class="space-y-2">
                            @foreach($group->students as $student)
                                <a href="{{ route('students.show', $student) }}" target="_blank" rel="noopener"
                                   class="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 px-3 py-2.5 transition-colors hover:bg-gray-100">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gray-200 text-[10px] font-black text-gray-600">{{ $initials($student->full_name ?? 'S') }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-extrabold text-gray-900">{{ $student->full_name ?? 'Student' }} <span class="font-semibold text-gray-400">· {{ $student->roll_number ?? '—' }}</span></p>
                                        <p class="truncate text-[10px] font-semibold text-gray-500">{{ $student->address_en ?: trim(collect([$student->permanent_tole, $student->permanent_municipality, $student->permanent_district])->filter()->implode(', ')) ?: 'No address on file' }}</p>
                                        <p class="truncate text-[10px] font-semibold text-gray-400">{{ $phoneFor($student) ?: 'No contact on file' }}</p>
                                    </div>
                                    <svg class="h-4 w-4 shrink-0 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs font-bold text-gray-400">No students absent today ✓</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Row 2: teachers not marking / conflicts / escalation --}}
    <div class="mb-6 grid grid-cols-1 gap-4 xl:grid-cols-3">

        {{-- Teachers who haven't marked attendance --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm xl:col-span-1">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-black text-gray-900">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-red-50 text-red-600">⚠</span>
                Teachers Who Haven't Marked Attendance
            </h3>
            <div class="max-h-96 space-y-2 overflow-y-auto pr-1">
                @forelse($notTakenRows as $row)
                    @php $badge = $row->status === 'pending' ? ['Pending', 'bg-amber-100 text-amber-800'] : ['Not Taken', 'bg-red-100 text-red-700']; @endphp
                    <div class="flex items-center gap-2.5 rounded-xl border-l-4 {{ $row->status === 'pending' ? 'border-l-amber-400' : 'border-l-red-500' }} bg-gray-50 px-3 py-2.5">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-gray-200 text-[10px] font-black text-gray-600">{{ $initials($teacherNamesFor($row->lesson)) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-extrabold text-gray-900">{{ $teacherNamesFor($row->lesson) }}</p>
                            <p class="truncate text-[10px] font-semibold text-gray-400">{{ $sectionLabelFor($row->lesson) }} · Period {{ $row->lesson->period->position }} · {{ $row->overdue_minutes }} min overdue</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[9px] font-black {{ $badge[1] }}">{{ $badge[0] }}</span>
                    </div>
                @empty
                    <p class="rounded-xl bg-emerald-50 px-3 py-4 text-center text-xs font-bold text-emerald-700">All started classes have marked attendance ✓</p>
                @endforelse
            </div>
        </div>

        {{-- Conflict flags --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm xl:col-span-1">
            <h3 class="mb-1 flex items-center gap-2 text-sm font-black text-gray-900">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-amber-50 text-amber-600">⚠</span>
                Attendance Conflict Flags
            </h3>
            <p class="mb-3 text-[10px] font-semibold text-gray-400">Students marked present and absent on the same day</p>
            <div class="max-h-96 space-y-2 overflow-x-auto overflow-y-auto pr-1">
                @forelse($conflicts as $conflict)
                    <div class="rounded-xl bg-gray-50 px-3 py-2.5">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-gray-200 text-[10px] font-black text-gray-600">{{ $initials($conflict->student->full_name ?? 'S') }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-extrabold text-gray-900">{{ $conflict->student->full_name ?? 'Student' }}</p>
                                <p class="truncate text-[10px] font-semibold text-gray-400">{{ $conflict->section_label }}</p>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-1">
                            @foreach($conflict->cells as $cell)
                                @php
                                    $dot = match ($cell->status) {
                                        'present', 'late' => 'bg-emerald-500',
                                        'absent' => 'bg-red-500 ring-2 ring-red-200',
                                        'excused' => 'bg-blue-300',
                                        default => 'bg-gray-200',
                                    };
                                @endphp
                                <span class="h-3 w-3 rounded-full {{ $dot }}" title="Period {{ $cell->period->position }}: {{ $cell->status ?? 'no class' }}"></span>
                            @endforeach
                            <span class="ml-1 text-[9px] font-black uppercase tracking-wide text-amber-600">Present → Absent flip</span>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl bg-emerald-50 px-3 py-4 text-center text-xs font-bold text-emerald-700">No conflicting attendance detected today ✓</p>
                @endforelse
            </div>
        </div>

        {{-- Consecutive absence escalation --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm xl:col-span-1">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-black text-gray-900">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-red-50 text-red-600">🚩</span>
                Consecutive Absence Escalation
            </h3>
            <div class="max-h-96 space-y-3 overflow-y-auto pr-1">
                @forelse(['yellow' => '2 Days Absent', 'orange' => '3 Days Absent', 'red' => '5+ Days — RED FLAG'] as $severity => $sectionLabel)
                    @php $rows = $streaksBySeverity->get($severity, collect())->filter(fn ($s) => $severity === 'red' ? $s->days >= 5 : ($severity === 'orange' ? $s->days === 3 || $s->days === 4 : $s->days === 2)); @endphp
                    @if($rows->isNotEmpty())
                        <div>
                            <p class="mb-1.5 flex items-center gap-2 text-[10px] font-black uppercase tracking-wide {{ $severityStyle[$severity]['chip'] }} inline-flex rounded-full px-2 py-1">
                                {{ $sectionLabel }} <span class="opacity-70">{{ $rows->count() }} student{{ $rows->count() === 1 ? '' : 's' }}</span>
                            </p>
                            <div class="space-y-1.5">
                                @foreach($rows as $streak)
                                    @php $phone = $phoneFor($streak->student); @endphp
                                    <div class="flex items-center gap-2.5 rounded-xl border-l-4 {{ $severityStyle[$severity]['border'] }} bg-gray-50 px-3 py-2">
                                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-gray-200 text-[10px] font-black text-gray-600">{{ $initials($streak->student->full_name ?? 'S') }}</span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs font-extrabold text-gray-900">{{ $streak->student->full_name ?? 'Student' }}</p>
                                            <p class="truncate text-[10px] font-semibold text-gray-400">{{ $streak->section_label }} · Last present {{ $streak->last_present ? \Carbon\Carbon::parse($streak->last_present)->format('j M') : '—' }}</p>
                                        </div>
                                        <span class="shrink-0 text-[10px] font-black text-gray-600">{{ $streak->days }}d</span>
                                        @if($phone)
                                            <a href="sms:{{ $phone }}" class="shrink-0 rounded-lg border border-gray-200 bg-white px-2 py-1 text-[9px] font-black text-gray-600 hover:bg-gray-50">Notify</a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @empty
                @endforelse
                @if($streaks->isEmpty())
                    <p class="rounded-xl bg-emerald-50 px-3 py-4 text-center text-xs font-bold text-emerald-700">No students with 2+ consecutive absent days ✓</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Row 3: trend / heatmap / top absentee classes --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">

        {{-- Weekly trend --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-black text-gray-900">Attendance Trend</h3>
            @php
                $points = collect($trend['labels'])->count();
                $w = 320; $h = 140; $pad = 8;
                $stepX = $points > 1 ? ($w - $pad * 2) / ($points - 1) : 0;
                $toXY = fn ($i, $v) => [$pad + $i * $stepX, $pad + (1 - $v / 100) * ($h - $pad * 2)];
                $lineFor = function ($series) use ($toXY) {
                    $pts = [];
                    foreach ($series as $i => $v) {
                        if ($v === null) continue;
                        $pts[] = $toXY($i, $v);
                    }
                    return $pts;
                };
                $attendancePts = $lineFor($trend['attendance']);
                $compliancePts = $lineFor($trend['compliance']);
                $toPath = fn ($pts) => collect($pts)->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L').round($p[0], 1).','.round($p[1], 1))->implode(' ');
            @endphp
            @if(count($attendancePts) >= 2)
                <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full" role="img" aria-label="Attendance and teacher compliance trend">
                    @foreach([25, 50, 75] as $gridPct)
                        <line x1="{{ $pad }}" y1="{{ $pad + (1 - $gridPct / 100) * ($h - $pad * 2) }}" x2="{{ $w - $pad }}" y2="{{ $pad + (1 - $gridPct / 100) * ($h - $pad * 2) }}" stroke="#f1f5f9" stroke-width="1"/>
                    @endforeach
                    <path d="{{ $toPath($attendancePts) }}" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    @if(count($compliancePts) >= 2)
                        <path d="{{ $toPath($compliancePts) }}" fill="none" stroke="#e2a024" stroke-width="2" stroke-dasharray="4 3" stroke-linecap="round" stroke-linejoin="round"/>
                    @endif
                    @foreach($attendancePts as $p)
                        <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="2.5" fill="#059669"><title>{{ round($p[1]) }}</title></circle>
                    @endforeach
                </svg>
                <div class="mt-2 flex items-center gap-4 text-[10px] font-bold text-gray-500">
                    <span class="flex items-center gap-1.5"><span class="h-0.5 w-3 rounded-full bg-emerald-600"></span> Attendance Rate</span>
                    <span class="flex items-center gap-1.5"><span class="h-0.5 w-3 rounded-full bg-[#e2a024]"></span> Teacher Compliance</span>
                </div>
                <div class="mt-1 flex justify-between text-[9px] font-semibold text-gray-400">
                    @foreach($trend['labels'] as $label)<span>{{ $label }}</span>@endforeach
                </div>
            @else
                <p class="py-8 text-center text-xs font-bold text-gray-400">Not enough attendance history yet to chart a trend.</p>
            @endif
        </div>

        {{-- Class-wise heatmap --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-black text-gray-900">Class-wise Attendance Heatmap</h3>
            @if(count($heatmap['sections']) > 0)
                <div class="max-h-72 overflow-auto">
                    <table class="w-full border-collapse text-[10px]">
                        <thead>
                            <tr>
                                <th class="sticky left-0 top-0 z-10 bg-white px-1.5 py-1 text-left font-black text-gray-500">Class</th>
                                @foreach($heatmap['dates'] as $date)
                                    <th class="sticky top-0 z-0 bg-white px-1 py-1 text-center font-black text-gray-400">{{ \Carbon\Carbon::parse($date)->format('D') }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($heatmap['sections'] as $section)
                                <tr>
                                    <td class="sticky left-0 z-10 whitespace-nowrap bg-white px-1.5 py-1 font-bold text-gray-700">{{ $section['label'] }}</td>
                                    @foreach($section['cells'] as $cell)
                                        @php
                                            $color = $cell['percent'] === null ? 'bg-gray-100' : ($cell['percent'] >= 90 ? 'bg-emerald-500' : ($cell['percent'] >= 70 ? 'bg-amber-400' : 'bg-red-500'));
                                        @endphp
                                        <td class="p-0.5">
                                            <div class="h-5 w-5 rounded {{ $color }}" title="{{ $cell['percent'] !== null ? $cell['percent'].'%' : 'No data' }}"></div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-[9px] font-bold text-gray-500">
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded bg-emerald-500"></span> 90-100% Excellent</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded bg-amber-400"></span> 70-89% Needs attention</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded bg-red-500"></span> Below 70% At risk</span>
                </div>
            @else
                <p class="py-8 text-center text-xs font-bold text-gray-400">No attendance data recorded yet.</p>
            @endif
        </div>

        {{-- Top absentee classes --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-black text-gray-900">Top Absentee Classes (Today)</h3>
            @php $maxCount = $topAbsenteeClasses->max('count') ?: 1; @endphp
            <div class="space-y-2">
                @forelse($topAbsenteeClasses as $class)
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-20 shrink-0 truncate font-bold text-gray-600">{{ $class->label }}</span>
                        <div class="h-3 flex-1 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-linear-to-r from-amber-400 to-red-500" style="width: {{ round($class->count / $maxCount * 100) }}%"></div>
                        </div>
                        <span class="w-6 shrink-0 text-right font-black text-gray-800">{{ $class->count }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs font-bold text-gray-400">No absences recorded today ✓</p>
                @endforelse
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
(function () {
    let last = Date.now();
    setInterval(function () {
        if (document.visibilityState !== 'visible') return;
        if (Date.now() - last < 55000) return;
        last = Date.now();
        window.location.reload();
    }, 15000);
})();
</script>
@endpush
