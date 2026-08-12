{{-- Mobile Sidebar Backdrop --}}
<div x-show="sidebarOpen"
     x-transition.opacity
     @click="sidebarOpen = false"
     class="fixed inset-0 z-40 bg-gray-900/70 backdrop-blur-sm lg:hidden"
     style="display:none;"></div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       @click.capture="if ($event.target.closest('a')) sidebarOpen = false"
       class="fixed inset-y-0 left-0 z-50 w-60 text-white transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto flex flex-col h-dvh border-r shrink-0"
       style="background: linear-gradient(180deg, var(--theme-sidebar-bg, #0b2415) 0%, var(--theme-sidebar-gradient-end, #050f09) 100%); border-color: rgba(255,255,255,0.08);">
    <div class="flex items-center justify-between h-14 px-4 border-b shrink-0" style="border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.25);">
        <a href="{{ route('admin.work-tasks.index') }}" class="flex items-center gap-2.5 min-w-0">
            <div class="w-7 h-7 bg-white/90 rounded-lg flex items-center justify-center p-1 shrink-0">
                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-white leading-none truncate">{{ $siteSettings->get('app_name', 'School ERP') }}</p>
                <p class="text-[9px] uppercase tracking-widest font-semibold mt-0.5" style="color: var(--theme-secondary, #e2a024);">Work Tasks Module</p>
            </div>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden p-1 text-white/40 hover:text-white rounded-md hover:bg-white/10 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
        @php
            $active = fn(string ...$patterns) => collect($patterns)->contains(fn($p) => request()->is($p) || request()->routeIs($p));
            $canAssign = auth()->user()?->canAccess('work-tasks.create');
            $canReview = auth()->user()?->canAccess('work-tasks.review');
            $canGroups = auth()->user()?->canAccess('work-groups.manage');
            $canChecklists = auth()->user()?->canAccess('work-checklists.manage');
        @endphp

        <p class="px-2 pt-1 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Performance</p>
        <a href="{{ route('admin.work-tasks.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $active('admin/work-tasks') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-7 4h8m-8 4h5m-6 8h10a2 2 0 002-2V7.5L15.5 4H7a2 2 0 00-2 2v13a2 2 0 002 2z"/></svg>
            <span class="flex-1 truncate">Task Board</span>
        </a>

        @if($canAssign)
        <a href="{{ route('admin.work-tasks.index') }}#assign-task"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all text-white/60 hover:text-white hover:bg-white/8">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="flex-1 truncate">Assign Task</span>
        </a>
        @endif

        @if($canGroups)
        <a href="{{ route('admin.work-tasks.groups.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.work-tasks.groups.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="flex-1 truncate">Groups</span>
        </a>
        @endif

        @if($canChecklists)
        <a href="{{ route('admin.work-tasks.checklists.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.work-tasks.checklists.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M5 7h8M5 12h4M5 17h8"/></svg>
            <span class="flex-1 truncate">Checklists</span>
        </a>
        @endif

        @if($canReview)
        <a href="{{ route('admin.work-tasks.review-queue.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.work-tasks.review-queue.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 2a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
            <span class="flex-1 truncate">Review Queue</span>
        </a>
        @endif

        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">ERP</p>
        @if(auth()->user()?->canAccess(['dashboard.admin', 'dashboard.view', 'dashboard.financial']))
        <a href="{{ route('admin.dashboard') }}" class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all text-white/60 hover:text-white hover:bg-white/8">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span class="flex-1 truncate">Main Dashboard</span>
        </a>
        @endif
    </nav>
</aside>
