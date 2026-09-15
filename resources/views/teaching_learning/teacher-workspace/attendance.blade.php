@extends('teaching_learning.teacher-workspace.layout')
@section('title','Take Attendance')
@section('content')
@php $end=$routineLesson->endPeriod?:$routineLesson->period; @endphp
<div class="mb-3 flex items-center justify-between"><a href="{{route('admin.teacher.workspace')}}" class="rounded-lg border bg-white px-3 py-2 text-xs font-black text-slate-600">← Today</a><span id="page-save-state" class="text-[10px] font-bold text-slate-400">{{$editable?'Tap a status to autosave':'Read only · Attendance closed'}}</span></div>
<section class="rounded-2xl p-5 text-white shadow-sm" style="background:linear-gradient(135deg,var(--teacher-dark),var(--teacher-primary))">
    <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-300">{{$routineLesson->day_of_week}} · {{$routineLesson->period->name}}{{($end->id!==$routineLesson->period->id)?'–'.$end->name:''}}</p>
    <h1 class="mt-2 text-2xl font-black">{{$groups->pluck('offering.subject.name')->unique()->implode(' / ')}}</h1>
    <p class="mt-1 text-xs font-semibold text-white/70">{{$routineLesson->plan->department->name}} · {{$routineLesson->section->name}} · {{\Carbon\Carbon::parse($routineLesson->period->starts_at)->format('h:i A')}}–{{\Carbon\Carbon::parse($end->ends_at)->format('h:i A')}}</p>
    @if($groups->pluck('group_label')->filter()->isNotEmpty())<div class="mt-3 flex flex-wrap gap-1">@foreach($groups as $group)<span class="rounded-lg bg-white/10 px-2 py-1 text-[10px] font-bold">{{$group->group_label}} · {{$group->students->count()}} students</span>@endforeach</div>@endif
</section>

<section class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <header class="border-b p-4">
        <div class="flex items-center justify-between gap-3"><h2 class="text-sm font-black">{{$students->count()}} students</h2><input id="attendance-search" aria-label="Search students" class="w-32 rounded-lg border-slate-200 px-3 py-2 text-xs sm:w-52" placeholder="Search"></div>
        <p class="mt-1 text-[10px] font-semibold text-slate-500">{{$editable?'Your first click also saves unmarked students as present. Changes save immediately.':'Saved attendance is available to view. Editing is closed.'}}</p>
        @if($editable)<div class="mt-3 flex gap-2"><button type="button" data-all="present" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white">Mark all present</button><button type="button" data-all="absent" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white">Mark all absent</button></div>@endif
    </header>
    <div id="attendance-list" class="divide-y divide-slate-100">
        @forelse($students as $student)@php $saved=$attendance->get($student->id);$status=$saved?->status; @endphp
        <article class="attendance-row flex items-center gap-2 p-3 sm:gap-4 sm:px-4" data-student="{{$student->id}}" data-status="{{$status}}" data-search="{{strtolower($student->full_name.' '.$student->roll_number)}}">
            <div class="min-w-0 flex-1"><h3 class="text-xs font-black sm:text-sm">{{$student->full_name}}</h3><p class="text-[10px] text-slate-500">{{$student->roll_number?:'—'}}</p><p class="row-state text-[9px] font-bold text-slate-400">{{$saved?'Saved '.optional($saved->marked_at)->format('h:i A').(!in_array($status,['present','absent'])?' · '.ucfirst($status):''):'Not recorded'}}</p></div>
            <div class="flex shrink-0 gap-1">
                <button type="button" data-value="present" @disabled(!$editable) aria-label="Mark {{$student->full_name}} present" class="status-button rounded-lg px-2 py-3 text-[10px] font-black">PRESENT</button>
                <button type="button" data-value="absent" @disabled(!$editable) aria-label="Mark {{$student->full_name}} absent" class="status-button rounded-lg px-2 py-3 text-[10px] font-black">ABSENT</button>
            </div>
        </article>
        @empty<div class="p-10 text-center text-sm font-bold text-amber-700">No students are assigned to your routine group.</div>@endforelse
    </div>
</section>
@endsection
@push('scripts')
<script>
const attendanceUrl=@json(route('admin.teacher.attendance.save',$routineLesson)),token=document.querySelector('meta[name="csrf-token"]').content;
const rows=[...document.querySelectorAll('.attendance-row')], controls=[...document.querySelectorAll('.status-button,[data-all]')];
const pageState=document.getElementById('page-save-state');
let editable=@json($editable),saving=false;
const closeTime=Date.now()+@json(max(0, now()->diffInMilliseconds($closesAt, false)));
const colors={present:'bg-emerald-600 text-white',absent:'bg-red-600 text-white'};
function paint(row){
    row.querySelectorAll('.status-button').forEach(button=>{
        button.classList.remove('bg-emerald-600','bg-red-600','text-white','bg-slate-100','text-slate-500');
        const selected=button.dataset.value===row.dataset.status;
        button.classList.add(...(selected?colors[button.dataset.value]:'bg-slate-100 text-slate-500').split(' '));
        button.setAttribute('aria-pressed',String(selected));
    });
}
function lockControls(){
    if(Date.now()>=closeTime)editable=false;
    controls.forEach(button=>button.disabled=!editable||saving);
    if(!editable&&!saving)pageState.textContent='Read only · Attendance closed';
}
async function save(status,row=null){
    lockControls();
    if(!editable||saving)return;
    saving=true;lockControls();pageState.textContent='Saving…';
    const affected=row?[row]:rows;
    affected.forEach(item=>item.querySelector('.row-state').textContent='Saving…');
    try{
        const response=await fetch(attendanceUrl,{method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify(row?{student_id:Number(row.dataset.student),status}:{all:true,status})});
        const data=await response.json();
        if(!response.ok)throw new Error(data.message||'Could not save');
        rows.forEach(item=>{
            if(!row||item===row)item.dataset.status=status;
            else if(!item.dataset.status)item.dataset.status='present';
            paint(item);
            item.querySelector('.row-state').textContent='Saved '+data.saved_at;
        });
        pageState.textContent='All changes saved';
    }catch(error){
        affected.forEach(item=>item.querySelector('.row-state').textContent='Save failed · Tap to retry');
        pageState.textContent=error.message||'Could not save. Please retry.';
    }finally{saving=false;lockControls();}
}
rows.forEach(row=>{paint(row);row.querySelectorAll('.status-button').forEach(button=>button.addEventListener('click',()=>save(button.dataset.value,row)));});
document.querySelectorAll('[data-all]').forEach(button=>button.addEventListener('click',()=>save(button.dataset.all)));
document.getElementById('attendance-search').addEventListener('input',event=>{const q=event.target.value.toLowerCase();rows.forEach(row=>row.style.display=row.dataset.search.includes(q)?'':'none');});
pageState.setAttribute('role','status');
setInterval(lockControls,1000);
lockControls();
window.addEventListener('beforeunload',event=>{if(saving){event.preventDefault();event.returnValue='';}});
</script>
@endpush
