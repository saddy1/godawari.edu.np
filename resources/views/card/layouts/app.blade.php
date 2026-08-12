<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Student Management') - {{ $siteSettings->localized('site_name', 'Godawari College') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '{{ $siteSettings->get('primary_color') }}', light: '{{ $siteSettings->get('primary_color') }}', dark: '{{ $siteSettings->get('dark_color') }}' },
                        accent:  { DEFAULT: '{{ $siteSettings->get('secondary_color') }}', light: '{{ $siteSettings->get('secondary_color') }}' },
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        :root {
            --theme-primary:              {{ $siteSettings->get('primary_color',        '#1a5632') }};
            --theme-primary-light:        {{ $siteSettings->get('primary_light_color',  '#237042') }};
            --theme-secondary:            {{ $siteSettings->get('secondary_color',      '#e2a024') }};
            --theme-dark:                 {{ $siteSettings->get('dark_color',           '#0b2415') }};
            --theme-sidebar-bg:           {{ $siteSettings->get('dark_color',           '#0b2415') }};
            --theme-sidebar-gradient-end: {{ $siteSettings->get('sidebar_gradient_end', '#050f09') }};
            --theme-notice-accent:        {{ $siteSettings->get('notice_accent_color',  '') ?: $siteSettings->get('secondary_color', '#e2a024') }};
        }
        .sidebar-link.active { background: rgba(255,255,255,0.15); color: #fff; }
        .erp-card-sidebar { background: linear-gradient(180deg, var(--theme-sidebar-bg) 0%, var(--theme-sidebar-gradient-end) 100%); border-color: rgba(255,255,255,0.08); }
        .erp-card-sidebar-header { background: rgba(0,0,0,0.25); border-color: rgba(255,255,255,0.08); }
        .erp-card-section { color: rgba(255,255,255,0.30); }
        .erp-card-muted { color: rgba(255,255,255,0.60); }
        .erp-card-sidebar .sidebar-link:hover { background: rgba(255,255,255,0.08); color: #ffffff; }
        .theme-bg-primary   { background-color: var(--theme-primary)   !important; }
        .theme-bg-secondary { background-color: var(--theme-secondary) !important; }
        .theme-text-primary { color: var(--theme-primary)              !important; }
        .bg-\[\#1a5632\]    { background-color: var(--theme-primary)   !important; }
        .bg-\[\#e2a024\]    { background-color: var(--theme-secondary) !important; }
        .text-\[\#1a5632\]  { color: var(--theme-primary)              !important; }
        .text-\[\#e2a024\]  { color: var(--theme-secondary)            !important; }
        main { min-width: 0; }
        main .overflow-x-auto { -webkit-overflow-scrolling: touch; }
        @media (max-width: 768px) {
            main table { min-width: 640px; }
            main .overflow-hidden:has(> table) { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        }
        ::selection      { background-color: var(--theme-primary); color: #fff; }
        ::-moz-selection { background-color: var(--theme-primary); color: #fff; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-800">

<div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">

    {{-- Mobile Sidebar Backdrop --}}
    <div x-show="sidebarOpen"
         x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-gray-900/70 backdrop-blur-sm lg:hidden"
         style="display: none;"></div>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           @click.capture="if ($event.target.closest('a')) sidebarOpen = false"
           class="fixed inset-y-0 left-0 z-50 w-60 erp-card-sidebar text-white transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto flex flex-col h-dvh border-r shrink-0">

        {{-- Brand --}}
        <div class="flex items-center justify-between h-14 px-4 border-b shrink-0 erp-card-sidebar-header">
            <a href="{{ route('students.index') }}" class="flex items-center gap-2.5 min-w-0">
                <div class="w-7 h-7 bg-white/90 rounded-lg flex items-center justify-center p-1 shrink-0">
                    <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-white leading-none truncate">{{ $siteSettings->get('app_name', 'Godawari College ERP') }}</p>
                    <p class="text-[9px] uppercase tracking-widest font-semibold mt-0.5" style="color: var(--theme-secondary, #e2a024);">Student Module</p>
                </div>
            </a>
            <button type="button" @click="sidebarOpen = false" class="lg:hidden p-1 text-white/40 hover:text-white rounded-md hover:bg-white/10 transition-colors shrink-0" aria-label="Close sidebar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
            @php
                $canMembers = auth()->user()->canAccess('students.view');
                $canCreateMembers = auth()->user()->canAccess('students.create');
                $canImport = auth()->user()->canAccess('users.bulk-import');
                $canPromote = auth()->user()->canAccess('students.edit');
                $canCards = auth()->user()->canAccess(['cards.view', 'cards.print']);
                $canRequests = auth()->user()->canAccess('students.card-request');
                $canCardSettings = auth()->user()->canAccess('card-settings.view');
                $canCertView = auth()->user()->canAccess('hr.certificates.view');
                $canCertCreate = auth()->user()->canAccess('hr.certificates.create');
            @endphp

            {{-- Members --}}
            @if($canMembers || $canCreateMembers || $canImport || $canPromote)
            <p class="erp-card-section px-2 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-widest">Members</p>
            @endif
            @if($canMembers)
            <a href="{{ route('students.index') }}"
               @click="sidebarOpen = false"
               class="sidebar-link group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('students.*') ? 'active' : 'erp-card-muted' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                <span class="flex-1 truncate">All Members</span>
            </a>
            @endif
            @if($canCreateMembers)
            <a href="{{ \App\Services\ModuleService::enabled('hr') && auth()->user()->canAccess('hr.members.create') ? route('admin.hr.members.create') : route('students.create') }}"
               @click="sidebarOpen = false"
               class="sidebar-link group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all erp-card-muted">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span class="flex-1 truncate">Add from HR</span>
            </a>
            @endif
            @if($canImport)
            <a href="{{ route('admin.hr.members.import') }}"
               @click="sidebarOpen = false"
               class="sidebar-link group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all erp-card-muted">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span class="flex-1 truncate">Import</span>
            </a>
            @endif

            {{-- Cards --}}
            @if($canCards)
            <p class="erp-card-section px-2 pt-4 pb-1.5 text-[10px] font-bold uppercase tracking-widest">Cards</p>
            <a href="{{ route('bulk.index', ['type' => 'student']) }}"
               @click="sidebarOpen = false"
               class="sidebar-link group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('bulk.*') && request('type') === 'student' ? 'active' : 'erp-card-muted' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zm-9 14a5 5 0 0110 0M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                <span class="flex-1 truncate">Student ID Card Print</span>
            </a>
            <a href="{{ route('bulk.index', ['type' => 'staff_teacher']) }}"
               @click="sidebarOpen = false"
               class="sidebar-link group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('bulk.*') && in_array(request('type'), ['staff_teacher', 'staff', 'teacher'], true) ? 'active' : 'erp-card-muted' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1m0 6H7m10 0v-2a5 5 0 00-10 0v2m10 0H7m0 0H2v-2a4 4 0 014-4h1m9-7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="flex-1 truncate">Staff / Teacher ID Print</span>
            </a>
            @endif

            {{-- Certificates --}}
            @if($canCertView || $canCertCreate)
            <p class="erp-card-section px-2 pt-4 pb-1.5 text-[10px] font-bold uppercase tracking-widest">Certificates</p>
            @if($canCertView)
            <a href="{{ route('certificates.index') }}"
               @click="sidebarOpen = false"
               class="sidebar-link group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('certificates.index') ? 'active' : 'erp-card-muted' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="flex-1 truncate">All Certificates</span>
            </a>
            @endif
            @if($canCertCreate)
            <a href="{{ route('certificates.create') }}"
               @click="sidebarOpen = false"
               class="sidebar-link group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('certificates.create') ? 'active' : 'erp-card-muted' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="flex-1 truncate">Issue Certificate</span>
            </a>
            @endif
            @endif

            {{-- Student Requests --}}
            @if($canRequests)
            <p class="erp-card-section px-2 pt-4 pb-1.5 text-[10px] font-bold uppercase tracking-widest">Student Requests</p>
            <a href="{{ route('admin.card-requests') }}"
               @click="sidebarOpen = false"
               class="sidebar-link group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.card-requests*') ? 'active' : 'erp-card-muted' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                <span class="flex-1 truncate">Card Requests</span>
                @php
                    $pendingCardQ = \App\Models\Card\CardRequest::where('status','pending');
                    if (!auth()->user()->isSuperAdmin()) {
                        $pendingCardQ->whereHas('student', fn($q) => auth()->user()->applyStudentScope($q));
                    }
                    $pendingCards = $pendingCardQ->count();
                @endphp
                @if($pendingCards > 0)
                    <span class="shrink-0 min-w-4 h-4 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $pendingCards }}</span>
                @endif
            </a>
            <a href="{{ route('admin.update-requests') }}"
               @click="sidebarOpen = false"
               class="sidebar-link group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('admin.update-requests*') ? 'active' : 'erp-card-muted' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span class="flex-1 truncate">Update Requests</span>
                @php
                    $pendingUpdQ = \App\Models\Card\UpdateRequest::where('status','pending');
                    if (!auth()->user()->isSuperAdmin()) {
                        $pendingUpdQ->whereHas('student', fn($q) => auth()->user()->applyStudentScope($q));
                    }
                    $pendingUpdates = $pendingUpdQ->count();
                @endphp
                @if($pendingUpdates > 0)
                    <span class="shrink-0 min-w-4 h-4 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $pendingUpdates }}</span>
                @endif
            </a>
            @endif

            {{-- Super Admin only --}}
            @if($canCardSettings)
                <p class="erp-card-section px-2 pt-4 pb-1.5 text-[10px] font-bold uppercase tracking-widest">Administration</p>
                <a href="{{ route('settings.index') }}"
                   @click="sidebarOpen = false"
                   class="sidebar-link group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('settings.*') ? 'active' : 'erp-card-muted' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="flex-1 truncate">Settings</span>
                </a>
            @endif

            {{-- Student Portal --}}
            <p class="erp-card-section px-2 pt-4 pb-1.5 text-[10px] font-bold uppercase tracking-widest">Portal</p>
            <a href="{{ route('student.login') }}" target="_blank"
               @click="sidebarOpen = false"
               class="sidebar-link group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all erp-card-muted">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="flex-1 truncate">Student Portal ↗</span>
            </a>
        </nav>

        {{-- User Footer --}}
        <div class="px-3 py-3 border-t shrink-0 sidebar-footer" style="border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.3);">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-white text-xs font-bold border-2"
                     style="background-color: var(--theme-primary, #1a5632); border-color: var(--theme-secondary, #e2a024);">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-white truncate leading-tight">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="text-[10px] text-white/35 truncate leading-tight mt-0.5">{{ auth()->user()->role_label ?? 'Admin' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="p-1.5 text-white/30 hover:text-white hover:bg-white/10 rounded-md transition-colors" title="Logout">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="flex-1 min-w-0 flex flex-col overflow-hidden">
        @include('backend.partials.module-header')

        {{-- Topbar --}}
        <header class="bg-white border-b border-[#dbe7df] px-4 sm:px-8 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 shadow-sm">
            <h1 class="text-lg font-bold text-primary">@yield('heading', 'Dashboard')</h1>
            <div class="flex items-center gap-3 sm:gap-4 text-sm text-gray-500">
                <span>{{ now()->format('D, d M Y') }}</span>
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-lg">
                    {{ auth()->user()->role_label }}
                </span>
            </div>
        </header>

        {{-- Flash Messages --}}
        <div class="px-4 sm:px-8 pt-4 space-y-2">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('info'))
                <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-lg px-4 py-3 text-sm">
                    {{ session('info') }}
                </div>
            @endif
            @if(isset($errors) && $errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Optional sub-header (sticky toolbar injected by individual pages) --}}
        @yield('sub-header')

        {{-- Page Content --}}
        <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-gray-50" data-page-scroll-root>
            @yield('content')
        </main>

    </div>
</div>

@include('partials.page-wheel-scroll')
@stack('scripts')
</body>
</html>
