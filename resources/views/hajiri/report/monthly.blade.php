@php
    $employeeCount = max(1, $users->count());
    $rowHeight = max(5.5, min(13, 112 / $employeeCount));
    $nameFont = $employeeCount > 16 ? 6.5 : ($employeeCount > 10 ? 7.5 : 9);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $groupLabel }} Attendance · {{ $monthLabel }} {{ $yearBS }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; color: #17251d; font-family: Arial, sans-serif; background: #eef2f0; }
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
            width: 277mm; min-height: 190mm; margin: 10mm auto; padding: 8mm;
            background: white; box-shadow: 0 2px 12px #0002;
        }
        .header {
            display: grid; grid-template-columns: 18mm 1fr 18mm; align-items: center;
            border-bottom: 1.5px solid #1a5632; padding-bottom: 2.5mm;
        }
        .logo { width: 15mm; height: 15mm; object-fit: contain; }
        .school { color: #1a5632; font-size: 18px; font-weight: 900; text-align: center; }
        .address, .subtitle { margin-top: 1px; color: #52645a; font-size: 8px; font-weight: 700; text-align: center; }
        .title { margin-top: 2px; color: #0b2415; font-size: 11px; font-weight: 900; text-align: center; }
        table { width: 100%; margin-top: 4mm; border-collapse: collapse; table-layout: fixed; }
        th, td {
            border: .3mm solid #52645a; padding: 1px; text-align: center;
            vertical-align: middle; overflow: hidden; font-size: 6px;
        }
        th { height: 7mm; background: #edf5ef; font-weight: 900; }
        .employee { width: 39mm; padding: 1.5mm; text-align: left; font-size: {{ $nameFont }}px; }
        .employee small { display: block; margin-top: 1px; color: #52645a; font-size: 6px; }
        .attendance-row { height: {{ $rowHeight }}mm; }
        .status { font-size: 7px; font-weight: 900; }
        .status.plain { color: #17251d; }
        .status.leave {
            color: #1d4ed8; background: #eff6ff; font-size: 5px; line-height: 1;
            writing-mode: vertical-rl; transform: rotate(180deg); overflow-wrap: normal;
        }
        .status.offday {
            color: #b91c1c; background: #fef2f2; font-size: 5px; line-height: 1;
            writing-mode: vertical-rl; transform: rotate(180deg); overflow-wrap: normal;
        }
        .status.detailed {
            color: #17251d; font-size: 5.2px; line-height: 1.05;
            writing-mode: vertical-rl; transform: rotate(180deg);
        }
        .total { width: 12mm; font-size: 8px; font-weight: 900; }
        .legend { margin-top: 2mm; color: #52645a; font-size: 7px; font-weight: 700; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18mm; margin-top: 9mm; }
        .signature { padding-top: 2mm; border-top: .3mm solid #52645a; color: #52645a; font-size: 8px; font-weight: 700; text-align: center; }
        .empty { margin: 30mm auto; color: #52645a; font-size: 15px; font-weight: 800; text-align: center; }
        @media print {
            html, body { background: white; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div><strong>{{ $groupLabel }}</strong> · {{ $monthLabel }} {{ $yearBS }} BS</div>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <main class="sheet">
        <header class="header">
            <img class="logo" src="{{ $siteSettings->logoUrl() }}" alt="">
            <div>
                <div class="school">{{ $siteSettings->localized('site_name', 'School') }}</div>
                <div class="address">{{ $siteSettings->localized('site_address', 'Nepal') }}</div>
                <div class="title">
                    {{ $reportType === 'ap' ? 'A/P Attendance Report' : 'Detailed Attendance Report' }}
                    · {{ $monthLabel }} {{ $yearBS }} BS
                </div>
                <div class="subtitle">{{ $groupLabel }} · Generated {{ now()->format('d M Y, h:i A') }}</div>
            </div>
            <div></div>
        </header>

        @if($users->isEmpty())
            <div class="empty">No employees with Device IDs match this report.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th class="employee">Employee</th>
                        @foreach($days as $day)
                            <th title="{{ $day['ad'] }}">{{ $day['day'] }}<br>{{ sprintf('%02d', $day['bs_day']) }}</th>
                        @endforeach
                        <th class="total">P</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        @php
                            $present = $days->filter(fn ($day) => ($attendance[$user->id][$day['ad']] ?? 'A') === 'P')->count();
                        @endphp
                        <tr class="attendance-row">
                            <td class="employee">
                                <strong>{{ $user->name }} [{{ $user->device_id }}]</strong>
                                <small>{{ $user->designation_id ? ($user->designation?->label ?: '—') : '—' }}</small>
                            </td>
                            @foreach($days as $day)
                                @php
                                    $value = $attendance[$user->id][$day['ad']] ?? ($reportType === 'ap' ? 'A' : '—');
                                    $type = $attendanceTypes[$user->id][$day['ad']] ?? null;
                                    $statusClass = match(true) {
                                        $type === 'leave' => 'leave',
                                        in_array($type, ['holiday', 'weekend']) => 'offday',
                                        default => str_contains($value, ':') ? 'detailed plain' : 'plain',
                                    };
                                @endphp
                                <td class="status {{ $statusClass }}" title="{{ $day['ad'] }} · {{ $value }}">{{ $value }}</td>
                            @endforeach
                            <td class="total">{{ $reportType === 'ap' ? $present : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="legend">
                @if($reportType === 'ap')
                    P = Present · A = Absent · Leave, holiday, and weekend cells show their full names
                @else
                    Times show check-in / check-out · A = Absent · Leave, holiday, and weekend cells show their full names
                @endif
            </div>
            <footer class="signatures">
                <div class="signature">Prepared By</div>
                <div class="signature">Checked By</div>
                <div class="signature">Approved By</div>
            </footer>
        @endif
    </main>
</body>
</html>
