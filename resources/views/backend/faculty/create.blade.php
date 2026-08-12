@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-[#0b2415]">Add Group Member</h2>
        <a href="{{ $selectedGroup ? route('admin.faculty.groups.show', $selectedGroup) : route('admin.faculty.index') }}" class="text-sm font-bold text-gray-500 hover:text-[#1a5632] transition-colors">&larr; {{ $selectedGroup ? 'Back to Members' : 'Back to Groups' }}</a>
    </div>

    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        <form action="{{ route('admin.faculty.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Group / Committee</label>
                @if($selectedGroup)
                    <input type="hidden" name="faculty_group_id" value="{{ old('faculty_group_id', $selectedGroup->id) }}">
                    <div class="w-full px-4 py-2.5 border rounded-xl bg-gray-50 border-gray-200 text-gray-800 font-semibold">
                        {{ $selectedGroup->name }}
                    </div>
                    <p class="text-xs text-gray-500 mt-2">This member will be added to this group.</p>
                @else
                    <select name="faculty_group_id" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200" required>
                        <option value="">Select group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ (string) old('faculty_group_id', $selectedGroupId) === (string) $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
                @if($groups->isEmpty())
                    <p class="text-xs text-red-600 mt-2">Create a group before adding members.</p>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Role/Position</label>
                    <input type="text" name="role" value="{{ old('role') }}" placeholder="e.g. Chairperson, Member, Principal" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Education / Qualification</label>
                <input type="text" name="education" value="{{ old('education') }}" placeholder="e.g. M.Ed., B.Sc., Community Representative" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200" required>
            </div>

            <div>
                <x-admin-image-picker
                    name="image_media_path"
                    file-name="image_file"
                    label="Profile Image"
                    help="Choose from Media or upload a new faculty profile image."
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Member Display Order</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200">
                </div>
                <label class="flex items-center gap-3 pt-8 text-sm font-bold text-gray-700">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-[#1a5632] focus:ring-[#1a5632]">
                    Visible on website
                </label>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full py-3 bg-[#1a5632] text-white font-bold rounded-xl shadow-lg hover:bg-[#0b2415] transition-all">Save Member</button>
            </div>
        </form>
    </div>
</div>
@endsection
