<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            background: #fff;
        }

        .card {
            width: 54mm;
            height: 85.6mm;
            position: relative;
            overflow: hidden;
            border: 0.5px solid #ddd;
        }

        .card-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .content-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        .photo-container {
            position: absolute;
            top: 18mm;
            left: 17.5mm;
            width: 22mm;
            height: 27mm;
            border: 1px solid #ccc;
            background: #fff;
        }

        .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .valid-till-bar {
            position: absolute;
            top: 16mm;
            right: 1mm;
            width: 10mm;
            height: 30mm;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
        }

        .valid-till-bar span {
            color: #055b0a;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            text-align: center;
        }

        .student-header {
            position: absolute;
            top: 47mm;
            left: 0;
            width: 100%;
            text-align: center;
        }

        .student-name {
            margin-top: 3mm;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #000;
            margin-bottom: 0.5mm;
        }

        .student-roll {
            font-size: 7pt;
            color: #333;
            margin-bottom: 0.5mm;
        }

        .student-program {
            font-size: 8pt;
            font-weight: bold;
            color: #0073e6;
            /* text-transform: uppercase; */
            margin-bottom: 1mm;
        }

        .student-address {
            font-size: 5.8pt;
            color: #444;
            margin-top: 0.5mm;
            padding: 0 3mm;
            text-align: center;
            line-height: 1.3;
        }

        .info-grid {
            position: absolute;
            top: 61mm;
            left: 3mm;
            width: 49mm; /* full usable width; QR (z-index 5) covers any lower-row overlap */
            font-size: 6.5pt;
            line-height: 1.4;
            color: #000;
            overflow: hidden;
        }

        .info-grid table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-grid td {
            vertical-align: top;
            overflow: hidden;
        }

        .info-grid td:first-child {
            font-weight: bold;
            color: #055b0a;
            white-space: nowrap;
            width: 10mm;
        }

        .info-grid td:last-child {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 37mm;
        }

        .stamp-container {
            position: absolute;
            top: 37mm;
            left: 30mm;
            width: 12mm;
            height: 12mm;
            opacity: 0.75;
            z-index: 6;
        }

        .qr-container {
            position: absolute;
            bottom: 4.5mm;
            right: 2mm;
            width: 13mm;
            height: 13mm;
            z-index: 5;
        }

        .qr-container img {
            width: 100%;
            height: 100%;
        }

        .signature-container {
            position: absolute;
            top: 35mm;
            left: 15mm;
            width: 15mm;
            height: auto;
            z-index: 5;
            opacity: 0.9;
        }

        .signature-container img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

    </style>
</head>

