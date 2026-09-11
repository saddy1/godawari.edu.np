<!DOCTYPE html>
<html lang="{{str_replace('_','-',app()->getLocale())}}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="csrf-token" content="{{csrf_token()}}">
    <title>@yield('title','Teacher') | {{$siteSettings->localized('site_name','School')}}</title>
    @include('partials.pwa-meta', ['manifest' => 'teacher-manifest.webmanifest', 'appleIcon' => 'icons/teacher/apple-touch-icon.png', 'icon192' => 'icons/teacher/icon-any-192.png', 'themeColor' => '#0b2415', 'appTitle' => 'Teacher'])
    @php
        $fallbackCss = null;
        $viteManifestPath = public_path('build/manifest.json');
        if (app()->isLocal() && is_file(public_path('hot')) && is_file($viteManifestPath)) {
            $viteManifest = json_decode(file_get_contents($viteManifestPath), true);
            $fallbackCss = $viteManifest['resources/css/app.css']['file'] ?? null;
        }
    @endphp
    @if($fallbackCss)<link rel="stylesheet" href="{{url('/build/'.$fallbackCss)}}" data-vite-css-fallback>@endif
    @vite(['resources/css/app.css','resources/js/app.js'])
    @if(request()->routeIs('hajiri.*'))
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link rel="stylesheet" href="{{url('/erp/hajiri/admin/plugins/nepali-date-picker/nepali-date-picker.min.css')}}">
    @endif
    @php $primary=$siteSettings->get('primary_color','#1a5632');$dark=$siteSettings->get('dark_color','#0b2415'); @endphp
    <style>
        :root{--teacher-primary:{{$primary}};--teacher-dark:{{$dark}};--theme-primary:{{$primary}};--theme-dark:{{$dark}};--theme-secondary:#e2a024}
        @include('partials.pwa-styles')
        @media(max-width:1023px){html,body{overflow-x:hidden}.teacher-mobile-nav{display:grid!important;position:fixed!important;visibility:visible!important;opacity:1!important;left:0!important;right:0!important;bottom:0!important;z-index:9999!important;width:100%!important;min-height:4.25rem!important;background:rgba(255,255,255,.97)!important;transform:translateZ(0);pointer-events:auto!important}.teacher-mobile-sheet{display:block;visibility:visible}.teacher-mobile-sheet.js-hidden{display:none!important}}
        @media(min-width:1024px){.teacher-mobile-nav,.teacher-mobile-sheet,[data-mobile-sheet-backdrop]{display:none!important}}
    </style>
    @stack('styles')
</head>
<body class="min-h-dvh bg-slate-50 font-sans text-slate-900 antialiased">
@php
    $nav=[['Today','admin.teacher.workspace','M4 6h16M4 12h16M4 18h10'],['Exams','admin.examinations.index','M9 12h6m-6 4h12v16H6z']];
    $teacherNotices=$teacherNotices??collect();
    $initials=collect(preg_split('/\s+/',trim(auth()->user()->name)))->filter()->take(2)->map(fn($part)=>mb_strtoupper(mb_substr($part,0,1)))->implode('');
@endphp
<aside class="fixed inset-y-0 left-0 z-30 hidden w-60 flex-col text-white lg:flex" style="background:linear-gradient(180deg,var(--teacher-dark),#04100a)">
    <a href="{{route('admin.teacher.workspace')}}" class="flex h-[72px] items-center gap-3 border-b border-white/10 px-4"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white p-1 shadow-lg shadow-black/10"><img src="{{$siteSettings->logoUrl()}}" class="h-full w-full object-contain" alt="Logo"></span><div class="min-w-0"><b class="block truncate text-sm">Teacher</b><small class="block truncate text-[10px] font-semibold text-white/50">{{$siteSettings->localized('site_name','School')}}</small></div></a>
    <div class="border-b border-white/10 px-4 py-4"><p class="truncate text-sm font-black">{{auth()->user()->name}}</p><p class="mt-1 text-[9px] font-bold uppercase tracking-widest text-emerald-300">Teaching account</p></div>
    <nav class="flex-1 space-y-1 p-3"><p class="px-3 pb-1 text-[9px] font-black uppercase tracking-[.2em] text-white/30">My work</p>
        @foreach($nav as [$text,$route,$path])<a href="{{route($route)}}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-black {{request()->routeIs($route)||($route==='admin.examinations.index'&&request()->routeIs('admin.examinations.marks.*'))?'bg-white/15 text-white':'text-white/60 hover:bg-white/10 hover:text-white'}}"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{$path}}"/></svg>{{$text}}</a>@endforeach
        <a href="{{route('admin.teacher.workspace')}}#today-classes" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-black {{request()->routeIs('admin.teacher.attendance*')?'bg-white/15 text-white':'text-white/60 hover:bg-white/10 hover:text-white'}}"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4M5 4h5M5 9h2M5 14h2M5 19h14"/></svg>Attendance</a>
        @if(\App\Services\ModuleService::enabled('hajiri'))
        <p class="px-3 pb-1 pt-5 text-[9px] font-black uppercase tracking-[.2em] text-white/30">My services</p>
        <a href="{{route('hajiri.home')}}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-black {{request()->routeIs('hajiri.home','hajiri.calendar-yy-mm')?'bg-white/15 text-white':'text-white/60 hover:bg-white/10 hover:text-white'}}"><span class="grid w-5 place-items-center">◷</span>My Hajiri</a>
        @if(\App\Services\ModuleService::enabled('hajiri_leave'))<a href="{{route('hajiri.my-leaves')}}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-black {{request()->routeIs('hajiri.my-leaves')?'bg-white/15 text-white':'text-white/60 hover:bg-white/10 hover:text-white'}}"><span class="grid w-5 place-items-center">▤</span>My leaves</a>@endif
        <a href="{{route('hajiri.staff-card-request.index')}}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-black {{request()->routeIs('hajiri.staff-card-request.index')?'bg-white/15 text-white':'text-white/60 hover:bg-white/10 hover:text-white'}}"><span class="grid w-5 place-items-center">▣</span>Request ID card</a>
        @endif
        <p class="px-3 pb-1 pt-5 text-[9px] font-black uppercase tracking-[.2em] text-white/30">Account</p>
        <a href="{{route('account.profile.edit')}}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-black {{request()->routeIs('account.profile.*')?'bg-white/15 text-white':'text-white/60 hover:bg-white/10 hover:text-white'}}"><span class="grid w-5 place-items-center">●</span>My profile</a>
        <a href="{{route('account.password.edit')}}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-black {{request()->routeIs('account.password.*')?'bg-white/15 text-white':'text-white/60 hover:bg-white/10 hover:text-white'}}"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a4 4 0 11-7.5 2H3v4h3v3h3v-3h2.5A4 4 0 0115 7z"/></svg>Change password</a>
        @if(auth()->user()->isAdmin())<a href="{{route('admin.dashboard')}}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-black text-amber-300 hover:bg-white/10"><span class="grid w-5 place-items-center">⚙</span>Admin access<svg class="ml-auto h-3.5 w-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>@endif
    </nav>
    <form method="POST" action="{{route('logout')}}" class="border-t border-white/10 p-3">@csrf<button class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-black text-white/60 hover:bg-white/10 hover:text-white"><span aria-hidden="true">↪</span>Log out</button></form>
