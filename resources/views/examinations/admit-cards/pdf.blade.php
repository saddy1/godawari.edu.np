@php $isPdf = true; @endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@include('examinations.admit-cards._card-styles')

@page { size: A4 portrait; margin: 10mm; }
.page-sheet:not(:last-child) { page-break-after: always; }
</style>
</head>
<body>
@forelse($roster->chunk($count) as $page)
    <div class="page-sheet">
        @foreach($page as $row)
            <div class="slot" style="height:{{ round(277 / $count, 2) }}mm">
                @include('examinations.admit-cards._card', ['student' => $row['student'], 'symbolNo' => $row['symbol_no'], 'subjects' => $row['subjects'], 'department' => $row['department']])
            </div>
        @endforeach
    </div>
@empty
    <div class="page-sheet">No students have a symbol number assigned yet.</div>
@endforelse
</body>
</html>
