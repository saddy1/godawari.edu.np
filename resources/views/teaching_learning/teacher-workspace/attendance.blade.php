@extends('teaching_learning.teacher-workspace.layout')
@section('title','Take Attendance')
@section('content')
@php $end=$routineLesson->endPeriod?:$routineLesson->period; @endphp
<div class="mb-3 flex items-center justify-between"><a href="{{route('admin.teacher.workspace')}}" class="rounded-lg border bg-white px-3 py-2 text-xs font-black text-slate-600">← Today</a><span id="page-save-state" class="text-[10px] font-bold text-slate-400">Tap a status to autosave</span></div>
<section class="rounded-2xl p-5 text-white shadow-sm" style="background:linear-gradient(135deg,var(--teacher-dark),var(--teacher-primary))">
    <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-300">{{$routineLesson->day_of_week}} · {{$routineLesson->period->name}}{{($end->id!==$routineLesson->period->id)?'–'.$end->name:''}}</p>
    <h1 class="mt-2 text-2xl font-black">{{$groups->pluck('offering.subject.name')->unique()->implode(' / ')}}</h1>
    <p class="mt-1 text-xs font-semibold text-white/70">{{$routineLesson->plan->department->name}} · {{$routineLesson->section->name}} · {{\Carbon\Carbon::parse($routineLesson->period->starts_at)->format('h:i A')}}–{{\Carbon\Carbon::parse($end->ends_at)->format('h:i A')}}</p>
    @if($groups->pluck('group_label')->filter()->isNotEmpty())<div class="mt-3 flex flex-wrap gap-1">@foreach($groups as $group)<span class="rounded-lg bg-white/10 px-2 py-1 text-[10px] font-bold">{{$group->group_label}} · {{$group->students->count()}} students</span>@endforeach</div>@endif
</section>

<section class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <header class="flex items-center justify-between border-b p-4"><div><h2 class="text-sm font-black">{{$students->count()}} students</h2><p class="text-[10px] font-semibold text-slate-400">Everyone starts as present. Change only when needed.</p></div><input id="attendance-search" class="w-32 rounded-lg border-slate-200 px-3 py-2 text-xs sm:w-52" placeholder="Search"></header>
    <div id="attendance-list" class="divide-y divide-slate-100">
        @forelse($students as $student)@php $saved=$attendance->get($student->id);$status=$saved?->status?:'present'; @endphp
        <article class="attendance-row p-3 sm:grid sm:grid-cols-[1fr_auto] sm:items-center sm:gap-4 sm:px-4" data-student="{{$student->id}}" data-status="{{$status}}" data-search="{{strtolower($student->full_name.' '.$student->roll_number)}}">
            <div class="mb-2 min-w-0 sm:mb-0"><div class="flex items-center gap-2"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100 text-[10px] font-black text-slate-500">{{$student->roll_number?:'—'}}</span><div class="min-w-0"><h3 class="truncate text-sm font-black">{{$student->full_name}}</h3><p class="row-state text-[9px] font-bold text-slate-400">{{$saved?'Saved '.optional($saved->marked_at)->format('h:i A'):'Not changed'}}</p></div></div></div>
            <div class="grid grid-cols-4 gap-1.5">
                <button type="button" data-value="present" class="status-button rounded-lg px-2 py-2.5 text-[10px] font-black">PRESENT</button>
                <button type="button" data-value="absent" class="status-button rounded-lg px-2 py-2.5 text-[10px] font-black">ABSENT</button>
                <button type="button" data-value="late" class="status-button rounded-lg px-2 py-2.5 text-[10px] font-black">LATE</button>
                <button type="button" data-value="excused" class="status-button rounded-lg px-2 py-2.5 text-[10px] font-black">EXCUSED</button>
            </div>
        </article>
        @empty<div class="p-10 text-center text-sm font-bold text-amber-700">No students are assigned to your routine group.</div>@endforelse
    </div>
</section>
<form method="POST" action="{{route('admin.teacher.attendance.finish',$routineLesson)}}" class="fixed inset-x-0 bottom-0 z-20 border-t bg-white/95 p-3 safe-bottom backdrop-blur sm:static sm:mt-4 sm:rounded-2xl sm:border">@csrf<button @disabled($students->isEmpty()) class="mx-auto block w-full max-w-6xl rounded-xl bg-emerald-600 px-5 py-3.5 text-sm font-black text-white disabled:opacity-40">Finish & save attendance</button></form>
@endsection
@push('scripts')
<script>
const attendanceUrl=@json(route('admin.teacher.attendance.save',$routineLesson)),token=document.querySelector('meta[name="csrf-token"]').content;
const colors={present:'bg-emerald-600 text-white',absent:'bg-red-600 text-white',late:'bg-amber-500 text-white',excused:'bg-blue-600 text-white'};
function paint(row){row.querySelectorAll('.status-button').forEach(button=>{Object.values(colors).join(' ').split(' ').forEach(c=>button.classList.remove(c));button.classList.add('bg-slate-100','text-slate-500');if(button.dataset.value===row.dataset.status){button.classList.remove('bg-slate-100','text-slate-500');colors[button.dataset.value].split(' ').forEach(c=>button.classList.add(c))}})}
document.querySelectorAll('.attendance-row').forEach(row=>{paint(row);row.querySelectorAll('.status-button').forEach(button=>button.addEventListener('click',async()=>{row.dataset.status=button.dataset.value;paint(row);const state=row.querySelector('.row-state');state.textContent='Saving…';try{const response=await fetch(attendanceUrl,{method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify({student_id:Number(row.dataset.student),status:button.dataset.value})});const data=await response.json();if(!response.ok)throw new Error(data.message||'Could not save');state.textContent='Saved '+data.saved_at;state.className='row-state text-[9px] font-bold text-emerald-600';document.getElementById('page-save-state').textContent='All changes saved'}catch(error){state.textContent=error.message;state.className='row-state text-[9px] font-bold text-red-600'}}))});
document.getElementById('attendance-search')?.addEventListener('input',event=>{const q=event.target.value.toLowerCase();document.querySelectorAll('.attendance-row').forEach(row=>row.style.display=row.dataset.search.includes(q)?'':'none')});
</script>
@endpush
