<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('routine_shift_assignments', 'organization_id')) {
            Schema::table('routine_shift_assignments', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->after('routine_shift_id')->constrained()->cascadeOnDelete();
            });
        }

        Schema::table('routine_shift_assignments', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->change();
        });

        DB::table('routine_shift_assignments as assignments')
            ->join('routine_shifts as shifts', 'shifts.id', '=', 'assignments.routine_shift_id')
            ->update(['assignments.organization_id' => DB::raw('shifts.organization_id')]);

        $now = now();
        DB::table('routine_shifts')->orderBy('id')->get()->each(function ($shift) use ($now) {
            $hasAssignment = DB::table('routine_shift_assignments')
                ->where('routine_shift_id', $shift->id)
                ->exists();

            if (! $hasAssignment && $shift->academic_year_id && $shift->organization_id) {
                DB::table('routine_shift_assignments')->insert([
                    'academic_year_id' => $shift->academic_year_id,
                    'routine_shift_id' => $shift->id,
                    'organization_id' => $shift->organization_id,
                    'department_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        Schema::table('routine_shift_assignments', function (Blueprint $table) {
            $table->index('academic_year_id', 'routine_assignment_academic_year_fk_index');
            $table->index('routine_shift_id', 'routine_assignment_shift_fk_index');
        });

        Schema::table('routine_shift_assignments', function (Blueprint $table) {
            $table->dropUnique('routine_year_department_unique');
            $table->dropUnique('routine_shift_department_unique');
            $table->index(['academic_year_id', 'organization_id'], 'routine_assignment_year_org_index');
            $table->index(['routine_shift_id', 'organization_id'], 'routine_assignment_shift_org_index');
        });

        Schema::table('routine_shifts', function (Blueprint $table) {
            $table->index('academic_year_id', 'routine_shift_academic_year_fk_index');
        });

        Schema::table('routine_shifts', function (Blueprint $table) {
            $table->dropUnique('routine_shift_year_org_name_unique');
            $table->unsignedBigInteger('academic_year_id')->nullable()->change();
            $table->unsignedBigInteger('organization_id')->nullable()->change();
            $table->unique('name', 'routine_shift_name_unique');
        });

        DB::table('routine_shifts')->update([
            'academic_year_id' => null,
            'organization_id' => null,
        ]);
    }

    public function down(): void
    {
        throw new RuntimeException('Reusable time-slot assignments cannot be safely converted back to organization-owned shifts.');
    }
};
