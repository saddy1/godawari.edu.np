{{-- resources/views/partials/footer.blade.php --}}
@php
    $footerBg        = $siteSettings->get('primary_color',        '#1a5632');
    $footerGradEnd   = $siteSettings->get('footer_gradient_end',  '#0b2415');
    $footerAccent    = $siteSettings->get('secondary_color',      '#e2a024');
    $footerDark      = $siteSettings->get('dark_color',           '#0b2415');
    $footerGradient  = "linear-gradient(135deg, {$footerBg} 0%, {$footerGradEnd} 100%)";
    $quickLinks      = \App\Models\QuickLink::where('is_active', true)->orderBy('sort_order')->get();
    $vacancyModuleEnabled = \App\Services\ModuleService::enabled('vacancy');
    $footerNav = collect([
        ['label' => __('site.nav.home'),        'url' => url('/')],
        ['label' => __('site.nav.about_us'),    'url' => url('/about')],
        ['label' => __('site.nav.admissions'),  'url' => url('/admissions')],
        ['label' => __('site.nav.news_events'), 'url' => url('/news')],
        ['label' => __('site.nav.gallery'),     'url' => url('/gallery')],
        $vacancyModuleEnabled ? ['label' => __('site.nav.vacancies'),   'url' => route('vacancies')] : null,
        ['label' => __('site.nav.contact'),     'url' => url('/contact')],
    ])->filter()->values();

    $socials = [
        'social_facebook'  => ['label' => 'Facebook',   'hover' => '#1877F2',
            'icon' => '<path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047v-2.66c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.953h-1.514c-1.491 0-1.956.93-1.956 1.886v2.283h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/>'],
        'social_instagram' => ['label' => 'Instagram',  'hover' => '#E1306C',
            'icon' => '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>'],
        'social_tiktok'    => ['label' => 'TikTok',     'hover' => '#010101',
            'icon' => '<path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>'],
        'social_twitter'   => ['label' => 'X / Twitter','hover' => '#000000',
            'icon' => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>'],
        'social_whatsapp'  => ['label' => 'WhatsApp',   'hover' => '#25D366',
            'icon' => '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>'],
        'social_youtube'   => ['label' => 'YouTube',    'hover' => '#FF0000',
            'icon' => '<path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>'],
    ];
    $activeSocials = array_filter($socials, fn($k) => filled($siteSettings->get($k)), ARRAY_FILTER_USE_KEY);
@endphp

<style>
    .site-footer-redesign {
        --footer-accent: {{ $footerAccent }};
        --footer-primary: {{ $footerBg }};
        --footer-dark: {{ $footerDark }};
        background: #f8fafc;
        border-top: 1px solid rgba(15,23,42,.08);
    }
    .site-footer-redesign .footer-contact-card {
        background: #fff;
        border: 1px solid rgba(15,23,42,.08);
        box-shadow: 0 22px 48px rgba(15,23,42,.08);
    }
    .site-footer-redesign .footer-link {
        color: rgba(255,255,255,.72);
        display: inline-flex;
        width: fit-content;
        font-size: .92rem;
        font-weight: 800;
        line-height: 1.5;
        transition: color .15s, transform .15s;
        white-space: nowrap;
    }
    .site-footer-redesign .footer-link:hover {
        color: var(--footer-accent);
        transform: translateX(3px);
    }
    .site-footer-redesign .footer-heading {
        color: #fff;
        font-size: .78rem;
        font-weight: 950;
        letter-spacing: .18em;
        text-transform: uppercase;
    }
    .site-footer-redesign .footer-social {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.16);
        color: #fff;
        transition: background .2s, border-color .2s, transform .15s;
    }
    .site-footer-redesign .footer-social:hover {
        transform: translateY(-2px);
        border-color: rgba(255,255,255,.72);
    }
    .site-footer-redesign .footer-safe-text {
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    @media (max-width: 640px) {
        .site-footer-redesign .footer-contact-card {
            border-radius: 12px;
        }
        .site-footer-redesign .footer-main-grid {
            gap: 1.75rem;
        }
        .site-footer-redesign .footer-mobile-link-columns {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 1.25rem;
        }
        .site-footer-redesign .footer-mobile-link-columns .footer-heading {
            font-size: .68rem;
            letter-spacing: .16em;
            margin-bottom: .85rem;
        }
        .site-footer-redesign .footer-mobile-link-columns .footer-link {
            white-space: normal;
            font-size: .86rem;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }
        .site-footer-redesign .footer-mobile-link-columns .grid {
            gap: .55rem;
        }
        .site-footer-redesign .footer-mobile-brand-title {
            font-size: clamp(1rem, 5.2vw, 1.35rem);
            line-height: 1.18;
        }
        .site-footer-redesign .footer-mobile-card-label {
            font-size: .68rem;
            letter-spacing: .12em;
        }
        .site-footer-redesign .footer-mobile-card-value {
            font-size: clamp(.82rem, 4.1vw, .98rem);
            line-height: 1.25;
        }
    }
</style>

<footer class="site-footer-redesign">

    <div style="background:{{ $footerGradient }};">
        <div class="footer-main-grid max-w-7xl mx-auto grid gap-9 px-4 py-10 sm:px-6 lg:grid-cols-[1.15fr_.85fr_.85fr_1fr] lg:px-8 lg:py-12">
            <div>
                <p class="footer-heading">{{ $siteSettings->localized('site_name', __('site.school_name')) }}</p>
                <p class="mt-4 max-w-sm text-sm font-semibold leading-7 text-white/70">{{ __('site.footer.about_text') }}</p>
                @if(count($activeSocials))
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        @foreach($activeSocials as $key => $s)
                            <a href="{{ $siteSettings->get($key) }}" target="_blank" rel="noopener" aria-label="{{ $s['label'] }}"
                               class="footer-social"
                               onmouseenter="this.style.background='{{ $s['hover'] }}'; this.style.borderColor='{{ $s['hover'] }}'"
                               onmouseleave="this.style.background='rgba(255,255,255,.08)'; this.style.borderColor='rgba(255,255,255,.16)'">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">{!! $s['icon'] !!}</svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="footer-mobile-link-columns contents lg:contents">
                <nav>
                    <p class="footer-heading mb-4">{{ __('site.footer.quick_links') }}</p>
                    <div class="grid gap-2">
                        @foreach($footerNav as $item)
                            <a href="{{ $item['url'] }}" class="footer-link">{{ $item['label'] }}</a>
                        @endforeach
                    </div>
                </nav>

                @if($quickLinks->isNotEmpty())
                    <div>
                        <p class="footer-heading mb-4">Resources</p>
                        <div class="grid gap-2">
                            @foreach($quickLinks as $ql)
                                <a href="{{ $ql->url }}"
                                   @if($ql->open_in_new_tab) target="_blank" rel="noopener" @endif
                                   class="footer-link">{{ $ql->localizedTitle() }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div>
                        <p class="footer-heading mb-4">{{ __('site.nav.portals') }}</p>
                        <div class="grid gap-2">
                            <a href="{{ route('login') }}" class="footer-link">{{ __('site.nav.staff_portal') }}</a>
                            <a href="/student/card/login" class="footer-link">{{ __('site.nav.student_portal') }}</a>
                            @if($vacancyModuleEnabled)
                            <a href="{{ route('applicant.login') }}" class="footer-link">{{ __('site.nav.applicant_login') }}</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div>
                <p class="footer-heading mb-4">{{ __('site.nav.portals') }}</p>
                <div class="grid gap-3">
                    <a href="{{ route('login') }}" class="rounded-lg border border-white/10 bg-white/10 px-4 py-3 text-sm font-black text-white hover:bg-white/20 transition">{{ __('site.nav.staff_portal') }}</a>
                    <a href="/student/card/login" class="rounded-lg border border-white/10 bg-white/10 px-4 py-3 text-sm font-black text-white hover:bg-white/20 transition">{{ __('site.nav.student_portal') }}</a>
                    @if($vacancyModuleEnabled)
                    <a href="{{ route('applicant.login') }}" class="rounded-lg border border-white/10 bg-white/10 px-4 py-3 text-sm font-black text-white hover:bg-white/20 transition">{{ __('site.nav.applicant_login') }}</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto flex flex-col items-center justify-between gap-3 px-4 py-4 text-center text-sm font-bold text-white/58 sm:px-6 lg:flex-row lg:px-8 lg:text-left">
                <p>&copy; {{ date('Y') }} {{ $siteSettings->localized('site_name', __('site.school_name')) }}. {{ __('site.footer.all_rights') }}</p>
                <span>{{ __('site.footer.designed') }} <a href="#" target="_blank" style="color:{{ $footerAccent }};" class="font-black hover:underline">Broad Tech Infosys</a></span>
            </div>
        </div>
    </div>

</footer>
