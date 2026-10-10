@extends(auth()->user()->isTeacher() ? 'teaching_learning.teacher-workspace.layout' : 'examinations.layouts.app')
@section('title', 'Enter Marks')
@section('content')
@php
    $exam = $examinationSubject->examination;
    $subject = $examinationSubject->offering->subject;
    $hasTheory = $theorySectionIds->isNotEmpty() && (float) $examinationSubject->theory_full_marks > 0;
    $hasPractical = $practicalSectionIds->isNotEmpty() && (float) $examinationSubject->practical_full_marks > 0;
    $theoryCode = $subject->code;
    $practicalCode = $subject->practical_code ?: $subject->code;
    $theorySectionNames = $sections->whereIn('id', $theorySectionIds)->pluck('name');
    $practicalSectionNames = $sections->whereIn('id', $practicalSectionIds)->pluck('name');
    $showRemarks = true;
    $registerColumnCount = 2 + (int) $hasTheory + (int) $hasPractical + (int) $showRemarks;
    $submissionLocked = (bool) $markSubmission?->is_locked;
    $entryReadOnly = $submissionLocked || $exam->is_locked;
    $submissionMissing = collect($submissionProgress)->sum('missing');
    $submissionExpected = collect($submissionProgress)->sum('expected');
    $selectedSectionId = $selectedSection?->id;
    $markAutosaveUrl = route('admin.examinations.marks.autosave', [
        $examinationSubject,
        'component' => $component,
        'section' => $selectedSectionId,
    ]);
