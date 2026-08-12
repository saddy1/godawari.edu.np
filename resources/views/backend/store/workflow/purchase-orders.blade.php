@extends('store.layouts.app')

@section('title', 'खरिद आदेश / Purchase Order')

@section('content')
<div class="mx-auto max-w-7xl space-y-4" x-data="{ showForm: false }">

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-gray-400">Store Workflow</p>
            <h1 class="mt-0.5 text-2xl font-black text-gray-950">खरिद आदेश <span class="text-base font-semibold text-gray-400">/ Purchase Order</span></h1>
        </div>
        <button @click="showForm = !showForm"
                class="rounded-xl px-4 py-2.5 text-sm font-extrabold transition-colors"
                :class="showForm ? 'bg-gray-800 text-white' : 'bg-[#1a5632] text-white hover:bg-[#0b2415]'">
            <span x-text="showForm ? '✕ Cancel' : '+ New / नयाँ खरिद आदेश'"></span>
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-extrabold text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-extrabold text-red-800">{{ $errors->first() }}</div>
    @endif

    <section x-show="showForm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="display:none;">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <x-store-transaction-form
                action="{{ route('admin.store.purchase-orders.store') }}"
                title="Purchase Order / खरिद आदेश"
                type="purchase"
                :items="$formItems"
                :suppliers="$suppliers"
                :categories="$categories"
                :units="$units"
                :requisitions="$openRequisitions"
            />
        </div>
    </section>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs font-black uppercase tracking-widest text-gray-500">All Purchase Orders</p>
            <form method="GET" class="flex gap-2">
                <input name="search" value="{{ request('search') }}" placeholder="Search by no. or supplier…" class="w-52 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold outline-none focus:border-[#1a5632]">
                <button class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-black text-gray-700 hover:bg-white">Search</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[680px] divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-black uppercase tracking-widest text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Order No.</th>
                        <th class="px-5 py-3">Supplier</th>
                        <th class="px-5 py-3">Order Date</th>
                        <th class="px-5 py-3">Items</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($records as $record)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3 font-black text-gray-950">{{ $record->order_no }}</td>
                            <td class="px-5 py-3">
                                <p class="font-bold text-gray-800">{{ $record->supplier_name }}</p>
                                <p class="text-xs font-semibold text-gray-400">{{ $record->supplier_address }}</p>
                            </td>
                            <td class="px-5 py-3 font-semibold text-gray-600">{{ $record->order_date_bs ?: $record->created_at?->format('Y-m-d') }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-600">{{ $record->items_count }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-black
                                    {{ $record->status === 'ordered' ? 'bg-blue-50 text-blue-700' : ($record->status === 'completed' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700') }}">
                                    {{ ucfirst($record->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.store.forms.show', ['type' => 'purchase-order', 'id' => $record->id]) }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-black text-gray-700 hover:bg-gray-50">View</a>
                                    <a href="{{ route('admin.store.documents.edit', ['type' => 'purchase-order', 'id' => $record->id]) }}" class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-black text-blue-700 hover:bg-blue-50">Edit</a>
                                    <form method="POST" action="{{ route('admin.store.documents.destroy', ['type' => 'purchase-order', 'id' => $record->id]) }}" onsubmit="return confirm('Delete this order?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-black text-red-600 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm font-bold text-gray-400">No purchase orders created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">{{ $records->links() }}</div>
        @endif
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
function storeTransaction(defaultItems, initialLines, purchaseOrders, requisitions, categories, suppliers) {
    return {
        purchaseOrderId: '', requisitionId: '',
        requisitionSearch: '',
        requisitionResults: [],
        requisitionSearchError: '',
        lines: (initialLines && initialLines.length ? initialLines : [{ key: Date.now(), store_requisition_item_id: '', store_purchase_order_item_id: '', store_item_id: '', store_category_id: '', asset_type: 'consumable', item_name: '', specification: '', unit: '', quantity: 1, rate: 0, tax_rate: 0, condition: '', remarks: '' }]),
        blankLine() {
            return { key: Date.now() + Math.random(), store_requisition_item_id: '', store_purchase_order_item_id: '', store_item_id: '', store_category_id: '', asset_type: 'consumable', item_name: '', specification: '', unit: '', quantity: 1, rate: 0, tax_rate: 0, condition: '', remarks: '' };
        },
        hasDemandLines() { return this.lines.some(line => Number(line.store_requisition_item_id || 0) > 0); },
        syncRequisitionHeader() {
            const ids = [...new Set(this.lines.filter(line => Number(line.store_requisition_item_id || 0) > 0).map(line => Number(line.requisition_id || 0)).filter(Boolean))];
            this.requisitionId = ids.length === 1 ? ids[0] : '';
        },
        addLine() { if (this.hasDemandLines()) return; this.lines.push(this.blankLine()); },
        removeLine(index) { if (this.lines.length === 1) return; this.lines.splice(index, 1); },
        fillLine(index) {
            const selected = defaultItems.find(item => Number(item.id) === Number(this.lines[index].store_item_id));
            if (!selected) return;
            this.lines[index].item_name = selected.name || '';
            this.lines[index].specification = selected.specification || '';
            this.lines[index].unit = selected.unit || '';
            this.lines[index].rate = selected.rate || 0;
            this.lines[index].store_category_id = selected.category_id || '';
            this.lines[index].asset_type = selected.asset_type || 'consumable';
        },
        syncAssetType(index) {
            const line = this.lines[index];
            if (line && !line.asset_type) line.asset_type = 'consumable';
        },
        async searchStoreItems(index) {
            const line = this.lines[index];
            const query = String(line?.item_name || '').trim();
            if (!line) return;
            line.store_item_id = '';
            line.itemSearchDone = false;
            if (query.length < 2) {
                line.itemResults = [];
                return;
            }
            try {
                const response = await fetch(`{{ route('admin.store.items.search') }}?q=${encodeURIComponent(query)}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const payload = await response.json();
                line.itemResults = Array.isArray(payload.results) ? payload.results : [];
            } catch (error) {
                line.itemResults = [];
            }
            line.itemSearchDone = true;
        },
        selectStoreItem(index, item) {
            const line = this.lines[index];
            if (!line) return;
            line.store_item_id = item.id || '';
            line.item_name = item.name || '';
            line.store_category_id = item.category_id || '';
            line.specification = item.specification || '';
            line.unit = item.unit || '';
            line.rate = item.rate || 0;
            line.asset_type = item.asset_type || 'consumable';
            line.itemResults = [];
            line.itemSearchDone = true;
        },
        selectSupplier() {
            const supplier = (suppliers || []).find(item => Number(item.id) === Number(this.supplierId));
            this.supplierName = supplier?.name || '';
            this.supplierAddress = supplier?.address || '';
            this.supplierPhone = supplier?.phone || '';
            this.taxMode = supplier?.tax_registration_type === 'vat' ? 'vat' : 'pan';
        },
        async searchRequisitions() {
            const query = String(this.requisitionSearch || '').trim();
            this.requisitionSearchError = '';
            if (query.length < 2) {
                this.requisitionResults = [];
                return;
            }
            try {
                const response = await fetch(`{{ route('admin.store.requisitions.search') }}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const payload = await response.json();
                this.requisitionResults = Array.isArray(payload.results) ? payload.results : [];
            } catch (error) {
                this.requisitionResults = [];
                this.requisitionSearchError = 'Unable to load demand forms.';
            }
        },
        selectRequisition(requisition) {
            if (!this.hasDemandLines()) this.lines = [];
            this.requisitionSearch = requisition.label || '';
            this.requisitionResults = [];
            this.requisitionSearchError = '';
            const existing = new Set(this.lines.map(line => Number(line.store_requisition_item_id || 0)).filter(Boolean));
            const incoming = (requisition.items || []).filter(item => !existing.has(Number(item.requisition_item_id || 0))).map((item, index) => ({
                key: Date.now() + index + Math.random(),
                requisition_id: requisition.id || '',
                store_requisition_item_id: item.requisition_item_id || '',
                store_purchase_order_item_id: '',
                store_item_id: item.store_item_id || '',
                store_category_id: item.store_category_id || '',
                category_label: item.category_label || '',
                ledger_type: item.ledger_type || '',
                asset_type: item.asset_type || 'consumable',
                item_name: item.item_name || '',
                specification: item.specification || '',
                unit: item.unit || '',
                quantity: item.quantity || item.requested_quantity || 1,
                rate: 0,
                tax_rate: 0,
                condition: '',
                remarks: item.remarks || '',
            }));
            this.lines = this.lines.concat(incoming);
            if (!this.lines.length) this.lines = [this.blankLine()];
            this.syncRequisitionHeader();
        },
        clearRequisition() {
            this.requisitionId = '';
            this.requisitionSearch = '';
            this.requisitionResults = [];
            this.lines = [this.blankLine()];
        },
        importPurchaseOrder() {},
        importRequisition() {
            const req = (requisitions || []).find(r => Number(r.id) === Number(this.requisitionId));
            if (!req) return;
            this.selectRequisition(req);
        },
        ledgerLabel(categoryId) { return ''; },
        total() {
            const base = this.lines.reduce((sum, line) => sum + (Number(line.quantity || 0) * Number(line.rate || 0)), 0);
            return this.taxMode === 'vat' ? base * 1.13 : base;
        },
        money(value) { return `Rs. ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`; },
    };
}
</script>
@endpush
