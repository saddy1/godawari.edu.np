<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Teaching & Learning') | {{ $siteSettings->localized('site_name', 'School') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $siteSettings->faviconUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @php
        $hrPrimary      = $siteSettings->get('primary_color', '#1a5632');
        $hrSecondary    = $siteSettings->get('secondary_color', '#e2a024');
        $hrDark         = $siteSettings->get('dark_color', '#0b2415');
        $hrPrimaryLight = $siteSettings->get('primary_light_color', '#237042');
        $hrSidebarEnd   = $siteSettings->get('sidebar_gradient_end', '#050f09');
    @endphp
    <style>
        :root {
            --theme-primary: {{ $hrPrimary }};
            --theme-primary-light: {{ $hrPrimaryLight }};
            --theme-secondary: {{ $hrSecondary }};
            --theme-dark: {{ $hrDark }};
            --theme-sidebar-bg: {{ $hrDark }};
            --theme-sidebar-gradient-end: {{ $hrSidebarEnd }};
        }
        .bg-\[\#1a5632\] { background-color: var(--theme-primary) !important; }
        .text-\[\#1a5632\] { color: var(--theme-primary) !important; }
        .border-\[\#1a5632\] { border-color: var(--theme-primary) !important; }
        main { min-width: 0; }
    </style>
    @stack('styles')
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50" x-data="{ sidebarOpen: false }">
    <div class="flex h-dvh overflow-hidden">
        @include('teaching_learning.partials.sidebar')

        <div class="relative flex flex-col flex-1 min-w-0 overflow-y-auto overflow-x-hidden" data-page-scroll-root>
            @include('backend.partials.module-header')

            <div class="px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
                @if(session('success'))
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
                @endif
            </div>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>

            <footer class="shrink-0 px-6 py-3 border-t border-gray-100 bg-white">
                <p class="text-xs text-gray-400 text-center">&copy; {{ date('Y') }} {{ $siteSettings->localized('site_name', config('app.name')) }} — Teaching &amp; Learning</p>
            </footer>
        </div>
    </div>

    @include('partials.page-wheel-scroll')
    @stack('scripts')
</body>
</html>
