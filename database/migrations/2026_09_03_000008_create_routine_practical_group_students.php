<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_lesson_group_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_lesson_id')->constrained('routine_lessons')->cascadeOnDelete();
            $table->foreignId('routine_lesson_group_id')->constrained('routine_lesson_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['routine_lesson_id', 'student_id'], 'routine_lesson_student_unique');
            $table->unique(['routine_lesson_group_id', 'student_id'], 'routine_group_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_lesson_group_students');
    }
};
