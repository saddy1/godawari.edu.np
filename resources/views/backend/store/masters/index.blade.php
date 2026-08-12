@extends('store.layouts.app')

@section('title', $title)

@section('content')
@php
    $edit = $editRecord;
    $action = $edit
        ? route("admin.store.$type.update", $edit)
        : route("admin.store.$type.store");
@endphp

<div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-gray-400">Store Master</p>
                <h1 class="mt-1 text-3xl font-black text-gray-950">{{ $title }}</h1>
                <p class="mt-1 text-sm font-semibold text-gray-500">Create, edit, and protect master data used by stock records.</p>
            </div>
            @if($edit)
                <a href="{{ route("admin.store.$type.index") }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-black text-gray-700 hover:bg-gray-50">Cancel Edit</a>
            @endif
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-extrabold text-green-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-extrabold text-red-800">{{ $errors->first() }}</div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[24rem_1fr]">
            <form method="POST" action="{{ $action }}" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                @csrf
                @if($edit)
                    @method('PATCH')
                @endif
                <h2 class="text-xl font-black text-gray-950">{{ $edit ? 'Edit' : 'Add' }} {{ Str::singular($title) }}</h2>
                <div class="mt-4 space-y-3">
                    @if($type === 'suppliers')
                        <input name="name" required value="{{ old('name', $edit->name ?? '') }}" placeholder="Supplier name" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <input name="contact_person" value="{{ old('contact_person', $edit->contact_person ?? '') }}" placeholder="Contact person" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <input name="phone" value="{{ old('phone', $edit->phone ?? '') }}" placeholder="Phone" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <input name="email" value="{{ old('email', $edit->email ?? '') }}" placeholder="Email" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <select name="tax_registration_type" required class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                            <option value="pan" @selected(old('tax_registration_type', $edit->tax_registration_type ?? 'pan') === 'pan')>Registered in PAN</option>
                            <option value="vat" @selected(old('tax_registration_type', $edit->tax_registration_type ?? 'pan') === 'vat')>Registered in VAT</option>
                        </select>
                        <input name="pan_vat_no" value="{{ old('pan_vat_no', $edit->pan_vat_no ?? '') }}" placeholder="PAN/VAT number" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <input name="registration_no" value="{{ old('registration_no', $edit->registration_no ?? '') }}" placeholder="Registration no." class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <input name="address" value="{{ old('address', $edit->address ?? '') }}" placeholder="Address" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#1a5632]">{{ old('notes', $edit->notes ?? '') }}</textarea>
                    @elseif($type === 'categories')
                        <input name="name" required value="{{ old('name', $edit->name ?? '') }}" placeholder="Category name" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <input name="code" value="{{ old('code', $edit->code ?? '') }}" placeholder="Code" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <select name="parent_id" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                            <option value="">No parent</option>
                            @foreach(($categories ?? collect()) as $category)
                                <option value="{{ $category->id }}" @selected(old('parent_id', $edit->parent_id ?? '') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <textarea name="description" rows="3" placeholder="Description" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#1a5632]">{{ old('description', $edit->description ?? '') }}</textarea>
                    @elseif($type === 'brands')
                        <input name="name" required value="{{ old('name', $edit->name ?? '') }}" placeholder="Brand name" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <input name="country" value="{{ old('country', $edit->country ?? '') }}" placeholder="Country" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#1a5632]">{{ old('notes', $edit->notes ?? '') }}</textarea>
                    @elseif($type === 'units')
                        <input name="name" required value="{{ old('name', $edit->name ?? '') }}" placeholder="Unit name" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <input name="symbol" required value="{{ old('symbol', $edit->symbol ?? '') }}" placeholder="Symbol" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-600"><input type="checkbox" name="allow_decimal" value="1" @checked(old('allow_decimal', $edit->allow_decimal ?? false)) class="rounded border-gray-300"> Allow decimal</label>
                    @endif
                    <button class="w-full rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-black text-white hover:bg-[#0b2415]">{{ $edit ? 'Update' : 'Save' }}</button>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <form method="GET" class="grid gap-3 border-b border-gray-100 bg-gray-50 p-4 sm:grid-cols-[1fr_auto]">
                    <input name="search" value="{{ request('search') }}" placeholder="Search {{ strtolower($title) }}" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                    <button class="rounded-xl border border-gray-200 px-5 py-3 text-sm font-black text-gray-700 hover:bg-white">Search</button>
                </form>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-black uppercase tracking-widest text-gray-500">
                            <tr>
                                <th class="px-5 py-4">Name</th>
                                <th class="px-5 py-4">Detail</th>
                                <th class="px-5 py-4">Usage</th>
                                <th class="px-5 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($records as $record)
                                @php
                                    $usage = match($type) {
                                        'suppliers' => ($record->purchase_orders_count ?? 0) + ($record->receipts_count ?? 0),
                                        'categories' => ($record->items_count ?? 0) + ($record->children_count ?? 0),
                                        default => $record->items_count ?? 0,
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-5 py-4">
                                        <p class="font-black text-gray-950">{{ $record->name }}</p>
                                        <p class="text-xs font-semibold text-gray-400">Created {{ $record->created_at?->format('Y-m-d') }}</p>
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-gray-600">
                                        @if($type === 'suppliers')
                                            {{ $record->phone ?: 'No phone' }} · {{ strtoupper($record->tax_registration_type ?? 'pan') }} @if($record->pan_vat_no) {{ $record->pan_vat_no }} @endif
                                        @elseif($type === 'categories')
                                            {{ $record->code ?: 'No code' }} @if($record->parent) · Parent: {{ $record->parent->name }} @endif
                                        @elseif($type === 'brands')
                                            {{ $record->country ?: 'No country' }}
                                        @else
                                            {{ $record->symbol }} · {{ $record->allow_decimal ? 'Decimal allowed' : 'Whole number' }}
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full {{ $usage ? 'bg-amber-50 text-amber-700' : 'bg-green-50 text-green-700' }} px-3 py-1 text-xs font-black">
                                            {{ $usage }} linked
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route("admin.store.$type.edit", $record) }}" class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-50">Edit</a>
                                            <form method="POST" action="{{ route("admin.store.$type.destroy", $record) }}" onsubmit="return confirm('Delete this record?');">
                                                @csrf
                                                @method('DELETE')
                                                <button @disabled($usage > 0) class="rounded-lg border px-3 py-2 text-xs font-black {{ $usage > 0 ? 'cursor-not-allowed border-gray-200 text-gray-300' : 'border-red-200 text-red-600 hover:bg-red-50' }}">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-12 text-center font-bold text-gray-400">No records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($records->hasPages())
                    <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">{{ $records->links() }}</div>
                @endif
            </div>
        </div>
</div>
@endsection
