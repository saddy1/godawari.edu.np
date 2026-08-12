<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_banners')) {
            Schema::create('home_banners', function (Blueprint $table) {
                $table->id();
                $table->string('eyebrow')->nullable();
                $table->string('title');
                $table->text('subtitle')->nullable();
                $table->string('primary_label')->nullable();
                $table->string('primary_url')->nullable();
                $table->string('secondary_label')->nullable();
                $table->string('secondary_url')->nullable();
                $table->string('image_path');
                $table->string('text_position')->default('left');
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (DB::table('home_banners')->exists()) {
            return;
        }

        DB::table('home_banners')->insert([
            'eyebrow' => 'Community Based Government School',
            'title' => 'Education, Discipline, Creativity, and Commitment',
            'subtitle' => 'Fostering Excellence, Inspiring Futures. Barchhain, Doti, Sudurpaschim Province, Nepal',
            'primary_label' => 'Learn More About Us',
            'primary_url' => '/about',
            'secondary_label' => 'Admission Open',
            'secondary_url' => '/admissions',
            'image_path' => 'assets/image/default-placeholder.jpg',
            'text_position' => 'left',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('home_banners');
    }
};