</aside>

<div class="min-h-dvh lg:ml-60">
    <header class="sticky top-0 z-30 text-white shadow-lg shadow-slate-900/10 safe-top" style="background:linear-gradient(100deg,var(--teacher-dark),#102b1c)">
        <div class="flex h-[72px] items-center gap-3 px-3 sm:px-5 lg:px-7">
            <a href="{{route('admin.teacher.workspace')}}" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white p-1 lg:hidden"><img src="{{$siteSettings->logoUrl()}}" class="h-full w-full object-contain" alt="Logo"></a>
            <div class="hidden min-w-0 shrink-0 lg:block"><p class="text-[9px] font-black uppercase tracking-[.2em] text-emerald-300">Teacher workspace</p><p class="truncate text-sm font-black">Good {{now()->hour<12?'morning':(now()->hour<17?'afternoon':'evening')}}, {{str(auth()->user()->name)->before(' ')}}</p></div>
            <div class="min-w-0 flex-1 lg:ml-5" data-notice-slider>
                @forelse($teacherNotices as $index=>$notice)
                    <a href="{{route('notices.show',$notice->slug)}}" data-notice-slide class="{{!$loop->first?'js-hidden ':''}}group flex min-w-0 items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2 transition hover:bg-white/15"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-amber-400 text-xs text-amber-950">●</span><span class="min-w-0"><span class="block text-[8px] font-black uppercase tracking-[.18em] text-amber-300">Notice {{$index+1}} of {{$teacherNotices->count()}}</span><span class="block truncate text-xs font-bold sm:text-sm">{{$notice->title}}</span></span><svg class="ml-auto h-4 w-4 shrink-0 text-white/40 transition group-hover:translate-x-0.5 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
                @empty
                    <div class="flex min-w-0 items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-emerald-400/20 text-xs text-emerald-300">✓</span><span class="min-w-0"><span class="block text-[8px] font-black uppercase tracking-[.18em] text-emerald-300">Notices</span><span class="block truncate text-xs font-bold sm:text-sm">You are all caught up</span></span></div>
                @endforelse
            </div>
            <button type="button" data-pwa-install title="Install app" class="js-hidden flex shrink-0 items-center gap-1.5 rounded-xl border border-white/10 bg-white/10 px-2.5 py-2 text-white transition hover:bg-white/15"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg><span class="hidden text-[11px] font-black sm:inline">Install</span></button>
            <div class="relative shrink-0" data-account-menu>
                <button type="button" data-account-toggle aria-expanded="false" class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/10 p-1.5 pr-2 text-left transition hover:bg-white/15" title="Account menu"><span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-300 text-xs font-black text-emerald-950">{{$initials?:'T'}}</span><span class="hidden max-w-28 min-w-0 sm:block"><b class="block truncate text-[11px]">{{auth()->user()->name}}</b><small class="block text-[8px] font-bold uppercase tracking-wider text-white/50">My account</small></span><svg class="hidden h-4 w-4 text-white/50 sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>
                <div data-account-panel class="js-hidden absolute right-0 mt-2 w-72 overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-2xl shadow-slate-900/20"><div class="border-b border-slate-100 px-4 py-3"><p class="truncate text-sm font-black">{{auth()->user()->name}}</p><p class="truncate text-xs text-slate-500">{{auth()->user()->email}}</p></div><div class="p-1.5"><a href="{{route('account.profile.edit')}}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-bold hover:bg-slate-50"><span class="grid h-8 w-8 place-items-center rounded-lg bg-blue-50 text-blue-700">●</span>My profile</a>@if(\App\Services\ModuleService::enabled('hajiri'))<a href="{{route('hajiri.home')}}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-bold hover:bg-slate-50"><span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-50 text-amber-700">◷</span>My Hajiri</a>@if(\App\Services\ModuleService::enabled('hajiri_leave'))<a href="{{route('hajiri.my-leaves')}}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-bold hover:bg-slate-50"><span class="grid h-8 w-8 place-items-center rounded-lg bg-purple-50 text-purple-700">▤</span>Apply for leave</a>@endif<a href="{{route('hajiri.staff-card-request.index')}}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-bold hover:bg-slate-50"><span class="grid h-8 w-8 place-items-center rounded-lg bg-cyan-50 text-cyan-700">▣</span>Request ID card</a>@endif<a href="{{route('account.password.edit')}}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-bold hover:bg-slate-50"><span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-50 text-emerald-700">⌁</span>Change password</a>@if(auth()->user()->isAdmin())<a href="{{route('admin.dashboard')}}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-bold hover:bg-slate-50"><span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-50 text-amber-700">⚙</span>Admin access</a>@endif</div><form method="POST" action="{{route('logout')}}">@csrf<button class="flex w-full items-center gap-3 border-t border-slate-100 px-4 py-3 text-left text-sm font-bold text-red-600 hover:bg-red-50"><span class="grid h-8 w-8 place-items-center rounded-lg bg-red-50">↪</span>Log out</button></form></div>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-6xl p-3 pb-28 sm:p-5 lg:p-7 lg:pb-8">@if(session('success')||session('status')||session('message'))<div class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-bold text-emerald-800">{{session('success')??session('status')??session('message')}}</div>@endif @if(session('error'))<div class="mb-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-bold text-red-700">{{session('error')}}</div>@endif @yield('content')</main>
