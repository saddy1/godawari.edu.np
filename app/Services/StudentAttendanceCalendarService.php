<?php

namespace App\Services;

use App\Http\Controllers\Hajiri\NepaliCalendarController;
use App\Models\Card\Student;
use App\Models\Hajiri\HajiriSetting;
use App\Models\Hajiri\Holiday;
use App\Models\TeachingLearning\AcademicYear;
use App\Models\TeachingLearning\RoutineStudentAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Builds the "whole academic year, every month, one page" attendance
// calendar — shared by the student's own Attendance page and the HR admin's
// per-student activity view, so the two can never drift out of the same
// format. Scoped to the school's own Academic Year records (the same ones
// created in Routine Configuration) rather than a raw BS calendar year,
// since an academic year's actual start/end rarely lines up with Baisakh 1
// to Chaitra 30 — it can run, say, mid-Ashadh to mid-Jestha, spanning two
// different BS years.
class StudentAttendanceCalendarService
{
    public function forAcademicYear(Student $student, ?int $requestedAcademicYearId = null): array
    {
        $academicYears = AcademicYear::orderByDesc('starts_on')->get();
        abort_if($academicYears->isEmpty(), 404, 'No academic year has been configured yet — create one in Routine Configuration first.');

        $academicYear = $academicYears->firstWhere('id', $requestedAcademicYearId)
            ?? $academicYears->firstWhere('is_active', true)
            ?? $academicYears->first();

        $calendar = new NepaliCalendarController();
        $today = Carbon::today();
        $todayBs = $calendar->ad_2_bs($today->year, $today->month, $today->day);
        abort_unless(is_array($todayBs), 500, 'The Nepali calendar is not available for the current date.');

        $monthNames = [
            1 => 'Baisakh', 2 => 'Jestha', 3 => 'Ashadh', 4 => 'Shrawan',
            5 => 'Bhadra', 6 => 'Ashwin', 7 => 'Kartik', 8 => 'Mangsir',
            9 => 'Poush', 10 => 'Magh', 11 => 'Falgun', 12 => 'Chaitra',
        ];

        $yearStart = $academicYear->starts_on->copy()->startOfDay();
        $yearEnd = $academicYear->ends_on->copy()->endOfDay();
        $startBs = $calendar->ad_2_bs($yearStart->year, $yearStart->month, $yearStart->day);
        $endBs = $calendar->ad_2_bs($yearEnd->year, $yearEnd->month, $yearEnd->day);
        abort_unless(is_array($startBs) && is_array($endBs), 404, 'The Nepali calendar does not cover this academic year.');

        // Walk every BS (year, month) the academic year's AD range touches —
        // this can cross a BS-year boundary, so each month block carries its
        // own BS year instead of assuming one shared year.
        $monthBounds = collect();
        $cursorYear = (int) $startBs['year'];
        $cursorMonth = (int) $startBs['month'];
        $endYear = (int) $endBs['year'];
        $endMonth = (int) $endBs['month'];
        $guard = 0;
        while ($guard++ < 36) {
            $calendarYear = $calendar->getBSCal($cursorYear);
            if (! $calendarYear) break;
            $daysInMonth = (int) $calendarYear[$cursorMonth];
            $firstAd = $calendar->bs_2_ad($cursorYear, $cursorMonth, 1);
            $lastAd = $calendar->bs_2_ad($cursorYear, $cursorMonth, $daysInMonth);
            if ($firstAd && $lastAd) {
                $monthBounds->push([
                    'number' => $cursorMonth, 'bs_year' => $cursorYear, 'days_in_month' => $daysInMonth,
                    'first_ad' => Carbon::createFromDate((int) $firstAd['year'], (int) $firstAd['month'], (int) $firstAd['date'])->startOfDay(),
                    'last_ad' => Carbon::createFromDate((int) $lastAd['year'], (int) $lastAd['month'], (int) $lastAd['date'])->endOfDay(),
                ]);
            }
            if ($cursorYear === $endYear && $cursorMonth === $endMonth) break;
            $cursorMonth++;
            if ($cursorMonth > 12) {
                $cursorMonth = 1;
                $cursorYear++;
            }
        }
        abort_if($monthBounds->isEmpty(), 404, 'This academic year is unavailable in the Nepali calendar.');

        // Attendance here comes from what the teacher actually marked in class
        // (routine-based, per period) — not the Hajiri biometric device logs,
        // which track staff/campus check-in, not classroom presence.
        $attendanceRecords = RoutineStudentAttendance::query()
            ->where('student_id', $student->id)
            ->whereHas('session', fn ($q) => $q->whereBetween('attendance_date', [$yearStart->toDateString(), $yearEnd->toDateString()]))
            ->with(['session.lesson.period', 'session.lesson.groups.offering.subject', 'markedBy'])
            ->get();

        // Most lessons have a single group (one subject for the whole
        // section) — only practical-split/elective lessons have more than
        // one, and only then do we need to know which specific group this
        // student was in. Resolved in one batched query, not per row.
        $multiGroupIds = $attendanceRecords
            ->filter(fn ($row) => $row->session->lesson->groups->count() > 1)
            ->flatMap(fn ($row) => $row->session->lesson->groups->pluck('id'))
            ->unique();
        $studentGroupIds = $multiGroupIds->isNotEmpty()
            ? DB::table('routine_lesson_group_students')->where('student_id', $student->id)->whereIn('routine_lesson_group_id', $multiGroupIds)->pluck('routine_lesson_group_id')
            : collect();

        $logsByDate = $attendanceRecords
            ->map(function ($record) use ($studentGroupIds) {
                $groups = $record->session->lesson->groups;
                $group = $groups->count() === 1 ? $groups->first() : ($groups->first(fn ($g) => $studentGroupIds->contains($g->id)) ?? $groups->first());
                $period = $record->session->lesson->period;

                return [
                    'date_key' => $record->session->attendance_date->toDateString(),
                    'status' => $record->status,
                    'subject' => $group?->offering?->subject?->name ?: '—',
                    'time' => $period ? trim(($period->name ?: '').' '.($period->starts_at && $period->ends_at ? "({$period->starts_at}–{$period->ends_at})" : '')) : '—',
                    'marked_by' => $record->markedBy?->name,
                    'remarks' => $record->remarks,
                    'notebook_checked' => $record->notebook_checked,
                ];
            })
            ->groupBy('date_key');

        $holidays = collect();
        if (Schema::hasTable('holiday')) {
            $holidayQuery = Holiday::query()
                ->whereDate('date', '>=', $yearStart->toDateString())
                ->whereDate('date', '<=', $yearEnd->toDateString());

            if (Schema::hasColumn('holiday', 'status')) {
                $holidayQuery->where('status', true);
            }

            $holidays = $holidayQuery->get()->keyBy(fn ($holiday) => $holiday->date->toDateString());
        }

        $setting = HajiriSetting::current();

        $months = $monthBounds->map(function ($bounds) use ($calendar, $logsByDate, $holidays, $setting) {
            $m = $bounds['number'];
            $bsYear = $bounds['bs_year'];
            $rows = collect();

            for ($day = 1; $day <= $bounds['days_in_month']; $day++) {
                $ad = $calendar->bs_2_ad($bsYear, $m, $day);
                $date = Carbon::createFromDate((int) $ad['year'], (int) $ad['month'], (int) $ad['date']);
                $dateKey = $date->toDateString();
                $dayPeriods = $logsByDate->get($dateKey, collect());
                $holiday = $holidays->get($dateKey);
                $isWeekend = $setting->isWeekend($date->dayOfWeek);

                if ($dayPeriods->isNotEmpty()) {
                    $hasAbsent = $dayPeriods->contains(fn ($p) => $p['status'] === 'absent');
                    $hasLate = $dayPeriods->contains(fn ($p) => $p['status'] === 'late');
                    $status = $hasAbsent ? 'absent' : ($hasLate ? 'late' : 'present');
                    $statusLabel = $status === 'absent' ? 'Absent' : ($status === 'late' ? 'Late' : 'Present');
                } elseif ($holiday) {
                    $status = 'holiday';
                    $statusLabel = $holiday->label ?: 'Holiday';
                } elseif ($isWeekend) {
                    $status = 'weekend';
                    $statusLabel = 'Weekend';
                } elseif ($date->isFuture()) {
                    $status = 'upcoming';
                    $statusLabel = 'Upcoming';
                } elseif ($date->isToday()) {
                    $status = 'pending';
                    $statusLabel = 'Not recorded';
                } else {
                    $status = 'absent';
                    $statusLabel = 'Absent';
                }

                // One flag per day: checked if any period's notebook was marked
                // checked, unchecked only if every marked period was explicitly
                // marked not-checked, and "none" if the teacher never set it.
                $notebookValues = $dayPeriods->pluck('notebook_checked')->filter(fn ($v) => $v !== null);
                $notebookStatus = $notebookValues->isEmpty() ? 'none' : ($notebookValues->contains(true) ? 'checked' : 'not_checked');

                $rows->push([
                    'number' => $day,
                    'bs_date' => sprintf('%04d-%02d-%02d', $bsYear, $m, $day),
                    'ad_date' => $date->format('d M Y'),
                    'weekday' => $date->format('l'),
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'periods' => $dayPeriods->values(),
                    'notebook_status' => $notebookStatus,
                ]);
            }

            $present = $rows->where('status', 'present')->count();
            $absent = $rows->where('status', 'absent')->count();
            $workingDays = $present + $absent;

            return [
                'number' => $m,
                'bs_year' => $bsYear,
                'days' => $rows,
                'summary' => [
                    'present' => $present,
                    'absent' => $absent,
                    'holidays' => $rows->whereIn('status', ['holiday', 'weekend'])->count(),
                    'rate' => $workingDays > 0 ? round(($present / $workingDays) * 100, 1) : 0,
                    'notebook_checked' => $rows->where('notebook_status', 'checked')->count(),
                    'notebook_not_checked' => $rows->where('notebook_status', 'not_checked')->count(),
                ],
            ];
        });

        $summary = [
            'present' => $months->sum('summary.present'),
            'absent' => $months->sum('summary.absent'),
            'holidays' => $months->sum('summary.holidays'),
        ];
        $yearWorkingDays = $summary['present'] + $summary['absent'];
        $summary['rate'] = $yearWorkingDays > 0 ? round(($summary['present'] / $yearWorkingDays) * 100, 1) : 0;

        // Which month tab should be pre-selected: today's, if it falls inside
        // this academic year; otherwise just the first month of the year.
        $currentMonthEntry = $months->first(fn ($m) => (int) $m['bs_year'] === (int) $todayBs['year'] && (int) $m['number'] === (int) $todayBs['month'])
            ?? $months->first();
        $currentMonth = $currentMonthEntry['number'];
        $currentMonthBsYear = $currentMonthEntry['bs_year'];

        return compact('academicYears', 'academicYear', 'monthNames', 'months', 'summary', 'currentMonth', 'currentMonthBsYear');
    }
}
