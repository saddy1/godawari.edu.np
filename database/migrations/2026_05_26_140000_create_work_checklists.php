<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('work_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_checklist_id')->constrained('work_checklists')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->unsignedInteger('max_score')->default(10);
            $table->decimal('incentive_amount', 12, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('work_tasks', function (Blueprint $table) {
            $table->foreignId('work_checklist_id')->nullable()->after('id')->constrained('work_checklists')->nullOnDelete();
            $table->foreignId('work_checklist_item_id')->nullable()->after('work_checklist_id')->constrained('work_checklist_items')->nullOnDelete();
        });

        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            DB::table('permissions')->updateOrInsert(
                ['name' => 'work-checklists.manage', 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );

            $permissionId = DB::table('permissions')->where('name', 'work-checklists.manage')->where('guard_name', 'web')->value('id');
            $roleIds = DB::table('roles')
                ->whereIn('name', ['super-admin', 'principal', 'administrator'])
                ->where('guard_name', 'web')
                ->pluck('id');

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_checklist_item_id');
            $table->dropConstrainedForeignId('work_checklist_id');
        });
        Schema::dropIfExists('work_checklist_items');
        Schema::dropIfExists('work_checklists');
    }
};
