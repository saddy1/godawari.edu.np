@extends('billing.layouts.app')
@section('title', 'Upload Payroll')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-widest text-gray-400">Payroll</p><h1 class="mt-1 text-2xl font-extrabold">Upload monthly salary sheet</h1></div><div class="flex flex-wrap gap-2"><a href="{{ route('admin.billing.payroll.template') }}" class="rounded-xl border border-[#1a5632] bg-green-50 px-4 py-2.5 text-sm font-extrabold text-[#1a5632]">Download Excel Template</a><a href="{{ route('admin.billing.payroll.index') }}" class="rounded-xl border px-4 py-2.5 text-sm font-extrabold text-gray-700">Back</a></div></div>
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.billing.payroll.preview') }}" class="space-y-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="mb-2 block text-sm font-extrabold text-gray-700">Month (BS)</label><select name="bs_month" required class="w-full rounded-xl border border-gray-200 px-4 py-3 font-semibold"><option value="">Choose month</option>@foreach($months as $number => $month)<option value="{{ $number }}" @selected((int)old('bs_month') === $number)>{{ $month }}</option>@endforeach</select></div>
            <div><label class="mb-2 block text-sm font-extrabold text-gray-700">Year (BS)</label><select name="bs_year" required class="w-full rounded-xl border border-gray-200 px-4 py-3 font-semibold"><option value="">Choose year</option>@foreach($years as $year)<option value="{{ $year }}" @selected((int)old('bs_year', 2083) === $year)>{{ $year }}</option>@endforeach</select></div>
        </div>
        <div><label class="mb-2 block text-sm font-extrabold text-gray-700">Excel salary sheet</label><input type="file" name="payroll_file" required accept=".xlsx,.xls,.csv" class="block w-full rounded-xl border border-dashed border-gray-300 bg-gray-50 p-5 text-sm"><p class="mt-2 text-xs text-gray-500">Excel/CSV, maximum 10 MB. The file is not saved to payroll until you verify the preview.</p></div>
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm font-extrabold text-blue-900">How matching works</p><p class="mt-1 text-sm text-blue-800">The <b>Staff ID</b> column must equal the employee’s unique Hajiri Device ID in HR. Required headings: Staff ID, Current Salary and Net Amount. Your other headings from the supplied sheet are recognized automatically.</p></div><a href="{{ route('admin.billing.payroll.template') }}" class="shrink-0 rounded-lg bg-white px-4 py-2 text-center text-xs font-extrabold text-blue-800 shadow-sm ring-1 ring-blue-200">Get blank template</a></div></div>
        <button class="w-full rounded-xl bg-[#1a5632] px-5 py-3.5 text-sm font-extrabold text-white">Verify & Preview</button>
    </form>
</div>
@endsection
