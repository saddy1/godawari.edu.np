@extends('store.layouts.app')

@section('title', 'Edit Store Slip')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-gray-400">Store Slip</p>
            <h1 class="mt-1 text-3xl font-black text-gray-950">Edit {{ $title }}</h1>
            <p class="mt-1 text-sm font-semibold text-gray-500">You can edit this slip while it has not been carried to the next linked level.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.store.dashboard') }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-extrabold text-gray-700 hover:bg-gray-50">Back</a>
            <a href="{{ route('admin.store.forms.show', ['type' => $type, 'id' => $record->id]) }}" class="rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-extrabold text-white hover:bg-gray-700">View Print</a>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-extrabold text-red-800">{{ $errors->first() }}</div>
    @endif

    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <x-store-transaction-form
            :action="route('admin.store.documents.update', ['type' => $type, 'id' => $record->id])"
            :title="$title"
            :type="$type === 'purchase-order' ? 'purchase' : $type"
            :items="$items"
            :suppliers="$suppliers"
            :categories="$categories"
            :units="$units"
            :purchase-orders="$purchaseOrders"
            :requisitions="$requisitions"
            method="PATCH"
            :record-data="$recordData"
            :lines="$lines"
            :store-keeper-name="$storeKeeperName"
        />
    </section>
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
        purchaseOrderId: @json($recordData['store_purchase_order_id'] ?? ''),
        purchaseOrderIds: [],
        purchaseOrderSearch: '',
        purchaseOrderResults: [],
        purchaseOrderSearchError: '',
        requisitionId: @json($recordData['store_requisition_id'] ?? ''),
        requisitionSearch: '',
        requisitionResults: [],
        requisitionSearchError: '',
        requestedByName: @json(old('requested_by_name', $recordData['requested_by_name'] ?? '')),
        requestedByDesignation: @json(old('requested_by_designation', $recordData['requested_by_designation'] ?? '')),
        issuedToName: @json(old('issued_to_name', $recordData['issued_to_name'] ?? '')),
        issuedToDesignation: @json(old('issued_to_designation', $recordData['issued_to_designation'] ?? '')),
        hrMemberResults: [],
        hrMemberSearchError: '',
        issueRecipientResults: [],
        issueRecipientSearchError: '',
        lines: (initialLines && initialLines.length ? initialLines : [{ key: Date.now(), store_requisition_item_id: '', store_purchase_order_item_id: '', store_item_id: '', store_category_id: '', asset_type: 'consumable', item_name: '', specification: '', unit: '', quantity: 1, rate: 0, tax_rate: 0, condition: '', remarks: '' }]),
        async searchHrMembers() {
            const query = String(this.requestedByName || '').trim();
            if (query.length < 2) {
                this.hrMemberResults = [];
                return;
            }

            this.hrMemberSearchError = '';
            try {
                const response = await fetch(`{{ route('admin.store.hr-members.search') }}?context=staff&q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const payload = await response.json();
                this.hrMemberResults = Array.isArray(payload.results) ? payload.results : [];
            } catch (error) {
                this.hrMemberResults = [];
                this.hrMemberSearchError = 'Unable to load HR members.';
            }
        },
        selectHrMember(member) {
            this.requestedByName = member.name || String(member.label || '').split(' · ')[0] || '';
            this.requestedByDesignation = member.designation || '';
            this.hrMemberResults = [];
            this.hrMemberSearchError = '';
        },
        async searchIssueRecipients() {
            const query = String(this.issuedToName || '').trim();
            if (query.length < 2) {
                this.issueRecipientResults = [];
                return;
            }
            this.issueRecipientSearchError = '';
            try {
                const response = await fetch(`{{ route('admin.store.hr-members.search') }}?context=issue&q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const payload = await response.json();
                this.issueRecipientResults = Array.isArray(payload.results) ? payload.results : [];
            } catch (error) {
                this.issueRecipientResults = [];
                this.issueRecipientSearchError = 'Unable to load names.';
            }
        },
        selectIssueRecipient(member) {
            this.issuedToName = member.name || String(member.label || '').split(' · ')[0] || '';
            this.issuedToDesignation = member.designation || '';
            this.issueRecipientResults = [];
            this.issueRecipientSearchError = '';
        },
        addLine() {
            if (this.hasDemandLines && this.hasDemandLines()) return;
            if (this.hasPurchaseOrderLines && this.hasPurchaseOrderLines()) return;
            this.lines.push({ key: Date.now() + Math.random(), store_requisition_item_id: '', store_purchase_order_item_id: '', store_item_id: '', store_category_id: '', asset_type: 'consumable', item_name: '', specification: '', unit: '', quantity: 1, rate: 0, tax_rate: 0, condition: '', remarks: '' });
        },
        hasDemandLines() { return this.lines.some(line => Number(line.store_requisition_item_id || 0) > 0); },
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
        syncRequisitionHeader() {
            const ids = [...new Set(this.lines.filter(line => Number(line.store_requisition_item_id || 0) > 0).map(line => Number(line.requisition_id || 0)).filter(Boolean))];
            this.requisitionId = ids.length === 1 ? ids[0] : '';
        },
        removeLine(index) {
            if (this.lines.length === 1) return;
            this.lines.splice(index, 1);
        },
        selectedIssueItemIds(exceptIndex = null) {
            return new Set(this.lines
                .map((line, index) => index === exceptIndex ? null : Number(line.store_item_id || 0))
                .filter(Boolean));
        },
        fillLine(index) {
            const selected = defaultItems.find(item => Number(item.id) === Number(this.lines[index].store_item_id));
            if (!selected) return;
            this.lines[index].item_name = selected.name || '';
            this.lines[index].specification = selected.specification || '';
            this.lines[index].unit = selected.unit || '';
            this.lines[index].rate = selected.rate || 0;
            this.lines[index].store_category_id = selected.category_id || '';
            this.lines[index].asset_type = selected.asset_type || 'consumable';
            this.lines[index].max_quantity = selected.stock || '';
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
            if ('{{ $type }}' === 'issue') line.max_quantity = '';
            line.itemSearchDone = false;
            if (query.length < 2) {
                line.itemResults = [];
                return;
            }
            try {
                const context = '{{ $type }}' === 'issue' ? 'issue' : '';
                const response = await fetch(`{{ route('admin.store.items.search') }}?q=${encodeURIComponent(query)}&context=${context}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const payload = await response.json();
                let results = Array.isArray(payload.results) ? payload.results : [];
                if ('{{ $type }}' === 'issue') {
                    const selectedIds = this.selectedIssueItemIds(index);
                    results = results
                        .filter(item => Number(item.stock || 0) > 0)
                        .filter(item => !selectedIds.has(Number(item.id || 0)));
                }
                line.itemResults = results;
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
            line.max_quantity = item.stock || '';
            if ('{{ $type }}' === 'issue' && Number(line.quantity || 0) > Number(line.max_quantity || 0)) {
                line.quantity = line.max_quantity || 1;
            }
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
            if (!this.lines.length) this.addLine();
            this.syncRequisitionHeader();
        },
        clearRequisition() {
            this.requisitionId = '';
            this.requisitionSearch = '';
            this.requisitionResults = [];
            this.lines = [];
            this.addLine();
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
                quantity: item.quantity || item.ordered_quantity || 1,
                max_quantity: item.quantity || item.ordered_quantity || '',
                rate: item.rate || 0,
                max_rate: item.rate || '',
                tax_rate: item.tax_rate || (order.tax_mode === 'vat' ? 13 : 0),
                condition: 'Good',
                remarks: item.remarks || '',
            })));
            if (!this.lines.length) this.lines = [this.blankReceiptLine()];
        },
        blankReceiptLine() {
            return { key: Date.now() + Math.random(), store_requisition_item_id: '', store_purchase_order_item_id: '', store_item_id: '', store_category_id: '', asset_type: 'consumable', item_name: '', specification: '', unit: '', quantity: 1, rate: 0, tax_rate: 0, condition: 'Good', remarks: '' };
        },
        clearPurchaseOrders() {
            this.purchaseOrderId = '';
            this.purchaseOrderIds = [];
            this.lines = [this.blankReceiptLine()];
            this.applyPurchaseOrderDateLimits([]);
        },
        importSinglePurchaseOrder() {
            const order = (purchaseOrders || []).find(order => Number(order.id) === Number(this.purchaseOrderId));
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
                quantity: item.quantity || item.ordered_quantity || 1,
                max_quantity: item.quantity || item.ordered_quantity || '',
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
        importRequisition() {
            const requisition = (requisitions || []).find(requisition => Number(requisition.id) === Number(this.requisitionId));
            if (!requisition) return;
            this.selectRequisition(requisition);
        },
        ledgerLabel(categoryId) {
            return '';
        },
        total() {
            const base = this.lines.reduce((sum, line) => sum + (Number(line.quantity || 0) * Number(line.rate || 0)), 0);
            return this.taxMode === 'vat' ? base * 1.13 : base;
        },
        money(value) {
            return `Rs. ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        },
    };
}
</script>
@endpush
