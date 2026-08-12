<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_contents')) {
            Schema::create('home_contents', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['quick_link', 'learning_pathway'])->index();
                $table->string('category')->nullable()->index();
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->text('description')->nullable();
                $table->string('url')->nullable();
                $table->string('image_path')->nullable();
                $table->string('icon_key')->default('book');
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (DB::table('home_contents')->exists()) {
            return;
        }

        $defaults = [
            'description' => null,
            'image_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('home_contents')->insert(array_map(fn ($row) => array_merge($defaults, $row), [
            [
                'type' => 'quick_link',
                'category' => 'notice',
                'title' => 'Notices',
                'subtitle' => 'View all notices',
                'url' => '/notices',
                'icon_key' => 'notice',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'quick_link',
                'category' => 'result',
                'title' => 'Results',
                'subtitle' => 'SEE & other results',
                'url' => '/notices?category=Result',
                'icon_key' => 'result',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'quick_link',
                'category' => 'routine',
                'title' => 'Routine',
                'subtitle' => 'Class routine',
                'url' => '/notices?category=Routine',
                'icon_key' => 'calendar',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'quick_link',
                'category' => 'iemis',
                'title' => 'IEMIS',
                'subtitle' => 'School data',
                'url' => 'http://iemis.cehrd.gov.np/login',
                'icon_key' => 'grid',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'type' => 'quick_link',
                'category' => 'download',
                'title' => 'Downloads',
                'subtitle' => 'Forms & documents',
                'url' => '/notices?category=Download',
                'icon_key' => 'download',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'type' => 'quick_link',
                'category' => 'contact',
                'title' => 'Contact Us',
                'subtitle' => 'Get in touch',
                'url' => '/contact',
                'icon_key' => 'contact',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'type' => 'learning_pathway',
                'category' => 'academic',
                'title' => 'General Education',
                'subtitle' => 'ECD to Grade 12',
                'description' => 'Quality school education with discipline, foundational skills, and community values.',
                'url' => '/academics/elementary',
                'image_path' => 'assets/image/default-placeholder.jpg',
                'icon_key' => 'book',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'type' => 'learning_pathway',
                'category' => 'academic',
                'title' => 'Project Based Learning',
                'subtitle' => 'Practical skills',
                'description' => 'Students learn through exploration, teamwork, presentations, and local problem solving.',
                'url' => '/academics/primary',
                'image_path' => 'assets/image/default-placeholder.jpg',
                'icon_key' => 'idea',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'type' => 'learning_pathway',
                'category' => 'academic',
                'title' => 'Inclusive Support',
                'subtitle' => 'Care and guidance',
                'description' => 'Supportive teaching for students who need additional academic or personal care.',
                'url' => '/academics/secondary',
                'image_path' => 'assets/image/default-placeholder.jpg',
                'icon_key' => 'people',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'type' => 'learning_pathway',
                'category' => 'academic',
                'title' => 'Technology Enabled',
                'subtitle' => 'Digital learning',
                'description' => 'Online resources, records, and digital workflows strengthen everyday learning.',
                'url' => '/contact',
                'image_path' => 'assets/image/default-placeholder.jpg',
                'icon_key' => 'screen',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('home_contents');
    }
};
