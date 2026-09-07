@php
    $printPages = collect();
    foreach ($sheets as $sheet) {
        $chunks = $sheet['students']->chunk($rowsPerPage);
        if ($chunks->isEmpty()) $chunks = collect([collect()]);
        foreach ($chunks as $chunkIndex => $pageStudents) {
            $printPages->push([
                'department' => $sheet['department'], 'section' => $sheet['section'],
                'section_students' => $sheet['students'], 'attendance' => $sheet['attendance'],
                'students' => $pageStudents, 'start_number' => $chunkIndex * $rowsPerPage,
                'section_page' => $chunkIndex + 1, 'section_pages' => $chunks->count(),
            ]);
        }
    }
    $logo = $organization?->logoAsset?->path;
    $logoUrl = null;
    if ($logo && (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://'))) {
        $logoUrl = $logo;
    } elseif ($logo && is_file(public_path(ltrim($logo, '/')))) {
        $logoUrl = asset(ltrim($logo, '/'));
    }
    $organizationInitials = collect(preg_split('/\s+/u', trim($organization->name)))
        ->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    $registerRowHeight = number_format(225 / $rowsPerPage, 3, '.', '');
    $scopeLabel = $section?->name ? $department->name.' · '.$section->name : ($department ? $department->name.' · All sections' : 'All faculties / classes · All sections');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{$organization->name}} · {{$scopeLabel}} · {{$monthLabel}} {{$bsYear}} Attendance</title>
    <style>
        @page{size:A4 portrait;margin:8mm}:root{--register-row-height:{{$registerRowHeight}}mm}*{box-sizing:border-box}html,body{margin:0;background:#edf1ef;color:#17251d;font-family:Arial,"Noto Sans Devanagari",sans-serif}
        .toolbar{position:sticky;top:0;z-index:10;display:flex;align-items:center;justify-content:space-between;padding:10px 18px;background:#0f4c54;color:#fff;box-shadow:0 2px 12px #0003}.toolbar button{border:0;border-radius:9px;background:#e2a024;padding:9px 16px;font-weight:900;cursor:pointer}
        .page{width:194mm;min-height:281mm;margin:6mm auto;padding:2.5mm;background:#fff;box-shadow:0 3px 16px #0002;break-after:page;page-break-after:always}.page:last-child{break-after:auto;page-break-after:auto}
        .head{display:grid;grid-template-columns:13mm 1fr 18mm;align-items:center;border-bottom:.55mm solid #154f32;padding:0 .5mm 1.4mm}.head-copy{display:grid;gap:.35mm}.logo-mark{display:grid;width:11mm;height:11mm;place-items:center;overflow:hidden;border:.25mm solid #aec2b6;border-radius:2mm;background:#f2f7f3;color:#154f32;font-size:7px;font-weight:900}.logo{display:block;width:100%;height:100%;object-fit:contain}.institution{text-align:center;color:#154f32;font-size:13px;line-height:1.05;font-weight:900}.address,.meta{text-align:center;color:#53645b;font-size:5.5px;line-height:1.15;font-weight:700}.title{text-align:center;font-size:8px;line-height:1.1;font-weight:900;letter-spacing:.3px}.page-no{text-align:right;font-size:5.5px;line-height:1.2;font-weight:800;color:#66756d}
        .scope{display:grid;grid-template-columns:1.2fr 1fr 1fr 1fr;margin-top:1mm;border:.25mm solid #405047;background:#f2f7f3}.scope div{min-width:0;padding:.85mm 1.6mm;border-right:.25mm solid #a6b0aa;font-size:5px;line-height:1.1}.scope div:last-child{border:0}.scope b{display:block;margin-top:.35mm;font-size:6px;color:#17251d;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        table{width:100%;margin-top:1mm;border-collapse:collapse;table-layout:fixed}th,td{border:.22mm solid #273a30;padding:.2mm;text-align:center;vertical-align:middle;font-size:4.8px}th{height:4.4mm;background:#e8f1eb;font-weight:900}.sn{width:6mm}.code{width:17mm;padding-left:.8mm;text-align:left;font-size:5.8px;font-weight:900}.student{width:40mm;padding-left:.8mm;text-align:left;font-size:8px;font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.day{width:auto}.student-row{height:var(--register-row-height)}.total-label{text-align:right;padding-right:1.5mm;font-size:5.5px;font-weight:900;background:#f4f6f5}.total-cell{height:3.5mm;font-size:4.8px;font-weight:900}
        .foot{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14mm;margin-top:7mm}.sign{border-top:.25mm solid #4d5d54;padding-top:1mm;text-align:center;font-size:5.5px;font-weight:800}.legend{margin-top:1.2mm;padding-left:.5mm;font-size:5px;color:#68776f}.empty{height:150mm;display:grid;place-items:center;text-align:center;font-weight:900;color:#718078}
        @media print{html,body{background:#fff}.toolbar{display:none}.page{width:auto;min-height:auto;margin:0;padding:0;box-shadow:none}}
    </style>
</head>
<body>
<div class="toolbar"><strong>{{$organization->name}} · {{$scopeLabel}} · {{$sheets->count()}} section{{ $sheets->count()===1?'':'s' }}</strong><button type="button" onclick="window.print()">Print / Save PDF</button></div>

@foreach($printPages as $printPage)
@php
    $pageDepartment=$printPage['department']; $pageSection=$printPage['section'];
    $pageStudents=$printPage['students']; $sectionStudents=$printPage['section_students'];
    $pageAttendance=$printPage['attendance'];
@endphp
<section class="page">
    <header class="head"><div class="logo-mark">@if($logoUrl)<img class="logo" src="{{$logoUrl}}" alt="{{$organization->name}} logo" onerror="this.remove();this.parentElement.textContent='{{$organizationInitials}}'">@else{{$organizationInitials}}@endif</div><div class="head-copy"><div class="institution">{{$organization->name}}</div><div class="address">{{$siteSettings->localized('site_address','Nepal')}} · {{$siteSettings->get('school_phone','')}} · {{$siteSettings->get('school_email','')}}</div><div class="title">MONTHLY STUDENT ATTENDANCE REGISTER</div><div class="meta">Academic Year {{$academicYear?->name?:'—'}}</div></div><div class="page-no">Page {{$loop->iteration}} / {{$printPages->count()}}<br>Section {{$printPage['section_page']}} / {{$printPage['section_pages']}}</div></header>
    <div class="scope"><div>Faculty / Class<b>{{$pageDepartment->name}}</b></div><div>Section / Group<b>{{$pageSection->name}}{{$pageSection->group_name?' · '.$pageSection->group_name:''}}</b></div><div>Nepali Month<b>{{$monthLabel}} {{$bsYear}} BS</b></div><div>Section students<b>{{$sectionStudents->count()}}</b></div></div>
    @if($pageStudents->isEmpty())
        <div class="empty">No students found in this section.</div>
    @else
        <table>
            <thead><tr><th class="sn">SN</th><th class="code">Student code</th><th class="student">Student name</th>@foreach($days as $day)<th class="day">{{$day['number']}}</th>@endforeach</tr></thead>
            <tbody>@foreach($pageStudents as $student)<tr class="student-row"><td>{{$printPage['start_number']+$loop->iteration}}</td><td class="code">{{$student->roll_number?:$student->registration_no}}</td><td class="student">{{$student->full_name}}</td>@foreach($days as $day)<td>{{$day['ad']?($pageAttendance[$student->id.'|'.$day['ad']]??''):''}}</td>@endforeach</tr>@endforeach</tbody>
            <tfoot>
                <tr><td colspan="3" class="total-label">Total Present</td>@foreach($days as $day)@php $present=$day['ad']?$pageStudents->filter(fn($student)=>in_array($pageAttendance[$student->id.'|'.$day['ad']]??null,['P','L','E'],true))->count():0;@endphp<td class="total-cell">{{$content==='recorded'&&$present?$present:''}}</td>@endforeach</tr>
                <tr><td colspan="3" class="total-label">Total Absent</td>@foreach($days as $day)@php $absent=$day['ad']?$pageStudents->filter(fn($student)=>($pageAttendance[$student->id.'|'.$day['ad']]??null)==='A')->count():0;@endphp<td class="total-cell">{{$content==='recorded'&&$absent?$absent:''}}</td>@endforeach</tr>
            </tfoot>
        </table>
        <div class="legend">{{$content==='recorded'?'P = Present · A = Absent · L = Late · E = Excused · Totals are per printed page.':'Use P = Present and A = Absent. Daily totals are provided at the bottom of each page.'}}</div>
    @endif
    <div class="foot"><div class="sign">Class Teacher</div><div class="sign">Department Head / Coordinator</div><div class="sign">Principal / Campus Chief</div></div>
</section>
@endforeach
</body></html>
