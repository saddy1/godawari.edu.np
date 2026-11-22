@extends('examinations.layouts.app')
@section('title', 'Results')
@section('content')
<div class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white p-5 shadow-sm">
        <div>
            <h1 class="text-xl font-black">Results</h1>
            <p class="mt-1 text-sm text-gray-500">Completed exams, ready to publish — pick one to control which faculties can see their results.</p>
        </div>
    </header>

    <form method="GET" action="{{ route('admin.examinations.results.index') }}" class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <select name="year" onchange="this.form.submit()" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-bold text-gray-700">
            <option value="">All years</option>
            @foreach($academicYears as $year)
                <option value="{{ $year->id }}" @selected((int) request('year') === $year->id)>{{ $year->name }}</option>
            @endforeach
        </select>
        <select name="org" onchange="this.form.submit()" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-bold text-gray-700">
            <option value="">All organizations</option>
            @foreach($organizations as $org)
                <option value="{{ $org->id }}" @selected((int) request('org') === $org->id)>{{ $org->name }}</option>
            @endforeach
        </select>
        <span class="ml-auto rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-black text-gray-600">{{ $examinations->count() }} exam(s)</span>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="grid gap-px bg-gray-100 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($examinations as $exam)
                @php
                    $stateStyles = [
                        'all' => ['bg-emerald-50 text-emerald-700', 'Published'],
                        'partial' => ['bg-amber-50 text-amber-700', 'Partially published'],
                        'none' => ['bg-gray-100 text-gray-600', 'Not published'],
                    ];
                    [$stateClass, $stateLabel] = $stateStyles[$exam->publish_state];
                @endphp
                <a href="{{ route('admin.examinations.results.show', $exam) }}" class="block min-w-0 bg-white p-4 transition hover:bg-emerald-50/40">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black">{{ $exam->name }}</p>
                            <p class="mt-1 truncate text-[10px] font-bold text-gray-400">{{ $exam->organization->name }} · {{ $exam->academicYear->name }}</p>
                        </div>
                        <span class="shrink-0 rounded-md px-2 py-1 text-[8px] font-black {{ $stateClass }}">{{ strtoupper($stateLabel) }}</span>
                    </div>
                    <p class="mt-3 line-clamp-2 text-[10px] font-semibold text-gray-500">{{ $exam->departments->pluck('name')->implode(', ') ?: 'No faculty linked' }}</p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <span class="rounded bg-emerald-50 px-2 py-1 text-[9px] font-black text-emerald-700">{{ $exam->published_count }}/{{ $exam->department_count }} faculties published</span>
                    </div>
                </a>
            @empty
                <div class="col-span-full py-12 text-center text-sm font-bold text-gray-400">No completed exams yet — mark an exam Completed from its settings once grading is done.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
