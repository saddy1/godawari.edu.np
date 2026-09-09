@php $isPdf = true; @endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@include('examinations.marksheets._sheet-styles')

@page { size: A4 portrait; margin: 0; }
.page-sheet { width: 190mm; height: 277mm; padding: 0; margin: 10mm; }
.page-sheet:not(:last-child) { page-break-after: always; }
</style>
</head>
<body>
@forelse($roster as $row)
    <div class="page-sheet">
        @include('examinations.marksheets._sheet', $row)
    </div>
@empty
    <div class="page-sheet">No students have a symbol number assigned yet.</div>
@endforelse
</body>
</html>
