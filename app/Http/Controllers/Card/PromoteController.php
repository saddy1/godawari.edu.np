<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;

use App\Models\Card\Organization;
use App\Models\Card\Section;
use App\Models\Card\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\SubjectEnrollmentService;

class PromoteController extends Controller
{
    // Highest class after which students are considered graduates
    public const MAX_CLASS = 12;

    // ── Show promotion dashboard ──────────────────────────────────────────
    public function index()
    {
        $user = auth()->user();
        $academicOptions = Organization::query()
            ->with('departments.sections')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Organization $organization) => [
                $organization->slug => [
                    'label' => $organization->name,
                    'type' => $organization->type,
                    'academic_systems' => $organization->departments
                        ->where('is_active', true)
                        ->mapWithKeys(fn ($department) => [$department->name => $department->academic_system ?? 'none'])
                        ->all(),
                    'streams' => $organization->departments
                        ->where('is_active', true)
                        ->mapWithKeys(fn ($department) => [
                            $department->name => $department->sections
                                ->where('is_active', true)
                                ->map(fn ($section) => [
                                    'id' => $section->id,
                                    'name' => $section->name,
                                    'group' => $section->group_name,
                                ])->values()->all(),
                        ])->all(),
                ],
            ])->all();

        $organizationSlugs = array_keys($academicOptions);

        $groups = Student::query()
            ->tap(fn($query) => $user->applyStudentScope($query))
            ->when($organizationSlugs, fn ($query) => $query->whereIn('organization', $organizationSlugs))
            ->where('member_type', 'student')
            ->where(fn ($query) => $query->whereNull('stream')->orWhere('stream', '!=', 'Graduated'))
            ->select('organization', 'stream', 'section', 'semester', 'year_level', DB::raw('COUNT(*) as count'))
            ->groupBy('organization', 'stream', 'section', 'semester', 'year_level')
            ->orderBy('organization')
            ->orderByRaw('stream IS NULL ASC')
            ->orderBy('stream')
            ->orderBy('section')
            ->get()
            ->map(function ($g) use ($academicOptions, $user) {
                $availableClasses = collect(array_keys($academicOptions[$g->organization]['streams'] ?? []));
                if ($availableClasses->isEmpty()) {
                    $availableClasses = $this->availableClassesFor($user, $g->organization);
                }
                $className      = trim($g->stream ?: '(Class not assigned)');
                $academicSystem = $academicOptions[$g->organization]['academic_systems'][$g->stream] ?? 'none';
                $suggestedClass = self::suggestPromotion($className);
                $currentClass   = $this->extractClassNumber($className);
                $targetClasses  = strcasecmp($suggestedClass, $className) === 0
                    ? collect()
                    : $availableClasses
                        ->filter(fn ($class) => strcasecmp($class, $suggestedClass) === 0)
                        ->values();

                return [
                    'program'          => $g->stream,
                    'organization'     => $g->organization,
                    'organization_label' => $academicOptions[$g->organization]['label'] ?? ucfirst((string) $g->organization),
                    'academic_system'  => $academicSystem,
                    'stream'           => $g->stream,
                    'section'          => $g->section,
                    'semester'         => $g->semester,
                    'year_level'       => $g->year_level,
                    'count'            => $g->count,
                    'class_name'       => $className,
                    'suggested_class'  => $suggestedClass,
                    'target_classes'   => $targetClasses,
                    'is_grad_year'     => $academicSystem === 'semester'
                        ? (int) $g->semester >= 8
                        : ($academicSystem === 'year' ? (int) $g->year_level >= 4 : ($currentClass !== null && $currentClass >= self::MAX_CLASS)),
                    'label'            => trim($className . ' ' . ($g->semester ? 'semester '.$g->semester : '') . ' ' . ($g->year_level ? 'year '.$g->year_level : '') . ' ' . ($g->section ?? '')),
                ];
            });

        return view('hr.members.promote', compact('groups', 'academicOptions'));
    }

    // ── AJAX: students in a class/section ────────────────────────────────
    public function students(\Illuminate\Http\Request $request)
    {
        $stream  = $request->input('stream');
        $section = $request->input('section');
        $organization = $request->input('organization');
        $semester = $request->input('semester');
        $yearLevel = $request->input('year_level');
        $q       = trim((string) $request->input('q', ''));
        $user    = auth()->user();

        $students = Student::query()
            ->tap(fn($query) => $user->applyStudentScope($query))
            ->when($organization, fn ($query) => $query->where('organization', $organization))
            ->where('member_type', 'student')
            ->when($stream  !== null, fn ($query) => $query->where('stream',  $stream  ?: null))
            ->when($section !== null, fn ($query) => $query->where('section', $section ?: null))
            ->when($semester !== null, fn ($query) => $query->where('semester', $semester ?: null))
            ->when($yearLevel !== null, fn ($query) => $query->where('year_level', $yearLevel ?: null))
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
    public function promote(Request $request, SubjectEnrollmentService $enrollments)
    {
        $user = auth()->user();

        $request->validate([
            'groups'               => 'required|array|min:1',
            'groups.*.from_program'=> 'nullable|string|max:100',
            'groups.*.from_organization'=> 'required|string|max:100',
            'groups.*.from_stream' => 'nullable|string|max:100',
            'groups.*.from_section'=> 'nullable|string|max:50',
            'groups.*.from_semester'=> 'nullable|integer|min:1|max:8',
            'groups.*.from_year_level'=> 'nullable|integer|min:1|max:6',
            'groups.*.to_program'  => 'nullable|string|max:100',
            'groups.*.to_section'  => 'nullable|string|max:50',
            'groups.*.to_section_id' => 'nullable|integer|exists:sections,id',
            'groups.*.to_semester' => 'nullable|integer|min:1|max:8',
            'groups.*.to_year_level' => 'nullable|integer|min:1|max:6',
            'groups.*.academic_system' => 'required|in:semester,year,none',
            'groups.*.action'      => 'required|in:promote,advance_semester,advance_year,graduate,skip',
            'valid_till'           => 'nullable|date',
            'grad_action'          => 'required|in:mark',
            'student_ids'          => 'nullable|array',
            'student_ids.*'        => 'integer|exists:students,id',
        ]);

        $groups     = $request->input('groups', []);
        $validTill  = $request->input('valid_till');
        $studentIds = $request->input('student_ids', []);

        foreach ($groups as $index => $group) {
            $action = $group['action'] ?? null;

            $organization = Organization::query()
                ->where('slug', $group['from_organization'])
                ->where('is_active', true)
                ->with('departments')
                ->first();
            $department = $organization?->departments
                ->first(fn ($department) => $department->name === ($group['from_stream'] ?? null));
            $academicSystem = $department?->academic_system ?? 'none';

            if ($academicSystem !== $group['academic_system']) {
                throw ValidationException::withMessages([
                    "groups.{$index}.academic_system" => 'The department academic system has changed. Refresh the page and try again.',
                ]);
            }

            $expectedAction = match ($academicSystem) {
                'semester' => 'advance_semester',
                'year' => 'advance_year',
                default => 'promote',
            };
            if (! in_array($action, [$expectedAction, 'graduate', 'skip'], true)) {
                throw ValidationException::withMessages([
                    "groups.{$index}.action" => 'This progression action is not valid for the department academic system.',
                ]);
            }

            if (! in_array($action, ['promote', 'advance_semester', 'advance_year'], true)) {
                continue;
            }

            if ($action === 'advance_semester') {
                $current = (int) ($group['from_semester'] ?? 0);
                $target = (int) ($group['to_semester'] ?? 0);
                if (($current && $target !== $current + 1) || $target < 1 || $target > 8) {
                    throw ValidationException::withMessages(["groups.{$index}.to_semester" => $current
                        ? 'Choose the next semester for this student group.'
                        : 'Choose a valid semester for this unassigned student group.']);
                }
                continue;
            }

            if ($action === 'advance_year') {
                $current = (int) ($group['from_year_level'] ?? 0);
                $target = (int) ($group['to_year_level'] ?? 0);
                if (($current && $target !== $current + 1) || $target < 1 || $target > 4) {
                    throw ValidationException::withMessages(["groups.{$index}.to_year_level" => $current
                        ? 'Choose the next study year for this student group.'
                        : 'Choose a valid study year for this unassigned student group.']);
                }
                continue;
            }

            $fromStream = trim((string) ($group['from_stream'] ?? ''));
            $toClass    = trim((string) ($group['to_program'] ?? ''));
            $organizationSlug = trim((string) ($group['from_organization'] ?? ''));
            $availableClasses = $this->availableClassesFor($user, $organizationSlug);

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

            if (! empty($group['to_section_id'])) {
                $targetSection = Section::with('department.organization')->find($group['to_section_id']);
                if (! $targetSection
                    || $targetSection->department->name !== $toClass
                    || $targetSection->department->organization->slug !== $organizationSlug) {
                    throw ValidationException::withMessages([
                        "groups.{$index}.to_section_id" => 'Choose a section belonging to the selected target class.',
                    ]);
                }
            }
        }

        $promoted  = 0;
        $graduated = 0;

        DB::transaction(function () use ($groups, $validTill, $studentIds, $user, &$promoted, &$graduated) {
            foreach ($groups as $g) {
                if ($g['action'] === 'skip') continue;

                $query = Student::query()
                    ->tap(fn($query) => $user->applyStudentScope($query))
                    ->where('organization', $g['from_organization'])
                    ->where('member_type', 'student')
                    ->where('stream', $g['from_stream'] ?: null)
                    ->where('section', $g['from_section'] ?: null)
                    ->where('semester', $g['from_semester'] ?: null)
                    ->where('year_level', $g['from_year_level'] ?: null)
                    ->when(!empty($studentIds), fn ($q) => $q->whereIn('id', $studentIds));

                if ($g['action'] === 'graduate') {
                    $data = ['stream' => 'Graduated', 'program' => 'Graduated', 'section' => null, 'section_id' => null, 'semester' => null, 'year_level' => null];
                    if ($validTill) $data['valid_till'] = $validTill;
                    $graduated += $query->update($data);
                } elseif ($g['action'] === 'advance_semester') {
                    $data = ['semester' => (int) $g['to_semester'], 'year_level' => null];
                    if ($validTill) $data['valid_till'] = $validTill;
                    $promoted += $query->update($data);
                } elseif ($g['action'] === 'advance_year') {
                    $data = ['year_level' => (int) $g['to_year_level'], 'semester' => null];
                    if ($validTill) $data['valid_till'] = $validTill;
                    $promoted += $query->update($data);
                } else {
                    $targetSection = ! empty($g['to_section_id'])
                        ? Section::find($g['to_section_id'])
                        : null;
                    $data = [
                        'stream'  => $g['to_program'],
                        'program' => $g['to_program'],
                        'section' => $targetSection?->name,
                        'section_id' => $targetSection?->id,
                    ];
                    if ($validTill) $data['valid_till'] = $validTill;
                    $promoted += $query->update($data);
                }
            }
        });

        if (! empty($studentIds)) {
            Student::query()
                ->tap(fn ($query) => $user->applyStudentScope($query))
                ->whereIn('id', $studentIds)
                ->get()
                ->each(fn ($student) => $enrollments->syncStudent($student));
        }

        $msg = [];
        if ($promoted)  $msg[] = "{$promoted} student(s) progressed";
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

    private function availableClassesFor($user, ?string $organizationSlug = null)
    {
        $school = Organization::query()
            ->when($organizationSlug, fn ($query) => $query->where('slug', $organizationSlug), fn ($query) => $query->where('type', 'school'))
            ->where('is_active', true)
            ->with('activeDepartments')
            ->first();

        $classes = $school?->activeDepartments->pluck('name') ?? collect();

        if ($classes->isEmpty()) {
            $classes = Student::query()
                ->tap(fn ($query) => $user->applyStudentScope($query))
                ->when($organizationSlug, fn ($query) => $query->where('organization', $organizationSlug))
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