</div>

<button type="button" data-mobile-sheet-backdrop aria-label="Close menu" class="js-hidden fixed inset-0 z-[9998] bg-slate-950/45 backdrop-blur-[1px] lg:hidden"></button>

<section data-mobile-sheet="attendance" aria-label="Attendance menu" class="teacher-mobile-sheet js-hidden fixed inset-x-2 z-[10000] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl lg:hidden" style="bottom:calc(4.25rem + env(safe-area-inset-bottom))">
    <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3"><div><p class="text-[9px] font-black uppercase tracking-[.18em] text-blue-600">Attendance</p><h2 class="text-base font-black text-slate-900">Attendance services</h2></div><button type="button" data-mobile-sheet-close class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-lg font-black text-slate-500" aria-label="Close attendance menu">×</button></header>
    <div class="grid grid-cols-2 gap-2 p-3">
        <a href="{{route('admin.teacher.workspace')}}#today-classes" class="rounded-xl bg-blue-50 p-3 text-blue-800"><span class="grid h-8 w-8 place-items-center rounded-lg bg-blue-100 text-base">✓</span><b class="mt-2 block text-xs">Take attendance</b><small class="mt-0.5 block text-[9px] font-semibold text-blue-600">Open today’s assigned class</small></a>
        @if(\App\Services\ModuleService::enabled('hajiri'))
            <a href="{{route('hajiri.home')}}" class="rounded-xl bg-amber-50 p-3 text-amber-800"><span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-100 text-base">◷</span><b class="mt-2 block text-xs">My Hajiri</b><small class="mt-0.5 block text-[9px] font-semibold text-amber-600">View attendance record</small></a>
            @if(\App\Services\ModuleService::enabled('hajiri_leave'))<a href="{{route('hajiri.my-leaves')}}" class="rounded-xl bg-purple-50 p-3 text-purple-800"><span class="grid h-8 w-8 place-items-center rounded-lg bg-purple-100 text-base">▤</span><b class="mt-2 block text-xs">Apply for leave</b><small class="mt-0.5 block text-[9px] font-semibold text-purple-600">Apply and track requests</small></a>@endif
        @endif
    </div>
