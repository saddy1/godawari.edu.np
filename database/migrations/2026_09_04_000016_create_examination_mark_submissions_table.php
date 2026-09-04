<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examination_mark_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_subject_id')->constrained('examination_subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->json('section_ids')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('unlock_requested_at')->nullable();
            $table->text('unlock_reason')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['examination_subject_id', 'teacher_id'], 'exam_mark_submission_teacher_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_mark_submissions');
    }
};
