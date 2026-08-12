@extends('layouts.app')

@section('title', __('site.faculty.page_title'))

@section('content')

{{-- Hero Section --}}
<section class="directory-hero relative overflow-hidden bg-linear-to-br from-[#0b2415] via-[#1a5632] to-[#0b2415] py-12 sm:py-16">

    <div class="absolute inset-0 opacity-[0.08] pointer-events-none"
         style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 30px 30px;" aria-hidden="true"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <nav class="flex items-center gap-2 text-green-200 text-sm font-medium mb-5" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-[#e2a024] transition-colors">{{ __('site.common.home') }}</a>
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-white">{{ __('site.faculty.breadcrumb') }}</span>
        </nav>

        <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4 dir-hero-title">
            {{ __('site.faculty.hero_h1') }}
        </h1>
        <p class="text-green-100/80 text-base sm:text-lg max-w-2xl dir-hero-sub">
            {{ __('site.faculty.hero_sub') }}
        </p>
    </div>
</section>

{{-- Directory Section --}}
<section class="py-10 sm:py-12 bg-[#fdfbf7] min-h-screen"
         x-data="{
             activeCategory: 'All',
             gridLayout: 3,
             init() {
                 const saved = sessionStorage.getItem('dir_layout');
                 if (saved) this.gridLayout = parseInt(saved);
             },
             setLayout(n) {
                 this.gridLayout = n;
                 sessionStorage.setItem('dir_layout', n);
             }
         }">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ── Toolbar ── --}}
        <div class="mb-10 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">

            {{-- Row 1: Title --}}
            <h2 class="text-2xl sm:text-3xl font-bold text-[#0b2415]">{{ __('site.faculty.board_title') }}</h2>

            {{-- Row 2: Filters + Grid Toggles --}}
            <div class="mt-5 flex flex-wrap items-center justify-between gap-4">

                {{-- Category filters --}}
                <div class="flex flex-wrap gap-2" role="group" aria-label="Filter by category">
                    <button
                        @click="activeCategory = 'All'"
                        :class="activeCategory === 'All'
                            ? 'bg-[#1a5632] text-white border-[#1a5632]'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-[#1a5632] hover:text-[#1a5632]'"
                        class="dir-filter-btn shadow-sm"
                        aria-pressed="activeCategory === 'All'">
                        {{ __('site.faculty.filter_all') }}
                    </button>

                    @foreach($categories ?? ['Leadership', 'Education', 'Management', 'Technical', 'Basic Level'] as $cat)
                    <button
                        @click="activeCategory = @js($cat)"
                        :class="activeCategory === @js($cat)
                            ? 'bg-[#1a5632] text-white border-[#1a5632]'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-[#1a5632] hover:text-[#1a5632]'"
                        class="dir-filter-btn shadow-sm"
                        :aria-pressed="activeCategory === @js($cat)">
                        {{ $cat }}
                    </button>
                    @endforeach
                </div>

                {{-- Grid layout toggles --}}
                <div class="flex items-center bg-white border border-gray-200 rounded-full p-1 shadow-sm shrink-0"
                     role="group" aria-label="Grid layout">
                    
                    {{-- 1 col --}}
                    <button @click="setLayout(1)" :class="gridLayout === 1 ? 'bg-gray-100 text-[#1a5632]' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'" class="dir-grid-btn" title="Single column">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>

                    {{-- 2 cols --}}
                    <button @click="setLayout(2)" :class="gridLayout === 2 ? 'bg-gray-100 text-[#1a5632]' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'" class="dir-grid-btn" title="Two columns">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4z M14 4h6v6h-6z M4 14h6v6H4z M14 14h6v6h-6z"/></svg>
                    </button>

                    {{-- 3 cols --}}
                    <button @click="setLayout(3)" :class="gridLayout === 3 ? 'bg-gray-100 text-[#1a5632]' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'" class="dir-grid-btn" title="Three columns">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h4v16H3z M10 4h4v16h-4z M17 4h4v16h-4z"/></svg>
                    </button>

                    {{-- 4 cols --}}
                    <button @click="setLayout(4)" :class="gridLayout === 4 ? 'bg-gray-100 text-[#1a5632]' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50'" class="dir-grid-btn" title="Four columns">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h3v16H3z M8 4h3v16H8z M13 4h3v16H13z M18 4h3v16H18z"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-10">
            @forelse($groups ?? [] as $group)
                <section x-show="activeCategory === 'All' || activeCategory === @js($group->name)"
                         x-transition:enter="dir-enter-transition"
                         x-transition:enter-start="dir-enter-start"
                         x-transition:enter-end="dir-enter-end"
                         x-transition:leave="dir-leave-transition"
                         x-transition:leave-start="dir-leave-start"
                         x-transition:leave-end="dir-leave-end">
                    <div class="mb-5">
                        <h3 class="text-xl sm:text-2xl font-bold text-[#0b2415]">{{ $group->name }}</h3>
                        @if($group->description)
                            <p class="text-gray-600 text-sm mt-2 max-w-3xl">{{ $group->description }}</p>
                        @endif
                    </div>

                    <div class="grid gap-5 transition-all duration-300"
                         :class="{
                             'grid-cols-1 max-w-2xl': gridLayout === 1,
                             'grid-cols-1 sm:grid-cols-2 max-w-4xl': gridLayout === 2,
                             'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 max-w-6xl': gridLayout === 3,
                             'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4': gridLayout === 4
                        }">
                        @foreach($group->activeMembers as $member)
                            <div class="bg-white rounded-2xl overflow-hidden border border-gray-200 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 group flex"
                                 :class="gridLayout === 1 ? 'flex-col sm:flex-row items-stretch' : 'flex-col'">
                                <div class="relative bg-gray-100 shrink-0 border-b border-gray-100"
                                     :class="gridLayout === 1 ? 'w-full sm:w-1/3 aspect-[4/3] sm:aspect-auto sm:min-h-full' : 'w-full aspect-[4/3]'">
                                    <img src="{{ $member->image_url }}"
                                         alt="{{ $member->name ?? 'Name' }}"
                                         loading="lazy"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                         class="absolute inset-0 w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500 ease-out">
                                    <div class="absolute inset-0 hidden items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 text-4xl font-black text-gray-400">
                                        {{ strtoupper(mb_substr($member->name ?? 'M', 0, 1)) }}
                                    </div>

                                    <div class="absolute top-4 right-4">
                                        <span class="text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-full bg-[#0b2415]/80 backdrop-blur-sm text-white shadow-sm">
                                            {{ $group->name }}
                                        </span>
                                    </div>
                                </div>

                                <div class="p-5 flex flex-col flex-1"
                                     :class="gridLayout === 1 ? 'justify-center items-start text-left' : 'items-start text-left'">
                                    <h3 class="text-lg font-bold text-[#0b2415] mb-1 leading-tight group-hover:text-[#1a5632] transition-colors">
                                        {{ $member->name ?? 'Full Name' }}
                                    </h3>

                                    <p class="text-sm font-medium text-gray-600 mb-3">
                                        {{ $member->role ?? 'Position' }}
                                    </p>

                                    <div class="w-full h-px bg-gray-100 mb-3 mt-auto"></div>

                                    <div class="flex flex-col gap-1 text-gray-500 text-xs font-medium w-full">
                                        <span class="font-bold text-[#0b2415] mb-0.5">{{ __('site.faculty.education_label') }}</span>
                                        <span>{{ $member->education ?? 'University Degree, Specialization' }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="flex flex-col items-center justify-center py-20 bg-white border border-gray-100 rounded-3xl shadow-sm">
                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <p class="text-gray-500 font-medium text-lg">{{ __('site.faculty.empty_title') }}</p>
                </div>
            @endforelse
        </div>

    </div>
</section>

{{-- ── Styles ── --}}
<style>
/* =========================================
   UI ELEMENTS
   ========================================= */
.directory-hero {
    background: linear-gradient(120deg, var(--theme-dark) 0%, var(--theme-primary) 54%, var(--theme-header-gradient-end) 100%) !important;
}

.dir-filter-btn {
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0.4rem 1rem;
    border-radius: 8px;
    border-width: 1px;
    border-style: solid;
    transition: all 0.2s;
    cursor: pointer;
}

.dir-grid-btn {
    padding: 0.45rem;
    border-radius: 6px;
    transition: background-color 0.15s, color 0.15s;
    cursor: pointer;
    border: none;
    background: none;
}

/* =========================================
   ALPINE x-transition helpers
   ========================================= */
.dir-enter-transition   { transition: opacity 0.3s ease, transform 0.3s ease; }
.dir-enter-start        { opacity: 0; transform: scale(0.95); }
.dir-enter-end          { opacity: 1; transform: scale(1); }
.dir-leave-transition   { transition: opacity 0.2s ease, transform 0.2s ease; }
.dir-leave-start        { opacity: 1; transform: scale(1); }
.dir-leave-end          { opacity: 0; transform: scale(0.95); }
</style>

@endsection

@section('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
