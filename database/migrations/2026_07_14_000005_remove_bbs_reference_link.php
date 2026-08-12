<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REFERENCE_URL = 'https://edusanjal.com/course/bachelor-of-business-studies-bbs-tribhuvan-university/';

    public function up(): void
    {
        $page = DB::table('cms_pages')->where('slug', 'bbs')->first();

        if (! $page) {
            return;
        }

        $updates = [];

        foreach (['content_blocks', 'content_blocks_ne'] as $field) {
            $blocks = json_decode($page->{$field} ?? '[]', true);

            if (is_array($blocks)) {
                $updates[$field] = json_encode($this->withoutReferenceButton($blocks), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        if ($updates !== []) {
            $updates['updated_at'] = now();
            DB::table('cms_pages')->where('id', $page->id)->update($updates);
        }
    }

    private function withoutReferenceButton(array $blocks): array
    {
        foreach ($blocks as &$block) {
            if (($block['type'] ?? null) !== 'row') {
                continue;
            }

            $columns = $block['columns'] ?? [];

            foreach ($columns as &$column) {
                $column['blocks'] = array_values(array_filter(
                    $column['blocks'] ?? [],
                    fn (array $item) => ($item['data']['url'] ?? null) !== self::REFERENCE_URL
                ));
            }
            unset($column);

            $block['columns'] = $columns;
        }
        unset($block);

        return $blocks;
    }

    public function down(): void
    {
        // Intentionally not restored: this external reference was removed by request.
    }
};
