<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Fetch Dynamic SEO from Database --}}
    @php
        // 1. Get the page identifier set in the child view, fallback to route name
        $seoPageName = View::getSection('seo_page_name') ?? Route::currentRouteName();
        
        // 2. Query the database
        $seo = \App\Models\SeoSetting::where('page_name', $seoPageName)->first();
        
        // 3. Define absolute fallbacks (SiteSettings override lang-file defaults)
        $defaultTitle    = $siteSettings->localized('seo_default_title',       __('site.seo.default_title'));
        $defaultDesc     = $siteSettings->localized('seo_default_description', __('site.seo.default_desc'));
        $defaultKeywords = $siteSettings->localized('seo_default_keywords',    __('site.seo.default_keywords'));
        $schoolName      = $siteSettings->localized('site_name',               __('site.school_name'));
        $schoolAddress   = $siteSettings->localized('site_address',            __('site.location'));
        $logoUrl         = $siteSettings->logoUrl();

        // 4. THE LOGIC: DB wins -> then @section() with auto school-name suffix -> then $default
        $sectionTitle    = View::getSection('title');
        $titleHasBrand   = $sectionTitle && str_contains(mb_strtolower($sectionTitle), mb_strtolower($schoolName));
        $finalTitle      = $seo->meta_title
            ?? ($sectionTitle ? rtrim($sectionTitle) . ($titleHasBrand ? '' : ' — ' . $schoolName) : null)
            ?? $defaultTitle;
        $finalDesc       = $seo->meta_description ?? View::getSection('meta_description') ?? $defaultDesc;
        $finalKeywords   = $seo->meta_keywords    ?? View::getSection('meta_keywords')    ?? $defaultKeywords;
        $canonicalRoot   = rtrim(config('app.env') === 'production' ? config('seo.canonical_url') : config('app.url'), '/');
        $canonicalPath   = request()->path() === '/' ? '' : '/'.ltrim(request()->path(), '/');
        $canonicalUrl    = $canonicalRoot.$canonicalPath;
        $socialProfiles  = collect(['social_facebook', 'social_instagram', 'social_tiktok', 'social_twitter', 'social_youtube'])
            ->map(fn ($key) => $siteSettings->get($key))
            ->filter()
            ->values()
            ->all();
        $robotsDirective = View::getSection('robots') ?: 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';
    @endphp

    {{-- Primary SEO Meta Tags --}}
    <title>{{ $finalTitle }}</title>
    <meta name="description" content="{{ $finalDesc }}">
    <meta name="keywords" content="{{ $finalKeywords }}">
    <meta name="robots" content="{{ $robotsDirective }}">
    <meta name="author" content="{{ $schoolName }}">
    <meta name="geo.region" content="NP-P1">
    <meta name="geo.placename" content="{{ $schoolAddress }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ $canonicalRoot }}/sitemap.xml">

    {{-- Open Graph / Social Media Meta Tags --}}
    <meta property="og:title" content="{{ $finalTitle }}">
    <meta property="og:description" content="{{ $finalDesc }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="@yield('og_image', $logoUrl)">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="{{ $schoolName }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'ne' ? 'ne_NP' : 'en_US' }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $finalTitle }}">
    <meta name="twitter:description" content="{{ $finalDesc }}">
    <meta name="twitter:image" content="@yield('og_image', $logoUrl)">

    {{-- Organization + WebSite Structured Data (JSON-LD) --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": ["CollegeOrUniversity", "LocalBusiness"],
                "@id": "{{ $canonicalRoot }}/#organization",
                "name": @json($schoolName),
                "alternateName": @json($siteSettings->get('school_alternate_name', $schoolName)),
                "url": "{{ $canonicalRoot }}",
                "logo": {
                    "@type": "ImageObject",
                    "url": "{{ $logoUrl }}",
                    "width": 200,
                    "height": 200
                },
                "description": @json($defaultDesc),
                "foundingDate": "{{ $siteSettings->get('school_founding_date_ad', '2005') }}",
                "address": {
                    "@type": "PostalAddress",
                    "streetAddress": @json($siteSettings->get('school_street', '')),
                    "addressLocality": @json($siteSettings->get('school_locality', '')),
                    "addressRegion": @json($siteSettings->get('school_region', '')),
                    "postalCode": "56705",
                    "addressCountry": "NP"
                },
                "email": @json($siteSettings->get('school_email')),
                "telephone": @json($siteSettings->get('school_phone')),
                "sameAs": @json($socialProfiles),
                "geo": {
                    "@type": "GeoCoordinates",
                    "latitude": @json($siteSettings->get('map_latitude')),
                    "longitude": @json($siteSettings->get('map_longitude'))
                },
                "hasMap": "https://maps.google.com/?q={{ $siteSettings->get('map_latitude') }},{{ $siteSettings->get('map_longitude') }}",
                "openingHours": "{{ $siteSettings->get('school_hours_schema', 'Su-Fr 09:00-17:00') }}",
                "areaServed": {
                    "@type": "AdministrativeArea",
                    "name": @json($siteSettings->get('school_area_served', 'Koshi Province'))
                }
            },
            {
                "@type": "WebSite",
                "@id": "{{ $canonicalRoot }}/#website",
                "url": "{{ $canonicalRoot }}",
                "name": @json($schoolName),
                "description": @json($defaultDesc),
                "publisher": {
                    "@id": "{{ $canonicalRoot }}/#organization"
                },
                "inLanguage": "{{ app()->getLocale() === 'ne' ? 'ne-NP' : 'en-US' }}"
            },
            {
                "@type": "WebPage",
                "@id": "{{ $canonicalUrl }}/#webpage",
                "url": "{{ $canonicalUrl }}",
                "name": @json($finalTitle),
                "description": @json($finalDesc),
                "isPartOf": { "@id": "{{ $canonicalRoot }}/#website" },
                "publisher": { "@id": "{{ $canonicalRoot }}/#organization" },
                "inLanguage": "{{ app()->getLocale() === 'ne' ? 'ne-NP' : 'en-US' }}"@if(isset($page) && $page?->updated_at),
                "dateModified": @json($page->updated_at->toIso8601String())@endif
            }
        ]
    }
    </script>

    {{-- Dynamic Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ $siteSettings->faviconUrl() }}">
    <link rel="shortcut icon" href="{{ $siteSettings->faviconUrl() }}">
    <link rel="apple-touch-icon" href="{{ $siteSettings->faviconUrl() }}">

    {{-- Fonts (Global variables for Playfair Display and DM Sans) — loaded async so the third-party
         round trip doesn't block first paint; the page-loader splash covers this gap visually. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @php $googleFontsHref = 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,700;1,400&family=Inter:wght@400;500;600;700;800&family=Lora:wght@500;600;700&family=Merriweather:wght@700;900&family=Noto+Sans+Devanagari:wght@400;500;600;700;800&family=Playfair+Display:wght@700;900&family=Poppins:wght@400;500;600;700;800&display=swap'; @endphp
    <link rel="preload" as="style" href="{{ $googleFontsHref }}">
    <link rel="stylesheet" href="{{ $googleFontsHref }}" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="{{ $googleFontsHref }}"></noscript>
    @php
        $themePrimary       = $siteSettings->get('primary_color',         '#1a5632');
        $themeSecondary     = $siteSettings->get('secondary_color',       '#e2a024');
        $themeDark          = $siteSettings->get('dark_color',            '#0b2415');
        $themePrimaryLight  = $siteSettings->get('primary_light_color',   '#237042');
        $themeBodyBg        = $siteSettings->get('body_bg_color',         '#fdfbf7');
        $themeBodyGradEnd   = $siteSettings->get('body_bg_gradient_end',  '#f4f5f0');
        $themeSurface       = $siteSettings->get('surface_color',         '#ffffff');
        $themeMutedSurface  = $siteSettings->get('muted_surface_color',   '#F8FAFC');
        $themeBorder        = $siteSettings->get('border_color',          '#E5E7EB');
        $themeText          = $siteSettings->get('text_color',            '#111827');
        $themeMutedText     = $siteSettings->get('muted_text_color',      '#64748B');
        $themeHeaderGradEnd = $siteSettings->get('header_gradient_end',   '#0f3d22');
        $themeHeroGradEnd   = $siteSettings->get('hero_gradient_end',     '') ?: $themeHeaderGradEnd;
        $themeCtaGradEnd    = $siteSettings->get('cta_gradient_end',      '') ?: $themeDark;
        $themeFooterGradEnd = $siteSettings->get('footer_gradient_end',   '#0b2415');
        $themeNoticeBg      = $themePrimary;
        $themeNoticeAccent  = $siteSettings->get('notice_accent_color',   '') ?: $themeSecondary;
        $themeSidebarEnd    = $siteSettings->get('sidebar_gradient_end',  '#050f09');
        $bodyGradient = ($themeBodyBg !== $themeBodyGradEnd)
            ? "linear-gradient(180deg, {$themeBodyBg} 0%, {$themeBodyGradEnd} 100%)"
            : 'none';
    @endphp
    <style>
        :root {
            --ff-head:                   {!! $siteSettings->fontFamily('heading_font', "'Playfair Display', Georgia, serif") !!};
            --ff-body:                   {!! $siteSettings->fontFamily('body_font', "'DM Sans', sans-serif") !!};
            --theme-primary:             {{ $themePrimary }};
            --theme-primary-light:       {{ $themePrimaryLight }};
            --theme-secondary:           {{ $themeSecondary }};
            --theme-secondary-light:     #f4b63e;
            --theme-dark:                {{ $themeDark }};
            --theme-body-bg:             {{ $themeBodyBg }};
            --theme-body-gradient:       {{ $bodyGradient }};
            --theme-surface:             {{ $themeSurface }};
            --theme-muted-surface:       {{ $themeMutedSurface }};
            --theme-border:              {{ $themeBorder }};
            --theme-text:                {{ $themeText }};
            --theme-muted-text:          {{ $themeMutedText }};
            --theme-primary-soft:        color-mix(in srgb, var(--theme-primary) 10%, white);
            --theme-secondary-soft:      color-mix(in srgb, var(--theme-secondary) 16%, white);
            --theme-header-gradient-end: {{ $themeHeaderGradEnd }};
            --theme-hero-gradient-end:   {{ $themeHeroGradEnd }};
            --theme-cta-gradient-end:    {{ $themeCtaGradEnd }};
            --theme-footer-gradient-end: {{ $themeFooterGradEnd }};
            --theme-notice-bg:           {{ $themeNoticeBg }};
            --theme-notice-accent:       {{ $themeNoticeAccent }};
            --theme-notice-gradient:     linear-gradient(90deg, {{ $themeNoticeBg }} 0%, {{ $themeHeaderGradEnd }} 100%);
            --theme-hero-gradient:       linear-gradient(135deg, {{ $themeDark }} 0%, {{ $themePrimary }} 58%, {{ $themeHeroGradEnd }} 100%);
            --theme-cta-gradient:        linear-gradient(135deg, {{ $themePrimary }} 0%, {{ $themeCtaGradEnd }} 100%);
            --theme-footer-gradient:     linear-gradient(135deg, {{ $themePrimary }} 0%, {{ $themeFooterGradEnd }} 100%);
            --theme-sidebar-bg:          {{ $themeDark }};
            --theme-sidebar-gradient-end:{{ $themeSidebarEnd }};
        }
        html, body { font-family: var(--ff-body); color: #111827; overflow-x: hidden; max-width: 100%; }
        h1, h2, h3, h4, h5, h6, .theme-heading { font-family: var(--ff-head); }
        body {
            background-color: #fff;
            background-image: none;
            background-attachment: scroll;
        }

        /* ── Semantic theme colour helpers ── */
        .theme-bg-primary        { background-color: var(--theme-primary)       !important; }
        .theme-bg-secondary      { background-color: var(--theme-secondary)     !important; }
        .theme-bg-dark           { background-color: var(--theme-dark)          !important; }
        .theme-bg-surface        { background-color: var(--theme-surface)       !important; }
        .theme-bg-muted          { background-color: var(--theme-muted-surface) !important; }
        .theme-text-primary      { color: var(--theme-primary)                  !important; }
        .theme-text-secondary    { color: var(--theme-secondary)                !important; }
        .theme-text-dark         { color: var(--theme-dark)                     !important; }
        .theme-text-body         { color: var(--theme-text)                     !important; }
        .theme-text-muted        { color: var(--theme-muted-text)               !important; }
        .theme-border-primary    { border-color: var(--theme-primary)           !important; }
        .theme-border-secondary  { border-color: var(--theme-secondary)         !important; }
        .theme-border            { border-color: var(--theme-border)            !important; }

        /* ── Gradient helpers ── */
        .theme-gradient-primary  { background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-primary-light) 100%) !important; }
        .theme-gradient-header   { background: var(--theme-notice-gradient)  !important; }
        .theme-gradient-hero     { background: var(--theme-hero-gradient)    !important; }
        .theme-gradient-cta      { background: var(--theme-cta-gradient)     !important; }
        .theme-gradient-footer   { background: var(--theme-footer-gradient)  !important; }
        .theme-gradient-accent   { background: linear-gradient(135deg, var(--theme-secondary) 0%, var(--theme-secondary-light) 100%) !important; }
        .theme-gradient-sidebar  { background: linear-gradient(180deg, var(--theme-sidebar-bg) 0%, var(--theme-sidebar-gradient-end) 100%) !important; }

        /* ── Shared public page sections ── */
        .theme-page-hero {
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(135deg, var(--theme-dark) 0%, var(--theme-primary) 62%, var(--theme-hero-gradient-end) 100%) !important;
            color: #fff;
        }
        .theme-page-hero > .absolute { display: none !important; }
        .theme-page-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(255,255,255,.07) 1px, transparent 1px),
                linear-gradient(180deg, rgba(255,255,255,.07) 1px, transparent 1px);
            background-size: 46px 46px;
            opacity: .35;
            pointer-events: none;
        }
        .theme-page-hero::after {
            content: '';
            position: absolute;
            inset: auto 0 0;
            height: 5px;
            background: linear-gradient(90deg, var(--theme-secondary), color-mix(in srgb, var(--theme-secondary) 45%, transparent));
            pointer-events: none;
        }
        .theme-page-hero > * { position: relative; z-index: 2; }
        .theme-page-hero h1,
        .theme-page-hero .theme-hero-title { color: #fff !important; }
        .theme-page-hero p,
        .theme-page-hero .theme-hero-subtitle { color: rgba(255,255,255,.82) !important; }
        .theme-page-hero nav,
        .theme-page-hero .theme-hero-breadcrumb { color: rgba(255,255,255,.72) !important; }
        .theme-page-hero nav a,
        .theme-page-hero .theme-hero-breadcrumb a { color: rgba(255,255,255,.78) !important; }
        .theme-page-hero nav a:hover,
        .theme-page-hero .theme-hero-breadcrumb a:hover { color: var(--theme-secondary) !important; }
        .theme-badge-accent {
            background: var(--theme-secondary) !important;
            color: var(--theme-dark) !important;
            box-shadow: 0 12px 28px color-mix(in srgb, var(--theme-secondary) 28%, transparent);
        }
        .theme-section-eyebrow {
            color: var(--theme-secondary) !important;
            font-weight: 900;
            letter-spacing: .18em;
            text-transform: uppercase;
        }
        .theme-section-eyebrow::before {
            content: '';
            display: inline-block;
            width: 2rem;
            height: 2px;
            border-radius: 999px;
            background: var(--theme-secondary);
            margin-right: .75rem;
            vertical-align: middle;
        }
        .theme-cta-band {
            position: relative;
            overflow: hidden;
            background: var(--theme-cta-gradient) !important;
            color: #fff;
        }
        .theme-cta-band::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,.14) 1px, transparent 1.2px);
            background-size: 28px 28px;
            opacity: .22;
            pointer-events: none;
        }
        .theme-cta-band > * { position: relative; z-index: 1; }

        /* ── Hardcoded colour overrides so admin settings always win ── */
        .bg-\[\#1a5632\]               { background-color: var(--theme-primary)   !important; }
        .bg-\[\#e2a024\]               { background-color: var(--theme-secondary) !important; }
        .bg-\[\#0b2415\]               { background-color: var(--theme-dark)      !important; }
        .bg-\[\#fdfbf7\]               { background-color: #fff                  !important; }
        .bg-\[\#f7faf4\]               { background-color: #fff                  !important; }
        .bg-\[\#f4f5f0\]               { background-color: #fff                  !important; }
        .text-\[\#1a5632\]             { color: var(--theme-primary)              !important; }
        .text-\[\#e2a024\]             { color: var(--theme-secondary)            !important; }
        .text-\[\#0b2415\]             { color: var(--theme-dark)                 !important; }
        .border-\[\#1a5632\]           { border-color: var(--theme-primary)       !important; }
        .border-\[\#e2a024\]           { border-color: var(--theme-secondary)     !important; }
        .border-\[\#0b2415\]           { border-color: var(--theme-dark)          !important; }
        .border-\[\#dfe8dc\]           { border-color: var(--theme-border)        !important; }
        .hover\:bg-\[\#1a5632\]:hover  { background-color: var(--theme-primary)   !important; }
        .hover\:bg-\[\#e2a024\]:hover  { background-color: var(--theme-secondary) !important; }
        .hover\:bg-\[\#0b2415\]:hover  { background-color: var(--theme-dark)      !important; }
        .hover\:text-\[\#1a5632\]:hover{ color: var(--theme-primary)              !important; }
        .hover\:text-\[\#e2a024\]:hover{ color: var(--theme-secondary)            !important; }
        .from-\[\#1a5632\] {
            --tw-gradient-from: var(--theme-primary) var(--tw-gradient-from-position) !important;
            --tw-gradient-to: color-mix(in srgb, var(--theme-primary) 0%, transparent) var(--tw-gradient-to-position) !important;
            --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;
        }
        .from-\[\#0b2415\] {
            --tw-gradient-from: var(--theme-dark) var(--tw-gradient-from-position) !important;
            --tw-gradient-to: color-mix(in srgb, var(--theme-dark) 0%, transparent) var(--tw-gradient-to-position) !important;
            --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;
        }
        .from-\[\#e2a024\] {
            --tw-gradient-from: var(--theme-secondary) var(--tw-gradient-from-position) !important;
            --tw-gradient-to: color-mix(in srgb, var(--theme-secondary) 0%, transparent) var(--tw-gradient-to-position) !important;
            --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;
        }
        .via-\[\#1a5632\] { --tw-gradient-to: color-mix(in srgb, var(--theme-primary) 0%, transparent) var(--tw-gradient-to-position) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--theme-primary) var(--tw-gradient-via-position), var(--tw-gradient-to) !important; }
        .via-\[\#0b2415\] { --tw-gradient-to: color-mix(in srgb, var(--theme-dark) 0%, transparent) var(--tw-gradient-to-position) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--theme-dark) var(--tw-gradient-via-position), var(--tw-gradient-to) !important; }
        .to-\[\#1a5632\] { --tw-gradient-to: var(--theme-primary) var(--tw-gradient-to-position) !important; }
        .to-\[\#0b2415\] { --tw-gradient-to: var(--theme-dark) var(--tw-gradient-to-position) !important; }
        .to-\[\#e2a024\] { --tw-gradient-to: var(--theme-secondary) var(--tw-gradient-to-position) !important; }
        .focus\:ring-\[\#1a5632\]:focus { --tw-ring-color: color-mix(in srgb, var(--theme-primary) 20%, transparent) !important; }
        .focus\:border-\[\#1a5632\]:focus { border-color: var(--theme-primary) !important; }

        /* ── Inner-page hero gradient fix ── */
        /* Tailwind v4 changed gradient internals; from/via/to class overrides no longer work
           reliably — force the theme gradient directly on any section with the school's pattern */
        .bg-gradient-to-br.from-\[\#0b2415\],
        .bg-linear-to-br.from-\[\#0b2415\],
        .bg-gradient-to-br.from-\[\#1a5632\],
        .bg-linear-to-br.from-\[\#1a5632\] {
            background: var(--theme-hero-gradient) !important;
        }
        /* Solid dark for single-color hero backgrounds */
        section.bg-\[\#0b2415\] { background-color: var(--theme-dark) !important; }

        /* ── Inner-page hero sections — uniform text regardless of Tailwind green shade ── */
        /* All page heroes use dark gradient bg; these make breadcrumbs/subtitles safe white */
        .text-green-200  { color: rgba(255,255,255,.78) !important; }
        .text-green-100\/90 { color: rgba(255,255,255,.9) !important; }
        .text-green-100\/85 { color: rgba(255,255,255,.85) !important; }
        .text-green-100\/80 { color: rgba(255,255,255,.8) !important; }
        .text-green-100\/70 { color: rgba(255,255,255,.7) !important; }
        /* Ensure headings inside dark-bg sections always inherit white from parent */
        [class*="bg-[#0b2415]"] h1,[class*="bg-[#0b2415]"] h2,[class*="bg-[#0b2415]"] h3,
        [class*="bg-[#1a5632]"] h1,[class*="bg-[#1a5632]"] h2,[class*="bg-[#1a5632]"] h3 { color: #fff; }
        /* Headings on light sections — always safe dark */
        .bg-white h1:not(.text-white):not([class*="text-["]):not([style*="color"]),
        .bg-white h2:not(.text-white):not([class*="text-["]):not([style*="color"]),
        .bg-white h3:not(.text-white):not([class*="text-["]):not([style*="color"]) { color: #111827; }

        /* ── Mobile ── */
        @media (max-width: 640px) { html { font-size: 15px; } }

        /* ── Selection ── */
        ::selection      { background-color: var(--theme-primary); color: #fff; }
        ::-moz-selection { background-color: var(--theme-primary); color: #fff; }
    </style>

    {{-- Tailwind & Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- Alpine.js (Loaded Once for Navbars/Galleries) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    {{-- Optional Page-Specific Schema markup or styles --}}
    @yield('schema')
    @stack('styles')
</head>

{{-- Added overflow-x-hidden and w-full to prevent horizontal scrolling bugs --}}
<body class="flex flex-col min-h-dvh overflow-x-hidden w-full relative">
    
    {{-- Page Loader --}}
    @include('components.loader')
    
    {{-- Main Navigation --}}
    @include('partials.header')

    {{-- Main Content Injection — offset equals fixed header: mobile(56+30=86px), sm+(80+30=110px) --}}
    <main class="flex-grow w-full flex flex-col relative z-0 mt-21.5 sm:mt-27.5">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('partials.footer')

    {{-- Body-level modals (rendered outside <main> so fixed z-index works above header) --}}
    @stack('modals')

    {{-- Page-Specific Scripts --}}
    @stack('scripts')
</body>
</html>
