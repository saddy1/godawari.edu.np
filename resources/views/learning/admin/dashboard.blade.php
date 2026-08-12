@extends('learning.layouts.admin')

@section('title', 'Learning Dashboard')

@section('content')
@php
    $progressColor = function (int $value): string {
        if ($value >= 80) return 'bg-emerald-500';
        if ($value >= 40) return 'bg-amber-500';
        return 'bg-rose-500';
    };
@endphp

<div class="space-y-6">
    <section class="rounded-2xl p-5 sm:p-6 text-white shadow-sm" style="background: linear-gradient(to bottom right, var(--theme-dark), var(--theme-primary))">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-widest text-white/50">E-Learning</p>
                <h1 class="mt-1 text-3xl font-extrabold">Learning Dashboard</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">
                    @if($isTeacherScoped)
                        Your allocated classes, subjects, student progress, and recent lesson activity.
                    @else
                        Whole-school learning progress, course activity, teacher mapping, and student completion.
                    @endif
                </p>
            </div>
            <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/15">
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-white/45">Scope</p>
                <p class="mt-1 text-sm font-extrabold">{{ $isTeacherScoped ? 'Allocated Teacher View' : 'Admin Full View' }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Courses</p>
            <p class="mt-2 text-3xl font-black text-gray-950">{{ $courseCount }}</p>
            <p class="mt-1 text-xs font-semibold text-gray-500">{{ $publishedCourseCount }} published</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Students</p>
            <p class="mt-2 text-3xl font-black text-gray-950">{{ $studentCount }}</p>
            <p class="mt-1 text-xs font-semibold text-gray-500">{{ $isTeacherScoped ? 'In assigned classes' : 'Learning accounts' }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Avg Progress</p>
            <p class="mt-2 text-3xl font-black text-gray-950">{{ $averageProgress }}%</p>
            <div class="mt-3 h-2 rounded-full bg-gray-100">
                <div class="h-2 rounded-full {{ $progressColor($averageProgress) }}" style="width: {{ min(100, $averageProgress) }}%"></div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Completed Lessons</p>
            <p class="mt-2 text-3xl font-black text-gray-950">{{ $completedLessonCount }}</p>
            <p class="mt-1 text-xs font-semibold text-gray-500">Lesson completions</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Resources</p>
            <p class="mt-2 text-3xl font-black text-gray-950">{{ $resourceCount }}</p>
            <p class="mt-1 text-xs font-semibold text-gray-500">Notes and files</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Teacher Maps</p>
            <p class="mt-2 text-3xl font-black text-gray-950">{{ $teacherMapCount }}</p>
            <p class="mt-1 text-xs font-semibold text-gray-500">{{ $isTeacherScoped ? 'Your classes' : 'Class assignments' }}</p>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.35fr_.9fr]">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-lg font-extrabold text-gray-950">Course Progress</h2>
                    <p class="text-sm font-semibold text-gray-500">{{ $isTeacherScoped ? 'Courses you are mapped to manage' : 'All visible courses' }}</p>
                </div>
                <a href="{{ route('admin.learning.courses.index') }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-extrabold text-gray-600 hover:bg-gray-50">Courses</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Course</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Students</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Started</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Progress</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Lessons</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($courseRows as $row)
                            @php($course = $row['course'])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-4">
                                    <p class="font-extrabold text-gray-950">{{ $course->title }}</p>
                                    <p class="mt-0.5 text-xs font-semibold text-gray-500">
                                        {{ $course->learningClass->name ?? 'Class' }} · {{ $course->subject->name ?? 'General' }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-sm font-bold text-gray-700">{{ $row['students'] }}</td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-bold text-gray-800">{{ $row['started'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $row['completed'] }} completed course</p>
                                </td>
                                <td class="px-5 py-4 min-w-[180px]">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 flex-1 rounded-full bg-gray-100">
                                            <div class="h-2 rounded-full {{ $progressColor($row['avg_progress']) }}" style="width: {{ min(100, $row['avg_progress']) }}%"></div>
                                        </div>
                                        <span class="w-10 text-right text-sm font-extrabold text-gray-700">{{ $row['avg_progress'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-bold text-gray-800">{{ $row['completed_lessons'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $course->lessons_count }} lessons/course</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm font-semibold text-gray-400">
                                    No courses available in this dashboard scope.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-lg font-extrabold text-gray-950">Recent Activity</h2>
                <p class="text-sm font-semibold text-gray-500">Latest lesson progress updates</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($recentActivity as $activity)
                    <div class="px-5 py-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-xs font-black text-[#1a5632]">
                                {{ strtoupper(substr($activity->user->name ?? 'S', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-extrabold text-gray-950">{{ $activity->user->name ?? 'Student' }}</p>
                                <p class="mt-0.5 line-clamp-2 text-xs font-semibold text-gray-500">
                                    {{ $activity->lesson->title ?? 'Lesson' }} · {{ $activity->course->title ?? 'Course' }}
                                </p>
                                <p class="mt-1 text-[11px] font-bold text-gray-400">{{ $activity->updated_at?->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm font-semibold text-gray-400">No lesson activity yet.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-extrabold text-gray-950">Student Progress</h2>
                <p class="text-sm font-semibold text-gray-500">{{ $isTeacherScoped ? 'Only students from your allocated classes are shown' : 'All student learning accounts' }}</p>
            </div>
            @if(auth()->user()?->canAccess('learning.students.view'))
                <a href="{{ route('admin.learning.students.index') }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-extrabold text-gray-600 hover:bg-gray-50">Student Accounts</a>
            @endif
        </div>
        <div class="border-b border-gray-100 bg-gray-50/70 px-5 py-4">
            <form id="student-progress-filters" method="GET" action="{{ route('admin.learning.dashboard') }}"
                  class="grid gap-3 lg:grid-cols-[1fr_180px_180px_130px_auto]">
                <div class="relative">
                    <input
                        id="student-progress-search"
                        type="search"
                        name="progress_q"
                        value="{{ $progressSearch }}"
                        placeholder="Search by student name, ID, email..."
                        autocomplete="off"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 pr-12 text-sm font-bold text-gray-800 focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/10"
                    >
                    <span id="student-progress-status" class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 text-[10px] font-extrabold uppercase tracking-wider text-gray-400">...</span>
                </div>
                <select name="class" class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-800 focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/10">
                    <option value="">All classes</option>
                    @foreach($classOptions as $className)
                        <option value="{{ $className }}" @selected($progressClass === $className)>{{ $className }}</option>
                    @endforeach
                </select>
                <select name="section" class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-800 focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/10">
                    <option value="">All sections</option>
                    @foreach($sectionOptions as $sectionName)
                        <option value="{{ $sectionName }}" @selected($progressSection === $sectionName)>{{ $sectionName }}</option>
                    @endforeach
                </select>
                <select name="per_page" class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold text-gray-800 focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/10">
                    @foreach(['10' => '10 rows', '39' => '39 rows', '100' => '100 rows', 'all' => 'All rows'] as $value => $label)
                        <option value="{{ $value }}" @selected($perPageInput === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-[#1a5632] px-4 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415]">
                    Apply
                </button>
            </form>
        </div>
        <div id="student-progress-results">
            @include('learning.admin.partials.student-progress-table', ['studentRows' => $studentRows, 'progressColor' => $progressColor])
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('student-progress-filters');
    const results = document.getElementById('student-progress-results');
    const search = document.getElementById('student-progress-search');
    const status = document.getElementById('student-progress-status');

    if (!form || !results) return;

    let timer = null;
    let controller = null;

    const loadProgress = (url = null) => {
        if (controller) controller.abort();
        controller = new AbortController();

        const target = new URL(url || form.action, window.location.origin);
        const data = new FormData(form);
        data.forEach((value, key) => {
            if (value) {
                target.searchParams.set(key, value);
            } else {
                target.searchParams.delete(key);
            }
        });

        if (!url) target.searchParams.delete('page');

        status?.classList.remove('hidden');

        fetch(target.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
        })
            .then((response) => response.text())
            .then((html) => {
                results.innerHTML = html;
                window.history.replaceState({}, '', target.toString());
            })
            .catch((error) => {
                if (error.name !== 'AbortError') console.error(error);
            })
            .finally(() => status?.classList.add('hidden'));
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadProgress();
    });

    form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => loadProgress());
    });

    search?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => loadProgress(), 250);
    });

    results.addEventListener('click', (event) => {
        const link = event.target.closest('nav a[href]');
        if (!link) return;

        event.preventDefault();
        loadProgress(link.href);
    });
});
</script>
@endpush