@endphp
<div class="mx-auto max-w-6xl space-y-3 sm:space-y-4">
    <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-[#0b2415] to-[#1a5632] p-3.5 text-white shadow-sm sm:p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0"><p class="text-[8px] font-black uppercase tracking-[.16em] text-amber-300">{{$exam->name}} · Marks entry</p><h1 class="mt-1 truncate text-lg font-black sm:text-xl">{{$subject->name}}</h1><p class="mt-0.5 text-[10px] font-semibold text-white/60">{{$examinationSubject->offering->department->name}} · {{$selectedSection?->name ?? 'Choose a section'}}</p></div>
            <div class="flex shrink-0 items-center gap-2">@if($entryReadOnly)<span class="rounded-lg bg-amber-400 px-2 py-1.5 text-[9px] font-black text-amber-950">🔒 VIEW ONLY</span>@endif<a href="{{auth()->user()->isTeacher()?route('admin.examinations.index',['exam'=>$exam->id]):route('admin.examinations.index',['exam'=>$exam->id])}}" class="rounded-lg border border-white/15 bg-white/10 px-2.5 py-1.5 text-[10px] font-black hover:bg-white/20">← Subjects</a></div>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
            @if($hasTheory)<div class="rounded-xl border border-white/10 bg-white/10 px-3 py-2"><p class="text-[8px] font-black uppercase tracking-widest text-emerald-200">Theory</p><p class="mt-0.5 text-xs font-black sm:text-sm">{{$theoryCode}} · FM {{$examinationSubject->theory_full_marks}} · PM {{$examinationSubject->theory_pass_marks}}</p></div>@endif
            @if($hasPractical)<div class="rounded-xl border border-white/10 bg-white/10 px-3 py-2"><p class="text-[8px] font-black uppercase tracking-widest text-purple-200">Practical</p><p class="mt-0.5 text-xs font-black sm:text-sm">{{$practicalCode}} · FM {{$examinationSubject->practical_full_marks}} · PM {{$examinationSubject->practical_pass_marks}}</p></div>@endif
            <div class="col-span-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2 sm:ml-auto"><p class="text-[8px] font-black uppercase tracking-widest text-white/45">Exam period</p><p class="mt-0.5 text-xs font-black">{{$exam->starts_on?->format('M d')??'—'}} – {{$exam->ends_on?->format('M d, Y')??'—'}}</p></div>
        </div>
    </section>

    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">@foreach($errors->all() as $error)<p>• {{$error}}</p>@endforeach</div>@endif

    <section class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex items-start justify-between gap-3"><div><p class="text-[9px] font-black uppercase tracking-widest text-emerald-700">Step 3 of 3</p><h2 class="mt-0.5 text-sm font-black text-slate-900">Choose a section</h2><p class="mt-0.5 text-[10px] font-semibold text-slate-400">Students are shown alphabetically after you choose a section.</p></div><span class="rounded-lg bg-slate-100 px-2 py-1 text-[9px] font-black text-slate-600">{{$sections->count()}} available</span></div>
        <div class="mt-3 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
            @foreach($sections as $section)
                <a href="{{route('admin.examinations.marks.edit', [$examinationSubject, 'component' => $component, 'section' => $section->id])}}" class="flex min-h-12 items-center justify-between rounded-xl border px-3 py-2 text-left transition {{$selectedSection?->id === $section->id ? 'border-emerald-600 bg-emerald-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300 hover:bg-emerald-50'}}"><span class="truncate text-xs font-black">{{$section->name}}</span><span class="ml-2 text-sm">{{$selectedSection?->id === $section->id ? '✓' : '→'}}</span></a>
            @endforeach
        </div>
    </section>

    @if($selectedSection)
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header class="relative z-20 flex flex-col gap-2 border-b border-slate-200 bg-white/95 px-3 py-2.5 backdrop-blur sm:flex-row sm:items-center sm:justify-between sm:px-4 sm:py-3">
            <div class="flex items-center justify-between gap-3"><div><h2 class="text-sm font-black">{{$students->count()}} students</h2><p class="text-[10px] font-semibold text-slate-400">{{$entryReadOnly?'Submitted marks are view-only.':'Marks save automatically in each row.'}}</p></div><span id="mark-save-state" class="shrink-0 rounded-lg {{$entryReadOnly?'bg-amber-100 text-amber-900':'bg-emerald-50 text-emerald-700'}} px-2 py-1 text-[9px] font-black">{{$entryReadOnly?'🔒 Locked':'Autosave ready'}}</span></div>
            <label class="relative block sm:w-60"><svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m21 21-4.3-4.3M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z"/></svg><input id="mark-search" placeholder="Search name or roll…" class="w-full rounded-xl border-slate-200 py-2 pl-9 pr-3 text-xs focus:border-emerald-600 focus:ring-emerald-100"></label>
        </header>

        <div class="mark-table-wrap pt-2 sm:pt-0" role="region" aria-label="Student marks register">
            <table class="min-w-full table-fixed divide-y divide-slate-100">
                <thead class="bg-slate-50 text-[9px] font-black uppercase tracking-wider text-slate-500"><tr><th class="w-[5%] px-2 py-2.5 text-center">S.N.</th><th class="w-[35%] px-3 py-2.5 text-left">Student</th>@if($hasTheory)<th class="w-[27%] px-3 py-2.5 text-left text-emerald-700">Theory · FM {{$examinationSubject->theory_full_marks}} · PM {{$examinationSubject->theory_pass_marks}}</th>@endif @if($hasPractical)<th class="w-[27%] px-3 py-2.5 text-left text-purple-700">Practical · FM {{$examinationSubject->practical_full_marks}} · PM {{$examinationSubject->practical_pass_marks}}</th>@endif @if($showRemarks)<th class="px-3 py-2.5 text-left">Remarks</th>@endif</tr></thead>
                <tbody id="mark-rows" class="divide-y divide-slate-100">
                @forelse($students as $student)
                    @php
                        $mark = $marks->get($student->id);
                        $studentSectionId = (int) $student->section_id;
                        $canTheory = $hasTheory && ($theorySectionIds->contains($studentSectionId) || $theorySectionNames->contains($student->section));
                        $canPractical = $hasPractical && ($practicalSectionIds->contains($studentSectionId) || $practicalSectionNames->contains($student->section));
                        $theoryAbsent = $mark ? (bool) $mark->theory_is_absent : false;
                        $practicalAbsent = $mark ? (bool) $mark->practical_is_absent : false;
                    @endphp
                    <tr data-student="{{$student->id}}" data-search="{{strtolower($student->full_name.' '.$student->roll_number.' '.$student->section)}}" class="transition-colors hover:bg-slate-50/70">
                        <td class="px-2 py-3 text-center text-[10px] font-black tabular-nums text-slate-400">{{$loop->iteration}}</td>
                        <td class="px-3 py-3"><div class="student-identity"><img src="{{$student->photo_url}}" alt="" class="student-photo" loading="lazy"><div class="min-w-0"><p class="truncate text-xs font-black text-slate-900 sm:text-sm">{{$student->full_name}}</p><p class="mt-0.5 truncate text-[9px] font-bold text-slate-400">{{$student->roll_number?:'No roll'}} · {{$student->section}} · <span class="row-save-state">{{$mark?'Saved':'New'}}</span></p></div></div></td>
                        @if($hasTheory)<td class="px-3 py-2" data-mobile-label="Theory · FM {{$examinationSubject->theory_full_marks}} · PM {{$examinationSubject->theory_pass_marks}}">@if($canTheory)<div data-component-panel="theory" class="flex items-center gap-1.5 rounded-xl border border-emerald-100 bg-emerald-50/50 p-1.5"><input data-role="score" data-locked="{{$entryReadOnly ? 1 : 0}}" aria-label="Theory marks for {{$student->full_name}}" placeholder="" type="text" inputmode="decimal" value="{{$mark?->theory_marks}}" @disabled($theoryAbsent || $entryReadOnly) class="min-w-0 flex-1 rounded-lg border-emerald-200 bg-white px-2 py-2 text-center text-base font-black focus:border-emerald-600 focus:ring-emerald-100 disabled:bg-slate-100"><label data-role="absent-label" title="Mark theory absent" class="shrink-0 {{$entryReadOnly?'cursor-not-allowed opacity-60':'cursor-pointer'}} rounded-lg px-2 py-2 text-[9px] font-black {{$theoryAbsent?'bg-red-600 text-white':'border border-emerald-200 bg-white text-slate-500'}}"><input data-role="absent" type="checkbox" value="1" @checked($theoryAbsent) @disabled($entryReadOnly) class="sr-only"><span>{{$theoryAbsent?'A ✓':'A'}}</span></label></div>@else<span class="text-[10px] font-bold text-slate-300"></span>@endif</td>@endif
                        @if($hasPractical)<td class="px-3 py-2" data-mobile-label="Practical · FM {{$examinationSubject->practical_full_marks}} · PM {{$examinationSubject->practical_pass_marks}}">@if($canPractical)<div data-component-panel="practical" class="flex items-center gap-1.5 rounded-xl border border-purple-100 bg-purple-50/50 p-1.5"><input data-role="score" data-locked="{{$entryReadOnly ? 1 : 0}}" aria-label="Practical marks for {{$student->full_name}}" placeholder="" type="text" inputmode="decimal" value="{{$mark?->practical_marks}}" @disabled($practicalAbsent || $entryReadOnly) class="min-w-0 flex-1 rounded-lg border-purple-200 bg-white px-2 py-2 text-center text-base font-black focus:border-purple-600 focus:ring-purple-100 disabled:bg-slate-100"><label data-role="absent-label" title="Mark practical absent" class="shrink-0 {{$entryReadOnly?'cursor-not-allowed opacity-60':'cursor-pointer'}} rounded-lg px-2 py-2 text-[9px] font-black {{$practicalAbsent?'bg-red-600 text-white':'border border-purple-200 bg-white text-slate-500'}}"><input data-role="absent" type="checkbox" value="1" @checked($practicalAbsent) @disabled($entryReadOnly) class="sr-only"><span>{{$practicalAbsent?'A ✓':'A'}}</span></label></div>@else<span class="text-[10px] font-bold text-slate-300"></span>@endif</td>@endif
                        @if($showRemarks)<td data-admin-remarks class="px-3 py-2"><input data-role="remarks" value="{{$mark?->remarks}}" placeholder="Optional remark" @disabled($entryReadOnly) class="w-full rounded-lg border-slate-200 px-2 py-2 text-xs focus:border-emerald-600 focus:ring-emerald-100 disabled:bg-slate-100"></td>@endif
                    </tr>
                @empty<tr><td colspan="{{$registerColumnCount}}" class="p-10 text-center text-sm font-bold text-amber-600">No eligible students for this subject and your assigned sections.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <footer class="flex items-center justify-between gap-3 border-t bg-slate-50 px-4 py-3"><p class="text-[10px] font-semibold leading-4 text-slate-400">Marks save after typing. ABS is separate for theory and practical.</p><button type="button" id="retry-unsaved" class="hidden shrink-0 rounded-lg bg-slate-900 px-3 py-2 text-[10px] font-black text-white">Retry failed</button></footer>
    </section>
    @else
    <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm"><p class="text-xl">↑</p><h2 class="mt-2 text-sm font-black text-slate-800">Select a section to start</h2><p class="mt-1 text-xs font-semibold text-slate-400">Only students enrolled in this subject and section will appear.</p></section>
    @endif

    @if($selectedSection && auth()->user()->isTeacher())
        @if($submissionLocked)
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4"><div class="flex items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-600 font-black text-white">✓</span><div><h2 class="text-sm font-black text-emerald-950">{{$selectedSection->name}} marks submitted and locked</h2><p class="mt-0.5 text-[10px] font-semibold text-emerald-700">Submitted {{$markSubmission->locked_at?->format('M d, Y · h:i A')}}. You can still enter marks for your other sections.</p></div></div>
                @if($markSubmission->unlock_is_pending)<div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-800">Unlock requested {{$markSubmission->unlock_requested_at?->diffForHumans()}}. Waiting for admin approval.<p class="mt-1 text-[10px] font-semibold">“{{$markSubmission->unlock_reason}}”</p></div>
                @else<form method="POST" action="{{route('admin.examinations.marks.request-unlock',$markSubmission)}}" class="mt-3 flex flex-col gap-2 sm:flex-row">@csrf<input name="reason" required minlength="5" maxlength="500" placeholder="Why do you need to correct these marks?" class="min-w-0 flex-1 rounded-xl border-emerald-200 bg-white px-3 py-2.5 text-xs"><button class="shrink-0 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-black text-amber-950">Request admin unlock</button></form>@endif
            </section>
        @else
            <section class="flex flex-col gap-3 rounded-2xl border {{$submissionMissing?'border-amber-200 bg-amber-50':'border-emerald-200 bg-emerald-50'}} p-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-[9px] font-black uppercase tracking-widest {{$submissionMissing?'text-amber-700':'text-emerald-700'}}">{{$selectedSection->name}} section</p><h2 class="mt-0.5 text-sm font-black {{$submissionMissing?'text-amber-950':'text-emerald-950'}}">{{$submissionMissing?'Complete all marks before submission':'All marks are complete'}}</h2><p class="mt-0.5 text-[10px] font-semibold {{$submissionMissing?'text-amber-700':'text-emerald-700'}}">{{$submissionMissing?$submissionMissing.' of '.$submissionExpected.' entries are still missing.':'This section will become view-only after submission.'}}</p></div><form method="POST" action="{{route('admin.examinations.marks.submit',$examinationSubject)}}">@csrf<input type="hidden" name="section" value="{{$selectedSection->id}}"><input type="hidden" name="component" value="{{$component}}"><button @disabled($submissionMissing>0 || $submissionExpected===0) class="w-full shrink-0 rounded-xl bg-emerald-700 px-5 py-3 text-xs font-black text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto">Submit {{$selectedSection->name}} & lock</button></form></section>
        @endif
    @endif
</div>
@endsection

@push('styles')
<style>
.mark-table-wrap{width:100%;height:auto;min-height:0;max-height:none;overflow:visible!important;overscroll-behavior:auto!important;touch-action:pan-y}
@media(max-width:639px){
    .mark-table-wrap{padding:0;background:#fff}
    .mark-table-wrap table,.mark-table-wrap tbody{display:block;width:100%}
    .mark-table-wrap thead{display:none}
    #mark-rows{background:#fff}
    #mark-rows tr[data-student]{display:grid;width:100%;grid-template-columns:minmax(0,1fr) 5.8rem 5.8rem;column-gap:.45rem;row-gap:.55rem;margin:0;border:0;border-bottom:1px solid #dbe3eb;border-radius:0;background:#fff;padding:.9rem .75rem;box-shadow:none}
    #mark-rows tr[data-student]:last-child{border-bottom:0}
    #mark-rows td{display:block;min-width:0;padding:0;border:0;align-self:center}
    #mark-rows td:first-child{display:none}
    #mark-rows td[data-admin-remarks]{display:none}
    #mark-rows td:nth-child(2){grid-column:1;grid-row:1;padding-right:.15rem}
    #mark-rows td:nth-child(2) p:first-child{font-size:.87rem;line-height:1.15rem;white-space:normal}
    #mark-rows td:nth-child(2) p:last-child{font-size:.58rem;line-height:.85rem;white-space:normal}
    #mark-rows td[data-mobile-label]::before{content:attr(data-mobile-label);display:block;margin:0 0 .3rem;font-size:.52rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#64748b}
    #mark-rows [data-component-panel]{gap:.2rem;padding:0;border:0;border-radius:0;background:transparent}
    #mark-rows [data-role="score"]{height:2.7rem;min-width:0;padding:.2rem;font-size:1rem;border:1.5px solid #94a3b8;border-radius:.5rem}
    #mark-rows [data-role="absent-label"]{min-width:1.8rem;padding:.75rem .15rem;text-align:center;font-size:.55rem;border-radius:.5rem}
    .student-identity{display:flex;align-items:center;gap:.55rem}.student-photo{width:2.5rem;height:2.5rem;flex:none;border-radius:9999px;object-fit:cover;background:#e2e8f0}
}
@media(min-width:640px){
    .mark-table-wrap{background:#f3f6fa;padding:.75rem}
    .mark-table-wrap table,.mark-table-wrap tbody{display:block;width:100%}
    .mark-table-wrap thead{display:none}
    #mark-rows{background:#fff;border:1px solid #dbe3eb;border-radius:1rem;overflow:hidden}
    #mark-rows tr[data-student]{display:grid;grid-template-columns:minmax(15rem,1fr) minmax(9rem,.42fr) minmax(9rem,.42fr) minmax(12rem,.6fr);gap:1rem;align-items:center;border-bottom:1px solid #dbe3eb;padding:1rem 1.25rem;background:#fff}
    #mark-rows tr[data-student]:last-child{border-bottom:0}
    #mark-rows td{display:block;min-width:0;padding:0;border:0}
    #mark-rows td:first-child{display:none}
    #mark-rows td:nth-child(2){grid-column:1}
    #mark-rows td[data-mobile-label]::before{content:attr(data-mobile-label);display:block;margin-bottom:.35rem;font-size:.62rem;font-weight:900;letter-spacing:.09em;text-transform:uppercase;color:#64748b}
    #mark-rows [data-component-panel]{gap:.35rem;padding:0;border:0;background:transparent}
    #mark-rows [data-role="score"]{height:3rem;font-size:1.05rem;border:1.5px solid #94a3b8}
    #mark-rows [data-role="absent-label"]{min-width:2.25rem;padding:.85rem .25rem}
    #mark-rows [data-admin-remarks]{border:1.5px solid #94a3b8!important;border-radius:.65rem!important;background:#fff;padding:.5rem!important}
    #mark-rows [data-admin-remarks]::before{content:'Remarks';display:block;margin-bottom:.35rem;font-size:.62rem;font-weight:900;letter-spacing:.09em;text-transform:uppercase;color:#64748b}
    #mark-rows [data-role="remarks"]{height:2.35rem;border:0;font-size:.8rem;box-shadow:none}
    .student-identity{display:flex;align-items:center;gap:.75rem}.student-photo{display:block;width:3.25rem;height:3.25rem;flex:none;border-radius:9999px;object-fit:cover;background:#e2e8f0}
}
</style>
@endpush

@push('scripts')
<script>
const markUrl=@json($markAutosaveUrl),markToken=document.querySelector('meta[name="csrf-token"]').content,markTimers=new Map(),failedSaves=new Map();
document.getElementById('mark-search')?.addEventListener('input',event=>{const query=event.target.value.toLowerCase().trim();document.querySelectorAll('#mark-rows tr[data-search]').forEach(row=>row.style.display=row.dataset.search.includes(query)?'':'none')});
function setAbsentState(panel){const absent=panel.querySelector('[data-role="absent"]'),score=panel.querySelector('[data-role="score"]'),label=panel.querySelector('[data-role="absent-label"]');score.disabled=absent.checked||score.dataset.locked==='1';label.classList.toggle('bg-red-600',absent.checked);label.classList.toggle('text-white',absent.checked);label.classList.toggle('border',!absent.checked);label.classList.toggle('bg-white',!absent.checked);label.classList.toggle('text-slate-500',!absent.checked);label.querySelector('span').textContent=absent.checked?'A ✓':'A'}
async function autosaveComponent(row,panel){const component=panel.dataset.componentPanel,score=panel.querySelector('[data-role="score"]'),absent=panel.querySelector('[data-role="absent"]'),remarks=row.querySelector('[data-role="remarks"]'),state=row.querySelector('.row-save-state'),global=document.getElementById('mark-save-state'),key=row.dataset.student+':'+component;state.textContent='Saving…';state.className='row-save-state text-amber-600';global.textContent='Saving…';try{const response=await fetch(markUrl,{method:'PUT',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':markToken},body:JSON.stringify({component,student_id:Number(row.dataset.student),score:score.value===''?null:score.value,is_absent:absent.checked?1:0,...(remarks?{remarks:remarks.value||null}:{})})});const raw=await response.text();let data={};try{data=raw?JSON.parse(raw):{}}catch(ignore){}if(!response.ok){const validation=Object.values(data.errors||{}).flat()[0];throw new Error(validation||data.message||('Could not save (HTTP '+response.status+').'))}failedSaves.delete(key);state.textContent='Saved '+(data.saved_at||'now');state.className='row-save-state text-emerald-600';global.textContent='All changes saved';global.className='shrink-0 rounded-lg bg-emerald-50 px-2 py-1 text-[9px] font-black text-emerald-700'}catch(error){failedSaves.set(key,{row,panel});state.textContent=error.message;state.className='row-save-state text-red-600';global.textContent='Save failed';global.className='shrink-0 rounded-lg bg-red-50 px-2 py-1 text-[9px] font-black text-red-700'}finally{document.getElementById('retry-unsaved')?.classList.toggle('hidden',failedSaves.size===0)}}
function scheduleSave(row,panel,immediate=false){const key=row.dataset.student+':'+panel.dataset.componentPanel;clearTimeout(markTimers.get(key));markTimers.set(key,setTimeout(()=>autosaveComponent(row,panel),immediate?0:650))}
document.querySelectorAll('#mark-rows tr[data-student]').forEach(row=>{const panels=[...row.querySelectorAll('[data-component-panel]')];panels.forEach(panel=>{setAbsentState(panel);panel.querySelector('[data-role="score"]').addEventListener('input',()=>scheduleSave(row,panel));panel.querySelector('[data-role="absent"]').addEventListener('change',()=>{setAbsentState(panel);scheduleSave(row,panel,true)})});const remarks=row.querySelector('[data-role="remarks"]');remarks?.addEventListener('input',()=>{if(panels[0])scheduleSave(row,panels[0])});remarks?.addEventListener('blur',()=>{if(panels[0])scheduleSave(row,panels[0],true)})});
document.getElementById('retry-unsaved')?.addEventListener('click',()=>[...failedSaves.values()].forEach(item=>autosaveComponent(item.row,item.panel)));
</script>
@endpush
