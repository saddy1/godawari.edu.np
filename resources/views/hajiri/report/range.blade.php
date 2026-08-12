<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Report {{ $fromBS }} to {{ $toBS }}</title>
    <style>
        @page { size: A4 landscape; margin: 6mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #17251d; font-family: Arial, sans-serif; background: #eef2f0; }
        .toolbar {
            position: sticky; top: 0; z-index: 10; display: flex; align-items: center;
            justify-content: space-between; gap: 16px; padding: 12px 20px;
            color: white; background: #0f4c54; box-shadow: 0 2px 10px #0002;
        }
        .toolbar button {
            border: 0; border-radius: 10px; padding: 10px 18px; color: white;
            background: #e2a024; font-size: 14px; font-weight: 800; cursor: pointer;
        }
        .sheet {
            width: 285mm; min-height: 198mm; margin: 8mm auto; padding: 6mm;
            background: white; box-shadow: 0 2px 12px #0002; page-break-after: always;
        }
        .sheet:last-child { page-break-after: auto; }
        .header { display: grid; grid-template-columns: 18mm 1fr 18mm; align-items: center; border-bottom: 1.5px solid #1a5632; padding-bottom: 2mm; }
        .logo { width: 15mm; height: 15mm; object-fit: contain; }
        .school { color: #1a5632; font-size: 18px; font-weight: 900; text-align: center; }
        .address, .subtitle { margin-top: 1px; color: #52645a; font-size: 8px; font-weight: 700; text-align: center; }
        .title { margin-top: 2px; color: #0b2415; font-size: 11px; font-weight: 900; text-align: center; }
        .employee-meta { display: flex; justify-content: space-between; gap: 8mm; margin-top: 2mm; font-size: 8px; font-weight: 800; }
        table { width: 100%; margin-top: 2mm; border-collapse: collapse; table-layout: fixed; }
        th, td {
            height: 8.2mm; border: .35mm solid #52645a; padding: .5px;
            text-align: center; vertical-align: middle; font-size: 6px; overflow: hidden;
        }
        th { height: 6mm; background: #edf5ef; font-weight: 900; }
        .month { width: 21mm; padding-left: 2mm; text-align: left; font-size: 7px; }
        .total { width: 12mm; font-size: 7px; font-weight: 900; }
        .status { font-weight: 800; line-height: 1.05; overflow-wrap: anywhere; }
        .status.detailed { font-size: 5px; writing-mode: vertical-rl; transform: rotate(180deg); }
        .muted { background: #f3f4f6; color: #c4c8c6; }
        .present { color: #166534; background: #f0fdf4; }
        .absent { color: #b91c1c; }
        .off { color: #6b7280; background: #f9fafb; font-size: 5px; }
        .legend { margin-top: 1.5mm; color: #52645a; font-size: 6px; font-weight: 700; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18mm; margin-top: 8mm; }
        .signature { padding-top: 1.5mm; border-top: .3mm solid #52645a; color: #52645a; font-size: 7px; font-weight: 700; text-align: center; }
        .empty { margin: 25mm auto; color: #52645a; font-size: 16px; font-weight: 800; text-align: center; }
        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div><strong>Yearly Attendance Report</strong> · BS {{ $fromBS }} to {{ $toBS }}</div>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    @forelse($users as $user)
        <section class="sheet">
            <header class="header">
                <img class="logo" src="{{ $siteSettings->logoUrl() }}" alt="">
                <div>
                    <div class="school">{{ $siteSettings->localized('site_name', 'School') }}</div>
                    <div class="address">{{ $siteSettings->localized('site_address', 'Nepal') }}</div>
                    <div class="title">{{ $reportType === 'detailed' ? 'Detailed' : 'A/P' }} Yearly Attendance Report</div>
                    <div class="subtitle">BS {{ $fromBS }} to {{ $toBS }} · One selected year per sheet</div>
                </div>
                <div></div>
            </header>

            <div class="employee-meta">
                <span>Employee: {{ $user->name }} [Device {{ $user->device_id }}]</span>
                <span>Designation: {{ $user->designation?->label ?: '—' }}</span>
                <span>Generated: {{ now()->format('d M Y, h:i A') }}</span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="month">BS Month</th>
                        @for($day = 1; $day <= 32; $day++)
                            <th>{{ $day }}</th>
                        @endfor
                        <th class="total">P</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($months as $month)
                        @php
                            $daysByNumber = collect($month['days'])->keyBy('bs_day');
                            $present = $daysByNumber->filter(function ($day) use ($attendance, $user) {
                                $value = $attendance[$user->id][$day['ad']] ?? 'A';
                                return $value === 'P' || $value === 'W.P' || str_contains($value, ':');
                            })->count();
                        @endphp
                        <tr>
                            <td class="month">{{ $month['label'] }} {{ $month['year'] }}</td>
                            @for($dayNumber = 1; $dayNumber <= 32; $dayNumber++)
                                @php
                                    $day = $daysByNumber->get($dayNumber);
                                    $value = $day ? ($attendance[$user->id][$day['ad']] ?? 'A') : '';
                                    $cellClass = match(true) {
                                        !$day => 'muted',
                                        $value === 'P' || $value === 'W.P' || str_contains($value, ':') => 'present',
                                        $value === 'A' => 'absent',
                                        default => 'off',
                                    };
                                @endphp
                                <td class="status {{ $reportType === 'detailed' ? 'detailed' : '' }} {{ $cellClass }}"
                                    title="{{ $day ? $day['ad'].' · '.$value : '' }}">{{ $value }}</td>
                            @endfor
                            <td class="total">{{ $present }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="legend">P = Present · A = Absent · W.P = Weekend Present · Other labels indicate holidays or approved leave.</div>
            <footer class="signatures">
                <div class="signature">Prepared By</div>
                <div class="signature">Checked By</div>
                <div class="signature">Approved By</div>
            </footer>
        </section>
    @empty
        <section class="sheet"><div class="empty">No employees with Device IDs match the selected filters.</div></section>
    @endforelse
</body>
</html>
