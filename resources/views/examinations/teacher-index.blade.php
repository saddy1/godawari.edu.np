@extends('teaching_learning.teacher-workspace.layout')
@section('title','My Exams')
@section('content')
<div class="mx-auto max-w-5xl space-y-3">
    <div class="flex items-end justify-between gap-3"><div><p class="text-[9px] font-black uppercase tracking-[.18em] text-purple-700">My responsibility</p><h1 class="text-xl font-black sm:text-2xl">Enter marks</h1><p class="text-[10px] font-semibold text-slate-500 sm:text-xs">Your assigned subjects from the published routine.</p></div><span class="rounded-full bg-purple-100 px-3 py-1.5 text-[10px] font-black text-purple-700">{{$entries->count()}} subjects</span></div>

    @if($examinations->count()>1)<div class="flex gap-2 overflow-x-auto pb-1">@foreach($examinations as $exam)<a href="{{route('admin.examinations.index',['exam'=>$exam->id])}}" class="shrink-0 rounded-xl border px-3 py-2 text-xs font-black {{$selectedExam?->id===$exam->id?'border-purple-600 bg-purple-600 text-white':'border-slate-200 bg-white text-slate-600'}}">{{$exam->name}}</a>@endforeach</div>@endif

    @if($selectedExam)
    <section class="rounded-2xl bg-slate-900 p-3.5 text-white shadow-sm"><div class="flex items-start justify-between gap-3"><div class="min-w-0 flex-1"><p class="text-[8px] font-black uppercase tracking-widest text-purple-300">{{$selectedExam->organization->name}}</p><h2 class="mt-0.5 truncate text-base font-black sm:text-lg">{{$selectedExam->name}}</h2><p class="mt-0.5 text-[9px] font-semibold text-white/60">{{$selectedExam->starts_on?->format('M d')??'No start date'}} – {{$selectedExam->ends_on?->format('M d, Y')??'No end date'}}</p></div><span class="h-fit shrink-0 rounded-lg {{$entryOpen?'bg-emerald-500':'bg-amber-400 text-amber-950'}} px-2.5 py-1.5 text-[8px] font-black uppercase">{{$entryOpen?'Entry open':$selectedExam->status}}</span></div></section>

    @unless($entryOpen)<div class="rounded-2xl border border-amber-200 bg-amber-50 p-4"><p class="text-sm font-black text-amber-900">Mark entry is not open</p><p class="mt-1 text-xs font-semibold text-amber-700">@if($selectedExam->status!=='ongoing')The examination manager must change this exam to Ongoing.@elseif($selectedExam->starts_on&&now()->startOfDay()->lt($selectedExam->starts_on))Entry opens on {{$selectedExam->starts_on->format('M d, Y')}}.@else The mark-entry period has ended.@endif</p></div>@endunless

    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($entries as $entry)@php $es=$entry->subject;$subject=$es->offering->subject;$submission=$entry->submission;$submitted=$submission?->is_locked;$unlockPending=$submission?->unlock_is_pending; @endphp
        <article class="rounded-2xl border {{$submitted?'border-emerald-200 bg-emerald-50/30':'border-slate-200 bg-white'}} p-3 shadow-sm">
            <div class="flex items-start justify-between gap-2"><span class="rounded-lg {{$submitted?'bg-emerald-600 text-white':'bg-emerald-100 text-emerald-700'}} px-2 py-1 text-[8px] font-black uppercase">{{$unlockPending?'Unlock requested':($submitted?'Submitted & locked':'Combined marks')}}</span><span class="text-[9px] font-black text-slate-400">{{$entry->theory_entered}} records</span></div>
            <h3 class="mt-2 text-base font-black">{{$subject->name}}</h3>
            <div class="mt-2 flex flex-wrap gap-1.5">@if($entry->has_theory)<span class="rounded-lg bg-emerald-50 px-2 py-1 text-[10px] font-black text-emerald-700">TH {{$subject->code}} · {{$es->theory_full_marks}}</span>@endif @if($entry->has_practical)<span class="rounded-lg bg-purple-50 px-2 py-1 text-[10px] font-black text-purple-700">PR {{$subject->practical_code?:$subject->code}} · {{$es->practical_full_marks}}</span>@endif</div>
            <p class="mt-2 text-[10px] font-semibold text-slate-400">{{$es->offering->department->name}} · {{$entry->sections}}</p>
            @if($entryOpen)<a href="{{route('admin.examinations.marks.edit',[$es,'component'=>$entry->has_theory?'theory':'practical'])}}" class="mt-3 flex w-full items-center justify-center rounded-xl {{$submitted?'bg-emerald-700':'bg-slate-900'}} px-4 py-2.5 text-xs font-black text-white">{{$submitted?'View submitted marks':'Enter all marks →'}}</a>@else<div class="mt-3 rounded-xl bg-slate-100 px-4 py-2.5 text-center text-xs font-black text-slate-400">Entry closed</div>@endif
        </article>
        @empty<div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"><p class="text-2xl">✓</p><h3 class="mt-2 text-sm font-black">No assigned subjects</h3><p class="mt-1 text-xs text-slate-400">Subjects appear after you are assigned to this section in Routine Builder.</p></div>@endforelse
    </div>
    @else<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"><h2 class="text-sm font-black">No examination assigned</h2><p class="mt-1 text-xs text-slate-400">Your exams will appear automatically from the published routine.</p></div>@endif
</div>
@endsection
