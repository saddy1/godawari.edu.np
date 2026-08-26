@extends('billing.layouts.app')
@section('title', 'Payroll & Payslips')
@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Salary / Pay Slip</p>
            <h1 class="mt-1 text-2xl font-extrabold text-gray-950">Payroll & Payslips</h1>
            <p class="mt-1 text-sm text-gray-500">Monthly BS payroll imports and employee payslips.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.billing.index') }}" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-extrabold text-gray-700">Bills</a>
            @can('billing.create')<a href="{{ route('admin.billing.payroll.template') }}" class="rounded-xl border border-[#1a5632] px-4 py-3 text-sm font-extrabold text-[#1a5632]">Excel Template</a><a href="{{ route('admin.billing.payroll.upload') }}" class="rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white">Upload Payroll</a>@endcan
        </div>
    </div>
    @if(session('success'))<div class="rounded-xl border border-green-200 bg-green-50 px-5 py-3 text-sm font-bold text-green-800">{{ session('success') }}</div>@endif
    <form method="GET" class="flex gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <input name="year" type="number" min="2070" max="2100" value="{{ request('year') }}" placeholder="BS year" class="w-44 rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold">
        <button class="rounded-xl border border-gray-200 px-5 py-3 text-sm font-extrabold text-gray-700">Filter</button>
    </form>
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="w-full text-left">
            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-widest text-gray-500"><tr><th class="px-5 py-4">Period</th><th class="px-5 py-4">Upload</th><th class="px-5 py-4 text-right">Employees</th><th class="px-5 py-4 text-right">Net Payroll</th><th class="px-5 py-4 text-right">Action</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($batches as $batch)
                <tr><td class="px-5 py-4"><p class="font-extrabold text-gray-950">{{ $batch->period_label }}</p><p class="text-xs text-gray-400">Revision {{ $batch->revision }}</p></td>
                    <td class="px-5 py-4"><p class="text-sm font-bold text-gray-700">{{ $batch->original_filename }}</p><p class="text-xs text-gray-400">{{ $batch->uploaded_at?->format('M d, Y h:i A') }} by {{ $batch->uploader?->name ?? 'System' }}</p></td>
                    <td class="px-5 py-4 text-right font-bold">{{ $batch->payslips_count }}</td><td class="px-5 py-4 text-right font-black">Rs. {{ number_format($batch->total_net_amount, 2) }}</td>
                    <td class="px-5 py-4 text-right"><a href="{{ route('admin.billing.payroll.batch', $batch) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-extrabold text-gray-700">View</a></td></tr>
            @empty<tr><td colspan="5" class="px-5 py-14 text-center"><p class="font-extrabold text-gray-900">No payroll uploaded yet</p><p class="mt-1 text-sm text-gray-500">Choose a BS month and upload the salary sheet.</p></td></tr>@endforelse
            </tbody>
        </table></div>
        @if($batches->hasPages())<div class="border-t bg-gray-50 px-4 py-3">{{ $batches->links() }}</div>@endif
    </div>
</div>
@endsection
