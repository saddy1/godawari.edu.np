<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('category', 80)->default('Terminal');
            $table->unsignedTinyInteger('semester')->nullable();
            $table->unsignedTinyInteger('year_level')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['academic_year_id', 'organization_id', 'department_id'], 'exam_scope_index');
            $table->index(['status', 'starts_on']);
        });

        Schema::create('examination_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['examination_id', 'section_id'], 'exam_section_unique');
        });

        Schema::create('examination_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_offering_id')->constrained('subject_offerings')->restrictOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('theory_full_marks', 8, 2)->default(100);
            $table->decimal('theory_pass_marks', 8, 2)->default(40);
            $table->decimal('practical_full_marks', 8, 2)->default(0);
            $table->decimal('practical_pass_marks', 8, 2)->default(0);
            $table->date('exam_date')->nullable();
            $table->time('starts_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->timestamps();
            $table->unique(['examination_id', 'subject_offering_id'], 'exam_subject_offering_unique');
            $table->index(['teacher_id', 'exam_date']);
        });

        Schema::create('examination_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('theory_marks', 8, 2)->nullable();
            $table->decimal('practical_marks', 8, 2)->nullable();
            $table->boolean('is_absent')->default(false);
            $table->string('remarks', 255)->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['examination_subject_id', 'student_id'], 'exam_mark_student_unique');
        });

        DB::table('module_settings')->updateOrInsert(
            ['key' => 'examinations'],
            [
                'label' => 'Examinations',
                'description' => 'Exam setup, marks entry, results and evaluation analytics',
                'group' => 'ERP',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permissions = [
            'examinations.view',
            'examinations.manage',
            'examinations.marks.enter',
            'examinations.marks.verify',
            'examinations.reports',
        ];
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id', 'name');
        foreach (['super-admin', 'administrator', 'principal'] as $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('id');
            if (! $roleId) continue;
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }
        $teacherRoleId = DB::table('roles')->where('name', 'teacher')->where('guard_name', 'web')->value('id');
        if ($teacherRoleId) {
            foreach ($permissionIds->only(['examinations.view', 'examinations.marks.enter']) as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert(['permission_id' => $permissionId, 'role_id' => $teacherRoleId]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_marks');
        Schema::dropIfExists('examination_subjects');
        Schema::dropIfExists('examination_sections');
        Schema::dropIfExists('examinations');
        DB::table('module_settings')->where('key', 'examinations')->delete();
        $permissionIds = DB::table('permissions')->whereIn('name', [
            'examinations.view', 'examinations.manage', 'examinations.marks.enter',
            'examinations.marks.verify', 'examinations.reports',
        ])->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
