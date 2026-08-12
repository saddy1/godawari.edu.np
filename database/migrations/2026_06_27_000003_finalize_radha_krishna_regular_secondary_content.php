<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateSecondaryCmsPage();
        $this->updateMenuLabels();
        $this->updateHomeContent();
    }

    public function down(): void
    {
        // Content cleanup migration. Do not restore the incorrect technical-school wording.
    }

    private function updateSecondaryCmsPage(): void
    {
        $page = DB::table('cms_pages')->where('slug', 'academics-secondary')->first();

        if (! $page) {
            return;
        }

        $blocksEn = $this->cleanBlocks(json_decode($page->content_blocks ?? '[]', true) ?: []);
        $blocksNe = $this->cleanBlocks(json_decode($page->content_blocks_ne ?? '[]', true) ?: []);

        DB::table('cms_pages')
            ->where('slug', 'academics-secondary')
            ->update([
                'title' => 'Secondary and Higher Secondary Education',
                'title_ne' => 'माध्यमिक तथा उच्च माध्यमिक शिक्षा',
                'content_blocks' => json_encode($blocksEn, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'content_blocks_ne' => json_encode($blocksNe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'meta_title' => 'Secondary and Higher Secondary Education - Radha Krishna Secondary School',
                'meta_title_ne' => 'माध्यमिक तथा उच्च माध्यमिक शिक्षा - राधाकृष्ण माध्यमिक विद्यालय',
                'meta_description' => 'General secondary education from Grade 9-10 and Grade 11-12 education and management groups.',
                'meta_description_ne' => 'कक्षा ९-१० साधारण माध्यमिक शिक्षा र कक्षा ११-१२ शिक्षा तथा व्यवस्थापन समूह।',
                'meta_keywords' => 'secondary education, Grade 9, Grade 10, Grade 11, Grade 12, education group, management group, Radha Krishna Secondary School',
                'meta_keywords_ne' => 'माध्यमिक शिक्षा, कक्षा ९, कक्षा १०, कक्षा ११, कक्षा १२, शिक्षा समूह, व्यवस्थापन समूह, राधाकृष्ण माध्यमिक विद्यालय',
                'updated_at' => now(),
            ]);
    }

    private function updateMenuLabels(): void
    {
        DB::table('cms_menu_items')
            ->where('url', '/academics/secondary')
            ->update([
                'label' => 'Secondary Level',
                'label_ne' => 'माध्यमिक तह',
                'subtitle' => 'Grade 9 - 12',
                'subtitle_ne' => 'कक्षा ९ - १२',
                'updated_at' => now(),
            ]);
    }

    private function updateHomeContent(): void
    {
        DB::table('home_contents')
            ->where(function ($query) {
                $query->where('title', 'like', '%Technical%')
                    ->orWhere('subtitle', 'like', '%Diploma%')
                    ->orWhere('description', 'like', '%Civil Engineering%')
                    ->orWhere('description', 'like', '%technical%');
            })
            ->update([
                'title' => 'Secondary Education',
                'subtitle' => 'Grade 9 to 12',
                'description' => 'General secondary education with Grade 11-12 education and management groups.',
                'title_ne' => 'माध्यमिक शिक्षा',
                'subtitle_ne' => 'कक्षा ९ देखि १२',
                'updated_at' => now(),
            ]);
    }

    private function cleanBlocks(array $blocks): array
    {
        return array_values(array_filter(array_map(fn ($block) => $this->cleanValue($block), $blocks)));
    }

    private function cleanValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->replaceText($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        if (($value['type'] ?? null) === 'feature_card') {
            $data = $value['data'] ?? [];
            $haystack = implode(' ', array_filter([
                $data['title'] ?? '',
                $data['text'] ?? '',
            ]));

            if (str_contains($haystack, 'Civil Engineering')
                || str_contains($haystack, 'civil engineering')
                || str_contains($haystack, 'CTEVT')
                || str_contains($haystack, 'Diploma')
                || str_contains($haystack, 'सिभिल')
                || str_contains($haystack, 'डिप्लोमा')
                || str_contains($haystack, 'सीटीईभीटी')) {
                return null;
            }
        }

        $cleaned = [];
        foreach ($value as $key => $item) {
            $cleanedItem = $this->cleanValue($item);
            if ($cleanedItem !== null) {
                $cleaned[$key] = $cleanedItem;
            }
        }

        return array_is_list($value) ? array_values($cleaned) : $cleaned;
    }

    private function replaceText(string $text): string
    {
        return str_replace([
            'Secondary and Technical Education',
            'Secondary, Higher Secondary and Technical Education',
            'Grade 9 to 12 - Technical Stream - Special Education',
            'Grade 9 to 12 · Technical Stream · Special Education',
            'Grade 9 - 12 / Diploma',
            'Technical Stream',
            'Technical',
            'technical education',
            'technical program',
            'CTEVT civil engineering',
            'CTEVT-affiliated Diploma in Civil Engineering',
            'Diploma in Civil Engineering',
            'Civil Engineering',
            'civil engineering',
            'Barchhain Secondary School',
            'माध्यमिक तथा प्राविधिक शिक्षा',
            'माध्यमिक, उच्च माध्यमिक तथा प्राविधिक शिक्षा',
            'कक्षा ९ देखि १२ - प्राविधिक धार - विशेष शिक्षा',
            'कक्षा ९ देखि १२ · प्राविधिक धार · विशेष शिक्षा',
            'कक्षा ९ - १२ / डिप्लोमा',
            'प्राविधिक धार',
            'प्राविधिक',
            'सीटीईभीटीबाट सम्बन्धन प्राप्त सिभिल इन्जिनियरिङ डिप्लोमा',
            'CTEVT सिभिल इन्जिनियरिङ',
            'सिभिल इन्जिनियरिङ डिप्लोमा',
            'सिभिल इन्जिनियरिङ',
            'बर्छैन माध्यमिक विद्यालय',
        ], [
            'Secondary and Higher Secondary Education',
            'Secondary and Higher Secondary Education',
            'Grade 9 to 12',
            'Grade 9 to 12',
            'Grade 9 - 12',
            'Grade 11-12',
            'Higher Secondary',
            'higher secondary education',
            'higher secondary program',
            'Grade 11-12 education and management',
            'Grade 11-12 education and management groups',
            'Grade 11-12 Education and Management',
            'Education and Management',
            'education and management',
            'Radha Krishna Secondary School',
            'माध्यमिक तथा उच्च माध्यमिक शिक्षा',
            'माध्यमिक तथा उच्च माध्यमिक शिक्षा',
            'कक्षा ९ देखि १२',
            'कक्षा ९ देखि १२',
            'कक्षा ९ - १२',
            'कक्षा ११-१२',
            'उच्च माध्यमिक',
            'कक्षा ११-१२ शिक्षा तथा व्यवस्थापन समूह',
            'कक्षा ११-१२ शिक्षा तथा व्यवस्थापन',
            'कक्षा ११-१२ शिक्षा तथा व्यवस्थापन',
            'शिक्षा तथा व्यवस्थापन',
            'राधाकृष्ण माध्यमिक विद्यालय',
        ], $text);
    }
};
