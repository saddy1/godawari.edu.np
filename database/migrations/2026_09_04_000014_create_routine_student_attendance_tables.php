<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL DDL is not transactional. These guards recover a first run that
        // stopped while creating the second table (for example, an FK-name limit).
        Schema::dropIfExists('routine_student_attendances');
        Schema::dropIfExists('routine_attendance_sessions');

        Schema::create('routine_attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_lesson_id')->constrained('routine_lessons')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['routine_lesson_id', 'attendance_date'], 'routine_attendance_lesson_date_unique');
        });

        Schema::create('routine_student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_attendance_session_id');
            $table->foreign('routine_attendance_session_id', 'rsa_session_fk')->references('id')->on('routine_attendance_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('status', 20)->default('present');
            $table->string('remarks', 255)->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();
            $table->unique(['routine_attendance_session_id', 'student_id'], 'routine_attendance_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_student_attendances');
        Schema::dropIfExists('routine_attendance_sessions');
    }
};
