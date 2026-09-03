<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('routine_shifts')->orderBy('id')->get()->each(function ($shift) {
            $lastPeriod = DB::table('routine_periods')
                ->where('routine_shift_id', $shift->id)
                ->orderByDesc('position')
                ->first();

            if (! $lastPeriod) return;

            $lastEnd = CarbonImmutable::createFromFormat('!H:i', substr((string) $lastPeriod->ends_at, 0, 5));
            $shiftEnd = CarbonImmutable::createFromFormat('!H:i', substr((string) $shift->ends_at, 0, 5));
            if (! $lastEnd->lt($shiftEnd)) return;

            $lessonNumber = DB::table('routine_periods')
                ->where('routine_shift_id', $shift->id)
                ->where('is_break', false)
                ->count() + 1;

            DB::table('routine_periods')->insert([
                'routine_shift_id' => $shift->id,
                'position' => $lastPeriod->position + 1,
                'name' => "Period {$lessonNumber}",
                'starts_at' => $lastEnd->format('H:i:s'),
                'ends_at' => $shiftEnd->format('H:i:s'),
                'is_break' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // The appended period is valid schedule data and is intentionally kept.
    }
};
