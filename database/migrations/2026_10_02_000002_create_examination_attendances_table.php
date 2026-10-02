<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examination_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('examination_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('exam_date');
            $table->enum('status', ['present', 'absent'])->default('present');
            $table->string('reason', 255)->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            $table->unique(['examination_subject_id', 'student_id'], 'exam_attendance_subject_student_unique');
            $table->index(['examination_id', 'exam_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_attendances');
    }
};
