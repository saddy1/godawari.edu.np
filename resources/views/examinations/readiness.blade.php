@extends('examinations.layouts.app')
@section('title', 'Mark Entry Status — '.$examination->name)
@section('content')
<div class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white p-5 shadow-sm">
        <div>
            <h1 class="text-xl font-black">Mark Entry Status</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $examination->name }} · {{ $examination->organization->name }}</p>
            <p class="mt-2 text-xs text-gray-500">Every subject and section, who's submitted, and who hasn't yet.</p>
        </div>
        <a href="{{ route('admin.examinations.index', ['exam' => $examination->id]) }}" class="rounded-lg border px-4 py-2 text-xs font-bold">Back to exam</a>
    </header>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header class="flex flex-col gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[9px] font-black uppercase tracking-widest text-emerald-700">Administrator workspace</p>
                <h2 class="mt-0.5 text-base font-black text-slate-900">Marks entry dashboard</h2>
                <p class="mt-0.5 text-[10px] font-semibold text-slate-500">Enter marks for any subject and section. See who has not submitted their section yet.</p>
            </div>
            <span class="self-start rounded-lg bg-emerald-100 px-2.5 py-1 text-[9px] font-black text-emerald-800">{{ $marksDashboard->count() }} subjects</span>
        </header>
        <div class="divide-y divide-slate-100">
            @forelse($marksDashboard as $subjectRow)
                @php $subject = $subjectRow->subject; @endphp
                <article class="p-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-black text-slate-900">{{ $subject->offering->subject->name }}</h3>
                            <p class="text-[10px] font-bold text-slate-400">Theory: FM {{ $subject->theory_full_marks }} · PM {{ $subject->theory_pass_marks }} @if((float) $subject->practical_full_marks > 0) <span class="mx-1">•</span> Practical: FM {{ $subject->practical_full_marks }} · PM {{ $subject->practical_pass_marks }} @endif</p>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-2 lg:grid-cols-2">
                        @foreach($subjectRow->sections as $sectionRow)
                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h4 class="text-sm font-black text-slate-800">{{ $sectionRow->section->name }}</h4>
                                        <p class="text-[9px] font-semibold text-slate-400">{{ $sectionRow->students }} enrolled students</p>
                                    </div>
                                    @if($sectionRow->locked_by->isNotEmpty())
                                        <span class="rounded-md bg-emerald-100 px-2 py-1 text-[8px] font-black text-emerald-800">SUBMITTED</span>
                                    @else
                                        <span class="rounded-md bg-amber-100 px-2 py-1 text-[8px] font-black text-amber-800">PENDING</span>
                                    @endif
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($sectionRow->components as $component => $progress)
                                        <span class="rounded bg-{{ $component === 'theory' ? 'emerald' : 'purple' }}-50 px-2 py-1 text-[9px] font-black text-{{ $component === 'theory' ? 'emerald' : 'purple' }}-700">{{ ucfirst($component) }} {{ $progress['entered'] }}/{{ $progress['expected'] }}</span>
                                    @endforeach
                                </div>
                                <p class="mt-2 min-h-4 text-[9px] font-semibold {{ $sectionRow->pending_teachers->isNotEmpty() ? 'text-amber-700' : 'text-slate-400' }}">
                                    @if($sectionRow->pending_teachers->isNotEmpty())
                                        Pending: {{ $sectionRow->pending_teachers->implode(', ') }}
                                    @elseif($sectionRow->locked_by->isNotEmpty())
                                        Submitted by: {{ $sectionRow->locked_by->implode(', ') }}
                                    @else
                                        No routine teacher assigned yet.
                                    @endif
                                </p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @if((float) $subject->theory_full_marks > 0)<a href="{{ route('admin.examinations.marks.edit', [$subject, 'component' => 'theory', 'section' => $sectionRow->section->id]) }}" class="rounded-lg bg-emerald-700 px-2.5 py-1.5 text-[9px] font-black text-white">Enter theory</a>@endif
                                    @if((float) $subject->practical_full_marks > 0)<a href="{{ route('admin.examinations.marks.edit', [$subject, 'component' => 'practical', 'section' => $sectionRow->section->id]) }}" class="rounded-lg bg-purple-700 px-2.5 py-1.5 text-[9px] font-black text-white">Enter practical</a>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="p-8 text-center text-sm font-semibold text-slate-400">No configured subjects with enrolled students yet.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
