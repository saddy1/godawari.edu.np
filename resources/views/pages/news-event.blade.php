@extends('layouts.app')

@section('title', __('site.news.page_title'))
@section('meta_description', __('site.news.meta_desc'))

@section('content')

@php
    $featuredNotice = $notices->first();
@endphp

<style>
    /* ── Category badge ── */
    .cat-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .2rem .75rem;
        font-size: .7rem;
        font-weight: 900;
        background: var(--theme-primary-soft);
        color: var(--theme-primary);
    }
    /* ── Filter pills ── */
    .filter-pill {
        display: inline-flex;
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
    .news-card {
        display: flex;
        flex-direction: column;
        min-height: 11rem;
        border-radius: .875rem;
        border: 1.5px solid var(--theme-border);
        background: var(--theme-surface);
        padding: 1.25rem;
        text-decoration: none;
        transition: border-color .2s, transform .2s, box-shadow .2s;
    }
    .news-card:hover {
        border-color: color-mix(in srgb, var(--theme-primary) 40%, transparent);
        transform: translateY(-2px);
        box-shadow: 0 6px 22px color-mix(in srgb, var(--theme-primary) 9%, transparent);
    }
    .news-card:hover .news-card-title { color: var(--theme-primary); }
    .news-card-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        border: 1.5px solid var(--theme-border);
        color: var(--theme-muted-text);
        transition: border-color .18s, color .18s, background .18s;
    }
    .news-card:hover .news-card-arrow {
        border-color: var(--theme-primary);
        color: var(--theme-primary);
        background: var(--theme-primary-soft);
    }
    /* ── Event date badge ── */
    .event-date-chip {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        flex-shrink: 0;
        border-radius: .625rem;
        background: var(--theme-primary);
        color: #fff;
    }
    /* ── Event list row ── */
    .event-row {
        display: flex;
        gap: .75rem;
        border-radius: .75rem;
        border: 1.5px solid var(--theme-border);
        background: var(--theme-surface);
        padding: .75rem;
        text-decoration: none;
        transition: border-color .18s;
    }
    .event-row:hover {
        border-color: color-mix(in srgb, var(--theme-primary) 35%, transparent);
    }
    .event-row:hover .event-row-title { color: var(--theme-primary); }
    /* ── Featured notice block ── */
    .featured-block {
        display: block;
        border-radius: .75rem;
        border: 1.5px solid var(--theme-border);
        background: var(--theme-muted-surface);
        padding: 1.25rem;
        text-decoration: none;
        transition: border-color .2s, background .2s;
    }
    .featured-block:hover {
        border-color: color-mix(in srgb, var(--theme-primary) 30%, transparent);
        background: var(--theme-surface);
    }
    .featured-block:hover .featured-title { color: var(--theme-primary); }
    /* ── Past event tile ── */
    .past-tile {
        display: block;
        border-radius: .75rem;
        border: 1.5px solid var(--theme-border);
        background: var(--theme-muted-surface);
        padding: 1rem;
        text-decoration: none;
        transition: border-color .18s, background .18s;
    }
    .past-tile:hover {
        border-color: color-mix(in srgb, var(--theme-primary) 35%, transparent);
        background: var(--theme-surface);
    }
    .past-tile:hover .past-tile-title { color: var(--theme-primary); }
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
            <span style="color:#fff;">{{ __('site.news.breadcrumb') }}</span>
        </nav>

        <div class="flex flex-col gap-8 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="theme-badge-accent inline-flex items-center rounded-full px-4 py-1.5 text-xs font-black uppercase tracking-widest mb-5">
                    {{ __('site.news.badge') }}
                </span>
                <h1 class="theme-hero-title text-4xl font-black tracking-tight sm:text-5xl mb-4 max-w-2xl">
                    {{ __('site.news.hero_h1') }}
                </h1>
                <p class="theme-hero-subtitle max-w-xl text-base leading-7">
                    {{ __('site.news.hero_sub', ['school_name' => $siteSettings->localized('site_name', config('app.name'))]) }}
                </p>
            </div>

            {{-- Quick links --}}
            <div class="flex shrink-0 gap-2">
                <a href="{{ route('notices') }}"
                   class="rounded-lg border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-black text-white backdrop-blur-sm transition hover:bg-white/20">
                    Notices
                </a>
                <a href="{{ route('events') }}"
                   class="rounded-lg border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-black text-white backdrop-blur-sm transition hover:bg-white/20">
                    Events
                </a>
            </div>
        </div>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- FEATURED NOTICE + UPCOMING EVENTS --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<div style="background: var(--theme-body-bg);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid gap-6 -translate-y-6 lg:grid-cols-[1.2fr_.8fr]">

            {{-- Featured notice --}}
            <div class="rounded-2xl border p-5 shadow-sm sm:p-6"
                 style="background: var(--theme-surface); border-color: var(--theme-border);">

                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <p class="theme-section-eyebrow text-xs mb-1.5">Latest update</p>
                        <h2 class="text-xl font-black" style="color: var(--theme-text);">Featured notice</h2>
                    </div>
                    <a href="{{ route('notices') }}"
                       class="hidden rounded-lg border px-3 py-2 text-sm font-bold transition sm:inline-flex"
                       style="border-color: var(--theme-border); color: var(--theme-muted-text);"
                       onmouseover="this.style.borderColor='var(--theme-primary)';this.style.color='var(--theme-primary)'"
                       onmouseout="this.style.borderColor='var(--theme-border)';this.style.color='var(--theme-muted-text)'">
                        View archive →
                    </a>
                </div>

                @if($featuredNotice)
                    <a href="{{ route('notices.show', $featuredNotice->slug) }}" class="featured-block group">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="cat-badge">{{ $featuredNotice->category ?: 'General' }}</span>
                            <span class="text-xs font-bold" style="color: var(--theme-muted-text);">
                                {{ $featuredNotice->created_at->format('M d, Y') }}
                                &nbsp;·&nbsp;
                                {{ $featuredNotice->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <h3 class="featured-title mt-4 text-2xl font-black leading-tight transition-colors" style="color: var(--theme-text);">
                            {{ $featuredNotice->title }}
                        </h3>
                        <p class="mt-3 line-clamp-3 text-sm leading-6" style="color: var(--theme-muted-text);">
                            {{ $featuredNotice->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($featuredNotice->content), 200) }}
                        </p>
                        <span class="mt-5 inline-flex items-center gap-2 text-sm font-black" style="color: var(--theme-primary);">
                            Read update
                            <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </span>
                    </a>
                @else
                    <div class="rounded-xl border border-dashed p-8 text-center" style="border-color: var(--theme-border);">
                        <p class="text-sm font-bold" style="color: var(--theme-muted-text);">No published updates yet.</p>
                    </div>
                @endif
            </div>

            {{-- Upcoming events --}}
            <div class="rounded-2xl border p-5 sm:p-6"
                 style="background: var(--theme-muted-surface); border-color: var(--theme-border);">

                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <p class="theme-section-eyebrow text-xs mb-1.5">Calendar</p>
                        <h2 class="text-xl font-black" style="color: var(--theme-text);">{{ __('site.news.events_h2') }}</h2>
                    </div>
                    <a href="{{ route('events') }}" class="text-sm font-black hover:underline" style="color: var(--theme-primary);">View all</a>
                </div>

                <div class="space-y-3">
                    @forelse($upcomingEvents as $event)
                        @php $date = $event->event_date ? \Carbon\Carbon::parse($event->event_date) : null; @endphp
                        <a href="{{ route('notices.show', $event->slug) }}" class="event-row">
                            <div class="event-date-chip">
                                <span class="text-base font-black leading-none">{{ $date ? $date->format('d') : '–' }}</span>
                                <span class="mt-0.5 text-[9px] font-black uppercase tracking-wider opacity-75">{{ $date ? $date->format('M') : 'TBA' }}</span>
                            </div>
                            <div class="min-w-0">
                                <h3 class="event-row-title line-clamp-2 text-sm font-black leading-5 transition-colors" style="color: var(--theme-text);">{{ $event->title }}</h3>
                                <p class="mt-1 truncate text-xs font-semibold" style="color: var(--theme-muted-text);">
                                    {{ $event->event_time ?? 'TBA' }} · {{ $event->event_location ?: 'School Campus' }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed p-6 text-center" style="border-color: var(--theme-border);">
                            <p class="text-sm font-bold" style="color: var(--theme-muted-text);">{{ __('site.news.no_events') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- NOTICE BOARD --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section id="notice-board" class="pb-16 sm:pb-20 scroll-mt-32" style="background: var(--theme-body-bg);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Filter bar --}}
        <div class="mb-6 rounded-2xl border p-4 shadow-sm sm:p-5"
             style="background: var(--theme-surface); border-color: var(--theme-border);">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="theme-section-eyebrow text-xs mb-1.5">Notice board</p>
                    <h2 class="text-xl font-black" style="color: var(--theme-text);">News, notices, results &amp; resources</h2>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('news', ['category' => 'All']) }}"
                       class="filter-pill {{ ($category ?? 'All') === 'All' ? 'is-active' : '' }}">All</a>
                    @foreach($noticeCategories as $noticeCategory)
                        <a href="{{ route('news', ['category' => $noticeCategory]) }}"
                           class="filter-pill {{ ($category ?? 'All') === $noticeCategory ? 'is-active' : '' }}">
                            {{ $noticeCategory }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Cards grid --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($notices as $notice)
                @php $noticeDate = $notice->created_at; @endphp
                <a href="{{ route('notices.show', $notice->slug) }}" class="news-card">
                    <div class="flex items-start justify-between gap-4">
                        <span class="cat-badge">{{ $notice->category ?: 'General' }}</span>
                        <time class="shrink-0 text-xs font-bold" style="color: var(--theme-muted-text);">
                            {{ $noticeDate->format('M d') }}
                        </time>
                    </div>

                    <h3 class="news-card-title mt-4 line-clamp-2 text-lg font-black leading-6 transition-colors" style="color: var(--theme-text);">
                        {{ $notice->title }}
                    </h3>
                    <p class="mt-3 line-clamp-2 text-sm leading-6" style="color: var(--theme-muted-text);">
                        {{ $notice->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($notice->content), 120) }}
                    </p>

                    <div class="mt-auto flex items-center justify-between pt-5">
                        <span class="text-xs font-bold" style="color: var(--theme-muted-text);">{{ $noticeDate->diffForHumans() }}</span>
                        <span class="news-card-arrow">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </div>
                </a>
            @empty
                <div class="sm:col-span-2 xl:col-span-3 rounded-2xl border border-dashed py-14 text-center"
                     style="border-color: var(--theme-border);">
                    <p class="text-sm font-bold" style="color: var(--theme-muted-text);">No posts found for this category.</p>
                </div>
            @endforelse
        </div>

        {{-- Past event highlights --}}
        @if($pastEvents->isNotEmpty())
            <div class="mt-10 rounded-2xl border p-5 shadow-sm sm:p-6"
                 style="background: var(--theme-surface); border-color: var(--theme-border);">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <h2 class="text-xl font-black" style="color: var(--theme-text);">Recent event highlights</h2>
                    <a href="{{ route('events') }}" class="text-sm font-black hover:underline" style="color: var(--theme-primary);">Event archive →</a>
                </div>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($pastEvents as $past)
                        @php $pastDate = $past->event_date ? \Carbon\Carbon::parse($past->event_date) : null; @endphp
                        <a href="{{ route('notices.show', $past->slug) }}" class="past-tile">
                            <p class="text-[10px] font-black uppercase tracking-widest" style="color: var(--theme-muted-text);">
                                {{ $pastDate ? $pastDate->format('M d, Y') : 'Past Event' }}
                            </p>
                            <h3 class="past-tile-title mt-2 line-clamp-2 text-sm font-black leading-5 transition-colors" style="color: var(--theme-text);">
                                {{ $past->title }}
                            </h3>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Pagination --}}
        @if($notices->hasPages())
            <div class="mt-8 overflow-x-auto">
                {{ $notices->links('pagination::tailwind') }}
            </div>
        @endif

    </div>
</section>

@endsection
