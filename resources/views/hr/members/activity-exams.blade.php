@extends('hr.layouts.app')
@section('title', $member->full_name.' — Exams & Results')
@section('content')
<div class="space-y-4" x-data="{ open: {{ $results->first()->examination->id ?? 'null' }} }">
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm">
        <div class="flex items-center gap-4">
            <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="h-14 w-14 rounded-xl object-cover ring-2 ring-white/20">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-widest text-white/50">Student 360 · Exams &amp; Results</p>
                <h1 class="truncate text-xl font-extrabold">{{ $member->full_name }}</h1>
                <p class="text-xs font-semibold text-white/60">{{ $member->roll_number }} · {{ $member->stream }}{{ $member->section ? ' - '.$member->section : '' }}</p>
            </div>
        </div>
    </div>

    @include('hr.members._activity-tabs')

    @if($results->isEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-sm">
            <p class="text-sm font-bold text-gray-600">No exams recorded yet</p>
            <p class="mt-1 text-xs font-semibold text-gray-400">This student has not been issued a symbol number for any exam.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($results as $row)
                @php
                    $exam = $row->examination;
                    $result = $row->result;
                    $grouped = collect($result['subjectRows'])->groupBy('subject_id');
                @endphp
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <button type="button" @click="open = open === {{ $exam->id }} ? null : {{ $exam->id }}"
                            class="flex w-full flex-wrap items-center justify-between gap-3 p-4 text-left">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-extrabold text-gray-950">{{ $exam->name }}</p>
                            <p class="mt-1 text-xs font-semibold text-gray-400">{{ $exam->organization->name }} · {{ $exam->academicYear->name }} · Symbol No. {{ $row->symbol_no ?: '—' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $row->published ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $row->published ? 'Published to student' : 'Not published yet' }}</span>
                            @if($result['percentage'] !== null)
                                <div class="text-right">
                                    <p class="text-base font-black text-[#1a5632]">{{ $result['overall_grade'] }}</p>
                                    <p class="text-[10px] font-bold text-gray-400">{{ $result['percentage'] }}%</p>
                                </div>
                            @endif
                            <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="open === {{ $exam->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </button>

                    <div x-show="open === {{ $exam->id }}" x-cloak x-transition class="space-y-4 border-t border-gray-100 px-4 pb-4 pt-3">
                        @if(auth()->user()?->canAccess(['examinations.manage', 'examinations.reports']) && $row->symbol_no)
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.examinations.marksheets.print', [$exam, 'student_id' => $member->id]) }}" target="_blank"
                                   class="inline-flex items-center gap-1.5 rounded-xl bg-[#1a5632] px-3.5 py-2 text-xs font-extrabold text-white hover:bg-[#0b2415]">
                                    View Official Marksheet
                                </a>
                                <a href="{{ route('admin.examinations.marksheets.download-one', [$exam, $member]) }}"
                                   class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-extrabold text-gray-700 hover:bg-gray-50">
                                    Download PDF
                                </a>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-xl bg-gray-50 p-3 text-center">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Total</p>
                                <p class="mt-1 text-sm font-extrabold text-gray-900">{{ $result['total_obtained'] }}/{{ $result['total_full'] }}</p>
                            </div>
                            <div class="rounded-xl bg-gray-50 p-3 text-center">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">GPA</p>
                                <p class="mt-1 text-sm font-extrabold text-gray-900">{{ $result['gpa'] ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl p-3 text-center {{ $result['overall_grade'] === 'NG' ? 'bg-red-50' : 'bg-emerald-50' }} col-span-2 sm:col-span-2">
                                <p class="text-[10px] font-bold uppercase tracking-wide {{ $result['overall_grade'] === 'NG' ? 'text-red-500' : 'text-emerald-600' }}">Overall Grade</p>
                                <p class="mt-1 text-sm font-extrabold {{ $result['overall_grade'] === 'NG' ? 'text-red-700' : 'text-emerald-700' }}">{{ $result['overall_grade'] ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-gray-100">
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
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
