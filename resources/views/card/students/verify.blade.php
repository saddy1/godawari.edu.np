<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Verification — {{ $student->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            max-width: 400px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1a6b2f 0%, #055b0a 100%);
            color: #fff;
            padding: 1.25rem 1.5rem;
            text-align: center;
        }
        .header h1 { font-size: 1rem; font-weight: 700; letter-spacing: .03em; }
        .header p  { font-size: .75rem; opacity: .8; margin-top: .2rem; }
        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 999px;
            padding: .25rem .75rem;
            font-size: .72rem;
            font-weight: 600;
            margin-top: .6rem;
        }
        .verified-badge svg { width: 14px; height: 14px; }
        .body { padding: 1.5rem; }
        .photo-row {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: 1.25rem;
        }
        .photo {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            flex-shrink: 0;
        }
        .name-block { flex: 1; }
        .name-block h2 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a202c;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .name-block .roll {
            font-size: .8rem;
            color: #4a5568;
            margin-top: .3rem;
        }
        .badge {
            display: inline-block;
            margin-top: .5rem;
            font-size: .7rem;
            font-weight: 600;
            padding: .2rem .6rem;
            border-radius: 999px;
            text-transform: capitalize;
        }
        .badge-student { background: #dbeafe; color: #1d4ed8; }
        .badge-teacher { background: #ede9fe; color: #6d28d9; }
        .badge-staff   { background: #dcfce7; color: #166534; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 1rem 0; }
        .info-table { width: 100%; font-size: .82rem; border-collapse: collapse; }
        .info-table tr td { padding: .35rem .2rem; }
        .info-table tr td:first-child { color: #718096; font-weight: 600; width: 38%; }
        .info-table tr td:last-child  { color: #2d3748; }
        .info-table tr + tr td { border-top: 1px solid #f7fafc; }
        .footer {
            background: #f7fafc;
            border-top: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
            text-align: center;
            font-size: .7rem;
            color: #a0aec0;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="header">
        <h1>{{ $siteSettings->localized('site_name', config('app.name')) }}</h1>
        <p>{{ $siteSettings->localized('site_address', '') }}</p>
        <div class="verified-badge">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Identity Verified
        </div>
    </div>

    <div class="body">
        <div class="photo-row">
            <img src="{{ $student->photo_url }}" class="photo" alt="Photo">
            <div class="name-block">
                <h2>{{ $student->full_name }}</h2>
                <p class="roll">
                    @if(in_array(strtolower($student->member_type), ['staff', 'teacher']))
                        Staff ID: {{ $student->roll_number }}
                    @else
                        Roll No: {{ $student->roll_number }}
                    @endif
                </p>
                <span class="badge badge-{{ strtolower($student->member_type) }}">
                    {{ ucfirst($student->member_type) }}
                </span>
            </div>
        </div>

        <table class="info-table">
            @if($student->stream || $student->program)
                <tr>
                    <td>Class / Dept</td>
                    <td>{{ $student->stream ?: $student->program }}
                        @if($student->section) ({{ $student->section }}) @endif
                    </td>
                </tr>
            @endif
            @if($student->registration_no)
                <tr><td>Reg No</td><td>{{ $student->registration_no }}</td></tr>
            @endif
            @if($student->dob)
                <tr><td>Date of Birth</td><td>{{ $student->dob->format('d M Y') }}</td></tr>
            @endif
            @if($student->valid_till)
                <tr>
                    <td>Valid Till</td>
                    <td>{{ $student->valid_till->format('M Y') }}
                        @if($student->valid_till->isPast())
                            <span style="color:#e53e3e;font-weight:700"> (Expired)</span>
                        @endif
                    </td>
                </tr>
            @endif
            @if($student->mobile)
                <tr><td>Mobile</td><td>{{ $student->mobile }}</td></tr>
            @endif
            @if($student->address_label)
                <tr><td>Address</td><td>{{ $student->address_label }}</td></tr>
            @endif
        </table>
    </div>

    <div class="footer">
        Scan verified on {{ now()->format('d M Y, h:i A') }} &mdash;
        {{ $siteSettings->localized('site_name', config('app.name')) }}
    </div>
</div>

</body>
</html>
