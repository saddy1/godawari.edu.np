@extends('store.layouts.app')

@section('title', 'बनाइएका स्लिपहरू / Created Slips')

@section('content')
<div class="mx-auto max-w-7xl space-y-4">

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-gray-400">Store Workflow</p>
            <h1 class="mt-0.5 text-2xl font-black text-gray-950">बनाइएका स्लिपहरू <span class="text-base font-semibold text-gray-400">/ Created Slips</span></h1>
        </div>
    </div>

    @php
        $slipGroups = [
            ['title' => 'Demand Slips', 'np' => 'माग फाराम', 'hint' => 'Edit/delete before it is linked to a purchase order.', 'records' => $requisitions, 'type' => 'requisition', 'no_field' => 'requisition_no', 'date_field' => 'requested_at_bs', 'party_field' => 'requested_by_name'],
            ['title' => 'Purchase Orders', 'np' => 'खरिद आदेश', 'hint' => 'Edit/delete before dakhila is prepared.', 'records' => $orders, 'type' => 'purchase-order', 'no_field' => 'order_no', 'date_field' => 'order_date_bs', 'party_field' => 'supplier_name'],
            ['title' => 'Dakhila Reports', 'np' => 'दाखिला', 'hint' => 'Editing reverses old stock first, then reposts.', 'records' => $receipts, 'type' => 'receipt', 'no_field' => 'receipt_no', 'date_field' => 'received_at_bs', 'party_field' => 'received_from'],
            ['title' => 'Issue Slips', 'np' => 'निकासा', 'hint' => 'Editing returns old issue stock first, then reposts.', 'records' => $issues, 'type' => 'issue', 'no_field' => 'issue_no', 'date_field' => 'issued_at_bs', 'party_field' => 'issued_to_name'],
        ];
    @endphp

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach($slipGroups as $group)
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 p-5">
                    <div>
                        <h2 class="text-lg font-black text-gray-950">{{ $group['np'] }} <span class="text-sm font-semibold text-gray-400">/ {{ $group['title'] }}</span></h2>
                        <p class="mt-0.5 text-xs font-semibold text-gray-500">{{ $group['hint'] }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-green-50 px-3 py-1 text-xs font-black text-[#1a5632]">{{ $group['records']->total() }} total</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-black uppercase tracking-widest text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Slip No.</th>
                                <th class="px-4 py-3">Date / Party</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($group['records'] as $record)
                                @php
                                    $no = $record->{$group['no_field']};
                                    $date = $record->{$group['date_field']} ?? $record->created_at?->format('Y-m-d');
                                    $party = $record->{$group['party_field']} ?? '—';
                                @endphp
                                <tr class="hover:bg-gray-50/60">
                                    <td class="px-4 py-3">
                                        <p class="font-black text-gray-950">{{ $no }}</p>
                                        <p class="text-xs font-semibold text-gray-400">{{ $record->items_count }} items</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-gray-700">{{ $date }}</p>
                                        <p class="text-xs font-semibold text-gray-500 truncate max-w-[140px]">{{ $party }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-1.5">
                                            <a href="{{ route('admin.store.forms.show', ['type' => $group['type'], 'id' => $record->id]) }}" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-black text-gray-700 hover:bg-gray-50">View</a>
                                            <a href="{{ route('admin.store.documents.edit', ['type' => $group['type'], 'id' => $record->id]) }}" class="rounded-lg border border-blue-200 px-2.5 py-1.5 text-xs font-black text-blue-700 hover:bg-blue-50">Edit</a>
                                            <form method="POST" action="{{ route('admin.store.documents.destroy', ['type' => $group['type'], 'id' => $record->id]) }}" onsubmit="return confirm('Delete this slip?')">
                                                @csrf @method('DELETE')
                                                <button class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-black text-red-600 hover:bg-red-50">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-sm font-bold text-gray-400">No slips yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($group['records']->hasPages())
                    <div class="border-t border-gray-100 bg-gray-50 px-4 py-3 text-xs">{{ $group['records']->links() }}</div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
