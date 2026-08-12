<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('cms_pages')->updateOrInsert(
            ['slug' => 'history-of-radha-krishna-mavi'],
            [
                'parent_id' => null,
                'created_by' => DB::table('users')->value('id'),
                'title' => 'History of Radha Krishna Secondary School',
                'title_ne' => 'राधाकृष्ण माध्यमिक विद्यालयको इतिहास',
                'status' => 'published',
                'content_blocks' => json_encode($this->blocksEn(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'content_blocks_ne' => json_encode($this->blocksNe(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'content_html' => null,
                'featured_image' => null,
                'template' => 'wide',
                'meta_title' => 'History of Radha Krishna Secondary School',
                'meta_title_ne' => 'राधाकृष्ण माध्यमिक विद्यालयको इतिहास',
                'meta_description' => 'A CMS-built history page covering the origin, growth, community contribution, leadership, location, and service area of Radha Krishna Secondary School.',
                'meta_description_ne' => 'राधाकृष्ण माध्यमिक विद्यालयको स्थापना, विकास, समुदायको योगदान, नेतृत्व, भौगोलिक अवस्था र सेवा क्षेत्र समेटिएको CMS इतिहास पृष्ठ।',
                'meta_keywords' => 'Radha Krishna Secondary School history, school history Doti, Latamandau Gopghat school',
                'meta_keywords_ne' => 'राधाकृष्ण माध्यमिक विद्यालय इतिहास, डोटी विद्यालय इतिहास, लाटामाण्डौँ गोपघाट विद्यालय',
                'sort_order' => 20,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $menuId = DB::table('cms_menus')->whereRaw('LOWER(location) = ?', ['header'])->value('id');
        if ($menuId) {
            $aboutId = DB::table('cms_menu_items')
                ->where('cms_menu_id', $menuId)
                ->where('label', 'About')
                ->value('id');

            DB::table('cms_menu_items')->updateOrInsert(
                ['cms_menu_id' => $menuId, 'label' => 'History'],
                [
                    'parent_id' => $aboutId,
                    'cms_page_id' => null,
                    'label_ne' => 'इतिहास',
                    'subtitle' => 'School journey',
                    'subtitle_ne' => 'विद्यालय यात्रा',
                    'type' => 'url',
                    'url' => '/pages/history-of-radha-krishna-mavi',
                    'target' => '_self',
                    'sort_order' => 5,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('cms_menu_items')
            ->where('url', '/pages/history-of-radha-krishna-mavi')
            ->delete();

        DB::table('cms_pages')
            ->where('slug', 'history-of-radha-krishna-mavi')
            ->delete();
    }

    private function blocksEn(): array
    {
        return [
            $this->hero(
                'School History',
                'A community-built institution with more than six decades of public service',
                'Radha Krishna Secondary School grew from a small community pathshala into a secondary and higher-secondary institution through local leadership, donated land, public support, and continuous commitment to education in Doti.',
                'Read the Journey',
                'Key Milestones',
                'School Legacy',
                'From a village learning initiative to a recognised public school serving Latamandau, Barpata, and nearby communities.'
            ),
            $this->stats([
                ['label' => 'Started', 'value' => '2009 B.S.'],
                ['label' => 'Secondary Level', 'value' => '2050 B.S.'],
                ['label' => '+2 Started', 'value' => '2065 B.S.'],
                ['label' => 'Location', 'value' => 'Gopghat, Doti'],
            ]),
            $this->textRow(
                'Historical Background',
                'Shree Radha Krishna Secondary School is a community-built public institution whose identity has grown with the faith, service, and educational commitment of local people. The name Radha Krishna carries the cultural and spiritual identity of the community and has remained a respected part of the school legacy for generations.',
                'Formal learning in this area began in B.S. 2009, when local leaders started a small village pathshala to spread educational awareness. In B.S. 2012, the school received temporary affiliation from the Forest Division Office and operated from Tilatali Pipalbot. As educational need increased, the school gradually received approvals, expanded class levels, and became a public institution serving children from Gopghat, Latamandau, Barpata, and nearby communities.'
            ),
            $this->cards('Community Effort', 'The school was built step by step by residents, teachers, students, guardians, school management committees, PTA members, principals, and local social workers.', [
                ['icon' => '🏫', 'title' => 'Village Pathshala', 'text' => 'The first phase began as a small community-run learning centre where local leaders worked to bring educational awareness to the village.'],
                ['icon' => '🌱', 'title' => 'Land and Infrastructure', 'text' => 'The move to the present location was possible through donated land and collective labour, including timber, stone transport, playground work, and classroom construction.'],
                ['icon' => '📚', 'title' => 'Academic Growth', 'text' => 'The school gradually moved from primary to lower-secondary, secondary, and then higher-secondary education, adding new streams as community demand increased.'],
            ]),
            $this->timelineTable(),
            $this->textRow(
                'Growth to Secondary and Higher Secondary Level',
                'In B.S. 2032, the school received permanent approval for classes 1 to 3. During the effort to upgrade to lower-secondary level, the original site lacked sufficient land. In B.S. 2036, the school was shifted to its present area at Gopghat, Latamandau-8, where the lower-secondary level could be developed.',
                'Motisingh Balayar made an important contribution by donating land for the school at the present location. The continued support of Mahavir Balayar, Laxmikant Pant, school heads, committee chairs, local residents, teachers, and social workers helped establish the lower-secondary foundation. In B.S. 2050, the school received permission to run classes 9 and 10, marking a major step toward full secondary education.'
            ),
            $this->cards('Important Contributions', 'The document records many contributions that shaped the school. Not every individual name can be included here, but the page preserves the major forms of support noted in the source.', [
                ['icon' => '🤝', 'title' => 'Local Leadership', 'text' => 'Community leaders and school committee members worked continuously for permissions, teachers, buildings, and recognition.'],
                ['icon' => '🪵', 'title' => 'Material Support', 'text' => 'Residents from surrounding areas contributed timber, furniture, labour, transport of stone and wood, and support for building works.'],
                ['icon' => '🎓', 'title' => 'Teacher and Student Effort', 'text' => 'Teachers and students contributed to school development, including classroom activities, physical works, playground development, and academic progress.'],
            ]),
            $this->textRow(
                'Achievements and Recognition',
                'After the arrival of new leadership in B.S. 2050, the document notes visible progress in both physical and academic areas. The school’s SLC results were considered among the better results in the district, and in B.S. 2057 the school received an education award flag and a cash award of Rs. 50,000 for strong performance.',
                'With the political change and wider educational awareness after the establishment of the republic, the school climbed another step. In B.S. 2065, +2 education started in the Education stream. In B.S. 2067, the Commerce stream was also introduced. Financial and institutional support from local bodies, political leaders, the community, and the school hostel helped make this expansion possible.'
            ),
            $this->tableRow('Short Institutional Timeline', [
                ['Year (B.S.)', 'Level / Event', 'Notes'],
                ['2009', 'Village pathshala started', 'Community-led educational awareness began.'],
                ['2012', 'Temporary affiliation', 'Received temporary connection and operated from Tilatali Pipalbot.'],
                ['2032', 'Primary approval', 'Classes 1-3 received permanent approval.'],
                ['2036', 'Moved to present site', 'Shifted to Gopghat, Latamandau-8 for lower-secondary expansion.'],
                ['2050', 'Secondary level', 'Permission received for classes 9 and 10.'],
                ['2065', '+2 Education', 'Higher-secondary education stream started.'],
                ['2067', '+2 Commerce', 'Commerce stream added.'],
            ]),
            $this->textRow(
                'Geographical Setting',
                'The school is located in Doti district, at Gopghat above the market area of Latamandau-8, near the Seti River. The document describes it as being about five minutes south of the D.K.I. Singh Highway in a hill area. Its altitude is noted as approximately 665 meters above sea level.',
                'The surrounding region is culturally rich and largely Hindu, with Doteli language and local traditions strongly present. The service area includes Latamandau wards 8 and 9 and Barpata wards 1 to 7, with students also coming from nearby settlements and districts because of the school’s reputation and SLC results.'
            ),
            $this->cta(
                'A Legacy Carried by the Community',
                'The school’s history is not only a record of dates. It is a record of donated land, shared labour, leadership, student effort, teacher commitment, and the belief that public education can transform a rural community.',
                'Contact School',
                '/contact'
            ),
        ];
    }

    private function blocksNe(): array
    {
        return [
            $this->hero(
                'विद्यालय इतिहास',
                'छ दशकभन्दा लामो सार्वजनिक सेवाको सामुदायिक यात्रा',
                'राधाकृष्ण माध्यमिक विद्यालय सानो सामुदायिक पाठशालाबाट सुरु भई स्थानीय नेतृत्व, जग्गा दान, समुदायको श्रम, शिक्षक-विद्यार्थीको योगदान र निरन्तर शैक्षिक प्रयासका आधारमा माध्यमिक तथा उच्च माध्यमिक तहसम्म विकसित भएको संस्था हो।',
                'इतिहास पढ्नुहोस्',
                'मुख्य उपलब्धि',
                'विद्यालयको विरासत',
                'गाउँको शैक्षिक चेतनाबाट सुरु भएको यात्रा आज लाटामाण्डौँ, बारपाटा र वरपरका समुदायलाई सेवा गर्ने सार्वजनिक विद्यालयका रूपमा अघि बढेको छ।'
            ),
            $this->stats([
                ['label' => 'सुरुवात', 'value' => 'वि.सं. २००९'],
                ['label' => 'माध्यमिक तह', 'value' => 'वि.सं. २०५०'],
                ['label' => '+२ सुरु', 'value' => 'वि.सं. २०६५'],
                ['label' => 'स्थान', 'value' => 'गोपघाट, डोटी'],
            ]),
            $this->textRow(
                'ऐतिहासिक पृष्ठभूमि',
                'विद्यालय इतिहास दस्तावेजअनुसार यो पूर्ण रूपमा सामुदायिक सार्वजनिक विद्यालय हो। “राधाकृष्ण” नाम स्थानीय संस्थापक तथा समुदायको भगवान श्रीकृष्णप्रतिको आस्थासँग जोडिएको मानिन्छ। नामकरणको औपचारिक प्रमाण नभेटिए पनि यो नामले समुदायको पहिचान, आस्था र विद्यालयको विरासत बोकेको छ।',
                'यस क्षेत्रमा औपचारिक शिक्षाको सुरुवात वि.सं. २००९ मा स्थानीय अगुवाहरूले गाउँकै सानो पाठशालाबाट शैक्षिक चेतना फैलाउने प्रयासबाट भएको देखिन्छ। वि.सं. २०१२ मा वन डिभिजन कार्यालयबाट अस्थायी सम्बन्धन प्राप्त गरी विद्यालय तिलताली पिपलबोटमा सञ्चालन भएको थियो। त्यसपछि राष्ट्रिय शिक्षा नीति र स्थानीय आवश्यकतासँगै विद्यालयले क्रमशः तह विस्तार गर्दै गयो।'
            ),
            $this->cards('समुदायको प्रयास', 'विद्यालय स्थानीय बासिन्दा, शिक्षक, विद्यार्थी, अभिभावक, विद्यालय व्यवस्थापन समिति, PTA, प्रधानाध्यापक र समाजसेवीहरूको संयुक्त प्रयासबाट क्रमशः निर्माण भएको हो।', [
                ['icon' => '🏫', 'title' => 'गाउँको पाठशाला', 'text' => 'पहिलो चरणमा स्थानीय अगुवाहरूले गाउँमा शिक्षाको चेतना ल्याउन सानो सामुदायिक पाठशाला सञ्चालन गरे।'],
                ['icon' => '🌱', 'title' => 'जग्गा र पूर्वाधार', 'text' => 'हालको स्थानमा विद्यालय सार्न जग्गा दान, काठपात, ढुंगा बोकाइ, खेलमैदान निर्माण र भवन निर्माणमा समुदायको महत्वपूर्ण श्रम र सहयोग रह्यो।'],
                ['icon' => '📚', 'title' => 'शैक्षिक वृद्धि', 'text' => 'विद्यालय प्राथमिकबाट निम्न माध्यमिक, माध्यमिक र उच्च माध्यमिक तहसम्म क्रमशः विस्तार हुँदै गयो।'],
            ]),
            $this->timelineTable(true),
            $this->textRow(
                'माध्यमिक र उच्च माध्यमिक तहसम्मको विकास',
                'वि.सं. २०३२ मा विद्यालयले कक्षा १-३ सम्मको स्थायी स्वीकृति प्राप्त गर्‍यो। निम्न माध्यमिक तह विस्तारको प्रयास गर्दा पुरानो स्थानमा जग्गाको अभाव भएपछि वि.सं. २०३६ मा विद्यालय हालको गोपघाट, लाटामाण्डौँ-८ क्षेत्रमा सारिएको थियो।',
                'इतिहास दस्तावेजले हालको स्थानमा विद्यालय निर्माणका लागि मोती सिंह बलायरले जग्गा दान गरेको उल्लेख गर्छ। महावीर बलायर, लक्ष्मीकान्त पन्त, तत्कालीन प्रधानाध्यापक, अध्यक्ष, स्थानीय बासिन्दा, शिक्षक र समाजसेवीहरूको योगदानले निम्न माध्यमिक तहको आधार बलियो बन्यो। वि.सं. २०५० मा कक्षा ९ र १० सञ्चालन अनुमति प्राप्त भएपछि विद्यालयले माध्यमिक तहमा महत्वपूर्ण फड्को मार्‍यो।'
            ),
            $this->cards('महत्वपूर्ण योगदान', 'इतिहास दस्तावेजमा विद्यालय निर्माण र विकासमा धेरै व्यक्ति र समूहको योगदान उल्लेख छ। यहाँ मुख्य प्रकारका योगदानलाई व्यवस्थित रूपमा समेटिएको छ।', [
                ['icon' => '🤝', 'title' => 'स्थानीय नेतृत्व', 'text' => 'अनुमति, शिक्षक दरबन्दी, भवन, तह वृद्धि र विद्यालय सञ्चालनका लागि स्थानीय अगुवा तथा समितिका पदाधिकारीहरू निरन्तर सक्रिय रहे।'],
                ['icon' => '🪵', 'title' => 'भौतिक सहयोग', 'text' => 'वरपरका समुदायबाट काठपात, फर्निचर, श्रमदान, ढुंगा-काठ ढुवानी र भवन निर्माणका लागि सहयोग प्राप्त भयो।'],
                ['icon' => '🎓', 'title' => 'शिक्षक र विद्यार्थीको श्रम', 'text' => 'शिक्षक र विद्यार्थीहरूले पठनपाठनसँगै विद्यालयको भौतिक विकास, खेलमैदान र शैक्षिक वातावरण सुधारमा योगदान गरे।'],
            ]),
            $this->textRow(
                'उपलब्धि र पहिचान',
                'वि.सं. २०५० पछि विद्यालयले भौतिक तथा शैक्षिक क्षेत्रमा उल्लेखनीय प्रगति गरेको दस्तावेजमा उल्लेख छ। विद्यालयको SLC नतिजा जिल्लाकै राम्रो नतिजामध्ये मानिएको र वि.सं. २०५७ को SLC नतिजाका आधारमा शिक्षा पुरस्कार झण्डा तथा रु. ५०,००० नगद पुरस्कार प्राप्त गरेको उल्लेख छ।',
                'गणतन्त्र स्थापनापछिको शैक्षिक चेतना र स्थानीय प्रयाससँगै वि.सं. २०६५ मा +२ शिक्षाशास्त्र संकाय सुरु भयो। वि.सं. २०६७ मा +२ वाणिज्य संकाय पनि सञ्चालन भयो। स्थानीय निकाय, समुदाय, राजनीतिक नेतृत्व र छात्रावासबाट आउने स्रोतले उच्च माध्यमिक तह सञ्चालनमा सहयोग पुर्‍यायो।'
            ),
            $this->tableRow('संक्षिप्त संस्थागत समयरेखा', [
                ['वर्ष (वि.सं.)', 'तह / घटना', 'टिप्पणी'],
                ['२००९', 'गाउँको पाठशाला सुरु', 'समुदायबाट शैक्षिक चेतनाको सुरुवात।'],
                ['२०१२', 'अस्थायी सम्बन्धन', 'तिलताली पिपलबोटमा सञ्चालन।'],
                ['२०३२', 'प्राथमिक स्वीकृति', 'कक्षा १-३ को स्थायी स्वीकृति।'],
                ['२०३६', 'हालको स्थानमा स्थानान्तरण', 'निम्न माध्यमिक तह विस्तारका लागि गोपघाट, लाटामाण्डौँ-८ मा सारियो।'],
                ['२०५०', 'माध्यमिक तह', 'कक्षा ९ र १० सञ्चालन अनुमति।'],
                ['२०६५', '+२ शिक्षाशास्त्र', 'उच्च माध्यमिक तह सुरु।'],
                ['२०६७', '+२ वाणिज्य', 'वाणिज्य संकाय थप।'],
            ]),
            $this->textRow(
                'भौगोलिक अवस्था',
                'विद्यालय डोटी जिल्लाको लाटामाण्डौँ-८ स्थित गोपघाट बजारमाथि, सेती नदीको नजिक, डि.के.आई. सिंह राजमार्गबाट दक्षिणतर्फ करिब पाँच मिनेटको दूरीमा रहेको पहाडी क्षेत्रमा अवस्थित छ। दस्तावेजमा विद्यालयको उचाइ समुद्री सतहबाट करिब ६६५ मिटर उल्लेख गरिएको छ।',
                'सेवा क्षेत्र लाटामाण्डौँका वडा ८ र ९ तथा बारपाटा वडा १ देखि ७ सम्म फैलिएको छ। विद्यालयको शैक्षिक पहिचान र नतिजाका कारण वरपरका बस्ती तथा छिमेकी क्षेत्रबाट समेत विद्यार्थी अध्ययनका लागि आउने गरेको उल्लेख छ।'
            ),
            $this->cta(
                'समुदायले बोकेको विरासत',
                'यो इतिहास केवल मिति र घटनाको सूची होइन। यो दान गरिएको जग्गा, साझा श्रम, नेतृत्व, विद्यार्थीको मेहनत, शिक्षकको प्रतिबद्धता र सार्वजनिक शिक्षाले समुदाय बदल्न सक्छ भन्ने विश्वासको दस्तावेज हो।',
                'विद्यालयमा सम्पर्क गर्नुहोस्',
                '/contact'
            ),
        ];
    }

    private function hero(string $eyebrow, string $title, string $description, string $primaryLabel, string $secondaryLabel, string $cardTitle, string $cardText): array
    {
        return [
            'type' => 'row',
            'data' => [
                'section' => 'hero',
                'width' => 'wide',
                'gap' => 'large',
                'columns' => 2,
                'eyebrow' => $eyebrow,
                'badge' => $eyebrow,
                'title' => $title,
                'description' => $description,
                'primary_label' => $primaryLabel,
                'primary_url' => '#key-milestones',
                'secondary_label' => $secondaryLabel,
                'secondary_url' => '#key-milestones',
            ],
            'columns' => [
                ['blocks' => []],
                ['blocks' => [[
                    'type' => 'feature_card',
                    'data' => ['icon' => '📜', 'title' => $cardTitle, 'text' => $cardText, 'align' => 'left'],
                ]]],
            ],
        ];
    }

    private function stats(array $items): array
    {
        return [
            'type' => 'row',
            'data' => ['section' => 'stats', 'width' => 'wide', 'gap' => 'compact', 'columns' => 4],
            'columns' => array_map(fn ($item) => ['blocks' => [[
                'type' => 'stat',
                'data' => ['label' => $item['label'], 'value' => $item['value'], 'align' => 'center'],
            ]]], $items),
        ];
    }

    private function textRow(string $title, string $left, string $right): array
    {
        return [
            'type' => 'row',
            'data' => ['section' => 'normal', 'width' => 'wide', 'gap' => 'large', 'columns' => 2],
            'columns' => [
                ['blocks' => [
                    ['type' => 'heading', 'data' => ['text' => $title, 'level' => '2', 'align' => 'left']],
                    ['type' => 'paragraph', 'data' => ['text' => $left, 'align' => 'left']],
                ]],
                ['blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => $right, 'align' => 'left']],
                ]],
            ],
        ];
    }

    private function cards(string $title, string $description, array $cards): array
    {
        return [
            'type' => 'row',
            'data' => ['section' => 'cards', 'width' => 'wide', 'gap' => 'normal', 'columns' => 3, 'eyebrow' => 'History', 'title' => $title, 'description' => $description],
            'columns' => array_map(fn ($card) => ['blocks' => [[
                'type' => 'feature_card',
                'data' => ['icon' => $card['icon'], 'title' => $card['title'], 'text' => $card['text'], 'align' => 'left'],
            ]]], $cards),
        ];
    }

    private function timelineTable(bool $ne = false): array
    {
        return $this->tableRow(
            $ne ? 'विद्यालय तह विस्तारको संक्षिप्त इतिहास' : 'Brief History of Level Expansion',
            $ne
                ? [
                    ['वर्ष (वि.सं.)', 'तह', 'कक्षा सञ्चालन', 'स्वीकृति / निकाय'],
                    ['२००९', 'प्राथमिक सुरुवात', '१-३', 'समुदायबाट सञ्चालन'],
                    ['२०१२', 'प्राथमिक', '१-५', 'वन डिभिजन कार्यालय'],
                    ['२०३२', 'प्राथमिक', '१-३', 'जिल्ला शिक्षा कार्यालय, डोटी'],
                    ['२०३६', 'निम्न माध्यमिक', '१-७', 'जिल्ला शिक्षा कार्यालय, डोटी'],
                    ['२०५०', 'माध्यमिक', '१-१०', 'जिल्ला शिक्षा कार्यालय, डोटी'],
                    ['२०६५', 'उच्च माध्यमिक', '१-१२', 'उच्च माध्यमिक शिक्षा परिषद्'],
                ]
                : [
                    ['Year (B.S.)', 'Level', 'Classes', 'Approval / Body'],
                    ['2009', 'Primary beginning', '1-3', 'Community operation'],
                    ['2012', 'Primary', '1-5', 'Forest Division Office'],
                    ['2032', 'Primary', '1-3', 'District Education Office, Doti'],
                    ['2036', 'Lower Secondary', '1-7', 'District Education Office, Doti'],
                    ['2050', 'Secondary', '1-10', 'District Education Office, Doti'],
                    ['2065', 'Higher Secondary', '1-12', 'Higher Secondary Education Council'],
                ],
            'key-milestones'
        );
    }

    private function tableRow(string $title, array $rows, ?string $id = null): array
    {
        return [
            'type' => 'row',
            'data' => ['section' => 'normal', 'width' => 'wide', 'gap' => 'normal', 'columns' => 1, 'title' => $title],
            'columns' => [[
                'blocks' => [[
                    'type' => 'heading',
                    'data' => ['text' => $title, 'level' => '2', 'align' => 'left'],
                ], [
                    'type' => 'html',
                    'data' => ['html' => '<div'.($id ? ' id="'.$id.'"' : '').'></div>'],
                ], [
                    'type' => 'table',
                    'data' => [
                        'rows' => json_encode([
                            'headerRow' => true,
                            'border' => true,
                            'padding' => 'md',
                            'cells' => array_map(fn ($row) => array_map(fn ($cell) => ['text' => $cell], $row), $rows),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ]],
            ]],
        ];
    }

    private function cta(string $title, string $description, string $label, string $url): array
    {
        return [
            'type' => 'row',
            'data' => [
                'section' => 'cta',
                'width' => 'wide',
                'gap' => 'normal',
                'columns' => 1,
                'eyebrow' => 'Radha Krishna Secondary School',
                'title' => $title,
                'description' => $description,
                'primary_label' => $label,
                'primary_url' => $url,
            ],
            'columns' => [['blocks' => []]],
        ];
    }
};
