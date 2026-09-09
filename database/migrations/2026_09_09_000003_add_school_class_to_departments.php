<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedTinyInteger('school_class')->nullable()->after('academic_system');
        });

        $schoolIds = DB::table('organizations')->where('type', 'school')->pluck('id');
        foreach (DB::table('departments')->whereIn('organization_id', $schoolIds)->get(['id', 'name']) as $department) {
            if (preg_match('/^(?:Class\s+)?(11|12)(?:\s|$)/i', trim($department->name), $matches)) {
                DB::table('departments')->where('id', $department->id)->update(['school_class' => (int) $matches[1]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('departments', fn (Blueprint $table) => $table->dropColumn('school_class'));
    }
};
