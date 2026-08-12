@extends('layouts.admin')

@section('title', 'Homepage Content')
@section('header_title', 'Homepage Content')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Manage Homepage Blocks</h2>
            <p class="mt-1 text-sm text-gray-500">Edit quick links and learning pathway cards shown on the homepage.</p>
        </div>
        <a href="{{ route('admin.home-content.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-bold text-white hover:bg-[#0b2415]">
            Add Content
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-bold text-[#1a5632]">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-gray-100 bg-gray-50 text-xs uppercase tracking-widest text-gray-500">
                    <tr>
                        <th class="px-5 py-4">Order</th>
                        <th class="px-5 py-4">Content</th>
                        <th class="px-5 py-4">Type</th>
                        <th class="px-5 py-4">Category</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4 text-sm font-bold text-gray-700">{{ $item->sort_order }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-50 text-[#1a5632]">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item->icon_path }}"/></svg>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-bold text-gray-900">{{ $item->title }}</span>
                                        <span class="block truncate text-sm text-gray-500">{{ $item->subtitle ?: $item->url }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-lg bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700">{{ str_replace('_', ' ', $item->type) }}</span>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600">{{ $item->category ?: '-' }}</td>
                            <td class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.home-content.toggle', $item) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-lg px-3 py-1 text-xs font-bold {{ $item->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $item->is_active ? 'Active' : 'Hidden' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.home-content.edit', $item) }}" class="rounded-lg px-3 py-2 text-sm font-bold text-blue-700 hover:bg-blue-50">Edit</a>
                                    <form method="POST" action="{{ route('admin.home-content.destroy', $item) }}" onsubmit="return confirm('Delete this homepage content?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg px-3 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm font-semibold text-gray-500">No homepage content found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
