@extends('teaching_learning.teacher-workspace.layout')
@section('title','My Teaching')
@section('content')
<section class="overflow-hidden rounded-2xl p-5 text-white shadow-sm sm:p-7" style="background:linear-gradient(135deg,var(--teacher-dark),var(--teacher-primary))">
    <p class="text-[10px] font-black uppercase tracking-[.22em] text-amber-300">{{now()->format('l · F j, Y')}}</p>
    <h1 class="mt-2 text-2xl font-black sm:text-3xl">Hello, {{str(auth()->user()->name)->before(' ')}}</h1>
    <p class="mt-1 text-sm font-medium text-white/70">Your classes, attendance and active mark entry are together here.</p>
    <div class="mt-5 grid grid-cols-2 gap-2 sm:max-w-md"><div class="rounded-xl bg-white/10 p-3"><b class="text-2xl">{{$lessons->count()}}</b><p class="text-[10px] font-bold uppercase text-white/60">Classes today</p></div><div class="rounded-xl bg-white/10 p-3"><b class="text-2xl">{{$markEntries->count()}}</b><p class="text-[10px] font-bold uppercase text-white/60">Marks open</p></div></div>
</section>

<div class="mt-5 grid gap-5 lg:grid-cols-2">
    <section>
        <div class="mb-2 flex items-end justify-between"><div><p class="text-[10px] font-black uppercase tracking-widest text-emerald-700">Live routine</p><h2 class="text-lg font-black">Today's classes</h2></div><span class="text-xs font-bold text-slate-400">{{now()->format('l')}}</span></div>
        <div class="space-y-2">
            @forelse($lessons as $lesson)
            @php $end=$lesson->endPeriod?:$lesson->period;$groups=$lesson->teacher_groups; @endphp
            <article class="rounded-2xl border {{$lesson->attendance_is_open?'border-emerald-300 bg-emerald-50/60':'border-slate-200 bg-white'}} p-4 shadow-sm">
                <div class="flex items-start gap-3"><div class="w-20 shrink-0 rounded-xl {{$lesson->attendance_is_open?'bg-emerald-600 text-white':'bg-slate-100 text-slate-700'}} px-2 py-2 text-center"><b class="block text-xs">{{\Carbon\Carbon::parse($lesson->period->starts_at)->format('h:i A')}}</b><span class="text-[9px] font-bold opacity-70">to {{\Carbon\Carbon::parse($end->ends_at)->format('h:i A')}}</span></div><div class="min-w-0 flex-1"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{$lesson->plan->department->name}} · {{$lesson->section->name}}</p><h3 class="truncate text-base font-black">{{$groups->pluck('offering.subject.name')->unique()->implode(' / ')}}</h3><p class="mt-0.5 text-xs font-semibold text-slate-500">{{$groups->pluck('group_label')->filter()->implode(' · ')?:'Whole class'}} · {{$groups->flatMap->students->unique('id')->count()}} students</p></div></div>
                @if($lesson->attendance_is_open)<a href="{{route('admin.teacher.attendance',$lesson)}}" class="mt-3 flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white shadow-sm">Take attendance now <span class="ml-2">→</span></a>
                @else<div class="mt-3 rounded-xl bg-slate-50 px-3 py-2.5 text-center text-xs font-bold text-slate-400">Attendance {{now()->lt($lesson->attendance_opens_at)?'opens '.$lesson->attendance_opens_at->format('h:i A'):'closed '.$lesson->attendance_closes_at->format('h:i A')}}</div>@endif
            </article>
            @empty<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center"><p class="text-2xl">☕</p><h3 class="mt-2 text-sm font-black">No published class today</h3><p class="mt-1 text-xs text-slate-400">Your scheduled classes will appear here automatically.</p></div>@endforelse
        </div>
    </section>

    <section>
        <div class="mb-2"><p class="text-[10px] font-black uppercase tracking-widest text-purple-700">Active examinations</p><h2 class="text-lg font-black">Enter your marks</h2></div>
        <div class="space-y-2">
            @forelse($markEntries as $entry)@php $es=$entry->subject;$subject=$es->offering->subject;$isPractical=$entry->component==='practical';$code=$isPractical?($subject->practical_code?:$subject->code):$subject->code; @endphp
            <a href="{{route('admin.examinations.marks.edit',[$es,'component'=>$entry->component])}}" class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-purple-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-3"><div class="min-w-0"><span class="rounded-md {{$isPractical?'bg-purple-100 text-purple-700':'bg-blue-100 text-blue-700'}} px-2 py-1 text-[9px] font-black uppercase">{{$entry->component}}</span><h3 class="mt-2 truncate text-base font-black">{{$subject->name}} <span class="text-xs text-slate-400">{{$code}}</span></h3><p class="mt-1 text-xs font-semibold text-slate-500">{{$entry->sections}} · {{$es->examination->name}}</p></div><span class="text-xl text-slate-300 group-hover:text-purple-600">→</span></div>
                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3"><span class="text-[10px] font-bold text-slate-400">{{$entry->entered}} student records entered</span><b class="text-xs text-purple-700">Enter marks</b></div>
            </a>
            @empty<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center"><p class="text-2xl">✓</p><h3 class="mt-2 text-sm font-black">No active mark entry</h3><p class="mt-1 text-xs text-slate-400">It appears here after an exam starts and while its entry dates are active.</p></div>@endforelse
        </div>
    </section>
</div>
@endsection
