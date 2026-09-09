@extends('examinations.layouts.app')
@section('title', 'Marksheets — '.$examination->name)
@section('content')
<div class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white p-5 shadow-sm">
        <div><h1 class="text-xl font-black">Marksheets</h1><p class="mt-1 text-sm text-gray-500">{{ $examination->name }} · {{ $examination->organization->name }}</p><p class="mt-2 text-xs text-gray-500">Find a student and preview their marksheet before printing.</p></div>
        <a href="{{ route('admin.examinations.index', ['exam' => $examination->id]) }}" class="rounded-lg border px-4 py-2 text-xs font-bold">Back to exam</a>
    </header>
    <section class="rounded-2xl border border-emerald-200 bg-white p-4 shadow-sm">
        <div class="mb-4 flex flex-wrap justify-between gap-3">
            <form method="GET" x-data="{
                    schoolClass: @js((string) request('school_class', '')),
                    faculty: @js((string) request('faculty', '')),
                    section: @js((string) request('section', '')),
                    options: @js($filterOptions),
                    get faculties() {
                        return [...new Set(this.options.filter(row => !this.schoolClass || String(row.school_class) === this.schoolClass).map(row => row.faculty).filter(Boolean))].sort();
                    },
                    get sections() {
                        return [...new Set(this.options.filter(row => (!this.schoolClass || String(row.school_class) === this.schoolClass) && (!this.faculty || row.faculty === this.faculty)).map(row => row.section).filter(Boolean))].sort();
                    },
                }" class="w-full space-y-3 rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Filter marksheets</p>
                <div class="flex flex-wrap items-end gap-3">
                    <label class="flex flex-col text-xs font-semibold text-gray-600">Student search<input name="q" value="{{ request('q') }}" placeholder="Name or symbol number" class="mt-1 w-56 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-emerald-600 focus:ring-emerald-600"></label>
                    @if($examination->organization->type === 'school')
                    <label class="flex flex-col text-xs font-semibold text-gray-600">Class<select name="school_class" x-model="schoolClass" @change="faculty = ''; section = ''" class="mt-1 w-36 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm"><option value="">All classes</option><option value="11">Class 11</option><option value="12">Class 12</option></select></label>
                    @endif
                    <label class="flex flex-col text-xs font-semibold text-gray-600">Faculty<select name="faculty" x-model="faculty" @change="section = ''" class="mt-1 w-44 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm"><option value="">All faculties</option><template x-for="name in faculties" :key="name"><option :value="name" x-text="name"></option></template></select></label>
                    <label class="flex flex-col text-xs font-semibold text-gray-600">Section<select name="section" x-model="section" class="mt-1 w-36 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm"><option value="">All sections</option><template x-for="name in sections" :key="name"><option :value="name" x-text="name"></option></template></select></label>
                    <button class="rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-bold text-white">Apply filters</button>
                    <a href="{{ route('admin.examinations.marksheets.index', $examination) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-bold">Clear</a>
                    <button formaction="{{ route('admin.examinations.marksheets.print', $examination) }}" class="rounded-lg border border-emerald-300 bg-white px-4 py-2 text-xs font-bold text-emerald-800">Preview matching marksheets</button>
                    <span class="self-center text-xs font-semibold text-emerald-800">{{ $students->total() }} matching students</span>
                </div>
            </form>
        </div>
        <div class="overflow-x-auto"><table class="w-full border border-gray-200 text-left text-sm">
            <thead><tr class="border-b border-emerald-200 bg-emerald-50 text-xs text-emerald-800"><th class="p-3">Symbol no.</th><th class="p-3">Student</th><th class="p-3">Faculty / section</th><th class="p-3">Actions</th></tr></thead>
            <tbody>@forelse($students as $student)
                <tr class="border-b border-gray-200 even:bg-gray-50 hover:bg-emerald-50/50"><td class="p-3">{{ $symbols->get($student->id) ?? '—' }}</td><td class="p-3 font-bold">{{ $student->full_name }}</td><td class="p-3">{{ $student->stream }} · {{ $student->academicSection?->name ?? $student->section }}</td><td class="p-3">
                    @if($symbols->has($student->id))
                        <a href="{{ route('admin.examinations.marksheets.print', ['examination' => $examination, 'student_id' => $student->id]) }}" class="inline-block rounded-lg bg-[#1a5632] px-3 py-2 text-xs font-bold text-white">Preview</a>
                        <a href="{{ route('admin.examinations.marksheets.download-one', [$examination, $student]) }}" class="inline-block rounded-lg border px-3 py-2 text-xs font-bold">PDF</a>
                    @else <span class="text-xs text-gray-400">Assign symbol number first</span> @endif
                </td></tr>
            @empty <tr><td colspan="4" class="p-8 text-center text-gray-500">No students found.</td></tr> @endforelse</tbody>
        </table></div>
        <div class="mt-4">{{ $students->links() }}</div>
    </section>
</div>
@endsection
