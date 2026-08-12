<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Library') | {{ $siteSettings->localized('site_name', 'School') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $siteSettings->faviconUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @php
        $primary      = $siteSettings->get('primary_color', '#1a5632');
        $primaryLight = $siteSettings->get('primary_light_color', '#237042');
        $secondary    = $siteSettings->get('secondary_color', '#e2a024');
        $dark         = $siteSettings->get('dark_color', '#0b2415');
        $sidebarEnd   = $siteSettings->get('sidebar_gradient_end', '#050f09');
    @endphp
    <style>
        :root {
            --theme-primary: {{ $primary }};
            --theme-primary-light: {{ $primaryLight }};
            --theme-secondary: {{ $secondary }};
            --theme-dark: {{ $dark }};
            --theme-sidebar-bg: {{ $dark }};
            --theme-sidebar-gradient-end: {{ $sidebarEnd }};
        }
        .bg-\[\#1a5632\] { background-color: var(--theme-primary) !important; }
        .bg-\[\#0b2415\] { background-color: var(--theme-dark) !important; }
        .text-\[\#1a5632\] { color: var(--theme-primary) !important; }
        .border-\[\#1a5632\] { border-color: var(--theme-primary) !important; }
        main { min-width: 0; }
        ::selection { background-color: var(--theme-primary); color: #fff; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50" x-data="{ sidebarOpen: false }">
    <div class="flex h-dvh overflow-hidden">
        @include('library-admin.partials.sidebar')

        <div class="relative flex flex-col flex-1 min-w-0 overflow-y-auto overflow-x-hidden" data-page-scroll-root>
            @include('backend.partials.module-header')

            <div class="px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
                @if(session('success'))
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
                @endif
                @if(session('info'))
                    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700">{{ session('info') }}</div>
                @endif
                @if($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
                @endif
            </div>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('library-content')
            </main>
        </div>
    </div>

    @include('partials.page-wheel-scroll')
    @stack('scripts')
</body>
</html>
