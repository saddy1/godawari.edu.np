<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_pages')) {
            return;
        }

        $legacySlugs = [
            'elementary-cms-demo',
            'academics-elementary',
            'academics-primary',
            'academics-secondary',
            'history-of-radha-krishna-mavi',
        ];

        if (Schema::hasTable('cms_menu_items')) {
            DB::table('cms_menu_items')
                ->whereIn('cms_page_id', DB::table('cms_pages')->whereIn('slug', $legacySlugs)->select('id'))
                ->update(['cms_page_id' => null, 'updated_at' => now()]);

            DB::table('cms_menu_items')
                ->where('url', '/pages/history-of-radha-krishna-mavi')
                ->delete();
        }

        DB::table('cms_pages')->whereIn('slug', $legacySlugs)->delete();

        foreach ($this->pages() as $page) {
            DB::table('cms_pages')->updateOrInsert(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'title_ne' => $page['title_ne'],
                    'status' => 'published',
                    'template' => 'wide',
                    'content_blocks' => json_encode($page['blocks'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'content_blocks_ne' => json_encode($page['blocks_ne'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'content_html' => null,
                    'featured_image' => $page['featured_image'],
                    'meta_title' => $page['meta_title'],
                    'meta_title_ne' => $page['meta_title_ne'],
                    'meta_description' => $page['meta_description'],
                    'meta_description_ne' => $page['meta_description_ne'],
                    'meta_keywords' => $page['meta_keywords'],
                    'meta_keywords_ne' => $page['meta_keywords_ne'],
                    'sort_order' => $page['sort_order'],
                    'published_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'about_page_slug'],
            ['value' => 'about-godawari-college', 'created_at' => now(), 'updated_at' => now()]
        );

        $this->updateMenu();
        Cache::forget('site_settings');
    }

    public function down(): void
    {
        if (Schema::hasTable('cms_pages')) {
            DB::table('cms_pages')->whereIn('slug', ['about-godawari-college', 'bsc-csit', 'bbs'])->delete();
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'about_page_slug')->delete();
        }

        Cache::forget('site_settings');
    }

    private function updateMenu(): void
    {
        if (! Schema::hasTable('cms_menu_items')) {
            return;
        }

        $programs = [
            '/academics/elementary' => ['slug' => 'bsc-csit', 'label' => 'B.Sc. CSIT', 'label_ne' => 'B.Sc. CSIT', 'subtitle' => 'Computer Science & IT', 'subtitle_ne' => 'कम्प्युटर विज्ञान तथा सूचना प्रविधि'],
            '/academics/primary' => ['slug' => 'bbs', 'label' => 'BBS', 'label_ne' => 'BBS', 'subtitle' => 'Business Studies', 'subtitle_ne' => 'व्यवसाय अध्ययन'],
        ];

        foreach ($programs as $oldUrl => $program) {
            $pageId = DB::table('cms_pages')->where('slug', $program['slug'])->value('id');
            $values = [
                'cms_page_id' => $pageId,
                'type' => 'page',
                'url' => null,
                'label' => $program['label'],
                'is_active' => true,
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('cms_menu_items', 'label_ne')) {
                $values['label_ne'] = $program['label_ne'];
            }
            if (Schema::hasColumn('cms_menu_items', 'subtitle')) {
                $values['subtitle'] = $program['subtitle'];
            }
            if (Schema::hasColumn('cms_menu_items', 'subtitle_ne')) {
                $values['subtitle_ne'] = $program['subtitle_ne'];
            }

            DB::table('cms_menu_items')->where('url', $oldUrl)->update($values);
        }

        DB::table('cms_menu_items')->where('url', '/academics/secondary')->delete();
    }

    private function pages(): array
    {
        return [
            $this->aboutPage(),
            $this->bscCsitPage(),
            $this->bbsPage(),
        ];
    }

    private function aboutPage(): array
    {
        return [
            'slug' => 'about-godawari-college',
            'title' => 'Explore Godawari College',
            'title_ne' => 'गोदावरी कलेज चिनौँ',
            'meta_title' => 'Explore Godawari College | B.Sc. CSIT and BBS',
            'meta_title_ne' => 'गोदावरी कलेज चिनौँ | B.Sc. CSIT र BBS',
            'meta_description' => 'Explore Godawari College in Itahari, its Tribhuvan University affiliation, student-focused learning environment, B.Sc. CSIT program with 36 seats, and BBS program.',
            'meta_description_ne' => 'इटहरीको गोदावरी कलेज, त्रिभुवन विश्वविद्यालय सम्बन्धन, विद्यार्थी केन्द्रित सिकाइ, ३६ सिटको B.Sc. CSIT र BBS कार्यक्रमबारे जान्नुहोस्।',
            'meta_keywords' => 'Godawari College, BSc CSIT Itahari, BBS Itahari, Tribhuvan University college',
            'meta_keywords_ne' => 'गोदावरी कलेज, BSc CSIT इटहरी, BBS इटहरी',
            'featured_image' => 'uploads/site/godawari-campus.jpg',
            'sort_order' => 1,
            'blocks' => $this->aboutBlocks(false),
            'blocks_ne' => $this->aboutBlocks(true),
        ];
    }

    private function aboutBlocks(bool $ne): array
    {
        $t = $ne ? [
            'eyebrow' => 'गोदावरी कलेज · इटहरी',
            'title' => 'सिक्न, बढ्न र भविष्य बनाउन केन्द्रित शैक्षिक थलो',
            'description' => 'अनुशासित अध्ययन, व्यवहारिक सिकाइ, सहयोगी शिक्षक र त्रिभुवन विश्वविद्यालयबाट सम्बन्धन प्राप्त दुई करियर केन्द्रित स्नातक कार्यक्रमसहितको कलेज अनुभव।',
            'primary' => 'हाम्रा कार्यक्रम हेर्नुहोस्',
            'secondary' => 'भर्ना सोधपुछ',
            'focus_title' => 'हाम्रो उद्देश्य',
            'focus_text' => 'ज्ञानसँगै आत्मविश्वास, सीप र जिम्मेवारी विकास।',
            'facts' => [['स्थापना', '१९९२ ई.'], ['सम्बन्धन', 'त्रिभुवन विश्वविद्यालय'], ['स्नातक कार्यक्रम', '२'], ['B.Sc. CSIT', '३६ सिट']],
            'story_title' => 'स्पष्ट शैक्षिक उद्देश्य भएको स्थापित कलेज',
            'story_text' => "गोदावरी कलेज सन् १९९२ (वि.सं. २०४९) मा इटहरी, सुनसरीमा स्थापना भएको हो। कलेजले बलियो शैक्षिक आधार र व्यावसायिक जीवनका लागि व्यवहारिक तयारी चाहने विद्यार्थीलाई स्नातक शिक्षा प्रदान गर्छ।\n\nहाम्रो दृष्टिकोण सरल छ: स्पष्ट अध्यापन, सिद्धान्तलाई व्यवहारसँग जोड्ने सिकाइ र निरन्तर विद्यार्थी सहयोग।",
            'story_button' => 'भर्ना विवरण हेर्नुहोस्',
            'experience_eyebrow' => 'कलेज अनुभव',
            'experience_title' => 'कक्षाभन्दा बाहिरसम्म फैलिएको सिकाइ',
            'experience_description' => 'व्याख्यान, छलफल, प्रयोगशाला, परियोजना, प्रस्तुति र अनुभवी शिक्षकको मार्गदर्शनबाट सिकाइ।',
            'experiences' => [['🧪', 'व्यवहारिक सिकाइ', 'परियोजना, प्रयोगशाला र वास्तविक उदाहरणले अवधारणालाई व्यवहारमा उतार्न सहयोग गर्छ।'], ['🎓', 'सहयोगी शिक्षक', 'स्पष्ट अध्यापन, पृष्ठपोषण, परामर्श र शैक्षिक सहयोग।'], ['🚀', 'व्यावसायिक विकास', 'सञ्चार, सहकार्य, नेतृत्व, जिम्मेवारी र करियर चेतना विकास।']],
            'programs_eyebrow' => 'हाम्रा कार्यक्रम',
            'programs_title' => 'स्नातक यात्राका लागि दुई केन्द्रित बाटा',
            'programs_description' => 'दुवै कार्यक्रम त्रिभुवन विश्वविद्यालयबाट सम्बन्धन प्राप्त छन्।',
            'bsc_text' => 'कम्प्युटर विज्ञानको सिद्धान्त र सूचना प्रविधिको व्यवहारिक प्रयोग जोड्ने चार वर्षे, आठ सेमेस्टरको १२६ क्रेडिट कार्यक्रम। प्रोग्रामिङ, प्रणाली, नेटवर्क, डेटाबेस, सफ्टवेयर इन्जिनियरिङ, परियोजना र इन्टर्नसिप समेटिन्छ।',
            'bbs_text' => 'व्यवस्थापन र व्यवहारिक व्यवसाय सीप विकास गर्ने चार वर्षे, आठ सेमेस्टरको १२० क्रेडिट कार्यक्रम। लेखा, वित्त, अर्थशास्त्र, बजारीकरण, अनुसन्धान र उद्यमशीलता समेटिन्छ।',
            'view_program' => 'विस्तृत कार्यक्रम हेर्नुहोस्',
            'leadership_title' => 'क्याम्पस प्रमुखको सन्देश',
            'leadership_text' => 'हाम्रो कलेज आवश्यक सीप विकास, विद्यार्थी प्रतिभा पहिचान र नवीन सोच प्रवर्द्धन गर्न प्रतिबद्ध छ। हामी राष्ट्रको विकासमा योगदान दिन सक्ने गतिशील र जिम्मेवार नागरिक निर्माण गर्न चाहन्छौँ।',
            'cta_eyebrow' => 'अर्को कदम',
            'cta_title' => 'आफ्नो भविष्यसँग मिल्ने कार्यक्रम रोज्नुहोस्',
            'cta_description' => 'B.Sc. CSIT, BBS, योग्यता, कागजात र आवेदन प्रक्रियाबारे हाम्रो भर्ना टोलीसँग कुरा गर्नुहोस्।',
            'cta_primary' => 'भर्ना सोधपुछ',
            'cta_secondary' => 'कलेजमा सम्पर्क गर्नुहोस्',
        ] : [
            'eyebrow' => 'Godawari College · Itahari',
            'title' => 'A focused place to learn, grow, and build your future',
            'description' => 'Explore disciplined academics, practical learning, supportive faculty, and two career-focused bachelor programs affiliated with Tribhuvan University.',
            'primary' => 'Explore Our Programs',
            'secondary' => 'Admission Inquiry',
            'focus_title' => 'Our Purpose',
            'focus_text' => 'Building confidence, skills, and responsibility alongside knowledge.',
            'facts' => [['Established', '1992 AD'], ['Affiliation', 'Tribhuvan University'], ['Bachelor Programs', '2'], ['B.Sc. CSIT', '36 Seats']],
            'story_title' => 'An established college with a clear academic purpose',
            'story_text' => "Godawari College was established in 1992 AD (2049 BS) in Itahari, Sunsari. The college offers undergraduate education for students who want strong academic foundations and practical preparation for professional life.\n\nOur approach is simple: teach with clarity, connect theory with practice, and support students consistently.",
            'story_button' => 'View Admission Details',
            'experience_eyebrow' => 'College Experience',
            'experience_title' => 'Learning that goes beyond the classroom',
            'experience_description' => 'Learning through lectures, discussion, laboratory work, projects, presentations, and guidance from experienced faculty.',
            'experiences' => [['🧪', 'Practical Learning', 'Projects, laboratory work, and real-world examples help students apply academic concepts.'], ['🎓', 'Supportive Faculty', 'Clear instruction, feedback, mentorship, and academic support throughout the program.'], ['🚀', 'Professional Growth', 'Communication, teamwork, leadership, responsibility, and career awareness.']],
            'programs_eyebrow' => 'Our Programs',
            'programs_title' => 'Two focused paths for your bachelor journey',
            'programs_description' => 'Both programs are affiliated with Tribhuvan University.',
            'bsc_text' => 'A four-year, eight-semester, 126-credit program combining computer science theory with information technology practice. It covers programming, systems, networks, databases, software engineering, projects, and internship.',
            'bbs_text' => 'A four-year, eight-semester, 120-credit program building management and practical business skills through accounting, finance, economics, marketing, research, and entrepreneurship.',
            'view_program' => 'Explore Full Program',
            'leadership_title' => 'Message from the Campus Chief',
            'leadership_text' => 'Our college is committed to fostering essential skills, identifying student talent, and stimulating innovative thinking. We aim to shape dynamic and responsible citizens who can contribute to the growth of our nation.',
            'cta_eyebrow' => 'Your Next Step',
            'cta_title' => 'Find the program that matches your future',
            'cta_description' => 'Talk with our admission team about B.Sc. CSIT, BBS, eligibility, documents, and the application process.',
            'cta_primary' => 'Admission Inquiry',
            'cta_secondary' => 'Contact the College',
        ];

        return [
            $this->row('hero', 2, [[
                $this->feature($t['focus_title'], $t['focus_text'], '🎓'),
            ], []], [
                'width' => 'wide', 'palette' => 'image', 'pattern' => 'grid',
                'background_image' => 'uploads/site/godawari-campus.jpg', 'image' => 'uploads/site/godawari-campus.jpg',
                'eyebrow' => $t['eyebrow'], 'badge' => $t['eyebrow'], 'title' => $t['title'], 'description' => $t['description'],
                'primary_label' => $t['primary'], 'primary_url' => '#programs', 'secondary_label' => $t['secondary'], 'secondary_url' => '/admissions',
            ]),
            $this->row('stats', 4, array_map(fn ($fact) => [$this->stat($fact[0], $fact[1])], $t['facts']), ['width' => 'wide', 'pattern' => 'none']),
            $this->row('normal', 2, [
                [$this->image('/uploads/site/godawari-csit.jpg', $t['story_title'])],
                [$this->heading($t['story_title']), $this->paragraph($t['story_text']), $this->button($t['story_button'], '/admissions')],
            ], ['width' => 'wide', 'gap' => 'large', 'pattern' => 'none']),
            $this->row('cards', 3, array_map(fn ($item) => [$this->feature($item[1], $item[2], $item[0])], $t['experiences']), [
                'width' => 'wide', 'palette' => 'light', 'pattern' => 'dots', 'eyebrow' => $t['experience_eyebrow'], 'title' => $t['experience_title'], 'description' => $t['experience_description'],
            ]),
            $this->row('cards', 2, [
                [$this->image('/uploads/site/godawari-csit.jpg', 'B.Sc. CSIT'), $this->heading('BSc Computer Science and Information Technology'), $this->paragraph($t['bsc_text']), $this->feature('36 Seats · 4 Years', 'Tribhuvan University · 8 Semesters · 126 Credits', '💻'), $this->button($t['view_program'], '/pages/bsc-csit')],
                [$this->image('/uploads/site/godawari-bbs.jpg', 'BBS'), $this->heading('Bachelor of Business Studies'), $this->paragraph($t['bbs_text']), $this->feature('4 Years', 'Tribhuvan University · 8 Semesters · 120 Credits', '📊'), $this->button($t['view_program'], '/pages/bbs')],
            ], [
                'width' => 'wide', 'palette' => 'default', 'pattern' => 'none', 'eyebrow' => $t['programs_eyebrow'], 'title' => $t['programs_title'], 'description' => $t['programs_description'],
            ]),
            $this->row('normal', 2, [
                [$this->image('/media/1784010094_bodh-raj-nepal.png', $t['leadership_title'])],
                [$this->heading($t['leadership_title']), $this->paragraph($t['leadership_text'])],
            ], ['width' => 'wide', 'gap' => 'large', 'palette' => 'green', 'pattern' => 'dots']),
            $this->row('cta', 1, [[]], [
                'width' => 'wide', 'palette' => 'dark', 'pattern' => 'grid', 'eyebrow' => $t['cta_eyebrow'], 'title' => $t['cta_title'], 'description' => $t['cta_description'],
                'primary_label' => $t['cta_primary'], 'primary_url' => '/admissions', 'secondary_label' => $t['cta_secondary'], 'secondary_url' => '/contact',
            ]),
        ];
    }

    private function bscCsitPage(): array
    {
        return $this->programPage([
            'slug' => 'bsc-csit',
            'title' => 'BSc Computer Science and Information Technology',
            'title_ne' => 'BSc Computer Science and Information Technology',
            'short' => 'B.Sc. CSIT',
            'badge' => '36 Seats · 4 Years · 8 Semesters',
            'badge_ne' => '३६ सिट · ४ वर्ष · ८ सेमेस्टर',
            'credits' => '126 Credits',
            'credits_ne' => '१२६ क्रेडिट',
            'description' => 'A Tribhuvan University computing degree combining computer science theory with practical information technology, projects, and internship.',
            'description_ne' => 'कम्प्युटर विज्ञानको सिद्धान्तलाई व्यवहारिक सूचना प्रविधि, परियोजना र इन्टर्नसिपसँग जोड्ने त्रिभुवन विश्वविद्यालयको कार्यक्रम।',
            'image' => 'uploads/site/godawari-csit.jpg',
            'study' => ['Programming and Data Structures', 'Algorithms and Computer Systems', 'Database and Computer Networks', 'Software Engineering, Projects, and Internship'],
            'study_ne' => ['प्रोग्रामिङ र डेटा स्ट्रक्चर', 'एल्गोरिदम र कम्प्युटर प्रणाली', 'डेटाबेस र कम्प्युटर नेटवर्क', 'सफ्टवेयर इन्जिनियरिङ, परियोजना र इन्टर्नसिप'],
            'careers' => ['Software and Web Development', 'Systems and Network Administration', 'Database and Information Systems', 'IT Management, Analysis, and Research'],
            'careers_ne' => ['सफ्टवेयर तथा वेब विकास', 'सिस्टम तथा नेटवर्क प्रशासन', 'डेटाबेस तथा सूचना प्रणाली', 'IT व्यवस्थापन, विश्लेषण र अनुसन्धान'],
            'source' => 'https://edusanjal.com/course/bsc-computer-science-and-information-technology-bsc-csit-tribhuvan-university/',
            'order' => 2,
        ]);
    }

    private function bbsPage(): array
    {
        return $this->programPage([
            'slug' => 'bbs',
            'title' => 'Bachelor of Business Studies',
            'title_ne' => 'Bachelor of Business Studies',
            'short' => 'BBS',
            'badge' => '4 Years · 8 Semesters',
            'badge_ne' => '४ वर्ष · ८ सेमेस्टर',
            'credits' => '120 Credits',
            'credits_ne' => '१२० क्रेडिट',
            'description' => 'A Tribhuvan University business degree building foundations in management and practical skills for organizations, government, finance, and entrepreneurship.',
            'description_ne' => 'संस्था, सरकार, वित्त र उद्यमशीलताका लागि व्यवस्थापनको आधार र व्यवहारिक सीप विकास गर्ने त्रिभुवन विश्वविद्यालयको व्यवसाय अध्ययन कार्यक्रम।',
            'image' => 'uploads/site/godawari-bbs.jpg',
            'study' => ['Management and Organizational Behavior', 'Accounting, Finance, and Economics', 'Marketing and Business Communication', 'Research, Entrepreneurship, and Strategy'],
            'study_ne' => ['व्यवस्थापन र संगठनात्मक व्यवहार', 'लेखा, वित्त र अर्थशास्त्र', 'बजारीकरण र व्यावसायिक सञ्चार', 'अनुसन्धान, उद्यमशीलता र रणनीति'],
            'careers' => ['Banking, Accounting, and Finance', 'Management and Administration', 'Marketing and Business Development', 'Entrepreneurship and Public Service'],
            'careers_ne' => ['बैंकिङ, लेखा र वित्त', 'व्यवस्थापन र प्रशासन', 'बजारीकरण र व्यवसाय विकास', 'उद्यमशीलता र सार्वजनिक सेवा'],
            'order' => 3,
        ]);
    }

    private function programPage(array $program): array
    {
        return [
            'slug' => $program['slug'],
            'title' => $program['title'],
            'title_ne' => $program['title_ne'],
            'meta_title' => $program['short'].' at Godawari College | Tribhuvan University',
            'meta_title_ne' => $program['short'].' · गोदावरी कलेज | त्रिभुवन विश्वविद्यालय',
            'meta_description' => $program['description'],
            'meta_description_ne' => $program['description_ne'],
            'meta_keywords' => $program['short'].', Godawari College, Tribhuvan University, Itahari',
            'meta_keywords_ne' => $program['short'].', गोदावरी कलेज, त्रिभुवन विश्वविद्यालय, इटहरी',
            'featured_image' => $program['image'],
            'sort_order' => $program['order'],
            'blocks' => $this->programBlocks($program, false),
            'blocks_ne' => $this->programBlocks($program, true),
        ];
    }

    private function programBlocks(array $program, bool $ne): array
    {
        $description = $ne ? $program['description_ne'] : $program['description'];
        $badge = $ne ? $program['badge_ne'] : $program['badge'];
        $credits = $ne ? $program['credits_ne'] : $program['credits'];
        $study = $ne ? $program['study_ne'] : $program['study'];
        $careers = $ne ? $program['careers_ne'] : $program['careers'];

        return [
            $this->row('hero', 2, [[$this->feature($credits, 'Tribhuvan University', '🎓')], []], [
                'width' => 'wide', 'palette' => 'image', 'pattern' => 'grid', 'background_image' => $program['image'], 'image' => $program['image'],
                'eyebrow' => $ne ? 'हाम्रो कार्यक्रम' : 'Our Program', 'badge' => $badge, 'title' => $program['title'], 'description' => $description,
                'primary_label' => $ne ? 'भर्ना सोधपुछ' : 'Admission Inquiry', 'primary_url' => '/admissions', 'secondary_label' => $ne ? 'कलेजबारे जान्नुहोस्' : 'Explore the College', 'secondary_url' => '/about',
            ]),
            $this->row('stats', 3, [
                [$this->stat($ne ? 'विश्वविद्यालय' : 'University', 'Tribhuvan University')],
                [$this->stat($ne ? 'अवधि' : 'Duration', $badge)],
                [$this->stat($ne ? 'शैक्षिक भार' : 'Academic Load', $credits)],
            ], ['width' => 'wide', 'pattern' => 'none']),
            $this->row('normal', 2, [
                [$this->image('/'.$program['image'], $program['title'])],
                [$this->heading($ne ? 'कार्यक्रम परिचय' : 'Program Overview'), $this->paragraph($description)],
            ], ['width' => 'wide', 'gap' => 'large', 'pattern' => 'none']),
            $this->row('cards', 2, [
                array_map(fn ($item) => $this->feature($item, $ne ? 'कार्यक्रमको मुख्य अध्ययन क्षेत्र' : 'Core area of study', '📘'), $study),
                array_map(fn ($item) => $this->feature($item, $ne ? 'सम्भावित व्यावसायिक दिशा' : 'Potential professional direction', '🚀'), $careers),
            ], [
                'width' => 'wide', 'palette' => 'light', 'pattern' => 'dots', 'eyebrow' => $ne ? 'विस्तृत जानकारी' : 'Program Details',
                'title' => $ne ? 'अध्ययन क्षेत्र र करियरका दिशा' : 'What you study and where it can lead',
                'description' => $ne ? 'CMS बाट यी विवरणहरू जुनसुकै बेला परिवर्तन गर्न सकिन्छ।' : 'These details can be updated any time from the CMS page editor.',
            ]),
            $this->row('cta', 1, [! empty($program['source']) ? [$this->button($ne ? 'आधिकारिक कार्यक्रम सन्दर्भ' : 'View Program Reference', $program['source'], 'outline')] : []], [
                'width' => 'wide', 'palette' => 'dark', 'pattern' => 'grid', 'eyebrow' => $ne ? 'भर्ना' : 'Admissions',
                'title' => $ne ? 'यो कार्यक्रम अध्ययन गर्न तयार हुनुहुन्छ?' : 'Ready to study this program?',
                'description' => $ne ? 'योग्यता, कागजात र आवेदन प्रक्रियाबारे हाम्रो भर्ना टोलीसँग कुरा गर्नुहोस्।' : 'Talk with our admission team about eligibility, documents, and the application process.',
                'primary_label' => $ne ? 'भर्ना सोधपुछ' : 'Admission Inquiry', 'primary_url' => '/admissions', 'secondary_label' => $ne ? 'सम्पर्क गर्नुहोस्' : 'Contact Us', 'secondary_url' => '/contact',
            ]),
        ];
    }

    private function row(string $section, int $columns, array $content, array $data = []): array
    {
        return [
            'type' => 'row',
            'data' => array_merge(['section' => $section, 'width' => 'normal', 'gap' => 'normal', 'palette' => 'default', 'pattern' => 'none', 'columns' => $columns], $data),
            'columns' => array_map(fn ($blocks) => ['blocks' => $blocks], $content),
        ];
    }

    private function heading(string $text): array
    {
        return ['type' => 'heading', 'data' => ['text' => $text, 'level' => '2', 'align' => 'left']];
    }

    private function paragraph(string $text): array
    {
        return ['type' => 'paragraph', 'data' => ['text' => $text, 'align' => 'left']];
    }

    private function image(string $url, string $alt): array
    {
        return ['type' => 'image', 'data' => ['url' => $url, 'alt' => $alt, 'align' => 'left']];
    }

    private function button(string $label, string $url, string $style = 'primary'): array
    {
        return ['type' => 'button', 'data' => ['label' => $label, 'url' => $url, 'style' => $style, 'align' => 'left']];
    }

    private function stat(string $label, string $value): array
    {
        return ['type' => 'stat', 'data' => ['label' => $label, 'value' => $value, 'align' => 'center']];
    }

    private function feature(string $title, string $text, string $icon): array
    {
        return ['type' => 'feature_card', 'data' => ['icon' => $icon, 'title' => $title, 'text' => $text, 'align' => 'left']];
    }

};
