<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;

use App\Models\Card\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CardController extends Controller
{
// Update these constants in both controllers
    const CARD_W   = 54.0;
    const CARD_H   = 85.6;
    const MARGIN_X = 5.5;  // Centered: (297 - 5*54 - 4*4) / 2 = 5.5mm
    const MARGIN_Y = 12.0; // Adjusted for portrait fitting
    const GAP_X    = 4.0;
    const GAP_Y    = 8.0;
    const COLS     = 5;
    const ROWS     = 2;

    public function preview(Student $student, string $type)
    {
        return view($this->resolveView($type), compact('student'));
    }

    /**
     * Render — returns raw card HTML for use as an iframe src.
     * Route: GET /cards/{student}/render/{type}
     */
    public function render(Student $student, string $type, Request $request)
    {
        $flip = $request->boolean('flip', false);
        $view = $this->resolveView($type);
        $viewPath = resource_path('views/' . str_replace('.', '/', $view) . '.blade.php');
        $viewVersion = is_file($viewPath) ? filemtime($viewPath) : time();
        $etag = '"' . md5($student->updated_at->timestamp . $type . $viewVersion . ($flip ? '1' : '0')) . '"';

        // Return 304 if browser already has the latest version cached
        if ($request->header('If-None-Match') === $etag) {
            return response('', 304);
        }

        $html = view($view, compact('student', 'flip'))->render();

        return response($html, 200, [
            'Content-Type'  => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
            'ETag'          => $etag,
        ]);
    }

    public function download(Student $student, string $type)
    {
        $pdf = Pdf::loadView($this->resolveView($type), compact('student'))
                  ->setPaper([0, 0, 241.89, 153.07]);

        return $pdf->download(str($student->full_name)->slug('_') . "_{$type}_card.pdf");
    }

    public function printSingle(Student $student, string $type)
    {
        $this->resolveView($type);

        $cards = [[
            'student'    => $student,
            'type'       => $type,
            'render_url' => $this->renderUrl($student, $type),
        ]];

        return view('card.cards.print-preview', [
            'title'  => $student->full_name . ' — ' . ucfirst($type) . ' Card',
            'cards'  => $cards,
            'layout' => $this->buildLayout(1),
            'studentIds' => [$student->id],
        ]);
    }

    public function printAll(Student $student)
    {
        $cards = [[
            'student'    => $student,
            'type'       => 'id',
            'render_url' => $this->renderUrl($student, 'id'),
        ]];

        if ($student->has_library_card) {
            $cards[] = [
                'student'    => $student,
                'type'       => 'library',
                'render_url' => $this->renderUrl($student, 'library'),
            ];
        }

        if ($student->has_bus_pass) {
            $cards[] = [
                'student'    => $student,
                'type'       => 'bus',
                'render_url' => $this->renderUrl($student, 'bus'),
            ];
        }

        return view('card.cards.print-preview', [
            'title'  => $student->full_name . ' — All Cards',
            'cards'  => $cards,
            'layout' => $this->buildLayout(count($cards)),
            'studentIds' => [$student->id],
        ]);
    }

    public function downloadAll(Student $student)
    {
        $pdf = Pdf::loadView('card.cards.all-cards', compact('student'))->setPaper('a4');
        return $pdf->download(str($student->full_name)->slug('_') . '_all_cards.pdf');
    }

    private function resolveView(string $type): string
    {
        return match ($type) {
            'id'      => 'card.cards.id-card',
            'library' => 'card.cards.library-card',
            'bus'     => 'card.cards.bus-pass',
            default   => abort(404),
        };
    }

    private function renderUrl(Student $student, string $type): string
    {
        $view = $this->resolveView($type);
        $viewPath = resource_path('views/' . str_replace('.', '/', $view) . '.blade.php');
        $version = is_file($viewPath) ? filemtime($viewPath) : time();

        return route('cards.render', [$student, $type, 'v' => $version]);
    }

    private function buildLayout(int $count): array
    {
        $perPage   = self::COLS * self::ROWS;
        $positions = [];

        for ($i = 0; $i < $count; $i++) {
            $posOnPage = $i % $perPage;
            $col       = $posOnPage % self::COLS;
            $row       = intdiv($posOnPage, self::COLS);

            $positions[] = [
                'x'    => self::MARGIN_X + $col * (self::CARD_W + self::GAP_X),
                'y'    => self::MARGIN_Y + $row * (self::CARD_H + self::GAP_Y),
                'page' => intdiv($i, $perPage),
            ];
        }

        return [
            'positions' => $positions,
            'card_w'    => self::CARD_W,
            'card_h'    => self::CARD_H,
            'margin_x'  => self::MARGIN_X,
            'margin_y'  => self::MARGIN_Y,
            'gap_x'     => self::GAP_X,
            'gap_y'     => self::GAP_Y,
            'cols'      => self::COLS,
            'rows'      => self::ROWS,
            'per_page'  => $perPage,
        ];
    }
}