</section>

<section data-mobile-sheet="profile" aria-label="Profile menu" class="teacher-mobile-sheet js-hidden fixed inset-x-2 z-[10000] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl lg:hidden" style="bottom:calc(4.25rem + env(safe-area-inset-bottom))">
    <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3"><div class="flex min-w-0 items-center gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-100 text-xs font-black text-emerald-800">{{$initials?:'T'}}</span><div class="min-w-0"><h2 class="truncate text-sm font-black text-slate-900">{{auth()->user()->name}}</h2><p class="truncate text-[10px] font-semibold text-slate-400">Teacher account</p></div></div><button type="button" data-mobile-sheet-close class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-lg font-black text-slate-500" aria-label="Close profile menu">×</button></header>
    <div class="grid grid-cols-2 gap-2 p-3">
        <a href="{{route('account.profile.edit')}}" class="rounded-xl bg-blue-50 p-3 text-blue-800"><span class="grid h-8 w-8 place-items-center rounded-lg bg-blue-100">●</span><b class="mt-2 block text-xs">My profile</b><small class="mt-0.5 block text-[9px] font-semibold text-blue-600">View and update details</small></a>
        <a href="{{route('account.password.edit')}}" class="rounded-xl bg-emerald-50 p-3 text-emerald-800"><span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-100">⌁</span><b class="mt-2 block text-xs">Change password</b><small class="mt-0.5 block text-[9px] font-semibold text-emerald-600">Secure your account</small></a>
        @if(\App\Services\ModuleService::enabled('hajiri'))<a href="{{route('hajiri.staff-card-request.index')}}" class="rounded-xl bg-cyan-50 p-3 text-cyan-800"><span class="grid h-8 w-8 place-items-center rounded-lg bg-cyan-100">▣</span><b class="mt-2 block text-xs">Request ID card</b><small class="mt-0.5 block text-[9px] font-semibold text-cyan-600">Apply and track status</small></a>@endif
        @if(auth()->user()->isAdmin())<a href="{{route('admin.dashboard')}}" target="_blank" rel="noopener" class="rounded-xl bg-amber-50 p-3 text-amber-800"><span class="grid h-8 w-8 place-items-center rounded-lg bg-amber-100">⚙</span><b class="mt-2 block text-xs">Admin access</b><small class="mt-0.5 block text-[9px] font-semibold text-amber-600">Opens in a new tab</small></a>@endif
        <form method="POST" action="{{route('logout')}}" class="contents">@csrf<button class="rounded-xl bg-red-50 p-3 text-left text-red-700"><span class="grid h-8 w-8 place-items-center rounded-lg bg-red-100">↪</span><b class="mt-2 block text-xs">Log out</b><small class="mt-0.5 block text-[9px] font-semibold text-red-500">Sign out safely</small></button></form>
    </div>
