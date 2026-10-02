<?php

use App\Services\ModuleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_settings')) {
            return;
        }

        DB::table('module_settings')->updateOrInsert(
            ['key' => 'counselling'],
            [
                'label' => 'Counselling',
                'description' => 'Student counselling requests, booking, and private counsellor reports',
                'group' => 'ERP',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        ModuleService::flush();
    }

    public function down(): void
    {
        if (! Schema::hasTable('module_settings')) {
            return;
        }

        DB::table('module_settings')->where('key', 'counselling')->delete();
        ModuleService::flush();
    }
};
