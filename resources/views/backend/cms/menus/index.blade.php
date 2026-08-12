@extends('layouts.admin')

@section('title', 'CMS Menus')

@section('content')
@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $createMenuOpenByDefault = $viewErrors->has('name') || $viewErrors->has('location');
@endphp
<div x-data="{ createMenuOpen: @js($createMenuOpenByDefault) }" class="space-y-5">
    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-extrabold text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($viewErrors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-800">
            <p class="mb-2 text-sm font-black">Could not save menu. Fix these issues:</p>
            <ul class="list-disc space-y-1 pl-5">
                @foreach($viewErrors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white px-5 py-4 shadow-sm border border-gray-100">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Website CMS</p>
            <h1 class="mt-1 text-2xl font-extrabold text-gray-900">Menus</h1>
        </div>
        <button type="button" @click="createMenuOpen = true" class="rounded-xl bg-[#1a5632] px-4 py-2 text-sm font-extrabold text-white">
            Create Menu
        </button>
    </div>

    <div class="grid gap-5">
        @if($menu)
        @php
            $rootPreviewItems = $menu->items
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->sortBy('sort_order')
                ->values();
        @endphp
        <div class="space-y-5 min-w-0">
            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Current Menu</p>
                        <p class="mt-1 text-sm font-bold text-gray-900">{{ $menu->name }} <span class="font-semibold text-gray-400">/ {{ $menu->location }}</span></p>
                    </div>
                    @if($menus->count() > 1)
                        <div class="flex flex-wrap gap-2">
                            @foreach($menus as $row)
                                <a href="{{ route('admin.cms.menus.index', ['menu' => $row->id]) }}" class="rounded-lg px-3 py-2 text-xs font-black {{ $menu?->id === $row->id ? 'bg-green-50 text-[#1a5632] ring-1 ring-green-100' : 'bg-gray-50 text-gray-500 hover:bg-gray-100' }}">
                                    {{ $row->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.cms.menus.update', $menu) }}">
                @csrf @method('PUT')
                <div class="grid gap-3 lg:grid-cols-[minmax(16rem,1fr)_12rem_auto_auto]">
                    <input name="name" value="{{ $menu->name }}" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                    <input name="location" value="{{ $menu->location }}" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                    <label class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold text-gray-600">
                        <input type="checkbox" name="is_active" value="1" @checked($menu->is_active)> Active
                    </label>
                    <button class="rounded-xl bg-[#1a5632] px-4 py-2 text-xs font-extrabold text-white">Save Menu</button>
                </div>
                </form>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-gray-50 px-5 py-3">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Live Header Preview</p>
                        <p class="mt-0.5 text-xs font-semibold text-gray-500">Hover dropdown items to preview the website menu.</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-extrabold text-gray-500 ring-1 ring-gray-200">{{ $rootPreviewItems->count() }} top links</span>
                </div>
                <div class="bg-white px-4 py-4">
                    <div class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex min-w-[18rem] items-center gap-3">
                                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="h-11 w-11 rounded-xl object-contain bg-gray-50 p-1">
                                <div class="min-w-0">
                                    <p class="truncate text-base font-extrabold leading-tight text-gray-950">{{ $siteSettings->localized('site_name', config('app.name')) }}</p>
                                    <p class="truncate text-xs font-semibold text-gray-500">{{ $siteSettings->localized('site_tagline', 'Fostering Excellence, Inspiring Futures') }}</p>
                                </div>
                            </div>
                            <div class="flex min-w-0 flex-1 flex-wrap items-center justify-end gap-x-5 gap-y-2">
                                @forelse($rootPreviewItems as $previewItem)
                                    @php
                                        $children = $menu->items
                                            ->where('parent_id', $previewItem->id)
                                            ->where('is_active', true)
                                            ->sortBy('sort_order')
                                            ->values();
                                    @endphp
                                    <div class="relative group/preview">
                                        <span class="inline-flex cursor-default items-center gap-1 text-sm font-bold text-gray-700">
                                            {{ $previewItem->localizedLabel() }}
                                            @if($children->isNotEmpty())
                                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            @endif
                                        </span>
                                        @if($children->isNotEmpty())
                                            <div class="invisible absolute left-0 top-full w-64 pt-3 opacity-0 transition-all group-hover/preview:visible group-hover/preview:opacity-100" style="z-index: 100;">
                                                <div class="rounded-2xl border border-gray-100 bg-white p-3 shadow-2xl">
                                                    @foreach($children as $child)
                                                        <div class="rounded-xl px-3 py-2.5 hover:bg-green-50">
                                                            <p class="text-sm font-extrabold text-gray-900">{{ $child->localizedLabel() }}</p>
                                                            @if($child->localizedSubtitle())
                                                                <p class="mt-0.5 text-xs font-semibold text-gray-400">{{ $child->localizedSubtitle() }}</p>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-sm font-semibold text-gray-400">No active menu items yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Add Menu Item</p>
                        <p class="mt-0.5 text-xs font-semibold text-gray-500">Create a top link, dropdown child, CMS page link, or custom URL.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.cms.menus.items.store', $menu) }}" class="space-y-3">
                    @csrf
                    @include('backend.cms.menus.partials.item-fields', ['item' => null, 'menu' => $menu, 'pages' => $pages])
                    <div class="flex justify-end">
                        <button class="rounded-xl bg-[#1a5632] px-5 py-2 text-sm font-extrabold text-white">Add Item</button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Menu Structure</p>
                            <p class="mt-1 text-sm font-semibold text-gray-500">Top-level items are main navbar links. Child items appear in dropdowns.</p>
                        </div>
                        <div class="rounded-full bg-green-50 px-3 py-1 text-xs font-extrabold text-[#1a5632]">{{ $menu->items->count() }} items</div>
                    </div>
                    <div class="space-y-2">
                        @forelse($menu->items->whereNull('parent_id')->sortBy('sort_order') as $rootItem)
                            @include('backend.cms.menus.partials.item-card', ['item' => $rootItem, 'menu' => $menu, 'pages' => $pages, 'level' => 0])
                            @foreach($menu->items->where('parent_id', $rootItem->id)->sortBy('sort_order') as $childItem)
                                @include('backend.cms.menus.partials.item-card', ['item' => $childItem, 'menu' => $menu, 'pages' => $pages, 'level' => 1])
                                @foreach($menu->items->where('parent_id', $childItem->id)->sortBy('sort_order') as $grandChildItem)
                                    @include('backend.cms.menus.partials.item-card', ['item' => $grandChildItem, 'menu' => $menu, 'pages' => $pages, 'level' => 2])
                                @endforeach
                            @endforeach
                        @empty
                            <p class="rounded-xl border-2 border-dashed border-gray-200 py-8 text-center text-sm font-semibold text-gray-400">No menu items yet.</p>
                        @endforelse
                    </div>
            </div>
        </div>
        @else
            <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-8 text-center shadow-sm">
                <p class="text-sm font-bold text-gray-500">No menus yet.</p>
                <button type="button" @click="createMenuOpen = true" class="mt-4 rounded-xl bg-[#1a5632] px-4 py-2 text-sm font-extrabold text-white">
                    Create Menu
                </button>
            </div>
        @endif
    </div>

    <div x-cloak x-show="createMenuOpen" x-transition.opacity class="fixed inset-0 z-[1000] flex items-center justify-center bg-gray-950/55 px-4 py-6">
        <div @click.outside="createMenuOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">New Menu</p>
                    <h2 class="mt-1 text-xl font-black text-gray-950">Create Menu</h2>
                    <p class="mt-1 text-xs font-semibold leading-5 text-gray-500">Location is the machine name. Example: header, footer, resources.</p>
                </div>
                <button type="button" @click="createMenuOpen = false" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-black text-gray-500 hover:bg-gray-50">Close</button>
            </div>

            <form method="POST" action="{{ route('admin.cms.menus.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-black uppercase tracking-widest text-gray-400">Menu name</label>
                    <input name="name" value="{{ old('name') }}" required placeholder="Main Header" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-black uppercase tracking-widest text-gray-400">Location</label>
                    <input name="location" value="{{ old('location') }}" placeholder="header" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="createMenuOpen = false" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-black text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button class="rounded-xl bg-[#1a5632] px-4 py-2 text-sm font-extrabold text-white">Create Menu</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
