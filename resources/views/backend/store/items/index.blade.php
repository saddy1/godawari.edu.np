@extends('store.layouts.app')

@section('title', 'Store Items')

@section('content')
@php
    $edit = $editItem;
    $action = $edit ? route('admin.store.items.update', $edit) : route('admin.store.items.store');
@endphp

<div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-gray-400">Store Master</p>
                <h1 class="mt-1 text-3xl font-black text-gray-950">Items</h1>
                <p class="mt-1 text-sm font-semibold text-gray-500">Maintain reusable item classes. Quantity, rate, and detailed specification are entered in demand, purchase, and dakhila forms.</p>
            </div>
            @if($edit)
                <a href="{{ route('admin.store.items.index') }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-black text-gray-700 hover:bg-gray-50">Cancel Edit</a>
            @endif
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-extrabold text-green-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-extrabold text-red-800">{{ $errors->first() }}</div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[26rem_1fr]">
            <form method="POST" action="{{ $action }}" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                @csrf
                @if($edit)
                    @method('PATCH')
                @endif
                <h2 class="text-xl font-black text-gray-950">{{ $edit ? 'Edit Item' : 'Add Item' }}</h2>
                <div class="mt-4 space-y-3">
                    <input name="name" required value="{{ old('name', $edit->name ?? '') }}" placeholder="Item name" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                    <input name="item_code" value="{{ old('item_code', $edit->item_code ?? '') }}" placeholder="Code (auto if blank)" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <select name="store_category_id" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                            <option value="">Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('store_category_id', $edit->store_category_id ?? '') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <select name="asset_type" required class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                            <option value="consumable" @selected(old('asset_type', $edit->asset_type ?? 'consumable') === 'consumable')>Consumable</option>
                            <option value="non_consumable" @selected(old('asset_type', $edit->asset_type ?? '') === 'non_consumable')>Non-consumable</option>
                            <option value="fixed_asset" @selected(old('asset_type', $edit->asset_type ?? '') === 'fixed_asset')>Fixed asset</option>
                        </select>
                        <select name="store_brand_id" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                            <option value="">Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('store_brand_id', $edit->store_brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        <select name="store_unit_id" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                            <option value="">Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('store_unit_id', $edit->store_unit_id ?? '') == $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>
                            @endforeach
                        </select>
                        <input name="min_stock" type="number" step="0.01" min="0" value="{{ old('min_stock', $edit->min_stock ?? '') }}" placeholder="Reorder level (optional)" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <input name="storage_location" value="{{ old('storage_location', $edit->storage_location ?? '') }}" placeholder="Default location (optional)" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                    </div>
                    <p class="rounded-xl bg-gray-50 px-4 py-3 text-xs font-bold leading-5 text-gray-500">Item master is only for classification. Actual quantity, rate, model/serial no., and specification are recorded when creating माग फाराम, खरिद आदेश, or दाखिला.</p>
                    @if($edit)
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-600"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $edit->is_active)) class="rounded border-gray-300"> Active item</label>
                    @endif
                    <button class="w-full rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-black text-white hover:bg-[#0b2415]">{{ $edit ? 'Update Item' : 'Save Item' }}</button>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <form method="GET" class="grid gap-3 border-b border-gray-100 bg-gray-50 p-4 sm:grid-cols-[1fr_auto]">
                    <input name="search" value="{{ request('search') }}" placeholder="Search items" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                    <button class="rounded-xl border border-gray-200 px-5 py-3 text-sm font-black text-gray-700 hover:bg-white">Search</button>
                </form>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-black uppercase tracking-widest text-gray-500">
                            <tr>
                                <th class="px-5 py-4">Item</th>
                                <th class="px-5 py-4">Master</th>
                                <th class="px-5 py-4 text-right">Stock</th>
                                <th class="px-5 py-4">Usage</th>
                                <th class="px-5 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($items as $item)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-5 py-4">
                                        <p class="font-black text-gray-950">{{ $item->name }}</p>
                                        <p class="text-xs font-semibold text-gray-400">{{ $item->item_code }} · {{ ucfirst(str_replace('_', ' ', $item->asset_type)) }}</p>
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-gray-600">
                                        {{ $item->category?->name ?: 'No category' }} @if($item->brand) · {{ $item->brand->name }} @endif
                                    </td>
                                    <td class="px-5 py-4 text-right font-black {{ (float) $item->min_stock > 0 && (float) $item->current_quantity <= (float) $item->min_stock ? 'text-red-600' : 'text-gray-950' }}">
                                        {{ number_format((float) $item->current_quantity, 2) }} {{ $item->unit?->symbol }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full {{ $item->movements_count ? 'bg-amber-50 text-amber-700' : 'bg-green-50 text-green-700' }} px-3 py-1 text-xs font-black">{{ $item->movements_count }} movements</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.store.items.edit', $item) }}" class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-50">Edit</a>
                                            <form method="POST" action="{{ route('admin.store.items.destroy', $item) }}" onsubmit="return confirm('Delete this item?');">
                                                @csrf
                                                @method('DELETE')
                                                <button @disabled($item->movements_count > 0) class="rounded-lg border px-3 py-2 text-xs font-black {{ $item->movements_count > 0 ? 'cursor-not-allowed border-gray-200 text-gray-300' : 'border-red-200 text-red-600 hover:bg-red-50' }}">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-12 text-center font-bold text-gray-400">No items found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($items->hasPages())
                    <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">{{ $items->links() }}</div>
                @endif
            </div>
        </div>
</div>
@endsection
