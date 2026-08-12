@php
    $title = match($type) {
        'requisition' => 'फाराम नं. २९ - माग फाराम',
        'purchase-order' => 'फाराम नं. ३० - खरिद आदेश',
        'receipt' => 'फाराम नं. ३१ - दाखिला प्रतिवेदन',
        'ledger-non-consumable' => 'फाराम नं. ३२ - खर्च भएर नजाने जिन्सी खाता',
        'ledger-consumable' => 'फाराम नं. ३३ - खर्च भएर जाने जिन्सी खाता',
        default => 'Store Form',
    };
    $landscape = in_array($type, ['receipt', 'ledger-non-consumable', 'ledger-consumable'], true);
    $schoolName = $siteSettings->localized('site_name', config('app.name')) ?? config('app.name');
    $schoolCode = $siteSettings->get('school_code') ?: '........';
    $storeNpCal = new \App\Http\Controllers\Hajiri\NepaliCalendarController();
    $storeTodayBsParts = $storeNpCal->ad_2_bs((int) now()->format('Y'), (int) now()->format('m'), (int) now()->format('d')) ?: null;
    $storeBsToday = $storeTodayBsParts ? sprintf('%04d-%02d-%02d', $storeTodayBsParts['year'], $storeTodayBsParts['month'], $storeTodayBsParts['date']) : '';
    $dateValue = function ($record, string $bsField, string $adField): string {
        $bs = $record->{$bsField} ?? null;
        if ($bs) {
            return $bs;
        }

        $ad = $record->{$adField} ?? null;
        return $ad instanceof \Carbon\CarbonInterface ? $ad->format('Y-m-d') : ($ad ?: '........');
    };
    $ledgerType = fn($item) => in_array($item?->asset_type, ['non_consumable', 'fixed_asset'], true) ? 'खर्च नहुने' : 'खर्च हुने';
@endphp

