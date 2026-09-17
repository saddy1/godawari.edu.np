@extends('card.student-portal.layout')
@section('title', 'E-Learning')

@section('content')
<div class="space-y-6">
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-widest text-[#1a5632]">Learning Center</p>
                <h1 class="mt-2 text-2xl font-extrabold text-gray-950">Courses & Resources</h1>
                <p class="mt-1 text-sm font-medium text-gray-500">
                    Showing content for {{ $student->stream ?: 'your class' }}{{ $student->section ? ' · Section '.$student->section : '' }}.
                </p>
            </div>
            @if(\App\Services\ModuleService::enabled('learning') && Route::has('learning.dashboard'))
                <a href="{{ route('learning.dashboard') }}" class="inline-flex justify-center rounded-xl bg-[#1a5632] px-4 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415]">
                    Open Full Learning View
                </a>
            @endif
        </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-[1fr_380px]">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-5">
                <h2 class="text-lg font-extrabold text-gray-950">My Courses</h2>
                <p class="mt-1 text-sm font-medium text-gray-500">Continue lessons and complete quizzes from assigned courses.</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($courses as $course)
                    @php
                        $percent = (int) ($progress[$course->id] ?? 0);
                    @endphp
                    <a href="{{ route('learning.courses.show', $course) }}" class="block p-5 transition hover:bg-gray-50">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-extrabold text-emerald-700">{{ $course->subject?->name ?? 'General' }}</span>
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-500">{{ $course->lessons->count() }} lessons</span>
                                </div>
                                <h3 class="mt-3 text-base font-extrabold text-gray-950">{{ $course->title }}</h3>
                                @if($course->description)
                                    <p class="mt-1 line-clamp-2 text-sm font-medium leading-6 text-gray-500">{{ $course->description }}</p>
                                @endif
                            </div>
                            <div class="w-full shrink-0 md:w-40">
                                <div class="mb-2 flex justify-between text-xs font-extrabold text-gray-500">
                                    <span>Progress</span>
                                    <span>{{ $percent }}%</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-full rounded-full bg-[#1a5632]" style="width: {{ min(100, max(0, $percent)) }}%"></div>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center">
                        <p class="text-sm font-extrabold text-gray-700">No courses published yet.</p>
                        <p class="mt-1 text-sm font-medium text-gray-500">Courses assigned to your class will appear here.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-5">
                <h2 class="text-lg font-extrabold text-gray-950">Study Resources</h2>
                <p class="mt-1 text-sm font-medium text-gray-500">Notes, question banks, syllabus, and practice materials.</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($resources as $resource)
                    <a href="{{ $resource->file_url ?: '#' }}" target="{{ $resource->file_url ? '_blank' : '_self' }}" class="block p-4 transition hover:bg-gray-50">
                        <div class="flex gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-extrabold text-gray-950">{{ $resource->title }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-400">{{ ucfirst($resource->type) }} · {{ $resource->subject?->name ?? 'General' }}</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-6 text-center">
                        <p class="text-sm font-extrabold text-gray-700">No resources yet.</p>
                        <p class="mt-1 text-xs font-medium text-gray-500">Resources uploaded for your class will show here.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
