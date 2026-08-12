<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_menus') || ! Schema::hasTable('cms_menu_items')) {
            return;
        }

        DB::table('cms_menus')
            ->whereRaw('LOWER(location) = ?', ['header'])
            ->update(['location' => 'header', 'is_active' => true, 'updated_at' => now()]);

        $headerMenuId = DB::table('cms_menus')->where('location', 'header')->value('id');

        if ($headerMenuId) {
            DB::table('cms_menu_items')
                ->where('cms_menu_id', $headerMenuId)
                ->where('label', 'Resources')
                ->update(['is_active' => true, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        //
    }
};
