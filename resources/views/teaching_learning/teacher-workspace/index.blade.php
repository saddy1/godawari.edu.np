@extends('teaching_learning.teacher-workspace.layout')
@section('title','My Teaching')
@section('content')
@php
    $openLessons=$lessons->where('attendance_is_open',true);
    $examGroups=$markEntries->groupBy(fn($entry)=>(int)$entry->examination->id);
@endphp

<section class="flex flex-col gap-3 overflow-hidden rounded-2xl p-4 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between" style="background:linear-gradient(120deg,var(--teacher-dark),var(--teacher-primary))">
    <div><p class="text-[9px] font-black uppercase tracking-[.2em] text-amber-300">{{now()->format('l · F j, Y')}}</p><h1 class="mt-1 text-xl font-black sm:text-2xl">Hello, {{str(auth()->user()->name)->before(' ')}}</h1><p class="mt-0.5 text-xs font-semibold text-white/65">Your daily teaching work, ready in one place.</p></div>
    <div class="grid grid-cols-3 gap-2 sm:w-80"><div class="rounded-xl bg-white/10 px-3 py-2"><b class="text-lg">{{$lessons->count()}}</b><p class="text-[8px] font-bold uppercase text-white/55">Today</p></div><div class="rounded-xl {{$openLessons->isNotEmpty()?'bg-emerald-400 text-emerald-950':'bg-white/10'}} px-3 py-2"><b class="text-lg">{{$openLessons->count()}}</b><p class="text-[8px] font-bold uppercase opacity-70">Open now</p></div><div class="rounded-xl bg-white/10 px-3 py-2"><b class="text-lg">{{$examGroups->count()}}</b><p class="text-[8px] font-bold uppercase text-white/55">Exams</p></div></div>
</section>

<section id="today-classes" class="mt-5 scroll-mt-20">
    <div class="mb-3 flex items-end justify-between"><div><p class="text-[10px] font-black uppercase tracking-widest text-emerald-700">Daily priority</p><h2 class="text-xl font-black">Take attendance</h2><p class="mt-0.5 text-xs font-semibold text-slate-400">Only today’s classes appear here. Attendance opens around class time.</p></div><span class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[10px] font-black text-slate-500">{{now()->format('l')}}</span></div>

    @if($openLessons->isNotEmpty())
        @php $activeLesson=$openLessons->first();$activeEnd=$activeLesson->endPeriod?:$activeLesson->period;$activeGroups=$activeLesson->teacher_groups; @endphp
        <a href="{{route('admin.teacher.attendance',$activeLesson)}}" class="mb-3 flex items-center gap-3 rounded-2xl bg-emerald-600 p-3 text-white shadow-lg shadow-emerald-700/15 transition active:scale-[.99] sm:p-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white/15 text-xl">✓</span><span class="min-w-0 flex-1"><span class="block text-[9px] font-black uppercase tracking-widest text-emerald-100">Attendance is open now</span><b class="block truncate text-base">{{$activeGroups->pluck('offering.subject.name')->unique()->implode(' / ')}} · {{$activeLesson->section->name}}</b><small class="block text-[10px] font-semibold text-emerald-100">Tap once to start marking students</small></span><span class="text-2xl">→</span></a>
    @endif

    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($lessons as $lesson)
            @php $end=$lesson->endPeriod?:$lesson->period;$groups=$lesson->teacher_groups;$studentCount=$groups->flatMap->students->unique('id')->count(); @endphp
            <article class="flex flex-col rounded-2xl border {{$lesson->attendance_is_open?'border-emerald-300 bg-emerald-50/60':'border-slate-200 bg-white'}} p-3 shadow-sm">
                <div class="flex items-center gap-3"><div class="w-[72px] shrink-0 rounded-xl {{$lesson->attendance_is_open?'bg-emerald-600 text-white':'bg-slate-100 text-slate-700'}} px-2 py-2 text-center"><b class="block text-[11px]">{{\Carbon\Carbon::parse($lesson->period->starts_at)->format('h:i A')}}</b><span class="text-[8px] font-bold opacity-65">{{\Carbon\Carbon::parse($end->ends_at)->format('h:i A')}}</span></div><div class="min-w-0 flex-1"><p class="truncate text-[9px] font-black uppercase tracking-wider text-slate-400">{{$lesson->plan->department->name}} · {{$lesson->section->name}}</p><h3 class="truncate text-sm font-black">{{$groups->pluck('offering.subject.name')->unique()->implode(' / ')}}</h3><p class="mt-0.5 text-[10px] font-semibold text-slate-500">{{$studentCount}} students · {{$groups->pluck('group_label')->filter()->implode(' · ')?:'Whole class'}}</p></div></div>
                @if($lesson->attendance_is_open)<a href="{{route('admin.teacher.attendance',$lesson)}}" class="mt-3 flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white">Take attendance →</a>@else<div class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-center text-[10px] font-bold text-slate-400">{{now()->lt($lesson->attendance_opens_at)?'Opens '.$lesson->attendance_opens_at->format('h:i A'):'Closed at '.$lesson->attendance_closes_at->format('h:i A')}}</div>@endif
            </article>
        @empty
            <div class="col-span-full flex items-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-white p-4"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-lg">☕</span><div><h3 class="text-sm font-black">No published class today</h3><p class="mt-0.5 text-xs text-slate-400">Today’s assigned routine will appear here automatically.</p></div></div>
        @endforelse
    </div>
