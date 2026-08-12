<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Card\Student;
use App\Models\HomeBanner;
use App\Models\HomeContent;
use App\Models\KeyPerson;
use App\Models\Media;
use App\Models\PopupNotice;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index()
{
    // Fetch latest notices
    $homeNotices = Announcement::where('type', 'notice')
        ->where('is_published', true)
        ->latest()
        ->take(5)
        ->get();

    // Fetch latest upcoming events
    $homeEvents = Announcement::where('type', 'event')
        ->where('is_published', true)
        ->latest()
        ->take(4)
        ->get();

        $homeNews = Announcement::whereIn('type', ['news', 'notice'])
            ->where('is_published', true)
            ->latest()
            ->take(5)
            ->get();

        $testimonials = Testimonial::where('is_active', true)->latest()->get();

        $hasGallerySelection = Schema::hasColumn('media', 'show_in_gallery');

        // Fetch selected campus design images uploaded via admin (category = 'Campus')
        $campusImagesQuery = Media::where('category', 'Campus')
            ->where('mime_type', 'like', 'image/%');
        $homeGalleryQuery = Media::where('mime_type', 'like', 'image/%');

        $campusImages = $hasGallerySelection
            ? $campusImagesQuery->where('show_in_gallery', true)->latest()->take(8)->get()
            : collect();
        if ($hasGallerySelection) {
            $selectedGallery = (clone $homeGalleryQuery)
                ->where('show_in_gallery', true)
                ->latest()
                ->take(48)
                ->get();

            // Show available media until the editor makes an explicit homepage selection.
            $homeGallery = $selectedGallery->isNotEmpty()
                ? $selectedGallery
                : $homeGalleryQuery->latest()->take(48)->get();
        } else {
            $homeGallery = $homeGalleryQuery->latest()->take(48)->get();
        }

        $popups = PopupNotice::where('is_active', true)->latest()->take(3)->get();

        $homeStats = [
            'students' => $this->safeCount(fn () => Schema::hasTable('students') ? Student::count() : 0, 470),
            'teachers' => $this->safeCount(fn () => User::role(['teacher', 'staff'])->count(), 25),
            'notices' => $this->safeCount(fn () => Announcement::where('is_published', true)->count(), $homeNews->count()),
        ];

        [$quickLinks, $learningPathways] = $this->homeContent();
        $homeBanners = $this->homeBanners();
        $keyPersons = $this->keyPersons();

    return view('pages.home', compact('homeNotices', 'homeEvents', 'homeNews', 'popups', 'testimonials', 'campusImages', 'homeGallery', 'homeStats', 'quickLinks', 'learningPathways', 'homeBanners', 'keyPersons'));
}

    private function safeCount(callable $callback, int $fallback = 0): int
    {
        try {
            return (int) $callback();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function homeContent(): array
    {
        if (Schema::hasTable('home_contents')) {
            $items = HomeContent::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();

            return [
                $items->where('type', 'quick_link')->values(),
                $items->where('type', 'learning_pathway')->values(),
            ];
        }

        return [
            collect([
                new HomeContent(['title' => 'Notices',           'title_ne' => 'सूचनाहरू',           'subtitle' => 'View all notices',    'subtitle_ne' => 'सबै सूचना हेर्नुहोस्', 'url' => route('notices'),                               'icon_key' => 'notice']),
                new HomeContent(['title' => 'Results',           'title_ne' => 'नतिजाहरू',           'subtitle' => 'SEE & other results', 'subtitle_ne' => 'SEE र अन्य नतिजा',     'url' => route('notices', ['category' => 'Result']),   'icon_key' => 'result']),
                new HomeContent(['title' => 'Academic Calendar', 'title_ne' => 'शैक्षिक पात्रो',    'subtitle' => 'Academic calendar',   'subtitle_ne' => 'शैक्षिक पात्रो',        'url' => route('notices', ['category' => 'Calendar']), 'icon_key' => 'calendar']),
                new HomeContent(['title' => 'IEMIS',             'title_ne' => 'आईईएमआईएस',          'subtitle' => 'School data',         'subtitle_ne' => 'विद्यालय डेटा',         'url' => 'http://iemis.cehrd.gov.np/login',            'icon_key' => 'grid']),
                new HomeContent(['title' => 'Downloads',         'title_ne' => 'डाउनलोडहरू',         'subtitle' => 'Forms & documents',   'subtitle_ne' => 'फारम र कागजात',         'url' => route('notices', ['category' => 'Download']), 'icon_key' => 'download']),
                new HomeContent(['title' => 'Contact Us',        'title_ne' => 'सम्पर्क गर्नुहोस्', 'subtitle' => 'Get in touch',        'subtitle_ne' => 'हामीसँग जोडिनुहोस्',   'url' => route('contact'),                             'icon_key' => 'contact']),
            ]),
            collect([
                new HomeContent(['title' => 'B.Sc. CSIT', 'subtitle' => '4 years · 8 semesters · 36 seats', 'description' => 'Build strong foundations in computing, software development, information systems, and emerging technologies.', 'url' => route('cms.pages.show', 'bsc-csit'), 'image_path' => 'uploads/site/godawari-csit.jpg', 'icon_key' => 'screen']),
                new HomeContent(['title' => 'Bachelor of Business Studies (BBS)', 'subtitle' => '4 years · Tribhuvan University', 'description' => 'Develop practical knowledge in management, accounting, finance, marketing, and entrepreneurship.', 'url' => route('cms.pages.show', 'bbs'), 'image_path' => 'uploads/site/godawari-bbs.jpg', 'icon_key' => 'idea']),
            ]),
        ];
    }

    private function homeBanners()
    {
        if (Schema::hasTable('home_banners')) {
            $banners = HomeBanner::where('is_active', true)
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->get();

            if ($banners->isNotEmpty()) {
                return $banners;
            }
        }

        return collect([
            new HomeBanner([
                'eyebrow' => 'Tribhuvan University Affiliated College',
                'title' => __('site.home.portal.hero_title'),
                'subtitle' => $this->fallbackBannerSubtitle(),
                'primary_label' => __('site.home.portal.learn_more_about'),
                'primary_url' => route('about'),
                'secondary_label' => __('site.home.portal.admission_open'),
                'secondary_url' => route('admissions'),
                'image_path' => 'assets/image/default-placeholder.jpg',
                'text_position' => 'left',
                'sort_order' => 1,
                'is_active' => true,
            ]),
        ]);
    }

    private function keyPersons()
    {
        if (! Schema::hasTable('key_persons')) {
            return collect();
        }

        return KeyPerson::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(8)
            ->get();
    }

    private function fallbackBannerSubtitle(): string
    {
        $siteSettings = app(\App\Support\SiteSettings::class);

        return $siteSettings->localized('site_tagline', __('site.tagline')).'. '.$siteSettings->localized('site_address', __('site.location'));
    }

    public function sitemap()
    {
        $now = now()->toAtomString();

        // Static pages
        $urls = [
            ['loc' => route('home'),                 'priority' => '1.0', 'freq' => 'daily',   'lastmod' => $now],
            ['loc' => route('about'),                'priority' => '0.9', 'freq' => 'monthly', 'lastmod' => $now],
            ['loc' => route('admissions'),           'priority' => '0.9', 'freq' => 'weekly',  'lastmod' => $now],
            ['loc' => route('academics.elementary'), 'priority' => '0.8', 'freq' => 'monthly', 'lastmod' => $now],
            ['loc' => route('academics.primary'),    'priority' => '0.8', 'freq' => 'monthly', 'lastmod' => $now],
            ['loc' => route('academics.secondary'),  'priority' => '0.8', 'freq' => 'monthly', 'lastmod' => $now],
            ['loc' => route('gallery'),              'priority' => '0.7', 'freq' => 'weekly',  'lastmod' => $now],
            ['loc' => route('news'),                 'priority' => '0.8', 'freq' => 'daily',   'lastmod' => $now],
            ['loc' => route('events'),               'priority' => '0.8', 'freq' => 'weekly',  'lastmod' => $now],
            ['loc' => route('notices'),              'priority' => '0.8', 'freq' => 'daily',   'lastmod' => $now],
            ['loc' => route('frontend.faculty'),     'priority' => '0.7', 'freq' => 'monthly', 'lastmod' => $now],
            ['loc' => route('vacancies'),            'priority' => '0.8', 'freq' => 'weekly',  'lastmod' => $now],
            ['loc' => route('contact'),              'priority' => '0.7', 'freq' => 'yearly',  'lastmod' => $now],
            ['loc' => route('privacy'),              'priority' => '0.3', 'freq' => 'yearly',  'lastmod' => $now],
            ['loc' => route('terms'),                'priority' => '0.3', 'freq' => 'yearly',  'lastmod' => $now],
        ];

        // Dynamic announcement pages (news & events)
        $announcements = Announcement::where('is_published', true)
            ->whereNotNull('slug')
            ->latest()
            ->get(['slug', 'type', 'updated_at']);

        foreach ($announcements as $item) {
            $urls[] = [
                'loc'      => route('news.show', $item->slug),
                'priority' => '0.6',
                'freq'     => 'monthly',
                'lastmod'  => $item->updated_at->toAtomString(),
            ];
        }

        return response()->view('pages.sitemap', compact('urls'))
                         ->header('Content-Type', 'application/xml');
    }

  
}
