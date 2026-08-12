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
                'template' => 'wide',
                'meta_title' => 'History of Radha Krishna Secondary School',
                'meta_title_ne' => 'राधाकृष्ण माध्यमिक विद्यालयको इतिहास',
                'meta_description' => 'Detailed CMS history of Radha Krishna Secondary School, including origin, approvals, relocation, service area, community contributions, and academic growth.',
                'meta_description_ne' => 'राधाकृष्ण माध्यमिक विद्यालयको सुरुवात, स्वीकृति, स्थानान्तरण, सेवा क्षेत्र, सामुदायिक योगदान र शैक्षिक विकास समेटिएको विस्तृत CMS इतिहास।',
                'meta_keywords' => 'Radha Krishna Secondary School history, Gopghat Doti, Latamandau school',
                'meta_keywords_ne' => 'राधाकृष्ण माध्यमिक विद्यालय इतिहास, गोपघाट डोटी, लाटामाण्डौँ विद्यालय',
                'sort_order' => 20,
                'published_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        // This migration enriches editable CMS content. Keep the page intact on rollback.
    }

    private function blocksEn(): array
    {
        return [
            $this->hero(
                'School History',
                'From a community pathshala to a public secondary school',
                'Radha Krishna Secondary School grew through local leadership, public participation, donated land, teacher commitment, and the educational needs of families around Gopghat, Latamandau, Barpata, and nearby communities.',
                'The history page preserves only the details recorded in the school history document and verified school facts.',
                'Key Milestones',
                'Documented History'
            ),
            $this->stats([
                ['label' => 'Formal learning began', 'value' => '2009 B.S.'],
                ['label' => 'Present site', 'value' => 'Gopghat'],
                ['label' => 'Secondary level', 'value' => '2050 B.S.'],
                ['label' => '+2 expansion', 'value' => '2065 B.S.'],
            ]),
            $this->textRow(
                'Early Beginning',
                'Formal education in the area began in B.S. 2009 as a small community pathshala. Local people started it to bring learning closer to children who had limited access to school education.',
                'In B.S. 2012, the school received temporary affiliation from the Forest Division Office and operated from Tilatali Pipalbot. This early phase created the foundation for a community-owned public school.'
            ),
            $this->tableRow('Institutional Timeline', [
                ['Year (B.S.)', 'Development', 'Recorded detail'],
                ['2009', 'Community pathshala', 'Formal learning began through local effort.'],
                ['2012', 'Temporary affiliation', 'Temporary affiliation was received from the Forest Division Office.'],
                ['2032', 'Primary approval', 'Classes 1 to 3 received permanent primary approval.'],
                ['2036', 'Shift to Gopghat', 'The school moved to its present Gopghat area for lower-secondary growth.'],
                ['2050', 'Secondary level', 'Permission was received for classes 9 and 10.'],
                ['2065', '+2 Education', 'Higher-secondary education started in the Education stream.'],
                ['2067', '+2 Commerce', 'The Commerce stream was added.'],
            ], 'key-milestones'),
            $this->textRow(
                'Move to the Present Location',
                'The old site did not have enough land for lower-secondary development. In B.S. 2036, the school shifted to the present area at Gopghat, Latamandau-8.',
                'The document records Motisingh Balayar as an important land donor for the present school location. Local residents, school leaders, teachers, students, guardians, and social workers then supported the school through labour, materials, leadership, and continuous public effort.'
            ),
            $this->cards('Community Support', 'The school’s growth was not the work of one person or one office. It came from many forms of community contribution recorded in the school history.', [
                ['icon' => '🏫', 'title' => 'Public Leadership', 'text' => 'Local leaders and school committees worked for affiliation, approvals, teachers, buildings, and the gradual upgrade of class levels.'],
                ['icon' => '🪵', 'title' => 'Labour and Materials', 'text' => 'The community contributed timber, furniture, stone transport, building labour, playground work, and other practical support.'],
                ['icon' => '🎓', 'title' => 'Teachers and Students', 'text' => 'Teachers and students supported both academic progress and physical development, including classroom and school environment work.'],
            ]),
            $this->textRow(
                'Secondary and Higher-Secondary Growth',
                'In B.S. 2050, the school received permission to operate classes 9 and 10. This marked a major step in making secondary education available locally.',
                'In B.S. 2065, +2 education began in the Education stream. In B.S. 2067, the Commerce stream was added. Local bodies, the community, political leadership, school hostel income, and institutional effort supported this expansion.'
            ),
            $this->textRow(
                'Location and Service Area',
                'The school is located in Doti district at Gopghat, above the Latamandau market area, near the Seti River. The document places it south of the D.K.I. Singh Highway in a hilly area at about 665 meters above sea level.',
                'Its service area includes Latamandau wards 8 and 9 and Barpata wards 1 to 7. Students have also come from nearby settlements and districts because of the school’s academic reputation and public service role.'
            ),
            $this->tableRow('Recorded School Service Area', [
                ['Area', 'Wards / places', 'Notes'],
                ['Latamandau', 'Wards 8 and 9', 'Core service area around Gopghat.'],
                ['Barpata', 'Wards 1 to 7', 'Regular surrounding service area.'],
                ['Nearby communities', 'Neighbouring settlements and districts', 'Students came because of school reputation and results.'],
            ]),
            $this->tableRow('Major Land Donors Recorded in the Document', [
                ['S.N.', 'Name'],
                ['1', 'Late Motisingh Balayar'],
                ['2', 'Late Tek Bahadur Balayar'],
                ['3', 'Late Kashi Balayar'],
                ['4', 'Late Hark Bahadur Balayar'],
                ['5', 'Bhim Bahadur Balayar'],
                ['6', 'Late Jhagadi Balayar'],
                ['7', 'Yogendra Balayar'],
                ['8', 'Late Wam Balayar'],
                ['9', 'Prasad Balayar'],
                ['10', 'Bishnu Devi Balayar'],
                ['11', 'Gorakh Bahadur Balayar'],
            ]),
            $this->textRow(
                'Recognition',
                'The school history records that after B.S. 2050 the school made visible progress in both academic and physical development. Its SLC results became one of the school’s strong public identities.',
                'For the B.S. 2057 SLC result, the school received an education award flag and a cash award of Rs. 50,000. This recognition reflected the effort of students, teachers, leadership, guardians, and the wider community.'
            ),
            $this->cta(
                'A Community History',
                'The school’s history is a record of local faith in education: donated land, shared labour, committee leadership, teacher commitment, student effort, and public responsibility.',
                'Contact School',
                '/contact',
                'Radha Krishna Secondary School'
            ),
        ];
    }

    private function blocksNe(): array
    {
        return [
            $this->hero(
                'विद्यालय इतिहास',
                'सामुदायिक पाठशालाबाट सार्वजनिक माध्यमिक विद्यालयसम्म',
                'राधाकृष्ण माध्यमिक विद्यालय स्थानीय नेतृत्व, जनसहभागिता, जग्गा दान, शिक्षकको प्रतिबद्धता र गोपघाट, लाटामाण्डौँ, बारपाटा तथा वरपरका समुदायको शैक्षिक आवश्यकताबाट विकसित भएको विद्यालय हो।',
                'यो इतिहास पृष्ठ विद्यालय इतिहास दस्तावेज र प्रमाणित विद्यालय तथ्यमा आधारित छ।',
                'मुख्य माइलस्टोन',
                'दस्तावेजमा आधारित इतिहास'
            ),
            $this->stats([
                ['label' => 'औपचारिक शिक्षाको सुरुवात', 'value' => 'वि.सं. २००९'],
                ['label' => 'हालको स्थान', 'value' => 'गोपघाट'],
                ['label' => 'माध्यमिक तह', 'value' => 'वि.सं. २०५०'],
                ['label' => '+२ विस्तार', 'value' => 'वि.सं. २०६५'],
            ]),
            $this->textRow(
                'प्रारम्भिक सुरुवात',
                'यस क्षेत्रमा औपचारिक शिक्षाको सुरुवात वि.सं. २००९ मा सानो सामुदायिक पाठशालाबाट भयो। विद्यालय शिक्षा टाढा रहेका बालबालिकासम्म सिकाइ पुर्‍याउने उद्देश्यले स्थानीय मानिसहरूले यसको सुरुवात गरेका थिए।',
                'वि.सं. २०१२ मा वन डिभिजन कार्यालयबाट अस्थायी सम्बन्धन प्राप्त भएपछि विद्यालय तिलताली पिपलबोटमा सञ्चालन भयो। यही प्रारम्भिक चरणले समुदायको स्वामित्वमा रहेको सार्वजनिक विद्यालयको आधार तयार गर्‍यो।'
            ),
            $this->tableRow('संस्थागत समयरेखा', [
                ['वर्ष (वि.सं.)', 'विकास', 'दस्तावेजमा उल्लेखित विवरण'],
                ['२००९', 'सामुदायिक पाठशाला', 'स्थानीय प्रयासबाट औपचारिक सिकाइ सुरु भयो।'],
                ['२०१२', 'अस्थायी सम्बन्धन', 'वन डिभिजन कार्यालयबाट अस्थायी सम्बन्धन प्राप्त भयो।'],
                ['२०३२', 'प्राथमिक स्वीकृति', 'कक्षा १ देखि ३ सम्म स्थायी प्राथमिक स्वीकृति प्राप्त भयो।'],
                ['२०३६', 'गोपघाटमा स्थानान्तरण', 'निम्न माध्यमिक विकासका लागि विद्यालय हालको गोपघाट क्षेत्रमा सारियो।'],
                ['२०५०', 'माध्यमिक तह', 'कक्षा ९ र १० सञ्चालन अनुमति प्राप्त भयो।'],
                ['२०६५', '+२ शिक्षाशास्त्र', 'उच्च माध्यमिक तहमा शिक्षाशास्त्र संकाय सुरु भयो।'],
                ['२०६७', '+२ वाणिज्य', 'वाणिज्य संकाय थप भयो।'],
            ], 'key-milestones'),
            $this->textRow(
                'हालको स्थानमा स्थानान्तरण',
                'पुरानो स्थानमा निम्न माध्यमिक तह विस्तारका लागि पर्याप्त जग्गा थिएन। त्यसैले वि.सं. २०३६ मा विद्यालय लाटामाण्डौँ-८, गोपघाटस्थित हालको क्षेत्रमा सारियो।',
                'दस्तावेजले हालको विद्यालय स्थानका लागि मोती सिंह बलायरको जग्गा दानलाई महत्वपूर्ण योगदानका रूपमा उल्लेख गर्छ। त्यसपछि स्थानीय बासिन्दा, विद्यालय नेतृत्व, शिक्षक, विद्यार्थी, अभिभावक र समाजसेवीहरूले श्रम, सामग्री, नेतृत्व र निरन्तर सार्वजनिक प्रयासबाट विद्यालयलाई अघि बढाए।'
            ),
            $this->cards('समुदायको सहयोग', 'विद्यालयको विकास कुनै एक व्यक्ति वा कार्यालयको मात्र काम थिएन। विद्यालय इतिहासमा उल्लेख भएका धेरै प्रकारका सामुदायिक योगदानले यो संस्था उभिएको हो।', [
                ['icon' => '🏫', 'title' => 'सार्वजनिक नेतृत्व', 'text' => 'स्थानीय अगुवा र विद्यालय समितिहरू सम्बन्धन, स्वीकृति, शिक्षक, भवन र तह वृद्धिका लागि सक्रिय रहे।'],
                ['icon' => '🪵', 'title' => 'श्रम र सामग्री', 'text' => 'समुदायले काठपात, फर्निचर, ढुंगा ढुवानी, भवन निर्माण श्रम, खेलमैदान निर्माण र अन्य व्यावहारिक सहयोग गर्‍यो।'],
                ['icon' => '🎓', 'title' => 'शिक्षक र विद्यार्थी', 'text' => 'शिक्षक र विद्यार्थीहरूले शैक्षिक प्रगति सँगै कक्षा कोठा, विद्यालय वातावरण र भौतिक विकासमा योगदान गरे।'],
            ]),
            $this->textRow(
                'माध्यमिक र उच्च माध्यमिक विकास',
                'वि.सं. २०५० मा विद्यालयले कक्षा ९ र १० सञ्चालन अनुमति प्राप्त गर्‍यो। यसले स्थानीयस्तरमा माध्यमिक शिक्षा उपलब्ध गराउने महत्वपूर्ण चरण पूरा गर्‍यो।',
                'वि.सं. २०६५ मा +२ शिक्षाशास्त्र संकाय सुरु भयो। वि.सं. २०६७ मा वाणिज्य संकाय थप भयो। स्थानीय निकाय, समुदाय, राजनीतिक नेतृत्व, छात्रावासबाट आउने स्रोत र संस्थागत प्रयासले यो विस्तार सम्भव बनायो।'
            ),
            $this->textRow(
                'स्थान र सेवा क्षेत्र',
                'विद्यालय डोटी जिल्लाको लाटामाण्डौँ बजारमाथि गोपघाट क्षेत्रमा, सेती नदी नजिक अवस्थित छ। दस्तावेजले यसलाई डि.के.आई. सिंह राजमार्गबाट दक्षिणतर्फको पहाडी क्षेत्रमा, समुद्री सतहबाट करिब ६६५ मिटर उचाइमा रहेको बताउँछ।',
                'विद्यालयको सेवा क्षेत्र लाटामाण्डौँ वडा ८ र ९ तथा बारपाटा वडा १ देखि ७ सम्म फैलिएको छ। विद्यालयको शैक्षिक पहिचान र सार्वजनिक सेवा भूमिकाका कारण नजिकका बस्ती तथा छिमेकी क्षेत्रबाट पनि विद्यार्थी आउने गरेका छन्।'
            ),
            $this->tableRow('दस्तावेजमा उल्लेखित सेवा क्षेत्र', [
                ['क्षेत्र', 'वडा / स्थान', 'टिप्पणी'],
                ['लाटामाण्डौँ', 'वडा ८ र ९', 'गोपघाट वरपरको मुख्य सेवा क्षेत्र।'],
                ['बारपाटा', 'वडा १ देखि ७', 'नियमित वरपरको सेवा क्षेत्र।'],
                ['नजिकका समुदाय', 'छिमेकी बस्ती र जिल्ला', 'विद्यालयको पहिचान र नतिजाका कारण विद्यार्थी आउने क्षेत्र।'],
            ]),
            $this->tableRow('दस्तावेजमा उल्लेखित प्रमुख जग्गादाता', [
                ['क्र.सं.', 'नाम'],
                ['१', 'स्व. मोती सिंह बलायर'],
                ['२', 'स्व. टेक बहादुर बलायर'],
                ['३', 'स्व. काशी बलायर'],
                ['४', 'स्व. हर्क बहादुर बलायर'],
                ['५', 'भीम बहादुर बलायर'],
                ['६', 'स्व. झगडी बलायर'],
                ['७', 'योगेन्द्र बलायर'],
                ['८', 'स्व. वाम बलायर'],
                ['९', 'प्रसाद बलायर'],
                ['१०', 'विष्णु देवी बलायर'],
                ['११', 'गोरख बहादुर बलायर'],
            ]),
            $this->textRow(
                'पहिचान र उपलब्धि',
                'विद्यालय इतिहासले वि.सं. २०५० पछि विद्यालयले शैक्षिक तथा भौतिक क्षेत्रमा देखिने प्रगति गरेको उल्लेख गर्छ। SLC नतिजा विद्यालयको बलियो सार्वजनिक पहिचानमध्ये एक बन्यो।',
                'वि.सं. २०५७ को SLC नतिजाका आधारमा विद्यालयले शिक्षा पुरस्कार झण्डा र रु. ५०,००० नगद पुरस्कार प्राप्त गर्‍यो। यो उपलब्धि विद्यार्थी, शिक्षक, नेतृत्व, अभिभावक र समुदायको साझा प्रयासको प्रतिफल थियो।'
            ),
            $this->cta(
                'समुदायको इतिहास',
                'विद्यालयको इतिहास शिक्षाप्रतिको स्थानीय विश्वासको दस्तावेज हो: दान गरिएको जग्गा, साझा श्रम, समितिको नेतृत्व, शिक्षकको प्रतिबद्धता, विद्यार्थीको मेहनत र सार्वजनिक जिम्मेवारी।',
                'विद्यालयमा सम्पर्क गर्नुहोस्',
                '/contact',
                'राधाकृष्ण माध्यमिक विद्यालय'
            ),
        ];
    }

    private function hero(string $eyebrow, string $title, string $description, string $note, string $primaryLabel, string $cardTitle): array
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
            ],
            'columns' => [
                ['blocks' => []],
                ['blocks' => [[
                    'type' => 'feature_card',
                    'data' => ['icon' => '📜', 'title' => $cardTitle, 'text' => $note, 'align' => 'left'],
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

    private function tableRow(string $title, array $rows, ?string $id = null): array
    {
        return [
            'type' => 'row',
            'data' => ['section' => 'normal', 'width' => 'wide', 'gap' => 'normal', 'columns' => 1, 'title' => $title],
            'columns' => [[
                'blocks' => [
                    ['type' => 'heading', 'data' => ['text' => $title, 'level' => '2', 'align' => 'left']],
                    ['type' => 'html', 'data' => ['html' => '<div'.($id ? ' id="'.$id.'"' : '').'></div>']],
                    ['type' => 'table', 'data' => [
                        'rows' => json_encode([
                            'headerRow' => true,
                            'border' => true,
                            'padding' => 'md',
                            'cells' => array_map(fn ($row) => array_map(fn ($cell) => ['text' => $cell], $row), $rows),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]],
                ],
            ]],
        ];
    }

    private function cta(string $title, string $description, string $label, string $url, string $eyebrow): array
    {
        return [
            'type' => 'row',
            'data' => [
                'section' => 'cta',
                'width' => 'wide',
                'gap' => 'normal',
                'columns' => 1,
                'eyebrow' => $eyebrow,
                'title' => $title,
                'description' => $description,
                'primary_label' => $label,
                'primary_url' => $url,
            ],
            'columns' => [['blocks' => []]],
        ];
    }
};
