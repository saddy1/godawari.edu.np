@extends('card.layouts.app')
@section('title', 'Add Member')
@section('heading', isset($student) ? 'Edit Member' : 'Add New Member')

@section('content')
@php
    $isEdit = isset($student);
    $lockAcademic = $isEdit && $student->member_type === 'student';
    $selectedOrganization = old('organization', $isEdit ? $student->organization : 'college');
    $selectedStream = old('stream', $isEdit ? $student->stream : '');
    $selectedSection = old('section', $isEdit ? $student->section : '');
@endphp

<div class="max-w-6xl">
<form method="POST"
      action="{{ $isEdit ? route('students.update', $student) : route('students.store') }}"
      enctype="multipart/form-data"
      class="space-y-4">
    @csrf
    @if($isEdit) @method('PUT') @endif
    @if($lockAcademic)
        <input type="hidden" name="member_type" value="{{ $student->member_type }}">
        <input type="hidden" name="organization" value="{{ $student->organization }}">
        <input type="hidden" name="stream" value="{{ $student->stream }}">
        <input type="hidden" name="section" value="{{ $student->section }}">
        <input type="hidden" name="roll_number" value="{{ $student->roll_number }}">
    @endif

    {{-- Member Type --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold text-primary mb-3 text-sm uppercase tracking-wide">Member Type</h2>
        @if($lockAcademic)
            <p class="mb-3 rounded-lg bg-gray-100 px-3 py-2 text-xs font-bold text-gray-600">Student type, organization, class, section, and roll number are locked.</p>
        @endif
        <div class="grid grid-cols-3 gap-3">
            @foreach(['student' => '🎓 Student', 'teacher' => '👨‍🏫 Teacher', 'staff' => '👷 Staff'] as $val => $label)
            <label class="relative cursor-pointer">
                <input type="radio" name="member_type" value="{{ $val }}"
                       class="peer sr-only" @disabled($lockAcademic) {{ old('member_type', $isEdit ? $student->member_type : '') == $val ? 'checked' : '' }}>
                <div class="border-2 border-gray-200 rounded-lg p-3 text-center text-sm font-medium
                            peer-checked:border-primary peer-checked:bg-blue-50 peer-checked:text-primary
                            hover:border-gray-300 transition">
                    {{ $label }}
                </div>
            </label>
            @endforeach
        </div>
    </div>

    {{-- Basic Info --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold text-primary mb-3 text-sm uppercase tracking-wide">Basic Information</h2>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div>
    <label class="block text-xs font-medium text-gray-600 mb-1">Organization <span class="text-red-500">*</span></label>
    <select name="organization" id="organizationSelect" @disabled($lockAcademic) class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 {{ $lockAcademic ? 'cursor-not-allowed bg-gray-100 text-gray-500' : '' }}">
        @foreach($formOptions as $slug => $organization)
            <option value="{{ $slug }}" {{ $selectedOrganization === $slug ? 'selected' : '' }}>{{ $organization['label'] }}</option>
        @endforeach
    </select>
</div>

<div>
    <label class="block text-xs font-medium text-gray-600 mb-1">Department / Class</label>
    <select name="stream" id="streamSelect" @disabled($lockAcademic)
           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 {{ $lockAcademic ? 'cursor-not-allowed bg-gray-100 text-gray-500' : '' }}">
    </select>
</div>
<div>
    <label class="block text-xs font-medium text-gray-600 mb-1">Section</label>
    <select name="section" id="sectionSelect" @disabled($lockAcademic)
           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 {{ $lockAcademic ? 'cursor-not-allowed bg-gray-100 text-gray-500' : '' }}">
    </select>
</div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Roll / ID Number <span class="text-red-500">*</span></label>
                <input type="text" name="roll_number" value="{{ old('roll_number', $isEdit ? $student->roll_number : '') }}"
                       @readonly($lockAcademic)
                       placeholder="e.g. PUR071BEL019 or ST-01"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 {{ $lockAcademic ? 'cursor-not-allowed bg-gray-100 text-gray-500' : '' }}">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Mobile <span class="text-red-500"></span></label>
                <input type="text" name="mobile" value="{{ old('mobile', $isEdit ? $student->mobile : '') }}"
                       placeholder="98XXXXXXXX"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">First Name <span class="text-red-500">*</span></label>
                <input type="text" name="first_name" value="{{ old('first_name', $isEdit ? $student->first_name : '') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Middle Name</label>
                <input type="text" name="middle_name" value="{{ old('middle_name', $isEdit ? $student->middle_name : '') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Last Name <span class="text-red-500">*</span></label>
                <input type="text" name="last_name" value="{{ old('last_name', $isEdit ? $student->last_name : '') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div id="guardianNameField">
                <label class="block text-xs font-medium text-gray-600 mb-1">Guardian Name</label>
                <input type="text" name="guardian_name" value="{{ old('guardian_name', $isEdit ? $student->guardian_name : '') }}"
                       placeholder="Father / Mother / Guardian"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div id="registrationNoField">
                <label class="block text-xs font-medium text-gray-600 mb-1">Registration No.</label>
                <input type="text" name="registration_no" value="{{ old('registration_no', $isEdit ? $student->registration_no : '') }}"
                       placeholder="e.g. REG-2080-001"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Date of Birth <span class="text-red-500"></span></label>
                <input type="date" name="dob" value="{{ old('dob', $isEdit ? $student->dob?->format('Y-m-d') : '') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $isEdit ? $student->email : '') }}"
                       placeholder="name@ioepc.edu.np"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Valid Till <span class="text-red-500">*</span> <span class="text-gray-400">(required for students)</span></label>
                <input type="date" name="valid_till" value="{{ old('valid_till', $isEdit ? $student->valid_till?->format('Y-m-d') : '') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Citizenship No.</label>
                <input type="text" name="citizenship_no" value="{{ old('citizenship_no', $isEdit ? $student->citizenship_no : '') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
        </div>
    </div>

    {{-- Photo --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5" x-data="adminCameraWidget()">

        <h2 class="font-semibold text-primary mb-4 text-sm uppercase tracking-wide">Photo</h2>

        <div class="flex flex-col sm:flex-row items-start gap-6">

            {{-- Avatar preview --}}
            <div class="flex flex-col items-center gap-3 shrink-0">
                <div class="relative w-36 h-44 rounded-xl overflow-hidden border-2 border-dashed border-gray-300 bg-gray-50">
                    <img id="admin-photo-preview"
                         src="{{ ($isEdit && $student->photo) ? $student->photo_url : '' }}"
                         class="w-full h-full object-cover {{ ($isEdit && $student->photo) ? '' : 'hidden' }}">
                    <div id="admin-photo-placeholder"
                         class="{{ ($isEdit && $student->photo) ? 'hidden' : 'flex' }} h-full w-full flex-col items-center justify-center text-gray-300">
                        <svg class="w-10 h-10 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <p class="text-xs text-center px-2">No photo</p>
                    </div>
                </div>
                <p id="admin-photo-label" class="text-xs text-gray-400 text-center">Passport size · max 2 MB</p>
            </div>

            {{-- Controls --}}
            <div class="flex flex-col gap-3 flex-1 justify-center">
                <input type="file" name="photo" accept="image/*" id="admin-photo-input" class="hidden">

                {{-- Use Camera button --}}
                <button type="button" id="admin-use-camera-btn" @click="openCamera()"
                        class="flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-green-600 bg-green-50 px-5 py-3 text-sm font-bold text-green-700 hover:bg-green-100 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/>
                    </svg>
                    Use Camera
                </button>
                <p id="admin-camera-https-note" class="hidden text-xs font-semibold text-amber-600">Camera requires HTTPS. Use Upload below.</p>

                {{-- Upload from Device --}}
                <button type="button" onclick="document.getElementById('admin-photo-input').click()"
                        class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-5 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Upload from Device
                </button>
            </div>
        </div>

        {{-- ── FULL-SCREEN CAMERA MODAL ── --}}
        <div x-show="open" x-cloak
             class="fixed inset-0 z-50 flex flex-col bg-black"
             style="display:none;">

            {{-- Header --}}
            <div class="flex flex-col gap-2 px-4 py-3 bg-black/80 text-white">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-extrabold">Take Photo</p>
                    <div class="flex items-center gap-3">
                        {{-- BG mode (when segmentation ready) --}}
                        <div x-show="segReady" x-cloak class="flex items-center gap-1">
                            <span class="text-xs font-bold text-white/50 mr-1">BG:</span>
                            @foreach(['none' => 'Off', 'white' => 'White', 'blue' => 'Blue', 'blur' => 'Blur'] as $val => $lbl)
                            <button type="button" @click="bgMode = '{{ $val }}'"
                                    :class="bgMode === '{{ $val }}' ? 'bg-white text-gray-900' : 'bg-white/20 text-white'"
                                    class="rounded-lg px-2 py-1 text-xs font-black transition-colors">{{ $lbl }}</button>
                            @endforeach
                        </div>
                        <button type="button" @click="closeCamera()" class="rounded-lg p-1.5 text-white/60 hover:text-white hover:bg-white/10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                {{-- Camera selector pills --}}
                <div x-show="cameras.length > 1" x-cloak class="flex items-center gap-2 overflow-x-auto pb-0.5">
                    <svg class="h-3.5 w-3.5 shrink-0 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/>
                    </svg>
                    <template x-for="(cam, i) in cameras" :key="cam.deviceId">
                        <button type="button" @click="selectCamera(cam.deviceId)"
                                :class="selectedDeviceId === cam.deviceId ? 'bg-white text-gray-900' : 'bg-white/15 text-white/80 hover:bg-white/25'"
                                class="shrink-0 rounded-lg px-2.5 py-1 text-xs font-bold transition-colors"
                                x-text="cam.label || ('Camera ' + (i + 1))"></button>
                    </template>
                </div>
            </div>

            {{-- Canvas preview --}}
            <div class="relative flex flex-1 items-center justify-center bg-black overflow-hidden">
                <video x-ref="video" class="absolute opacity-0 pointer-events-none" playsinline autoplay muted></video>
                <canvas x-ref="canvas"
                        :style="!captured && facingMode === 'user' ? 'transform:scaleX(-1)' : ''"
                        class="max-h-full max-w-full rounded-xl transition-transform"></canvas>
                <div x-show="!streamReady" class="absolute inset-0 flex items-center justify-center bg-black">
                    <div class="text-center text-white">
                        <svg class="mx-auto mb-3 h-10 w-10 animate-spin opacity-50" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <p class="text-sm font-bold opacity-50">Starting camera…</p>
                    </div>
                </div>
                <div x-show="streamReady && !segReady && !segFailed" x-cloak
                     class="absolute top-3 left-3 rounded-full bg-black/50 px-3 py-1 text-xs font-bold text-white/70">
                    Loading BG removal…
                </div>
            </div>

            {{-- Controls --}}
            <div class="flex items-center justify-center gap-6 bg-black/80 px-6 py-5">
                <button type="button" @click="toggleCamera()" x-show="!captured"
                        class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/30 text-white hover:bg-white/10 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
                <div x-show="captured" class="h-12 w-12"></div>

                <button type="button"
                        @click="captured ? retake() : capture()"
                        :class="captured ? 'bg-gray-600 hover:bg-gray-500' : 'bg-white hover:bg-gray-100'"
                        class="h-16 w-16 rounded-full font-extrabold text-gray-900 shadow-lg transition-all active:scale-95">
                    <span x-text="captured ? '↺' : ''" class="text-2xl"></span>
                    <span x-show="!captured" class="block h-12 w-12 mx-auto rounded-full border-4 border-gray-900"></span>
                </button>

                <button type="button" @click="usePhoto()" x-show="captured" x-cloak
                        class="flex h-12 items-center gap-2 rounded-full bg-green-700 px-5 font-extrabold text-white hover:bg-green-800 transition-colors shadow-lg">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Use Photo
                </button>
            </div>
        </div>
    </div>

    {{-- Staff/Teacher Only Fields --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5" id="staffFields">
        <h2 class="font-semibold text-primary mb-3 text-sm uppercase tracking-wide">Employment Details <span class="text-xs text-gray-400 font-normal">(for Staff/Teacher only)</span></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Designation</label>
                <input type="text" name="designation" value="{{ old('designation', $isEdit ? $student->designation : '') }}"
                       placeholder="e.g. Assistant Professor"
                       list="list-designation"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                <datalist id="list-designation"></datalist>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Employment Type</label>
                <input type="text" name="employment_type" value="{{ old('employment_type', $isEdit ? $student->employment_type : '') }}"
                       placeholder="e.g. Permanent / Contract"
                       list="list-employment_type"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                <datalist id="list-employment_type"></datalist>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Program / Department</label>
                <input type="text" name="program" value="{{ old('program', $isEdit ? $student->program : '') }}"
                       placeholder="e.g. BE Computer / Civil Dept."
                       list="list-program"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                <datalist id="list-program"></datalist>
            </div>
        </div>
    </div>

    {{-- Cards to Issue --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold text-primary mb-3 text-sm uppercase tracking-wide">Cards to Issue</h2>
        <div class="space-y-4">
            {{-- Library Card --}}
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="has_library_card" value="1"
                       {{ old('has_library_card', $isEdit ? $student->has_library_card : false) ? 'checked' : '' }}
                       class="mt-1 accent-primary" id="libCheck">
                <div>
                    <p class="font-medium text-sm">📚 Library Card</p>
                    <p class="text-xs text-gray-400">Generates a library membership card with auto-assigned Library ID</p>
                </div>
            </label>
            <div id="libIdField" class="ml-6 {{ old('has_library_card', $isEdit ? $student->has_library_card : false) ? '' : 'hidden' }}">
                <label class="block text-xs font-medium text-gray-600 mb-1">Library ID (auto-generated if empty)</label>
                <input type="text" name="library_id" value="{{ old('library_id', $isEdit ? $student->library_id : '') }}"
                       placeholder="e.g. LIB-STU-0001"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 w-64">
            </div>

            {{-- Bus Pass --}}
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="has_bus_pass" value="1"
                       {{ old('has_bus_pass', $isEdit ? $student->has_bus_pass : false) ? 'checked' : '' }}
                       class="mt-1 accent-primary" id="busCheck">
                <div>
                    <p class="font-medium text-sm">🚌 Bus Pass</p>
                    <p class="text-xs text-gray-400">Generates a campus bus pass with route information</p>
                </div>
            </label>
            <div id="busFields" class="ml-6 grid grid-cols-2 gap-3 {{ old('has_bus_pass', $isEdit ? $student->has_bus_pass : false) ? '' : 'hidden' }}">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Bus Route</label>
                    <input type="text" name="bus_route" value="{{ old('bus_route', $isEdit ? $student->bus_route : '') }}"
                           placeholder="e.g. Route 3 — Biratnagar"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Bus Stop</label>
                    <input type="text" name="bus_stop" value="{{ old('bus_stop', $isEdit ? $student->bus_stop : '') }}"
                           placeholder="e.g. Itahari, Sunsari"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
            </div>
        </div>
    </div>

    {{-- Portal Login --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5" id="learningLoginPanel">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-primary mb-1 text-sm uppercase tracking-wide">Portal Login</h2>
                <p class="text-sm text-gray-500">Use this same member record for ID Card, Library and E-Learning access.</p>
                @if($isEdit && $student->user_id)
                    <p class="mt-2 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 border border-emerald-100">
                        Login linked: {{ $student->user?->student_code ?? $student->roll_number }}
                    </p>
                @endif
            </div>
            <label class="flex items-start gap-3 cursor-pointer rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                <input type="checkbox" name="create_learning_account" value="1"
                       {{ old('create_learning_account', $isEdit ? (bool) $student->user_id : true) ? 'checked' : '' }}
                       class="mt-1 accent-primary" id="learningLoginCheck">
                <span>
                    <span class="block text-sm font-semibold text-gray-800">Enable portal login</span>
                    <span class="block text-xs text-gray-500">User ID will be the Roll / ID Number.</span>
                </span>
            </label>
        </div>

        <div id="learningPasswordFields" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Password</label>
                <input type="password" name="learning_password"
                       placeholder="{{ $isEdit && $student->user_id ? 'Leave blank to keep current password' : 'Leave blank to use Roll / ID Number' }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-200 focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Confirm Password</label>
                <input type="password" name="learning_password_confirmation"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-200 focus:border-primary">
            </div>
        </div>
    </div>

    {{-- Submit --}}
    <div class="flex gap-3">
        <button type="submit"
                class="bg-primary text-white px-8 py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-light transition">
            {{ $isEdit ? 'Update Member' : 'Add Member & Generate Cards' }}
        </button>
        <a href="{{ route('students.index') }}"
           class="bg-gray-100 text-gray-600 px-6 py-2.5 rounded-lg text-sm hover:bg-gray-200 transition">
            Cancel
        </a>
    </div>
</form>
</div>
@endsection

@push('scripts')
<script>
    const formOptions = @json($formOptions);
    const selectedStream = @json($selectedStream);
    const selectedSection = @json($selectedSection);
    const organizationSelect = document.getElementById('organizationSelect');
    const streamSelect = document.getElementById('streamSelect');
    const sectionSelect = document.getElementById('sectionSelect');

    function fillSelect(select, options, selectedValue, placeholder) {
        select.innerHTML = '';

        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = placeholder;
        select.appendChild(placeholderOption);

        options.forEach(function(optionValue) {
            const option = document.createElement('option');
            option.value = optionValue;
            option.textContent = optionValue;
            option.selected = optionValue === selectedValue;
            select.appendChild(option);
        });
    }

    function refreshSections(selectedValue = '') {
        const organization = formOptions[organizationSelect.value] || {streams: {}};
        const sections = organization.streams[streamSelect.value] || [];

        fillSelect(sectionSelect, sections, selectedValue, sections.length ? '-- Select Section --' : '-- No sections available --');
        sectionSelect.disabled = sections.length === 0;
    }

    function refreshStreams(selectedStreamValue = '', selectedSectionValue = '') {
        const organization = formOptions[organizationSelect.value] || {streams: {}};
        const streams = Object.keys(organization.streams || {}).sort();

        fillSelect(streamSelect, streams, selectedStreamValue, streams.length ? '-- Select Department / Class --' : '-- No departments/classes available --');
        streamSelect.disabled = streams.length === 0;

        refreshSections(selectedSectionValue);
    }

    organizationSelect?.addEventListener('change', function() {
        refreshStreams('', '');
    });

    streamSelect?.addEventListener('change', function() {
        refreshSections('');
    });

    refreshStreams(selectedStream, selectedSection);

    // Show guardian/registration fields only for school org
    function toggleSchoolFields() {
        const isSchool = organizationSelect.value === 'school';
        document.getElementById('guardianNameField').style.display   = isSchool ? '' : 'none';
        document.getElementById('registrationNoField').style.display = isSchool ? '' : 'none';
    }
    organizationSelect?.addEventListener('change', toggleSchoolFields);
    toggleSchoolFields();

    function toggleLearningLoginPanel() {
        const selectedType = document.querySelector('input[name="member_type"]:checked')?.value;
        const isStudent = selectedType === 'student' || selectedType === 'teacher';
        const panel = document.getElementById('learningLoginPanel');
        const check = document.getElementById('learningLoginCheck');
        const fields = document.getElementById('learningPasswordFields');

        panel.classList.toggle('hidden', !isStudent);
        fields.classList.toggle('hidden', !check.checked);
    }

    document.querySelectorAll('input[name="member_type"]').forEach(function(input) {
        input.addEventListener('change', toggleLearningLoginPanel);
    });
    document.getElementById('learningLoginCheck')?.addEventListener('change', toggleLearningLoginPanel);
    toggleLearningLoginPanel();

    document.getElementById('libCheck').addEventListener('change', function() {
        document.getElementById('libIdField').classList.toggle('hidden', !this.checked);
    });
    document.getElementById('busCheck').addEventListener('change', function() {
        document.getElementById('busFields').classList.toggle('hidden', !this.checked);
    });

    // ── Autocomplete via datalist ─────────────────────────────────────────
    const suggestFields = ['designation', 'employment_type', 'program'];

    suggestFields.forEach(function(field) {
        const datalist = document.getElementById('list-' + field);
        if (!datalist) return;

        fetch('/api/suggestions?field=' + field)
            .then(r => r.json())
            .then(values => {
                values.forEach(function(v) {
                    const opt = document.createElement('option');
                    opt.value = v;
                    datalist.appendChild(opt);
                });
            });
    });

    // ── Admin camera widget (Alpine.js component) ─────────────────────────
    // Hide Use Camera button on HTTP (camera API unavailable outside localhost/HTTPS)
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        const btn  = document.getElementById('admin-use-camera-btn');
        const note = document.getElementById('admin-camera-https-note');
        if (btn)  btn.style.display = 'none';
        if (note) note.classList.remove('hidden');
    }

    // File upload → preview
    document.getElementById('admin-photo-input').addEventListener('change', function () {
        if (!this.files[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            const img  = document.getElementById('admin-photo-preview');
            const ph   = document.getElementById('admin-photo-placeholder');
            const lbl  = document.getElementById('admin-photo-label');
            img.src = e.target.result;
            img.classList.remove('hidden');
            if (ph) ph.classList.add('hidden');
            if (lbl) lbl.textContent = '✓ ' + this.files[0].name;
        };
        reader.readAsDataURL(this.files[0]);
    });

function adminCameraWidget() {
    return {
        open: false,
        stream: null,
        facingMode: 'environment',
        bgMode: 'none',
        captured: false,
        streamReady: false,
        segReady: false,
        segFailed: false,
        segModel: null,
        animId: null,
        looping: false,
        cameras: [],
        selectedDeviceId: null,
        _capturedCanvas: null,

        async openCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Camera not available.\n\nThis feature requires HTTPS. Please use "Upload from Device" instead.');
                return;
            }
            this.open = true;
            this.captured = false;
            this.streamReady = false;
            await this.$nextTick();
            await this.startStream();
            this.initSegmentation();
            this.enumerateCameras();
        },

        async enumerateCameras() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.cameras = devices.filter(d => d.kind === 'videoinput');
                if (this.stream && !this.selectedDeviceId) {
                    const activeId = this.stream.getVideoTracks()[0]?.getSettings()?.deviceId;
                    if (activeId) this.selectedDeviceId = activeId;
                }
            } catch {}
        },

        async selectCamera(deviceId) {
            this.selectedDeviceId = deviceId;
            this.looping = false;
            cancelAnimationFrame(this.animId);
            this.streamReady = false;
            if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { deviceId: { exact: deviceId }, width: { ideal: 640 }, height: { ideal: 480 } },
                    audio: false,
                });
                const video = this.$refs.video;
                video.srcObject = this.stream;
                await new Promise(r => { video.onloadedmetadata = r; });
                await video.play();
                const settings = this.stream.getVideoTracks()[0]?.getSettings();
                if (settings?.deviceId)   this.selectedDeviceId = settings.deviceId;
                if (settings?.facingMode) this.facingMode = settings.facingMode;
                this.streamReady = true;
                this.startDrawLoop();
            } catch (e) { alert('Could not open selected camera.\n' + e.message); }
        },

        async startStream() {
            this.streamReady = false;
            if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
            try {
                let stream;
                if (this.selectedDeviceId) {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { deviceId: { exact: this.selectedDeviceId }, width: { ideal: 640 }, height: { ideal: 480 } },
                        audio: false,
                    });
                } else {
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { exact: this.facingMode }, width: { ideal: 640 }, height: { ideal: 480 } },
                            audio: false,
                        });
                    } catch (e2) {
                        if (e2.name === 'OverconstrainedError' || e2.name === 'ConstraintNotSatisfiedError') {
                            stream = await navigator.mediaDevices.getUserMedia({
                                video: { width: { ideal: 640 }, height: { ideal: 480 } }, audio: false,
                            });
                        } else { throw e2; }
                    }
                }
                this.stream = stream;
                const video = this.$refs.video;
                video.srcObject = stream;
                await new Promise(r => { video.onloadedmetadata = r; });
                await video.play();
                const settings = stream.getVideoTracks()[0]?.getSettings();
                if (settings?.deviceId)   this.selectedDeviceId = settings.deviceId;
                if (settings?.facingMode) this.facingMode = settings.facingMode;
                this.streamReady = true;
                this.startDrawLoop();
            } catch (e) {
                alert('Camera access denied or unavailable.\n' + e.message);
                this.open = false;
            }
        },

        async initSegmentation() {
            if (this.segModel || typeof SelfieSegmentation === 'undefined') {
                if (typeof SelfieSegmentation === 'undefined') this.segFailed = true;
                return;
            }
            try {
                this.segModel = new SelfieSegmentation({
                    locateFile: f => `https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation@0.1/${f}`,
                });
                this.segModel.setOptions({ modelSelection: 1 });
                this.segModel.onResults(r => this.drawWithBg(r));
                await this.segModel.initialize();
                this.segReady = true;
            } catch (e) { this.segFailed = true; }
        },

        startDrawLoop() {
            cancelAnimationFrame(this.animId);
            this.looping = true;
            const tick = async () => {
                if (!this.open || !this.looping || this.captured) return;
                const video = this.$refs.video, canvas = this.$refs.canvas;
                if (!video || !canvas || video.readyState < 2) { this.animId = requestAnimationFrame(tick); return; }
                canvas.width  = video.videoWidth  || 640;
                canvas.height = video.videoHeight || 480;
                if (this.segReady && this.segModel && this.bgMode !== 'none') {
                    try { await this.segModel.send({ image: video }); } catch {}
                } else {
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                }
                this.animId = requestAnimationFrame(tick);
            };
            tick();
        },

        drawWithBg(results) {
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            const ctx = canvas.getContext('2d'), w = canvas.width, h = canvas.height;
            ctx.clearRect(0, 0, w, h);
            if (this.bgMode === 'white') { ctx.fillStyle = '#f0f0f0'; ctx.fillRect(0, 0, w, h); }
            else if (this.bgMode === 'blue') { ctx.fillStyle = '#b8d4f0'; ctx.fillRect(0, 0, w, h); }
            else if (this.bgMode === 'blur') { ctx.filter = 'blur(20px)'; ctx.drawImage(results.image, 0, 0, w, h); ctx.filter = 'none'; }
            else { ctx.drawImage(results.image, 0, 0, w, h); }
            const off = new OffscreenCanvas(w, h), oc = off.getContext('2d');
            oc.drawImage(results.image, 0, 0, w, h);
            oc.globalCompositeOperation = 'destination-in';
            oc.drawImage(results.segmentationMask, 0, 0, w, h);
            ctx.drawImage(off, 0, 0);
        },

        async toggleCamera() {
            const reportedFacing = this.stream?.getVideoTracks()[0]?.getSettings()?.facingMode;
            if (reportedFacing) {
                this.facingMode = reportedFacing === 'user' ? 'environment' : 'user';
                this.selectedDeviceId = null;
                this.looping = false;
                cancelAnimationFrame(this.animId);
                await this.startStream();
            } else if (this.cameras.length > 1) {
                const idx  = this.cameras.findIndex(c => c.deviceId === this.selectedDeviceId);
                const next = (idx + 1) % this.cameras.length;
                await this.selectCamera(this.cameras[next].deviceId);
            } else {
                this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
                this.selectedDeviceId = null;
                this.looping = false;
                cancelAnimationFrame(this.animId);
                await this.startStream();
            }
        },

        capture() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            const w = canvas.width, h = canvas.height;
            const out = document.createElement('canvas');
            out.width = w; out.height = h;
            const oc = out.getContext('2d');
            if (this.facingMode === 'user') { oc.translate(w, 0); oc.scale(-1, 1); }
            oc.drawImage(canvas, 0, 0);
            this._capturedCanvas = out;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, w, h);
            ctx.drawImage(out, 0, 0);
            this.looping = false;
            cancelAnimationFrame(this.animId);
            this.captured = true;
        },

        retake() {
            this.captured = false;
            this._capturedCanvas = null;
            this.startDrawLoop();
        },

        async usePhoto() {
            const src = this._capturedCanvas || this.$refs.canvas;
            if (!src) return;
            src.toBlob(blob => {
                const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
                const dt   = new DataTransfer();
                dt.items.add(file);
                document.getElementById('admin-photo-input').files = dt.files;

                const img = document.getElementById('admin-photo-preview');
                const ph  = document.getElementById('admin-photo-placeholder');
                const lbl = document.getElementById('admin-photo-label');
                img.src = URL.createObjectURL(blob);
                img.classList.remove('hidden');
                if (ph)  ph.classList.add('hidden');
                if (lbl) lbl.textContent = '✓ Photo captured from camera';

                this.closeCamera();
            }, 'image/jpeg', 0.92);
        },

        closeCamera() {
            this.looping = false;
            cancelAnimationFrame(this.animId);
            if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
            this.open = false;
            this.captured = false;
        },
    };
}
</script>
@endpush
