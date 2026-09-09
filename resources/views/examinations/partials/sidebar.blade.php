<div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen=false" class="fixed inset-0 z-40 bg-gray-900/70 lg:hidden" style="display:none"></div>
<aside :class="sidebarOpen?'translate-x-0':'-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex h-dvh w-60 shrink-0 flex-col border-r border-white/10 text-white transition-transform lg:static lg:translate-x-0" style="background:linear-gradient(180deg,var(--theme-sidebar-bg) 0%,var(--theme-sidebar-gradient-end) 100%)">
    <div class="flex h-14 shrink-0 items-center justify-between border-b border-white/10 bg-black/20 px-4"><a href="{{route('admin.examinations.index')}}" class="flex min-w-0 items-center gap-2.5"><div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M9 8h6M5 4h14v16H5z"/></svg></div><div><p class="text-sm font-black">Examinations</p><p class="text-[9px] font-bold uppercase tracking-widest text-amber-300">Assessment & results</p></div></a><button @click="sidebarOpen=false" class="lg:hidden">×</button></div>
    <nav class="flex-1 space-y-1 overflow-y-auto p-2">
        <p class="px-2 pb-1 pt-2 text-[10px] font-bold uppercase tracking-widest text-white/30">Workspace</p>
        @if(auth()->user()->isTeacher())<a href="{{route('admin.teacher.workspace')}}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-bold text-amber-300 hover:bg-white/10">My teaching</a>@endif
        <a href="{{route('admin.examinations.index')}}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-bold {{request()->routeIs('admin.examinations.index')?'bg-white/15 text-white':'text-white/60 hover:bg-white/10 hover:text-white'}}"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h10"/></svg>Exam dashboard</a>
        @if(request()->route('examinationSubject'))<a href="{{route('admin.examinations.index',['exam'=>request()->route('examinationSubject')->examination_id])}}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-bold text-white/60 hover:bg-white/10 hover:text-white">← Exam setup</a>@endif

        @php
            $sidebarExamId = request()->route('examination')?->id
                ?? request()->route('examinationSubject')?->examination_id
                ?? request()->query('exam');
        @endphp
        @if($sidebarExamId && auth()->user()->canAccess('examinations.manage'))
            <p class="px-2 pb-1 pt-4 text-[10px] font-bold uppercase tracking-widest text-white/30">This exam</p>
            <a href="{{route('admin.examinations.admit-cards.index',$sidebarExamId)}}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-bold {{request()->routeIs('admin.examinations.admit-cards.*')?'bg-white/15 text-white':'text-white/60 hover:bg-white/10 hover:text-white'}}"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 0v6m-4-2h8M5 4h14v6a2 2 0 01-2 2H7a2 2 0 01-2-2V4z"/></svg>Admit Cards</a>
            <a href="{{route('admin.examinations.marksheets.index',$sidebarExamId)}}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-bold {{request()->routeIs('admin.examinations.marksheets.*')?'bg-white/15 text-white':'text-white/60 hover:bg-white/10 hover:text-white'}}"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Marksheets</a>
        @endif
    </nav>
    @include('backend.partials.sidebar-user-footer')
</aside>
