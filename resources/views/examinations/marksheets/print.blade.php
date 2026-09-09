<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Marksheets — {{ $examination->name }}</title>
<style>
@include('examinations.marksheets._sheet-styles')

#toolbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; background: #1e3a5f; color: #fff; display: flex; align-items: center; gap: 12px; padding: 10px 20px; box-shadow: 0 2px 8px rgba(0,0,0,.3); flex-wrap: wrap; }
#toolbar h1 { font-size: 14px; font-weight: 600; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
#toolbar a, #toolbar button { border: none; cursor: pointer; border-radius: 6px; font-size: 13px; font-weight: 600; padding: 8px 18px; text-decoration: none; display: inline-flex; align-items: center; }
.btn-print { background: #c8a951; color: #1e3a5f; }
.btn-back { background: rgba(255,255,255,.15); color: #fff; }
#pages { margin-top: 70px; padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 20px; }
.page-sheet { width: 210mm; min-height: 297mm; background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,.15); }

@media print {
    #toolbar { display: none; }
    #pages { margin-top: 0; padding: 0; }
    .page-sheet { box-shadow: none; margin: 0; page-break-after: always; }
    .page-sheet:last-child { page-break-after: auto; }
}
</style>
</head>
<body>
<div id="toolbar">
    <h1>Marksheets — {{ $examination->name }} ({{ $roster->count() }} students)</h1>
    <a class="btn-back" href="{{ route('admin.examinations.marksheets.index', array_merge(request()->only(['q', 'school_class', 'faculty', 'section']), ['examination' => $examination])) }}">← Back</a>
    <a class="btn-print" href="{{ request('student_id') ? route('admin.examinations.marksheets.download-one', [$examination, request('student_id')]) : route('admin.examinations.marksheets.download', array_merge(request()->only(['q', 'school_class', 'faculty', 'section']), ['examination' => $examination])) }}">Download PDF</a>
    <button class="btn-print" onclick="window.print()">Print</button>
</div>

<div id="pages">
@forelse($roster as $row)
    <div class="page-sheet">
        @include('examinations.marksheets._sheet', $row)
    </div>
@empty
    <div class="page-sheet" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;font-weight:700">No students have a symbol number assigned yet.</div>
@endforelse
</div>
</body>
</html>
