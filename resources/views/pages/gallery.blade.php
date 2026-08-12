{{-- resources/views/pages/gallery.blade.php --}}
@extends('layouts.app')

@section('title', __('site.gallery.page_title'))
@section('meta_description', __('site.gallery.meta_desc'))

@section('content')

{{-- ============================================================ --}}
{{-- HERO SECTION --}}
{{-- ============================================================ --}}
<section class="relative py-12 sm:py-16 overflow-hidden bg-gradient-to-br from-[#0b2415] via-[#1a5632] to-[#0b2415]">
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 30px 30px;"></div>
    <div class="absolute -top-20 -right-20 w-96 h-96 bg-[#e2a024]/20 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-80 h-80 bg-white/10 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <nav class="flex items-center gap-2 text-green-200 text-sm font-medium mb-8" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-[#e2a024] hover:underline transition-colors">{{ __('site.common.home') }}</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-white">{{ __('site.gallery.breadcrumb') }}</span>
        </nav>

        <div class="inline-flex items-center gap-2 bg-[#e2a024] text-[#0b2415] font-bold text-sm px-6 py-2.5 rounded-full mb-6 shadow-lg">
            📸 {{ __('site.gallery.badge') }}
        </div>

        <h1 class="text-4xl lg:text-5xl xl:text-6xl font-bold text-white mb-6 tracking-tight">{{ __('site.gallery.hero_h1') }}</h1>
        <p class="text-green-100/90 text-lg md:text-xl font-medium leading-relaxed max-w-2xl">
            {{ __('site.gallery.hero_sub') }}
        </p>
    </div>
</section>

