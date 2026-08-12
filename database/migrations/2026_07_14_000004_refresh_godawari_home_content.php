<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $quickLinks = [
            'Notices' => ['title_ne' => 'सूचनाहरू', 'subtitle' => 'Latest college notices', 'subtitle_ne' => 'कलेजका नवीनतम सूचनाहरू', 'url' => '/notices', 'icon_key' => 'notice', 'sort_order' => 1],
            'Results' => ['title' => 'Admissions', 'title_ne' => 'भर्ना', 'subtitle' => 'Apply to Godawari College', 'subtitle_ne' => 'गोदावरी कलेजमा आवेदन दिनुहोस्', 'url' => '/admissions', 'icon_key' => 'result', 'sort_order' => 2],
            'Academic Calendar' => ['title' => 'Our Programs', 'title_ne' => 'हाम्रा कार्यक्रमहरू', 'subtitle' => 'B.Sc. CSIT and BBS', 'subtitle_ne' => 'बी.एस्सी. सीएसआईटी र बीबीएस', 'url' => '/admissions#programs', 'icon_key' => 'book', 'sort_order' => 3],
            'IEMIS' => ['title' => 'About College', 'title_ne' => 'कलेजको बारेमा', 'subtitle' => 'Explore Godawari College', 'subtitle_ne' => 'गोदावरी कलेज चिनौं', 'url' => '/about', 'icon_key' => 'people', 'sort_order' => 4],
            'Downloads' => ['title_ne' => 'डाउनलोडहरू', 'subtitle' => 'Forms and documents', 'subtitle_ne' => 'फारम तथा कागजातहरू', 'url' => '/notices?category=Download', 'icon_key' => 'download', 'sort_order' => 5],
            'Contact Us' => ['title_ne' => 'सम्पर्क गर्नुहोस्', 'subtitle' => 'Talk to our team', 'subtitle_ne' => 'हाम्रो टोलीसँग सम्पर्क गर्नुहोस्', 'url' => '/contact', 'icon_key' => 'contact', 'sort_order' => 6],
        ];

        foreach ($quickLinks as $legacyTitle => $values) {
            DB::table('home_contents')
                ->where('type', 'quick_link')
                ->where('title', $legacyTitle)
                ->update(array_merge($values, ['updated_at' => $now]));
        }

        DB::table('home_contents')
            ->where('type', 'learning_pathway')
            ->whereIn('title', [
                'General Education',
                'Project Based Learning',
                'Inclusive Support',
                'Technology Enabled',
            ])
            ->delete();

        $programs = [
            [
                'category' => 'program',
                'title' => 'B.Sc. CSIT',
                'title_ne' => 'बी.एस्सी. सीएसआईटी',
                'subtitle' => '4 years · 8 semesters · 36 seats',
                'subtitle_ne' => '४ वर्ष · ८ सेमेस्टर · ३६ सिट',
                'description' => 'Build strong foundations in computing, software development, information systems, and emerging technologies.',
                'url' => '/pages/bsc-csit',
                'image_path' => 'uploads/site/godawari-csit.jpg',
                'icon_key' => 'screen',
                'sort_order' => 1,
            ],
            [
                'category' => 'program',
                'title' => 'Bachelor of Business Studies (BBS)',
                'title_ne' => 'ब्याचलर अफ बिजनेस स्टडिज (बीबीएस)',
                'subtitle' => '4 years · Tribhuvan University',
                'subtitle_ne' => '४ वर्ष · त्रिभुवन विश्वविद्यालय',
                'description' => 'Develop practical knowledge in management, accounting, finance, marketing, and entrepreneurship.',
                'url' => '/pages/bbs',
                'image_path' => 'uploads/site/godawari-bbs.jpg',
                'icon_key' => 'idea',
                'sort_order' => 2,
            ],
        ];

        foreach ($programs as $program) {
            DB::table('home_contents')->updateOrInsert(
                ['type' => 'learning_pathway', 'title' => $program['title']],
                array_merge($program, [
                    'type' => 'learning_pathway',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('home_contents')
            ->where('type', 'learning_pathway')
            ->whereIn('title', ['B.Sc. CSIT', 'Bachelor of Business Studies (BBS)'])
            ->delete();
    }
};
