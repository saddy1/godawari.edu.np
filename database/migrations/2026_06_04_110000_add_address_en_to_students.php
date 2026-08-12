<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // English free-text address for use on certificates and official documents.
            // The permanent_* fields store Nepali names from the ekSunye picker;
            // this field holds the English equivalent entered manually by the admin.
            $table->string('address_en', 300)->nullable()->after('permanent_tole');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('address_en');
        });
    }
};
