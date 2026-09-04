<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examination_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['examination_id', 'department_id'], 'exam_department_unique');
        });

        $now = now();
        DB::table('examinations')->select('id', 'department_id')->orderBy('id')->get()->each(fn ($exam) =>
            DB::table('examination_departments')->insert([
                'examination_id' => $exam->id, 'department_id' => $exam->department_id,
                'created_at' => $now, 'updated_at' => $now,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_departments');
    }
};
