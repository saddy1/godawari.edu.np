<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->time('starts_at')->nullable()->after('ends_on');
            $table->unsignedSmallInteger('theory_duration_minutes')->nullable()->after('starts_at');
            $table->unsignedSmallInteger('practical_duration_minutes')->nullable()->after('theory_duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'theory_duration_minutes', 'practical_duration_minutes']);
        });
    }
};
