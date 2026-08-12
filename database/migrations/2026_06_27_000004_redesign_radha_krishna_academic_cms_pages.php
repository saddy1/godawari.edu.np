<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->pages() as $page) {
            DB::table('cms_pages')->where('slug', $page['slug'])->update([
                'title' => $page['title'],
                'title_ne' => $page['title_ne'],
                'content_blocks' => json_encode($this->blocks($page), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'content_blocks_ne' => json_encode($this->blocksNe($page), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'meta_title' => $page['title'].' - Radha Krishna Secondary School',
                'meta_title_ne' => $page['title_ne'].' - राधाकृष्ण माध्यमिक विद्यालय',
                'meta_description' => $page['description'],
                'meta_description_ne' => $page['description_ne'],
                'meta_keywords' => $page['title'].', Radha Krishna Secondary School, academics, '.$page['badge'],
                'meta_keywords_ne' => $page['title_ne'].', राधाकृष्ण माध्यमिक विद्यालय, शैक्षिक, '.$page['badge_ne'],
                'updated_at' => now(),
            ]);
        }

        DB::table('cms_menu_items')->where('url', '/academics/elementary')->update([
            'label' => 'Early Childhood',
            'subtitle' => 'ECD - Grade 3',
            'label_ne' => 'बालविकास',
            'subtitle_ne' => 'बालविकास - कक्षा ३',
            'updated_at' => now(),
        ]);

        DB::table('cms_menu_items')->where('url', '/academics/primary')->update([
            'label' => 'Basic Level',
            'subtitle' => 'Grade 4 - 8',
            'label_ne' => 'आधारभूत तह',
            'subtitle_ne' => 'कक्षा ४ - ८',
            'updated_at' => now(),
        ]);

        DB::table('cms_menu_items')->where('url', '/academics/secondary')->update([
            'label' => 'Secondary Level',
            'subtitle' => 'Grade 9 - 12',
            'label_ne' => 'माध्यमिक तह',
            'subtitle_ne' => 'कक्षा ९ - १२',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // This migration is a final content pass. It intentionally does not restore older demo copy.
    }

    private function pages(): array
    {
        return [
            [
                'slug' => 'academics-elementary',
                'title' => 'Early Childhood and Foundation Level',
                'title_ne' => 'बालविकास तथा आधार निर्माण तह',
                'badge' => 'ECD to Grade 3',
                'badge_ne' => 'बालविकासदेखि कक्षा ३',
                'image' => 'uploads/site/academics-elementary-image.jpeg',
                'description' => 'A careful beginning for children where language, number sense, habits, cleanliness, confidence, and social behaviour are built through child-friendly classroom activities.',
                'description_ne' => 'बालबालिकाको भाषा, अंकज्ञान, बानी, सरसफाइ, आत्मविश्वास र सामाजिक व्यवहार बालमैत्री गतिविधिबाट विकास गरिने प्रारम्भिक तह।',
                'overview' => [
                    'Radha Krishna Secondary School treats the early years as the foundation of the whole school journey. Children are supported with a calm classroom routine, friendly teachers, local examples, stories, songs, games, drawing, picture talk, and repeated practice so that school becomes familiar and joyful.',
                    'The aim is not only to finish a textbook. The focus is to help every child speak clearly, listen carefully, recognise letters and numbers, write with confidence, follow healthy habits, respect classmates, and attend school regularly. These habits prepare learners for the basic level and for a disciplined life in the school community.',
                ],
                'overview_ne' => [
                    'राधाकृष्ण माध्यमिक विद्यालयले प्रारम्भिक तहलाई सम्पूर्ण विद्यालय यात्राको आधार मान्छ। बालबालिकालाई नियमित कक्षा वातावरण, मैत्री शिक्षक, स्थानीय उदाहरण, कथा, गीत, खेल, चित्रकला र दोहोर्‍याइ अभ्यासमार्फत विद्यालयप्रति आत्मीयता र रुचि विकास गराइन्छ।',
                    'यस तहको उद्देश्य पाठ्यपुस्तक पूरा गर्नु मात्र होइन। बालबालिकाले स्पष्ट बोल्न, ध्यान दिएर सुन्न, अक्षर र अंक चिन्न, आत्मविश्वासका साथ लेख्न, स्वास्थ्य बानी अपनाउन, साथीलाई सम्मान गर्न र नियमित विद्यालय आउन सिक्छन्।',
                ],
                'stats' => [
                    ['label' => 'Level', 'value' => 'ECD - Grade 3'],
                    ['label' => 'Method', 'value' => 'Activity Based'],
                    ['label' => 'Priority', 'value' => 'Reading Habit'],
                ],
                'stats_ne' => [
                    ['label' => 'तह', 'value' => 'बालविकास - कक्षा ३'],
                    ['label' => 'विधि', 'value' => 'गतिविधि आधारित'],
                    ['label' => 'प्राथमिकता', 'value' => 'पढाइ बानी'],
                ],
                'features' => [
                    ['icon' => '📖', 'title' => 'Language Readiness', 'text' => 'Daily speaking, listening, reading aloud, picture discussion, handwriting practice, and simple storytelling strengthen Nepali and English foundations.'],
                    ['icon' => '🔢', 'title' => 'Early Numeracy', 'text' => 'Counting, comparison, shapes, patterns, number writing, and simple problem solving are taught through objects, games, and local materials.'],
                    ['icon' => '🌱', 'title' => 'Habits and Care', 'text' => 'Teachers guide children in cleanliness, punctuality, classroom manners, sharing, confidence, and respect for school routines.'],
                ],
                'features_ne' => [
                    ['icon' => '📖', 'title' => 'भाषिक तयारी', 'text' => 'दैनिक बोलाइ, सुनाइ, ठूलो स्वरमा पढाइ, चित्र छलफल, हस्तलेखन अभ्यास र साना कथाबाट नेपाली तथा अंग्रेजीको आधार बलियो बनाइन्छ।'],
                    ['icon' => '🔢', 'title' => 'प्रारम्भिक अंकज्ञान', 'text' => 'गन्ती, तुलना, आकार, ढाँचा, अंक लेखन र सरल समस्या समाधान वस्तु, खेल र स्थानीय सामग्रीबाट सिकाइन्छ।'],
                    ['icon' => '🌱', 'title' => 'बानी र हेरचाह', 'text' => 'सरसफाइ, समयपालन, कक्षा व्यवहार, बाँडचुँड, आत्मविश्वास र विद्यालय अनुशासनमा शिक्षकले निरन्तर मार्गदर्शन गर्छन्।'],
                ],
                'focus' => [
                    ['icon' => '🎨', 'title' => 'Creative Activities', 'text' => 'Drawing, colouring, singing, movement games, recitation, and local stories make learning active and memorable.'],
                    ['icon' => '🤝', 'title' => 'Social Behaviour', 'text' => 'Children learn to work in pairs, ask for help, wait for turns, care for classroom materials, and build friendship.'],
                    ['icon' => '✅', 'title' => 'Regular Attendance', 'text' => 'Attendance habit is strengthened through parent contact, classroom encouragement, and a child-friendly school environment.'],
                ],
                'focus_ne' => [
                    ['icon' => '🎨', 'title' => 'सृजनात्मक गतिविधि', 'text' => 'चित्रकला, रंग भर्ने काम, गीत, खेल, कविता र स्थानीय कथाले सिकाइलाई सक्रिय र सम्झनायोग्य बनाउँछ।'],
                    ['icon' => '🤝', 'title' => 'सामाजिक व्यवहार', 'text' => 'बालबालिकाले जोडीमा काम गर्न, सहयोग माग्न, पालो पर्खन, सामग्री जोगाउन र साथीभाव विकास गर्न सिक्छन्।'],
                    ['icon' => '✅', 'title' => 'नियमित उपस्थिति', 'text' => 'अभिभावक सम्पर्क, कक्षा प्रोत्साहन र बालमैत्री वातावरणबाट नियमित विद्यालय आउने बानी बलियो बनाइन्छ।'],
                ],
                'table' => [
                    ['Area', 'What students develop'],
                    ['Language', 'Listening, speaking, reading readiness, handwriting, vocabulary'],
                    ['Mathematics', 'Counting, number recognition, shapes, comparison, patterns'],
                    ['Life Habits', 'Cleanliness, punctuality, sharing, confidence, discipline'],
                    ['Expression', 'Drawing, songs, stories, games, classroom participation'],
                ],
                'table_ne' => [
                    ['क्षेत्र', 'विद्यार्थीले विकास गर्ने कुरा'],
                    ['भाषा', 'सुनाइ, बोलाइ, पढाइ तयारी, हस्तलेखन, शब्दभण्डार'],
                    ['गणित', 'गन्ती, अंक पहिचान, आकार, तुलना, ढाँचा'],
                    ['जीवन बानी', 'सरसफाइ, समयपालन, बाँडचुँड, आत्मविश्वास, अनुशासन'],
                    ['अभिव्यक्ति', 'चित्र, गीत, कथा, खेल, कक्षा सहभागिता'],
                ],
            ],
            [
                'slug' => 'academics-primary',
                'title' => 'Basic Level Education',
                'title_ne' => 'आधारभूत तह शिक्षा',
                'badge' => 'Grade 4 to Grade 8',
                'badge_ne' => 'कक्षा ४ देखि ८',
                'image' => 'uploads/site/academics-primary-image.jpeg',
                'description' => 'The basic level connects foundational literacy with deeper subject learning, discipline, teamwork, local knowledge, ICT awareness, and preparation for secondary education.',
                'description_ne' => 'आधारभूत तहले साक्षरता, विषयगत बुझाइ, अनुशासन, सहकार्य, स्थानीय ज्ञान, ICT परिचय र माध्यमिक तहको तयारीलाई जोड्छ।',
                'overview' => [
                    'In Grades 4 to 8, students begin to move from simple learning habits toward deeper subject understanding. Teachers use explanation, reading practice, written work, questions, group activities, local examples, library habits, project work, and regular feedback to make learning meaningful.',
                    'The school’s history shows that Radha Krishna Secondary School grew through community effort and public trust. That same spirit is reflected in the basic level: students are encouraged to respect their community, understand local culture and environment, participate in school activities, and build the confidence needed for Grade 9 and beyond.',
                ],
                'overview_ne' => [
                    'कक्षा ४ देखि ८ मा विद्यार्थीहरू सरल सिकाइ बानीबाट गहिरो विषयगत बुझाइतर्फ अघि बढ्छन्। शिक्षकले व्याख्या, पढाइ अभ्यास, लेखाइ, प्रश्नोत्तर, समूहकार्य, स्थानीय उदाहरण, पुस्तकालय बानी, परियोजना कार्य र नियमित पृष्ठपोषणबाट सिकाइलाई अर्थपूर्ण बनाउँछन्।',
                    'विद्यालयको इतिहासले राधाकृष्ण माध्यमिक विद्यालय समुदायको प्रयास र जनविश्वासबाट अघि बढेको देखाउँछ। यही भावना आधारभूत तहमा पनि देखिन्छ: विद्यार्थीलाई समुदायप्रति सम्मान, स्थानीय संस्कृति र वातावरणको बुझाइ, विद्यालय गतिविधिमा सहभागिता र कक्षा ९ का लागि आत्मविश्वास विकास गर्न प्रेरित गरिन्छ।',
                ],
                'stats' => [
                    ['label' => 'Level', 'value' => 'Grade 4 - 8'],
                    ['label' => 'Focus', 'value' => 'Core Subjects'],
                    ['label' => 'Learning', 'value' => 'Practice + Projects'],
                ],
                'stats_ne' => [
                    ['label' => 'तह', 'value' => 'कक्षा ४ - ८'],
                    ['label' => 'केन्द्र', 'value' => 'मुख्य विषयहरू'],
                    ['label' => 'सिकाइ', 'value' => 'अभ्यास + परियोजना'],
                ],
                'features' => [
                    ['icon' => '📚', 'title' => 'Strong Subject Foundation', 'text' => 'Nepali, English, mathematics, science, social studies, health, physical education, and local curriculum are taught with regular practice and assessment.'],
                    ['icon' => '🧠', 'title' => 'Understanding Before Memorising', 'text' => 'Teachers connect lessons with local life, observation, examples, discussion, and written expression so students understand ideas clearly.'],
                    ['icon' => '🧾', 'title' => 'Continuous Feedback', 'text' => 'Classwork, homework, unit tests, attendance, participation, and teacher feedback are used to identify learning gaps early.'],
                ],
                'features_ne' => [
                    ['icon' => '📚', 'title' => 'बलियो विषयगत आधार', 'text' => 'नेपाली, अंग्रेजी, गणित, विज्ञान, सामाजिक, स्वास्थ्य, शारीरिक शिक्षा र स्थानीय पाठ्यक्रम नियमित अभ्यास र मूल्याङ्कनसँगै पढाइन्छ।'],
                    ['icon' => '🧠', 'title' => 'रटाइभन्दा बुझाइ', 'text' => 'शिक्षकले पाठलाई स्थानीय जीवन, अवलोकन, उदाहरण, छलफल र लेखाइ अभिव्यक्तिसँग जोडेर स्पष्ट बुझाइ विकास गर्छन्।'],
                    ['icon' => '🧾', 'title' => 'निरन्तर पृष्ठपोषण', 'text' => 'कक्षाकार्य, गृहकार्य, एकाइ परीक्षा, उपस्थिति, सहभागिता र शिक्षकको पृष्ठपोषणबाट सिकाइ कमजोरी समयमै पहिचान गरिन्छ।'],
                ],
                'focus' => [
                    ['icon' => '🔬', 'title' => 'Science and Practical Thinking', 'text' => 'Students observe, compare, ask questions, use simple materials, and learn to explain natural and social phenomena.'],
                    ['icon' => '💻', 'title' => 'ICT and Library Culture', 'text' => 'Digital awareness, book corners, reading habits, and responsible use of learning resources are gradually strengthened.'],
                    ['icon' => '🏃', 'title' => 'Discipline and Participation', 'text' => 'Sports, sanitation, assembly, classroom leadership, group work, and co-curricular activities build confidence and responsibility.'],
                ],
                'focus_ne' => [
                    ['icon' => '🔬', 'title' => 'विज्ञान र व्यवहारिक सोच', 'text' => 'विद्यार्थीले अवलोकन, तुलना, प्रश्न, सरल सामग्री प्रयोग र प्राकृतिक-सामाजिक घटनाको व्याख्या गर्न सिक्छन्।'],
                    ['icon' => '💻', 'title' => 'ICT र पुस्तकालय संस्कृति', 'text' => 'डिजिटल परिचय, पुस्तक कुना, पढाइ बानी र सिकाइ स्रोतको जिम्मेवार प्रयोग क्रमशः बलियो बनाइन्छ।'],
                    ['icon' => '🏃', 'title' => 'अनुशासन र सहभागिता', 'text' => 'खेलकुद, सरसफाइ, प्रार्थना, कक्षा नेतृत्व, समूहकार्य र सहपाठ्यक्रमले आत्मविश्वास र जिम्मेवारी विकास गर्छ।'],
                ],
                'table' => [
                    ['Subject Area', 'Learning emphasis'],
                    ['Languages', 'Reading comprehension, grammar, handwriting, speaking, creative expression'],
                    ['Mathematics', 'Operations, geometry, measurement, problem solving, logical thinking'],
                    ['Science and Technology', 'Observation, experiments, environment, health, simple technology'],
                    ['Social and Local Studies', 'Community, history, geography, citizenship, local culture'],
                    ['Health and Activities', 'Clean habits, sports, teamwork, discipline, participation'],
                ],
                'table_ne' => [
                    ['विषय क्षेत्र', 'सिकाइ जोड'],
                    ['भाषा', 'पढाइ बुझाइ, व्याकरण, हस्तलेखन, बोलाइ, सृजनात्मक अभिव्यक्ति'],
                    ['गणित', 'गणना, ज्यामिति, मापन, समस्या समाधान, तार्किक सोच'],
                    ['विज्ञान तथा प्रविधि', 'अवलोकन, प्रयोग, वातावरण, स्वास्थ्य, सरल प्रविधि'],
                    ['सामाजिक तथा स्थानीय अध्ययन', 'समुदाय, इतिहास, भूगोल, नागरिकता, स्थानीय संस्कृति'],
                    ['स्वास्थ्य तथा गतिविधि', 'स्वच्छ बानी, खेलकुद, सहकार्य, अनुशासन, सहभागिता'],
                ],
            ],
            [
                'slug' => 'academics-secondary',
                'title' => 'Secondary and Higher Secondary Education',
                'title_ne' => 'माध्यमिक तथा उच्च माध्यमिक शिक्षा',
                'badge' => 'Grade 9 to Grade 12',
                'badge_ne' => 'कक्षा ९ देखि १२',
                'image' => 'uploads/site/academics-secondary-image.jpeg',
                'description' => 'A focused secondary and higher-secondary pathway with Grade 9-10 general education and Grade 11-12 education and management groups.',
                'description_ne' => 'कक्षा ९-१० साधारण माध्यमिक शिक्षा र कक्षा ११-१२ शिक्षा तथा व्यवस्थापन समूहसहितको केन्द्रित माध्यमिक तथा उच्च माध्यमिक यात्रा।',
                'overview' => [
                    'The secondary level is the stage where students prepare for board examinations, higher study, public service, entrepreneurship, teaching, and responsible social life. Radha Krishna Secondary School gives attention to regular attendance, subject mastery, examination readiness, disciplined routines, career guidance, and practical learning activities.',
                    'The school reached an important academic milestone in B.S. 2050 when it received permission to run classes 9 and 10. Later, in B.S. 2065, +2 education started in the Education stream, and in B.S. 2067 the Commerce stream was introduced. The present academic direction therefore focuses on Grade 9-10 general education and Grade 11-12 education and management groups.',
                ],
                'overview_ne' => [
                    'माध्यमिक तह विद्यार्थीले बोर्ड परीक्षा, उच्च अध्ययन, सार्वजनिक सेवा, उद्यमशीलता, शिक्षण र जिम्मेवार सामाजिक जीवनका लागि तयारी गर्ने चरण हो। राधाकृष्ण माध्यमिक विद्यालयले नियमित उपस्थिति, विषयगत दक्षता, परीक्षा तयारी, अनुशासित दिनचर्या, करियर मार्गदर्शन र व्यवहारिक सिकाइ गतिविधिमा ध्यान दिन्छ।',
                    'इतिहास दस्तावेजअनुसार विद्यालयले वि.सं. २०५० मा कक्षा ९ र १० सञ्चालनको अनुमति प्राप्त गर्‍यो। पछि वि.सं. २०६५ मा +2 शिक्षा समूह सुरु भयो र वि.सं. २०६७ मा वाणिज्य/व्यवस्थापन समूह थपियो। त्यसैले हालको शैक्षिक दिशा कक्षा ९-१० साधारण शिक्षा र कक्षा ११-१२ शिक्षा तथा व्यवस्थापन समूहमा केन्द्रित छ।',
                ],
                'stats' => [
                    ['label' => 'Level', 'value' => 'Grade 9 - 12'],
                    ['label' => '+2 Groups', 'value' => 'Education + Management'],
                    ['label' => 'Focus', 'value' => 'Board Preparation'],
                ],
                'stats_ne' => [
                    ['label' => 'तह', 'value' => 'कक्षा ९ - १२'],
                    ['label' => '+2 समूह', 'value' => 'शिक्षा + व्यवस्थापन'],
                    ['label' => 'केन्द्र', 'value' => 'बोर्ड परीक्षा तयारी'],
                ],
                'features' => [
                    ['icon' => '🎓', 'title' => 'Grade 9-10 General Education', 'text' => 'Compulsory and optional subjects are taught with exam preparation, written practice, practical activities, discipline, and teacher mentoring.'],
                    ['icon' => '📘', 'title' => 'Grade 11-12 Education Group', 'text' => 'The Education group supports students interested in teaching, child learning, social development, community service, and education-related higher study.'],
                    ['icon' => '📊', 'title' => 'Grade 11-12 Management Group', 'text' => 'The Management group supports students interested in accounting, economics, business, entrepreneurship, office practice, and commerce-related pathways.'],
                ],
                'features_ne' => [
                    ['icon' => '🎓', 'title' => 'कक्षा ९-१० साधारण शिक्षा', 'text' => 'अनिवार्य तथा ऐच्छिक विषय परीक्षा तयारी, लेखाइ अभ्यास, व्यवहारिक गतिविधि, अनुशासन र शिक्षक परामर्शसँगै पढाइन्छ।'],
                    ['icon' => '📘', 'title' => 'कक्षा ११-१२ शिक्षा समूह', 'text' => 'शिक्षण, बाल सिकाइ, सामाजिक विकास, समुदाय सेवा र शिक्षासम्बन्धी उच्च अध्ययनमा रुचि भएका विद्यार्थीलाई शिक्षा समूहले सहयोग गर्छ।'],
                    ['icon' => '📊', 'title' => 'कक्षा ११-१२ व्यवस्थापन समूह', 'text' => 'लेखा, अर्थशास्त्र, व्यवसाय, उद्यमशीलता, कार्यालय अभ्यास र वाणिज्यसम्बन्धी बाटो रोज्ने विद्यार्थीका लागि व्यवस्थापन समूह उपयोगी छ।'],
                ],
                'focus' => [
                    ['icon' => '📝', 'title' => 'Examination Readiness', 'text' => 'Students are guided through class tests, model questions, writing practice, revision plans, feedback, and subject-wise improvement.'],
                    ['icon' => '🧭', 'title' => 'Career and Stream Guidance', 'text' => 'Teachers help students understand subject choices, higher education routes, local opportunities, and responsible future planning.'],
                    ['icon' => '🤝', 'title' => 'Student Support and Activities', 'text' => 'Learners receive academic guidance while participating in sports, sanitation, assemblies, cultural programmes, and community activities.'],
                ],
                'focus_ne' => [
                    ['icon' => '📝', 'title' => 'परीक्षा तयारी', 'text' => 'कक्षा परीक्षा, नमुना प्रश्न, लेखाइ अभ्यास, पुनरावृत्ति योजना, पृष्ठपोषण र विषयगत सुधारबाट विद्यार्थीलाई मार्गदर्शन गरिन्छ।'],
                    ['icon' => '🧭', 'title' => 'करियर तथा समूह मार्गदर्शन', 'text' => 'शिक्षकले विषय छनोट, उच्च शिक्षाका बाटा, स्थानीय अवसर र जिम्मेवार भविष्य योजनाबारे विद्यार्थीलाई बुझाउँछन्।'],
                    ['icon' => '🤝', 'title' => 'विद्यार्थी सहयोग र गतिविधि', 'text' => 'विद्यार्थीले शैक्षिक मार्गदर्शनसँगै खेलकुद, सरसफाइ, प्रार्थना, सांस्कृतिक कार्यक्रम र समुदाय गतिविधिमा सहभागिता जनाउँछन्।'],
                ],
                'table' => [
                    ['Program', 'Purpose'],
                    ['Grade 9-10 General Education', 'SEE preparation, subject foundation, discipline, practical learning, student mentoring'],
                    ['Grade 11-12 Education Group', 'Teaching pathway, child learning, social development, education studies'],
                    ['Grade 11-12 Management Group', 'Accounting, economics, business, entrepreneurship, commerce pathway'],
                    ['Co-curricular Activities', 'Sports, culture, sanitation, assemblies, leadership, community participation'],
                ],
                'table_ne' => [
                    ['कार्यक्रम', 'उद्देश्य'],
                    ['कक्षा ९-१० साधारण शिक्षा', 'SEE तयारी, विषयगत आधार, अनुशासन, व्यवहारिक सिकाइ, विद्यार्थी परामर्श'],
                    ['कक्षा ११-१२ शिक्षा समूह', 'शिक्षण बाटो, बाल सिकाइ, सामाजिक विकास, शिक्षा अध्ययन'],
                    ['कक्षा ११-१२ व्यवस्थापन समूह', 'लेखा, अर्थशास्त्र, व्यवसाय, उद्यमशीलता, वाणिज्य बाटो'],
                    ['सहपाठ्यक्रम', 'खेलकुद, संस्कृति, सरसफाइ, प्रार्थना, नेतृत्व, समुदाय सहभागिता'],
                ],
            ],
        ];
    }

    private function blocks(array $page): array
    {
        return [
            $this->hero($page, false),
            $this->stats($page['stats']),
            $this->overview($page['overview'], 'Academic Overview', 'Learning built on community trust', $page['image'], $page['badge'], 'Contact School'),
            $this->cards('Program Structure', 'What students experience', $page['features'], 'light', 'Each level is organized around age-appropriate learning, regular teacher guidance, and steady preparation for the next stage of school life.'),
            $this->cards('Classroom Practice', 'How learning is strengthened every day', $page['focus'], 'green', 'These focus areas guide classroom practice, student support, and the daily academic culture of the school.'),
            $this->table('Learning at a glance', $page['table']),
            $this->cta('Plan admission with the school office', 'For class availability, documents, fee details, and subject group guidance, contact the school office before submitting an admission inquiry.', 'Admission Inquiry', '/admissions'),
        ];
    }

    private function blocksNe(array $page): array
    {
        $ne = $page;
        $ne['title'] = $page['title_ne'];
        $ne['badge'] = $page['badge_ne'];
        $ne['description'] = $page['description_ne'];
        $ne['stats'] = $page['stats_ne'];
        $ne['overview'] = $page['overview_ne'];
        $ne['features'] = $page['features_ne'];
        $ne['focus'] = $page['focus_ne'];
        $ne['table'] = $page['table_ne'];

        return [
            $this->hero($ne, true),
            $this->stats($ne['stats']),
            $this->overview($ne['overview'], 'शैक्षिक परिचय', 'समुदायको विश्वासमा आधारित सिकाइ', $ne['image'], $ne['badge'], 'विद्यालय सम्पर्क'),
            $this->cards('कार्यक्रम संरचना', 'विद्यार्थीले अनुभव गर्ने सिकाइ', $ne['features'], 'light', 'हरेक तह उमेरअनुसारको सिकाइ, नियमित शिक्षक मार्गदर्शन र अर्को शैक्षिक चरणको तयारीलाई ध्यानमा राखेर व्यवस्थित गरिएको छ।'),
            $this->cards('कक्षा अभ्यास', 'दैनिक सिकाइ कसरी बलियो बनाइन्छ', $ne['focus'], 'green', 'यी केन्द्रहरूले कक्षा अभ्यास, विद्यार्थी सहयोग र विद्यालयको दैनिक शैक्षिक संस्कृतिलाई दिशा दिन्छन्।'),
            $this->table('सिकाइ एक नजरमा', $ne['table']),
            $this->cta('भर्ना योजना विद्यालय कार्यालयसँग मिलाउनुहोस्', 'कक्षा उपलब्धता, कागजात, शुल्क विवरण र विषय समूह मार्गदर्शनका लागि भर्ना फारम भर्नुअघि विद्यालय कार्यालयमा सम्पर्क गर्नुहोस्।', 'भर्ना सोधपुछ', '/admissions', 'सम्पर्क', 'भर्ना'),
        ];
    }

    private function hero(array $page, bool $ne): array
    {
        return [
            'type' => 'row',
            'data' => [
                'section' => 'hero',
                'width' => 'wide',
                'gap' => 'large',
                'columns' => 2,
                'palette' => 'dark',
                'pattern' => 'grid',
                'eyebrow' => $ne ? 'शैक्षिक' : 'Academics',
                'badge' => $page['badge'],
                'title' => $page['title'],
                'description' => $page['description'],
                'image' => $page['image'],
                'primary_label' => $ne ? 'भर्ना सोधपुछ' : 'Admission Inquiry',
                'primary_url' => '/admissions',
                'secondary_label' => $ne ? 'सम्पर्क' : 'Contact Us',
                'secondary_url' => '/contact',
            ],
            'columns' => [
                ['blocks' => []],
                ['blocks' => [[
                    'type' => 'feature_card',
                    'data' => [
                        'icon' => '🎯',
                        'title' => $ne ? 'मुख्य सिकाइ केन्द्र' : 'Main Learning Focus',
                        'text' => $page['description'],
                        'align' => 'left',
                    ],
                ]]],
            ],
        ];
    }

    private function stats(array $stats): array
    {
        return [
            'type' => 'row',
            'data' => ['section' => 'stats', 'width' => 'wide', 'gap' => 'compact', 'columns' => count($stats)],
            'columns' => array_map(fn ($stat) => ['blocks' => [[
                'type' => 'stat',
                'data' => ['label' => $stat['label'], 'value' => $stat['value'], 'align' => 'center'],
            ]]], $stats),
        ];
    }

    private function overview(array $paragraphs, string $title, string $eyebrow, string $image, string $caption, string $buttonLabel): array
    {
        return [
            'type' => 'row',
            'data' => ['section' => 'normal', 'width' => 'wide', 'gap' => 'large', 'columns' => 2],
            'columns' => [
                ['blocks' => [
                    ['type' => 'heading', 'data' => ['text' => $title, 'level' => '2', 'align' => 'left']],
                    ['type' => 'paragraph', 'data' => ['text' => $paragraphs[0] ?? '', 'align' => 'left']],
                    ['type' => 'paragraph', 'data' => ['text' => $paragraphs[1] ?? '', 'align' => 'left']],
                    ['type' => 'button', 'data' => ['label' => $buttonLabel, 'url' => '/contact', 'style' => 'outline', 'align' => 'left']],
                ]],
                ['blocks' => [
                    ['type' => 'heading', 'data' => ['text' => $eyebrow, 'level' => '3', 'align' => 'left']],
                    ['type' => 'image', 'data' => ['url' => $image, 'caption' => $caption, 'align' => 'left']],
                ]],
            ],
        ];
    }

    private function cards(string $eyebrow, string $title, array $cards, string $palette, string $description): array
    {
        return [
            'type' => 'row',
            'data' => [
                'section' => $palette === 'green' ? 'dark' : 'cards',
                'width' => 'wide',
                'gap' => 'normal',
                'columns' => 3,
                'palette' => $palette,
                'pattern' => $palette === 'green' ? 'grid' : 'none',
                'eyebrow' => $eyebrow,
                'title' => $title,
                'description' => $description,
            ],
            'columns' => array_map(fn ($card) => ['blocks' => [[
                'type' => 'feature_card',
                'data' => ['icon' => $card['icon'], 'title' => $card['title'], 'text' => $card['text'], 'align' => 'left'],
            ]]], $cards),
        ];
    }

    private function table(string $title, array $rows): array
    {
        return [
            'type' => 'row',
            'data' => ['section' => 'normal', 'width' => 'wide', 'gap' => 'normal', 'columns' => 1],
            'columns' => [[
                'blocks' => [
                    ['type' => 'heading', 'data' => ['text' => $title, 'level' => '2', 'align' => 'left']],
                    ['type' => 'table', 'data' => ['rows' => collect($rows)->map(fn ($row) => implode(',', array_map(fn ($cell) => '"'.str_replace('"', '""', $cell).'"', $row)))->implode("\n"), 'align' => 'left']],
                ],
            ]],
        ];
    }

    private function cta(string $title, string $description, string $label, string $url, string $secondaryLabel = 'Contact Us', string $eyebrow = 'Admissions'): array
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
                'secondary_label' => $secondaryLabel,
                'secondary_url' => '/contact',
            ],
            'columns' => [['blocks' => []]],
        ];
    }
};
