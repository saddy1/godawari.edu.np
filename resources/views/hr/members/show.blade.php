@extends('hr.layouts.app')

@section('title', 'Member Profile')

@section('content')
@php
    $typeLabels = ['student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff'];
    $typeStyles = [
        'student' => 'bg-blue-50 text-blue-700 border-blue-100',
        'teacher' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'staff' => 'bg-amber-50 text-amber-700 border-amber-100',
    ];

    $value = fn ($text) => filled($text) ? $text : '—';
    $dateValue = fn ($date) => $date ? $date->format('d M Y') : '—';
@endphp

<div class="space-y-6">
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 sm:p-6 text-white shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="h-20 w-20 rounded-2xl object-cover ring-2 ring-white/20">
                <div class="min-w-0">
                    <p class="text-sm font-bold uppercase tracking-widest text-white/50">Member Profile</p>
                    <h1 class="mt-1 truncate text-3xl font-extrabold">{{ $member->full_name }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm font-semibold text-white/75">
                        <span>{{ $member->roll_number }}</span>
                        <span class="text-white/30">/</span>
                        <span>{{ $typeLabels[$member->member_type] ?? ucfirst($member->member_type) }}</span>
                        @if($member->stream)
                            <span class="text-white/30">/</span>
                            <span>{{ $member->stream }}{{ $member->section ? ' - ' . $member->section : '' }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.hr.members.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-extrabold text-white hover:bg-white/20">
                    Back
                </a>
                @if(auth()->user()?->canAccess('hr.members.edit'))
                    <a href="{{ route('admin.hr.members.edit', $member) }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-extrabold text-[#1a5632] hover:bg-gray-100">
                        Edit
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[280px_1fr]">
        <aside class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="mx-auto h-40 w-32 rounded-xl object-cover ring-1 ring-gray-200">
                <div class="mt-4 text-center">
                    <p class="text-lg font-extrabold text-gray-950">{{ $member->full_name }}</p>
                    <span class="mt-2 inline-flex rounded-full border px-3 py-1 text-xs font-extrabold {{ $typeStyles[$member->member_type] ?? 'bg-gray-50 text-gray-600 border-gray-100' }}">
                        {{ $typeLabels[$member->member_type] ?? ucfirst($member->member_type) }}
                    </span>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Linked Modules</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">ID Card</span>
                    @if($member->user)
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Login</span>
                        @if($member->user->device_id)
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">Hajiri</span>
                        @endif
                        @if($member->user->hasAnyRole(['student', 'teacher']))
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">Learning</span>
                        @endif
                    @else
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-500">No Login</span>
                    @endif
                </div>
            </div>
        </aside>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-extrabold uppercase tracking-widest text-gray-400">Basic Information</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-bold text-gray-400">ID / Roll No.</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->roll_number) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Registration No.</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->registration_no) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Gender</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->gender) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Blood Group</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->blood_group) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Date of Birth AD</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $dateValue($member->dob) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Date of Birth BS</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->dob_bs) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Citizenship No.</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->citizenship_no) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Valid Till</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $dateValue($member->valid_till) }}{{ $member->valid_till_bs ? ' / ' . $member->valid_till_bs : '' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-extrabold uppercase tracking-widest text-gray-400">Academic Details</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-bold text-gray-400">Organization</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ ucfirst($value($member->organization)) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Class / Stream</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->stream) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Section</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->section) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Program</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->program) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Batch</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->batch) }}</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-extrabold uppercase tracking-widest text-gray-400">Family Details</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-bold text-gray-400">Father</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->father_name) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Mother</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->mother_name) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Grandfather</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->grandfather_name) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Guardian</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->guardian_name) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Guardian Relation</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->guardian_relation) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Guardian Contact</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->guardian_contact) }}</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-extrabold uppercase tracking-widest text-gray-400">Contact</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-bold text-gray-400">Mobile</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->mobile) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Parent Contact</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->parent_contact) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Email</dt><dd class="mt-1 break-words text-sm font-semibold text-gray-900">{{ $value($member->email) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Emergency Contact</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->emergency_contact_name) }}{{ $member->emergency_contact_phone ? ' / ' . $member->emergency_contact_phone : '' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
                <h2 class="text-sm font-extrabold uppercase tracking-widest text-gray-400">Address</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div><dt class="text-xs font-bold text-gray-400">Permanent Province</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->permanent_province ?: $member->zone) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Permanent District</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->permanent_district ?: $member->district) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Permanent Municipality</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->permanent_municipality ?: $member->municipality) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Permanent Ward / Tole</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value(trim(($member->permanent_ward ?: '') . ' ' . ($member->permanent_tole ?: ''))) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Temporary Province</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->temporary_province) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Temporary District</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->temporary_district) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Temporary Municipality</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->temporary_municipality) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Temporary Ward / Tole</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value(trim(($member->temporary_ward ?: '') . ' ' . ($member->temporary_tole ?: ''))) }}</dd></div>
                    <div class="sm:col-span-2 xl:col-span-4"><dt class="text-xs font-bold text-gray-400">Address EN</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->address_en) }}</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-extrabold uppercase tracking-widest text-gray-400">Cards and Services</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-bold text-gray-400">Library Card</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $member->has_library_card ? 'Yes' : 'No' }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Library ID</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->library_id) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Bus Pass</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $member->has_bus_pass ? 'Yes' : 'No' }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Bus Route / Stop</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value(trim(($member->bus_route ?: '') . ' / ' . ($member->bus_stop ?: ''), ' /')) }}</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-extrabold uppercase tracking-widest text-gray-400">Login Account</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-bold text-gray-400">Login ID</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->user?->student_code) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Login Email</dt><dd class="mt-1 break-words text-sm font-semibold text-gray-900">{{ $value($member->user?->email) }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Roles</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $member->user ? $value($member->user->roles->pluck('label')->filter()->implode(', ') ?: $member->user->roles->pluck('name')->implode(', ')) : '—' }}</dd></div>
                    <div><dt class="text-xs font-bold text-gray-400">Hajiri Device ID</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->user?->device_id) }}</dd></div>
                </dl>
            </section>

            @if(in_array($member->member_type, ['teacher', 'staff'], true))
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
                    <h2 class="text-sm font-extrabold uppercase tracking-widest text-gray-400">Employment & Hajiri</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div><dt class="text-xs font-bold text-gray-400">Designation</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->user?->designation?->label ?: $member->designation) }}</dd></div>
                        <div><dt class="text-xs font-bold text-gray-400">Employment Type</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->user?->employment?->label ?: $member->employment_type) }}</dd></div>
                        <div><dt class="text-xs font-bold text-gray-400">Employee Category</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->employee_category ? ucfirst($member->employee_category) : null) }}</dd></div>
                        <div><dt class="text-xs font-bold text-gray-400">Work Area</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->user?->working_at?->label) }}</dd></div>
                        <div><dt class="text-xs font-bold text-gray-400">Joining Date</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $dateValue($member->joining_date) }}{{ $member->joining_date_bs ? ' / ' . $member->joining_date_bs : '' }}</dd></div>
                        <div><dt class="text-xs font-bold text-gray-400">Permanent Date</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $dateValue($member->permanent_date) }}{{ $member->permanent_date_bs ? ' / ' . $member->permanent_date_bs : '' }}</dd></div>
                        <div><dt class="text-xs font-bold text-gray-400">Hajiri Department</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->user?->hajiriDepartment?->label) }}</dd></div>
                        <div><dt class="text-xs font-bold text-gray-400">Device ID</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ $value($member->user?->device_id) }}</dd></div>
                    </dl>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection
