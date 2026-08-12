<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('store_suppliers', 'tax_registration_type')) {
            return;
        }

        Schema::table('store_suppliers', function (Blueprint $table) {
            $table->string('tax_registration_type', 10)->default('pan')->after('email');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('store_suppliers', 'tax_registration_type')) {
            return;
        }

        Schema::table('store_suppliers', function (Blueprint $table) {
            $table->dropColumn('tax_registration_type');
        });
    }
};
