@extends('learning.layouts.admin')

@section('title', 'Quizzes')

@php
    $inp = 'w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
@endphp

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 sm:p-6 text-white shadow-sm">
        <p class="text-sm font-bold uppercase tracking-widest text-white/50">E-Learning</p>
        <h1 class="mt-1 text-3xl font-extrabold">Mock Tests & Quizzes</h1>
        <p class="mt-2 text-sm font-medium text-white/70">Create MCQ and short-answer quizzes to test student understanding after lessons.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    {{-- Create quiz form --}}
    @if(auth()->user()?->canAccess('learning.lessons.edit'))
    <form method="POST" action="{{ route('admin.learning.quizzes.store') }}"
          class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden"
          x-data="{
            selClass: '',
            selSubject: '',
            selCourse: '{{ request('course') }}',
            isPublished: false,
            addFirstQuestion: false,
            qType: 'mcq',
            options: ['', '', '', ''],
            correctIdx: 0,
            allSubjects: {{ $subjects->toJson() }},
            allCourses:  {{ $courses->toJson() }},
            get filteredSubjects() {
                if (!this.selClass) return [];
                return this.allSubjects.filter(s => s.learning_class_id == this.selClass);
            },
            get filteredCourses() {
                return this.allCourses.filter(c => {
                    if (this.selClass && c.learning_class_id != this.selClass) return false;
                    if (this.selSubject && c.learning_subject_id != this.selSubject) return false;
                    return true;
                });
            }
          }">
        @csrf

        {{-- Header --}}
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/60">
            <h2 class="text-base font-extrabold text-gray-900">Create New Quiz</h2>
            <p class="text-xs text-gray-400 mt-0.5">Select class → subject → course, then fill quiz details.</p>
        </div>

        <div class="p-5 sm:p-6 space-y-5">

            {{-- Step 1 · Class --}}
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-500 mb-1.5">
                    ① Class <span class="text-red-400">*</span>
                </label>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                    @foreach($classes as $cls)
                    <button type="button"
                            @click="selClass = '{{ $cls->id }}'; selSubject = ''; selCourse = ''"
                            class="rounded-xl border-2 px-4 py-2 text-sm font-bold transition-all"
                            :class="selClass == '{{ $cls->id }}'
                                ? 'border-[#1a5632] bg-[#1a5632] text-white'
                                : 'border-gray-200 bg-white text-gray-600 hover:border-[#1a5632]/40 hover:text-[#1a5632]'">
                        {{ $cls->name }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Step 2 · Subject --}}
            <div x-show="selClass" x-cloak>
                <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-500 mb-1.5">
                    ② Subject <span class="text-red-400">*</span>
                </label>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap"
                     x-show="filteredSubjects.length > 0">
                    <template x-for="s in filteredSubjects" :key="s.id">
                        <button type="button"
                                @click="selSubject = s.id; selCourse = ''"
                                class="rounded-xl border-2 px-4 py-2 text-sm font-bold transition-all"
                                :class="selSubject == s.id
                                    ? 'border-[#1a5632] bg-[#1a5632] text-white'
                                    : 'border-gray-200 bg-white text-gray-600 hover:border-[#1a5632]/40 hover:text-[#1a5632]'"
                                x-text="s.name">
                        </button>
                    </template>
                </div>
                <p x-show="filteredSubjects.length === 0"
                   class="text-sm text-gray-400 italic">No subjects found for this class.</p>
            </div>

            {{-- Step 3 · Course --}}
            <div x-show="selSubject" x-cloak>
                <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-500 mb-1.5">
                    ③ Course <span class="text-red-400">*</span>
                </label>
                <input type="hidden" name="learning_course_id" :value="selCourse">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                     x-show="filteredCourses.length > 0">
                    <template x-for="c in filteredCourses" :key="c.id">
                        <button type="button"
                                @click="selCourse = c.id"
                                class="rounded-xl border-2 px-4 py-2.5 text-sm font-bold text-left transition-all"
                                :class="selCourse == c.id
                                    ? 'border-[#1a5632] bg-[#1a5632] text-white'
                                    : 'border-gray-200 bg-white text-gray-600 hover:border-[#1a5632]/40 hover:text-[#1a5632]'"
                                x-text="c.title">
                        </button>
                    </template>
                </div>
                <p x-show="filteredCourses.length === 0"
                   class="text-sm text-gray-400 italic">No courses found for this subject.</p>
            </div>

            {{-- Step 4 · Quiz details (only after course selected) --}}
            <div x-show="selCourse" x-cloak class="space-y-4 border-t border-gray-100 pt-5">
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-500">④ Quiz Details</p>

                <input name="title" required placeholder="Quiz title (e.g. Chapter 1 Mock Test)"
                       class="{{ $inp }}">

                <textarea name="description" rows="2" placeholder="Short description (optional)"
                          class="{{ $inp }}"></textarea>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-500 mb-1">Time Limit (min)</label>
                        <input type="number" name="time_limit_minutes" min="1" max="180" placeholder="No limit"
                               class="{{ $inp }}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-500 mb-1">Pass %</label>
                        <input type="number" name="pass_percentage" value="60" min="1" max="100"
                               class="{{ $inp }}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-widest text-gray-500 mb-1">Max Attempts</label>
                        <input type="number" name="max_attempts" value="3" min="1" max="10"
                               class="{{ $inp }}">
                    </div>
                </div>

                {{-- Optional first question --}}
                <div class="rounded-2xl border border-dashed border-amber-200 bg-amber-50/40 p-4"
                     :class="addFirstQuestion ? 'border-solid bg-white' : ''">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-amber-700">⑤ First Question</p>
                            <p class="mt-0.5 text-xs font-semibold text-gray-500">Optional. You can add more questions after creating the quiz.</p>
                        </div>
                        <button type="button"
                                @click="addFirstQuestion = ! addFirstQuestion"
                                class="inline-flex items-center justify-center rounded-xl border px-4 py-2 text-xs font-extrabold transition"
                                :class="addFirstQuestion ? 'border-red-200 bg-red-50 text-red-700' : 'border-amber-200 bg-white text-amber-700 hover:bg-amber-50'">
                            <span x-text="addFirstQuestion ? 'Remove Question' : '+ Add Question Now'"></span>
                        </button>
                    </div>

                    <div x-show="addFirstQuestion" x-cloak class="mt-4 space-y-3">
                        <textarea name="first_question[question_text]" rows="3"
                                  :required="addFirstQuestion"
                                  class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                  placeholder="Question text..."></textarea>

                        <div class="grid gap-2 sm:grid-cols-[1fr_120px_1fr]">
                            <select name="first_question[type]" x-model="qType"
                                    :required="addFirstQuestion"
                                    class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30">
                                <option value="mcq">Multiple Choice (MCQ)</option>
                                <option value="short_answer">Short Answer</option>
                            </select>
                            <input type="number" name="first_question[marks]" value="1" min="1"
                                   :required="addFirstQuestion"
                                   class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30"
                                   placeholder="Marks">
                            <input name="first_question[explanation]" placeholder="Explanation (optional)"
                                   class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/30">
                        </div>

                        <div x-show="qType === 'mcq'" class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-500">Answer Options</p>
                                <p class="text-[11px] font-semibold text-gray-400">Mark the correct answer</p>
                            </div>
                            <template x-for="(opt, i) in options" :key="i">
                                <div class="flex items-center gap-2 rounded-xl border-2 px-3 py-2 transition"
                                     :class="correctIdx == i ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 bg-white hover:border-gray-300'">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-extrabold"
                                          :class="correctIdx == i ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-500'"
                                          x-text="String.fromCharCode(65 + i)"></span>
                                    <input :name="'first_question[options][' + i + ']'" x-model="options[i]"
                                           :required="addFirstQuestion && qType === 'mcq' && i < 2"
                                           :placeholder="'Option ' + String.fromCharCode(65 + i)"
                                           class="min-w-0 flex-1 bg-transparent text-sm font-semibold focus:outline-none placeholder:text-gray-300">
                                    <button type="button" @click="correctIdx = i"
                                            class="shrink-0 rounded-lg px-2.5 py-1 text-[11px] font-extrabold transition"
                                            :class="correctIdx == i ? 'bg-emerald-500 text-white' : 'border border-gray-200 text-gray-400 hover:border-emerald-400 hover:text-emerald-600'">
                                        <span x-text="correctIdx == i ? 'Correct' : 'Mark'"></span>
                                    </button>
                                </div>
                            </template>
                            <input type="hidden" name="first_question[correct_option]" :value="correctIdx">
                            <button type="button" @click="options.push('')"
                                    class="text-xs font-bold text-blue-600 hover:text-blue-800">+ Add another option</button>
                        </div>
                    </div>
                </div>

                {{-- Publish toggle + Submit --}}
                <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" x-model="isPublished" class="sr-only">
                    <button type="button" @click="isPublished = !isPublished"
                            class="inline-flex items-center gap-2 rounded-full border-2 px-4 py-2 text-sm font-extrabold transition-all"
                            :class="isPublished
                                ? 'border-emerald-400 bg-emerald-50 text-emerald-700'
                                : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300'">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full transition-all"
                              :class="isPublished ? 'bg-emerald-500 text-white' : 'border-2 border-gray-300'">
                            <svg x-show="isPublished" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        Publish immediately
                    </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit"
                                class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-extrabold text-gray-700 hover:bg-gray-50">
                            Create Quiz
                        </button>
                        <button type="submit" name="_redirect_manage" value="1"
                                class="rounded-xl bg-[#1a5632] px-6 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415] transition-colors shadow-sm">
                            Create & Manage Questions →
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
    @endif

    {{-- Filter bar --}}
    <form method="GET" id="quizFilterForm"
          class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
          x-data="{
            cls: '{{ request('class') }}',
            subj: '{{ request('subject') }}',
            allSubjects: {{ $subjects->toJson() }},
            get filteredSubjects() {
                if (!this.cls) return this.allSubjects;
                return this.allSubjects.filter(s => s.learning_class_id == this.cls);
            }
          }">
        <div class="flex flex-wrap gap-3 items-end">
            {{-- Class filter --}}
            <div class="flex flex-col gap-1 min-w-40">
                <label class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Class</label>
                <select name="class" x-model="cls" @change="subj = ''; $nextTick(() => $el.form.submit())"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    <option value="">All Classes</option>
                    @foreach($classes as $cls)
                        <option value="{{ $cls->id }}" {{ request('class') == $cls->id ? 'selected' : '' }}>{{ $cls->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Subject filter --}}
            <div class="flex flex-col gap-1 min-w-40">
                <label class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Subject</label>
                <select name="subject" x-model="subj" @change="$el.form.submit()"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    <option value="">All Subjects</option>
                    <template x-for="s in filteredSubjects" :key="s.id">
                        <option :value="s.id" :selected="s.id == subj" x-text="s.name"></option>
                    </template>
                </select>
            </div>

            {{-- Course filter --}}
            <div class="flex flex-col gap-1 min-w-45">
                <label class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Course</label>
                <select name="course" @change="$el.form.submit()"
                        class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course') == $c->id ? 'selected' : '' }}>{{ $c->title }}</option>
                    @endforeach
                </select>
            </div>

            @if(request()->hasAny(['class', 'subject', 'course']))
                <a href="{{ route('admin.learning.quizzes.index') }}"
                   class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-500 hover:bg-gray-50 self-end">
                    Clear filters
                </a>
            @endif
        </div>
    </form>

    {{-- Quiz list --}}
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase text-gray-500">Quiz</th>
                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase text-gray-500 hidden sm:table-cell">Class · Subject</th>
                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase text-gray-500">Course</th>
                        <th class="px-5 py-3 text-center text-xs font-extrabold uppercase text-gray-500">Questions</th>
                        <th class="px-5 py-3 text-center text-xs font-extrabold uppercase text-gray-500">Attempts</th>
                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($quizzes as $quiz)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-extrabold text-gray-900">{{ $quiz->title }}</p>
                            <div class="flex flex-wrap gap-2 mt-1 text-xs text-gray-400">
                                @if($quiz->time_limit_minutes) <span>⏱ {{ $quiz->time_limit_minutes }}min</span> @endif
                                <span>Pass: {{ $quiz->pass_percentage }}%</span>
                                <span>Attempts: {{ $quiz->max_attempts }}</span>
                            </div>
                            @if($quiz->description) <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $quiz->description }}</p> @endif
                        </td>
                        <td class="px-5 py-4 hidden sm:table-cell">
                            @if($quiz->course)
                                <p class="text-xs font-bold text-gray-700">{{ $quiz->course->learningClass->name ?? '—' }}</p>
                                @if($quiz->course->subject)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $quiz->course->subject->name }}</p>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-gray-600">{{ $quiz->course?->title ?? '—' }}</td>
                        <td class="px-5 py-4 text-center font-bold text-gray-700">{{ $quiz->questions_count }}</td>
                        <td class="px-5 py-4 text-center font-bold text-gray-700">{{ $quiz->attempts_count }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $quiz->is_published ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $quiz->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.learning.quizzes.manage', $quiz) }}"
                                   class="inline-flex items-center gap-1 rounded-xl bg-[#1a5632] px-3 py-1.5 text-xs font-extrabold text-white hover:bg-[#0b2415] transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Manage
                                </a>
                                @if(auth()->user()?->canAccess('learning.lessons.edit'))
                                <form method="POST" action="{{ route('admin.learning.quizzes.destroy', $quiz) }}"
                                      onsubmit="return confirm('Delete this quiz and all its questions?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-xl border border-red-200 px-3 py-1.5 text-xs font-extrabold text-red-600 hover:bg-red-50 transition-colors">
                                        Delete
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center">
                            <div class="text-gray-300 mb-3">
                                <svg class="mx-auto w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                            </div>
                            <p class="font-extrabold text-gray-500">No quizzes yet</p>
                            <p class="text-sm text-gray-400 mt-1">Create the first mock test above.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quizzes->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">{{ $quizzes->links() }}</div>
        @endif
    </div>

</div>
@endsection
