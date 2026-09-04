<?php

namespace App\Http\Controllers\TeachingLearning;

use App\Http\Controllers\Controller;
use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\Card\Section;
use App\Models\Card\Student;
use App\Models\Card\StudentSubjectEnrollment;
use App\Models\Card\SubjectOffering;
use App\Models\TeachingLearning\AcademicYear;
use App\Models\TeachingLearning\RoutineLesson;
use App\Models\TeachingLearning\RoutinePeriod;
use App\Models\TeachingLearning\RoutinePlan;
use App\Models\TeachingLearning\RoutineRoom;
use App\Models\TeachingLearning\RoutineShift;
use App\Models\User;
use App\Services\RoutineCollisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoutineBuilderController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::latest('starts_on')->get();
        $organizations = Organization::with(['departments.sections'])->where('is_active', true)->orderBy('name')->get();
        $shifts = RoutineShift::with(['assignments', 'periods'])->where('is_active', true)->orderBy('starts_at')->get();
        $plans = RoutinePlan::with(['academicYear', 'organization', 'department', 'shift', 'sections'])->withCount('lessons')->latest()->get();
        $rooms = RoutineRoom::with('organization')->orderBy('organization_id')->orderBy('name')->get();

        return view('teaching_learning.routine-builder.index', compact('academicYears', 'organizations', 'shifts', 'plans', 'rooms'));
    }

    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'organization_id' => ['required', 'exists:organizations,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'routine_shift_id' => ['required', 'exists:routine_shifts,id'],
            'name' => ['required', 'string', 'max:120'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:8'],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:6'],
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['integer', 'exists:sections,id'],
        ]);
        $department = Department::findOrFail($data['department_id']);
        if ((int) $department->organization_id !== (int) $data['organization_id']) {
            throw ValidationException::withMessages(['department_id' => 'The faculty/class does not belong to this organization.']);
        }
        $sectionIds = Section::where('department_id', $department->id)->whereIn('id', $data['section_ids'])->pluck('id');
        if ($sectionIds->count() !== count(array_unique($data['section_ids']))) {
            throw ValidationException::withMessages(['section_ids' => 'Every section must belong to the selected faculty/class.']);
        }
        $this->ensureShiftAssigned((int) $data['academic_year_id'], (int) $data['organization_id'], $department->id, (int) $data['routine_shift_id']);
        if (AcademicYear::findOrFail($data['academic_year_id'])->is_locked) {
            throw ValidationException::withMessages(['academic_year_id' => 'This academic year is locked. Unlock it before creating a routine.']);
        }
        $data['semester'] = $department->academic_system === 'semester' ? ($data['semester'] ?? null) : null;
        $data['year_level'] = $department->academic_system === 'year' ? ($data['year_level'] ?? null) : null;
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();
        unset($data['section_ids']);

        $plan = DB::transaction(function () use ($data, $sectionIds) {
            $plan = RoutinePlan::create($data);
            $plan->sections()->sync($sectionIds);
            return $plan;
        });
        return redirect()->route('admin.teaching-learning.routine-builder.show', $plan)->with('success', 'Draft routine created. Click any period cell to schedule a class.');
    }

    public function show(Request $request, RoutinePlan $routinePlan)
    {
        $routinePlan->load(['academicYear', 'organization', 'department.sections', 'shift.periods', 'sections', 'lessons.period', 'lessons.endPeriod', 'lessons.groups.offering.subject', 'lessons.groups.teacher', 'lessons.groups.teachers', 'lessons.groups.room', 'lessons.groups.students']);
        $day = in_array($request->day, $routinePlan->shift->working_days ?? [], true) ? $request->day : ($routinePlan->shift->working_days[0] ?? 'Sunday');
        $offerings = SubjectOffering::with('subject')->where('department_id', $routinePlan->department_id)
            ->when($routinePlan->semester, fn ($query) => $query->where(fn ($query) => $query->whereNull('semester')->orWhere('semester', $routinePlan->semester)))
            ->when($routinePlan->year_level, fn ($query) => $query->where(fn ($query) => $query->whereNull('year_level')->orWhere('year_level', $routinePlan->year_level)))
            ->orderBy('is_elective')->orderBy('group_name')->get();
        $electiveSectionKeys = StudentSubjectEnrollment::with('student:id,section_id,section')
            ->where('academic_year', $routinePlan->academicYear->name)
            ->where('assignment_source', 'manual')
            ->whereIn('subject_offering_id', $offerings->where('is_elective', true)->pluck('id'))
            ->get()
            ->groupBy('subject_offering_id')
            ->map(fn ($enrollments) => $enrollments->flatMap(function ($enrollment) {
                $student = $enrollment->student;
                if (! $student) return [];

                return $student->section_id
                    ? ['id:'.$student->section_id]
                    : (filled($student->section) ? ['name:'.mb_strtolower(trim($student->section))] : []);
            })->unique()->values());
        $offeringOptions = $offerings->map(function ($offering) use ($routinePlan, $electiveSectionKeys) {
            $sectionIds = $routinePlan->sections
                ->filter(fn ($section) => ! $offering->group_name || $section->group_name === $offering->group_name)
                ->when($offering->is_elective, function ($sections) use ($offering, $electiveSectionKeys) {
                    $assignedKeys = $electiveSectionKeys->get($offering->id, collect());

                    return $sections->filter(fn ($section) => $assignedKeys->contains('id:'.$section->id)
                        || $assignedKeys->contains('name:'.mb_strtolower(trim($section->name))));
                })
                ->pluck('id')->values();
            return [
                'id' => $offering->id,
                'name' => $offering->subject->name,
                'code' => $offering->subject->code,
                'practical_code' => $offering->subject->practical_code,
                'has_practical' => (bool) $offering->subject->has_practical,
                'is_elective' => (bool) $offering->is_elective,
                'group_name' => $offering->group_name,
                'section_ids' => $sectionIds,
            ];
        })->filter(fn ($row) => $row['section_ids']->isNotEmpty())->values();
        $teachers = User::role('teacher')->where('is_active', true)->orderBy('name')->limit(12)->get(['id', 'name'])->map(fn ($teacher) => [
            'id' => $teacher->id, 'name' => $teacher->name, 'initials' => $this->initials($teacher->name),
        ]);
        $rooms = RoutineRoom::where('organization_id', $routinePlan->organization_id)->where('is_active', true)->orderBy('type')->orderBy('name')->get();
        $periods = $routinePlan->shift->periods->keyBy('id');
        $availableShifts = RoutineShift::with('periods')->where('is_active', true)
            ->whereHas('assignments', fn ($query) => $query
                ->where('academic_year_id', $routinePlan->academic_year_id)
                ->where('organization_id', $routinePlan->organization_id)
                ->where(fn ($query) => $query->whereNull('department_id')->orWhere('department_id', $routinePlan->department_id)))
            ->orderBy('starts_at')->get();
        $entries = collect();
        $cellLessons = collect();
        foreach ($routinePlan->lessons->where('day_of_week', $day) as $lesson) {
            $start = $periods->get($lesson->routine_period_id);
            $end = $periods->get($lesson->end_routine_period_id) ?? $start;
            if (! $start || ! $end) continue;
            $payload = [
                'id' => $lesson->id,
                'routine_period_id' => (string) $start->id,
                'end_routine_period_id' => (string) $end->id,
                'mode' => $lesson->mode,
                'subject_offering_id' => (string) $lesson->groups->first()?->subject_offering_id,
                'notes' => $lesson->notes,
                'groups' => $lesson->groups->map(fn ($group) => [
                    'teacher_ids' => $group->teachers->pluck('id')->map(fn ($id) => (string) $id)->values(),
                    'routine_room_id' => $group->routine_room_id ? (string) $group->routine_room_id : '',
                    'group_label' => $group->group_label,
                    'student_count' => $group->students->count(),
                ])->values(),
            ];
            $coveredPeriods = $routinePlan->shift->periods->filter(fn ($period) => ! $period->is_break && $period->position >= $start->position && $period->position <= $end->position);
            foreach ($coveredPeriods as $covered) {
                $key = "{$lesson->section_id}:{$covered->id}";
                $entries->put($key, $payload);
                $cellLessons->put($key, $lesson);
            }
        }

        return view('teaching_learning.routine-builder.show', compact('routinePlan', 'day', 'offeringOptions', 'teachers', 'rooms', 'entries', 'cellLessons', 'availableShifts'));
    }

    public function teacherOptions(Request $request, RoutinePlan $routinePlan)
    {
        abort_unless(auth()->user()->canAccess(['teaching-learning.routine.view', 'teaching-learning.routine.manage']), 403);
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'ids' => ['nullable', 'array', 'max:20'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);
        $search = trim((string) ($data['q'] ?? ''));
        $selectedIds = collect($data['ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $base = fn () => User::role('teacher')->where('is_active', true);
        $selected = $selectedIds->isEmpty() ? collect() : $base()->whereIn('id', $selectedIds)->get(['id', 'name']);
        $matches = $base()->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->limit(20)->get(['id', 'name']);
        if ($search !== '' && ! str_contains($search, ' ') && $matches->count() < 20) {
            $initialMatches = $base()->orderBy('name')->limit(500)->get(['id', 'name'])
                ->filter(fn ($teacher) => str_contains(mb_strtolower($this->initials($teacher->name)), mb_strtolower($search)))
                ->take(20 - $matches->count());
            $matches = $matches->concat($initialMatches)->unique('id')->values();
        }

        return response()->json($selected->concat($matches)->unique('id')->values()->map(fn ($teacher) => [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'initials' => $this->initials($teacher->name),
        ]));
    }

    public function updatePlan(Request $request, RoutinePlan $routinePlan)
    {
        $this->ensurePlanEditable($routinePlan);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'routine_shift_id' => ['required', 'integer', 'exists:routine_shifts,id'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:8'],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:6'],
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['integer', 'distinct', 'exists:sections,id'],
        ]);
        $routinePlan->loadMissing(['department', 'sections']);
        $sectionIds = Section::where('department_id', $routinePlan->department_id)
            ->whereIn('id', $data['section_ids'])->pluck('id');
        if ($sectionIds->count() !== count($data['section_ids'])) {
            throw ValidationException::withMessages(['section_ids' => 'Every selected section must belong to this faculty/class.']);
        }
        $removedWithClasses = $routinePlan->lessons()->whereNotIn('section_id', $sectionIds)->with('section:id,name')->get()
            ->pluck('section.name')->filter()->unique()->values();
        if ($removedWithClasses->isNotEmpty()) {
            throw ValidationException::withMessages(['section_ids' => 'Clear classes from '.$removedWithClasses->implode(', ').' before removing those sections.']);
        }
        if ((int) $data['routine_shift_id'] !== (int) $routinePlan->routine_shift_id && $routinePlan->lessons()->exists()) {
            throw ValidationException::withMessages(['routine_shift_id' => 'Clear the scheduled classes before changing the time slot because period IDs will be different.']);
        }
        $levelChanged = ($routinePlan->department->academic_system === 'semester' && (int) ($data['semester'] ?? 0) !== (int) $routinePlan->semester)
            || ($routinePlan->department->academic_system === 'year' && (int) ($data['year_level'] ?? 0) !== (int) $routinePlan->year_level);
        if ($levelChanged && $routinePlan->lessons()->exists()) {
            throw ValidationException::withMessages(['semester' => 'Clear the scheduled classes before changing the semester/study year because its subject allocation is different.']);
        }
        $this->ensureShiftAssigned($routinePlan->academic_year_id, $routinePlan->organization_id, $routinePlan->department_id, (int) $data['routine_shift_id']);
        $data['semester'] = $routinePlan->department->academic_system === 'semester' ? ($data['semester'] ?? null) : null;
        $data['year_level'] = $routinePlan->department->academic_system === 'year' ? ($data['year_level'] ?? null) : null;

        DB::transaction(function () use ($routinePlan, $data, $sectionIds) {
            $routinePlan->update(collect($data)->except('section_ids')->all());
            $routinePlan->sections()->sync($sectionIds);
        });

        return redirect()->route('admin.teaching-learning.routine-builder.show', $routinePlan)
            ->with('success', 'Routine settings updated. New sections are ready in every day grid.');
    }

    public function saveLesson(Request $request, RoutinePlan $routinePlan, RoutineCollisionService $collisions)
    {
        $this->ensurePlanEditable($routinePlan);
        $base = $request->validate([
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'routine_period_id' => ['required', 'integer', 'exists:routine_periods,id'],
            'end_routine_period_id' => ['nullable', 'integer', 'exists:routine_periods,id'],
            'day_of_week' => ['required', 'string', 'max:12'],
            'intent' => ['nullable', 'in:save,delete'],
        ]);
        $lesson = RoutineLesson::where('routine_plan_id', $routinePlan->id)
            ->where('section_id', $base['section_id'])->where('routine_period_id', $base['routine_period_id'])
            ->where('day_of_week', $base['day_of_week'])->first();
        if (($base['intent'] ?? 'save') === 'delete') {
            $lesson?->delete();
            return $request->expectsJson()
                ? response()->json(['message' => 'Routine class cleared.'])
                : back()->with('success', 'Routine cell cleared.');
        }
        $data = $request->validate([
            'mode' => ['required', Rule::in(['single', 'practical_split'])],
            'subject_offering_id' => ['required', 'integer', 'exists:subject_offerings,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'groups' => ['required', 'array', 'min:1', 'max:2'],
            'groups.*.teacher_ids' => ['required', 'array', 'min:1'],
            'groups.*.teacher_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'groups.*.routine_room_id' => ['nullable', 'integer', 'exists:routine_rooms,id'],
            'groups.*.group_label' => ['nullable', 'string', 'max:30'],
        ]);
        $routinePlan->loadMissing(['academicYear', 'shift', 'sections']);
        if (! $routinePlan->sections->contains('id', (int) $base['section_id'])) throw ValidationException::withMessages(['section_id' => 'This section is outside the routine.']);
        if (! in_array($base['day_of_week'], $routinePlan->shift->working_days ?? [], true)) throw ValidationException::withMessages(['day_of_week' => 'This is not a working day for the selected time slot.']);
        $startPeriod = RoutinePeriod::where('routine_shift_id', $routinePlan->routine_shift_id)->where('is_break', false)->findOrFail($base['routine_period_id']);
        $endPeriodId = $routinePlan->organization->type === 'school' ? $startPeriod->id : (int) ($base['end_routine_period_id'] ?? $startPeriod->id);
        $endPeriod = RoutinePeriod::where('routine_shift_id', $routinePlan->routine_shift_id)->where('is_break', false)->findOrFail($endPeriodId);
        if ($endPeriod->position < $startPeriod->position) throw ValidationException::withMessages(['end_routine_period_id' => 'End period must be the same as or after the start period.']);
        if ($routinePlan->shift->periods()->where('is_break', true)->whereBetween('position', [$startPeriod->position, $endPeriod->position])->exists()) {
            throw ValidationException::withMessages(['end_routine_period_id' => 'A combined class cannot continue across a break.']);
        }
        $groups = collect($data['groups']);
        if ($data['mode'] === 'single' && $groups->count() !== 1) throw ValidationException::withMessages(['groups' => 'A theory class requires one whole-class group.']);
        if ($data['mode'] === 'practical_split' && $groups->count() !== 2) throw ValidationException::withMessages(['groups' => 'A 50/50 practical requires exactly two groups.']);
        $section = $routinePlan->sections->firstWhere('id', (int) $base['section_id']);
        $offering = SubjectOffering::with('subject')->where('department_id', $routinePlan->department_id)
            ->whereKey($data['subject_offering_id'])
            ->when($routinePlan->semester, fn ($query) => $query->where(fn ($query) => $query->whereNull('semester')->orWhere('semester', $routinePlan->semester)))
            ->when($routinePlan->year_level, fn ($query) => $query->where(fn ($query) => $query->whereNull('year_level')->orWhere('year_level', $routinePlan->year_level)))
            ->where(fn ($query) => $query->whereNull('group_name')->orWhere('group_name', $section->group_name))
            ->first();
        if (! $offering) throw ValidationException::withMessages(['subject_offering_id' => 'This subject is not allocated to the selected faculty and section.']);
        if ($offering->is_elective && ! StudentSubjectEnrollment::query()
            ->where('subject_offering_id', $offering->id)
            ->where('academic_year', $routinePlan->academicYear->name)
            ->where('assignment_source', 'manual')
            ->whereHas('student', fn ($query) => $query
                ->where('section_id', $section->id)
                ->orWhere(fn ($query) => $query->whereNull('section_id')->where('section', $section->name)))
            ->exists()) {
            throw ValidationException::withMessages([
                'subject_offering_id' => 'Assign this elective to students in the selected section before adding it to the routine.',
            ]);
        }
        if ($data['mode'] === 'practical_split' && ! $offering->subject->has_practical) throw ValidationException::withMessages(['mode' => 'This subject does not have a practical class.']);
        $roomIds = $groups->pluck('routine_room_id')->filter();
        if ($data['mode'] === 'practical_split' && $roomIds->count() !== 2) {
            throw ValidationException::withMessages(['groups' => 'Choose a laboratory for both practical groups.']);
        }
        if ($roomIds->isNotEmpty() && RoutineRoom::whereIn('id', $roomIds)->where('organization_id', '!=', $routinePlan->organization_id)->exists()) {
            throw ValidationException::withMessages(['groups' => 'A selected room does not belong to this organization.']);
        }
        $groups = $groups->values()->map(fn ($group, $index) => [
            'subject_offering_id' => (int) $offering->id,
            'teacher_id' => (int) collect($group['teacher_ids'])->first(),
            'teacher_ids' => collect($group['teacher_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all(),
            'routine_room_id' => filled($group['routine_room_id'] ?? null) ? (int) $group['routine_room_id'] : null,
            'position' => $index + 1,
            'group_label' => $data['mode'] === 'practical_split' ? (($group['group_label'] ?? null) ?: 'Group '.chr(65 + $index)) : null,
            'student_percentage' => $data['mode'] === 'practical_split' ? 50 : 100,
        ]);
        $collisions->ensureAvailable($routinePlan, (int) $base['section_id'], $base['day_of_week'], $startPeriod, $endPeriod, $groups, $lesson?->id);

        DB::transaction(function () use ($routinePlan, $base, $data, $groups, $lesson, $endPeriod) {
            $lesson = $lesson ?: new RoutineLesson(['routine_plan_id' => $routinePlan->id]);
            $lesson->fill(['section_id' => $base['section_id'], 'routine_period_id' => $base['routine_period_id'], 'end_routine_period_id' => $endPeriod->id, 'day_of_week' => $base['day_of_week'], 'mode' => $data['mode'], 'notes' => $data['notes'] ?? null])->save();
            $lesson->groups()->delete();
            foreach ($groups as $groupData) {
                $teacherIds = $groupData['teacher_ids'];
                unset($groupData['teacher_ids']);
                $createdGroup = $lesson->groups()->create($groupData);
                $createdGroup->teachers()->sync($teacherIds);
            }
            $this->assignStudents($lesson->fresh('groups'), $routinePlan);
        });
        $message = $data['mode'] === 'practical_split' ? '50/50 practical groups scheduled.' : 'Theory class scheduled.';
        return $request->expectsJson() ? response()->json(['message' => $message]) : back()->with('success', $message);
    }

    public function togglePublish(RoutinePlan $routinePlan)
    {
        $routinePlan->loadMissing('academicYear');
        if ($routinePlan->academicYear->is_locked) throw ValidationException::withMessages(['lock' => 'The academic year is locked.']);
        if ($routinePlan->status !== 'published' && ! $routinePlan->lessons()->exists()) {
            throw ValidationException::withMessages(['routine' => 'Add at least one class before publishing this routine.']);
        }
        $routinePlan->update(['status' => $routinePlan->status === 'published' ? 'draft' : 'published']);
        return back()->with('success', $routinePlan->fresh()->status === 'published' ? 'Routine published and locked.' : 'Routine returned to draft for editing.');
    }

    public function print(Request $request, RoutinePlan $routinePlan)
    {
        $routinePlan->load(['academicYear', 'organization', 'department', 'shift.periods', 'sections', 'lessons.period', 'lessons.endPeriod', 'lessons.groups.offering.subject', 'lessons.groups.teacher', 'lessons.groups.teachers', 'lessons.groups.room', 'lessons.groups.students']);
        $days = $request->filled('day') && in_array($request->day, $routinePlan->shift->working_days ?? [], true) ? [$request->day] : ($routinePlan->shift->working_days ?? []);
        return view('teaching_learning.routine-builder.print', compact('routinePlan', 'days'));
    }

    public function destroy(RoutinePlan $routinePlan)
    {
        $this->ensurePlanEditable($routinePlan);
        $routinePlan->delete();
        return redirect()->route('admin.teaching-learning.routine-builder.index')->with('success', 'Draft routine deleted.');
    }

    public function storeRoom(Request $request)
    {
        $data = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:80'],
            'code' => ['required', 'string', 'max:30'],
            'type' => ['required', Rule::in(['classroom', 'laboratory', 'hall'])],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);
        $duplicate = RoutineRoom::where('organization_id', $data['organization_id'])->whereRaw('LOWER(code) = ?', [strtolower($data['code'])])->exists();
        if ($duplicate) throw ValidationException::withMessages(['code' => 'This room code already exists in the organization.']);
        $data['is_active'] = true;
        RoutineRoom::create($data);
        return back()->with('success', 'Room/laboratory added.');
    }

    private function ensureShiftAssigned(int $yearId, int $organizationId, int $departmentId, int $shiftId): void
    {
        $assigned = RoutineShift::whereKey($shiftId)->whereHas('assignments', fn ($query) => $query
            ->where('academic_year_id', $yearId)->where('organization_id', $organizationId)
            ->where(fn ($query) => $query->whereNull('department_id')->orWhere('department_id', $departmentId)))->exists();
        if (! $assigned) throw ValidationException::withMessages(['routine_shift_id' => 'Assign this time slot to the selected organization or faculty first.']);
    }

    private function ensurePlanEditable(RoutinePlan $plan): void
    {
        $plan->loadMissing('academicYear');
        if ($plan->is_locked) throw ValidationException::withMessages(['lock' => 'This routine is published or its academic year is locked. Return it to draft/unlock before editing.']);
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))->filter()->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->take(3)->implode('');
    }

    private function assignStudents(RoutineLesson $lesson, RoutinePlan $plan): void
    {
        $lesson->loadMissing('section', 'groups');
        $offeringIds = $lesson->groups->pluck('subject_offering_id')->unique()->values();
        $section = $lesson->section;
        $students = Student::with(['subjectEnrollments' => fn ($query) => $query
                ->where('academic_year', $plan->academicYear->name)
                ->whereIn('subject_offering_id', $offeringIds)])
            ->where('member_type', 'student')
            ->where(fn ($query) => $query->where('section_id', $section->id)
                ->orWhere(fn ($query) => $query->whereNull('section_id')->where('section', $section->name)))
            ->orderByRaw('roll_number IS NULL')->orderBy('roll_number')->orderBy('first_name')->get()
            ->filter(fn ($student) => $student->subjectEnrollments->isNotEmpty())->values();

        $buckets = $lesson->groups->mapWithKeys(fn ($group) => [$group->id => collect()]);
        if ($lesson->mode === 'single') {
            $group = $lesson->groups->first();
            $buckets[$group->id] = $students->filter(fn ($student) => $student->subjectEnrollments->contains('subject_offering_id', $group->subject_offering_id))->values();
        } elseif ($offeringIds->count() === 1) {
            $half = (int) ceil($students->count() / 2);
            $buckets[$lesson->groups[0]->id] = $students->take($half)->values();
            $buckets[$lesson->groups[1]->id] = $students->slice($half)->values();
        } else {
            foreach ($students as $student) {
                $eligible = $lesson->groups->filter(fn ($group) => $student->subjectEnrollments->contains('subject_offering_id', $group->subject_offering_id));
                if ($eligible->isEmpty()) continue;
                $group = $eligible->sortBy(fn ($candidate) => $buckets[$candidate->id]->count())->first();
                $buckets[$group->id]->push($student);
            }
        }

        $now = now();
        $rows = $buckets->flatMap(fn ($assigned, $groupId) => $assigned->map(fn ($student) => [
            'routine_lesson_id' => $lesson->id,
            'routine_lesson_group_id' => $groupId,
            'student_id' => $student->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]))->values()->all();
        if ($rows) DB::table('routine_lesson_group_students')->insert($rows);
    }
}
