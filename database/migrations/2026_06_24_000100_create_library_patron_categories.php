<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_patron_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('patron_type')->nullable();      // student / teacher / staff (maps to ERP role)
            $table->string('class_range')->nullable();      // e.g. "1-5", "6-8", "9-10", "11-12" — null means all classes
            $table->unsignedTinyInteger('max_active_books')->default(3);
            $table->unsignedSmallInteger('loan_days')->default(14);
            $table->decimal('fine_per_day', 8, 2)->default(2);
            $table->boolean('block_same_title')->default(true);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('library_patron_categories')->insert([
            [
                'name'             => 'Class 1–5 Students',
                'slug'             => 'student-class-1-5',
                'patron_type'      => 'student',
                'class_range'      => '1-5',
                'max_active_books' => 2,
                'loan_days'        => 14,
                'fine_per_day'     => 1,
                'block_same_title' => true,
                'description'      => 'Primary level students (Class 1 to 5)',
                'is_active'        => true,
                'sort_order'       => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'name'             => 'Class 6–8 Students',
                'slug'             => 'student-class-6-8',
                'patron_type'      => 'student',
                'class_range'      => '6-8',
                'max_active_books' => 3,
                'loan_days'        => 14,
                'fine_per_day'     => 2,
                'block_same_title' => true,
                'description'      => 'Lower secondary level students (Class 6 to 8)',
                'is_active'        => true,
                'sort_order'       => 2,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'name'             => 'Class 9–10 Students',
                'slug'             => 'student-class-9-10',
                'patron_type'      => 'student',
                'class_range'      => '9-10',
                'max_active_books' => 4,
                'loan_days'        => 21,
                'fine_per_day'     => 3,
                'block_same_title' => true,
                'description'      => 'Secondary level students (Class 9 to 10 / SEE)',
                'is_active'        => true,
                'sort_order'       => 3,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'name'             => 'Class 11–12 Students',
                'slug'             => 'student-class-11-12',
                'patron_type'      => 'student',
                'class_range'      => '11-12',
                'max_active_books' => 5,
                'loan_days'        => 21,
                'fine_per_day'     => 3,
                'block_same_title' => true,
                'description'      => 'Higher secondary level students (Class 11 to 12)',
                'is_active'        => true,
                'sort_order'       => 4,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'name'             => 'Teachers',
                'slug'             => 'teacher',
                'patron_type'      => 'teacher',
                'class_range'      => null,
                'max_active_books' => 10,
                'loan_days'        => 30,
                'fine_per_day'     => 5,
                'block_same_title' => false,
                'description'      => 'All teaching staff',
                'is_active'        => true,
                'sort_order'       => 5,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'name'             => 'Staff / Administration',
                'slug'             => 'staff',
                'patron_type'      => 'staff',
                'class_range'      => null,
                'max_active_books' => 5,
                'loan_days'        => 30,
                'fine_per_day'     => 5,
                'block_same_title' => false,
                'description'      => 'Non-teaching administrative staff',
                'is_active'        => true,
                'sort_order'       => 6,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('library_patron_categories');
    }
};
