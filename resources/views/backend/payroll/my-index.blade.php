@extends('billing.layouts.app')
@section('title', 'My Payslips')
@section('content')
<div class="mx-auto max-w-5xl space-y-6"><div><p class="text-xs font-bold uppercase tracking-widest text-gray-400">Employee self-service</p><h1 class="mt-1 text-2xl font-extrabold">My Payslips</h1><p class="mt-1 text-sm text-gray-500">View and print your salary slips for each BS month.</p></div>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@forelse($payslips as $payslip)<a href="{{ route('payroll.mine.show', $payslip) }}" class="group rounded-2xl border border-gray-100 bg-white p-5 shadow-sm hover:border-green-300"><p class="text-xs font-bold uppercase text-gray-400">Salary slip</p><h2 class="mt-2 text-lg font-black group-hover:text-[#1a5632]">{{ $payslip->batch->period_label }}</h2><p class="mt-4 text-sm text-gray-500">Net receivable</p><p class="text-xl font-black text-[#1a5632]">Rs. {{ number_format($payslip->net_amount,2) }}</p><p class="mt-4 text-xs font-bold text-gray-400">View / Print →</p></a>@empty<div class="sm:col-span-2 lg:col-span-3 rounded-2xl border bg-white py-16 text-center"><p class="font-extrabold">No payslips available yet</p><p class="mt-1 text-sm text-gray-500">Your payslip will appear after Accounts uploads the monthly payroll.</p></div>@endforelse</div>
@if($payslips->hasPages()){{ $payslips->links() }}@endif</div>
@endsection
