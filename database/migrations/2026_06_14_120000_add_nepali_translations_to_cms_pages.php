<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->string('title_ne')->nullable()->after('title');
            $table->json('content_blocks_ne')->nullable()->after('content_blocks');
            $table->string('meta_title_ne')->nullable()->after('meta_title');
            $table->text('meta_description_ne')->nullable()->after('meta_description');
            $table->string('meta_keywords_ne')->nullable()->after('meta_keywords');
        });

        foreach ($this->pages() as $page) {
            DB::table('cms_pages')->where('slug', $page['slug'])->update([
                'title_ne' => $page['title'],
                'content_blocks_ne' => json_encode($this->blocks($page), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'meta_title_ne' => $page['meta_title'],
                'meta_description_ne' => $page['description'],
                'meta_keywords_ne' => $page['keywords'],
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn([
                'title_ne',
                'content_blocks_ne',
                'meta_title_ne',
                'meta_description_ne',
                'meta_keywords_ne',
            ]);
        });
    }

    private function pages(): array
    {
        return [
            [
                'slug' => 'academics-elementary',
                'title' => 'प्रारम्भिक बालविकास तथा आधारभूत जग',
                'meta_title' => 'प्रारम्भिक बालविकास तथा आधारभूत जग - बर्छैन माध्यमिक विद्यालय',
                'subtitle' => 'बालविकासदेखि कक्षा ३ सम्म',
                'description' => 'बालबालिकाले भाषा, गणित, असल बानी, जिज्ञासा र आत्मविश्वास विकास गर्ने विद्यालय जीवनको आत्मीय सुरुवात।',
                'overview' => 'प्रारम्भिक कक्षाहरूमा बालमैत्री सिकाइ, नियमित उपस्थिति, पढ्ने बानी, हस्तलेखन, आधारभूत गणित, सरसफाइ, अनुशासन, गीत, कथा, खेल तथा सामाजिक व्यवहारमा जोड दिइन्छ।',
                'image' => 'uploads/site/academics-elementary-image.jpeg',
                'focus' => 'पढ्ने बानी, आधारभूत गणित, सिर्जनात्मक क्रियाकलाप, सरसफाइ, शिष्टाचार र नियमित उपस्थितिको विकास।',
                'keywords' => 'बालविकास, कक्षा ३, प्रारम्भिक शिक्षा, बर्छैन माध्यमिक विद्यालय',
                'stats' => [
                    ['label' => 'तह', 'value' => 'बालविकासदेखि कक्षा ३'],
                    ['label' => 'कार्यक्रम', 'value' => 'आधार निर्माण'],
                    ['label' => 'सिकाइ', 'value' => 'क्रियाकलापमा आधारित'],
                ],
                'highlights' => [
                    ['icon' => '📚', 'title' => 'बालमैत्री सिकाइ', 'text' => 'क्रियाकलाप, कथा, चित्रकला, खेल तथा स्थानीय उदाहरणमार्फत बालबालिकाले स्वाभाविक रूपमा सिक्छन्।'],
                    ['icon' => '✍️', 'title' => 'भाषा तथा गणित', 'text' => 'नेपाली, अङ्ग्रेजी, हस्तलेखन, पठन, गणना र प्रारम्भिक समस्या समाधानमा विशेष ध्यान दिइन्छ।'],
                    ['icon' => '✅', 'title' => 'हेरचाह तथा दिनचर्या', 'text' => 'शिक्षकहरूले सरसफाइ, समयपालन, कक्षाकोठाका बानी, आत्मविश्वास र सामाजिक भावनात्मक विकासमा सहयोग गर्छन्।'],
                ],
                'subjects' => [
                    ['icon' => '📖', 'title' => 'पढ्ने बानी', 'text' => 'दैनिक पठन, चित्रमाथि छलफल, कथा र आधारभूत बोध।'],
                    ['icon' => '🔢', 'title' => 'आधारभूत गणित', 'text' => 'गणना, सङ्ख्याबोध, आकार, ढाँचा र प्रारम्भिक समस्या समाधान।'],
                    ['icon' => '🎨', 'title' => 'सिर्जनात्मक क्रियाकलाप', 'text' => 'चित्रकला, गीत, खेल, स्थानीय सामग्री र आत्मविश्वास बढाउने गतिविधि।'],
                ],
            ],
            [
                'slug' => 'academics-primary',
                'title' => 'आधारभूत तह शिक्षा',
                'meta_title' => 'आधारभूत तह शिक्षा - बर्छैन माध्यमिक विद्यालय',
                'subtitle' => 'कक्षा ४ देखि कक्षा ८ सम्म',
                'description' => 'माध्यमिक तहका लागि विषयगत आधार, अनुशासन, सहकार्य र व्यावहारिक सिकाइलाई बलियो बनाउने शिक्षा।',
                'overview' => 'आधारभूत तहले प्रारम्भिक साक्षरतालाई गहन विषयगत सिकाइसँग जोड्छ। विद्यार्थीहरूले पठनबोध, गणित, वैज्ञानिक सोच, सामाजिक चेतना, स्थानीय ज्ञान, सूचना प्रविधिको परिचय र सहक्रियाकलापमा सहभागिता विकास गर्छन्।',
                'image' => 'uploads/site/academics-primary-image.jpeg',
                'focus' => 'नेपाली, अङ्ग्रेजी, गणित, विज्ञान तथा प्रविधि, सामाजिक अध्ययन, स्वास्थ्य तथा शारीरिक शिक्षा, स्थानीय पाठ्यक्रम र सूचना प्रविधि।',
                'keywords' => 'कक्षा ४, कक्षा ८, आधारभूत तह शिक्षा, बर्छैन माध्यमिक विद्यालय',
                'stats' => [
                    ['label' => 'तह', 'value' => 'कक्षा ४ देखि ८'],
                    ['label' => 'केन्द्र', 'value' => 'मुख्य विषयहरू'],
                    ['label' => 'सिकाइ', 'value' => 'परियोजना र सूचना प्रविधि'],
                ],
                'highlights' => [
                    ['icon' => '🧠', 'title' => 'अवधारणामा आधारित शिक्षण', 'text' => 'शिक्षकहरूले व्याख्या, अभ्यास, परियोजना कार्य, प्रश्न र स्थानीय सन्दर्भबाट बुझाइ बलियो बनाउँछन्।'],
                    ['icon' => '💻', 'title' => 'सूचना प्रविधि तथा पुस्तकालय संस्कृति', 'text' => 'विद्यालय सुधार योजनाले सूचना प्रविधि, पुस्तक कुना, पुस्तकालय प्रयोग र डिजिटल साक्षरतालाई प्राथमिकता दिन्छ।'],
                    ['icon' => '📋', 'title' => 'निरन्तर मूल्याङ्कन', 'text' => 'कक्षाकार्य, एकाइ परीक्षा, सहभागिता, उपस्थिति र पृष्ठपोषणबाट प्रत्येक विद्यार्थीलाई सहयोग गरिन्छ।'],
                ],
                'subjects' => [
                    ['icon' => '🔬', 'title' => 'विज्ञान तथा प्रविधि', 'text' => 'अवलोकन, प्रयोग, स्थानीय उदाहरण र व्यावहारिक वैज्ञानिक सोच।'],
                    ['icon' => '🌍', 'title' => 'सामाजिक अध्ययन', 'text' => 'समुदाय, नागरिकता, इतिहास, भूगोल र स्थानीय चेतना।'],
                    ['icon' => '🏃', 'title' => 'स्वास्थ्य तथा शारीरिक शिक्षा', 'text' => 'स्वस्थ बानी, सहकार्य, खेल, अनुशासन र विद्यार्थी सहभागिता।'],
                ],
            ],
            [
                'slug' => 'academics-secondary',
                'title' => 'माध्यमिक तथा प्राविधिक शिक्षा',
                'meta_title' => 'माध्यमिक, उच्च माध्यमिक तथा प्राविधिक शिक्षा - बर्छैन माध्यमिक विद्यालय',
                'subtitle' => 'कक्षा ९ देखि १२ - प्राविधिक धार - विशेष शिक्षा',
                'description' => 'सामान्य शिक्षा, कक्षा ११-१२ को शिक्षा तथा व्यवस्थापन समूह, सिभिल इन्जिनियरिङ र समावेशी सिकाइ सहयोग।',
                'overview' => 'बर्छैन माध्यमिक विद्यालयले कक्षा ९-१० मा सामान्य माध्यमिक शिक्षा, कक्षा ११-१२ मा शिक्षा तथा व्यवस्थापन/वाणिज्य समूह, सीटीईभीटीबाट सम्बन्धन प्राप्त सिभिल इन्जिनियरिङ डिप्लोमा र बौद्धिक अपाङ्गता भएका बालबालिकाका लागि आवासीय स्रोत कक्षा सञ्चालन गर्छ।',
                'image' => 'uploads/site/academics-secondary-image.jpeg',
                'focus' => 'नियमित कक्षा र उपस्थिति, प्रयोगात्मक तथा परियोजनामा आधारित सिकाइ, प्रयोगशाला र सूचना प्रविधिको प्रयोग, वृत्ति मार्गदर्शन, विद्यार्थी सहयोग, खेलकुद र सहक्रियाकलाप।',
                'keywords' => 'माध्यमिक शिक्षा, कक्षा ११, कक्षा १२, सीटीईभीटी, सिभिल इन्जिनियरिङ, बर्छैन माध्यमिक विद्यालय',
                'stats' => [
                    ['label' => 'तह', 'value' => 'कक्षा ९ देखि १२'],
                    ['label' => 'प्राविधिक', 'value' => 'सिभिल इन्जिनियरिङ'],
                    ['label' => 'सहयोग', 'value' => 'समावेशी कक्षा'],
                ],
                'highlights' => [
                    ['icon' => '🎓', 'title' => 'कक्षा ९-१० सामान्य धार', 'text' => 'अनिवार्य तथा ऐच्छिक विषयमा केन्द्रित सिकाइसँगै परीक्षा तयारी, अनुशासन, परियोजना कार्य र विद्यार्थी परामर्श।'],
                    ['icon' => '🏫', 'title' => 'कक्षा ११-१२ का समूह', 'text' => 'शिक्षा तथा व्यवस्थापन समूहले शिक्षण, सामाजिक विकास, व्यवसाय, लेखा र वाणिज्यमा रुचि भएका विद्यार्थीलाई सहयोग गर्छ।'],
                    ['icon' => '🏗️', 'title' => 'सिभिल इन्जिनियरिङ डिप्लोमा', 'text' => 'स्थानीय तथा राष्ट्रिय विकासका लागि व्यावहारिक इन्जिनियरिङ सीप विकास गर्न २०७६ सालमा सुरु गरिएको तीनवर्षे प्राविधिक कार्यक्रम।'],
                ],
                'subjects' => [
                    ['icon' => '🧪', 'title' => 'प्रयोगशाला तथा सूचना प्रविधि', 'text' => 'प्रयोगात्मक सिकाइ, प्रविधिमैत्री शिक्षण र डिजिटल स्रोतहरू।'],
                    ['icon' => '🧭', 'title' => 'वृत्ति मार्गदर्शन', 'text' => 'विषयधार, परीक्षा, सीप, उच्च शिक्षा र भावी अवसरका लागि मार्गदर्शन।'],
                    ['icon' => '🤝', 'title' => 'समावेशी सहयोग', 'text' => 'हेरचाह, समावेशिता र जीवनोपयोगी सीपमा केन्द्रित आवासीय सिकाइ सहयोग।'],
                ],
            ],
        ];
    }

    private function blocks(array $page): array
    {
        return [
            [
                'type' => 'row',
                'data' => [
                    'section' => 'hero', 'width' => 'wide', 'gap' => 'large', 'columns' => 2,
                    'palette' => 'dark', 'pattern' => 'grid', 'eyebrow' => 'शैक्षिक कार्यक्रम',
                    'badge' => $page['subtitle'], 'title' => $page['title'], 'description' => $page['description'],
                    'image' => $page['image'], 'primary_label' => 'भर्ना जानकारी', 'primary_url' => '/admissions',
                    'secondary_label' => 'सम्पर्क गर्नुहोस्', 'secondary_url' => '/contact',
                ],
                'columns' => [
                    ['blocks' => []],
                    ['blocks' => [[
                        'type' => 'feature_card',
                        'data' => ['icon' => '🎯', 'title' => 'सिकाइको केन्द्र', 'text' => $page['focus'], 'align' => 'left'],
                    ]]],
                ],
            ],
            [
                'type' => 'row',
                'data' => ['section' => 'stats', 'width' => 'wide', 'gap' => 'compact', 'columns' => 3],
                'columns' => array_map(fn ($stat) => ['blocks' => [[
                    'type' => 'stat',
                    'data' => ['label' => $stat['label'], 'value' => $stat['value'], 'align' => 'left'],
                ]]], $page['stats']),
            ],
            [
                'type' => 'row',
                'data' => ['section' => 'normal', 'width' => 'wide', 'gap' => 'large', 'columns' => 2],
                'columns' => [
                    ['blocks' => [
                        ['type' => 'heading', 'data' => ['text' => 'शैक्षिक कार्यक्रमको परिचय', 'level' => '2', 'align' => 'left']],
                        ['type' => 'paragraph', 'data' => ['text' => $page['overview'], 'align' => 'left']],
                        ['type' => 'button', 'data' => ['label' => 'भर्ना जानकारी', 'url' => '/admissions', 'style' => 'primary', 'align' => 'left']],
                    ]],
                    ['blocks' => [[
                        'type' => 'image',
                        'data' => ['url' => $page['image'], 'caption' => $page['subtitle'], 'align' => 'left'],
                    ]]],
                ],
            ],
            [
                'type' => 'row',
                'data' => [
                    'section' => 'cards', 'width' => 'wide', 'gap' => 'normal', 'columns' => 3,
                    'palette' => 'light', 'pattern' => 'none', 'eyebrow' => 'कार्यक्रमका विशेषता',
                    'title' => 'विद्यार्थीले प्राप्त गर्ने अनुभव',
                    'description' => 'यी सामग्रीहरू प्रशासकले सीएमएसबाट आवश्यकता अनुसार सम्पादन गर्न सक्छन्।',
                ],
                'columns' => $this->featureColumns($page['highlights']),
            ],
            [
                'type' => 'row',
                'data' => [
                    'section' => 'dark', 'width' => 'wide', 'gap' => 'normal', 'columns' => 3,
                    'palette' => 'green', 'pattern' => 'diagonal', 'eyebrow' => 'सिकाइका क्षेत्र',
                    'title' => 'मुख्य केन्द्रहरू', 'description' => $page['focus'],
                ],
                'columns' => $this->featureColumns($page['subjects']),
            ],
            [
                'type' => 'row',
                'data' => [
                    'section' => 'cta', 'width' => 'wide', 'gap' => 'normal', 'columns' => 1,
                    'eyebrow' => 'भर्ना', 'title' => 'बर्छैन माध्यमिक विद्यालयमा अध्ययन गर्न इच्छुक हुनुहुन्छ?',
                    'description' => 'भर्ना विवरण, कक्षा उपलब्धता, प्राविधिक शिक्षा र विद्यार्थी सहयोगका लागि विद्यालय कार्यालयमा सम्पर्क गर्नुहोस्।',
                    'primary_label' => 'भर्ना जानकारी', 'primary_url' => '/admissions',
                    'secondary_label' => 'सम्पर्क गर्नुहोस्', 'secondary_url' => '/contact',
                ],
                'columns' => [['blocks' => []]],
            ],
        ];
    }

    private function featureColumns(array $items): array
    {
        return array_map(fn ($item) => ['blocks' => [[
            'type' => 'feature_card',
            'data' => [
                'icon' => $item['icon'],
                'title' => $item['title'],
                'text' => $item['text'],
                'align' => 'left',
            ],
        ]]], $items);
    }
};
