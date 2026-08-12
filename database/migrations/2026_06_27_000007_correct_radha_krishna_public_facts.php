<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            $now = now();
            $settings = [
                'site_address_en' => 'Gopghat, Latamandau, Doti, Sudurpashchim Province, Nepal',
                'site_address_ne' => 'गोपघाट, लाटामाण्डौँ, डोटी, सुदूरपश्चिम प्रदेश, नेपाल',
                'school_street' => 'Gopghat',
                'school_locality' => 'Latamandau',
                'school_region' => 'Doti',
                'school_area_served' => 'Latamandau, Barpata, and nearby communities in Doti',
                'school_founding_date_ad' => '1952',
                'school_estd' => '2009 B.S.',
            ];

            foreach ($settings as $key => $value) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        if (Schema::hasTable('home_banners')) {
            DB::table('home_banners')
                ->where('title', 'Education, Discipline, Creativity, and Commitment')
                ->update([
                    'eyebrow' => 'Community Based Government School',
                    'subtitle' => 'Serving learners from early childhood to Grade 12 from Gopghat, Latamandau, Doti.',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();
        $settings = [
            'site_address_en' => 'Barchhain, Doti, Sudurpashchim Province, Nepal',
            'site_address_ne' => 'बर्छैन, डोटी, सुदूरपश्चिम प्रदेश, नेपाल',
            'school_street' => 'Barchhain',
            'school_locality' => 'Barchhain',
            'school_area_served' => 'Doti',
            'school_founding_date_ad' => '2005',
            'school_estd' => '2017 B.S.',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
};
