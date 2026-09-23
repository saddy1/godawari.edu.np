{{-- resources/views/backend/founder-dashboard/students/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Students')

@php
    $initials = fn (string $name) => collect(preg_split('/\s+/', trim($name)))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
    $input = 'w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold outline-none transition-colors duration-300 hover:border-gray-300 focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15 disabled:border-gray-100 disabled:bg-gray-50 disabled:text-gray-400';
    $label = 'mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500';
@endphp

@section('content')

<div x-data="founderStudentFilters(@js($organizations), @js([
    'organization_id' => (string) request('organization_id', ''),
    'department_id' => (string) request('department_id', ''),
    'section_id' => (string) request('section_id', ''),
]))">

    <section class="mb-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <form method="GET" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="{{ $label }}">Organization</label>
                <select name="organization_id" x-model="organizationId" @change="departmentId=''; sectionId=''; $el.form.submit()" class="{{ $input }}">
                    <option value="">All organizations</option>
                    <template x-for="item in organizations" :key="item.id"><option :value="String(item.id)" x-text="item.name"></option></template>
                </select>
            </div>
            <div>
                <label class="{{ $label }}">Faculty / class</label>
                <select name="department_id" x-model="departmentId" @change="sectionId=''; $el.form.submit()" :disabled="!organizationId" class="{{ $input }}">
                    <option value="">All faculties / classes</option>
                    <template x-for="item in departments" :key="item.id"><option :value="String(item.id)" x-text="item.name"></option></template>
                </select>
            </div>
            <div>
                <label class="{{ $label }}">Section</label>
                <select name="section_id" x-model="sectionId" @change="$el.form.submit()" :disabled="!departmentId" class="{{ $input }}">
                    <option value="">All sections</option>
                    <template x-for="item in sections" :key="item.id"><option :value="String(item.id)" x-text="item.name + (item.group_name ? ' · '+item.group_name : '')"></option></template>
                </select>
            </div>
            <div>
                <label class="{{ $label }}">Student</label>
                <div class="flex gap-2">
                    <input type="text" name="q" value="{{ $search }}" placeholder="Search by name or roll no." class="{{ $input }}">
                    <button type="submit" class="shrink-0 rounded-xl bg-[#1a5632] px-4 py-2.5 text-sm font-extrabold text-white">Search</button>
                </div>
            </div>
        </form>
    </section>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50 text-left text-[10px] font-black uppercase tracking-wide text-gray-400">
                    <th class="px-4 py-3">Student</th>
                    <th class="px-4 py-3">Roll No.</th>
                    <th class="px-4 py-3">Department / Class</th>
                    <th class="px-4 py-3">Section</th>
                    <th class="px-4 py-3">Contact</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($students as $student)
                    <tr onclick="window.location='{{ route('admin.founder.students.show', $student) }}'" class="cursor-pointer transition-colors hover:bg-gray-50">
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-gray-200 text-[10px] font-black text-gray-600">{{ $initials($student->full_name ?: 'S') }}</span>
                                <p class="truncate text-xs font-extrabold text-gray-900">{{ $student->full_name ?: 'Student' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-xs font-bold text-gray-600">{{ $student->roll_number ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-xs font-bold text-gray-600">{{ $student->stream ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-xs font-bold text-gray-600">{{ $student->section ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-xs font-bold text-gray-600">{{ $student->mobile ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm font-bold text-gray-400">No students match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $students->links() }}</div>
</div>

@endsection

@push('scripts')
<script>
function founderStudentFilters(organizations, initial) {
    return {
        organizations,
        organizationId: initial.organization_id,
        departmentId: initial.department_id,
        sectionId: initial.section_id,
        get organization() { return this.organizations.find(item => String(item.id) === this.organizationId) },
        get departments() { return this.organization?.departments || [] },
        get department() { return this.departments.find(item => String(item.id) === this.departmentId) },
        get sections() { return this.department?.sections || [] },
    }
}
</script>
@endpush
