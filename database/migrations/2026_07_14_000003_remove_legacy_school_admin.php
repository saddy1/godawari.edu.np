<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'admin@barchhainsecondary.edu.np')
            ->delete();
    }

    public function down(): void
    {
        // Removed credentials are intentionally not recreated on rollback.
    }
};
