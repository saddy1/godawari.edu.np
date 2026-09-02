<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('departments', 'academic_system')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->string('academic_system', 20)->default('semester')->after('name');
            });
        }

        if (! Schema::hasColumn('students', 'year_level')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedTinyInteger('year_level')->nullable()->after('semester');
            });
        }

        if (! Schema::hasColumn('subject_offerings', 'year_level')) {
            Schema::table('subject_offerings', function (Blueprint $table) {
                $table->unsignedTinyInteger('year_level')->nullable()->after('semester');
            });
        }

        $schoolOrganizationIds = DB::table('organizations')->where('type', 'school')->pluck('id');
        DB::table('departments')->whereIn('organization_id', $schoolOrganizationIds)->update(['academic_system' => 'none']);
        DB::table('departments')->whereRaw('LOWER(name) = ?', ['bbs'])->update(['academic_system' => 'year']);
    }

    public function down(): void
    {
        Schema::table('subject_offerings', fn (Blueprint $table) => $table->dropColumn('year_level'));
        Schema::table('students', fn (Blueprint $table) => $table->dropColumn('year_level'));
        Schema::table('departments', fn (Blueprint $table) => $table->dropColumn('academic_system'));
    }
};
