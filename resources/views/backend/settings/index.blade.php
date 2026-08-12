@extends('layouts.admin')
@section('title', 'Global Settings')

@section('content')
@php
$presets = [
    ['name'=>'Forest Green',  'primary'=>'#1a5632','primary_light'=>'#237042','secondary'=>'#e2a024','dark'=>'#0b2415','body_bg'=>'#fdfbf7','body_end'=>'#f4f5f0','header_end'=>'#0f3d22','footer_end'=>'#0b2415','notice_bg'=>'','notice_accent'=>'','sidebar_end'=>'#050f09'],
    ['name'=>'Ocean Blue',     'primary'=>'#1a4a6e','primary_light'=>'#1e5f8c','secondary'=>'#f0a500','dark'=>'#0d2d44','body_bg'=>'#f0f7ff','body_end'=>'#e8f4fd','header_end'=>'#0d2d44','footer_end'=>'#0a1e2e','notice_bg'=>'','notice_accent'=>'','sidebar_end'=>'#061520'],
    ['name'=>'Royal Purple',   'primary'=>'#5b21b6','primary_light'=>'#7c3aed','secondary'=>'#f59e0b','dark'=>'#2e1065','body_bg'=>'#faf5ff','body_end'=>'#ede9fe','header_end'=>'#3b0764','footer_end'=>'#2e1065','notice_bg'=>'','notice_accent'=>'','sidebar_end'=>'#1a0538'],
    ['name'=>'Crimson Red',    'primary'=>'#9b1c1c','primary_light'=>'#b91c1c','secondary'=>'#fbbf24','dark'=>'#450a0a','body_bg'=>'#fff5f5','body_end'=>'#fee2e2','header_end'=>'#450a0a','footer_end'=>'#3c0707','notice_bg'=>'','notice_accent'=>'','sidebar_end'=>'#260404'],
    ['name'=>'Deep Teal',      'primary'=>'#0f766e','primary_light'=>'#0d9488','secondary'=>'#f97316','dark'=>'#042f2e','body_bg'=>'#f0fdfa','body_end'=>'#ccfbf1','header_end'=>'#042f2e','footer_end'=>'#021a1a','notice_bg'=>'','notice_accent'=>'','sidebar_end'=>'#01100f'],
    ['name'=>'Slate Modern',   'primary'=>'#334155','primary_light'=>'#475569','secondary'=>'#38bdf8','dark'=>'#0f172a','body_bg'=>'#f8fafc','body_end'=>'#e2e8f0','header_end'=>'#1e293b','footer_end'=>'#0f172a','notice_bg'=>'','notice_accent'=>'','sidebar_end'=>'#060d18'],
];
@endphp
<div class="max-w-5xl mx-auto px-4 py-8">
    
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Global Settings</h2>
        <p class="text-sm text-gray-500 mt-1">Manage global variables like contact information and active academic years.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('mail_success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm font-bold shadow-sm">
            {{ session('mail_success') }}
        </div>
    @endif

    @if(session('mail_error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm font-bold shadow-sm">
            {{ session('mail_error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm font-bold shadow-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Branding & Appearance Card --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-8 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-[#1a5632]/10 flex items-center justify-center text-[#1a5632]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h14m-7-7h7m-7-4h7M7 7h.01"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-[#0b2415]">Branding & Website Appearance</h3>
                        <p class="text-xs text-gray-500">These values control the public website logo, school name, app name, colors, and fonts.</p>
                    </div>
                </div>
            </div>

            <div class="p-8 space-y-8">
                <div class="grid lg:grid-cols-[220px_220px_1fr] gap-8 items-start">
                    <div>
                        <x-admin-image-picker
                            name="site_logo_media_path"
                            file-name="site_logo"
                            label="Logo"
                            :current-url="$siteSettings->logoUrl()"
                            :current-path="$settings['site_logo'] ?? null"
                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                            help="Choose from Media or upload PNG, JPG, WEBP, or SVG up to 2 MB."
                        />
                    </div>

                    <div>
                        <x-admin-image-picker
                            name="site_favicon_media_path"
                            file-name="site_favicon"
                            label="Favicon"
                            :current-url="$siteSettings->faviconUrl()"
                            :current-path="$settings['site_favicon'] ?? null"
                            accept="image/png,image/jpeg,image/webp,image/x-icon"
                            help="Choose from Media or upload PNG, JPG, WEBP, or ICO. Recommended: 32x32 or 64x64 px."
                        />
                    </div>

                    <div class="grid sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">School Name English <span class="text-red-500">*</span></label>
                            <input type="text" name="site_name_en" value="{{ old('site_name_en', $settings['site_name_en'] ?? $siteSettings->get('site_name_en')) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">School Name Nepali</label>
                            <input type="text" name="site_name_ne" value="{{ old('site_name_ne', $settings['site_name_ne'] ?? $siteSettings->get('site_name_ne')) }}" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">App / ERP Name <span class="text-red-500">*</span></label>
                            <input type="text" name="app_name" value="{{ old('app_name', $settings['app_name'] ?? $siteSettings->get('app_name')) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Website URL</label>
                            <input type="url" name="website_url" value="{{ old('website_url', $settings['website_url'] ?? $siteSettings->get('website_url')) }}" placeholder="https://example.edu.np" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">School Code</label>
                            <input type="text" name="school_code" value="{{ old('school_code', $settings['school_code'] ?? $siteSettings->get('school_code')) }}" placeholder="School code used in official forms" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                            <p class="text-xs text-gray-400 mt-2">Used in store requisition and other official forms.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Default Website Language <span class="text-red-500">*</span></label>
                            <select name="default_locale" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                                <option value="en" {{ old('default_locale', $settings['default_locale'] ?? $siteSettings->get('default_locale', 'en')) === 'en' ? 'selected' : '' }}>English</option>
                                <option value="ne" {{ old('default_locale', $settings['default_locale'] ?? $siteSettings->get('default_locale', 'en')) === 'ne' ? 'selected' : '' }}>Nepali</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-2">Public visitors use this language unless they choose another from the header.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Tagline English</label>
                            <input type="text" name="site_tagline_en" value="{{ old('site_tagline_en', $settings['site_tagline_en'] ?? $siteSettings->get('site_tagline_en')) }}" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Tagline Nepali</label>
                            <input type="text" name="site_tagline_ne" value="{{ old('site_tagline_ne', $settings['site_tagline_ne'] ?? $siteSettings->get('site_tagline_ne')) }}" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Address English <span class="text-red-500">*</span></label>
                            <input type="text" name="site_address_en" value="{{ old('site_address_en', $settings['site_address_en'] ?? $siteSettings->get('site_address_en')) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Address Nepali</label>
                            <input type="text" name="site_address_ne" value="{{ old('site_address_ne', $settings['site_address_ne'] ?? $siteSettings->get('site_address_ne')) }}" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                    </div>
                </div>

                {{-- Base Colors Row --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 sm:gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Primary Color</label>
                        <input type="color" name="primary_color" value="{{ old('primary_color', $settings['primary_color'] ?? $siteSettings->get('primary_color')) }}" class="h-12 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        <p class="text-xs text-gray-400 mt-1">Header, buttons, links</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Accent / Gold Color</label>
                        <input type="color" name="secondary_color" value="{{ old('secondary_color', $settings['secondary_color'] ?? $siteSettings->get('secondary_color')) }}" class="h-12 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        <p class="text-xs text-gray-400 mt-1">Notice label, highlights</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Dark Shade</label>
                        <input type="color" name="dark_color" value="{{ old('dark_color', $settings['dark_color'] ?? $siteSettings->get('dark_color')) }}" class="h-12 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        <p class="text-xs text-gray-400 mt-1">Footer, sidebar dark areas</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Primary Light</label>
                        <input type="color" name="primary_light_color" value="{{ old('primary_light_color', $settings['primary_light_color'] ?? $siteSettings->get('primary_light_color', '#237042')) }}" class="h-12 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        <p class="text-xs text-gray-400 mt-1">Gradient end, hover states</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Body Font</label>
                        @php($bodyFont = old('body_font', $settings['body_font'] ?? $siteSettings->get('body_font')))
                        <select name="body_font" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                            @foreach(['DM Sans', 'Inter', 'Noto Sans Devanagari', 'Poppins'] as $font)
                                <option value="{{ $font }}" @selected($bodyFont === $font)>{{ $font }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Heading Font</label>
                        @php($headingFont = old('heading_font', $settings['heading_font'] ?? $siteSettings->get('heading_font')))
                        <select name="heading_font" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                            @foreach(['Playfair Display', 'Lora', 'Merriweather', 'Noto Sans Devanagari'] as $font)
                                <option value="{{ $font }}" @selected($headingFont === $font)>{{ $font }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- ADVANCED THEME & GRADIENT CUSTOMIZATION CARD           --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-gray-50 to-purple-50/30 border-b border-gray-100 px-6 sm:px-8 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h14m-7-7h7m-7-4h7M7 7h.01M14 5l3 3-3 3"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-[#0b2415]">Advanced Theme & Gradient Colors</h3>
                        <p class="text-xs text-gray-500">Customize gradients for body, header notice bar, footer, and admin sidebar. Tip: set start and end to same color for solid fills.</p>
                    </div>
                </div>
            </div>

            <div class="p-6 sm:p-8 space-y-8">

                {{-- Quick Preset Themes --}}
                <div>
                    <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                        Quick Preset Themes
                    </p>
                    <div class="flex flex-wrap gap-2" id="presetThemes">
                        @foreach($presets as $p)
                        <button type="button" onclick="applyPreset({{ json_encode($p) }})"
                                class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold border border-gray-200 hover:border-gray-300 bg-white hover:bg-gray-50 transition-all shadow-sm group">
                            <span class="flex gap-1">
                                <span class="w-4 h-4 rounded-full border border-white shadow-sm" style="background:{{ $p['primary'] }};"></span>
                                <span class="w-4 h-4 rounded-full border border-white shadow-sm" style="background:{{ $p['secondary'] }};"></span>
                            </span>
                            <span class="text-gray-700 group-hover:text-gray-900">{{ $p['name'] }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Body Background --}}
                <div>
                    <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/></svg>
                        Body Background Gradient
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Start Color (top)</label>
                            <input type="color" id="body_bg_color" name="body_bg_color"
                                   value="{{ old('body_bg_color', $settings['body_bg_color'] ?? $siteSettings->get('body_bg_color', '#fdfbf7')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">End Color (bottom)</label>
                            <input type="color" id="body_bg_gradient_end" name="body_bg_gradient_end"
                                   value="{{ old('body_bg_gradient_end', $settings['body_bg_gradient_end'] ?? $siteSettings->get('body_bg_gradient_end', '#f4f5f0')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        </div>
                        <div class="col-span-2 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center text-xs text-gray-400 font-medium" id="bodyBgPreview" style="min-height:44px; background: linear-gradient(180deg, {{ $settings['body_bg_color'] ?? '#fdfbf7' }}, {{ $settings['body_bg_gradient_end'] ?? '#f4f5f0' }});">
                            Body preview
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Surface, Border & Text Colors --}}
                <div>
                    <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                        Surface, Border &amp; Text Colors
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Surface (card / panel)</label>
                            <input type="color" name="surface_color"
                                   value="{{ old('surface_color', $settings['surface_color'] ?? $siteSettings->get('surface_color', '#ffffff')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Muted Surface (alt rows)</label>
                            <input type="color" name="muted_surface_color"
                                   value="{{ old('muted_surface_color', $settings['muted_surface_color'] ?? $siteSettings->get('muted_surface_color', '#F8FAFC')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Border Color</label>
                            <input type="color" name="border_color"
                                   value="{{ old('border_color', $settings['border_color'] ?? $siteSettings->get('border_color', '#E5E7EB')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Body Text Color</label>
                            <input type="color" name="text_color"
                                   value="{{ old('text_color', $settings['text_color'] ?? $siteSettings->get('text_color', '#111827')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Muted Text Color</label>
                            <input type="color" name="muted_text_color"
                                   value="{{ old('muted_text_color', $settings['muted_text_color'] ?? $siteSettings->get('muted_text_color', '#64748B')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Hero & CTA Gradients --}}
                <div>
                    <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                        Hero &amp; CTA Gradients
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Hero Start (uses Dark)</label>
                            <div class="h-11 rounded-xl border border-gray-200 flex items-center justify-center text-xs" style="background:{{ $siteSettings->get('dark_color','#0b2415') }};">
                                <span class="text-white/60">= Dark Color</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Hero Gradient End</label>
                            <input type="color" name="hero_gradient_end"
                                   value="{{ old('hero_gradient_end', $settings['hero_gradient_end'] ?? $siteSettings->get('hero_gradient_end', '#0f3d22')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                            <p class="text-[10px] text-gray-400 mt-1">Page hero banner gradient end</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">CTA Start (uses Primary)</label>
                            <div class="h-11 rounded-xl border border-gray-200 flex items-center justify-center text-xs" style="background:{{ $siteSettings->get('primary_color','#1a5632') }};">
                                <span class="text-white/60">= Primary Color</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">CTA Gradient End</label>
                            <input type="color" name="cta_gradient_end"
                                   value="{{ old('cta_gradient_end', $settings['cta_gradient_end'] ?? $siteSettings->get('cta_gradient_end', '#0b2415')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                            <p class="text-[10px] text-gray-400 mt-1">Call-to-action band gradient end</p>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Header / Notice Bar --}}
                <div>
                    <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        Notice Bar Gradient
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Bar Start (uses Primary)</label>
                            <div class="h-11 rounded-xl border border-gray-200 flex items-center justify-center text-xs text-gray-400" style="background:{{ $siteSettings->get('primary_color','#1a5632') }};">
                                <span class="text-white/60">= Primary Color</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Bar End Color</label>
                            <input type="color" id="header_gradient_end" name="header_gradient_end"
                                   value="{{ old('header_gradient_end', $settings['header_gradient_end'] ?? $siteSettings->get('header_gradient_end', '#0f3d22')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Notice Label BG</label>
                            <input type="color" id="notice_accent_color" name="notice_accent_color"
                                   value="{{ old('notice_accent_color', $settings['notice_accent_color'] ?? $siteSettings->get('notice_accent_color') ?: $siteSettings->get('secondary_color','#e2a024')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                            <p class="text-[10px] text-gray-400 mt-1">The "NOTICE" badge color</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Notice Bar Override</label>
                            <input type="color" id="notice_bg_color" name="notice_bg_color"
                                   value="{{ old('notice_bg_color', $settings['notice_bg_color'] ?? $siteSettings->get('notice_bg_color') ?: $siteSettings->get('primary_color','#1a5632')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                            <p class="text-[10px] text-gray-400 mt-1">Override bar start (blank = Primary)</p>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Footer --}}
                <div>
                    <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        Footer Gradient
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Start (uses Primary)</label>
                            <div class="h-11 rounded-xl border border-gray-200 flex items-center justify-center text-xs" style="background:{{ $siteSettings->get('primary_color','#1a5632') }};">
                                <span class="text-white/60">= Primary Color</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">End Color</label>
                            <input type="color" id="footer_gradient_end" name="footer_gradient_end"
                                   value="{{ old('footer_gradient_end', $settings['footer_gradient_end'] ?? $siteSettings->get('footer_gradient_end', '#0b2415')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        </div>
                        <div class="col-span-2 rounded-xl overflow-hidden border border-gray-200 flex items-center justify-center text-xs text-white/60 font-medium" id="footerGradPreview" style="min-height:44px; background: linear-gradient(135deg, {{ $settings['primary_color'] ?? '#1a5632' }}, {{ $settings['footer_gradient_end'] ?? '#0b2415' }});">
                            Footer gradient preview
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Admin Sidebar --}}
                <div>
                    <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Admin Sidebar Gradient
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Start (uses Dark)</label>
                            <div class="h-11 rounded-xl border border-gray-200 flex items-center justify-center text-xs" style="background:{{ $siteSettings->get('dark_color','#0b2415') }};">
                                <span class="text-white/60">= Dark Color</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">End Color</label>
                            <input type="color" id="sidebar_gradient_end" name="sidebar_gradient_end"
                                   value="{{ old('sidebar_gradient_end', $settings['sidebar_gradient_end'] ?? $siteSettings->get('sidebar_gradient_end', '#050f09')) }}"
                                   class="h-11 w-full rounded-xl border border-gray-300 bg-gray-50 p-1 cursor-pointer">
                        </div>
                        <div class="col-span-2 rounded-xl overflow-hidden border border-gray-200 flex items-center justify-center text-xs text-white/60 font-medium" id="sidebarGradPreview" style="min-height:44px; background: linear-gradient(180deg, {{ $settings['dark_color'] ?? '#0b2415' }}, {{ $settings['sidebar_gradient_end'] ?? '#050f09' }});">
                            Sidebar gradient preview
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Admission & Academic Settings Card --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-8 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-[#1a5632]/10 flex items-center justify-center text-[#1a5632]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-[#0b2415]">Admission & Academic Settings</h3>
                        <p class="text-xs text-gray-500">This updates the current admission banners across the public website.</p>
                    </div>
                </div>
            </div>
            
            <div class="p-8">
                <div class="max-w-md">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Active Admission Year <span class="text-red-500">*</span></label>
                    <input type="text" name="admission_year" 
                           value="{{ old('admission_year', $settings['admission_year'] ?? '2082 – 2083 B.S.') }}" 
                           required placeholder="e.g., 2082 – 2083 B.S." 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    <p class="text-xs text-gray-400 mt-2 font-medium">Displayed on the Admission page headers and forms.</p>
                </div>
            </div>
        </div>

        {{-- Contact Information Card --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-8 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-[#e2a024]/10 flex items-center justify-center text-[#c98d1f]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-[#0b2415]">Public Contact Information</h3>
                        <p class="text-xs text-gray-500">Details displayed on the contact page and footers.</p>
                    </div>
                </div>
            </div>
            
            <div class="p-8 grid sm:grid-cols-2 gap-8">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">School Phone Number <span class="text-red-500">*</span></label>
                    <input type="text" name="school_phone" 
                           value="{{ old('school_phone', $settings['school_phone'] ?? $siteSettings->get('school_phone')) }}" 
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Public Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="school_email" 
                           value="{{ old('school_email', $settings['school_email'] ?? $siteSettings->get('school_email')) }}" 
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Office Hours (quick card) <span class="text-red-500">*</span></label>
                    <input type="text" name="office_hours"
                           value="{{ old('office_hours', $settings['office_hours'] ?? $siteSettings->get('office_hours', 'Sun-Fri 9:00 AM - 5:00 PM')) }}"
                           required placeholder="Sun-Fri 9:00 AM - 5:00 PM"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    <p class="text-xs text-gray-400 mt-1">Shown on the contact card strip at the top of the contact page.</p>
                </div>
            </div>

            {{-- Map & Detailed Office Hours --}}
            <div class="border-t border-gray-100 px-8 py-6">
                <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-6">Map Location & Detailed Office Hours</p>
                <div class="grid sm:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Latitude</label>
                        <input type="text" name="map_latitude"
                               value="{{ old('map_latitude', $settings['map_latitude'] ?? $siteSettings->get('map_latitude', '29.2844')) }}"
                               placeholder="e.g. 29.2844"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Longitude</label>
                        <input type="text" name="map_longitude"
                               value="{{ old('map_longitude', $settings['map_longitude'] ?? $siteSettings->get('map_longitude', '81.0897')) }}"
                               placeholder="e.g. 81.0897"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Map Zoom <span class="text-gray-400 font-normal">(1–20)</span></label>
                        <input type="number" name="map_zoom" min="1" max="20"
                               value="{{ old('map_zoom', $settings['map_zoom'] ?? $siteSettings->get('map_zoom', '16')) }}"
                               placeholder="16"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>
                </div>
                <p class="text-xs text-gray-400 mb-6">
                    💡 Find your coordinates on <a href="https://maps.google.com" target="_blank" class="underline text-blue-500">Google Maps</a> → right-click the school pin → copy the lat/lng numbers.
                </p>
                <div class="grid sm:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Working Days Label</label>
                        <input type="text" name="office_hours_days"
                               value="{{ old('office_hours_days', $settings['office_hours_days'] ?? $siteSettings->get('office_hours_days', 'Sunday – Friday')) }}"
                               placeholder="Sunday – Friday"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Working Hours</label>
                        <input type="text" name="office_hours_time"
                               value="{{ old('office_hours_time', $settings['office_hours_time'] ?? $siteSettings->get('office_hours_time', '9:00 AM – 5:00 PM')) }}"
                               placeholder="9:00 AM – 5:00 PM"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Closed Label</label>
                        <input type="text" name="office_hours_closed"
                               value="{{ old('office_hours_closed', $settings['office_hours_closed'] ?? $siteSettings->get('office_hours_closed', 'Saturday & Public Holidays')) }}"
                               placeholder="Saturday & Public Holidays"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>
                </div>
            </div>
        </div>

        {{-- SEO & School Identity Card --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-8 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-[#0b2415]">SEO & School Identity</h3>
                        <p class="text-xs text-gray-500">These values are embedded in page titles, meta tags, and JSON-LD structured data. Change school name/address above and these will auto-generate — only override here if you want custom SEO text.</p>
                    </div>
                </div>
            </div>

            <div class="p-8 space-y-8">
                {{-- School Identity for Structured Data --}}
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-5">School Identity (Structured Data / JSON-LD)</p>
                    <div class="grid sm:grid-cols-2 gap-6">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Alternate / Short Name</label>
                            <input type="text" name="school_alternate_name"
                                   value="{{ old('school_alternate_name', $settings['school_alternate_name'] ?? $siteSettings->get('school_alternate_name')) }}"
                                   placeholder="e.g. Godawari College"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                            <p class="text-xs text-gray-400 mt-1">Used in Google's schema.org alternateName field.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Street Address</label>
                            <input type="text" name="school_street"
                                   value="{{ old('school_street', $settings['school_street'] ?? $siteSettings->get('school_street')) }}"
                                   placeholder="e.g. Street / Ward"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Locality (City/VDC)</label>
                            <input type="text" name="school_locality"
                                   value="{{ old('school_locality', $settings['school_locality'] ?? $siteSettings->get('school_locality')) }}"
                                   placeholder="e.g. Municipality / VDC"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Region / District</label>
                            <input type="text" name="school_region"
                                   value="{{ old('school_region', $settings['school_region'] ?? $siteSettings->get('school_region')) }}"
                                   placeholder="e.g. Doti"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Area Served</label>
                            <input type="text" name="school_area_served"
                                   value="{{ old('school_area_served', $settings['school_area_served'] ?? $siteSettings->get('school_area_served')) }}"
                                   placeholder="e.g. Doti"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Founding Year (A.D.)</label>
                            <input type="text" name="school_founding_date_ad"
                                   value="{{ old('school_founding_date_ad', $settings['school_founding_date_ad'] ?? $siteSettings->get('school_founding_date_ad')) }}"
                                   placeholder="e.g. 2005"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                            <p class="text-xs text-gray-400 mt-1">Google requires A.D. (Gregorian) year, not B.S.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Opening Hours (Schema format)</label>
                            <input type="text" name="school_hours_schema"
                                   value="{{ old('school_hours_schema', $settings['school_hours_schema'] ?? $siteSettings->get('school_hours_schema')) }}"
                                   placeholder="e.g. Su-Fr 09:00-17:00"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all font-mono">
                            <p class="text-xs text-gray-400 mt-1">Machine-readable format for JSON-LD. Days: Mo Tu We Th Fr Sa Su</p>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100">

                {{-- Default SEO Text --}}
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-5">Default SEO Text (used on pages without per-page SEO set)</p>
                    <div class="grid sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Default Title (English)</label>
                            <input type="text" name="seo_default_title_en"
                                   value="{{ old('seo_default_title_en', $settings['seo_default_title_en'] ?? $siteSettings->get('seo_default_title_en')) }}"
                                   placeholder="School Name | Location tagline"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Default Title (Nepali)</label>
                            <input type="text" name="seo_default_title_ne"
                                   value="{{ old('seo_default_title_ne', $settings['seo_default_title_ne'] ?? $siteSettings->get('seo_default_title_ne')) }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Default Description (English)</label>
                            <textarea name="seo_default_description_en" rows="2"
                                      placeholder="Short school description for search engines (150-160 chars)"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all resize-none">{{ old('seo_default_description_en', $settings['seo_default_description_en'] ?? $siteSettings->get('seo_default_description_en')) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Default Description (Nepali)</label>
                            <textarea name="seo_default_description_ne" rows="2"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all resize-none">{{ old('seo_default_description_ne', $settings['seo_default_description_ne'] ?? $siteSettings->get('seo_default_description_ne')) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Default Keywords (English)</label>
                            <input type="text" name="seo_default_keywords_en"
                                   value="{{ old('seo_default_keywords_en', $settings['seo_default_keywords_en'] ?? $siteSettings->get('seo_default_keywords_en')) }}"
                                   placeholder="comma, separated, keywords"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Default Keywords (Nepali)</label>
                            <input type="text" name="seo_default_keywords_ne"
                                   value="{{ old('seo_default_keywords_ne', $settings['seo_default_keywords_ne'] ?? $siteSettings->get('seo_default_keywords_ne')) }}"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Social Media Links Card --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gray-50 border-b border-gray-100 px-8 py-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-[#0b2415]">Social Media Links</h3>
                        <p class="text-xs text-gray-500">Leave blank to hide an icon. Filled links show as icons in the website footer.</p>
                    </div>
                </div>
            </div>
            <div class="p-8 grid sm:grid-cols-2 gap-6">
                @foreach([
                    'social_facebook'  => ['label' => 'Facebook',  'placeholder' => 'https://facebook.com/yourpage',    'color' => '#1877F2'],
                    'social_instagram' => ['label' => 'Instagram', 'placeholder' => 'https://instagram.com/yourhandle', 'color' => '#E1306C'],
                    'social_tiktok'    => ['label' => 'TikTok',    'placeholder' => 'https://tiktok.com/@yourhandle',   'color' => '#010101'],
                    'social_twitter'   => ['label' => 'X / Twitter','placeholder' => 'https://x.com/yourhandle',        'color' => '#000000'],
                    'social_whatsapp'  => ['label' => 'WhatsApp',  'placeholder' => 'https://wa.me/977XXXXXXXXXX',      'color' => '#25D366'],
                    'social_youtube'   => ['label' => 'YouTube',   'placeholder' => 'https://youtube.com/@yourchannel', 'color' => '#FF0000'],
                ] as $key => $social)
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">{{ $social['label'] }}</label>
                    <input type="url" name="{{ $key }}"
                           value="{{ old($key, $settings[$key] ?? $siteSettings->get($key)) }}"
                           placeholder="{{ $social['placeholder'] }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                </div>
                @endforeach
            </div>
        </div>

        @if(auth()->user()?->isSuperAdmin())
            {{-- Mail Settings Card --}}
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="bg-gray-50 border-b border-gray-100 px-8 py-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-18 8h18a2 2 0 002-2V8a2 2 0 00-2-2H3a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-[#0b2415]">Email Delivery Settings</h3>
                            <p class="text-xs text-gray-500">Super admin SMTP settings used for verification and other application emails.</p>
                        </div>
                    </div>
                </div>

                <div class="p-8 grid sm:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Mailer <span class="text-red-500">*</span></label>
                        @php($selectedMailer = old('mail_mailer', $settings['mail_mailer'] ?? env('MAIL_MAILER', 'log')))
                        <select name="mail_mailer" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                            <option value="smtp" @selected($selectedMailer === 'smtp')>SMTP - send real emails</option>
                            <option value="log" @selected($selectedMailer === 'log')>Log - local testing only</option>
                            <option value="array" @selected($selectedMailer === 'array')>Array - testing only</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-2 font-medium">Use SMTP when emails must reach inboxes.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">SMTP Host</label>
                        <input type="text" name="mail_host" value="{{ old('mail_host', $settings['mail_host'] ?? env('MAIL_HOST')) }}" placeholder="smtp.example.com" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">SMTP Port</label>
                        <input type="number" min="1" max="65535" name="mail_port" value="{{ old('mail_port', $settings['mail_port'] ?? env('MAIL_PORT', 587)) }}" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Encryption</label>
                        @php($selectedEncryption = old('mail_encryption', $settings['mail_encryption'] ?? env('MAIL_ENCRYPTION')))
                        <select name="mail_encryption" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                            <option value="" @selected(blank($selectedEncryption))>None</option>
                            <option value="tls" @selected($selectedEncryption === 'tls')>TLS</option>
                            <option value="ssl" @selected($selectedEncryption === 'ssl')>SSL</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">SMTP Username</label>
                        <input type="text" name="mail_username" value="{{ old('mail_username', $settings['mail_username'] ?? env('MAIL_USERNAME')) }}" autocomplete="off" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">SMTP Password</label>
                        <input type="password" name="mail_password" value="" autocomplete="new-password" placeholder="{{ !empty($settings['mail_password']) ? 'Saved - leave blank to keep current password' : 'Enter SMTP password' }}" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">From Email <span class="text-red-500">*</span></label>
                        <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address'] ?? env('MAIL_FROM_ADDRESS')) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">From Name <span class="text-red-500">*</span></label>
                        <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name'] ?? env('MAIL_FROM_NAME', config('app.name'))) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>
                </div>
            </div>
        @endif

        {{-- Sticky Save Bar --}}
        <div class="sticky bottom-0 z-30 -mx-4 px-4 py-3 bg-white/90 backdrop-blur border-t border-gray-200 shadow-lg flex items-center justify-between gap-4">
            <p class="text-sm text-gray-500 hidden sm:block">Scroll up to review all sections before saving.</p>
            <button type="submit" class="px-8 py-3 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] hover:shadow-lg transition-all text-sm flex items-center gap-2 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                Save All Settings
            </button>
        </div>
        
    </form>

    @if(auth()->user()?->isSuperAdmin())
        <form action="{{ route('admin.settings.test-mail') }}" method="POST" class="mt-8 bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
            @csrf
            <div class="grid sm:grid-cols-[1fr_auto] gap-4 items-end">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Send Test Email</label>
                    <input type="email" name="test_email" value="{{ old('test_email', auth()->user()->email) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    <p class="text-xs text-gray-400 mt-2 font-medium">Save settings first, then send a test email to confirm delivery.</p>
                </div>
                <button type="submit" class="px-6 py-3 bg-blue-700 text-white font-bold rounded-xl hover:bg-blue-800 transition-colors">
                    Send Test
                </button>
            </div>
        </form>
    @endif
</div>
@push('scripts')
<script>
    /* ── Live gradient preview ── */
    function updateBodyPreview() {
        const start = document.getElementById('body_bg_color')?.value || '#fdfbf7';
        const end   = document.getElementById('body_bg_gradient_end')?.value || '#f4f5f0';
        const el    = document.getElementById('bodyBgPreview');
        if (el) el.style.background = `linear-gradient(180deg, ${start}, ${end})`;
    }
    function updateFooterPreview() {
        const end = document.getElementById('footer_gradient_end')?.value || '#0b2415';
        const primary = document.querySelector('[name="primary_color"]')?.value || '#1a5632';
        const el  = document.getElementById('footerGradPreview');
        if (el) el.style.background = `linear-gradient(135deg, ${primary}, ${end})`;
    }
    function updateSidebarPreview() {
        const end  = document.getElementById('sidebar_gradient_end')?.value || '#050f09';
        const dark = document.querySelector('[name="dark_color"]')?.value || '#0b2415';
        const el   = document.getElementById('sidebarGradPreview');
        if (el) el.style.background = `linear-gradient(180deg, ${dark}, ${end})`;
    }

    document.getElementById('body_bg_color')?.addEventListener('input', updateBodyPreview);
    document.getElementById('body_bg_gradient_end')?.addEventListener('input', updateBodyPreview);
    document.getElementById('footer_gradient_end')?.addEventListener('input', updateFooterPreview);
    document.getElementById('sidebar_gradient_end')?.addEventListener('input', updateSidebarPreview);
    document.querySelector('[name="primary_color"]')?.addEventListener('input', updateFooterPreview);
    document.querySelector('[name="dark_color"]')?.addEventListener('input', updateSidebarPreview);

    /* ── Apply preset ── */
    function applyPreset(p) {
        const setVal = (name, val) => {
            const el = document.querySelector(`[name="${name}"]`);
            if (el && val) { el.value = val; el.dispatchEvent(new Event('input')); }
        };
        setVal('primary_color',        p.primary);
        setVal('primary_light_color',  p.primary_light);
        setVal('secondary_color',      p.secondary);
        setVal('dark_color',           p.dark);
        setVal('body_bg_color',        p.body_bg);
        setVal('body_bg_gradient_end', p.body_end);
        setVal('header_gradient_end',  p.header_end);
        setVal('footer_gradient_end',  p.footer_end);
        setVal('sidebar_gradient_end', p.sidebar_end);
        if (p.notice_bg)     setVal('notice_bg_color',     p.notice_bg);
        if (p.notice_accent) setVal('notice_accent_color', p.notice_accent);
        updateBodyPreview();
        updateFooterPreview();
        updateSidebarPreview();
    }
</script>
@endpush

@endsection
