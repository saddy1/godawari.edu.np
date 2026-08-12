<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('learning_quizzes')) {
            return;
        }

        Schema::create('learning_quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('learning_course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('learning_lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('time_limit_minutes')->nullable();
            $table->unsignedTinyInteger('pass_percentage')->default(60);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('learning_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_quiz_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('type', ['mcq', 'short_answer'])->default('mcq');
            $table->unsignedTinyInteger('marks')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('explanation')->nullable();
            $table->timestamps();
        });

        Schema::create('learning_quiz_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_quiz_question_id')->constrained()->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('learning_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_quiz_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 6, 2)->default(0);
            $table->unsignedSmallInteger('total_marks')->default(0);
            $table->boolean('passed')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('learning_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_quiz_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('learning_quiz_options')->nullOnDelete();
            $table->text('text_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('marks_awarded', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_quiz_answers');
        Schema::dropIfExists('learning_quiz_attempts');
        Schema::dropIfExists('learning_quiz_options');
        Schema::dropIfExists('learning_quiz_questions');
        Schema::dropIfExists('learning_quizzes');
    }
};
