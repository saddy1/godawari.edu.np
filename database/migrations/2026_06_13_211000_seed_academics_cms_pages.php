<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->pages() as $page) {
            DB::table('cms_pages')->updateOrInsert(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'status' => 'published',
                    'template' => 'wide',
                    'content_blocks' => json_encode($this->blocks($page), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'content_html' => null,
                    'featured_image' => $page['image'],
                    'meta_title' => $page['meta_title'],
                    'meta_description' => $page['description'],
                    'meta_keywords' => $page['keywords'],
                    'sort_order' => $page['order'],
                    'published_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('cms_pages')
            ->whereIn('slug', ['academics-elementary', 'academics-primary', 'academics-secondary'])
            ->delete();
    }

    private function pages(): array
    {
        return [
            [
                'slug' => 'academics-elementary',
                'title' => 'Early Childhood and Basic Foundation',
                'meta_title' => 'Early Childhood and Basic Foundation - Barchhain Secondary School',
                'subtitle' => 'ECD to Grade 3',
                'description' => 'A warm start to school life where children build language, numeracy, habits, curiosity, and confidence.',
                'overview' => 'The early grades focus on child-friendly learning, regular attendance, reading habits, handwriting, basic mathematics, hygiene, discipline, songs, stories, games, and social behavior.',
                'image' => 'uploads/site/academics-elementary-image.jpeg',
                'focus' => 'Reading habit, basic mathematics, creative activities, cleanliness, manners, and attendance habit.',
                'keywords' => 'ECD, Grade 3, early childhood, Barchhain Secondary School',
                'order' => 10,
                'stats' => [
                    ['label' => 'Level', 'value' => 'ECD to Grade 3'],
                    ['label' => 'Program', 'value' => 'Foundation'],
                    ['label' => 'Learning', 'value' => 'Activity Based'],
                ],
                'highlights' => [
                    ['icon' => '📚', 'title' => 'Child-Friendly Learning', 'text' => 'Activity-based lessons, stories, drawing, play, and local examples help young children learn naturally.'],
                    ['icon' => '✍️', 'title' => 'Language and Numeracy', 'text' => 'Strong attention to Nepali, English, handwriting, reading, counting, and early problem solving.'],
                    ['icon' => '✅', 'title' => 'Care and Routine', 'text' => 'Teachers support hygiene, punctuality, classroom habits, confidence, and social-emotional growth.'],
                ],
                'subjects' => [
                    ['icon' => '📖', 'title' => 'Reading Habit', 'text' => 'Daily reading, picture talk, stories, and basic comprehension.'],
                    ['icon' => '🔢', 'title' => 'Basic Mathematics', 'text' => 'Counting, number sense, shapes, patterns, and early problem solving.'],
                    ['icon' => '🎨', 'title' => 'Creative Activities', 'text' => 'Drawing, songs, games, local materials, and confidence-building activities.'],
                ],
            ],
            [
                'slug' => 'academics-primary',
                'title' => 'Basic Level Education',
                'meta_title' => 'Basic Level Education - Barchhain Secondary School',
                'subtitle' => 'Grade 4 to Grade 8',
                'description' => 'Building strong subject foundations, discipline, teamwork, and practical learning for the secondary level.',
                'overview' => 'The basic level connects foundational literacy with deeper subject learning. Students develop reading comprehension, mathematics, science thinking, social awareness, local knowledge, ICT familiarity, and participation in co-curricular activities.',
                'image' => 'uploads/site/academics-primary-image.jpeg',
                'focus' => 'Nepali, English, mathematics, science and technology, social studies, health and physical education, local curriculum, and ICT awareness.',
                'keywords' => 'Grade 4, Grade 8, basic level education, Barchhain Secondary School',
                'order' => 20,
                'stats' => [
                    ['label' => 'Level', 'value' => 'Grade 4 to 8'],
                    ['label' => 'Focus', 'value' => 'Core Subjects'],
                    ['label' => 'Learning', 'value' => 'Projects + ICT'],
                ],
                'highlights' => [
                    ['icon' => '🧠', 'title' => 'Concept-Based Teaching', 'text' => 'Teachers use explanation, practice, project work, questions, and local context to strengthen understanding.'],
                    ['icon' => '💻', 'title' => 'ICT and Library Culture', 'text' => 'The school improvement plan gives priority to ICT, book corners, library habits, and digital literacy.'],
                    ['icon' => '📋', 'title' => 'Continuous Assessment', 'text' => 'Classwork, unit tests, participation, attendance, and feedback are used to support every learner.'],
                ],
                'subjects' => [
                    ['icon' => '🔬', 'title' => 'Science and Technology', 'text' => 'Observation, experiments, local examples, and practical science thinking.'],
                    ['icon' => '🌍', 'title' => 'Social Studies', 'text' => 'Community, citizenship, history, geography, and local awareness.'],
                    ['icon' => '🏃', 'title' => 'Health and Physical Education', 'text' => 'Healthy habits, teamwork, games, discipline, and student participation.'],
                ],
            ],
            [
                'slug' => 'academics-secondary',
                'title' => 'Secondary and Technical Education',
                'meta_title' => 'Secondary, Higher Secondary and Technical Education - Barchhain Secondary School',
                'subtitle' => 'Grade 9 to 12 - Technical Stream - Special Education',
                'description' => 'General education, Grade 11-12 education and management groups, CTEVT civil engineering, and inclusive learning support.',
                'overview' => 'Barchhain Secondary School runs general secondary education from Grade 9-10, Grade 11-12 classes in education and management/commerce groups, a CTEVT-affiliated Diploma in Civil Engineering, and a residential resource class for children with intellectual disability.',
                'image' => 'uploads/site/academics-secondary-image.jpeg',
                'focus' => 'Regular classes and attendance, practical and project-based learning, laboratory and ICT use, career guidance, learner support, sports, and co-curricular activities.',
                'keywords' => 'secondary education, Grade 11, Grade 12, CTEVT, civil engineering, Barchhain Secondary School',
                'order' => 30,
                'stats' => [
                    ['label' => 'Level', 'value' => 'Grade 9 to 12'],
                    ['label' => 'Technical', 'value' => 'Civil Engineering'],
                    ['label' => 'Support', 'value' => 'Inclusive Class'],
                ],
                'highlights' => [
                    ['icon' => '🎓', 'title' => 'Grade 9-10 General Stream', 'text' => 'Focused learning in compulsory and optional subjects with exam preparation, discipline, project work, and student mentoring.'],
                    ['icon' => '🏫', 'title' => 'Grade 11-12 Groups', 'text' => 'Education and management groups support students interested in teaching, social development, business, accounting, and commerce.'],
                    ['icon' => '🏗️', 'title' => 'Diploma in Civil Engineering', 'text' => 'A three-year technical program started in 2076 B.S. to build practical engineering skills for local and national development.'],
                ],
                'subjects' => [
                    ['icon' => '🧪', 'title' => 'Laboratory and ICT Use', 'text' => 'Practical learning, technology-friendly teaching, and digital resources.'],
                    ['icon' => '🧭', 'title' => 'Career Guidance', 'text' => 'Guidance for streams, exams, skills, higher study, and future pathways.'],
                    ['icon' => '🤝', 'title' => 'Inclusive Support', 'text' => 'Residential learning support focused on care, inclusion, and life skills.'],
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
                    'section' => 'hero',
                    'width' => 'wide',
                    'gap' => 'large',
                    'columns' => 2,
                    'palette' => 'dark',
                    'pattern' => 'grid',
                    'eyebrow' => 'Academics',
                    'badge' => $page['subtitle'],
                    'title' => $page['title'],
                    'description' => $page['description'],
                    'image' => $page['image'],
                    'primary_label' => 'Admission Inquiry',
                    'primary_url' => '/admissions',
                    'secondary_label' => 'Contact Us',
                    'secondary_url' => '/contact',
                ],
                'columns' => [
                    ['blocks' => []],
                    [
                        'blocks' => [
                            [
                                'type' => 'feature_card',
                                'data' => [
                                    'icon' => '🎯',
                                    'title' => 'Learning Focus',
                                    'text' => $page['focus'],
                                    'align' => 'left',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'row',
                'data' => [
                    'section' => 'stats',
                    'width' => 'wide',
                    'gap' => 'compact',
                    'columns' => 3,
                ],
                'columns' => array_map(fn ($stat) => [
                    'blocks' => [
                        [
                            'type' => 'stat',
                            'data' => [
                                'label' => $stat['label'],
                                'value' => $stat['value'],
                                'align' => 'left',
                            ],
                        ],
                    ],
                ], $page['stats']),
            ],
            [
                'type' => 'row',
                'data' => [
                    'section' => 'normal',
                    'width' => 'wide',
                    'gap' => 'large',
                    'columns' => 2,
                ],
                'columns' => [
                    [
                        'blocks' => [
                            ['type' => 'heading', 'data' => ['text' => 'Academic Overview', 'level' => '2', 'align' => 'left']],
                            ['type' => 'paragraph', 'data' => ['text' => $page['overview'], 'align' => 'left']],
                            ['type' => 'button', 'data' => ['label' => 'Admission Inquiry', 'url' => '/admissions', 'style' => 'primary', 'align' => 'left']],
                        ],
                    ],
                    [
                        'blocks' => [
                            ['type' => 'image', 'data' => ['url' => $page['image'], 'caption' => $page['subtitle'], 'align' => 'left']],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'row',
                'data' => [
                    'section' => 'cards',
                    'width' => 'wide',
                    'gap' => 'normal',
                    'columns' => 3,
                    'palette' => 'light',
                    'pattern' => 'none',
                    'eyebrow' => 'Program Features',
                    'title' => 'What students experience',
                    'description' => 'These cards are CMS feature blocks. Admin can edit text, icons, order, layout, and background style.',
                ],
                'columns' => array_map(fn ($item) => [
                    'blocks' => [
                        [
                            'type' => 'feature_card',
                            'data' => [
                                'icon' => $item['icon'],
                                'title' => $item['title'],
                                'text' => $item['text'],
                                'align' => 'left',
                            ],
                        ],
                    ],
                ], $page['highlights']),
            ],
            [
                'type' => 'row',
                'data' => [
                    'section' => 'dark',
                    'width' => 'wide',
                    'gap' => 'normal',
                    'columns' => 3,
                    'palette' => 'green',
                    'pattern' => 'diagonal',
                    'eyebrow' => 'Learning Areas',
                    'title' => 'Focus areas',
                    'description' => $page['focus'],
                ],
                'columns' => array_map(fn ($item) => [
                    'blocks' => [
                        [
                            'type' => 'feature_card',
                            'data' => [
                                'icon' => $item['icon'],
                                'title' => $item['title'],
                                'text' => $item['text'],
                                'align' => 'left',
                            ],
                        ],
                    ],
                ], $page['subjects']),
            ],
            [
                'type' => 'row',
                'data' => [
                    'section' => 'cta',
                    'width' => 'wide',
                    'gap' => 'normal',
                    'columns' => 1,
                    'eyebrow' => 'Admissions',
                    'title' => 'Interested in studying at Barchhain Secondary?',
                    'description' => 'Contact the school office for admission details, class availability, technical education, and student support.',
                    'primary_label' => 'Admission Inquiry',
                    'primary_url' => '/admissions',
                    'secondary_label' => 'Contact Us',
                    'secondary_url' => '/contact',
                ],
                'columns' => [
                    ['blocks' => []],
                ],
            ],
        ];
    }
};
