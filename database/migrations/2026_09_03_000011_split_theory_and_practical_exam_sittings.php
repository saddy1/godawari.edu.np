<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examination_subjects', function (Blueprint $table) {
            $table->foreignId('practical_teacher_id')->nullable()->after('teacher_id')->constrained('users')->nullOnDelete();
            $table->date('practical_exam_date')->nullable()->after('duration_minutes');
            $table->time('practical_starts_at')->nullable()->after('practical_exam_date');
            $table->unsignedSmallInteger('practical_duration_minutes')->nullable()->after('practical_starts_at');
            $table->index(['practical_teacher_id', 'practical_exam_date'], 'exam_practical_teacher_date_index');
        });

        Schema::table('examination_marks', function (Blueprint $table) {
            $table->boolean('theory_is_absent')->default(false)->after('practical_marks');
            $table->boolean('practical_is_absent')->default(false)->after('theory_is_absent');
        });

        DB::table('examination_marks')->where('is_absent', true)->update([
            'theory_is_absent' => true,
            'practical_is_absent' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('examination_marks', function (Blueprint $table) {
            $table->dropColumn(['theory_is_absent', 'practical_is_absent']);
        });

        Schema::table('examination_subjects', function (Blueprint $table) {
            $table->dropIndex('exam_practical_teacher_date_index');
            $table->dropConstrainedForeignId('practical_teacher_id');
            $table->dropColumn(['practical_exam_date', 'practical_starts_at', 'practical_duration_minutes']);
        });
    }
};
