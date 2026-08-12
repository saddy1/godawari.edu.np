@extends('learning.layouts.admin')

@section('title', 'Learning Subjects')

@section('content')
@php
    $input = 'w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
    $smallInput = 'rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
    $canCreate = auth()->user()?->canAccess('learning.courses.create');
    $canEdit = auth()->user()?->canAccess('learning.courses.edit');
    $canDelete = auth()->user()?->canAccess('learning.courses.delete');
    $totalSubjects = $classes->sum(fn ($class) => $class->subjects->count());
    $activeSubjects = $classes->sum(fn ($class) => $class->subjects->where('is_active', true)->count());
@endphp

<div class="space-y-6" x-data="{ openClass: @js(optional($classes->first())->id), addOpen: {{ $canCreate ? 'true' : 'false' }} }">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-gray-400">E-Learning</p>
            <h1 class="mt-1 text-3xl font-extrabold text-gray-950">Subjects</h1>
            <p class="mt-2 max-w-2xl text-sm font-semibold text-gray-500">Class-wise subject distribution. Expand a class to manage its subjects.</p>
        </div>
        <div class="grid grid-cols-3 gap-2 sm:w-[430px]">
            <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 shadow-sm">
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Classes</p>
                <p class="text-xl font-black text-gray-950">{{ $classes->count() }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 shadow-sm">
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Subjects</p>
                <p class="text-xl font-black text-gray-950">{{ $totalSubjects }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 shadow-sm">
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Active</p>
                <p class="text-xl font-black text-[#1a5632]">{{ $activeSubjects }}</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    @if($canCreate)
    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <button type="button" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left" @click="addOpen = ! addOpen">
            <div>
                <h2 class="text-lg font-extrabold text-gray-950">Add Subject</h2>
                <p class="text-xs font-semibold text-gray-500">Create a subject under the correct class.</p>
            </div>
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#1a5632]/10 text-[#1a5632]">
                <svg class="h-4 w-4 transition-transform" :class="addOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </span>
        </button>
        <form x-show="addOpen" method="POST" action="{{ route('admin.learning.subjects.store') }}" class="grid gap-4 border-t border-gray-100 p-5 md:grid-cols-[240px_1fr_160px_130px_auto]" style="display: none;">
            @csrf
            <select name="learning_class_id" required class="{{ $input }}">
                <option value="">Select class</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected(old('learning_class_id') == $class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            <input name="name" required value="{{ old('name') }}" placeholder="Science" class="{{ $input }}">
            <input name="code" value="{{ old('code') }}" placeholder="SCI" class="{{ $input }}">
            <label class="flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="accent-[#1a5632]"> Active
            </label>
            <button class="rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white">Save</button>
        </form>
    </section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
            <h2 class="text-base font-extrabold text-gray-950">Class Wise Distribution</h2>
            <p class="text-xs font-semibold text-gray-500">Click a class to expand subjects.</p>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($classes as $class)
                @php
                    $subjectCount = $class->subjects->count();
                    $classActiveSubjects = $class->subjects->where('is_active', true)->count();
                    $courseCount = $class->subjects->sum('courses_count');
                @endphp
                <article>
                    <button type="button" class="grid w-full gap-3 px-5 py-4 text-left transition hover:bg-gray-50 lg:grid-cols-[1fr_110px_110px_110px_36px] lg:items-center" @click="openClass = openClass === {{ $class->id }} ? null : {{ $class->id }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-extrabold text-gray-950">{{ $class->name }}</h3>
                                <span class="rounded-full {{ $class->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }} px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider">
                                    {{ $class->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs font-semibold text-gray-500">{{ $subjectCount }} subject{{ $subjectCount === 1 ? '' : 's' }} distributed in this class</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 px-3 py-2">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Subjects</p>
                            <p class="text-lg font-black text-gray-950">{{ $subjectCount }}</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 px-3 py-2">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-emerald-600/70">Active</p>
                            <p class="text-lg font-black text-emerald-700">{{ $classActiveSubjects }}</p>
                        </div>
                        <div class="rounded-xl bg-amber-50 px-3 py-2">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-amber-600/70">Courses</p>
                            <p class="text-lg font-black text-amber-700">{{ $courseCount }}</p>
                        </div>
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500">
                            <svg class="h-4 w-4 transition-transform" :class="openClass === {{ $class->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </span>
                    </button>

                    <div x-show="openClass === {{ $class->id }}" class="border-t border-gray-100 bg-gray-50/70 px-4 py-4">
                        @if($class->subjects->isNotEmpty())
                            <div class="grid gap-3 xl:grid-cols-2">
                                @foreach($class->subjects as $subject)
                                    <div class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <p class="text-sm font-extrabold text-gray-950">{{ $subject->name }}</p>
                                                <div class="mt-1 flex flex-wrap gap-1.5">
                                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-extrabold text-gray-600">{{ $subject->code ?: 'No code' }}</span>
                                                    <span class="rounded-full {{ $subject->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }} px-2 py-0.5 text-[10px] font-extrabold">
                                                        {{ $subject->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-extrabold text-amber-700">{{ $subject->courses_count }} course{{ $subject->courses_count === 1 ? '' : 's' }}</span>
                                                </div>
                                            </div>
                                            @if($canDelete && ! $canEdit)
                                                <form method="POST" action="{{ route('admin.learning.subjects.destroy', $subject) }}" onsubmit="return confirm('Delete this subject? Courses linked to it may also be affected.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-extrabold text-red-700">Delete</button>
                                                </form>
                                            @endif
                                        </div>

                                        @if($canEdit)
                                            <form method="POST" action="{{ route('admin.learning.subjects.update', $subject) }}" class="mt-4 border-t border-gray-100 pt-4">
                                                @csrf
                                                @method('PATCH')
                                                <div class="grid min-w-0 gap-2 sm:grid-cols-[130px_minmax(0,1fr)_90px]">
                                                    <select name="learning_class_id" class="{{ $smallInput }} min-w-0">
                                                        @foreach($classes as $optionClass)
                                                            <option value="{{ $optionClass->id }}" @selected($subject->learning_class_id === $optionClass->id)>{{ $optionClass->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input name="name" value="{{ $subject->name }}" class="{{ $smallInput }} min-w-0">
                                                    <input name="code" value="{{ $subject->code }}" class="{{ $smallInput }} min-w-0">
                                                </div>
                                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                                    <label class="inline-flex min-h-10 items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-bold">
                                                        <input type="checkbox" name="is_active" value="1" @checked($subject->is_active) class="accent-[#1a5632]"> Active
                                                    </label>
                                                    <button class="inline-flex min-h-10 items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-extrabold text-gray-700">Update</button>
                                                    @if($canDelete)
                                                        <button
                                                            type="submit"
                                                            form="delete-subject-{{ $subject->id }}"
                                                            class="inline-flex min-h-10 items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-extrabold text-red-700">
                                                            Delete
                                                        </button>
                                                    @endif
                                                </div>
                                            </form>
                                        @endif
                                        @if($canDelete)
                                            <form id="delete-subject-{{ $subject->id }}" method="POST" action="{{ route('admin.learning.subjects.destroy', $subject) }}" onsubmit="return confirm('Delete this subject? Courses linked to it may also be affected.')" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-8 text-center">
                                <p class="text-sm font-extrabold text-gray-700">No subjects in {{ $class->name }} yet.</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500">Use Add Subject above and select this class.</p>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-5 py-10 text-center text-sm font-semibold text-gray-500">No active classes found. Create classes first.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
