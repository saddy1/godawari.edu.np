<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $defaultLocale = 'en';

        try {
            $defaultLocale = app(SiteSettings::class)->get('default_locale', config('app.locale', 'en')) ?: 'en';
        } catch (\Throwable) {
            $defaultLocale = config('app.locale', 'en');
        }

        $locale = session('locale', $request->cookie('locale', $defaultLocale));

        if (! in_array($locale, ['en', 'ne'], true)) {
            $locale = 'en';
        }

        session(['locale' => $locale]);
        App::setLocale($locale);

        return $next($request);
    }
}
