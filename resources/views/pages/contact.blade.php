{{-- resources/views/pages/contact.blade.php --}}
@extends('layouts.app')

@section('title', __('site.contact.page_title'))
@section('meta_description', __('site.contact.meta_desc'))

@section('content')

@php
    $mapLat  = $siteSettings->get('map_latitude',  '29.2844');
    $mapLng  = $siteSettings->get('map_longitude', '81.0897');
    $mapZoom = $siteSettings->get('map_zoom',      '16');
    $mapSrc  = "https://maps.google.com/maps?q={$mapLat},{$mapLng}&t=&z={$mapZoom}&ie=UTF8&iwloc=&output=embed";
    $ohDays   = $siteSettings->get('office_hours_days',   'Sunday – Friday');
    $ohTime   = $siteSettings->get('office_hours_time',   '9:00 AM – 5:00 PM');
    $ohClosed = $siteSettings->get('office_hours_closed', 'Saturday & Public Holidays');
@endphp

{{-- ── Scoped styles (all colours driven by CSS vars) ── --}}
<style>
    .contact-field {
        width: 100%;
        border-radius: .625rem;
        border: 1.5px solid var(--theme-border);
        background: var(--theme-muted-surface);
        padding: .75rem 1rem;
        font-size: .875rem;
        color: var(--theme-text);
        outline: none;
        transition: border-color .18s, background .18s, box-shadow .18s;
    }
    .contact-field:focus {
        border-color: var(--theme-primary);
        background: var(--theme-surface);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-primary) 12%, transparent);
    }
    .contact-field.is-error { border-color: #ef4444; }
    .contact-field::placeholder { color: var(--theme-muted-text); opacity: .65; }
    .contact-submit {
        width: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        border-radius: .75rem;
        background: var(--theme-secondary);
        color: var(--theme-dark);
        font-weight: 900;
        font-size: .9375rem;
        padding: .9rem 1.5rem;
        border: none;
        cursor: pointer;
        transition: filter .2s, transform .18s, box-shadow .2s;
        box-shadow: 0 4px 14px color-mix(in srgb, var(--theme-secondary) 30%, transparent);
    }
    .contact-submit:hover {
        filter: brightness(1.06);
        transform: translateY(-1px);
        box-shadow: 0 8px 22px color-mix(in srgb, var(--theme-secondary) 38%, transparent);
    }
    .info-chip {
        border-radius: .875rem;
        border: 1.5px solid var(--theme-border);
        background: var(--theme-surface);
        padding: 1.25rem 1.25rem;
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        transition: border-color .2s, transform .2s;
    }
    .info-chip:hover {
        border-color: color-mix(in srgb, var(--theme-primary) 35%, transparent);
        transform: translateY(-2px);
    }
    .info-icon {
        width: 2.5rem;
        height: 2.5rem;
        flex-shrink: 0;
        border-radius: .625rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--theme-primary-soft);
    }
    .info-icon svg { color: var(--theme-primary); }
    .hours-open {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: .75rem;
        padding: .75rem 1rem;
        background: var(--theme-primary-soft);
    }
    .hours-closed {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: .75rem;
        padding: .75rem 1rem;
        background: #fff1f2;
        border: 1px solid #fecdd3;
    }
