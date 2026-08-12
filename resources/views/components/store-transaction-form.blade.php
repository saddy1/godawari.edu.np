@props(['action', 'title', 'type', 'items', 'suppliers', 'categories' => collect(), 'units' => collect(), 'purchaseOrders' => collect(), 'requisitions' => collect(), 'method' => null, 'recordData' => [], 'lines' => null, 'storeKeeperName' => ''])

@php
    $fieldClass = 'w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-bold text-gray-950 placeholder:text-gray-500 outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/10';
    $labelClass = 'space-y-1.5 text-xs font-black uppercase tracking-widest text-gray-400';
    $dateClass = $fieldClass.' store-bs-date font-mono';
    $itemPayload = collect($items)->map(function ($item) {
        $assetType = in_array($item->asset_type, ['non_consumable', 'fixed_asset'], true) ? 'non_consumable' : 'consumable';

        return [
            'id' => $item->id,
            'name' => $item->name,
            'unit' => $item->unit?->symbol,
            'specification' => $item->specification,
            'rate' => $item->average_rate,
            'stock' => (float) $item->current_quantity,
            'category_id' => $item->store_category_id,
            'category_label' => $item->category?->name,
            'ledger_type' => $assetType === 'non_consumable' ? 'Non-consumable' : 'Consumable',
            'asset_type' => $assetType,
        ];
    })->values();
    $categoryPayload = collect($categories)->map(fn($category) => [
        'id' => $category->id,
        'name' => $category->name,
        'ledger_type' => '',
        'asset_type' => '',
    ])->values();
    $supplierPayload = collect($suppliers)->map(fn($supplier) => [
        'id' => $supplier->id,
        'name' => $supplier->name,
        'address' => $supplier->address,
        'phone' => $supplier->phone,
        'tax_registration_type' => $supplier->tax_registration_type ?: 'pan',
    ])->values();
    $submittedLines = old('items');
    $lineSource = $submittedLines !== null ? $submittedLines : ($lines ?: [[
        'store_requisition_item_id' => '',
        'store_purchase_order_item_id' => '',
        'store_item_id' => '',
        'store_category_id' => '',
        'asset_type' => 'consumable',
        'item_name' => '',
        'specification' => '',
        'unit' => '',
        'quantity' => 1,
        'rate' => 0,
        'tax_rate' => 0,
        'condition' => '',
        'remarks' => '',
    ]]);
    $linePayload = collect($lineSource)->map(function ($line, $index) {
        return [
            'key' => $index + 1,
            'store_requisition_item_id' => $line['store_requisition_item_id'] ?? '',
            'store_purchase_order_item_id' => $line['store_purchase_order_item_id'] ?? '',
            'store_item_id' => $line['store_item_id'] ?? '',
            'store_category_id' => $line['store_category_id'] ?? '',
            'category_label' => $line['category_label'] ?? '',
            'ledger_type' => $line['ledger_type'] ?? '',
            'asset_type' => $line['asset_type'] ?? 'consumable',
            'item_name' => $line['item_name'] ?? '',
            'specification' => $line['specification'] ?? '',
            'unit' => $line['unit'] ?? '',
            'quantity' => (float) ($line['quantity'] ?? 1),
            'max_quantity' => $line['max_quantity'] ?? '',
            'rate' => (float) ($line['rate'] ?? 0),
            'tax_rate' => (float) ($line['tax_rate'] ?? 0),
            'condition' => $line['condition'] ?? '',
            'remarks' => $line['remarks'] ?? '',
        ];
    })->values();
    $existingReceiptPurchaseOrderItemIds = $linePayload->pluck('store_purchase_order_item_id')->filter()->map(fn($id) => (int) $id);
    $purchaseOrderPayload = collect($purchaseOrders)->map(fn($order) => [
        'id' => $order->id,
        'label' => $order->order_no.' - '.$order->supplier_name,
        'supplier_id' => $order->store_supplier_id,
        'supplier_name' => $order->supplier_name,
        'supplier_address' => $order->supplier_address,
        'supplier_phone' => $order->supplier_phone,
        'tax_mode' => $order->tax_mode ?: (((float) $order->items->max('tax_rate') > 0) ? 'vat' : 'pan'),
        'order_date_bs' => $order->order_date_bs,
        'items' => $order->items->map(function ($item) {
            $assetType = in_array($item->item?->asset_type, ['non_consumable', 'fixed_asset'], true) ? 'non_consumable' : 'consumable';

            return [
                'purchase_order_item_id' => $item->id,
                'requisition_id' => $item->requisitionItem?->store_requisition_id,
                'store_requisition_item_id' => $item->store_requisition_item_id,
                'store_item_id' => $item->store_item_id,
                'store_category_id' => $item->store_category_id ?: $item->item?->store_category_id,
                'category_label' => $item->category?->name ?: $item->item?->category?->name,
                'ledger_type' => $assetType === 'non_consumable' ? 'Non-consumable' : 'Consumable',
                'asset_type' => $assetType,
                'item_name' => $item->item_name,
                'specification' => $item->specification,
                'unit' => $item->unit,
                'quantity' => (float) $item->remaining_quantity,
                'ordered_quantity' => (float) $item->quantity,
                'rate' => (float) $item->rate,
                'tax_rate' => (float) $item->tax_rate,
                'remarks' => $item->remarks,
            ];
        })->filter(fn($item) => $item['quantity'] > 0 || $existingReceiptPurchaseOrderItemIds->contains((int) $item['purchase_order_item_id']))->values(),
    ])->values();
    $requisitionPayload = collect($requisitions)->map(fn($requisition) => [
        'id' => $requisition->id,
        'label' => $requisition->display_requisition_no.' - '.$requisition->requested_by_name,
        'meta' => collect([$requisition->requested_by_designation, $requisition->purpose, ucfirst((string) $requisition->status)])->filter()->implode(' · '),
        'items' => $requisition->items->map(function ($item) use ($requisition) {
            $assetType = in_array($item->item?->asset_type, ['non_consumable', 'fixed_asset'], true) ? 'non_consumable' : 'consumable';

            return [
                'requisition_item_id' => $item->id,
                'requisition_id' => $requisition->id,
                'store_item_id' => $item->store_item_id,
                'store_category_id' => $item->store_category_id ?: $item->item?->store_category_id,
                'category_label' => $item->category?->name ?: $item->item?->category?->name,
                'ledger_type' => $assetType === 'non_consumable' ? 'Non-consumable' : 'Consumable',
                'asset_type' => $assetType,
                'item_name' => $item->item_name,
                'specification' => $item->specification,
                'unit' => $item->unit,
                'quantity' => (float) $item->remaining_quantity,
                'requested_quantity' => (float) $item->quantity,
                'remarks' => $item->remarks,
            ];
        })->filter(fn($item) => $item['quantity'] > 0)->values(),
    ])->values();
    $selectedRequisitionLabel = $requisitionPayload->firstWhere('id', $recordData['store_requisition_id'] ?? null)['label'] ?? '';
    $selectedReceiptPurchaseOrderIds = collect();
    if ($type === 'receipt') {
        $receiptLinePurchaseOrderItemIds = $linePayload->pluck('store_purchase_order_item_id')->filter()->map(fn($id) => (int) $id);
        $selectedReceiptPurchaseOrderIds = $purchaseOrderPayload
            ->filter(fn($order) => collect($order['items'])->pluck('purchase_order_item_id')->map(fn($id) => (int) $id)->intersect($receiptLinePurchaseOrderItemIds)->isNotEmpty())
            ->pluck('id')
            ->values();
        if ($selectedReceiptPurchaseOrderIds->isEmpty() && ! empty($recordData['store_purchase_order_id'])) {
            $selectedReceiptPurchaseOrderIds = collect([(int) $recordData['store_purchase_order_id']]);
        }
    }
