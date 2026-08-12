@extends('layouts.admin')

@section('title', 'Home Banners')
@section('header_title', 'Home Banners')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Homepage Banners</h2>
            <p class="mt-1 text-sm text-gray-500">Create, edit, reorder, hide, and remove hero banners.</p>
        </div>
        <a href="{{ route('admin.home-banners.create') }}" class="inline-flex justify-center rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-bold text-white hover:bg-[#0b2415]">Add Banner</a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-bold text-[#1a5632]">{{ session('success') }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        @forelse($banners as $banner)
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="relative h-44 bg-gray-100">
                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 right-4">
                        <span class="rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-[#1a5632]">Order {{ $banner->sort_order }}</span>
                        <h3 class="mt-2 line-clamp-1 text-lg font-black text-white">{{ $banner->title }}</h3>
                    </div>
                </div>
                <div class="p-5">
                    <p class="line-clamp-2 text-sm leading-6 text-gray-600">{{ $banner->subtitle ?: 'No subtitle' }}</p>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <form method="POST" action="{{ route('admin.home-banners.toggle', $banner) }}">
                            @csrf
                            @method('PATCH')
                            <button class="rounded-lg px-3 py-1.5 text-xs font-bold {{ $banner->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $banner->is_active ? 'Active' : 'Hidden' }}</button>
                        </form>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.home-banners.edit', $banner) }}" class="rounded-lg px-3 py-2 text-sm font-bold text-blue-700 hover:bg-blue-50">Edit</a>
                            <form method="POST" action="{{ route('admin.home-banners.destroy', $banner) }}" onsubmit="return confirm('Delete this banner?');">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg px-3 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center text-sm font-semibold text-gray-500 lg:col-span-2">No banners created yet.</div>
        @endforelse
    </div>
</div>
@endsection
