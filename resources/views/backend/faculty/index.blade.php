@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Faculty & Committee Groups</h2>
            <p class="text-sm text-gray-500 mt-1">Manage group detail here. Open a group to view and manage its members.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Create Group / Committee</h3>
        <form action="{{ route('admin.faculty.groups.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            @csrf
            <div class="md:col-span-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Group Name</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. School Management Committee" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-[#1a5632]/20" required>
            </div>
            <div class="md:col-span-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">Short Description</label>
                <input type="text" name="description" value="{{ old('description') }}" placeholder="Optional" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-[#1a5632]/20">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Sort</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-[#1a5632]/20">
            </div>
            <div class="md:col-span-1">
                <button type="submit" class="w-full px-4 py-2.5 bg-[#1a5632] text-white rounded-lg font-bold">Save</button>
            </div>
            <input type="hidden" name="is_active" value="1">
        </form>
    </div>

    @if($ungroupedCount > 0)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
            {{ $ungroupedCount }} member{{ $ungroupedCount === 1 ? '' : 's' }} are not assigned to any group. Open any group and assign them from the ungrouped section.
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Group Detail</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Sort</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Members</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500">Status</th>
                        <th class="px-6 py-4 text-xs font-bold uppercase text-gray-500 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($groups as $group)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900">{{ $group->name }}</p>
                                <p class="text-sm text-gray-500 mt-1">{{ $group->description ?: 'No short description added.' }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $group->sort_order }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $group->members_count }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="font-semibold {{ $group->is_active ? 'text-green-700' : 'text-red-600' }}">{{ $group->is_active ? 'Visible' : 'Hidden' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.faculty.groups.show', $group) }}" class="px-3 py-2 rounded-lg bg-[#1a5632] text-white text-sm font-bold">View Members</a>
                                    <a href="{{ route('admin.faculty.groups.edit', $group) }}" class="px-3 py-2 rounded-lg bg-blue-50 text-blue-700 text-sm font-bold">Edit</a>
                                    <form action="{{ route('admin.faculty.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('Delete this group? Members must be moved or deleted first.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-2 rounded-lg bg-red-50 text-red-700 text-sm font-bold">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">No groups found. Create your first group above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
