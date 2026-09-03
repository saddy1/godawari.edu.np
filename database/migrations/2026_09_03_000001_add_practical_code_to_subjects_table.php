<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects') || Schema::hasColumn('subjects', 'practical_code')) {
            return;
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('practical_code', 50)->nullable()->after('has_practical');
        });

        DB::table('subjects')
            ->where('has_practical', true)
            ->whereNull('practical_code')
            ->update(['practical_code' => DB::raw('code')]);
    }

    public function down(): void
    {
        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'practical_code')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('practical_code');
            });
        }
    }
};
