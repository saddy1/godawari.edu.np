<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;

use App\Models\Card\Organization;
use App\Models\Card\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromoteController extends Controller
{
    // Highest class after which students are considered graduates
    public const MAX_CLASS = 12;

    // ── Show promotion dashboard ──────────────────────────────────────────
    public function index()
    {
        $user = auth()->user();

        $availableClasses = $this->availableClassesFor($user);

        $groups = Student::query()
            ->tap(fn($query) => $user->applyStudentScope($query))
            ->where('organization', 'school')
            ->where('member_type', 'student')
            ->where(fn ($query) => $query->whereNull('stream')->orWhere('stream', '!=', 'Graduated'))
            ->select('stream', 'section', DB::raw('COUNT(*) as count'))
            ->groupBy('stream', 'section')
            ->orderByRaw('stream IS NULL ASC')
            ->orderBy('stream')
            ->orderBy('section')
            ->get()
            ->map(function ($g) use ($availableClasses) {
                $className      = trim($g->stream ?: '(Class not assigned)');
                $suggestedClass = self::suggestPromotion($className);
                $currentClass   = $this->extractClassNumber($className);
                $targetClasses  = strcasecmp($suggestedClass, $className) === 0
                    ? collect()
                    : $availableClasses
                        ->filter(fn ($class) => strcasecmp($class, $suggestedClass) === 0)
                        ->values();

                return [
                    'program'          => $g->stream,
                    'stream'           => $g->stream,
                    'section'          => $g->section,
                    'count'            => $g->count,
                    'class_name'       => $className,
                    'suggested_class'  => $suggestedClass,
                    'target_classes'   => $targetClasses,
                    'is_grad_year'     => $currentClass !== null && $currentClass >= self::MAX_CLASS,
                    'label'            => trim($className . ' ' . ($g->section ?? '')),
                ];
            });

        return view('hr.members.promote', compact('groups'));
    }

    // ── AJAX: students in a class/section ────────────────────────────────
    public function students(\Illuminate\Http\Request $request)
    {
        $stream  = $request->input('stream');
        $section = $request->input('section');
        $q       = trim((string) $request->input('q', ''));
        $user    = auth()->user();

        $students = Student::query()
            ->tap(fn($query) => $user->applyStudentScope($query))
            ->where('organization', 'school')
            ->where('member_type', 'student')
            ->when($stream  !== null, fn ($query) => $query->where('stream',  $stream  ?: null))
            ->when($section !== null, fn ($query) => $query->where('section', $section ?: null))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('first_name', 'like', "%{$q}%")
                          ->orWhere('last_name',  'like', "%{$q}%")
                          ->orWhere('roll_number','like', "%{$q}%");
                });
            })
            ->orderBy('roll_number')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'roll_number', 'stream', 'section', 'photo'])
            ->map(fn (Student $s) => [
                'id'          => $s->id,
                'name'        => trim("{$s->first_name} {$s->middle_name} {$s->last_name}"),
                'roll_number' => $s->roll_number,
                'photo_url'   => $s->photo_url,
            ]);

        return response()->json(['students' => $students]);
    }

    // ── Apply promotion ───────────────────────────────────────────────────
    public function promote(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'groups'               => 'required|array|min:1',
            'groups.*.from_program'=> 'nullable|string|max:100',
            'groups.*.from_stream' => 'nullable|string|max:100',
            'groups.*.from_section'=> 'nullable|string|max:50',
            'groups.*.to_program'  => 'nullable|string|max:100',
            'groups.*.to_section'  => 'nullable|string|max:50',
            'groups.*.action'      => 'required|in:promote,graduate,skip',
            'valid_till'           => 'nullable|date',
            'grad_action'          => 'required|in:mark',
            'student_ids'          => 'nullable|array',
            'student_ids.*'        => 'integer|exists:students,id',
        ]);

        $groups     = $request->input('groups', []);
        $validTill  = $request->input('valid_till');
        $studentIds = $request->input('student_ids', []);
        $availableClasses = $this->availableClassesFor($user);

        foreach ($groups as $index => $group) {
            if (($group['action'] ?? null) !== 'promote') {
                continue;
            }

            $fromStream = trim((string) ($group['from_stream'] ?? ''));
            $toClass    = trim((string) ($group['to_program'] ?? ''));

            if ($toClass === '') {
                throw ValidationException::withMessages([
                    "groups.{$index}.to_program" => 'Choose a target class before promoting students.',
                ]);
            }

            if (strcasecmp($fromStream, $toClass) === 0) {
                throw ValidationException::withMessages([
                    "groups.{$index}.to_program" => 'Target class must be different from the current class.',
                ]);
            }

            $expectedClass = self::suggestPromotion($fromStream);
            $isExpectedNextClass = strcasecmp($expectedClass, $fromStream) !== 0
                && strcasecmp($expectedClass, $toClass) === 0;
            $isAvailable = $availableClasses->contains(fn ($class) => strcasecmp($class, $toClass) === 0);
            if (!$isExpectedNextClass || !$isAvailable) {
                throw ValidationException::withMessages([
                    "groups.{$index}.to_program" => 'Choose the next available class for this student group.',
                ]);
            }
        }

        $promoted  = 0;
        $graduated = 0;

        DB::transaction(function () use ($groups, $validTill, $studentIds, $user, &$promoted, &$graduated) {
            foreach ($groups as $g) {
                if ($g['action'] === 'skip') continue;

                $query = Student::query()
                    ->tap(fn($query) => $user->applyStudentScope($query))
                    ->where('organization', 'school')
                    ->where('member_type', 'student')
                    ->where('stream', $g['from_stream'] ?: null)
                    ->where('section', $g['from_section'] ?: null)
                    ->when(!empty($studentIds), fn ($q) => $q->whereIn('id', $studentIds));

                if ($g['action'] === 'graduate') {
                    $data = ['stream' => 'Graduated', 'program' => 'Graduated', 'section' => null];
                    if ($validTill) $data['valid_till'] = $validTill;
                    $graduated += $query->update($data);
                } else {
                    $data = [
                        'stream'  => $g['to_program'],
                        'program' => $g['to_program'],
                        'section' => $g['to_section'] ?: $g['from_section'] ?: null,
                    ];
                    if ($validTill) $data['valid_till'] = $validTill;
                    $promoted += $query->update($data);
                }
            }
        });

        $msg = [];
        if ($promoted)  $msg[] = "{$promoted} student(s) promoted";
        if ($graduated) $msg[] = "{$graduated} student(s) marked as Graduated";

        return redirect()->route('admin.hr.members.index')
                         ->with('success', implode(', ', $msg) . '.');
    }

    // ── Auto-suggest the next grade without changing the cohort year ─────
    // "Class 5"  → "Class 6"
    // "Grade 10" → "Grade 11"
    // "5"        → "6"
    // "11 Science 2083" → "12 Science 2083"
    // "Science"  → "Science"  (no grade number → unchanged)
    public static function suggestPromotion(string $stream): string
    {
        if ($stream === '' || $stream === 'Graduated') return $stream;

        if (!preg_match_all('/(?<!\d)(\d{1,2})(?!\d)/', $stream, $matches, PREG_OFFSET_CAPTURE)) {
            return $stream;
        }

        foreach ($matches[1] as [$number, $offset]) {
            $grade = (int) $number;
            if ($grade >= 1 && $grade < self::MAX_CLASS) {
                return substr_replace($stream, (string) ($grade + 1), $offset, strlen($number));
            }
        }

        return $stream;
    }

    // ── Extract the class number from a stream string (or null) ──────────
    private function extractClassNumber(string $stream): ?int
    {
        if (preg_match_all('/(?<!\d)(\d{1,2})(?!\d)/', $stream, $matches)) {
            foreach ($matches[1] as $number) {
                $grade = (int) $number;
                if ($grade >= 1 && $grade <= self::MAX_CLASS) {
                    return $grade;
                }
            }
        }
        return null;
    }

    private function availableClassesFor($user)
    {
        $school = Organization::query()
            ->where('slug', 'school')
            ->where('is_active', true)
            ->with('activeDepartments')
            ->first();

        $classes = $school?->activeDepartments->pluck('name') ?? collect();

        if ($classes->isEmpty()) {
            $classes = Student::query()
                ->tap(fn ($query) => $user->applyStudentScope($query))
                ->where('organization', 'school')
                ->where('member_type', 'student')
                ->pluck('stream');
        }

        return $classes
            ->filter()
            ->reject(fn ($class) => strcasecmp($class, 'Graduated') === 0)
            ->unique(fn ($class) => strtolower($class))
            ->sort(fn ($left, $right) => strnatcasecmp($left, $right))
            ->values();
    }
}
