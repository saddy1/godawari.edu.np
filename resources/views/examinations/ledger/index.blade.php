@extends('examinations.layouts.app')
@section('title', 'Marks Ledger — '.$examination->name)
@section('content')
<div class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white p-5 shadow-sm">
        <div>
            <h1 class="text-xl font-black">Marks Ledger</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $examination->name }} · {{ $examination->organization->name }}</p>
            <p class="mt-2 text-xs text-gray-500">{{ $ledger['total_students'] }} students across {{ $ledger['departments']->count() }} faculty sheet(s). Download for the full subject-wise ledger.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.examinations.marks-ledger.download', $examination) }}" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-xs font-black text-white hover:bg-emerald-800">⬇ Download Excel (.xlsx)</a>
            <a href="{{ route('admin.examinations.index', ['exam' => $examination->id]) }}" class="rounded-lg border px-4 py-2 text-xs font-bold">Back to exam</a>
        </div>
    </header>

    <section class="grid gap-4 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="border-b border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs font-black uppercase tracking-widest text-emerald-700">Section Toppers</p></header>
            <div class="divide-y divide-slate-100">
                @forelse($ledger['stats']['section_toppers'] as $sectionName => $row)
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <div><p class="text-sm font-black text-slate-900">{{ $row['student']->full_name }}</p><p class="text-xs font-semibold text-slate-400">{{ $sectionName }} · {{ $row['department_name'] }}</p></div>
                        <div class="text-right"><p class="text-sm font-black text-emerald-700">{{ $row['percentage'] }}%</p><p class="text-[10px] font-bold text-slate-400">GPA {{ $row['gpa'] ?? '—' }}</p></div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-xs font-semibold text-slate-400">No section has fully-graded results yet.</p>
                @endforelse
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="border-b border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs font-black uppercase tracking-widest text-emerald-700">Faculty Toppers</p></header>
            <div class="divide-y divide-slate-100">
                @forelse($ledger['stats']['faculty_toppers'] as $facultyName => $row)
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <div><p class="text-sm font-black text-slate-900">{{ $row['student']->full_name }}</p><p class="text-xs font-semibold text-slate-400">{{ $facultyName }} · {{ $row['section_name'] }}</p></div>
                        <div class="text-right"><p class="text-sm font-black text-emerald-700">{{ $row['percentage'] }}%</p><p class="text-[10px] font-bold text-slate-400">GPA {{ $row['gpa'] ?? '—' }}</p></div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-xs font-semibold text-slate-400">No faculty has fully-graded results yet.</p>
                @endforelse
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="border-b border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs font-black uppercase tracking-widest text-sky-700">Top 5 Male Students</p></header>
            <div class="divide-y divide-slate-100">
                @forelse($ledger['stats']['top_male'] as $i => $row)
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <div class="flex items-center gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sky-50 text-[10px] font-black text-sky-700">{{ $i + 1 }}</span><div><p class="text-sm font-black text-slate-900">{{ $row['student']->full_name }}</p><p class="text-xs font-semibold text-slate-400">{{ $row['department_name'] }} · {{ $row['section_name'] }}</p></div></div>
                        <div class="text-right"><p class="text-sm font-black text-sky-700">{{ $row['percentage'] }}%</p><p class="text-[10px] font-bold text-slate-400">GPA {{ $row['gpa'] ?? '—' }}</p></div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-xs font-semibold text-slate-400">No fully-graded male students yet.</p>
                @endforelse
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="border-b border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs font-black uppercase tracking-widest text-pink-700">Top 5 Female Students</p></header>
            <div class="divide-y divide-slate-100">
                @forelse($ledger['stats']['top_female'] as $i => $row)
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <div class="flex items-center gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-pink-50 text-[10px] font-black text-pink-700">{{ $i + 1 }}</span><div><p class="text-sm font-black text-slate-900">{{ $row['student']->full_name }}</p><p class="text-xs font-semibold text-slate-400">{{ $row['department_name'] }} · {{ $row['section_name'] }}</p></div></div>
                        <div class="text-right"><p class="text-sm font-black text-pink-700">{{ $row['percentage'] }}%</p><p class="text-[10px] font-bold text-slate-400">GPA {{ $row['gpa'] ?? '—' }}</p></div>
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-xs font-semibold text-slate-400">No fully-graded female students yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="space-y-3">
        @foreach($ledger['departments'] as $dept)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-sm font-black text-slate-900">{{ $dept['name'] }}</p>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600">{{ $dept['rows']->count() }} students · {{ $dept['subject_columns']->count() }} subject columns</span>
                </header>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500"><tr><th class="px-3 py-2">Student</th><th class="px-3 py-2">Section</th><th class="px-3 py-2 text-right">Obtained / Full</th><th class="px-3 py-2 text-right">%</th><th class="px-3 py-2 text-right">GPA</th><th class="px-3 py-2 text-right">Grade</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($dept['rows']->take(8) as $row)
                                <tr>
                                    <td class="px-3 py-2 font-bold text-slate-800">{{ $row['student']->full_name }}</td>
                                    <td class="px-3 py-2 text-slate-500">{{ $row['section_name'] }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-slate-600">{{ $row['total_obtained'] }} / {{ $row['total_full'] }}</td>
                                    <td class="px-3 py-2 text-right font-black {{ $row['percentage'] !== null ? 'text-emerald-700' : 'text-amber-600' }}">{{ $row['percentage'] !== null ? $row['percentage'].'%' : 'Incomplete' }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-slate-600">{{ $row['gpa'] ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-slate-600">{{ $row['overall_grade'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($dept['rows']->count() > 8)
                    <p class="border-t border-slate-100 px-4 py-2 text-[10px] font-bold text-slate-400">+{{ $dept['rows']->count() - 8 }} more in the downloaded sheet.</p>
                @endif
            </div>
        @endforeach
    </section>
</div>
@endsection
