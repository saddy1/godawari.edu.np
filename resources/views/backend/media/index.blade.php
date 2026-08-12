{{-- resources/views/backend/media/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Media Library')
@section('header_title', 'Media Library')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#1a5632]">Website Module</p>
            <h2 class="mt-1 text-2xl font-bold text-gray-900">Media Library</h2>
            <p class="mt-1 text-sm text-gray-500">Store images, PDFs, documents, and other website files.</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-600 shadow-sm">
            {{ $media->total() }} files
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

    <section x-data="{
            isDropping: false,
            isUploading: false,
            handleFileSelect() {
                if (this.$refs.fileInput.files.length > 0) {
                    this.isUploading = true;
                    this.$refs.uploadForm.submit();
                }
            }
        }"
        class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <form x-ref="uploadForm" action="{{ route('admin.media-library.upload') }}" method="POST" enctype="multipart/form-data" class="grid gap-3 lg:grid-cols-[13rem_minmax(0,1fr)]">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400" for="media-category">Category</label>
                <select id="media-category" name="category" class="mt-1 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700 outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15">
                    <option value="Documents">Documents</option>
                    <option value="Campus">Campus</option>
                    <option value="Events">Events</option>
                    <option value="Academics">Academics</option>
                    <option value="Sports">Sports</option>
                    <option value="Cultural">Cultural</option>
                    <option value="CMS">CMS</option>
                </select>
                <p class="mt-2 text-xs font-semibold text-gray-400">Upload files into Media.</p>
            </div>

            <div class="relative flex min-h-28 flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-5 text-center transition-colors"
                 :class="isDropping ? 'border-[#1a5632] bg-green-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
                 @dragover.prevent="isDropping = true"
                 @dragleave.prevent="isDropping = false"
                 @drop.prevent="isDropping = false; $refs.fileInput.files = $event.dataTransfer.files; handleFileSelect()">
                <svg x-show="!isUploading" class="mb-2 h-7 w-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6H16a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <svg x-show="isUploading" style="display:none;" class="mb-2 h-7 w-7 animate-spin text-[#1a5632]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <p class="text-sm text-gray-500" x-show="!isUploading"><span class="font-bold text-[#1a5632]">Upload</span> or drag files</p>
                <p class="mt-0.5 text-xs text-gray-400" x-show="!isUploading">Images, PDF, Office, TXT, CSV. Max 10MB.</p>
                <p class="text-sm font-bold text-[#1a5632]" x-show="isUploading" style="display:none;">Uploading media...</p>
                <input x-ref="fileInput" @change="handleFileSelect" type="file" name="files[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" :disabled="isUploading">
            </div>
        </form>
    </section>

    <section class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <div class="mb-4 flex flex-col gap-2 border-b border-gray-100 pb-3 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-lg font-bold text-gray-900">All Media Files</h3>
            <p class="text-xs font-semibold text-gray-400">Hover a media item to rename or manage it.</p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
            @forelse($media as $item)
                @php
                    $isImage = str_starts_with((string) $item->mime_type, 'image/');
                    $extension = strtoupper(pathinfo((string) $item->file_path, PATHINFO_EXTENSION) ?: 'FILE');
                    $sizeKb = $item->size ? number_format($item->size / 1024, 1).' KB' : 'Unknown size';
                @endphp
                <article class="group relative overflow-hidden rounded-xl border border-gray-200 bg-gray-100 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-within:-translate-y-0.5 focus-within:shadow-md">
                    <a href="{{ $item->url }}" target="_blank" class="block aspect-square">
                        <div class="flex h-full w-full items-center justify-center overflow-hidden bg-gray-100">
                            @if($isImage)
                                <img src="{{ $item->url }}" alt="{{ $item->name }}" class="h-full w-full object-cover">
                            @else
                                <div class="text-center">
                                    <svg class="mx-auto h-8 w-8 text-[#1a5632]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9l-6-6H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 3v6h6"/></svg>
                                    <span class="mt-1 block text-[10px] font-extrabold text-gray-500">{{ $extension }}</span>
                                </div>
                            @endif
                        </div>
                    </a>

                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/75 to-transparent p-2">
                        <p class="truncate text-xs font-extrabold text-white" title="{{ $item->name }}">{{ $item->name }}</p>
                        <div class="mt-1 flex items-center gap-1">
                            <span class="rounded bg-white/90 px-1.5 py-0.5 text-[9px] font-black uppercase text-gray-700">{{ $item->category ?: 'Media' }}</span>
                            <span class="rounded bg-white/90 px-1.5 py-0.5 text-[9px] font-black uppercase text-gray-700">{{ $sizeKb }}</span>
                        </div>
                    </div>

                    @if($isImage && $item->show_in_gallery)
                        <span class="absolute left-2 top-2 rounded-full bg-[#1a5632] px-2 py-1 text-[9px] font-black uppercase tracking-wide text-white shadow">Gallery</span>
                    @endif

                    <div class="absolute inset-0 flex flex-col justify-end bg-black/70 p-2 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100">
                        <form action="{{ route('admin.media.update', $item->id) }}" method="POST" class="space-y-1.5">
                            @csrf
                            @method('PATCH')
                            <input name="name" value="{{ old('name', $item->name) }}" aria-label="Rename media" class="w-full rounded-md border-0 bg-white px-2 py-1.5 text-xs font-bold text-gray-800 outline-none focus:ring-2 focus:ring-[#e2a024]">
                            <input name="category" value="{{ old('category', $item->category ?: 'Campus') }}" aria-label="Category" class="w-full rounded-md border-0 bg-white/95 px-2 py-1.5 text-xs font-semibold text-gray-700 outline-none focus:ring-2 focus:ring-[#e2a024]">
                            <input type="hidden" name="caption" value="{{ $item->caption }}">
                            <div class="flex items-center gap-1.5">
                                <a href="{{ $item->url }}" target="_blank" class="flex-1 rounded-md bg-white/15 px-2 py-1.5 text-center text-[11px] font-black text-white ring-1 ring-white/25 hover:bg-white/25">Open</a>
                                <button type="submit" class="flex-1 rounded-md bg-[#e2a024] px-2 py-1.5 text-[11px] font-black text-[#0b2415] hover:bg-[#f4b63e]">Save</button>
                            </div>
                        </form>
                        <form action="{{ route('admin.media.destroy', $item->id) }}" method="POST" class="mt-1.5" onsubmit="return confirm('Permanently delete this media file? Existing pages using this URL may stop showing it.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-md bg-red-600 px-2 py-1.5 text-[11px] font-black text-white hover:bg-red-700">Delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-12 text-center">
                    <p class="text-sm font-bold text-gray-700">No media files uploaded yet.</p>
                    <p class="mt-1 text-xs font-semibold text-gray-400">Use the upload area above to add website files.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $media->links() }}</div>
    </section>
</div>
@endsection
