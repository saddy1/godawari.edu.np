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

    <p class="gs-intro">THE FOLLOWING ARE THE GRADE(S) OBTAINED BY: <strong>{{ Str::upper($student->full_name) }}</strong></p>
    <table class="gs-info gs-student-details">
        <tr><td class="gs-field-label">Date of birth</td><td colspan="3" class="gs-field-value">{{ $dobLabel }}</td></tr>
        <tr><td class="gs-field-label">Registration no.</td><td class="gs-field-value">{{ $student->registration_no ?: ' ' }}</td><td class="gs-field-label">Symbol no.</td><td class="gs-field-value">{{ $symbol_no }}</td></tr>
        <tr><td class="gs-field-label">Class / faculty</td><td class="gs-field-value">{{ $department->name ?? $student->stream ?? ' ' }}</td><td class="gs-field-label">Academic year</td><td class="gs-field-value">{{ $academicYearName }} B.S.</td></tr>
    </table>

    <p class="gs-intro gs-exam-statement">IN THE <strong>{{ Str::upper($examination->name) }}</strong> EXAMINATION CONDUCTED IN <strong>{{ $academicYearName }} B.S.</strong>, THE GRADES OBTAINED ARE GIVEN BELOW.</p>

    <table class="gs-table">
        <thead><tr><th>Subject Code</th><th>Subjects</th><th>Credit Hour</th><th>Grade Point</th><th>Grade</th><th>Remarks</th></tr></thead>
        <tbody>
        @forelse($subjectRows as $row)
            <tr>
                <td class="num">{{ $row['code'] }}</td>
                <td>{{ Str::upper($row['name']) }} ({{ $row['label'] }})</td>
                <td class="num">{{ $row['credit_hour'] ? number_format($row['credit_hour'], 2) : '—' }}</td>
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

    <div class="gs-footer">
        <div class="left">
            PREPARED BY:  <span style=" font: bold; font-size: larger;">&nbsp; 0688</span><br>
            CHECKED BY: <span class="gs-dotted">&nbsp;</span><br>
            DATE OF ISSUE: {{ now()->format('d F, Y') }}
        </div>

        <div class="gs-sign-block">
            @if($signaturePath)<img src="{{ (($isPdf ?? false) && is_file(public_path($signaturePath)) ? public_path($signaturePath) : asset($signaturePath)) }}" alt="Signature" class="gs-sign-img">@else<span class="gs-sign-line"></span>@endif
            PRINCIPAL
        </div>
    </div>

    <p class="gs-note">
        <b>NOTE:</b> ONE CREDIT HOUR EQUALS 32 WORKING HOURS.<br>
        <b>INTERNAL (IN):</b> THIS COVERS THE PARTICIPATION, PRACTICAL/PROJECT WORKS, COMMUNITY WORKS, INTERNSHIP, PRESENTATIONS &amp; TERMINAL EXAMINATIONS.<br>
        <b>THEORY (TH):</b> THIS COVERS WRITTEN &amp; EXTERNAL EXAMINATION<br><br>
        <b>ABS</b> = ABSENT &nbsp;&nbsp;&nbsp; <b>W</b> = WITHHELD
    </p>
</div>
