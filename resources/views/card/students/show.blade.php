@extends('card.layouts.app')
@section('title', 'Member Details')
@section('heading', 'Member Details')

@section('content')
<div class="max-w-5xl space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <img src="{{ $student->photo_url }}" alt="{{ $student->full_name }}" class="w-20 h-24 rounded-xl object-cover border bg-gray-100">
            <div>
                <h2 class="text-2xl font-bold text-primary">{{ $student->full_name }}</h2>
                <p class="text-sm text-gray-500">{{ ucfirst($student->member_type) }} · {{ $student->roll_number }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.hr.members.edit', $student) }}" class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:bg-primary-light transition">Edit</a>
            <a href="{{ route('students.index') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 text-sm hover:bg-gray-200 transition">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-primary mb-4 text-sm uppercase tracking-wide">Basic Information</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-400">Organization</dt><dd class="text-gray-800 font-medium mt-1">{{ ucfirst($student->organization) }}</dd></div>
                <div><dt class="text-gray-400">Department / Class</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->stream ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Section</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->section ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Program</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->program ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Batch</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->batch ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Valid Till</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->valid_till?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-gray-400">Date of Birth</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->dob?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-gray-400">Citizenship No.</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->citizenship_no ?: '—' }}</dd></div>
                @if($student->guardian_name)
                <div><dt class="text-gray-400">Guardian Name</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->guardian_name }}</dd></div>
                @endif
                @if($student->registration_no)
                <div><dt class="text-gray-400">Registration No.</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->registration_no }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-primary mb-4 text-sm uppercase tracking-wide">Contact and Address</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-400">Mobile</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->mobile ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Email</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->email ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Province</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->zone ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">District</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->district ?: '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-gray-400">Municipality</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->municipality ?: '—' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-primary mb-4 text-sm uppercase tracking-wide">Employment</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-400">Designation</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->designation ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Employment Type</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->employment_type ?: '—' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-primary mb-4 text-sm uppercase tracking-wide">Cards and Services</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-400">Library Card</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->has_library_card ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="text-gray-400">Library ID</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->library_id ?: '—' }}</dd></div>
                <div><dt class="text-gray-400">Bus Pass</dt><dd class="text-gray-800 font-medium mt-1">{{ $student->has_bus_pass ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="text-gray-400">Bus Route / Stop</dt><dd class="text-gray-800 font-medium mt-1">{{ trim(($student->bus_route ?: '—') . ' / ' . ($student->bus_stop ?: '—')) }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
