<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examination_symbol_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_id')->constrained('examinations')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedInteger('symbol_no');
            $table->timestamps();
            $table->unique(['examination_id', 'student_id'], 'exam_symbol_student_unique');
            $table->unique(['examination_id', 'symbol_no'], 'exam_symbol_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_symbol_numbers');
    }
};
