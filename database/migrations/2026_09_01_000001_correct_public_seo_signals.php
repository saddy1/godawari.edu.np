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
            $this->replaceSetting('website_url', 'https://www.godawari.edu.np');
            $this->replaceSetting('school_alternate_name', 'Sushma Godawari College');

            $this->replaceSettingWhenOld('school_phone', ['College Office', ''], '+977 985-2056519');
            $this->replaceSettingWhenOld('map_latitude', ['29.2844', ''], '26.6540757');
            $this->replaceSettingWhenOld('map_longitude', ['81.0897', ''], '87.2774388');
            $this->replaceSettingWhenOld('school_street', ['Itahari', ''], 'Itahari-9, near Itahari Stadium');
        }

        if (Schema::hasTable('cms_pages')) {
            DB::table('cms_pages')->where('slug', 'bsc-csit')->update([
                'title' => 'BSc CSIT at Godawari College, Itahari',
                'meta_title' => 'BSc CSIT College in Itahari | Godawari College',
                'meta_description' => 'Study TU-affiliated BSc CSIT at Godawari College in Itahari: 4 years, 8 semesters, 126 credits and 36 seats. Explore the program and admission process.',
                'updated_at' => now(),
            ]);

            $page = DB::table('cms_pages')->where('slug', 'bsc-csit')->first(['id', 'content_blocks']);
            if ($page?->content_blocks) {
                $blocks = json_decode($page->content_blocks, true);
                if (is_array($blocks)) {
                    $blocks = $this->replaceContentStrings($blocks, [
                        'BSc Computer Science and Information Technology' => 'BSc CSIT at Godawari College, Itahari',
                        'These details can be updated any time from the CMS page editor.' => 'Explore the core subjects, practical learning areas, and career directions covered by this four-year TU BSc CSIT program in Itahari.',
                    ]);

                    DB::table('cms_pages')->where('id', $page->id)->update([
                        'content_blocks' => json_encode($blocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }
        }

        Cache::forget('site_settings');
    }

    public function down(): void
    {
        // Public identity corrections should not be restored to known-wrong values.
    }

    private function replaceSetting(string $key, string $value): void
    {
        if (DB::table('settings')->where('key', $key)->exists()) {
            DB::table('settings')->where('key', $key)->update([
                'value' => $value,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('settings')->insert([
            'key' => $key,
            'value' => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function replaceSettingWhenOld(string $key, array $oldValues, string $value): void
    {
        $existing = DB::table('settings')->where('key', $key)->value('value');

        if ($existing === null || in_array((string) $existing, $oldValues, true)) {
            $this->replaceSetting($key, $value);
        }
    }

    private function replaceContentStrings(array $value, array $replacements): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->replaceContentStrings($item, $replacements);
            } elseif (is_string($item) && isset($replacements[$item])) {
                $value[$key] = $replacements[$item];
            }
        }

        return $value;
    }
};
