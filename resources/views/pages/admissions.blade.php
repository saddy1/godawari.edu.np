{{-- resources/views/pages/admissions.blade.php --}}
@extends('layouts.app')

@section('title', __('site.admissions.page_title') . ' ' . ($settings['academic_year'] ?? ''))
@section('meta_description', __('site.admissions.page_title'))

@section('content')

<style>
    /* ── Section background pattern ── */
    .admissions-section {
        background-color: var(--theme-body-bg);
        background-image:
            radial-gradient(circle at 1px 1px,
                color-mix(in srgb, var(--theme-primary) 8%, transparent) 1px,
                transparent 0);
        background-size: 28px 28px;
    }
    /* ── Info item ── */
    .info-item-icon {
        width: 2.5rem;
        height: 2.5rem;
        flex-shrink: 0;
        border-radius: .625rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--theme-primary-soft);
        transition: background .2s;
    }
    .info-item-icon svg { color: var(--theme-primary); }
    .info-item:hover .info-item-icon { background: var(--theme-primary); }
    .info-item:hover .info-item-icon svg { color: #fff; }
    .info-item:hover .info-item-title { color: var(--theme-primary); }
    /* ── Contact card ── */
    .contact-card {
        border-radius: 1rem;
        padding: 1.75rem;
        position: relative;
        overflow: hidden;
        background: var(--theme-primary);
        color: #fff;
    }
    .contact-card::before {
        content: '';
        position: absolute;
        top: -3rem; right: -3rem;
        width: 9rem; height: 9rem;
        border-radius: 50%;
        background: var(--theme-secondary);
        opacity: .15;
        pointer-events: none;
    }
    .contact-card::after {
        content: '';
        position: absolute;
        bottom: -2rem; left: -2rem;
        width: 7rem; height: 7rem;
        border-radius: 50%;
        background: #fff;
        opacity: .06;
        pointer-events: none;
    }
    /* ── Form card ── */
    .form-card {
        background: var(--theme-surface);
        border-radius: 1.25rem;
        overflow: hidden;
        border: 1.5px solid var(--theme-border);
        box-shadow: 0 8px 40px color-mix(in srgb, var(--theme-primary) 8%, transparent),
                    0 2px 8px rgba(0,0,0,.06);
    }
    /* ── Form card header band ── */
    .form-card-header {
        padding: 1.5rem 2rem;
        background: linear-gradient(135deg,
            var(--theme-primary) 0%,
            var(--theme-primary-light) 100%);
        position: relative;
        overflow: hidden;
    }
    .form-card-header::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: radial-gradient(rgba(255,255,255,.12) 1px, transparent 1.2px);
        background-size: 22px 22px;
    }
    .form-card-header::after {
        content: '';
        position: absolute;
        top: -2rem; right: -2rem;
        width: 8rem; height: 8rem;
        border-radius: 50%;
        background: var(--theme-secondary);
        opacity: .2;
    }
    /* ── Form fields ── */
    .adm-field {
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
    .adm-field:focus {
        border-color: var(--theme-primary);
        background: var(--theme-surface);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-primary) 12%, transparent);
    }
    .adm-field::placeholder { color: var(--theme-muted-text); opacity: .65; }
    /* ── Submit button ── */
    .adm-submit {
        width: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        border-radius: .75rem;
        background: var(--theme-secondary);
        color: var(--theme-dark);
        font-weight: 900;
        font-size: 1rem;
        padding: .9rem 1.5rem;
        border: none;
        cursor: pointer;
        transition: filter .2s, transform .18s, box-shadow .2s;
        box-shadow: 0 4px 14px color-mix(in srgb, var(--theme-secondary) 35%, transparent);
    }
    .adm-submit:hover {
        filter: brightness(1.06);
        transform: translateY(-1px);
        box-shadow: 0 8px 24px color-mix(in srgb, var(--theme-secondary) 40%, transparent);
    }
    /* ── Select chevron ── */
    .adm-select-wrap { position: relative; }
    .adm-select-wrap .adm-chevron {
        pointer-events: none;
        position: absolute;
        inset-y: 0; right: .875rem;
        display: flex; align-items: center;
        color: var(--theme-muted-text);
    }
    .adm-field[type=date]::-webkit-calendar-picker-indicator { opacity: .5; cursor: pointer; }