<body>

    @php
        use Illuminate\Support\Facades\Cache;

        // ── Cache per-org and per-dept data; same student org reuses one DB hit ──
        $orgRecord = Cache::remember("card_org_{$student->organization}", 300, fn() =>
            \App\Models\Card\Organization::with(['logoAsset', 'signatureAsset', 'stampAsset'])
                ->where('slug', $student->organization)->first()
        );

        $deptRecord = $student->stream
            ? Cache::remember("card_dept_{$student->organization}_{$student->stream}", 300, fn() =>
                \App\Models\Card\Department::whereHas('organization',
                    fn($q) => $q->where('slug', $student->organization))
                    ->where('name', $student->stream)->first()
              )
            : null;

        // 2. Resolve card background — DB-managed with hardcoded fallbacks
        $isSchool = $student->organization === 'school';
        $orgType  = $isSchool ? 'school' : 'college';
        $mtKey    = strtolower($student->member_type);

        // Cache Schema::hasTable (expensive info_schema call) + background record
        $hasBgTable = Cache::remember('card_has_bg_table', 600, fn() =>
            \Illuminate\Support\Facades\Schema::hasTable('card_backgrounds')
        );
        $dbBg = $hasBgTable
            ? Cache::remember("card_bg_{$orgType}_{$mtKey}", 300, fn() =>
                \App\Models\Card\CardBackground::activeFor($orgType, $mtKey))
            : null;

        if ($dbBg) {
            $bgImage = $dbBg->file_path;
        } else {
            $fallbackMap = $isSchool
                ? ['student' => 'erp/card/img/school_student.png', 'staff' => 'erp/card/img/school_staff.png', 'teacher' => 'erp/card/img/school_faculty.png']
                : ['student' => 'erp/card/img/clz_student.png',    'staff' => 'erp/card/img/clz_staff.png',    'teacher' => 'erp/card/img/clz_faculty.png'];
            $bgImage = $fallbackMap[$mtKey] ?? 'erp/card/img/school_student.png';
        }

        // 3. Resolve header text: department overrides org which overrides hardcoded defaults
        if ($isSchool) {
            $orgTitle1 = $orgRecord->name ?? $siteSettings->localized('site_name', config('app.name'));
            $orgTitle2 = '';
            $orgTitle3 = $siteSettings->localized('site_address', '');
            $logoImage = $orgRecord->logo_path ?? 'img/school_logo.jpg';
        } else {
            // Start with org-level defaults
            $orgTitle1 = 'TRIBHUVAN UNIVERSITY';
            $orgTitle2 = $orgRecord->name ?? $siteSettings->localized('site_name', config('app.name'));
            $orgTitle3 = $siteSettings->localized('site_address', '');
            $logoImage = $orgRecord->logo_path ?? 'assets/image/logo.png';

            // Department overrides university name + logo (for multi-university orgs)
            if ($deptRecord && $deptRecord->university) {
                $orgTitle1 = $deptRecord->university;
                $orgTitle2 = $deptRecord->university_college ?? $orgTitle2;
                $logoImage = $deptRecord->university_logo ?? $logoImage;
            }
        }

        // 4. Signature & stamp — from shared asset library, with hardcoded fallbacks
        $signatureImage = $orgRecord?->signatureAsset?->path ?? 'img/omSirLarge.png';
        $stampImage = $orgRecord?->stampAsset?->path ?? 'img/stamp_ioepc.png';

        // Also override org logo from asset if set (for non-department-specific logo)
        if ($orgRecord?->logoAsset?->path && !$deptRecord?->university_logo) {
            $logoImage = $orgRecord->logoAsset->path;
        }

        // 2. Resolve Profile Photo
        $photoSrc = $student->photo_url;

        // QR code — public verification URL, rendered as base64 PNG (works in both iframe and dompdf)
        $qrOptions = new \chillerlan\QRCode\QROptions([
            'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
            'outputBase64'    => true,
            'scale'           => 8,
            'quietzoneSize'   => 1,
        ]);
        $qrDataUri = (new \chillerlan\QRCode\QRCode($qrOptions))
            ->render(route('student.verify', $student->id));

        // Permanent employee — show "Permanent" on card instead of a valid-till date
        // $student->employment_type accessor resolves user->employment->label as fallback
        $empTypeLabel = $student->employment_type ?? '';
        $isPermanent  = str_contains(strtolower($empTypeLabel), 'permanent')
                     || str_contains($empTypeLabel, 'स्थायी');

        // Prefer stored BS dates on the card. AD is only used when the BS
        // counterpart is unavailable. DOI is the first/current print date.
        $dobLabel = $student->dob_bs
            ? ['calendar' => 'BS', 'date' => $student->dob_bs]
            : ($student->dob ? ['calendar' => 'AD', 'date' => $student->dob->format('Y-m-d')] : null);

        $validTillLabel = $student->valid_till_bs
            ? ['calendar' => 'BS', 'date' => $student->valid_till_bs]
            : ($student->valid_till ? ['calendar' => 'AD', 'date' => $student->valid_till->format('Y-m-d')] : null);

        $issueDate = $student->card_printed_at ?? now();
        $issueDateBs = Cache::remember(
            'card_issue_date_bs_'.$issueDate->format('Y-m-d'),
            now()->addYear(),
            function () use ($issueDate) {
                $converted = app(\App\Http\Controllers\Hajiri\NepaliCalendarController::class)
                    ->ad_2_bs((int) $issueDate->format('Y'), (int) $issueDate->format('m'), (int) $issueDate->format('d'));

                return $converted
                    ? sprintf('%04d-%02d-%02d', $converted['year'], $converted['month'], $converted['date'])
                    : null;
            }
        );
        $issueDateLabel = $issueDateBs
            ? ['calendar' => 'BS', 'date' => $issueDateBs]
            : ['calendar' => 'AD', 'date' => $issueDate->format('Y-m-d')];
    @endphp

    <div class="card" @if ($flip ?? false) style="transform: scaleX(-1);" @endif>
        <img src="{{ asset($bgImage) }}" class="card-background" alt="Background">

        <div class="content-layer">

            <div class="header"
                style="display: flex; align-items: center; padding: 1mm; border-bottom: 0.5px solid #055b0a;">
                <img src="{{ asset($logoImage) }}" class="logo" alt="Logo" style="width: 8mm; height: 8mm;">
                <div class="header-text" style="text-align: center; flex: 1; color: #023014;">
                    <h2 style="font-size: 8pt; font-weight: bold; margin: 0; text-transform: uppercase;">
                        {{ $orgTitle1 }}</h2>
                    @if (!empty($orgTitle2))
                        <h2 style="font-size: 8pt; font-weight: bold; margin: 0; color: #055b0a;">{{ $orgTitle2 }}
                        </h2>
                    @endif
                    <h3 style="font-size: 7pt; font-weight: bold; margin: 0; color: #cb910b;">{{ $orgTitle3 }}</h3>
                </div>
            </div>

            <div class="photo-container">
                <img src="{{ $photoSrc }}" class="profile-pic" alt="Profile Photo">
            </div>

            @if ($isPermanent)
                <div class="valid-till-bar">
                    <span style="font-size:12px">Permanent</span>
                </div>
            @elseif ($validTillLabel)
                <div class="valid-till-bar">
                    <span>Valid Till <br> {{ $validTillLabel['date'] }}</span>
                </div>
            @endif

            <div class="student-header">
                <div class="student-name">{{ $student->full_name }}</div>
                <div class="student-roll">
                    ID Number : {{ $student->roll_number }}
                </div>
                @if(in_array(strtolower($student->member_type), ['staff', 'teacher']))
                    @if($student->designation)
                        <div class="student-program">{{ $student->designation }}</div>
                    @endif
                @else
                    <div class="student-program">
                        {{ $student->department_label ?? 'N/A' }}
                    </div>
                @endif
            </div>

            <div class="info-grid" style=" margin-top: 8px;">
                <table>
                    {{-- Email first — sits at the top of the grid (~61mm), safely above the QR zone (~68mm).
                         word-break overrides the td:last-child nowrap so long addresses show in full. --}}
                    @if ($student->email)
                        <tr>
                            <td>Email:</td>
                            <td>{{ $student->email }}</td>
                        </tr>
                    @endif
                    @if ($student->guardian_name && !in_array(strtolower($student->member_type), ['staff', 'teacher']))
                        <tr>
                            <td>Guardian:</td>
                            <td>{{ $student->guardian_name }}</td>
                        </tr>
                    @endif
                    @if ($student->registration_no)
                        <tr>
                            <td>Reg No:</td>
                            <td>{{ $student->registration_no }}</td>
                        </tr>
                    @endif
                    @if ($dobLabel)
                        <tr>
                            <td>DOB:</td>
                            <td>{{ $dobLabel['date'] }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>DOI:</td>
                        <td>{{ $issueDateLabel['date'] }}</td>
                    </tr>
                    @if ($student->citizenship_no)
                        <tr>
                            <td>CTZ No:</td>
                            <td>{{ $student->citizenship_no }}</td>
                        </tr>
                    @endif
                    @if ($student->mobile)
                        <tr>
                            <td>Mobile:</td>
                            <td>{{ $student->mobile }}</td>
                        </tr>
                    @endif
                    @if ($student->address_label)
                        <tr>
                            <td>Address:</td>
                            <td>{{ $student->address_label }}</td>
                        </tr>
                    @endif
                </table>
            </div>

            {{-- Signature --}}
            <div class="signature-container">
                <img src="{{ asset($signatureImage) }}" alt="Signature">
            </div>

            {{-- Stamp — overlapping bottom-right corner of photo --}}
            <div class="stamp-container">
                <img src="{{ asset($stampImage) }}" alt="Stamp" style="width:100%; height:auto;">
            </div>

            {{-- QR code — links to public verification page --}}
            <div class="qr-container">
                <img src="{{ $qrDataUri }}" alt="Verify">
            </div>

        </div>
    </div>
</body>

</html>
