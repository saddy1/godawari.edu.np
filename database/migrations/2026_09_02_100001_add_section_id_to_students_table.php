<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Links to the named section master list (sections.id) once staff
            // assign it. The existing `section` text column is left untouched
            // and kept as a display cache — nothing that already reads it breaks.
            $table->foreignId('section_id')->nullable()->after('section')->constrained('sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('section_id');
        });
    }
};
