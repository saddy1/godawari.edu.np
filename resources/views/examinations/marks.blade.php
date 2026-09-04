@extends('examinations.layouts.app')
@section('title', ucfirst($component).' Marks')
@section('content')
@php
    $exam = $examinationSubject->examination;
    $subject = $examinationSubject->offering->subject;
    $isPractical = $component === 'practical';
    $code = $isPractical ? ($subject->practical_code ?: $subject->code) : $subject->code;
    $fullMarks = $isPractical ? $examinationSubject->practical_full_marks : $examinationSubject->theory_full_marks;
    $passMarks = $isPractical ? $examinationSubject->practical_pass_marks : $examinationSubject->theory_pass_marks;
    $startsAt = $exam->starts_at;
    $duration = $isPractical ? $exam->practical_duration_minutes : $exam->theory_duration_minutes;
    $scoreColumn = $component.'_marks';
    $absentColumn = $component.'_is_absent';
@endphp
<div class="space-y-4">
    <section class="flex flex-col gap-3 rounded-2xl bg-gradient-to-r {{$isPractical?'from-purple-950 to-purple-700':'from-[#0b2415] to-[#1a5632]'}} p-5 text-white sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-[10px] font-black uppercase tracking-widest text-amber-300">{{$exam->name}} · {{ucfirst($component)}} marks</p><h1 class="mt-1 text-2xl font-black">{{$subject->name}} <span class="text-base text-white/60">{{$code}}</span></h1><p class="mt-1 text-xs font-semibold text-white/65">{{$examinationSubject->offering->department->name}} · Your assigned sections: {{$sections->pluck('name')->implode(', ')}}</p></div>
        <div class="flex gap-2">
            @if((float)$examinationSubject->theory_full_marks > 0)<a href="{{route('admin.examinations.marks.edit',[$examinationSubject,'component'=>'theory'])}}" class="rounded-xl px-4 py-2.5 text-xs font-black {{$component==='theory'?'bg-white text-[#1a5632]':'border border-white/20 bg-white/10'}}">Theory</a>@endif
            @if((float)$examinationSubject->practical_full_marks > 0)<a href="{{route('admin.examinations.marks.edit',[$examinationSubject,'component'=>'practical'])}}" class="rounded-xl px-4 py-2.5 text-xs font-black {{$component==='practical'?'bg-white text-purple-800':'border border-white/20 bg-white/10'}}">Practical</a>@endif
            <a href="{{auth()->user()->isTeacher()?route('admin.teacher.workspace'):route('admin.examinations.show',$exam)}}" class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-black">Back</a>
        </div>
    </section>

    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
        <div class="rounded-xl border bg-white p-3"><p class="text-xl font-black">{{$code}}</p><p class="text-[9px] font-black uppercase text-gray-400">{{ucfirst($component)}} code</p></div>
        <div class="rounded-xl border bg-white p-3"><p class="text-xl font-black">{{$fullMarks}}</p><p class="text-[9px] font-black uppercase text-gray-400">Full marks</p></div>
        <div class="rounded-xl border bg-white p-3"><p class="text-xl font-black text-emerald-700">{{$passMarks}}</p><p class="text-[9px] font-black uppercase text-gray-400">Pass marks</p></div>
        <div class="rounded-xl border bg-white p-3"><p class="text-sm font-black">{{$exam->starts_on?->format('M d')??'—'}} – {{$exam->ends_on?->format('M d, Y')??'—'}}</p><p class="text-[9px] font-black uppercase text-gray-400">Exam period</p></div>
        <div class="rounded-xl border bg-white p-3"><p class="text-sm font-black">{{$startsAt?substr((string)$startsAt,0,5):'—'}}{{$duration?' · '.$duration.' min':''}}</p><p class="text-[9px] font-black uppercase text-gray-400">Time</p></div>
    </div>

    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">@foreach($errors->all() as $error)<p>• {{$error}}</p>@endforeach</div>@endif

    <form method="POST" action="{{route('admin.examinations.marks.update',$examinationSubject)}}">@csrf @method('PUT')
        <input type="hidden" name="component" value="{{$component}}">
        <section class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <header class="flex flex-col gap-2 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-sm font-black">{{$students->count()}} eligible students</h2><p class="text-[10px] font-semibold text-gray-400">Each change saves automatically. The other component remains unchanged.</p></div><div class="flex items-center gap-2"><span id="mark-save-state" class="text-[10px] font-bold text-emerald-600">Autosave ready</span><input id="mark-search" placeholder="Search student..." class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs sm:w-52"></div></header>
            <div class="overflow-x-auto"><table class="min-w-full divide-y"><thead class="bg-gray-50 text-[9px] font-black uppercase text-gray-500"><tr><th class="px-4 py-2 text-left">Student</th><th class="w-40 px-3 py-2">{{$code}} / {{$fullMarks}}</th><th class="w-24 px-3 py-2">Attendance</th><th class="px-3 py-2 text-left">Remarks</th></tr></thead>
                <tbody id="mark-rows" class="divide-y">@forelse($students as $student)@php $mark=$marks->get($student->id); @endphp
                    <tr x-data="{absent:@js((bool)$mark?->{$absentColumn})}" :class="absent?'bg-red-50':''" data-student="{{$student->id}}" data-search="{{strtolower($student->full_name.' '.$student->roll_number.' '.$student->section)}}"><td class="px-4 py-2.5"><p class="text-xs font-black">{{$student->full_name}}</p><p class="text-[9px] font-bold text-gray-400">{{$student->roll_number?:'No roll'}} · {{$student->section}} · <span class="row-save-state">{{$mark?'Saved':'New'}}</span></p></td><td class="px-3 py-2"><input data-role="score" type="number" step="0.01" min="0" max="{{$fullMarks}}" name="marks[{{$student->id}}][score]" value="{{$mark?->{$scoreColumn}}}" :disabled="absent" class="w-full rounded-lg border-gray-200 px-2 py-2 text-center text-base font-black disabled:bg-gray-100 sm:text-xs"></td><td class="px-3 py-2 text-center"><label :class="absent?'bg-red-600 text-white':'border bg-white text-gray-500'" class="inline-flex cursor-pointer rounded-lg px-3 py-2 text-[10px] font-black"><input data-role="absent" type="checkbox" name="marks[{{$student->id}}][is_absent]" value="1" x-model="absent" class="sr-only"><span x-text="absent?'Absent ✓':'Present'"></span></label></td><td class="px-3 py-2"><input data-role="remarks" name="marks[{{$student->id}}][remarks]" value="{{$mark?->remarks}}" placeholder="Optional" class="w-full rounded-lg border-gray-200 px-2 py-2 text-xs"></td></tr>
                @empty<tr><td colspan="4" class="p-10 text-center text-sm font-bold text-amber-600">No eligible students. Synchronize compulsory subjects or assign electives to these sections first.</td></tr>@endforelse</tbody>
            </table></div>
            <div class="sticky bottom-0 flex items-center justify-between border-t bg-white/95 px-4 py-3 backdrop-blur"><p class="text-[10px] font-semibold text-gray-400">Autosave protects each student row. Use Save all as a final backup.</p><button @disabled($exam->is_locked||$students->isEmpty()) class="rounded-lg bg-[#1a5632] px-5 py-2.5 text-xs font-black text-white disabled:opacity-40">Save all</button></div>
        </section>
    </form>
