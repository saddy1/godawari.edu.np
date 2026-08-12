<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Student Portal') - {{ $siteSettings->localized('site_name', 'School') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ $siteSettings->faviconUrl() }}">
    <link rel="shortcut icon" href="{{ $siteSettings->faviconUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
        $spPrimary      = $siteSettings->get('primary_color', '#1a5632');
        $spPrimaryLight = $siteSettings->get('primary_light_color', '#237042');
        $spAccent       = $siteSettings->get('secondary_color', '#e2a024');
        $spDark         = $siteSettings->get('dark_color', '#0b2415');
        $portalStudent  = session('student_id') ? \App\Models\Card\Student::find(session('student_id')) : null;
        $navItems = [
            ['label' => 'Dashboard',       'route' => 'student.dashboard',       'active' => 'student.dashboard',       'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['label' => 'My Attendance',   'route' => 'student.attendance',      'active' => 'student.attendance',      'icon' => 'M9 12l2 2 4-4m5-4v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h3.5a2.5 2.5 0 015 0H18a2 2 0 012 2z'],
            ['label' => 'ID Card',         'route' => 'student.card-status',     'active' => 'student.card-status',     'icon' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0'],
            ['label' => 'Update Profile',  'route' => 'student.request-update',  'active' => 'student.request-update',  'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            ['label' => 'Change Password', 'route' => 'student.change-password', 'active' => 'student.change-password', 'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
        ];
    @endphp
    <style>
        :root {
            --sp-primary: {{ $spPrimary }};
            --sp-primary-light: {{ $spPrimaryLight }};
            --sp-accent: {{ $spAccent }};
            --sp-dark: {{ $spDark }};
            --theme-primary: {{ $spPrimary }};
            --theme-secondary: {{ $spAccent }};
            --theme-dark: {{ $spDark }};
        }
        .bg-primary { background-color: var(--sp-primary) !important; }
        .text-primary { color: var(--sp-primary) !important; }
        .hover\:bg-primary-light:hover { background-color: var(--sp-primary-light) !important; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-100 text-gray-900 antialiased">
<div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
    <aside class="hidden lg:flex lg:flex-col bg-[#0b2415] text-white">
        <div class="flex h-16 items-center gap-3 border-b border-white/10 px-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white p-1.5">
                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="h-full w-full object-contain">
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-extrabold">{{ $siteSettings->localized('site_name', 'School') }}</p>
                <p class="text-[10px] font-bold uppercase tracking-widest text-[#e2a024]">Student Portal</p>
            </div>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-4">
            @foreach($navItems as $item)
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold transition {{ request()->routeIs($item['active']) ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/8 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 overflow-hidden rounded-full border border-white/15 bg-white/10">
                    @if($portalStudent?->photo)
                        <img src="{{ $portalStudent->photo_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-sm font-extrabold">{{ strtoupper(substr($portalStudent?->first_name ?? 'S', 0, 1)) }}</div>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-extrabold">{{ $portalStudent?->full_name ?? 'Student' }}</p>
                    <p class="truncate text-[10px] font-semibold text-white/40">{{ $portalStudent?->roll_number ?? 'Portal user' }}</p>
                </div>
                <form method="POST" action="{{ route('student.logout') }}">
                    @csrf
                    <button class="rounded-lg p-2 text-white/40 hover:bg-white/10 hover:text-white" title="Logout">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Mobile drawer --}}
    <div x-data="{ drawerOpen: false }" class="min-w-0">
        {{-- Drawer backdrop --}}
        <div x-show="drawerOpen" x-cloak @click="drawerOpen = false"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>

        {{-- Slide-in drawer --}}
        <div x-show="drawerOpen" x-cloak
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-[#0b2415] text-white lg:hidden shadow-2xl">

            {{-- Drawer header --}}
            <div class="flex h-16 items-center justify-between border-b border-white/10 px-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white p-1.5">
                        <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="h-full w-full object-contain">
                    </div>
                    <div>
                        <p class="text-xs font-extrabold leading-none">{{ $siteSettings->localized('site_name', 'School') }}</p>
                        <p class="mt-0.5 text-[10px] font-bold uppercase tracking-widest text-[#e2a024]">Student Portal</p>
                    </div>
                </div>
                <button @click="drawerOpen = false" class="rounded-lg p-1.5 text-white/40 hover:bg-white/10 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Drawer nav --}}
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}" @click="drawerOpen = false"
                       class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold transition {{ request()->routeIs($item['active']) ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/8 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            {{-- Drawer footer --}}
            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 overflow-hidden rounded-full border border-white/15 bg-white/10 shrink-0">
                        @if($portalStudent?->photo)
                            <img src="{{ $portalStudent->photo_url }}" alt="" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-sm font-extrabold">{{ strtoupper(substr($portalStudent?->first_name ?? 'S', 0, 1)) }}</div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-extrabold">{{ $portalStudent?->full_name ?? 'Student' }}</p>
                        <p class="truncate text-[10px] font-semibold text-white/40">{{ $portalStudent?->roll_number ?? '' }}</p>
                    </div>
                    <form method="POST" action="{{ route('student.logout') }}">
                        @csrf
                        <button class="rounded-lg p-2 text-white/40 hover:bg-white/10 hover:text-white" title="Logout">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <header class="sticky top-0 z-20 border-b border-gray-200 bg-white/95 backdrop-blur">
            <div class="flex h-14 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                {{-- Hamburger (mobile only) --}}
                <button @click="drawerOpen = true" class="rounded-xl p-2 text-gray-500 hover:bg-gray-100 lg:hidden">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="min-w-0 flex-1 lg:flex-none">
                    <p class="truncate text-sm font-extrabold text-gray-950">@yield('title', 'Student Portal')</p>
                    <p class="truncate text-xs font-semibold text-gray-400 lg:hidden">{{ $portalStudent?->stream ?? '' }}{{ $portalStudent?->section ? ' · Section '.$portalStudent->section : '' }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ url('/') }}" class="hidden rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-extrabold text-gray-600 hover:bg-gray-50 sm:inline-flex">Website</a>
                    <form method="POST" action="{{ route('student.logout') }}">
                        @csrf
                        <button class="rounded-xl bg-[#0b2415] px-3 py-2 text-xs font-extrabold text-white hover:bg-[#1a5632]">Logout</button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Bottom tab bar (mobile only) --}}
        @php
            $bottomItems = array_slice($navItems, 0, 4);
            $hasMore     = count($navItems) > 4;
        @endphp
        <nav class="fixed bottom-0 inset-x-0 z-30 flex border-t border-gray-200 bg-white lg:hidden"
             style="padding-bottom: env(safe-area-inset-bottom)">
            @foreach($bottomItems as $item)
                <a href="{{ route($item['route']) }}"
                   class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 text-[10px] font-extrabold transition-colors
                          {{ request()->routeIs($item['active']) ? 'text-[#1a5632]' : 'text-gray-400' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs($item['active']) ? '2.5' : '1.8' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            @if($hasMore)
                <button @click="drawerOpen = true"
                        class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 text-[10px] font-extrabold text-gray-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    <span>More</span>
                </button>
            @endif
        </nav>

        <main class="px-4 py-6 pb-24 sm:px-6 lg:px-8 lg:pb-8">
            @foreach(['success' => ['bg-green-50','border-green-200','text-green-800'], 'info' => ['bg-blue-50','border-blue-200','text-blue-800'], 'error' => ['bg-red-50','border-red-200','text-red-800']] as $type => $cls)
                @if(session($type))
                    <div class="mb-5 rounded-xl border {{ $cls[0] }} {{ $cls[1] }} px-4 py-3 text-sm font-semibold {{ $cls[2] }}">{{ session($type) }}</div>
                @endif
            @endforeach

            @if($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
