<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->string('scope_type', 20)->default('departments')->after('organization_id');
        });

        DB::table('examinations')->orderBy('id')->get(['id', 'organization_id'])->each(function ($exam) {
            $activeDepartments = DB::table('departments')->where('organization_id', $exam->organization_id)->where('is_active', true)->count();
            $examDepartments = DB::table('examination_departments')->where('examination_id', $exam->id)->count();
            DB::table('examinations')->where('id', $exam->id)->update([
                'scope_type' => $activeDepartments > 0 && $examDepartments >= $activeDepartments ? 'organization' : 'departments',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('examinations', fn (Blueprint $table) => $table->dropColumn('scope_type'));
    }
};
