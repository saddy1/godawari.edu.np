@extends('layouts.admin')

@section('title', $bill->bill_no)

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div class="no-print flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Billing</p>
            <h1 class="mt-1 text-2xl font-extrabold text-gray-950">{{ $bill->bill_no }}</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.billing.index') }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-extrabold text-gray-700 hover:bg-gray-50">Back</a>
            <button onclick="window.print()" class="rounded-xl bg-[#1a5632] px-5 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415]">Print Bill</button>
        </div>
    </div>

    @if(session('success'))
        <div class="no-print rounded-xl border border-green-200 bg-green-50 px-5 py-3 text-sm font-bold text-green-800">{{ session('success') }}</div>
    @endif

    <article class="bill-page bg-white p-8 shadow-sm ring-1 ring-gray-200 print:shadow-none print:ring-0">
        <header class="bill-header flex items-start justify-between gap-6 border-b-2 border-gray-900 pb-5">
            <div class="bill-brand flex items-center gap-4">
                <img src="{{ $siteSettings->logoUrl() }}" alt="School logo" class="bill-logo h-20 w-20 object-contain">
                <div class="bill-school-copy">
                    <h2 class="text-2xl font-black uppercase tracking-wide text-gray-950">{{ $siteSettings->localized('site_name', config('app.name')) }}</h2>
                    <p class="mt-1 text-sm font-semibold text-gray-600">{{ $siteSettings->localized('site_address', '') }}</p>
                    <p class="text-sm font-semibold text-gray-600">{{ $siteSettings->get('school_email', '') }}</p>
                </div>
            </div>
            <div class="bill-meta text-right">
                <p class="text-xs font-black uppercase tracking-[.2em] text-gray-400">{{ $bill->type_label }}</p>
                <p class="mt-2 text-xl font-black text-gray-950">{{ $bill->bill_no }}</p>
                <p class="mt-1 text-sm font-semibold text-gray-600">{{ optional($bill->issued_at)->format('F d, Y h:i A') }}</p>
            </div>
        </header>

        <section class="bill-party-grid mt-6 grid gap-4 md:grid-cols-2">
            <div class="bill-info-card rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-black uppercase tracking-widest text-gray-400">{{ $bill->type === 'payment' ? 'Paid To' : 'Received From' }}</p>
                <h3 class="mt-2 text-lg font-black text-gray-950">{{ $bill->party_name }}</h3>
                <div class="mt-2 space-y-1 text-sm font-semibold text-gray-600">
                    @if($bill->party_identifier)<p>ID: {{ $bill->party_identifier }}</p>@endif
                    @if($bill->party_phone)<p>Phone: {{ $bill->party_phone }}</p>@endif
                    @if($bill->party_email)<p>Email: {{ $bill->party_email }}</p>@endif
                    @if($bill->party_address)<p>Address: {{ $bill->party_address }}</p>@endif
                </div>
            </div>
            <div class="bill-info-card rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-black uppercase tracking-widest text-gray-400">Purpose</p>
                <h3 class="mt-2 text-lg font-black text-gray-950">{{ $bill->purpose }}</h3>
                <p class="mt-2 text-sm font-semibold capitalize text-gray-600">Payment method: {{ $bill->payment_method }}</p>
                @if($bill->reference_no)
                    <p class="mt-1 text-sm font-semibold text-gray-600">Reference: {{ $bill->reference_no }}</p>
                @endif
            </div>
        </section>

        <section class="bill-items-wrap mt-6 overflow-hidden rounded-xl border border-gray-300">
            <table class="bill-items-table w-full border-collapse text-left">
                <thead class="bg-gray-100 text-xs font-black uppercase tracking-widest text-gray-600">
                    <tr>
                        <th class="border-b border-gray-300 px-4 py-3">S.N</th>
                        <th class="border-b border-gray-300 px-4 py-3">Particulars</th>
                        <th class="border-b border-gray-300 px-4 py-3 text-right">Qty</th>
                        <th class="border-b border-gray-300 px-4 py-3 text-right">Rate</th>
                        <th class="border-b border-gray-300 px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bill->items as $item)
                    <tr>
                        <td class="border-b border-gray-200 px-4 py-3 font-semibold">{{ $loop->iteration }}</td>
                        <td class="border-b border-gray-200 px-4 py-3 font-semibold">{{ $item->description }}</td>
                        <td class="border-b border-gray-200 px-4 py-3 text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="border-b border-gray-200 px-4 py-3 text-right">{{ number_format($item->rate, 2) }}</td>
                        <td class="border-b border-gray-200 px-4 py-3 text-right font-bold">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="bill-summary-grid mt-6 grid gap-6 md:grid-cols-[1fr_20rem]">
            <div class="bill-words">
                <p class="text-xs font-black uppercase tracking-widest text-gray-400">Amount in words</p>
                <p class="mt-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-black text-gray-950">{{ $bill->amount_words }}</p>
                @if($bill->notes)
                    <p class="mt-4 text-sm font-semibold text-gray-600">{{ $bill->notes }}</p>
                @endif
            </div>
            <div class="bill-total-card rounded-xl border border-gray-200 p-4">
                <div class="flex justify-between py-1 text-sm"><span>Subtotal</span><strong>Rs. {{ number_format($bill->subtotal, 2) }}</strong></div>
                <div class="flex justify-between py-1 text-sm"><span>Discount</span><strong>Rs. {{ number_format($bill->discount, 2) }}</strong></div>
                <div class="flex justify-between py-1 text-sm"><span>Tax / Extra</span><strong>Rs. {{ number_format($bill->tax, 2) }}</strong></div>
                <div class="mt-3 flex justify-between border-t border-gray-200 pt-3 text-lg font-black"><span>Total</span><strong>Rs. {{ number_format($bill->total, 2) }}</strong></div>
            </div>
        </section>

        <footer class="bill-signatures mt-12 grid gap-8 md:grid-cols-3">
            <div>
                <div class="h-12 border-b border-gray-900"></div>
                <p class="mt-2 text-center text-xs font-black uppercase tracking-widest text-gray-500">Prepared By</p>
                <p class="text-center text-sm font-bold text-gray-700">{{ $bill->creator?->name ?? 'Admin' }}</p>
            </div>
            <div>
                <div class="h-12 border-b border-gray-900"></div>
                <p class="mt-2 text-center text-xs font-black uppercase tracking-widest text-gray-500">{{ $bill->type === 'payment' ? 'Receiver Signature' : 'Payer Signature' }}</p>
            </div>
            <div>
                <div class="h-12 border-b border-gray-900"></div>
                <p class="mt-2 text-center text-xs font-black uppercase tracking-widest text-gray-500">Authorized Signature</p>
            </div>
        </footer>
    </article>
