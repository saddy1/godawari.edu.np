<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\TeachingLearning\RoutineAttendanceSession;
use App\Models\TeachingLearning\RoutineLesson;
use App\Models\TeachingLearning\RoutineStudentAttendance;
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
        $topAbsenteeClasses = collect($todayStatuses)
            ->where('status', 'absent')
            ->groupBy('section_label')
            ->map(fn ($rows, $label) => (object) ['label' => $label, 'count' => $rows->count()])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        return view('backend.founder-dashboard.index', [
            'range' => $range,
            'kpis' => $kpis,
            'notTakenRows' => $notTakenRows->take(25),
            'conflicts' => $conflicts,
            'streaks' => $streaks,
            'trend' => $trend,
            'heatmap' => $heatmap,
            'topAbsenteeClasses' => $topAbsenteeClasses,
        ]);
    }

    private function lessonsForDate(Carbon $date): Collection
    {
        return RoutineLesson::query()
            ->with(['section.department', 'period', 'endPeriod', 'groups.teachers'])
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
