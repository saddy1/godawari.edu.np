<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_menus') || ! Schema::hasTable('cms_menu_items')) {
            return;
        }

        if (! Schema::hasColumn('cms_menu_items', 'subtitle')) {
            Schema::table('cms_menu_items', function (Blueprint $table) {
                $table->string('subtitle')->nullable()->after('label');
            });
        }

        DB::table('cms_menus')->updateOrInsert(
            ['location' => 'header'],
            [
                'name' => 'Main Header',
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $menuId = DB::table('cms_menus')->where('location', 'header')->value('id');

        $rootItems = [
            ['key' => 'home', 'label' => 'Home', 'url' => '/', 'sort_order' => 10],
            ['key' => 'academics', 'label' => 'Academics', 'url' => '#', 'sort_order' => 20],
            ['key' => 'admissions', 'label' => 'Admissions', 'url' => '/admissions', 'sort_order' => 30],
            ['key' => 'news', 'label' => 'News', 'url' => '/news', 'sort_order' => 40],
            ['key' => 'gallery', 'label' => 'Gallery', 'url' => '/gallery', 'sort_order' => 50],
            ['key' => 'vacancies', 'label' => 'Vacancies', 'url' => '/vacancies', 'sort_order' => 60],
            ['key' => 'about', 'label' => 'About', 'url' => '#', 'sort_order' => 70],
        ];

        $ids = [];
        foreach ($rootItems as $item) {
            DB::table('cms_menu_items')->updateOrInsert(
                ['cms_menu_id' => $menuId, 'parent_id' => null, 'label' => $item['label']],
                [
                    'type' => 'url',
                    'url' => $item['url'],
                    'target' => '_self',
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $ids[$item['key']] = DB::table('cms_menu_items')
                ->where('cms_menu_id', $menuId)
                ->whereNull('parent_id')
                ->where('label', $item['label'])
                ->value('id');
        }

        $children = [
            'academics' => [
                ['label' => 'Early Childhood', 'subtitle' => 'ECD - Grade 3', 'url' => '/academics/elementary', 'sort_order' => 10],
                ['label' => 'Basic Level', 'subtitle' => 'Grade 4 - 8', 'url' => '/academics/primary', 'sort_order' => 20],
                ['label' => 'Secondary & Technical', 'subtitle' => 'Grade 9 - 12 / Diploma', 'url' => '/academics/secondary', 'sort_order' => 30],
            ],
            'vacancies' => [
                ['label' => 'Open Vacancies', 'subtitle' => 'Browse jobs', 'url' => '/vacancies', 'sort_order' => 10],
                ['label' => 'Applicant Login', 'subtitle' => 'Track application', 'url' => '/applicant/login', 'sort_order' => 20],
            ],
            'about' => [
                ['label' => 'About Us', 'subtitle' => null, 'url' => '/about', 'sort_order' => 10],
                ['label' => 'Faculty', 'subtitle' => null, 'url' => '/Frontend/faculty', 'sort_order' => 20],
                ['label' => 'Contact', 'subtitle' => null, 'url' => '/contact', 'sort_order' => 30],
            ],
        ];

        foreach ($children as $parentKey => $items) {
            foreach ($items as $item) {
                DB::table('cms_menu_items')->updateOrInsert(
                    ['cms_menu_id' => $menuId, 'parent_id' => $ids[$parentKey], 'label' => $item['label']],
                    [
                        'subtitle' => $item['subtitle'],
                        'type' => 'url',
                        'url' => $item['url'],
                        'target' => '_self',
                        'sort_order' => $item['sort_order'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cms_menus')) {
            $menuId = DB::table('cms_menus')->where('location', 'header')->value('id');
            if ($menuId) {
                DB::table('cms_menus')->where('id', $menuId)->delete();
            }
        }
    }
};
