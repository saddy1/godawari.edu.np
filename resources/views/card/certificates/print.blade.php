<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $certificate->certificate_type_label }} — {{ $certificate->student_name }}</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --brand: {{ $siteSettings->get('primary_color',   '#1a5632') }};
    --dark:  {{ $siteSettings->get('dark_color',      '#0b2415') }};
    --gold:  {{ $siteSettings->get('secondary_color', '#c8951a') }};
}

body { font-family: 'Times New Roman', Times, serif; background: #c8c8c8; color: #111; }

/* ── Toolbar ── */
#toolbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    background: var(--dark); color: white;
    display: flex; align-items: center; gap: 12px;
    padding: 9px 20px; box-shadow: 0 2px 8px rgba(0,0,0,.4);
}
#toolbar h1 { font-size: 13px; font-weight: 600; flex: 1; }
#toolbar button {
    border: none; cursor: pointer; border-radius: 6px;
    font-size: 13px; font-weight: 600; padding: 7px 16px;
    display: flex; align-items: center; gap: 6px;
}
.btn-print { background: var(--gold); color: var(--dark); }
.btn-back  { background: rgba(255,255,255,.15); color: white; }
.btn-back:hover  { background: rgba(255,255,255,.25); }
.btn-print:hover { filter: brightness(1.1); }

#wrapper {
    margin-top: 52px;
    display: flex; justify-content: center;
    padding: 28px 20px 40px;
}

/* ── A4 Landscape ── */
.page {
    width: 1123px;
    min-height: 794px;
    background: #fff;
    box-shadow: 0 6px 32px rgba(0,0,0,.25);
    position: relative;
    padding: 32px 80px 36px;
    display: flex;
    flex-direction: column;
}

/* ── Decorative border ── */
.page::before {
    content: '';
    position: absolute; inset: 10px;
    border: 6px solid var(--brand);
    pointer-events: none; z-index: 1;
}
.page::after {
    content: '';
    position: absolute; inset: 18px;
    border: 1.5px solid var(--gold);
    pointer-events: none; z-index: 1;
}