{{-- ============================================================ --}}
{{-- GALLERY APP (Dynamic & Interactive) --}}
{{-- ============================================================ --}}
<div x-data="galleryApp()"
     @keydown.right.window="nextImage()"
     @keydown.left.window="prevImage()"
     @keydown.escape.window="closeLightbox()">

    {{-- ── FILTER TABS & MOBILE GRID SELECTOR ── --}}
    <section class="py-4 sm:py-6 bg-white border-b border-gray-200 sticky top-20 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            
            {{-- Category Filters --}}
            <div class="flex gap-3 overflow-x-auto pb-2 sm:pb-0 flex-1" style="-ms-overflow-style:none;scrollbar-width:none;">
                @foreach(['All' => __('site.gallery.filter_all'), 'Events' => __('site.gallery.filter_events'), 'Academics' => __('site.gallery.filter_academics'), 'Sports' => __('site.gallery.filter_sports'), 'Cultural' => __('site.gallery.filter_cultural'), 'Campus' => __('site.gallery.filter_campus')] as $key => $label)
                <button
                    @click="activeCategory = '{{ $key }}'"
                    :class="activeCategory === '{{ $key }}' ? 'bg-[#1a5632] text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-green-50 hover:text-[#1a5632]'"
                    class="text-xs sm:text-sm font-bold px-5 py-2 sm:py-2.5 rounded-full whitespace-nowrap transition-colors duration-200">
                    {{ $label }}
                </button>
                @endforeach
            </div>

            {{-- Mobile Grid Layout Selector (Hidden on Desktop) --}}
            <div class="hidden max-[639px]:flex items-center gap-1.5 pl-4 ml-4 border-l border-gray-200 shrink-0 pb-2">
                <button @click="gridCols = 1" :class="gridCols === 1 ? 'bg-[#1a5632] text-white' : 'bg-gray-100 text-gray-400'" class="p-1.5 rounded-md transition-colors" title="1 Column">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
                </button>
                <button @click="gridCols = 2" :class="gridCols === 2 ? 'bg-[#1a5632] text-white' : 'bg-gray-100 text-gray-400'" class="p-1.5 rounded-md transition-colors" title="2 Columns">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="7" height="16" rx="1"/><rect x="14" y="4" width="7" height="16" rx="1"/></svg>
                </button>
                <button @click="gridCols = 3" :class="gridCols === 3 ? 'bg-[#1a5632] text-white' : 'bg-gray-100 text-gray-400'" class="p-1.5 rounded-md transition-colors" title="3 Columns">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="2" y="4" width="5" height="16" rx="1"/><rect x="9.5" y="4" width="5" height="16" rx="1"/><rect x="17" y="4" width="5" height="16" rx="1"/></svg>
                </button>
            </div>

        </div>
    </section>

    {{-- ── MASONRY GRID ── --}}
    <section class="py-12 sm:py-16 bg-[#fdfbf7] min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            {{-- Grid Container --}}
            {{-- We use dynamic classes for mobile (columns-1, 2, or 3) and default tailwind for desktop (sm:columns-2 md:columns-3 lg:columns-4) --}}
            <div :class="{
                    'columns-1': gridCols === 1,
                    'columns-2': gridCols === 2,
                    'columns-3': gridCols === 3
                 }"
                 class="sm:columns-2 md:columns-3 lg:columns-4 gap-3 sm:gap-6 transition-all duration-300">
                 
                @forelse($galleryImages as $i => $img)
                @php
                    $category = $img->category ?? 'Campus';
                @endphp
                <div x-show="activeCategory === 'All' || activeCategory === '{{ $category }}'"
                     x-transition.opacity.duration.400ms
                     class="break-inside-avoid inline-block w-full mb-3 sm:mb-6">
                    <div class="group relative rounded-xl sm:rounded-2xl overflow-hidden cursor-zoom-in shadow-sm hover:shadow-xl transition-shadow duration-300 border border-gray-100 bg-gray-200"
                         @click="openLightbox({{ $i }})">
                        <img src="{{ $img->url }}"
                             alt="{{ $img->name }}"
                             class="w-full object-cover group-hover:scale-105 transition-transform duration-700 pointer-events-none"
                             loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0b2415]/95 via-[#0b2415]/20 to-transparent opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-3 sm:p-6">
                            <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-[#e2a024] mb-1 block">{{ $category }}</span>
                            @if(!empty($img->caption))
                                <h3 class="text-white font-bold text-xs sm:text-lg leading-tight truncate">{{ $img->caption }}</h3>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-20 text-center">
                    <div class="text-5xl mb-4">📸</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">{{ __('site.gallery.empty_title') }}</h3>
                    <p class="text-gray-500">Upload photos from the Admin Media Library to see them here.</p>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ============================================================ --}}
    {{-- LIGHTBOX (With Swipe & Zoom) --}}
    {{-- ============================================================ --}}
    <div x-show="lightboxOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[9999] bg-[#0b2415]/95 backdrop-blur-md flex flex-col"
         style="display:none;">

        {{-- ── TOP TOOLBAR ── --}}
        <div class="flex-shrink-0 flex items-center justify-between px-4 py-3 select-none">
            <div class="text-white/60 font-medium text-sm">
                <span x-text="currentIndex + 1"></span> / <span x-text="images.length"></span>
            </div>

            <div class="flex items-center gap-2 ml-auto">
                <div class="hidden sm:flex items-center bg-white/10 backdrop-blur-md rounded-full border border-white/20 p-1">
                    <button @click.stop="zoomBy(-0.5)" class="w-9 h-9 flex items-center justify-center text-white hover:bg-white/20 rounded-full transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/></svg></button>
                    <button @click.stop="resetZoom()" class="px-2 min-w-[52px] h-9 text-white text-xs font-bold text-center hover:bg-white/20 rounded-full transition-colors" x-text="Math.round(scale * 100) + '%'"></button>
                    <button @click.stop="zoomBy(0.5)" class="w-9 h-9 flex items-center justify-center text-white hover:bg-white/20 rounded-full transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg></button>
                </div>

                {{-- Close button — always visible on all screen sizes --}}
                <button @click="closeLightbox()"
                        class="flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 active:bg-red-700 rounded-full text-white font-bold text-sm transition-colors shadow-lg ml-1">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Close</span>
                </button>
            </div>
        </div>

        {{-- ── VIEWPORT ── --}}
        <div class="flex-1 relative overflow-hidden select-none"
             x-ref="viewport"
             @wheel.prevent="onWheel($event)"
             @mousedown.prevent="startDrag($event)"
             @mousemove="onDrag($event)"
             @mouseup="endDrag()"
             @mouseleave="endDrag()"
             @touchstart.prevent="onTouchStart($event)"
             @touchmove.prevent="onTouchMove($event)"
             @touchend="onTouchEnd($event)"
             @dblclick="onDblClick($event)"
             :style="{ cursor: dragging ? 'grabbing' : (scale > 1 ? 'grab' : 'zoom-in') }">
            
            <div class="absolute inset-0" @click="scale <= 1 && closeLightbox()"></div>

            <img x-ref="img"
                 :src="images[currentIndex]?.src"
                 :alt="images[currentIndex]?.alt"
                 class="absolute top-1/2 left-1/2 max-w-full max-h-full object-contain rounded-lg shadow-2xl"
                 draggable="false"
                 @click.stop
                 :style="`max-width: 90vw; max-height: 80vh; transform-origin: center center; transform: translate(calc(-50% + ${panX}px), calc(-50% + ${panY}px)) scale(${scale}); transition: ${animating ? 'transform 0.2s ease' : 'none'}; will-change: transform;`">
                 
            {{-- Hint toast --}}
            <div x-show="showHint"
                 x-transition:leave="transition duration-500"
                 x-transition:leave-end="opacity-0"
                 class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 text-white/80 text-xs font-medium px-4 py-2 rounded-full pointer-events-none whitespace-nowrap">
                Swipe to navigate · Pinch to zoom
            </div>
        </div>

        {{-- ── PREV / NEXT ARROWS ── --}}
        <button @click.stop="prevImage()" class="absolute left-4 md:left-8 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/10 hover:bg-white/25 backdrop-blur-md rounded-full border border-white/20 flex items-center justify-center text-white transition-all z-50 hidden sm:flex"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
        <button @click.stop="nextImage()" class="absolute right-4 md:right-8 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/10 hover:bg-white/25 backdrop-blur-md rounded-full border border-white/20 flex items-center justify-center text-white transition-all z-50 hidden sm:flex"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>

        {{-- Mobile tap zones --}}
        <div class="absolute top-20 bottom-20 left-0 w-1/4 z-40 sm:hidden" @click.stop="prevImage()"></div>
        <div class="absolute top-20 bottom-20 right-0 w-1/4 z-40 sm:hidden" @click.stop="nextImage()"></div>

        {{-- ── BOTTOM INFO BAR ── --}}
        <div class="flex-shrink-0 py-3 text-center pointer-events-none" x-show="images.length > 0">
            <span class="text-[#e2a024] text-xs font-bold uppercase tracking-widest block mb-0.5" x-text="images[currentIndex]?.cat"></span>
            <p class="text-white text-sm font-medium mb-0.5" x-show="images[currentIndex]?.alt" x-text="images[currentIndex]?.alt"></p>
            <p class="text-white/30 text-xs mt-1">Esc to close · ← → to navigate</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- Format the data cleanly in PHP first --}}
