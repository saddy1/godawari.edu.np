@extends('store.layouts.app')

@section('title', 'दाखिला / Dakhila')

@section('content')
<div class="mx-auto max-w-7xl space-y-4" x-data="{ showForm: false }">

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-gray-400">Store Workflow</p>
            <h1 class="mt-0.5 text-2xl font-black text-gray-950">दाखिला <span class="text-base font-semibold text-gray-400">/ Dakhila / Receipt</span></h1>
        </div>
        <button @click="showForm = !showForm"
                class="rounded-xl px-4 py-2.5 text-sm font-extrabold transition-colors"
                :class="showForm ? 'bg-gray-800 text-white' : 'bg-[#1a5632] text-white hover:bg-[#0b2415]'">
            <span x-text="showForm ? '✕ Cancel' : '+ New / नयाँ दाखिला'"></span>
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-extrabold text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-extrabold text-red-800">Please correct the highlighted fields in the form.</div>
    @endif

    <section x-show="showForm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="display:none;">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <x-store-transaction-form
                action="{{ route('admin.store.receipts.store') }}"
                title="Dakhila / दाखिला"
                type="receipt"
                :items="$formItems"
                :suppliers="$suppliers"
                :categories="$categories"
                :units="$units"
                :purchase-orders="$openPurchaseOrders"
                :store-keeper-name="$storeKeeperName"
            />
        </div>
    </section>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs font-black uppercase tracking-widest text-gray-500">All Dakhila Reports</p>
            <form method="GET" class="flex gap-2">
                <input name="search" value="{{ request('search') }}" placeholder="Search by no. or party…" class="w-52 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold outline-none focus:border-[#1a5632]">
                <button class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-black text-gray-700 hover:bg-white">Search</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-black uppercase tracking-widest text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Receipt No.</th>
                        <th class="px-5 py-3">Received From</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Items</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($records as $record)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3 font-black text-gray-950">{{ $record->receipt_no }}</td>
                            <td class="px-5 py-3 font-bold text-gray-800">{{ $record->received_from ?: '—' }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-600">{{ $record->received_at_bs ?: $record->created_at?->format('Y-m-d') }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-600">{{ $record->items_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.store.forms.show', ['type' => 'receipt', 'id' => $record->id]) }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-black text-gray-700 hover:bg-gray-50">View</a>
                                    <a href="{{ route('admin.store.documents.edit', ['type' => 'receipt', 'id' => $record->id]) }}" class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-black text-blue-700 hover:bg-blue-50">Edit</a>
                                    <form method="POST" action="{{ route('admin.store.documents.destroy', ['type' => 'receipt', 'id' => $record->id]) }}" onsubmit="return confirm('Delete this receipt?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-black text-red-600 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm font-bold text-gray-400">No dakhila reports created yet.</td></tr>
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
        purchaseOrderId: '', purchaseOrderIds: [], purchaseOrderSearch: '', purchaseOrderResults: [], purchaseOrderSearchError: '', requisitionId: '',
        lines: (initialLines && initialLines.length ? initialLines : [{ key: Date.now(), store_requisition_item_id: '', store_purchase_order_item_id: '', store_item_id: '', store_category_id: '', category_label: '', ledger_type: '', item_name: '', specification: '', unit: '', quantity: 1, max_quantity: '', rate: 0, max_rate: '', tax_rate: 0, condition: 'Good', remarks: '' }]),
        addLine() { this.lines.push({ key: Date.now() + Math.random(), store_requisition_item_id: '', store_purchase_order_item_id: '', store_item_id: '', store_category_id: '', category_label: '', ledger_type: '', item_name: '', specification: '', unit: '', quantity: 1, max_quantity: '', rate: 0, max_rate: '', tax_rate: 0, condition: 'Good', remarks: '' }); },
        hasPurchaseOrderLines() { return this.lines.some(line => Number(line.store_purchase_order_item_id || 0) > 0); },
        knownPurchaseOrders() {
            const byId = new Map();
            (purchaseOrders || []).concat(this.purchaseOrderResults || []).forEach(order => {
                if (order?.id) byId.set(Number(order.id), order);
            });
            return Array.from(byId.values());
        },
        recommendedPurchaseOrders() {
            return (purchaseOrders || []).slice(0, 3);
        },
        selectedPurchaseOrders() {
            const selectedIds = (this.purchaseOrderIds || []).map(value => Number(value));
            return this.knownPurchaseOrders().filter(order => selectedIds.includes(Number(order.id)));
        },
        visiblePurchaseOrderResults() {
            return this.purchaseOrderResults || [];
        },
        isPurchaseOrderSelected(id) {
            return (this.purchaseOrderIds || []).map(value => Number(value)).includes(Number(id));
        },
        choosePurchaseOrder(order) {
            this.ensurePurchaseOrder(order);
            const id = String(order?.id || '');
            if (!id) return;
            this.purchaseOrderIds = this.isPurchaseOrderSelected(id)
                ? this.purchaseOrderIds.filter(value => Number(value) !== Number(id))
                : this.purchaseOrderIds.concat([id]);
            this.purchaseOrderId = this.purchaseOrderIds.length === 1 ? String(this.purchaseOrderIds[0]) : '';
            this.importPurchaseOrders();
        },
        togglePurchaseOrder(order) {
            this.ensurePurchaseOrder(order);
            const id = String(order.id);
            this.purchaseOrderIds = this.isPurchaseOrderSelected(id)
                ? this.purchaseOrderIds.filter(value => Number(value) !== Number(id))
                : this.purchaseOrderIds.concat([id]);
            this.importPurchaseOrders();
        },
        ensurePurchaseOrder(order) {
            if (!order?.id || (purchaseOrders || []).some(existing => Number(existing.id) === Number(order.id))) return;
            purchaseOrders.push(order);
        },
        async searchPurchaseOrders() {
            const query = String(this.purchaseOrderSearch || '').trim();
            this.purchaseOrderSearchError = '';
            if (query.length < 2) {
                this.purchaseOrderResults = [];
                return;
            }
            try {
                const response = await fetch(`{{ route('admin.store.purchase-orders.search') }}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const payload = await response.json();
                this.purchaseOrderResults = Array.isArray(payload.results) ? payload.results : [];
            } catch (error) {
                this.purchaseOrderResults = [];
                this.purchaseOrderSearchError = 'Unable to load purchase orders.';
            }
        },
        removeLine(index) { if (this.lines.length === 1) return; this.lines.splice(index, 1); },
        fillLine(index) {
            const selected = defaultItems.find(item => Number(item.id) === Number(this.lines[index].store_item_id));
            if (!selected) return;
            this.lines[index].item_name = selected.name || '';
            this.lines[index].specification = selected.specification || '';
            this.lines[index].unit = selected.unit || '';
            this.lines[index].rate = selected.rate || 0;
            this.lines[index].store_category_id = selected.category_id || '';
        },
        selectSupplier() {
            const supplier = (suppliers || []).find(item => Number(item.id) === Number(this.supplierId));
            this.supplierName = supplier?.name || '';
            this.supplierAddress = supplier?.address || '';
            this.supplierPhone = supplier?.phone || '';
        },
        importPurchaseOrder() {
            this.purchaseOrderIds = this.purchaseOrderId ? [String(this.purchaseOrderId)] : [];
            this.importPurchaseOrders();
        },
        importPurchaseOrders() {
            const selectedIds = (this.purchaseOrderIds || []).map(id => Number(id)).filter(Boolean);
            const selectedOrders = this.knownPurchaseOrders().filter(order => selectedIds.includes(Number(order.id)));
            this.applyPurchaseOrderDateLimits(selectedOrders);
            if (!selectedOrders.length) {
                this.clearPurchaseOrders();
                return;
            }
            this.lines = selectedOrders.flatMap((order, orderIndex) => (order.items || []).map((item, itemIndex) => ({
                key: Date.now() + orderIndex + itemIndex + Math.random(),
                store_requisition_item_id: item.store_requisition_item_id || '',
                store_purchase_order_item_id: item.purchase_order_item_id || '',
                store_item_id: item.store_item_id || '',
                store_category_id: item.store_category_id || '',
                category_label: item.category_label || '',
                ledger_type: item.ledger_type || '',
                asset_type: item.asset_type || 'consumable',
                item_name: item.item_name || '',
                specification: item.specification || '',
                unit: item.unit || '',
                quantity: item.quantity || 1,
                max_quantity: item.quantity || '',
                rate: item.rate || 0,
                max_rate: item.rate || '',
                tax_rate: item.tax_rate || (order.tax_mode === 'vat' ? 13 : 0),
                condition: 'Good',
                remarks: item.remarks || '',
            })));
            if (!this.lines.length) this.lines = [this.blankReceiptLine()];
        },
        blankReceiptLine() {
            return { key: Date.now() + Math.random(), store_requisition_item_id: '', store_purchase_order_item_id: '', store_item_id: '', store_category_id: '', category_label: '', ledger_type: '', item_name: '', specification: '', unit: '', quantity: 1, max_quantity: '', rate: 0, max_rate: '', tax_rate: 0, condition: 'Good', remarks: '' };
        },
        clearPurchaseOrders() {
            this.purchaseOrderId = '';
            this.purchaseOrderIds = [];
            this.lines = [this.blankReceiptLine()];
            this.applyPurchaseOrderDateLimits([]);
        },
        importSinglePurchaseOrder() {
            const order = (purchaseOrders || []).find(o => Number(o.id) === Number(this.purchaseOrderId));
            this.applyPurchaseOrderDateLimits(order ? [order] : []);
            if (!order) return;
            this.lines = (order.items || []).map((item, index) => ({
                key: Date.now() + index,
                store_requisition_item_id: item.store_requisition_item_id || '',
                store_purchase_order_item_id: item.purchase_order_item_id || '',
                store_item_id: item.store_item_id || '',
                store_category_id: item.store_category_id || '',
                category_label: item.category_label || '',
                ledger_type: item.ledger_type || '',
                asset_type: item.asset_type || 'consumable',
                item_name: item.item_name || '',
                specification: item.specification || '',
                unit: item.unit || '',
                quantity: item.quantity || 1,
                max_quantity: item.quantity || '',
                rate: item.rate || 0,
                max_rate: item.rate || '',
                tax_rate: item.tax_rate || (order.tax_mode === 'vat' ? 13 : 0),
                condition: 'Good',
                remarks: item.remarks || '',
            }));
        },
        applyPurchaseOrderDateLimits(orders) {
            const selectedOrders = Array.isArray(orders) ? orders : (orders ? [orders] : []);
            const minDate = selectedOrders.map(order => order?.order_date_bs || '').filter(Boolean).sort().pop() || '';
            document.querySelectorAll('[name="invoice_date_bs"], [name="received_at_bs"]').forEach(input => {
                if (minDate) input.dataset.minBs = minDate;
                else delete input.dataset.minBs;
            });
        },
        importRequisition() {},
        ledgerLabel(categoryId) { return ''; },
        total() { return this.lines.reduce((sum, line) => sum + (Number(line.quantity || 0) * Number(line.rate || 0) * (1 + Number(line.tax_rate || 0) / 100)), 0); },
        money(value) { return `Rs. ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`; },
    };
}
</script>
@endpush
