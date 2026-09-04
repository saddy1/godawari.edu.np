<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\CmsMenu;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // Import View facade
use Illuminate\Support\Facades\URL;
use App\Models\Announcement; // Import Announcement model
use App\Models\CmsMenuItem;
use App\Services\ModuleService;
use App\Support\SiteSettings;
use Illuminate\Support\Collection;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
   public function boot(): void
    {
        // In development Laravel must use the current request host. Forcing the
        // configured 127.0.0.1 URL breaks CSS/JS when the site is opened from a
        // phone over the LAN because 127.0.0.1 then points to the phone itself.
        if (config('app.env') === 'production') {
            URL::forceRootUrl(rtrim((string) config('seo.canonical_url'), '/'));
        }

        $this->applyDatabaseMailSettings();

        View::share('siteSettings', app(SiteSettings::class));

        // Bind the latest 10 active notices specifically to the navbar view
        View::composer('partials.header', function ($view) {
            $notices = collect();
            $headerMenuItems = collect();

            if (Schema::hasTable('announcements')) {
                $notices = Announcement::where('type', 'notice')
                    ->where('is_published', true)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();
            }

            if (Schema::hasTable('cms_menus') && Schema::hasTable('cms_menu_items')) {
                $headerMenu = CmsMenu::where('location', 'header')
                    ->where('is_active', true)
                    ->with(['rootItems.page'])
                    ->first();

                $headerMenuItems = $this->visibleHeaderMenuItems($headerMenu?->rootItems ?? collect());
            }

            $view->with([
                'notices' => $notices,
                'headerMenuItems' => $headerMenuItems,
            ]);
        });

        // Feed the focused teacher app from the same published notices as the website.
        View::composer('teaching_learning.teacher-workspace.layout', function ($view) {
            $teacherNotices = collect();

            try {
                if (Schema::hasTable('announcements')) {
                    $teacherNotices = Announcement::query()
                        ->where('type', 'notice')
                        ->where('is_published', true)
                        ->latest()
                        ->limit(8)
                        ->get(['id', 'title', 'slug', 'excerpt', 'created_at']);
                }
            } catch (Throwable) {
                // Keep the workspace usable while the database is being prepared.
            }

            $view->with('teacherNotices', $teacherNotices);
        });
    }

    private function visibleHeaderMenuItems(Collection $items): Collection
    {
        return $items
            ->filter(fn (CmsMenuItem $item) => $this->menuItemIsVisible($item))
            ->map(function (CmsMenuItem $item) {
                if ($item->relationLoaded('children')) {
                    $item->setRelation('children', $this->visibleHeaderMenuItems($item->children));
                }

                return $item;
            })
            ->values();
    }

    private function menuItemIsVisible(CmsMenuItem $item): bool
    {
        if (ModuleService::disabled('vacancy') && $this->menuItemMatchesAny($item, ['vacanc', 'applicant'])) {
            return false;
        }

        if (ModuleService::disabled('admissions') && $this->menuItemMatchesAny($item, ['admission'])) {
            return false;
        }

        return true;
    }

    private function menuItemMatchesAny(CmsMenuItem $item, array $needles): bool
    {
        $haystack = strtolower(implode(' ', array_filter([
            $item->label,
            $item->label_ne,
            $item->subtitle,
            $item->subtitle_ne,
            $item->url,
            $item->resolved_url,
        ])));

        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function applyDatabaseMailSettings(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $settings = Setting::whereIn('key', [
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
            ])->pluck('value', 'key');

            $mailer = $settings->get('mail_mailer');
            if (!$mailer) {
                return;
            }

            config([
                'mail.default' => $mailer,
                'mail.mailers.smtp.host' => $settings->get('mail_host') ?: config('mail.mailers.smtp.host'),
                'mail.mailers.smtp.port' => (int) ($settings->get('mail_port') ?: config('mail.mailers.smtp.port')),
                'mail.mailers.smtp.username' => $settings->get('mail_username') ?: null,
                'mail.mailers.smtp.password' => $this->decryptSetting($settings->get('mail_password')) ?: config('mail.mailers.smtp.password'),
                'mail.mailers.smtp.encryption' => $settings->get('mail_encryption') ?: null,
                'mail.from.address' => $settings->get('mail_from_address') ?: config('mail.from.address'),
                'mail.from.name' => $settings->get('mail_from_name') ?: config('mail.from.name'),
            ]);
        } catch (Throwable) {
            //
        }
    }

    private function decryptSetting(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }
}
