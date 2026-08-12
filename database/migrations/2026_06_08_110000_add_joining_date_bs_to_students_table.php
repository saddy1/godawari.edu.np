<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('joining_date_bs', 20)->nullable()->after('joining_date');
            $table->string('permanent_date_bs', 20)->nullable()->after('permanent_date');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['joining_date_bs', 'permanent_date_bs']);
        });
    }
};
