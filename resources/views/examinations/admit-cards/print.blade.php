<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admit Cards — {{ $examination->name }}</title>
<style>
@include('examinations.admit-cards._card-styles')

#toolbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; background: #1e3a5f; color: #fff; display: flex; align-items: center; gap: 12px; padding: 10px 20px; box-shadow: 0 2px 8px rgba(0,0,0,.3); flex-wrap: wrap; }
#toolbar h1 { font-size: 14px; font-weight: 600; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
#toolbar select { border-radius: 6px; border: none; padding: 8px 10px; font-size: 13px; font-weight: 600; }
#toolbar a, #toolbar button { border: none; cursor: pointer; border-radius: 6px; font-size: 13px; font-weight: 600; padding: 8px 18px; text-decoration: none; display: inline-flex; align-items: center; }
.btn-print { background: #c8a951; color: #1e3a5f; }
.btn-back { background: rgba(255,255,255,.15); color: #fff; }
#pages { margin-top: 70px; padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 20px; }
.page-sheet { width: 210mm; min-height: 297mm; background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,.15); padding: 10mm; }

@media print {
    #toolbar { display: none; }
    #pages { margin-top: 0; padding: 0; }
    .page-sheet { box-shadow: none; margin: 0; padding: 10mm; page-break-after: always; }
    .page-sheet:last-child { page-break-after: auto; }
}
</style>
</head>
<body>
<div id="toolbar">
    <h1>Admit Cards — {{ $examination->name }} ({{ $roster->count() }} students)</h1>
    @unless(isset($singleStudent))
    <form method="GET" style="display:flex;gap:8px;align-items:center">
        @foreach(request()->only(['q', 'school_class', 'faculty', 'section']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <select name="count" onchange="this.form.submit()">
            <option value="1" @selected($count===1)>1 per page</option>
            <option value="2" @selected($count===2)>2 per page</option>
            <option value="4" @selected($count===4)>4 per page</option>
        </select>
    </form>
    @endunless
    <a class="btn-back" href="{{ route('admin.examinations.admit-cards.index', array_merge(request()->only(['q', 'school_class', 'faculty', 'section']), ['examination' => $examination])) }}">← Back</a>
    <a class="btn-print" href="{{ isset($singleStudent) ? route('admin.examinations.admit-cards.download-one', [$examination, $singleStudent]) : route('admin.examinations.admit-cards.download', array_merge(request()->only(['q', 'school_class', 'faculty', 'section']), ['examination' => $examination, 'count' => $count])) }}">Download PDF</a>
    <button class="btn-print" onclick="window.print()">Print</button>
</div>

<div id="pages">
@foreach($roster->chunk($count) as $page)
    <div class="page-sheet">
        @foreach($page as $row)
            <div class="slot" style="height:{{ round(277 / $count, 2) }}mm">
                @include('examinations.admit-cards._card', ['student' => $row['student'], 'symbolNo' => $row['symbol_no'], 'subjects' => $row['subjects'], 'department' => $row['department']])
            </div>
        @endforeach
    </div>
@endforeach
@if($roster->isEmpty())
    <div class="page-sheet" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;font-weight:700">No students have a symbol number assigned yet.</div>
@endif
</div>
</body>
</html>
