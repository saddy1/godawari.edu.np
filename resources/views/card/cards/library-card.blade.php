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
        background: #1a6b3a;
        color: white;
        padding: 2.5mm 4mm;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .header-left { }
    .college-name { font-size: 6pt; font-weight: bold; }
    .card-title { font-size: 4pt; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 1px; }

    .lib-id-box {
        background: rgba(255,255,255,0.15);
        border: 0.5px solid rgba(255,255,255,0.3);
        border-radius: 1mm;
        padding: 1mm 2mm;
        text-align: center;
    }
    .lib-id-label { font-size: 3.5pt; color: rgba(255,255,255,0.6); }
    .lib-id-val   { font-size: 6.5pt; font-weight: bold; letter-spacing: 1px; }

    .body {
        display: flex;
        gap: 3mm;
        padding: 3mm 4mm;
    }

    .photo {
        width: 15mm; height: 18mm;
        border: 0.5px solid #ccc;
        border-radius: 1mm;
        overflow: hidden;
        flex-shrink: 0;
    }
    .photo img { width: 100%; height: 100%; object-fit: cover; }

    .info { flex: 1; }
    .name { font-size: 7.5pt; font-weight: bold; color: #1a6b3a; margin-bottom: 2mm; }
    .row  { display: flex; gap: 2mm; margin-bottom: 1mm; }
    .label { font-size: 4pt; color: #888; text-transform: uppercase; letter-spacing: 0.3px; }
    .value { font-size: 5pt; color: #333; }

    .footer {
        background: #f0faf4;
        border-top: 0.5px solid #c3e6cb;
        padding: 1.5mm 4mm;
        display: flex;
        justify-content: space-between;
        font-size: 4pt;
        color: #666;
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
    }

    .signature-line {
        border-top: 0.5px solid #aaa;
        margin-top: 5mm;
        padding-top: 1mm;
        font-size: 3.5pt;
        color: #999;
        text-align: center;
    }

    .strip {
        position: absolute;
        top: 0; bottom: 0; right: 0;
        width: 5mm;
        background: repeating-linear-gradient(
            45deg,
            #1a6b3a,
            #1a6b3a 1mm,
            #2d9e58 1mm,
            #2d9e58 2mm
        );
        opacity: 0.15;
    }
</style>
</head>
<body>
<div class="card">
    <div class="strip"></div>

    <div class="header">
        <div class="header-left">
            <div class="college-name">IOE, Purwanchal Campus</div>
            <div class="card-title">📚 Library Membership Card</div>
        </div>
        <div class="lib-id-box">
            <div class="lib-id-label">Lib. ID</div>
            <div class="lib-id-val">{{ $student->library_id ?? 'N/A' }}</div>
        </div>
    </div>

    <div class="body">
        <div class="photo">
            <img src="{{ $student->photo ? public_path('storage/' . $student->photo) : public_path('images/default-avatar.svg') }}" alt="">
        </div>
        <div class="info">
            <div class="name">{{ $student->full_name }}</div>
            <div class="row">
                <div>
                    <div class="label">Member Type</div>
                    <div class="value">{{ ucfirst($student->member_type) }}</div>
                </div>
                <div>
                    <div class="label">Roll / ID</div>
                    <div class="value">{{ $student->roll_number }}</div>
                </div>
            </div>
            @if($student->program)
            <div>
                <div class="label">Program</div>
                <div class="value">{{ $student->program }}</div>
            </div>
            @endif
            <div class="signature-line">Librarian's Signature</div>
        </div>
    </div>

    <div class="footer">
        <span>This card is non-transferable</span>
        <span>Valid: {{ $student->valid_till ? $student->valid_till->format('M Y') : date('Y') + 1 }}</span>
    </div>
</div>
</body>
</html>
