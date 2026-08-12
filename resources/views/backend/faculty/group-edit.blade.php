@extends('layouts.admin')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-[#0b2415]">Edit Group</h2>
        <a href="{{ route('admin.faculty.index') }}" class="text-sm font-bold text-gray-500 hover:text-[#1a5632] transition-colors">&larr; Back to Groups</a>
    </div>

    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        <form action="{{ route('admin.faculty.groups.update', $group) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Group Name</label>
                <input type="text" name="name" value="{{ old('name', $group->name) }}" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200" required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Short Description</label>
                <textarea name="description" rows="4" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200">{{ old('description', $group->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $group->sort_order) }}" class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#1a5632]/20 outline-none border-gray-200">
                </div>
                <label class="flex items-center gap-3 pt-8 text-sm font-bold text-gray-700">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $group->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-[#1a5632] focus:ring-[#1a5632]">
                    Visible on website
                </label>
            </div>

            <button type="submit" class="w-full py-3 bg-[#1a5632] text-white font-bold rounded-xl shadow-lg hover:bg-[#0b2415] transition-all">Update Group</button>
        </form>
    </div>
</div>
@endsection
