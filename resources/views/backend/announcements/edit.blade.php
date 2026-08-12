{{-- resources/views/backend/announcements/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Edit Post')
@section('header_title', 'Edit Content')

@section('content')

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Post: {{ Str::limit($announcement->title, 30) }}</h2>
        <a href="{{ route('admin.announcements.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 font-medium transition-colors">
            &larr; Cancel
        </a>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-bold">The post could not be updated. Please correct the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Initialize Alpine state with existing database values --}}
    <form action="{{ route('admin.announcements.update', $announcement->id) }}" method="POST" enctype="multipart/form-data" 
          x-data="{ postType: '{{ old('type', $announcement->type) }}', imageType: '{{ old('image_type', $announcement->image_type) }}' }" 
          class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid lg:grid-cols-3 gap-8">
            
            {{-- Left Column: Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Post Title *</label>
                    <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required 
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-lg font-bold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Content Body *</label>
                    <textarea name="content" id="editor">{{ old('content', $announcement->content) }}</textarea>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Short Excerpt</label>
                    <textarea name="excerpt" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] resize-none">{{ old('excerpt', $announcement->excerpt) }}</textarea>
                </div>
            </div>

            {{-- Right Column: Settings & Meta --}}
            <div class="space-y-6">
                
                {{-- Publish Settings --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Publish Settings</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Post Type *</label>
                        <select name="type" x-model="postType" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#1a5632]">
                            <option value="notice">Notice / Announcement</option>
                            <option value="event">Upcoming Event</option>
                            <option value="news">School News</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Category *</label>
                        <select name="category" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#1a5632]">
                            @foreach(collect(['Academic', 'Admission', 'Event', 'Sports', 'General', 'Result', 'Calendar', 'Download', 'Resource'])->merge([old('category', $announcement->category)])->filter()->unique() as $cat)
                                <option value="{{ $cat }}" {{ old('category', $announcement->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="w-full py-3 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] shadow-md hover:shadow-lg transition-all flex justify-center items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Update Content
                    </button>
                </div>

                {{-- Featured Image Settings --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Featured Image</h3>

                    <div class="flex bg-gray-100 rounded-lg p-1 mb-4">
                        <button type="button" @click="imageType = 'upload'" :class="imageType === 'upload' ? 'bg-white shadow text-[#1a5632]' : 'text-gray-500'" class="flex-1 py-1.5 text-xs font-bold rounded-md transition-all">Upload / Media</button>
                        <button type="button" @click="imageType = 'link'" :class="imageType === 'link' ? 'bg-white shadow text-[#1a5632]' : 'text-gray-500'" class="flex-1 py-1.5 text-xs font-bold rounded-md transition-all">Drive Link</button>
                    </div>

                    <input type="hidden" name="image_type" :value="imageType">

                    <div x-show="imageType === 'upload'">
                        <x-admin-image-picker
                            name="featured_image_media_path"
                            file-name="image_file"
                            label=""
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            media-mode="document"
                            help="Choose an image from Media, or upload JPG, PNG, WEBP, or PDF up to 5MB."
                            :current-url="$announcement->featured_image && $announcement->image_type === 'upload' ? $announcement->image_url : null"
                            :current-path="$announcement->featured_image && $announcement->image_type === 'upload' ? $announcement->featured_image : null"
                        />
                    </div>

                    <div x-show="imageType === 'link'" style="display: none;">
                        <input type="url" name="image_link" value="{{ old('image_link', $announcement->image_type === 'link' ? $announcement->featured_image : '') }}" :required="imageType === 'link'" placeholder="Paste Google Drive link here..." class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#1a5632]">
                        <p class="text-[10px] text-gray-400 mt-2">Ensure the Drive link is set to "Anyone with the link can view".</p>
                    </div>
                </div>

                {{-- Dynamic Event Settings --}}
                <div x-show="postType === 'event'" x-transition class="bg-[#fdfbf7] p-6 rounded-2xl shadow-sm border border-[#e2a024]/30" style="display: none;">
                    <h3 class="font-bold text-[#0b2415] mb-4 border-b border-[#e2a024]/20 pb-2">Event Specifics</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Event Date</label>
                            <input type="date" name="event_date" value="{{ old('event_date', $announcement->event_date) }}" class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-[#1a5632]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Event Time</label>
                            <input type="text" name="event_time" value="{{ old('event_time', $announcement->event_time) }}" class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-[#1a5632]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Location</label>
                            <input type="text" name="event_location" value="{{ old('event_location', $announcement->event_location) }}" class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-[#1a5632]">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
    let announcementEditor;

    ClassicEditor
        .create(document.querySelector('#editor'), {
            toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo' ]
        })
        .then(editor => {
            announcementEditor = editor;
            document.querySelector('form[action="{{ route('admin.announcements.update', $announcement->id) }}"]')
                .addEventListener('submit', () => editor.updateSourceElement());
        })
        .catch(error => { console.error(error); });
</script>

<style>
    .ck-editor__editable_inline { min-height: 400px; border-radius: 0 0 12px 12px !important; }
    .ck-toolbar { border-radius: 12px 12px 0 0 !important; background: #f9fafb !important; }
</style>

@endsection
