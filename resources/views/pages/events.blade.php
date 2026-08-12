{{-- resources/views/pages/events.blade.php --}}
@extends('layouts.app')

@section('title', 'Events & Activities')
@section('meta_description', 'View upcoming and past events at our school. Sports day, cultural programs, science exhibitions, parent-teacher meetings, and more.')

@section('content')

<style>
    /* ── Upcoming event card ── */
    .event-card {
        display: flex;
        flex-direction: column;
        min-height: 14rem;
        border-radius: .875rem;
        border: 1.5px solid var(--theme-border);
        background: var(--theme-surface);
        text-decoration: none;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.04);
        transition: border-color .2s, transform .2s, box-shadow .2s;
    }
    .event-card:hover {
        border-color: color-mix(in srgb, var(--theme-primary) 40%, transparent);
        transform: translateY(-2px);
        box-shadow: 0 6px 22px color-mix(in srgb, var(--theme-primary) 9%, transparent);
    }
    .event-card:hover .event-card-title { color: var(--theme-primary); }
    /* ── Date badge ── */
    .event-date-badge {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 4rem;
        height: 4rem;
        flex-shrink: 0;
        border-radius: .75rem;
        background: var(--theme-primary);
        color: #fff;
    }
    /* ── Category pill ── */
    .cat-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .2rem .75rem;
        font-size: .7rem;
        font-weight: 900;
        background: var(--theme-primary-soft);
        color: var(--theme-primary);
    }
    /* ── Meta icon ── */
    .meta-icon { color: var(--theme-primary); }
    /* ── Past event row ── */
    .past-row {
        display: grid;
        gap: .75rem;
        border-bottom: 1px solid var(--theme-border);
        padding: 1rem 1.25rem;
        text-decoration: none;
        transition: background .16s;
    }
    .past-row:last-child { border-bottom: none; }
    .past-row:hover { background: var(--theme-muted-surface); }
    .past-row:hover .past-row-title { color: var(--theme-primary); }
    @media (min-width: 640px) {
        .past-row { grid-template-columns: 8rem 1fr auto; align-items: center; padding: 1.125rem 1.25rem; }
    }
    .past-row-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        border: 1.5px solid var(--theme-border);
        color: var(--theme-muted-text);
        transition: border-color .16s, color .16s;
    }
    .past-row:hover .past-row-arrow {
        border-color: var(--theme-primary);
        color: var(--theme-primary);
    }
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
            <span style="color:#fff;">Events</span>
        </nav>

        <div class="flex flex-col gap-8 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="theme-badge-accent inline-flex items-center rounded-full px-4 py-1.5 text-xs font-black uppercase tracking-widest mb-5">
                    School calendar
                </span>
                <h1 class="theme-hero-title text-4xl font-black tracking-tight sm:text-5xl mb-4 max-w-2xl">
                    Events &amp; Activities
                </h1>
                <p class="theme-hero-subtitle max-w-xl text-base leading-7">
                    Follow upcoming school programs, competitions, meetings, and community activities in one clean calendar view.
                </p>
            </div>

            {{-- Today chip --}}
            <div class="shrink-0 rounded-2xl border border-white/20 bg-white/10 px-6 py-4 backdrop-blur-sm">
                <p class="text-xs font-black uppercase tracking-widest text-white/60">Today</p>
                <p class="mt-1 text-2xl font-black text-white leading-none">{{ now()->format('M d, Y') }}</p>
                <p class="mt-1 text-sm font-semibold text-white/70">
                    {{ $upcomingEvents->count() }} upcoming {{ Str::plural('event', $upcomingEvents->count()) }}
                </p>
            </div>
        </div>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- UPCOMING EVENTS --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section style="background: var(--theme-body-bg);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header (overlaps hero) --}}
        <div class="-translate-y-6 mb-2 flex flex-col gap-4 rounded-2xl border p-5 shadow-sm sm:p-6 lg:flex-row lg:items-center lg:justify-between"
             style="background: var(--theme-surface); border-color: var(--theme-border);">
            <div>
                <p class="theme-section-eyebrow text-xs mb-1.5">What's next</p>
                <h2 class="text-xl font-black" style="color: var(--theme-text);">Upcoming events</h2>
            </div>
            <a href="{{ route('news') }}"
               class="inline-flex w-full items-center justify-center rounded-xl border px-4 py-2.5 text-sm font-black transition sm:w-auto"
               style="border-color: var(--theme-border); color: var(--theme-muted-text);"
               onmouseover="this.style.borderColor='var(--theme-primary)';this.style.color='var(--theme-primary)'"
               onmouseout="this.style.borderColor='var(--theme-border)';this.style.color='var(--theme-muted-text)'">
                News &amp; notices →
            </a>
        </div>

        @if($upcomingEvents->isNotEmpty())
            <div class="grid gap-4 pb-12 md:grid-cols-2 xl:grid-cols-3">
                @foreach($upcomingEvents as $event)
                    @php $eDate = \Carbon\Carbon::parse($event->event_date); @endphp
                    <a href="{{ route('news.show', $event->slug) }}" class="event-card">

                        {{-- Card header --}}
                        <div class="flex items-start gap-4 border-b p-5" style="border-color: var(--theme-border);">
                            <div class="event-date-badge">
                                <span class="text-2xl font-black leading-none">{{ $eDate->format('d') }}</span>
                                <span class="mt-0.5 text-[9px] font-black uppercase tracking-wider opacity-75">{{ $eDate->format('M') }}</span>
                            </div>
                            <div class="min-w-0">
                                <span class="cat-pill">{{ $event->category ?: 'Event' }}</span>
                                <h3 class="event-card-title mt-3 line-clamp-2 text-base font-black leading-6 transition-colors" style="color: var(--theme-text);">
                                    {{ $event->title }}
                                </h3>
                            </div>
                        </div>

                        {{-- Card body --}}
                        <div class="flex flex-1 flex-col p-5">
                            <p class="line-clamp-2 text-sm leading-6" style="color: var(--theme-muted-text);">
                                {{ $event->excerpt ?? Str::limit(strip_tags($event->content), 120) }}
                            </p>
                            <div class="mt-auto space-y-2 pt-5 text-sm font-semibold" style="color: var(--theme-muted-text);">
                                <p class="flex items-center gap-2">
                                    <svg class="meta-icon h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $event->event_time ?? 'Time TBA' }}
                                </p>
                                <p class="flex items-center gap-2">
                                    <svg class="meta-icon h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    {{ $event->event_location ?? 'School Campus' }}
                                </p>
                            </div>
                        </div>

                    </a>
                @endforeach
            </div>
        @else
            <div class="mb-12 rounded-2xl border border-dashed py-16 text-center" style="border-color: var(--theme-border);">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full"
                     style="background: var(--theme-primary-soft);">
                    <svg class="h-6 w-6" style="color: var(--theme-primary);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-black" style="color: var(--theme-text);">No upcoming events</h3>
                <p class="mt-2 text-sm font-semibold" style="color: var(--theme-muted-text);">New schedules will appear here when published.</p>
            </div>
        @endif

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- PAST EVENTS --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<section class="py-10 sm:py-14" style="background: var(--theme-muted-surface);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="theme-section-eyebrow text-xs mb-1.5">Archive</p>
                <h2 class="text-2xl font-black" style="color: var(--theme-text);">Past events</h2>
            </div>
            <a href="{{ url('/gallery') }}" class="text-sm font-black hover:underline" style="color: var(--theme-primary);">
                View photo gallery →
            </a>
        </div>

        @if($pastEvents->isNotEmpty())
            <div class="overflow-hidden rounded-2xl border shadow-sm" style="background: var(--theme-surface); border-color: var(--theme-border);">
                @foreach($pastEvents as $past)
                    @php $pastDate = $past->event_date ? \Carbon\Carbon::parse($past->event_date) : null; @endphp
                    <a href="{{ route('news.show', $past->slug) }}" class="past-row">
                        <time class="text-sm font-black" style="color: var(--theme-muted-text);">
                            {{ $pastDate ? $pastDate->format('M d, Y') : 'Past Event' }}
                        </time>
                        <div>
                            <h3 class="past-row-title font-black transition-colors" style="color: var(--theme-text);">{{ $past->title }}</h3>
                            <p class="mt-1 line-clamp-1 text-sm" style="color: var(--theme-muted-text);">
                                {{ $past->excerpt ?? Str::limit(strip_tags($past->content), 100) }}
                            </p>
                        </div>
                        <span class="past-row-arrow">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-dashed py-12 text-center" style="border-color: var(--theme-border);">
                <p class="text-sm font-bold" style="color: var(--theme-muted-text);">Archive is currently empty.</p>
            </div>
        @endif

    </div>
</section>

@endsection
