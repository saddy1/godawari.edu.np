{{-- resources/views/backend/seo/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Dynamic SEO Management')

@section('content')

<div class="max-w-4xl mx-auto px-4 py-8" 
     x-data="seoManager({{ \Illuminate\Support\Js::from($seoSettings ?? []) }})">
    
    <div class="bg-[#1a5632] rounded-[2rem] p-8 text-white shadow-lg mb-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-[#e2a024] rounded-full blur-3xl opacity-20 -translate-y-1/2 translate-x-1/4"></div>
        <div class="relative z-10">
            <h2 class="text-3xl font-bold mb-2">AI-Powered SEO Optimizer</h2>
            <p class="text-green-100">Select a page and let AI generate SEO from the current school settings, page content, notices, faculty, and gallery data.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-[#1a5632]/20 text-[#1a5632] rounded-xl p-4 text-sm font-bold flex items-center gap-3">
            <span class="w-6 h-6 bg-[#1a5632] text-white rounded-full flex items-center justify-center shrink-0">✓</span>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        
        {{-- Page Selector --}}
        <div class="mb-8 border-b border-gray-100 pb-8">
            <label class="block text-sm font-bold text-gray-700 mb-3">Select Page to Edit</label>
            <div class="flex flex-wrap gap-3">
                <template x-for="page in pages" :key="page">
                    <button @click="loadPageData(page)"
                            :class="activePage === page ? 'bg-[#1a5632] text-white shadow-md' : 'bg-gray-50 text-gray-600 hover:bg-green-50 hover:text-[#1a5632] border border-gray-200'"
                            class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all capitalize"
                            x-text="page.replace('_', ' ')">
                    </button>
                </template>
            </div>
        </div>

        {{-- SEO Form --}}
        <form action="{{ route('admin.seo.store') }}" method="POST" x-show="activePage" style="display: none;">
            @csrf
            <input type="hidden" name="page_name" :value="activePage">

            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-[#0b2415] capitalize">Editing: <span x-text="activePage?.replace('_', ' ')"></span></h3>
                
                {{-- AI Generate Button --}}
                <button type="button" @click="generateAI()" :disabled="isGenerating"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-purple-600 to-blue-600 text-white font-bold rounded-xl hover:shadow-lg hover:opacity-90 transition-all disabled:opacity-50 text-sm">
                    <span x-show="!isGenerating">Generate with AI</span>
                    <span x-show="isGenerating" class="flex items-center gap-2" style="display: none;">
                        <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Reading Data...
                    </span>
                </button>
            </div>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Meta Title</label>
                    <input type="text" name="meta_title" x-model="formData.meta_title"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] outline-none transition-all">
                    <p class="text-xs text-gray-500 mt-1" :class="formData.meta_title?.length > 60 ? 'text-red-500' : ''">Optimal: 50-60 characters (<span x-text="formData.meta_title?.length || 0"></span>)</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Meta Description</label>
                    <textarea name="meta_description" x-model="formData.meta_description" rows="3"
                              class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] outline-none transition-all resize-none"></textarea>
                    <p class="text-xs text-gray-500 mt-1" :class="formData.meta_description?.length > 160 ? 'text-red-500' : ''">Optimal: 150-160 characters (<span x-text="formData.meta_description?.length || 0"></span>)</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Meta Keywords (Comma Separated)</label>
                    <textarea name="meta_keywords" x-model="formData.meta_keywords" rows="3"
                              class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] outline-none transition-all resize-none"></textarea>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
                <button type="submit" class="px-8 py-3.5 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] hover:shadow-lg hover:-translate-y-0.5 transition-all">
                    Save SEO Settings
                </button>
            </div>
        </form>

        {{-- Select Prompt State --}}
        <div x-show="!activePage" class="text-center py-16">
            <div class="text-4xl mb-4">📄</div>
            <p class="text-gray-500 font-medium">Please select a page from above to edit its SEO settings.</p>
        </div>

    </div>
</div>

<script>
    function seoManager(existingData) {
        return {
            pages: ['home', 'about', 'admissions', 'notices', 'gallery', 'elementary', 'middle_school', 'secondary', 'faculty'],
            activePage: null,
            isGenerating: false,
            existingSettings: existingData || {},
            formData: {
                meta_title: '',
                meta_description: '',
                meta_keywords: ''
            },

            loadPageData(page) {
                this.activePage = page;
                if (this.existingSettings[page]) {
                    this.formData.meta_title = this.existingSettings[page].meta_title || '';
                    this.formData.meta_description = this.existingSettings[page].meta_description || '';
                    this.formData.meta_keywords = this.existingSettings[page].meta_keywords || '';
                } else {
                    this.formData = { meta_title: '', meta_description: '', meta_keywords: '' };
                }
            },

            async generateAI() {
                if (!this.activePage) return;
                this.isGenerating = true;

                try {
                    const response = await fetch('{{ route('admin.seo.generate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ page_name: this.activePage })
                    });

                    const data = await response.json();

                    if (data.error) {
                        alert('AI Generation Failed: ' + data.error);
                    } else {
                        this.formData.meta_title = data.meta_title;
                        this.formData.meta_description = data.meta_description;
                        this.formData.meta_keywords = data.meta_keywords;
                    }
                } catch (error) {
                    alert('Network error occurred. Make sure your OpenAI API key is configured.');
                } finally {
                    this.isGenerating = false;
                }
            }
        }
    }
</script>
@endsection
