<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Card\CardRequest;
use App\Models\Card\Organization;
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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FounderDashboardController extends Controller
{
    private const OPENS_BEFORE_MINUTES = 10;
    private const STREAK_LOOKBACK_DAYS = 21;
    private const STREAK_SCHOOL_DAYS = 12;

    public function index(Request $request)
    {
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
                'students' => $group->pluck('student')->sortBy('full_name')->values(),
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
        ]);
    }

    public function pendingApprovals()
    {
        return view('backend.founder-dashboard.pending', [
            'items' => $this->pendingApprovalCategories(),
        ]);
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
        return collect([
            (object) ['label' => 'Leave Requests', 'icon' => '🗓', 'count' => LeaveRequest::where('status', 'pending')->count(), 'route' => route('hajiri.leave-requests.index')],
            (object) ['label' => 'Staff ID Card Requests', 'icon' => '🪪', 'count' => StaffCardRequest::where('status', 'pending')->count(), 'route' => route('hajiri.staff-card-request.admin')],
            (object) ['label' => 'Student ID Card Requests', 'icon' => '🎫', 'count' => CardRequest::where('status', 'pending')->count(), 'route' => route('admin.card-requests')],
            (object) ['label' => 'Unread Contact Messages', 'icon' => '✉️', 'count' => ContactMessage::where('is_read', false)->count(), 'route' => route('admin.contacts.index')],
            (object) ['label' => 'Work Task Reviews', 'icon' => '📋', 'count' => WorkTaskSubmission::where('status', 'submitted')->count(), 'route' => route('admin.work-tasks.index')],
            (object) ['label' => 'Pending Admissions', 'icon' => '🎓', 'count' => Admission::where('status', 'Pending')->count(), 'route' => route('admin.admissions.index')],
            (object) ['label' => 'Vacancy Applications', 'icon' => '💼', 'count' => VacancyApplication::where('status', 'Pending')->count(), 'route' => route('admin.vacancies.index')],
            (object) ['label' => 'Store Requisitions Awaiting Approval', 'icon' => '📦', 'count' => StoreRequisition::where('status', 'draft')->count(), 'route' => route('admin.store.requisitions.index')],
            (object) ['label' => 'Purchase Orders Awaiting Approval', 'icon' => '🧾', 'count' => StorePurchaseOrder::where('status', 'draft')->count(), 'route' => route('admin.store.purchase-orders.index')],
            (object) ['label' => 'Exam Mark Unlock Requests', 'icon' => '🔓', 'count' => ExaminationMarkSubmission::whereNotNull('unlock_requested_at')->whereNull('unlocked_at')->count(), 'route' => route('admin.examinations.index')],
        ]);
    }

    private function lessonsForDate(Carbon $date): Collection
    {
        return RoutineLesson::query()
            ->with(['section.department', 'period', 'endPeriod', 'groups.teachers', 'groups.offering.subject'])
            ->where('day_of_week', $date->format('l'))
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
        $lessonIds = RoutineLesson::query()
            ->where('day_of_week', $date->format('l'))
            ->whereHas('plan', fn ($query) => $query->where('status', 'published'))
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return [];
        }

        $rows = RoutineStudentAttendance::query()
            ->when($includeStudent, fn ($query) => $query->with('student'))
            ->with('session.lesson.section.department')
            ->whereHas('session', fn ($query) => $query->whereDate('attendance_date', $date)
                ->whereNotNull('submitted_at')
                ->whereIn('routine_lesson_id', $lessonIds))
            ->get();

        return $rows->groupBy('student_id')->map(function (Collection $studentRows) use ($includeStudent) {
            $counted = $studentRows->where('status', '!=', 'excused');
            $absentCount = $counted->where('status', 'absent')->count();
            $status = $counted->isNotEmpty() && $absentCount > $counted->count() / 2 ? 'absent' : 'present';

            $first = $studentRows->first();

            return (object) [
                'status' => $status,
                'section_label' => $this->sectionLabel($first->session->lesson->section ?? null),
                'student' => $includeStudent ? $first->student : null,
            ];
        })->all();
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
            $rollup = $this->dailyStudentStatuses($date, includeStudent: true);
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

        $result = collect();
        foreach ($candidateStudentIds as $studentId) {
            $days = 0;
            $lastPresentDate = null;
            $student = null;
            $sectionLabel = null;

            foreach ($dailyRollups as $date => $rollup) {
                if (! array_key_exists($studentId, $rollup)) {
                    continue;
                }
                $row = $rollup[$studentId];
                $student ??= $row->student;
                $sectionLabel ??= $row->section_label;

                if ($row->status === 'absent') {
                    $days++;

                    continue;
                }

                $lastPresentDate = $date;
                break;
            }

            if ($days >= 2 && $student) {
                $result->push((object) [
                    'student' => $student,
                    'section_label' => $sectionLabel,
                    'days' => $days,
                    'last_present' => $lastPresentDate,
                    'severity' => $days >= 5 ? 'red' : ($days >= 3 ? 'orange' : 'yellow'),
                ]);
            }
        }

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

            $statuses = $this->dailyStudentStatuses($date);
            $present = collect($statuses)->where('status', 'present')->count();
            $total = count($statuses);
            $attendanceRate[] = $total > 0 ? round($present / $total * 100, 1) : null;

            $lessonIds = RoutineLesson::where('day_of_week', $date->format('l'))
                ->whereHas('plan', fn ($query) => $query->where('status', 'published'))
                ->pluck('id');
            if ($lessonIds->isEmpty()) {
                $complianceRate[] = null;
                continue;
            }
            $taken = RoutineAttendanceSession::whereIn('routine_lesson_id', $lessonIds)
                ->whereDate('attendance_date', $date)->whereNotNull('submitted_at')->count();
            $complianceRate[] = round($taken / $lessonIds->count() * 100, 1);
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
