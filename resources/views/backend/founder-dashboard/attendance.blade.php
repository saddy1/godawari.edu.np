{{-- resources/views/backend/founder-dashboard/attendance.blade.php --}}
@extends('layouts.admin')

@section('title', 'Class Attendance')

@php
    $initials = fn (string $name) => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
    $sectionLabelOf = fn ($section) => trim(($section->department->name ?? '').' - '.$section->name, ' -');
@endphp

@section('content')

    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <div>
            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-gray-400">Section</label>
            <select name="section" onchange="this.form.submit()" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold text-gray-700">
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" {{ (int) $selectedSectionId === $section->id ? 'selected' : '' }}>{{ $sectionLabelOf($section) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-gray-400">Date</label>
            <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold text-gray-700">
        </div>
        <button type="submit" class="rounded-xl bg-[#1a5632] px-4 py-2 text-sm font-extrabold text-white">Go</button>
    </form>

    @if($noLessonsToday)
        <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center shadow-sm">
            <p class="text-sm font-bold text-gray-500">No classes were scheduled for this section on {{ $date->format('l, j M Y') }}.</p>
        </div>
    @else
        <div class="mb-4 grid grid-cols-3 gap-3">
            <div class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Total</p>
                <p class="mt-1 text-2xl font-black text-gray-900">{{ $summary['total'] }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-700">Present</p>
                <p class="mt-1 text-2xl font-black text-emerald-800">{{ $summary['present'] }}</p>
            </div>
            <div class="rounded-2xl border border-red-100 bg-red-50 p-4 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-red-700">Absent</p>
                <p class="mt-1 text-2xl font-black text-red-700">{{ $summary['absent'] }}</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-gray-100 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 text-left text-[10px] font-black uppercase tracking-wide text-gray-400">
                        <th class="px-4 py-3">Student</th>
                        @foreach($periods as $period)
                            <th class="px-2 py-3 text-center">P{{ $period->position }}</th>
                        @endforeach
                        <th class="px-4 py-3 text-right">Day Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-gray-200 text-[10px] font-black text-gray-600">{{ $initials($row->student->full_name ?? 'S') }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-extrabold text-gray-900">{{ $row->student->full_name ?? 'Student' }}</p>
                                        <p class="truncate text-[10px] font-semibold text-gray-400">{{ $row->student->roll_number ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            @foreach($row->cells as $cell)
                                @php
                                    $dot = match ($cell->status) {
                                        'present', 'late' => 'bg-emerald-500',
                                        'absent' => 'bg-red-500',
                                        'excused' => 'bg-blue-300',
                                        default => 'bg-gray-200',
                                    };
                                @endphp
                                <td class="px-2 py-2.5 text-center">
                                    <span class="inline-block h-3 w-3 rounded-full {{ $dot }}" title="{{ $cell->status ?? 'no record' }}"></span>
                                </td>
                            @endforeach
                            <td class="px-4 py-2.5 text-right">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $row->day_status === 'present' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    {{ ucfirst($row->day_status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $periods->count() + 2 }}" class="px-4 py-8 text-center text-sm font-bold text-gray-400">No attendance has been submitted for this section on this date yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

@endsection
