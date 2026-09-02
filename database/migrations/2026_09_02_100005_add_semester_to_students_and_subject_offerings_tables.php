<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Only meaningful for semester-based Bachelor's programs
            // (BSc CSIT, BCA, BBA...) — left null for BBS, +2, and all staff/teachers.
            $table->unsignedTinyInteger('semester')->nullable()->after('batch');
            $table->unsignedTinyInteger('year_level')->nullable()->after('semester');
        });

        Schema::table('subject_offerings', function (Blueprint $table) {
            // Which semester this offering applies to, for departments whose
            // subjects differ by semester rather than (or in addition to) group.
            // Null = department has no semester split (BBS, +2, ...).
            $table->unsignedTinyInteger('semester')->nullable()->after('group_name');
            $table->unsignedTinyInteger('year_level')->nullable()->after('semester');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('semester');
            $table->dropColumn('year_level');
        });

        Schema::table('subject_offerings', function (Blueprint $table) {
            $table->dropColumn('semester');
            $table->dropColumn('year_level');
        });
    }
};
