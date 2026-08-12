@extends('store.layouts.app')

@section('title', 'Store Management')

@section('content')
<div class="mx-auto max-w-7xl space-y-4" x-data="{ showSettings: false }">

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-gray-400">School ERP</p>
            <h1 class="mt-0.5 text-2xl font-black text-gray-950">Store Management</h1>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1.5 text-xs font-black text-[#1a5632]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#1a5632]"></span>
                FY {{ $activeFiscalYear->name }}
            </span>
            <button @click="showSettings = !showSettings"
                    class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-sm font-extrabold transition-colors"
                    :class="showSettings ? 'border-[#1a5632] bg-green-50 text-[#1a5632]' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Settings</span>
            </button>
        </div>
    </div>

    <div x-show="showSettings"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         style="display:none;">
        <form method="POST" action="{{ route('admin.store.fiscal-year.store') }}" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            @csrf
            <p class="mb-3 text-xs font-black uppercase tracking-widest text-gray-400">Active Fiscal Year Settings</p>
            <div class="grid gap-3 sm:grid-cols-[1fr_10rem_10rem_auto] sm:items-end">
                <div>
                    <input name="name" value="{{ old('name', $activeFiscalYear->name) }}" required placeholder="e.g. 2082/83" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-black outline-none focus:border-[#1a5632]">
                    <p class="mt-1.5 text-xs font-semibold text-gray-400">All demand, purchase, dakhila, issue, and ledger entries are saved under this fiscal year.</p>
                </div>
                <label class="text-xs font-black uppercase tracking-widest text-gray-400">Start BS
                    <input name="starts_on_bs" value="{{ old('starts_on_bs', $activeFiscalYear->starts_on_bs) }}" data-max-source="ends_on_bs" maxlength="10" inputmode="numeric" placeholder="YYYY-MM-DD" class="store-bs-date mt-1.5 w-full rounded-xl border border-gray-200 px-4 py-3 font-mono text-sm font-black outline-none focus:border-[#1a5632]">
                </label>
                <label class="text-xs font-black uppercase tracking-widest text-gray-400">End BS
                    <input name="ends_on_bs" value="{{ old('ends_on_bs', $activeFiscalYear->ends_on_bs) }}" data-min-source="starts_on_bs" maxlength="10" inputmode="numeric" placeholder="YYYY-MM-DD" class="store-bs-date mt-1.5 w-full rounded-xl border border-gray-200 px-4 py-3 font-mono text-sm font-black outline-none focus:border-[#1a5632]">
                </label>
                <button class="whitespace-nowrap rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-black text-white hover:bg-[#0b2415]">Set Active Year</button>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-extrabold text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-extrabold text-red-800">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-widest text-gray-400">Items</p>
            <p class="mt-3 text-3xl font-black text-gray-950">{{ number_format($summary['items']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-widest text-gray-400">Low Stock</p>
            <p class="mt-3 text-3xl font-black text-red-600">{{ number_format($summary['low_stock']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-widest text-gray-400">Suppliers</p>
            <p class="mt-3 text-3xl font-black text-gray-950">{{ number_format($summary['suppliers']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-widest text-gray-400">Stock Value</p>
            <p class="mt-3 text-3xl font-black text-[#1a5632]">Rs. {{ number_format($summary['stock_value'], 2) }}</p>
        </div>
    </div>

    @php
        $workflowLinks = [
            ['href' => route('admin.store.requisitions.index'),    'np' => 'माग फाराम',         'en' => 'Demand Form',     'color' => 'bg-blue-50 text-blue-700 hover:bg-blue-100'],
            ['href' => route('admin.store.purchase-orders.index'), 'np' => 'खरिद आदेश',         'en' => 'Purchase Order',  'color' => 'bg-amber-50 text-amber-700 hover:bg-amber-100'],
            ['href' => route('admin.store.receipts.index'),        'np' => 'दाखिला',            'en' => 'Dakhila',         'color' => 'bg-green-50 text-green-700 hover:bg-green-100'],
            ['href' => route('admin.store.issues.index'),          'np' => 'निकासा',            'en' => 'Issue / Nikasa',  'color' => 'bg-purple-50 text-purple-700 hover:bg-purple-100'],
            ['href' => route('admin.store.slips.index'),           'np' => 'बनाइएका स्लिपहरू',  'en' => 'Created Slips',   'color' => 'bg-gray-50 text-gray-700 hover:bg-gray-100'],
            ['href' => route('admin.store.reports.index'),         'np' => 'फाराम र प्रतिवेदन', 'en' => 'Forms & Reports', 'color' => 'bg-rose-50 text-rose-700 hover:bg-rose-100'],
        ];
    @endphp
    <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <p class="mb-3 px-1 text-[10px] font-black uppercase tracking-[0.22em] text-gray-400">Workflow / कार्यप्रवाह</p>
        <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
            @foreach($workflowLinks as $link)
                <a href="{{ $link['href'] }}" class="flex flex-col items-center justify-center rounded-xl px-3 py-4 text-center transition {{ $link['color'] }}">
                    <span class="block text-sm font-black leading-tight">{{ $link['np'] }}</span>
                    <span class="mt-0.5 block text-[10px] font-semibold opacity-70">{{ $link['en'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function normalizeStoreBsDate(value) {
    const digits = String(value || '').replace(/\D/g, '').slice(0, 8);
    if (digits.length > 6) return `${digits.slice(0, 4)}-${digits.slice(4, 6)}-${digits.slice(6)}`;
    if (digits.length > 4) return `${digits.slice(0, 4)}-${digits.slice(4)}`;
    return digits;
}
document.addEventListener('input', event => {
    if (!event.target.classList.contains('store-bs-date')) return;
    event.target.value = normalizeStoreBsDate(event.target.value);
});
</script>
@endpush
