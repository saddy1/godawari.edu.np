<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('code', 30);
            $table->string('type', 30)->default('classroom');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code'], 'routine_room_org_code_unique');
        });

        Schema::create('routine_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('routine_shift_id')->constrained('routine_shifts')->restrictOnDelete();
            $table->string('name', 120);
            $table->unsignedTinyInteger('semester')->nullable();
            $table->unsignedTinyInteger('year_level')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['academic_year_id', 'organization_id', 'department_id'], 'routine_plan_scope_index');
        });

        Schema::create('routine_plan_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_plan_id')->constrained('routine_plans')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['routine_plan_id', 'section_id'], 'routine_plan_section_unique');
        });

        Schema::create('routine_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_plan_id')->constrained('routine_plans')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->restrictOnDelete();
            $table->foreignId('routine_period_id')->constrained('routine_periods')->restrictOnDelete();
            $table->foreignId('end_routine_period_id')->nullable()->constrained('routine_periods')->restrictOnDelete();
            $table->string('day_of_week', 12);
            $table->string('mode', 20)->default('single');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['routine_plan_id', 'section_id', 'day_of_week', 'routine_period_id'], 'routine_lesson_cell_unique');
            $table->index(['day_of_week', 'routine_period_id']);
        });

        Schema::create('routine_lesson_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_lesson_id')->constrained('routine_lessons')->cascadeOnDelete();
            $table->foreignId('subject_offering_id')->constrained('subject_offerings')->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('routine_room_id')->nullable()->constrained('routine_rooms')->restrictOnDelete();
            $table->unsignedTinyInteger('position')->default(1);
            $table->string('group_label', 30)->nullable();
            $table->decimal('student_percentage', 5, 2)->default(100);
            $table->timestamps();
            $table->unique(['routine_lesson_id', 'position'], 'routine_lesson_group_position_unique');
            $table->index(['teacher_id', 'routine_lesson_id']);
            $table->index(['routine_room_id', 'routine_lesson_id']);
        });

        Schema::create('routine_lesson_group_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_lesson_group_id')->constrained('routine_lesson_groups')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['routine_lesson_group_id', 'teacher_id'], 'routine_group_teacher_unique');
        });

        $permissions = [
            'teaching-learning.routine.view',
            'teaching-learning.routine.manage',
            'teaching-learning.routine.publish',
        ];
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
        foreach (['super-admin', 'administrator', 'principal'] as $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('id');
            if (! $roleId) continue;
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_lesson_group_teachers');
        Schema::dropIfExists('routine_lesson_groups');
        Schema::dropIfExists('routine_lessons');
        Schema::dropIfExists('routine_plan_sections');
        Schema::dropIfExists('routine_plans');
        Schema::dropIfExists('routine_rooms');
        $ids = DB::table('permissions')->whereIn('name', [
            'teaching-learning.routine.view',
            'teaching-learning.routine.manage',
            'teaching-learning.routine.publish',
        ])->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
