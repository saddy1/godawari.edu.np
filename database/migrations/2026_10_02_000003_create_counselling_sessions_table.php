<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counselling_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            // Null until an admin assigns one — a student's own request starts
            // unassigned (status 'requested').
            $table->foreignId('counsellor_id')->nullable()->constrained('users')->nullOnDelete();
            // The student's own user account if they self-requested; null for
            // a session an admin booked directly without a prior request.
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('topic', 255)->nullable();
            $table->enum('status', ['requested', 'scheduled', 'completed', 'cancelled'])->default('requested');
            $table->dateTime('scheduled_at')->nullable();
            // Private notes — only the assigned counsellor and a super-admin
            // may ever read this column; every read path must enforce that
            // explicitly, since there is no row-level DB security here.
            $table->text('report')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['counsellor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counselling_sessions');
    }
};
