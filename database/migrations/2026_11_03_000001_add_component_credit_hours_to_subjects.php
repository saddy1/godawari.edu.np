<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects')) return;

        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'theory_credit_hours')) {
                $table->decimal('theory_credit_hours', 5, 2)->nullable()->after('credit_hours');
            }
            if (! Schema::hasColumn('subjects', 'practical_credit_hours')) {
                $table->decimal('practical_credit_hours', 5, 2)->nullable()->after('theory_credit_hours');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subjects')) return;
        Schema::table('subjects', function (Blueprint $table) {
            $columns = array_filter(['practical_credit_hours', 'theory_credit_hours'], fn ($column) => Schema::hasColumn('subjects', $column));
            if ($columns) $table->dropColumn($columns);
        });
    }
};
