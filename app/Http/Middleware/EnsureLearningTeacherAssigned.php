<?php

namespace App\Http\Middleware;

use App\Services\ModuleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLearningTeacherAssigned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isScopedTeacher = $user?->isTeacher()
            && ! $user->isSuperAdmin()
            && ! $user->isPrincipal()
            && ! $user->hasRole('administrator');

        if (! $isScopedTeacher || $user->assignedLearningClasses()->exists()) {
            return $next($request);
        }

        $message = 'Learning access will appear after a class is allocated to your teacher account.';

        if ($request->expectsJson()) {
            abort(403, $message);
        }

        if (ModuleService::enabled('hajiri')) {
            return redirect()->route('hajiri.home')->with('message', $message);
        }

        abort(403, $message);
    }
}
