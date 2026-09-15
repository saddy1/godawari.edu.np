{{-- Mobile Sidebar Backdrop --}}
<div x-show="sidebarOpen"
     x-transition.opacity
     @click="sidebarOpen = false"
     class="fixed inset-0 z-40 bg-gray-900/70 backdrop-blur-sm lg:hidden"
     style="display:none;"></div>

{{-- Sidebar --}}
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       @click.capture="if ($event.target.closest('a')) sidebarOpen = false"
       class="fixed inset-y-0 left-0 z-50 w-64 text-white transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto flex flex-col h-dvh border-r shrink-0"
       style="background: linear-gradient(180deg, var(--theme-sidebar-bg, #0b2415) 0%, var(--theme-sidebar-gradient-end, #050f09) 100%); border-color: rgba(255,255,255,0.08);">

    {{-- Logo / Brand --}}
    <div class="flex items-center justify-between h-14 px-4 border-b shrink-0" style="border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.25);">
        <a href="{{ route('admin.founder.dashboard') }}" class="flex items-center gap-2.5 min-w-0">
            <div class="w-7 h-7 bg-white/90 rounded-lg flex items-center justify-center p-1 shrink-0">
                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-white leading-none truncate">{{ $siteSettings->get('app_name', 'School ERP') }}</p>
                <p class="text-[9px] uppercase tracking-widest font-semibold mt-0.5 text-amber-300">Founder View</p>
            </div>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden p-1 text-white/40 hover:text-white rounded-md hover:bg-white/10 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Install app prompt --}}
    <button type="button" data-pwa-install title="Install app"
            class="js-hidden mx-2 mt-3 flex shrink-0 items-center gap-2.5 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-3 py-2.5 text-left transition-colors hover:border-emerald-400/35 hover:bg-emerald-400/15">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-emerald-400/15 text-emerald-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-xs font-extrabold text-white">Install App</span>
            <span class="block text-[10px] font-semibold text-white/40">Add to home screen</span>
        </span>
    </button>

    @php
        $founderActive = fn (string ...$patterns) => collect($patterns)->contains(fn ($p) => request()->routeIs($p));
        $founderPendingTotal = 0;
        try {
            $founderPendingTotal = \App\Models\Hajiri\LeaveRequest::where('status', 'pending')->count()
                + \App\Models\Hajiri\StaffCardRequest::where('status', 'pending')->count()
                + \App\Models\Card\CardRequest::where('status', 'pending')->count()
                + \App\Models\ContactMessage::where('is_read', false)->count()
                + \App\Models\Work\WorkTaskSubmission::where('status', 'submitted')->count()
                + \App\Models\Admission::where('status', 'Pending')->count()
                + \App\Models\VacancyApplication::where('status', 'Pending')->count()
                + \App\Models\StoreRequisition::where('status', 'draft')->count()
                + \App\Models\StorePurchaseOrder::where('status', 'draft')->count()
                + \App\Models\Examination\ExaminationMarkSubmission::whereNotNull('unlock_requested_at')->whereNull('unlocked_at')->count();
        } catch (\Throwable) {
            $founderPendingTotal = 0;
        }
    @endphp

    {{-- Nav --}}
    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5 custom-scrollbar">

        <p class="px-2 pt-1 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Overview</p>
        <a href="{{ route('admin.founder.dashboard') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $founderActive('admin.founder.dashboard') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span class="flex-1 truncate">Teaching &amp; Learning Overview</span>
        </a>
        <a href="{{ route('admin.founder.attendance') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $founderActive('admin.founder.attendance') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-4v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h3.5a2.5 2.5 0 015 0H18a2 2 0 012 2z"/></svg>
            <span class="flex-1 truncate">Class Attendance</span>
        </a>
        <a href="{{ route('admin.founder.analysis') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $founderActive('admin.founder.analysis') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V9m4 8V5m4 12v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <span class="flex-1 truncate">Detailed Analysis</span>
        </a>
        <a href="{{ route('admin.founder.students.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $founderActive('admin.founder.students.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="flex-1 truncate">Students</span>
        </a>
        <a href="{{ route('admin.founder.pending') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $founderActive('admin.founder.pending') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 12h6m-6 4h6"/></svg>
            <span class="flex-1 truncate">Pending Approvals</span>
            @if($founderPendingTotal > 0)
                <span class="shrink-0 min-w-4 h-4 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $founderPendingTotal > 99 ? '99+' : $founderPendingTotal }}</span>
            @endif
        </a>

    </nav>

    @include('backend.partials.sidebar-user-footer')
</aside>
