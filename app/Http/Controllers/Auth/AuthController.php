<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle the login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['login'])
            ->orWhere('student_code', $credentials['login'])
            ->first();

        if (! $user) {
            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.',
            ])->onlyInput('login');
        }

        $loginCredentials = [
            'email' => $user->email,
            'password' => $credentials['password'],
        ];

        $authenticated = false;

        try {
            $authenticated = Auth::attempt($loginCredentials, $request->boolean('remember'));
        } catch (\RuntimeException) {
            // Stored hash uses a non-Bcrypt algorithm (e.g. MD5 from an old import).
            // Verify with MD5 and re-hash with Bcrypt on success.
            if ($user && md5($credentials['password']) === $user->password) {
                $user->password = Hash::make($credentials['password']);
                $user->save();
                Auth::login($user, $request->boolean('remember'));
                $authenticated = true;
            }
        }

        if (! $authenticated) {
            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.',
            ])->onlyInput('login');
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if ($user->isStudent()) {
            return redirect()->intended(route('learning.dashboard'));
        }

        if ($user->isTeacher()) {
            if (\App\Services\ModuleService::enabled('teaching_learning')) {
                return redirect()->intended(route('admin.teacher.workspace'));
            }

            if (\App\Services\ModuleService::enabled('learning') && $user->assignedLearningClasses()->exists()) {
                return redirect()->intended(route('admin.learning.dashboard'));
            }

            if (\App\Services\ModuleService::enabled('hajiri')) {
                return redirect()->intended(route('hajiri.home'));
            }
        }

        if ($user->canAccess([
            'learning.courses.create',
            'learning.lessons.create',
            'learning.resources.create',
            'learning.quizzes.create',
            'learning.reports.view',
        ])) {
            return redirect()->intended(route('admin.learning.dashboard'));
        }

        // Staff employee with a biometric device — send to the Hajiri portal
        if ($user->device_id) {
            return redirect()->route('hajiri.home');
        }

        // Admin-only accounts (no teacher/student/staff role of their own) land on
        // the admin dashboard. Checked last so a teacher/student/staff account that
        // has ALSO been given admin access still lands on their own dashboard by
        // default — they can reach the admin dashboard via the "Admin access" button.
        if ($user->isAdmin()) {
            return redirect()->intended('admin/dashboard');
        }

        // Not a staff member — applicants use the applicant login form
        Auth::logout();
        return back()->withErrors([
            'login' => 'Please use the Applicant Login page to access your account.',
        ])->onlyInput('login');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect back to the login page or home page
        return redirect()->route('login');
    }
}
