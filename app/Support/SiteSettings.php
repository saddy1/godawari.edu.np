<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SiteSettings
{
    private array $defaults = [
        'site_name_en' => 'Godawari College',
        'site_name_ne' => 'गोदावरी कलेज',
        'app_name' => 'Godawari College ERP',
        'site_tagline_en' => 'A Place Where Dreams Are Nurtured',
        'site_tagline_ne' => 'सपना साकार गर्ने शैक्षिक थलो',
        'site_address_en' => 'Itahari, Sunsari, Nepal',
        'site_address_ne' => 'इटहरी, सुनसरी, नेपाल',
        'school_code' => '',
        'school_phone' => '+977 985-2056519',
        'school_email' => 'info@godawari.edu.np',
        'office_hours' => 'Sun-Fri 9:00 AM - 5:00 PM',
        'office_hours_days' => 'Sunday – Friday',
        'office_hours_time' => '9:00 AM – 5:00 PM',
        'office_hours_closed' => 'Saturday & Public Holidays',
        'map_latitude'  => '26.6540757',
        'map_longitude' => '87.2774388',
        'map_zoom'      => '16',
        'website_url' => 'https://www.godawari.edu.np',
        'site_logo'     => 'media/1784009566_sushma.png',
        'site_favicon'  => 'media/1784009566_sushma.png',
        'home_hero_image' => 'uploads/site/godawari-csit.jpg',
        'home_principal_image' => 'media/1784010094_bodh-raj-nepal.png',
        'academics_elementary_image' => 'assets/image/default-placeholder.jpg',
        'academics_primary_image' => 'assets/image/default-placeholder.jpg',
        'academics_secondary_image' => 'assets/image/default-placeholder.jpg',
        'social_facebook'  => '',
        'social_instagram' => '',
        'social_tiktok'    => '',
        'social_twitter'   => '',
        'social_whatsapp'  => '',
        'social_youtube'   => '',
        'school_alternate_name'       => 'Sushma Godawari College',
        'school_street'               => 'Itahari-9, near Itahari Stadium',
        'school_locality'             => 'Itahari',
        'school_region'               => 'Sunsari',
        'school_area_served'          => 'Koshi Province',
        'school_founding_date_ad'     => '1992',
        'school_hours_schema'         => 'Su-Fr 09:00-17:00',
        'seo_default_title_en'        => 'Godawari College | B.Sc. CSIT and BBS in Itahari',
        'seo_default_title_ne'        => 'गोदावरी कलेज | इटहरीमा B.Sc. CSIT र BBS',
        'seo_default_description_en'  => 'Godawari College is a Tribhuvan University affiliated college in Itahari offering B.Sc. CSIT and BBS programs.',
        'seo_default_description_ne'  => 'गोदावरी कलेज इटहरीमा रहेको त्रिभुवन विश्वविद्यालयबाट सम्बन्धन प्राप्त कलेज हो, जहाँ B.Sc. CSIT र BBS कार्यक्रम सञ्चालन हुन्छन्।',
        'seo_default_keywords_en'     => 'Godawari College, college in Itahari, BSc CSIT Itahari, BBS Itahari, Tribhuvan University college',
        'seo_default_keywords_ne'     => 'गोदावरी कलेज, इटहरी कलेज, BSc CSIT, BBS',
        'school_estd'          => '2049 B.S. (1992 AD)',
        'principal_name'       => 'Bodh Raj Nepal',
        'principal_initials'   => 'BR',
        'principal_role_en'    => 'Campus Chief, Godawari College',
        'principal_role_ne'    => 'क्याम्पस प्रमुख, गोदावरी कलेज',
        'principal_message_en' => 'Message From the Campus Chief',
        'principal_message_ne' => 'क्याम्पस प्रमुखको सन्देश',
        'principal_quote_en'   => 'Our college is committed to fostering essential skills, identifying student talent, and stimulating innovative thinking.',
        'principal_quote_ne'   => 'हाम्रो कलेज आवश्यक सीप विकास, विद्यार्थी प्रतिभा पहिचान र नवीन सोच प्रवर्द्धन गर्न प्रतिबद्ध छ।',
        'primary_color' => '#0d5963',
        'secondary_color' => '#f2a51a',
        'dark_color' => '#071d2b',
        'primary_light_color' => '#167480',
        'body_bg_color' => '#fdfbf7',
        'body_bg_gradient_end' => '#f4f5f0',
        'surface_color' => '#ffffff',
        'muted_surface_color' => '#F8FAFC',
        'border_color' => '#E5E7EB',
        'text_color' => '#111827',
        'muted_text_color' => '#64748B',
        'header_gradient_end' => '#083b45',
        'hero_gradient_end' => '#083b45',
        'cta_gradient_end' => '#071d2b',
        'footer_gradient_end' => '#071d2b',
        'notice_bg_color' => '',
        'notice_accent_color' => '',
        'sidebar_gradient_end' => '#050f09',
        'body_font' => 'DM Sans',
        'heading_font' => 'Playfair Display',
        'default_locale' => 'en',
    ];

    public function all(): array
    {
        return array_merge($this->defaults, $this->stored());
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $settings = $this->all();

        return $settings[$key] ?? $default;
    }

    public function localized(string $baseKey, ?string $default = null): ?string
    {
        $locale = app()->getLocale() === 'ne' ? 'ne' : 'en';

        return $this->get($baseKey.'_'.$locale, $default);
    }

    public function logoUrl(): string
    {
        $path = $this->get('site_logo', 'assets/image/logo.png');

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }

    public function faviconUrl(): string
    {
        $path = $this->get('site_favicon', 'assets/image/favicon.png');

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }

    public function imageUrl(string $key, string $fallback): string
    {
        $path = $this->get($key, $fallback);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }

    public function fontFamily(string $key, string $fallback): string
    {
        return match ($this->get($key)) {
            'Inter' => "'Inter', sans-serif",
            'Noto Sans Devanagari' => "'Noto Sans Devanagari', sans-serif",
            'Poppins' => "'Poppins', sans-serif",
            'Lora' => "'Lora', Georgia, serif",
            'Merriweather' => "'Merriweather', Georgia, serif",
            'Playfair Display' => "'Playfair Display', Georgia, serif",
            default => $fallback,
        };
    }

    public function clearCache(): void
    {
        Cache::forget('site_settings');
    }

    private function stored(): array
    {
        return Cache::remember('site_settings', 300, function () {
            try {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::pluck('value', 'key')
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->toArray();
            } catch (Throwable) {
                return [];
            }
        });
    }
}
