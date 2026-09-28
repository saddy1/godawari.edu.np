<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->timestamp('symbol_numbers_locked_at')->nullable()->after('status');
            $table->foreignId('symbol_numbers_locked_by')->nullable()->after('symbol_numbers_locked_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('symbol_numbers_locked_by');
            $table->dropColumn('symbol_numbers_locked_at');
        });
    }
};
