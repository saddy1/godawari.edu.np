@extends('teaching_learning.layouts.app')
@section('title','Weekly Routine')
@section('content')
<style>@media print{.no-print{display:none !important}body{margin:0}}</style>
<div class="space-y-4" x-data="weeklyRoutinePicker(@js($organizations),@js($section?->department?->organization_id?(string)$section->department->organization_id:''),@js($section?->department_id?(string)$section->department_id:''),@js($section?->id?(string)$section->id:''))">
    <section class="no-print rounded-2xl border bg-white p-4 shadow-sm">
        <div class="mb-3"><h1 class="text-lg font-black text-gray-900">Weekly Routine</h1><p class="text-xs font-semibold text-gray-400">Choose organization, faculty/class and section to view that section's full weekly routine.</p></div>
        <form method="GET" action="{{route('admin.teaching-learning.routine-builder.weekly')}}" class="grid gap-3 sm:grid-cols-3">
            <div>
                <label class="text-[9px] font-black uppercase text-gray-500">Organization</label>
                <select name="organization_id" x-model="organizationId" @change="onOrgChange()" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold outline-none transition-colors duration-300 hover:border-gray-300 focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15">
                    <option value="">Choose organization</option>
                    <template x-for="org in organizations" :key="org.id"><option :value="String(org.id)" x-text="org.name"></option></template>
                </select>
            </div>
            <div>
                <label class="text-[9px] font-black uppercase text-gray-500">Faculty / Class</label>
                <select name="department_id" x-model="departmentId" @change="onDeptChange()" :disabled="!organizationId" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold outline-none transition-colors duration-300 hover:border-gray-300 focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-50 disabled:text-gray-400">
                    <option value="">Choose faculty / class</option>
                    <template x-for="dept in departments" :key="dept.id"><option :value="String(dept.id)" x-text="dept.name"></option></template>
                </select>
            </div>
            <div>
                <label class="text-[9px] font-black uppercase text-gray-500">Section</label>
                <select name="section_id" x-model="sectionId" @change="$el.form.submit()" :disabled="!departmentId" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold outline-none transition-colors duration-300 hover:border-gray-300 focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-50 disabled:text-gray-400">
                    <option value="">Choose section</option>
                    <template x-for="sec in sections" :key="sec.id"><option :value="String(sec.id)" x-text="sec.name"></option></template>
                </select>
            </div>
        </form>
    </section>

    @if($section && $routinePlan)
        <section class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-wider text-[#1a5632]">{{$routinePlan->organization->name}} · {{$routinePlan->department->name}}</p>
                    <h2 class="text-sm font-black">Section {{$section->name}} · Weekly Routine</h2>
                    <p class="text-[10px] font-semibold text-gray-400">{{$routinePlan->academicYear->name}} · {{$routinePlan->shift->name}} @if($routinePlan->status!=='published')<span class="font-black text-amber-600">· Draft (not yet published)</span>@endif</p>
                </div>
                <button type="button" onclick="window.print()" class="no-print shrink-0 rounded-xl bg-[#1a5632] px-4 py-2.5 text-xs font-black text-white hover:bg-[#0b2415]">Print weekly routine</button>
            </header>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] border-collapse text-center">
                    <thead><tr class="bg-gray-50">
                        <th class="sticky left-0 z-10 w-24 border-b border-r bg-gray-50 px-2 py-3 text-left text-[9px] font-black uppercase text-gray-500">Day</th>
                        @foreach($routinePlan->shift->periods as $period)<th class="min-w-[118px] border-b border-r px-2 py-2 {{$period->is_break?'w-16 min-w-[64px] bg-amber-50':''}}"><span class="block text-[10px] font-black {{$period->is_break?'text-amber-700':'text-gray-800'}}">{{$period->name}}</span><span class="mt-0.5 block text-[8px] font-bold text-gray-400">{{date('g:i A',strtotime($period->starts_at))}}–{{date('g:i A',strtotime($period->ends_at))}}</span></th>@endforeach
                    </tr></thead>
                    <tbody>
                    @foreach($days as $day)
                        <tr>
                            <th class="sticky left-0 z-10 border-b border-r bg-white px-2 py-3 text-left text-xs font-black">{{$day}}</th>
                            @php $skipUntilPosition=null; @endphp
                            @foreach($routinePlan->shift->periods as $period)
                                @continue($skipUntilPosition!==null && $period->position<=$skipUntilPosition)
                                @if($period->is_break)
                                    <td class="border-b border-r bg-amber-50/70 px-1 text-[9px] font-black uppercase tracking-widest text-amber-700">Break</td>
                                @else
                                    @php
                                        $lesson=$cellLessons->get($day.':'.$period->id);
                                        $colspan=1;
                                        if($lesson && $lesson->end_routine_period_id && (int)$lesson->end_routine_period_id!==(int)$period->id){
                                            $endPeriod=$routinePlan->shift->periods->firstWhere('id',$lesson->end_routine_period_id);
                                            if($endPeriod){$colspan=$routinePlan->shift->periods->whereBetween('position',[$period->position,$endPeriod->position])->count();$skipUntilPosition=$endPeriod->position;}
                                        }
                                    @endphp
                                    <td colspan="{{$colspan}}" class="h-[70px] border-b border-r p-1">
                                        <div class="flex h-full flex-col justify-center rounded-lg {{$lesson?->mode==='practical_split'?'bg-purple-50':($lesson?'bg-emerald-50':'')}}">
                                            @if($lesson)
                                                <div class="grid h-full w-full {{$lesson->mode==='practical_split'&&$lesson->groups->count()===2?'grid-cols-2':'grid-cols-1'}}">
                                                    @foreach($lesson->groups as $group)
                                                        @php $groupSubject=$group->offering->subject; @endphp
                                                        <div class="min-w-0 px-1 py-0.5 {{!$loop->first?'border-l border-purple-100':''}}">
                                                            <b class="block truncate text-[10px] leading-tight" title="{{$groupSubject->name}}">{{$lesson->mode==='practical_split'&&$groupSubject->practical_code?$groupSubject->practical_code:$groupSubject->code}}</b>
                                                            <span class="block truncate text-[8px] font-bold text-gray-500" title="{{$group->teacher_names ?? ''}}">{{$group->teacher_initials ?: 'No teacher'}}</span>
                                                            <span class="block truncate text-[7px] font-bold text-gray-400">{{$group->room?->code}}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @elseif($section && !$routinePlan)
        <div class="rounded-2xl border border-dashed bg-white p-10 text-center text-sm text-gray-400">No routine has been created yet for this section.</div>
    @endif
</div>
@endsection
@push('scripts')
<script>
function weeklyRoutinePicker(organizations,orgId,deptId,sectionId){
    return {
        organizations,organizationId:orgId||'',departmentId:deptId||'',sectionId:sectionId||'',
        get departments(){const org=this.organizations.find(o=>String(o.id)===String(this.organizationId));return org?org.departments:[]},
        get sections(){const dept=this.departments.find(d=>String(d.id)===String(this.departmentId));return dept?dept.sections:[]},
        onOrgChange(){this.departmentId='';this.sectionId=''},
        onDeptChange(){this.sectionId=''},
    }
}
</script>
@endpush
