@extends('layouts.admin')
@section('title', 'Edit Vacancy')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="{{ route('admin.vacancies.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-[#1a5632] font-medium mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to Vacancies
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Edit Vacancy</h2>
        <p class="text-sm text-gray-500 mt-1">Update job details for: <span class="font-bold text-[#0b2415]">{{ $vacancy->title }}</span></p>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm font-bold">
            <ul>@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <form action="{{ route('admin.vacancies.update', $vacancy->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Job Title *</label>
                <input type="text" name="title" value="{{ old('title', $vacancy->title) }}" required
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Department</label>
                    <input type="text" name="department" value="{{ old('department', $vacancy->department) }}"
                        class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Employment Type *</label>
                    <div class="relative">
                        <select name="type" required
                            class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all appearance-none">
                            @foreach(['Full Time', 'Part Time', 'Contract'] as $t)
                            <option value="{{ $t }}" {{ old('type', $vacancy->type) == $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Job Description *</label>
                <textarea name="description" rows="5" required
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all resize-y">{{ old('description', $vacancy->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Requirements / Qualifications</label>
                <textarea name="requirements" rows="4"
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all resize-y">{{ old('requirements', $vacancy->requirements) }}</textarea>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Application Deadline</label>
                    <input type="date" name="deadline" value="{{ old('deadline', $vacancy->deadline?->format('Y-m-d')) }}"
                        class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Replace Vacancy Document (optional)</label>
                    @if($vacancy->document_path)
                    <div class="mb-2">
                        <a href="{{ asset($vacancy->document_path) }}" target="_blank" class="text-xs text-[#1a5632] font-bold hover:underline flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            Current document attached
                        </a>
                    </div>
                    @endif
                    <input type="file" name="document" accept=".pdf,.doc,.docx"
                        class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#1a5632] file:text-white">
                </div>
            </div>

            {{-- Featured Image --}}
            <div>
                <x-admin-image-picker
                    name="featured_image_media_path"
                    file-name="featured_image"
                    label="Featured Image"
                    :current-url="$vacancy->featured_image ? asset($vacancy->featured_image) : null"
                    :current-path="$vacancy->featured_image"
                    accept="image/jpeg,image/png,image/webp"
                    help="Choose from Media or upload JPG, PNG, WEBP. Leave unchanged to keep existing image."
                />
                @if($vacancy->featured_image)
                    <label class="mt-3 flex items-center gap-2 text-xs font-bold text-red-600 cursor-pointer">
                        <input type="checkbox" name="remove_image" value="1" class="w-3.5 h-3.5 rounded border-gray-300 text-red-600">
                        Remove current image
                    </label>
                @endif
            </div>

            <div class="flex items-center gap-3 pt-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $vacancy->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-gray-300 text-[#1a5632] focus:ring-[#1a5632]">
                <label for="is_active" class="text-sm font-bold text-gray-700">Active (visible on website)</label>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-gray-100">
                <button type="submit" class="px-8 py-3 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] transition-colors shadow-sm">
                    Update Vacancy
                </button>
                <a href="{{ route('admin.vacancies.index') }}" class="px-8 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
