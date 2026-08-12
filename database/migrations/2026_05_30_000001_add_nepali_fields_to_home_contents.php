<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_contents', function (Blueprint $table) {
            $table->string('title_ne')->nullable()->after('title');
            $table->string('subtitle_ne')->nullable()->after('subtitle');
        });

        // Rename Routine → Academic Calendar
        DB::table('home_contents')
            ->where('category', 'routine')
            ->update([
                'title'      => 'Academic Calendar',
                'subtitle'   => 'Class schedule',
                'updated_at' => now(),
            ]);

        // Seed Nepali translations for all default quick links
        $translations = [
            'notice'   => ['title_ne' => 'सूचनाहरू',           'subtitle_ne' => 'सबै सूचना हेर्नुहोस्'],
            'result'   => ['title_ne' => 'नतिजाहरू',           'subtitle_ne' => 'SEE र अन्य नतिजा'],
            'routine'  => ['title_ne' => 'शैक्षिक पात्रो',    'subtitle_ne' => 'कक्षा तालिका'],
            'iemis'    => ['title_ne' => 'आईईएमआईएस',          'subtitle_ne' => 'विद्यालय डेटा'],
            'download' => ['title_ne' => 'डाउनलोडहरू',         'subtitle_ne' => 'फारम र कागजात'],
            'contact'  => ['title_ne' => 'सम्पर्क गर्नुहोस्', 'subtitle_ne' => 'हामीसँग जोडिनुहोस्'],
        ];

        foreach ($translations as $category => $data) {
            DB::table('home_contents')
                ->where('type', 'quick_link')
                ->where('category', $category)
                ->update(array_merge($data, ['updated_at' => now()]));
        }
    }

    public function down(): void
    {
        Schema::table('home_contents', function (Blueprint $table) {
            $table->dropColumn(['title_ne', 'subtitle_ne']);
        });
    }
};
