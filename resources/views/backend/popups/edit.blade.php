@extends('layouts.admin')
@section('title', 'Edit Popup Notice')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('admin.popups.index') }}" class="text-sm font-bold text-gray-500 hover:text-[#1a5632]">&larr; Back to Popups</a>
    </div>

    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="font-bold text-2xl mb-6 text-[#0b2415] border-b pb-4">Edit Notice: {{ $popup->title }}</h3>
        
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm font-bold mb-6">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('admin.popups.update', $popup->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Notice Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ $popup->title }}" required class="w-full border border-blue-900 rounded-xl focus:ring-[#1a5632] focus:border-[#1a5632] p-3">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Drive / PDF Link <span class="text-gray-400 font-normal">(Optional)</span></label>
                <input type="url" name="link_url" value="{{ $popup->link_url }}" class="w-full border border-blue-900 rounded-xl focus:ring-[#1a5632] focus:border-[#1a5632] p-3">
            </div>

            <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" {{ $popup->is_active ? 'checked' : '' }} class="w-5 h-5 rounded text-[#1a5632] focus:ring-[#1a5632]">
                    <span class="text-sm font-bold text-gray-800">Notice is Active</span>
                </label>
                <p class="text-xs text-gray-500 mt-2 ml-8">If checked, it will display on the frontend (Max 3 allowed).</p>
            </div>

            <div class="border-t border-gray-100 pt-6">
                <x-admin-image-picker
                    name="image_media_path"
                    file-name="image"
                    label="Current Image / Update Image"
                    :current-url="asset($popup->image_path)"
                    :current-path="$popup->image_path"
                    help="Choose from Media or upload a new popup image only if you want to replace the current one."
                />
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full sm:w-auto px-8 bg-[#1a5632] text-white font-bold py-3.5 rounded-xl hover:bg-[#0b2415] transition-colors shadow-md">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
