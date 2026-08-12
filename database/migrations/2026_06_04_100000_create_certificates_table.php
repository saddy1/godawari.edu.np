<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->index();
            $table->enum('certificate_type', ['character', 'provisional'])->default('character');
            $table->string('certificate_number', 30)->unique();

            // Fields collected at generation time (not in student HR)
            $table->string('exam_name', 100)->nullable();
            $table->string('division_gpa', 100)->nullable();
            $table->string('pass_year_bs', 10)->nullable();
            $table->string('pass_year_ad', 10)->nullable();
            $table->string('character_description', 100)->nullable();
            $table->string('symbol_no', 100)->nullable();
            $table->date('issue_date');

            // Snapshot fields (pre-filled from HR, can be overridden)
            $table->string('student_name', 200)->nullable();
            $table->string('parent_name', 200)->nullable();
            $table->string('address', 300)->nullable();
            $table->string('registration_no', 100)->nullable();
            $table->string('gender', 30)->nullable();

            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('issued_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
