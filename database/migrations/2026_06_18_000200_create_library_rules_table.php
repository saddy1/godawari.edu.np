<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_rules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('value')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('library_rules')->insert([
            [
                'key' => 'loan_days',
                'label' => 'Default Loan Days',
                'value' => '180',
                'description' => 'Default number of days before an issued book becomes due.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_active_books',
                'label' => 'Maximum Active Books',
                'value' => '3',
                'description' => 'Maximum number of books one borrower can hold at a time.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fine_per_day',
                'label' => 'Fine Per Late Day',
                'value' => '5',
                'description' => 'Fine amount in rupees for each late day after due date.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'block_same_title',
                'label' => 'Block Same Title',
                'value' => '1',
                'description' => 'Prevent a borrower from holding multiple copies of the same book title.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('library_rules');
    }
};
