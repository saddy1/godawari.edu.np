<?php

namespace App\Http\Middleware\Card;

use App\Models\Card\Student;
use Closure;
use Illuminate\Http\Request;

class StudentAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('student_id')) {
            return redirect()->route('student.login')
                ->with('error', 'Please login to continue.');
        }

        $student = Student::find(session('student_id'));

        if (!$student) {
            $request->session()->forget('student_id');

            return redirect()->route('student.login')
                ->with('error', 'Please login to continue.');
        }

        $hasPendingUpdate = $student->profile_completed_at === null
            && $student->updateRequests()->where('status', 'pending')->exists();

        $onboardingRoute = $request->routeIs('student.request-update', 'student.submit-update', 'student.logout');

        if ($student->profile_completed_at === null && ! $hasPendingUpdate && ! $onboardingRoute) {
            return redirect()->route('student.request-update')
                ->with('info', 'Please review your contact and address details first. Changes will be sent for approval.');
        }

        return $next($request);
    }
}
