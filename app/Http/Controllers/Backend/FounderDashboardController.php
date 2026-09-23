<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AbsenceReason;
use App\Models\Admission;
use App\Models\Card\CardRequest;
use App\Models\Card\Organization;
use App\Models\Card\Student;
use App\Models\Card\SubjectOffering;
use App\Models\ContactMessage;
use App\Models\Examination\ExaminationMarkSubmission;
use App\Models\Hajiri\LeaveRequest;
use App\Models\Hajiri\StaffCardRequest;
use App\Models\StorePurchaseOrder;
use App\Models\StoreRequisition;
use App\Models\TeachingLearning\RoutineAttendanceSession;
use App\Models\TeachingLearning\RoutineLesson;
use App\Models\TeachingLearning\RoutineStudentAttendance;
use App\Models\User;
use App\Models\VacancyApplication;
use App\Models\Work\WorkTaskSubmission;
use App\Services\AttendanceSessionCloser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FounderDashboardController extends Controller
{
    private const OPENS_BEFORE_MINUTES = 10;
    private const STREAK_LOOKBACK_DAYS = 21;
    private const STREAK_SCHOOL_DAYS = 12;

    // day_of_week only takes 7 values, and the same date's aggregate is asked
    // for repeatedly across trend()/heatmap()/absenceStreaks()/analysis() —
    // these two caches turn what was 150-200+ queries per page load into a
    // handful, without changing what's computed.
    private array $lessonsByWeekdayCache = [];
    private array $dailyAggregateCache = [];

    public function index(Request $request, AttendanceSessionCloser $sessionCloser)
    {
        // Self-heals sessions left "pending" after their class period ended, in
        // case the scheduled attendance:auto-close cron isn't running on this host.
        $sessionCloser->closeStaleSessions();

        $range = in_array($request->get('range'), ['week', 'month'], true) ? $request->get('range') : 'today';
        $chartDays = $range === 'month' ? 30 : 7;
        $today = Carbon::today();

        $lessons = $this->lessonsForDate($today);
        $sessionsByLesson = $this->sessionsForDate($lessons->pluck('id'), $today);

        $lessonRows = $lessons->map(function (RoutineLesson $lesson) use ($sessionsByLesson, $today) {
            $session = $sessionsByLesson->get($lesson->id);
            $periodStart = Carbon::parse($today->toDateString().' '.$lesson->period->starts_at)->subMinutes(self::OPENS_BEFORE_MINUTES);
            $started = now()->gte($periodStart);
            $taken = (bool) $session?->submitted_at;

            return (object) [
                'lesson' => $lesson,
                'session' => $session,
                'started' => $started,
                'taken' => $taken,
                'status' => $taken ? 'taken' : ($session ? 'pending' : ($started ? 'not_taken' : 'upcoming')),
                'overdue_minutes' => $started && ! $taken ? max(0, now()->diffInMinutes($periodStart)) : 0,
            ];
        });

        $startedRows = $lessonRows->where('started', true);
        $notTakenRows = $startedRows->where('taken', false)->sortByDesc('overdue_minutes')->values();

        $kpis = [
            'scheduled' => $lessonRows->count(),
            'taken' => $lessonRows->where('taken', true)->count(),
            'not_taken' => $notTakenRows->count(),
            'compliance' => $startedRows->count() > 0
                ? (int) round($startedRows->where('taken', true)->count() / $startedRows->count() * 100)
                : 100,
        ];

        $todayStatuses = $this->dailyStudentStatuses($today, includeStudent: true);
        $kpis['students_present'] = collect($todayStatuses)->where('status', 'present')->count();
        $kpis['students_absent'] = collect($todayStatuses)->where('status', 'absent')->count();

        $conflicts = $this->conflictRows($today);
        $streaks = $this->absenceStreaks($today);
        $trend = $this->trend($today, $chartDays);
        $heatmap = $this->heatmap($today, min($chartDays, 7));

        $groupStudentsBySection = fn (Collection $rows) => $rows
            ->filter(fn ($row) => $row->student)
            ->groupBy('section_label')
            ->map(fn ($group, $label) => (object) [
                'label' => $label ?: 'Unassigned',
                // unique('id') guards against a student ever being listed twice even if
                // the same person's attendance rows resolved through more than one path.
                'students' => $group->pluck('student')->filter()->unique('id')->sortBy('full_name')->values(),
            ])
            ->sortBy('label')
            ->values();

        $absentStudentsToday = $groupStudentsBySection(collect($todayStatuses)->where('status', 'absent'));
        $presentStudentsToday = $groupStudentsBySection(collect($todayStatuses)->where('status', 'present'));
        $topAbsenteeClasses = $absentStudentsToday
            ->map(fn ($group) => (object) ['label' => $group->label, 'count' => $group->students->count()])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $normalizeGender = fn (?string $gender) => match (mb_strtolower((string) $gender)) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            default => 'other',
        };
        $genderBreakdown = ['male' => ['present' => 0, 'absent' => 0], 'female' => ['present' => 0, 'absent' => 0], 'other' => ['present' => 0, 'absent' => 0]];
        foreach (collect($todayStatuses)->unique(fn ($row) => $row->student?->id) as $row) {
            if (! $row->student || ! in_array($row->status, ['present', 'absent'], true)) {
                continue;
            }
            $genderBreakdown[$normalizeGender($row->student->gender)][$row->status]++;
        }

        $formatLessonRow = fn ($row) => (object) [
            'period_label' => 'P'.$row->lesson->period->position.' · '.$row->lesson->period->name,
            'section_label' => $this->sectionLabel($row->lesson->section),
            'subject_names' => $row->lesson->groups->flatMap(fn ($group) => $group->offering ? [$group->offering->subject] : [])
                ->filter()->unique('id')->pluck('name')->implode(', ') ?: '—',
            'teacher_names' => $row->lesson->groups->flatMap->teachers->unique('id')->pluck('name')->implode(', ') ?: 'Unassigned',
            'status' => $row->status,
        ];

        return view('backend.founder-dashboard.index', [
            'range' => $range,
            'kpis' => $kpis,
            'notTakenRows' => $notTakenRows->take(25),
            'conflicts' => $conflicts,
            'streaks' => $streaks,
            'trend' => $trend,
            'heatmap' => $heatmap,
            'topAbsenteeClasses' => $topAbsenteeClasses,
            'absentStudentsToday' => $absentStudentsToday,
            'presentStudentsToday' => $presentStudentsToday,
            'allClassesDetail' => $lessonRows->map($formatLessonRow)->values(),
            'takenClassesDetail' => $lessonRows->where('taken', true)->map($formatLessonRow)->values(),
            'notTakenClassesDetail' => $notTakenRows->map($formatLessonRow)->values(),
            'genderBreakdown' => $genderBreakdown,
        ]);
    }

    public function pendingApprovals()
    {
        return view('backend.founder-dashboard.pending', [
            'items' => $this->pendingApprovalCategories(),
        ]);
    }

    public function students(Request $request)
    {
        $organizations = Organization::with(['departments' => fn ($query) => $query->where('is_active', true)
            ->with(['sections' => fn ($sections) => $sections->where('is_active', true)->orderBy('name')])])
            ->where('is_active', true)->orderBy('name')->get();
        $organization = $organizations->firstWhere('id', $request->integer('organization_id'));
        $department = $organization?->departments->firstWhere('id', $request->integer('department_id'));
        $section = $department?->sections->firstWhere('id', $request->integer('section_id'));

        $search = trim((string) $request->get('q', ''));

        $query = match (true) {
            $section !== null => $section->studentsQuery(),
            $department !== null => $department->studentsQuery(),
            $organization !== null => Student::where('organization', $organization->slug),
            default => Student::query(),
        };

        $students = $query->where('member_type', 'student')
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('roll_number', 'like', "%{$search}%");
            }))
            ->orderBy('first_name')
            ->paginate(30)
            ->withQueryString();

        return view('backend.founder-dashboard.students.index', compact(
            'organizations', 'organization', 'department', 'section', 'search', 'students'
        ));
    }

    public function showStudent(Request $request, Student $student)
    {
        $range = in_array($request->get('range'), ['week', 'month'], true) ? $request->get('range') : 'week';
        $days = $range === 'month' ? 30 : 7;
        $today = Carbon::today();

        $attendanceRows = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $attendanceRows->push((object) [
                'date' => $date,
                'status' => $this->dailyAggregate($date)->statuses[$student->id]->status ?? null,
            ]);
        }

        $presentCount = $attendanceRows->where('status', 'present')->count();
        $absentCount = $attendanceRows->where('status', 'absent')->count();
        $recordedCount = $presentCount + $absentCount;
        $streakDays = $this->studentAbsenceStreak($student, $today);

        $recentRemarks = RoutineStudentAttendance::where('student_id', $student->id)
            ->whereNotNull('remarks')->where('remarks', '!=', '')
            ->with('session')
            ->get()
            ->unique(fn ($row) => $row->session?->attendance_date?->toDateString())
            ->sortByDesc(fn ($row) => $row->session?->attendance_date)
            ->take(20)
            ->values();

        return view('backend.founder-dashboard.students.show', compact(
            'student', 'range', 'attendanceRows', 'presentCount', 'absentCount', 'recordedCount', 'streakDays', 'recentRemarks'
        ));
    }

    public function updateStudentContact(Request $request, Student $student)
    {
        $data = $request->validate([
            'guardian_contact' => ['required', 'string', 'max:30'],
        ]);

        $student->update(['guardian_contact' => $data['guardian_contact']]);

        return back()->with('success', 'Contact number saved.');
    }

    public function saveAbsenceRemark(Request $request, Student $student)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        $updated = RoutineStudentAttendance::where('student_id', $student->id)
            ->whereHas('session', fn ($query) => $query->whereDate('attendance_date', $data['date']))
            ->update(['remarks' => $data['remark']]);

        if ($updated === 0) {
            return back()->with('error', 'No attendance record found for that date to attach a remark to.');
        }

        if (filled($data['remark'] ?? null)) {
            AbsenceReason::recordUsage($data['remark']);
        }

        return back()->with('success', 'Remark saved.');
    }

    /**
     * Organization → Class → Section → Student drill-down of who's absent on a
     * given date. State lives entirely in the query string so a refresh (or a
     * shared link) reopens exactly the same view.
     */
    public function absences(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->get('date')) : Carbon::today();
        $minStreak = $request->filled('min_streak') ? max(1, $request->integer('min_streak')) : null;

        $organizations = Organization::with(['departments' => fn ($query) => $query->where('is_active', true)
            ->with(['sections' => fn ($sections) => $sections->where('is_active', true)->orderBy('name')])])
            ->where('is_active', true)->orderBy('name')->get();

        $organization = $organizations->firstWhere('id', $request->integer('organization_id'));
        $department = $organization?->departments->firstWhere('id', $request->integer('department_id'));
        $section = $department?->sections->firstWhere('id', $request->integer('section_id'));

        $absentStatuses = collect($this->dailyAggregate($date)->statuses)->filter(fn ($row) => $row->status === 'absent');
        $absentStudentIds = $absentStatuses->keys();
        $absentStudents = Student::whereIn('id', $absentStudentIds)->get();

        $orgCounts = $absentStudents->groupBy('organization')->map->count();
        $orgSummaries = $organizations->map(fn ($org) => (object) [
            'organization' => $org,
            'count' => $orgCounts->get($org->slug, 0),
        ])->sortByDesc('count')->values();

        $deptSummaries = collect();
        $sectionSummaries = collect();
        $studentRows = collect();

        if ($organization) {
            $orgAbsentStudents = $absentStudents->where('organization', $organization->slug);
            $deptCounts = $orgAbsentStudents->groupBy('stream')->map->count();
            $deptSummaries = $organization->departments->map(fn ($dept) => (object) [
                'department' => $dept,
                'count' => $deptCounts->get($dept->name, 0),
            ])->sortByDesc('count')->values();
        }

        if ($department) {
            $deptAbsentStudents = $absentStudents
                ->where('organization', $organization->slug)
                ->where('stream', $department->name);

            $sectionSummaries = $department->sections->map(function ($sec) use ($deptAbsentStudents) {
                $count = $deptAbsentStudents->filter(fn ($s) => $s->section_id
                    ? (int) $s->section_id === $sec->id
                    : $s->section === $sec->name)->count();

                return (object) ['section' => $sec, 'count' => $count];
            })->sortByDesc('count')->values();
        }

        $reasonSuggestions = AbsenceReason::orderByDesc('usage_count')->orderBy('label')->pluck('label');
        $hasReason = $request->boolean('has_reason');

        if ($section) {
            $streaks = $this->absenceStreaks($date)->keyBy(fn ($row) => $row->student->id);

            $sectionStudents = $absentStudents
                ->where('organization', $organization->slug)
                ->where('stream', $department->name)
                ->filter(fn ($s) => $s->section_id ? (int) $s->section_id === $section->id : $s->section === $section->name);

            $studentIds = $sectionStudents->pluck('id');
            $todayRemarks = RoutineStudentAttendance::whereIn('student_id', $studentIds)
                ->whereHas('session', fn ($query) => $query->whereDate('attendance_date', $date))
                ->whereNotNull('remarks')->where('remarks', '!=', '')
                ->pluck('remarks', 'student_id');

            $yesterday = $date->copy()->subDay();
            $yesterdayAbsentIds = collect($this->dailyAggregate($yesterday)->statuses)
                ->filter(fn ($row) => $row->status === 'absent')->keys();
            $yesterdayRemarks = RoutineStudentAttendance::whereIn('student_id', $studentIds->intersect($yesterdayAbsentIds))
                ->whereHas('session', fn ($query) => $query->whereDate('attendance_date', $yesterday))
                ->whereNotNull('remarks')->where('remarks', '!=', '')
                ->pluck('remarks', 'student_id');

            $studentRows = $sectionStudents
                ->map(fn ($student) => (object) [
                    'student' => $student,
                    'streak_days' => $streaks->get($student->id)?->days ?? 1,
                    'remark_today' => $todayRemarks->get($student->id),
                    'was_absent_yesterday' => $yesterdayAbsentIds->contains($student->id),
                    'remark_yesterday' => $yesterdayRemarks->get($student->id),
                ])
                ->when($minStreak, fn ($rows) => $rows->filter(fn ($row) => $row->streak_days >= $minStreak))
                ->when($hasReason, fn ($rows) => $rows->filter(fn ($row) => filled($row->remark_today)))
                ->sortByDesc('streak_days')
                ->values();
        }

        return view('backend.founder-dashboard.absences', compact(
            'date', 'minStreak', 'hasReason', 'organizations', 'organization', 'department', 'section',
            'orgSummaries', 'deptSummaries', 'sectionSummaries', 'studentRows', 'reasonSuggestions'
        ));
    }

    private function studentAbsenceStreak(Student $student, Carbon $asOf): int
    {
        $days = 0;
        for ($i = 0; $i < self::STREAK_LOOKBACK_DAYS; $i++) {
            $status = $this->dailyAggregate($asOf->copy()->subDays($i))->statuses[$student->id]->status ?? null;
            if ($status === null) {
                continue;
            }
            if ($status === 'absent') {
                $days++;

                continue;
            }
            break;
        }

        return $days;
    }

    public function classAttendance(Request $request)
    {
        $organizations = Organization::with(['departments' => fn ($query) => $query->where('is_active', true)
            ->with(['sections' => fn ($sections) => $sections->where('is_active', true)->orderBy('name')])])
            ->where('is_active', true)->orderBy('name')->get();
        $organization = $organizations->firstWhere('id', $request->integer('organization_id'));
        $department = $organization?->departments->firstWhere('id', $request->integer('department_id'));
        $section = $department?->sections->firstWhere('id', $request->integer('section_id'));

        $sectionIds = null;
        if ($section) {
            $sectionIds = collect([$section->id]);
        } elseif ($department) {
            $sectionIds = $department->sections->pluck('id');
        } elseif ($organization) {
            $sectionIds = $organization->departments->flatMap->sections->pluck('id');
        }

        $subjects = SubjectOffering::with('subject')
            ->when($department, fn ($query) => $query->where('department_id', $department->id))
            ->get()->pluck('subject')->filter()->unique('id')->sortBy('name')->values();
        $subjectId = $request->integer('subject_id') ?: null;
        if ($subjectId && ! $subjects->contains('id', $subjectId)) {
            $subjectId = null;
        }

        $teachers = User::role('teacher')->orderBy('name')->get(['id', 'name']);
        $teacherId = $request->integer('teacher_id') ?: null;

        $studentQuery = trim((string) $request->get('q', ''));

        $today = Carbon::today();
        $from = $request->filled('from') ? Carbon::parse($request->get('from')) : $today->copy();
        $to = $request->filled('to') ? Carbon::parse($request->get('to')) : $today->copy();
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > 62) {
            $to = $from->copy()->addDays(62);
        }

        $filters = compact('organizations', 'organization', 'department', 'section', 'subjects', 'subjectId', 'teachers', 'teacherId', 'studentQuery', 'from', 'to');

        $matchesStudentQuery = function ($student) use ($studentQuery) {
            if ($studentQuery === '') {
                return true;
            }
            $needle = mb_strtolower($studentQuery);

            return str_contains(mb_strtolower($student->full_name ?? ''), $needle)
                || str_contains(mb_strtolower((string) ($student->roll_number ?? '')), $needle);
        };

        if ($from->isSameDay($to) && $sectionIds !== null && $sectionIds->count() === 1) {
            $lessons = $this->filteredLessonsForDate($from, $sectionIds, $subjectId, $teacherId);
            $periods = $lessons->pluck('period')->filter()->unique('position')->sortBy('position')->values();

            $rows = collect();
            if ($lessons->isNotEmpty()) {
                $records = RoutineStudentAttendance::query()
                    ->with(['student', 'session.lesson.period'])
                    ->whereHas('session', fn ($query) => $query->whereDate('attendance_date', $from)
                        ->whereNotNull('submitted_at')
                        ->whereIn('routine_lesson_id', $lessons->pluck('id')))
                    ->get();

                $rows = $records->groupBy('student_id')->map(function (Collection $studentRows) use ($periods) {
                    $counted = $studentRows->where('status', '!=', 'excused');
                    $absentCount = $counted->where('status', 'absent')->count();
                    $dayStatus = $counted->isNotEmpty() && $absentCount > $counted->count() / 2 ? 'absent' : 'present';
                    $byPosition = $studentRows->keyBy(fn ($row) => $row->session->lesson->period->position ?? null);

                    return (object) [
                        'student' => $studentRows->first()->student,
                        'day_status' => $dayStatus,
                        'cells' => $periods->map(fn ($period) => (object) [
                            'period' => $period,
                            'status' => $byPosition->get($period->position)?->status,
                        ]),
                    ];
                })->filter(fn ($row) => $matchesStudentQuery($row->student))
                    ->sortBy(fn ($row) => $row->student->full_name ?? '')->values();
            }

            return view('backend.founder-dashboard.attendance', array_merge($filters, [
                'mode' => 'daily',
                'periods' => $periods,
                'rows' => $rows,
                'noLessonsToday' => $lessons->isEmpty(),
                'summary' => [
                    'total' => $rows->count(),
                    'present' => $rows->where('day_status', 'present')->count(),
                    'absent' => $rows->where('day_status', 'absent')->count(),
                ],
            ]));
        }

        $studentAgg = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $lessons = $this->filteredLessonsForDate($cursor, $sectionIds, $subjectId, $teacherId);
            if ($lessons->isNotEmpty()) {
                $records = RoutineStudentAttendance::query()
                    ->with(['student', 'session.lesson.section.department'])
                    ->whereHas('session', fn ($query) => $query->whereDate('attendance_date', $cursor)
                        ->whereNotNull('submitted_at')
                        ->whereIn('routine_lesson_id', $lessons->pluck('id')))
                    ->get();

                foreach ($records->groupBy('student_id') as $studentId => $studentRows) {
                    $counted = $studentRows->where('status', '!=', 'excused');
                    if ($counted->isEmpty()) {
                        continue;
                    }
                    $absentCount = $counted->where('status', 'absent')->count();
                    $dayStatus = $absentCount > $counted->count() / 2 ? 'absent' : 'present';
                    $first = $studentRows->first();

                    $studentAgg[$studentId] ??= [
                        'student' => $first->student,
                        'section_label' => $this->sectionLabel($first->session->lesson->section ?? null),
                        'scheduled' => 0, 'present' => 0, 'absent' => 0,
                    ];
                    $studentAgg[$studentId]['scheduled']++;
                    $studentAgg[$studentId][$dayStatus]++;
                }
            }
            $cursor->addDay();
        }

        $rows = collect($studentAgg)->map(fn ($row) => (object) [
            'student' => $row['student'],
            'section_label' => $row['section_label'],
            'scheduled' => $row['scheduled'],
            'present' => $row['present'],
            'absent' => $row['absent'],
            'rate' => $row['scheduled'] > 0 ? (int) round($row['present'] / $row['scheduled'] * 100) : 0,
        ])->filter(fn ($row) => $matchesStudentQuery($row->student))
            ->sortBy('rate')->values();

        return view('backend.founder-dashboard.attendance', array_merge($filters, [
            'mode' => 'range',
            'rows' => $rows,
            'summary' => [
                'students' => $rows->count(),
                'avg_rate' => $rows->count() > 0 ? (int) round($rows->avg('rate')) : 0,
            ],
        ]));
    }

    private function filteredLessonsForDate(Carbon $date, ?Collection $sectionIds, ?int $subjectId, ?int $teacherId): Collection
    {
        $lessons = $this->lessonsForDate($date);
        if ($sectionIds !== null) {
            $lessons = $lessons->whereIn('section_id', $sectionIds->all());
        }
        if ($subjectId || $teacherId) {
            $lessons = $lessons->filter(fn ($lesson) => $lesson->groups->contains(function ($group) use ($subjectId, $teacherId) {
                $subjectOk = ! $subjectId || $group->offering?->subject_id === $subjectId;
                $teacherOk = ! $teacherId || $group->teachers->contains('id', $teacherId);

                return $subjectOk && $teacherOk;
            }));
        }

        return $lessons->values();
    }

    public function analysis()
    {
        $today = Carbon::today();
        $windowDays = 20;
        $maxLookback = 60;

        $teacherStats = [];
        $sectionStats = [];
        $daysScanned = 0;

        for ($i = 0; $daysScanned < $windowDays && $i < $maxLookback; $i++) {
            $date = $today->copy()->subDays($i);
            $lessons = $this->lessonsForDate($date);
            if ($lessons->isEmpty()) {
                continue;
            }
            $daysScanned++;

            $sessionsByLesson = $this->sessionsForDate($lessons->pluck('id'), $date);
            foreach ($lessons as $lesson) {
                $taken = (bool) $sessionsByLesson->get($lesson->id)?->submitted_at;
                foreach ($lesson->groups->flatMap->teachers->unique('id') as $teacher) {
                    $teacherStats[$teacher->id] ??= ['name' => $teacher->name, 'scheduled' => 0, 'taken' => 0];
                    $teacherStats[$teacher->id]['scheduled']++;
                    if ($taken) {
                        $teacherStats[$teacher->id]['taken']++;
                    }
                }
            }

            foreach ($this->dailyStudentStatuses($date) as $row) {
                $label = $row->section_label ?? 'Unassigned';
                $sectionStats[$label] ??= ['present' => 0, 'total' => 0];
                $sectionStats[$label]['total']++;
                if ($row->status === 'present') {
                    $sectionStats[$label]['present']++;
                }
            }
        }

        $teacherRanking = collect($teacherStats)->map(fn ($stat) => (object) [
            'name' => $stat['name'],
            'scheduled' => $stat['scheduled'],
            'taken' => $stat['taken'],
            'rate' => $stat['scheduled'] > 0 ? (int) round($stat['taken'] / $stat['scheduled'] * 100) : 0,
        ])->sortBy('rate')->values();

        $sectionRanking = collect($sectionStats)->map(fn ($stat, $label) => (object) [
            'label' => $label,
            'total' => $stat['total'],
            'rate' => $stat['total'] > 0 ? (int) round($stat['present'] / $stat['total'] * 100) : 0,
        ])->sortBy('rate')->values();

        $trend = $this->trend($today, 30);

        return view('backend.founder-dashboard.analysis', [
            'teacherRanking' => $teacherRanking,
            'sectionRanking' => $sectionRanking,
            'trend' => $trend,
            'daysScanned' => $daysScanned,
        ]);
    }

    private function pendingApprovalCategories(): Collection
    {
        $row = fn (string $title, ?string $subtitle, $date) => (object) [
            'title' => $title ?: '—',
            'subtitle' => $subtitle ?: null,
            'date' => $date,
        ];

        $limit = 20;

        $leaveRequests = LeaveRequest::where('status', 'pending')->with('user')->latest()->limit($limit)->get()
            ->map(fn ($r) => $row($r->user->name ?? 'Staff', ($r->days_count ?? '?').' day(s) · '.($r->reason ?: 'No reason given'), $r->start_date));

        $staffCardRequests = StaffCardRequest::where('status', 'pending')->with('user')->latest()->limit($limit)->get()
            ->map(fn ($r) => $row($r->user->name ?? 'Staff', $r->reason, $r->created_at));

        $studentCardRequests = CardRequest::where('status', 'pending')->with('student')->latest()->limit($limit)->get()
            ->map(fn ($r) => $row($r->student->full_name ?? 'Student', $r->student->roll_number ?? null, $r->created_at));

        $contactMessages = ContactMessage::where('is_read', false)->latest()->limit($limit)->get()
            ->map(fn ($r) => $row($r->name ?: $r->email, $r->subject ?: str($r->message)->limit(80), $r->created_at));

        $workTaskReviews = WorkTaskSubmission::where('status', 'submitted')->with(['task', 'submittedBy'])->latest()->limit($limit)->get()
            ->map(fn ($r) => $row($r->task->title ?? 'Task', 'Submitted by '.($r->submittedBy->name ?? 'staff'), $r->submitted_at));

        $admissions = Admission::where('status', 'Pending')->latest()->limit($limit)->get()
            ->map(fn ($r) => $row($r->student_name, $r->applied_grade, $r->created_at));

        $vacancyApplications = VacancyApplication::where('status', 'Pending')->with('vacancy')->latest()->limit($limit)->get()
            ->map(fn ($r) => $row($r->full_name, $r->vacancy->title ?? null, $r->created_at));

        $storeRequisitions = StoreRequisition::where('status', 'draft')->latest()->limit($limit)->get()
            ->map(fn ($r) => $row($r->requisition_no, ($r->requested_by_name ?: '—').' · '.($r->purpose ?: 'No purpose given'), $r->requested_at));

        $purchaseOrders = StorePurchaseOrder::where('status', 'draft')->latest()->limit($limit)->get()
            ->map(fn ($r) => $row($r->order_no, $r->supplier_name, $r->order_date));

        $markUnlocks = ExaminationMarkSubmission::whereNotNull('unlock_requested_at')->whereNull('unlocked_at')
            ->with(['teacher', 'examinationSubject.offering.subject'])->latest('unlock_requested_at')->limit($limit)->get()
            ->map(fn ($r) => $row($r->teacher->name ?? 'Teacher', $r->examinationSubject->offering->subject->name ?? null, $r->unlock_requested_at));

        return collect([
            (object) ['label' => 'Leave Requests', 'icon' => '🗓', 'count' => LeaveRequest::where('status', 'pending')->count(), 'items' => $leaveRequests],
            (object) ['label' => 'Staff ID Card Requests', 'icon' => '🪪', 'count' => StaffCardRequest::where('status', 'pending')->count(), 'items' => $staffCardRequests],
            (object) ['label' => 'Student ID Card Requests', 'icon' => '🎫', 'count' => CardRequest::where('status', 'pending')->count(), 'items' => $studentCardRequests],
            (object) ['label' => 'Unread Contact Messages', 'icon' => '✉️', 'count' => ContactMessage::where('is_read', false)->count(), 'items' => $contactMessages],
            (object) ['label' => 'Work Task Reviews', 'icon' => '📋', 'count' => WorkTaskSubmission::where('status', 'submitted')->count(), 'items' => $workTaskReviews],
            (object) ['label' => 'Pending Admissions', 'icon' => '🎓', 'count' => Admission::where('status', 'Pending')->count(), 'items' => $admissions],
            (object) ['label' => 'Vacancy Applications', 'icon' => '💼', 'count' => VacancyApplication::where('status', 'Pending')->count(), 'items' => $vacancyApplications],
            (object) ['label' => 'Store Requisitions Awaiting Approval', 'icon' => '📦', 'count' => StoreRequisition::where('status', 'draft')->count(), 'items' => $storeRequisitions],
            (object) ['label' => 'Purchase Orders Awaiting Approval', 'icon' => '🧾', 'count' => StorePurchaseOrder::where('status', 'draft')->count(), 'items' => $purchaseOrders],
            (object) ['label' => 'Exam Mark Unlock Requests', 'icon' => '🔓', 'count' => ExaminationMarkSubmission::whereNotNull('unlock_requested_at')->whereNull('unlocked_at')->count(), 'items' => $markUnlocks],
        ]);
    }

    private function lessonsForDate(Carbon $date): Collection
    {
        $weekday = $date->format('l');

        return $this->lessonsByWeekdayCache[$weekday] ??= RoutineLesson::query()
            ->with(['section.department', 'period', 'endPeriod', 'groups.teachers', 'groups.offering.subject'])
            ->where('day_of_week', $weekday)
            ->whereHas('plan', fn ($query) => $query->where('status', 'published'))
            ->get()
            ->sortBy(fn ($lesson) => $lesson->period->starts_at)
            ->values();
    }

    private function sessionsForDate(Collection $lessonIds, Carbon $date): Collection
    {
        if ($lessonIds->isEmpty()) {
            return collect();
        }

        return RoutineAttendanceSession::query()
            ->whereIn('routine_lesson_id', $lessonIds)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('routine_lesson_id');
    }

    private function sectionLabel($section): ?string
    {
        if (! $section) {
            return null;
        }

        return trim(($section->department->name ?? '').' - '.$section->name, ' -');
    }

    /**
     * Per-student attendance for one day, derived from that day's *submitted* sessions:
     * a student counts as "absent" for the day when more than half of their non-excused
     * periods that day are marked absent. Days with no submitted sessions return empty.
     */
    private function dailyStudentStatuses(Carbon $date, bool $includeStudent = false): array
    {
        $statuses = $this->dailyAggregate($date)->statuses;

        return $includeStudent ? $this->attachStudents($statuses) : $statuses;
    }

    /**
     * Full Student models only for the specific student IDs given — resolved
     * once per call, never cached across dates. Kept separate from
     * dailyAggregate()'s cache so that cache stays cheap to hold for many days
     * at once (trend/heatmap/streaks) without also retaining full models +
     * their relations in memory for every one of those days simultaneously.
     */
    private function attachStudents(array $statuses): array
    {
        if (empty($statuses)) {
            return [];
        }

        $students = Student::whereIn('id', array_keys($statuses))->get()->keyBy('id');

        return collect($statuses)->map(fn ($row, $id) => (object) [
            'status' => $row->status,
            'section_label' => $row->section_label,
            'student' => $students->get($id),
        ])->all();
    }

    /**
     * One cached fetch per date backing dailyStudentStatuses() AND trend()'s
     * compliance rate — both used to be computed independently per date across
     * several methods, multiplying the same queries many times over.
     *
     * Deliberately lightweight (status + a section label string, no Eloquent
     * models) so caching many days at once — trend() covers up to 30 — doesn't
     * also mean holding that many days' worth of full Student/session/lesson
     * relation chains in memory simultaneously.
     */
    private function dailyAggregate(Carbon $date): object
    {
        $key = $date->toDateString();
        if (isset($this->dailyAggregateCache[$key])) {
            return $this->dailyAggregateCache[$key];
        }

        $lessons = $this->lessonsForDate($date);
        $lessonIds = $lessons->pluck('id');

        if ($lessonIds->isEmpty()) {
            return $this->dailyAggregateCache[$key] = (object) ['statuses' => [], 'scheduled' => 0, 'taken' => 0];
        }

        $sectionLabelByLesson = $lessons->mapWithKeys(fn ($lesson) => [$lesson->id => $this->sectionLabel($lesson->section)]);

        $taken = $this->sessionsForDate($lessonIds, $date)->whereNotNull('submitted_at')->count();

        $rows = RoutineStudentAttendance::query()
            ->select('student_id', 'status', 'routine_attendance_session_id')
            ->with('session:id,routine_lesson_id')
            ->whereHas('session', fn ($query) => $query->whereDate('attendance_date', $date)
                ->whereNotNull('submitted_at')
                ->whereIn('routine_lesson_id', $lessonIds))
            ->get();

        $statuses = $rows->groupBy('student_id')->map(function (Collection $studentRows) use ($sectionLabelByLesson) {
            $counted = $studentRows->where('status', '!=', 'excused');
            $absentCount = $counted->where('status', 'absent')->count();
            $status = $counted->isNotEmpty() && $absentCount > $counted->count() / 2 ? 'absent' : 'present';

            $first = $studentRows->first();

            return (object) [
                'status' => $status,
                'section_label' => $sectionLabelByLesson->get($first->session->routine_lesson_id),
            ];
        })->all();

        return $this->dailyAggregateCache[$key] = (object) [
            'statuses' => $statuses,
            'scheduled' => $lessonIds->count(),
            'taken' => $taken,
        ];
    }

    /**
     * Students whose most recent school day was marked absent, with how many
     * consecutive prior school days (skipping days without any records) were also absent.
     */
    private function absenceStreaks(Carbon $today): Collection
    {
        $dailyRollups = [];
        for ($i = 0; $i < self::STREAK_LOOKBACK_DAYS && count($dailyRollups) < self::STREAK_SCHOOL_DAYS; $i++) {
            $date = $today->copy()->subDays($i);
            // Lightweight (no student models) — only the final candidates below
            // get their Student resolved, not every student on every scanned day.
            $rollup = $this->dailyAggregate($date)->statuses;
            if (! empty($rollup)) {
                $dailyRollups[$date->toDateString()] = $rollup;
            }
        }

        $mostRecentDate = array_key_first($dailyRollups);
        if (! $mostRecentDate) {
            return collect();
        }

        $candidateStudentIds = collect($dailyRollups[$mostRecentDate])
            ->filter(fn ($row) => $row->status === 'absent')
            ->keys();

        $streakData = [];
        foreach ($candidateStudentIds as $studentId) {
            $days = 0;
            $lastPresentDate = null;
            $sectionLabel = null;

            foreach ($dailyRollups as $date => $rollup) {
                if (! array_key_exists($studentId, $rollup)) {
                    continue;
                }
                $row = $rollup[$studentId];
                $sectionLabel ??= $row->section_label;

                if ($row->status === 'absent') {
                    $days++;

                    continue;
                }

                $lastPresentDate = $date;
                break;
            }

            if ($days >= 2) {
                $streakData[$studentId] = ['section_label' => $sectionLabel, 'days' => $days, 'last_present' => $lastPresentDate];
            }
        }

        if (empty($streakData)) {
            return collect();
        }

        $students = Student::whereIn('id', array_keys($streakData))->get()->keyBy('id');

        $result = collect($streakData)->map(fn ($data, $studentId) => (object) [
            'student' => $students->get($studentId),
            'section_label' => $data['section_label'],
            'days' => $data['days'],
            'last_present' => $data['last_present'],
            'severity' => $data['days'] >= 5 ? 'red' : ($data['days'] >= 3 ? 'orange' : 'yellow'),
        ])->filter(fn ($row) => $row->student)->values();

        return $result->sortByDesc('days')->values();
    }

    /**
     * Today's per-student per-period attendance flips (present in one period, absent in another).
     */
    private function conflictRows(Carbon $date): Collection
    {
        $lessons = $this->lessonsForDate($date);
        $lessonIds = $lessons->pluck('id');
        if ($lessonIds->isEmpty()) {
            return collect();
        }

        $periods = $lessons->pluck('period')->filter()->unique('position')->sortBy('position')->values();

        $rows = RoutineStudentAttendance::query()
            ->with(['student', 'session.lesson.period', 'session.lesson.section.department'])
            ->whereHas('session', fn ($query) => $query->whereDate('attendance_date', $date)
                ->whereNotNull('submitted_at')
                ->whereIn('routine_lesson_id', $lessonIds))
            ->get();

        $conflicts = collect();
        foreach ($rows->groupBy('student_id') as $studentRows) {
            $counted = $studentRows->where('status', '!=', 'excused');
            $hasPresentLike = $counted->contains(fn ($row) => in_array($row->status, ['present', 'late'], true));
            $hasAbsent = $counted->contains(fn ($row) => $row->status === 'absent');

            if (! ($hasPresentLike && $hasAbsent)) {
                continue;
            }

            $first = $studentRows->first();
            $byPosition = $studentRows->keyBy(fn ($row) => $row->session->lesson->period->position ?? null);

            $conflicts->push((object) [
                'student' => $first->student,
                'section_label' => $this->sectionLabel($first->session->lesson->section ?? null),
                'cells' => $periods->map(function ($period) use ($byPosition) {
                    $row = $byPosition->get($period->position);

                    return (object) ['period' => $period, 'status' => $row?->status];
                }),
            ]);
        }

        return $conflicts->values();
    }

    private function trend(Carbon $today, int $days): array
    {
        $labels = [];
        $attendanceRate = [];
        $complianceRate = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $labels[] = $date->format('D');

            $aggregate = $this->dailyAggregate($date);
            $present = collect($aggregate->statuses)->where('status', 'present')->count();
            $total = count($aggregate->statuses);
            $attendanceRate[] = $total > 0 ? round($present / $total * 100, 1) : null;
            $complianceRate[] = $aggregate->scheduled > 0 ? round($aggregate->taken / $aggregate->scheduled * 100, 1) : null;
        }

        return ['labels' => $labels, 'attendance' => $attendanceRate, 'compliance' => $complianceRate];
    }

    private function heatmap(Carbon $today, int $days): array
    {
        $days = max(1, $days);
        $dates = [];
        $bySectionDate = [];
        $sectionLabels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $dates[] = $date->toDateString();

            foreach ($this->dailyStudentStatuses($date, includeStudent: false) as $row) {
                $label = $row->section_label ?? 'Unassigned';
                $sectionLabels[$label] = true;
                $bySectionDate[$label][$date->toDateString()]['total'] = ($bySectionDate[$label][$date->toDateString()]['total'] ?? 0) + 1;
                if ($row->status === 'present') {
                    $bySectionDate[$label][$date->toDateString()]['present'] = ($bySectionDate[$label][$date->toDateString()]['present'] ?? 0) + 1;
                }
            }
        }

        $sections = collect(array_keys($sectionLabels))->sort()->values();
        $grid = $sections->map(function ($label) use ($dates, $bySectionDate) {
            return [
                'label' => $label,
                'cells' => collect($dates)->map(function ($date) use ($label, $bySectionDate) {
                    $cell = $bySectionDate[$label][$date] ?? null;
                    $percent = $cell && $cell['total'] > 0 ? round(($cell['present'] ?? 0) / $cell['total'] * 100) : null;

                    return ['date' => $date, 'percent' => $percent];
                })->all(),
            ];
        });

        return ['dates' => $dates, 'sections' => $grid];
    }
}
