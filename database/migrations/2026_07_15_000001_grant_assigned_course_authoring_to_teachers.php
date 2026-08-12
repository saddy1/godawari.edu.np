<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'learning.courses.create',
        'learning.courses.edit',
        'learning.courses.delete',
        'learning.lessons.create',
        'learning.lessons.edit',
        'learning.lessons.delete',
        'learning.quizzes.create',
        'learning.quizzes.edit',
        'learning.quizzes.delete',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $teacherRoleId = DB::table('roles')
            ->where('name', 'teacher')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $teacherRoleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $teacherRoleId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $teacherRoleId = DB::table('roles')
            ->where('name', 'teacher')
            ->where('guard_name', 'web')
            ->value('id');

        if ($teacherRoleId) {
            $permissionIds = DB::table('permissions')
                ->where('guard_name', 'web')
                ->whereIn('name', $this->permissions)
                ->pluck('id');

            DB::table('role_has_permissions')
                ->where('role_id', $teacherRoleId)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
