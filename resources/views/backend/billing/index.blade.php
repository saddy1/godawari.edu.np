@extends('layouts.admin')

@section('title', 'Billing')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Accounts</p>
            <h1 class="mt-1 text-2xl font-extrabold text-gray-950">Billing</h1>
            <p class="mt-1 text-sm text-gray-500">Create cash receipts and payment vouchers with printable school bills.</p>
        </div>
        @can('billing.create')
        <a href="{{ route('admin.billing.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-[#0b2415]">
            + New Bill
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-3 text-sm font-bold text-green-800">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Received</p>
            <p class="mt-2 text-2xl font-black text-[#1a5632]">Rs. {{ number_format($summary['received'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Paid</p>
            <p class="mt-2 text-2xl font-black text-amber-600">Rs. {{ number_format($summary['paid'], 2) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Bills</p>
            <p class="mt-2 text-2xl font-black text-gray-950">{{ number_format($summary['count']) }}</p>
        </div>
    </div>

    <form method="GET" class="grid gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm md:grid-cols-[1fr_12rem_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Search bill no, name, purpose" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#1a5632]">
        <select name="type" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#1a5632]">
            <option value="">All types</option>
            <option value="receipt" @selected(request('type') === 'receipt')>Receipts</option>
            <option value="payment" @selected(request('type') === 'payment')>Payments</option>
        </select>
        <button class="rounded-xl border border-gray-200 px-5 py-3 text-sm font-extrabold text-gray-700 hover:bg-gray-50">Filter</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-xs font-bold uppercase tracking-widest text-gray-500">
                    <tr>
                        <th class="px-5 py-4">Bill</th>
                        <th class="px-5 py-4">Person</th>
                        <th class="px-5 py-4">Purpose</th>
                        <th class="px-5 py-4">Type</th>
                        <th class="px-5 py-4 text-right">Amount</th>
                        <th class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($bills as $bill)
                    <tr class="hover:bg-gray-50/70">
                        <td class="px-5 py-4">
                            <p class="font-extrabold text-gray-950">{{ $bill->bill_no }}</p>
                            <p class="text-xs text-gray-400">{{ optional($bill->issued_at)->format('M d, Y h:i A') }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-gray-900">{{ $bill->party_name }}</p>
                            <p class="text-xs text-gray-400">{{ $bill->party_identifier ?: 'Custom / no ID' }}</p>
                        </td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-600">{{ $bill->purpose }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-extrabold {{ $bill->type === 'payment' ? 'bg-amber-50 text-amber-700' : 'bg-green-50 text-green-700' }}">
                                {{ $bill->type_label }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right font-black text-gray-950">Rs. {{ number_format($bill->total, 2) }}</td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.billing.show', $bill) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-extrabold text-gray-700 hover:bg-gray-50">View</a>
                                @can('billing.delete')
                                <form method="POST" action="{{ route('admin.billing.destroy', $bill) }}" onsubmit="return confirm('Delete this bill?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-extrabold text-red-600 hover:bg-red-50">Delete</button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14 text-center">
                            <p class="text-lg font-extrabold text-gray-900">No bills yet</p>
                            <p class="mt-1 text-sm text-gray-500">Create the first receipt or payment voucher.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($bills->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">{{ $bills->links() }}</div>
        @endif
    </div>
</div>
@endsection
