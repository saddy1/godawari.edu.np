@extends('layouts.app')

@section('title', __('site.student_login.title'))
@section('meta_description', __('site.student_login.meta_desc'))
@section('seo_page_name', 'student-login')

@section('content')
    <style>
        .student-login-page {
            min-height: calc(100dvh - 7.5rem);
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(135deg,
                    color-mix(in srgb, var(--theme-primary) 7%, white) 0%,
                    #fff 52%,
                    color-mix(in srgb, var(--theme-secondary) 10%, white) 100%);
        }
        .student-login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(90deg, rgba(15,23,42,.055) 1px, transparent 1px),
                linear-gradient(180deg, rgba(15,23,42,.055) 1px, transparent 1px);
            background-size: 42px 42px;
            pointer-events: none;
        }
        .student-login-shell {
            position: relative;
            z-index: 1;
        }
        .student-login-card {
            width: min(100%, 62rem);
            background: rgba(255,255,255,.92);
            border: 1px solid rgba(15,23,42,.10);
            box-shadow: 0 24px 70px rgba(15,23,42,.14);
            backdrop-filter: blur(16px);
        }
        .student-id-panel {
            background:
                linear-gradient(145deg, var(--theme-dark) 0%, var(--theme-primary) 62%, var(--theme-header-gradient-end) 100%);
        }
        .student-id-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,.18) 1px, transparent 1.2px);
            background-size: 24px 24px;
            opacity: .24;
            pointer-events: none;
        }
        .student-field {
            width: 100%;
            min-height: 3.15rem;
            border-radius: .875rem;
            border: 1.5px solid #dbe3ea;
            background: #f8fafc;
            padding: .8rem 1rem;
            color: #0f172a;
            font-size: .92rem;
            font-weight: 800;
            outline: none;
            transition: border-color .18s, background .18s, box-shadow .18s;
        }
        .student-field:focus {
            border-color: var(--theme-primary);
            background: #fff;
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--theme-primary) 14%, transparent);
        }
        .student-login-button {
            background: var(--theme-primary);
            box-shadow: 0 14px 30px color-mix(in srgb, var(--theme-primary) 26%, transparent);
        }
        .student-login-button:hover {
            background: var(--theme-dark);
            box-shadow: 0 18px 38px color-mix(in srgb, var(--theme-primary) 32%, transparent);
        }
        .student-brand-link:hover {
            border-color: var(--theme-primary);
            color: var(--theme-primary);
        }
        @media (max-width: 640px) {
            .student-login-card {
                border-radius: 1rem;
                box-shadow: 0 16px 42px rgba(15,23,42,.12);
            }
            .student-id-panel {
                min-height: auto;
            }
        }
    </style>
@php
    $fieldErrors = collect($errors->getMessages())
        ->except('credentials')
        ->flatMap(fn ($messages) => $messages);
@endphp

<section class="student-login-page flex items-center px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <div class="student-login-shell mx-auto flex w-full justify-center">
        <div class="student-login-card grid overflow-hidden rounded-3xl lg:grid-cols-[.82fr_1fr]">
            <section class="student-id-panel relative overflow-hidden p-5 text-white sm:p-7 lg:min-h-[34rem] lg:p-8">
                <div class="relative z-10 flex h-full flex-col justify-between gap-8">
                    <div>
                        <a href="{{ url('/') }}" class="inline-flex max-w-full items-center gap-3 rounded-2xl bg-white/10 px-3 py-2 ring-1 ring-white/15">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg">
                                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->localized('site_name', 'School') }} Logo" class="h-full w-full object-contain">
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-black">{{ $siteSettings->localized('site_name', 'School') }}</span>
                                <span class="block text-[11px] font-black uppercase tracking-[0.18em] text-white/55">{{ __('site.student_login.access_label') }}</span>
                            </span>
                        </a>

                        <div class="mt-8 rounded-2xl border border-white/15 bg-white/10 p-5 shadow-2xl backdrop-blur">
                            <p class="text-xs font-black uppercase tracking-[0.22em]" style="color: var(--theme-secondary);">{{ __('site.student_login.portal_badge') }}</p>
                            <h1 class="mt-3 text-3xl font-black leading-tight sm:text-4xl">
                                {{ __('site.student_login.panel_title') }}
                            </h1>
                            <div class="mt-6 grid gap-3 text-sm font-bold text-white/78">
                                <div class="flex items-center gap-3">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background: var(--theme-secondary);"></span>
                                    {{ __('site.student_login.feature_card') }}
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="h-2.5 w-2.5 rounded-full bg-white"></span>
                                    {{ __('site.student_login.feature_profile') }}
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="h-2.5 w-2.5 rounded-full bg-white/70"></span>
                                    {{ __('site.student_login.feature_learning') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hidden rounded-2xl border border-white/15 bg-black/10 p-4 text-xs font-bold text-white/58 lg:block">
                        {{ __('site.student_login.office_note') }}
                    </div>
                </div>
            </section>

            <section class="flex items-center p-5 sm:p-7 lg:p-9">
                <div class="w-full">
                    <div class="mb-6">
                        <p class="text-xs font-black uppercase tracking-[0.22em]" style="color: var(--theme-primary);">{{ __('site.student_login.eyebrow') }}</p>
                        <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ __('site.student_login.welcome') }}</h2>
                    </div>

                    @if($errors->has('credentials'))
                        <div class="mb-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ $errors->first('credentials') }}</span>
                        </div>
                    @endif

                    @if($fieldErrors->isNotEmpty())
                        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                            <ul class="list-inside list-disc space-y-1">
                                @foreach($fieldErrors as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('student.login.post') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="login" class="mb-2 block text-sm font-black text-slate-700">{{ __('site.student_login.login_label') }}</label>
                            <input id="login"
                                   type="text"
                                   name="login"
                                   value="{{ old('login') }}"
                                   required
                                   autofocus
                                   autocomplete="username"
                                   placeholder="{{ __('site.student_login.login_placeholder') }}"
                                   class="student-field">
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-black text-slate-700">{{ __('site.student_login.password_label') }}</label>
                            <input id="password"
                                   type="password"
                                   name="password"
                                   required
                                   autocomplete="current-password"
                                   placeholder="{{ __('site.student_login.password_placeholder') }}"
                                   class="student-field">
                        </div>

                        <button type="submit" class="student-login-button flex w-full items-center justify-center gap-2 rounded-xl px-5 py-3.5 text-base font-black text-white transition duration-200 hover:-translate-y-0.5">
                            {{ __('site.student_login.submit') }}
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </form>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <a href="{{ url('/') }}" class="student-brand-link rounded-xl border border-slate-200 px-4 py-3 text-center text-sm font-black text-slate-700">
                            {{ __('site.student_login.back_website') }}
                        </a>
                        <a href="{{ route('login') }}" class="student-brand-link rounded-xl border border-slate-200 px-4 py-3 text-center text-sm font-black text-slate-700">
                            {{ __('site.student_login.staff_login') }}
                        </a>
                    </div>

                    <p class="mt-5 text-center text-xs font-bold leading-5 text-slate-400">
                        {{ __('site.student_login.help_text') }}
                    </p>
                </div>
            </section>
        </div>
    </div>
</section>
@endsection
