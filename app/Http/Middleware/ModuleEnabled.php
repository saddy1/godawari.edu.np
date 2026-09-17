<?php

namespace App\Http\Middleware;

use App\Services\ModuleService;
use Closure;
use Illuminate\Http\Request;

class ModuleEnabled
{
    public function handle(Request $request, Closure $next, string $moduleKey, string $context = 'admin'): mixed
    {
        if (!ModuleService::enabled($moduleKey)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'This module is not enabled.'], 403);
            }

            // Public-facing pages (e.g. the applicant portal when there's no
            // vacancy module) get a plain "not available" message instead of
            // the admin-oriented "ask admin for access" screen — there's no
            // permission to grant here, the feature just isn't in use.
            if ($context === 'public') {
                $copy = match ($moduleKey) {
                    'vacancy' => [
                        'title' => 'No Vacancy Published',
                        'message' => 'There are no open vacancies right now, so the applicant portal isn\'t available. You can only log in once a vacancy has been published.',
                    ],
                    'admissions' => [
                        'title' => 'Admissions Currently Closed',
                        'message' => 'Admissions are not open right now. Please check back later or contact the school office for more information.',
                    ],
                    default => [
                        'title' => 'Not Available',
                        'message' => 'This isn\'t available right now. Please check back later.',
                    ],
                };

                return response()->view('errors.module-unavailable', $copy, 403);
            }

            abort(403, "The '{$moduleKey}' module is not enabled for this installation.");
        }

        return $next($request);
    }
}
