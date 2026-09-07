<?php

namespace App\Http\Controllers\TeachingLearning;

use App\Http\Controllers\Controller;
use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\TeachingLearning\AcademicYear;
use App\Models\TeachingLearning\RoutinePeriod;
use App\Models\TeachingLearning\RoutineShift;
use App\Models\TeachingLearning\RoutineShiftAssignment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoutineConfigurationController extends Controller
{
    private const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public function index(Request $request)
    {
        $academicYears = AcademicYear::withCount('shiftAssignments')->latest('starts_on')->latest('id')->get();
        $selectedYear = $academicYears->firstWhere('id', $request->integer('year'))
            ?? $academicYears->firstWhere('is_active', true)
            ?? $academicYears->first();

        $organizations = Organization::with(['departments' => fn ($query) => $query->where('is_active', true)])->where('is_active', true)->orderBy('name')->get();
        $shifts = RoutineShift::with([
                'periods',
                'assignments' => fn ($query) => $query
                    ->when($selectedYear, fn ($query) => $query->where('academic_year_id', $selectedYear->id))
                    ->with(['organization', 'department']),
            ])
            ->orderBy('starts_at')
            ->orderBy('name')
            ->get();

        return view('teaching_learning.routine-configuration.index', compact(
            'academicYears', 'selectedYear', 'organizations', 'shifts'
        ));
    }

    public function storeAcademicYear(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:academic_years,name'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use (&$data, $request) {
            $data['is_active'] = $request->boolean('is_active');
            if ($data['is_active']) AcademicYear::where('is_active', true)->update(['is_active' => false]);
            AcademicYear::create($data);
        });

        return back()->with('success', 'Academic year created.');
    }

    public function updateAcademicYear(Request $request, AcademicYear $academicYear)
    {
        $this->ensureYearEditable($academicYear);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('academic_years', 'name')->ignore($academicYear->id)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);
        $academicYear->update($data);
        return back()->with('success', 'Academic year updated.');
    }

    public function activateAcademicYear(AcademicYear $academicYear)
    {
        DB::transaction(function () use ($academicYear) {
            AcademicYear::whereKeyNot($academicYear->id)->update(['is_active' => false]);
            $academicYear->update(['is_active' => true]);
        });
        return back()->with('success', "{$academicYear->name} is now active.");
    }

    public function toggleAcademicYearLock(AcademicYear $academicYear)
    {
        $academicYear->update(['is_locked' => ! $academicYear->is_locked]);
        return back()->with('success', $academicYear->fresh()->is_locked ? 'Academic year locked.' : 'Academic year unlocked.');
    }

    public function destroyAcademicYear(AcademicYear $academicYear)
    {
        $this->ensureYearEditable($academicYear);
        if ($academicYear->is_active) return back()->with('error', 'Activate another academic year before deleting this one.');
        $academicYear->delete();
        return back()->with('success', 'Academic year deleted.');
    }

    public function storeShift(Request $request)
    {
        $data = $this->validateShift($request);

        $shift = DB::transaction(function () use ($data, $request) {
            $data['is_active'] = $request->boolean('is_active', true);
            $shift = RoutineShift::create($data);
            $this->generatePeriods($shift);
            return $shift;
        });

        return back()->with('success', "{$shift->name} time slot created with {$shift->periods()->where('is_break', false)->count()} periods. Assign it below.");
    }

    public function updateShift(Request $request, RoutineShift $routineShift)
    {
        $this->ensureShiftEditable($routineShift);
        $data = $this->validateShift($request, $routineShift);

        DB::transaction(function () use ($routineShift, $data, $request) {
            $data['is_active'] = $request->boolean('is_active');
            $routineShift->update($data);
            $routineShift->periods()->delete();
            $this->generatePeriods($routineShift->fresh());
        });

        return back()->with('success', 'Shift and periods regenerated.');
    }

    public function toggleShiftLock(RoutineShift $routineShift)
    {
        $routineShift->update(['is_locked' => ! $routineShift->is_locked]);
        return back()->with('success', $routineShift->fresh()->is_locked ? 'Time slot locked.' : 'Time slot unlocked.');
    }

    public function updateBreaks(Request $request, RoutineShift $routineShift)
    {
        $this->ensureShiftEditable($routineShift);
        $data = $request->validate([
            'breaks' => ['nullable', 'array', 'max:6'],
            'breaks.*.name' => ['required', 'string', 'max:40'],
            'breaks.*.after_period' => ['required', 'integer', 'min:1', 'max:20'],
            'breaks.*.minutes' => ['required', 'integer', 'min:1', 'max:120'],
        ]);
        $breaks = $this->normalizedBreaks(
            $data['breaks'] ?? [],
            substr($routineShift->starts_at, 0, 5),
            substr($routineShift->ends_at, 0, 5),
            (int) $routineShift->period_minutes
        );

        DB::transaction(function () use ($routineShift, $breaks) {
            $routineShift->update([
                'breaks' => $breaks,
                'break_after_period' => $breaks[0]['after_period'] ?? null,
                'break_minutes' => $breaks[0]['minutes'] ?? null,
            ]);
            $this->syncPeriods($routineShift->fresh());
        });

        return back()->with('success', count($breaks)
            ? count($breaks).' break'.(count($breaks) === 1 ? '' : 's').' saved and the timeline regenerated.'
            : 'All breaks removed and the timeline regenerated.');
    }

    public function destroyShift(RoutineShift $routineShift)
    {
        $this->ensureShiftEditable($routineShift);
        if ($routineShift->assignments()->exists()) {
            return back()->with('error', 'Remove this time slot from every academic-year assignment before deleting it.');
        }
        $routineShift->delete();
        return back()->with('success', 'Time slot deleted.');
    }

    public function assignDepartments(Request $request, RoutineShift $routineShift)
    {
        $this->ensureShiftUnlocked($routineShift);
        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'whole_organization' => ['nullable', 'boolean'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
        ]);
        $year = AcademicYear::findOrFail($data['academic_year_id']);
        $this->ensureYearEditable($year);
        $organizationId = (int) $data['organization_id'];
        $wholeOrganization = $request->boolean('whole_organization');
        $departmentIds = collect($data['department_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $departments = Department::whereIn('id', $departmentIds)->get();

        if ($departments->contains(fn ($department) => $department->organization_id !== $organizationId)) {
            throw ValidationException::withMessages(['department_ids' => 'Every selected faculty/class must belong to the selected organization.']);
        }

        $lockedConflict = RoutineShiftAssignment::query()
            ->where('academic_year_id', $year->id)
            ->where('organization_id', $organizationId)
            ->where('routine_shift_id', '!=', $routineShift->id)
            ->when(! $wholeOrganization, fn ($query) => $query->whereIn('department_id', $departmentIds))
            ->whereHas('shift', fn ($query) => $query->where('is_locked', true))
            ->exists();
        if ($lockedConflict) {
            throw ValidationException::withMessages(['department_ids' => 'A selected scope belongs to a locked time slot. Unlock it before reassigning.']);
        }

        if (! $wholeOrganization && $departmentIds->isEmpty()) {
            RoutineShiftAssignment::where('academic_year_id', $year->id)
                ->where('routine_shift_id', $routineShift->id)
                ->where('organization_id', $organizationId)
                ->delete();

            return back()->with('success', 'Time-slot assignment removed for this organization.');
        }

        DB::transaction(function () use ($routineShift, $year, $organizationId, $wholeOrganization, $departmentIds) {
            if ($wholeOrganization) {
                RoutineShiftAssignment::where('academic_year_id', $year->id)
                    ->where('organization_id', $organizationId)
                    ->delete();
                RoutineShiftAssignment::create([
                    'academic_year_id' => $year->id,
                    'routine_shift_id' => $routineShift->id,
                    'organization_id' => $organizationId,
                    'department_id' => null,
                ]);
                return;
            }

            RoutineShiftAssignment::where('academic_year_id', $year->id)
                ->where('routine_shift_id', $routineShift->id)
                ->where('organization_id', $organizationId)
                ->whereNull('department_id')
                ->delete();
            RoutineShiftAssignment::where('academic_year_id', $year->id)
                ->where('routine_shift_id', $routineShift->id)
                ->where('organization_id', $organizationId)
                ->whereNotIn('department_id', $departmentIds)->delete();
            foreach ($departmentIds as $departmentId) {
                RoutineShiftAssignment::where('academic_year_id', $year->id)
                    ->where('department_id', $departmentId)->delete();
                RoutineShiftAssignment::create([
                    'academic_year_id' => $year->id,
                    'routine_shift_id' => $routineShift->id,
                    'organization_id' => $organizationId,
                    'department_id' => $departmentId,
                ]);
            }
        });

        return back()->with('success', $wholeOrganization
            ? 'Time slot assigned to the complete organization.'
            : 'Department time-slot assignments updated.');
    }

    public function updatePeriod(Request $request, RoutinePeriod $routinePeriod)
    {
        $routinePeriod->loadMissing('shift');
        $this->ensureShiftEditable($routinePeriod->shift);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'is_break' => ['nullable', 'boolean'],
        ]);

        if ($data['starts_at'] < substr($routinePeriod->shift->starts_at, 0, 5)
            || $data['ends_at'] > substr($routinePeriod->shift->ends_at, 0, 5)) {
            throw ValidationException::withMessages(['starts_at' => 'Period times must remain inside the shift start and end time.']);
        }
        $overlaps = $routinePeriod->shift->periods()->whereKeyNot($routinePeriod->id)
            ->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at'])->exists();
        if ($overlaps) throw ValidationException::withMessages(['starts_at' => 'This time overlaps another period.']);

        $data['is_break'] = $request->boolean('is_break');
        $routinePeriod->update($data);
        return back()->with('success', 'Period updated.');
    }

    private function validateShift(Request $request, ?RoutineShift $shift = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('routine_shifts', 'name')->ignore($shift?->id)],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'period_minutes' => ['required', 'integer', 'min:15', 'max:180'],
            'breaks' => ['nullable', 'array', 'max:6'],
            'breaks.*.name' => ['required', 'string', 'max:40'],
            'breaks.*.after_period' => ['required', 'integer', 'min:1', 'max:20'],
            'breaks.*.minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'break_after_period' => ['nullable', 'integer', 'min:1', 'max:20'],
            'break_minutes' => ['nullable', 'required_with:break_after_period', 'integer', 'min:1', 'max:120'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['string', Rule::in(self::DAYS)],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $availableMinutes = CarbonImmutable::createFromFormat('H:i', $data['starts_at'])
            ->diffInMinutes(CarbonImmutable::createFromFormat('H:i', $data['ends_at']));
        if ($availableMinutes < (int) $data['period_minutes']) {
            throw ValidationException::withMessages(['ends_at' => 'The shift must contain at least one complete period.']);
        }

        $requestedBreaks = $data['breaks'] ?? ($shift?->breaks ?? []);
        if (! $request->has('breaks') && $request->filled('break_after_period')) {
            $requestedBreaks = collect($requestedBreaks)->values()->all();
            $requestedBreaks[0] = [
                'name' => $requestedBreaks[0]['name'] ?? 'Break',
                'after_period' => (int) $data['break_after_period'],
                'minutes' => (int) $data['break_minutes'],
            ];
        }
        $breaks = $this->normalizedBreaks($requestedBreaks, $data['starts_at'], $data['ends_at'], (int) $data['period_minutes']);

        $data['breaks'] = $breaks;
        // Retain the first break in the legacy columns for older integrations.
        $data['break_after_period'] = $breaks[0]['after_period'] ?? null;
        $data['break_minutes'] = $breaks[0]['minutes'] ?? null;
        $data['working_days'] = collect(self::DAYS)->filter(fn ($day) => in_array($day, $data['working_days'], true))->values()->all();
        return $data;
    }

    private function normalizedBreaks(array $requestedBreaks, string $startsAt, string $endsAt, int $periodMinutes): array
    {
        $breaks = collect($requestedBreaks)
            ->map(fn (array $break) => [
                'name' => trim($break['name']),
                'after_period' => (int) $break['after_period'],
                'minutes' => (int) $break['minutes'],
            ])
            ->sortBy('after_period')
            ->values();

        if ($breaks->pluck('after_period')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['breaks' => 'Only one break can be placed after the same period.']);
        }

        $availableMinutes = CarbonImmutable::createFromFormat('H:i', substr($startsAt, 0, 5))
            ->diffInMinutes(CarbonImmutable::createFromFormat('H:i', substr($endsAt, 0, 5)));
        $teachingMinutes = $availableMinutes - $breaks->sum('minutes');
        if ($teachingMinutes < $periodMinutes) {
            throw ValidationException::withMessages(['breaks' => 'Breaks leave insufficient time for a complete teaching period.']);
        }

        $periodCount = (int) ceil($teachingMinutes / $periodMinutes);
        if ($breaks->contains(fn (array $break) => $break['after_period'] >= $periodCount)) {
            throw ValidationException::withMessages(['breaks' => "Place each break before the final period (this slot produces {$periodCount} periods)."]);
        }

        return $breaks->all();
    }

    private function generatePeriods(RoutineShift $shift): void
    {
        foreach ($this->periodSchedule($shift) as $period) {
            $shift->periods()->create($period);
        }
    }

    private function syncPeriods(RoutineShift $shift): void
    {
        $schedule = collect($this->periodSchedule($shift));
        $existingLessons = $shift->periods()->where('is_break', false)->orderBy('position')->get();
        $scheduledLessons = $schedule->where('is_break', false)->values();

        if ($existingLessons->count() !== $scheduledLessons->count()
            && DB::table('routine_lessons')->whereIn('routine_period_id', $existingLessons->pluck('id'))
                ->orWhereIn('end_routine_period_id', $existingLessons->pluck('id'))->exists()) {
            throw ValidationException::withMessages([
                'breaks' => 'These breaks would change the number of teaching periods. Clear scheduled classes from this time slot first, or adjust the break duration.',
            ]);
        }

        // Move existing positions temporarily to avoid the unique shift/position
        // constraint while breaks are inserted between teaching periods.
        $shift->periods()->increment('position', 100);
        $shift->periods()->where('is_break', true)->delete();

        $lessonIndex = 0;
        foreach ($schedule as $period) {
            if ($period['is_break']) {
                $shift->periods()->create($period);
                continue;
            }

            $existing = $existingLessons->get($lessonIndex++);
            if ($existing) {
                $period['name'] = $existing->name;
                $existing->update($period);
            } else {
                $shift->periods()->create($period);
            }
        }

        $unused = $existingLessons->slice($lessonIndex);
        if ($unused->isNotEmpty()) {
            $shift->periods()->whereIn('id', $unused->pluck('id'))->delete();
        }
    }

    private function periodSchedule(RoutineShift $shift): array
    {
        // Depending on the PDO/MySQL configuration, TIME columns may be
        // hydrated as either HH:MM or HH:MM:SS. Period generation only needs
        // hours and minutes, so normalize both representations first.
        $cursor = $this->parseRoutineTime($shift->starts_at);
        $end = $this->parseRoutineTime($shift->ends_at);
        $position = 1;
        $lessonNumber = 1;
        $breaks = collect($shift->breaks ?: ($shift->break_after_period ? [[
            'name' => 'Break',
            'after_period' => (int) $shift->break_after_period,
            'minutes' => (int) $shift->break_minutes,
        ]] : []))->keyBy('after_period');
        $breaksAdded = [];
        $periods = [];

        while ($cursor->lt($end)) {
            $break = $breaks->get($lessonNumber - 1);
            if ($break && ! in_array($lessonNumber - 1, $breaksAdded, true)) {
                $breakEnd = $cursor->addMinutes((int) $break['minutes']);
                if ($breakEnd->gt($end)) break;
                $periods[] = ['position' => $position++, 'name' => $break['name'], 'starts_at' => $cursor->format('H:i:s'), 'ends_at' => $breakEnd->format('H:i:s'), 'is_break' => true];
                $cursor = $breakEnd;
                $breaksAdded[] = $lessonNumber - 1;
                continue;
            }
            $periodEnd = $cursor->addMinutes((int) $shift->period_minutes);
            // Keep the final period when the remaining schedule time is
            // shorter than the standard duration (for example 55 of 60 min).
            if ($periodEnd->gt($end)) $periodEnd = $end;
            $periods[] = ['position' => $position++, 'name' => "Period {$lessonNumber}", 'starts_at' => $cursor->format('H:i:s'), 'ends_at' => $periodEnd->format('H:i:s'), 'is_break' => false];
            $cursor = $periodEnd;
            $lessonNumber++;
        }

        return $periods;
    }

    private function parseRoutineTime(string $time): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!H:i', substr($time, 0, 5));
    }

    private function ensureYearEditable(AcademicYear $year): void
    {
        if ($year->is_locked) throw ValidationException::withMessages(['lock' => 'This academic year is locked. Unlock it before making changes.']);
    }

    private function ensureShiftEditable(RoutineShift $shift): void
    {
        $this->ensureShiftUnlocked($shift);
        if ($shift->assignments()->whereHas('academicYear', fn ($query) => $query->where('is_locked', true))->exists()) {
            throw ValidationException::withMessages(['lock' => 'This time slot is used by a locked academic year and cannot be changed.']);
        }
    }

    private function ensureShiftUnlocked(RoutineShift $shift): void
    {
        if ($shift->is_locked) throw ValidationException::withMessages(['lock' => 'This time slot is locked. Unlock it before making changes.']);
    }
}
