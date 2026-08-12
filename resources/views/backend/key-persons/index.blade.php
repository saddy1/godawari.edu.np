@extends('layouts.admin')
@section('title', 'Key Personnel (Aadaksha)')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8" x-data="keyPersonsPage()">

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Key Personnel <span class="text-sm font-normal text-gray-400 ml-2">(Aadaksha Section)</span></h2>
        <p class="text-sm text-gray-500 mt-1">Manage key persons displayed on the school website — principal, vice-principal, coordinators, etc.</p>
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

        {{-- ── Add Form ─────────────────────────────────────────── --}}
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-24">
                <h3 class="font-bold text-xl mb-6 text-[#0b2415] border-b pb-4" x-text="editId ? 'Edit Person' : 'Add New Person'">Add New Person</h3>

                <form :action="editId ? '{{ url('admin/key-persons') }}/' + editId : '{{ route('admin.key-persons.store') }}'"
                      method="POST"
                      enctype="multipart/form-data"
                      class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" :value="editId ? 'PUT' : 'POST'">

                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" x-model="form.name" required placeholder="e.g., Hari Bahadur Thapa"
                               class="w-full px-4 py-3 border @error('name') border-red-300 @else border-gray-300 @enderror focus:border-[#1a5632] focus:ring-[#1a5632]/20 rounded-xl text-sm transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                        @error('name')<p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Designation --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Designation <span class="text-red-500">*</span></label>
                        <input type="text" name="designation" x-model="form.designation" required placeholder="e.g., Principal"
                               class="w-full px-4 py-3 border @error('designation') border-red-300 @else border-gray-300 @enderror focus:border-[#1a5632] focus:ring-[#1a5632]/20 rounded-xl text-sm transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                        @error('designation')<p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Phone <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" name="phone" x-model="form.phone" placeholder="e.g., 056-XXXXXX"
                               class="w-full px-4 py-3 border border-gray-300 focus:border-[#1a5632] focus:ring-[#1a5632]/20 rounded-xl text-sm transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="email" name="email" x-model="form.email" placeholder="e.g., principal@school.edu.np"
                               class="w-full px-4 py-3 border border-gray-300 focus:border-[#1a5632] focus:ring-[#1a5632]/20 rounded-xl text-sm transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                    </div>

                    {{-- Sort Order --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Sort Order</label>
                        <input type="number" name="sort_order" x-model="form.sort_order" min="0" max="9999" value="0"
                               class="w-full px-4 py-3 border border-gray-300 focus:border-[#1a5632] focus:ring-[#1a5632]/20 rounded-xl text-sm transition-all bg-gray-50 focus:bg-white">
                    </div>

                    {{-- Photo --}}
                    <div>
                        <div x-show="editId && form.existingPhoto" class="mb-2">
                            <img :src="form.existingPhoto" alt="Current photo" class="w-16 h-16 rounded-full object-cover border-2 border-[#1a5632]">
                            <p class="text-xs text-gray-400 mt-1">Choose from Media or upload to replace.</p>
                        </div>
                        <x-admin-image-picker
                            name="photo_media_path"
                            file-name="photo"
                            label="Photo"
                            accept="image/jpeg,image/png,image/webp"
                            help="Choose from Media or upload JPG, PNG, WEBP up to 4 MB."
                        />
                    </div>

                    {{-- Active --}}
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                                   class="w-5 h-5 rounded border-gray-300 text-[#1a5632] focus:ring-[#1a5632] transition-colors">
                            <span class="text-sm font-bold text-gray-800">Show on Website</span>
                        </label>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 bg-[#1a5632] text-white font-bold py-3 rounded-xl hover:bg-[#0b2415] hover:shadow-lg hover:-translate-y-0.5 transition-all text-sm"
                                x-text="editId ? 'Update Person' : 'Add Person'">Add Person</button>
                        <button type="button" x-show="editId" @click="cancelEdit()"
                                class="px-4 py-3 rounded-xl border border-gray-300 text-gray-600 font-bold text-sm hover:bg-gray-100 transition-all">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── List ─────────────────────────────────────────────── --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-800">All Key Persons</h3>
                    <span class="text-sm text-gray-400">{{ $keyPersons->total() }} total</span>
                </div>

                @if($keyPersons->isEmpty())
                    <div class="flex flex-col items-center justify-center py-20">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <p class="text-gray-400 font-medium">No key persons yet.</p>
                        <p class="text-sm text-gray-300 mt-1">Use the form to add the first person.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="px-4 py-3 w-16">Photo</th>
                                    <th class="px-4 py-3">Name / Designation</th>
                                    <th class="px-4 py-3 hidden md:table-cell">Contact</th>
                                    <th class="px-4 py-3 text-center w-16">Order</th>
                                    <th class="px-4 py-3 text-center w-20">Status</th>
                                    <th class="px-4 py-3 text-right w-28">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($keyPersons as $person)
                                <tr class="hover:bg-gray-50 transition-colors {{ $person->is_active ? '' : 'opacity-60' }}">
                                    <td class="px-4 py-3">
                                        <img src="{{ $person->photo_url }}"
                                             alt="{{ $person->name }}"
                                             class="w-10 h-10 rounded-full object-cover border-2 border-gray-200">
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-gray-900">{{ $person->name }}</p>
                                        <p class="text-xs text-[#1a5632] font-semibold mt-0.5">{{ $person->designation }}</p>
                                    </td>
                                    <td class="px-4 py-3 hidden md:table-cell text-gray-500">
                                        @if($person->phone)
                                            <p class="text-xs">{{ $person->phone }}</p>
                                        @endif
                                        @if($person->email)
                                            <p class="text-xs truncate max-w-[180px]">{{ $person->email }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-400 font-mono text-xs">{{ $person->sort_order }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            @click="toggleActive({{ $person->id }}, $el)"
                                            data-active="{{ $person->is_active ? '1' : '0' }}"
                                            class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1.5 rounded-lg border transition-colors
                                                   {{ $person->is_active ? 'bg-green-50 border-green-200 text-green-700 hover:bg-green-100' : 'bg-gray-50 border-gray-200 text-gray-500 hover:bg-gray-100' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $person->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                            {{ $person->is_active ? 'On' : 'Off' }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                @click="startEdit({{ $person->id }}, {{ json_encode(['name' => $person->name, 'designation' => $person->designation, 'phone' => $person->phone ?? '', 'email' => $person->email ?? '', 'sort_order' => $person->sort_order, 'is_active' => $person->is_active, 'existingPhoto' => $person->photo ? asset($person->photo) : '']) }})"
                                                class="text-xs font-bold px-3 py-1.5 rounded-lg border border-[#1a5632]/30 bg-[#1a5632]/5 text-[#1a5632] hover:bg-[#1a5632]/10 transition-colors">
                                                Edit
                                            </button>
                                            <button
                                                @click="deletePerson({{ $person->id }}, '{{ addslashes($person->name) }}')"
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

                    @if($keyPersons->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100">
                            {{ $keyPersons->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Delete form (hidden) --}}
<form id="deletePersonForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
function keyPersonsPage() {
    return {
        editId: null,
        form: {
            name: '',
            designation: '',
            phone: '',
            email: '',
            sort_order: 0,
            is_active: true,
            existingPhoto: '',
        },

        startEdit(id, data) {
            this.editId = id;
            this.form   = { ...data };
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        cancelEdit() {
            this.editId = null;
            this.form   = { name: '', designation: '', phone: '', email: '', sort_order: 0, is_active: true, existingPhoto: '' };
        },

        toggleActive(id, btn) {
            fetch(`/admin/key-persons/${id}/toggle`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            })
            .catch(() => alert('Failed to toggle status.'));
        },

        deletePerson(id, name) {
            if (! confirm(`Delete "${name}"? This cannot be undone.`)) return;

            fetch(`/admin/key-persons/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            })
            .catch(() => alert('Failed to delete person.'));
        },
    };
}
</script>
@endpush
