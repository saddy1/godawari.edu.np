@php
    $collegeName = $siteSettings->localized('site_name', __('site.school_name'));
    $collegeAddress = $siteSettings->localized('site_address', __('site.location'));
    $banners = collect($homeBanners ?? []);
    $links = collect($quickLinks ?? []);
    $featured = collect($learningPathways ?? []);
    $notices = collect($homeNotices ?? [])->take(4);
    $events = collect($homeEvents ?? [])->take(3);
    $gallery = collect($homeGallery ?? [])->values();
    $gcCells = [
        // G
        [1,2],[1,3],[1,4],[1,5],[1,6],
        [2,1],[2,2],[3,1],[3,2],
        [4,1],[4,2],[4,4],[4,5],[4,6],
        [5,1],[5,2],[5,6],[6,1],[6,2],[6,6],
        [7,2],[7,3],[7,4],[7,5],[7,6],
        // C
        [1,11],[1,12],[1,13],[1,14],[1,15],
        [2,10],[2,11],[3,10],[3,11],[4,10],[4,11],
        [5,10],[5,11],[6,10],[6,11],
        [7,11],[7,12],[7,13],[7,14],[7,15],
    ];
@endphp

<main class="bg-white text-slate-700">
    <a href="https://sushmasecondary.edu.np/"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="{{ __('site.college_home.visit_school') }} — {{ __('site.college_home.school_name') }}"
       title="{{ __('site.college_home.school_name') }}"
       class="group fixed right-0 top-[46%] z-40 flex w-12 -translate-y-1/2 flex-col items-center gap-2 bg-[var(--theme-primary)] px-2 pb-6 pt-3 text-white shadow-xl shadow-slate-950/20 transition hover:w-14 hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--theme-secondary)] sm:w-14 sm:hover:w-16"
       style="clip-path:polygon(0 0,100% 0,100% calc(100% - 12px),50% 100%,0 calc(100% - 12px));">
        <svg class="h-5 w-5 shrink-0 text-[var(--theme-secondary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 4l9 6.5M5 9.5V20h14V9.5M9 20v-6h6v6M8 10h.01M12 10h.01M16 10h.01"/>
        </svg>
        <span class="text-xs font-black tracking-wide sm:text-sm" style="writing-mode:vertical-rl;transform:rotate(180deg);">{{ __('site.college_home.visit_school') }}</span>
        <svg class="h-4 w-4 shrink-0 text-white/75 transition-transform group-hover:-translate-y-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5h5v5M19 5l-9 9M19 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/>
        </svg>
    </a>

    {{-- Every banner in this hero is managed from Admin → Home Banners. --}}
    <section
        x-data="{ active: 0, total: {{ $banners->count() }} }"
        @if($banners->count() > 1) x-init="setInterval(() => active = (active + 1) % total, 7000)" @endif
        class="relative isolate min-h-[520px] overflow-hidden bg-slate-950 sm:min-h-[580px]"
    >
        @foreach($banners as $index => $banner)
            <article
                x-show="active === {{ $index }}"
                x-transition:enter="transition duration-700"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition duration-500"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0"
                @if($index !== 0) style="display:none" @endif
            >
                <img src="{{ $banner->image_url }}" alt="{{ $banner->localizedTitle() }}" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-slate-950/65"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/75 via-slate-950/35 to-transparent"></div>

                <div class="absolute inset-0 flex items-center px-5 sm:px-6 lg:px-8">
                    <div class="mx-auto w-full max-w-7xl">
                        <div class="max-w-3xl {{ $banner->text_position === 'center' ? 'mx-auto text-center' : '' }}">
                            @if($banner->localizedEyebrow())
                                <p class="text-xs font-black uppercase tracking-[.2em] text-[var(--theme-secondary)] sm:text-sm">{{ $banner->localizedEyebrow() }}</p>
                            @endif
                            <h1 class="mt-4 text-4xl font-black leading-[1.08] text-white sm:text-6xl" style="font-family:var(--ff-head)">{{ $banner->localizedTitle() }}</h1>
                            @if($banner->localizedSubtitle())
                                <p class="mt-5 max-w-2xl text-base font-medium leading-8 text-white/80 sm:text-lg {{ $banner->text_position === 'center' ? 'mx-auto' : '' }}">{{ $banner->localizedSubtitle() }}</p>
                            @endif
                            <div class="mt-8 flex flex-wrap gap-3 {{ $banner->text_position === 'center' ? 'justify-center' : '' }}">
                                @if($banner->localizedPrimaryLabel() && $banner->primary_url)
                                    <a href="{{ $banner->primary_url }}" class="rounded-lg bg-[var(--theme-secondary)] px-6 py-3 text-sm font-black text-slate-950 transition hover:brightness-110">{{ $banner->localizedPrimaryLabel() }}</a>
                                @endif
                                @if($banner->localizedSecondaryLabel() && $banner->secondary_url)
                                    <a href="{{ $banner->secondary_url }}" class="rounded-lg border border-white/40 bg-white/10 px-6 py-3 text-sm font-black text-white backdrop-blur transition hover:bg-white/20">{{ $banner->localizedSecondaryLabel() }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach

        @if($banners->count() > 1)
            <div class="absolute bottom-6 left-1/2 z-20 flex -translate-x-1/2 gap-2">
                @foreach($banners as $index => $banner)
                    <button type="button" @click="active = {{ $index }}" :class="active === {{ $index }} ? 'w-8 bg-white' : 'w-2 bg-white/45'" class="h-2 rounded-full transition-all" aria-label="Show banner {{ $index + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Quick links are managed from Admin → Homepage Content. --}}
    @if($links->isNotEmpty())
        <section class="relative z-10 -mt-8 px-5 sm:px-6 lg:px-8">
            <div class="mx-auto grid max-w-7xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($links as $item)
                    @php $external = $item->url && preg_match('/^https?:\/\//', $item->url); @endphp
                    <a href="{{ $item->url ?: '#' }}" @if($external) target="_blank" rel="noopener" @endif class="group flex items-center gap-4 border-b border-slate-200 p-5 transition hover:bg-slate-50 sm:border-r">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[var(--theme-primary)]/10 text-[var(--theme-primary)] transition group-hover:bg-[var(--theme-primary)] group-hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item->icon_path }}"/></svg>
                        </span>
                        <span class="min-w-0">
                            <strong class="block text-sm font-black text-slate-900">{{ $item->localizedTitle() }}</strong>
                            @if($item->localizedSubtitle())<span class="mt-1 block truncate text-xs font-semibold text-slate-500">{{ $item->localizedSubtitle() }}</span>@endif
                        </span>
                        <span class="ml-auto text-slate-300 transition group-hover:translate-x-1 group-hover:text-[var(--theme-primary)]">→</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="border-y border-[color:color-mix(in_srgb,var(--theme-primary)_10%,transparent)] bg-[color:color-mix(in_srgb,var(--theme-primary)_3%,white)] px-5 py-20 sm:px-6 lg:px-8 lg:py-24">
        <div class="mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-[.9fr_1.1fr]">
            <img src="{{ asset('uploads/site/godawari-campus.jpg') }}" alt="{{ $collegeName }}" class="aspect-[4/3] w-full rounded-2xl bg-slate-100 object-cover shadow-lg">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-[var(--theme-primary)]">{{ __('site.college_home.about_eyebrow') }}</p>
                <h2 class="mt-3 text-3xl font-black leading-tight text-slate-950 sm:text-4xl" style="font-family:var(--ff-head)">{{ $collegeName }}</h2>
                <p class="mt-5 max-w-2xl leading-8 text-slate-600">{{ __('site.college_home.about_text') }}</p>
                <div class="mt-6 flex flex-wrap gap-x-6 gap-y-3 text-sm font-bold text-slate-600">
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-[var(--theme-secondary)]"></span>{{ __('site.college_home.tu_affiliated') }}</span>
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-[var(--theme-secondary)]"></span>{{ $collegeAddress }}</span>
                </div>
                <a href="{{ route('about') }}" class="mt-7 inline-flex items-center gap-2 rounded-lg bg-[var(--theme-primary)] px-5 py-3 text-sm font-black text-white transition hover:brightness-110">{{ __('site.college_home.read_story') }} <span>→</span></a>
            </div>
        </div>
    </section>

    {{-- Featured cards are also managed from Admin → Homepage Content. --}}
    @if($featured->isNotEmpty())
        <section id="programs" class="bg-slate-50 px-5 py-20 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-5xl">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-black uppercase tracking-[.18em] text-[var(--theme-primary)]">{{ __('site.college_home.programs_eyebrow') }}</p>
                    <h2 class="mt-3 text-3xl font-black text-slate-950 sm:text-4xl" style="font-family:var(--ff-head)">{{ __('site.college_home.programs_title') }}</h2>
                    <p class="mt-4 leading-7 text-slate-600">{{ __('site.college_home.programs_text') }}</p>
                </div>

                <div class="mx-auto mt-10 grid max-w-4xl gap-6 md:grid-cols-2">
                    @foreach($featured as $item)
                        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                            @if($item->image_url)
                                <img src="{{ $item->image_url }}" alt="{{ $item->localizedTitle() }}" class="aspect-[16/9] w-full bg-slate-100 object-cover">
                            @endif
                            <div class="p-6">
                                @if($item->localizedSubtitle())<p class="text-xs font-black uppercase tracking-[.12em] text-[var(--theme-primary)]">{{ $item->localizedSubtitle() }}</p>@endif
                                <h3 class="mt-3 text-xl font-black text-slate-950">{{ $item->localizedTitle() }}</h3>
                                @if($item->description)<p class="mt-3 line-clamp-3 text-sm leading-7 text-slate-600">{{ $item->description }}</p>@endif
                                @if($item->url)<a href="{{ $item->url }}" class="mt-5 inline-flex items-center gap-2 text-sm font-black text-[var(--theme-primary)]">{{ __('site.college_home.learn_program') }} <span>→</span></a>@endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="px-5 py-20 sm:px-6 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-7xl">
            <div class="flex items-end justify-between gap-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.18em] text-[var(--theme-primary)]">{{ __('site.college_home.updates_eyebrow') }}</p>
                    <h2 class="mt-3 text-3xl font-black text-slate-950 sm:text-4xl" style="font-family:var(--ff-head)">{{ __('site.college_home.updates_title') }}</h2>
                </div>
                <a href="{{ route('notices') }}" class="hidden text-sm font-black text-[var(--theme-primary)] sm:block">{{ __('site.view_all') }} →</a>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="divide-y divide-slate-100">
                        @forelse($notices as $notice)
                            <a href="{{ $notice->slug ? route('news.show', $notice->slug) : route('notices') }}" class="group flex items-start gap-5 p-5 hover:bg-slate-50 sm:p-6">
                                <time class="w-12 shrink-0 text-center"><strong class="block text-xl font-black text-[var(--theme-primary)]">{{ optional($notice->created_at)->format('d') }}</strong><span class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ optional($notice->created_at)->format('M') }}</span></time>
                                <div class="min-w-0"><p class="text-xs font-black uppercase tracking-wider text-[var(--theme-primary)]">{{ ucfirst($notice->type) }}</p><h3 class="mt-1 line-clamp-2 font-extrabold leading-6 text-slate-900 group-hover:text-[var(--theme-primary)]">{{ $notice->title }}</h3></div>
                            </a>
                        @empty
                            <p class="p-10 text-center text-sm font-semibold text-slate-400">{{ __('site.college_home.no_updates') }}</p>
                        @endforelse
                    </div>
                </div>

                <aside class="relative isolate overflow-hidden rounded-2xl p-6 text-white shadow-xl shadow-[color:color-mix(in_srgb,var(--theme-primary)_18%,transparent)] sm:p-7" style="background:linear-gradient(145deg,var(--theme-primary),color-mix(in srgb,var(--theme-primary) 72%,#071d2b))">
                    <span class="absolute -right-16 -top-16 -z-10 h-44 w-44 rounded-full bg-[var(--theme-secondary)]/15 blur-2xl"></span>
                    <span class="absolute -bottom-20 -left-14 -z-10 h-48 w-48 rounded-full bg-white/10 blur-3xl"></span>
                    <h3 class="text-lg font-black text-white">{{ __('site.college_home.upcoming_events') }}</h3>
                    <div class="mt-5 space-y-3">
                        @forelse($events as $event)
                            <a href="{{ $event->slug ? route('news.show', $event->slug) : route('events') }}" class="block rounded-xl border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                                <p class="text-xs font-black uppercase tracking-wider text-[var(--theme-secondary)]">{{ optional($event->created_at)->format('M d, Y') }}</p>
                                <p class="mt-2 font-bold leading-6">{{ $event->title }}</p>
                            </a>
                        @empty
                            <p class="rounded-xl border border-white/20 bg-white/10 p-5 text-sm font-semibold text-white/75 backdrop-blur-sm">{{ __('site.college_home.no_events') }}</p>
                        @endforelse
                    </div>
                </aside>
            </div>
        </div>
    </section>

    @if($gallery->isNotEmpty())
        <section class="relative isolate overflow-hidden bg-[var(--theme-primary)] px-3 py-16 sm:px-6 sm:py-20 lg:px-8">
            <img src="{{ $gallery->first()->url }}" alt="" class="absolute inset-0 -z-20 h-full w-full scale-105 object-cover opacity-20 blur-[2px] saturate-50">
            <div class="absolute inset-0 -z-10" style="background:linear-gradient(180deg,color-mix(in srgb,var(--theme-primary) 82%,#071d2b),color-mix(in srgb,var(--theme-primary) 94%,#071d2b))"></div>
            <div class="absolute inset-x-0 top-0 -z-10 h-px bg-gradient-to-r from-transparent via-[var(--theme-secondary)]/70 to-transparent"></div>

            <div class="mx-auto max-w-7xl text-center">
                <p class="text-xs font-black uppercase tracking-[.22em] text-[var(--theme-secondary)]">{{ app()->getLocale() === 'ne' ? 'क्याम्पसका झलकहरू' : 'Life at Godawari' }}</p>
                <h2 class="mt-3 text-3xl font-black text-white sm:text-5xl" style="font-family:var(--ff-head)">{{ app()->getLocale() === 'ne' ? 'विद्यार्थी जीवन' : 'Student Life' }}</h2>
                <a href="{{ route('gallery') }}" class="mt-5 inline-flex rounded-lg bg-white px-5 py-2.5 text-sm font-black text-[var(--theme-primary)] shadow-lg transition hover:bg-[var(--theme-secondary)] hover:text-slate-950">{{ app()->getLocale() === 'ne' ? 'पूरा ग्यालरी हेर्नुहोस्' : 'View Full Gallery' }}</a>

                <div class="mx-auto mt-10 grid w-fit" style="grid-template-columns:repeat(15,clamp(18px,5.5vw,68px));grid-template-rows:repeat(7,clamp(18px,5.5vw,68px));gap:clamp(2px,.45vw,7px)" aria-label="GC photo gallery">
                    @foreach($gcCells as $index => [$row, $column])
                        @php $image = $gallery[$index % $gallery->count()]; @endphp
                        <a href="{{ route('gallery') }}" class="group block overflow-hidden border-2 border-white/80 bg-white/10 shadow-lg shadow-black/20 transition duration-300 hover:z-10 hover:scale-110 hover:border-[var(--theme-secondary)]" style="grid-row:{{ $row }};grid-column:{{ $column }};border-radius:clamp(4px,.7vw,10px)" title="{{ $image->caption ?: $image->name }}">
                            <img src="{{ $image->url }}" alt="{{ $image->caption ?: $image->name ?: $collegeName }}" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:brightness-110">
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="px-5 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 rounded-2xl bg-[var(--theme-primary)] px-6 py-10 text-white sm:px-10 lg:flex-row lg:items-center">
            <div class="max-w-2xl"><p class="text-sm font-bold text-white/65">{{ __('site.college_home.cta_eyebrow') }}</p><h2 class="mt-2 text-2xl font-black text-white sm:text-3xl">{{ __('site.college_home.cta_title') }}</h2><p class="mt-3 text-sm leading-6 text-white/70">{{ __('site.college_home.cta_text') }}</p></div>
            <div class="flex flex-wrap gap-3"><a href="{{ route('admissions') }}" class="rounded-lg bg-[var(--theme-secondary)] px-5 py-3 text-sm font-black text-slate-950">{{ __('site.college_home.admission_cta') }}</a><a href="{{ route('contact') }}" class="rounded-lg border border-white/30 px-5 py-3 text-sm font-black text-white">{{ __('site.college_home.contact_cta') }}</a></div>
        </div>
    </section>
</main>
