{{-- resources/views/pages/news-detail.blade.php --}}
@extends('layouts.app')

@section('title', $announcement->title)
@section('meta_description', $announcement->excerpt ?? Str::limit(strip_tags($announcement->content), 150))

@section('content')

{{-- Hero Section --}}
<section class="relative py-20 bg-[#0b2415] overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 30px 30px;"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <nav class="flex items-center gap-2 text-green-200 text-sm font-medium mb-6">
            <a href="{{ route('home') }}" class="hover:text-[#e2a024]">Home</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
            <a href="{{ url('/news') }}" class="hover:text-[#e2a024]">News & Events</a>
        </nav>
        <span class="inline-block px-3 py-1 rounded bg-[#e2a024] text-[#0b2415] text-xs font-bold uppercase tracking-widest mb-4">
            {{ $announcement->category }}
        </span>
        <h1 class="text-3xl lg:text-5xl font-bold text-white leading-tight max-w-4xl">
            {{ $announcement->title }}
        </h1>
        <div class="flex items-center gap-6 mt-8 text-green-100/70 text-sm">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Published: {{ $announcement->created_at->format('M d, Y') }}
            </span>
        </div>
    </div>
</section>

<section class="py-16 bg-[#fdfbf7]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-12">
            
            {{-- Main Content --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl p-6 sm:p-10 shadow-sm border border-gray-100">
                    
                    {{-- Featured Media (Image or PDF Preview) --}}
                    @if($announcement->featured_image)
                    <div class="mb-10 rounded-2xl overflow-hidden shadow-lg border border-gray-200">
                        @php
                            $isLocalPdf = ($announcement->image_type === 'upload' && Str::endsWith(strtolower($announcement->featured_image), '.pdf'));
                            $isDriveLink = ($announcement->image_type === 'link' && str_contains($announcement->featured_image, 'drive.google.com'));
                            
                            // Convert Drive link to Preview Mode for Iframe
                            $previewUrl = $announcement->image_url;
                            if($isDriveLink && preg_match('~/file/d/([^/]+)~', $announcement->featured_image, $reg)) {
                                $previewUrl = "https://drive.google.com/file/d/" . $reg[1] . "/preview";
                            } elseif($isDriveLink && preg_match('/[?&]id=([^&]+)/', $announcement->featured_image, $reg)) {
                                $previewUrl = "https://drive.google.com/file/d/" . $reg[1] . "/preview";
                            }
                        @endphp

                        @if($isLocalPdf || $isDriveLink)
                            {{-- Interactive PDF Previewer --}}
                            <div class="bg-gray-100 p-4 border-b border-gray-200 flex items-center justify-between">
                                <span class="text-sm font-bold text-[#0b2415] flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM13 9V4l5 5h-5z"/></svg>
                                    Document Preview
                                </span>
                                <div class="flex gap-2">
                                    <a href="{{ $announcement->image_url }}" download class="px-3 py-1.5 bg-[#1a5632] text-white text-xs font-bold rounded-lg hover:bg-[#0b2415] transition-all">
                                        Download File
                                    </a>
                                    <a href="{{ $announcement->image_url }}" target="_blank" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-50 transition-all">
                                        Open Full Screen
                                    </a>
                                </div>
                            </div>
                            
                            {{-- The Preview Iframe --}}
                            <div class="relative w-full h-[600px] bg-white">
                                <iframe src="{{ $isLocalPdf ? $announcement->image_url : $previewUrl }}"
                                        class="w-full h-full border-none" 
                                        allow="autoplay">
                                </iframe>
                            </div>
                        @else
                            {{-- Regular Image --}}
                            <img src="{{ $announcement->image_url }}" alt="{{ $announcement->title }}" class="w-full object-cover">
                        @endif
                    </div>
                    @endif

                    {{-- Event Details --}}
                    @if($announcement->type === 'event')
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10 p-6 bg-green-50 rounded-2xl border border-green-100">
                        <div>
                            <p class="text-[10px] font-bold text-green-600 uppercase tracking-widest">Date</p>
                            <p class="font-bold text-[#0b2415]">{{ $announcement->event_date ?? 'TBA' }}</p>
                        </div>
                        <div class="border-y sm:border-y-0 sm:border-x border-green-100 py-4 sm:py-0">
                            <p class="text-[10px] font-bold text-green-600 uppercase tracking-widest">Time</p>
                            <p class="font-bold text-[#0b2415]">{{ $announcement->event_time ?? 'TBA' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-green-600 uppercase tracking-widest">Location</p>
                            <p class="font-bold text-[#0b2415]">{{ $announcement->event_location ?? 'Campus' }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="prose prose-lg prose-green max-w-none text-gray-700 leading-relaxed announcement-body">
                        {!! $announcement->content !!}
                    </div>

                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-8">
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold text-[#0b2415] mb-6">Recent Updates</h3>
                    <div class="space-y-6">
                        @foreach($recentNotices as $recent)
                        <a href="{{ route('news.show', $recent->slug) }}" class="group block">
                            <p class="text-xs text-green-600 font-bold mb-1">{{ $recent->created_at->format('M d, Y') }}</p>
                            <h4 class="text-sm font-bold text-gray-800 group-hover:text-[#1a5632] transition-colors line-clamp-2">
                                {{ $recent->title }}
                            </h4>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<style>
    .announcement-body h2 { font-size: 1.5rem; font-weight: 700; margin-top: 2rem; margin-bottom: 1rem; color: #0b2415; }
    .announcement-body p { margin-bottom: 1.25rem; }
</style>

@endsection