</div>

<style>
@media print {
    @page { size: A4; margin: 10mm; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    html, body { width: 210mm; background: #fff !important; }
    body { margin: 0 !important; color: #111827 !important; }
    aside,
    .no-print,
    [data-page-scroll-root] > header,
    [data-page-scroll-root] > div:not(main),
    [data-page-scroll-root] > main + * { display: none !important; }
    [data-page-scroll-root],
    main,
    main > div { display: block !important; width: 100% !important; max-width: none !important; overflow: visible !important; padding: 0 !important; margin: 0 !important; }
    .bill-page {
        display: block !important;
        width: 190mm !important;
        min-height: 277mm !important;
        margin: 0 auto !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: 0 !important;
        outline: 0 !important;
    }
    .bill-header {
        display: flex !important;
        align-items: flex-start !important;
        justify-content: space-between !important;
        gap: 10mm !important;
        border-bottom: 1.2mm solid #111827 !important;
        padding-bottom: 7mm !important;
    }
    .bill-brand { display: flex !important; align-items: center !important; gap: 5mm !important; min-width: 0 !important; }
    .bill-logo { width: 23mm !important; height: 23mm !important; object-fit: contain !important; flex: 0 0 auto !important; }
    .bill-school-copy h2 { font-size: 20pt !important; line-height: 1.1 !important; letter-spacing: .02em !important; margin: 0 !important; }
    .bill-school-copy p { font-size: 11pt !important; line-height: 1.35 !important; margin: 1mm 0 0 !important; color: #4b5563 !important; }
    .bill-meta { flex: 0 0 58mm !important; text-align: right !important; }
    .bill-meta p:first-child { font-size: 8pt !important; letter-spacing: .3em !important; color: #9ca3af !important; }
    .bill-meta p:nth-child(2) { font-size: 16pt !important; line-height: 1.1 !important; margin-top: 4mm !important; }
    .bill-meta p:nth-child(3) { font-size: 10.5pt !important; margin-top: 2mm !important; color: #4b5563 !important; }
    .bill-party-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 6mm !important;
        margin-top: 10mm !important;
    }
    .bill-info-card,
    .bill-total-card,
    .bill-words p {
        border: .35mm solid #e5e7eb !important;
        border-radius: 5mm !important;
        background: #fff !important;
    }
    .bill-info-card { min-height: 36mm !important; padding: 6mm !important; }
    .bill-info-card > p:first-child,
    .bill-words > p:first-child { font-size: 8pt !important; letter-spacing: .25em !important; color: #9ca3af !important; margin: 0 !important; }
    .bill-info-card h3 { font-size: 14pt !important; line-height: 1.25 !important; margin: 4mm 0 0 !important; }
    .bill-info-card div,
    .bill-info-card p { font-size: 10.5pt !important; line-height: 1.45 !important; color: #4b5563 !important; }
    .bill-items-wrap {
        margin-top: 9mm !important;
        border: .4mm solid #d1d5db !important;
        border-radius: 5mm !important;
        overflow: hidden !important;
    }
    .bill-items-table { width: 100% !important; border-collapse: collapse !important; font-size: 10.5pt !important; }
    .bill-items-table thead { background: #f3f4f6 !important; color: #4b5563 !important; }
    .bill-items-table th { font-size: 8pt !important; letter-spacing: .22em !important; text-transform: uppercase !important; }
    .bill-items-table th,
    .bill-items-table td { padding: 4mm !important; border-bottom: .25mm solid #e5e7eb !important; }
    .bill-items-table tbody tr:last-child td { border-bottom: 0 !important; }
    .bill-summary-grid {
        display: grid !important;
        grid-template-columns: 1fr 62mm !important;
        gap: 8mm !important;
        margin-top: 9mm !important;
        align-items: start !important;
    }
    .bill-words p:nth-child(2) { background: #f9fafb !important; padding: 4mm !important; margin-top: 3mm !important; font-size: 10.5pt !important; }
    .bill-total-card { padding: 5mm !important; }
    .bill-total-card div { display: flex !important; justify-content: space-between !important; gap: 8mm !important; font-size: 10.5pt !important; }
    .bill-total-card div:last-child { margin-top: 4mm !important; padding-top: 4mm !important; border-top: .3mm solid #e5e7eb !important; font-size: 15pt !important; }
    .bill-signatures {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 12mm !important;
        margin-top: 18mm !important;
    }
    .bill-signatures div div { height: 14mm !important; border-bottom: .35mm solid #111827 !important; }
    .bill-signatures p { font-size: 8pt !important; margin-top: 2mm !important; letter-spacing: .14em !important; color: #4b5563 !important; }
}
</style>
@endsection
