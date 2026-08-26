<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Salary / Pay Slip') | {{ $siteSettings->localized('site_name', 'School') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $siteSettings->faviconUrl() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @php
        $salaryPrimary = $siteSettings->get('primary_color', '#1a5632');
        $salarySecondary = $siteSettings->get('secondary_color', '#e2a024');
        $salaryDark = $siteSettings->get('dark_color', '#0b2415');
        $salarySidebarEnd = $siteSettings->get('sidebar_gradient_end', '#050f09');
    @endphp
    <style>
        :root {
            --theme-primary: {{ $salaryPrimary }};
            --theme-secondary: {{ $salarySecondary }};
            --theme-dark: {{ $salaryDark }};
            --theme-sidebar-bg: {{ $salaryDark }};
            --theme-sidebar-gradient-end: {{ $salarySidebarEnd }};
        }
        .bg-\[\#1a5632\] { background-color: var(--theme-primary) !important; }
        .bg-\[\#0b2415\] { background-color: var(--theme-dark) !important; }
        .text-\[\#1a5632\] { color: var(--theme-primary) !important; }
        .border-\[\#1a5632\] { border-color: var(--theme-primary) !important; }
        main { min-width: 0; }
        ::selection { background-color: var(--theme-primary); color: #fff; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans text-gray-900 antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex h-dvh overflow-hidden">
        @include('billing.partials.sidebar')
        <div class="relative flex min-w-0 flex-1 flex-col overflow-y-auto overflow-x-hidden" data-page-scroll-root>
            @include('backend.partials.module-header')
            <main class="grow p-4 sm:p-6 lg:p-8">@yield('content')</main>
            <footer class="shrink-0 border-t border-gray-100 bg-white px-6 py-3">
                <p class="text-center text-xs text-gray-400">&copy; {{ date('Y') }} {{ $siteSettings->localized('site_name', 'School') }} — Salary / Pay Slip</p>
            </footer>
        </div>
    </div>
    @include('partials.page-wheel-scroll')
    @stack('modals')
    @stack('scripts')
</body>
</html>
