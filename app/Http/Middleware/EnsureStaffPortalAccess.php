<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffPortalAccess
{
    // Blocks the Hajiri/staff portal for accounts with no HR (teacher/staff)
    // profile and no legacy device_id — e.g. a plain applicant account created
    // only to apply for a vacancy. Being logged in is not enough on its own.
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->isStaffPortalEligible()) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
