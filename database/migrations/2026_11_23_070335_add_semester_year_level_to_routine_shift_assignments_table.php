<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routine_shift_assignments', function (Blueprint $table) {
            // Null = whole department/organization. Set = a faculty's own
            // semester or year level can use a different time slot than the
            // rest of that faculty, without needing to unlock/replace it.
            $table->unsignedTinyInteger('semester')->nullable()->after('department_id');
            $table->unsignedTinyInteger('year_level')->nullable()->after('semester');
        });
    }

    public function down(): void
    {
        Schema::table('routine_shift_assignments', function (Blueprint $table) {
            $table->dropColumn(['semester', 'year_level']);
        });
    }
};
