<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $siteSettings->get('app_name', config('app.name', 'School ERP')) }} - Hajiri</title>

    {{-- Tailwind via Vite (same as all other modules) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- jQuery (needed for Select2, NepaliDatePicker, DataTables) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- DataTables --}}
    <link href="//cdn.datatables.net/1.11.2/css/jquery.dataTables.min.css" rel="stylesheet">

    {{-- NepaliDatePicker --}}
    <link rel="stylesheet" href="{{ url('/erp/hajiri/admin/plugins/nepali-date-picker/nepali-date-picker.min.css') }}">

    @php
        $hajiriPrimary     = $siteSettings->get('primary_color',        '#1a5632');
        $hajiriSecondary   = $siteSettings->get('secondary_color',      '#e2a024');
        $hajiriDark        = $siteSettings->get('dark_color',           '#0b2415');
        $hajiriPrimaryLight= $siteSettings->get('primary_light_color',  '#237042');
        $hajiriSidebarEnd  = $siteSettings->get('sidebar_gradient_end', '#050f09');
        $hajiriNoticeAccent= $siteSettings->get('notice_accent_color',  '') ?: $hajiriSecondary;
    @endphp
    <style>
        :root {
            --theme-primary:              {{ $hajiriPrimary }};
            --theme-primary-light:        {{ $hajiriPrimaryLight }};
            --theme-secondary:            {{ $hajiriSecondary }};
            --theme-dark:                 {{ $hajiriDark }};
            --theme-sidebar-bg:           {{ $hajiriDark }};
            --theme-sidebar-gradient-end: {{ $hajiriSidebarEnd }};
            --theme-notice-accent:        {{ $hajiriNoticeAccent }};
        }
        .theme-bg-primary     { background-color: var(--theme-primary)   !important; }
        .theme-bg-secondary   { background-color: var(--theme-secondary) !important; }
        .theme-text-primary   { color: var(--theme-primary)              !important; }
        .theme-text-secondary { color: var(--theme-secondary)            !important; }
        .bg-\[\#1a5632\]      { background-color: var(--theme-primary)   !important; }
        .bg-\[\#e2a024\]      { background-color: var(--theme-secondary) !important; }
        .bg-\[\#0b2415\]      { background-color: var(--theme-dark)      !important; }
        .text-\[\#1a5632\]    { color: var(--theme-primary)              !important; }
        .text-\[\#e2a024\]    { color: var(--theme-secondary)            !important; }

        main { min-width: 0; }
        main .overflow-x-auto, .dataTables_wrapper { -webkit-overflow-scrolling: touch; }

        @media (max-width: 768px) {
            main table { min-width: 640px; }
            main .overflow-hidden:has(> table), .dataTables_wrapper { overflow-x: auto; }
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                float: none; text-align: left; margin: 0 0 .75rem 0;
            }
        }

        ::selection      { background-color: var(--theme-primary); color: #fff; }
        ::-moz-selection { background-color: var(--theme-primary); color: #fff; }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50"
      x-data="{ sidebarOpen: false }">

    <div class="flex h-dvh overflow-hidden">

        {{-- Hajiri Sidebar --}}
        @include('hajiri.partials.sidebar')

        {{-- Main wrapper --}}
        <div class="relative flex flex-col flex-1 min-w-0 overflow-y-auto overflow-x-hidden" data-page-scroll-root>

            {{-- Shared ERP module-switcher header --}}
            @include('backend.partials.module-header')

            {{-- Flash messages --}}
            <div class="px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
                @if(session('message'))
                    <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm font-medium">
                        <svg class="w-4 h-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('message') }}
                    </div>
                @endif
                @if(isset($errors) && $errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li class="font-medium">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Page content --}}
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>

            <footer class="shrink-0 px-6 py-3 border-t border-gray-100 bg-white">
                <p class="text-xs text-gray-400 text-center">
                    &copy; {{ date('Y') }} {{ $siteSettings->localized('site_name', config('app.name')) }} — Hajiri ERP
                </p>
            </footer>
        </div>
    </div>

    {{-- jQuery.loadScript helper --}}
    <script>
        jQuery.loadScript = function(url, callback) {
            jQuery.ajax({ url: url, dataType: 'script', success: callback, async: true });
        };

        $(function() {
            // Nepali Date Picker
            $.loadScript("{{ asset('/erp/hajiri/admin/plugins/nepali-date-picker/nepali-date-picker.min.js') }}", function() {
                $('.date-picker').nepaliDatePicker();
            });

            // Device sync is intentionally not auto-started here.
            // On shared hosting, recursive background sync can exhaust PHP/Apache workers.
        });
    </script>

    @include('partials.page-wheel-scroll')
    @stack('scripts')
</body>
</html>
