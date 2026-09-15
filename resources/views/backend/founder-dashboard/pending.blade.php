{{-- resources/views/backend/founder-dashboard/pending.blade.php --}}
@extends('layouts.admin')

@section('title', 'Pending Approvals')

@php
    $totalPending = $items->sum('count');
    $slugFor = fn (string $label) => \Illuminate\Support\Str::slug($label);
@endphp

@section('content')

<div x-data="{ open: null }">

    <div class="mb-5">
        <p class="text-sm font-bold text-gray-500">Everything across the ERP waiting on a decision, in one place. View only — nothing here can be approved, edited, or deleted from this page.</p>
    </div>

    <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Pending Items</p>
        <p class="mt-1 text-3xl font-black {{ $totalPending > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $totalPending }}</p>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($items as $item)
            @php $slug = $slugFor($item->label); @endphp
            <button type="button" @click="open = (open === '{{ $slug }}' ? null : '{{ $slug }}')"
               class="flex items-center gap-3 rounded-2xl border bg-white p-4 text-left shadow-sm transition-colors hover:shadow-md {{ $item->count > 0 ? 'border-red-100' : 'border-gray-100' }}">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-xl {{ $item->count > 0 ? 'bg-red-50' : 'bg-gray-50' }}">{{ $item->icon }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-extrabold text-gray-900">{{ $item->label }}</p>
                    <p class="text-[11px] font-semibold text-gray-400">{{ $item->count > 0 ? 'Tap to view' : 'All clear' }}</p>
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-sm font-black {{ $item->count > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $item->count }}</span>
            </button>
        @endforeach
    </div>

    {{-- Inline detail panel for whichever category is open — nothing here ever navigates away. --}}
    <div x-show="open" x-cloak x-transition class="mt-4 rounded-2xl border border-gray-100 bg-white shadow-sm">
        @foreach($items as $item)
            @php $slug = $slugFor($item->label); @endphp
            <div x-show="open === '{{ $slug }}'">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                    <div>
                        <p class="text-sm font-black text-gray-900">{{ $item->label }}</p>
                        <p class="text-[11px] font-semibold text-gray-400">{{ $item->count }} pending{{ $item->count > count($item->items) ? ' · showing latest '.count($item->items) : '' }}</p>
                    </div>
                    <button type="button" @click="open = null" class="grid h-8 w-8 place-items-center rounded-lg bg-gray-100 text-lg font-black text-gray-500 hover:bg-gray-200">×</button>
                </div>
                <div class="max-h-112 overflow-y-auto p-4">
                    @forelse($item->items as $row)
                        <div class="mb-2 flex items-center gap-3 rounded-xl bg-gray-50 px-3 py-2.5 last:mb-0">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-extrabold text-gray-900">{{ $row->title }}</p>
                                @if($row->subtitle)
                                    <p class="truncate text-[10px] font-semibold text-gray-500">{{ $row->subtitle }}</p>
                                @endif
                            </div>
                            @if($row->date)
                                <span class="shrink-0 text-[10px] font-bold text-gray-400">{{ \Illuminate\Support\Carbon::parse($row->date)->format('j M Y') }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="py-8 text-center text-xs font-bold text-gray-400">Nothing pending here ✓</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

</div>

@endsection
