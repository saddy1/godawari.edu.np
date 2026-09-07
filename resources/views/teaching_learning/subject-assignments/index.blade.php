@extends('teaching_learning.layouts.app')

@section('title', 'Student Subject Assignments')

@section('content')
@php
    $input = 'w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold outline-none transition-colors duration-300 hover:border-gray-300 focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15 disabled:border-gray-100 disabled:bg-gray-50 disabled:text-gray-400';
    $label = 'mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500';
    $fixedOfferings = $offerings->where('is_elective', false);
    $electiveOfferings = $offerings->where('is_elective', true);
    $offeringById = $offerings->keyBy('id');
    $studentIdsBySection = ($selectedDepartment?->sections ?? collect())->mapWithKeys(function ($section) use ($students) {
        $ids = $students->filter(fn ($student) => (int) $student->section_id === (int) $section->id
            || (! $student->section_id && $student->section === $section->name))
            ->pluck('id')->map(fn ($id) => (string) $id)->values();
        return $ids->isEmpty() ? [] : [$section->name => $ids];
    });
@endphp

<div class="space-y-4" x-data="subjectAssignmentsPage()">
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
                <header class="flex items-center justify-between border-b border-gray-100 px-4 py-3"><div><h2 class="text-sm font-black text-gray-900">Compulsory subjects</h2><p class="text-[10px] font-semibold text-gray-400">Automatically assigned from Faculty Subjects.</p></div><div class="flex gap-1.5"><span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-black text-gray-600">{{ $students->count() }} students shown</span><span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-black text-blue-700">{{ $fixedOfferings->count() }} fixed</span></div></header>
                <div class="flex flex-wrap gap-2 p-4">@forelse($fixedOfferings as $offering)<div class="rounded-xl border border-blue-100 bg-blue-50/50 px-3 py-2"><p class="text-xs font-black text-gray-900">{{ $offering->subject->name }}</p><p class="text-[10px] font-bold text-blue-700">{{ $offering->subject->code }} · Automatic{{ $offering->group_name ? ' · '.$offering->group_name.' sections' : '' }}</p></div>@empty<p class="text-xs font-semibold text-amber-600">No compulsory subjects match this scope.</p>@endforelse</div>
                @if($selectedYear && !$selectedYear->is_locked)
                    <form method="POST" action="{{ route('admin.teaching-learning.subject-assignments.sync-fixed') }}" class="border-t border-gray-100 p-4">@csrf<input type="hidden" name="department_id" value="{{ $selectedDepartment->id }}"><input type="hidden" name="academic_year_id" value="{{ $selectedYear->id }}"><button class="rounded-lg bg-blue-600 px-3 py-2 text-[11px] font-black text-white">Synchronize existing students</button><p class="mt-1 text-[10px] font-semibold text-gray-400">Updates students in this department against their matching compulsory subjects. It never creates additional students.</p></form>
                @endif
            </section>

            <section class="rounded-2xl border border-purple-200 bg-white shadow-sm">
                <header class="flex items-center justify-between border-b border-purple-100 px-4 py-3"><div><h2 class="text-sm font-black text-gray-900">Elective subjects</h2><p class="text-[10px] font-semibold text-gray-400">Select an elective and explicitly assign students.</p></div><span class="rounded-full bg-purple-50 px-2.5 py-1 text-[10px] font-black text-purple-700">{{ $electiveOfferings->count() }} elective</span></header>
                @if($electiveOfferings->isEmpty())
                    <p class="p-6 text-center text-xs font-semibold text-gray-400">No electives match this scope.</p>
                @else
                    <form x-ref="assignmentForm" method="POST" action="{{ route('admin.teaching-learning.subject-assignments.electives') }}" class="space-y-3 p-4">@csrf @method('PUT')<input type="hidden" name="academic_year_id" value="{{ $selectedYear?->id }}"><template x-for="id in editScope" :key="'scope-'+id"><input type="hidden" name="scope_student_ids[]" :value="id"></template>
                        <div x-show="mode==='replace'" x-cloak class="flex items-center justify-between rounded-xl border border-purple-200 bg-purple-50 px-3 py-2"><p class="text-[10px] font-black text-purple-800">Editing saved section assignment — tick the students who should keep this elective.</p><button type="button" @click="cancelEdit()" class="rounded-lg bg-white px-2.5 py-1.5 text-[9px] font-black text-gray-600">Cancel edit</button></div>
                        <div class="grid gap-3 sm:grid-cols-[1fr_160px]"><div><label class="{{ $label }}">Elective subject</label><select name="subject_offering_id" x-model="electiveId" required class="{{ $input }}"><option value="">Choose elective</option>@foreach($electiveOfferings as $offering)<option value="{{ $offering->id }}">{{ $offering->subject->code }} · {{ $offering->subject->name }}{{ $offering->group_name ? ' · '.$offering->group_name.' sections' : '' }}{{ $offering->elective_group ? ' — '.$offering->elective_group : '' }}</option>@endforeach</select></div><div><label class="{{ $label }}">Action</label><select name="mode" x-model="mode" class="{{ $input }}"><option value="assign">Assign elective</option><option value="remove">Remove elective</option><option x-show="mode==='replace'" value="replace">Save edited selection</option></select></div></div>
                        @if($studentIdsBySection->isNotEmpty())
                            <div class="rounded-xl border border-purple-100 bg-purple-50/40 p-3">
                                <div class="mb-2 flex items-center justify-between gap-3"><p class="text-[10px] font-black uppercase tracking-wider text-purple-700">Select complete sections</p><p class="text-[10px] font-semibold text-gray-400">One click selects every student currently in that section</p></div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($studentIdsBySection as $sectionName => $sectionStudentIds)
                                        <button type="button" @click="toggleSection(@js($sectionStudentIds))" :class="sectionSelected(@js($sectionStudentIds)) ? 'border-purple-600 bg-purple-600 text-white shadow-sm' : 'border-purple-200 bg-white text-purple-800'" class="flex items-center gap-2 rounded-lg border px-3 py-2 text-[10px] font-black transition">
                                            <span :class="sectionSelected(@js($sectionStudentIds)) ? 'border-white bg-white text-purple-700' : 'border-purple-300 text-transparent'" class="flex h-4 w-4 items-center justify-center rounded border-2"><svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                                            {{ $sectionName }} <span class="opacity-70">{{ $sectionStudentIds->count() }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <div class="flex items-center gap-2"><input x-model="search" placeholder="Search student or roll number..." class="{{ $input }}"><button type="button" @click="selectAll()" class="shrink-0 rounded-lg border px-3 py-2.5 text-[10px] font-black text-[#1a5632]">Select all</button><button type="button" @click="selected = []" class="shrink-0 rounded-lg border px-3 py-2.5 text-[10px] font-black text-gray-500">Clear</button></div>
                        <div class="max-h-96 divide-y overflow-y-auto rounded-xl border border-gray-200">
                            @forelse($students as $student)
                                @php $studentOfferingIds = $enrollmentMap->get($student->id, collect()); @endphp
                                <label x-show="!search || @js(strtolower($student->full_name.' '.$student->roll_number)).includes(search.toLowerCase())" class="flex cursor-pointer items-start gap-3 px-3 py-2.5 hover:bg-gray-50"><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" x-model="selected" class="mt-1 rounded text-purple-600"><div class="min-w-0 flex-1"><div class="flex items-center justify-between gap-2"><p class="truncate text-xs font-black text-gray-900">{{ $student->full_name }}</p><span class="text-[10px] font-bold text-gray-400">{{ $student->roll_number ?: 'No roll' }}</span></div><div class="mt-1 flex flex-wrap gap-1">@foreach($studentOfferingIds as $offeringId)@if(($assigned = $offeringById->get($offeringId))?->is_elective)<span class="rounded bg-purple-50 px-1.5 py-0.5 text-[9px] font-bold text-purple-700">{{ $assigned->subject->code }}</span>@endif @endforeach</div></div></label>
                            @empty<p class="p-6 text-center text-xs font-semibold text-gray-400">No students match this department, level, and section.</p>@endforelse
                        </div>
                        <div class="flex items-center justify-between gap-3"><p class="text-[10px] font-bold text-gray-400"><span x-text="selected.length"></span> student(s) selected</p><button @disabled(!$selectedYear || $selectedYear->is_locked) class="rounded-lg bg-purple-600 px-4 py-2.5 text-xs font-black text-white disabled:opacity-40" x-text="mode==='replace'?'Save edited assignment':'Apply elective assignment'"></button></div>
                    </form>
                @endif
            </section>
        </div>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <header class="flex flex-col gap-2 border-b border-gray-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-[10px] font-black uppercase tracking-wider text-purple-600">Saved selections</p><h2 class="text-sm font-black text-gray-900">Current elective assignments</h2><p class="text-[10px] font-semibold text-gray-400">Section → elective subject → student names for {{ $selectedYear?->name }}.</p></div>
                <div class="flex gap-1.5"><span class="rounded-full bg-purple-50 px-2.5 py-1 text-[10px] font-black text-purple-700">{{ $electiveEnrollmentCount }} student-subject records</span><span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-black text-gray-600">{{ $electiveSummary->count() }} sections</span></div>
            </header>

            @if($electiveSummary->isEmpty())
                <div class="px-5 py-8 text-center"><p class="text-sm font-extrabold text-gray-500">No elective subjects assigned yet</p><p class="mt-1 text-xs font-semibold text-gray-400">Choose an elective and select a complete section or individual students above.</p></div>
            @else
                <div class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($electiveSummary as $sectionName => $subjectGroups)
                        @php
                            $sectionStudentCount = $subjectGroups->flatMap(fn ($group) => $group['students']->pluck('id'))->unique()->count();
                            $sectionRecord = $selectedDepartment->sections->firstWhere('name', $sectionName);
                        @endphp
                        <article class="overflow-hidden rounded-xl border border-purple-100 bg-purple-50/30">
                            <div class="flex items-center justify-between gap-3 border-b border-purple-100 bg-white px-3 py-2.5">
                                <div><div class="flex items-center gap-1.5"><h3 class="text-xs font-black text-gray-900">Section {{ $sectionName }}</h3>@if($sectionRecord?->group_name)<span class="rounded bg-emerald-50 px-1.5 py-0.5 text-[9px] font-black text-emerald-700">{{ $sectionRecord->group_name }}</span>@endif</div><p class="text-[9px] font-bold text-gray-400">{{ $sectionStudentCount }} students · {{ $subjectGroups->count() }} electives</p></div>
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-100 text-purple-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6"/></svg></span>
                            </div>
                            <div class="divide-y divide-purple-100">
                                @foreach($subjectGroups as $group)
                                    <details class="group bg-white/60">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2.5">
                                            <div class="min-w-0"><p class="truncate text-xs font-extrabold text-gray-800">{{ $group['offering']->subject->name }}</p><p class="text-[9px] font-black text-purple-700">{{ $group['offering']->subject->code }}{{ $group['offering']->elective_group ? ' · '.$group['offering']->elective_group : '' }}</p></div>
                                            <div class="flex shrink-0 items-center gap-1.5"><button type="button" @click.stop.prevent="editAssignment(@js((string)$group['offering']->id),@js($group['students']->pluck('id')->map(fn($id)=>(string)$id)->values()),@js(($studentIdsBySection->get($sectionName,collect()))->values()))" class="rounded-md border border-purple-200 bg-white px-2 py-1 text-[9px] font-black text-purple-700">Edit</button><span class="rounded-full bg-purple-100 px-2 py-0.5 text-[9px] font-black text-purple-700">{{ $group['students']->count() }} students</span><svg class="h-3.5 w-3.5 text-gray-400 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></div>
                                        </summary>
                                        <div class="max-h-52 space-y-1 overflow-y-auto border-t border-purple-100 px-3 py-2">
                                            @foreach($group['students'] as $student)
                                                <div class="flex items-center justify-between gap-2 rounded-md bg-white px-2 py-1.5 text-[10px]"><span class="truncate font-bold text-gray-700">{{ $student->full_name }}</span><span class="shrink-0 font-semibold text-gray-400">{{ $student->roll_number ?: 'No roll' }}</span></div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
function subjectAssignmentsPage() {
    return {
        search: '',
        selected: [],
        electiveId: '',
        mode: 'assign',
        editScope: [],
        allStudentIds: @js($students->pluck('id')->map(fn ($id) => (string) $id)->values()),

        selectAll() {
            this.selected = [...this.allStudentIds];
        },

        sectionSelected(ids) {
            return ids.length > 0 && ids.every(id => this.selected.includes(String(id)));
        },

        toggleSection(ids) {
            const normalized = ids.map(String);
            if (this.sectionSelected(normalized)) {
                this.selected = this.selected.filter(id => !normalized.includes(String(id)));
                return;
            }
            this.selected = [...new Set([...this.selected.map(String), ...normalized])];
        },

        editAssignment(offeringId, assignedIds, sectionIds) {
            this.electiveId = String(offeringId);
            this.selected = assignedIds.map(String);
            this.editScope = sectionIds.map(String);
            this.mode = 'replace';
            this.$refs.assignmentForm.scrollIntoView({behavior: 'smooth', block: 'start'});
        },

        cancelEdit() {
            this.electiveId = '';
            this.selected = [];
            this.editScope = [];
            this.mode = 'assign';
        },
    };
}
</script>
@endpush
