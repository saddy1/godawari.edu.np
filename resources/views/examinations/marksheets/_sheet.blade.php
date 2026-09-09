@php
    $orgRecord = $examination->organization;
    $logoPath = $orgRecord->type === 'school' ? ($orgRecord->stampAsset->path ?? null) : ($orgRecord->logoAsset->path ?? null);
    $signaturePath = $orgRecord->signatureAsset->path ?? null;
    $siteSettings = $siteSettings ?? app(\App\Support\SiteSettings::class);
    $academicYearName = $examination->academicYear->name ?? '';

    $dobLabel = ($student->dob_bs ? $student->dob_bs.' B.S.' : '—').($student->dob ? ' ('.$student->dob->format('Y-m-d').' A.D.)' : '');
    $gradeLevel = trim(($department->name ?? $student->stream ?? '—').'-'.$academicYearName, '-');
@endphp
<div class="gs-sheet">
    <table class="gs-header"><tr>
        <td class="gs-logo">@if($logoPath && is_file(public_path($logoPath)))<img src="{{ (($isPdf ?? false) && is_file(public_path($logoPath)) ? public_path($logoPath) : asset($logoPath)) }}" alt="{{ $orgRecord->type === 'school' ? 'School stamp' : 'Logo' }}">@endif</td>
        <td class="gs-title">
            <h1>{{ $orgRecord->name }}</h1>
            <p class="gs-address">{{ $siteSettings->localized('site_address', '') }}</p>
            <p class="gs-doc-title">GRADE - SHEET</p>
        </td>
        <td class="gs-logo"></td>
    </tr></table>

    <table class="gs-info">
        <tr><td colspan="3">THE FOLLOWING ARE THE GRADE(S) OBTAINED BY: <b class="gs-dotted">{{ Str::upper($student->full_name) }}</b></td></tr>
        <tr><td colspan="3">DATE OF BIRTH: <b class="gs-dotted">{{ $dobLabel }}</b></td></tr>
        <tr><td>REGISTRATION NO: <b>{{ $student->registration_no ?: '—' }}</b></td><td>SYMBOL NO: <b>{{ $symbol_no }}</b></td><td>GRADE: <b>{{ $gradeLevel }}</b></td></tr>
        <tr><td>IN THE <b>{{ Str::upper($examination->name) }}</b></td><td colspan="2">EXAMINATION CONDUCTED IN <b>{{ $academicYearName }} B.S.</b></td></tr>
        <tr><td colspan="3">ARE GIVEN BELOW.</td></tr>
    </table>

    <table class="gs-table">
        <thead><tr><th>Subject Code</th><th>Subjects</th><th>Credit Hour</th><th>Grade Point</th><th>Grade</th><th>Remarks</th></tr></thead>
        <tbody>
        @forelse($subjectRows as $row)
            <tr>
                <td class="num">{{ $row['code'] }}</td>
                <td>{{ Str::upper($row['name']) }} ({{ $row['label'] }})</td>
                <td class="num">{{ $row['credit_hour'] ?: '—' }}</td>
                <td class="num">{{ $row['point'] !== null ? number_format($row['point'], 2) : '—' }}</td>
                <td class="num">{{ $row['grade'] ?? '—' }}</td>
                <td>{{ $row['remarks'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#888">No subjects configured for this student yet.</td></tr>
        @endforelse
        </tbody>
        <tfoot><tr><td colspan="6" class="gs-gpa">GRADE POINT AVERAGE (GPA): {{ $gpa !== null ? number_format($gpa, 2) : '—' }}</td></tr></tfoot>
    </table>

    <div class="gs-extra">
        <p>EXTRA CREDIT SUBJECTS</p>
        <table class="gs-extra-box"><tr>
            @if($extraSubjects->isNotEmpty())
                @foreach($extraSubjects as $row)<td>{{ Str::upper($row['name']) }}<br>{{ $row['grade'] ?? '—' }} ({{ $row['point'] !== null ? number_format($row['point'], 2) : '—' }})</td>@endforeach
            @else
                <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
            @endif
        </tr></table>
    </div>

    <div class="gs-footer">
        <div class="left">
            PREPARED BY: <span class="gs-dotted">&nbsp;</span><br>
            CHECKED BY: <span class="gs-dotted">&nbsp;</span><br>
            DATE OF ISSUE: {{ now()->format('d F, Y') }}
        </div>
    </div>

    <div class="gs-sign-block">
        @if($signaturePath)<img src="{{ (($isPdf ?? false) && is_file(public_path($signaturePath)) ? public_path($signaturePath) : asset($signaturePath)) }}" alt="Signature" class="gs-sign-img">@else<span class="gs-sign-line"></span>@endif
        PRINCIPAL
    </div>

    <p class="gs-note">
        <b>NOTE:</b> ONE CREDIT HOUR EQUALS 32 WORKING HOURS.<br>
        <b>INTERNAL (IN):</b> THIS COVERS THE PARTICIPATION, PRACTICAL/PROJECT WORKS, COMMUNITY WORKS, INTERNSHIP, PRESENTATIONS &amp; TERMINAL EXAMINATIONS.<br>
        <b>THEORY (TH):</b> THIS COVERS WRITTEN &amp; EXTERNAL EXAMINATION<br><br>
        <b>ABS</b> = ABSENT &nbsp;&nbsp;&nbsp; <b>W</b> = WITHHELD
    </p>
</div>
