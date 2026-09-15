{{-- resources/views/backend/founder-dashboard/attendance.blade.php --}}
@extends('layouts.admin')

@section('title', 'Class Attendance')

@php
    $initials = fn (string $name) => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
    $input = 'w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold outline-none transition-colors duration-300 hover:border-gray-300 focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15 disabled:border-gray-100 disabled:bg-gray-50 disabled:text-gray-400';
    $label = 'mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500';
    $today = \Carbon\Carbon::today();
    $presets = [
        'Today' => [$today->toDateString(), $today->toDateString()],
        'Yesterday' => [$today->copy()->subDay()->toDateString(), $today->copy()->subDay()->toDateString()],
        'Last 7 Days' => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
        'Last 30 Days' => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
        'This Month' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
    ];
@endphp

@section('content')

<div x-data="founderAttendanceFilters(@js($organizations), @js([
    'organization_id' => (string) request('organization_id', ''),
    'department_id' => (string) request('department_id', ''),
    'section_id' => (string) request('section_id', ''),
]))">

    <section class="mb-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <form method="GET" class="space-y-3">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="{{ $label }}">Organization</label>
                    <select name="organization_id" x-model="organizationId" @change="departmentId=''; sectionId=''" class="{{ $input }}">
                        <option value="">All organizations</option>
                        <template x-for="item in organizations" :key="item.id"><option :value="String(item.id)" x-text="item.name"></option></template>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Faculty / class</label>
                    <select name="department_id" x-model="departmentId" @change="sectionId=''" :disabled="!organizationId" class="{{ $input }}">
                        <option value="">All faculties / classes</option>
                        <template x-for="item in departments" :key="item.id"><option :value="String(item.id)" x-text="item.name"></option></template>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Section</label>
                    <select name="section_id" x-model="sectionId" :disabled="!departmentId" class="{{ $input }}">
                        <option value="">All sections</option>
                        <template x-for="item in sections" :key="item.id"><option :value="String(item.id)" x-text="item.name + (item.group_name ? ' · '+item.group_name : '')"></option></template>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Subject</label>
                    <select name="subject_id" class="{{ $input }}">
                        <option value="">All subjects</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected($subjectId === $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="{{ $label }}">Teacher</label>
                    <select name="teacher_id" class="{{ $input }}">
                        <option value="">All teachers</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected($teacherId === $teacher->id)>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Student</label>
                    <input type="text" name="q" value="{{ $studentQuery }}" placeholder="Search by name or roll no." class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">From (Nepali date)</label>
                    <x-nepali-date-input name="from" :value="$from" />
                </div>
                <div>
                    <label class="{{ $label }}">To (Nepali date)</label>
                    <x-nepali-date-input name="to" :value="$to" />
                </div>
            </div>

            <div class="flex justify-end border-t border-gray-100 pt-3">
                <button type="submit" class="rounded-xl bg-[#1a5632] px-5 py-2.5 text-sm font-extrabold text-white">Apply Filters</button>
            </div>
        </form>

        <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3">
            <span class="text-[10px] font-black uppercase tracking-wide text-gray-400">Quick range:</span>
            @foreach($presets as $presetLabel => [$presetFrom, $presetTo])
                <a href="{{ route('admin.founder.attendance', array_merge(request()->except(['from', 'to']), ['from' => $presetFrom, 'to' => $presetTo])) }}"
                   class="rounded-lg border border-gray-200 px-2.5 py-1 text-[10px] font-black text-gray-600 hover:bg-gray-50">{{ $presetLabel }}</a>
            @endforeach
        </div>
    </section>

    @if($mode === 'daily')
        @if($noLessonsToday)
            <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center shadow-sm">
                <p class="text-sm font-bold text-gray-500">No classes match these filters on {{ $from->format('l, j M Y') }}.</p>
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
                            <tr><td colspan="{{ $periods->count() + 2 }}" class="px-4 py-8 text-center text-sm font-bold text-gray-400">No attendance has been submitted for these filters yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    @else
        {{-- Range summary mode: multiple days and/or multiple sections --}}
        <div class="mb-4 grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Students with Records</p>
                <p class="mt-1 text-2xl font-black text-gray-900">{{ $summary['students'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Average Attendance Rate</p>
                <p class="mt-1 text-2xl font-black {{ $summary['avg_rate'] >= 90 ? 'text-emerald-700' : ($summary['avg_rate'] >= 70 ? 'text-amber-600' : 'text-red-700') }}">{{ $summary['avg_rate'] }}%</p>
            </div>
        </div>

        <p class="mb-2 text-[11px] font-semibold text-gray-400">{{ $from->format('j M Y') }} — {{ $to->format('j M Y') }} · lowest attendance first</p>

        <div class="overflow-x-auto rounded-2xl border border-gray-100 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 text-left text-[10px] font-black uppercase tracking-wide text-gray-400">
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Section</th>
                        <th class="px-2 py-3 text-center">Scheduled</th>
                        <th class="px-2 py-3 text-center">Present</th>
                        <th class="px-2 py-3 text-center">Absent</th>
                        <th class="px-4 py-3 text-right">Rate</th>
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
                            <td class="px-4 py-2.5 text-xs font-bold text-gray-600">{{ $row->section_label }}</td>
                            <td class="px-2 py-2.5 text-center text-xs font-bold text-gray-600">{{ $row->scheduled }}</td>
                            <td class="px-2 py-2.5 text-center text-xs font-bold text-emerald-700">{{ $row->present }}</td>
                            <td class="px-2 py-2.5 text-center text-xs font-bold text-red-700">{{ $row->absent }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $row->rate >= 90 ? 'bg-emerald-100 text-emerald-700' : ($row->rate >= 70 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ $row->rate }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm font-bold text-gray-400">No attendance records match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
function founderAttendanceFilters(organizations, initial) {
    return {
        organizations,
        organizationId: initial.organization_id,
        departmentId: initial.department_id,
        sectionId: initial.section_id,
        get organization() { return this.organizations.find(item => String(item.id) === this.organizationId) },
        get departments() { return this.organization?.departments || [] },
        get department() { return this.departments.find(item => String(item.id) === this.departmentId) },
        get sections() { return this.department?.sections || [] },
    }
}
</script>
@endpush