</style>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- HERO --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<section class="theme-page-hero py-12 sm:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <nav class="theme-hero-breadcrumb mb-6 flex items-center gap-2 text-sm font-semibold" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="transition-colors">{{ __('site.common.home') }}</a>
            <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
            <span style="color:#fff;">{{ __('site.contact.breadcrumb') }}</span>
        </nav>

        <span class="theme-badge-accent inline-flex items-center rounded-full px-4 py-1.5 text-xs font-black uppercase tracking-widest mb-5">
            {{ __('site.contact.badge') }}
        </span>

        <h1 class="theme-hero-title text-4xl font-black tracking-tight sm:text-5xl mb-4 max-w-2xl">
            {{ __('site.contact.hero_h1') }}
        </h1>

        <p class="theme-hero-subtitle max-w-xl text-base leading-7">
            {{ __('site.contact.hero_sub') }}
        </p>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- THREE INFO CHIPS --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div style="background: var(--theme-body-bg);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid gap-4 sm:grid-cols-3 -translate-y-6">

            {{-- Address --}}
            <div class="info-chip">
                <div class="info-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-widest mb-1" style="color: var(--theme-muted-text);">{{ __('site.contact.visit_title') }}</p>
                    <p class="text-sm font-bold leading-snug" style="color: var(--theme-text);">{{ $siteSettings->localized('site_address', __('site.location')) }}</p>
                </div>
            </div>

            {{-- Phone --}}
            <div class="info-chip">
                <div class="info-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-widest mb-1" style="color: var(--theme-muted-text);">{{ __('site.contact.call_title') }}</p>
                    <a href="tel:{{ $siteSettings->get('school_phone', '') }}"
                       class="text-sm font-bold leading-snug block hover:underline"
                       style="color: var(--theme-primary);">{{ $siteSettings->get('school_phone', __('site.footer.phone')) }}</a>
                    <p class="text-xs mt-0.5" style="color: var(--theme-muted-text);">{{ $ohDays }}</p>
                </div>
            </div>

            {{-- Email --}}
            <div class="info-chip">
                <div class="info-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-widest mb-1" style="color: var(--theme-muted-text);">{{ __('site.contact.email_title') }}</p>
                    <a href="mailto:{{ $siteSettings->get('school_email', '') }}"
                       class="text-sm font-bold leading-snug block break-all hover:underline"
                       style="color: var(--theme-primary);">{{ $siteSettings->get('school_email', 'info@godawari.edu.np') }}</a>
                    <p class="text-xs mt-0.5" style="color: var(--theme-muted-text);">{{ __('site.contact.email_line2') }}</p>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- FORM + MAP/HOURS --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<section id="contact-form" class="pb-16 sm:pb-20" style="background: var(--theme-body-bg);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">

            {{-- ── FORM PANEL ── --}}
            <div class="rounded-2xl border p-6 sm:p-8 shadow-sm"
                 style="background: var(--theme-surface); border-color: var(--theme-border);">

                {{-- Success banner --}}
                @if(session('contact_success'))
                <div x-data="{ show: true }"
                     x-show="show"
                     x-init="setTimeout(() => show = false, 6000)"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="mb-6 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="flex-1 text-sm font-bold text-green-800">{{ session('contact_success') }}</p>
                    <button @click="show=false" class="text-green-400 hover:text-green-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                @endif

                {{-- Form heading --}}
                <div class="mb-7">
                    <p class="theme-section-eyebrow text-xs mb-3">{{ __('site.contact.badge') }}</p>
                    <h2 class="text-2xl font-black" style="color: var(--theme-text);">{{ __('site.contact.form_h2') }}</h2>
                    <p class="mt-1.5 text-sm" style="color: var(--theme-muted-text);">{{ __('site.contact.form_sub') }}</p>
                </div>

                <form action="{{ route('contact.submit') }}#contact-form" method="POST" class="space-y-5">
                    @csrf

                    {{-- Honeypot: hidden from humans, bots fill it in --}}
                    <div style="position:absolute; left:-9999px; top:-9999px;" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                    </div>
                    <input type="hidden" name="form_rendered_at" value="{{ now()->timestamp }}">

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-wider"
                                   style="color: var(--theme-muted-text);" for="name">{{ __('site.contact.name_label') }}</label>
                            <input type="text" name="name" id="name" required value="{{ old('name') }}"
                                   placeholder="{{ __('site.contact.name_ph') }}"
                                   class="contact-field {{ $errors->has('name') ? 'is-error' : '' }}">
                            @error('name') <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-wider"
                                   style="color: var(--theme-muted-text);" for="contact_phone">{{ __('site.contact.phone_label') }}</label>
                            <input type="tel" name="phone" id="contact_phone" required value="{{ old('phone') }}"
                                   placeholder="98XXXXXXXX"
                                   class="contact-field {{ $errors->has('phone') ? 'is-error' : '' }}">
                            @error('phone') <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wider"
                               style="color: var(--theme-muted-text);" for="contact_email">{{ __('site.contact.email_label') }}</label>
                        <input type="email" name="email" id="contact_email" value="{{ old('email') }}"
                               placeholder="optional@email.com"
                               class="contact-field {{ $errors->has('email') ? 'is-error' : '' }}">
                        @error('email') <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wider"
                               style="color: var(--theme-muted-text);" for="subject">{{ __('site.contact.subject_label') }}</label>
                        <select name="subject" id="subject" required
                                class="contact-field {{ $errors->has('subject') ? 'is-error' : '' }}"
                                style="appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%2364748B' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 1rem center;">
                            <option value="" disabled {{ old('subject') ? '' : 'selected' }}>Select a topic…</option>
                            <option value="Admission Inquiry"    {{ old('subject') == 'Admission Inquiry'    ? 'selected' : '' }}>Admission Inquiry</option>
                            <option value="Fee Structure"        {{ old('subject') == 'Fee Structure'        ? 'selected' : '' }}>Fee Structure</option>
                            <option value="Academic Programs"    {{ old('subject') == 'Academic Programs'    ? 'selected' : '' }}>Academic Programs</option>
                            <option value="Transportation / Bus" {{ old('subject') == 'Transportation / Bus' ? 'selected' : '' }}>Transportation / Bus Service</option>
                            <option value="General Question"     {{ old('subject') == 'General Question'     ? 'selected' : '' }}>General Question</option>
                        </select>
                        @error('subject') <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wider"
                               style="color: var(--theme-muted-text);" for="contact_message">{{ __('site.contact.message_label') }}</label>
                        <textarea name="message" id="contact_message" rows="5" required
                                  placeholder="{{ __('site.contact.message_ph') }}"
                                  class="contact-field resize-none {{ $errors->has('message') ? 'is-error' : '' }}">{{ old('message') }}</textarea>
                        @error('message') <span class="mt-1 block text-xs font-bold text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-1">
                        <button type="submit" class="contact-submit">
                            {{ __('site.contact.submit_btn') }}
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            {{-- ── RIGHT COLUMN: Hours + Map ── --}}
            <div class="flex flex-col gap-5">

                {{-- Office Hours --}}
                <div class="rounded-2xl border p-6" style="background: var(--theme-surface); border-color: var(--theme-border);">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="info-icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-black text-base" style="color: var(--theme-text);">{{ __('site.contact.hours_h3') }}</h3>
                    </div>

                    <div class="space-y-2.5">
                        <div class="hours-open">
                            <div class="flex items-center gap-2.5">
                                <span class="h-2 w-2 rounded-full shrink-0" style="background: var(--theme-primary);"></span>
                                <span class="text-sm font-semibold" style="color: var(--theme-text);">{{ $ohDays }}</span>
                            </div>
                            <span class="text-sm font-black" style="color: var(--theme-primary);">{{ $ohTime }}</span>
                        </div>
                        <div class="hours-closed">
                            <div class="flex items-center gap-2.5">
                                <span class="h-2 w-2 rounded-full bg-red-400 shrink-0"></span>
                                <span class="text-sm font-semibold text-gray-600">{{ $ohClosed }}</span>
                            </div>
                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-black text-red-600">Closed</span>
                        </div>
                    </div>
                </div>

                {{-- Map --}}
                <div class="flex-1 overflow-hidden rounded-2xl border"
                     style="border-color: var(--theme-border); border-top: 3px solid var(--theme-secondary); min-height: 280px;">
                    <iframe
                        src="{{ $mapSrc }}"
                        width="100%"
                        height="100%"
                        style="border:0; display:block; min-height: 280px;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="{{ $siteSettings->localized('site_name', __('site.school_name')) }} Location">
                    </iframe>
                </div>

            </div>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
