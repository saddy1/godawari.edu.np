{{-- Library Sidebar --}}
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
        <a href="{{ route('admin.library.dashboard') }}" class="flex items-center gap-2.5 min-w-0">
            <div class="w-7 h-7 bg-white/90 rounded-lg flex items-center justify-center p-1 shrink-0">
                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-white leading-none truncate">{{ $siteSettings->get('app_name', 'School ERP') }}</p>
                <p class="text-[9px] uppercase tracking-widest font-semibold mt-0.5" style="color: var(--theme-secondary, #e2a024);">Library Module</p>
            </div>
        </a>
        <button type="button" @click="sidebarOpen = false" class="lg:hidden p-1 text-white/40 hover:text-white rounded-md hover:bg-white/10 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    @php
        $navActive = fn(string ...$patterns) => collect($patterns)->contains(fn($p) => request()->routeIs($p) || request()->is($p));
    @endphp

    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5 custom-scrollbar">

        {{-- Dashboard --}}
        <p class="px-2 pt-1 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Library</p>

        <a href="{{ route('admin.library.dashboard') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.library.dashboard') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span class="flex-1 truncate">Dashboard</span>
        </a>

        {{-- Catalog --}}
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Catalog</p>

        <a href="{{ route('admin.library.books.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.library.books.index', 'admin.library.books.show', 'admin.library.books.edit') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/></svg>
            <span class="flex-1 truncate">Books</span>
        </a>

        <a href="{{ route('admin.library.books.create') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.library.books.create') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="flex-1 truncate">Add Book</span>
        </a>

        <a href="{{ route('admin.library.rules.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.library.rules.*', 'admin.library.categories.*', 'admin.library.patron-categories.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7l8-4 8 4-8 4-8-4zm0 5l8 4 8-4M4 17l8 4 8-4"/></svg>
            <span class="flex-1 truncate">Categories & Rules</span>
        </a>

        <a href="{{ route('admin.library.books.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.library.books.index') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <span class="flex-1 truncate">Search Books</span>
        </a>

        {{-- Circulation --}}
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Circulation</p>

        <a href="{{ route('admin.library.issue.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.library.issue.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h11m0 0l-4-4m4 4l-4 4M17 17H6m0 0l4 4m-4-4l4-4"/></svg>
            <span class="flex-1 truncate">Issue & Return</span>
        </a>

        <a href="{{ route('admin.library.patrons.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.library.patrons.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="flex-1 truncate">HR Patrons</span>
        </a>

        <a href="{{ route('admin.library.fines.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.library.fines.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="flex-1 truncate">Student Fines</span>
            @php
                $outstandingFineCount = \App\Models\LibraryLoan::whereRaw('GREATEST(fine_amount - fine_paid, 0) > 0')->count()
                    + \App\Models\LibraryLoan::where('status','issued')->whereDate('due_date','<',now()->toDateString())->count();
            @endphp
            @if($outstandingFineCount > 0)
                <span class="ml-auto rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-black text-white shrink-0">{{ $outstandingFineCount }}</span>
            @endif
        </a>

        {{-- My Books --}}
        @php
            $myActiveCount = \App\Models\LibraryLoan::where('user_id', auth()->id())->where('status', 'issued')->count();
            $myOverdueCount = \App\Models\LibraryLoan::where('user_id', auth()->id())->where('status', 'issued')->whereDate('due_date', '<', now()->toDateString())->count();
        @endphp
        <a href="{{ route('admin.library.my-books.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.library.my-books.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
            <span class="flex-1 truncate">My Books</span>
            @if($myActiveCount > 0)
                <span class="ml-auto rounded-full px-1.5 py-0.5 text-[10px] font-black text-white shrink-0 {{ $myOverdueCount > 0 ? 'bg-red-500' : 'bg-emerald-600' }}">{{ $myActiveCount }}</span>
            @endif
        </a>

        {{-- Reports --}}
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Reports</p>

        <a href="{{ route('admin.library.activity-logs.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.library.activity-logs.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3M5 3v5h5M19 21v-5h-5M5 8a8 8 0 0113.66-3.66M19 16A8 8 0 015.34 19.66"/></svg>
            <span class="flex-1 truncate">Activity Logs</span>
        </a>

        <a href="{{ route('admin.library.reports.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ $navActive('admin.library.reports.*', 'admin.library.statistics.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6M5 20h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z"/></svg>
            <span class="flex-1 truncate">Reports</span>
        </a>

        {{-- ERP link --}}
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">ERP</p>

        @if(auth()->user()?->canAccess(['dashboard.admin', 'dashboard.view', 'dashboard.financial']))
        <a href="{{ route('admin.dashboard') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all text-white/60 hover:text-white hover:bg-white/8">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
            <span class="flex-1 truncate">Main Dashboard</span>
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
