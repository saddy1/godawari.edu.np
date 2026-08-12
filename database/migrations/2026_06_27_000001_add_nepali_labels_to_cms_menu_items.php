<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_menu_items')) {
            return;
        }

        Schema::table('cms_menu_items', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_menu_items', 'label_ne')) {
                $table->string('label_ne')->nullable()->after('label');
            }

            if (! Schema::hasColumn('cms_menu_items', 'subtitle_ne')) {
                $table->string('subtitle_ne')->nullable()->after('subtitle');
            }
        });

        $translations = [
            'Home' => ['गृहपृष्ठ', null],
            'Academics' => ['शैक्षिक', null],
            'Admissions' => ['भर्ना', null],
            'News' => ['समाचार', null],
            'Gallery' => ['ग्यालरी', null],
            'Vacancies' => ['रिक्त पद', null],
            'About' => ['हाम्रो बारेमा', null],
            'Early Childhood' => ['बालविकास', 'बालविकास - कक्षा ३'],
            'Basic Level' => ['आधारभूत तह', 'कक्षा ४ - ८'],
            'Secondary & Technical' => ['माध्यमिक र प्राविधिक', 'कक्षा ९ - १२ / डिप्लोमा'],
            'Open Vacancies' => ['खुला रिक्त पद', 'जागिरका अवसर हेर्नुहोस्'],
            'Applicant Login' => ['आवेदक लगइन', 'आफ्नो आवेदन हेर्नुहोस्'],
            'About Us' => ['हाम्रो बारेमा', null],
            'Faculty' => ['शिक्षक तथा कर्मचारी', null],
            'Contact' => ['सम्पर्क', null],
        ];

        foreach ($translations as $label => [$labelNe, $subtitleNe]) {
            DB::table('cms_menu_items')
                ->where('label', $label)
                ->whereNull('label_ne')
                ->update([
                    'label_ne' => $labelNe,
                    'subtitle_ne' => $subtitleNe,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cms_menu_items')) {
            return;
        }

        Schema::table('cms_menu_items', function (Blueprint $table) {
            if (Schema::hasColumn('cms_menu_items', 'subtitle_ne')) {
                $table->dropColumn('subtitle_ne');
            }

            if (Schema::hasColumn('cms_menu_items', 'label_ne')) {
                $table->dropColumn('label_ne');
            }
        });
    }
};
