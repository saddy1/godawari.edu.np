<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_patron_categories', function (Blueprint $table) {
            $table->string('organization_slug')->nullable()->after('patron_type');
            $table->string('department_name')->nullable()->after('organization_slug');
            $table->index(['organization_slug', 'department_name'], 'library_patron_department_scope');
        });
    }

    public function down(): void
    {
        Schema::table('library_patron_categories', function (Blueprint $table) {
            $table->dropIndex('library_patron_department_scope');
            $table->dropColumn(['organization_slug', 'department_name']);
        });
    }
};
