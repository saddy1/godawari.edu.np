<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE routine_lesson_groups MODIFY teacher_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::table('routine_lesson_groups')->whereNull('teacher_id')->delete();
        DB::statement('ALTER TABLE routine_lesson_groups MODIFY teacher_id BIGINT UNSIGNED NOT NULL');
    }
};
