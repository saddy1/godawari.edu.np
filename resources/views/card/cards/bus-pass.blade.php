<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; }

    .card {
        width: 85.6mm;
        height: 54mm;
        border-radius: 4mm;
        overflow: hidden;
        border: 0.5px solid #ccc;
        position: relative;
        background: #fff;
    }

    .header {
        background: linear-gradient(90deg, #d4520a 0%, #f47c20 100%);
        color: white;
        padding: 2.5mm 4mm;
        display: flex;
        align-items: center;
        gap: 2mm;
    }

    .bus-icon { font-size: 10pt; }
    .header-text { }
    .college-name { font-size: 6pt; font-weight: bold; }
    .card-title { font-size: 4pt; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 1px; }

    .body {
        display: flex;
        padding: 3mm 4mm;
        gap: 3mm;
    }

    .photo {
        width: 13mm; height: 16mm;
        border: 0.5px solid #ddd;
        border-radius: 1mm;
        overflow: hidden;
        flex-shrink: 0;
    }
    .photo img { width: 100%; height: 100%; object-fit: cover; }

    .info { flex: 1; }
    .name { font-size: 7pt; font-weight: bold; color: #333; margin-bottom: 1.5mm; }
    .label { font-size: 3.5pt; color: #999; text-transform: uppercase; letter-spacing: 0.3px; }
    .value { font-size: 5pt; color: #333; margin-bottom: 1mm; }

    .route-box {
        margin-top: 2mm;
        background: #fff5ef;
        border: 0.5px solid #f4c39a;
        border-radius: 1mm;
        padding: 1.5mm;
    }
    .route-label { font-size: 3.5pt; color: #d4520a; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5mm; }
    .route-val   { font-size: 5.5pt; font-weight: bold; color: #333; }

    .footer {
        background: #fff5ef;
        border-top: 0.5px solid #f4c39a;
        padding: 1.5mm 4mm;
        display: flex;
        justify-content: space-between;
        font-size: 4pt;
        color: #888;
        position: absolute;
        bottom: 0; left: 0; right: 0;
    }

    .pass-no {
        background: #d4520a;
        color: white;
        font-size: 5pt;
        font-weight: bold;
        padding: 1mm 2mm;
        border-radius: 1mm;
    }

    .diagonal-strip {
        position: absolute;
        top: 0; right: 0;
        width: 15mm; height: 100%;
        background: repeating-linear-gradient(
            -45deg,
            transparent,
            transparent 1mm,
            rgba(212,82,10,0.05) 1mm,
            rgba(212,82,10,0.05) 2mm
        );
    }
</style>
</head>
<body>
<div class="card">
    <div class="diagonal-strip"></div>

    <div class="header">
        <div class="bus-icon">🚌</div>
        <div class="header-text">
            <div class="college-name">IOE, Purwanchal Campus</div>
            <div class="card-title">Campus Bus Pass</div>
        </div>
        <div style="margin-left:auto">
            <div class="pass-no">{{ $student->roll_number }}</div>
        </div>
    </div>

    <div class="body">
        <div class="photo">
            <img src="{{ $student->photo ? public_path('storage/' . $student->photo) : public_path('images/default-avatar.svg') }}" alt="">
        </div>
        <div class="info">
            <div class="name">{{ $student->full_name }}</div>
            <div class="label">Member Type</div>
            <div class="value">{{ ucfirst($student->member_type) }}</div>

            <div class="route-box">
                <div class="route-label">🛣️ Route</div>
                <div class="route-val">{{ $student->bus_route ?? 'Campus Route' }}</div>
                @if($student->bus_stop)
                <div style="font-size:4pt;color:#888;margin-top:0.5mm;">Stop: {{ $student->bus_stop }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="footer">
        <span>Non-transferable • Bus passes are for campus transport only</span>
        <span>Valid: {{ $student->valid_till ? $student->valid_till->format('M Y') : date('Y') }}</span>
    </div>
</div>
</body>
</html>
