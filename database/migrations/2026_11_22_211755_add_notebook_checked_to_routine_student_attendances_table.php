<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routine_student_attendances', function (Blueprint $table) {
            // Null = not checked yet (no judgement made); true/false is an
            // explicit record once the teacher reviews the notebook.
            $table->boolean('notebook_checked')->nullable()->after('status');
            $table->foreignId('notebook_checked_by')->nullable()->after('notebook_checked')->constrained('users')->nullOnDelete();
            $table->timestamp('notebook_checked_at')->nullable()->after('notebook_checked_by');
        });
    }

    public function down(): void
    {
        Schema::table('routine_student_attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('notebook_checked_by');
            $table->dropColumn(['notebook_checked', 'notebook_checked_at']);
        });
    }
};
