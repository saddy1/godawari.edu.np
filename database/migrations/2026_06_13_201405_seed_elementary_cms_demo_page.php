<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cms_pages')->updateOrInsert(
            ['slug' => 'elementary-cms-demo'],
            [
                'title' => 'Elementary CMS Demo',
                'status' => 'published',
                'template' => 'wide',
                'content_blocks' => json_encode($this->blocks(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'content_html' => null,
                'featured_image' => 'assets/image/default-placeholder.jpg',
                'meta_title' => 'Elementary CMS Demo - Barchhain Secondary School',
                'meta_description' => 'A CMS-built demo page showing hero, stats, overview, feature cards, dark highlight, testimonials, and call to action sections.',
                'meta_keywords' => 'CMS page builder, elementary education, Barchhain Secondary School',
                'sort_order' => 10,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('cms_pages')->where('slug', 'elementary-cms-demo')->delete();
    }

    private function blocks(): array
    {
        return [
            [
                'type' => 'row',
                'data' => [
                    'section' => 'hero',
                    'width' => 'wide',
                    'gap' => 'large',
                    'columns' => 2,
                    'eyebrow' => 'ECD to Grade 3',
                    'badge' => 'ECD to Grade 3',
                    'title' => 'Early Childhood and Basic Foundation',
                    'description' => 'A warm start to school life where children build language, numeracy, habits, curiosity, and confidence.',
                    'image' => 'assets/image/default-placeholder.jpg',
                    'primary_label' => 'Apply Now',
                    'primary_url' => '/admissions',
                    'secondary_label' => 'Contact Us',
                    'secondary_url' => '/contact',
                ],
                'columns' => [
                    [
                        'blocks' => [
                            [
                                'type' => 'paragraph',
                                'data' => [
                                    'text' => 'This hero is built from a CMS row set to Hero section. Use the row settings to change the background image, badge, title, description, and buttons.',
                                    'align' => 'left',
                                ],
                            ],
                        ],
                    ],
                    [
                        'blocks' => [
                            [
                                'type' => 'feature_card',
                                'data' => [
                                    'icon' => '🎓',
                                    'title' => 'Learning Focus',
                                    'text' => 'Reading habit, basic mathematics, creative activities, cleanliness, manners, and attendance habit.',
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
                'columns' => [
                    ['blocks' => [['type' => 'stat', 'data' => ['label' => 'Program', 'value' => 'ECD to Grade 3', 'align' => 'left']]]],
                    ['blocks' => [['type' => 'stat', 'data' => ['label' => 'Community School', 'value' => '100%', 'align' => 'left']]]],
                    ['blocks' => [['type' => 'stat', 'data' => ['label' => 'Started', 'value' => '2009 B.S.', 'align' => 'left']]]],
                ],
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
                            ['type' => 'heading', 'data' => ['text' => 'Early Childhood and Basic Foundation', 'level' => '2', 'align' => 'left']],
                            ['type' => 'paragraph', 'data' => ['text' => 'The early grades focus on child-friendly learning, regular attendance, reading habits, handwriting, basic mathematics, hygiene, discipline, songs, stories, games, and social behavior.', 'align' => 'left']],
                            ['type' => 'image', 'data' => ['url' => 'assets/image/default-placeholder.jpg', 'caption' => 'Overview image selected from the media/image block.', 'align' => 'left']],
                        ],
                    ],
                    [
                        'blocks' => [
                            ['type' => 'heading', 'data' => ['text' => 'How this section was made', 'level' => '3', 'align' => 'left']],
                            ['type' => 'paragraph', 'data' => ['text' => 'This is a 2-column normal row. The left column has heading, paragraph, and image blocks. The right column explains how the CMS settings work.', 'align' => 'left']],
                            ['type' => 'table', 'data' => ['rows' => "Block,Use\nHeading,Section title\nParagraph,Body text\nImage,Media or uploaded image", 'align' => 'left']],
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
                    'eyebrow' => 'Program Features',
                    'title' => 'Learning Focus',
                    'description' => 'Use Feature Card blocks inside a Card grid row for clean visual highlights.',
                ],
                'columns' => [
                    ['blocks' => [['type' => 'feature_card', 'data' => ['icon' => '📚', 'title' => 'Child-Friendly Learning', 'text' => 'Activity-based lessons, stories, drawing, play, and local examples help young children learn naturally.', 'align' => 'left']]]],
                    ['blocks' => [['type' => 'feature_card', 'data' => ['icon' => '✍️', 'title' => 'Language and Numeracy', 'text' => 'Strong attention to Nepali, English, handwriting, reading, counting, and early problem solving.', 'align' => 'left']]]],
                    ['blocks' => [['type' => 'feature_card', 'data' => ['icon' => '✅', 'title' => 'Care and Routine', 'text' => 'Teachers support hygiene, punctuality, classroom habits, confidence, and social-emotional growth.', 'align' => 'left']]]],
                ],
            ],
            [
                'type' => 'row',
                'data' => [
                    'section' => 'dark',
                    'width' => 'wide',
                    'gap' => 'normal',
                    'columns' => 3,
                    'eyebrow' => 'Subjects',
                    'title' => 'Focus Areas',
                    'description' => 'Use Dark highlight rows for important lists, focus topics, or program summaries.',
                ],
                'columns' => [
                    ['blocks' => [['type' => 'feature_card', 'data' => ['icon' => '📖', 'title' => 'Reading Habit', 'text' => 'Daily reading and basic comprehension.', 'align' => 'left']]]],
                    ['blocks' => [['type' => 'feature_card', 'data' => ['icon' => '🔢', 'title' => 'Basic Mathematics', 'text' => 'Counting, number sense, and problem solving.', 'align' => 'left']]]],
                    ['blocks' => [['type' => 'feature_card', 'data' => ['icon' => '🎨', 'title' => 'Creative Activities', 'text' => 'Drawing, songs, stories, and play-based learning.', 'align' => 'left']]]],
                ],
            ],
            [
                'type' => 'row',
                'data' => [
                    'section' => 'cards',
                    'width' => 'wide',
                    'gap' => 'normal',
                    'columns' => 3,
                    'eyebrow' => 'Community Voices',
                    'title' => 'Family Feedback',
                    'description' => 'Use Testimonial blocks for parent, student, and community feedback.',
                ],
                'columns' => [
                    ['blocks' => [['type' => 'testimonial', 'data' => ['quote' => 'The school gives children a friendly start and builds confidence step by step.', 'name' => 'Parent Representative', 'role' => 'Guardian', 'align' => 'left']]]],
                    ['blocks' => [['type' => 'testimonial', 'data' => ['quote' => 'Regular reading, discipline, and classroom routines help students grow steadily.', 'name' => 'Class Teacher', 'role' => 'Early Grade Teacher', 'align' => 'left']]]],
                    ['blocks' => [['type' => 'testimonial', 'data' => ['quote' => 'Activities, songs, and stories make learning easier for young children.', 'name' => 'Student Voice', 'role' => 'Basic Level', 'align' => 'left']]]],
                ],
            ],
            [
                'type' => 'row',
                'data' => [
                    'section' => 'cta',
                    'width' => 'wide',
                    'gap' => 'normal',
                    'columns' => 1,
                    'eyebrow' => 'Admissions',
                    'title' => 'Start the admission process',
                    'description' => 'This CTA row uses row-level buttons. Edit the row settings to change button labels and links.',
                    'primary_label' => 'Apply Now',
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
