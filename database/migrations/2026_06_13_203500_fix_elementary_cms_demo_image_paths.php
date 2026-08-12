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
            $blocks = [];
        }

        $blocks = $this->replaceImagePath($blocks);

        DB::table('cms_pages')
            ->where('slug', 'elementary-cms-demo')
            ->update([
                'featured_image' => 'uploads/site/academics-elementary-image.jpeg',
                'content_blocks' => json_encode($blocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('cms_pages')
            ->where('slug', 'elementary-cms-demo')
            ->update([
                'featured_image' => 'assets/image/default-placeholder.jpg',
                'updated_at' => now(),
            ]);
    }

    private function replaceImagePath(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->replaceImagePath($item);
                continue;
            }

            if ($item === 'assets/image/default-placeholder.jpg') {
                $value[$key] = 'uploads/site/academics-elementary-image.jpeg';
            }
        }

        return $value;
    }
};
