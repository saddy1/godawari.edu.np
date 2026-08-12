@extends('layouts.admin')

@section('title', 'CMS Pages')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-sm border border-gray-100">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Website CMS</p>
            <h1 class="mt-1 text-2xl font-extrabold text-gray-900">Pages</h1>
        </div>
        <a href="{{ route('admin.cms.pages.create') }}" class="rounded-xl bg-[#1a5632] px-4 py-2 text-sm font-extrabold text-white hover:bg-[#0b2415]">Create Page</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-widest text-gray-400">
                <tr>
                    <th class="px-5 py-3">Page</th>
                    <th class="px-5 py-3">Parent</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Order</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($pages as $page)
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-extrabold text-gray-900">{{ $page->title }}</p>
                            @if($page->title_ne)
                                <p class="mt-0.5 text-xs font-semibold text-gray-500">{{ $page->title_ne }}</p>
                            @endif
                            <a href="{{ $page->url }}" target="_blank" class="text-xs font-semibold text-[#1a5632] hover:underline">{{ $page->url }}</a>
                        </td>
                        <td class="px-5 py-4 text-gray-500">{{ $page->parent?->title ?? '—' }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $page->status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ ucfirst($page->status) }}</span>
                        </td>
                        <td class="px-5 py-4 font-bold text-gray-600">{{ $page->sort_order }}</td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.cms.pages.edit', $page) }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-600 hover:bg-gray-50">Edit</a>
                                <form method="POST" action="{{ route('admin.cms.pages.destroy', $page) }}" onsubmit="return confirm('Delete this page?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm font-semibold text-gray-400">No CMS pages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
