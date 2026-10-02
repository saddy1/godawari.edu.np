<?php

namespace App\Http\Middleware;

use App\Services\DeviceRememberService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Runs early in the global web stack, before any route's own `guest`/`auth`
// middleware, so a remembered device is already logged in by the time, e.g.,
// the guest-only /login route checks whether to show the form or redirect.
class AutoLoginFromDeviceToken
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guest()) {
            $user = app(DeviceRememberService::class)->resolve($request);

            if ($user) {
                Auth::login($user);
            }
        }

        return $next($request);
    }
}
