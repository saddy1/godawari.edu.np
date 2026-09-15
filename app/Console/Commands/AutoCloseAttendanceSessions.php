<?php

namespace App\Console\Commands;

use App\Services\AttendanceSessionCloser;
use Illuminate\Console\Command;

class AutoCloseAttendanceSessions extends Command
{
    protected $signature = 'attendance:auto-close';

    protected $description = 'Finalize attendance sessions whose class period has ended but were never submitted, so they stop showing as pending indefinitely';

    public function handle(AttendanceSessionCloser $closer): int
    {
        $closed = $closer->closeStaleSessions();

        $this->components->info("Auto-closed {$closed} attendance session(s) whose class period had ended.");

        return self::SUCCESS;
    }
}