@php
    $formattedImages = $galleryImages->map(fn($img) => [
        'src' => $img->url,
        'alt' => $img->caption ?? '',
        'cat' => $img->category ?? 'Campus',
    ])->values();
@endphp

<script>
function galleryApp() {
    return {
        activeCategory: 'All',
        gridCols: 2, // Default mobile grid columns
        lightboxOpen: false,
        currentIndex: 0,
        images: {{ \Illuminate\Support\Js::from($formattedImages) }},

        scale: 1, panX: 0, panY: 0, minScale: 0.5, maxScale: 5, animating: false,
        dragging: false, dragStartX: 0, dragStartY: 0, panOriginX: 0, panOriginY: 0,
        lastPinchDist: null, pinchStartScale: 1, showHint: false, _hintTimer: null,
        
        // Touch Swipe logic variables
        swipeStartX: null,
        swipeStartY: null,

        openLightbox(index) {
            this.currentIndex = index;
            this.resetZoom();
            this.lightboxOpen = true;
            document.body.style.overflow = 'hidden';
            clearTimeout(this._hintTimer);
            this.showHint = true;
            this._hintTimer = setTimeout(() => { this.showHint = false; }, 3000);
        },

        closeLightbox() {
            this.lightboxOpen = false;
            this.resetZoom();
            document.body.style.overflow = '';
        },

        nextImage() {
            if (!this.lightboxOpen || this.images.length === 0) return;
            this.currentIndex = (this.currentIndex + 1) % this.images.length;
            this.resetZoom();
        },

        prevImage() {
            if (!this.lightboxOpen || this.images.length === 0) return;
            this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
            this.resetZoom();
        },

        resetZoom() {
            this.animating = true; this.scale = 1; this.panX = 0; this.panY = 0;
            setTimeout(() => { this.animating = false; }, 220);
        },

        zoomBy(delta, originX = 0, originY = 0) {
            const prevScale = this.scale;
            const newScale  = Math.min(this.maxScale, Math.max(this.minScale, prevScale + delta));
            const ratio     = newScale / prevScale;
            this.panX = originX + ratio * (this.panX - originX);
            this.panY = originY + ratio * (this.panY - originY);
            this.scale = newScale;
            this.clampPan();
            this.animating = true;
            setTimeout(() => { this.animating = false; }, 220);
        },

        clampPan() {
            if (this.scale <= 1) { this.panX = 0; this.panY = 0; return; }
            const vp = this.$refs.viewport, img = this.$refs.img;
            if (!vp || !img) return;
            const scaledW = img.clientWidth * this.scale, scaledH = img.clientHeight * this.scale;
            const maxX = Math.max(0, (scaledW - vp.clientWidth) / 2), maxY = Math.max(0, (scaledH - vp.clientHeight) / 2);
            this.panX = Math.min(maxX, Math.max(-maxX, this.panX));
            this.panY = Math.min(maxY, Math.max(-maxY, this.panY));
        },

        onWheel(e) {
            const vp = this.$refs.viewport, rect = vp.getBoundingClientRect();
            const ox = e.clientX - rect.left - rect.width / 2, oy = e.clientY - rect.top - rect.height / 2;
            const delta = e.deltaY < 0 ? 0.2 : -0.2;
            const newScale = Math.min(this.maxScale, Math.max(this.minScale, this.scale + delta));
            const ratio = newScale / this.scale;
            this.panX = ox + ratio * (this.panX - ox); this.panY = oy + ratio * (this.panY - oy);
            this.scale = newScale; this.animating = false; this.clampPan();
        },

        onDblClick(e) {
            if (this.scale > 1) { this.resetZoom(); } 
            else {
                const rect = this.$refs.viewport.getBoundingClientRect();
                this.zoomBy(1.5, e.clientX - rect.left - rect.width / 2, e.clientY - rect.top - rect.height / 2);
            }
        },

        startDrag(e) {
            if (this.scale <= 1) return;
            this.dragging = true; this.dragStartX = e.clientX; this.dragStartY = e.clientY;
            this.panOriginX = this.panX; this.panOriginY = this.panY;
        },

        onDrag(e) {
            if (!this.dragging) return;
            this.animating = false;
            this.panX = this.panOriginX + (e.clientX - this.dragStartX);
            this.panY = this.panOriginY + (e.clientY - this.dragStartY);
            this.clampPan();
        },

        endDrag() { this.dragging = false; },
        
        _pinchDist(t) { 
            return Math.sqrt(Math.pow(t[0].clientX - t[1].clientX, 2) + Math.pow(t[0].clientY - t[1].clientY, 2)); 
        },
        
        onTouchStart(e) {
            if (e.touches.length === 2) {
                // Pinch to Zoom tracking
                this.lastPinchDist = this._pinchDist(e.touches);
                this.pinchStartScale = this.scale;
                this.swipeStartX = null; // Cancel swipe if zooming
            } else if (e.touches.length === 1) {
                if (this.scale > 1) {
                    // Panning around zoomed image
                    this.dragging = true; this.dragStartX = e.touches[0].clientX; this.dragStartY = e.touches[0].clientY;
                    this.panOriginX = this.panX; this.panOriginY = this.panY;
                } else {
                    // Start Swipe tracking (only when fully zoomed out)
                    this.swipeStartX = e.touches[0].clientX;
                    this.swipeStartY = e.touches[0].clientY;
                }
            }
        },
        
        onTouchMove(e) {
            if (e.touches.length === 2 && this.lastPinchDist !== null) {
                const ratio = this._pinchDist(e.touches) / this.lastPinchDist;
                const newScale = Math.min(this.maxScale, Math.max(this.minScale, this.pinchStartScale * ratio));
                const scaleRatio = newScale / this.scale;
                this.panX *= scaleRatio; this.panY *= scaleRatio; this.scale = newScale;
                this.animating = false; this.clampPan();
            } else if (e.touches.length === 1 && this.dragging) {
                this.animating = false;
                this.panX = this.panOriginX + (e.touches[0].clientX - this.dragStartX);
                this.panY = this.panOriginY + (e.touches[0].clientY - this.dragStartY);
                this.clampPan();
            }
        },
        
        onTouchEnd(e) {
            this.lastPinchDist = null; 
            this.dragging = false;
            
            // Handle Swipe Gesture
            if (this.swipeStartX !== null && this.scale === 1 && e.changedTouches.length > 0) {
                let endX = e.changedTouches[0].clientX;
                let endY = e.changedTouches[0].clientY;
                let diffX = this.swipeStartX - endX;
                let diffY = Math.abs(this.swipeStartY - endY);

                // If swipe is mostly horizontal and traveled at least 40 pixels
                if (Math.abs(diffX) > 40 && diffY < 100) {
                    if (diffX > 0) {
                        this.nextImage(); // Swiped Left
                    } else {
                        this.prevImage(); // Swiped Right
                    }
                }
            }
            
            this.swipeStartX = null;
            this.swipeStartY = null;

            if (this.scale < 1) this.resetZoom();
        }
    };
}
</script>
@endpush