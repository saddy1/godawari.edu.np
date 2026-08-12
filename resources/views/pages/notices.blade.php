{{-- resources/views/pages/notices.blade.php --}}
@extends('layouts.app')

@section('title', __('site.notices.page_title'))
@section('meta_description', __('site.notices.meta_desc'))

@section('content')

<style>
    .notices-page {
        max-width: 100%;
        overflow-x: clip;
    }
    .notices-page * {
        min-width: 0;
    }
    .notices-safe-text {
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    /* ── Filter pills ── */
    .filter-pill {
        display: inline-flex;
        max-width: 100%;
        border-radius: 999px;
        border: 1.5px solid var(--theme-border);
        background: var(--theme-surface);
        padding: .375rem .875rem;
        font-size: .75rem;
        font-weight: 900;
        color: var(--theme-muted-text);
        transition: border-color .16s, color .16s, background .16s;
        text-decoration: none;
    }
    .filter-pill:hover, .filter-pill.is-active {
        border-color: var(--theme-primary);
        background: var(--theme-primary);
        color: #fff;
    }
    /* ── Notice cards ── */
    .notice-card {
        display: flex;
        flex-direction: column;
        min-height: 12rem;
        border-radius: .875rem;
        border: 1.5px solid var(--theme-border);
        background: var(--theme-surface);
        padding: 1.25rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.04);
        transition: border-color .2s, transform .2s, box-shadow .2s;
    }
    .notice-card:hover {
        border-color: color-mix(in srgb, var(--theme-primary) 40%, transparent);
        transform: translateY(-2px);
        box-shadow: 0 6px 22px color-mix(in srgb, var(--theme-primary) 9%, transparent);
    }
    .notice-card:hover .notice-title { color: var(--theme-primary); }
    /* ── Read more link ── */
    .read-more-link {
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        font-size: .8rem;
        font-weight: 900;
        color: var(--theme-primary);
        text-decoration: none;
        transition: gap .18s;
    }
    .notice-card:hover .read-more-link { gap: .6rem; }
    @media (max-width: 640px) {
        .notices-hero-title {
            font-size: clamp(2rem, 12vw, 3rem);
            line-height: 1.05;
        }
        .notices-hero-subtitle {
            font-size: clamp(.98rem, 5.3vw, 1.18rem);
            line-height: 1.55;
        }
        .notices-hero-badge {
            max-width: 100%;
            white-space: normal;
            text-align: center;
            letter-spacing: .12em;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .filter-pill {
            flex: 1 1 auto;
            justify-content: center;
            padding: .55rem .85rem;
            font-size: .72rem;
        }
        .notice-card {
            padding: 1rem;
        }
        .read-more-link {
            font-size: .74rem;
            white-space: nowrap;
        }
        .notices-pagination-wrap nav,
        .notices-pagination-wrap > div {
            max-width: 100%;
        }
        .notices-pagination-wrap svg {
            max-width: 1rem;
        }
    }
</style>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- HERO --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="notices-page theme-page-hero py-12 sm:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <nav class="theme-hero-breadcrumb notices-safe-text mb-6 flex flex-wrap items-center gap-2 text-sm font-semibold" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">{{ __('site.common.home') }}</a>
            <svg class="h-3.5 w-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
            <span style="color:#fff;">{{ __('site.notices.breadcrumb') }}</span>
        </nav>

        <div class="flex max-w-full flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0 max-w-full">
                <span class="notices-hero-badge theme-badge-accent inline-flex items-center rounded-full px-4 py-1.5 text-xs font-black uppercase tracking-widest mb-5">
                    {{ __('site.notices.badge') }}
                </span>
                <h1 class="notices-hero-title notices-safe-text theme-hero-title text-4xl font-black tracking-tight sm:text-5xl mb-4 max-w-2xl">
                    {{ __('site.notices.hero_h1') }}
                </h1>
                <p class="notices-hero-subtitle notices-safe-text theme-hero-subtitle max-w-xl text-base leading-7">
                    {{ __('site.notices.hero_sub', ['school_name' => $siteSettings->localized('site_name', config('app.name'))]) }}
                </p>
            </div>

            {{-- Total count chip --}}
            <div class="w-full max-w-full shrink-0 rounded-2xl border border-white/20 bg-white/10 px-5 py-4 backdrop-blur-sm sm:w-auto sm:px-6">
                <p class="text-xs font-black uppercase tracking-widest text-white/60">Total notices</p>
                <p class="mt-1 text-4xl font-black text-white leading-none">{{ $notices->total() }}</p>
                <p class="mt-1 text-sm font-semibold text-white/70">{{ Str::plural('published notice', $notices->total()) }}</p>
            </div>
        </div>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- FILTER + CARDS --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="notices-page pb-16 sm:pb-20" style="background: var(--theme-body-bg);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Filter bar (overlaps hero) --}}
        <div class="mb-8 -translate-y-6 rounded-2xl border p-4 shadow-sm sm:p-5"
             style="background: var(--theme-surface); border-color: var(--theme-border);">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="theme-section-eyebrow text-xs mb-1.5">{{ __('site.common.filter_by') }}</p>
                    <h2 class="text-xl font-black" style="color: var(--theme-text);">Official notice board</h2>
                </div>

                <div class="flex max-w-full flex-wrap gap-2">
                    @foreach(collect(['All'])->merge($noticeCategories ?? collect()) as $key)
                        <a href="{{ route('notices', ['category' => $key]) }}"
                           class="filter-pill {{ $category === $key ? 'is-active' : '' }}">
                            <span class="notices-safe-text">{{ $key === 'All' ? __('site.notices.filter_all') : $key }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Cards --}}
        @if($notices->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($notices as $notice)
                    @php
                        $nDate = \Carbon\Carbon::parse($notice->created_at);
                        $catColor = match($notice->category) {
                            'Admission' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'Academic'  => 'bg-sky-50 text-sky-700 border-sky-200',
                            'Event'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'Result'    => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            default     => null,
                        };
                    @endphp

                    <article class="notice-card">
                        <div class="flex items-start justify-between gap-4">
                            {{-- Category badge --}}
                            @if($catColor)
                                <span class="rounded-full border px-3 py-1 text-xs font-black {{ $catColor }}">
                                    {{ $notice->category }}
                                </span>
                            @else
                                <span class="rounded-full px-3 py-1 text-xs font-black"
                                      style="background: var(--theme-primary-soft); color: var(--theme-primary);">
                                    {{ $notice->category ?? 'General' }}
                                </span>
                            @endif

                            {{-- Date --}}
                            <time class="shrink-0 text-right text-xs font-bold" style="color: var(--theme-muted-text);">
                                <span class="block text-xl font-black leading-none" style="color: var(--theme-text);">{{ $nDate->format('d') }}</span>
                                {{ $nDate->format('M Y') }}
                            </time>
                        </div>

                        <a href="{{ route('news.show', $notice->slug) }}" class="mt-4 block">
                            <h2 class="notice-title notices-safe-text line-clamp-2 text-lg font-black leading-6 transition-colors" style="color: var(--theme-text);">
                                {{ $notice->title }}
                            </h2>
                        </a>

                        <p class="notices-safe-text mt-3 line-clamp-2 text-sm leading-6" style="color: var(--theme-muted-text);">
                            {{ $notice->excerpt ?? Str::limit(strip_tags($notice->content), 130) }}
                        </p>

                        <div class="mt-auto flex items-center justify-between border-t pt-4"
                             style="border-color: var(--theme-border);">
                            <span class="text-xs font-bold" style="color: var(--theme-muted-text);">
                                {{ $notice->created_at->diffForHumans() }}
                            </span>
                            <a href="{{ route('news.show', $notice->slug) }}" class="read-more-link">
                                {{ __('site.news.read_more') }}
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed py-16 text-center" style="border-color: var(--theme-border);">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full"
                     style="background: var(--theme-primary-soft);">
                    <svg class="h-6 w-6" style="color: var(--theme-primary);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <h3 class="text-xl font-black" style="color: var(--theme-text);">{{ __('site.notices.empty_title') }}</h3>
                <p class="mt-2 text-sm font-semibold" style="color: var(--theme-muted-text);">{{ __('site.notices.empty_sub') }}</p>
            </div>
        @endif

        {{-- Pagination --}}
        @if($notices->hasPages())
            <div class="mt-8 rounded-2xl border p-4 shadow-sm"
                 style="background: var(--theme-surface); border-color: var(--theme-border);">
                <div class="flex flex-col items-center justify-between gap-4 lg:flex-row">
                    <p class="text-sm font-semibold" style="color: var(--theme-muted-text);">
                        Showing {{ $notices->firstItem() }}–{{ $notices->lastItem() }} of {{ $notices->total() }} notices
                    </p>
                    <div class="notices-pagination-wrap w-full max-w-full overflow-x-auto lg:w-auto">
                        {{ $notices->links('pagination::tailwind') }}
                    </div>
                </div>
            </div>
        @endif

    </div>
</section>

@endsection
