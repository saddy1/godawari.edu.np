<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('routine_lessons', 'end_routine_period_id')) {
            Schema::table('routine_lessons', function (Blueprint $table) {
                $table->foreignId('end_routine_period_id')->nullable()->after('routine_period_id')->constrained('routine_periods')->restrictOnDelete();
            });
        }
        DB::table('routine_lessons')->whereNull('end_routine_period_id')->update(['end_routine_period_id' => DB::raw('routine_period_id')]);

        if (! Schema::hasTable('routine_lesson_group_teachers')) {
            Schema::create('routine_lesson_group_teachers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('routine_lesson_group_id')->constrained('routine_lesson_groups')->cascadeOnDelete();
                $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->unique(['routine_lesson_group_id', 'teacher_id'], 'routine_group_teacher_unique');
            });
        }
        $now = now();
        DB::table('routine_lesson_groups')->whereNotNull('teacher_id')->orderBy('id')->get()->each(function ($group) use ($now) {
            DB::table('routine_lesson_group_teachers')->updateOrInsert(
                ['routine_lesson_group_id' => $group->id, 'teacher_id' => $group->teacher_id],
                ['created_at' => $now, 'updated_at' => $now]
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_lesson_group_teachers');
        if (Schema::hasColumn('routine_lessons', 'end_routine_period_id')) {
            Schema::table('routine_lessons', fn (Blueprint $table) => $table->dropConstrainedForeignId('end_routine_period_id'));
        }
    }
};
