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
                <div><dt class="text-gray-400">Guardian / Parent Contact</dt><dd class="mt-1 font-bold text-gray-800">{{ $student->guardian_contact ?: ($student->parent_contact ?: '—') }}</dd></div>
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
