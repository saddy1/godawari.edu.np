@extends('teaching_learning.layouts.app')

@section('title', 'Faculty Subjects')

@section('content')
@php
    $canCreate = auth()->user()?->canAccess('teaching-learning.subjects.create');
    $canDelete = auth()->user()?->canAccess('teaching-learning.subjects.delete');
    $groupedOfferings = $offerings->groupBy(function ($offering) {
        $parts = [];
        if ($offering->semester) $parts[] = "Semester {$offering->semester}";
        if ($offering->year_level) $parts[] = "Year {$offering->year_level}";
        if ($offering->group_name) $parts[] = $offering->group_name;
        return $parts ? implode(' · ', $parts) : 'Whole faculty / class';
    });
    $sectionGroupMemberships = $selectedDept
        ? $selectedDept->sections->filter(fn ($section) => filled($section->group_name))
            ->groupBy('group_name')->map(fn ($sections) => $sections->pluck('id')->map(fn ($id) => (string) $id)->values())
        : collect();
@endphp

<div class="space-y-4" x-data="facultySubjectsPage()">

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
            <p class="font-extrabold">Please correct the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-[#1a5632]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M5 7v12h14V7M9 7V5a3 3 0 016 0v2m-6 5h6"/></svg>
                </span>
                <div><p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Step 1</p><h2 class="text-base font-extrabold text-gray-950">Choose organization and faculty</h2></div>
            </div>
        </div>
        <div class="space-y-3 p-4 sm:px-5">
            <div class="flex gap-2 overflow-x-auto pb-1">
                @forelse($organizations as $organization)
                    <a href="{{ route('admin.teaching-learning.subjects.index', ['org' => $organization->id]) }}"
                       class="group min-w-48 rounded-xl border px-4 py-3 transition {{ $orgId === $organization->id ? 'border-[#1a5632] bg-emerald-50 ring-2 ring-[#1a5632]/10' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50' }}">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0"><p class="truncate text-sm font-extrabold {{ $orgId === $organization->id ? 'text-[#1a5632]' : 'text-gray-800' }}">{{ $organization->name }}</p><p class="mt-0.5 text-xs font-semibold text-gray-400">{{ $organization->departments->count() }} {{ Str::plural('faculty', $organization->departments->count()) }}</p></div>
                            @if($orgId === $organization->id)
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#1a5632] text-white"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="text-sm font-semibold text-gray-400">No organizations are available.</p>
                @endforelse
            </div>
            @if($selectedOrg)
                <div class="border-t border-gray-100 pt-4">
                    <p class="mb-2 text-[11px] font-extrabold uppercase tracking-widest text-gray-400">Faculty / class</p>
                    <div class="flex flex-wrap gap-2">
                        @forelse($selectedOrg->departments as $department)
                            <a href="{{ route('admin.teaching-learning.subjects.index', ['org' => $selectedOrg->id, 'dept' => $department->id]) }}"
                               class="rounded-xl border px-4 py-2.5 text-sm font-extrabold transition {{ $deptId === $department->id ? 'border-[#1a5632] bg-[#1a5632] text-white shadow-sm' : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-[#1a5632]/30 hover:bg-emerald-50 hover:text-[#1a5632]' }}">{{ $department->name }}</a>
                        @empty
                            <p class="text-sm font-semibold text-gray-400">This organization has no faculties/classes yet.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </section>

    <div>
        <main class="min-w-0">
            @if($selectedDept)
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#1a5632] text-white shadow-sm"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m-6-9.5V16l6 4 6-4v-5.5"/></svg></div>
                            <div class="min-w-0"><p class="truncate text-[10px] font-extrabold uppercase tracking-widest text-[#1a5632]">{{ $selectedDept->organization?->name }}</p><h2 class="truncate text-lg font-black text-gray-950">{{ $selectedDept->name }}</h2></div>
                        </div>
                        <div class="flex flex-wrap gap-1.5 text-[11px] font-extrabold"><span class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-gray-500">{{ $selectedDept->sections->count() }} {{ Str::plural('section', $selectedDept->sections->count()) }}</span><span class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-blue-700">{{ ['semester' => 'Semester', 'year' => 'Year', 'none' => 'Class-based'][$selectedDept->academic_system] ?? 'Class-based' }}</span><span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-emerald-700">{{ $offerings->count() }} allocated</span></div>
                    </div>

                    @if($canCreate && $selectedDept->sections->isNotEmpty())
                        <div class="border-b border-gray-100 bg-emerald-50/30 p-3 sm:p-4" x-data="sectionGroupManager()">
                            <form method="POST" action="{{ route('admin.teaching-learning.section-groups.update') }}" class="space-y-3" @submit="if ($event.submitter?.value === 'save' && !selected.length) { $event.preventDefault(); alert('Select at least one section.'); }">
                                @csrf @method('PUT')
                                <input type="hidden" name="department_id" value="{{ $selectedDept->id }}">
                                <div class="flex flex-col gap-2 lg:flex-row lg:items-end">
                                    <div class="min-w-56 lg:w-64">
                                        <label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-emerald-800">Section group name</label>
                                        <input name="group_name" x-model="groupName" @input.debounce.150ms="loadExistingGroup()" list="section-group-list" required placeholder="e.g. Bio" class="w-full rounded-lg border-emerald-200 bg-white px-3 py-2 text-sm font-extrabold focus:border-emerald-600 focus:ring-emerald-600">
                                        <datalist id="section-group-list">@foreach($deptGroups as $group)<option value="{{ $group }}"></option>@endforeach</datalist>
                                    </div>
                                    <div class="flex min-w-0 flex-1 flex-wrap gap-1.5">
                                        @foreach($deptGroups as $group)
                                            <button type="button" @click="chooseGroup(@js($group))" :class="groupName === @js($group) ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-emerald-200 bg-white text-emerald-800'" class="rounded-lg border px-2.5 py-2 text-[10px] font-black transition">{{ $group }}</button>
                                        @endforeach
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" name="intent" value="save" :disabled="!groupName.trim() || !selected.length" class="rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-40">Save group</button>
                                        <button type="submit" name="intent" value="remove" x-show="isExisting" onclick="return confirm('Remove this section group? Subject allocations using its name will remain available.')" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-extrabold text-red-600">Remove</button>
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-1.5 flex items-center justify-between gap-3"><p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Choose all sections in this group</p><p class="text-[10px] font-bold text-emerald-700"><span x-text="selected.length"></span> selected</p></div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($selectedDept->sections as $section)
                                            <label :class="selected.includes(@js((string) $section->id)) ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-emerald-300'" class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-xs font-extrabold transition">
                                                <input type="checkbox" name="section_ids[]" value="{{ $section->id }}" x-model="selected" class="sr-only">
                                                <span :class="selected.includes(@js((string) $section->id)) ? 'border-white bg-white text-emerald-700' : 'border-gray-300 bg-white text-transparent'" class="flex h-4 w-4 items-center justify-center rounded border-2 transition"><svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                                                {{ $section->name }}
                                                @if($section->group_name)<span class="text-[9px] opacity-70">{{ $section->group_name }}</span>@endif
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-[10px] font-semibold text-gray-400">Students follow their current section automatically. Moving a student to another class or section recalculates compulsory subjects; no student list needs to be maintained here.</p>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if($canCreate)
                        <div class="border-b border-gray-100 p-3 sm:p-4">
                            <div class="mb-2 flex items-center justify-between gap-3"><div><h3 class="text-sm font-extrabold text-gray-950">Allocate a subject</h3><p class="text-[10px] font-semibold text-gray-400">Enter a code and assign it to this faculty in one row.</p></div><span class="hidden rounded-md bg-gray-100 px-2 py-1 text-[9px] font-extrabold uppercase tracking-wider text-gray-400 sm:inline">Existing codes auto-fill</span></div>
                            <form method="POST" action="{{ route('admin.teaching-learning.subject-offerings.store') }}" class="rounded-xl border border-gray-200 bg-gray-50/70 p-3">
                                @csrf
                                <input type="hidden" name="department_id" value="{{ $selectedDept->id }}">

                                <div class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-[minmax(150px,1fr)_135px_minmax(160px,1fr)_145px_minmax(145px,0.8fr)_auto] xl:items-end">
                                    <div>
                                        <label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Subject code <span class="text-red-500">*</span></label>
                                        <input name="subject_code" x-model="subjectCode" @input="normalizeCode()" list="subject-code-list" required autocomplete="off" placeholder="Type code, e.g. MTH101"
                                               class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-extrabold uppercase outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/10">
                                        <datalist id="subject-code-list">
                                            @foreach($subjects as $subject)<option value="{{ $subject->code }}">{{ $subject->name }}</option>@endforeach
                                        </datalist>
                                    </div>

                                    @if($selectedDept->academic_system === 'semester')
                                        <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Semester</label><select name="semester" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/10"><option value="">All semesters</option>@for($semester = 1; $semester <= 8; $semester++)<option value="{{ $semester }}" @selected((int) old('semester') === $semester)>Semester {{ $semester }}</option>@endfor</select></div>
                                    @elseif($selectedDept->academic_system === 'year')
                                        <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Study year</label><select name="year_level" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/10"><option value="">All years</option>@for($year = 1; $year <= 4; $year++)<option value="{{ $year }}" @selected((int) old('year_level') === $year)>Year {{ $year }}</option>@endfor</select></div>
                                    @else
                                        <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Period</label><div class="rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-600">Class-based</div></div>
                                    @endif

                                    <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Class group</label><select name="group_name" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/10"><option value="">Whole faculty / class</option>@foreach($deptGroups as $group)<option value="{{ $group }}" @selected(old('group_name') === $group)>{{ $group }}</option>@endforeach</select></div>

                                    <div>
                                        <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Subject type</span>
                                        <label :class="allocationElective ? 'border-purple-400 bg-purple-50' : 'border-gray-300 bg-white hover:border-gray-400'" class="flex h-[38px] cursor-pointer items-center gap-2 rounded-lg border px-3 transition-all">
                                        <input type="checkbox" name="is_elective" value="1" x-model="allocationElective" class="peer sr-only">
                                        <span :class="allocationElective ? 'border-purple-600 bg-purple-600 text-white' : 'border-gray-300 bg-white text-transparent'" class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border-2 transition-all">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                        <span :class="allocationElective ? 'text-purple-800' : 'text-gray-700'" class="truncate text-xs font-extrabold" x-text="allocationElective ? 'Elective' : 'Fixed'"></span>
                                    </label>
                                    </div>

                                    <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Elective group</label><input type="text" name="elective_group" value="{{ old('elective_group') }}" placeholder="e.g. Elective I" :disabled="!allocationElective" :class="allocationElective ? 'border-purple-400 bg-purple-50 text-purple-950' : 'border-gray-300 bg-gray-100 text-gray-400'" class="w-full rounded-lg px-3 py-2 text-sm font-semibold outline-none transition focus:border-purple-500 focus:ring-2 focus:ring-purple-200"></div>

                                    <button type="submit" :disabled="!subjectCode" class="inline-flex h-[38px] items-center justify-center gap-1.5 whitespace-nowrap rounded-lg bg-[#1a5632] px-4 text-xs font-extrabold text-white transition hover:bg-[#0b2415] disabled:cursor-not-allowed disabled:opacity-40"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m6-6H6"/></svg><span x-text="existingSubject ? 'Assign subject' : 'Create & assign'"></span></button>
                                </div>

                                <div x-show="existingSubject" x-cloak class="mt-2.5 flex flex-wrap items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs">
                                    <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span class="font-extrabold text-emerald-950" x-text="existingSubject?.name"></span>
                                    <span class="rounded bg-white px-2 py-0.5 text-[10px] font-bold text-emerald-700" x-show="existingSubject?.credit_hours" x-text="`${existingSubject?.credit_hours} credits`"></span>
                                    <span class="rounded bg-white px-2 py-0.5 text-[10px] font-bold" :class="existingSubject?.has_practical ? 'text-emerald-700' : 'text-gray-500'" x-text="existingSubject?.has_practical ? `Practical: ${existingSubject?.practical_code || subjectCode}` : 'Theory only'"></span>
                                </div>

                                <div x-show="isNewSubject" x-cloak class="mt-2.5 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                    <div class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-[minmax(180px,1fr)_110px_165px_180px] lg:items-end">
                                        <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-amber-700">New subject name <span class="text-red-500">*</span></label><input name="subject_name" value="{{ old('subject_name') }}" :disabled="!isNewSubject" :required="isNewSubject" placeholder="Full subject name" class="w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-semibold outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-200"></div>
                                        <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-amber-700">Credits</label><input type="number" name="credit_hours" value="{{ old('credit_hours') }}" :disabled="!isNewSubject" min="0" max="255" placeholder="e.g. 3" class="w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-semibold outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-200"></div>
                                        <label :class="hasPractical ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-100' : 'border-amber-300 bg-white hover:border-emerald-300'" class="flex h-[38px] cursor-pointer items-center gap-2 rounded-lg border px-3 transition-all">
                                            <input type="checkbox" name="has_practical" value="1" x-model="hasPractical" :disabled="!isNewSubject" class="sr-only">
                                            <span :class="hasPractical ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-gray-300 bg-white text-transparent'" class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border-2 transition-all">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                            <span :class="hasPractical ? 'text-emerald-800' : 'text-gray-700'" class="text-xs font-extrabold" x-text="hasPractical ? 'Practical selected' : 'Has practical'"></span>
                                        </label>
                                        <div x-show="hasPractical" x-cloak>
                                            <label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-emerald-700">Practical code</label>
                                            <input name="practical_code" x-model="practicalCode" :disabled="!isNewSubject || !hasPractical" :placeholder="subjectCode || 'Same as subject code'" class="w-full rounded-lg border border-emerald-300 bg-white px-3 py-2 text-sm font-extrabold uppercase outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200">
                                        </div>
                                    </div>
                                    <p x-show="hasPractical" class="mt-1.5 text-[10px] font-semibold text-emerald-700">Leave practical code blank to use the same code as the main subject.</p>
                                </div>
                            </form>
                        </div>
                    @endif

                    <div class="p-4">
                        <div class="mb-3 flex items-center justify-between gap-3"><div><p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Current curriculum</p><h3 class="text-base font-black text-gray-950">Allocated subjects</h3></div>@if($offerings->isNotEmpty())<span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-extrabold text-gray-500">{{ $groupedOfferings->count() }} {{ Str::plural('group', $groupedOfferings->count()) }}</span>@endif</div>
                        <div class="space-y-4">
                            @forelse($groupedOfferings as $groupLabel => $groupOfferings)
                                <section class="overflow-hidden rounded-2xl border border-gray-200">
                                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 bg-gray-50 px-4 py-3 sm:px-5"><div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-[#1a5632]"></span><h4 class="text-sm font-extrabold text-gray-800">{{ $groupLabel }}</h4></div><span class="text-xs font-bold text-gray-400">{{ $groupOfferings->count() }} {{ Str::plural('subject', $groupOfferings->count()) }}</span></div>
                                    <div class="divide-y divide-gray-100">
                                        @foreach($groupOfferings as $offering)
                                            <div x-data="{ edit: @js((int) old('editing_offering') === $offering->id), practical: @js((int) old('editing_offering') === $offering->id ? (bool) old('has_practical') : (bool) $offering->subject->has_practical), elective: @js((int) old('editing_offering') === $offering->id ? (bool) old('is_elective') : (bool) $offering->is_elective) }" class="group px-4 py-3 sm:px-5">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="flex min-w-0 items-center gap-3"><div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-xs font-black text-[#1a5632]">{{ strtoupper(substr($offering->subject->code, 0, 2)) }}</div><div class="min-w-0"><p class="truncate text-sm font-extrabold text-gray-900">{{ $offering->subject->name }}</p><div class="mt-1 flex flex-wrap items-center gap-1.5"><span class="rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-extrabold text-gray-500">{{ $offering->subject->code }}</span>@if($offering->subject->has_practical)<span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700">Practical: {{ $offering->subject->practical_code ?: $offering->subject->code }}</span>@endif @if($offering->is_elective)<span class="rounded-md bg-purple-50 px-2 py-0.5 text-[10px] font-extrabold text-purple-700">Elective{{ $offering->elective_group ? ': '.$offering->elective_group : '' }}</span>@else<span class="rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-extrabold text-blue-700">Fixed</span>@endif</div></div></div>
                                                    <div class="flex items-center gap-1.5 self-end sm:self-auto">
                                                        @if($canCreate)<button type="button" @click="edit = !edit" :class="edit ? 'border-[#1a5632] bg-emerald-50 text-[#1a5632]' : 'border-gray-200 bg-white text-gray-600'" class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-extrabold transition"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7m-1.5-8.5a2.121 2.121 0 013 3L12 17l-4 1 1-4 9.5-9.5z"/></svg><span x-text="edit ? 'Close' : 'Edit'"></span></button>@endif
                                                        @if($canDelete)<form method="POST" action="{{ route('admin.teaching-learning.subject-offerings.destroy', $offering) }}" onsubmit="return confirm('Remove this subject from the selected faculty?')">@csrf @method('DELETE')<button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-xs font-extrabold text-red-600 transition hover:border-red-200 hover:bg-red-100"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>Remove</button></form>@endif
                                                    </div>
                                                </div>

                                                @if($canCreate)
                                                    <form x-show="edit" x-cloak x-transition method="POST" action="{{ route('admin.teaching-learning.subject-offerings.update', $offering) }}" class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-3">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="editing_offering" value="{{ $offering->id }}">
                                                        <div class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-[minmax(180px,1fr)_130px_90px_150px_170px] xl:items-end">
                                                            <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Subject name</label><input name="subject_name" value="{{ $offering->subject->name }}" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs font-semibold focus:border-[#1a5632] focus:ring-[#1a5632]"></div>
                                                            <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Theory code</label><input name="subject_code" value="{{ $offering->subject->code }}" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs font-extrabold uppercase focus:border-[#1a5632] focus:ring-[#1a5632]"></div>
                                                            <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Credits</label><input type="number" name="credit_hours" value="{{ $offering->subject->credit_hours }}" min="0" max="255" class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs font-semibold focus:border-[#1a5632] focus:ring-[#1a5632]"></div>
                                                            <div><span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Practical</span><label :class="practical ? 'border-emerald-500 bg-emerald-100 text-emerald-800' : 'border-gray-200 bg-white text-gray-600'" class="flex h-[34px] cursor-pointer items-center gap-2 rounded-lg border px-3"><input type="checkbox" name="has_practical" value="1" x-model="practical" class="sr-only"><span :class="practical ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-gray-300 text-transparent'" class="flex h-4 w-4 items-center justify-center rounded border-2"><svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span><span class="text-[11px] font-bold" x-text="practical ? 'Yes' : 'No'"></span></label></div>
                                                            <div x-show="practical"><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-emerald-700">Practical code</label><input name="practical_code" value="{{ $offering->subject->practical_code }}" :disabled="!practical" placeholder="Same as theory" class="w-full rounded-lg border-emerald-200 px-3 py-2 text-xs font-extrabold uppercase focus:border-emerald-500 focus:ring-emerald-500"></div>
                                                        </div>

                                                        <div class="mt-2.5 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-[140px_minmax(170px,1fr)_150px_minmax(150px,1fr)_auto_auto] xl:items-end">
                                                            @if($selectedDept->academic_system === 'semester')
                                                                <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Semester</label><select name="semester" class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs font-semibold"> <option value="">All semesters</option>@for($semester = 1; $semester <= 8; $semester++)<option value="{{ $semester }}" @selected((int)$offering->semester === $semester)>Semester {{ $semester }}</option>@endfor</select></div>
                                                            @elseif($selectedDept->academic_system === 'year')
                                                                <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Study year</label><select name="year_level" class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs font-semibold"><option value="">All years</option>@for($year = 1; $year <= 4; $year++)<option value="{{ $year }}" @selected((int)$offering->year_level === $year)>Year {{ $year }}</option>@endfor</select></div>
                                                            @else
                                                                <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Period</label><div class="rounded-lg border bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-500">Class-based</div></div>
                                                            @endif
                                                            <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Class group</label><select name="group_name" class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs font-semibold"><option value="">Whole faculty / class</option>@foreach($deptGroups as $group)<option value="{{ $group }}" @selected($offering->group_name === $group)>{{ $group }}</option>@endforeach</select></div>
                                                            <div><span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Subject type</span><label :class="elective ? 'border-purple-500 bg-purple-50 text-purple-800' : 'border-gray-200 bg-white text-gray-600'" class="flex h-[34px] cursor-pointer items-center gap-2 rounded-lg border px-3"><input type="checkbox" name="is_elective" value="1" x-model="elective" class="sr-only"><span :class="elective ? 'border-purple-600 bg-purple-600 text-white' : 'border-gray-300 text-transparent'" class="flex h-4 w-4 items-center justify-center rounded border-2"><svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span><span class="text-[11px] font-bold" x-text="elective ? 'Elective' : 'Fixed'"></span></label></div>
                                                            <div><label class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Elective group</label><input name="elective_group" value="{{ $offering->elective_group }}" :disabled="!elective" placeholder="e.g. Elective I" class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs font-semibold disabled:bg-gray-100 disabled:text-gray-400"></div>
                                                            <button type="button" @click="edit = false" class="h-[34px] rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold text-gray-600">Cancel</button>
                                                            <button type="submit" class="h-[34px] whitespace-nowrap rounded-lg bg-[#1a5632] px-4 text-xs font-extrabold text-white">Save changes</button>
                                                        </div>
                                                        <p class="mt-2 text-[9px] font-semibold text-gray-400">Subject name, code, credits, and practical settings update the shared subject master everywhere it is allocated.</p>
                                                    </form>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @empty
                                <div class="rounded-xl border-2 border-dashed border-gray-200 px-5 py-8 text-center"><div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-400"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18.523 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div><h4 class="mt-3 text-sm font-extrabold text-gray-700">No subjects allocated yet</h4><p class="mt-1 text-xs font-medium text-gray-400">Use the row above to add the first subject for {{ $selectedDept->name }}.</p></div>
                            @endforelse
                        </div>
                    </div>
                </section>
            @else
                <section class="flex min-h-[20rem] items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 bg-white px-6 py-10 text-center shadow-sm"><div class="max-w-md"><div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-[#1a5632]"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m-6-9.5V16l6 4 6-4v-5.5"/></svg></div><p class="mt-4 text-xs font-extrabold uppercase tracking-widest text-[#1a5632]">Ready to configure</p><h2 class="mt-1 text-xl font-black text-gray-900">Select a faculty or class</h2><p class="mt-2 text-sm font-medium leading-6 text-gray-500">Choose an organization and faculty above. Its subject allocation workspace will appear here.</p></div></section>
            @endif
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
function facultySubjectsPage() {
    return {
        subjectMap: @js($subjects->mapWithKeys(fn ($subject) => [
            $subject->code => [
                'name' => $subject->name,
                'credit_hours' => $subject->credit_hours,
                'has_practical' => (bool) $subject->has_practical,
                'practical_code' => $subject->practical_code,
            ],
        ])),
        subjectCode: @js(strtoupper(preg_replace('/\s+/', '', (string) old('subject_code')))),
        allocationElective: @js((bool) old('is_elective')),
        hasPractical: @js((bool) old('has_practical')),
        practicalCode: @js(strtoupper(preg_replace('/\s+/', '', (string) old('practical_code')))),

        normalizeCode() {
            this.subjectCode = String(this.subjectCode || '').replace(/\s+/g, '').toUpperCase();
        },

        get existingSubject() {
            return this.subjectMap[this.subjectCode] || null;
        },

        get isNewSubject() {
            return this.subjectCode.length > 0 && !this.existingSubject;
        },
    };
}

function sectionGroupManager() {
    return {
        memberships: @js($sectionGroupMemberships),
        groupName: @js((string) old('group_name', '')),
        selected: @js(collect(old('section_ids', []))->map(fn ($id) => (string) $id)->values()),

        get existingName() {
            const wanted = this.groupName.trim().toLowerCase();
            return Object.keys(this.memberships).find(name => name.toLowerCase() === wanted) || null;
        },

        get isExisting() {
            return Boolean(this.existingName);
        },

        loadExistingGroup() {
            const name = this.existingName;
            if (name) this.selected = [...this.memberships[name]];
        },

        chooseGroup(name) {
            this.groupName = name;
            this.selected = [...(this.memberships[name] || [])];
        },
    };
}
</script>
@endpush
