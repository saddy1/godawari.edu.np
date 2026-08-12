{{-- resources/views/backend/gallery/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Gallery')
@section('header_title', 'Gallery')

@section('content')
<div x-data="{ pickerOpen: false, tab: 'media' }" class="max-w-7xl mx-auto space-y-5">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#1a5632]">Website Module</p>
            <h2 class="mt-1 text-2xl font-bold text-gray-900">Gallery</h2>
            <p class="mt-1 text-sm text-gray-500">Manage images shown on the public gallery page.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('gallery') }}" target="_blank" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 shadow-sm hover:bg-gray-50">View Public</a>
            <button type="button" @click="pickerOpen = true; tab = 'media'" class="rounded-lg bg-[#1a5632] px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#0b2415]">Add Image</button>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-[#1a5632]/20 bg-green-50 p-4 text-sm font-bold text-[#1a5632]">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-600">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="mb-4 flex flex-col gap-2 border-b border-gray-100 pb-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Selected Gallery Images</h3>
                <p class="mt-1 text-xs font-semibold text-gray-400">Remove hides from Gallery. Delete removes from Media and deletes the file.</p>
            </div>
            <span class="text-xs font-bold uppercase tracking-widest text-gray-400">{{ $selectedImages->count() }} selected</span>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
            @forelse($selectedImages as $image)
                <article class="group relative overflow-hidden rounded-xl border border-gray-200 bg-gray-100 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-within:-translate-y-0.5 focus-within:shadow-md">
                    <a href="{{ $image->url }}" target="_blank" class="block aspect-square">
                        <img src="{{ $image->url }}" alt="{{ $image->name }}" class="h-full w-full object-cover">
                    </a>
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 to-transparent p-2">
                        <p class="truncate text-xs font-extrabold text-white">{{ $image->name }}</p>
                        <p class="truncate text-[10px] font-bold text-white/75">{{ $image->category ?: 'Campus' }}</p>
                    </div>

                    <div class="absolute inset-0 flex flex-col justify-end bg-black/70 p-2 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100">
                        <form action="{{ route('admin.media.update', $image->id) }}" method="POST" class="space-y-1.5">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="name" value="{{ $image->name }}">
                            <input type="hidden" name="category" value="{{ $image->category ?: 'Campus' }}">
                            <input name="caption" value="{{ old('caption', $image->caption) }}" aria-label="Caption" placeholder="Caption" class="w-full rounded-md border-0 bg-white px-2 py-1.5 text-xs font-bold text-gray-800 outline-none focus:ring-2 focus:ring-[#e2a024]">
                            @if($hasGallerySelection)
                                <input type="hidden" name="show_in_gallery" value="1">
                            @endif
                            <button type="submit" class="w-full rounded-md bg-[#e2a024] px-2 py-1.5 text-[11px] font-black text-[#0b2415] hover:bg-[#f4b63e]">Save Caption</button>
                        </form>
                        <div class="mt-1.5 grid grid-cols-2 gap-1.5">
                            <form action="{{ route('admin.media.update', $image->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="name" value="{{ $image->name }}">
                                <input type="hidden" name="category" value="{{ $image->category ?: 'Campus' }}">
                                <input type="hidden" name="caption" value="{{ $image->caption }}">
                                <button type="submit" @disabled(! $hasGallerySelection) class="w-full rounded-md bg-white/15 px-2 py-1.5 text-[11px] font-black text-white ring-1 ring-white/25 hover:bg-white/25 disabled:cursor-not-allowed disabled:opacity-50">Remove</button>
                            </form>
                            <form action="{{ route('admin.media.destroy', $image->id) }}" method="POST" onsubmit="return confirm('Delete this image from Gallery and Media? This removes the file too.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full rounded-md bg-red-600 px-2 py-1.5 text-[11px] font-black text-white hover:bg-red-700">Delete</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-16 text-center">
                    <p class="text-sm font-bold text-gray-700">No images selected for Gallery.</p>
                    <button type="button" @click="pickerOpen = true; tab = 'media'" class="mt-4 rounded-lg bg-[#1a5632] px-4 py-2 text-sm font-bold text-white hover:bg-[#0b2415]">Add Image</button>
                </div>
            @endforelse
        </div>
    </section>

    <div x-show="pickerOpen"
         x-transition.opacity
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4"
         style="display:none;"
         @keydown.escape.window="pickerOpen = false">
        <div class="flex max-h-[88vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" @click.outside="pickerOpen = false">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Add Image</h3>
                    <p class="text-xs font-semibold text-gray-400">Choose from Media or upload from computer.</p>
                </div>
                <button type="button" @click="pickerOpen = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex gap-2 border-b border-gray-100 px-5 pt-3">
                <button type="button" @click="tab = 'media'" :class="tab === 'media' ? 'border-[#1a5632] text-[#1a5632]' : 'border-transparent text-gray-500'" class="border-b-2 px-3 pb-3 text-sm font-bold">Choose From Media</button>
                <button type="button" @click="tab = 'upload'" :class="tab === 'upload' ? 'border-[#1a5632] text-[#1a5632]' : 'border-transparent text-gray-500'" class="border-b-2 px-3 pb-3 text-sm font-bold">Upload From Computer</button>
            </div>

            <div class="overflow-y-auto p-5">
                <div x-show="tab === 'media'" class="space-y-4">
                    <form action="{{ route('admin.gallery.select') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                            @forelse($availableImages as $image)
                                <label class="group relative block overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                                    <input type="checkbox" name="media_ids[]" value="{{ $image->id }}" class="peer sr-only">
                                    <img src="{{ $image->url }}" alt="{{ $image->name }}" class="aspect-square w-full object-cover transition group-hover:scale-105">
                                    <span class="absolute inset-0 bg-[#1a5632]/0 ring-0 ring-inset ring-[#1a5632] transition peer-checked:bg-[#1a5632]/25 peer-checked:ring-4"></span>
                                    <span class="absolute right-1.5 top-1.5 hidden rounded-full bg-[#1a5632] p-1 text-white shadow peer-checked:block">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span class="absolute bottom-0 left-0 right-0 truncate bg-black/60 px-1.5 py-1 text-[10px] font-bold text-white">{{ $image->name }}</span>
                                </label>
                            @empty
                                <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-12 text-center">
                                    <p class="text-sm font-bold text-gray-700">No media images available.</p>
                                    <p class="mt-1 text-xs font-semibold text-gray-400">Upload from computer to add new gallery images.</p>
                                </div>
                            @endforelse
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" @disabled($availableImages->isEmpty() || ! $hasGallerySelection) class="rounded-lg bg-[#1a5632] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0b2415] disabled:cursor-not-allowed disabled:opacity-50">Add Selected</button>
                        </div>
                    </form>
                </div>

                <div x-show="tab === 'upload'" style="display:none;">
                    <form x-data="{
                            isDropping: false,
                            isUploading: false,
                            handleFileSelect() {
                                if (this.$refs.fileInput.files.length > 0) {
                                    this.isUploading = true;
                                    this.$refs.uploadForm.submit();
                                }
                            }
                        }"
                        x-ref="uploadForm"
                        action="{{ route('admin.gallery.upload') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="grid gap-4 md:grid-cols-[13rem_minmax(0,1fr)]">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Category</label>
                            <select name="category" class="mt-1 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-bold text-gray-700 outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15">
                                <option value="Campus">Campus</option>
                                <option value="Events">Events</option>
                                <option value="Academics">Academics</option>
                                <option value="Sports">Sports</option>
                                <option value="Cultural">Cultural</option>
                            </select>
                        </div>

                        <div class="relative flex min-h-44 flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-6 text-center transition-colors"
                             :class="isDropping ? 'border-[#1a5632] bg-green-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
                             @dragover.prevent="isDropping = true"
                             @dragleave.prevent="isDropping = false"
                             @drop.prevent="isDropping = false; $refs.fileInput.files = $event.dataTransfer.files; handleFileSelect()">
                            <svg x-show="!isUploading" class="mb-2 h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6H16a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <p class="text-sm text-gray-500" x-show="!isUploading"><span class="font-bold text-[#1a5632]">Upload</span> or drag images</p>
                            <p class="mt-0.5 text-xs text-gray-400" x-show="!isUploading">JPG, PNG, WEBP, GIF. Max 5MB.</p>
                            <p class="text-sm font-bold text-[#1a5632]" x-show="isUploading" style="display:none;">Uploading...</p>
                            <input x-ref="fileInput" @change="handleFileSelect" type="file" name="files[]" multiple accept="image/*" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" :disabled="isUploading">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
