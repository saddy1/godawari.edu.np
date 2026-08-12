@extends('work-tasks.layout')

@section('title', 'Groups & Committees')

@section('content')
@php
    $input = 'w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
    $label = 'mb-1.5 block text-xs font-extrabold uppercase tracking-wider text-gray-600';
    $hint = 'mt-1 text-[11px] font-semibold leading-4 text-gray-400';
@endphp

<div class="space-y-5">
    <div class="rounded-2xl bg-[#0b2415] p-5 sm:p-6 text-white shadow-sm">
        <p class="text-sm font-bold uppercase tracking-widest text-white/50">Work Teams</p>
        <h1 class="mt-1 text-3xl font-extrabold">Groups & Committees</h1>
        <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">Create reusable teacher groups for committee-based assignments.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[420px_1fr]">
        <form method="POST" action="{{ route('admin.work-tasks.groups.store') }}" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            @csrf
            <h2 class="text-base font-extrabold text-gray-950">Create Group</h2>
            <p class="mt-1 text-xs font-semibold text-gray-500">Tick members; no keyboard shortcut needed.</p>

            <div class="mt-4 space-y-3">
                <div>
                    <label class="{{ $label }}" for="work-group-name">Group Name</label>
                    <input id="work-group-name" name="name" required placeholder="e.g. Exam Committee" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}" for="work-group-type">Group Type</label>
                    <input id="work-group-type" name="type" placeholder="e.g. Subject group, level committee" class="{{ $input }}">
                </div>
                <div x-data="{ selected: @js(collect(old('member_ids', []))->map(fn($id) => (int) $id)->values()) }">
                    <label class="{{ $label }}">Members</label>
                    <div class="max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-gray-50 p-2">
                        @foreach($teachers as $teacher)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2.5 py-2 text-sm font-semibold transition-all"
                                   :class="selected.includes({{ $teacher->id }}) ? 'bg-white text-[#1a5632] ring-1 ring-[#1a5632]/20' : 'text-gray-700 hover:bg-white'">
                                <input type="checkbox"
                                       name="member_ids[]"
                                       value="{{ $teacher->id }}"
                                       x-model.number="selected"
                                       class="sr-only">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition-all"
                                      :class="selected.includes({{ $teacher->id }}) ? 'border-[#1a5632] bg-[#1a5632] text-white' : 'border-gray-300 bg-white text-transparent'">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                <span class="min-w-0 truncate">{{ $teacher->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="{{ $hint }}">Select all teachers who belong to this group.</p>
                </div>
                <div>
                    <label class="{{ $label }}" for="work-group-description">Description</label>
                    <textarea id="work-group-description" name="description" rows="3" placeholder="Purpose or responsibility of this group" class="{{ $input }}"></textarea>
                </div>
            </div>

            <div class="mt-4 flex justify-end border-t border-gray-100 pt-4">
                <button class="rounded-xl bg-[#1a5632] px-5 py-2.5 text-sm font-extrabold text-white">Create Group</button>
            </div>
        </form>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-base font-extrabold text-gray-950">Group Library</h2>
                <p class="text-xs font-semibold text-gray-500">Available in the task assignment form.</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($groups as $group)
                    <div class="p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-extrabold text-gray-950">{{ $group->name }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500">{{ $group->type ?: 'General group' }} · {{ $group->members->count() }} member{{ $group->members->count() === 1 ? '' : 's' }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.work-tasks.groups.destroy', $group) }}" onsubmit="return confirm('Delete this group? Groups with assigned tasks cannot be deleted.')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-extrabold text-red-700">Delete</button>
                            </form>
                        </div>
                        <p class="mt-3 rounded-xl bg-gray-50 p-3 text-sm font-medium text-gray-600">{{ $group->members->pluck('name')->join(', ') ?: 'No members yet.' }}</p>
                    </div>
                @empty
                    <p class="p-5 text-sm font-semibold text-gray-500">No groups created yet.</p>
                @endforelse
            </div>
            <div class="border-t border-gray-100 px-5 py-4">{{ $groups->links() }}</div>
        </div>
    </div>
</div>
@endsection