@endphp

<form method="POST" action="{{ $action }}"
    x-data="Object.assign(storeTransaction(@js($itemPayload), @js($linePayload), @js($purchaseOrderPayload), @js($requisitionPayload), @js($categoryPayload), @js($supplierPayload)), {
        purchaseDateErrors: [],
        supplierId: @js(old('store_supplier_id', $recordData['store_supplier_id'] ?? '')),
        supplierName: @js(old('supplier_name', $recordData['supplier_name'] ?? '')),
        supplierAddress: @js(old('supplier_address', $recordData['supplier_address'] ?? '')),
        supplierPhone: @js(old('supplier_phone', $recordData['supplier_phone'] ?? '')),
        requisitionSearch: @js($selectedRequisitionLabel),
        taxMode: @js(old('tax_mode', $recordData['tax_mode'] ?? '')),
        purchaseOrderIds: @js($selectedReceiptPurchaseOrderIds),
    })"
    @if($type === 'purchase')
    x-on:submit="
        const d = $el.querySelector('[name=decision_date_bs]')?.value?.trim() || '';
        const o = $el.querySelector('[name=order_date_bs]')?.value?.trim() || '';
        const e = $el.querySelector('[name=expected_date_bs]')?.value?.trim() || '';
        const errs = [];
        if (d && o && o < d) errs.push('खरिद आदेश मिति (Order date) must be on or after Decision date.');
        if (e && o && e < o) errs.push('अपेक्षित मिति (Expected date) must be on or after Order date.');
        purchaseDateErrors = errs;
        if (errs.length) { $event.preventDefault(); $el.querySelector('#purchase-date-errors')?.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    "
    @endif
    class="space-y-5">
        @csrf
        @if($method)
            @method($method)
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-extrabold text-red-800">
                Please correct the highlighted fields below.
            </div>
        @endif
    @if($type === 'purchase')
        <template x-if="purchaseDateErrors.length">
            <div id="purchase-date-errors" class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-extrabold text-red-800">
                <template x-for="err in purchaseDateErrors"><p x-text="err" class="mb-1 last:mb-0"></p></template>
            </div>
        </template>
    @endif
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-gray-400">Store Document</p>
            <h2 class="text-2xl font-black text-gray-950">{{ $title }}</h2>
        </div>
        <div class="rounded-xl bg-green-50 px-4 py-3 text-sm font-black text-[#1a5632]" x-show="['purchase','receipt','issue'].includes('{{ $type }}')">
            <span x-text="money(total())"></span>
        </div>
    </div>

    @if($type === 'requisition')
        <div class="grid gap-3 md:grid-cols-4">
            <label class="{{ $labelClass }} relative">Requested by
                <input name="requested_by_name"
                       x-model="requestedByName"
                       @input.debounce.250ms="searchHrMembers()"
                       @focus="searchHrMembers()"
                       @keydown.escape="hrMemberResults = []"
                       autocomplete="off"
                       required
                       placeholder="Search HR teacher/staff"
                       class="{{ $fieldClass }}">
                <div x-show="hrMemberResults.length"
                     @click.outside="hrMemberResults = []"
                     class="absolute left-0 right-0 top-full z-30 mt-1 max-h-64 overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-xl"
                     style="display:none;">
                    <template x-for="member in hrMemberResults" :key="member.id">
                        <button type="button" @click="selectHrMember(member)" class="block w-full px-4 py-3 text-left hover:bg-gray-50">
                            <span class="block text-sm font-black text-gray-900" x-text="member.name || member.label || 'Unnamed HR member'"></span>
                            <span class="block text-xs font-bold text-gray-400" x-text="[member.designation, member.meta].filter(Boolean).join(' · ')"></span>
                        </button>
                    </template>
                </div>
                <span x-show="hrMemberSearchError" x-text="hrMemberSearchError" class="mt-1 block text-xs font-bold text-red-600"></span>
            </label>
            <label class="{{ $labelClass }}">Designation<input name="requested_by_designation" x-model="requestedByDesignation" placeholder="Post / section" class="{{ $fieldClass }}"></label>
            <label class="{{ $labelClass }}">Purpose<input name="purpose" value="{{ $recordData['purpose'] ?? '' }}" placeholder="Purpose" class="{{ $fieldClass }}"></label>
            <label class="{{ $labelClass }}">Demand date BS<input name="requested_at_bs" value="{{ $recordData['requested_at_bs'] ?? '' }}" maxlength="10" inputmode="numeric" placeholder="YYYY-MM-DD" class="{{ $dateClass }}"></label>
            <label class="{{ $labelClass }}">Approved by<input name="approved_by_name" value="{{ $recordData['approved_by_name'] ?? '' }}" placeholder="Optional" class="{{ $fieldClass }}"></label>
        </div>
    @elseif($type === 'purchase')
        <div class="grid gap-3 md:grid-cols-4">
            <label class="{{ $labelClass }} relative">Demand form
                <input type="hidden" name="store_requisition_id" x-model="requisitionId">
                <input x-model="requisitionSearch"
                       @input.debounce.250ms="searchRequisitions()"
                       @focus="searchRequisitions()"
                       @keydown.escape="requisitionResults = []"
                       autocomplete="off"
                       placeholder="Search demand form or keep direct"
                       class="{{ $fieldClass }}">
                <div x-show="requisitionResults.length"
                     @click.outside="requisitionResults = []"
                     class="absolute left-0 right-0 top-full z-30 mt-1 max-h-72 overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-xl"
                     style="display:none;">
                    <template x-for="requisition in requisitionResults" :key="requisition.id">
                        <button type="button" @click="selectRequisition(requisition)" class="block w-full border-b border-gray-100 px-4 py-3 text-left last:border-0 hover:bg-green-50">
                            <span class="block text-sm font-black text-gray-900" x-text="requisition.label"></span>
                            <span class="block text-xs font-bold text-gray-400" x-text="requisition.meta"></span>
                        </button>
                    </template>
                </div>
                <button type="button" x-show="hasDemandLines && hasDemandLines()" @click="clearRequisition()" class="mt-1 text-xs font-black normal-case tracking-normal text-amber-700 hover:text-amber-900">Use direct purchase / clear demand forms</button>
                <span x-show="hasDemandLines && hasDemandLines()" class="mt-1 block text-[11px] font-bold normal-case tracking-normal text-gray-500">Demand PO can include multiple demand slips. Manual lines are disabled.</span>
                <span x-show="requisitionSearchError" x-text="requisitionSearchError" class="mt-1 block text-xs font-bold text-red-600"></span>
            </label>
            <label class="{{ $labelClass }}">Supplier<select name="store_supplier_id" x-model="supplierId" @change="selectSupplier()" required class="{{ $fieldClass }}">
                <option value="">Select supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(($recordData['store_supplier_id'] ?? null) == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select></label>
            <label class="{{ $labelClass }}">Name<input name="supplier_name" x-model="supplierName" required readonly placeholder="Fetched from supplier" class="{{ $fieldClass }} bg-gray-50"></label>
            <label class="{{ $labelClass }}">Address<input name="supplier_address" x-model="supplierAddress" readonly placeholder="Fetched from supplier" class="{{ $fieldClass }} bg-gray-50"></label>
            <label class="{{ $labelClass }}">Phone<input name="supplier_phone" x-model="supplierPhone" readonly placeholder="Fetched from supplier" class="{{ $fieldClass }} bg-gray-50"></label>
            <label class="{{ $labelClass }}">Tax mode
                <input type="hidden" name="tax_mode" x-model="taxMode">
                <select x-model="taxMode" disabled class="{{ $fieldClass }} bg-gray-50 text-gray-600">
                <option value="">Select tax mode</option>
                <option value="pan">PAN mode / no VAT</option>
                <option value="vat">VAT mode / add 13%</option>
            </select></label>
            <label class="{{ $labelClass }}">Decision no.<input name="decision_no" value="{{ $recordData['decision_no'] ?? '' }}" placeholder="Decision no." class="{{ $fieldClass }}"></label>
            {{-- Date fields with real-time cross-field validation --}}
            <div class="contents" x-data="{
                dec: '{{ old('decision_date_bs', $recordData['decision_date_bs'] ?? '') }}',
                ord: '{{ old('order_date_bs', $recordData['order_date_bs'] ?? '') }}',
                exp: '{{ old('expected_date_bs', $recordData['expected_date_bs'] ?? '') }}',
            }">
                <label class="{{ $labelClass }}">
                    Decision date BS (निर्णय मिति)
                    <input name="decision_date_bs" x-model="dec" data-max-source="order_date_bs" maxlength="10" inputmode="numeric" placeholder="YYYY-MM-DD"
                        :class="dec && ord && dec > ord ? '{{ $fieldClass }} store-bs-date font-mono border-red-400 bg-red-50' : '{{ $dateClass }}'">
                    <span x-show="dec && ord && dec > ord" class="mt-1 block text-xs font-bold text-red-600">Must be on or before Order date</span>
                </label>
                <label class="{{ $labelClass }}">
                    Order date BS (खरिद आदेश मिति)
                    <input name="order_date_bs" x-model="ord" data-min-source="decision_date_bs" data-max-source="expected_date_bs" maxlength="10" inputmode="numeric" placeholder="YYYY-MM-DD"
                        :class="(dec && ord && ord < dec) || (exp && ord && ord > exp) ? '{{ $fieldClass }} store-bs-date font-mono border-red-400 bg-red-50' : '{{ $dateClass }}'">
                    <span x-show="dec && ord && ord < dec" class="mt-1 block text-xs font-bold text-red-600">Must be on or after Decision date</span>
                    <span x-show="exp && ord && ord > exp" class="mt-1 block text-xs font-bold text-red-600">Must be on or before Expected date</span>
                </label>
                <label class="{{ $labelClass }}">
                    Expected date BS (अपेक्षित मिति)
                    <input name="expected_date_bs" x-model="exp" data-min-source="order_date_bs" maxlength="10" inputmode="numeric" placeholder="YYYY-MM-DD"
                        :class="exp && ord && exp < ord ? '{{ $fieldClass }} store-bs-date font-mono border-red-400 bg-red-50' : '{{ $dateClass }}'">
                    <span x-show="exp && ord && exp < ord" class="mt-1 block text-xs font-bold text-red-600">Must be on or after Order date</span>
                </label>
            </div>
        </div>
    @elseif($type === 'receipt')
        <div class="grid gap-3 md:grid-cols-4">
            <label class="{{ $labelClass }}">Purchase orders
                <input type="hidden" name="store_purchase_order_id" :value="purchaseOrderIds.length === 1 ? purchaseOrderIds[0] : ''">
                <template x-for="poId in purchaseOrderIds" :key="`po-hidden-${poId}`">
                    <input type="hidden" name="purchase_order_ids[]" :value="poId">
                </template>
                <input x-model="purchaseOrderSearch"
                       @input.debounce.250ms="searchPurchaseOrders()"
                       @focus="searchPurchaseOrders()"
                       placeholder="Search PO no. or supplier"
                       class="{{ $fieldClass }}">
                <div class="mt-2 max-h-56 overflow-y-auto rounded-xl border border-gray-200 bg-white p-2 normal-case tracking-normal" x-show="purchaseOrderSearch || purchaseOrderIds.length">
                    <template x-if="purchaseOrderIds.length">
                        <div class="mb-2 border-b border-gray-100 pb-2">
                            <p class="px-2 pb-1 text-[11px] font-black uppercase tracking-widest text-gray-400">Selected POs</p>
                            <template x-for="order in selectedPurchaseOrders()" :key="`po-selected-${order.id}`">
                                <button type="button" @click="choosePurchaseOrder(order)" class="mb-1 flex w-full cursor-pointer items-start gap-2 rounded-lg bg-green-50 px-2 py-2 text-left text-xs font-bold text-green-800 hover:bg-green-100">
                                    <span class="mt-0.5 flex h-4 w-4 items-center justify-center rounded border border-green-300 text-[10px] font-black text-[#1a5632]">✓</span>
                                    <span class="leading-4">
                                        <span class="block font-black" x-text="order.label"></span>
                                        <span class="block text-[11px]" x-text="`${order.items?.length || 0} items${order.order_date_bs ? ' · ' + order.order_date_bs : ''}`"></span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </template>
                    <template x-if="purchaseOrderSearch">
                        <div>
                            <p class="px-2 pb-1 text-[11px] font-black uppercase tracking-widest text-gray-400">Search results</p>
                            <template x-for="order in visiblePurchaseOrderResults()" :key="`po-search-${order.id}`">
                        <button type="button" @click="choosePurchaseOrder(order)" class="mb-1 flex w-full cursor-pointer items-start gap-2 rounded-lg px-2 py-2 text-left text-xs font-bold text-gray-700 hover:bg-gray-50">
                            <span class="mt-0.5 flex h-4 w-4 items-center justify-center rounded border border-gray-300 text-[10px] font-black text-[#1a5632]" x-text="isPurchaseOrderSelected(order.id) ? '✓' : ''"></span>
                            <span class="leading-4">
                                <span class="block font-black text-gray-950" x-text="order.label"></span>
                                <span class="block text-[11px] text-gray-500" x-text="`${order.items?.length || 0} items${order.order_date_bs ? ' · ' + order.order_date_bs : ''}`"></span>
                            </span>
                        </button>
                            </template>
                            <p x-show="purchaseOrderSearchError" class="px-2 py-1 text-[11px] font-bold text-red-600" x-text="purchaseOrderSearchError"></p>
                            <p x-show="!purchaseOrderSearchError && !visiblePurchaseOrderResults().length" class="px-2 py-1 text-[11px] font-bold text-gray-400">No matching open PO.</p>
                        </div>
                    </template>
                </div>
                <button type="button" @click="clearPurchaseOrders()" x-show="purchaseOrderIds.length" class="mt-1 text-xs font-black normal-case tracking-normal text-amber-700 hover:text-amber-900">Use custom dakhila / clear PO</button>
                <span x-show="purchaseOrderIds.length" class="mt-1 block text-[11px] font-bold normal-case tracking-normal text-gray-500">Add Line is disabled for PO-based Dakhila. Choose more POs from search to add their items.</span>
            </label>
            <label class="{{ $labelClass }}">Supplier<select name="store_supplier_id" class="{{ $fieldClass }}">
                <option value="">Select supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(old('store_supplier_id', $recordData['store_supplier_id'] ?? null) == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select></label>
            <label class="{{ $labelClass }}">Received from<input name="received_from" value="{{ old('received_from', $recordData['received_from'] ?? '') }}" placeholder="Received from" class="{{ $fieldClass }}"></label>
            <label class="{{ $labelClass }}">Challan no.<input name="challan_no" value="{{ old('challan_no', $recordData['challan_no'] ?? '') }}" placeholder="Challan no." class="{{ $fieldClass }}"></label>
            <label class="{{ $labelClass }}">Invoice no.<input name="invoice_no" value="{{ old('invoice_no', $recordData['invoice_no'] ?? '') }}" placeholder="Invoice no." class="{{ $fieldClass }}"></label>
            <label class="{{ $labelClass }}">Invoice date BS<input name="invoice_date_bs" value="{{ old('invoice_date_bs', $recordData['invoice_date_bs'] ?? '') }}" data-max-source="received_at_bs" maxlength="10" inputmode="numeric" placeholder="YYYY-MM-DD" class="{{ $dateClass }}"></label>
            <label class="{{ $labelClass }}">Dakhila date BS<input name="received_at_bs" value="{{ old('received_at_bs', $recordData['received_at_bs'] ?? '') }}" data-min-source="invoice_date_bs" maxlength="10" inputmode="numeric" placeholder="YYYY-MM-DD" class="{{ $dateClass }}"></label>
            <label class="{{ $labelClass }}">Store keeper<input name="received_by_name" value="{{ old('received_by_name', $recordData['received_by_name'] ?? $storeKeeperName ?? '') }}" placeholder="Store keeper" class="{{ $fieldClass }}"></label>
            <label class="{{ $labelClass }}">Verified by<input name="verified_by_name" value="{{ old('verified_by_name', $recordData['verified_by_name'] ?? '') }}" placeholder="Verified by" class="{{ $fieldClass }}"></label>
        </div>
    @elseif($type === 'issue')
        <div class="grid gap-3 md:grid-cols-4">
            <label class="{{ $labelClass }} relative">Issued to
                <input name="issued_to_name" x-model="issuedToName" @input.debounce.250ms="searchIssueRecipients()" @focus="searchIssueRecipients()" @keydown.escape="issueRecipientResults = []" autocomplete="off" required placeholder="Search teacher/staff" class="{{ $fieldClass }}">
                <div x-show="issueRecipientResults.length" @click.outside="issueRecipientResults = []" class="absolute left-0 right-0 top-full z-40 mt-1 max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl" style="display:none;">
                    <template x-for="member in issueRecipientResults" :key="member.id">
                        <button type="button" @click="selectIssueRecipient(member)" class="block w-full border-b border-gray-100 px-3 py-2.5 text-left last:border-0 hover:bg-green-50">
                            <span class="block text-sm font-black text-gray-950" x-text="member.name || member.label"></span>
                            <span class="block text-[11px] font-semibold text-gray-500" x-text="member.meta"></span>
                        </button>
                    </template>
                </div>
                <span x-show="issueRecipientSearchError" x-text="issueRecipientSearchError" class="mt-1 block text-xs font-bold text-red-600"></span>
            </label>
            <label class="{{ $labelClass }}">Designation<input name="issued_to_designation" x-model="issuedToDesignation" placeholder="Post / section" class="{{ $fieldClass }}"></label>
            <label class="{{ $labelClass }}">Purpose<input name="purpose" value="{{ old('purpose', $recordData['purpose'] ?? '') }}" placeholder="Purpose" class="{{ $fieldClass }}"></label>
            <label class="{{ $labelClass }}">Issue date BS<input name="issued_at_bs" value="{{ old('issued_at_bs', $recordData['issued_at_bs'] ?? '') }}" maxlength="10" inputmode="numeric" placeholder="YYYY-MM-DD" class="{{ $dateClass }}"></label>
            <label class="{{ $labelClass }}">Store keeper<input name="store_keeper_name" value="{{ old('store_keeper_name', $recordData['store_keeper_name'] ?? $storeKeeperName ?? '') }}" placeholder="Store keeper" class="{{ $fieldClass }}"></label>
            <label class="{{ $labelClass }}">Approved by<input name="approved_by_name" value="{{ old('approved_by_name', $recordData['approved_by_name'] ?? $siteSettings->get('principal_name')) }}" placeholder="Approved by" class="{{ $fieldClass }}"></label>
        </div>
    @endif

    <div class="relative z-20 overflow-visible">
        <div class="{{ $type === 'issue' ? 'overflow-visible' : 'overflow-x-auto' }}">
        <table class="w-full min-w-[900px] table-fixed divide-y divide-gray-100 rounded-2xl border border-gray-200 text-sm">
            <colgroup>
                @if($type === 'issue')
                    <col class="w-[42%]">
                    <col class="w-[14%]">
                    <col class="w-[10%]">
                    <col>
                    <col class="w-[5rem]">
                @else
                    <col class="w-[19%]">
                    <col class="w-[16%]">
                    <col class="w-[18%]">
                    <col class="w-[8%]">
                    <col class="w-[7%]">
                    @if(in_array($type, ['purchase', 'receipt'], true))
                        <col class="w-[8%]">
                    @endif
                    @if($type === 'receipt')
                        <col class="w-[10%]">
                    @endif
                    <col>
                    <col class="w-[5rem]">
                @endif
            </colgroup>
            <thead class="bg-gray-50 text-left text-xs font-black uppercase tracking-widest text-gray-500">
                <tr>
                    <th class="px-2.5 py-2.5">Item</th>
                    @if($type === 'issue')
                        <th class="px-2.5 py-2.5">Available</th>
                    @else
                        <th class="px-2.5 py-2.5">Category / Ledger</th>
                        <th class="px-2.5 py-2.5">Specification</th>
                        <th class="px-2.5 py-2.5">Unit</th>
                    @endif
                    <th class="px-2.5 py-2.5">Qty</th>
                    @if(in_array($type, ['purchase', 'receipt'], true))
                        <th class="px-2.5 py-2.5">{{ $type === 'purchase' ? 'Rate without VAT' : 'Rate' }}</th>
                    @endif
                    @if($type === 'receipt')
                        <th class="px-2.5 py-2.5">Condition</th>
                    @endif
                    <th class="px-2.5 py-2.5">Remarks</th>
                    <th class="px-2 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="(line, index) in lines" :key="line.key">
                    <tr class="align-top">
                        <td class="px-2.5 py-2.5">
                            @if(in_array($type, ['requisition', 'purchase', 'issue'], true))
                                <div class="relative z-50">
                                    <input :name="`items[${index}][item_name]`" x-model="line.item_name" @input.debounce.250ms="searchStoreItems(index)" @focus="searchStoreItems(index)" @keydown.escape="line.itemResults = []" autocomplete="off" required placeholder="{{ $type === 'issue' ? 'Search stocked item' : ($type === 'requisition' ? 'Search or add item' : 'Search item or type name') }}" class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm font-bold text-gray-950 placeholder:text-gray-500 outline-none focus:border-[#1a5632]">
                                    <div x-show="line.itemResults?.length" @click.outside="line.itemResults = []" class="absolute left-0 right-0 top-full z-[9999] mt-1 max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-2xl ring-1 ring-black/5" style="display:none;">
                                        <template x-for="item in line.itemResults" :key="item.key || item.id || item.name">
                                            <button type="button" @click="selectStoreItem(index, item)" class="block w-full border-b border-gray-100 px-3 py-2.5 text-left last:border-0 hover:bg-green-50">
                                                <span class="block whitespace-normal break-words text-sm font-black text-gray-950" x-text="item.name"></span>
                                                <span class="block whitespace-normal break-words text-[11px] font-semibold text-gray-500" x-text="[item.code, item.unit, item.stock !== null && item.stock !== undefined ? `{{ $type === 'issue' ? 'Available' : 'Stock' }}: ${item.stock}` : ''].filter(Boolean).join(' · ')"></span>
                                            </button>
                                        </template>
                                    </div>
                                    <p x-show="line.itemSearchDone && !line.itemResults?.length && !line.store_item_id && line.item_name" class="mt-1 text-[11px] font-bold text-amber-700">{{ $type === 'issue' ? 'Only stocked Dakhila items can be issued.' : ($type === 'requisition' ? 'New item will be added when saved.' : 'Typed item will be saved on this purchase order.') }}</p>
                                </div>
                            @elseif($type === 'receipt')
                                <input :name="`items[${index}][item_name]`" x-model="line.item_name" required placeholder="Bill item name" class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm font-bold text-gray-950 placeholder:text-gray-500 outline-none focus:border-[#1a5632]">
                                @foreach($errors->getMessages() as $field => $messages)
                                    @if(preg_match('/^items\.(\d+)\.item_name$/', $field, $matches))
                                        <p x-show="index === {{ (int) $matches[1] }}" class="mt-1 text-[11px] font-bold text-red-600">{{ $messages[0] }}</p>
                                    @endif
                                @endforeach
                                <p x-show="line.store_purchase_order_item_id" class="mt-1.5 whitespace-normal break-words text-[11px] font-bold leading-4 text-gray-500">Fetched from PO; item name can be edited to match bill.</p>
                            @else
                                <select x-model="line.store_item_id" @change="fillLine(index)" required class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm font-bold text-gray-950 outline-none focus:border-[#1a5632]">
                                    <option value="">Select item</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" :name="`items[${index}][item_name]`" x-model="line.item_name">
                                <p x-show="line.item_name" x-text="line.item_name" class="mt-1.5 whitespace-normal break-words text-xs font-bold leading-4 text-gray-600"></p>
                            @endif
                            <input type="hidden" :name="`items[${index}][store_item_id]`" x-model="line.store_item_id">
                            <input type="hidden" :name="`items[${index}][store_requisition_item_id]`" x-model="line.store_requisition_item_id">
                            <input type="hidden" :name="`items[${index}][store_purchase_order_item_id]`" x-model="line.store_purchase_order_item_id">
                            @if($type === 'issue')
                                <input type="hidden" :name="`items[${index}][store_category_id]`" x-model="line.store_category_id">
                                <input type="hidden" :name="`items[${index}][specification]`" x-model="line.specification">
                                <input type="hidden" :name="`items[${index}][unit]`" x-model="line.unit">
                            @endif
                        </td>
                        @if($type === 'issue')
                            <td class="px-2.5 py-2.5">
                                <div class="min-h-10 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2 text-sm font-black text-gray-800">
                                    <span x-show="line.max_quantity" x-text="`${line.max_quantity} ${line.unit || ''}`"></span>
                                    <span x-show="!line.max_quantity" class="text-gray-400">Select item</span>
                                </div>
                            </td>
                        @else
                        <td class="px-2.5 py-2.5">
                            @if($type === 'receipt')
                                <input type="hidden" :name="`items[${index}][store_category_id]`" x-model="line.store_category_id">
                                <select x-show="!line.store_purchase_order_item_id" x-model="line.store_category_id" class="mb-1 w-full rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm font-bold text-gray-950 outline-none focus:border-[#1a5632]">
                                    <option value="">Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div x-show="line.store_purchase_order_item_id" class="mb-1 min-h-10 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2 text-sm font-bold text-gray-800" x-text="line.category_label || 'Fetched from PO'"></div>
                                <p class="whitespace-normal break-words text-[10px] font-black uppercase tracking-wide text-gray-500" x-text="line.ledger_type || (line.asset_type === 'non_consumable' ? 'Non-consumable' : 'Consumable')"></p>
                            @else
                                <select :name="`items[${index}][store_category_id]`" x-model="line.store_category_id" class="mb-1 w-full rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm font-bold text-gray-950 outline-none focus:border-[#1a5632]">
                                    <option value="">Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @if($type === 'requisition')
                                <select :name="`items[${index}][asset_type]`" x-model="line.asset_type" required class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-xs font-bold text-gray-950 outline-none focus:border-[#1a5632]">
                                    <option value="consumable">Consumable</option>
                                    <option value="non_consumable">Non-consumable</option>
                                </select>
                            @elseif($type !== 'receipt')
                                <p class="whitespace-normal break-words text-[10px] font-black uppercase tracking-wide text-gray-500" x-text="line.category_label ? `${line.category_label} · ${line.ledger_type || (line.asset_type === 'non_consumable' ? 'Non-consumable' : 'Consumable')}` : (line.ledger_type || (line.asset_type === 'non_consumable' ? 'Non-consumable' : 'Consumable'))"></p>
                            @endif
                        </td>
                        <td class="px-2.5 py-2.5"><textarea :name="`items[${index}][specification]`" x-model="line.specification" rows="2" @if($type === 'receipt') :readonly="!!line.store_purchase_order_item_id" @endif class="min-h-16 w-full resize-y rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm font-semibold leading-5 text-gray-950 outline-none focus:border-[#1a5632] read-only:bg-gray-50"></textarea></td>
                        <td class="px-2.5 py-2.5">
                            <input type="hidden" :name="`items[${index}][unit]`" x-model="line.unit">
                            @if($type === 'receipt')
                                <select x-show="!line.store_purchase_order_item_id" x-model="line.unit" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm font-bold text-gray-950 outline-none focus:border-[#1a5632]">
                                    <option value="">Unit</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->symbol }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                                    @endforeach
                                </select>
                                <div x-show="line.store_purchase_order_item_id" class="min-h-10 rounded-lg border border-gray-200 bg-gray-50 px-2 py-2 text-sm font-bold text-gray-800" x-text="line.unit || 'Fetched from PO'"></div>
                            @elseif($type === 'issue')
                                <div class="min-h-10 rounded-lg border border-gray-200 bg-gray-50 px-2 py-2 text-sm font-bold text-gray-800" x-text="line.unit || 'Fetched from item'"></div>
                            @else
                                <select x-model="line.unit" @if($type === 'requisition') required @endif class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm font-bold text-gray-950 outline-none focus:border-[#1a5632]">
                                    <option value="">Unit</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->symbol }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                                    @endforeach
                                </select>
                            @endif
                            @foreach($errors->getMessages() as $field => $messages)
                                @if(preg_match('/^items\.(\d+)\.unit$/', $field, $matches))
                                    <p x-show="index === {{ (int) $matches[1] }}" class="mt-1 text-[11px] font-bold text-red-600">{{ $messages[0] }}</p>
                                @endif
                            @endforeach
                        </td>
                        @endif
                        <td class="px-2.5 py-2.5">
                            <input :name="`items[${index}][quantity]`" x-model.number="line.quantity" type="number" min="0.01" :max="line.max_quantity || null" step="0.01" required class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm font-bold text-gray-950 outline-none focus:border-[#1a5632]">
                            @foreach($errors->getMessages() as $field => $messages)
                                @if(preg_match('/^items\.(\d+)\.quantity$/', $field, $matches))
                                    <p x-show="index === {{ (int) $matches[1] }}" class="mt-1 text-[11px] font-bold text-red-600">{{ $messages[0] }}</p>
                                @endif
                            @endforeach
                            @if(in_array($type, ['receipt', 'issue'], true))
                                <p x-show="line.max_quantity" class="mt-1 text-[11px] font-bold text-gray-500">Max: <span x-text="line.max_quantity"></span> <span x-text="line.unit || ''"></span></p>
                            @endif
                        </td>
                        @if(in_array($type, ['purchase', 'receipt'], true))
                            <td class="px-2.5 py-2.5">
                                <input :name="`items[${index}][rate]`" x-model.number="line.rate" type="number" min="0.01" :max="line.max_rate || null" step="0.01" required @if($type === 'receipt') :readonly="!!line.store_purchase_order_item_id" @endif class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm font-bold text-gray-950 outline-none focus:border-[#1a5632] read-only:bg-gray-50">
                                @foreach($errors->getMessages() as $field => $messages)
                                    @if(preg_match('/^items\.(\d+)\.rate$/', $field, $matches))
                                        <p x-show="index === {{ (int) $matches[1] }}" class="mt-1 text-[11px] font-bold text-red-600">{{ $messages[0] }}</p>
                                    @endif
                                @endforeach
                                @if($type === 'purchase')
                                    <p class="mt-1 text-[11px] font-bold text-amber-700">Enter base rate before VAT. VAT is added only in VAT mode.</p>
                                @elseif($type === 'receipt')
                                    <p x-show="line.store_purchase_order_item_id" class="mt-1 text-[11px] font-bold text-amber-700">Rate is without VAT. VAT is added from PO if applicable.</p>
                                @endif
                            </td>
                        @endif
                        @if($type === 'receipt')
                            <td class="px-2.5 py-2.5"><input :name="`items[${index}][condition]`" x-model="line.condition" placeholder="Good" class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm font-bold text-gray-950 outline-none focus:border-[#1a5632]"></td>
                        @endif
                        <td class="px-2.5 py-2.5"><textarea :name="`items[${index}][remarks]`" x-model="line.remarks" rows="2" class="min-h-16 w-full resize-y rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm font-semibold leading-5 text-gray-950 outline-none focus:border-[#1a5632]"></textarea></td>
                        <td class="px-2 py-2.5 text-center"><button type="button" @click="removeLine(index)" class="rounded-lg border border-red-200 px-2 py-2 text-[11px] font-black text-red-600 hover:bg-red-50">Remove</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <button type="button" @click="addLine()" x-show="('{{ $type }}' !== 'purchase' || !(hasDemandLines && hasDemandLines())) && ('{{ $type }}' !== 'receipt' || !(hasPurchaseOrderLines && hasPurchaseOrderLines()))" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-black text-gray-700 hover:bg-gray-50">+ Add Line</button>
        @if($type === 'purchase')
            <span x-show="hasDemandLines && hasDemandLines()" class="text-xs font-bold text-amber-700">Add Line is available only for direct purchase. Search another demand form above to add more demand items.</span>
        @elseif($type === 'receipt')
            <span x-show="hasPurchaseOrderLines && hasPurchaseOrderLines()" class="text-xs font-bold text-amber-700">Add Line is available only for custom Dakhila. Select more POs above to add PO items.</span>
        @endif
        <button class="rounded-xl bg-[#1a5632] px-6 py-3 text-sm font-black text-white hover:bg-[#0b2415]">{{ $method ? 'Update and Print' : 'Save and Print' }}</button>
    </div>
</form>
