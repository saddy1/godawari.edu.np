@extends('teaching_learning.layouts.app')

@section('title', 'Student Subject Assignments')

@section('content')
@php
    $input = 'w-full rounded-xl border-gray-200 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:ring-[#1a5632]';
    $label = 'mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500';
    $fixedOfferings = $offerings->where('is_elective', false);
    $electiveOfferings = $offerings->where('is_elective', true);
    $offeringById = $offerings->keyBy('id');
@endphp

<div class="space-y-4" x-data="{ search: '', selected: [] }">
    <section class="flex flex-col gap-3 rounded-2xl bg-gradient-to-r from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div><p class="text-[10px] font-black uppercase tracking-widest text-amber-300">Curriculum enrollment</p><h1 class="mt-1 text-2xl font-black">Student Subject Assignments</h1><p class="mt-1 text-xs font-semibold text-white/65">Compulsory subjects follow the curriculum automatically. Assign only electives manually.</p></div>
        <a href="{{ route('admin.teaching-learning.subjects.index') }}" class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-black text-white">Manage faculty subjects</a>
    </section>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">@foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach</div>
    @endif

    <form method="GET" class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-5">
        <div><label class="{{ $label }}">Academic year</label><select name="year" class="{{ $input }}" onchange="this.form.submit()">@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected($selectedYear?->id === $year->id)>{{ $year->name }}{{ $year->is_active ? ' · Active' : '' }}{{ $year->is_locked ? ' · Locked' : '' }}</option>@endforeach</select></div>
        <div><label class="{{ $label }}">Organization</label><select name="org" class="{{ $input }}" onchange="this.form.elements.dept.value=''; this.form.submit()"><option value="">Select organization</option>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected($selectedOrganization?->id === $organization->id)>{{ $organization->name }}</option>@endforeach</select></div>
        <div><label class="{{ $label }}">Class / Department</label><select name="dept" class="{{ $input }}" onchange="this.form.submit()"><option value="">Select department</option>@foreach($selectedOrganization?->departments ?? [] as $department)<option value="{{ $department->id }}" @selected($selectedDepartment?->id === $department->id)>{{ $department->name }}</option>@endforeach</select></div>
        @if($selectedDepartment?->academic_system === 'semester')
            <div><label class="{{ $label }}">Semester</label><select name="semester" class="{{ $input }}" onchange="this.form.submit()"><option value="">All / unassigned</option>@for($level=1;$level<=8;$level++)<option value="{{ $level }}" @selected($semester === $level)>Semester {{ $level }}</option>@endfor</select></div>
        @elseif($selectedDepartment?->academic_system === 'year')
            <div><label class="{{ $label }}">Study year</label><select name="year_level" class="{{ $input }}" onchange="this.form.submit()"><option value="">All / unassigned</option>@for($level=1;$level<=4;$level++)<option value="{{ $level }}" @selected($yearLevel === $level)>Year {{ $level }}</option>@endfor</select></div>
        @endif
        <div><label class="{{ $label }}">Section</label><select name="section" class="{{ $input }}" onchange="this.form.submit()"><option value="">All sections</option>@foreach($selectedDepartment?->sections ?? [] as $section)<option value="{{ $section->id }}" @selected($selectedSection?->id === $section->id)>{{ $section->name }}{{ $section->group_name ? ' · '.$section->group_name : '' }}</option>@endforeach</select></div>
    </form>

    @if($selectedYear?->is_locked)
        <div class="flex items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-800">
            <span>{{ $selectedYear->name }} is locked. Existing assignments are visible, but synchronization and elective changes are disabled.</span>
            <a href="{{ route('admin.teaching-learning.routine-configuration.index') }}" class="shrink-0 rounded-lg bg-amber-600 px-3 py-1.5 text-[10px] font-black text-white">Open academic years</a>
        </div>
    @endif

    @if(!$selectedDepartment)
        <section class="rounded-2xl border-2 border-dashed border-gray-200 bg-white px-6 py-14 text-center"><p class="text-sm font-black text-gray-500">Select an organization and class/department</p><p class="mt-1 text-xs font-semibold text-gray-400">Its compulsory and elective curriculum will appear here.</p></section>
    @else
        <div class="grid gap-4 xl:grid-cols-[1fr_1.3fr]">
            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <header class="flex items-center justify-between border-b border-gray-100 px-4 py-3"><div><h2 class="text-sm font-black text-gray-900">Compulsory subjects</h2><p class="text-[10px] font-semibold text-gray-400">Automatically assigned from Faculty Subjects.</p></div><span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-black text-blue-700">{{ $fixedOfferings->count() }} fixed</span></header>
                <div class="flex flex-wrap gap-2 p-4">@forelse($fixedOfferings as $offering)<div class="rounded-xl border border-blue-100 bg-blue-50/50 px-3 py-2"><p class="text-xs font-black text-gray-900">{{ $offering->subject->name }}</p><p class="text-[10px] font-bold text-blue-700">{{ $offering->subject->code }} · Automatic</p></div>@empty<p class="text-xs font-semibold text-amber-600">No compulsory subjects match this scope.</p>@endforelse</div>
                @if($selectedYear && !$selectedYear->is_locked)
                    <form method="POST" action="{{ route('admin.teaching-learning.subject-assignments.sync-fixed') }}" class="border-t border-gray-100 p-4">@csrf<input type="hidden" name="department_id" value="{{ $selectedDepartment->id }}"><input type="hidden" name="academic_year_id" value="{{ $selectedYear->id }}"><button class="rounded-lg bg-blue-600 px-3 py-2 text-[11px] font-black text-white">Synchronize existing students</button><p class="mt-1 text-[10px] font-semibold text-gray-400">New fixed subjects are assigned automatically; use this once for previously created subjects.</p></form>
                @endif
            </section>

            <section class="rounded-2xl border border-purple-200 bg-white shadow-sm">
                <header class="flex items-center justify-between border-b border-purple-100 px-4 py-3"><div><h2 class="text-sm font-black text-gray-900">Elective subjects</h2><p class="text-[10px] font-semibold text-gray-400">Select an elective and explicitly assign students.</p></div><span class="rounded-full bg-purple-50 px-2.5 py-1 text-[10px] font-black text-purple-700">{{ $electiveOfferings->count() }} elective</span></header>
                @if($electiveOfferings->isEmpty())
                    <p class="p-6 text-center text-xs font-semibold text-gray-400">No electives match this scope.</p>
                @else
                    <form method="POST" action="{{ route('admin.teaching-learning.subject-assignments.electives') }}" class="space-y-3 p-4">@csrf @method('PUT')<input type="hidden" name="academic_year_id" value="{{ $selectedYear?->id }}">
                        <div class="grid gap-3 sm:grid-cols-[1fr_160px]"><div><label class="{{ $label }}">Elective subject</label><select name="subject_offering_id" required class="{{ $input }}"><option value="">Choose elective</option>@foreach($electiveOfferings as $offering)<option value="{{ $offering->id }}">{{ $offering->subject->code }} · {{ $offering->subject->name }}{{ $offering->elective_group ? ' — '.$offering->elective_group : '' }}</option>@endforeach</select></div><div><label class="{{ $label }}">Action</label><select name="mode" class="{{ $input }}"><option value="assign">Assign elective</option><option value="remove">Remove elective</option></select></div></div>
                        <div class="flex items-center gap-2"><input x-model="search" placeholder="Search student or roll number..." class="{{ $input }}"><button type="button" @click="selected = @js($students->pluck('id')->map(fn($id)=>(string)$id)->values())" class="shrink-0 rounded-lg border px-3 py-2.5 text-[10px] font-black text-[#1a5632]">Select all</button><button type="button" @click="selected = []" class="shrink-0 rounded-lg border px-3 py-2.5 text-[10px] font-black text-gray-500">Clear</button></div>
                        <div class="max-h-96 divide-y overflow-y-auto rounded-xl border border-gray-200">
                            @forelse($students as $student)
                                @php $studentOfferingIds = $enrollmentMap->get($student->id, collect()); @endphp
                                <label x-show="!search || @js(strtolower($student->full_name.' '.$student->roll_number)).includes(search.toLowerCase())" class="flex cursor-pointer items-start gap-3 px-3 py-2.5 hover:bg-gray-50"><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" x-model="selected" class="mt-1 rounded text-purple-600"><div class="min-w-0 flex-1"><div class="flex items-center justify-between gap-2"><p class="truncate text-xs font-black text-gray-900">{{ $student->full_name }}</p><span class="text-[10px] font-bold text-gray-400">{{ $student->roll_number ?: 'No roll' }}</span></div><div class="mt-1 flex flex-wrap gap-1">@foreach($studentOfferingIds as $offeringId)@if(($assigned = $offeringById->get($offeringId))?->is_elective)<span class="rounded bg-purple-50 px-1.5 py-0.5 text-[9px] font-bold text-purple-700">{{ $assigned->subject->code }}</span>@endif @endforeach</div></div></label>
                            @empty<p class="p-6 text-center text-xs font-semibold text-gray-400">No students match this department, level, and section.</p>@endforelse
                        </div>
                        <div class="flex items-center justify-between gap-3"><p class="text-[10px] font-bold text-gray-400"><span x-text="selected.length"></span> student(s) selected</p><button @disabled(!$selectedYear || $selectedYear->is_locked) class="rounded-lg bg-purple-600 px-4 py-2.5 text-xs font-black text-white disabled:opacity-40">Apply elective assignment</button></div>
                    </form>
                @endif
            </section>
        </div>
    @endif
</div>
@endsection
