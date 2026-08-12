<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SettingController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()?->isSuperAdmin(), 403);

        $settings = Setting::pluck('value', 'key')->toArray();

        return view('backend.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $rules = [
            'admission_year' => 'required|string|max:255',
            'school_phone'   => 'required|string|max:255',
            'school_email'   => 'required|email|max:255',
            'office_hours'        => 'required|string|max:255',
            'office_hours_days'   => 'nullable|string|max:100',
            'office_hours_time'   => 'nullable|string|max:100',
            'office_hours_closed' => 'nullable|string|max:100',
            'map_latitude'        => ['nullable', 'regex:/^-?\d{1,3}(\.\d+)?$/'],
            'map_longitude'       => ['nullable', 'regex:/^-?\d{1,3}(\.\d+)?$/'],
            'map_zoom'            => 'nullable|integer|min:1|max:20',
            'site_name_en'   => 'required|string|max:255',
            'site_name_ne'   => 'nullable|string|max:255',
            'app_name'       => 'required|string|max:255',
            'site_tagline_en' => 'nullable|string|max:255',
            'site_tagline_ne' => 'nullable|string|max:255',
            'site_address_en' => 'required|string|max:255',
            'site_address_ne' => 'nullable|string|max:255',
            'school_code'     => 'nullable|string|max:100',
            'website_url'    => 'nullable|url|max:255',
            'default_locale' => 'required|in:en,ne',
            'primary_color'         => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'dark_color'            => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'primary_light_color'   => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'body_bg_color'         => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'body_bg_gradient_end'  => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'surface_color'         => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'muted_surface_color'   => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'border_color'          => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color'            => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'muted_text_color'      => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_gradient_end'   => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'hero_gradient_end'     => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cta_gradient_end'      => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_gradient_end'   => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'notice_bg_color'       => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'notice_accent_color'   => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sidebar_gradient_end'  => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'body_font'      => 'required|in:DM Sans,Inter,Noto Sans Devanagari,Poppins',
            'heading_font'   => 'required|in:Playfair Display,Lora,Merriweather,Noto Sans Devanagari',
            'school_alternate_name'      => 'nullable|string|max:255',
            'school_street'              => 'nullable|string|max:255',
            'school_locality'            => 'nullable|string|max:255',
            'school_region'              => 'nullable|string|max:255',
            'school_area_served'         => 'nullable|string|max:255',
            'school_founding_date_ad'    => 'nullable|string|max:10',
            'school_hours_schema'        => 'nullable|string|max:100',
            'seo_default_title_en'       => 'nullable|string|max:255',
            'seo_default_title_ne'       => 'nullable|string|max:255',
            'seo_default_description_en' => 'nullable|string|max:500',
            'seo_default_description_ne' => 'nullable|string|max:500',
            'seo_default_keywords_en'    => 'nullable|string|max:500',
            'seo_default_keywords_ne'    => 'nullable|string|max:500',
            'social_facebook'  => 'nullable|url|max:255',
            'social_instagram' => 'nullable|url|max:255',
            'social_tiktok'    => 'nullable|url|max:255',
            'social_twitter'   => 'nullable|url|max:255',
            'social_whatsapp'  => 'nullable|url|max:255',
            'social_youtube'   => 'nullable|url|max:255',
            'site_logo_media_path' => 'nullable|string|max:500',
            'site_favicon_media_path' => 'nullable|string|max:500',
            'site_logo'      => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'site_favicon'   => 'nullable|file|mimes:png,jpg,jpeg,webp,ico|max:512',
        ];

        if ($request->user()?->isSuperAdmin()) {
            $rules += [
                'mail_mailer'       => 'required|in:smtp,log,array',
                'mail_host'         => 'nullable|required_if:mail_mailer,smtp|string|max:255',
                'mail_port'         => 'nullable|required_if:mail_mailer,smtp|integer|min:1|max:65535',
                'mail_username'     => 'nullable|string|max:255',
                'mail_password'     => 'nullable|string|max:255',
                'mail_encryption'   => 'nullable|in:tls,ssl',
                'mail_from_address' => 'required|email|max:255',
                'mail_from_name'    => 'required|string|max:255',
            ];
        }

        $request->validate($rules);

        $keys = [
            'admission_year',
            'school_phone',
            'school_email',
            'office_hours',
            'office_hours_days',
            'office_hours_time',
            'office_hours_closed',
            'map_latitude',
            'map_longitude',
            'map_zoom',
            'site_name_en',
            'site_name_ne',
            'app_name',
            'site_tagline_en',
            'site_tagline_ne',
            'site_address_en',
            'site_address_ne',
            'school_code',
            'website_url',
            'default_locale',
            'school_alternate_name',
            'school_street',
            'school_locality',
            'school_region',
            'school_area_served',
            'school_founding_date_ad',
            'school_hours_schema',
            'seo_default_title_en',
            'seo_default_title_ne',
            'seo_default_description_en',
            'seo_default_description_ne',
            'seo_default_keywords_en',
            'seo_default_keywords_ne',
            'social_facebook',
            'social_instagram',
            'social_tiktok',
            'social_twitter',
            'social_whatsapp',
            'social_youtube',
            'primary_color',
            'secondary_color',
            'dark_color',
            'primary_light_color',
            'body_bg_color',
            'body_bg_gradient_end',
            'surface_color',
            'muted_surface_color',
            'border_color',
            'text_color',
            'muted_text_color',
            'header_gradient_end',
            'hero_gradient_end',
            'cta_gradient_end',
            'footer_gradient_end',
            'notice_bg_color',
            'notice_accent_color',
            'sidebar_gradient_end',
            'body_font',
            'heading_font',
        ];

        if ($request->user()?->isSuperAdmin()) {
            $keys = array_merge($keys, [
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
            ]);
        }

        foreach ($request->only($keys) as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        if ($request->user()?->isSuperAdmin() && $request->filled('mail_password')) {
            Setting::updateOrCreate(
                ['key' => 'mail_password'],
                ['value' => Crypt::encryptString($request->mail_password)]
            );
        }

        if ($mediaPath = $this->selectedMediaPath($request, 'site_logo_media_path')) {
            $this->setSettingImagePath('site_logo', $mediaPath);
        } elseif ($request->hasFile('site_logo')) {
            $this->replaceSettingImage($request, 'site_logo', 'site-logo');
        }

        if ($mediaPath = $this->selectedMediaPath($request, 'site_favicon_media_path')) {
            $this->setSettingImagePath('site_favicon', $mediaPath);
        } elseif ($request->hasFile('site_favicon')) {
            $this->replaceSettingImage($request, 'site_favicon', 'site-favicon');
        }

        app(SiteSettings::class)->clearCache();

        if ($request->filled('default_locale')) {
            session(['locale' => $request->default_locale]);
            cookie()->queue(cookie('locale', $request->default_locale, 60 * 24 * 365));
        }

        return back()->with('success', 'Global settings updated successfully.');
    }

    private function replaceSettingImage(Request $request, string $key, ?string $filenameBase = null): void
    {
        $file = $request->file($key);
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::slug($filenameBase ?: $key) . '.' . $extension;
        $relativePath = 'uploads/site/' . $filename;
        $targetPath = public_path($relativePath);

        File::ensureDirectoryExists(public_path('uploads/site'));

        $oldPath = Setting::where('key', $key)->value('value');
        if ($oldPath && str_starts_with($oldPath, 'uploads/site/')) {
            $oldFullPath = public_path($oldPath);
            if (File::exists($oldFullPath)) {
                File::delete($oldFullPath);
            }
        }

        if (File::exists($targetPath)) {
            File::delete($targetPath);
        }

        $file->move(public_path('uploads/site'), $filename);

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $relativePath]
        );
    }

    private function selectedMediaPath(Request $request, string $field): ?string
    {
        $path = $request->input($field);

        if (! $path) {
            return null;
        }

        return Media::where('file_path', $path)
            ->where('mime_type', 'like', 'image/%')
            ->value('file_path');
    }

    private function setSettingImagePath(string $key, string $relativePath): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $relativePath]);
    }

    public function testMail(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        try {
            $schoolName = app(\App\Support\SiteSettings::class)->get('site_name_en', config('app.name'));
            Mail::raw(
                "This is a test email from {$schoolName} mail settings.",
                function ($message) use ($request, $schoolName) {
                    $message->to($request->test_email)
                        ->subject($schoolName . ' — Mail Settings Test');
                }
            );
        } catch (Throwable $exception) {
            return back()->with('mail_error', 'Test email failed: ' . $exception->getMessage());
        }

        $mailer = config('mail.default');
        $message = $mailer === 'smtp'
            ? 'Test email sent through SMTP. If it is not in the inbox, check spam and confirm the SMTP account is allowed to send as the From address.'
            : "Test email handled by the {$mailer} mailer. This does not deliver to an inbox.";

        return back()->with('mail_success', $message);
    }
}