</section>

<nav class="teacher-mobile-nav fixed inset-x-0 bottom-0 z-40 grid grid-cols-4 border-t border-slate-200 bg-white/95 safe-bottom shadow-[0_-8px_30px_rgba(15,23,42,.08)] backdrop-blur lg:hidden" aria-label="Teacher app navigation">
    <a href="{{route('admin.teacher.workspace')}}" class="flex flex-col items-center gap-0.5 py-1.5 text-[9px] font-black {{request()->routeIs('admin.teacher.workspace')?'text-emerald-700':'text-slate-400'}}"><span class="grid h-8 w-10 place-items-center rounded-xl text-lg {{request()->routeIs('admin.teacher.workspace')?'bg-emerald-50':''}}">⌂</span>Home</a>
    <a href="{{route('admin.examinations.index')}}" class="flex flex-col items-center gap-0.5 py-1.5 text-[9px] font-black {{request()->routeIs('admin.examinations.*')?'text-purple-700':'text-slate-400'}}"><span class="grid h-8 w-10 place-items-center rounded-xl text-lg {{request()->routeIs('admin.examinations.*')?'bg-purple-50':''}}">▣</span>Exams</a>
    <button type="button" data-mobile-sheet-toggle="attendance" aria-expanded="false" class="flex flex-col items-center gap-0.5 py-1.5 text-[9px] font-black {{request()->routeIs('admin.teacher.attendance*')||request()->routeIs('hajiri.home','hajiri.calendar-yy-mm','hajiri.my-leaves')?'text-blue-700':'text-slate-400'}}"><span class="grid h-8 w-10 place-items-center rounded-xl text-lg {{request()->routeIs('admin.teacher.attendance*')||request()->routeIs('hajiri.home','hajiri.calendar-yy-mm','hajiri.my-leaves')?'bg-blue-50':''}}">✓</span>Attendance</button>
    <button type="button" data-mobile-sheet-toggle="profile" aria-expanded="false" class="flex flex-col items-center gap-0.5 py-1.5 text-[9px] font-black {{request()->routeIs('account.*')||request()->routeIs('hajiri.staff-card-request.index')?'text-emerald-700':'text-slate-400'}}"><span class="grid h-8 w-10 place-items-center rounded-xl text-lg {{request()->routeIs('account.*')||request()->routeIs('hajiri.staff-card-request.index')?'bg-emerald-50':''}}">●</span>Profile</button>
