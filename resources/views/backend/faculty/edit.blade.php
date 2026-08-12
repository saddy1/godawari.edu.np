@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-[#0b2415]">Edit Group Member</h2>
        <a href="{{ route('admin.faculty.index') }}" class="text-sm font-bold text-gray-500 hover:text-[#1a5632] transition-colors">&larr; Back to Groups</a>
    </div>

    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        <form action="{{ route('admin.faculty.update', $faculty->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Group / Committee</label>
                <select name="faculty_group_id" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200" required>
                    <option value="">Select group</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ (string) old('faculty_group_id', $faculty->faculty_group_id) === (string) $group->id ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $faculty->name) }}" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Role/Position</label>
                    <input type="text" name="role" value="{{ old('role', $faculty->role) }}" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Education / Qualification</label>
                <input type="text" name="education" value="{{ old('education', $faculty->education) }}" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200" required>
            </div>

            <x-admin-image-picker
                name="image_media_path"
                file-name="image_file"
                label="Change Profile Image"
                :current-url="$faculty->image_url"
                :current-path="$faculty->image"
                help="Choose from Media or upload a new profile image. Leave unchanged to keep current image."
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Member Display Order</label>
                    <input type="number" name="order" value="{{ old('order', $faculty->order) }}" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200">
                </div>
                <label class="flex items-center gap-3 pt-8 text-sm font-bold text-gray-700">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $faculty->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-[#1a5632] focus:ring-[#1a5632]">
                    Visible on website
                </label>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full py-3 bg-[#1a5632] text-white font-bold rounded-xl shadow-lg hover:bg-[#0b2415] transition-all">
                    Update Member
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
