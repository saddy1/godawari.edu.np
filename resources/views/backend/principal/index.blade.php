@extends('layouts.admin')
@section('title', 'Principal Settings')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Principal Settings</h2>
        <p class="text-sm text-gray-500 mt-1">Update the principal's name, photo, role, and message shown on the public website.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm font-bold shadow-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.principal.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Photo & Identity --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-100 px-8 py-5 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#1a5632]/10 flex items-center justify-center text-[#1a5632]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-[#0b2415]">Identity</h3>
                    <p class="text-xs text-gray-500">Name, initials, and photo.</p>
                </div>
            </div>

            <div class="p-8 grid sm:grid-cols-[180px_1fr] gap-8 items-start">
                <div>
                    <x-admin-image-picker
                        name="home_principal_image_media_path"
                        file-name="home_principal_image"
                        label="Principal Photo"
                        :current-url="$siteSettings->imageUrl('home_principal_image', 'assets/image/default-placeholder.jpg')"
                        :current-path="$settings['home_principal_image'] ?? null"
                        help="Choose from Media or upload a new principal photo. PNG, JPG, WEBP up to 4 MB."
                    />
                </div>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="principal_name"
                               value="{{ old('principal_name', $settings['principal_name'] ?? $siteSettings->get('principal_name')) }}"
                               required placeholder="e.g. Indra Bahadur Bam"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Initials <span class="text-red-500">*</span></label>
                        <input type="text" name="principal_initials" maxlength="5"
                               value="{{ old('principal_initials', $settings['principal_initials'] ?? $siteSettings->get('principal_initials')) }}"
                               required placeholder="e.g. IB"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        <p class="text-xs text-gray-400 mt-1">Shown in the avatar circle when no photo is set. Max 5 characters.</p>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Role (English) <span class="text-red-500">*</span></label>
                            <input type="text" name="principal_role_en"
                                   value="{{ old('principal_role_en', $settings['principal_role_en'] ?? $siteSettings->get('principal_role_en')) }}"
                                   required placeholder="e.g. Principal, School Name"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Role (Nepali)</label>
                            <input type="text" name="principal_role_ne"
                                   value="{{ old('principal_role_ne', $settings['principal_role_ne'] ?? $siteSettings->get('principal_role_ne')) }}"
                                   placeholder="प्रधानाध्यापक, बर्छैन माध्यमिक विद्यालय"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Message / Quote --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-100 px-8 py-5 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#e2a024]/10 flex items-center justify-center text-[#c98d1f]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-[#0b2415]">Message from the Principal</h3>
                    <p class="text-xs text-gray-500">Section heading and the principal's quote shown on the home and about pages.</p>
                </div>
            </div>

            <div class="p-8 space-y-6">
                <div class="grid sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Section Heading (English) <span class="text-red-500">*</span></label>
                        <input type="text" name="principal_message_en"
                               value="{{ old('principal_message_en', $settings['principal_message_en'] ?? $siteSettings->get('principal_message_en')) }}"
                               required placeholder="Message From the Principal"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Section Heading (Nepali)</label>
                        <input type="text" name="principal_message_ne"
                               value="{{ old('principal_message_ne', $settings['principal_message_ne'] ?? $siteSettings->get('principal_message_ne')) }}"
                               placeholder="प्रधानाध्यापकको सन्देश"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Quote / Message (English) <span class="text-red-500">*</span></label>
                    <textarea name="principal_quote_en" rows="5" required
                              placeholder="Write the principal's message here..."
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all resize-y">{{ old('principal_quote_en', $settings['principal_quote_en'] ?? $siteSettings->get('principal_quote_en')) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Max 2000 characters. Displayed as a blockquote on the website.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Quote / Message (Nepali)</label>
                    <textarea name="principal_quote_ne" rows="5"
                              placeholder="प्रधानाध्यापकको सन्देश नेपालीमा लेख्नुहोस्..."
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-[#1a5632] focus:border-[#1a5632] bg-gray-50 focus:bg-white transition-all resize-y">{{ old('principal_quote_ne', $settings['principal_quote_ne'] ?? $siteSettings->get('principal_quote_ne')) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Sticky Save Bar --}}
        <div class="sticky bottom-0 z-30 -mx-4 px-4 py-3 bg-white/90 backdrop-blur border-t border-gray-200 shadow-lg flex items-center justify-between gap-4">
            <p class="text-sm text-gray-500 hidden sm:block">Changes apply to both the Home and About pages.</p>
            <button type="submit" class="px-8 py-3 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] hover:shadow-lg transition-all text-sm flex items-center gap-2 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Save Principal Settings
            </button>
        </div>
    </form>
</div>
@endsection
