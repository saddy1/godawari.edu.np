@extends('layouts.app')

@section('title', 'My Store Items')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8">
    <div class="mb-5">
        <h1 class="text-2xl font-black text-gray-950">My Store Items</h1>
        <p class="mt-1 text-sm font-semibold text-gray-500">Items currently issued in your name.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-gray-50 text-xs font-black uppercase tracking-widest text-gray-500">
                <tr>
                    <th class="px-5 py-3">Item</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Issue No.</th>
                    <th class="px-5 py-3 text-right">Held Qty</th>
                    <th class="px-5 py-3">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $line)
                    <tr>
                        <td class="px-5 py-3">
                            <p class="font-black text-gray-950">{{ $line->item_name }}</p>
                            <p class="text-xs font-semibold text-gray-500">{{ $line->specification }}</p>
                        </td>
                        <td class="px-5 py-3 font-semibold text-gray-600">{{ $line->item?->category?->name ?: 'No category' }}</td>
                        <td class="px-5 py-3 font-bold text-gray-700">{{ $line->issue?->issue_no }}</td>
                        <td class="px-5 py-3 text-right font-black text-gray-950">{{ number_format((float) $line->quantity - (float) $line->returned_quantity, 2) }} {{ $line->unit }}</td>
                        <td class="px-5 py-3 font-semibold text-gray-600">{{ $line->issue?->issued_at_bs ?: $line->issue?->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center font-bold text-gray-400">No active store items are issued in your name.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($items->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">{{ $items->links() }}</div>
        @endif
    </div>
</div>
@endsection
