@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('admin.faculty.index') }}" class="text-sm font-bold text-gray-500 hover:text-[#1a5632] transition-colors">&larr; Back to Groups</a>
            <h2 class="text-2xl font-bold text-gray-800 mt-2">{{ $group->name }}</h2>
            @if($group->description)
                <p class="text-sm text-gray-500 mt-1 max-w-3xl">{{ $group->description }}</p>
            @endif
            <div class="flex flex-wrap items-center gap-2 mt-3">
                <span class="text-xs font-bold px-2 py-1 rounded bg-gray-100 text-gray-600">Sort {{ $group->sort_order }}</span>
                <span class="text-xs font-bold px-2 py-1 rounded {{ $group->is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600' }}">{{ $group->is_active ? 'Visible' : 'Hidden' }}</span>
                <span class="text-xs font-bold px-2 py-1 rounded bg-gray-100 text-gray-600">{{ $group->members->count() }} Member{{ $group->members->count() === 1 ? '' : 's' }}</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.faculty.create', ['group' => $group->id]) }}" class="px-4 py-2 rounded-lg bg-[#1a5632] text-white text-sm font-bold">Add Member</a>
            <a href="{{ route('admin.faculty.groups.edit', $group) }}" class="px-4 py-2 rounded-lg bg-blue-50 text-blue-700 text-sm font-bold">Edit Group</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Members</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-white border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold uppercase text-gray-500">Member</th>
                        <th class="px-6 py-3 text-xs font-bold uppercase text-gray-500">Education</th>
                        <th class="px-6 py-3 text-xs font-bold uppercase text-gray-500">Order</th>
                        <th class="px-6 py-3 text-xs font-bold uppercase text-gray-500">Status</th>
                        <th class="px-6 py-3 text-xs font-bold uppercase text-gray-500 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($group->members as $teacher)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $teacher->image_url }}" class="w-10 h-10 rounded-full object-cover bg-gray-100">
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $teacher->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $teacher->role }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $teacher->education }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $teacher->order }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="font-semibold {{ $teacher->is_active ? 'text-green-700' : 'text-red-600' }}">{{ $teacher->is_active ? 'Visible' : 'Hidden' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.faculty.edit', $teacher) }}" class="text-blue-600 hover:bg-blue-50 p-2 rounded">Edit</a>
                                    <form action="{{ route('admin.faculty.destroy', $teacher) }}" method="POST" onsubmit="return confirm('Delete this member?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:bg-red-50 p-2 rounded">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No members in this group yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($ungroupedFaculties->isNotEmpty())
        <section class="mt-8 bg-white rounded-xl shadow-sm border border-amber-100 overflow-hidden">
            <div class="px-6 py-4 bg-amber-50 border-b border-amber-100">
                <h3 class="text-lg font-bold text-amber-900">Ungrouped Members</h3>
                <p class="text-sm text-amber-700 mt-1">Edit these members and assign a group.</p>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($ungroupedFaculties as $teacher)
                    <div class="px-6 py-4 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <img src="{{ $teacher->image_url }}" class="w-10 h-10 rounded-full object-cover bg-gray-100">
                            <div>
                                <p class="font-bold text-gray-900">{{ $teacher->name }}</p>
                                <p class="text-xs text-gray-500">{{ $teacher->role }}</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.faculty.edit', $teacher) }}" class="text-blue-600 hover:bg-blue-50 p-2 rounded">Assign Group</a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
