@extends('examinations.layouts.app')
@section('title','Marks Entry')
@section('content')
<div class="mx-auto max-w-6xl space-y-3 sm:space-y-4">
    <section class="rounded-2xl bg-gradient-to-r from-[#0b2415] to-[#1a5632] p-4 text-white shadow-sm"><p class="text-[9px] font-black uppercase tracking-widest text-amber-300">Administrator workspace</p><h1 class="mt-1 text-xl font-black">Marks Entry</h1><p class="mt-1 text-xs font-semibold text-white/65">Choose an exam, then a subject, then a section to enter marks.</p></section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{route('admin.examinations.marks.dashboard')}}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <label class="sm:col-span-2 lg:col-span-1"><span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-500">Step 1 · Exam</span><select name="exam" onchange="this.form.submit()" class="h-12 w-full rounded-xl border-slate-200 text-sm font-bold"><option value="">Choose exam</option>@foreach($exams as $item)<option value="{{$item->id}}" @selected($exam?->id===$item->id)>{{$item->name}} · {{$item->academicYear->name}}</option>@endforeach</select></label>
            <label><span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-500">Faculty (narrow list)</span><select name="faculty" onchange="this.form.submit()" @disabled(!$exam) class="h-12 w-full rounded-xl border-slate-200 text-sm font-bold"><option value="">All faculties</option>@foreach($faculties as $faculty)<option value="{{$faculty->id}}" @selected($facultyId===$faculty->id)>{{$faculty->name}}</option>@endforeach</select></label>
            <label class="sm:col-span-2 lg:col-span-1"><span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-500">Step 2 · Subject</span><select name="subject" onchange="this.form.submit()" @disabled(!$exam) class="h-12 w-full rounded-xl border-slate-200 text-sm font-bold"><option value="">Choose subject</option>@foreach($subjects as $item)<option value="{{$item->id}}" @selected($examinationSubject?->id===$item->id)>{{$item->offering->subject->name}} · {{$item->offering->department->name}}</option>@endforeach</select></label>
            <div class="flex items-end"><a href="{{route('admin.examinations.marks.dashboard')}}" class="h-12 w-full rounded-xl border border-slate-200 px-4 text-center text-xs font-black leading-[2.75rem] text-slate-600">Reset choices</a></div>
        </form>
    </section>

    @if($examinationSubject)
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header class="border-b border-slate-200 px-4 py-3">
            <p class="text-[9px] font-black uppercase tracking-widest text-emerald-700">Step 3 · Choose a section</p>
            <h2 class="mt-0.5 text-base font-black">{{$examinationSubject->offering->subject->name}}</h2>
            <p class="mt-0.5 text-[10px] font-bold text-slate-400">Theory FM {{$examinationSubject->theory_full_marks}} · PM {{$examinationSubject->theory_pass_marks}} @if((float)$examinationSubject->practical_full_marks>0) · Practical FM {{$examinationSubject->practical_full_marks}} · PM {{$examinationSubject->practical_pass_marks}} @endif</p>
        </header>
        <div class="divide-y divide-slate-100">
            @forelse($sections as $section)
                <article class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div><h3 class="text-sm font-black text-slate-900">{{$section->name}}</h3></div>
                    <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                        @if((float)$examinationSubject->theory_full_marks>0)<a href="{{route('admin.examinations.marks.edit',[$examinationSubject,'component'=>'theory','section'=>$section->id])}}" class="flex min-h-11 items-center justify-center rounded-lg bg-emerald-700 px-3 py-2 text-center text-xs font-black text-white">Enter theory</a>@endif
                        @if((float)$examinationSubject->practical_full_marks>0)<a href="{{route('admin.examinations.marks.edit',[$examinationSubject,'component'=>'practical','section'=>$section->id])}}" class="flex min-h-11 items-center justify-center rounded-lg bg-purple-700 px-3 py-2 text-center text-xs font-black text-white">Enter practical</a>@endif
                    </div>
                </article>
            @empty
                <div class="p-10 text-center text-sm font-semibold text-slate-400">No enrolled sections found for this subject.</div>
            @endforelse
        </div>
    </section>
    @elseif($exam)
    <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"><p class="text-sm font-black text-slate-700">Choose a subject to see its sections and start entering marks.</p></section>
    @else
    <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"><p class="text-sm font-black text-slate-700">Choose an exam to get started.</p></section>
    @endif
</div>
@endsection