</div>
@endsection
@push('styles')
<style>
@media(max-width:639px){#mark-rows{display:block;padding:.35rem}#mark-rows tr[data-student]{display:grid;grid-template-columns:minmax(0,1fr) 7rem;gap:.45rem;margin:.45rem 0;padding:.75rem;border:1px solid #e5e7eb;border-radius:1rem}#mark-rows td{display:block;padding:0}#mark-rows td:first-child{grid-column:1/-1}#mark-rows td:nth-child(2){grid-column:1}#mark-rows td:nth-child(3){grid-column:2;display:flex;align-items:center;justify-content:center}#mark-rows td:nth-child(4){grid-column:1/-1}table thead{display:none}}
</style>
@endpush
@push('scripts')<script>
document.getElementById('mark-search')?.addEventListener('input',e=>{const q=e.target.value.toLowerCase();document.querySelectorAll('#mark-rows tr[data-search]').forEach(row=>row.style.display=row.dataset.search.includes(q)?'':'none')});
const markUrl=@json(route('admin.examinations.marks.autosave',$examinationSubject)),markToken=document.querySelector('meta[name="csrf-token"]').content,markTimers=new Map();
async function autosaveMark(row){const score=row.querySelector('[data-role="score"]'),absent=row.querySelector('[data-role="absent"]'),remarks=row.querySelector('[data-role="remarks"]'),state=row.querySelector('.row-save-state'),global=document.getElementById('mark-save-state');state.textContent='Saving…';global.textContent='Saving…';try{const response=await fetch(markUrl,{method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':markToken},body:JSON.stringify({component:@json($component),student_id:Number(row.dataset.student),score:score.value===''?null:Number(score.value),is_absent:absent.checked?1:0,remarks:remarks.value||null})});const data=await response.json();if(!response.ok)throw new Error(data.message||Object.values(data.errors||{})[0]?.[0]||'Could not save');state.textContent='Saved '+data.saved_at;state.className='row-save-state text-emerald-600';global.textContent='All changes saved';global.className='text-[10px] font-bold text-emerald-600'}catch(error){state.textContent=error.message;state.className='row-save-state text-red-600';global.textContent='Save failed';global.className='text-[10px] font-bold text-red-600'}}
document.querySelectorAll('#mark-rows tr[data-student]').forEach(row=>{row.querySelectorAll('[data-role]').forEach(input=>{const event=input.dataset.role==='absent'?'change':'input';input.addEventListener(event,()=>{clearTimeout(markTimers.get(row));markTimers.set(row,setTimeout(()=>autosaveMark(row),input.dataset.role==='absent'?0:650))});if(input.dataset.role==='remarks')input.addEventListener('blur',()=>autosaveMark(row))})});
</script>@endpush
