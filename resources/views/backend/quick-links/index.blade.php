@extends('layouts.admin')
@section('title', 'Quick Links')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8" x-data="quickLinksPage()">

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Quick Links</h2>
        <p class="text-sm text-gray-500 mt-1">Manage quick links shown in the website footer.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm font-bold shadow-sm">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-5 h-5 text-red-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                Please fix the errors below.
            </div>
            <ul class="list-disc list-inside mt-1 font-normal text-red-600">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-8">

        {{-- ── Add / Edit Form ─────────────────────────────── --}}
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-24">
                <h3 class="font-bold text-xl mb-6 text-[#0b2415] border-b pb-4" x-text="editId ? 'Edit Quick Link' : 'Add Quick Link'">Add Quick Link</h3>

                <form :action="editId ? '{{ url('admin/quick-links') }}/' + editId : '{{ route('admin.quick-links.store') }}'"
                      method="POST"
                      class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" :value="editId ? 'PUT' : 'POST'">

                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Link Title English <span class="text-red-500">*</span></label>
                        <input type="text" name="title" x-model="form.title" required placeholder="e.g., Exam Routine"
                               class="w-full px-4 py-3 border @error('title') border-red-300 @else border-gray-300 @enderror focus:border-[#1a5632] focus:ring-[#1a5632]/20 rounded-xl text-sm transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                        @error('title')<p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Nepali Title --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Link Title Nepali</label>
                        <input type="text" name="title_ne" x-model="form.title_ne" placeholder="जस्तै: परीक्षा तालिका"
                               class="w-full px-4 py-3 border @error('title_ne') border-red-300 @else border-gray-300 @enderror focus:border-[#1a5632] focus:ring-[#1a5632]/20 rounded-xl text-sm transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                        @error('title_ne')<p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- URL --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">URL <span class="text-red-500">*</span></label>
                        <input type="text" name="url" x-model="form.url" required placeholder="https://... or /page-slug"
                               class="w-full px-4 py-3 border @error('url') border-red-300 @else border-gray-300 @enderror focus:border-[#1a5632] focus:ring-[#1a5632]/20 rounded-xl text-sm transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                        @error('url')<p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Sort Order --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Sort Order</label>
                        <input type="number" name="sort_order" x-model="form.sort_order" min="0" max="9999" value="0"
                               class="w-full px-4 py-3 border border-gray-300 focus:border-[#1a5632] focus:ring-[#1a5632]/20 rounded-xl text-sm transition-all bg-gray-50 focus:bg-white">
                    </div>

                    {{-- Open in new tab --}}
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="open_in_new_tab" value="1" x-model="form.open_in_new_tab"
                                   class="w-5 h-5 rounded border-gray-300 text-[#1a5632] focus:ring-[#1a5632] transition-colors">
                            <span class="text-sm font-bold text-gray-800">Open in New Tab</span>
                        </label>
                    </div>

                    {{-- Active --}}
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                   class="w-5 h-5 rounded border-gray-300 text-[#1a5632] focus:ring-[#1a5632] transition-colors">
                            <span class="text-sm font-bold text-gray-800">Show in Footer</span>
                        </label>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 bg-[#1a5632] text-white font-bold py-3 rounded-xl hover:bg-[#0b2415] hover:shadow-lg hover:-translate-y-0.5 transition-all text-sm"
                                x-text="editId ? 'Update Link' : 'Add Link'">Add Link</button>
                        <button type="button" x-show="editId" @click="cancelEdit()"
                                class="px-4 py-3 rounded-xl border border-gray-300 text-gray-600 font-bold text-sm hover:bg-gray-100 transition-all">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── List ─────────────────────────────────────────── --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800">All Quick Links</h3>
                    <span class="text-sm text-gray-400">{{ $quickLinks->count() }} total</span>
                </div>

                @if($quickLinks->isEmpty())
                    <div class="flex flex-col items-center justify-center py-20">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        </div>
                        <p class="text-gray-400 font-medium">No quick links yet.</p>
                        <p class="text-sm text-gray-300 mt-1">Use the form to add footer links.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="px-4 py-3">Title</th>
                                    <th class="px-4 py-3 hidden md:table-cell">URL</th>
                                    <th class="px-4 py-3 text-center w-16 hidden sm:table-cell">Order</th>
                                    <th class="px-4 py-3 text-center w-20 hidden sm:table-cell">New Tab</th>
                                    <th class="px-4 py-3 text-center w-20">Status</th>
                                    <th class="px-4 py-3 text-right w-28">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($quickLinks as $link)
                                <tr class="hover:bg-gray-50 transition-colors {{ $link->is_active ? '' : 'opacity-60' }}">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-gray-900">{{ $link->title }}</p>
                                        @if($link->title_ne)
                                            <p class="mt-0.5 text-xs font-semibold text-gray-500">{{ $link->title_ne }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 hidden md:table-cell">
                                        <a href="{{ $link->url }}" target="_blank" rel="noopener"
                                           class="text-xs text-[#1a5632] hover:underline truncate block max-w-[220px]">{{ $link->url }}</a>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-400 font-mono text-xs hidden sm:table-cell">{{ $link->sort_order }}</td>
                                    <td class="px-4 py-3 text-center hidden sm:table-cell">
                                        @if($link->open_in_new_tab)
                                            <span class="text-xs text-blue-600 font-bold">Yes</span>
                                        @else
                                            <span class="text-xs text-gray-400">No</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            @click="toggleActive({{ $link->id }})"
                                            class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1.5 rounded-lg border transition-colors
                                                   {{ $link->is_active ? 'bg-green-50 border-green-200 text-green-700 hover:bg-green-100' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $link->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                            {{ $link->is_active ? 'On' : 'Off' }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                @click="startEdit({{ $link->id }}, {{ json_encode(['title' => $link->title, 'title_ne' => $link->title_ne, 'url' => $link->url, 'open_in_new_tab' => $link->open_in_new_tab, 'sort_order' => $link->sort_order, 'is_active' => $link->is_active]) }})"
                                                class="text-xs font-bold px-3 py-1.5 rounded-lg border border-[#1a5632]/30 bg-[#1a5632]/5 text-[#1a5632] hover:bg-[#1a5632]/10 transition-colors">
                                                Edit
                                            </button>
                                            <button
                                                @click="deleteLink({{ $link->id }}, '{{ addslashes($link->title) }}')"
                                                class="text-xs font-bold px-3 py-1.5 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function quickLinksPage() {
    return {
        editId: null,
        form: {
            title: '',
            title_ne: '',
            url: '',
            open_in_new_tab: false,
            sort_order: 0,
            is_active: true,
        },

        startEdit(id, data) {
            this.editId = id;
            this.form   = { ...data };
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        cancelEdit() {
            this.editId = null;
            this.form   = { title: '', title_ne: '', url: '', open_in_new_tab: false, sort_order: 0, is_active: true };
        },

        toggleActive(id) {
            fetch(`/admin/quick-links/${id}/toggle`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) window.location.reload();
            })
            .catch(() => alert('Failed to toggle status.'));
        },

        deleteLink(id, title) {
            if (! confirm(`Delete "${title}"? This cannot be undone.`)) return;

            fetch(`/admin/quick-links/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) window.location.reload();
            })
            .catch(() => alert('Failed to delete link.'));
        },
    };
}
</script>
@endpush
