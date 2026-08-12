{{-- resources/views/partials/navbar.blade.php --}}
@php
    $headerUser = auth()->user();
    $isStudentPortalSession = session()->has('student_id') || ($headerUser?->isStudent() ?? false);
    $vacancyModuleEnabled = \App\Services\ModuleService::enabled('vacancy');
@endphp

<header x-data="{
    scrolled: false,
    mobileMenuOpen: false,
    academicsOpen: false,
    aboutOpen: false,
    vacanciesOpen: false,
    portalsOpen: false,
    accountOpen: false
}" @scroll.window="scrolled = (window.pageYOffset > 20)"
    :class="scrolled ? 'shadow-md' : 'shadow-sm'"
    class="fixed top-0 left-0 right-0 z-60 bg-white transition-shadow duration-300 flex flex-col w-full">

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- MAIN TOP BAR --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="max-w-350 w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14 sm:h-20">

            {{-- Logo & School Name --}}
            <a href="{{ url('/') }}" class="flex items-center gap-2 sm:gap-3 group min-w-0">
                <div class="w-10 h-10 sm:w-16 sm:h-16 bg-[#f7f7f9] rounded-xl flex items-center justify-center shrink-0">
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->localized('site_name', __('site.school_name')) }} Logo"
                        class="w-8 h-8 sm:w-15 sm:h-15 object-contain group-hover:scale-110 transition-transform duration-300">
                </div>
                <div class="flex flex-col justify-center min-w-0">
                    <span class="font-bold text-[15px] sm:text-lg leading-tight whitespace-normal break-words" style="color: var(--theme-primary);">{{ $siteSettings->localized('site_name', __('site.school_name')) }}</span>
                    <span class="hidden sm:block text-[11px] text-gray-500 font-medium">{{ $siteSettings->localized('site_tagline', __('site.tagline')) }}</span>
                </div>
            </a>

            {{-- Desktop Nav --}}
            <div class="hidden lg:flex items-center gap-5 shrink-0">

                @if(($headerMenuItems ?? collect())->isNotEmpty())
                    @include('partials.cms-menu-desktop', ['items' => $headerMenuItems])
                @else
                <a href="{{ url('/') }}"
                    class="text-[14px] transition-all duration-200 {{ request()->is('/') ? 'text-[#1a5632] font-bold underline' : 'text-gray-700 font-medium hover:text-[#1a5632] hover:font-bold' }}">{{ __('site.nav.home') }}</a>

                {{-- Academics Dropdown --}}
                <div class="relative" @mouseenter="academicsOpen = true" @mouseleave="academicsOpen = false">
                    <button class="flex items-center gap-1 text-[14px] transition-all duration-200 outline-none {{ request()->is('academics*') ? 'text-[#1a5632] font-bold underline' : 'text-gray-700 font-medium hover:text-[#1a5632] hover:font-bold' }}">
                        {{ __('site.nav.academics') }}
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="academicsOpen"
                        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute left-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-100"
                        style="display:none;">
                        <div class="p-2">
                            <a href="{{ route('academics.elementary') }}" class="block px-4 py-2.5 rounded-lg hover:bg-green-50 transition-colors group">
                                <div class="text-gray-900 text-sm font-semibold group-hover:text-[#1a5632]">{{ __('site.nav.kids_school') }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ __('site.nav.kids_sub') }}</div>
                            </a>
                            <a href="{{ route('academics.primary') }}" class="block px-4 py-2.5 rounded-lg hover:bg-green-50 transition-colors group">
                                <div class="text-gray-900 text-sm font-semibold group-hover:text-[#1a5632]">{{ __('site.nav.middle_school') }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ __('site.nav.middle_sub') }}</div>
                            </a>
                            <a href="{{ route('academics.secondary') }}" class="block px-4 py-2.5 rounded-lg hover:bg-green-50 transition-colors group">
                                <div class="text-gray-900 text-sm font-semibold group-hover:text-[#1a5632]">{{ __('site.nav.high_school') }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ __('site.nav.high_sub') }}</div>
                            </a>
                        </div>
                    </div>
                </div>

                <a href="{{ url('/admissions') }}"
                    class="text-[14px] transition-all duration-200 {{ request()->is('admissions') ? 'text-[#1a5632] font-bold underline' : 'text-gray-700 font-medium hover:text-[#1a5632] hover:font-bold' }}">{{ __('site.nav.admissions') }}</a>

                <a href="{{ url('/news') }}"
                    class="text-[14px] transition-all duration-200 {{ request()->is('news') ? 'text-[#1a5632] font-bold underline' : 'text-gray-700 font-medium hover:text-[#1a5632] hover:font-bold' }}">{{ __('site.nav.news') }}</a>

                <a href="{{ url('/gallery') }}"
                    class="text-[14px] transition-all duration-200 {{ request()->is('gallery') ? 'text-[#1a5632] font-bold underline' : 'text-gray-700 font-medium hover:text-[#1a5632] hover:font-bold' }}">{{ __('site.nav.gallery') }}</a>

                {{-- Vacancies Dropdown --}}
                @if($vacancyModuleEnabled)
                <div class="relative" @mouseenter="vacanciesOpen = true" @mouseleave="vacanciesOpen = false">
                    <button class="flex items-center gap-1 text-[14px] transition-all duration-200 outline-none {{ request()->is('vacancies*') ? 'text-[#1a5632] font-bold underline' : 'text-gray-700 font-medium hover:text-[#1a5632] hover:font-bold' }}">
                        {{ __('site.nav.vacancies') }}
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="vacanciesOpen"
                        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute left-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-100"
                        style="display:none;">
                        <div class="p-2">
                            <a href="{{ route('vacancies') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-green-50 transition-colors group">
                                <svg class="w-4 h-4 text-[#1a5632] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 group-hover:text-[#1a5632]">{{ __('site.nav.open_vacancies') }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ __('site.nav.browse_jobs') }}</div>
                                </div>
                            </a>
                            @guest
                            <div class="mx-2 my-1 border-t border-gray-100"></div>
                            <a href="{{ route('applicant.login') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-amber-50 transition-colors group">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 group-hover:text-amber-700">{{ __('site.nav.applicant_login') }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ __('site.nav.track_application') }}</div>
                                </div>
                            </a>
                            @endguest
                        </div>
                    </div>
                </div>
                @endif

                {{-- About Dropdown --}}
                <div class="relative" @mouseenter="aboutOpen = true" @mouseleave="aboutOpen = false">
                    <button class="flex items-center gap-1 text-[14px] transition-all duration-200 outline-none {{ request()->is('about') || request()->is('contact') || request()->is('faculty') ? 'text-[#1a5632] font-bold underline' : 'text-gray-700 font-medium hover:text-[#1a5632] hover:font-bold' }}">
                        {{ __('site.nav.about') }}
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="aboutOpen"
                        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute left-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-100"
                        style="display:none;">
                        <div class="p-2">
                            <a href="{{ url('/about') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-green-50 transition-colors group">
                                <svg class="w-4 h-4 text-[#1a5632] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div class="text-sm font-semibold text-gray-900 group-hover:text-[#1a5632]">{{ __('site.nav.about_us') }}</div>
                            </a>
                            <a href="{{ route('frontend.faculty') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-green-50 transition-colors group">
                                <svg class="w-4 h-4 text-[#1a5632] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                <div class="text-sm font-semibold text-gray-900 group-hover:text-[#1a5632]">{{ __('site.nav.faculty') }}</div>
                            </a>
                            <a href="{{ url('/contact') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-green-50 transition-colors group">
                                <svg class="w-4 h-4 text-[#1a5632] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <div class="text-sm font-semibold text-gray-900 group-hover:text-[#1a5632]">{{ __('site.nav.contact') }}</div>
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                <div class="flex items-center gap-1 rounded-full bg-gray-100 p-1 text-xs font-bold" aria-label="{{ __('site.language.switch') }}">
                    <a href="{{ route('language.switch', 'en') }}"
                       class="rounded-full px-2.5 py-1 transition-colors {{ app()->getLocale() === 'en' ? 'theme-bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-[#1a5632]' }}">
                        EN
                    </a>
                    <a href="{{ route('language.switch', 'ne') }}"
                       class="rounded-full px-2.5 py-1 transition-colors {{ app()->getLocale() === 'ne' ? 'theme-bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-[#1a5632]' }}">
                        ने
                    </a>
                </div>

                {{-- Portals / User area --}}
                @auth
                    <div class="relative" @click.outside="accountOpen = false">
                        <button type="button" @click="accountOpen = !accountOpen"
                            class="ml-1 inline-flex items-center gap-2 rounded-full border border-gray-200 bg-gray-50 py-1.5 pl-1.5 pr-3 text-sm font-bold text-gray-800 hover:border-[#1a5632]/30 hover:bg-green-50 transition-colors">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full theme-bg-primary text-xs font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </span>
                            <span class="max-w-27.5 truncate">{{ auth()->user()->name }}</span>
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="accountOpen" x-transition
                            class="absolute right-0 mt-3 w-60 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl z-100"
                            style="display: none;">
                            <div class="border-b border-gray-100 px-4 py-3">
                                <p class="truncate text-sm font-bold text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="p-2">
                                @if($headerUser?->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-[#1a5632]">{{ __('site.nav.admin_dashboard') }}</a>
                                    <a href="{{ route('hajiri.home') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-[#1a5632]">{{ __('site.nav.hajiri_module') }}</a>
                                @elseif($isStudentPortalSession)
                                    <a href="{{ route('student.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-[#1a5632]">{{ __('site.nav.student_portal') }}</a>
                                    <a href="{{ route('student.learning') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-[#1a5632]">E-Learning</a>
                                    <a href="{{ route('student.card-status') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-[#1a5632]">ID Card Request</a>
                                @elseif($headerUser?->device_id)
                                    <a href="{{ route('hajiri.home') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-[#1a5632]">{{ __('site.nav.my_dashboard') }}</a>
                                    <a href="{{ route('hajiri.my-leaves') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-[#1a5632]">{{ __('site.nav.my_leaves') }}</a>
                                @else
                                    @if($vacancyModuleEnabled)
                                        <a href="{{ route('account.applications.index') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-[#1a5632]">{{ __('site.nav.my_applications') }}</a>
                                        <a href="{{ route('vacancies') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-[#1a5632]">{{ __('site.nav.open_vacancies') }}</a>
                                    @endif
                                @endif
                                @unless($isStudentPortalSession)
                                    <a href="{{ route('account.password.edit') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-green-50 hover:text-[#1a5632]">{{ __('site.nav.change_password') }}</a>
                                @endunless
                                <div class="my-1 border-t border-gray-100 mx-2"></div>
                                <form method="POST" action="{{ $isStudentPortalSession ? route('student.logout') : (($headerUser?->isAdmin() || $headerUser?->device_id) ? route('logout') : route('applicant.logout')) }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-red-600 hover:bg-red-50">{{ __('site.nav.log_out') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Portals Dropdown (shown when not logged in) --}}
                    <div class="relative ml-1" @mouseenter="portalsOpen = true" @mouseleave="portalsOpen = false">
                        <button class="inline-flex items-center gap-1.5 px-4 py-2 text-[13px] font-bold theme-bg-primary text-white rounded-full hover:bg-[#0b2415] transition-colors shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ __('site.nav.portals') }}
                            <svg class="w-3 h-3 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="portalsOpen"
                            x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-100"
                            style="display:none;">
                            <div class="p-2">
                                <a href="{{ route('login') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-green-50 transition-colors group">
                                    <div class="w-8 h-8 bg-[#1a5632] rounded-lg flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900 group-hover:text-[#1a5632]">{{ __('site.nav.staff_portal') }}</div>
                                        <div class="text-xs text-gray-400">{{ __('site.nav.staff_portal_sub') }}</div>
                                    </div>
                                </a>
                                <a href="/student/card/login" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-50 transition-colors group">
                                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900 group-hover:text-blue-700">{{ __('site.nav.student_portal') }}</div>
                                        <div class="text-xs text-gray-400">{{ __('site.nav.student_portal_sub') }}</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                @endauth
            </div>

            {{-- Mobile Hamburger --}}
            <button @click="mobileMenuOpen = true"
                class="lg:hidden p-2 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none shrink-0"
                aria-label="Open menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- NOTICE TICKER STRIP (dynamic gradient)                      --}}
    {{-- ══════════════════════════════════════════════════════════ --}}
    <div class="border-t border-white/10 w-full" style="height:30px; background: var(--theme-notice-gradient, var(--theme-notice-bg, #1a5632));">
        <div class="flex items-stretch h-full max-w-350 mx-auto w-full">
            <div class="shrink-0 flex items-center gap-2 px-3 sm:px-4 font-bold text-xs uppercase tracking-widest select-none" style="background-color: var(--theme-notice-accent, #e2a024); color: var(--theme-dark, #0b2415);">
                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span class="hidden sm:inline">{{ __('site.notice') }}</span>
            </div>

            <div class="flex-1 overflow-hidden relative py-1.5" style="min-width:0;">
                <div class="absolute left-0 top-0 bottom-0 w-6 sm:w-8 z-10 pointer-events-none" style="background: linear-gradient(to right, var(--theme-notice-bg, #1a5632), transparent);"></div>
                <div class="absolute right-0 top-0 bottom-0 w-6 sm:w-8 z-10 pointer-events-none" style="background: linear-gradient(to left, var(--theme-header-gradient-end, #0f3d22), transparent);"></div>
                <div class="ticker-wrapper flex items-center whitespace-nowrap">
                    <div class="ticker-track flex items-center gap-0">
                        @foreach ($notices ?? [] as $notice)
                            <a href="{{ route('news.show', $notice->slug) }}" class="inline-flex items-center gap-2 sm:gap-3 px-4 sm:px-6 group cursor-pointer min-h-0">
                                <span class="w-1.5 h-1.5 rounded-full shrink-0 opacity-70" style="background-color: var(--theme-notice-accent, #e2a024);"></span>
                                <span class="text-white text-xs font-medium group-hover:opacity-80 transition-opacity duration-200">{{ $notice->title }}</span>
                                <span class="hidden sm:inline text-[10px] font-semibold text-white/50 bg-white/10 px-2 py-0.5 rounded-full shrink-0 transition-colors duration-200">{{ $notice->created_at->diffForHumans() }}</span>
                            </a>
                        @endforeach
                    </div>
                    <div class="ticker-track flex items-center gap-0" aria-hidden="true">
                        @foreach ($notices ?? [] as $notice)
                            <a href="{{ route('news.show', $notice->slug) }}" class="inline-flex items-center gap-2 sm:gap-3 px-4 sm:px-6 group cursor-pointer min-h-0">
                                <span class="w-1.5 h-1.5 rounded-full shrink-0 opacity-70" style="background-color: var(--theme-notice-accent, #e2a024);"></span>
                                <span class="text-white text-xs font-medium group-hover:opacity-80 transition-opacity duration-200">{{ $notice->title }}</span>
                                <span class="hidden sm:inline text-[10px] font-semibold text-white/50 bg-white/10 px-2 py-0.5 rounded-full shrink-0 transition-colors duration-200">{{ $notice->created_at->diffForHumans() }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <a href="/news" class="shrink-0 flex items-center gap-1 sm:gap-1.5 px-3 sm:px-4 text-xs font-bold hover:bg-white/10 transition-colors border-l border-white/10 select-none whitespace-nowrap min-h-0" style="color: var(--theme-notice-accent, #e2a024);">
                <span class="hidden sm:inline">{{ __('site.view_all') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    {{-- MOBILE DRAWER MENU                                         --}}
    {{-- ══════════════════════════════════════════════════════════ --}}

    <div x-show="mobileMenuOpen"
        x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @click="mobileMenuOpen = false"
        class="fixed inset-0 bg-black/60 z-100 lg:hidden" style="display: none;"></div>

    <div x-show="mobileMenuOpen"
        x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-110 flex h-screen max-h-[100dvh] w-[85%] max-w-sm flex-col overflow-hidden bg-white shadow-2xl lg:hidden"
        style="display: none; height: 100dvh;">

        {{-- Drawer Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 bg-gray-50 shrink-0">
            <span class="font-bold text-lg text-[#0b2415]">{{ __('site.menu') }}</span>
            <button @click="mobileMenuOpen = false" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Drawer Links --}}
        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-4 pb-6 space-y-1" style="-webkit-overflow-scrolling: touch;">
            @if(($headerMenuItems ?? collect())->isNotEmpty())
                @include('partials.cms-menu-mobile', ['items' => $headerMenuItems])
            @else
            <a href="{{ url('/') }}"
                class="block px-4 py-3 rounded-xl transition-all {{ request()->is('/') ? 'text-[#1a5632] bg-green-50 font-bold' : 'text-gray-700 font-medium hover:bg-gray-50' }} text-base">{{ __('site.nav.home') }}</a>

            {{-- Academics Accordion --}}
            <div x-data="{ open: {{ request()->is('academics*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-gray-50 text-base">
                    <span :class="open ? 'text-[#1a5632] font-bold' : ''">{{ __('site.nav.academics') }}</span>
                    <svg :class="open ? 'rotate-180 text-[#1a5632]' : 'text-gray-400'" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="pl-4 pr-2 py-1 mb-1 space-y-1 border-l-2 border-green-100 ml-4">
                    <a href="{{ route('academics.elementary') }}" class="block px-4 py-2 rounded-lg text-sm text-gray-600 hover:text-[#1a5632] hover:bg-green-50 transition-colors">{{ __('site.nav.kids_school') }}</a>
                    <a href="{{ route('academics.primary') }}" class="block px-4 py-2 rounded-lg text-sm text-gray-600 hover:text-[#1a5632] hover:bg-green-50 transition-colors">{{ __('site.nav.middle_school') }}</a>
                    <a href="{{ route('academics.secondary') }}" class="block px-4 py-2 rounded-lg text-sm text-gray-600 hover:text-[#1a5632] hover:bg-green-50 transition-colors">{{ __('site.nav.high_school') }}</a>
                </div>
            </div>

            <a href="{{ url('/admissions') }}"
                class="block px-4 py-3 rounded-xl transition-all {{ request()->is('admissions') ? 'text-[#1a5632] bg-green-50 font-bold' : 'text-gray-700 font-medium hover:bg-gray-50' }} text-base">{{ __('site.nav.admissions') }}</a>
            <a href="{{ url('/news') }}"
                class="block px-4 py-3 rounded-xl transition-all {{ request()->is('news') ? 'text-[#1a5632] bg-green-50 font-bold' : 'text-gray-700 font-medium hover:bg-gray-50' }} text-base">{{ __('site.nav.news_events') }}</a>
            <a href="{{ url('/gallery') }}"
                class="block px-4 py-3 rounded-xl transition-all {{ request()->is('gallery') ? 'text-[#1a5632] bg-green-50 font-bold' : 'text-gray-700 font-medium hover:bg-gray-50' }} text-base">{{ __('site.nav.gallery') }}</a>

            {{-- Vacancies Accordion --}}
            @if($vacancyModuleEnabled)
            <div x-data="{ open: {{ request()->is('vacancies*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-gray-50 text-base">
                    <span :class="open ? 'text-[#1a5632] font-bold' : ''">{{ __('site.nav.vacancies') }}</span>
                    <svg :class="open ? 'rotate-180 text-[#1a5632]' : 'text-gray-400'" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="pl-4 pr-2 py-1 mb-1 space-y-1 border-l-2 border-green-100 ml-4">
                    <a href="{{ route('vacancies') }}" class="block px-4 py-2 rounded-lg text-sm text-gray-600 hover:text-[#1a5632] hover:bg-green-50 transition-colors">{{ __('site.nav.open_vacancies') }}</a>
                    @guest
                    <a href="{{ route('applicant.login') }}" class="block px-4 py-2 rounded-lg text-sm text-amber-700 hover:bg-amber-50 font-medium transition-colors">{{ __('site.nav.applicant_login') }}</a>
                    @endguest
                </div>
            </div>
            @endif

            {{-- About Accordion --}}
            <div x-data="{ open: {{ request()->is('about') || request()->is('contact') || request()->is('faculty') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-gray-50 text-base">
                    <span :class="open ? 'text-[#1a5632] font-bold' : ''">{{ __('site.nav.about') }}</span>
                    <svg :class="open ? 'rotate-180 text-[#1a5632]' : 'text-gray-400'" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition class="pl-4 pr-2 py-1 mb-1 space-y-1 border-l-2 border-green-100 ml-4">
                    <a href="{{ url('/about') }}" class="block px-4 py-2 rounded-lg text-sm text-gray-600 hover:text-[#1a5632] hover:bg-green-50 transition-colors">{{ __('site.nav.about_us') }}</a>
                    <a href="{{ route('frontend.faculty') }}" class="block px-4 py-2 rounded-lg text-sm text-gray-600 hover:text-[#1a5632] hover:bg-green-50 transition-colors">{{ __('site.nav.faculty') }}</a>
                    <a href="{{ url('/contact') }}" class="block px-4 py-2 rounded-lg text-sm text-gray-600 hover:text-[#1a5632] hover:bg-green-50 transition-colors">{{ __('site.nav.contact') }}</a>
                </div>
            </div>
            @endif

            {{-- Portals section (mobile) --}}
            <div class="pt-2 mt-2 border-t border-gray-100">
                <p class="px-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ __('site.language.switch') }}</p>
                <div class="mx-4 grid grid-cols-2 gap-2">
                    <a href="{{ route('language.switch', 'en') }}"
                       class="rounded-xl py-2.5 text-center text-sm font-bold transition-colors {{ app()->getLocale() === 'en' ? 'bg-[#1a5632] text-white' : 'bg-gray-100 text-gray-700' }}">
                        English
                    </a>
                    <a href="{{ route('language.switch', 'ne') }}"
                       class="rounded-xl py-2.5 text-center text-sm font-bold transition-colors {{ app()->getLocale() === 'ne' ? 'bg-[#1a5632] text-white' : 'bg-gray-100 text-gray-700' }}">
                        नेपाली
                    </a>
                </div>
            </div>

            @guest
                <div class="pt-3 mt-3 border-t border-gray-100">
                    <p class="px-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ __('site.nav.portals') }}</p>
                    <div class="mx-4 grid gap-2">
                        <a href="{{ route('login') }}" class="flex items-center justify-center rounded-xl bg-[#1a5632] px-4 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415] transition-colors">
                            {{ __('site.nav.staff_portal') }}
                        </a>
                        <a href="/student/card/login" class="flex items-center justify-center rounded-xl bg-blue-50 px-4 py-3 text-sm font-extrabold text-blue-700 hover:bg-blue-100 transition-colors">
                            {{ __('site.nav.student_portal') }}
                        </a>
                        @if($vacancyModuleEnabled)
                            <a href="{{ route('applicant.login') }}" class="flex items-center justify-center rounded-xl bg-amber-50 px-4 py-3 text-sm font-extrabold text-amber-700 hover:bg-amber-100 transition-colors">
                                {{ __('site.nav.applicant_login') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endguest

        </div>

        {{-- Drawer Footer (auth state) --}}
        <div class="px-4 py-3 bg-white border-t border-gray-100 shrink-0">
            @auth
                <div class="mb-2 flex items-center gap-2 rounded-xl bg-gray-50 px-3 py-2">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#1a5632] text-xs font-bold text-white">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-gray-900 leading-tight">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-gray-400 leading-tight">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @if($headerUser?->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-xl bg-[#1a5632] py-2 text-center text-xs font-bold text-white hover:bg-[#0b2415] transition-colors">{{ __('site.nav.admin_panel') }}</a>
                    @elseif($isStudentPortalSession)
                        <a href="{{ route('student.dashboard') }}" class="rounded-xl bg-[#1a5632] py-2 text-center text-xs font-bold text-white hover:bg-[#0b2415] transition-colors">{{ __('site.nav.student_portal') }}</a>
                    @elseif($headerUser?->device_id)
                        <a href="{{ route('hajiri.home') }}" class="rounded-xl bg-[#1a5632] py-2 text-center text-xs font-bold text-white hover:bg-[#0b2415] transition-colors">{{ __('site.nav.my_dashboard') }}</a>
                    @elseif($vacancyModuleEnabled)
                        <a href="{{ route('account.applications.index') }}" class="rounded-xl bg-gray-100 py-2 text-center text-xs font-bold text-gray-700 hover:bg-gray-200 transition-colors">{{ __('site.nav.applications') }}</a>
                    @endif
                    <form method="POST" action="{{ $isStudentPortalSession ? route('student.logout') : (($headerUser?->isAdmin() || $headerUser?->device_id) ? route('logout') : route('applicant.logout')) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-xl bg-red-50 py-2 text-xs font-bold text-red-700 hover:bg-red-100 transition-colors">{{ __('site.nav.log_out') }}</button>
                    </form>
                </div>
            @else
                <p class="mb-2 px-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ __('site.nav.portals') }}</p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('login') }}" class="rounded-xl bg-[#1a5632] py-2.5 text-center text-xs font-bold text-white hover:bg-[#0b2415] transition-colors">
                        {{ __('site.nav.staff_portal') }}
                    </a>
                    <a href="/student/card/login" class="rounded-xl bg-blue-50 py-2.5 text-center text-xs font-bold text-blue-700 hover:bg-blue-100 transition-colors">
                        {{ __('site.nav.student_portal') }}
                    </a>
                    @if($vacancyModuleEnabled)
                        <a href="{{ route('applicant.login') }}" class="col-span-2 rounded-xl bg-amber-50 py-2.5 text-center text-xs font-bold text-amber-700 hover:bg-amber-100 transition-colors">
                            {{ __('site.nav.applicant_login') }}
                        </a>
                    @endif
                </div>
            @endauth
        </div>
    </div>
</header>

{{-- TICKER CSS --}}
<style>
    @keyframes ticker-scroll {
        0%   { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    .ticker-wrapper {
        display: flex;
        width: max-content;
        animation: ticker-scroll 40s linear infinite;
    }
    .ticker-wrapper:hover { animation-play-state: paused; }
    .ticker-track { display: flex; align-items: center; }
    header,
    header * {
        min-width: 0;
    }
    header :where(a, span, button) {
        overflow-wrap: anywhere;
    }
    @media (max-width: 640px) {
        header .ticker-track a {
            padding-left: .5rem;
            padding-right: .5rem;
        }
        header .ticker-track a span.truncate {
            max-width: 34vw;
        }
    }
</style>
