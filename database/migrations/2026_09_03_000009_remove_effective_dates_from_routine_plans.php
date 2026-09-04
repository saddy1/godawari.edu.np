<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = array_values(array_filter(['effective_from', 'effective_to'], fn ($column) => Schema::hasColumn('routine_plans', $column)));
        if ($columns) Schema::table('routine_plans', fn (Blueprint $table) => $table->dropColumn($columns));
    }

    public function down(): void
    {
        Schema::table('routine_plans', function (Blueprint $table) {
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
        });
    }
};
