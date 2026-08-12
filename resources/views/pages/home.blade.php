@extends('layouts.app')

@section('title', __('site.college_home.meta_title'))
@section('meta_description', __('site.college_home.meta_description'))

@push('modals')
@if(isset($popups) && $popups->isNotEmpty())
<div x-data="{ open: true, active: 0, total: {{ $popups->count() }} }"
     x-show="open"
     x-transition.opacity
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-[99999] flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-md"
     style="display:none;">
    <div class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-white/15 bg-white shadow-2xl">
        <button type="button" @click="open = false" aria-label="{{ __('site.common.close') }}"
                class="absolute right-3 top-3 z-20 grid h-10 w-10 place-items-center rounded-full bg-slate-950/75 text-white backdrop-blur hover:bg-amber-500 hover:text-slate-950">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>

        @foreach($popups as $index => $popup)
        <div x-show="active === {{ $index }}" class="min-h-0 flex-1 overflow-y-auto bg-slate-100 p-3 sm:p-5">
            @if($popup->link_url)<a href="{{ $popup->link_url }}" target="_blank" rel="noopener">@endif
                <img src="{{ asset($popup->image_path) }}" alt="{{ $popup->title }}" class="mx-auto max-h-[70dvh] w-auto rounded-xl bg-white object-contain shadow-lg">
            @if($popup->link_url)</a>@endif
        </div>
        @endforeach

        <div class="flex items-center justify-between gap-4 border-t border-slate-200 bg-white px-4 py-3">
            <div class="min-w-0">
                @foreach($popups as $index => $popup)
                    <p x-show="active === {{ $index }}" class="truncate text-sm font-extrabold text-slate-900">{{ $popup->title }}</p>
                @endforeach
                <p class="mt-0.5 text-xs font-bold text-slate-400"><span x-text="active + 1"></span> / {{ $popups->count() }}</p>
            </div>
            <button type="button" @click="active < total - 1 ? active++ : open = false"
                    class="rounded-lg bg-[var(--theme-primary)] px-5 py-2.5 text-sm font-black text-white hover:brightness-110">
                <span x-text="active < total - 1 ? @js(__('site.common.next')) : @js(__('site.common.close'))"></span>
            </button>
        </div>
    </div>
</div>
@endif
@endpush

@section('content')
    @include('pages.partials.home-portal')
@endsection
