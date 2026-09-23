{{-- resources/views/backend/founder-dashboard/students/show.blade.php --}}
@extends('layouts.admin')

@section('title', 'Student Detail')

@section('content')
<div class="max-w-5xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-4">
            <img src="{{ $student->photo_url }}" alt="{{ $student->full_name }}" class="h-24 w-20 rounded-xl border bg-gray-100 object-cover">
            <div>
                <h2 class="text-2xl font-black text-gray-900">{{ $student->full_name }}</h2>
                <p class="text-sm font-semibold text-gray-500">{{ ucfirst($student->member_type) }} · {{ $student->roll_number }}</p>
                <p class="mt-1 inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-gray-500">View only</p>
            </div>
        </div>
        <a href="{{ url()->previous() }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-extrabold text-gray-600 hover:bg-gray-50">← Back</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Attendance history --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-black uppercase tracking-wide text-gray-900">Attendance History</h3>
            <div class="flex gap-1.5">
                @foreach(['week' => 'Last 7 days', 'month' => 'Last 30 days'] as $value => $label)
                    <a href="{{ route('admin.founder.students.show', [$student, 'range' => $value]) }}"
                       class="rounded-full border px-3 py-1.5 text-[11px] font-extrabold {{ $range === $value ? 'border-[#1a5632] bg-[#1a5632] text-white' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
            <div class="rounded-xl bg-gray-50 p-3"><p class="text-2xl font-black text-gray-900">{{ $recordedCount }}</p><p class="text-[10px] font-extrabold uppercase text-gray-400">Days Recorded</p></div>
            <div class="rounded-xl bg-emerald-50 p-3"><p class="text-2xl font-black text-emerald-700">{{ $presentCount }}</p><p class="text-[10px] font-extrabold uppercase text-emerald-600">Present</p></div>
            <div class="rounded-xl bg-red-50 p-3"><p class="text-2xl font-black text-red-700">{{ $absentCount }}</p><p class="text-[10px] font-extrabold uppercase text-red-600">Absent</p></div>
        </div>

        @if($streakDays >= 2)
            <div class="mt-3 rounded-xl {{ $streakDays >= 5 ? 'bg-red-50 text-red-700' : ($streakDays >= 3 ? 'bg-amber-50 text-amber-700' : 'bg-yellow-50 text-yellow-700') }} px-4 py-2.5 text-sm font-extrabold">
                ⚠ Currently on a {{ $streakDays }}-day absence streak
            </div>
        @endif

        <div class="mt-4 flex flex-wrap gap-1.5">
            @foreach($attendanceRows as $row)
                <div title="{{ $row->date->format('d M Y') }} · {{ $row->status ? ucfirst($row->status) : 'No record' }}"
                     class="flex h-9 w-9 items-center justify-center rounded-lg text-[10px] font-black {{ match($row->status) { 'present' => 'bg-emerald-500 text-white', 'absent' => 'bg-red-500 text-white', default => 'bg-gray-100 text-gray-300' } }}">
                    {{ $row->date->format('j') }}
                </div>
            @endforeach
        </div>
    </div>

    {{-- Absence remarks --}}
    @if($recentRemarks->isNotEmpty())
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-sm font-black uppercase tracking-wide text-gray-900">Absence Remarks</h3>
        <div class="space-y-2">
            @foreach($recentRemarks as $remark)
                <div class="flex items-start justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2 text-sm">
                    <span class="font-semibold text-gray-700">{{ $remark->remarks }}</span>
                    <span class="shrink-0 text-xs font-bold text-gray-400">{{ $remark->session?->attendance_date?->format('d M Y') }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-sm font-black uppercase tracking-wide text-gray-900">Basic Information</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-400">Organization</dt><dd class="mt-1 font-bold text-gray-800">{{ ucfirst($student->organization ?: '—') }}</dd></div>
                <div><dt class="text-gray-400">Department / Class</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->stream ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Section</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->section ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Program</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->program ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Batch</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->batch ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Valid Till</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->valid_till?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-gray-400">Date of Birth</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->dob?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-gray-400">Citizenship No.</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->citizenship_no ?: '—' }}</dd></div>
                @if($student->guardian_name)
                <div><dt class="text-gray-400">Guardian Name</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->guardian_name }}</dd></div>
                @endif
                @if($student->registration_no)
                <div><dt class="text-gray-400">Registration No.</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->registration_no }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-sm font-black uppercase tracking-wide text-gray-900">Contact and Address</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-400">Mobile</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->mobile ?: '—' }}</dd></div>
                <div>
                    <dt class="text-gray-400">Guardian / Parent Contact</dt>
                    @if($student->guardian_contact ?: $student->parent_contact)
                        <dd class="mt-1 font-bold text-gray-800">{{ $student->guardian_contact ?: $student->parent_contact }}</dd>
                    @else
                        <dd class="mt-1">
                            <form method="POST" action="{{ route('admin.founder.students.contact', $student) }}" class="flex gap-2">
                                @csrf @method('PATCH')
                                <input type="text" name="guardian_contact" placeholder="Add contact no." required
                                       class="w-full rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold">
                                <button class="shrink-0 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-extrabold text-white hover:bg-amber-700">Save</button>
                            </form>
                        </dd>
                    @endif
                </div>
                <div><dt class="text-gray-400">Email</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->email ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Province</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->zone ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">District</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->district ?: '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-400">Municipality</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->municipality ?: '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-400">Address</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->address_en ?: trim(collect([$student->permanent_tole, $student->permanent_municipality, $student->permanent_district])->filter()->implode(', ')) ?: '—' }}</dd></div>
            </dl>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-sm font-black uppercase tracking-wide text-gray-900">Employment</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-400">Designation</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->designation ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Employment Type</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->employment_type ?: '—' }}</dd></div>
            </dl>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-sm font-black uppercase tracking-wide text-gray-900">Cards and Services</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-400">Library Card</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->has_library_card ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="text-gray-400">Library ID</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->library_id ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Bus Pass</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->has_bus_pass ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="text-gray-400">Bus Route / Stop</dt><dd class="mt-1 font-bold text-gray-800">{{ trim(($student->bus_route ?: '—').' / '.($student->bus_stop ?: '—')) }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
