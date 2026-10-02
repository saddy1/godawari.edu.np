<?php

namespace App\Services;

use App\Models\DeviceRememberToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

// "Remember this device" — a deliberately separate mechanism from Laravel's
// own session "remember me" cookie (which defaults to a ~5 year lifetime with
// no built-in way to bound it). This one is scoped to a fixed 30 days and is
// easy to reason about: one row per remembered browser, one cookie, one place
// that issues/checks/revokes it.
class DeviceRememberService
{
    public const COOKIE_NAME = 'device_token';
    public const DAYS = 30;

    public function remember(User $user, Request $request): void
    {
        $plainToken = Str::random(64);

        DeviceRememberToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'expires_at' => now()->addDays(self::DAYS),
        ]);

        Cookie::queue(Cookie::make(
            self::COOKIE_NAME,
            $plainToken,
            self::DAYS * 24 * 60,
            null,
            null,
            $request->secure(),
            true,
            false,
            'lax'
        ));
    }

    // Returns the user this device's cookie belongs to, or null if there's no
    // cookie, it's expired, or it doesn't match anything — never throws, a bad
    // or missing cookie just means "not remembered," same as having none.
    public function resolve(Request $request): ?User
    {
        $plainToken = $request->cookie(self::COOKIE_NAME);
        if (! $plainToken) {
            return null;
        }

        $record = DeviceRememberToken::with('user')
            ->where('token', hash('sha256', $plainToken))
            ->where('expires_at', '>', now())
            ->first();

        if (! $record || ! $record->user) {
            return null;
        }

        // Sliding expiry: a device in regular use never has to re-enter a
        // password, but one left idle for over a month will.
        $record->update(['expires_at' => now()->addDays(self::DAYS)]);

        return $record->user;
    }

    // Explicit logout must actually log the device out — otherwise the very
    // next page load would silently re-authenticate them right back in.
    public function forget(Request $request): void
    {
        $plainToken = $request->cookie(self::COOKIE_NAME);
        if ($plainToken) {
            DeviceRememberToken::where('token', hash('sha256', $plainToken))->delete();
        }
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }
}
