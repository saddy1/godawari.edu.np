@extends('examinations.layouts.app')
@section('title', 'Exam Attendance Report — '.$examination->name)
@section('content')
<div class="space-y-4">
    <section class="flex flex-col gap-3 rounded-2xl bg-gradient-to-r from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-300">{{ $examination->organization->name }}</p>
            <h1 class="mt-1 text-2xl font-black">Exam Attendance Report — {{ $examination->name }}</h1>
            <p class="mt-1 text-xs font-semibold text-white/65">Every absence recorded across the exam, with the reason given.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.examinations.attendance.index', $examination) }}" class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-black text-white hover:bg-white/20">Take attendance</a>
            <a href="{{ route('admin.examinations.show', $examination) }}" class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-black text-white hover:bg-white/20">← Back to exam</a>
        </div>
    </section>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4"><p class="text-2xl font-black text-emerald-700">{{ $stats['present'] }}</p><p class="text-[10px] font-black uppercase tracking-wider text-emerald-600">Present{{ $dateFilter ? ' on this date' : '' }}</p></div>
        <div class="rounded-2xl border border-red-100 bg-red-50 p-4"><p class="text-2xl font-black text-red-700">{{ $stats['absent'] }}</p><p class="text-[10px] font-black uppercase tracking-wider text-red-600">Absent{{ $dateFilter ? ' on this date' : '' }}</p></div>
        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4"><p class="text-2xl font-black text-gray-600">{{ $dates->count() }}</p><p class="text-[10px] font-black uppercase tracking-wider text-gray-500">Dates with attendance taken</p></div>
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="mb-2 text-[10px] font-black uppercase tracking-wider text-gray-500">Filter by date</p>
        <div class="flex flex-wrap gap-1.5">
            <a href="{{ route('admin.examinations.attendance.report', $examination) }}" class="rounded-full border px-3 py-1.5 text-[11px] font-extrabold {{ ! $dateFilter ? 'border-[#1a5632] bg-[#1a5632] text-white' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">All dates</a>
            @foreach($dates as $d)
                <a href="{{ route('admin.examinations.attendance.report', [$examination, 'date' => $d->toDateString()]) }}" class="rounded-full border px-3 py-1.5 text-[11px] font-extrabold {{ $dateFilter?->toDateString() === $d->toDateString() ? 'border-[#1a5632] bg-[#1a5632] text-white' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">{{ $d->format('d M Y') }}</a>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-3"><p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">{{ $absentees->count() }} absent record{{ $absentees->count() === 1 ? '' : 's' }}</p></div>
        <div class="divide-y divide-gray-50">
            @forelse($absentees as $row)
                <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-extrabold text-gray-900">{{ $row->student->full_name ?? 'Unknown student' }}</p>
                        <p class="text-xs font-semibold text-gray-400">{{ $row->student->roll_number ?? '—' }} · {{ $row->department_name }} · {{ $row->subject_name }} · {{ $row->date->format('d M Y') }}</p>
                        @if($row->reason)<p class="mt-1 text-xs font-semibold text-amber-700">Reason: {{ $row->reason }}</p>@endif
                    </div>
                    @if($row->student)<a href="{{ route('admin.hr.members.show', $row->student) }}" class="shrink-0 text-xs font-black text-[#1a5632] hover:underline">View profile →</a>@endif
                </div>
            @empty
                <p class="p-10 text-center text-sm text-gray-400">No absences recorded{{ $dateFilter ? ' for this date' : '' }}.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
