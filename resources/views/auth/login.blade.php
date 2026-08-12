{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.app')

@section('title', __('site.staff_login.title'))
@section('meta_description', __('site.staff_login.meta_desc'))
@section('seo_page_name', 'login')

@section('content')
<style>
    .public-login-page {
        position: relative;
        overflow: hidden;
        background:
            linear-gradient(180deg, #fff 0%, var(--theme-muted-surface) 100%),
            radial-gradient(circle at 8% 10%, color-mix(in srgb, var(--theme-primary) 12%, transparent), transparent 30%);
    }
    .public-login-page::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(90deg, color-mix(in srgb, var(--theme-primary) 7%, transparent) 1px, transparent 1px),
            linear-gradient(180deg, color-mix(in srgb, var(--theme-primary) 7%, transparent) 1px, transparent 1px);
        background-size: 44px 44px;
        opacity: .38;
        pointer-events: none;
    }
    .login-shell {
        position: relative;
        z-index: 1;
    }
    .login-panel {
        width: min(100%, 34rem);
        border: 1px solid var(--theme-border);
        background: var(--theme-surface);
        box-shadow: 0 24px 70px color-mix(in srgb, var(--theme-primary) 12%, transparent);
    }
    .login-field {
        width: 100%;
        border-radius: .75rem;
        border: 1.5px solid var(--theme-border);
        background: var(--theme-muted-surface);
        padding: .875rem 1rem;
        font-size: .9375rem;
        font-weight: 800;
        min-height: 3.25rem;
        color: var(--theme-text);
        outline: none;
        transition: border-color .18s, background .18s, box-shadow .18s;
    }
    .login-field:focus {
        border-color: var(--theme-primary);
        background: var(--theme-surface);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-primary) 12%, transparent);
    }
    .login-field.is-error { border-color: #ef4444; }
    .login-action {
        display: inline-flex;
        width: 100%;
        align-items: center;
        justify-content: center;
        gap: .6rem;
        border-radius: .75rem;
        background: var(--theme-primary);
        color: #fff;
        padding: .85rem 1.4rem;
        font-weight: 900;
        transition: transform .18s, filter .18s, box-shadow .18s;
        box-shadow: 0 10px 24px color-mix(in srgb, var(--theme-primary) 24%, transparent);
    }
    .login-action:hover {
        filter: brightness(.96);
        transform: translateY(-1px);
        box-shadow: 0 15px 32px color-mix(in srgb, var(--theme-primary) 30%, transparent);
    }
    .login-alt-link {
        border: 1.5px solid var(--theme-border);
        border-radius: .75rem;
        color: var(--theme-text);
        transition: border-color .18s, color .18s, background .18s;
    }
    .login-alt-link:hover {
        border-color: var(--theme-primary);
        color: var(--theme-primary);
        background: var(--theme-primary-soft);
    }
    @media (max-width: 640px) {
        .public-login-page {
            background:
                linear-gradient(180deg, #f8fbff 0%, #eef6f2 100%),
                radial-gradient(circle at 10% 0%, color-mix(in srgb, var(--theme-secondary) 18%, transparent), transparent 32%);
        }
        .login-panel {
            border-color: color-mix(in srgb, var(--theme-primary) 18%, white);
            background:
                linear-gradient(180deg, #fffaf0 0%, #f6fbf8 100%);
            border-radius: 1rem;
            box-shadow: 0 12px 36px color-mix(in srgb, var(--theme-primary) 10%, transparent);
        }
        .login-panel > div {
            background:
                linear-gradient(180deg, rgba(255,255,255,.86) 0%, rgba(240,248,244,.92) 100%);
        }
        .login-field,
        .login-alt-link,
        .login-panel label.flex {
            background-color: rgba(255,255,255,.78);
            border-color: color-mix(in srgb, var(--theme-primary) 14%, #d1d5db);
        }
        .login-field:focus {
            background: #fff;
        }
    }
</style>

<section class="public-login-page flex min-h-[calc(100dvh-7.5rem)] items-center py-6 sm:py-8">
    <div class="login-shell mx-auto flex w-full max-w-6xl justify-center px-4 sm:px-6 lg:px-8">
        <div class="login-panel overflow-hidden rounded-2xl">
            <div class="p-5 sm:p-7">
                <div class="mb-5 flex items-center gap-4 border-b border-gray-200 pb-5">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white p-2 shadow-sm">
                        <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->localized('site_name', config('app.name')) }} Logo" class="h-full w-full object-contain">
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--theme-primary)]">{{ __('site.staff_login.eyebrow') }}</p>
                        <h1 class="theme-heading mt-1 text-2xl font-black leading-tight text-gray-950 sm:text-3xl">{{ __('site.staff_login.heading') }}</h1>
                    </div>
                </div>

                @if (session('status'))
                    <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="login" class="mb-2 block text-sm font-black text-gray-700">{{ __('site.staff_login.login_label') }}</label>
                        <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                               class="login-field @error('login') is-error @enderror">
                        @error('login')
                            <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <label for="password" class="block text-sm font-black text-gray-700">{{ __('site.staff_login.password_label') }}</label>
                            @if (Route::has('password.request'))
                                <a class="text-sm font-black text-[var(--theme-primary)] hover:underline" href="{{ route('password.request') }}">
                                    {{ __('site.staff_login.forgot') }}
                                </a>
                            @endif
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                               class="login-field @error('password') is-error @enderror">
                        @error('password')
                            <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5">
                        <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-[var(--theme-primary)] focus:ring-[var(--theme-primary)]">
                        <span class="text-sm font-bold text-gray-600">{{ __('site.staff_login.remember') }}</span>
                    </label>

                    <button type="submit" class="login-action">
                        {{ __('site.staff_login.submit') }}
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </button>
                </form>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <a href="/student/card/login" class="login-alt-link px-4 py-2.5 text-center text-sm font-black">
                        {{ __('site.staff_login.student_portal') }}
                    </a>
                    <a href="{{ route('applicant.login') }}" class="login-alt-link px-4 py-2.5 text-center text-sm font-black">
                        {{ __('site.staff_login.applicant_login') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
