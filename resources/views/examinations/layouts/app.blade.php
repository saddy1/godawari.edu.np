<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Examinations') | {{ $siteSettings->localized('site_name', 'School') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $siteSettings->faviconUrl() }}">@vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @php $examPrimary=$siteSettings->get('primary_color','#1a5632');$examSecondary=$siteSettings->get('secondary_color','#e2a024');$examDark=$siteSettings->get('dark_color','#0b2415'); @endphp
    <style>:root{--theme-primary:{{$examPrimary}};--theme-secondary:{{$examSecondary}};--theme-dark:{{$examDark}};--theme-sidebar-bg:{{$examDark}};--theme-sidebar-gradient-end:#050f09}.bg-\[\#1a5632\]{background-color:var(--theme-primary)!important}.text-\[\#1a5632\]{color:var(--theme-primary)!important}main{min-width:0}[x-cloak]{display:none!important}</style>
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans text-gray-900 antialiased" x-data="{sidebarOpen:false}">
<div class="flex h-dvh overflow-hidden">
    @include('examinations.partials.sidebar')
    <div class="relative flex min-w-0 flex-1 flex-col overflow-y-auto overflow-x-hidden" data-page-scroll-root>
        @include('backend.partials.module-header')
        <div class="space-y-2 px-4 pt-4 sm:px-6 lg:px-8">@if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{session('success')}}</div>@endif @if(session('error'))<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{session('error')}}</div>@endif</div>
        <main class="flex-1 p-4 sm:p-6 lg:p-8">@yield('content')</main>
        <footer class="shrink-0 border-t border-gray-100 bg-white px-6 py-3 text-center text-xs text-gray-400">© {{date('Y')}} {{ $siteSettings->localized('site_name', config('app.name')) }} — Examinations</footer>
    </div>
</div>
@include('partials.page-wheel-scroll') @stack('scripts')
</body></html>