<!DOCTYPE html>
<html lang="ne">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 {{ $landscape ? 'landscape' : 'portrait' }}; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f4f6; color: #111827; font-family: "Noto Sans Devanagari", "Mangal", "Kalimati", "Arial", sans-serif; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 16px 22px; background: #fff; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 10; }
        .toolbar a, .toolbar button { border: 1px solid #d1d5db; background: #fff; color: #111827; border-radius: 10px; padding: 10px 14px; font-weight: 800; text-decoration: none; cursor: pointer; }
        .toolbar button { background: #1a5632; border-color: #1a5632; color: #fff; }
        .paper { width: {{ $landscape ? '297mm' : '210mm' }}; min-height: {{ $landscape ? '210mm' : '297mm' }}; margin: 18px auto; background: #fff; padding: 13mm; box-shadow: 0 10px 30px rgba(15, 23, 42, .08); }
        .form-no { text-align: right; font-size: 20px; font-weight: 800; }
        .center { text-align: center; }
        .title { margin-top: 18px; font-size: 23px; font-weight: 900; }
        .sub-title { margin-top: 6px; font-size: 19px; font-weight: 800; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 22px; font-size: 17px; line-height: 1.9; }
        .meta .right { text-align: left; justify-self: end; min-width: 70mm; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; font-size: {{ $landscape ? '12px' : '15px' }}; }
        th, td { border: 1px solid #111827; padding: 7px 6px; vertical-align: middle; }
        th { font-weight: 800; text-align: center; }
        td { height: 28px; }
        .number-row td { text-align: center; font-weight: 700; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; margin-top: 28px; font-size: 16px; line-height: 2; }
        .signatures.two { grid-template-columns: repeat(2, 1fr); }
        .signature-box { min-height: 80px; }
        .note { margin-top: 12px; font-size: 14px; line-height: 1.8; }
        .instructions { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 18px; font-size: 13px; line-height: 1.75; }
        .actions-note { margin-top: 12px; border: 1px solid #111827; padding: 10px; font-size: 14px; }
        .ledger-category-row td { background: #f3f4f6; font-weight: 900; }
        .ledger-page-break { break-before: page; page-break-before: always; }
        @media print {
            body { background: #fff; }
            .toolbar, .no-print { display: none; }
            .paper { margin: 0; padding: 0; width: auto; min-height: auto; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('admin.store.dashboard') }}">Back to Store</a>
        <strong>{{ $title }}</strong>
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <main class="paper">
        @if($type === 'requisition')
            <div class="form-no">फाराम नं. २९</div>
            <div class="center">
                <div class="school-name">{{ $schoolName }}</div>
                <div class="title">माग फाराम</div>
            </div>
            <div class="meta">
                <div>
                    विद्यालय कोड नं. {{ $schoolCode }}<br>
                    माग गर्नेको नाम: {{ $record->requested_by_name }}<br>
                    पद: {{ $record->requested_by_designation ?: '........' }}<br>
                    प्रयोजन: {{ $record->purpose ?: '........' }}
                </div>
                <div class="right">
                    आर्थिक वर्ष: {{ $record->fiscal_year ?: '........' }}<br>
                    माग फाराम नं.: {{ $record->display_requisition_no }}<br>
                    मिति: {{ $dateValue($record, 'requested_at_bs', 'requested_at') }}
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">क्र.सं.</th>
                        <th rowspan="2">सामानको नाम</th>
                        <th rowspan="2">जिन्सी खाता प्रकार</th>
                        <th rowspan="2">स्पेसिफिकेशन</th>
                        <th colspan="2">माग गरिएको</th>
                        <th rowspan="2">कैफियत</th>
                    </tr>
                    <tr><th>इकाई</th><th>परिमाण</th></tr>
                </thead>
                <tbody>
                    @foreach($record->items as $index => $item)
                        <tr>
                            <td class="center">{{ $index + 1 }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td class="center">{{ $item->category?->name ?: $item->item?->category?->name }}<br>{{ $ledgerType($item->item ?: $item->purchaseOrderItem?->item) }}</td>
                            <td>{{ $item->specification }}</td>
                            <td class="center">{{ $item->unit }}</td>
                            <td class="center">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td>
                                {{ $item->remarks }}
                                @if($item->is_returned)
                                    <div style="font-size:11px; font-weight:800; color:#15803d;">Returned: {{ number_format((float) $item->returned_quantity, 2) }} {{ $item->returned_at_bs }}</div>
                                @elseif(($item->item?->asset_type ?? '') === 'non_consumable' || ($item->item?->asset_type ?? '') === 'fixed_asset')
                                    <div style="font-size:11px; font-weight:800; color:#92400e;">Custody: {{ $record->issued_to_name }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @for($i = $record->items->count(); $i < 4; $i++)
                        <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    @endfor
                </tbody>
            </table>
            <div class="signatures">
                <div class="signature-box">माग गर्नेको दस्तखत:<br>नाम:<br>मिति:</div>
                <div class="signature-box">सिफारिस गर्नेको दस्तखत:<br>नाम:<br>मिति:</div>
                <div class="signature-box">आदेश दिनेको दस्तखत:<br>मिति:</div>
            </div>
            <div class="signatures two">
                <div>मालसामान बुझिलिनेको नाम:<br>मिति:</div>
                <div>जिन्सी खातामा चढाउनेको दस्तखत:<br>मिति:</div>
            </div>
        @elseif($type === 'purchase-order')
            @php
                $taxMode = $record->tax_mode ?: (((float) $record->items->max('tax_rate') > 0) ? 'vat' : 'pan');
                $subtotal  = $record->items->sum(fn($i) => round((float)$i->quantity * (float)$i->rate, 2));
                $vatAmount = $taxMode === 'vat' ? round($subtotal * 0.13, 2) : 0;
                $grandTotal = $subtotal + $vatAmount;
            @endphp
            <div class="form-no">फाराम नं. ३०</div>
            <div class="center">
                <div>...............विद्यालय,</div>
                <div class="title">खरिद आदेश</div>
            </div>
            <div class="meta">
                <div>
                    श्री {{ $record->supplier_name }}<br>
                    आदेश गरिएको व्यक्ति/निकायको नाम ।<br>
                    ठेगाना: {{ $record->supplier_address ?: '........' }}&emsp;&emsp;फोन नं.: {{ $record->supplier_phone ?: '........' }}<br>
                    संस्था दर्ता नं.: {{ $record->supplier?->registration_no ?: '........' }}&emsp;&emsp;मूल्य अभिवृद्धि कर/स्थायी लेखा नम्बर: {{ $record->supplier?->pan_vat_no ?: '........' }}<br>
                    अपेक्षित मिति: {{ $record->expected_date_bs ?: '........' }}
                </div>
                <div class="right">
                    खरिद आदेश नं.: {{ $record->order_no }}<br>
                    खरिद आदेश मिति: {{ $dateValue($record, 'order_date_bs', 'order_date') }}<br>
                    खरिद सम्बन्धी निर्णय नं.: {{ $record->decision_no ?: '........' }}<br>
                    निर्णय मिति: {{ $dateValue($record, 'decision_date_bs', 'decision_date') }}
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">क.सं.</th>
                        <th colspan="5">सामानको</th>
                        <th colspan="2">मूल्य</th>
                        <th rowspan="2">कैफियत</th>
                    </tr>
                    <tr>
                        <th>जिन्सी वर्गीकरण संकेत नं.</th>
                        <th>नाम</th>
                        <th>स्पेसिफिकेशन</th>
                        <th>इकाई</th>
                        <th>परिमाण</th>
                        <th>दर<br><small>(VAT बाहेक)</small></th>
                        <th>जम्मा</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record->items as $index => $item)
                        <tr>
                            <td class="center">{{ $index + 1 }}</td>
                            <td class="center">PO-{{ str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->specification }}</td>
                            <td class="center">{{ $item->unit }}</td>
                            <td class="center">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="center">{{ number_format((float) $item->rate, 2) }}</td>
                            <td class="center">{{ number_format(round((float) $item->quantity * (float) $item->rate, 2), 2) }}</td>
                            <td>{{ $item->remarks }}</td>
                        </tr>
                    @endforeach
                    @for($i = $record->items->count(); $i < 4; $i++)
                        <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    @endfor
                    <tr>
                        <td colspan="7" style="text-align:right; font-weight:800; padding-right:8px;">जम्मा रकम</td>
                        <td class="center" style="font-weight:800;">{{ number_format($subtotal, 2) }}</td>
                        <td></td>
                    </tr>
                    @if($taxMode === 'vat')
                        <tr>
                            <td colspan="7" style="text-align:right; font-weight:800; padding-right:8px;">सु.अ.कर (१३%)</td>
                            <td class="center" style="font-weight:800;">{{ number_format($vatAmount, 2) }}</td>
                            <td></td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="7" style="text-align:right; font-weight:900; padding-right:8px;">कुल जम्मा रकम</td>
                        <td class="center" style="font-weight:900;">{{ number_format($grandTotal, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <div class="actions-note">माथि उल्लेखित सामान मिति ........ भित्र विद्यालय स्थानमा दाखिला गरी बील/इन्भ्वाइस प्रस्तुत गर्नु होला ।</div>
            <div class="actions-note" style="margin-top:12px;">
                <div style="font-weight:900; margin-bottom:8px;">उपरोक्त अनुसार खरिद आदेश तयार गर्ने, सिफारिस गर्ने र स्वीकृत गर्ने</div>
                <div class="signatures" style="margin-top:0;">
                    <div>फाँटवालाको दस्तखत:<br>नाम:<br>मिति:</div>
                    <div>शाखा प्रमुखको दस्तखत:<br>नाम:<br>मिति:</div>
                    <div>प्रधानाध्यापकको नाम:<br>दस्तखत:<br>मिति:</div>
                </div>
            </div>
            <div class="note" style="margin-top:14px;">माथि उल्लेखित सामानहरु मिति {{ $record->expected_date_bs ?: $dateValue($record, 'expected_date_bs', 'expected_date') }} भित्र {{ $schoolName }} मा बुझ्काउने छु भनी सहीछाप गर्ने</div>
            <div class="signatures" style="margin-top:8px; border-top:1px solid #111827; padding-top:10px;">
                <div style="text-align:center; font-weight:800; border-top:1px solid #111827; padding-top:6px; margin-top:28px;">फर्मको नाम</div>
                <div style="text-align:center; font-weight:800; border-top:1px solid #111827; padding-top:6px; margin-top:28px;">दस्तखत र छाप</div>
                <div style="text-align:center; font-weight:800; border-top:1px solid #111827; padding-top:6px; margin-top:28px;">मिति</div>
            </div>
        @elseif($type === 'receipt')
            <div class="form-no">फाराम नं. ३१</div>
            <div class="center">
                <div>.............................विद्यालय</div>
                <div class="title">दाखिला प्रतिवेदन</div>
            </div>
            <div class="meta">
                <div>आर्थिक वर्ष: {{ $record->fiscal_year ?: '........' }}</div>
                <div class="right">दाखिला मिति: {{ $dateValue($record, 'received_at_bs', 'received_at') }}<br>दाखिला प्रतिवेदन नं.: {{ $record->receipt_no }}</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>क्र.स.</th><th>खरिद आदेश/हस्तान्तरण फारम नं.</th><th>जिन्सी वर्गीकरण संकेत नं.</th><th>जिन्सी खाता पाना नं.</th><th>सामानको नाम</th><th>स्पेसिफिकेशन</th><th>सामानको पहिचान नं.</th><th>मोडल नं.</th><th>इकाई</th><th>परिमाण</th><th>दर</th><th>जम्मा मू.अ. कर बाहेक</th><th>मू.अ कर</th><th>सामानको जम्मा मूल्य</th><th>अन्य खर्च</th><th>अन्य खर्च समेत जम्मा रकम</th><th>कैफियत</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="number-row">@for($i = 1; $i <= 17; $i++)<td>{{ $i }}</td>@endfor</tr>
                    @foreach($record->items as $index => $item)
                        <tr>
                            <td class="center">{{ $index + 1 }}</td><td>{{ $item->purchaseOrderItem?->purchaseOrder?->order_no ?? $record->challan_no }}</td><td>{{ $item->item?->item_code }}</td><td>{{ $item->item?->category?->name }}<br>{{ $ledgerType($item->item?->category) }}</td><td>{{ $item->item_name }}</td><td>{{ $item->specification }}</td><td>{{ $item->item?->serial_no }}</td><td>{{ $item->item?->model_no }}</td><td>{{ $item->unit }}</td><td>{{ number_format((float) $item->quantity, 2) }}</td><td>{{ number_format((float) $item->rate, 2) }}</td><td>{{ number_format((float) $item->amount, 2) }}</td><td></td><td>{{ number_format((float) $item->amount, 2) }}</td><td></td><td>{{ number_format((float) $item->amount, 2) }}</td><td>{{ $item->remarks }}</td>
                        </tr>
                    @endforeach
                    @for($i = $record->items->count(); $i < 3; $i++)
                        <tr>@for($j = 0; $j < 17; $j++)<td>&nbsp;</td>@endfor</tr>
                    @endfor
                </tbody>
            </table>
            <div class="signatures">
                <div>फाँटवालाको दस्तखत:<br>नाम:<br>पद:<br>मिति:</div>
                <div>भण्डार प्रमुखको दस्तखत:<br>नाम:<br>पद:<br>मिति:</div>
                <div>प्रमाणित गर्नेको दस्तखत:<br>नाम:<br>पद:<br>मिति:</div>
            </div>
        @elseif($type === 'issue')
            <div class="form-no">निकासा फाराम</div>
            <div class="center">
                <div>.............................विद्यालय</div>
                <div class="title">जिन्सी सामान निकासा / हस्तान्तरण फाराम</div>
            </div>
            <div class="meta">
                <div>
                    बुझिलिनेको नाम: {{ $record->issued_to_name }}<br>
                    पद: {{ $record->issued_to_designation ?: '........' }}<br>
                    प्रयोजन: {{ $record->purpose ?: '........' }}
                </div>
                <div class="right">
                    आर्थिक वर्ष: {{ $record->fiscal_year ?: '........' }}<br>
                    निकासा नं.: {{ $record->issue_no }}<br>
                    मिति: {{ $dateValue($record, 'issued_at_bs', 'issued_at') }}
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>क्र.सं.</th><th>जिन्सी संकेत नं.</th><th>सामानको नाम</th><th>स्पेसिफिकेशन</th><th>इकाई</th><th>परिमाण</th><th>दर</th><th>रकम</th><th>कैफियत</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="number-row"><td>१</td><td>२</td><td>३</td><td>४</td><td>५</td><td>६</td><td>७</td><td>८</td><td>९</td></tr>
                    @foreach($record->items as $index => $item)
                        <tr>
                            <td class="center">{{ $index + 1 }}</td>
                            <td>{{ $item->item?->item_code }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->specification }}</td>
                            <td class="center">{{ $item->unit }}</td>
                            <td class="center">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="center">{{ number_format((float) $item->rate, 2) }}</td>
                            <td class="center">{{ number_format((float) $item->amount, 2) }}</td>
                            <td>{{ $item->remarks }}</td>
                        </tr>
                    @endforeach
                    @for($i = $record->items->count(); $i < 4; $i++)
                        <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    @endfor
                </tbody>
            </table>
            <div class="signatures">
                <div>निकासा गर्नेको दस्तखत:<br>नाम: {{ $record->store_keeper_name ?: '........' }}<br>मिति:</div>
                <div>बुझिलिनेको दस्तखत:<br>नाम: {{ $record->issued_to_name }}<br>मिति:</div>
                <div>स्वीकृत गर्नेको दस्तखत:<br>नाम: {{ $record->approved_by_name ?: '........' }}<br>मिति:</div>
            </div>
            <div class="no-print" style="margin-top:16px;">
                <h3 style="margin:0 0 8px; font-size:14px;">Return Items</h3>
                @foreach($record->items as $item)
                    @php $remainingReturn = max((float) $item->quantity - (float) $item->returned_quantity, 0); @endphp
                    @if($remainingReturn > 0)
                        <form method="POST" action="{{ route('admin.store.issue-items.return', $item) }}" style="display:flex; gap:8px; align-items:center; margin-bottom:8px; padding:8px; border:1px solid #e5e7eb; border-radius:8px;">
                            @csrf
                            <strong style="min-width:160px;">{{ $item->item_name }}</strong>
                            <span>Remaining: {{ number_format($remainingReturn, 2) }}</span>
                            <input name="returned_quantity" type="number" min="0.01" max="{{ $remainingReturn }}" step="0.01" value="{{ $remainingReturn }}" style="width:100px; border:1px solid #d1d5db; border-radius:6px; padding:6px;">
                            <input name="returned_at_bs" value="{{ $storeBsToday ?? '' }}" placeholder="YYYY-MM-DD" style="width:120px; border:1px solid #d1d5db; border-radius:6px; padding:6px;">
                            <button style="border:1px solid #1a5632; background:#1a5632; color:#fff; border-radius:6px; padding:7px 10px; font-weight:800;">Return</button>
                        </form>
                    @endif
                @endforeach
            </div>
        @elseif(in_array($type, ['ledger-non-consumable', 'ledger-consumable'], true))
            <div class="form-no">फाराम नं. {{ $type === 'ledger-consumable' ? '३३' : '३२' }}</div>
            <div class="center">
                <div>.............................विद्यालय</div>
                <div class="title">{{ $type === 'ledger-consumable' ? 'खर्च भएर जाने (खप्ने मालसामान) सामानको जिन्सी खाता' : 'खर्च भएर नजाने (नखप्ने मालसामान) सामानको जिन्सी खाता' }}</div>
            </div>
            <div class="meta">
                <div>
                    जिन्सी सामानको नाम: ................................<br>
                    इकाई: ................................<br>
                    स्पेसिफिकेशन: ................................
                </div>
                <div class="right">
                    आर्थिक वर्ष: {{ $activeFiscalYear->name ?? '........' }}<br>
                    जिन्सी संकेत नं.: ........<br>
                    जिन्सी खाता पाना नं.: ........
                </div>
            </div>
            <table>
                <thead>
                    @if($type === 'ledger-consumable')
                        <tr><th rowspan="2">मिति</th><th rowspan="2">दाखिला/निकासी नं.</th><th colspan="3">स्टोर दाखिला (आम्दानी)</th><th colspan="3">निकासा (खर्च)</th><th colspan="3">बाँकी</th><th rowspan="2">कैफियत</th></tr>
                        <tr><th>परिमाण</th><th>दर</th><th>रकम</th><th>परिमाण</th><th>दर</th><th>रकम</th><th>परिमाण</th><th>दर</th><th>रकम</th></tr>
                    @else
                        <tr><th rowspan="2">मिति</th><th rowspan="2">दाखिला नं./हस्तान्तरण फाराम नं.</th><th rowspan="2">स्पेसिफिकेशन</th><th rowspan="2">सामानको पहिचान</th><th rowspan="2">मोडल नं.</th><th colspan="4">विवरण</th><th colspan="3">आम्दानी</th><th colspan="2">हस्तान्तरण</th><th colspan="2">बाँकी</th><th rowspan="2">कैफियत</th></tr>
                        <tr><th>उत्पादन गर्ने देश वा कम्पनी</th><th>साइज</th><th>अनुमानित आयु</th><th>सामान प्राप्ति स्रोत</th><th>परिमाण</th><th>प्रति इकाई दर</th><th>जम्मा मूल्य</th><th>परिमाण</th><th>जम्मा मूल्य</th><th>परिमाण</th><th>जम्मा मूल्य</th></tr>
                    @endif
                </thead>
                <tbody>
                    @foreach($movements->groupBy(fn($movement) => $movement->item->category?->name ?: 'Uncategorized') as $categoryName => $categoryMovements)
                        @php($category = $categoryMovements->first()?->item?->category)
                        <tr class="ledger-category-row {{ $loop->first ? '' : 'ledger-page-break' }}">
                            <td colspan="{{ $type === 'ledger-consumable' ? 12 : 17 }}">जिन्सी खाता समूह: {{ $categoryName }} | {{ $ledgerType($category) }}</td>
                        </tr>
                    @foreach($categoryMovements as $movement)
                        @if($type === 'ledger-consumable')
                            <tr>
                                <td>{{ $dateValue($movement, 'movement_date_bs', 'movement_date') }}</td><td>{{ $movement->remarks }}</td><td>{{ number_format((float) $movement->quantity_in, 2) }}</td><td>{{ number_format((float) $movement->rate, 2) }}</td><td>{{ number_format((float) $movement->quantity_in * (float) $movement->rate, 2) }}</td><td>{{ number_format((float) $movement->quantity_out, 2) }}</td><td>{{ number_format((float) $movement->rate, 2) }}</td><td>{{ number_format((float) $movement->quantity_out * (float) $movement->rate, 2) }}</td><td>{{ number_format((float) $movement->balance_quantity, 2) }}</td><td>{{ number_format((float) $movement->rate, 2) }}</td><td>{{ number_format((float) $movement->balance_value, 2) }}</td><td>{{ $movement->item->name }}</td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ $dateValue($movement, 'movement_date_bs', 'movement_date') }}</td><td>{{ $movement->remarks }}</td><td>{{ $movement->item->specification }}</td><td>{{ $movement->item->serial_no }}</td><td>{{ $movement->item->model_no }}</td><td></td><td></td><td>{{ $movement->item->useful_life_months }}</td><td>{{ $movement->movement_type }}</td><td>{{ number_format((float) $movement->quantity_in, 2) }}</td><td>{{ number_format((float) $movement->rate, 2) }}</td><td>{{ number_format((float) $movement->quantity_in * (float) $movement->rate, 2) }}</td><td>{{ number_format((float) $movement->quantity_out, 2) }}</td><td>{{ number_format((float) $movement->quantity_out * (float) $movement->rate, 2) }}</td><td>{{ number_format((float) $movement->balance_quantity, 2) }}</td><td>{{ number_format((float) $movement->balance_value, 2) }}</td><td>{{ $movement->item->name }}</td>
                            </tr>
                        @endif
                    @endforeach
                    @endforeach
                    @for($i = $movements->count(); $i < 5; $i++)
                        <tr>@for($j = 0; $j < ($type === 'ledger-consumable' ? 12 : 17); $j++)<td>&nbsp;</td>@endfor</tr>
                    @endfor
                </tbody>
            </table>
            <div class="signatures">
                <div>{{ $type === 'ledger-consumable' ? 'स्टोर प्रमुखको नाम:' : 'फाँटवालाको दस्तखत:' }}<br>दस्तखत:<br>मिति:</div>
                <div>{{ $type === 'ledger-consumable' ? '' : 'शाखा प्रमुखको दस्तखत:' }}<br>नाम:<br>पद:<br>मिति:</div>
                <div>प्रधानाध्यापकको दस्तखत:<br>नाम:<br>पद:<br>मिति:</div>
            </div>
        @endif
    </main>
</body>
</html>
