@extends('store.layouts.app')

@section('title', 'फाराम र प्रतिवेदन / Forms & Reports')

@section('content')
<div class="mx-auto max-w-7xl space-y-4">

    <div class="rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-black uppercase tracking-[0.22em] text-gray-400">Store Workflow</p>
        <h1 class="mt-0.5 text-2xl font-black text-gray-950">फाराम र प्रतिवेदन <span class="text-base font-semibold text-gray-400">/ Forms & Reports</span></h1>
    </div>

    @php
        $formGroups = [
            ['form' => 'Form 29', 'np' => 'माग फाराम', 'type' => 'requisition', 'records' => $recentRequisitions, 'no_field' => 'requisition_no'],
            ['form' => 'Form 30', 'np' => 'खरिद आदेश', 'type' => 'purchase-order', 'records' => $recentOrders, 'no_field' => 'order_no'],
            ['form' => 'Form 31', 'np' => 'दाखिला',    'type' => 'receipt',       'records' => $recentReceipts, 'no_field' => 'receipt_no'],
            ['form' => 'Issue',   'np' => 'निकासा',    'type' => 'issue',         'records' => $recentIssues,   'no_field' => 'issue_no'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($formGroups as $group)
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ $group['form'] }}</p>
                <h2 class="mt-1 text-lg font-black text-gray-950">{{ $group['np'] }}</h2>
                <div class="mt-4 space-y-2">
                    @forelse($group['records'] as $record)
                        @php $number = $record->{$group['no_field']}; @endphp
                        <a href="{{ route('admin.store.forms.show', ['type' => $group['type'], 'id' => $record->id]) }}"
                           class="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-2.5 hover:bg-gray-50 hover:border-gray-200 transition-colors">
                            <span class="block text-sm font-black text-gray-900">{{ $number }}</span>
                            <span class="text-xs font-semibold text-gray-400">{{ $record->items_count }} items</span>
                        </a>
                    @empty
                        <p class="rounded-xl bg-gray-50 px-4 py-4 text-sm font-bold text-gray-400">No records yet.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <a href="{{ route('admin.store.forms.show', ['type' => 'ledger-consumable']) }}"
           class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white px-5 py-5 shadow-sm hover:bg-gray-50 transition-colors">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-green-50">
                <svg class="w-6 h-6 text-[#1a5632]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Form 33</p>
                <p class="mt-0.5 text-base font-black text-gray-950">खर्च भएर जाने जिन्सी खाता</p>
                <p class="text-xs font-semibold text-gray-400">Consumable Ledger</p>
            </div>
        </a>
        <a href="{{ route('admin.store.forms.show', ['type' => 'ledger-non-consumable']) }}"
           class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white px-5 py-5 shadow-sm hover:bg-gray-50 transition-colors">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Form 32</p>
                <p class="mt-0.5 text-base font-black text-gray-950">खर्च भएर नजाने जिन्सी खाता</p>
                <p class="text-xs font-semibold text-gray-400">Non-consumable Ledger</p>
            </div>
        </a>
    </div>
</div>
@endsection