</section>

<section class="mt-6">
    <div class="mb-3"><p class="text-[10px] font-black uppercase tracking-widest text-purple-700">Occasional work</p><h2 class="text-xl font-black">Examination marks</h2><p class="mt-0.5 text-xs font-semibold text-slate-400">Choose an exam, then choose the subject you teach.</p></div>
    <div class="space-y-2">
        @forelse($examGroups as $entries)
            @php $exam=$entries->first()->examination; @endphp
            <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <summary class="flex cursor-pointer list-none items-center gap-3 p-4 marker:content-none"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-purple-100 text-purple-700">▣</span><span class="min-w-0 flex-1"><b class="block truncate text-sm text-slate-900">{{$exam->name}}</b><small class="mt-0.5 block text-[10px] font-semibold text-slate-400">{{$entries->count()}} {{Str::plural('subject',$entries->count())}} · {{$exam->starts_on?->format('M d')??'—'}} – {{$exam->ends_on?->format('M d, Y')??'—'}}</small></span><span class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-slate-500 transition group-open:rotate-180">⌄</span></summary>
                <div class="grid gap-2 border-t border-slate-100 bg-slate-50/70 p-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($entries as $entry)@php $es=$entry->subject;$subject=$es->offering->subject; @endphp
                        <a href="{{route('admin.examinations.marks.edit',[$es,'component'=>$entry->entry_component])}}" class="group/subject rounded-xl border border-slate-200 bg-white p-3 transition hover:border-purple-300 hover:shadow-sm"><div class="flex items-start justify-between gap-2"><div class="min-w-0"><h3 class="truncate text-sm font-black text-slate-900">{{$subject->name}}</h3><p class="mt-0.5 text-[10px] font-bold text-slate-400">{{$subject->code}} · {{$entry->sections}}</p></div><span class="text-lg text-slate-300 group-hover/subject:text-purple-600">→</span></div><div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2"><span class="text-[9px] font-bold text-slate-400">{{$entry->entered}} records entered</span><b class="text-[10px] text-purple-700">Enter marks</b></div></a>
                    @endforeach
                </div>
            </details>
        @empty
            <div class="flex items-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-white p-4"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-100">✓</span><div><h3 class="text-sm font-black">No active mark entry</h3><p class="mt-0.5 text-xs text-slate-400">Exams appear only during their active mark-entry period.</p></div></div>
        @endforelse
    </div>
</section>
@endsection
