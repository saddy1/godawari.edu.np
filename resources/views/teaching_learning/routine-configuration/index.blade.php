@extends('teaching_learning.layouts.app')

@section('title', 'Routine Configuration')

@section('content')
@php
    $input = 'w-full rounded-lg border-gray-200 px-3 py-2 text-sm font-semibold focus:border-[#1a5632] focus:ring-[#1a5632]';
    $label = 'mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-gray-500';
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $displayTime = fn ($time) => \Carbon\CarbonImmutable::createFromFormat('!H:i', substr((string) $time, 0, 5))->format('g:i A');
@endphp

<div class="space-y-4">
    <section class="flex flex-col gap-3 rounded-2xl bg-gradient-to-r from-[#0b2415] to-[#1a5632] p-4 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-[10px] font-extrabold uppercase tracking-widest text-amber-300">Routine setup</p><h1 class="mt-0.5 text-xl font-black">Academic Years &amp; Time Slots</h1><p class="mt-1 text-xs font-semibold text-white/65">Create schedules once, generate periods automatically, and assign them to faculties or classes.</p></div>
        @if($selectedYear)<div class="flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-3 py-2"><span class="text-xs text-white/60">Selected year</span><strong class="text-sm">{{ $selectedYear->name }}</strong>@if($selectedYear->is_locked)<span class="rounded bg-amber-400 px-1.5 py-0.5 text-[9px] font-black text-amber-950">LOCKED</span>@endif</div>@endif
    </section>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><p class="font-black">Please correct the configuration:</p>@foreach($errors->all() as $error)<p class="mt-1">• {{ $error }}</p>@endforeach</div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3"><div><h2 class="text-sm font-black text-gray-900">Academic years</h2><p class="text-[10px] font-semibold text-gray-400">Only one year is active; locking freezes its routine configuration.</p></div><button type="button" onclick="document.getElementById('new-academic-year').showModal()" class="rounded-lg bg-[#1a5632] px-3 py-2 text-xs font-black text-white">+ New year</button></div>
        <div class="flex gap-2 overflow-x-auto p-3">
            @forelse($academicYears as $year)
                <div class="min-w-56 rounded-xl border p-3 {{ $selectedYear?->id === $year->id ? 'border-[#1a5632] bg-emerald-50/60 ring-1 ring-[#1a5632]/10' : 'border-gray-200' }}">
                    <div class="flex items-start justify-between gap-2"><a href="{{ route('admin.teaching-learning.routine-configuration.index', ['year'=>$year->id]) }}" class="min-w-0"><h3 class="truncate text-sm font-black text-gray-900">{{ $year->name }}</h3><p class="mt-0.5 text-[10px] font-semibold text-gray-400">{{ $year->starts_on?->format('Y-m-d') ?: 'No start date' }} → {{ $year->ends_on?->format('Y-m-d') ?: 'No end date' }}</p></a><div class="flex gap-1">@if($year->is_active)<span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[9px] font-black text-emerald-700">ACTIVE</span>@endif @if($year->is_locked)<span class="rounded bg-amber-100 px-1.5 py-0.5 text-[9px] font-black text-amber-700">LOCKED</span>@endif</div></div>
                    <div class="mt-3 flex items-center gap-1.5 border-t border-gray-100 pt-2.5">
                        @unless($year->is_active)<form method="POST" action="{{ route('admin.teaching-learning.routine-configuration.academic-years.activate',$year) }}">@csrf<button class="rounded-lg border px-2 py-1 text-[10px] font-bold text-emerald-700">Activate</button></form>@endunless
                        <form method="POST" action="{{ route('admin.teaching-learning.routine-configuration.academic-years.lock',$year) }}">@csrf<button class="rounded-lg border px-2 py-1 text-[10px] font-bold {{ $year->is_locked ? 'text-amber-700' : 'text-gray-600' }}">{{ $year->is_locked ? 'Unlock' : 'Lock' }}</button></form>
                        @unless($year->is_locked)<button type="button" onclick="document.getElementById('edit-year-{{ $year->id }}').showModal()" class="rounded-lg border px-2 py-1 text-[10px] font-bold text-gray-600">Edit</button>@endunless
                        @unless($year->is_locked || $year->is_active)<form method="POST" action="{{ route('admin.teaching-learning.routine-configuration.academic-years.destroy',$year) }}" onsubmit="return confirm('Delete this academic year and all its routine schedules?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-100 px-2 py-1 text-[10px] font-bold text-red-500">Delete</button></form>@endunless
                        <span class="ml-auto text-[10px] font-bold text-gray-400">{{ $year->shift_assignments_count }} assignments</span>
                    </div>
                </div>

                <dialog id="edit-year-{{ $year->id }}" class="m-auto w-[calc(100%_-_2rem)] max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-gray-950/55"><form method="POST" action="{{ route('admin.teaching-learning.routine-configuration.academic-years.update',$year) }}">@csrf @method('PATCH')<div class="flex items-center justify-between border-b px-5 py-4"><h3 class="font-black">Edit academic year</h3><button type="button" onclick="this.closest('dialog').close()" class="h-8 w-8 rounded-full bg-gray-100 text-xl text-gray-500">&times;</button></div><div class="grid gap-3 p-5"><div><label class="{{ $label }}">Name</label><input name="name" value="{{ $year->name }}" required class="{{ $input }}"></div><div class="grid grid-cols-2 gap-3"><div><label class="{{ $label }}">Starts on</label><input type="date" name="starts_on" value="{{ $year->starts_on?->format('Y-m-d') }}" class="{{ $input }}"></div><div><label class="{{ $label }}">Ends on</label><input type="date" name="ends_on" value="{{ $year->ends_on?->format('Y-m-d') }}" class="{{ $input }}"></div></div></div><div class="flex justify-end gap-2 border-t px-5 py-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border px-4 py-2 text-xs font-bold">Cancel</button><button class="rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-black text-white">Save</button></div></form></dialog>
            @empty
                <p class="w-full py-6 text-center text-sm font-semibold text-gray-400">Create an academic year to begin.</p>
            @endforelse
        </div>
    </section>

    <dialog id="new-academic-year" class="m-auto w-[calc(100%_-_2rem)] max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-gray-950/55"><form method="POST" action="{{ route('admin.teaching-learning.routine-configuration.academic-years.store') }}">@csrf<div class="flex items-center justify-between border-b px-5 py-4"><div><p class="text-[10px] font-black uppercase tracking-widest text-[#1a5632]">Routine year</p><h3 class="font-black">Create academic year</h3></div><button type="button" onclick="this.closest('dialog').close()" class="h-8 w-8 rounded-full bg-gray-100 text-xl text-gray-500">&times;</button></div><div class="space-y-3 p-5"><div><label class="{{ $label }}">Name</label><input name="name" value="{{ old('name') }}" placeholder="e.g. 2083/84" required class="{{ $input }}"></div><div class="grid grid-cols-2 gap-3"><div><label class="{{ $label }}">Starts on</label><input type="date" name="starts_on" class="{{ $input }}"></div><div><label class="{{ $label }}">Ends on</label><input type="date" name="ends_on" class="{{ $input }}"></div></div><label class="flex items-center gap-2 rounded-lg border p-3 text-xs font-bold text-gray-600"><input type="checkbox" name="is_active" value="1" class="rounded text-[#1a5632]"> Make this the active year</label></div><div class="flex justify-end gap-2 border-t px-5 py-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border px-4 py-2 text-xs font-bold">Cancel</button><button class="rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-black text-white">Create year</button></div></form></dialog>

            <details class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" @if($errors->any()) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3"><div><h2 class="text-sm font-black text-gray-900">Time-slot master</h2><p class="text-[10px] font-semibold text-gray-400">Create a reusable Morning, Day, or other time slot once, then assign it to organizations or departments.</p></div><span class="rounded-lg bg-[#1a5632] px-3 py-2 text-xs font-black text-white">+ New time slot</span></summary>
                <form method="POST" action="{{ route('admin.teaching-learning.routine-configuration.shifts.store') }}" class="border-t border-gray-100 p-4">@csrf
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                        <div class="xl:col-span-2"><label class="{{ $label }}">Time-slot name</label><input name="name" placeholder="e.g. Morning" required class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">Starts</label><input type="time" name="starts_at" value="06:30" required class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">Ends</label><input type="time" name="ends_at" value="11:30" required class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">Period minutes</label><input type="number" name="period_minutes" value="45" min="15" max="180" required class="{{ $input }}"><p class="mt-1 text-[9px] font-semibold text-gray-400">The last period uses all remaining time.</p></div>
                        <div><label class="{{ $label }}">Break after period</label><input type="number" name="break_after_period" min="1" max="20" placeholder="Optional" class="{{ $input }}"></div>
                        <div><label class="{{ $label }}">Break minutes</label><input type="number" name="break_minutes" min="5" max="120" placeholder="Optional" class="{{ $input }}"></div>
                        <div class="sm:col-span-2 xl:col-span-4"><label class="{{ $label }}">Working days</label><div class="flex flex-wrap gap-1.5">@foreach($days as $day)<label class="cursor-pointer"><input type="checkbox" name="working_days[]" value="{{ $day }}" class="peer sr-only" @checked($day !== 'Saturday')><span class="inline-flex rounded-lg border px-2.5 py-2 text-[10px] font-bold text-gray-500 peer-checked:border-[#1a5632] peer-checked:bg-emerald-50 peer-checked:text-[#1a5632]">{{ substr($day,0,3) }}</span></label>@endforeach</div></div>
                        <label class="flex items-center gap-2 text-xs font-bold text-gray-600"><input type="checkbox" name="is_active" value="1" checked class="rounded text-[#1a5632]"> Active</label>
                        <button class="rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-black text-white">Create reusable slot</button>
                    </div>
                </form>
            </details>

    @if($selectedYear)
        <div class="space-y-4">
            @forelse($shifts as $shift)
                <section class="overflow-hidden rounded-2xl border {{ $shift->is_locked ? 'border-amber-200' : 'border-gray-200' }} bg-white shadow-sm">
                    <header class="flex flex-col gap-3 border-b border-gray-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-xl {{ $shift->is_locked ? 'bg-amber-100 text-amber-700' : 'bg-emerald-50 text-[#1a5632]' }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span><div><div class="flex items-center gap-2"><h2 class="text-sm font-black text-gray-900">{{ $shift->name }}</h2><span class="rounded bg-blue-50 px-1.5 py-0.5 text-[9px] font-black text-blue-700">REUSABLE</span>@if($shift->is_locked)<span class="rounded bg-amber-100 px-1.5 py-0.5 text-[9px] font-black text-amber-700">LOCKED</span>@endif</div><p class="text-[10px] font-semibold text-gray-400">{{ $displayTime($shift->starts_at) }}–{{ $displayTime($shift->ends_at) }} · {{ $shift->period_minutes }} min · {{ implode(', ', array_map(fn($day)=>substr($day,0,3),$shift->working_days ?? [])) }}</p></div></div>
                        <div class="flex gap-1.5"><form method="POST" action="{{ route('admin.teaching-learning.routine-configuration.shifts.lock',$shift) }}">@csrf<button class="rounded-lg border px-3 py-1.5 text-[11px] font-bold {{ $shift->is_locked ? 'border-amber-200 text-amber-700' : 'text-gray-600' }}">{{ $shift->is_locked ? 'Unlock' : 'Lock' }}</button></form>@unless($shift->is_locked)<button type="button" onclick="document.getElementById('edit-shift-{{ $shift->id }}').showModal()" class="rounded-lg border px-3 py-1.5 text-[11px] font-bold text-gray-600">Edit time slot</button><form method="POST" action="{{ route('admin.teaching-learning.routine-configuration.shifts.destroy',$shift) }}" onsubmit="return confirm('Delete this reusable time slot? It can be deleted only when it has no assignments.')">@csrf @method('DELETE')<button class="rounded-lg border border-red-100 px-3 py-1.5 text-[11px] font-bold text-red-500">Delete</button></form>@endunless</div>
                    </header>

                    <div class="p-3">
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            @foreach($shift->periods as $period)
                                <button type="button" @unless($shift->is_locked) onclick="document.getElementById('edit-period-{{ $period->id }}').showModal()" @endunless class="min-w-36 rounded-lg border px-3 py-2 text-left {{ $period->is_break ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-gray-50' }}"><p class="text-[10px] font-black {{ $period->is_break ? 'text-amber-700' : 'text-gray-700' }}">{{ $period->name }}</p><p class="mt-0.5 whitespace-nowrap text-[10px] font-semibold text-gray-400">{{ $displayTime($period->starts_at) }}–{{ $displayTime($period->ends_at) }}</p></button>
                                <dialog id="edit-period-{{ $period->id }}" class="m-auto w-[calc(100%_-_2rem)] max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-gray-950/55"><form method="POST" action="{{ route('admin.teaching-learning.routine-configuration.periods.update',$period) }}">@csrf @method('PATCH')<div class="flex items-center justify-between border-b px-5 py-4"><h3 class="font-black">Edit period</h3><button type="button" onclick="this.closest('dialog').close()" class="h-8 w-8 rounded-full bg-gray-100 text-xl text-gray-500">&times;</button></div><div class="space-y-3 p-5"><div><label class="{{ $label }}">Name</label><input name="name" value="{{ $period->name }}" required class="{{ $input }}"></div><div class="grid grid-cols-2 gap-3"><div><label class="{{ $label }}">Starts</label><input type="time" name="starts_at" value="{{ substr($period->starts_at,0,5) }}" required class="{{ $input }}"></div><div><label class="{{ $label }}">Ends</label><input type="time" name="ends_at" value="{{ substr($period->ends_at,0,5) }}" required class="{{ $input }}"></div></div><label class="flex items-center gap-2 rounded-lg border p-3 text-xs font-bold"><input type="checkbox" name="is_break" value="1" @checked($period->is_break) class="rounded text-amber-600"> Break / non-teaching slot</label></div><div class="flex justify-end gap-2 border-t px-5 py-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border px-4 py-2 text-xs font-bold">Cancel</button><button class="rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-black text-white">Save period</button></div></form></dialog>
                            @endforeach
                        </div>

                        <details class="mt-3 rounded-xl border border-gray-200 bg-gray-50/50">
                            <summary class="flex cursor-pointer list-none items-center justify-between px-3 py-2.5">
                                <span class="text-xs font-black text-gray-700">Use this slot in {{ $selectedYear->name }}</span>
                                <span class="text-[10px] font-bold text-gray-400">{{ $shift->assignments->count() }} assignment{{ $shift->assignments->count() === 1 ? '' : 's' }}</span>
                            </summary>
                            <div class="grid gap-3 border-t border-gray-200 p-3 lg:grid-cols-2">
                                @foreach($organizations as $organization)
                                    @php
                                        $organizationAssignments = $shift->assignments->where('organization_id', $organization->id);
                                        $wholeOrganization = $organizationAssignments->contains(fn ($assignment) => $assignment->department_id === null);
                                        $assignedDepartmentIds = $organizationAssignments->pluck('department_id')->filter()->map(fn ($id) => (int) $id)->all();
                                    @endphp
                                    <form method="POST" action="{{ route('admin.teaching-learning.routine-configuration.shifts.departments',$shift) }}"
                                          x-data="{ whole: @js($wholeOrganization) }"
                                          class="rounded-xl border border-gray-200 bg-white p-3">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="academic_year_id" value="{{ $selectedYear->id }}">
                                        <input type="hidden" name="organization_id" value="{{ $organization->id }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <div><p class="text-xs font-black text-gray-900">{{ $organization->name }}</p><p class="text-[10px] font-semibold text-gray-400">Whole organization or selected faculties/classes</p></div>
                                            @if($wholeOrganization)<span class="rounded-full bg-emerald-100 px-2 py-1 text-[9px] font-black text-emerald-700">ALL</span>@elseif(count($assignedDepartmentIds))<span class="rounded-full bg-blue-50 px-2 py-1 text-[9px] font-black text-blue-700">{{ count($assignedDepartmentIds) }} SELECTED</span>@endif
                                        </div>
                                        <label class="mt-3 flex cursor-pointer items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50/60 p-2.5 text-xs font-black text-[#1a5632]">
                                            <input type="checkbox" name="whole_organization" value="1" x-model="whole" @checked($wholeOrganization) class="rounded text-[#1a5632]">
                                            Apply to complete organization
                                        </label>
                                        <div class="mt-2 flex flex-wrap gap-1.5" :class="whole && 'opacity-40'">
                                            @foreach($organization->departments as $department)
                                                <label class="cursor-pointer">
                                                    <input type="checkbox" name="department_ids[]" value="{{ $department->id }}" :disabled="whole" class="peer sr-only" @checked(in_array($department->id, $assignedDepartmentIds, true))>
                                                    <span class="inline-flex rounded-lg border bg-white px-2.5 py-2 text-[10px] font-bold text-gray-500 peer-checked:border-[#1a5632] peer-checked:bg-emerald-50 peer-checked:text-[#1a5632]">{{ $department->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @unless($shift->is_locked || $selectedYear->is_locked)
                                            <button class="mt-3 rounded-lg bg-[#1a5632] px-3 py-2 text-[11px] font-black text-white">Save for {{ $organization->name }}</button>
                                        @else
                                            <p class="mt-3 text-[10px] font-bold text-amber-600">Unlock the academic year and time slot to change this assignment.</p>
                                        @endunless
                                    </form>
                                @endforeach
                            </div>
                        </details>
                    </div>
                </section>

                <dialog id="edit-shift-{{ $shift->id }}" class="m-auto w-[calc(100%_-_2rem)] max-w-2xl rounded-2xl p-0 shadow-2xl backdrop:bg-gray-950/55"><form method="POST" action="{{ route('admin.teaching-learning.routine-configuration.shifts.update',$shift) }}">@csrf @method('PATCH')<div class="flex items-center justify-between border-b px-5 py-4"><div><p class="text-[10px] font-black uppercase tracking-widest text-[#1a5632]">Reusable time-slot master</p><h3 class="font-black">Edit {{ $shift->name }}</h3></div><button type="button" onclick="this.closest('dialog').close()" class="h-8 w-8 rounded-full bg-gray-100 text-xl text-gray-500">&times;</button></div><div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3"><div><label class="{{ $label }}">Name</label><input name="name" value="{{ $shift->name }}" required class="{{ $input }}"></div><div><label class="{{ $label }}">Starts</label><input type="time" name="starts_at" value="{{ substr($shift->starts_at,0,5) }}" required class="{{ $input }}"></div><div><label class="{{ $label }}">Ends</label><input type="time" name="ends_at" value="{{ substr($shift->ends_at,0,5) }}" required class="{{ $input }}"></div><div><label class="{{ $label }}">Period minutes</label><input type="number" name="period_minutes" value="{{ $shift->period_minutes }}" min="15" max="180" required class="{{ $input }}"></div><div><label class="{{ $label }}">Break after</label><input type="number" name="break_after_period" value="{{ $shift->break_after_period }}" min="1" max="20" class="{{ $input }}"></div><div><label class="{{ $label }}">Break minutes</label><input type="number" name="break_minutes" value="{{ $shift->break_minutes }}" min="5" max="120" class="{{ $input }}"></div><div class="sm:col-span-2 lg:col-span-3"><label class="{{ $label }}">Working days</label><div class="flex flex-wrap gap-1.5">@foreach($days as $day)<label class="cursor-pointer"><input type="checkbox" name="working_days[]" value="{{ $day }}" class="peer sr-only" @checked(in_array($day,$shift->working_days ?? []))><span class="inline-flex rounded-lg border px-2.5 py-2 text-[10px] font-bold text-gray-500 peer-checked:border-[#1a5632] peer-checked:bg-emerald-50 peer-checked:text-[#1a5632]">{{ substr($day,0,3) }}</span></label>@endforeach</div></div><label class="flex items-center gap-2 text-xs font-bold"><input type="checkbox" name="is_active" value="1" @checked($shift->is_active) class="rounded text-[#1a5632]"> Active</label><p class="rounded-lg bg-amber-50 p-2 text-[10px] font-bold text-amber-700 sm:col-span-2">Saving changes this shared slot everywhere it is assigned and regenerates its periods.</p></div><div class="flex justify-end gap-2 border-t px-5 py-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border px-4 py-2 text-xs font-bold">Cancel</button><button class="rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-black text-white">Save &amp; regenerate</button></div></form></dialog>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center"><h3 class="text-sm font-black text-gray-700">No reusable time slots yet</h3><p class="mt-1 text-xs font-semibold text-gray-400">Create Morning, Day, or another slot above, then assign it for {{ $selectedYear->name }}.</p></div>
            @endforelse
        </div>
    @endif
</div>
@endsection
