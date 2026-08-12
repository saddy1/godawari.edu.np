{{-- resources/views/layouts/admin.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Dashboard') | {{ $siteSettings->localized('site_name', 'School') }}</title>

    {{-- Dynamic Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ $siteSettings->faviconUrl() }}">
    <link rel="shortcut icon" href="{{ $siteSettings->faviconUrl() }}">
    <link rel="apple-touch-icon" href="{{ $siteSettings->faviconUrl() }}">

    {{-- Fonts & Tailwind --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    {{-- Alpine.js for Sidebar & Dropdowns --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @php
        $adminThemePrimary      = $siteSettings->get('primary_color',        '#1a5632');
        $adminThemeSecondary    = $siteSettings->get('secondary_color',      '#e2a024');
        $adminThemeDark         = $siteSettings->get('dark_color',           '#0b2415');
        $adminThemePrimaryLight = $siteSettings->get('primary_light_color',  '#237042');
        $adminNoticeBg          = $siteSettings->get('notice_bg_color',      '') ?: $adminThemeDark;
        $adminNoticeAccent      = $siteSettings->get('notice_accent_color',  '') ?: $adminThemeSecondary;
        $adminSidebarEnd        = $siteSettings->get('sidebar_gradient_end', '#050f09');
        $adminHeaderGradEnd     = $siteSettings->get('header_gradient_end',  '#0f3d22');
    @endphp
    <style>
        :root {
            --theme-primary:              {{ $adminThemePrimary }};
            --theme-primary-light:        {{ $adminThemePrimaryLight }};
            --theme-secondary:            {{ $adminThemeSecondary }};
            --theme-secondary-light:      #f4b63e;
            --theme-dark:                 {{ $adminThemeDark }};
            --theme-sidebar-bg:           {{ $adminThemeDark }};
            --theme-sidebar-gradient-end: {{ $adminSidebarEnd }};
            --theme-notice-bg:            {{ $adminNoticeBg }};
            --theme-notice-accent:        {{ $adminNoticeAccent }};
            --theme-header-gradient-end:  {{ $adminHeaderGradEnd }};
        }

        /* Semantic helpers */
        .theme-bg-primary       { background-color: var(--theme-primary)   !important; }
        .theme-bg-secondary     { background-color: var(--theme-secondary) !important; }
        .theme-bg-dark          { background-color: var(--theme-dark)      !important; }
        .theme-text-primary     { color: var(--theme-primary)              !important; }
        .theme-text-secondary   { color: var(--theme-secondary)            !important; }
        .theme-border-primary   { border-color: var(--theme-primary)       !important; }
        .theme-border-secondary { border-color: var(--theme-secondary)     !important; }
        .theme-gradient-primary { background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-primary-light) 100%) !important; }
        .theme-gradient-sidebar { background: linear-gradient(180deg, var(--theme-sidebar-bg) 0%, var(--theme-sidebar-gradient-end) 100%) !important; }

        /* Hardcoded colour overrides */
        .bg-\[\#1a5632\]               { background-color: var(--theme-primary)   !important; }
        .bg-\[\#0b2415\]               { background-color: var(--theme-dark)      !important; }
        .bg-\[\#e2a024\]               { background-color: var(--theme-secondary) !important; }
        .text-\[\#1a5632\]             { color: var(--theme-primary)              !important; }
        .text-\[\#e2a024\]             { color: var(--theme-secondary)            !important; }
        .text-\[\#0b2415\]             { color: var(--theme-dark)                 !important; }
        .border-\[\#1a5632\]           { border-color: var(--theme-primary)       !important; }
        .border-\[\#e2a024\]           { border-color: var(--theme-secondary)     !important; }
        .hover\:bg-\[\#1a5632\]:hover  { background-color: var(--theme-primary)   !important; }
        .hover\:text-\[\#1a5632\]:hover{ color: var(--theme-primary)              !important; }
        .hover\:text-\[\#e2a024\]:hover{ color: var(--theme-secondary)            !important; }

        /* Main content area */
        main { min-width: 0; }
        main .overflow-x-auto { -webkit-overflow-scrolling: touch; }

        /* Mobile table responsiveness */
        @media (max-width: 768px) {
            main table { min-width: 640px; }
            main .overflow-hidden:has(> table) {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* iOS safe-area bottom */
        @supports (padding: env(safe-area-inset-bottom)) {
            .sidebar-footer { padding-bottom: max(1rem, env(safe-area-inset-bottom)); }
        }

        /* Selection */
        ::selection      { background-color: var(--theme-primary); color: #fff; }
        ::-moz-selection { background-color: var(--theme-primary); color: #fff; }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50">
    
    {{-- Alpine state for mobile sidebar toggle --}}
    <div x-data="{ sidebarOpen: false }" class="flex h-dvh overflow-hidden">
        
        {{-- Sidebar Component --}}
        @include('backend.partials.sidebar')

        {{-- Main Content Wrapper --}}
        <div class="relative flex flex-col flex-1 min-w-0 overflow-y-auto overflow-x-hidden" data-page-scroll-root>
            
            {{-- Header Component --}}
            @include('backend.partials.header')

            {{-- Notice Ticker --}}
            @include('backend.partials.notice-ticker')

            {{-- Main Content Area --}}
            <main class="w-full grow p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
            
        </div>
    </div>

    @include('partials.page-wheel-scroll')
    <x-media-manager />
    @stack('modals')
    @stack('scripts')
</body>
</html>
