<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routine_shifts', function (Blueprint $table) {
            $table->json('breaks')->nullable()->after('break_minutes');
        });

        DB::table('routine_shifts')
            ->whereNotNull('break_after_period')
            ->whereNotNull('break_minutes')
            ->orderBy('id')
            ->eachById(function ($shift) {
                DB::table('routine_shifts')->where('id', $shift->id)->update([
                    'breaks' => json_encode([[
                        'name' => 'Break',
                        'after_period' => (int) $shift->break_after_period,
                        'minutes' => (int) $shift->break_minutes,
                    ]]),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('routine_shifts', function (Blueprint $table) {
            $table->dropColumn('breaks');
        });
    }
};