</nav>
@stack('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
    const slider=document.querySelector('[data-notice-slider]');
    if(slider){const slides=[...slider.querySelectorAll('[data-notice-slide]')];let active=0;if(slides.length>1)setInterval(function(){slides[active].classList.add('js-hidden');active=(active+1)%slides.length;slides[active].classList.remove('js-hidden')},5500)}

    const menu=document.querySelector('[data-account-menu]'),toggle=menu?.querySelector('[data-account-toggle]'),panel=menu?.querySelector('[data-account-panel]');
    const sheets=[...document.querySelectorAll('[data-mobile-sheet]')],sheetToggles=[...document.querySelectorAll('[data-mobile-sheet-toggle]')],backdrop=document.querySelector('[data-mobile-sheet-backdrop]');
    const closeDesktopMenu=()=>{panel?.classList.add('js-hidden');toggle?.setAttribute('aria-expanded','false')};
    const closeSheets=()=>{sheets.forEach(sheet=>sheet.classList.add('js-hidden'));sheetToggles.forEach(button=>button.setAttribute('aria-expanded','false'));backdrop?.classList.add('js-hidden');document.body.style.overflow=''};
    const openSheet=name=>{closeSheets();const sheet=sheets.find(item=>item.dataset.mobileSheet===name),button=sheetToggles.find(item=>item.dataset.mobileSheetToggle===name);if(!sheet)return;sheet.classList.remove('js-hidden');button?.setAttribute('aria-expanded','true');backdrop?.classList.remove('js-hidden');document.body.style.overflow='hidden'};

    sheetToggles.forEach(button=>button.addEventListener('click',()=>button.getAttribute('aria-expanded')==='true'?closeSheets():openSheet(button.dataset.mobileSheetToggle)));
    document.querySelectorAll('[data-mobile-sheet-close]').forEach(button=>button.addEventListener('click',closeSheets));
    backdrop?.addEventListener('click',closeSheets);
    toggle?.addEventListener('click',function(event){event.stopPropagation();if(window.matchMedia('(max-width:1023px)').matches){openSheet('profile');return}const opening=panel.classList.contains('js-hidden');closeDesktopMenu();if(opening){panel.classList.remove('js-hidden');toggle.setAttribute('aria-expanded','true')}});
    document.addEventListener('click',function(event){if(menu&&!menu.contains(event.target))closeDesktopMenu()});
    document.addEventListener('keydown',event=>{if(event.key==='Escape'){closeDesktopMenu();closeSheets()}});
});
@if(request()->routeIs('hajiri.*'))
jQuery.loadScript=function(url,callback){jQuery.ajax({url:url,dataType:'script',success:callback,async:true})};jQuery(function(){jQuery.loadScript(@json(asset('/erp/hajiri/admin/plugins/nepali-date-picker/nepali-date-picker.min.js')),function(){jQuery('.date-picker').nepaliDatePicker()})});
@endif
</script>

@include('partials.pwa-install-script', ['sw' => 'teacher-sw.js', 'storageKey' => 'teacher-ios-install-dismissed', 'appLabel' => 'this app', 'bannerBottom' => '4.75rem'])
</body></html>
