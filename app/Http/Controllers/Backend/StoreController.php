<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Card\Student;
use App\Models\StoreBrand;
use App\Models\StoreCategory;
use App\Models\StoreFiscalYear;
use App\Models\StoreIssue;
use App\Models\StoreIssueItem;
use App\Models\StoreItem;
use App\Models\StorePurchaseOrder;
use App\Models\StorePurchaseOrderItem;
use App\Models\StoreReceipt;
use App\Models\StoreReceiptItem;
use App\Models\StoreRequisition;
use App\Models\StoreRequisitionItem;
use App\Models\StoreStockMovement;
use App\Models\StoreSupplier;
use App\Models\StoreUnit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function dashboard(Request $request): View
    {
        $items = StoreItem::with(['category', 'brand', 'unit'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('item_code', 'like', "%{$search}%")
                        ->orWhere('specification', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'items' => StoreItem::count(),
            'low_stock' => StoreItem::whereColumn('current_quantity', '<=', 'min_stock')->where('min_stock', '>', 0)->count(),
            'suppliers' => StoreSupplier::where('is_active', true)->count(),
            'stock_value' => StoreItem::sum('current_value'),
        ];

        return view('backend.store.dashboard', [
            'items' => $items,
            'formItems' => StoreItem::with(['unit', 'category'])->where('is_active', true)->orderBy('name')->get(),
            'summary' => $summary,
            'suppliers' => StoreSupplier::where('is_active', true)->orderBy('name')->get(),
            'categories' => StoreCategory::orderBy('name')->get(),
            'brands' => StoreBrand::orderBy('name')->get(),
            'units' => StoreUnit::orderBy('name')->get(),
            'activeFiscalYear' => $this->activeFiscalYear(),
            'fiscalYears' => StoreFiscalYear::orderByDesc('is_active')->latest()->get(),
            'openPurchaseOrders' => StorePurchaseOrder::with(['items.item.unit', 'items.item.category', 'items.category'])
                ->whereIn('status', ['draft', 'ordered'])
                ->latest('order_date')
                ->latest()
                ->limit(30)
                ->get(),
            'openRequisitions' => StoreRequisition::with(['items.item.category', 'items.category'])
                ->whereIn('status', ['draft', 'approved'])
                ->latest()
                ->limit(30)
                ->get(),
            'recentRequisitions' => StoreRequisition::withCount('items')->latest()->limit(20)->get(),
            'recentOrders' => StorePurchaseOrder::withCount('items')->latest()->limit(20)->get(),
            'recentReceipts' => StoreReceipt::withCount('items')->latest()->limit(20)->get(),
            'recentIssues' => StoreIssue::withCount('items')->latest()->limit(20)->get(),
        ]);
    }

    public function myIssuedItems(Request $request): View
    {
        $user = $request->user()->loadMissing('student');
        $names = collect([
            $user->name,
            $user->student?->full_name,
        ])->filter()->map(fn ($name) => trim((string) $name))->unique()->values();

        $items = StoreIssueItem::with(['item.category', 'issue'])
            ->whereColumn('returned_quantity', '<', 'quantity')
            ->whereHas('issue', function ($query) use ($names) {
                $query->where(function ($inner) use ($names) {
                    foreach ($names as $name) {
                        $inner->orWhere('issued_to_name', $name);
                    }
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('store.my-items', [
            'items' => $items,
            'names' => $names,
        ]);
    }

    public function requisitionsIndex(Request $request): View
    {
        $records = StoreRequisition::with(['items' => fn ($query) => $query->oldest('id')])
            ->withCount('items')
            ->when($request->filled('search'), fn ($q) => $q->where('requisition_no', 'like', "%{$request->search}%")->orWhere('requested_by_name', 'like', "%{$request->search}%"))
            ->latest()->paginate(20)->withQueryString();

        return view('backend.store.workflow.requisitions', [
            'records' => $records,
            'formItems' => StoreItem::with(['unit', 'category'])->where('is_active', true)->orderBy('name')->get(),
            'suppliers' => collect(),
            'categories' => StoreCategory::orderBy('name')->get(),
            'units' => StoreUnit::orderBy('name')->get(),
            'activeFiscalYear' => $this->activeFiscalYear(),
            'storeKeeperName' => $this->storeKeeperName(),
        ]);
    }

    public function purchaseOrdersIndex(Request $request): View
    {
        $records = StorePurchaseOrder::withCount('items')
            ->when($request->filled('search'), fn ($q) => $q->where('order_no', 'like', "%{$request->search}%")->orWhere('supplier_name', 'like', "%{$request->search}%"))
            ->latest()->paginate(20)->withQueryString();

        return view('backend.store.workflow.purchase-orders', [
            'records' => $records,
            'formItems' => StoreItem::with(['unit', 'category'])->where('is_active', true)->orderBy('name')->get(),
            'suppliers' => StoreSupplier::where('is_active', true)->orderBy('name')->get(),
            'categories' => StoreCategory::orderBy('name')->get(),
            'units' => StoreUnit::orderBy('name')->get(),
            'openRequisitions' => StoreRequisition::with(['items.item.category', 'items.category'])
                ->whereIn('status', ['draft', 'approved'])->latest()->limit(30)->get(),
            'activeFiscalYear' => $this->activeFiscalYear(),
            'storeKeeperName' => $this->storeKeeperName(),
        ]);
    }

    public function receiptsIndex(Request $request): View
    {
        $records = StoreReceipt::withCount('items')
            ->when($request->filled('search'), fn ($q) => $q->where('receipt_no', 'like', "%{$request->search}%")->orWhere('received_from', 'like', "%{$request->search}%"))
            ->latest()->paginate(20)->withQueryString();

        return view('backend.store.workflow.receipts', [
            'records' => $records,
            'formItems' => StoreItem::with(['unit', 'category'])->where('is_active', true)->orderBy('name')->get(),
            'suppliers' => StoreSupplier::where('is_active', true)->orderBy('name')->get(),
            'categories' => StoreCategory::orderBy('name')->get(),
            'units' => StoreUnit::orderBy('name')->get(),
            'openPurchaseOrders' => StorePurchaseOrder::with(['items.item.unit', 'items.item.category', 'items.category'])
                ->whereIn('status', ['draft', 'ordered'])->latest()->limit(30)->get(),
            'activeFiscalYear' => $this->activeFiscalYear(),
            'storeKeeperName' => $this->storeKeeperName(),
        ]);
    }

    public function issuesIndex(Request $request): View
    {
        $records = StoreIssue::withCount('items')
            ->when($request->filled('search'), fn ($q) => $q->where('issue_no', 'like', "%{$request->search}%")->orWhere('issued_to_name', 'like', "%{$request->search}%"))
            ->latest()->paginate(20)->withQueryString();

        return view('backend.store.workflow.issues', [
            'records' => $records,
            'formItems' => StoreItem::with(['unit', 'category'])->where('is_active', true)->where('current_quantity', '>', 0)->orderBy('name')->get(),
            'suppliers' => collect(),
            'categories' => StoreCategory::orderBy('name')->get(),
            'units' => StoreUnit::orderBy('name')->get(),
            'activeFiscalYear' => $this->activeFiscalYear(),
            'storeKeeperName' => $this->storeKeeperName(),
        ]);
    }

    public function slipsIndex(Request $request): View
    {
        return view('backend.store.workflow.slips', [
            'requisitions' => StoreRequisition::withCount('items')->latest()->paginate(15, ['*'], 'req_page'),
            'orders' => StorePurchaseOrder::withCount('items')->latest()->paginate(15, ['*'], 'ord_page'),
            'receipts' => StoreReceipt::withCount('items')->latest()->paginate(15, ['*'], 'rec_page'),
            'issues' => StoreIssue::withCount('items')->latest()->paginate(15, ['*'], 'iss_page'),
            'activeFiscalYear' => $this->activeFiscalYear(),
        ]);
    }

    public function reportsIndex(Request $request): View
    {
        return view('backend.store.workflow.reports', [
            'recentRequisitions' => StoreRequisition::withCount('items')->latest()->limit(20)->get(),
            'recentOrders' => StorePurchaseOrder::withCount('items')->latest()->limit(20)->get(),
            'recentReceipts' => StoreReceipt::withCount('items')->latest()->limit(20)->get(),
            'recentIssues' => StoreIssue::withCount('items')->latest()->limit(20)->get(),
            'activeFiscalYear' => $this->activeFiscalYear(),
        ]);
    }

    public function storeFiscalYear(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:20'],
            'starts_on_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'ends_on_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
        ]);

        $this->validateFiscalYearDates($validated);

        DB::transaction(function () use ($validated) {
            StoreFiscalYear::query()->update(['is_active' => false]);
            StoreFiscalYear::updateOrCreate(
                ['name' => $validated['name']],
                $validated + ['is_active' => true, 'created_by' => auth()->id()]
            );
        });

        return back()->with('success', 'Active store fiscal year updated.');
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        StoreSupplier::create($this->supplierRules($request) + ['is_active' => true]);

        return back()->with('success', 'Supplier added.');
    }

    public function suppliersIndex(Request $request): View
    {
        return view('backend.store.masters.index', [
            'type' => 'suppliers',
            'title' => 'Suppliers',
            'records' => StoreSupplier::withCount(['purchaseOrders', 'receipts'])
                ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'editRecord' => null,
        ]);
    }

    public function editSupplier(StoreSupplier $supplier): View
    {
        return view('backend.store.masters.index', [
            'type' => 'suppliers',
            'title' => 'Suppliers',
            'records' => StoreSupplier::withCount(['purchaseOrders', 'receipts'])->latest()->paginate(15),
            'editRecord' => $supplier,
        ]);
    }

    public function updateSupplier(Request $request, StoreSupplier $supplier): RedirectResponse
    {
        $supplier->update($this->supplierRules($request));

        return redirect()->route('admin.store.suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroySupplier(StoreSupplier $supplier): RedirectResponse
    {
        if ($supplier->purchaseOrders()->exists() || $supplier->receipts()->exists()) {
            return back()->withErrors('This supplier is already used in purchase/dakhila records and cannot be deleted.');
        }

        $supplier->delete();

        return back()->with('success', 'Supplier deleted.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        StoreCategory::create($this->categoryRules($request) + ['is_consumable' => true]);

        return back()->with('success', 'Category added.');
    }

    public function categoriesIndex(Request $request): View
    {
        return view('backend.store.masters.index', [
            'type' => 'categories',
            'title' => 'Categories',
            'records' => StoreCategory::with(['parent'])->withCount(['items', 'children'])
                ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'editRecord' => null,
            'categories' => StoreCategory::orderBy('name')->get(),
        ]);
    }

    public function editCategory(StoreCategory $category): View
    {
        return view('backend.store.masters.index', [
            'type' => 'categories',
            'title' => 'Categories',
            'records' => StoreCategory::with(['parent'])->withCount(['items', 'children'])->latest()->paginate(15),
            'editRecord' => $category,
            'categories' => StoreCategory::whereKeyNot($category->id)->orderBy('name')->get(),
        ]);
    }

    public function updateCategory(Request $request, StoreCategory $category): RedirectResponse
    {
        $category->update($this->categoryRules($request, $category));

        return redirect()->route('admin.store.categories.index')->with('success', 'Category updated.');
    }

    public function destroyCategory(StoreCategory $category): RedirectResponse
    {
        if ($category->items()->exists() || $category->children()->exists()) {
            return back()->withErrors('This category is already used by items or subcategories and cannot be deleted.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    public function storeBrand(Request $request): RedirectResponse
    {
        StoreBrand::create($this->brandRules($request));

        return back()->with('success', 'Brand added.');
    }

    public function brandsIndex(Request $request): View
    {
        return view('backend.store.masters.index', [
            'type' => 'brands',
            'title' => 'Brands',
            'records' => StoreBrand::withCount('items')
                ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'editRecord' => null,
        ]);
    }

    public function editBrand(StoreBrand $brand): View
    {
        return view('backend.store.masters.index', [
            'type' => 'brands',
            'title' => 'Brands',
            'records' => StoreBrand::withCount('items')->latest()->paginate(15),
            'editRecord' => $brand,
        ]);
    }

    public function updateBrand(Request $request, StoreBrand $brand): RedirectResponse
    {
        $brand->update($this->brandRules($request));

        return redirect()->route('admin.store.brands.index')->with('success', 'Brand updated.');
    }

    public function destroyBrand(StoreBrand $brand): RedirectResponse
    {
        if ($brand->items()->exists()) {
            return back()->withErrors('This brand is already used by store items and cannot be deleted.');
        }

        $brand->delete();

        return back()->with('success', 'Brand deleted.');
    }

    public function storeUnit(Request $request): RedirectResponse
    {
        StoreUnit::create($this->unitRules($request) + ['allow_decimal' => $request->boolean('allow_decimal')]);

        return back()->with('success', 'Unit added.');
    }

    public function unitsIndex(Request $request): View
    {
        return view('backend.store.masters.index', [
            'type' => 'units',
            'title' => 'Units',
            'records' => StoreUnit::withCount('items')
                ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->input('search').'%'))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'editRecord' => null,
        ]);
    }

    public function editUnit(StoreUnit $unit): View
    {
        return view('backend.store.masters.index', [
            'type' => 'units',
            'title' => 'Units',
            'records' => StoreUnit::withCount('items')->latest()->paginate(15),
            'editRecord' => $unit,
        ]);
    }

    public function updateUnit(Request $request, StoreUnit $unit): RedirectResponse
    {
        $unit->update($this->unitRules($request) + ['allow_decimal' => $request->boolean('allow_decimal')]);

        return redirect()->route('admin.store.units.index')->with('success', 'Unit updated.');
    }

    public function destroyUnit(StoreUnit $unit): RedirectResponse
    {
        if ($unit->items()->exists()) {
            return back()->withErrors('This unit is already used by store items and cannot be deleted.');
        }

        $unit->delete();

        return back()->with('success', 'Unit deleted.');
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $validated = $this->itemRules($request);

        DB::transaction(function () use ($validated) {
            $quantity = 0;
            $rate = 0;
            $validated['item_code'] = $validated['item_code'] ?: $this->nextCode('ITM', StoreItem::class, 'item_code');
            $validated['asset_type'] = $validated['asset_type'] ?? 'consumable';
            $item = StoreItem::create($validated + [
                'min_stock' => $validated['min_stock'] ?? 0,
                'opening_quantity' => $quantity,
                'opening_rate' => $rate,
                'current_quantity' => $quantity,
                'current_value' => round($quantity * $rate, 2),
                'is_active' => true,
            ]);

            if ($quantity > 0) {
                $this->movement($item, 'opening', $quantity, 0, $rate, null, null, 'Opening balance');
            }
        });

        return back()->with('success', 'Store item added.');
    }

    public function itemsIndex(): RedirectResponse
    {
        return redirect()->route('admin.store.requisitions.index');
    }

    public function editItem(StoreItem $item): View
    {
        return view('backend.store.items.index', [
            'items' => StoreItem::with(['category', 'brand', 'unit'])->withCount('movements')->latest()->paginate(15),
            'categories' => StoreCategory::orderBy('name')->get(),
            'brands' => StoreBrand::orderBy('name')->get(),
            'units' => StoreUnit::orderBy('name')->get(),
            'editItem' => $item,
        ]);
    }

    public function updateItem(Request $request, StoreItem $item): RedirectResponse
    {
        $validated = $this->itemRules($request, $item);
        if (blank($validated['item_code'] ?? null)) {
            unset($validated['item_code']);
        }
        $validated['asset_type'] = $validated['asset_type'] ?? $item->asset_type ?? 'consumable';
        $item->update($validated + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.store.items.index')->with('success', 'Store item updated.');
    }

    public function destroyItem(StoreItem $item): RedirectResponse
    {
        if ($item->movements()->exists()) {
            return back()->withErrors('This item already has stock movement history and cannot be deleted.');
        }

        $item->delete();

        return back()->with('success', 'Store item deleted.');
    }

    public function storeRequisition(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requested_by_name' => ['required', 'string', 'max:190'],
            'requested_by_designation' => ['nullable', 'string', 'max:190'],
            'purpose' => ['nullable', 'string', 'max:190'],
            'requested_at_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'approved_by_name' => ['nullable', 'string', 'max:190'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.store_item_id' => ['nullable', 'exists:store_items,id'],
            'items.*.store_category_id' => ['required', 'exists:store_categories,id'],
            'items.*.asset_type' => ['required', Rule::in(['consumable', 'non_consumable'])],
            'items.*.store_purchase_order_item_id' => ['nullable', 'exists:store_purchase_order_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:190'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.unit' => ['required', 'string', 'max:40'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.remarks' => ['nullable', 'string', 'max:190'],
        ]);

        $requisition = DB::transaction(function () use ($validated) {
            $validated['items'] = $this->resolveRequisitionItems($validated['items']);
            $requisition = StoreRequisition::create([
                'requisition_no' => $this->nextRequisitionCode(),
                'requested_by_name' => $validated['requested_by_name'],
                'requested_by_designation' => $validated['requested_by_designation'] ?? null,
                'purpose' => $validated['purpose'] ?? null,
                'fiscal_year' => $this->fiscalYear(),
                'status' => $validated['approved_by_name'] ? 'approved' : 'draft',
                'requested_at_bs' => $validated['requested_at_bs'] ?? null,
                'approved_at_bs' => $validated['approved_by_name'] ? ($validated['requested_at_bs'] ?? null) : null,
                'approved_by_name' => $validated['approved_by_name'] ?? null,
                'created_by' => auth()->id(),
            ]);
            $requisition->items()->createMany($this->rows($validated['items']));

            return $requisition;
        });

        return redirect()->route('admin.store.forms.show', ['type' => 'requisition', 'id' => $requisition->id])->with('success', 'Demand form created.');
    }

    public function storePurchaseOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_supplier_id' => ['nullable', 'exists:store_suppliers,id'],
            'supplier_name' => ['required', 'string', 'max:190'],
            'supplier_address' => ['nullable', 'string', 'max:255'],
            'supplier_phone' => ['nullable', 'string', 'max:40'],
            'store_requisition_id' => ['nullable', 'exists:store_requisitions,id'],
            'decision_no' => ['nullable', 'string', 'max:80'],
            'decision_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'order_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'expected_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'tax_mode' => ['required', Rule::in(['pan', 'vat'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.store_item_id' => ['nullable', 'exists:store_items,id'],
            'items.*.store_category_id' => ['nullable', 'exists:store_categories,id'],
            'items.*.store_requisition_item_id' => ['nullable', 'exists:store_requisition_items,id'],
            'items.*.store_purchase_order_item_id' => ['nullable', 'exists:store_purchase_order_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:190'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.unit' => ['required', 'string', 'max:40'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['required', 'numeric', 'min:0.01'],
            'items.*.remarks' => ['nullable', 'string', 'max:190'],
        ]);

        $this->validatePurchaseOrderDates($validated);
        $this->syncPurchaseSupplierTaxMode($validated);
        $validated['items'] = $this->applyPurchaseTaxMode($validated['items'], $validated['tax_mode']);
        $this->syncPurchaseRequisitionHeader($validated);

        $order = DB::transaction(function () use ($validated) {
            $this->validatePurchaseAgainstRequisition($validated);

            $order = StorePurchaseOrder::create($validated + [
                'order_no' => $this->nextCode('PO', StorePurchaseOrder::class, 'order_no'),
                'fiscal_year' => $this->fiscalYear(),
                'status' => 'ordered',
                'created_by' => auth()->id(),
            ]);
            $order->items()->createMany($this->rows($validated['items'], true, true));

            return $order;
        });

        return redirect()->route('admin.store.forms.show', ['type' => 'purchase-order', 'id' => $order->id])->with('success', 'Purchase order created.');
    }

    public function storeReceipt(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_purchase_order_id' => ['nullable', 'exists:store_purchase_orders,id'],
            'purchase_order_ids' => ['nullable', 'array'],
            'purchase_order_ids.*' => ['nullable', 'exists:store_purchase_orders,id'],
            'store_supplier_id' => ['nullable', 'exists:store_suppliers,id'],
            'received_from' => ['nullable', 'string', 'max:190'],
            'challan_no' => ['nullable', 'string', 'max:80'],
            'invoice_no' => ['nullable', 'string', 'max:80'],
            'invoice_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'received_at_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'received_by_name' => ['nullable', 'string', 'max:190'],
            'verified_by_name' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.store_item_id' => ['nullable', 'exists:store_items,id'],
            'items.*.store_category_id' => ['nullable', 'exists:store_categories,id'],
            'items.*.store_purchase_order_item_id' => ['nullable', 'exists:store_purchase_order_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:190'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.unit' => ['nullable', 'string', 'max:40'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['required', 'numeric', 'min:0.01'],
            'items.*.condition' => ['nullable', 'string', 'max:190'],
            'items.*.remarks' => ['nullable', 'string', 'max:190'],
        ], [], $this->lineValidationAttributes($request));

        $this->validateReceiptDates($validated);
        $this->syncReceiptPurchaseOrderHeader($validated);

        $receipt = DB::transaction(function () use ($validated) {
            $this->validateReceiptAgainstPurchaseOrder($validated);
            $purchaseOrderIds = $this->receiptPurchaseOrderIds($validated);

            $receipt = StoreReceipt::create($validated + [
                'receipt_no' => $this->nextCode('RCV', StoreReceipt::class, 'receipt_no'),
                'fiscal_year' => $this->fiscalYear(),
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            foreach ($this->receiptRows($validated['items']) as $row) {
                $receipt->items()->create($row);
                if (! empty($row['store_item_id'])) {
                    $item = StoreItem::lockForUpdate()->find($row['store_item_id']);
                    $this->postReceipt($item, (float) $row['quantity'], (float) $row['rate'], $receipt, (float) $row['amount']);
                }
            }

            $purchaseOrderIds->each(fn ($purchaseOrderId) => $this->refreshPurchaseOrderStatus((int) $purchaseOrderId));

            return $receipt;
        });

        return redirect()->route('admin.store.forms.show', ['type' => 'receipt', 'id' => $receipt->id])->with('success', 'Dakhila report posted.');
    }

    public function storeIssue(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_requisition_id' => ['nullable', 'exists:store_requisitions,id'],
            'issued_to_name' => ['required', 'string', 'max:190'],
            'issued_to_designation' => ['nullable', 'string', 'max:190'],
            'purpose' => ['nullable', 'string', 'max:190'],
            'issued_at_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'approved_by_name' => ['nullable', 'string', 'max:190'],
            'store_keeper_name' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.store_item_id' => ['required', 'distinct', 'exists:store_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:190'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.unit' => ['nullable', 'string', 'max:40'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.remarks' => ['nullable', 'string', 'max:190'],
        ], [], $this->lineValidationAttributes($request));

        $issue = DB::transaction(function () use ($validated) {
            $issue = StoreIssue::create($validated + [
                'issue_no' => $this->nextCode('ISS', StoreIssue::class, 'issue_no'),
                'fiscal_year' => $this->fiscalYear(),
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            foreach ($this->rows($validated['items']) as $row) {
                $item = StoreItem::lockForUpdate()->find($row['store_item_id']);
                $rate = $item->average_rate;
                $row['rate'] = $rate;
                $row['amount'] = round((float) $row['quantity'] * $rate, 2);
                $issue->items()->create($row);
                $this->postIssue($item, (float) $row['quantity'], $rate, $issue);
            }

            return $issue;
        });

        return redirect()->route('admin.store.forms.show', ['type' => 'issue', 'id' => $issue->id])->with('success', 'Issue form posted.');
    }

    public function returnIssueItem(Request $request, StoreIssueItem $issueItem): RedirectResponse
    {
        $validated = $request->validate([
            'returned_quantity' => ['required', 'numeric', 'min:0.01'],
            'returned_at_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
        ]);

        DB::transaction(function () use ($validated, $issueItem) {
            $issueItem->loadMissing('item', 'issue');
            $remaining = round((float) $issueItem->quantity - (float) $issueItem->returned_quantity, 2);
            $quantity = round((float) $validated['returned_quantity'], 2);

            if ($quantity > $remaining + 0.000001) {
                throw ValidationException::withMessages([
                    'returned_quantity' => 'Return quantity cannot exceed issued remaining quantity.',
                ]);
            }

            $item = StoreItem::lockForUpdate()->findOrFail($issueItem->store_item_id);
            $isNonConsumable = in_array($item->asset_type, ['non_consumable', 'fixed_asset'], true);
            $rate = (float) $issueItem->rate;
            $amount = round($quantity * $rate, 2);

            if (! $isNonConsumable) {
                $item->current_quantity = round((float) $item->current_quantity + $quantity, 2);
                $item->current_value = round((float) $item->current_value + $amount, 2);
                $item->save();
                $this->movement($item, 'return', $quantity, 0, $rate, $issueItem::class, $issueItem->id, 'Return '.$issueItem->issue?->issue_no, $validated['returned_at_bs'] ?? null);
            }

            $issueItem->returned_quantity = round((float) $issueItem->returned_quantity + $quantity, 2);
            if ((float) $issueItem->returned_quantity >= (float) $issueItem->quantity) {
                $issueItem->returned_at_bs = $validated['returned_at_bs'] ?? $issueItem->issue?->issued_at_bs;
                $issueItem->returned_at = now();
                $issueItem->returned_by = auth()->id();
            }
            $issueItem->save();
        });

        return back()->with('success', 'Issued item returned.');
    }

    public function searchItems(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));
        $issueOnly = $request->input('context') === 'issue';

        $items = StoreItem::with(['unit', 'category'])
            ->where('is_active', true)
            ->when($issueOnly, fn ($query) => $query->where('current_quantity', '>', 0))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('item_code', 'like', "%{$search}%")
                    ->orWhere('specification', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(function (StoreItem $item) use ($issueOnly) {
                $isNonConsumable = in_array($item->asset_type, ['non_consumable', 'fixed_asset'], true);
                $activeIssued = $issueOnly && $isNonConsumable
                    ? (float) StoreIssueItem::where('store_item_id', $item->id)
                        ->whereColumn('returned_quantity', '<', 'quantity')
                        ->sum(DB::raw('quantity - returned_quantity'))
                    : 0.0;
                $availableStock = $isNonConsumable
                    ? max((float) $item->current_quantity - $activeIssued, 0)
                    : (float) $item->current_quantity;

                return [
                    'key' => 'master-'.$item->id,
                    'id' => $item->id,
                    'name' => $item->name,
                    'code' => $item->item_code,
                    'unit' => $item->unit?->symbol,
                    'category_id' => $item->store_category_id,
                    'category' => $item->category?->name,
                    'ledger_type' => $isNonConsumable ? 'Non-consumable' : 'Consumable',
                    'asset_type' => $isNonConsumable ? 'non_consumable' : 'consumable',
                    'specification' => $item->specification,
                    'rate' => $item->average_rate,
                    'stock' => $availableStock,
                    'source' => 'Item master',
                ];
            })
            ->when($issueOnly, fn ($items) => $items->filter(fn ($item) => (float) $item['stock'] > 0)->values());

        if ($issueOnly) {
            return response()->json(['results' => $items->values()]);
        }

        $knownNames = $items->pluck('name')->map(fn ($name) => mb_strtolower(trim((string) $name)));
        $history = collect()
            ->merge(StorePurchaseOrderItem::with(['item.unit', 'category'])
                ->when($search !== '', fn ($query) => $query->where('item_name', 'like', "%{$search}%"))
                ->latest()->limit(15)->get()
                ->map(fn (StorePurchaseOrderItem $item) => [
                    'key' => 'purchase-'.$item->id,
                    'id' => $item->store_item_id,
                    'name' => $item->item_name,
                    'code' => $item->item?->item_code,
                    'unit' => $item->unit ?: $item->item?->unit?->symbol,
                    'category_id' => $item->store_category_id ?: $item->item?->store_category_id,
                    'category' => $item->category?->name ?: $item->item?->category?->name,
                    'ledger_type' => in_array($item->item?->asset_type, ['non_consumable', 'fixed_asset'], true) ? 'Non-consumable' : 'Consumable',
                    'asset_type' => in_array($item->item?->asset_type, ['non_consumable', 'fixed_asset'], true) ? 'non_consumable' : 'consumable',
                    'specification' => $item->specification,
                    'rate' => (float) $item->rate,
                    'stock' => $item->item ? (float) $item->item->current_quantity : null,
                    'source' => 'Past purchase',
                ]))
            ->merge(StoreRequisitionItem::with(['item.unit', 'category'])
                ->when($search !== '', fn ($query) => $query->where('item_name', 'like', "%{$search}%"))
                ->latest()->limit(15)->get()
                ->map(fn (StoreRequisitionItem $item) => [
                    'key' => 'requisition-'.$item->id,
                    'id' => $item->store_item_id,
                    'name' => $item->item_name,
                    'code' => $item->item?->item_code,
                    'unit' => $item->unit ?: $item->item?->unit?->symbol,
                    'category_id' => $item->store_category_id ?: $item->item?->store_category_id,
                    'category' => $item->category?->name ?: $item->item?->category?->name,
                    'ledger_type' => in_array($item->item?->asset_type, ['non_consumable', 'fixed_asset'], true) ? 'Non-consumable' : 'Consumable',
                    'asset_type' => $item->item?->asset_type === 'non_consumable' || $item->item?->asset_type === 'fixed_asset'
                        ? 'non_consumable'
                        : 'consumable',
                    'specification' => $item->specification,
                    'rate' => $item->item?->average_rate ?? 0,
                    'stock' => $item->item ? (float) $item->item->current_quantity : null,
                    'source' => 'Past requisition',
                ]))
            ->filter(fn ($item) => filled($item['name']) && ! $knownNames->contains(mb_strtolower(trim((string) $item['name']))))
            ->unique(fn ($item) => mb_strtolower(trim((string) $item['name'])))
            ->take(max(15 - $items->count(), 0));

        return response()->json(['results' => $items->concat($history)->values()]);
    }

    public function searchRequisitions(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        $requisitions = StoreRequisition::with(['items.item.category', 'items.category'])
            ->whereIn('status', ['draft', 'approved'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('requisition_no', 'like', "%{$search}%")
                        ->orWhere('requested_by_name', 'like', "%{$search}%")
                        ->orWhere('requested_by_designation', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (StoreRequisition $requisition) => [
                'id' => $requisition->id,
                'label' => $requisition->display_requisition_no.' - '.$requisition->requested_by_name,
                'meta' => collect([
                    $requisition->requested_by_designation,
                    $requisition->purpose,
                    ucfirst((string) $requisition->status),
                ])->filter()->implode(' · '),
                'items' => $requisition->items->map(fn ($item) => [
                    'requisition_item_id' => $item->id,
                    'store_item_id' => $item->store_item_id,
                    'store_category_id' => $item->store_category_id ?: $item->item?->store_category_id,
                    'item_name' => $item->item_name,
                    'specification' => $item->specification,
                    'unit' => $item->unit,
                    'quantity' => (float) $item->remaining_quantity,
                    'requested_quantity' => (float) $item->quantity,
                    'remarks' => $item->remarks,
                ])->filter(fn ($item) => $item['quantity'] > 0)->values(),
            ])
            ->filter(fn ($requisition) => $requisition['items']->isNotEmpty())
            ->values();

        return response()->json(['results' => $requisitions]);
    }

    public function searchPurchaseOrders(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        $orders = StorePurchaseOrder::with(['items.item.unit', 'items.item.category', 'items.category'])
            ->whereIn('status', ['draft', 'ordered'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('order_no', 'like', "%{$search}%")
                        ->orWhere('supplier_name', 'like', "%{$search}%")
                        ->orWhere('supplier_address', 'like', "%{$search}%")
                        ->orWhere('supplier_phone', 'like', "%{$search}%");
                });
            })
            ->latest('order_date')
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (StorePurchaseOrder $order) => $this->purchaseOrderPayload($order))
            ->filter(fn (array $order) => $order['items']->isNotEmpty())
            ->values();

        return response()->json(['results' => $orders]);
    }

    public function searchHrMembers(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));
        $staffOnly = in_array($request->input('context'), ['issue', 'staff'], true);
        $memberTypes = $staffOnly
            ? ['teacher', 'staff']
            : ['student', 'teacher', 'staff'];

        $members = Student::query()
            ->with('user:id,name')
            ->whereIn('member_type', $memberTypes)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) LIKE ?", ["%{$search}%"])
                        ->orWhere('roll_number', 'like', "%{$search}%")
                        ->orWhere('registration_no', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(12)
            ->get()
            ->map(function (Student $member) {
                $name = trim($member->full_name) ?: trim((string) $member->user?->name);
                $designation = $member->designation ?: ucfirst((string) $member->member_type);

                return [
                    'id' => $member->id,
                    'name' => $name,
                    'designation' => $designation,
                    'label' => collect([$name, $designation])->filter()->implode(' · '),
                    'meta' => collect([$member->roll_number, $member->mobile, $member->email])->filter()->implode(' · '),
                ];
            })
            ->filter(fn (array $member) => $member['name'] !== '')
            ->values();

        return response()->json(['results' => $members]);
    }

    public function form(string $type, ?int $id = null): View
    {
        $record = match ($type) {
            'requisition' => StoreRequisition::with('items.item.category', 'items.category')->findOrFail($id),
            'purchase-order' => StorePurchaseOrder::with('items.item.category', 'items.category', 'requisition')->findOrFail($id),
            'receipt' => StoreReceipt::with('items.item.category', 'items.purchaseOrderItem.category', 'items.purchaseOrderItem.purchaseOrder')->findOrFail($id),
            'issue' => StoreIssue::with('items.item')->findOrFail($id),
            'ledger-consumable', 'ledger-non-consumable' => null,
            default => abort(404),
        };

        $movements = collect();
        if (in_array($type, ['ledger-consumable', 'ledger-non-consumable'], true)) {
            $isConsumable = $type === 'ledger-consumable';
            $movements = StoreStockMovement::with('item.unit', 'item.category')
                ->whereHas('item', function ($query) use ($isConsumable) {
                    if ($isConsumable) {
                        $query->where('asset_type', 'consumable');
                    } else {
                        $query->whereIn('asset_type', ['non_consumable', 'fixed_asset']);
                    }
                })
                ->latest('movement_date')
                ->latest()
                ->limit(100)
                ->get();
        }

        return view('backend.store.forms.show', [
            'type' => $type,
            'record' => $record,
            'movements' => $movements,
            'activeFiscalYear' => $this->activeFiscalYear(),
        ]);
    }

    public function edit(string $type, int $id): View
    {
        $record = $this->document($type, $id);
        $record->load('items');

        return view('backend.store.edit', [
            'type' => $type,
            'record' => $record,
            'title' => $this->documentTitle($type),
            'items' => StoreItem::with(['unit', 'category'])->where('is_active', true)->orderBy('name')->get(),
            'suppliers' => StoreSupplier::where('is_active', true)->orderBy('name')->get(),
            'categories' => StoreCategory::orderBy('name')->get(),
            'units' => StoreUnit::orderBy('name')->get(),
            'requisitions' => StoreRequisition::with(['items.item.category', 'items.category'])
                ->whereIn('status', ['draft', 'approved'])
                ->latest()
                ->limit(30)
                ->get(),
            'purchaseOrders' => StorePurchaseOrder::with(['items.item.unit', 'items.item.category', 'items.category'])
                ->where(function ($query) use ($type, $record) {
                    $query->whereIn('status', ['draft', 'ordered']);
                    if ($type === 'receipt' && $record instanceof StoreReceipt) {
                        $relatedPurchaseOrderIds = $this->receiptRelatedPurchaseOrderIds($record);
                        if ($relatedPurchaseOrderIds->isNotEmpty()) {
                            $query->orWhereIn('id', $relatedPurchaseOrderIds);
                        }
                    }
                })
                ->latest('order_date')
                ->latest()
                ->limit(30)
                ->get(),
            'recordData' => $this->recordData($type, $record),
            'storeKeeperName' => $this->storeKeeperName(),
            'lines' => $record->items->map(fn ($item) => [
                'store_requisition_item_id' => $item->store_requisition_item_id ?? '',
                'requisition_id' => $item->requisitionItem?->store_requisition_id ?? '',
                'store_purchase_order_item_id' => $item->store_purchase_order_item_id ?? '',
                'store_item_id' => $item->store_item_id,
                'store_category_id' => $item->store_category_id ?? $item->item?->store_category_id,
                'category_label' => $item->purchaseOrderItem?->category?->name ?: $item->purchaseOrderItem?->item?->category?->name ?: $item->item?->category?->name,
                'ledger_type' => in_array($item->item?->asset_type ?? $item->purchaseOrderItem?->item?->asset_type, ['non_consumable', 'fixed_asset'], true) ? 'Non-consumable' : 'Consumable',
                'asset_type' => $item->item?->asset_type === 'non_consumable' || $item->item?->asset_type === 'fixed_asset'
                    ? 'non_consumable'
                    : (in_array($item->purchaseOrderItem?->item?->asset_type, ['non_consumable', 'fixed_asset'], true) ? 'non_consumable' : 'consumable'),
                'item_name' => $item->item_name,
                'specification' => $item->specification,
                'unit' => $item->unit,
                'quantity' => (float) $item->quantity,
                'rate' => (float) ($item->rate ?? 0),
                'max_rate' => (float) ($item->purchaseOrderItem?->rate ?? 0),
                'tax_rate' => (float) ($item->tax_rate ?? $item->purchaseOrderItem?->tax_rate ?? 0),
                'condition' => $item->condition ?? '',
                'remarks' => $item->remarks,
            ])->values(),
        ]);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $record = $this->document($type, $id);
        $this->guardEditable($type, $record);

        match ($type) {
            'requisition' => $this->updateRequisition($request, $record),
            'purchase-order' => $this->updatePurchaseOrder($request, $record),
            'receipt' => $this->updateReceipt($request, $record),
            'issue' => $this->updateIssue($request, $record),
            default => abort(404),
        };

        return redirect()->route('admin.store.forms.show', ['type' => $type, 'id' => $record->id])->with('success', 'Slip updated.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $record = $this->document($type, $id);
        $this->guardEditable($type, $record);

        DB::transaction(function () use ($type, $record) {
            $purchaseOrderIds = $record instanceof StoreReceipt ? $this->receiptRelatedPurchaseOrderIds($record) : collect();
            if ($type === 'receipt') {
                $this->reverseReceipt($record);
            }
            if ($type === 'issue') {
                $this->reverseIssue($record);
            }
            $record->delete();
            $purchaseOrderIds->each(fn ($purchaseOrderId) => $this->refreshPurchaseOrderStatus((int) $purchaseOrderId));
        });

        return redirect()->route('admin.store.dashboard')->with('success', 'Slip deleted.');
    }

    private function rows(array $items, bool $withRate = false, bool $withTax = false, bool $withCondition = false): array
    {
        return collect($items)
            ->filter(fn ($item) => trim((string) ($item['item_name'] ?? '')) !== '')
            ->map(function ($item) use ($withRate, $withTax, $withCondition) {
                $quantity = round((float) ($item['quantity'] ?? 0), 2);
                $rate = $withRate ? round((float) ($item['rate'] ?? 0), 2) : round((float) ($item['rate'] ?? 0), 2);

                $row = [
                    'store_item_id' => $item['store_item_id'] ?? null,
                    'store_category_id' => $item['store_category_id'] ?? null,
                    'store_requisition_item_id' => $item['store_requisition_item_id'] ?? null,
                    'store_purchase_order_item_id' => $item['store_purchase_order_item_id'] ?? null,
                    'item_name' => trim($item['item_name']),
                    'specification' => $item['specification'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'quantity' => $quantity,
                    'remarks' => $item['remarks'] ?? null,
                ];

                if ($withRate) {
                    $taxRate = $withTax ? round((float) ($item['tax_rate'] ?? 0), 2) : 0;
                    $row['rate'] = $rate;
                    $row['amount'] = round($quantity * $rate, 2);
                }

                if ($withTax) {
                    $row['tax_rate'] = round((float) ($item['tax_rate'] ?? 0), 2);
                }

                if ($withCondition) {
                    $row['condition'] = $item['condition'] ?? null;
                }

                return $row;
            })
            ->values()
            ->all();
    }

    private function receiptRows(array $items): array
    {
        $rows = $this->rows($items, true, false, true);
        $orderLineIds = collect($rows)
            ->pluck('store_purchase_order_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($orderLineIds->isEmpty()) {
            return $rows;
        }

        $orderLines = StorePurchaseOrderItem::query()
            ->whereIn('id', $orderLineIds)
            ->get()
            ->keyBy('id');

        return collect($rows)
            ->map(function (array $row) use ($orderLines) {
                $orderLine = $orderLines->get((int) ($row['store_purchase_order_item_id'] ?? 0));
                if (! $orderLine) {
                    return $row;
                }

                $quantity = (float) $row['quantity'];
                $rate = (float) $orderLine->rate;
                $taxRate = (float) $orderLine->tax_rate;

                $row['store_item_id'] = $orderLine->store_item_id;
                $row['specification'] = $orderLine->specification;
                $row['unit'] = $orderLine->unit;
                $row['rate'] = round($rate, 2);
                $row['amount'] = round($quantity * $rate * (1 + ($taxRate / 100)), 2);

                return $row;
            })
            ->all();
    }

    private function lineValidationAttributes(Request $request): array
    {
        $attributes = [];
        foreach (array_keys((array) $request->input('items', [])) as $index) {
            $line = ((int) $index) + 1;
            $attributes["items.{$index}.store_item_id"] = "Item {$line}";
            $attributes["items.{$index}.item_name"] = "Item {$line} name";
            $attributes["items.{$index}.quantity"] = "Item {$line} quantity";
            $attributes["items.{$index}.rate"] = "Item {$line} rate";
            $attributes["items.{$index}.unit"] = "Item {$line} unit";
            $attributes["items.{$index}.specification"] = "Item {$line} specification";
        }

        return $attributes;
    }

    private function applyPurchaseTaxMode(array $items, string $taxMode): array
    {
        $taxRate = $taxMode === 'vat' ? 13 : 0;

        return collect($items)
            ->map(function (array $item) use ($taxRate) {
                $item['tax_rate'] = $taxRate;

                return $item;
            })
            ->all();
    }

    private function syncPurchaseSupplierTaxMode(array &$validated): void
    {
        if (empty($validated['store_supplier_id'])) {
            return;
        }

        $supplier = StoreSupplier::find($validated['store_supplier_id']);
        $validated['tax_mode'] = $supplier?->tax_registration_type === 'vat' ? 'vat' : 'pan';
    }

    private function supplierRules(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'contact_person' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'tax_registration_type' => ['required', Rule::in(['pan', 'vat'])],
            'pan_vat_no' => ['nullable', 'string', 'max:80'],
            'registration_no' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function categoryRules(Request $request, ?StoreCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('store_categories', 'code')->ignore($category?->id)],
            'parent_id' => ['nullable', 'exists:store_categories,id', $category ? Rule::notIn([$category->id]) : 'nullable'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function brandRules(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function unitRules(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:30'],
        ]);
    }

    private function itemRules(Request $request, ?StoreItem $item = null): array
    {
        return $request->validate([
            'store_category_id' => ['nullable', 'exists:store_categories,id'],
            'store_brand_id' => ['nullable', 'exists:store_brands,id'],
            'store_unit_id' => ['nullable', 'exists:store_units,id'],
            'item_code' => ['nullable', 'string', 'max:60', Rule::unique('store_items', 'item_code')->ignore($item?->id)],
            'name' => ['required', 'string', 'max:190'],
            'specification' => ['nullable', 'string', 'max:2000'],
            'model_no' => ['nullable', 'string', 'max:190'],
            'serial_no' => ['nullable', 'string', 'max:190'],
            'asset_type' => ['nullable', Rule::in(['consumable', 'non_consumable', 'fixed_asset'])],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'storage_location' => ['nullable', 'string', 'max:190'],
            'useful_life_months' => ['nullable', 'integer', 'min:1'],
            'depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    private function resolveRequisitionItems(array $rows): array
    {
        return collect($rows)->map(function (array $row) {
            $name = trim((string) ($row['item_name'] ?? ''));
            $requestedAssetType = ($row['asset_type'] ?? 'consumable') === 'non_consumable'
                ? 'non_consumable'
                : 'consumable';
            $item = ! empty($row['store_item_id']) ? StoreItem::find($row['store_item_id']) : null;

            if (! $item && $name !== '') {
                $item = StoreItem::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first();
            }

            if (! $item && $name !== '') {
                $unitSymbol = trim((string) ($row['unit'] ?? ''));
                $unit = $unitSymbol !== ''
                    ? StoreUnit::whereRaw('LOWER(TRIM(symbol)) = ?', [mb_strtolower($unitSymbol)])->first()
                    : null;

                $item = StoreItem::create([
                    'store_category_id' => $row['store_category_id'] ?? null,
                    'store_unit_id' => $unit?->id,
                    'item_code' => $this->nextCode('ITM', StoreItem::class, 'item_code'),
                    'name' => $name,
                    'specification' => $row['specification'] ?? null,
                    'asset_type' => $requestedAssetType,
                    'min_stock' => 0,
                    'opening_quantity' => 0,
                    'opening_rate' => 0,
                    'current_quantity' => 0,
                    'current_value' => 0,
                    'is_active' => true,
                ]);
            }

            if ($item) {
                if ($item->asset_type !== 'fixed_asset' && $item->asset_type !== $requestedAssetType) {
                    $item->update(['asset_type' => $requestedAssetType]);
                }

                $row['store_item_id'] = $item->id;
                $row['item_name'] = $item->name;
                $row['store_category_id'] = $row['store_category_id'] ?? $item->store_category_id;
                $row['asset_type'] = $item->asset_type === 'fixed_asset' ? 'non_consumable' : $requestedAssetType;
                $row['specification'] = filled($row['specification'] ?? null) ? $row['specification'] : $item->specification;
                $row['unit'] = filled($row['unit'] ?? null) ? $row['unit'] : $item->unit?->symbol;
            }

            return $row;
        })->all();
    }

    private function updateRequisition(Request $request, StoreRequisition $requisition): void
    {
        $validated = $request->validate([
            'requested_by_name' => ['required', 'string', 'max:190'],
            'requested_by_designation' => ['nullable', 'string', 'max:190'],
            'purpose' => ['nullable', 'string', 'max:190'],
            'requested_at_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'approved_by_name' => ['nullable', 'string', 'max:190'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.store_item_id' => ['nullable', 'exists:store_items,id'],
            'items.*.store_category_id' => ['required', 'exists:store_categories,id'],
            'items.*.asset_type' => ['required', Rule::in(['consumable', 'non_consumable'])],
            'items.*.item_name' => ['required', 'string', 'max:190'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.unit' => ['required', 'string', 'max:40'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.remarks' => ['nullable', 'string', 'max:190'],
        ]);

        DB::transaction(function () use ($validated, $requisition) {
            $validated['items'] = $this->resolveRequisitionItems($validated['items']);
            $requisition->update([
                'requested_by_name' => $validated['requested_by_name'],
                'requested_by_designation' => $validated['requested_by_designation'] ?? null,
                'purpose' => $validated['purpose'] ?? null,
                'fiscal_year' => $this->fiscalYear(),
                'status' => $validated['approved_by_name'] ? 'approved' : 'draft',
                'requested_at_bs' => $validated['requested_at_bs'] ?? null,
                'approved_at_bs' => $validated['approved_by_name'] ? ($validated['requested_at_bs'] ?? null) : null,
                'approved_by_name' => $validated['approved_by_name'] ?? null,
            ]);
            $requisition->items()->delete();
            $requisition->items()->createMany($this->rows($validated['items']));
        });
    }

    private function updatePurchaseOrder(Request $request, StorePurchaseOrder $order): void
    {
        $validated = $request->validate([
            'store_supplier_id' => ['nullable', 'exists:store_suppliers,id'],
            'supplier_name' => ['required', 'string', 'max:190'],
            'supplier_address' => ['nullable', 'string', 'max:255'],
            'supplier_phone' => ['nullable', 'string', 'max:40'],
            'store_requisition_id' => ['nullable', 'exists:store_requisitions,id'],
            'decision_no' => ['nullable', 'string', 'max:80'],
            'decision_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'order_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'expected_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'tax_mode' => ['required', Rule::in(['pan', 'vat'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.store_item_id' => ['nullable', 'exists:store_items,id'],
            'items.*.store_category_id' => ['nullable', 'exists:store_categories,id'],
            'items.*.store_requisition_item_id' => ['nullable', 'exists:store_requisition_items,id'],
            'items.*.store_purchase_order_item_id' => ['nullable', 'exists:store_purchase_order_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:190'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.unit' => ['nullable', 'string', 'max:40'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string', 'max:190'],
        ]);

        $this->validatePurchaseOrderDates($validated);
        $this->syncPurchaseSupplierTaxMode($validated);
        $validated['items'] = $this->applyPurchaseTaxMode($validated['items'], $validated['tax_mode']);
        $this->syncPurchaseRequisitionHeader($validated);

        DB::transaction(function () use ($validated, $order) {
            $this->validatePurchaseAgainstRequisition($validated, $order->id);
            $order->update($validated + [
                'fiscal_year' => $this->fiscalYear(),
            ]);
            $order->items()->delete();
            $order->items()->createMany($this->rows($validated['items'], true, true));
        });
    }

    private function updateReceipt(Request $request, StoreReceipt $receipt): void
    {
        $validated = $request->validate([
            'store_purchase_order_id' => ['nullable', 'exists:store_purchase_orders,id'],
            'purchase_order_ids' => ['nullable', 'array'],
            'purchase_order_ids.*' => ['nullable', 'exists:store_purchase_orders,id'],
            'store_supplier_id' => ['nullable', 'exists:store_suppliers,id'],
            'received_from' => ['nullable', 'string', 'max:190'],
            'challan_no' => ['nullable', 'string', 'max:80'],
            'invoice_no' => ['nullable', 'string', 'max:80'],
            'invoice_date_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'received_at_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'received_by_name' => ['nullable', 'string', 'max:190'],
            'verified_by_name' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.store_item_id' => ['nullable', 'exists:store_items,id'],
            'items.*.store_category_id' => ['nullable', 'exists:store_categories,id'],
            'items.*.store_purchase_order_item_id' => ['nullable', 'exists:store_purchase_order_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:190'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.unit' => ['nullable', 'string', 'max:40'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['required', 'numeric', 'min:0.01'],
            'items.*.condition' => ['nullable', 'string', 'max:190'],
            'items.*.remarks' => ['nullable', 'string', 'max:190'],
        ], [], $this->lineValidationAttributes($request));

        $this->validateReceiptDates($validated);
        $this->syncReceiptPurchaseOrderHeader($validated);

        DB::transaction(function () use ($validated, $receipt) {
            $oldPurchaseOrderIds = $this->receiptRelatedPurchaseOrderIds($receipt);
            $this->reverseReceipt($receipt);
            $this->validateReceiptAgainstPurchaseOrder($validated, $receipt->id);
            $purchaseOrderIds = $this->receiptPurchaseOrderIds($validated);
            $receipt->update($validated + [
                'fiscal_year' => $this->fiscalYear(),
                'status' => 'posted',
            ]);
            $receipt->items()->delete();
            foreach ($this->receiptRows($validated['items']) as $row) {
                $receipt->items()->create($row);
                if (! empty($row['store_item_id'])) {
                    $item = StoreItem::lockForUpdate()->find($row['store_item_id']);
                    $this->postReceipt($item, (float) $row['quantity'], (float) $row['rate'], $receipt, (float) $row['amount']);
                }
            }
            $oldPurchaseOrderIds->merge($purchaseOrderIds)
                ->unique()
                ->each(fn ($purchaseOrderId) => $this->refreshPurchaseOrderStatus((int) $purchaseOrderId));
        });
    }

    private function updateIssue(Request $request, StoreIssue $issue): void
    {
        $validated = $request->validate([
            'store_requisition_id' => ['nullable', 'exists:store_requisitions,id'],
            'issued_to_name' => ['required', 'string', 'max:190'],
            'issued_to_designation' => ['nullable', 'string', 'max:190'],
            'purpose' => ['nullable', 'string', 'max:190'],
            'issued_at_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'approved_by_name' => ['nullable', 'string', 'max:190'],
            'store_keeper_name' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.store_item_id' => ['required', 'distinct', 'exists:store_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:190'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.unit' => ['nullable', 'string', 'max:40'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.remarks' => ['nullable', 'string', 'max:190'],
        ], [], $this->lineValidationAttributes($request));

        DB::transaction(function () use ($validated, $issue) {
            $this->reverseIssue($issue);
            $issue->update($validated + [
                'fiscal_year' => $this->fiscalYear(),
                'status' => 'posted',
            ]);
            $issue->items()->delete();
            foreach ($this->rows($validated['items']) as $row) {
                $item = StoreItem::lockForUpdate()->find($row['store_item_id']);
                $rate = $item->average_rate;
                $row['rate'] = $rate;
                $row['amount'] = round((float) $row['quantity'] * $rate, 2);
                $issue->items()->create($row);
                $this->postIssue($item, (float) $row['quantity'], $rate, $issue);
            }
        });
    }

    private function postReceipt(StoreItem $item, float $quantity, float $rate, StoreReceipt $receipt, ?float $amount = null): void
    {
        $amount = round($amount ?? ($quantity * $rate), 2);
        $movementRate = $quantity > 0 ? round($amount / $quantity, 2) : $rate;
        $item->current_quantity = round((float) $item->current_quantity + $quantity, 2);
        $item->current_value = round((float) $item->current_value + $amount, 2);
        $item->save();
        $this->movement($item, 'receipt', $quantity, 0, $movementRate, $receipt::class, $receipt->id, $receipt->receipt_no, $receipt->received_at_bs);
    }

    private function postIssue(StoreItem $item, float $quantity, float $rate, StoreIssue $issue): void
    {
        $isNonConsumable = in_array($item->asset_type, ['non_consumable', 'fixed_asset'], true);
        if ($quantity > (float) $item->current_quantity + 0.000001) {
            throw ValidationException::withMessages([
                'items' => "Not enough stock for {$item->name}. Available: ".number_format((float) $item->current_quantity, 2),
            ]);
        }

        $amount = round($quantity * $rate, 2);
        if ($isNonConsumable) {
            $activeIssued = StoreIssueItem::where('store_item_id', $item->id)
                ->whereColumn('returned_quantity', '<', 'quantity')
                ->sum(DB::raw('quantity - returned_quantity'));

            if ((float) $activeIssued > (float) $item->current_quantity + 0.000001) {
                throw ValidationException::withMessages([
                    'items' => "{$item->name} is already assigned to someone and not enough returned stock is available.",
                ]);
            }
        } else {
            $item->current_quantity = round(max((float) $item->current_quantity - $quantity, 0), 2);
            $item->current_value = round(max((float) $item->current_value - $amount, 0), 2);
            $item->save();
        }
        $this->movement($item, 'issue', 0, $isNonConsumable ? 0 : $quantity, $rate, $issue::class, $issue->id, $issue->issue_no, $issue->issued_at_bs);
    }

    private function movement(StoreItem $item, string $type, float $in, float $out, float $rate, ?string $sourceType, ?int $sourceId, ?string $remarks, ?string $movementDateBs = null): void
    {
        StoreStockMovement::create([
            'store_item_id' => $item->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'movement_type' => $type,
            'movement_date_bs' => $movementDateBs,
            'fiscal_year' => $this->fiscalYear(),
            'quantity_in' => $in,
            'quantity_out' => $out,
            'rate' => $rate,
            'amount' => round(($in ?: $out) * $rate, 2),
            'balance_quantity' => $item->current_quantity,
            'balance_value' => $item->current_value,
            'remarks' => $remarks,
            'created_by' => auth()->id(),
        ]);
    }

    private function reverseReceipt(StoreReceipt $receipt): void
    {
        $receipt->loadMissing('items');
        foreach ($receipt->items as $line) {
            if (! $line->store_item_id) {
                continue;
            }
            $item = StoreItem::lockForUpdate()->find($line->store_item_id);
            if (! $item) {
                continue;
            }
            if (in_array($item->asset_type, ['non_consumable', 'fixed_asset'], true)) {
                continue;
            }
            $amount = round((float) $line->quantity * (float) $line->rate, 2);
            $item->current_quantity = round(max((float) $item->current_quantity - (float) $line->quantity, 0), 2);
            $item->current_value = round(max((float) $item->current_value - $amount, 0), 2);
            $item->save();
        }
        StoreStockMovement::where('source_type', $receipt::class)->where('source_id', $receipt->id)->delete();
    }

    private function reverseIssue(StoreIssue $issue): void
    {
        $issue->loadMissing('items');
        foreach ($issue->items as $line) {
            if (! $line->store_item_id) {
                continue;
            }
            $item = StoreItem::lockForUpdate()->find($line->store_item_id);
            if (! $item) {
                continue;
            }
            $amount = round((float) $line->quantity * (float) $line->rate, 2);
            $item->current_quantity = round((float) $item->current_quantity + (float) $line->quantity, 2);
            $item->current_value = round((float) $item->current_value + $amount, 2);
            $item->save();
        }
        StoreStockMovement::where('source_type', $issue::class)->where('source_id', $issue->id)->delete();
    }

    private function document(string $type, int $id): StoreRequisition|StorePurchaseOrder|StoreReceipt|StoreIssue
    {
        return match ($type) {
            'requisition' => StoreRequisition::findOrFail($id),
            'purchase-order' => StorePurchaseOrder::findOrFail($id),
            'receipt' => StoreReceipt::findOrFail($id),
            'issue' => StoreIssue::findOrFail($id),
            default => abort(404),
        };
    }

    private function guardEditable(string $type, StoreRequisition|StorePurchaseOrder|StoreReceipt|StoreIssue $record): void
    {
        if ($type === 'requisition' && (
            StorePurchaseOrder::where('store_requisition_id', $record->id)->exists()
            || StorePurchaseOrderItem::whereIn('store_requisition_item_id', $record->items()->pluck('id'))->exists()
        )) {
            throw ValidationException::withMessages([
                'document' => 'This demand form is already used in a purchase order.',
            ]);
        }

        if ($type === 'requisition' && StoreIssue::where('store_requisition_id', $record->id)->exists()) {
            throw ValidationException::withMessages([
                'document' => 'This demand form is already used in an issue slip.',
            ]);
        }

        if ($type === 'purchase-order' && (
            StoreReceipt::where('store_purchase_order_id', $record->id)->exists()
            || StoreReceiptItem::whereIn('store_purchase_order_item_id', $record->items()->pluck('id'))->exists()
        )) {
            throw ValidationException::withMessages([
                'document' => 'This purchase order is already used in a dakhila report.',
            ]);
        }
    }

    private function documentTitle(string $type): string
    {
        return match ($type) {
            'requisition' => 'Demand Form',
            'purchase-order' => 'Purchase Order',
            'receipt' => 'Dakhila / Receipt',
            'issue' => 'Issue / Nikasa',
            default => 'Store Slip',
        };
    }

    private function syncReceiptPurchaseOrderHeader(array &$validated): void
    {
        $purchaseOrderIds = $this->receiptPurchaseOrderIds($validated);
        $validated['store_purchase_order_id'] = $purchaseOrderIds->count() === 1 ? $purchaseOrderIds->first() : null;
    }

    private function purchaseOrderPayload(StorePurchaseOrder $order): array
    {
        return [
            'id' => $order->id,
            'label' => $order->order_no.' - '.$order->supplier_name,
            'supplier_id' => $order->store_supplier_id,
            'supplier_name' => $order->supplier_name,
            'supplier_address' => $order->supplier_address,
            'supplier_phone' => $order->supplier_phone,
            'tax_mode' => $order->tax_mode ?: (((float) $order->items->max('tax_rate') > 0) ? 'vat' : 'pan'),
            'order_date_bs' => $order->order_date_bs,
            'meta' => collect([$order->order_date_bs, $order->supplier_phone])->filter()->implode(' · '),
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
            })->filter(fn ($item) => $item['quantity'] > 0)->values(),
        ];
    }

    private function receiptPurchaseOrderIds(array $validated): \Illuminate\Support\Collection
    {
        $selectedPurchaseOrderIds = collect($validated['purchase_order_ids'] ?? [])
            ->push($validated['store_purchase_order_id'] ?? null)
            ->filter()
            ->map(fn ($id) => (int) $id);

        $lineIds = collect($validated['items'] ?? [])
            ->pluck('store_purchase_order_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($lineIds->isNotEmpty()) {
            $selectedPurchaseOrderIds = $selectedPurchaseOrderIds->merge(
                StorePurchaseOrderItem::whereIn('id', $lineIds)->pluck('store_purchase_order_id')
            );
        }

        return $selectedPurchaseOrderIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function receiptRelatedPurchaseOrderIds(StoreReceipt $receipt): \Illuminate\Support\Collection
    {
        $purchaseOrderIds = collect([$receipt->store_purchase_order_id])->filter()->map(fn ($id) => (int) $id);
        $lineIds = $receipt->items()
            ->pluck('store_purchase_order_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($lineIds->isNotEmpty()) {
            $purchaseOrderIds = $purchaseOrderIds->merge(
                StorePurchaseOrderItem::whereIn('id', $lineIds)->pluck('store_purchase_order_id')
            );
        }

        return $purchaseOrderIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function validateReceiptAgainstPurchaseOrder(array $validated, ?int $ignoreReceiptId = null): void
    {
        $selectedPurchaseOrderIds = collect($validated['purchase_order_ids'] ?? [])
            ->push($validated['store_purchase_order_id'] ?? null)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $lineIds = collect($validated['items'])
            ->pluck('store_purchase_order_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($selectedPurchaseOrderIds->isEmpty() && $lineIds->isEmpty()) {
            return;
        }

        if ($lineIds->count() !== count($validated['items'])) {
            throw ValidationException::withMessages([
                'items' => 'Select a purchase order line for every Dakhila item when receiving against a purchase order.',
            ]);
        }

        if ($lineIds->unique()->count() !== $lineIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'The same purchase order item cannot be entered twice in one Dakhila.',
            ]);
        }

        $orderLines = StorePurchaseOrderItem::with('purchaseOrder')
            ->whereIn('id', $lineIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($orderLines->count() !== $lineIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'One or more Dakhila lines do not belong to a valid purchase order.',
            ]);
        }

        if ($selectedPurchaseOrderIds->isNotEmpty()) {
            $linePurchaseOrderIds = $orderLines->pluck('store_purchase_order_id')->map(fn ($id) => (int) $id)->unique()->values();
            if ($linePurchaseOrderIds->diff($selectedPurchaseOrderIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'One or more Dakhila lines do not belong to the selected purchase orders.',
                ]);
            }
        }

        $orderDate = $orderLines
            ->pluck('purchaseOrder.order_date_bs')
            ->filter()
            ->sort()
            ->last();

        if ($orderDate && ! empty($validated['invoice_date_bs']) && $validated['invoice_date_bs'] < $orderDate) {
            throw ValidationException::withMessages([
                'invoice_date_bs' => "Invoice date cannot be before purchase order date {$orderDate}.",
            ]);
        }

        if ($orderDate && ! empty($validated['received_at_bs']) && $validated['received_at_bs'] < $orderDate) {
            throw ValidationException::withMessages([
                'received_at_bs' => "Dakhila date cannot be before purchase order date {$orderDate}.",
            ]);
        }

        $incomingByOrderLine = collect($validated['items'])
            ->groupBy(fn ($row) => (int) ($row['store_purchase_order_item_id'] ?? 0))
            ->map(fn ($rows) => [
                'quantity' => $rows->sum(fn ($row) => (float) ($row['quantity'] ?? 0)),
                'rate' => $rows->max(fn ($row) => (float) ($row['rate'] ?? 0)),
            ]);

        foreach ($orderLines as $orderLine) {
            $incoming = $incomingByOrderLine->get((int) $orderLine->id);
            if (! $incoming) {
                continue;
            }

            $alreadyReceived = StoreReceiptItem::query()
                ->where('store_purchase_order_item_id', $orderLine->id)
                ->when($ignoreReceiptId, fn ($query) => $query->where('store_receipt_id', '!=', $ignoreReceiptId))
                ->sum('quantity');

            $totalAfterThisReceipt = (float) $alreadyReceived + (float) $incoming['quantity'];
            if ($totalAfterThisReceipt > (float) $orderLine->quantity + 0.000001) {
                throw ValidationException::withMessages([
                    'items' => "Received quantity for {$orderLine->item_name} cannot exceed ordered quantity.",
                ]);
            }

            if ((float) $incoming['rate'] > (float) $orderLine->rate + 0.000001) {
                throw ValidationException::withMessages([
                    'items' => "Received rate for {$orderLine->item_name} cannot exceed purchase order rate.",
                ]);
            }
        }
    }

    private function validatePurchaseOrderDates(array $validated): void
    {
        $decisionDate = $validated['decision_date_bs'] ?? null;
        $orderDate    = $validated['order_date_bs'] ?? null;
        $expectedDate = $validated['expected_date_bs'] ?? null;

        $errors = [];

        if ($decisionDate && $orderDate && $orderDate < $decisionDate) {
            $errors['order_date_bs'] = ['खरिद आदेश मिति (Order date) must be on or after Decision date (निर्णय मिति).'];
        }

        if ($expectedDate && $orderDate && $expectedDate < $orderDate) {
            $errors['expected_date_bs'] = ['अपेक्षित मिति (Expected date) must be on or after Order date (खरिद आदेश मिति).'];
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function validateReceiptDates(array $validated): void
    {
        $invoiceDate = $validated['invoice_date_bs'] ?? null;
        $receivedDate = $validated['received_at_bs'] ?? null;

        if ($invoiceDate && $receivedDate && $receivedDate < $invoiceDate) {
            throw ValidationException::withMessages([
                'received_at_bs' => ['दाखिला मिति (Dakhila date) must be on or after Invoice date.'],
            ]);
        }
    }

    private function validateFiscalYearDates(array $validated): void
    {
        $start = $validated['starts_on_bs'] ?? null;
        $end = $validated['ends_on_bs'] ?? null;

        if ($start && $end && $end < $start) {
            throw ValidationException::withMessages([
                'ends_on_bs' => ['Fiscal year end BS must be on or after start BS.'],
            ]);
        }
    }

    private function validatePurchaseAgainstRequisition(array $validated, ?int $ignoreOrderId = null): void
    {
        $lineIds = collect($validated['items'])
            ->pluck('store_requisition_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($lineIds->isEmpty()) {
            return;
        }

        if ($lineIds->unique()->count() !== $lineIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'The same demand form item cannot be entered twice in one purchase order.',
            ]);
        }

        if ($lineIds->count() !== count($validated['items'])) {
            throw ValidationException::withMessages([
                'items' => 'Manual lines are not allowed when purchase order is based on demand form lines. Use Direct Purchase for manual items.',
            ]);
        }

        $requisitionLines = StoreRequisitionItem::query()
            ->whereIn('id', $lineIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($lineIds as $lineId) {
            if (! $requisitionLines->has($lineId)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more purchase lines do not belong to a valid demand form.',
                ]);
            }
        }

        $incomingByLine = collect($validated['items'])
            ->groupBy(fn ($row) => (int) ($row['store_requisition_item_id'] ?? 0))
            ->map(fn ($rows) => $rows->sum(fn ($row) => (float) ($row['quantity'] ?? 0)));

        foreach ($requisitionLines as $requisitionLine) {
            $incoming = (float) ($incomingByLine->get((int) $requisitionLine->id) ?? 0);
            if ($incoming <= 0) {
                continue;
            }

            $alreadyOrdered = StorePurchaseOrderItem::query()
                ->where('store_requisition_item_id', $requisitionLine->id)
                ->when($ignoreOrderId, fn ($query) => $query->where('store_purchase_order_id', '!=', $ignoreOrderId))
                ->sum('quantity');

            if ((float) $alreadyOrdered + $incoming > (float) $requisitionLine->quantity + 0.000001) {
                throw ValidationException::withMessages([
                    'items' => "Purchase quantity for {$requisitionLine->item_name} cannot exceed demand form quantity.",
                ]);
            }
        }
    }

    private function syncPurchaseRequisitionHeader(array &$validated): void
    {
        $lineIds = collect($validated['items'] ?? [])
            ->pluck('store_requisition_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($lineIds->isEmpty()) {
            $validated['store_requisition_id'] = null;
            return;
        }

        $requisitionIds = StoreRequisitionItem::whereIn('id', $lineIds)
            ->pluck('store_requisition_id')
            ->unique()
            ->values();

        $validated['store_requisition_id'] = $requisitionIds->count() === 1 ? $requisitionIds->first() : null;
    }

    private function refreshPurchaseOrderStatus(int $purchaseOrderId): void
    {
        $order = StorePurchaseOrder::with('items.receivedItems')->find($purchaseOrderId);
        if (! $order) {
            return;
        }

        $allReceived = $order->items->isNotEmpty()
            && $order->items->every(fn ($item) => (float) $item->received_quantity >= (float) $item->quantity);

        $order->status = $allReceived ? 'received' : 'ordered';
        $order->save();
    }

    private function recordData(string $type, StoreRequisition|StorePurchaseOrder|StoreReceipt|StoreIssue $record): array
    {
        return match ($type) {
            'requisition' => [
                'requested_by_name' => $record->requested_by_name,
                'requested_by_designation' => $record->requested_by_designation,
                'purpose' => $record->purpose,
                'requested_at_bs' => $record->requested_at_bs,
                'approved_by_name' => $record->approved_by_name,
            ],
            'purchase-order' => [
                'store_requisition_id' => $record->store_requisition_id,
                'store_supplier_id' => $record->store_supplier_id,
                'supplier_name' => $record->supplier_name,
                'supplier_address' => $record->supplier_address,
                'supplier_phone' => $record->supplier_phone,
                'decision_no' => $record->decision_no,
                'decision_date_bs' => $record->decision_date_bs,
                'order_date_bs' => $record->order_date_bs,
                'expected_date_bs' => $record->expected_date_bs,
                'tax_mode' => $record->tax_mode ?: (((float) $record->items->max('tax_rate') > 0) ? 'vat' : 'pan'),
            ],
            'receipt' => [
                'store_purchase_order_id' => $record->store_purchase_order_id,
                'store_supplier_id' => $record->store_supplier_id,
                'received_from' => $record->received_from,
                'challan_no' => $record->challan_no,
                'invoice_no' => $record->invoice_no,
                'invoice_date_bs' => $record->invoice_date_bs,
                'received_at_bs' => $record->received_at_bs,
                'received_by_name' => $record->received_by_name,
                'verified_by_name' => $record->verified_by_name,
            ],
            'issue' => [
                'issued_to_name' => $record->issued_to_name,
                'issued_to_designation' => $record->issued_to_designation,
                'purpose' => $record->purpose,
                'issued_at_bs' => $record->issued_at_bs,
                'store_keeper_name' => $record->store_keeper_name,
                'approved_by_name' => $record->approved_by_name,
            ],
            default => [],
        };
    }

    private function nextCode(string $prefix, string $modelClass, string $column): string
    {
        $base = $prefix.'-'.now()->format('Ym').'-';
        $last = $modelClass::where($column, 'like', $base.'%')
            ->lockForUpdate()
            ->orderByDesc($column)
            ->value($column);
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $base.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function nextRequisitionCode(): string
    {
        $lastSequence = StoreRequisition::lockForUpdate()
            ->pluck('requisition_no')
            ->map(function ($number) {
                preg_match('/(\d+)$/', (string) $number, $matches);

                return (int) ($matches[1] ?? 0);
            })
            ->max() ?? 0;

        return 'REQ-'.str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    private function fiscalYear(): string
    {
        return $this->activeFiscalYear()->name;
    }

    private function activeFiscalYear(): StoreFiscalYear
    {
        $active = StoreFiscalYear::where('is_active', true)->latest()->first();
        if ($active) {
            return $active;
        }

        return StoreFiscalYear::create([
            'name' => '2082/83',
            'starts_on_bs' => '2082-04-01',
            'ends_on_bs' => '2083-03-32',
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);
    }

    private function storeKeeperName(): string
    {
        return (string) (User::whereHas('roles', fn ($query) => $query->where('name', 'store-keeper'))
            ->orderBy('name')
            ->value('name') ?: '');
    }
}
