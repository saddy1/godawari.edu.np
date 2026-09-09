@php
    $orgRecord = $examination->organization;
    $logoPath = $orgRecord->type === 'school' ? ($orgRecord->stampAsset->path ?? null) : ($orgRecord->logoAsset->path ?? null);
    $signaturePath = $orgRecord->signatureAsset->path ?? null;
    $academicYearName = $examination->academicYear->name ?? '';
    $siteSettings = $siteSettings ?? app(\App\Support\SiteSettings::class);

    $dobLabel = $student->dob_bs ?: ($student->dob ? $student->dob->format('Y-m-d') : '—');
    $sectionName = $student->academicSection->name ?? $student->section ?? '—';
    $gradeLevel = trim(($department->name ?? $student->stream ?? '—').'-'.$academicYearName, '-');

    $formatBs = function ($date) {
        if (! $date) return 'To be announced';
        $date = \Carbon\Carbon::parse($date);
        $bs = app(\App\Http\Controllers\Hajiri\NepaliCalendarController::class)->ad_2_bs($date->year, $date->month, $date->day);
        return $bs ? sprintf('%04d-%02d-%02d', $bs['year'], $bs['month'], $bs['date']) : '—';
    };
    $scheduleRows = collect();
    foreach ($subjects as $subject) {
        $offering = $subject->offering;
        $subjectModel = $offering->subject;
        $theoryDate = $subject->exam_date;
        $scheduleRows->push([
            'name' => $subjectModel->name,
            'code' => $subjectModel->code,
            'date' => $theoryDate,
        ]);

    }
    $scheduleRows = $scheduleRows->sortBy(fn ($row) => $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('Y-m-d') : '9999-12-31')->values();
@endphp
<div class="admit-card">
    <table class="ac-header"><tr>
        <td class="ac-logo">@if($logoPath && is_file(public_path($logoPath)))<img src="{{ (($isPdf ?? false) && is_file(public_path($logoPath)) ? public_path($logoPath) : asset($logoPath)) }}" alt="{{ $orgRecord->type === 'school' ? 'School stamp' : 'Logo' }}">@endif</td>
        <td class="ac-title">
            <h1>{{ $orgRecord->name }}</h1>
            <p class="ac-address">{{ $siteSettings->localized('site_address', '') }}</p>
            <p class="ac-exam-name">{{ Str::upper($examination->name) }}</p>
            <p class="ac-admit">ADMIT CARD</p>
        </td>
        <td class="ac-logo"></td>
    </tr></table>

    <table class="ac-meta">
        <tr><td class="ac-label">Symbol No.</td><td class="ac-value">{{ $symbolNo }}</td><td class="ac-label">Date of Birth</td><td class="ac-value">{{ $dobLabel }}</td></tr>
        <tr><td class="ac-label">Registration No.</td><td class="ac-value">{{ $student->registration_no ?: '—' }}</td><td class="ac-label"></td><td class="ac-value"></td></tr>
        <tr><td class="ac-label">Name of Student</td><td class="ac-value" colspan="3">{{ $student->full_name }}</td></tr>
        <tr><td class="ac-label">Grade/Level</td><td class="ac-value">{{ $gradeLevel }}</td><td class="ac-label">Section</td><td class="ac-value">{{ $sectionName }}</td></tr>
        <tr><td class="ac-label">College Roll No.</td><td class="ac-value" colspan="3">{{ $student->roll_number ?: '—' }}</td></tr>
    </table>

    <table class="ac-subjects">
        <thead><tr><th>S.N.</th><th>Subject Name</th><th>Subject Code</th><th>Date (BS)</th></tr></thead>
        <tbody>
        @forelse($scheduleRows as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td class="ac-subject-name">{{ Str::upper($row['name']) }}</td>
                <td>{{ $row['code'] }}</td>
                <td>{{ $formatBs($row['date']) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="ac-empty">No subjects configured for this student yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    <table class="ac-footer"><tr>
        <td class="ac-sign"><span class="ac-sign-line"></span>Signature of Student</td>
        <td class="ac-sign"><span class="ac-sign-line"></span>Co-ordinator</td>
        <td class="ac-sign">
            @if($signaturePath)<img src="{{ (($isPdf ?? false) && is_file(public_path($signaturePath)) ? public_path($signaturePath) : asset($signaturePath)) }}" alt="Signature" class="ac-sign-img">@endif
            <span class="ac-sign-line"></span>Principal
        </td>
    </tr></table>

    <div class="ac-photo">
        <img src="{{ $student->photo_url }}" alt="Photo">
    </div>
</div>
