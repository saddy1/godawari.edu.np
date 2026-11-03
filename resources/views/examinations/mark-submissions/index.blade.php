@extends('examinations.layouts.app')
@section('title', 'Marks submissions')
@section('content')
<div class="mx-auto max-w-6xl space-y-4">
    <section class="rounded-2xl bg-gradient-to-r from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm">
        <p class="text-[9px] font-black uppercase tracking-widest text-amber-300">Administrator workspace</p>
        <h1 class="mt-1 text-xl font-black">Teacher marks submissions</h1>
        <p class="mt-1 text-xs font-semibold text-white/70">Track every assigned subject and section. A submission is complete only after the teacher locks it.</p>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
            <label><span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-500">Examination</span><select name="exam" onchange="this.form.submit()" class="w-full rounded-xl border-slate-200 text-sm font-bold"><option value="">Choose examination</option>@foreach($exams as $item)<option value="{{ $item->id }}" @selected($exam?->id === $item->id)>{{ $item->name }} · {{ $item->academicYear->name }}</option>@endforeach</select></label>
            <div class="flex items-end"><a href="{{ route('admin.examinations.mark-submissions.index', $exam ? ['exam' => $exam->id] : []) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-600">Reset filter</a></div>
        </form>
    </section>

    @if($exam)
        <section class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4"><p class="text-[9px] font-black uppercase tracking-widest text-emerald-700">Submitted & locked</p><p class="mt-1 text-2xl font-black text-emerald-950">{{ $summary['submitted'] }}</p></div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4"><p class="text-[9px] font-black uppercase tracking-widest text-amber-700">Awaiting teacher</p><p class="mt-1 text-2xl font-black text-amber-950">{{ $summary['pending'] }}</p></div>
            <div class="rounded-2xl border border-red-100 bg-red-50 p-4"><p class="text-[9px] font-black uppercase tracking-widest text-red-700">No teacher assigned</p><p class="mt-1 text-2xl font-black text-red-950">{{ $summary['unassigned'] }}</p></div>
        </section>

        <div class="flex flex-wrap gap-2"><a href="{{ route('admin.examinations.mark-submissions.index', ['exam' => $exam->id]) }}" class="rounded-full border px-3 py-1.5 text-[11px] font-extrabold {{ $status === '' ? 'border-[#1a5632] bg-[#1a5632] text-white' : 'border-gray-200 text-gray-600' }}">All</a><a href="{{ route('admin.examinations.mark-submissions.index', ['exam' => $exam->id, 'status' => 'pending']) }}" class="rounded-full border px-3 py-1.5 text-[11px] font-extrabold {{ $status === 'pending' ? 'border-amber-600 bg-amber-600 text-white' : 'border-amber-200 text-amber-800' }}">Pending</a><a href="{{ route('admin.examinations.mark-submissions.index', ['exam' => $exam->id, 'status' => 'submitted']) }}" class="rounded-full border px-3 py-1.5 text-[11px] font-extrabold {{ $status === 'submitted' ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-emerald-200 text-emerald-800' }}">Submitted</a></div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">Teacher</th><th class="px-4 py-3">Subject</th><th class="px-4 py-3">Class / section</th><th class="px-4 py-3">Components</th><th class="px-4 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($entries as $entry)<tr><td class="px-4 py-3 font-bold text-slate-900">{{ $entry->teacher->name }}</td><td class="px-4 py-3"><p class="font-black text-slate-900">{{ $entry->subject->offering->subject->name }}</p><p class="text-[10px] font-bold text-slate-400">{{ $entry->subject->offering->subject->code }} · {{ $entry->subject->offering->department->name }}</p></td><td class="px-4 py-3 font-semibold text-slate-700">{{ $entry->section->name }}</td><td class="px-4 py-3">@foreach($entry->components as $component)<span class="mr-1 rounded-md {{ $component === 'theory' ? 'bg-emerald-50 text-emerald-700' : 'bg-purple-50 text-purple-700' }} px-2 py-1 text-[9px] font-black uppercase">{{ $component === 'theory' ? 'TH' : 'IN' }}</span>@endforeach</td><td class="px-4 py-3">@if($entry->submission?->is_locked)<span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-800">Submitted {{ $entry->submission->locked_at->format('d M, h:i A') }}</span>@elseif($entry->submission?->unlock_requested_at)<span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black text-amber-800">Correction requested</span>@else<span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black text-amber-800">Pending submission</span>@endif</td></tr>@empty<tr><td colspan="5" class="px-4 py-12 text-center text-sm font-semibold text-slate-400">No teacher assignments found. Assign teachers in Routine Builder for this exam's sections and subjects.</td></tr>@endforelse</tbody></table></div>
        </section>
    @else
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm font-bold text-slate-500">Choose an examination to view teacher submissions.</section>
    @endif
</div>
@endsection
