{{-- HR Sidebar --}}
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
        <a href="{{ route('admin.hr.members.index') }}" class="flex items-center gap-2.5 min-w-0">
            <div class="w-7 h-7 bg-white/90 rounded-lg flex items-center justify-center p-1 shrink-0">
                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-white leading-none truncate">{{ $siteSettings->get('app_name', 'School ERP') }}</p>
                <p class="text-[9px] uppercase tracking-widest font-semibold mt-0.5" style="color: var(--theme-secondary, #e2a024);">HR Module</p>
            </div>
        </a>
        <button type="button" @click="sidebarOpen = false" class="lg:hidden p-1 text-white/40 hover:text-white rounded-md hover:bg-white/10 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    @php
        $sidebarUser = auth()->user();
        $canHrView = $sidebarUser?->canAccess('hr.members.view');
        $canHrCreate = $sidebarUser?->canAccess('hr.members.create');
        $canHrEdit = $sidebarUser?->canAccess('hr.members.edit');
        $navActive = fn(string ...$patterns) => collect($patterns)->contains(fn ($pattern) => request()->routeIs($pattern) || request()->is($pattern));
    @endphp

    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5 custom-scrollbar">
        <p class="px-2 pt-1 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">People</p>

        @if($canHrView)
            <a href="{{ route('admin.hr.members.index') }}"
               class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.hr.members.index', 'admin/hr/members') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2a5 5 0 00-10 0v2m10 0H7m8-13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="flex-1 truncate">All People</span>
            </a>
        @endif

        @if($canHrCreate)
            <a href="{{ route('admin.hr.members.create') }}"
               class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.hr.members.create', 'admin/hr/members/create') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 4v16m8-8H4"/></svg>
                <span class="flex-1 truncate">New Member</span>
            </a>

            <a href="{{ route('admin.hr.members.import') }}"
               class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.hr.members.import', 'admin/hr/members/import') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span class="flex-1 truncate">Bulk Import</span>
            </a>
        @endif

        @if($canHrEdit)
            <a href="{{ route('admin.hr.members.promote.index') }}"
               class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.hr.members.promote.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                <span class="flex-1 truncate">Promote Students</span>
            </a>
        @endif

        @if($sidebarUser?->canAccess('settings.view'))
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Settings</p>
        <a href="{{ route('admin.hr.designations.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.hr.designations.*', 'admin/hr/designations*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span class="flex-1 truncate">Designations</span>
        </a>
        @endif
    </nav>

    <div class="px-3 py-3 border-t shrink-0" style="border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.3);">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-white text-xs font-bold border-2"
                 style="background-color: var(--theme-primary, #1a5632); border-color: var(--theme-secondary, #e2a024);">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-white truncate leading-tight">{{ auth()->user()->name ?? 'Admin' }}</p>
                <p class="text-[10px] text-white/35 truncate leading-tight mt-0.5">{{ auth()->user()->role_label ?? 'Admin' }}</p>
            </div>
        </div>
    </div>
</aside>
