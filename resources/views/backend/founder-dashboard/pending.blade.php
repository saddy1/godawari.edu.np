{{-- resources/views/backend/founder-dashboard/pending.blade.php --}}
@extends('layouts.admin')

@section('title', 'Pending Approvals')

@php
    $totalPending = $items->sum('count');
@endphp

@section('content')

    <div class="mb-5">
        <p class="text-sm font-bold text-gray-500">Everything across the ERP waiting on a decision, in one place.</p>
    </div>

    <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Pending Items</p>
        <p class="mt-1 text-3xl font-black {{ $totalPending > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $totalPending }}</p>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($items as $item)
            <a href="{{ $item->route }}"
               class="flex items-center gap-3 rounded-2xl border bg-white p-4 shadow-sm transition-colors hover:shadow-md {{ $item->count > 0 ? 'border-red-100' : 'border-gray-100' }}">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-xl {{ $item->count > 0 ? 'bg-red-50' : 'bg-gray-50' }}">{{ $item->icon }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-extrabold text-gray-900">{{ $item->label }}</p>
                    <p class="text-[11px] font-semibold text-gray-400">{{ $item->count > 0 ? 'Needs attention' : 'All clear' }}</p>
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-sm font-black {{ $item->count > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $item->count }}</span>
            </a>
        @endforeach
    </div>

@endsection
