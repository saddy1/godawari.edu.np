<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            // Matches sections.group_name — null means this offering applies
            // department-wide (no elective group split, e.g. Bachelor's programs).
            $table->string('group_name')->nullable();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            // Elective status belongs to this faculty/class assignment, never
            // to the master subject. A subject may be fixed in one faculty and
            // elective in another.
            $table->boolean('is_elective')->default(false);
            $table->string('elective_group', 50)->nullable();
            $table->timestamps();

            $table->index(['department_id', 'group_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_offerings');
    }
};
