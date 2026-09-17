<?php

namespace App\Http\Controllers\Auth\Concerns;

use Illuminate\Http\Request;

trait ForgetsStaleIntendedUrl
{
    /**
     * All portals (staff, student, applicant) share one session. If a guest was
     * ever bounced off an /admin URL, Laravel stashes it in `url.intended` — and
     * a completely unrelated login on another portal would otherwise inherit
     * that stale admin URL via redirect()->intended(), landing non-admins on
     * a permission wall ("Ask admin for this access"). Drop it for anyone who
     * isn't actually an admin, so each portal falls back to its own home page.
     */
    protected function forgetStaleAdminIntendedUrl(Request $request, $user): void
    {
        if ($user?->isAdmin()) {
            return;
        }

        $intended = (string) $request->session()->get('url.intended');

        if (str_starts_with(parse_url($intended, PHP_URL_PATH) ?? '', '/admin')) {
            $request->session()->forget('url.intended');
        }
    }
}
