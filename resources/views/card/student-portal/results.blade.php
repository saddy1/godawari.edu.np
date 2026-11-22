@extends('card.student-portal.layout')
@section('title', 'My Exams')

@section('content')
@php
    $publishedCount = $results->filter(fn ($row) => $row->published)->count();
@endphp
<div class="space-y-6" x-data="{ open: {{ $results->first()->examination->id ?? 'null' }} }">
    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm sm:p-6">
        <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-[#e2a024]">Student Portal</p>
        <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">My Exams</h1>
        <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-white/65">
            Exam-day attendance and results — all in one place. Marksheets appear automatically once your school publishes them for your faculty.
        </p>

        <div class="mt-5 grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-white/10 bg-white/10 p-3 text-center backdrop-blur-sm">
                <p class="text-xl font-extrabold">{{ $results->count() }}</p>
                <p class="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-white/55">Exams</p>
            </div>
            <div class="rounded-xl border border-white/10 p-3 text-center backdrop-blur-sm {{ $publishedCount > 0 ? 'bg-[#e2a024]/20' : 'bg-white/10' }}">
                <p class="text-xl font-extrabold {{ $publishedCount > 0 ? 'text-[#e2a024]' : '' }}">{{ $publishedCount }}</p>
                <p class="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-white/55">Results Published</p>
            </div>
        </div>
    </section>

    @if($results->isEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m4-5H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2zM8 3v3m8-3v3"/></svg>
            </div>
            <p class="mt-4 text-sm font-bold text-gray-600">No exams yet</p>
            <p class="mt-1 text-xs font-semibold text-gray-400">Once your school assigns you to an exam, it'll show up here.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($results as $row)
                @php
                    $exam = $row->examination;
                    $result = $row->result;
                    $grouped = $result ? collect($result['subjectRows'])->groupBy('subject_id') : collect();
                @endphp
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <button type="button" @click="open = open === {{ $exam->id }} ? null : {{ $exam->id }}"
                            class="flex w-full flex-wrap items-center justify-between gap-3 p-4 text-left sm:p-5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-extrabold text-gray-950">{{ $exam->name }}</p>
                            <p class="mt-1 text-xs font-semibold text-gray-400">{{ $exam->organization->name }} · {{ $exam->academicYear->name }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($row->published && $result['percentage'] !== null)
                                <div class="text-right">
                                    <p class="text-lg font-black text-[#1a5632]">{{ $result['overall_grade'] }}</p>
                                    <p class="text-[10px] font-bold text-gray-400">{{ $result['percentage'] }}%</p>
                                </div>
                            @elseif($row->published)
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-[10px] font-extrabold text-amber-700">Incomplete</span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-[10px] font-extrabold text-gray-500">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Awaiting results
                                </span>
                            @endif
                            <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="open === {{ $exam->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </button>

                    <div x-show="open === {{ $exam->id }}" x-cloak x-transition class="space-y-5 border-t border-gray-100 px-4 pb-5 pt-4 sm:px-5">
                        {{-- Result / marksheet --}}
                        @if($row->published && $result)
                            <div>
                                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Marksheet</p>
                                    <a href="{{ route('student.marksheet', $exam) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 rounded-xl bg-[#1a5632] px-3.5 py-2 text-xs font-extrabold text-white hover:bg-[#0b2415]">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View Marksheet
                                    </a>
                                </div>
                                <p class="mb-3 text-[11px] font-semibold text-gray-400">The summary below is a quick glance — the full marksheet above is in the school's standard, signed format.</p>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div class="rounded-xl bg-gray-50 p-3 text-center">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Symbol No.</p>
                                        <p class="mt-1 text-sm font-extrabold text-gray-900">{{ $result['symbol_no'] ?: '—' }}</p>
                                    </div>
                                    <div class="rounded-xl bg-gray-50 p-3 text-center">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Total</p>
                                        <p class="mt-1 text-sm font-extrabold text-gray-900">{{ $result['total_obtained'] }}/{{ $result['total_full'] }}</p>
                                    </div>
                                    <div class="rounded-xl bg-gray-50 p-3 text-center">
                                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">GPA</p>
                                        <p class="mt-1 text-sm font-extrabold text-gray-900">{{ $result['gpa'] ?? '—' }}</p>
                                    </div>
                                    <div class="rounded-xl p-3 text-center {{ $result['overall_grade'] === 'NG' ? 'bg-red-50' : 'bg-emerald-50' }}">
                                        <p class="text-[10px] font-bold uppercase tracking-wide {{ $result['overall_grade'] === 'NG' ? 'text-red-500' : 'text-emerald-600' }}">Overall Grade</p>
                                        <p class="mt-1 text-sm font-extrabold {{ $result['overall_grade'] === 'NG' ? 'text-red-700' : 'text-emerald-700' }}">{{ $result['overall_grade'] ?? '—' }}</p>
                                    </div>
                                </div>

                                <div class="mt-3 overflow-x-auto rounded-xl border border-gray-100">
                                    <table class="w-full min-w-[480px] text-left text-xs">
                                        <thead class="bg-gray-50 text-[10px] font-extrabold uppercase tracking-wide text-gray-400">
                                            <tr>
                                                <th class="px-3 py-2">Subject</th>
                                                <th class="px-3 py-2">TH</th>
                                                <th class="px-3 py-2">IN</th>
                                                <th class="px-3 py-2">Grade</th>
                                                <th class="px-3 py-2">Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                                            @foreach($grouped as $subjectId => $components)
                                                @php
                                                    $theory = $components->firstWhere('component', 'theory');
                                                    $practical = $components->firstWhere('component', 'practical');
                                                    $finalGrade = $theory['final_grade'] ?? $practical['final_grade'] ?? null;
                                                @endphp
                                                <tr>
                                                    <td class="px-3 py-2 font-extrabold text-gray-900">{{ $theory['name'] ?? $practical['name'] }}</td>
                                                    <td class="px-3 py-2">{{ $theory ? ($theory['absent'] ? 'Abs' : ($theory['obtained'] ?? '—').'/'.$theory['full']) : '—' }}</td>
                                                    <td class="px-3 py-2">{{ $practical ? ($practical['absent'] ? 'Abs' : ($practical['obtained'] ?? '—').'/'.$practical['full']) : '—' }}</td>
                                                    <td class="px-3 py-2">
                                                        <span class="rounded px-2 py-0.5 text-[10px] font-extrabold {{ $finalGrade === 'NG' || $finalGrade === 'Abs' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $finalGrade ?? '—' }}</span>
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-400">{{ $theory['remarks'] ?? $practical['remarks'] ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @elseif($row->symbol_no > 0)
                            <div class="flex items-center gap-2 rounded-xl border border-amber-100 bg-amber-50 px-3.5 py-2.5 text-xs font-bold text-amber-700">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Results for this exam haven't been published for your faculty yet.
                            </div>
                        @endif

                        {{-- Exam-day attendance, marked by the invigilating teacher --}}
                        @if($row->attendance->isNotEmpty())
                            <div>
                                <p class="mb-2 text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Exam Attendance <span class="normal-case font-semibold text-gray-300">· marked by your invigilating teacher, not the biometric device</span></p>
                                <div class="overflow-x-auto rounded-xl border border-gray-100">
                                    <table class="w-full min-w-[520px] text-left text-xs">
                                        <thead class="bg-gray-50 text-[10px] font-extrabold uppercase tracking-wide text-gray-400">
                                            <tr>
                                                <th class="px-3 py-2">Date</th>
                                                <th class="px-3 py-2">Subject</th>
                                                <th class="px-3 py-2">Status</th>
                                                <th class="px-3 py-2">Reason</th>
                                                <th class="px-3 py-2">Marked by</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 font-semibold text-gray-700">
                                            @foreach($row->attendance as $log)
                                                <tr>
                                                    <td class="px-3 py-2">{{ $log->exam_date->format('d M Y') }}</td>
                                                    <td class="px-3 py-2">{{ $log->examinationSubject?->offering?->subject?->name ?? '—' }}</td>
                                                    <td class="px-3 py-2">
                                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-extrabold {{ $log->status === 'present' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                                            <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                                                            {{ ucfirst($log->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-400">{{ $log->status === 'absent' ? ($log->reason ?: '—') : '—' }}</td>
                                                    <td class="px-3 py-2 text-gray-400">{{ $log->markedBy?->name ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