</style>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- HERO --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="theme-page-hero py-12 sm:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <nav class="theme-hero-breadcrumb mb-6 flex items-center gap-2 text-sm font-semibold" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">{{ __('site.common.home') }}</a>
            <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
            <span style="color:#fff;">{{ __('site.admissions.breadcrumb') }}</span>
        </nav>

        <span class="theme-badge-accent inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-black uppercase tracking-widest mb-5">
            🎓 {{ __('site.admissions.badge') }} {{ $settings['academic_year'] }}
        </span>

        <h1 class="theme-hero-title text-4xl font-black tracking-tight sm:text-5xl xl:text-6xl mb-4 max-w-3xl">
            {{ __('site.admissions.hero_h1') }}
        </h1>
        <p class="theme-hero-subtitle max-w-2xl text-base leading-7 sm:text-lg">
            {{ __('site.admissions.hero_sub') }}
        </p>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- CONTENT + FORM --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="admissions-section py-16 sm:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-12 lg:gap-14">

            {{-- ── LEFT: Info column ── --}}
            <div class="lg:col-span-5">
                <div class="lg:sticky lg:top-28 space-y-8">

                    <div>
                        <p class="theme-section-eyebrow text-xs mb-3">{{ __('site.admissions.enroll_label') }}</p>
                        <h2 class="text-2xl font-black sm:text-3xl" style="color: var(--theme-text);">Admission Information</h2>
                    </div>

                    {{-- Info items --}}
                    <div class="space-y-5">
                        @foreach([
                            ['title' => __('site.admissions.classes_title'), 'content' => __('site.admissions.classes_content')],
                            ['title' => __('site.admissions.year_title'),    'content' => $settings['academic_year']],
                            ['title' => __('site.admissions.age_title'),     'content' => __('site.admissions.age_content')],
                            ['title' => __('site.admissions.medium_title'),  'content' => __('site.admissions.medium_content')],
                            ['title' => __('site.admissions.process_title'), 'content' => __('site.admissions.process_content')],
                            ['title' => __('site.admissions.docs_title'),    'content' => __('site.admissions.docs_content')],
                        ] as $info)
                        <div class="info-item flex gap-4 group">
                            <div class="info-item-icon transition-all duration-200">
                                <svg class="w-4 h-4 transition-colors duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="info-item-title text-sm font-black transition-colors duration-200" style="color: var(--theme-text);">{{ $info['title'] }}</p>
                                <p class="mt-0.5 text-sm leading-relaxed" style="color: var(--theme-muted-text);">{{ $info['content'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Contact card --}}
                    <div class="contact-card">
                        <div class="relative z-10">
                            <p class="mb-4 flex items-center gap-2 text-base font-black" style="color: var(--theme-secondary);">
                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                {{ __('site.admissions.direct_inquiries') }}
                            </p>
                            <div class="space-y-2.5 text-sm text-white/80">
                                <p>{{ __('site.admissions.call_admin') }}
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings['phone']) }}"
                                       class="font-black hover:underline" style="color: var(--theme-secondary);">
                                        {{ $settings['phone'] }}
                                    </a>
                                </p>
                                <p>{{ __('site.admissions.email_us') }}
                                    <a href="mailto:{{ $settings['email'] }}"
                                       class="font-black hover:underline" style="color: var(--theme-secondary);">
                                        {{ $settings['email'] }}
                                    </a>
                                </p>
                                <div class="my-4 h-px w-full" style="background: rgba(255,255,255,.15);"></div>
                                <p class="text-xs text-white/60 font-semibold">
                                    {{ __('site.admissions.office_hours') }} {{ $settings['office_hours'] }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ── RIGHT: Form card ── --}}
            <div class="lg:col-span-7">
                <div class="form-card">

                    {{-- Decorative form header --}}
                    <div class="form-card-header relative z-10 flex items-center justify-between gap-4">
                        <div class="relative z-10">
                            <p class="text-[10px] font-black uppercase tracking-widest mb-1" style="color: var(--theme-secondary);">
                                Academic Year {{ $settings['academic_year'] }}
                            </p>
                            <h2 class="text-xl font-black text-white sm:text-2xl" style="text-shadow: 0 1px 3px rgba(0,0,0,.3);">{{ __('site.admissions.form_h2') }}</h2>
                            <p class="mt-1 text-sm text-white/90">{{ __('site.admissions.form_sub') }}</p>
                        </div>
                        <div class="relative z-10 hidden shrink-0 sm:flex h-14 w-14 items-center justify-center rounded-2xl"
                             style="background: rgba(255,255,255,.15);">
                            <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Form body --}}
                    <div class="p-6 sm:p-8">

                        {{-- Validation errors --}}
                        @if($errors->any())
                        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
                            <p class="mb-2 text-sm font-black text-red-700">Please fix the following:</p>
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                <li class="flex items-start gap-2 text-xs font-semibold text-red-600">
                                    <span class="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-red-400"></span>
                                    {{ $error }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        {{-- Success --}}
                        @if(session('success'))
                        <div class="mb-6 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                                 style="background: var(--theme-primary);">
                                <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-green-800">{{ session('success') }}</p>
                        </div>
                        @endif

                        <form action="{{ route('admissions.store') }}" method="POST" class="space-y-5">
                            @csrf

                            {{-- Honeypot: hidden from humans, bots fill it in --}}
                            <div style="position:absolute; left:-9999px; top:-9999px;" aria-hidden="true">
                                <label for="admission_website">Website</label>
                                <input type="text" name="website" id="admission_website" tabindex="-1" autocomplete="off">
                            </div>
                            <input type="hidden" name="form_rendered_at" value="{{ now()->timestamp }}">

                            {{-- Row: Student name + DOB --}}
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wider" style="color: var(--theme-text);" for="student_name">
                                        {{ __('site.admissions.student_name') }}
                                    </label>
                                    <input type="text" name="student_name" id="student_name"
                                           value="{{ old('student_name') }}" required
                                           placeholder="{{ __('site.admissions.student_name_ph') }}"
                                           class="adm-field {{ $errors->has('student_name') ? 'border-red-400' : '' }}">
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wider" style="color: var(--theme-text); opacity: .75;" for="dob">
                                        {{ __('site.admissions.dob') }}
                                    </label>
                                    <input type="date" name="dob" id="dob"
                                           value="{{ old('dob') }}" required
                                           class="adm-field {{ $errors->has('dob') ? 'border-red-400' : '' }}">
                                </div>
                            </div>

                            {{-- Row: Gender + Program --}}
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wider" style="color: var(--theme-text); opacity: .75;" for="gender">
                                        {{ __('site.admissions.gender') }}
                                    </label>
                                    <div class="adm-select-wrap">
                                        <select name="gender" id="gender" required
                                                class="adm-field {{ $errors->has('gender') ? 'border-red-400' : '' }}"
                                                style="appearance:none;padding-right:2.5rem;">
                                            <option value="" disabled {{ !old('gender') ? 'selected' : '' }}>{{ __('site.admissions.gender_ph') }}</option>
                                            <option value="Male"   {{ old('gender') == 'Male'   ? 'selected' : '' }}>{{ __('site.admissions.gender_male') }}</option>
                                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>{{ __('site.admissions.gender_female') }}</option>
                                            <option value="Other"  {{ old('gender') == 'Other'  ? 'selected' : '' }}>{{ __('site.admissions.gender_other') }}</option>
                                        </select>
                                        <span class="adm-chevron"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></span>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wider" style="color: var(--theme-text); opacity: .75;" for="applied_grade">
                                        {{ __('site.admissions.class') }}
                                    </label>
                                    <div class="adm-select-wrap">
                                        <select name="applied_grade" id="applied_grade" required
                                                class="adm-field {{ $errors->has('applied_grade') ? 'border-red-400' : '' }}"
                                                style="appearance:none;padding-right:2.5rem;">
                                            <option value="" disabled {{ !old('applied_grade') ? 'selected' : '' }}>{{ __('site.admissions.class_ph') }}</option>
                                            @foreach($programs as $program)
                                                <option value="{{ $program }}" @selected(old('applied_grade') === $program)>{{ $program }}</option>
                                            @endforeach
                                        </select>
                                        <span class="adm-chevron"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Guardian name --}}
                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wider" style="color: var(--theme-text); opacity: .75;" for="guardian_name">
                                    {{ __('site.admissions.guardian_name') }}
                                </label>
                                <input type="text" name="guardian_name" id="guardian_name"
                                       value="{{ old('guardian_name') }}" required
                                       placeholder="{{ __('site.admissions.guardian_ph') }}"
                                       class="adm-field {{ $errors->has('guardian_name') ? 'border-red-400' : '' }}">
                            </div>

                            {{-- Row: Phone + Email --}}
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wider" style="color: var(--theme-text); opacity: .75;" for="phone">
                                        {{ __('site.admissions.phone') }}
                                    </label>
                                    <input type="tel" name="phone" id="phone"
                                           value="{{ old('phone') }}" required placeholder="98XXXXXXXX"
                                           class="adm-field {{ $errors->has('phone') ? 'border-red-400' : '' }}">
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wider" style="color: var(--theme-text); opacity: .75;" for="email">
                                        {{ __('site.admissions.email') }}
                                    </label>
                                    <input type="email" name="email" id="email"
                                           value="{{ old('email') }}" placeholder="optional@email.com"
                                           class="adm-field {{ $errors->has('email') ? 'border-red-400' : '' }}">
                                </div>
                            </div>

                            {{-- Address --}}
                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wider" style="color: var(--theme-text); opacity: .75;" for="address">
                                    {{ __('site.admissions.address') }}
                                </label>
                                <input type="text" name="address" id="address"
                                       value="{{ old('address') }}" required
                                       placeholder="{{ __('site.admissions.address_ph') }}"
                                       class="adm-field {{ $errors->has('address') ? 'border-red-400' : '' }}">
                            </div>

                            {{-- Previous school --}}
                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wider" style="color: var(--theme-text); opacity: .75;" for="previous_school">
                                    {{ __('site.admissions.prev_school') }}
                                </label>
                                <input type="text" name="previous_school" id="previous_school"
                                       value="{{ old('previous_school') }}"
                                       placeholder="{{ __('site.admissions.prev_school_ph') }}"
                                       class="adm-field">
                            </div>

                            {{-- Submit --}}
                            <div class="pt-2">
                                <button type="submit" class="adm-submit">
                                    {{ __('site.admissions.submit_btn') }}
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </button>
                                <p class="mt-4 flex items-center justify-center gap-1.5 text-center text-xs font-semibold" style="color: var(--theme-muted-text);">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    {{ __('site.common.secure_note') }}
                                </p>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

@endsection
