{{-- Teaching & Learning Sidebar --}}
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
        <a href="{{ route('admin.teaching-learning.dashboard') }}" class="flex items-center gap-2.5 min-w-0">
            <div class="w-7 h-7 bg-white/90 rounded-lg flex items-center justify-center p-1 shrink-0">
                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-white leading-none truncate">{{ $siteSettings->get('app_name', 'School ERP') }}</p>
                <p class="text-[9px] uppercase tracking-widest font-semibold mt-0.5" style="color: var(--theme-secondary, #e2a024);">Teaching &amp; Learning</p>
            </div>
        </a>
        <button type="button" @click="sidebarOpen = false" class="lg:hidden p-1 text-white/40 hover:text-white rounded-md hover:bg-white/10 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    @php
        $tlUser = auth()->user();
        $canTlView = $tlUser?->canAccess('teaching-learning.subjects.view');
        $navActive = fn(string ...$patterns) => collect($patterns)->contains(fn ($pattern) => request()->routeIs($pattern) || request()->is($pattern));
    @endphp

    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5 custom-scrollbar">
        <p class="px-2 pt-1 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Curriculum</p>

        @if($canTlView)
            <a href="{{ route('admin.teaching-learning.dashboard') }}"
               class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.teaching-learning.dashboard') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                <span class="flex-1 truncate">Dashboard</span>
            </a>
            <a href="{{ route('admin.teaching-learning.subjects.index') }}"
               class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.teaching-learning.subjects.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span class="flex-1 truncate">Faculty Subjects</span>
            </a>
            <a href="{{ route('admin.teaching-learning.subject-assignments.index') }}"
               class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.teaching-learning.subject-assignments.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-2a9 9 0 11-16 0 9 9 0 0116 0z"/></svg>
                <span class="flex-1 truncate">Subject Assignments</span>
            </a>
            <a href="{{ route('admin.teaching-learning.routine-configuration.index') }}"
               class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.teaching-learning.routine-configuration.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="flex-1 truncate">Routine Configuration</span>
            </a>
            @can('teaching-learning.routine.view')
            <a href="{{ route('admin.teaching-learning.routine-builder.index') }}"
               class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.teaching-learning.routine-builder.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 012 2v12H4V7a2 2 0 012-2zm2 9h3v3H8z"/></svg>
                <span class="flex-1 truncate">Routine Builder</span>
                <span class="rounded bg-amber-400/20 px-1.5 py-0.5 text-[8px] font-black text-amber-300">NEW</span>
            </a>
            @endcan
        @endif

        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/20 uppercase tracking-widest">Coming soon</p>
        <div class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium text-white/25 cursor-not-allowed">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="flex-1 truncate">Class Attendance</span>
        </div>
    </nav>

    @include('backend.partials.sidebar-user-footer')
</aside>
