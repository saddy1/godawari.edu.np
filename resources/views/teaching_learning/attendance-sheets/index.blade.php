@extends('teaching_learning.layouts.app')
@section('title', 'Monthly Attendance Sheets')
@section('content')
@php
    $input = 'w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold outline-none transition-colors duration-300 hover:border-gray-300 focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15 disabled:border-gray-100 disabled:bg-gray-50 disabled:text-gray-400';
    $label = 'mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500';
    $monthNames = [1=>'वैशाख',2=>'जेठ',3=>'असार',4=>'साउन',5=>'भदौ',6=>'असोज',7=>'कार्तिक',8=>'मंसिर',9=>'पुष',10=>'माघ',11=>'फागुन',12=>'चैत'];
@endphp

<div class="space-y-4" x-data="attendanceSheetFilters(@js($organizations), @js([
    'organization_id' => (string) request('organization_id', ''),
    'department_id' => (string) request('department_id', ''),
    'section_id' => (string) request('section_id', ''),
]))">
    <section class="flex flex-col gap-3 rounded-2xl bg-gradient-to-r from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-300">Printable class register</p><h1 class="mt-1 text-2xl font-black">Monthly Attendance Sheet</h1><p class="mt-1 text-xs font-semibold text-white/65">Choose a class and Nepali month. Student rows and BS dates are prepared automatically.</p></div>
        @if($organization && $sheets->isNotEmpty())<a href="{{ route('admin.teaching-learning.attendance-sheets.print', request()->query()) }}" target="_blank" class="rounded-xl bg-white px-4 py-2.5 text-xs font-black text-[#1a5632]">Print full sheet →</a>@endif
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.teaching-learning.attendance-sheets.index') }}" class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div><label class="{{$label}}">Organization</label><select name="organization_id" x-model="organizationId" @change="departmentId=''; sectionId=''" required class="{{$input}}"><option value="">Choose organization</option><template x-for="item in organizations" :key="item.id"><option :value="String(item.id)" x-text="item.name"></option></template></select></div>
                <div><label class="{{$label}}">Faculty / class</label><select name="department_id" x-model="departmentId" @change="sectionId=''" :disabled="!organizationId" class="{{$input}}"><option value="">All faculties / classes</option><template x-for="item in departments" :key="item.id"><option :value="String(item.id)" x-text="item.name"></option></template></select></div>
                <div><label class="{{$label}}">Section</label><select name="section_id" x-model="sectionId" :disabled="!departmentId" class="{{$input}}"><option value="">All sections</option><template x-for="item in sections" :key="item.id"><option :value="String(item.id)" x-text="item.name + (item.group_name ? ' · '+item.group_name : '')"></option></template></select></div>
                <div><label class="{{$label}}">Academic year</label><select name="academic_year_id" class="{{$input}}">@foreach($academicYears as $year)<option value="{{$year->id}}" @selected($academicYear?->id===$year->id)>{{$year->name}}{{$year->is_active?' · Active':''}}</option>@endforeach</select></div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                <div><label class="{{$label}}">BS year</label><select name="bs_year" class="{{$input}}">@foreach($yearOptions as $year)<option value="{{$year}}" @selected($bsYear===$year)>{{$year}}</option>@endforeach</select></div>
                <div><label class="{{$label}}">Nepali month</label><select name="bs_month" class="{{$input}}">@foreach($monthNames as $number=>$name)<option value="{{$number}}" @selected($bsMonth===$number)>{{$name}}</option>@endforeach</select></div>
                <div><label class="{{$label}}">Students</label><select name="gender" class="{{$input}}"><option value="">All students</option><option value="male" @selected($gender==='male')>Male only</option><option value="female" @selected($gender==='female')>Female only</option><option value="other" @selected($gender==='other')>Other only</option></select></div>
                <div><label class="{{$label}}">Sort by</label><select name="sort" class="{{$input}}"><option value="roll" @selected($sort==='roll')>Student code</option><option value="name" @selected($sort==='name')>Student name</option></select></div>
                <div class="lg:col-span-2"><label class="{{$label}}">Sheet content</label><select name="content" class="{{$input}}"><option value="blank" @selected($content==='blank')>Blank manual register</option><option value="recorded" @selected($content==='recorded')>Fill online attendance</option></select></div>
                <div><label class="{{$label}}">Rows / page</label><select name="rows_per_page" class="{{$input}}">@foreach([30,35,40,45,50] as $rows)<option value="{{$rows}}" @selected($rowsPerPage===$rows)>{{$rows}}</option>@endforeach</select></div>
            </div>

            <div class="flex flex-col justify-between gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:items-center">
                <p class="text-[10px] font-semibold text-gray-400" x-text="summary"></p>
                <div class="flex gap-2"><button class="rounded-xl bg-[#1a5632] px-4 py-2.5 text-xs font-black text-white">Generate preview</button><button formaction="{{ route('admin.teaching-learning.attendance-sheets.print') }}" formtarget="_blank" :disabled="!organizationId" class="rounded-xl bg-amber-400 px-4 py-2.5 text-xs font-black text-gray-950 disabled:opacity-40">Print / PDF</button></div>
            </div>
        </form>
    </section>

    @php
        $previewSheet = $sheets->first();
        $previewStudents = $previewSheet['students'] ?? collect();
        $previewAttendance = $previewSheet['attendance'] ?? collect();
        $scopeTitle = $section
            ? $department->name.' · '.$section->name
            : ($department ? $department->name.' · All sections' : 'All faculties / classes · All sections');
    @endphp
    @if($organization && $previewSheet)
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <header class="flex flex-col gap-2 border-b border-gray-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-[9px] font-black uppercase tracking-widest text-[#1a5632]">{{$organization->name}}</p><h2 class="text-base font-black">{{$scopeTitle}}</h2><p class="text-[10px] font-semibold text-gray-400">{{$monthLabel}} {{$bsYear}} BS · {{$academicYear?->name}} · {{$sheets->count()}} sections · {{$students->count()}} students</p></div><span class="rounded-lg {{$content==='recorded'?'bg-blue-50 text-blue-700':'bg-gray-100 text-gray-600'}} px-3 py-2 text-[9px] font-black uppercase">{{$content==='recorded'?'Online attendance':'Blank register'}}</span></header>
            @if($previewStudents->isEmpty())
                <div class="p-10 text-center"><p class="text-sm font-black text-gray-600">No students found in the first selected section</p><p class="mt-1 text-xs text-gray-400">The print job still creates a separate page for every selected section.</p></div>
            @else
                @if($sheets->count()>1)<div class="border-b border-blue-100 bg-blue-50 px-4 py-2 text-[10px] font-bold text-blue-700">Preview: {{$previewSheet['department']->name}} · {{$previewSheet['section']->name}}. Printing includes all {{$sheets->count()}} selected sections, each starting on a new page.</div>@endif
                <div class="max-h-[34rem] overflow-auto">
                    <table class="min-w-[1180px] w-full border-collapse text-[9px]">
                        <thead class="sticky top-0 z-10 bg-emerald-50"><tr><th class="sticky left-0 z-20 w-10 border bg-emerald-50 p-2">SN</th><th class="sticky left-10 z-20 min-w-28 border bg-emerald-50 p-2 text-left">Code</th><th class="sticky left-[9.5rem] z-20 min-w-52 border bg-emerald-50 p-2 text-left">Student</th>@foreach($days as $day)<th class="w-7 border p-1">{{$day['number']}}</th>@endforeach</tr></thead>
                        <tbody>@foreach($previewStudents->take(15) as $student)<tr class="odd:bg-white even:bg-gray-50/60"><td class="sticky left-0 border bg-inherit p-2 text-center font-bold">{{$loop->iteration}}</td><td class="sticky left-10 border bg-inherit p-2 text-[10px] font-black text-gray-800">{{$student->roll_number?:'—'}}</td><td class="sticky left-[9.5rem] border bg-inherit p-2 text-[11px] font-black text-gray-900">{{$student->full_name}}</td>@foreach($days as $day)<td class="h-8 border text-center font-black">{{$day['ad'] ? ($previewAttendance[$student->id.'|'.$day['ad']] ?? '') : ''}}</td>@endforeach</tr>@endforeach</tbody>
                    </table>
                </div>
                @if($previewStudents->count()>15)<div class="border-t bg-gray-50 px-4 py-2 text-center text-[10px] font-bold text-gray-500">Preview shows 15 of {{$previewStudents->count()}} students in {{$previewSheet['section']->name}}. The print sheet includes everyone.</div>@endif
            @endif
        </section>
    @else
        <section class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-xl">▦</span><h2 class="mt-3 text-sm font-black">Choose an organization to build the register</h2><p class="mt-1 text-xs font-semibold text-gray-400">Leave faculty and section on “All” to print the complete organization.</p></section>
    @endif
</div>
@endsection

@push('scripts')
<script>
function attendanceSheetFilters(organizations, initial) {
    return {
        organizations,
        organizationId: initial.organization_id,
        departmentId: initial.department_id,
        sectionId: initial.section_id,
        get organization() { return this.organizations.find(item => String(item.id) === this.organizationId) },
        get departments() { return this.organization?.departments || [] },
        get department() { return this.departments.find(item => String(item.id) === this.departmentId) },
        get sections() { return this.department?.sections || [] },
        get section() { return this.sections.find(item => String(item.id) === this.sectionId) },
        get summary() { if (!this.organization) return 'Choose an organization to begin.'; if (!this.department) return `${this.organization.name} · all faculties and all sections`; if (!this.section) return `${this.organization.name} · ${this.department.name} · all sections`; return `${this.organization.name} · ${this.department.name} · ${this.section.name}` }
    }
}
</script>
@endpush
