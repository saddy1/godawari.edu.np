@extends('learning.layouts.admin')

@section('title', 'Manage: ' . $course->title)

@push('styles')
<style>
    .lesson-body h1,.lesson-body h2,.lesson-body h3,.lesson-body h4{font-weight:800;color:#111827;margin-top:1.25rem;margin-bottom:.4rem;line-height:1.3}
    .lesson-body h1{font-size:1.4rem}.lesson-body h2{font-size:1.2rem}.lesson-body h3{font-size:1.05rem}
    .lesson-body p{color:#374151;line-height:1.75;margin-bottom:.6rem}
    .lesson-body ul{list-style:disc;padding-left:1.5rem;margin-bottom:.6rem;color:#374151}
    .lesson-body ol{list-style:decimal;padding-left:1.5rem;margin-bottom:.6rem;color:#374151}
    .lesson-body li{margin-bottom:.2rem;line-height:1.6}
    .lesson-body strong{font-weight:700;color:#111827}
    .lesson-body blockquote{border-left:4px solid #1a5632;background:#f0fdf4;padding:.6rem 1rem;border-radius:0 .6rem .6rem 0;margin:.75rem 0;color:#166534;font-style:italic}
    .lesson-body code{background:#f3f4f6;border-radius:.25rem;padding:.1rem .3rem;font-family:monospace;font-size:.85em}
    .lesson-body pre{background:#1f2937;color:#f9fafb;border-radius:.6rem;padding:.75rem;overflow-x:auto;margin-bottom:.6rem}
    .lesson-body pre code{background:none;color:inherit;padding:0}
    .lesson-body{overflow-wrap:anywhere}
    .course-manage-card{border-radius:1rem}
    .course-editor-grid{display:grid;gap:.75rem;align-items:start}
    .course-editor-pane{min-width:0;border:1px solid #e5e7eb;border-radius:.875rem;background:#fff;padding:.75rem;box-shadow:0 1px 2px rgba(15,23,42,.04)}
    .course-editor-label{margin-bottom:.5rem;font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#6b7280}
    .course-editor-help{border-radius:.75rem;border:1px solid #e5e7eb;background:#fff;padding:.65rem .8rem;color:#64748b;line-height:1.55}
    .course-editor-source{height:14rem;max-height:26rem;resize:vertical}
    .course-editor-preview{height:31.4rem;max-height:min(52vh,34rem);overflow:auto}
    .course-form-actions{position:sticky;bottom:0;z-index:20;margin:1rem -1.25rem -1rem;padding:.8rem 1.25rem;background:rgba(255,255,255,.94);border-top:1px solid #e5e7eb;backdrop-filter:blur(10px)}
    /* Quill editor theming */
    .ql-toolbar.ql-snow { border-radius:.75rem .75rem 0 0; border-color:#e5e7eb; background:#f9fafb; }
    .ql-container.ql-snow { border-radius:0 0 .75rem .75rem; border-color:#e5e7eb; min-height:180px; font-size:.875rem; }
    .ql-editor { min-height:180px; }
    .ql-editor.ql-blank::before { color:#9ca3af; font-style:normal; }
    .course-editor-pane .ql-container.ql-snow{height:13.65rem;min-height:13.65rem;overflow-y:auto}
    .course-editor-pane .ql-editor{min-height:13.65rem}
    @media (min-width:1280px){.course-editor-grid{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}}
    @media (max-width:640px){
        .course-editor-preview{height:22rem;max-height:22rem}
        .course-form-actions{margin-left:-1rem;margin-right:-1rem;padding-left:1rem;padding-right:1rem}
    }
</style>
@endpush

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 sm:p-6 text-white shadow-sm flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('admin.learning.courses.index') }}"
               class="inline-flex items-center gap-1 text-xs font-bold text-white/50 hover:text-white/80 uppercase tracking-widest mb-2">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                Courses
            </a>
            <h1 class="text-2xl font-extrabold leading-tight">{{ $course->title }}</h1>
            <p class="mt-1 text-sm text-white/60">{{ $course->learningClass->name ?? '' }}{{ $course->subject ? ' · ' . $course->subject->name : '' }}</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <span class="rounded-full {{ $course->status === 'published' ? 'bg-emerald-400/25 text-emerald-200' : 'bg-white/10 text-white/50' }} px-3 py-1 text-xs font-extrabold">
                {{ ucfirst($course->status) }}
            </span>
            @if(auth()->user()?->canAccess('learning.quizzes.view'))
            <a href="{{ route('admin.learning.quizzes.index', ['course' => $course->id]) }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-amber-400/20 px-3 py-1.5 text-xs font-extrabold text-amber-200 hover:bg-amber-400/30 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Quizzes ({{ $course->quizzes()->count() }})
            </a>
            @endif
        </div>
    </div>

    {{-- Chapters list --}}
    <div class="space-y-3" id="chapters-list">
        @forelse($course->chapters as $chapterIndex => $chapter)
        <div class="course-manage-card border border-gray-200 bg-white shadow-sm overflow-hidden"
             x-data="{chOpen: true, addLesson: false, addLessonType: 'video', editChapter: false, newPublished: false, newFree: false, addQuiz: false, quizPublished: false}">

            {{-- Chapter header row --}}
            <div class="flex items-center gap-3 px-5 py-4 bg-gray-50/70 cursor-pointer select-none"
                 @click="chOpen = !chOpen">
                <button type="button" class="shrink-0 w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-700">
                    <svg x-show="chOpen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                    <svg x-show="!chOpen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div class="flex-1 min-w-0">
                    <p class="font-extrabold text-gray-900 leading-tight">
                        Chapter {{ $chapterIndex + 1 }}: {{ $chapter->title }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $chapter->lessons->count() }} {{ $chapter->lessons->count() === 1 ? 'lesson' : 'lessons' }}
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0" @click.stop>
                    <button type="button" @click="editChapter = !editChapter"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-600 hover:bg-white transition-colors">
                        Edit
                    </button>
                    <form method="POST" action="{{ route('admin.learning.chapters.destroy', [$course, $chapter]) }}"
                          onsubmit="return confirm('Delete this chapter?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50 transition-colors">
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            {{-- Edit chapter form --}}
            <div x-show="editChapter" x-cloak class="border-b border-gray-100 px-5 py-4 bg-amber-50/60">
                <form method="POST" action="{{ route('admin.learning.chapters.update', [$course, $chapter]) }}">
                    @csrf @method('PATCH')
                    <div class="flex gap-3">
                        <input type="text" name="title" value="{{ old('title', $chapter->title) }}" required
                               class="flex-1 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                               placeholder="Chapter title">
                        <input type="number" name="sort_order" value="{{ $chapter->sort_order }}" min="0"
                               class="w-20 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-center focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30">
                        <button type="submit"
                                class="rounded-xl bg-[#1a5632] px-4 py-2 text-xs font-extrabold text-white hover:bg-[#0b2415]">
                            Save
                        </button>
                        <button type="button" @click="editChapter = false"
                                class="rounded-xl border border-gray-200 px-3 py-2 text-xs font-extrabold text-gray-500 hover:bg-gray-50">
                            Cancel
                        </button>
                    </div>
                    <textarea name="description" rows="2" placeholder="Chapter description (optional)"
                              class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30">{{ old('description', $chapter->description) }}</textarea>
                </form>
            </div>

            {{-- Lessons --}}
            <div x-show="chOpen">
                <div class="divide-y divide-gray-50">
                    @forelse($chapter->lessons as $lessonIndex => $lesson)

                    <div x-data="{
                            editLesson: false,
                            quizOpen: false,
                            lessonType: '{{ $lesson->type }}',
                            lessonPublished: {{ $lesson->is_published ? 'true' : 'false' }},
                            lessonFree: {{ $lesson->is_free ? 'true' : 'false' }},
                            openEdit() {
                                this.editLesson = true;
                                this.quizOpen = false;
                                if (this.lessonType === 'text')
                                    this.$nextTick(() => window.initQE('qe-edit-{{ $lesson->id }}', 'qi-edit-{{ $lesson->id }}'));
                            },
                            openQuiz() {
                                this.quizOpen = !this.quizOpen;
                                this.editLesson = false;
                            }
                         }">

                        {{-- Lesson display row --}}
                        <div x-show="!editLesson" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50/30 group transition-colors">
                            {{-- Type icon --}}
                            <span class="shrink-0 flex h-7 w-7 items-center justify-center rounded-full text-xs
                                {{ $lesson->type === 'video' ? 'bg-red-50 text-red-500' : ($lesson->type === 'audio' ? 'bg-purple-50 text-purple-500' : 'bg-blue-50 text-blue-500') }}">
                                @if($lesson->type === 'video')
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                                @elseif($lesson->type === 'audio')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @endif
                            </span>

                            {{-- Title + meta --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 leading-tight">
                                    {{ $chapterIndex + 1 }}.{{ $lessonIndex + 1 }}. {{ $lesson->title }}
                                </p>
                                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                    <span class="text-xs text-gray-400 capitalize">{{ $lesson->type }}</span>
                                    @if($lesson->is_free) <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700">FREE</span> @endif
                                    @if(!$lesson->is_published) <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-extrabold text-gray-500">DRAFT</span> @endif
                                    @if($lesson->quiz)
                                        <span class="rounded-full bg-amber-50 border border-amber-200 px-2 py-0.5 text-[10px] font-extrabold text-amber-700">
                                            Quiz: {{ $lesson->quiz->title }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-1.5 shrink-0">
                                {{-- Include Quiz button — always visible --}}
                                <button type="button" @click="openQuiz()"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold transition-all border"
                                        :class="quizOpen
                                            ? 'bg-amber-500 text-white border-amber-500'
                                            : '{{ $lesson->quiz ? 'bg-amber-50 border-amber-300 text-amber-700 hover:bg-amber-100' : 'bg-white border-dashed border-amber-300 text-amber-500 hover:bg-amber-50' }}'">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                    </svg>
                                    {{ $lesson->quiz ? 'Quiz ✓' : 'Quiz' }}
                                </button>

                                {{-- Edit / Del --}}
                                <button type="button" @click="openEdit()"
                                        class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-bold text-gray-500 hover:bg-gray-50 md:opacity-0 md:group-hover:opacity-100 transition-all">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('admin.learning.lessons.destroy', [$course, $lesson]) }}"
                                      onsubmit="return confirm('Delete this lesson?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-bold text-red-500 hover:bg-red-50 md:opacity-0 md:group-hover:opacity-100 transition-all">
                                        Del
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Quiz picker panel --}}
                        <div x-show="quizOpen" x-cloak
                             class="border-t border-amber-100 bg-gradient-to-b from-amber-50/60 to-white px-5 py-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-xs font-extrabold uppercase tracking-widest text-amber-700">Include Quiz After Lesson</p>
                                <a href="{{ route('admin.learning.quizzes.index') }}?course={{ $course->id }}"
                                   class="text-[11px] font-bold text-amber-600 hover:text-amber-800 underline">
                                    + Create new quiz
                                </a>
                            </div>

                            @php
                                $freeQuizzes = $availableQuizzes->filter(fn($q) => is_null($q->learning_lesson_id));
                            @endphp

                            @if($availableQuizzes->isEmpty())
                                <div class="rounded-xl border-2 border-dashed border-amber-200 py-5 text-center">
                                    <p class="text-sm font-bold text-amber-600">No quizzes for this course yet</p>
                                    <a href="{{ route('admin.learning.quizzes.index') }}?course={{ $course->id }}"
                                       class="mt-1 inline-block text-xs font-extrabold text-[#1a5632] hover:underline">
                                        Create a quiz →
                                    </a>
                                </div>
                            @else
                                {{-- Currently attached quiz --}}
                                @if($lesson->quiz)
                                <div class="mb-3 rounded-xl border-2 border-emerald-400 bg-emerald-50 p-3 flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-extrabold text-emerald-800 truncate">{{ $lesson->quiz->title }}</p>
                                        <p class="text-[11px] text-emerald-600">
                                            {{ $lesson->quiz->questions->count() }} questions · Pass {{ $lesson->quiz->pass_percentage }}%
                                            @if(!$lesson->quiz->is_published) <span class="font-bold text-amber-600">· DRAFT</span> @endif
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <a href="{{ route('admin.learning.quizzes.manage', $lesson->quiz) }}"
                                           class="rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-[11px] font-extrabold text-white transition-colors">
                                            Manage
                                        </a>
                                        <form method="POST" action="{{ route('admin.learning.lessons.attach-quiz', [$course, $lesson]) }}"
                                              onsubmit="return confirm('Remove this quiz from the lesson?')">
                                            @csrf
                                            <input type="hidden" name="quiz_id" value="">
                                            <button class="rounded-lg border border-red-200 px-3 py-1.5 text-[11px] font-extrabold text-red-500 hover:bg-red-50 transition-colors">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                @endif

                                {{-- Unattached quizzes available to assign --}}
                                @if($freeQuizzes->isNotEmpty())
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400 mb-2">
                                    {{ $lesson->quiz ? 'Switch to another quiz' : 'Choose a quiz' }}
                                </p>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach($freeQuizzes as $q)
                                    <form method="POST" action="{{ route('admin.learning.lessons.attach-quiz', [$course, $lesson]) }}">
                                        @csrf
                                        <input type="hidden" name="quiz_id" value="{{ $q->id }}">
                                        <button type="submit"
                                                class="w-full text-left rounded-xl border-2 border-gray-200 bg-white p-3 hover:border-amber-400 hover:bg-amber-50/50 transition-all group/q">
                                            <div class="flex items-start gap-2.5">
                                                <div class="shrink-0 w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center mt-0.5">
                                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-extrabold text-gray-800 leading-tight truncate">{{ $q->title }}</p>
                                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                                        {{ $q->questions_count }} questions · Pass {{ $q->pass_percentage }}%
                                                        @if(!$q->is_published)
                                                            <span class="text-amber-500 font-bold">· Draft</span>
                                                        @endif
                                                    </p>
                                                </div>
                                                <span class="shrink-0 rounded-lg bg-amber-500 text-white px-2 py-1 text-[10px] font-extrabold opacity-0 group-hover/q:opacity-100 transition-opacity">
                                                    Attach
                                                </span>
                                            </div>
                                        </button>
                                    </form>
                                    @endforeach
                                </div>
                                @elseif(!$lesson->quiz)
                                <p class="text-xs text-amber-600 text-center py-2">All quizzes are already attached to other lessons.<br>
                                    <a href="{{ route('admin.learning.quizzes.index') }}?course={{ $course->id }}" class="font-bold hover:underline">Create a new quiz →</a>
                                </p>
                                @endif
                            @endif
                        </div>

                        {{-- Edit lesson inline form --}}
                        <div x-show="editLesson" x-cloak class="px-5 py-4 bg-blue-50/40 border-t border-blue-100">
                            <form method="POST" action="{{ route('admin.learning.lessons.update', [$course, $lesson]) }}">
                                @csrf @method('PATCH')

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <input type="text" name="title" value="{{ old('title', $lesson->title) }}" required
                                           class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                           placeholder="Lesson title">
                                    <div class="flex gap-2">
                                        <select name="type" x-model="lessonType"
                                                @change="if(lessonType==='text') $nextTick(()=>window.initQE('qe-edit-{{ $lesson->id }}','qi-edit-{{ $lesson->id }}'))"
                                                class="flex-1 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30">
                                            <option value="video">Video</option>
                                            <option value="audio">Audio</option>
                                            <option value="text">Text</option>
                                        </select>
                                        <input type="number" name="sort_order" value="{{ $lesson->sort_order }}" min="0"
                                               class="w-16 rounded-xl border border-gray-200 bg-white px-2 py-2 text-sm text-center focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                               placeholder="#" title="Sort order">
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-3 text-sm">
                                    <input type="hidden" name="is_published" value="0">
                                    <input type="checkbox" name="is_published" value="1" x-model="lessonPublished" class="sr-only">
                                    <button type="button" @click="lessonPublished = !lessonPublished"
                                            class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-extrabold transition-all"
                                            :class="lessonPublished ? 'border-emerald-200 bg-emerald-50 text-emerald-700 ring-2 ring-emerald-100' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50'">
                                        <span class="flex h-4 w-4 items-center justify-center rounded-full"
                                              :class="lessonPublished ? 'bg-emerald-500 text-white' : 'border border-gray-300 bg-white'">
                                            <svg x-show="lessonPublished" class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                        Published
                                    </button>
                                    <input type="hidden" name="is_free" value="0">
                                    <input type="checkbox" name="is_free" value="1" x-model="lessonFree" class="sr-only">
                                    <button type="button" @click="lessonFree = !lessonFree"
                                            class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-extrabold transition-all"
                                            :class="lessonFree ? 'border-amber-200 bg-amber-50 text-amber-700 ring-2 ring-amber-100' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50'">
                                        <span class="flex h-4 w-4 items-center justify-center rounded-full"
                                              :class="lessonFree ? 'bg-amber-500 text-white' : 'border border-gray-300 bg-white'">
                                            <svg x-show="lessonFree" class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                        Free preview
                                    </button>
                                </div>

                                <div x-show="lessonType === 'video'" class="mt-3">
                                    <input type="url" name="video_url" value="{{ old('video_url', $lesson->video_url) }}"
                                           class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                           placeholder="YouTube URL — e.g. https://www.youtube.com/watch?v=...">
                                </div>
                                <div x-show="lessonType === 'audio'" class="mt-3">
                                    <input type="url" name="audio_url" value="{{ old('audio_url', $lesson->audio_url) }}"
                                           class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                           placeholder="Audio file URL (mp3, ogg, wav)">
                                </div>

                                <div class="mt-3">
                                    <textarea name="description" rows="2"
                                              class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                              placeholder="Short description shown in lesson list">{{ old('description', $lesson->description) }}</textarea>
                                </div>

                                {{-- Rich text editor (Quill) for text lessons --}}
                                <div x-show="lessonType === 'text'" class="mt-3 space-y-2">
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Lesson Content</p>
                                    <div class="course-editor-grid">
                                        <div class="course-editor-pane">
                                            <p class="course-editor-label">Editor</p>
                                            <div id="qe-edit-{{ $lesson->id }}" class="bg-white"></div>
                                            <p class="course-editor-label mt-3">Markdown / HTML Source</p>
                                            <textarea id="qs-edit-{{ $lesson->id }}" rows="8"
                                                      class="course-editor-source w-full rounded-xl border border-gray-200 bg-slate-950 px-3 py-2 font-mono text-xs leading-relaxed text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                                      spellcheck="false">{{ old('content_body', $lesson->content_body) }}</textarea>
                                        </div>
                                        <div class="course-editor-pane">
                                            <p class="course-editor-label">Live Preview</p>
                                            <div id="prev-edit-{{ $lesson->id }}"
                                                 class="course-editor-preview rounded-xl border border-gray-200 bg-white p-4 lesson-body text-sm"></div>
                                        </div>
                                    </div>
                                    <textarea name="content_body" id="qi-edit-{{ $lesson->id }}" class="hidden">{{ old('content_body', $lesson->content_body) }}</textarea>
                                    <p class="course-editor-help text-xs">Use the source box for Markdown, LaTeX, tables, SVG diagrams, and lesson HTML. Inline: <code class="bg-gray-100 px-1 rounded">$x^2$</code> · Display: <code class="bg-gray-100 px-1 rounded">$$\frac{a}{b}$$</code></p>
                                </div>

                                <div class="course-form-actions flex flex-wrap gap-2 justify-end">
                                    <button type="button" @click="editLesson = false"
                                            class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-extrabold text-gray-600 hover:bg-gray-50">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            class="rounded-xl bg-[#1a5632] px-5 py-2 text-xs font-extrabold text-white hover:bg-[#0b2415]">
                                        Save Lesson
                                    </button>
                                </div>
                            </form>

                        </div>

                    </div>

                    @empty
                        <div class="px-5 py-4 text-sm text-gray-400 italic">No lessons yet — add one below.</div>
                    @endforelse
                </div>

                {{-- Chapter footer: Add Lesson | Add Quiz --}}
                <div class="border-t border-dashed border-gray-200">
                    {{-- Toggle buttons row --}}
                    <div class="grid grid-cols-2 divide-x divide-dashed divide-gray-200">
                        <button type="button" @click="addLesson = !addLesson; addQuiz = false"
                                class="flex items-center justify-center gap-2 px-5 py-3 text-sm font-bold transition-colors"
                                :class="addLesson ? 'bg-emerald-50 text-[#0b2415]' : 'text-[#1a5632] hover:bg-emerald-50/60'">
                            <svg class="w-4 h-4 transition-transform" :class="addLesson ? 'rotate-45' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span x-text="addLesson ? 'Cancel' : 'Add Lesson'"></span>
                        </button>
                        <button type="button" @click="addQuiz = !addQuiz; addLesson = false"
                                class="flex items-center justify-center gap-2 px-5 py-3 text-sm font-bold transition-colors"
                                :class="addQuiz ? 'bg-amber-50 text-amber-900' : 'text-amber-600 hover:bg-amber-50/60'">
                            <svg class="w-4 h-4 transition-transform" :class="addQuiz ? 'rotate-45' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span x-text="addQuiz ? 'Cancel' : 'Add Quiz'"></span>
                        </button>
                    </div>

                    {{-- Add Lesson form --}}
                    <div x-show="addLesson" x-cloak class="px-5 pb-5 pt-3 bg-emerald-50/40 border-t border-emerald-100/60">
                        <form method="POST" action="{{ route('admin.learning.lessons.store', [$course, $chapter]) }}">
                            @csrf
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input type="text" name="title" required
                                       class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                       placeholder="Lesson title">
                                <div class="flex gap-2">
                                    <select name="type" x-model="addLessonType"
                                            @change="if(addLessonType==='text') $nextTick(()=>window.initQE('qe-new-{{ $chapter->id }}','qi-new-{{ $chapter->id }}'))"
                                            class="flex-1 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30">
                                        <option value="video">Video</option>
                                        <option value="audio">Audio</option>
                                        <option value="text">Text</option>
                                    </select>
                                    <input type="number" name="sort_order" min="0"
                                           class="w-16 rounded-xl border border-gray-200 bg-white px-2 py-2 text-sm text-center focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                           placeholder="#" title="Sort order">
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-3 text-sm">
                                <input type="hidden" name="is_published" value="0">
                                <input type="checkbox" name="is_published" value="1" x-model="newPublished" class="sr-only">
                                <button type="button" @click="newPublished = !newPublished"
                                        class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-extrabold transition-all"
                                        :class="newPublished ? 'border-emerald-200 bg-emerald-50 text-emerald-700 ring-2 ring-emerald-100' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50'">
                                    <span class="flex h-4 w-4 items-center justify-center rounded-full"
                                          :class="newPublished ? 'bg-emerald-500 text-white' : 'border border-gray-300 bg-white'">
                                        <svg x-show="newPublished" class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                    Published
                                </button>
                                <input type="hidden" name="is_free" value="0">
                                <input type="checkbox" name="is_free" value="1" x-model="newFree" class="sr-only">
                                <button type="button" @click="newFree = !newFree"
                                        class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-extrabold transition-all"
                                        :class="newFree ? 'border-amber-200 bg-amber-50 text-amber-700 ring-2 ring-amber-100' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50'">
                                    <span class="flex h-4 w-4 items-center justify-center rounded-full"
                                          :class="newFree ? 'bg-amber-500 text-white' : 'border border-gray-300 bg-white'">
                                        <svg x-show="newFree" class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                    Free preview
                                </button>
                            </div>
                            <div x-show="addLessonType === 'video'" class="mt-3">
                                <input type="url" name="video_url"
                                       class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                       placeholder="YouTube URL">
                            </div>
                            <div x-show="addLessonType === 'audio'" class="mt-3">
                                <input type="url" name="audio_url"
                                       class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                       placeholder="Audio file URL">
                            </div>
                            <div class="mt-3">
                                <textarea name="description" rows="2"
                                          class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                          placeholder="Short description (optional)"></textarea>
                            </div>
                            <div x-show="addLessonType === 'text'" class="mt-3 space-y-2">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Lesson Content</p>
                                <div class="course-editor-grid">
                                    <div class="course-editor-pane">
                                        <p class="course-editor-label">Editor</p>
                                        <div id="qe-new-{{ $chapter->id }}" class="bg-white"></div>
                                        <p class="course-editor-label mt-3">Markdown / HTML Source</p>
                                        <textarea id="qs-new-{{ $chapter->id }}" rows="8"
                                                  class="course-editor-source w-full rounded-xl border border-gray-200 bg-slate-950 px-3 py-2 font-mono text-xs leading-relaxed text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                                  spellcheck="false"></textarea>
                                    </div>
                                    <div class="course-editor-pane">
                                        <p class="course-editor-label">Live Preview</p>
                                        <div id="prev-new-{{ $chapter->id }}"
                                             class="course-editor-preview rounded-xl border border-gray-200 bg-white p-4 lesson-body text-sm"></div>
                                    </div>
                                </div>
                                <textarea name="content_body" id="qi-new-{{ $chapter->id }}" class="hidden"></textarea>
                                <p class="course-editor-help text-xs">Use the source box for Markdown, LaTeX, tables, SVG diagrams, and lesson HTML. Inline: <code class="bg-gray-100 px-1 rounded">$x^2$</code> · Display: <code class="bg-gray-100 px-1 rounded">$$\frac{a}{b}$$</code></p>
                            </div>
                            <div class="course-form-actions flex flex-wrap gap-2 justify-end">
                                <button type="button" @click="addLesson = false"
                                        class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-extrabold text-gray-600 hover:bg-gray-50">Cancel</button>
                                <button type="submit"
                                        class="rounded-xl bg-[#1a5632] px-5 py-2 text-xs font-extrabold text-white hover:bg-[#0b2415]">Add Lesson</button>
                            </div>
                        </form>
                    </div>

                    {{-- Add Quiz inline form --}}
                    <div x-show="addQuiz" x-cloak class="px-5 pb-5 pt-3 bg-amber-50/40 border-t border-amber-100/60">
                        <p class="text-xs font-extrabold uppercase tracking-widest text-amber-700 mb-3">New Quiz for this Chapter</p>
                        <form method="POST" action="{{ route('admin.learning.quizzes.store') }}">
                            @csrf
                            <input type="hidden" name="learning_course_id" value="{{ $course->id }}">
                            <input type="hidden" name="_redirect_manage" value="1">
                            <div class="grid gap-3 sm:grid-cols-2">
                                @if($chapter->lessons->isNotEmpty())
                                <div class="sm:col-span-2">
                                    <label class="block text-[10px] font-extrabold uppercase tracking-widest text-amber-700 mb-1">Show quiz after lesson</label>
                                    <select name="learning_lesson_id" required
                                            class="w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-amber-400/30">
                                        @foreach($chapter->lessons as $quizLesson)
                                            <option value="{{ $quizLesson->id }}" {{ $loop->last ? 'selected' : '' }}>
                                                {{ $chapterIndex + 1 }}.{{ $loop->iteration }}. {{ $quizLesson->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-[11px] font-semibold text-amber-600">Students will see this quiz directly below the selected lesson after completing that lesson.</p>
                                </div>
                                @else
                                <div class="sm:col-span-2 rounded-xl border border-amber-200 bg-white px-3 py-3 text-sm font-bold text-amber-700">
                                    Add a lesson first, then create the quiz after that lesson.
                                </div>
                                @endif
                                <input type="text" name="title" required
                                       class="rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-amber-400/30 sm:col-span-2"
                                       placeholder="Quiz title (e.g. Chapter {{ $chapterIndex + 1 }} Mock Test)">
                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase tracking-widest text-amber-700 mb-1">Pass %</label>
                                    <input type="number" name="pass_percentage" value="60" min="1" max="100"
                                           class="w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/30">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase tracking-widest text-amber-700 mb-1">Time Limit (min, optional)</label>
                                    <input type="number" name="time_limit_minutes" min="1" max="180" placeholder="No limit"
                                           class="w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/30">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase tracking-widest text-amber-700 mb-1">Max Attempts</label>
                                    <input type="number" name="max_attempts" value="3" min="1" max="10"
                                           class="w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/30">
                                </div>
                                <div class="flex items-end">
                                    <input type="hidden" name="is_published" value="0">
                                    <input type="checkbox" name="is_published" value="1" x-model="quizPublished" class="sr-only">
                                    <button type="button" @click="quizPublished = !quizPublished"
                                            class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-extrabold transition-all"
                                            :class="quizPublished ? 'border-emerald-200 bg-emerald-50 text-emerald-700 ring-2 ring-emerald-100' : 'border-amber-200 bg-white text-amber-600 hover:bg-amber-50'">
                                        <span class="flex h-4 w-4 items-center justify-center rounded-full"
                                              :class="quizPublished ? 'bg-emerald-500 text-white' : 'border border-amber-300 bg-white'">
                                            <svg x-show="quizPublished" class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                        Publish now
                                    </button>
                                </div>
                            </div>
                            <p class="mt-2 text-[11px] text-amber-600">After creating, you'll be taken to the quiz page to add questions.</p>
                            <div class="mt-3 flex gap-2 justify-end">
                                <button type="button" @click="addQuiz = false"
                                        class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-extrabold text-gray-600 hover:bg-gray-50">Cancel</button>
                                <button type="submit" @if($chapter->lessons->isEmpty()) disabled @endif
                                        class="rounded-xl px-5 py-2 text-xs font-extrabold text-white transition-colors {{ $chapter->lessons->isEmpty() ? 'cursor-not-allowed bg-gray-300' : 'bg-amber-500 hover:bg-amber-600' }}">
                                    Create Quiz & Add Questions →
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-gray-200 py-14 text-center">
                <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="font-extrabold text-gray-500">No chapters yet</p>
                <p class="mt-1 text-sm text-gray-400">Add the first chapter below to get started.</p>
            </div>
        @endforelse
    </div>

    {{-- Add chapter --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5" x-data="{open: {{ $course->chapters->isEmpty() ? 'true' : 'false' }}}">
        <button type="button" @click="open = !open"
                class="flex items-center gap-2 text-sm font-extrabold text-[#1a5632] hover:text-[#0b2415] transition-colors">
            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-45' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            <span x-text="open ? 'Cancel' : 'Add Chapter'"></span>
        </button>

        <div x-show="open" x-cloak class="mt-4">
            <form method="POST" action="{{ route('admin.learning.chapters.store', $course) }}">
                @csrf
                <div class="flex gap-3">
                    <input type="text" name="title" required
                           class="flex-1 rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                           placeholder="Chapter title (e.g. Introduction, Core Concepts)">
                    <input type="number" name="sort_order" min="0"
                           class="w-20 rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                           placeholder="Order">
                    <button type="submit"
                            class="rounded-xl bg-[#1a5632] px-5 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415] transition-colors">
                        Add Chapter
                    </button>
                </div>
                <textarea name="description" rows="2"
                          class="mt-3 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                          placeholder="Chapter description (optional)"></textarea>
            </form>
        </div>
    </div>

</div>
@endsection
