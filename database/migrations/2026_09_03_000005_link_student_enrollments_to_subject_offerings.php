<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            $table->foreignId('subject_offering_id')->nullable()->after('subject_id')->constrained()->cascadeOnDelete();
            $table->string('assignment_source', 20)->default('manual')->after('academic_year');
            $table->index(['subject_offering_id', 'academic_year'], 'student_enrollment_offering_year_index');
        });
    }

    public function down(): void
    {
        Schema::table('student_subject_enrollments', function (Blueprint $table) {
            $table->dropIndex('student_enrollment_offering_year_index');
            $table->dropConstrainedForeignId('subject_offering_id');
            $table->dropColumn('assignment_source');
        });
    }
};
