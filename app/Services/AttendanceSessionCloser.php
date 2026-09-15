<?php

namespace App\Services;

use App\Models\TeachingLearning\RoutineAttendanceSession;
use Carbon\Carbon;

class AttendanceSessionCloser
{
    /**
     * Finalize attendance sessions whose class period has already ended but
     * were never explicitly submitted by the teacher. Whatever attendance was
     * captured stays as-is — this only stops the session from sitting open
     * (and showing as "Pending" forever) once it can no longer be edited.
     */
    public function closeStaleSessions(): int
    {
        $closed = 0;

        RoutineAttendanceSession::query()
            ->whereNull('submitted_at')
            ->with(['lesson.period', 'lesson.endPeriod'])
            ->chunkById(200, function ($sessions) use (&$closed) {
                foreach ($sessions as $session) {
                    $lesson = $session->lesson;
                    if (! $lesson || ! $lesson->period) {
                        continue;
                    }

                    $endPeriod = $lesson->endPeriod ?: $lesson->period;
                    $closesAt = Carbon::parse($session->attendance_date->toDateString().' '.$endPeriod->ends_at);

                    if (now()->gte($closesAt)) {
                        $session->update(['submitted_at' => $closesAt]);
                        $closed++;
                    }
                }
            });

        return $closed;
    }
}
