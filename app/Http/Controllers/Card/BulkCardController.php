<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;

use App\Models\Card\Organization;
use App\Models\Card\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;

class BulkCardController extends Controller
{
    const CARD_W   = 54.0;
    const CARD_H   = 85.6;
    const MARGIN_X = 5.5;
    const MARGIN_Y = 12.0;
    const GAP_X    = 4.0;
    const GAP_Y    = 8.0;
    const COLS     = 5;
    const ROWS     = 2;

    public function index(Request $request)
    {
        $filterOptions = $this->buildFilterOptions();

        $query = Student::query();

        if ($request->filled('stream')) {
            $query->where('stream', $request->stream);
        }
        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }
        if ($request->filled('type')) {
            if ($request->type === 'staff_teacher') {
                $query->whereIn('member_type', ['staff', 'teacher']);
            } else {
                $query->where('member_type', $request->type);
            }
        }
        if ($request->filled('q')) {
            $this->applySearch($query, $request->string('q')->toString());
        }
        if ($request->filled('print_status')) {
            if ($request->print_status === 'printed') {
                $query->whereNotNull('card_printed_at')
                      ->whereColumn('card_printed_at', '>=', 'updated_at');
            } elseif ($request->print_status === 'pending') {
                $query->where(function ($q) {
                    $q->whereNull('card_printed_at')
                      ->orWhereColumn('card_printed_at', '<', 'updated_at');
                });
            }
        }

        $students = $this->applyBulkPrintOrder($query)->get();

        return view('card.cards.bulk-print', compact('students', 'filterOptions'));
    }

    /** Search members from print preview so operators can add missed cards. */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = Student::query();
        $this->applySearch($query, $validated['q']);

        $students = $this->applyBulkPrintOrder($query)->limit(12)->get();

        return response()->json([
            'students' => $students->map(fn (Student $student) => [
                'id'         => $student->id,
                'name'       => $student->full_name,
                'roll'       => $student->roll_number,
                'department' => $student->department_label,
                'section'    => $student->section,
                'photo_url'  => $student->photo_url,
                'render_url' => $this->renderUrl($student, 'id'),
            ])->values(),
        ]);
    }

    /**
     * Browser print preview — opens in a new tab.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'card_types'    => 'required|array|min:1',
            'card_types.*'  => 'in:id,library,bus',
        ]);

        $query = Student::whereIn('students.id', $request->student_ids);
        $students = $this->applyBulkPrintOrder($query)->get();
        $cardTypes = $request->card_types;
        $cards     = $this->buildCardList($students, $cardTypes);

        if (empty($cards)) {
            return back()->with('error', 'No cards available for the selected members and types.');
        }

        return view('card.cards.print-preview', [
            'title'      => 'Bulk Print — ' . count($students) . ' member(s)',
            'cards'      => $cards,
            'layout'     => $this->buildLayout(count($cards)),
            'studentIds' => $request->student_ids,
        ]);
    }

    /**
     * Mark cards as printed via AJAX (called from print-preview page).
     */
    public function markPrinted(Request $request)
    {
        $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
        ]);

        $query = Student::whereIn('students.id', $request->student_ids);
        $query->update(['card_printed_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * Download bulk PDF.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'card_types'    => 'required|array|min:1',
            'card_types.*'  => 'in:id,library,bus',
        ]);

        $query = Student::whereIn('students.id', $request->student_ids);
        $students = $this->applyBulkPrintOrder($query)->get();
        $cardTypes = $request->card_types;

        // Mark as printed
        $markPrintedQuery = Student::whereIn('students.id', $students->pluck('id'));
        $markPrintedQuery->update(['card_printed_at' => now()]);

        $pdf = Pdf::loadView('card.cards.bulk-output', compact('students', 'cardTypes'))
                  ->setPaper('a4', 'landscape');

        return $pdf->download('bulk_cards_' . now()->format('Ymd_His') . '.pdf');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function buildCardList($students, array $cardTypes): array
    {
        $cards = [];

        foreach ($students as $student) {
            foreach ($cardTypes as $type) {
                if ($type !== 'id') continue; // only ID cards supported

                $cards[] = [
                    'student'    => $student,
                    'type'       => $type,
                    'render_url' => $this->renderUrl($student, $type),
                ];
            }
        }

        return $cards;
    }

    private function applyBulkPrintOrder($query)
    {
        return $query
            ->orderByRaw('CASE WHEN students.card_printed_at IS NULL OR students.card_printed_at < students.updated_at THEN 0 ELSE 1 END')
            ->orderByDesc('students.updated_at')
            ->orderByRaw("CASE WHEN students.stream IS NULL OR students.stream = '' THEN 1 ELSE 0 END")
            ->orderBy('students.stream')
            ->orderBy('students.first_name')
            ->orderBy('students.middle_name')
            ->orderBy('students.last_name')
            ->orderBy('students.roll_number');
    }

    private function applySearch($query, string $term): void
    {
        $term = trim($term);
        $nameParts = collect(preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY))
            ->take(4)
            ->values();

        $query->where(function ($q) use ($term, $nameParts) {
            $q->where('students.first_name', 'like', "%{$term}%")
              ->orWhere('students.middle_name', 'like', "%{$term}%")
              ->orWhere('students.last_name', 'like', "%{$term}%")
              ->orWhere('students.roll_number', 'like', "%{$term}%")
              ->orWhere('students.mobile', 'like', "%{$term}%");

            if ($nameParts->count() > 1) {
                $q->orWhere(function ($nameQuery) use ($nameParts) {
                    foreach ($nameParts as $part) {
                        $nameQuery->where(function ($partQuery) use ($part) {
                            $partQuery->where('students.first_name', 'like', "%{$part}%")
                                ->orWhere('students.middle_name', 'like', "%{$part}%")
                                ->orWhere('students.last_name', 'like', "%{$part}%");
                        });
                    }
                });
            }
        });
    }

    private function renderUrl(Student $student, string $type): string
    {
        $view = match ($type) {
            'id'      => 'card.cards.id-card',
            'library' => 'card.cards.library-card',
            'bus'     => 'card.cards.bus-pass',
            default   => 'card.cards.id-card',
        };
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

    private function buildFilterOptions(): array
    {
        if (Schema::hasTable('organizations') && Schema::hasTable('departments') && Schema::hasTable('sections')) {
            $organizations = Organization::where('is_active', true)
                ->with(['activeDepartments.activeSections'])
                ->orderBy('name')
                ->get();

            return $organizations->mapWithKeys(fn($org) => [
                $org->slug => [
                    'label'   => $org->name,
                    'streams' => $org->activeDepartments
                        ->mapWithKeys(fn($dept) => [
                            $dept->name => $dept->activeSections->pluck('name')->values()->all(),
                        ])->all(),
                ],
            ])->all();
        }

        return [];
    }
}
