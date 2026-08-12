@extends('layouts.admin')
@section('title', 'Post New Vacancy')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="{{ route('admin.vacancies.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-[#1a5632] font-medium mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to Vacancies
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Post New Vacancy</h2>
        <p class="text-sm text-gray-500 mt-1">Fill in the job details. Optionally attach a vacancy notice PDF.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm font-bold">
            <ul>@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <form action="{{ route('admin.vacancies.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Job Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Mathematics Teacher (Secondary Level)"
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Department</label>
                    <input type="text" name="department" value="{{ old('department') }}" placeholder="e.g. Teaching, Administration, Support"
                        class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Employment Type *</label>
                    <div class="relative">
                        <select name="type" required
                            class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all appearance-none">
                            <option value="Full Time" {{ old('type') == 'Full Time' ? 'selected' : '' }}>Full Time</option>
                            <option value="Part Time" {{ old('type') == 'Part Time' ? 'selected' : '' }}>Part Time</option>
                            <option value="Contract" {{ old('type') == 'Contract' ? 'selected' : '' }}>Contract</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Job Description *</label>
                <textarea name="description" rows="5" required placeholder="Describe the role, responsibilities, working hours, etc."
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all resize-y">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Requirements / Qualifications</label>
                <textarea name="requirements" rows="4" placeholder="List required qualifications, experience, skills, etc. (one per line)"
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all resize-y">{{ old('requirements') }}</textarea>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Application Deadline</label>
                    <input type="date" name="deadline" value="{{ old('deadline') }}"
                        class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Attach Vacancy Notice (PDF/DOC)</label>
                    <input type="file" name="document" accept=".pdf,.doc,.docx"
                        class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#1a5632] file:text-white">
                    <p class="text-xs text-gray-400 mt-1">Max 10MB. Applicants can download this.</p>
                </div>
            </div>

            <div>
                <x-admin-image-picker
                    name="featured_image_media_path"
                    file-name="featured_image"
                    label="Featured Image"
                    accept="image/jpeg,image/png,image/webp"
                    help="Choose from Media or upload JPG, PNG, WEBP. Max 4MB. Shown on the vacancies page."
                />
            </div>

            <div class="flex items-center gap-3 pt-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-gray-300 text-[#1a5632] focus:ring-[#1a5632]">
                <label for="is_active" class="text-sm font-bold text-gray-700">Publish immediately (visible on website)</label>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-gray-100">
                <button type="submit" class="px-8 py-3 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] transition-colors shadow-sm">
                    Post Vacancy
                </button>
                <a href="{{ route('admin.vacancies.index') }}" class="px-8 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
