<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });

        Schema::create('routine_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('period_minutes');
            $table->unsignedTinyInteger('break_after_period')->nullable();
            $table->unsignedSmallInteger('break_minutes')->nullable();
            $table->json('working_days');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->unique(['academic_year_id', 'organization_id', 'name'], 'routine_shift_year_org_name_unique');
        });

        Schema::create('routine_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_shift_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('name', 80);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_break')->default(false);
            $table->timestamps();

            $table->unique(['routine_shift_id', 'position']);
        });

        Schema::create('routine_shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routine_shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['academic_year_id', 'department_id'], 'routine_year_department_unique');
            $table->unique(['routine_shift_id', 'department_id'], 'routine_shift_department_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_shift_assignments');
        Schema::dropIfExists('routine_periods');
        Schema::dropIfExists('routine_shifts');
        Schema::dropIfExists('academic_years');
    }
};
