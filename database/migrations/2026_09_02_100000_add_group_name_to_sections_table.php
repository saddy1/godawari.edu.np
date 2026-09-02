<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            // Tags which elective group (e.g. "Bio", "Computer") this named
            // section belongs to within its department. Null = department has
            // no group split (Bachelor's programs, Management, etc.).
            $table->string('group_name')->nullable()->after('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });
    }
};