/* ── Header ── */
.cert-header {
    display: flex; align-items: flex-start; gap: 16px;
    margin-bottom: 0;
}
.cert-logo { width: 96px; height: 96px; object-fit: contain; flex-shrink: 0; }
.cert-school-center { flex: 1; text-align: center; }
.cert-school-name {
    font-family: 'Times New Roman', Times, serif;
    font-size: 22px; font-weight: 900;
    color: var(--dark); text-transform: uppercase;
    letter-spacing: 1px; line-height: 1.2;
}
.cert-school-sub  { font-size: 15px; color: #444; font-weight: 600; margin-top: 4px; }
.cert-school-estd { font-size: 14px; color: #666; margin-top: 2px; }
.cert-no-block    { font-size: 11px; color: #888; margin-top: 6px; font-weight: 600; }

/* Photo box */
.cert-photo {
    width: 90px; height: 110px; flex-shrink: 0;
    border: 1.5px solid #333;
    background: #f5f5f5;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
.cert-photo img  { width: 100%; height: 100%; object-fit: cover; }
.cert-photo span { font-size: 10px; color: #aaa; text-align: center; line-height: 1.5; }

/* ── Divider ── */
.divider {
    margin: 12px 0 4px;
    border: none;
    border-top: 2.5px solid var(--dark);
}
.divider-thin {
    border: none;
    border-top: 1px solid var(--gold);
    margin: 3px 0 14px;
}

/* ── Title ── */
.cert-title {
    text-align: center; margin-bottom: 18px;
}
.cert-title-inner {
    font-family: 'Times New Roman', Times, serif;
    font-size: 36px; font-weight: 900;
    color: var(--dark);
    letter-spacing: 3px;
    text-transform: uppercase;
    line-height: 1;
    text-decoration: underline;
    text-underline-offset: 5px;
    text-decoration-thickness: 2px;
}

/* ── Body text ── */
.cert-text {
    font-size: 15.5px;
    line-height: 1.9;
    text-align: justify;
    flex: 1;
}
.cert-text p { margin-bottom: 12px; }
strong { font-weight: 900; font-style: normal; color: #000; }

/* ── Records ── */
.cert-record {
    margin-top: 14px;
    font-size: 15px;
    line-height: 1.9;
}
.cert-record-title { margin-bottom: 2px; }

/* ── Signature row ── */
.cert-sig-row {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-top: 36px;
}
.cert-sig-block { text-align: center; min-width: 160px; }
.cert-sig-line  { border-top: 1.5px solid #333; width: 100%; margin-bottom: 5px; }
.cert-sig-name  { font-size: 13.5px; font-weight: 700; }
.cert-sig-role  { font-size: 12px; color: #555; }

/* Issue date left-align */
.cert-sig-block.left { text-align: left; }
.cert-sig-block.left .cert-sig-line { width: 140px; }

@media print {
    @page { size: A4 landscape; margin: 0; }
    body   { background: white; }
    #toolbar { display: none !important; }
    #wrapper { margin-top: 0; padding: 0; background: white; justify-content: flex-start; }
    .page  { box-shadow: none; min-height: 100vh; width: 297mm; padding: 28px 72px 32px; }
}
</style>
</head>
<body>

<div id="toolbar">
    <h1>{{ $certificate->certificate_type_label }} — {{ $certificate->student_name }} ({{ $certificate->certificate_number }})</h1>
    <a href="{{ route('certificates.index') }}" class="btn-back" style="text-decoration:none;">← Back</a>
    <button class="btn-print" onclick="window.print()">🖨 Print</button>
</div>

<div id="wrapper">
<div class="page">

    {{-- ── Header ── --}}
    <div class="cert-header">
        <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="cert-logo">

        <div class="cert-school-center">
            <div class="cert-school-name">{{ $siteSettings->get('site_name_en', 'School') }}</div>
            <div class="cert-school-sub">{{ $siteSettings->get('site_address_en', $siteSettings->localized('site_address', '')) }}</div>
            <div class="cert-school-estd">Estd.: {{ $siteSettings->get('school_estd', '2017 B.S.') }}</div>
            <div class="cert-no-block">No.: {{ $certificate->certificate_number }}</div>
        </div>

        <div class="cert-photo">
            <span>Affix<br>Photo<br>Here</span>
        </div>
    </div>

    <hr class="divider">
    <hr class="divider-thin">

    {{-- ── Title ── --}}
    <div class="cert-title">
        <span class="cert-title-inner">{{ $certificate->certificate_type_label }}</span>
    </div>

    {{-- ── Body ── --}}
    @php
        $isFemale   = strtolower($certificate->gender ?? '') === 'female';
        $pronHe     = $isFemale ? 'She'      : 'He';
        $pronHis    = $isFemale ? 'Her'      : 'His';
        $pronHim    = $isFemale ? 'her'      : 'him';
        $pronHeShe  = $isFemale ? 'she'      : 'he';
        $sonDaughter= $isFemale ? 'daughter' : 'son';
        $mrMs       = $isFemale ? 'Ms.'      : 'Mr.';
        $hisHer     = $isFemale ? 'her'      : 'his';
        $memberDob  = $certificate->member?->dob;
        $dobText    = $memberDob ? $memberDob->format('d F Y') : '—';
        $district = $certificate->member?->permanent_district ?? '';
        $address  = $certificate->address ?: '';
        if ($district && $address && !str_contains($address, $district)) {
            $address = rtrim($address, ', ') . ', ' . $district . ' District';
        } elseif ($district && !$address) {
            $address = $district . ' District';
        }
        $addressDisplay = $address ?: '..............................';
    @endphp

    <div class="cert-text">
        <p>
            This is to certify that <strong>{{ $mrMs }} {{ $certificate->student_name }}</strong>,
            {{ $sonDaughter }} of <strong>Mr. {{ $certificate->parent_name ?: '..............................' }}</strong>
            an inhabitant of <strong>{{ $addressDisplay }}</strong>,
            was a Bonafide student of this school.
        </p>

        <p>
            {{ $pronHe }} has
            @if($certificate->certificate_type === 'provisional') provisionally passed @else passed @endif
            <strong>{{ $certificate->exam_name ?: '..............................' }}</strong>
            @if($certificate->division_gpa) with <strong>{{ $certificate->division_gpa }}</strong>@endif
            in the year <strong>{{ $certificate->pass_year_bs ?: '........' }}&nbsp;B.S.
            @if($certificate->pass_year_ad)({{ $certificate->pass_year_ad }}&nbsp;A.D.)@endif</strong>.
            {{ $pronHis }} character was <strong>{{ $certificate->character_description ?: '..................' }}</strong>
            during the period {{ $pronHeShe }} studied in this school.
        </p>

        <p>I know nothing against of {{ $hisHer }} moral character. I wish {{ $pronHim }} every success in the academic and professional endeavours.</p>

        <div class="cert-record">
            <div class="cert-record-title">According to the school record, {{ $hisHer }}:</div>
            <div>1.&nbsp; Registration No. : <strong>{{ $certificate->registration_no ?: '—' }}</strong></div>
            <div>2.&nbsp; Date of Birth : <strong>{{ $dobText }}</strong></div>
            <div>3.&nbsp; Symbol No. : <strong>{{ $certificate->symbol_no ?: '—' }}</strong></div>
        </div>
    </div>

    {{-- ── Signature row ── --}}
    <div class="cert-sig-row">
        <div class="cert-sig-block left">
            <div class="cert-sig-line"></div>
            <div class="cert-sig-name">{{ $certificate->issue_date?->format('M d, Y') }}</div>
            <div class="cert-sig-role">Date of Issue</div>
        </div>

        <div class="cert-sig-block">
            <div class="cert-sig-line"></div>
            <div class="cert-sig-name">{{ $siteSettings->get('principal_name', 'Principal') }}</div>
            <div class="cert-sig-role">Principal</div>
            <div class="cert-sig-role">{{ $siteSettings->get('site_name_en', 'School') }}</div>
        </div>
    </div>

</div>{{-- /.page --}}
</div>{{-- /#wrapper --}}

</body>
</html>
