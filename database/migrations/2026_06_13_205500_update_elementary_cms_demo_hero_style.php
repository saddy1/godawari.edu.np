<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $page = DB::table('cms_pages')->where('slug', 'elementary-cms-demo')->first();
        if (! $page) {
            return;
        }

        $blocks = json_decode($page->content_blocks ?? '[]', true);
        if (! is_array($blocks)) {
            return;
        }

        if (($blocks[0]['type'] ?? null) === 'row' && ($blocks[0]['data']['section'] ?? null) === 'hero') {
            $blocks[0]['data']['primary_label'] = 'Admission Inquiry';
            $blocks[0]['data']['secondary_label'] = 'Contact Us';
            $blocks[0]['data']['eyebrow'] = 'Academics';
            $blocks[0]['data']['badge'] = 'ECD to Grade 3';
        }

        DB::table('cms_pages')->where('slug', 'elementary-cms-demo')->update([
            'content_blocks' => json_encode($blocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        //
    }
};
