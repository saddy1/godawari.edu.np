@extends('learning.layouts.app')

@section('title', $course->title)

@section('content')
<div class="space-y-6">

    {{-- Course header --}}
    <div>
        <a href="{{ route('learning.dashboard') }}"
           class="inline-flex items-center gap-1 text-sm font-bold text-[#1a5632] hover:underline">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
            All Courses
        </a>
        <p class="text-xs font-bold uppercase tracking-widest text-gray-400 mt-3">
            {{ $course->learningClass->name ?? 'Class' }}
            @if($course->subject) · {{ $course->subject->name }} @endif
        </p>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-950 mt-1 leading-tight">{{ $course->title }}</h1>
        @if($course->description)
            <p class="mt-3 max-w-3xl text-gray-600">{{ $course->description }}</p>
        @endif
    </div>

    {{-- Chapter accordion --}}
    @php
        $chapterNumber = 0;
        $globalLessonIndex = 0;
    @endphp

    @forelse($course->chapters as $chapter)
        @php $chapterNumber++ @endphp
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden"
             x-data="{open: true}">

            {{-- Chapter header --}}
            <button type="button"
                    class="w-full flex items-center gap-3 px-5 py-4 text-left bg-gray-50/80 hover:bg-gray-100/60 transition-colors focus:outline-none"
                    @click="open = !open">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#1a5632] text-xs font-extrabold text-white">
                    {{ $chapterNumber }}
                </span>
                <div class="flex-1 min-w-0">
                    <p class="font-extrabold text-gray-900">{{ $chapter->title }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $chapter->lessons->count() }} {{ $chapter->lessons->count() === 1 ? 'lesson' : 'lessons' }}
                    </p>
                </div>
                <svg class="w-4 h-4 text-gray-400 transition-transform shrink-0" :class="open ? '' : '-rotate-90'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Lessons --}}
            <div x-show="open" class="divide-y divide-gray-50">
                @forelse($chapter->lessons as $lessonIndex => $lesson)
                    @php
                        $isCompleted = in_array($lesson->id, $completedLessonIds);
                        $isUnlocked  = in_array($lesson->id, $unlockedLessonIds);
                        $globalLessonIndex++;
                    @endphp

                    @if($isUnlocked)
                        <a href="{{ route('learning.lessons.show', [$course, $lesson->id]) }}"
                           class="flex items-center gap-4 px-5 py-4 hover:bg-emerald-50/40 transition-colors group">
                    @else
                        <div class="flex items-center gap-4 px-5 py-4 opacity-60 cursor-not-allowed">
                    @endif

                        {{-- Status icon --}}
                        <span class="shrink-0 flex h-8 w-8 items-center justify-center rounded-full
                            {{ $isCompleted
                                ? 'bg-emerald-500 text-white'
                                : ($isUnlocked ? 'bg-white border-2 border-[#1a5632] text-[#1a5632]' : 'bg-gray-100 text-gray-400') }}">
                            @if($isCompleted)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            @elseif(!$isUnlocked)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            @else
                                {{-- Type icon --}}
                                @if($lesson->type === 'video')
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                    </svg>
                                @elseif($lesson->type === 'audio')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/>
                                    </svg>
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                @endif
                            @endif
                        </span>

                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 leading-tight
                               {{ $isCompleted ? 'line-through text-gray-500' : '' }}">
                                {{ $chapterNumber }}.{{ $lessonIndex + 1 }}. {{ $lesson->title }}
                            </p>
                            <div class="flex flex-wrap items-center gap-2 mt-0.5">
                                <span class="text-xs text-gray-400 capitalize">{{ $lesson->type }}</span>
                                @if($lesson->quiz)
                                    <span class="text-xs text-amber-600 font-semibold">· includes quiz</span>
                                @endif
                                @if($lesson->is_free && !$isUnlocked)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700">FREE</span>
                                @endif
                                @if(!$isUnlocked)
                                    <span class="text-xs text-gray-400">Complete the previous lesson to unlock</span>
                                @endif
                            </div>
                        </div>

                        @if($isUnlocked && !$isCompleted)
                            <span class="shrink-0 rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-extrabold text-white
                                         group-hover:bg-[#0b2415] transition-colors">
                                Start
                            </span>
                        @elseif($isCompleted)
                            <span class="shrink-0 rounded-lg bg-emerald-50 px-4 py-2 text-xs font-extrabold text-emerald-700">
                                Done
                            </span>
                        @endif

                    @if($isUnlocked)
                        </a>
                    @else
                        </div>
                    @endif

                    @if($lesson->quiz)
                        @php
                            $quiz = $lesson->quiz;
                            $attempt = $quiz->attempts->sortByDesc('created_at')->first();
                            $attemptsUsed = $quiz->attempts->whereNotNull('completed_at')->count();
                            $canAttempt = $isCompleted && $attemptsUsed < $quiz->max_attempts;
                        @endphp
                        <div class="bg-amber-50/35 px-5 py-3">
                            <div class="ml-12 flex flex-wrap items-center gap-3 rounded-xl border border-amber-100 bg-white px-4 py-3 shadow-sm">
                                <span class="shrink-0 flex h-8 w-8 items-center justify-center rounded-full
                                    {{ $attempt?->passed ? 'bg-emerald-500 text-white' : ($isCompleted ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-400') }}">
                                    @if($attempt?->passed)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                        </svg>
                                    @endif
                                </span>

                                <div class="flex-1 min-w-0" style="min-width:140px">
                                    <p class="text-sm font-extrabold text-gray-900 leading-tight">
                                        Quiz after {{ $chapterNumber }}.{{ $lessonIndex + 1 }}: {{ $quiz->title }}
                                    </p>
                                    <p class="mt-0.5 text-xs font-semibold text-gray-400">
                                        {{ $quiz->questions_count }} Q · Pass {{ $quiz->pass_percentage }}%
                                        @if($quiz->time_limit_minutes) · {{ $quiz->time_limit_minutes }}min @endif
                                        @if($attempt)
                                            · <span class="{{ $attempt->passed ? 'text-emerald-600' : 'text-amber-600' }}">{{ $attempt->percentage() }}% ({{ $attemptsUsed }}/{{ $quiz->max_attempts }})</span>
                                        @elseif(!$isCompleted)
                                            · Complete the lesson first
                                        @else
                                            · {{ $quiz->max_attempts }} attempts
                                        @endif
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 ml-auto shrink-0">
                                    @if($attempt)
                                        <a href="{{ route('learning.quizzes.result', [$quiz, $attempt]) }}"
                                           class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-extrabold text-gray-600 hover:bg-gray-50 transition-colors">
                                            Results
                                        </a>
                                    @endif
                                    @if($canAttempt)
                                        <a href="{{ route('learning.quizzes.show', $quiz) }}"
                                           class="rounded-lg bg-amber-500 hover:bg-amber-600 px-4 py-1.5 text-xs font-extrabold text-white transition-colors">
                                            {{ $attempt ? 'Retake' : 'Take Quiz' }}
                                        </a>
                                    @elseif(!$isCompleted)
                                        <span class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-extrabold text-gray-400">
                                            Locked
                                        </span>
                                    @else
                                        <span class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-extrabold text-gray-400">
                                            No attempts left
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="px-5 py-6 text-sm text-gray-400 italic">No lessons in this chapter yet.</div>
                @endforelse
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-10 text-center text-gray-500">
            No content published yet. Check back soon.
        </div>
    @endforelse

    {{-- Mock Tests --}}
    @if($quizzes->isNotEmpty())
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-4 bg-gray-50/80 border-b border-gray-100">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-500 text-white">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </span>
            <div>
                <p class="font-extrabold text-gray-900">Mock Tests</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $quizzes->count() }} {{ $quizzes->count() === 1 ? 'quiz' : 'quizzes' }} available</p>
            </div>
        </div>

        <div class="divide-y divide-gray-50">
            @foreach($quizzes as $quiz)
            @php
                $attempt = $quiz->attempts->sortByDesc('created_at')->first();
                $attemptsUsed = $quiz->attempts->count();
                $canAttempt = $attemptsUsed < $quiz->max_attempts;
            @endphp
            <div class="flex flex-wrap items-center gap-3 px-4 py-3.5">
                {{-- Status dot --}}
                <span class="shrink-0 flex h-8 w-8 items-center justify-center rounded-full
                    {{ $attempt?->passed ? 'bg-emerald-500 text-white' : ($attempt ? 'bg-amber-100 text-amber-700' : 'bg-amber-50 text-amber-500') }}">
                    @if($attempt?->passed)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    @else
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    @endif
                </span>

                <div class="flex-1 min-w-0" style="min-width:120px">
                    <p class="font-bold text-gray-900 leading-tight text-sm">{{ $quiz->title }}</p>
                    <p class="text-xs text-gray-400 font-semibold mt-0.5">
                        {{ $quiz->questions->count() }} Q · Pass {{ $quiz->pass_percentage }}%
                        @if($quiz->time_limit_minutes) · {{ $quiz->time_limit_minutes }}min @endif
                        @if($attempt)
                            · <span class="{{ $attempt->passed ? 'text-emerald-600' : 'text-amber-600' }}">{{ $attempt->percentage() }}% ({{ $attemptsUsed }}/{{ $quiz->max_attempts }})</span>
                        @else
                            · {{ $quiz->max_attempts }} attempts
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2 ml-auto shrink-0">
                    @if($attempt)
                        <a href="{{ route('learning.quizzes.result', [$quiz, $attempt]) }}"
                           class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-extrabold text-gray-600 hover:bg-gray-50 transition-colors">
                            Results
                        </a>
                    @endif
                    @if($canAttempt)
                        <a href="{{ route('learning.quizzes.show', $quiz) }}"
                           class="rounded-lg bg-amber-500 hover:bg-amber-600 px-4 py-1.5 text-xs font-extrabold text-white transition-colors">
                            {{ $attempt ? 'Retake' : 'Take Test' }}
                        </a>
                    @else
                        <span class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-extrabold text-gray-400">
                            No attempts left
                        </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
