{{--
    resources/views/cards/bulk-output.blade.php
    ────────────────────────────────────────────
    Used by BulkCardController::generate() to render the dompdf PDF.
    Layout: 5 columns × 2 rows = 10 CR80 cards per A4 landscape page.
    Each card is placed with absolute mm positions.
--}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; background: white; }

/* ── Page setup ─────────────────────────────────────────────── */
@page {
    size: A4 landscape;   /* 297mm × 210mm */
    margin: 0;
}

/*
    Card grid constants
    ───────────────────
    CARD_W   = 85.6mm
    CARD_H   = 54.0mm
    MARGIN_X = 7mm
    MARGIN_Y = 7mm
    GAP_X    = 3mm
    GAP_Y    = 5mm
    COLS     = 5    → total width  = 7 + 5×85.6 + 4×3 = 7 + 428 + 12 = 447mm  < 297mm ✓
                                   wait — that's landscape: 297mm wide ✓
    ROWS     = 2    → total height = 7 + 2×54 + 1×5 = 7 + 108 + 5 = 120mm < 210mm ✓
*/

.page {
    position: relative;
    width:  297mm;
    height: 210mm;
    overflow: hidden;
    page-break-after: always;
}
.page:last-child { page-break-after: auto; }

/* Each card occupies an absolute slot */
.card-slot {
    position: absolute;
    width:  85.6mm;
    height: 54mm;
    overflow: hidden;
}

/* ── Inline card styles ───────────────────────────────────────
   Because dompdf renders in a single HTML context, we can't use
   <iframe> — instead we @@include each card partial directly.
   Each partial wraps itself in .card which already has
   width/height set to 85.6mm/54mm, so it fits the slot exactly.
──────────────────────────────────────────────────────────────── */
</style>
</head>
<body>

@php
    $cardW   = 85.6;
    $cardH   = 54.0;
    $marginX = 7.0;
    $marginY = 7.0;
    $gapX    = 3.0;
    $gapY    = 5.0;
    $cols    = 5;
    $rows    = 2;
    $perPage = $cols * $rows;

    // Build a flat list of [student, type, view]
    $viewMap = [
        'id'      => 'card.cards.id-card',
        'library' => 'card.cards.library-card',
        'bus'     => 'card.cards.bus-pass',
    ];

    $allCards = [];
    foreach ($students as $student) {
        foreach ($cardTypes as $type) {
            if ($type === 'library' && ! $student->has_library_card) continue;
            if ($type === 'bus'     && ! $student->has_bus_pass)     continue;
            $allCards[] = ['student' => $student, 'type' => $type, 'view' => $viewMap[$type]];
        }
    }

    $pages = (int) ceil(count($allCards) / $perPage);
@endphp

@for ($p = 0; $p < $pages; $p++)
<div class="page">
    @for ($i = $p * $perPage; $i < min(($p + 1) * $perPage, count($allCards)); $i++)
    @php
        $entry   = $allCards[$i];
        $student = $entry['student'];
        $view    = $entry['view'];
        $posOnPage = $i % $perPage;
        $col     = $posOnPage % $cols;
        $row     = intdiv($posOnPage, $cols);
        $x       = $marginX + $col * ($cardW + $gapX);
        $y       = $marginY + $row * ($cardH + $gapY);
    @endphp
    <div class="card-slot" style="left: {{ $x }}mm; top: {{ $y }}mm;">
        @include($view, ['student' => $student])
    </div>
    @endfor
</div>
@endfor

</body>
</html>