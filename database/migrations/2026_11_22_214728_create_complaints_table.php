<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            // Students, teachers and staff are all rows in the same `students`
            // table (distinguished by member_type) — this one FK covers every
            // kind of complainant.
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('category', 50);
            $table->string('subject', 150);
            $table->text('message');
            $table->string('status', 20)->default('pending');
            $table->text('admin_response')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
