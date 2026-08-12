@extends('layouts.admin')

@section('title', $banner->exists ? 'Edit Banner' : 'Add Banner')
@section('header_title', 'Home Banners')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">{{ $banner->exists ? 'Edit Banner' : 'Add Banner' }}</h2>
        <p class="mt-1 text-sm text-gray-500">This controls the large responsive hero banner on the homepage.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    @php
        $fieldWrap  = 'block rounded-2xl border border-gray-100 bg-gray-50/70 p-4';
        $labelText  = 'text-xs font-black uppercase tracking-wide text-gray-600';
        $field      = 'mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm transition focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
        $fieldNe    = $field . ' font-[Mukta,system-ui]';
    @endphp

    <form method="POST"
          action="{{ $banner->exists ? route('admin.home-banners.update', $banner) : route('admin.home-banners.store') }}"
          enctype="multipart/form-data"
          class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
          x-data="{ tab: 'en' }">
        @csrf
        @if($banner->exists)
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
                        <span class="{{ $labelText }}">Eyebrow <span class="text-gray-400 font-normal normal-case">(small tag above title)</span></span>
                        <input name="eyebrow" value="{{ old('eyebrow', $banner->eyebrow) }}" class="{{ $field }}"
                               placeholder="Community Based Government School">
                    </label>

                    <label class="{{ $fieldWrap }}">
                        <span class="{{ $labelText }}">Sort Order</span>
                        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $banner->sort_order) }}" class="{{ $field }}">
                    </label>

                    <label class="{{ $fieldWrap }} md:col-span-2">
                        <span class="{{ $labelText }}">Title <span class="text-red-500">*</span></span>
                        <input name="title" value="{{ old('title', $banner->title) }}" class="{{ $field }}" required
                               placeholder="Education, Discipline, Creativity, and Commitment">
                    </label>

                    <label class="{{ $fieldWrap }} md:col-span-2">
                        <span class="{{ $labelText }}">Subtitle</span>
                        <textarea name="subtitle" rows="3" class="{{ $field }}"
                                  placeholder="Fostering Excellence, Inspiring Futures.">{{ old('subtitle', $banner->subtitle) }}</textarea>
                    </label>

                    <label class="{{ $fieldWrap }}">
                        <span class="{{ $labelText }}">Primary Button Text</span>
                        <input name="primary_label" value="{{ old('primary_label', $banner->primary_label) }}" class="{{ $field }}"
                               placeholder="Learn More About Us">
                    </label>

                    <label class="{{ $fieldWrap }}">
                        <span class="{{ $labelText }}">Primary Button URL</span>
                        <input name="primary_url" value="{{ old('primary_url', $banner->primary_url) }}" class="{{ $field }}" placeholder="/about">
                    </label>

                    <label class="{{ $fieldWrap }}">
                        <span class="{{ $labelText }}">Secondary Button Text</span>
                        <input name="secondary_label" value="{{ old('secondary_label', $banner->secondary_label) }}" class="{{ $field }}"
                               placeholder="Admission Open">
                    </label>

                    <label class="{{ $fieldWrap }}">
                        <span class="{{ $labelText }}">Secondary Button URL</span>
                        <input name="secondary_url" value="{{ old('secondary_url', $banner->secondary_url) }}" class="{{ $field }}" placeholder="/admissions">
                    </label>

                </div>

            {{-- ══ NEPALI FIELDS ══ --}}
            <div x-show="tab === 'ne'" class="md:col-span-2 grid gap-5 md:grid-cols-2">

                    <div class="md:col-span-2 rounded-xl border border-blue-100 bg-blue-50 px-5 py-3 text-xs font-semibold text-blue-700">
                        ℹ️ Nepali fields are optional. If left blank, the English text is shown to all visitors even when the site is in Nepali.
                    </div>

                    <label class="{{ $fieldWrap }} md:col-span-2">
                        <span class="{{ $labelText }}">Eyebrow – नेपाली</span>
                        <input name="eyebrow_ne" value="{{ old('eyebrow_ne', $banner->eyebrow_ne) }}" class="{{ $fieldNe }}"
                               placeholder="सामुदायिक सरकारी विद्यालय" lang="ne">
                    </label>

                    <label class="{{ $fieldWrap }} md:col-span-2">
                        <span class="{{ $labelText }}">Title – नेपाली</span>
                        <input name="title_ne" value="{{ old('title_ne', $banner->title_ne) }}" class="{{ $fieldNe }}"
                               placeholder="शिक्षा, अनुशासन, सिर्जनात्मकता र प्रतिबद्धता" lang="ne">
                    </label>

                    <label class="{{ $fieldWrap }} md:col-span-2">
                        <span class="{{ $labelText }}">Subtitle – नेपाली</span>
                        <textarea name="subtitle_ne" rows="3" class="{{ $fieldNe }}"
                                  placeholder="उत्कृष्टता प्रवर्द्धन, भविष्य निर्माण। बर्छैन, डोटी, सुदूरपश्चिम प्रदेश, नेपाल" lang="ne">{{ old('subtitle_ne', $banner->subtitle_ne) }}</textarea>
                    </label>

                    <label class="{{ $fieldWrap }}">
                        <span class="{{ $labelText }}">Primary Button – नेपाली</span>
                        <input name="primary_label_ne" value="{{ old('primary_label_ne', $banner->primary_label_ne) }}" class="{{ $fieldNe }}"
                               placeholder="हाम्रो बारेमा थप जान्नुहोस्" lang="ne">
                    </label>

                    <label class="{{ $fieldWrap }}">
                        <span class="{{ $labelText }}">Secondary Button – नेपाली</span>
                        <input name="secondary_label_ne" value="{{ old('secondary_label_ne', $banner->secondary_label_ne) }}" class="{{ $fieldNe }}"
                               placeholder="भर्ना खुला छ" lang="ne">
                    </label>

                    <p class="md:col-span-2 text-xs text-gray-400">
                        Button URLs are shared between languages — set them on the English tab.
                    </p>

                </div>

            {{-- ══ SHARED SETTINGS (always visible) ══ --}}
            <label class="{{ $fieldWrap }}">
                <span class="{{ $labelText }}">Text Position</span>
                <select name="text_position" class="{{ $field }}">
                    <option value="left"   @selected(old('text_position', $banner->text_position) === 'left')>Left</option>
                    <option value="center" @selected(old('text_position', $banner->text_position) === 'center')>Center</option>
                </select>
            </label>

            <div class="{{ $fieldWrap }}">
                <x-admin-image-picker
                    name="selected_image_path"
                    file-name="image"
                    label="Banner Image"
                    :required="! $banner->exists"
                    :current-url="$banner->image_path ? $banner->image_url : null"
                    :current-path="$banner->image_path"
                    help="Choose from Media or upload a new banner image. Leave unchanged to keep the current image."
                />
            </div>

        </div>

        @if($banner->image_path)
            <div class="mt-5 overflow-hidden rounded-2xl border border-gray-100">
                <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="h-56 w-full object-cover">
            </div>
        @endif

        <label class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-gray-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-[#1a5632]" @checked(old('is_active', $banner->is_active))>
            Show this banner
        </label>

        <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.home-banners.index') }}" class="inline-flex justify-center rounded-xl border border-gray-200 px-5 py-3 text-sm font-bold text-gray-700 hover:bg-gray-50">Cancel</a>
            <button class="inline-flex justify-center rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-bold text-white hover:bg-[#0b2415]">Save Banner</button>
        </div>
    </form>
</div>
@endsection
