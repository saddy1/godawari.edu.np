{{-- resources/views/backend/founder-dashboard/teacher-attendance.blade.php --}}
@extends('layouts.admin')

@section('title', 'Teacher Attendance Explorer')

@section('content')
@php
    $initials = fn (string $name) => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $baseParams = ['date' => $date->toDateString()];
@endphp
<div class="max-w-5xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-black text-gray-900">Teacher Attendance Explorer</h2>
            <p class="text-sm font-semibold text-gray-500">
                {{ $date->format('l, d M Y') }}
                @if($dateBsLabel)
                    <span class="text-gray-300">·</span> {{ $dateBsLabel }}
                @endif
            </p>
        </div>
        <form method="GET" onchange="this.submit()" class="flex items-end gap-2">
            <x-nepali-date-input name="date" :value="$date" class="w-36 rounded-xl border border-gray-300 px-3 py-2 text-sm font-semibold" />
            <button class="mb-0.5 shrink-0 rounded-xl bg-[#1a5632] px-4 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415]">Go</button>
        </form>
    </div>

    @if(! $selectedSummary)
        {{-- Level 1: teachers who missed marking on this date --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-3">
                <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">{{ $teacherSummaries->count() }} teacher{{ $teacherSummaries->count() === 1 ? '' : 's' }} with unmarked classes</p>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($teacherSummaries as $summary)
                    <a href="{{ route('admin.founder.teacher-attendance', array_merge($baseParams, ['teacher_id' => $summary->teacher->id])) }}"
                       class="flex items-center gap-3 p-4 transition hover:bg-gray-50 sm:p-5">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-red-100 text-xs font-black text-red-700">{{ $initials($summary->teacher->name) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-extrabold text-gray-900">{{ $summary->teacher->name }}</p>
                            <p class="text-xs font-semibold text-gray-400">{{ $summary->rows->pluck('lesson.section')->filter()->unique('id')->map(fn ($s) => trim(($s->department->name ?? '').' - '.$s->name, ' -'))->implode(', ') }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-red-50 px-3 py-1.5 text-xs font-black text-red-700">{{ $summary->missed_count }} class{{ $summary->missed_count === 1 ? '' : 'es' }} unmarked</span>
                        <span class="shrink-0 text-gray-300">→</span>
                    </a>
                @empty
                    <p class="p-10 text-center text-sm text-gray-400">Every teacher marked attendance for all their scheduled classes on this date ✓</p>
                @endforelse
            </div>
        </div>

        @if($unassignedMisses->isNotEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50/40 shadow-sm overflow-hidden">
                <div class="border-b border-amber-200 px-5 py-3">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-amber-700">{{ $unassignedMisses->count() }} unmarked class{{ $unassignedMisses->count() === 1 ? '' : 'es' }} with no teacher assigned</p>
                </div>
                <div class="divide-y divide-amber-100">
                    @foreach($unassignedMisses as $row)
                        @php $section = $row->lesson->section; @endphp
                        <div class="flex items-center justify-between gap-3 p-4">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ $section ? trim(($section->department->name ?? '').' - '.$section->name, ' -') : '—' }}</p>
                                <p class="text-xs font-semibold text-gray-400">Period {{ $row->lesson->period->position }} · {{ $row->lesson->groups->flatMap(fn ($g) => $g->offering ? [$g->offering->subject] : [])->filter()->unique('id')->pluck('name')->implode(', ') ?: '—' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $row->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700' }}">{{ $row->status === 'pending' ? 'Pending' : 'Not Taken' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        {{-- Level 2: one teacher's missed classes + 7-day pattern --}}
        <div class="flex flex-wrap items-center gap-1.5 text-sm font-bold text-gray-500">
            <a href="{{ route('admin.founder.teacher-attendance', $baseParams) }}" class="hover:underline">All teachers</a>
            <span>/</span>
            <span class="text-[#1a5632]">{{ $selectedSummary->teacher->name }}</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-red-100 text-sm font-black text-red-700">{{ $initials($selectedSummary->teacher->name) }}</span>
                <div>
                    <h3 class="font-extrabold text-gray-900">{{ $selectedSummary->teacher->name }}</h3>
                    <p class="text-xs font-semibold text-gray-400">{{ $selectedSummary->missed_count }} class{{ $selectedSummary->missed_count === 1 ? '' : 'es' }} not marked on {{ $date->format('d M Y') }}</p>
                </div>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($selectedSummary->rows as $row)
                    @php $section = $row->lesson->section; $subjects = $row->lesson->groups->flatMap(fn ($g) => $g->offering ? [$g->offering->subject] : [])->filter()->unique('id')->pluck('name')->implode(', '); @endphp
                    <div class="flex items-center justify-between gap-3 p-4 sm:p-5">
                        <div>
                            <p class="font-bold text-gray-800">Period {{ $row->lesson->period->position }} · {{ $row->lesson->period->name }}</p>
                            <p class="text-xs font-semibold text-gray-400">{{ $section ? trim(($section->department->name ?? '').' - '.$section->name, ' -') : '—' }} · {{ $subjects ?: '—' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $row->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700' }}">{{ $row->status === 'pending' ? 'Pending' : 'Not Taken' }}</span>
                            @if($row->overdue_minutes)<p class="mt-1 text-[10px] font-bold text-gray-400">{{ $row->overdue_minutes }} min overdue</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($weeklyPattern)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-3 text-xs font-extrabold uppercase tracking-widest text-gray-400">Last 7 days — marking pattern</p>
                <div class="grid grid-cols-7 gap-2">
                    @foreach($weeklyPattern as $day)
                        <div class="rounded-xl border p-2 text-center {{ $day->missed > 0 ? 'border-red-200 bg-red-50' : ($day->scheduled > 0 ? 'border-emerald-200 bg-emerald-50' : 'border-gray-100 bg-gray-50') }}">
                            <p class="text-[9px] font-black uppercase text-gray-500">{{ $day->date->format('D') }}</p>
                            <p class="mt-1 text-lg font-black {{ $day->missed > 0 ? 'text-red-600' : ($day->scheduled > 0 ? 'text-emerald-600' : 'text-gray-300') }}">{{ $day->scheduled ? $day->missed : '—' }}</p>
                            <p class="text-[8px] font-bold text-gray-400">{{ $day->scheduled ? 'of '.$day->scheduled.' missed' : 'no classes' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
