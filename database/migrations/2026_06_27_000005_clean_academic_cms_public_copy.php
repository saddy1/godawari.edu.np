<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['academics-elementary', 'academics-primary', 'academics-secondary'] as $slug) {
            $page = DB::table('cms_pages')->where('slug', $slug)->first();
            if (! $page) {
                continue;
            }

            DB::table('cms_pages')->where('slug', $slug)->update([
                'content_blocks' => $this->cleanJson($page->content_blocks),
                'content_blocks_ne' => $this->cleanJson($page->content_blocks_ne),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Public copy cleanup only.
    }

    private function cleanJson(?string $json): ?string
    {
        if (! $json) {
            return $json;
        }

        $blocks = json_decode($json, true);
        if (! is_array($blocks)) {
            return $json;
        }

        $blocks = $this->replaceValue($blocks);

        return json_encode($blocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function replaceValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_replace([
                'The content is written as final public copy for Radha Krishna Secondary School and can still be edited from the CMS when needed.',
                'यो सामग्री राधाकृष्ण माध्यमिक विद्यालयका लागि अन्तिम सार्वजनिक प्रतिलिपिको रूपमा तयार गरिएको हो र आवश्यक पर्दा CMS बाट सम्पादन गर्न सकिन्छ।',
            ], [
                'Each level is organized around age-appropriate learning, regular teacher guidance, and steady preparation for the next stage of school life.',
                'हरेक तह उमेरअनुसारको सिकाइ, नियमित शिक्षक मार्गदर्शन र अर्को शैक्षिक चरणको तयारीलाई ध्यानमा राखेर व्यवस्थित गरिएको छ।',
            ], $value);
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->replaceValue($item);
        }

        return $value;
    }
};
