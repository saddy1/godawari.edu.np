@extends('card.layouts.app')

@section('title', 'Issue Certificate')

@section('content')
<div class="space-y-5" x-data="certForm()" x-init="init()">
    <div class="overflow-hidden rounded-2xl bg-[#0b2415] text-white shadow-sm">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="flex items-center gap-4 min-w-0">
                <a href="{{ route('certificates.index') }}" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white hover:bg-white/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div class="min-w-0">
                    <p class="text-[11px] font-black uppercase tracking-[.22em] text-[#e2a024]">Student Module</p>
                    <h1 class="mt-1 text-2xl font-black leading-tight">Issue Certificate</h1>
                    <p class="mt-1 text-sm font-medium text-white/60">Select a student, confirm details, and generate a printable certificate.</p>
                </div>
            </div>
            <a href="{{ route('certificates.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/15 px-4 py-2.5 text-sm font-extrabold text-white/85 hover:bg-white/10">
                All Certificates
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('certificates.store') }}" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px] xl:items-start">
        @csrf
        <input type="hidden" name="member_id" x-model="memberId">

        <div class="space-y-5 min-w-0">
            <section class="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <div class="border-b border-emerald-100 bg-emerald-50/70 px-5 py-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest text-[#1a5632]">Step 1</p>
                            <h2 class="text-base font-black text-gray-950">Student Selection</h2>
                        </div>
                        @error('member_id') <p class="text-xs font-bold text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div class="space-y-4 min-w-0">
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4">
                            <div class="relative">
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-800">Realtime Student Search</label>
                                <input type="text" x-model="searchQuery" @input.debounce.300ms="runSearch()"
                                       @focus="if(searchQuery.length >= 1) runSearch()"
                                       @keydown.escape="closeDropdown()"
                                       @keydown.arrow-down.prevent="highlightNext()"
                                       @keydown.arrow-up.prevent="highlightPrev()"
                                       @keydown.enter.prevent="selectHighlighted()"
                                       placeholder="Search by name or roll number..."
                                       autocomplete="off"
                                       class="w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">

                                <div x-show="loading" class="absolute right-3 top-9">
                                    <svg class="h-4 w-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                </div>

                                <div x-show="open" x-cloak @click.outside="closeDropdown()"
                                     class="absolute z-50 left-0 right-0 mt-2 max-h-80 overflow-y-auto overflow-hidden rounded-xl border border-emerald-100 bg-white shadow-xl">
                                    <template x-if="results.length === 0 && !loading && searchQuery.length >= 1">
                                        <p class="px-4 py-3 text-sm font-semibold text-gray-400">No students found.</p>
                                    </template>
                                    <template x-for="(student, index) in results" :key="student.id">
                                        <div @click="selectStudent(student)"
                                             @mouseenter="highlightIndex = index"
                                             :class="highlightIndex === index ? 'bg-[#1a5632]/8' : 'hover:bg-gray-50'"
                                             class="flex cursor-pointer items-center gap-3 border-b border-emerald-50 px-4 py-3 transition-colors last:border-0">
                                            <img :src="student.photo_url || fallbackAvatar" x-on:error="$el.src = fallbackAvatar" :alt="student.name" class="h-10 w-10 shrink-0 rounded-xl object-cover ring-1 ring-gray-200">
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-black text-gray-900" x-text="student.name"></p>
                                                <p class="truncate text-xs font-semibold text-gray-500">
                                                    <span x-text="student.roll_number"></span>
                                                    <template x-if="student.stream"><span> · <span x-text="student.stream"></span></span></template>
                                                    <template x-if="student.section"><span> / <span x-text="student.section"></span></span></template>
                                                </p>
                                            </div>
                                            <svg x-show="memberId == student.id" class="h-4 w-4 shrink-0 text-[#1a5632]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-gray-500">Class Filter</label>
                                <select x-model="filterStream" @change="filterSection = ''; runSearch()"
                                        class="w-full rounded-xl border border-emerald-100 bg-white px-3 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                                    <option value="">All Classes</option>
                                    @foreach(\App\Models\Card\Student::where('member_type','student')->whereNotNull('stream')->distinct()->orderBy('stream')->pluck('stream') as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-gray-500">Section Filter</label>
                                <select x-model="filterSection" @change="runSearch()" :disabled="!filterStream"
                                        class="w-full rounded-xl border border-emerald-100 bg-white px-3 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15 disabled:opacity-50">
                                    <option value="">All Sections</option>
                                    @foreach(\App\Models\Card\Student::where('member_type','student')->whereNotNull('section')->distinct()->orderBy('section')->pluck('section') as $sec)
                                        <option value="{{ $sec }}">{{ $sec }}</option>
                                    @endforeach
                                </select>
                            </div>
                            </div>
                        </div>

                        <div class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50/60 p-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-600">Student Name <span class="text-red-500">*</span></label>
                                <input type="text" name="student_name" x-model="studentName" @input.debounce.300ms="syncNameSearch()"
                                       value="{{ old('student_name') }}"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15"
                                       placeholder="Full name as it appears on certificate">
                                @error('student_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-600">Gender</label>
                                <select name="gender" x-model="gender"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                                    <option value="male">Male (He / His / Son)</option>
                                    <option value="female">Female (She / Her / Daughter)</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-600">Parent / Guardian</label>
                                <input type="text" name="parent_name" x-model="parentName" value="{{ old('parent_name') }}"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15"
                                       placeholder="Father's / guardian's name">
                                @error('parent_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-600">Registration No.</label>
                                <input type="text" name="registration_no" x-model="registrationNo" value="{{ old('registration_no') }}"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15"
                                       placeholder="Registration number">
                                @error('registration_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-600">Address on Certificate</label>
                                <input type="text" name="address" x-model="address" value="{{ old('address') }}"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15"
                                       placeholder="Municipality, Ward, District">
                                @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                        <div x-show="memberId" x-cloak class="space-y-4">
                            <div class="flex items-center gap-3">
                                <img :src="selectedStudent.photo_url || fallbackAvatar" x-on:error="$el.src = fallbackAvatar" class="h-14 w-14 shrink-0 rounded-2xl object-cover ring-1 ring-[#1a5632]/20">
                                <div class="min-w-0">
                                    <p class="truncate text-base font-black text-[#0b2415]" x-text="selectedStudent.name"></p>
                                    <p class="truncate text-xs font-bold text-emerald-700">
                                        <span x-text="selectedStudent.roll_number"></span>
                                        <template x-if="selectedStudent.stream"><span> · <span x-text="selectedStudent.stream"></span></span></template>
                                        <template x-if="selectedStudent.section"><span> / <span x-text="selectedStudent.section"></span></span></template>
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="clearStudent()" class="w-full rounded-xl border border-red-200 bg-white px-3 py-2.5 text-xs font-black text-red-600 hover:bg-red-50">
                                Change Selected Student
                            </button>
                        </div>
                        <div x-show="!memberId" class="flex min-h-[116px] flex-col justify-center rounded-xl border border-dashed border-gray-300 bg-white p-4 text-center">
                            <p class="text-sm font-black text-gray-800">No student selected</p>
                            <p class="mt-1 text-xs font-semibold text-gray-400">Search by name, roll number, class, or section.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-sky-100 bg-white shadow-sm">
                <div class="border-b border-sky-100 bg-sky-50/70 px-5 py-4">
                    <p class="text-[11px] font-black uppercase tracking-widest text-sky-700">Step 2</p>
                    <h2 class="text-base font-black text-gray-950">Certificate Details</h2>
                </div>

                <div class="grid gap-4 p-5 lg:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-gray-500">Certificate Type <span class="text-red-500">*</span></label>
                        <select name="certificate_type"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                            <option value="character" @selected(old('certificate_type', 'character') === 'character')>Character / Transfer Certificate</option>
                            <option value="provisional" @selected(old('certificate_type') === 'provisional')>Provisional Certificate</option>
                        </select>
                        @error('certificate_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-gray-500">Exam Name <span class="text-red-500">*</span></label>
                        <input type="text" name="exam_name" value="{{ old('exam_name') }}" list="exam-suggestions"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15"
                               placeholder="e.g. SEE, Grade 10, +2 NEB">
                        <datalist id="exam-suggestions">
                            <option value="SEE (Secondary Education Examination)">
                            <option value="SLC (School Leaving Certificate)">
                            <option value="Grade 10 Internal Examination">
                            <option value="Grade 8 Examination">
                            <option value="Grade 5 Examination">
                            <option value="+2 (NEB)">
                        </datalist>
                        @error('exam_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                @php
                    $oldDivGpa   = old('division_gpa', '');
                    $isGpaMode   = str_starts_with($oldDivGpa, 'GPA ');
                    $initMode    = $isGpaMode ? 'gpa' : 'division';
                    $initDiv     = $isGpaMode ? '' : $oldDivGpa;
                    $initGpaNum  = $isGpaMode ? ltrim(substr($oldDivGpa, 4)) : '';
                @endphp
                    <div x-data="{
                        mode:   '{{ $initMode }}',
                        div:    '{{ $initDiv }}',
                        gpaNum: '{{ $initGpaNum }}',
                        get value() {
                            if (this.mode === 'gpa') return this.gpaNum ? 'GPA ' + this.gpaNum : '';
                            return this.div;
                        }
                     }" class="rounded-2xl border border-sky-100 bg-sky-50/50 p-4">
                    <label class="mb-3 block text-xs font-black uppercase tracking-wide text-sky-700">GPA / Division</label>

                    <div class="mb-3 flex w-fit gap-1 rounded-xl border border-sky-100 bg-white p-1">
                        <button type="button" @click="mode = 'division'"
                                :class="mode === 'division'
                                    ? 'bg-white shadow-sm text-gray-900'
                                    : 'text-gray-500 hover:text-gray-700'"
                                class="rounded-lg px-4 py-1.5 text-xs font-black transition-all">
                            Division
                        </button>
                        <button type="button" @click="mode = 'gpa'"
                                :class="mode === 'gpa'
                                    ? 'bg-white shadow-sm text-gray-900'
                                    : 'text-gray-500 hover:text-gray-700'"
                                class="rounded-lg px-4 py-1.5 text-xs font-black transition-all">
                            GPA
                        </button>
                    </div>

                    <div x-show="mode === 'division'" class="flex flex-wrap gap-2">
                        @foreach(['Distinction', 'First Division', 'Second Division', 'Third Division'] as $d)
                        <button type="button"
                                @click="div = div === '{{ $d }}' ? '' : '{{ $d }}'"
                                :class="div === '{{ $d }}'
                                    ? 'bg-[#1a5632] text-white border-[#1a5632]'
                                    : 'bg-white text-gray-700 border-gray-300 hover:border-[#1a5632] hover:text-[#1a5632]'"
                                class="rounded-xl border px-3 py-2 text-xs font-black transition-all">
                            {{ $d }}
                        </button>
                        @endforeach
                    </div>

                    <div x-show="mode === 'gpa'">
                        <div class="flex items-center rounded-xl border border-gray-300 bg-white overflow-hidden w-44 focus-within:border-[#1a5632] focus-within:ring-2 focus-within:ring-[#1a5632]/15">
                            <span class="px-3 py-3 text-sm font-extrabold text-gray-400 border-r border-gray-200 bg-gray-50 select-none">GPA</span>
                            <input type="number" x-model="gpaNum"
                                   min="0" max="4" step="0.01"
                                   placeholder="0.00–4.00"
                                   class="w-full px-3 py-3 text-sm font-semibold outline-none bg-white">
                        </div>
                    </div>

                    <p class="mt-2 text-xs font-semibold text-gray-400" x-show="value" x-cloak>
                        Will print: <span class="text-gray-700 font-extrabold" x-text="value"></span>
                    </p>
                    <input type="hidden" name="division_gpa" :value="value">
                    @error('division_gpa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                @php
                    $oldBs = old('pass_year_bs', '');
                    $oldAd = old('pass_year_ad', '');
                    // Parse stored BS value like "2081" or "2081 Baisakh"
                    $bsMonths = ['Baisakh','Jestha','Ashadh','Shrawan','Bhadra','Ashwin','Kartik','Mangsir','Poush','Magh','Falgun','Chaitra'];
                    $initBsYear  = '';
                    $initBsMonth = '0';
                    if ($oldBs) {
                        foreach ($bsMonths as $i => $mn) {
                            if (str_contains($oldBs, $mn)) {
                                $initBsMonth = (string)($i + 1);
                                $initBsYear  = trim(str_replace($mn, '', $oldBs));
                                break;
                            }
                        }
                        if (!$initBsYear) $initBsYear = $oldBs;
                    }
                @endphp
                <div
                     x-data="{
                        bsYear:  '{{ $initBsYear }}',
                        bsMonth: '{{ $initBsMonth }}',
                        adYear:  '{{ $oldAd }}',
                        months: ['Baisakh','Jestha','Ashadh','Shrawan','Bhadra','Ashwin','Kartik','Mangsir','Poush','Magh','Falgun','Chaitra'],
                        get bsDisplay() {
                            if (!this.bsYear) return '';
                            const m = parseInt(this.bsMonth);
                            return m > 0 ? this.months[m-1] + ' ' + this.bsYear : this.bsYear;
                        },
                        get computedAD() {
                            if (!this.bsYear) return '';
                            const m = parseInt(this.bsMonth) || 0;
                            return String(parseInt(this.bsYear) - (m >= 10 ? 56 : 57));
                        },
                        onBsChange() { this.adYear = this.computedAD; },
                        onAdChange() {
                            if (!this.adYear) return;
                            const m = parseInt(this.bsMonth) || 0;
                            this.bsYear = String(parseInt(this.adYear) + (m >= 10 ? 56 : 57));
                        }
                     }" class="rounded-2xl border border-sky-100 bg-sky-50/50 p-4">
                    <label class="mb-3 block text-xs font-black uppercase tracking-wide text-sky-700">Pass Year</label>

                    <div class="grid gap-3">
                        <div class="space-y-2 rounded-xl border border-sky-100 bg-white p-3">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Bikram Sambat (B.S.)</p>
                            <div class="flex gap-2">
                                <select x-model="bsYear" @change="onBsChange()"
                                        class="flex-1 rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                                    <option value="">Year</option>
                                    @for($y = 2085; $y >= 2055; $y--)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endfor
                                </select>
                                <select x-model="bsMonth" @change="onBsChange()"
                                        class="flex-1 rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                                    <option value="0">Month (opt.)</option>
                                    @foreach(['Baisakh','Jestha','Ashadh','Shrawan','Bhadra','Ashwin','Kartik','Mangsir','Poush','Magh','Falgun','Chaitra'] as $i => $mn)
                                        <option value="{{ $i + 1 }}">{{ $mn }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-xs text-gray-400 font-medium h-4"
                               x-show="bsDisplay" x-cloak>
                                Prints as: <span class="font-extrabold text-gray-700" x-text="bsDisplay"></span>
                            </p>
                        </div>

                        <div class="space-y-2 rounded-xl border border-sky-100 bg-white p-3">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Anno Domini (A.D.)</p>
                            <div class="flex gap-2">
                                <select x-model="adYear" @change="onAdChange()"
                                        class="flex-1 rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                                    <option value="">Year</option>
                                    @for($y = 2029; $y >= 1998; $y--)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <p class="text-xs font-medium h-4"
                               x-show="bsYear && !adYear" x-cloak>
                                <span class="text-amber-500">← will auto-fill from B.S.</span>
                            </p>
                            <p class="text-xs font-medium h-4"
                               x-show="adYear" x-cloak>
                                <span class="text-emerald-600 font-extrabold" x-text="adYear + ' A.D.'"></span>
                            </p>
                        </div>
                    </div>

                    <input type="hidden" name="pass_year_bs" :value="bsDisplay">
                    <input type="hidden" name="pass_year_ad" :value="adYear">
                    @error('pass_year_bs') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('pass_year_ad')  <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-gray-500">Character Description</label>
                    <select name="character_description"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">— Select character —</option>
                        @foreach(['Excellent', 'Very Good', 'Good', 'Satisfactory', 'Needs Improvement'] as $opt)
                            <option value="{{ $opt }}" @selected(old('character_description') === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('character_description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-gray-500">Symbol No.</label>
                    <input type="text" name="symbol_no" value="{{ old('symbol_no') }}"
                           class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15"
                           placeholder="Exam symbol number">
                    @error('symbol_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase tracking-wide text-gray-500">Issue Date <span class="text-red-500">*</span></label>
                    <input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}"
                           class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    @error('issue_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                </div>
            </section>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-4">
            <div class="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <div class="border-b border-emerald-100 bg-emerald-50 px-5 py-4">
                    <p class="text-[11px] font-black uppercase tracking-widest text-emerald-700">Certificate Preview</p>
                    <h2 class="mt-1 text-lg font-black text-gray-950">Ready to Generate</h2>
                </div>
                <div class="space-y-4 p-5">
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Student</p>
                        <p class="mt-1 truncate text-base font-black text-gray-950" x-text="studentName || 'Not selected'"></p>
                        <p class="mt-1 truncate text-xs font-bold text-gray-500" x-text="address || 'Address will appear here'"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Parent</p>
                            <p class="mt-1 truncate font-extrabold text-gray-800" x-text="parentName || '-'"></p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Reg. No.</p>
                            <p class="mt-1 truncate font-extrabold text-gray-800" x-text="registrationNo || '-'"></p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-semibold leading-5 text-amber-800">
                        The certificate opens in print format after saving. Review the student name, parent name, address, and exam year before generating.
                    </div>

                    <div class="grid gap-3">
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-[#237042]">
                            Generate &amp; Print Certificate
                        </button>
                        <a href="{{ route('certificates.index') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 px-5 py-3 text-sm font-black text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </aside>
    </form>
</div>
@endsection

@php
    $initMemberId      = old('member_id', $member?->id ?? '');
    $initStudentName   = old('student_name', $member?->full_name ?? '');
    $initGender        = old('gender', strtolower($member?->gender ?? 'male'));
    $initParentName    = old('parent_name', $member ? ($member->father_name ?: $member->guardian_name ?: '') : '');
    $initAddress       = old('address', $member ? implode(', ', array_filter([
                             $member->permanent_municipality ?? null,
                             ($member->permanent_ward ?? null) ? 'Ward-'.($member->permanent_ward) : null,
                             $member->permanent_district ?? null,
                         ])) : '');
    $initRegNo         = old('registration_no', $member?->registration_no ?? '');
    $initSelectedStudent = $member ? [
        'id'          => $member->id,
        'name'        => $member->full_name ?? '',
        'roll_number' => $member->roll_number ?? '',
        'stream'      => $member->stream ?? '',
        'section'     => $member->section ?? '',
        'photo_url'   => $member->photo_url ?? '',
    ] : null;
@endphp

@push('scripts')
<script>
function certForm() {
    return {
        // ── search state ──────────────────────────────────────────────────
        searchQuery:    '',
        filterStream:   '',
        filterSection:  '',
        results:        [],
        loading:        false,
        open:           false,
        highlightIndex: -1,

        // ── selected student ──────────────────────────────────────────────
        memberId:        @json($initMemberId),
        selectedStudent: @json($initSelectedStudent ?? (object)[]),
        fallbackAvatar:  @json(asset('images/default-avatar.svg')),

        // ── certificate detail fields ─────────────────────────────────────
        studentName:    @json($initStudentName),
        gender:         @json($initGender),
        parentName:     @json($initParentName),
        address:        @json($initAddress),
        registrationNo: @json($initRegNo),

        // ── search ────────────────────────────────────────────────────────
        async runSearch() {
            if (this.searchQuery.length < 1 && !this.filterStream && !this.filterSection) {
                this.results = []; this.open = false; return;
            }
            this.loading = true; this.open = true; this.highlightIndex = -1;
            const params = new URLSearchParams({
                q:       this.searchQuery,
                stream:  this.filterStream,
                section: this.filterSection,
            });
            try {
                const res  = await fetch(`{{ route('certificates.students.search') }}?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.results = data.results || [];
            } catch { this.results = []; }
            finally { this.loading = false; }
        },

        syncNameSearch() {
            if (this.memberId) return;
            this.searchQuery = this.studentName || '';
            this.runSearch();
        },

        selectStudent(student) {
            this.memberId        = student.id;
            this.selectedStudent = student;
            this.studentName     = student.name;
            const g = (student.gender || 'male').toLowerCase();
            this.gender          = g === 'female' ? 'female' : 'male';
            this.parentName      = student.father_name || student.guardian_name || '';
            this.address         = student.address || '';
            this.registrationNo  = student.registration_no || '';
            this.closeDropdown();
        },

        clearStudent() {
            this.memberId        = '';
            this.selectedStudent = {};
            this.studentName     = '';
            this.gender          = 'male';
            this.parentName      = '';
            this.address         = '';
            this.registrationNo  = '';
            this.searchQuery     = '';
            this.results         = [];
        },

        closeDropdown() { this.open = false; this.highlightIndex = -1; },

        highlightNext() {
            if (!this.open) { this.open = true; return; }
            this.highlightIndex = Math.min(this.highlightIndex + 1, this.results.length - 1);
        },
        highlightPrev() {
            this.highlightIndex = Math.max(this.highlightIndex - 1, 0);
        },
        selectHighlighted() {
            if (this.highlightIndex >= 0 && this.results[this.highlightIndex]) {
                this.selectStudent(this.results[this.highlightIndex]);
            }
        },

        init() {
            // If validation failed and member_id is in old(), pre-fill search label
            if (this.memberId && this.selectedStudent?.name) {
                this.searchQuery = this.selectedStudent.name;
            }
        },
    };
}
</script>
@endpush
