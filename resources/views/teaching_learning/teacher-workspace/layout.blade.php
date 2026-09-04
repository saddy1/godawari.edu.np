<!DOCTYPE html>
<html lang="{{str_replace('_','-',app()->getLocale())}}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="csrf-token" content="{{csrf_token()}}">
    <title>@yield('title', 'Teacher Workspace') | {{$siteSettings->localized('site_name','School')}}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @php $primary=$siteSettings->get('primary_color','#1a5632');$dark=$siteSettings->get('dark_color','#0b2415'); @endphp
    <style>:root{--teacher-primary:{{$primary}};--teacher-dark:{{$dark}}}[x-cloak]{display:none!important}.safe-bottom{padding-bottom:max(1rem,env(safe-area-inset-bottom))}</style>
</head>
<body class="min-h-dvh bg-slate-50 font-sans text-slate-900 antialiased">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4">
            <a href="{{route('admin.teacher.workspace')}}" class="flex items-center gap-2.5"><span class="grid h-9 w-9 place-items-center rounded-xl text-sm font-black text-white" style="background:var(--teacher-dark)">TW</span><span><b class="block text-sm leading-none">Teacher Workspace</b><small class="text-[10px] font-bold text-slate-400">{{$siteSettings->localized('site_name','School')}}</small></span></a>
            <div class="flex items-center gap-2"><span class="hidden text-right sm:block"><b class="block text-xs">{{auth()->user()->name}}</b><small class="text-[10px] text-slate-400">Teacher</small></span><form method="POST" action="{{route('logout')}}">@csrf<button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">Log out</button></form></div>
        </div>
    </header>
    <main class="mx-auto max-w-6xl p-3 pb-24 sm:p-6">@if(session('success'))<div class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-bold text-emerald-800">{{session('success')}}</div>@endif @yield('content')</main>
    @stack('scripts')
</body></html>
