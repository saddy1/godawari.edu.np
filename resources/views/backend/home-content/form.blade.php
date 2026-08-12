@extends('layouts.admin')

@section('title', $item->exists ? 'Edit Homepage Content' : 'Add Homepage Content')
@section('header_title', 'Homepage Content')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">{{ $item->exists ? 'Edit Homepage Content' : 'Add Homepage Content' }}</h2>
        <p class="mt-1 text-sm text-gray-500">Use quick links for shortcuts and featured content for programs or other homepage cards.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    @php
        $fieldWrap = 'block rounded-2xl border border-gray-100 bg-gray-50/70 p-4';
        $labelText = 'text-xs font-black uppercase tracking-wide text-gray-600';
        $field     = 'mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm transition focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
        $fieldNe   = $field . ' font-[Mukta,system-ui]';
    @endphp

    <form method="POST"
          action="{{ $item->exists ? route('admin.home-content.update', $item) : route('admin.home-content.store') }}"
          enctype="multipart/form-data"
          class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
          x-data="{ tab: 'en' }">
        @csrf
        @if($item->exists)
            @method('PUT')
        @endif

        {{-- ── Language Tab Switcher ── --}}
        <div class="mb-6 flex items-center gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1 w-fit">
            <button type="button"
                    @click="tab = 'en'"
                    :class="tab === 'en' ? 'bg-[#1a5632] text-white shadow-sm' : 'text-gray-500 hover:text-gray-800'"
                    class="rounded-lg px-5 py-2 text-sm font-black transition-all">
                🇬🇧 English
            </button>
            <button type="button"
                    @click="tab = 'ne'"
                    :class="tab === 'ne' ? 'bg-[#1a5632] text-white shadow-sm' : 'text-gray-500 hover:text-gray-800'"
                    class="rounded-lg px-5 py-2 text-sm font-black transition-all">
                🇳🇵 नेपाली
            </button>
        </div>

        <div class="grid gap-5 md:grid-cols-2">

            {{-- ══ ENGLISH FIELDS ══ --}}
            <div x-show="tab === 'en'" class="md:col-span-2 grid gap-5 md:grid-cols-2">

                <label class="{{ $fieldWrap }}">
                    <span class="{{ $labelText }}">Title <span class="text-red-500">*</span></span>
                    <input name="title" value="{{ old('title', $item->title) }}" class="{{ $field }}" required
                           placeholder="Academic Calendar">
                </label>

                <label class="{{ $fieldWrap }}">
                    <span class="{{ $labelText }}">Subtitle</span>
                    <input name="subtitle" value="{{ old('subtitle', $item->subtitle) }}" class="{{ $field }}"
                           placeholder="Class schedule">
                </label>

            </div>

            {{-- ══ NEPALI FIELDS ══ --}}
            <div x-show="tab === 'ne'" class="md:col-span-2 grid gap-5 md:grid-cols-2">

                <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50 px-5 py-3 text-xs font-semibold text-blue-700">
                    ℹ️ Nepali fields are optional. If left blank, the English text is shown to all visitors even when the site is in Nepali.
                </div>

                <label class="{{ $fieldWrap }}">
                    <span class="{{ $labelText }}">Title – नेपाली</span>
                    <input name="title_ne" value="{{ old('title_ne', $item->title_ne) }}" class="{{ $fieldNe }}"
                           placeholder="शैक्षिक पात्रो" lang="ne">
                </label>

                <label class="{{ $fieldWrap }}">
                    <span class="{{ $labelText }}">Subtitle – नेपाली</span>
                    <input name="subtitle_ne" value="{{ old('subtitle_ne', $item->subtitle_ne) }}" class="{{ $fieldNe }}"
                           placeholder="कक्षा तालिका" lang="ne">
                </label>

            </div>

            {{-- ══ SHARED SETTINGS (always visible) ══ --}}
            <label class="{{ $fieldWrap }}">
                <span class="{{ $labelText }}">Type</span>
                <select name="type" class="{{ $field }}">
                    <option value="quick_link"      @selected(old('type', $item->type) === 'quick_link')>Quick link</option>
                    <option value="learning_pathway" @selected(old('type', $item->type) === 'learning_pathway')>Featured content</option>
                </select>
            </label>

            <label class="{{ $fieldWrap }}">
                <span class="{{ $labelText }}">Category</span>
                <input name="category" value="{{ old('category', $item->category) }}" class="{{ $field }}"
                       placeholder="routine, result, academic">
            </label>

            <label class="{{ $fieldWrap }}">
                <span class="{{ $labelText }}">URL</span>
                <input name="url" value="{{ old('url', $item->url) }}" class="{{ $field }}"
                       placeholder="/notices?category=Routine">
            </label>

            <label class="{{ $fieldWrap }}">
                <span class="{{ $labelText }}">Icon</span>
                <select name="icon_key" class="{{ $field }}">
                    @foreach($icons as $key => $path)
                        <option value="{{ $key }}" @selected(old('icon_key', $item->icon_key) === $key)>{{ ucfirst($key) }}</option>
                    @endforeach
                </select>
            </label>

            <label class="{{ $fieldWrap }}">
                <span class="{{ $labelText }}">Sort Order</span>
                <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $item->sort_order) }}" class="{{ $field }}">
            </label>

            <div class="{{ $fieldWrap }}">
                <x-admin-image-picker
                    name="selected_image_path"
                    file-name="image"
                    label="Image"
                    :current-url="$item->image_url"
                    :current-path="$item->image_path"
                    help="Choose from Media or upload a new image for this homepage content item."
                />
            </div>

        </div>

        <label class="{{ $fieldWrap }} mt-5">
            <span class="{{ $labelText }}">Description</span>
            <textarea name="description" rows="4" class="{{ $field }}">{{ old('description', $item->description) }}</textarea>
        </label>

        <label class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-[#1a5632]" @checked(old('is_active', $item->is_active))>
            Show on homepage
        </label>

        <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.home-content.index') }}" class="inline-flex justify-center rounded-xl border border-gray-200 px-5 py-3 text-sm font-bold text-gray-700 hover:bg-gray-50">Cancel</a>
            <button class="inline-flex justify-center rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-bold text-white hover:bg-[#0b2415]">Save Content</button>
        </div>
    </form>
</div>
@endsection
