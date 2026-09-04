@extends('teaching_learning.layouts.app')

@section('title', 'Teaching & Learning Dashboard')

@section('content')
<div class="space-y-5">
    <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-[#0b2415] to-[#1a5632] px-5 py-5 text-white shadow-sm sm:px-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-amber-300">Curriculum workspace</p>
                <h1 class="mt-1 text-2xl font-black">Teaching &amp; Learning</h1>
                <p class="mt-1 max-w-2xl text-sm font-medium text-white/65">Manage subject masters, practical subjects, and faculty-wise fixed or elective allocations.</p>
            </div>
            <div class="flex flex-wrap gap-2"><a href="{{ route('admin.teaching-learning.subjects.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-extrabold text-[#1a5632] shadow-sm transition hover:bg-emerald-50">Faculty subjects<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></a><a href="{{ route('admin.teaching-learning.subject-assignments.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-extrabold text-white transition hover:bg-white/20">Assign subjects<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></a><a href="{{ route('admin.teaching-learning.routine-builder.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-extrabold text-gray-950 transition hover:bg-amber-300">Routine Builder<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></a><a href="{{ route('admin.teaching-learning.routine-configuration.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-extrabold text-white transition hover:bg-white/20">Routine setup<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></a></div>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach([
            ['value' => $stats['subjects'], 'label' => 'Subjects', 'tone' => 'text-[#1a5632] bg-emerald-50'],
            ['value' => $stats['allocations'], 'label' => 'Allocations', 'tone' => 'text-blue-700 bg-blue-50'],
            ['value' => $stats['faculties'], 'label' => 'Faculties / Classes', 'tone' => 'text-amber-700 bg-amber-50'],
            ['value' => $stats['practical'], 'label' => 'With Practical', 'tone' => 'text-teal-700 bg-teal-50'],
            ['value' => $stats['electives'], 'label' => 'Elective Allocations', 'tone' => 'text-purple-700 bg-purple-50'],
        ] as $stat)
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <span class="inline-flex rounded-lg px-2.5 py-1 text-xl font-black {{ $stat['tone'] }}">{{ $stat['value'] }}</span>
                <p class="mt-3 text-[10px] font-extrabold uppercase tracking-wider text-gray-400">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid gap-5 xl:grid-cols-[1.2fr_0.8fr]">
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div><p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Structure</p><h2 class="mt-0.5 font-black text-gray-900">Organizations</h2></div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-extrabold text-gray-500">{{ $organizations->count() }}</span>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($organizations as $organization)
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                        <div class="min-w-0"><p class="truncate text-sm font-extrabold text-gray-800">{{ $organization->name }}</p><p class="mt-0.5 text-xs font-semibold capitalize text-gray-400">{{ $organization->type }}</p></div>
                        <a href="{{ route('admin.teaching-learning.subjects.index', ['org' => $organization->id]) }}" class="shrink-0 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-extrabold text-[#1a5632] hover:bg-emerald-100">{{ $organization->departments_count }} {{ Str::plural('faculty', $organization->departments_count) }}</a>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm font-semibold text-gray-400">No organizations available.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div><p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Subject master</p><h2 class="mt-0.5 font-black text-gray-900">Recently created</h2></div>
                <a href="{{ route('admin.teaching-learning.subjects.index') }}" class="text-xs font-extrabold text-[#1a5632] hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($recentSubjects as $subject)
                    <div class="flex items-center gap-3 px-5 py-3.5">
                        <span class="flex h-9 min-w-12 items-center justify-center rounded-lg bg-gray-100 px-2 text-[10px] font-black text-gray-600">{{ $subject->code }}</span>
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-extrabold text-gray-800">{{ $subject->name }}</p><p class="mt-0.5 text-[11px] font-semibold text-gray-400">{{ $subject->offerings_count }} {{ Str::plural('allocation', $subject->offerings_count) }}{{ $subject->has_practical ? ' · Practical '.$subject->practical_code : '' }}</p></div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm font-semibold text-gray-400">No subjects created yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
