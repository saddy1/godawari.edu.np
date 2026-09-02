@extends('hr.layouts.app')

@section('title', $member ? 'Edit HR Member' : 'New HR Member')

@section('content')
@php
    $isEdit = (bool) $member;
    $p      = $prefillUser ?? null;  // Existing user being linked to HR

    // Split the user's single name field into parts for prefill
    if ($p) {
        $nameParts    = preg_split('/\s+/', trim($p->name), 3);
        $pfFirst      = $nameParts[0] ?? '';
        $pfMiddle     = count($nameParts) === 3 ? $nameParts[1] : '';
        $pfLast       = count($nameParts) >= 2 ? end($nameParts) : '';
        $pfType       = $p->roles->firstWhere('name', 'teacher') ? 'teacher' : 'staff';
    } else {
        $pfFirst = $pfMiddle = $pfLast = $pfType = '';
    }

    $selectedType = old('member_type', $isEdit ? $member->member_type : ($pfType ?: 'student'));
    $lockAcademic = $isEdit && $member->member_type === 'student';
    $pendingUpdateRequest = $pendingUpdateRequest ?? null;
    $input = 'w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
    $label = 'block text-xs font-extrabold uppercase tracking-widest text-gray-500 mb-1.5';
@endphp

<div class="space-y-6" x-data="hrMemberForm()">
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 sm:p-6 text-white shadow-sm">
        <p class="text-sm font-bold uppercase tracking-widest text-white/50">Human Resource</p>
        <h1 class="mt-1 text-3xl font-extrabold">{{ $isEdit ? 'Edit Member' : 'New Member' }}</h1>
        <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">
            Choose the member type first. The form then shows only the fields needed for student or employee records.
        </p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-extrabold">Please correct the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if($pendingUpdateRequest)
        @php
            $requestLabels = [
                'dob_bs' => 'Date of Birth (BS)', 'father_name' => 'Father Name',
                'mother_name' => 'Mother Name', 'grandfather_name' => 'Grandfather Name',
                'mobile' => 'Mobile', 'email' => 'Email', 'parent_contact' => 'Parent Contact',
                'guardian_contact' => 'Guardian Contact', 'permanent_province' => 'Permanent Province',
                'permanent_district' => 'Permanent District', 'permanent_municipality' => 'Permanent Municipality',
                'permanent_ward' => 'Permanent Ward', 'permanent_tole' => 'Permanent Tole',
                'temporary_province' => 'Temporary Province', 'temporary_district' => 'Temporary District',
                'temporary_municipality' => 'Temporary Municipality', 'temporary_ward' => 'Temporary Ward',
                'temporary_tole' => 'Temporary Tole', 'photo' => 'Profile Photo',
            ];
        @endphp
        <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-widest text-amber-700">Pending student request</p>
                    <h2 class="mt-1 text-lg font-extrabold text-amber-950">Review requested profile corrections</h2>
                    <p class="mt-1 text-sm font-semibold text-amber-800">Submitted {{ $pendingUpdateRequest->created_at->diffForHumans() }}. Academic identity fields are never included.</p>
                </div>
                <span class="w-fit rounded-full bg-amber-200 px-3 py-1 text-xs font-extrabold text-amber-900">Awaiting review</span>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach($pendingUpdateRequest->requested_changes as $field => $proposed)
                    <div class="rounded-xl border border-amber-200 bg-white p-3">
                        <p class="text-xs font-extrabold uppercase tracking-wide text-gray-500">{{ $requestLabels[$field] ?? str($field)->headline() }}</p>
                        @if($field === 'photo')
                            <div class="mt-2 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                                <div><p class="mb-1 text-[10px] font-bold uppercase text-gray-400">Current</p><img src="{{ $member->photo_url }}" alt="Current profile photo" class="h-28 w-24 rounded-xl border object-cover"></div>
                                <span class="text-gray-400">→</span>
                                <div><p class="mb-1 text-[10px] font-bold uppercase text-emerald-600">Requested</p><img src="{{ asset($proposed) }}" alt="Requested profile photo" class="h-28 w-24 rounded-xl border border-emerald-300 object-cover"></div>
                            </div>
                        @else
                            <div class="mt-2 grid grid-cols-[1fr_auto_1fr] items-center gap-2 text-sm">
                                <span class="break-words font-semibold text-red-600 line-through">{{ filled($member->{$field}) ? $member->{$field} : 'Not set' }}</span>
                                <span class="text-gray-400">→</span>
                                <span class="break-words font-extrabold text-emerald-700">{{ $proposed }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <form method="POST" action="{{ route('admin.update-requests.review', $pendingUpdateRequest) }}" class="mt-4 flex flex-col gap-3 border-t border-amber-200 pt-4 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-amber-800">Review note (optional)</label>
                    <input name="admin_note" maxlength="500" class="w-full rounded-xl border border-amber-300 bg-white px-4 py-3 text-sm font-semibold focus:border-amber-600 focus:outline-none" placeholder="Reason or internal note">
                </div>
                <div class="flex gap-2">
                    <button name="action" value="approve" class="flex-1 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-extrabold text-white hover:bg-emerald-800">Approve</button>
                    <button name="action" value="reject" class="flex-1 rounded-xl bg-red-600 px-5 py-3 text-sm font-extrabold text-white hover:bg-red-700">Reject</button>
                </div>
            </form>
        </section>
    @endif

    @if($p)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-200 text-sm font-extrabold text-amber-900">
                {{ strtoupper(substr($p->name, 0, 1)) }}
            </div>
            <div>
                <p class="text-sm font-extrabold text-amber-900">Pre-filled from existing account: {{ $p->name }}</p>
                <p class="text-xs font-medium text-amber-700">Review all fields and fill in any missing information before saving.</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('admin.hr.members.update', $member) : route('admin.hr.members.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if($isEdit) @method('PUT') @endif
        @if($p)
            <input type="hidden" name="prefill_user" value="{{ $p->id }}">
        @endif
        @if($lockAcademic)
            <input type="hidden" name="member_type" value="{{ $member->member_type }}">
            <input type="hidden" name="organization" value="{{ $member->organization }}">
            <input type="hidden" name="stream" value="{{ $member->stream }}">
            <input type="hidden" name="section" value="{{ $member->section }}">
            <input type="hidden" name="roll_number" value="{{ $member->roll_number }}">
        @endif

        {{-- ── Type gate: shown alone, before any other field, until a type is picked ── --}}
        <section x-show="!typeChosen" x-cloak class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-xs font-extrabold uppercase tracking-widest text-[#1a5632]">Step 1</p>
            <h2 class="mt-1 text-2xl font-extrabold text-gray-950">Who are you adding?</h2>
            <p class="mt-1 text-sm font-medium text-gray-500">Pick one to continue. The form below will only show the fields that type needs.</p>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <button type="button" @click="memberType = 'student'; typeChosen = true"
                        class="flex flex-col items-start rounded-2xl border-2 border-gray-200 bg-white p-5 text-left transition hover:border-[#1a5632] hover:bg-emerald-50">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-[#1a5632]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                    </span>
                    <span class="mt-3 text-lg font-extrabold text-gray-950">Student</span>
                    <span class="mt-1 text-xs font-semibold text-gray-500">Class, section, roll, guardian</span>
                </button>
                <button type="button" @click="memberType = 'teacher'; typeChosen = true"
                        class="flex flex-col items-start rounded-2xl border-2 border-gray-200 bg-white p-5 text-left transition hover:border-[#1a5632] hover:bg-emerald-50">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-[#1a5632]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m0-6L3 9m9 5v6M3 9v6a9 9 0 0018 0V9"/></svg>
                    </span>
                    <span class="mt-3 text-lg font-extrabold text-gray-950">Academic Employee</span>
                    <span class="mt-1 text-xs font-semibold text-gray-500">Teacher, resources, attendance</span>
                </button>
                <button type="button" @click="memberType = 'staff'; typeChosen = true"
                        class="flex flex-col items-start rounded-2xl border-2 border-gray-200 bg-white p-5 text-left transition hover:border-[#1a5632] hover:bg-emerald-50">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-[#1a5632]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </span>
                    <span class="mt-3 text-lg font-extrabold text-gray-950">Administrative Employee</span>
                    <span class="mt-1 text-xs font-semibold text-gray-500">Office staff, attendance, payroll</span>
                </button>
            </div>
        </section>

        <section x-show="typeChosen" x-cloak class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-widest text-[#1a5632]">Step 1</p>
                    <h2 class="mt-1 text-lg font-extrabold text-gray-950">Categorize Member</h2>
                    <p class="mt-1 text-sm font-medium text-gray-500">This decides where the record appears later: ID Card, Learning, Attendance, Library, and future modules.</p>
                    @if($lockAcademic)
                        <p class="mt-2 inline-flex rounded-lg bg-gray-100 px-3 py-2 text-xs font-extrabold text-gray-600">Academic identity is locked. Use the promotion workflow for class or section movement.</p>
                    @endif
                </div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <label class="cursor-pointer">
                    <input type="radio" name="member_type" value="student" x-model="memberType" class="peer sr-only" @disabled($lockAcademic)>
                    <span class="flex min-h-20 flex-col justify-center rounded-xl border-2 border-gray-200 px-4 py-3 text-sm font-extrabold text-gray-600 transition peer-checked:border-[#1a5632] peer-checked:bg-emerald-50 peer-checked:text-[#1a5632]">
                        Student
                        <small class="mt-1 text-xs font-semibold opacity-70">Class, section, roll, guardian</small>
                    </span>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="member_type" value="teacher" x-model="memberType" class="peer sr-only" @disabled($lockAcademic)>
                    <span class="flex min-h-20 flex-col justify-center rounded-xl border-2 border-gray-200 px-4 py-3 text-sm font-extrabold text-gray-600 transition peer-checked:border-[#1a5632] peer-checked:bg-emerald-50 peer-checked:text-[#1a5632]">
                        Academic Employee
                        <small class="mt-1 text-xs font-semibold opacity-70">Teacher, resources, attendance</small>
                    </span>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="member_type" value="staff" x-model="memberType" class="peer sr-only" @disabled($lockAcademic)>
                    <span class="flex min-h-20 flex-col justify-center rounded-xl border-2 border-gray-200 px-4 py-3 text-sm font-extrabold text-gray-600 transition peer-checked:border-[#1a5632] peer-checked:bg-emerald-50 peer-checked:text-[#1a5632]">
                        Administrative Employee
                        <small class="mt-1 text-xs font-semibold opacity-70">Office staff, attendance, payroll</small>
                    </span>
                </label>
            </div>
            @unless($lockAcademic)
                <button type="button" @click="typeChosen = false" class="mt-3 text-xs font-extrabold text-gray-400 hover:text-[#1a5632] hover:underline">
                    &larr; Change member type
                </button>
            @endunless

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="{{ $label }}">Organization</label>
                    <select name="organization" x-model="organization" @change="stream = ''; section = ''" required @disabled($lockAcademic) class="{{ $input }} {{ $lockAcademic ? 'cursor-not-allowed bg-gray-100 text-gray-500' : '' }}">
                        <option value="">Select organization</option>
                        @foreach($formOptions as $orgValue => $orgData)
                            <option value="{{ $orgValue }}">{{ $orgData['label'] ?? $orgValue }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" x-text="isStudent ? 'Class' : 'Department / Unit (optional)'"></label>
                    <select name="stream" x-model="stream" @change="section = ''" :required="isStudent" @disabled($lockAcademic) class="{{ $input }} {{ $lockAcademic ? 'cursor-not-allowed bg-gray-100 text-gray-500' : '' }}">
                        <option value="" x-text="organization ? (isStudent ? 'Select class' : 'Select department / unit') : 'Select organization first'"></option>
                        <template x-for="item in streamOptions" :key="item">
                            <option :value="item" :selected="item === stream" x-text="item"></option>
                        </template>
                    </select>
                </div>
                <div x-show="isStudent" x-transition>
                    <label class="{{ $label }}">Section</label>
                    <select name="section" x-model="section" @disabled($lockAcademic) class="{{ $input }} {{ $lockAcademic ? 'cursor-not-allowed bg-gray-100 text-gray-500' : '' }}">
                        <option value="" x-text="stream ? 'Select section' : 'Select class first'"></option>
                        <template x-for="item in sectionOptions" :key="item">
                            <option :value="item" :selected="item === section" x-text="item"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}" x-text="isStudent ? 'Roll Number / Student ID' : 'Employee ID'"></label>
                    <input name="roll_number" value="{{ old('roll_number', $isEdit ? $member->roll_number : '') }}" required @readonly($lockAcademic) class="{{ $input }} {{ $lockAcademic ? 'cursor-not-allowed bg-gray-100 text-gray-500' : '' }}">
                </div>
                <input type="hidden" name="employee_category" :value="memberType === 'teacher' ? 'academic' : (memberType === 'staff' ? 'administrative' : '')">
            </div>
        </section>

        <div x-show="typeChosen" x-cloak class="space-y-5">

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-widest text-[#1a5632]">Step 2</p>
            <h2 class="mt-1 text-lg font-extrabold text-gray-950">Personal Details</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="xl:col-span-3">
                    <label class="{{ $label }}">Full Name</label>
                    <input name="full_name"
                           value="{{ old('full_name', $isEdit ? $member->full_name : ($p ? $p->name : '')) }}"
                           placeholder="First Middle Last"
                           required
                           class="{{ $input }}">
                </div>
                {{-- Photo — file picker + webcam capture --}}
                <div x-data="photoCapture()" class="xl:col-span-1">
                    <label class="{{ $label }}">Photo</label>

                    {{-- Current / preview --}}
                    <div class="flex items-start gap-3 mb-2">
                        <img :src="preview" x-show="preview" x-cloak
                             class="w-16 h-16 rounded-xl object-cover ring-2 ring-[#1a5632]/20 shrink-0">
                        @if($isEdit && $member->photo)
                            <img src="{{ asset($member->photo) }}" x-show="!preview"
                                 class="w-16 h-16 rounded-xl object-cover ring-2 ring-gray-200 shrink-0">
                        @else
                            <div x-show="!preview"
                                 class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="flex flex-col gap-1.5 flex-1 min-w-0">
                            <input x-ref="photoInput" type="file" name="photo" accept="image/*" class="hidden" @change="onFileChange($event)">
                            <button type="button" @click="openPhotoPicker(false)" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-extrabold text-gray-700 hover:border-[#1a5632] hover:text-[#1a5632] transition-colors">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Upload file
                            </button>
                            <button type="button" @click="openPhotoPicker(true)" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-300 bg-blue-50 px-3 py-2 text-xs font-extrabold text-blue-700 hover:bg-blue-100 transition-colors">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                                Rear camera
                            </button>
                            <button type="button" @click="openCamera()"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-extrabold text-gray-700 hover:border-[#1a5632] hover:text-[#1a5632] transition-colors">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                                Use camera
                            </button>
                        </div>
                    </div>

                    {{-- Webcam modal --}}
                    <div x-show="cameraOpen" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
                        <div class="w-full max-w-sm rounded-2xl bg-white shadow-2xl overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-extrabold text-gray-900">Take Photo</p>
                                <button type="button" @click="closeCamera()"
                                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            {{-- Camera selector --}}
                            <div class="px-4 pt-3" x-show="cameras.length > 1">
                                <select x-model="selectedCamera" @change="switchCamera()"
                                        class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs font-semibold focus:border-[#1a5632] focus:outline-none">
                                    <template x-for="cam in cameras" :key="cam.deviceId">
                                        <option :value="cam.deviceId" x-text="cam.label || 'Camera ' + (cameras.indexOf(cam)+1)"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="p-4">
                                <div class="relative rounded-xl overflow-hidden bg-black aspect-[4/3]">
                                    <video x-ref="video" autoplay playsinline muted
                                           class="w-full h-full object-cover"></video>
                                    <p x-show="cameraError" class="absolute inset-0 flex items-center justify-center text-white text-xs font-semibold bg-black/70 px-4 text-center" x-text="cameraError"></p>
                                </div>
                                <canvas x-ref="canvas" class="hidden"></canvas>
                            </div>

                            <div class="flex gap-2 px-4 pb-4">
                                <button type="button" @click="toggleCamera()"
                                        :disabled="!!cameraError"
                                        class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-extrabold text-gray-700 hover:bg-gray-50 disabled:opacity-40">
                                    Switch
                                </button>
                                <button type="button" @click="capturePhoto()"
                                        :disabled="!!cameraError"
                                        class="flex-1 rounded-xl bg-[#1a5632] py-3 text-sm font-extrabold text-white hover:bg-[#0b2415] disabled:opacity-40 transition-colors">
                                    Capture
                                </button>
                                <button type="button" @click="closeCamera()"
                                        class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-extrabold text-gray-600 hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden input for captured webcam image (base64 → sent as separate field) --}}
                    <input type="hidden" name="photo_capture" x-model="capturedData">
                </div>
                {{-- Date of Birth AD --}}
                <div>
                    <label class="{{ $label }}">Date of Birth (AD)</label>
                    <input type="date" name="dob"
                           value="{{ old('dob', $isEdit ? $member->dob?->format('Y-m-d') : '') }}"
                           class="{{ $input }}">
                </div>
                {{-- Date of Birth BS --}}
                <div x-data="bsDateInput(@json(old('dob_bs', $isEdit ? ($member->dob_bs ?? '') : '')))">
                    <label class="{{ $label }}">Date of Birth (BS)</label>
                    <input type="text" name="dob_bs" x-ref="bs"
                           value="{{ old('dob_bs', $isEdit ? ($member->dob_bs ?? '') : '') }}"
                           @beforeinput="onBeforeInput($event)"
                           @input="onInput($event)"
                           @paste.prevent="onPaste($event)"
                           placeholder="YYYY-MM-DD"
                           maxlength="10"
                           inputmode="numeric"
                           autocomplete="off"
                           data-min-bs="2000-01-01"
                           data-max-bs="today"
                           class="{{ $input }} nepali-date-picker">
                </div>
                <div><label class="{{ $label }}">Gender</label><select name="gender" class="{{ $input }}"><option value="">Select</option>@foreach(['Male','Female','Other'] as $g)<option value="{{ $g }}" @selected(old('gender', $isEdit ? $member->gender : '') === $g)>{{ $g }}</option>@endforeach</select></div>
                <div><label class="{{ $label }}">Blood Group</label><input name="blood_group" value="{{ old('blood_group', $isEdit ? $member->blood_group : '') }}" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Citizenship No.</label><input name="citizenship_no" value="{{ old('citizenship_no', $isEdit ? $member->citizenship_no : '') }}" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Mobile</label><input name="mobile" value="{{ old('mobile', $isEdit ? $member->mobile : ($p?->phone ?? '')) }}" class="{{ $input }}"></div>
                <div class="xl:col-span-2"><label class="{{ $label }}">Email</label><input type="email" name="email" value="{{ old('email', $isEdit ? $member->email : ($p?->email ?? '')) }}" class="{{ $input }}"></div>
            </div>
        </section>

        <section x-show="isStudent" x-transition class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <fieldset :disabled="!isStudent">
            <p class="text-xs font-extrabold uppercase tracking-widest text-[#1a5632]">Student</p>
            <h2 class="mt-1 text-lg font-extrabold text-gray-950">Parent & Guardian Details</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div><label class="{{ $label }}">Father Name</label><input name="father_name" x-model="fatherName" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Mother Name</label><input name="mother_name" x-model="motherName" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Parent Contact</label><input name="parent_contact" x-model="parentContact" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Grandfather Name</label><input name="grandfather_name" value="{{ old('grandfather_name', $isEdit ? $member->grandfather_name : '') }}" class="{{ $input }}"></div>
                <div>
                    <label class="{{ $label }}">Guardian</label>
                    <select name="guardian_relation" x-model="guardianRelation" class="{{ $input }}">
                        <option value="father">Father</option>
                        <option value="mother">Mother</option>
                        <option value="grandfather">Grandfather</option>
                        <option value="guardian">Other Guardian</option>
                    </select>
                </div>
                <div x-show="guardianRelation === 'guardian'" x-transition>
                    <label class="{{ $label }}">Guardian Name</label>
                    <input x-model="customGuardianName" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Guardian Contact</label>
                    <input name="guardian_contact" x-model="guardianContact" class="{{ $input }}">
                </div>
                <input type="hidden" name="guardian_name" :value="guardianName">
                <div><label class="{{ $label }}">Registration No.</label><input name="registration_no" value="{{ old('registration_no', $isEdit ? $member->registration_no : '') }}" class="{{ $input }}"></div>
                <div x-show="!isPermanentEmployee" x-transition><label class="{{ $label }}">Valid Till (AD)</label><input type="date" name="valid_till" value="{{ old('valid_till', $isEdit ? $member->valid_till?->format('Y-m-d') : '') }}" class="{{ $input }}"></div>
                <div x-show="!isPermanentEmployee" x-transition x-data="bsDateInput(@json(old('valid_till_bs', $isEdit ? ($member->valid_till_bs ?? '') : '')))">
                    <label class="{{ $label }}">Valid Till (BS)</label>
                    <input type="text" name="valid_till_bs" x-ref="bs"
                           value="{{ old('valid_till_bs', $isEdit ? ($member->valid_till_bs ?? '') : '') }}"
                           @beforeinput="onBeforeInput($event)"
                           @input="onInput($event)"
                           @paste.prevent="onPaste($event)"
                           placeholder="YYYY-MM-DD"
                           maxlength="10"
                           inputmode="numeric"
                           autocomplete="off"
                           class="{{ $input }}">
                </div>
            </div>
            </fieldset>
        </section>

        <section x-show="isEmployee" x-transition class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <fieldset :disabled="!isEmployee">
            <p class="text-xs font-extrabold uppercase tracking-widest text-[#1a5632]">Employee</p>
            <h2 class="mt-1 text-lg font-extrabold text-gray-950">Employment & Payroll</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div><label class="{{ $label }}">Father Name</label><input name="father_name" x-model="fatherName" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Grandfather Name</label><input name="grandfather_name" value="{{ old('grandfather_name', $isEdit ? $member->grandfather_name : '') }}" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Joining Date (AD)</label><input type="date" name="joining_date" value="{{ old('joining_date', $isEdit ? $member->joining_date?->format('Y-m-d') : '') }}" class="{{ $input }}"></div>
                <div x-data="bsDateInput(@json(old('joining_date_bs', $isEdit ? ($member->joining_date_bs ?? '') : '')))">
                    <label class="{{ $label }}">Joining Date (BS)</label>
                    <input type="text" name="joining_date_bs" x-ref="bs"
                           value="{{ old('joining_date_bs', $isEdit ? ($member->joining_date_bs ?? '') : '') }}"
                           @beforeinput="onBeforeInput($event)"
                           @input="onInput($event)"
                           @paste.prevent="onPaste($event)"
                           placeholder="YYYY-MM-DD"
                           maxlength="10"
                           inputmode="numeric"
                           autocomplete="off"
                           class="{{ $input }}">
                </div>
                <div><label class="{{ $label }}">Employment Type</label><select name="employment_type_id" x-model="employmentTypeId" class="{{ $input }}"><option value="">Select</option>@foreach($hajiriOptions['employmentTypes'] as $item)<option value="{{ $item->id }}" @selected((int) old('employment_type_id', $isEdit ? $member->user?->employment_type_id : ($p?->employment_type_id ?? 0)) === $item->id)>{{ $item->label }}</option>@endforeach</select></div>
                <div x-show="isPermanentEmployee" x-transition>
                    <label class="{{ $label }}">Permanent Date (AD)</label>
                    <input type="date" name="permanent_date" value="{{ old('permanent_date', $isEdit ? $member->permanent_date?->format('Y-m-d') : '') }}" class="{{ $input }}">
                </div>
                <div x-show="isPermanentEmployee" x-transition x-data="bsDateInput(@json(old('permanent_date_bs', $isEdit ? ($member->permanent_date_bs ?? '') : '')))">
                    <label class="{{ $label }}">Permanent Date (BS)</label>
                    <input type="text" name="permanent_date_bs" x-ref="bs"
                           value="{{ old('permanent_date_bs', $isEdit ? ($member->permanent_date_bs ?? '') : '') }}"
                           @beforeinput="onBeforeInput($event)"
                           @input="onInput($event)"
                           @paste.prevent="onPaste($event)"
                           placeholder="YYYY-MM-DD"
                           maxlength="10"
                           inputmode="numeric"
                           autocomplete="off"
                           class="{{ $input }}">
                </div>
                <div><label class="{{ $label }}">Designation</label><select name="designation_id" class="{{ $input }}"><option value="">Select</option>@foreach($hajiriOptions['designations'] as $item)<option value="{{ $item->id }}" @selected((int) old('designation_id', $isEdit ? $member->user?->designation_id : ($p?->designation_id ?? 0)) === $item->id)>{{ $item->label }}</option>@endforeach</select></div>
                <div>
                    <label class="{{ $label }}">Attendance Group</label>
                    <div class="{{ $input }} flex items-center bg-gray-50 text-gray-600" x-text="memberType === 'teacher' ? 'Academic (automatic)' : 'Administration (automatic)'"></div>
                </div>
                <div><label class="{{ $label }}">Department</label><select name="hajiri_department_id" class="{{ $input }}"><option value="">Select</option>@foreach($hajiriOptions['departments'] as $item)<option value="{{ $item->id }}" @selected((int) old('hajiri_department_id', $isEdit ? $member->user?->hajiri_department_id : ($p?->hajiri_department_id ?? 0)) === $item->id)>{{ $item->label }}</option>@endforeach</select></div>
                <div><label class="{{ $label }}">Bank Name</label><input name="bank_name" value="{{ old('bank_name', $isEdit ? $member->bank_name : '') }}" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Bank Branch</label><input name="bank_branch" value="{{ old('bank_branch', $isEdit ? $member->bank_branch : '') }}" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Account Name</label><input name="bank_account_name" value="{{ old('bank_account_name', $isEdit ? $member->bank_account_name : '') }}" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Account Number</label><input name="bank_account_number" value="{{ old('bank_account_number', $isEdit ? $member->bank_account_number : '') }}" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">PAN No.</label><input name="pan_number" value="{{ old('pan_number', $isEdit ? $member->pan_number : '') }}" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">SSF / CIT</label><div class="grid grid-cols-2 gap-3"><input name="ssf_number" value="{{ old('ssf_number', $isEdit ? $member->ssf_number : '') }}" placeholder="SSF" class="{{ $input }}"><input name="cit_number" value="{{ old('cit_number', $isEdit ? $member->cit_number : '') }}" placeholder="CIT" class="{{ $input }}"></div></div>
            </div>
            </fieldset>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-widest text-[#1a5632]">Attendance</p>
            <h2 class="mt-1 text-lg font-extrabold text-gray-950">Biometric Device</h2>
            <div class="mt-4 max-w-md">
                <label class="{{ $label }}">Device ID</label>
                <input type="text" inputmode="numeric" pattern="[0-9]*" name="device_id"
                       value="{{ old('device_id', $isEdit ? $member->user?->device_id : ($p?->device_id ?? '')) }}"
                       placeholder="Enter the ID registered on the device"
                       onwheel="this.blur()"
                       class="{{ $input }}">
                <p class="mt-1.5 text-xs font-medium leading-5 text-gray-400">Available for students, teachers, and staff. Each device ID can only be assigned to one member.</p>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-extrabold text-gray-950">Address</h2>
            <div class="mt-4 space-y-5">

                {{-- ── Permanent Address (English cascading selects) ── --}}
                <div>
                    <p class="mb-3 text-sm font-extrabold text-gray-700">Permanent Address</p>
                    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
                        <div>
                            <label class="{{ $label }}">Province</label>
                            <select name="permanent_province" x-model="permProvince"
                                    @change="permDistrict = ''; permMunicipality = ''"
                                    class="{{ $input }}">
                                <option value="">Select Province</option>
                                <template x-for="prov in NEPAL_ADDR.provinces" :key="prov">
                                    <option :value="prov" :selected="prov === permProvince" x-text="prov"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">District</label>
                            <select name="permanent_district" x-model="permDistrict"
                                    @change="permMunicipality = ''"
                                    :disabled="!permProvince"
                                    class="{{ $input }}">
                                <option value="">Select District</option>
                                <template x-for="dist in (NEPAL_ADDR.districts[permProvince] || [])" :key="dist">
                                    <option :value="dist" :selected="dist === permDistrict" x-text="dist"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Municipality / Rural Municipality</label>
                            <select name="permanent_municipality" x-model="permMunicipality"
                                    :disabled="!permDistrict"
                                    class="{{ $input }}">
                                <option value="">Select Municipality</option>
                                <template x-for="mun in (NEPAL_ADDR.municipalities[permDistrict] || [])" :key="mun">
                                    <option :value="mun" :selected="mun === permMunicipality" x-text="mun"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Ward</label>
                            <input name="permanent_ward" x-model="permWard"
                                   placeholder="Ward no." class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Tole / Street</label>
                            <input name="permanent_tole" x-model="permTole" placeholder="Tole / Street" class="{{ $input }}">
                        </div>
                    </div>
                </div>

                {{-- ── Same-as-permanent toggle ── --}}
                <button type="button" @click="sameAddress = !sameAddress"
                        :class="sameAddress
                            ? 'border-[#1a5632] bg-emerald-50 text-[#1a5632]'
                            : 'border-gray-200 text-gray-500 hover:border-[#1a5632] hover:text-[#1a5632] hover:bg-green-50'"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-bold transition-colors select-none">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span x-text="sameAddress ? '✓ Temporary same as Permanent' : 'Temporary address same as Permanent'"></span>
                </button>

                {{-- Hidden copies submitted when sameAddress = true --}}
                <template x-if="sameAddress">
                    <div>
                        <input type="hidden" name="temporary_province"     :value="permProvince">
                        <input type="hidden" name="temporary_district"     :value="permDistrict">
                        <input type="hidden" name="temporary_municipality" :value="permMunicipality">
                        <input type="hidden" name="temporary_ward"         :value="permWard">
                        <input type="hidden" name="temporary_tole"         :value="permTole">
                    </div>
                </template>

                {{-- ── Temporary Address ── --}}
                <div x-show="!sameAddress" x-transition>
                    <p class="mb-3 text-sm font-extrabold text-gray-700">Temporary Address</p>
                    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
                        <div>
                            <label class="{{ $label }}">Province</label>
                            <select name="temporary_province" x-model="tempProvince"
                                    @change="tempDistrict = ''; tempMunicipality = ''"
                                    class="{{ $input }}">
                                <option value="">Select Province</option>
                                <template x-for="prov in NEPAL_ADDR.provinces" :key="prov">
                                    <option :value="prov" :selected="prov === tempProvince" x-text="prov"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">District</label>
                            <select name="temporary_district" x-model="tempDistrict"
                                    @change="tempMunicipality = ''"
                                    :disabled="!tempProvince"
                                    class="{{ $input }}">
                                <option value="">Select District</option>
                                <template x-for="dist in (NEPAL_ADDR.districts[tempProvince] || [])" :key="dist">
                                    <option :value="dist" :selected="dist === tempDistrict" x-text="dist"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Municipality / Rural Municipality</label>
                            <select name="temporary_municipality" x-model="tempMunicipality"
                                    :disabled="!tempDistrict"
                                    class="{{ $input }}">
                                <option value="">Select Municipality</option>
                                <template x-for="mun in (NEPAL_ADDR.municipalities[tempDistrict] || [])" :key="mun">
                                    <option :value="mun" :selected="mun === tempMunicipality" x-text="mun"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Ward</label>
                            <input name="temporary_ward" x-model="tempWard" placeholder="Ward no." class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Tole / Street</label>
                            <input name="temporary_tole" x-model="tempTole" placeholder="Tole / Street" class="{{ $input }}">
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-extrabold text-gray-950">Login{{ !$isEdit ? ', Library & Transport' : '' }}</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {{-- Login User ID — students only (employees log in via email) --}}
                <div x-show="isStudent" x-transition>
                    <label class="{{ $label }}">Login User ID</label>
                    <input name="login_user_id"
                           value="{{ old('login_user_id', $isEdit ? $member->user?->student_code : ($p?->student_code ?? '')) }}"
                           placeholder="Blank = roll number"
                           class="{{ $input }}">
                </div>
                <div><label class="{{ $label }}">Login Password</label><input type="password" name="password" placeholder="{{ $isEdit ? 'New password optional' : 'Blank = Login User ID / Email' }}" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Confirm Password</label><input type="password" name="password_confirmation" class="{{ $input }}"></div>
                <div x-show="isStudent" x-transition><label class="{{ $label }}">Batch</label><input name="batch" value="{{ old('batch', $isEdit ? $member->batch : '') }}" class="{{ $input }}"></div>
                <div x-show="isStudent && academicSystem === 'semester'" x-transition>
                    <label class="{{ $label }}">Semester</label>
                    <select name="semester" :disabled="academicSystem !== 'semester'" class="{{ $input }}">
                        <option value="">Select semester</option>
                        @for($s = 1; $s <= 8; $s++)
                            <option value="{{ $s }}" @selected(old('semester', $isEdit ? $member->semester : '') == $s)>Semester {{ $s }}</option>
                        @endfor
                    </select>
                    <p class="mt-1 text-[11px] font-semibold text-gray-400">Automatically shown because this faculty uses the semester system.</p>
                </div>
                <div x-show="isStudent && academicSystem === 'year'" x-transition>
                    <label class="{{ $label }}">Study Year</label>
                    <select name="year_level" :disabled="academicSystem !== 'year'" class="{{ $input }}">
                        <option value="">Select year</option>
                        @for($year = 1; $year <= 4; $year++)
                            <option value="{{ $year }}" @selected(old('year_level', $isEdit ? $member->year_level : '') == $year)>Year {{ $year }}</option>
                        @endfor
                    </select>
                    <p class="mt-1 text-[11px] font-semibold text-gray-400">Automatically shown because this faculty uses the year system.</p>
                </div>
                <div x-show="isStudent" x-transition><label class="{{ $label }}">Library ID</label><input name="library_id" value="{{ old('library_id', $isEdit ? $member->library_id : '') }}" class="{{ $input }}"></div>
                <div x-show="isStudent" x-transition><label class="{{ $label }}">Bus Route</label><input name="bus_route" value="{{ old('bus_route', $isEdit ? $member->bus_route : '') }}" class="{{ $input }}"></div>
                <div x-show="isStudent" x-transition><label class="{{ $label }}">Bus Stop</label><input name="bus_stop" value="{{ old('bus_stop', $isEdit ? $member->bus_stop : '') }}" class="{{ $input }}"></div>
            </div>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.hr.members.index') }}" class="inline-flex justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-extrabold text-gray-700 hover:bg-gray-50">Cancel</a>
            <button class="rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415]">{{ $isEdit ? 'Update Member' : 'Create Member' }}</button>
        </div>

        </div>
    </form>
</div>

@push('scripts')
<script src="{{ asset('js/nepal-address.js') }}"></script>
@include('partials.nepali-date-picker')
<script>
function photoCapture() {
    return {
        preview:       null,
        capturedData:  '',
        cameraOpen:    false,
        cameraError:   '',
        cameras:       [],
        selectedCamera: '',
        facingMode:    'environment',
        stream:        null,

        openPhotoPicker(useRearCamera) {
            const input = this.$refs.photoInput;
            input.value = '';
            if (useRearCamera) input.setAttribute('capture', 'environment');
            else input.removeAttribute('capture');
            input.click();
        },

        onFileChange(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.capturedData = '';
            this.preview = URL.createObjectURL(file);
        },

        async openCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.cameraError = 'Camera requires HTTPS and browser permission. You can still upload a photo from your device.';
                this.cameraOpen = true;
                return;
            }

            this.cameraError = '';
            this.cameraOpen  = true;
            this.selectedCamera = '';
            this.facingMode = 'environment';
            await this.$nextTick();

            try {
                await this.startStream();
                await this.enumerateCameras();
            } catch (err) {
                this.cameraError = this.cameraMessage(err);
            }
        },

        async enumerateCameras() {
            const devices = await navigator.mediaDevices.enumerateDevices();
            this.cameras = devices.filter(device => device.kind === 'videoinput');
            const activeId = this.stream?.getVideoTracks()[0]?.getSettings()?.deviceId;
            if (activeId) this.selectedCamera = activeId;
        },

        async startStream() {
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
            }
            try {
                const size = { width: { ideal: 1280 }, height: { ideal: 960 } };

                if (this.selectedCamera) {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: { deviceId: { exact: this.selectedCamera }, ...size },
                        audio: false,
                    });
                } else {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { exact: this.facingMode }, ...size },
                            audio: false,
                        });
                    } catch (error) {
                        if (!['OverconstrainedError', 'ConstraintNotSatisfiedError', 'NotFoundError'].includes(error.name)) throw error;
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { ideal: this.facingMode }, ...size },
                            audio: false,
                        });
                    }
                }

                const video = this.$refs.video;
                video.srcObject = this.stream;
                await video.play();
                const settings = this.stream.getVideoTracks()[0]?.getSettings() || {};
                if (settings.facingMode) this.facingMode = settings.facingMode;
                if (settings.deviceId) this.selectedCamera = settings.deviceId;
                this.cameraError = '';
            } catch (err) {
                this.cameraError = this.cameraMessage(err);
            }
        },

        async switchCamera() {
            await this.startStream();
        },

        async toggleCamera() {
            const currentFacing = this.stream?.getVideoTracks()[0]?.getSettings()?.facingMode || this.facingMode;
            this.facingMode = currentFacing === 'environment' ? 'user' : 'environment';
            this.selectedCamera = '';
            await this.startStream();
            await this.enumerateCameras();
        },

        cameraMessage(error) {
            if (error?.name === 'NotAllowedError' || error?.name === 'SecurityError') {
                return 'Camera permission was denied. Allow camera access in the browser and retry.';
            }
            if (error?.name === 'NotFoundError') return 'No camera was found on this device.';
            return 'Could not open the camera. Close other camera apps and retry.';
        },

        capturePhoto() {
            const video  = this.$refs.video;
            const canvas = this.$refs.canvas;
            if (!video || video.readyState < 2) {
                this.cameraError = 'Camera is still starting. Wait a moment and retry.';
                return;
            }
            canvas.width  = video.videoWidth  || 640;
            canvas.height = video.videoHeight || 480;
            canvas.getContext('2d').drawImage(video, 0, 0);
            this.capturedData = canvas.toDataURL('image/jpeg', 0.88);
            this.preview      = this.capturedData;
            this.closeCamera();
        },

        closeCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            this.cameraOpen = false;
        },
    };
}

    function hrMemberForm() {
        return {
            memberType: @json($selectedType),
            fatherName: @json(old('father_name', $isEdit ? $member->father_name : '')),
            motherName: @json(old('mother_name', $isEdit ? $member->mother_name : '')),
            parentContact: @json(old('parent_contact', $isEdit ? $member->parent_contact : ($p?->phone ?? ''))),
            guardianRelation: @json(old('guardian_relation', $isEdit ? ($member->guardian_relation ?: 'father') : 'father')),
            customGuardianName: @json(old('guardian_name', $isEdit ? $member->guardian_name : '')),
            guardianContact: @json(old('guardian_contact', $isEdit ? ($member->guardian_contact ?: $member->parent_contact) : ($p?->phone ?? ''))),
            cardOptions: @json($formOptions),
            organization: @json(old('organization', $isEdit ? $member->organization : '')),
            stream: @json(old('stream', $isEdit ? $member->stream : '')),
            section: @json(old('section', $isEdit ? $member->section : '')),
            employmentTypeId: @json((string) old('employment_type_id', $isEdit ? $member->user?->employment_type_id : ($p?->employment_type_id ?? ''))),
            initialHasPermanentDate: @json($isEdit && (filled($member->permanent_date) || filled($member->permanent_date_bs))),
            permanentEmploymentTypeIds: @json($hajiriOptions['employmentTypes']
                ->filter(fn ($item) => str_contains(strtolower($item->label), 'permanent') || str_contains($item->label, 'स्थायी'))
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->values()),

            // Permanent address
            permProvince:     @json(old('permanent_province',     $isEdit ? $member->permanent_province     : '')),
            permDistrict:     @json(old('permanent_district',     $isEdit ? $member->permanent_district     : '')),
            permMunicipality: @json(old('permanent_municipality', $isEdit ? $member->permanent_municipality : '')),
            permWard:         @json(old('permanent_ward',         $isEdit ? $member->permanent_ward         : '')),
            permTole:         @json(old('permanent_tole',         $isEdit ? $member->permanent_tole         : '')),

            // Temporary address
            tempProvince:     @json(old('temporary_province',     $isEdit ? $member->temporary_province     : '')),
            tempDistrict:     @json(old('temporary_district',     $isEdit ? $member->temporary_district     : '')),
            tempMunicipality: @json(old('temporary_municipality', $isEdit ? $member->temporary_municipality : '')),
            tempWard:         @json(old('temporary_ward',         $isEdit ? $member->temporary_ward         : '')),
            tempTole:         @json(old('temporary_tole',         $isEdit ? $member->temporary_tole         : '')),

            typeChosen: @json($isEdit || (bool) old('member_type') || (bool) $p),

            sameAddress: @json(
                old('same_address') !== null
                    ? (bool) old('same_address')
                    : ($isEdit
                        ? ($member->permanent_province === $member->temporary_province
                           && $member->permanent_district === $member->temporary_district
                           && $member->permanent_municipality === $member->temporary_municipality)
                        : false)
            ),

            get isStudent()  { return this.memberType === 'student'; },
            get isEmployee() { return this.memberType === 'teacher' || this.memberType === 'staff'; },
            get isPermanentEmployee() {
                return this.isEmployee && (this.permanentEmploymentTypeIds.includes(String(this.employmentTypeId)) || this.initialHasPermanentDate);
            },
            get streamOptions() {
                return Object.keys(this.cardOptions[this.organization]?.streams || {});
            },
            get sectionOptions() {
                return this.cardOptions[this.organization]?.streams?.[this.stream] || [];
            },
            get academicSystem() {
                return this.cardOptions[this.organization]?.academic_systems?.[this.stream] || 'none';
            },
            get guardianName() {
                if (this.guardianRelation === 'father')   return this.fatherName;
                if (this.guardianRelation === 'mother')   return this.motherName;
                return this.customGuardianName;
            },

            init() {
                this.$watch('parentContact', value => {
                    if (!this.guardianContact) this.guardianContact = value;
                });
            }
        }
    }
</script>
@endpush
@endsection
