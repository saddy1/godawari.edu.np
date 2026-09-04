<section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">
    <header class="flex flex-col gap-3 bg-gradient-to-r from-emerald-50 to-white p-4 sm:flex-row sm:items-center sm:justify-between"><div><div class="flex flex-wrap gap-1.5"><span class="rounded bg-[#1a5632] px-2 py-1 text-[8px] font-black uppercase text-white">{{$examination->status}}</span><span class="rounded bg-white px-2 py-1 text-[8px] font-black uppercase text-gray-600">{{$examination->category}}</span><span class="rounded bg-blue-50 px-2 py-1 text-[8px] font-black uppercase text-blue-700">{{$examination->scope_type==='organization'?'Whole organization':$examination->departments->count().' selected faculties'}}</span></div><h2 class="mt-2 text-xl font-black">{{$examination->name}}</h2><p class="mt-1 text-[10px] font-semibold text-gray-500">{{$examination->organization->name}} · {{$examination->departments->pluck('name')->implode(', ')}} · {{$examination->sections->count()}} sections</p></div><div class="flex gap-2"><a href="{{route('admin.examinations.index')}}" class="rounded-lg border bg-white px-3 py-2 text-xs font-black text-gray-600">Close</a>@if($canManage)<button type="button" onclick="document.getElementById('exam-details').showModal()" class="rounded-lg bg-[#1a5632] px-3 py-2 text-xs font-black text-white">Edit setup</button>@endif</div></header>
    @include('examinations.partials.subject-table')
</section>

@if($canManage && $unlockRequests->isNotEmpty())
<section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm"><div class="mb-3"><p class="text-[9px] font-black uppercase tracking-widest text-amber-700">Correction requests</p><h2 class="text-base font-black text-amber-950">Marks waiting for unlock</h2></div><div class="grid gap-2 sm:grid-cols-2">@foreach($unlockRequests as $submission)<article class="rounded-xl border border-amber-200 bg-white p-3"><div class="flex items-start justify-between gap-2"><div><b class="block text-sm text-slate-900">{{$submission->examinationSubject->offering->subject->name}}</b><p class="mt-0.5 text-[10px] font-semibold text-slate-500">{{$submission->teacher->name}} · requested {{$submission->unlock_requested_at->diffForHumans()}}</p></div><span class="rounded-md bg-amber-100 px-2 py-1 text-[8px] font-black uppercase text-amber-800">Pending</span></div><p class="mt-2 rounded-lg bg-amber-50 px-2.5 py-2 text-[10px] font-semibold text-amber-900">“{{$submission->unlock_reason}}”</p><form method="POST" action="{{route('admin.examinations.marks.unlock',$submission)}}" class="mt-2">@csrf @method('PATCH')<button class="w-full rounded-lg bg-amber-600 px-3 py-2 text-xs font-black text-white">Approve unlock</button></form></article>@endforeach</div></section>
@endif

@if($canManage)
@php $section = 'flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-[#1a5632]'; @endphp
<dialog id="exam-details" class="m-auto w-[calc(100%_-_2rem)] max-w-2xl overflow-hidden rounded-2xl border border-gray-100 p-0 shadow-2xl backdrop:bg-gray-950/60 backdrop:backdrop-blur-sm">
    <form method="POST" action="{{route('admin.examinations.update',$examination)}}" class="flex max-h-[85vh] flex-col">
        @csrf @method('PATCH')
        @foreach($examination->sections as $s)<input type="hidden" name="section_ids[]" value="{{$s->id}}">@endforeach

        <header class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-white px-5 py-4">
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-widest text-[#1a5632]">Exam settings</p>
                <h2 class="truncate text-base font-black text-gray-900">Edit {{$examination->name}}</h2>
            </div>
            <button type="button" onclick="this.closest('dialog').close()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm ring-1 ring-gray-200 transition-colors hover:bg-gray-50 hover:text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-5">
            <div class="space-y-5">

                <div>
                    <p class="{{$section}}"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Basic info</p>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        <div><label class="{{$label}}">Name</label><input name="name" value="{{$examination->name}}" required class="{{$input}}"></div>
                        <div><label class="{{$label}}">Category</label><input name="category" value="{{$examination->category}}" required class="{{$input}}"></div>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-5">
                    <p class="{{$section}}"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Schedule &amp; status</p>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div><label class="{{$label}}">Starts</label><input type="date" name="starts_on" value="{{$examination->starts_on?->format('Y-m-d')}}" class="{{$input}}"></div>
                        <div><label class="{{$label}}">Ends</label><input type="date" name="ends_on" value="{{$examination->ends_on?->format('Y-m-d')}}" class="{{$input}}"></div>
                        <div><label class="{{$label}}">Daily start</label><input type="time" name="starts_at" value="{{substr((string)$examination->starts_at,0,5)}}" class="{{$input}}"></div>
                        <div><label class="{{$label}}">Status</label><select name="status" class="{{$input}}">@foreach(['draft'=>'Draft — setup','ongoing'=>'Ongoing — marks open','completed'=>'Completed — locked','published'=>'Published — final'] as $value=>$text)<option value="{{$value}}" @selected($examination->status===$value)>{{$text}}</option>@endforeach</select></div>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-5">
                    <p class="{{$section}}"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Durations</p>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        <div><label class="{{$label}}">Theory minutes</label><input type="number" name="theory_duration_minutes" min="1" max="600" value="{{$examination->theory_duration_minutes??180}}" class="{{$input}}"></div>
                        <div><label class="{{$label}}">Practical minutes</label><input type="number" name="practical_duration_minutes" min="1" max="600" value="{{$examination->practical_duration_minutes??120}}" class="{{$input}}"></div>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-5">
                    <p class="{{$section}}"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Notes</p>
                    <div class="mt-2"><textarea name="notes" rows="3" class="{{$input}}" placeholder="Optional notes about this exam…">{{$examination->notes}}</textarea></div>
                </div>

            </div>
        </div>

        <footer class="flex shrink-0 justify-end gap-2 border-t border-gray-100 bg-gray-50/60 px-5 py-4">
            <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-xs font-black text-gray-600 transition-colors hover:bg-gray-50">Cancel</button>
            <button class="rounded-lg bg-[#1a5632] px-5 py-2.5 text-xs font-black text-white shadow-sm transition-colors hover:bg-[#164a2a]">Save exam setup</button>
        </footer>
    </form>
</dialog>
@endif

@if($examination->subjects->isNotEmpty())<section class="rounded-2xl border bg-white p-4 shadow-sm"><div class="mb-3 flex items-end justify-between"><div><p class="text-[10px] font-black uppercase text-emerald-700">Live evaluation</p><h2 class="text-base font-black">Result overview</h2></div><span class="text-[10px] font-semibold text-gray-400">Updates from entered marks</span></div><div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-8">@foreach([['Students',$analytics['students'],'gray'],['Pass %',$analytics['pass_rate'].'%','emerald'],['Fail %',$analytics['fail_rate'].'%','red'],['Average',$analytics['average'].'%','blue'],['Passed',$analytics['passed'],'emerald'],['Failed',$analytics['failed'],'red'],['Absent',$analytics['absent'],'amber'],['Incomplete',$analytics['incomplete'],'gray']] as [$text,$number,$color])<div class="rounded-xl border border-{{$color}}-100 bg-{{$color}}-50 p-3"><p class="text-lg font-black text-{{$color}}-700">{{$number}}</p><p class="text-[8px] font-black uppercase text-{{$color}}-600">{{$text}}</p></div>@endforeach</div></section>@endif
