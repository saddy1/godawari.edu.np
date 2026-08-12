<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            $settings = [
                'site_name_en' => 'Godawari College',
                'site_name_ne' => 'गोदावरी कलेज',
                'app_name' => 'Godawari College ERP',
                'site_tagline_en' => 'A Place Where Dreams Are Nurtured',
                'site_tagline_ne' => 'सपना साकार गर्ने शैक्षिक थलो',
                'site_address_en' => 'Itahari, Sunsari, Nepal',
                'site_address_ne' => 'इटहरी, सुनसरी, नेपाल',
                'school_phone' => 'College Office',
                'school_email' => 'info@godawari.edu.np',
                'website_url' => 'https://godawari.edu.np',
                'site_logo' => 'media/1784009566_sushma.png',
                'site_favicon' => 'media/1784009566_sushma.png',
                'home_hero_image' => 'uploads/site/godawari-csit.jpg',
                'home_principal_image' => 'media/1784010094_bodh-raj-nepal.png',
                'school_alternate_name' => 'Godawari College Itahari',
                'school_street' => 'Itahari',
                'school_locality' => 'Itahari',
                'school_region' => 'Sunsari',
                'school_area_served' => 'Koshi Province',
                'school_founding_date_ad' => '1992',
                'school_estd' => '2049 B.S. (1992 AD)',
                'established_year' => '2049 B.S. (1992 AD)',
                'principal_name' => 'Bodh Raj Nepal',
                'principal_initials' => 'BR',
                'principal_role_en' => 'Campus Chief, Godawari College',
                'principal_role_ne' => 'क्याम्पस प्रमुख, गोदावरी कलेज',
                'principal_message_en' => 'Message From the Campus Chief',
                'principal_message_ne' => 'क्याम्पस प्रमुखको सन्देश',
                'principal_quote_en' => 'Our college is committed to fostering essential skills, identifying student talent, and stimulating innovative thinking.',
                'principal_quote_ne' => 'हाम्रो कलेज आवश्यक सीप विकास, विद्यार्थी प्रतिभा पहिचान र नवीन सोच प्रवर्द्धन गर्न प्रतिबद्ध छ।',
                'seo_default_title_en' => 'Godawari College | B.Sc. CSIT and BBS in Itahari',
                'seo_default_title_ne' => 'गोदावरी कलेज | इटहरीमा B.Sc. CSIT र BBS',
                'seo_default_description_en' => 'Godawari College is a Tribhuvan University affiliated college in Itahari offering B.Sc. CSIT and BBS programs.',
                'seo_default_description_ne' => 'गोदावरी कलेज इटहरीमा रहेको त्रिभुवन विश्वविद्यालयबाट सम्बन्धन प्राप्त कलेज हो, जहाँ B.Sc. CSIT र BBS कार्यक्रम सञ्चालन हुन्छन्।',
                'seo_default_keywords_en' => 'Godawari College, college in Itahari, BSc CSIT Itahari, BBS Itahari, Tribhuvan University college',
                'seo_default_keywords_ne' => 'गोदावरी कलेज, इटहरी कलेज, BSc CSIT, BBS',
                'primary_color' => '#0d5963',
                'primary_light_color' => '#167480',
                'secondary_color' => '#f2a51a',
                'dark_color' => '#071d2b',
                'header_gradient_end' => '#083b45',
                'hero_gradient_end' => '#083b45',
                'cta_gradient_end' => '#071d2b',
                'footer_gradient_end' => '#071d2b',
            ];

            foreach ($settings as $key => $value) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        if (Schema::hasTable('home_banners')) {
            DB::table('home_banners')->where('is_active', true)->update(['is_active' => false, 'updated_at' => now()]);
            DB::table('home_banners')->insert([
                'eyebrow' => 'Established 1992 AD · Itahari, Sunsari',
                'eyebrow_ne' => 'स्थापना १९९२ ई. · इटहरी, सुनसरी',
                'title' => 'Knowledge for today. Confidence for tomorrow.',
                'title_ne' => 'आजका लागि ज्ञान। भोलिका लागि आत्मविश्वास।',
                'subtitle' => 'Tribhuvan University affiliated B.Sc. CSIT and BBS programs with practical, student-focused learning.',
                'subtitle_ne' => 'व्यवहारिक र विद्यार्थी केन्द्रित सिकाइसहित त्रिभुवन विश्वविद्यालयबाट सम्बन्धन प्राप्त B.Sc. CSIT र BBS कार्यक्रम।',
                'primary_label' => 'Start Your Admission',
                'primary_label_ne' => 'भर्ना प्रक्रिया सुरु गर्नुहोस्',
                'primary_url' => '/admissions',
                'secondary_label' => 'Explore Programs',
                'secondary_label_ne' => 'कार्यक्रमहरू हेर्नुहोस्',
                'secondary_url' => '/#programs',
                'image_path' => 'uploads/site/godawari-csit.jpg',
                'text_position' => 'left',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('cms_pages')) {
            DB::table('cms_pages')
                ->where('slug', 'history-of-radha-krishna-mavi')
                ->update(['status' => 'draft', 'updated_at' => now()]);
        }

        if (Schema::hasTable('cms_menu_items')) {
            DB::table('cms_menu_items')
                ->where('url', '/pages/history-of-radha-krishna-mavi')
                ->update(['is_active' => false, 'updated_at' => now()]);

            $menuUpdates = [
                '/academics/elementary' => ['label' => 'B.Sc. CSIT', 'label_ne' => 'B.Sc. CSIT'],
                '/academics/primary' => ['label' => 'BBS', 'label_ne' => 'BBS'],
                '/academics/secondary' => ['label' => 'Academic Resources', 'label_ne' => 'शैक्षिक स्रोत'],
            ];

            foreach ($menuUpdates as $url => $labels) {
                $values = ['label' => $labels['label'], 'updated_at' => now()];
                if (Schema::hasColumn('cms_menu_items', 'label_ne')) {
                    $values['label_ne'] = $labels['label_ne'];
                }
                DB::table('cms_menu_items')->where('url', $url)->update($values);
            }
        }

        Cache::forget('site_settings');
    }

    public function down(): void
    {
        if (Schema::hasTable('home_banners')) {
            DB::table('home_banners')
                ->where('image_path', 'uploads/site/godawari-csit.jpg')
                ->where('title', 'Knowledge for today. Confidence for tomorrow.')
                ->delete();
        }

        Cache::forget('site_settings');
    }
};
