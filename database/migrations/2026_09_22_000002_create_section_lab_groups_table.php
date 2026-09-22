<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_lab_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30);
            $table->timestamps();
            $table->unique(['section_id', 'name']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('lab_group_id')->nullable()->after('lab_group')->constrained('section_lab_groups')->nullOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('lab_group');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('lab_group', 10)->nullable()->after('section');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lab_group_id');
        });

        Schema::dropIfExists('section_lab_groups');
    }
};
