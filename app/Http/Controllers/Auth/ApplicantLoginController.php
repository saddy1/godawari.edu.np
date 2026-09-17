<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\ForgetsStaleIntendedUrl;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicantLoginController extends Controller
{
    use ForgetsStaleIntendedUrl;

    public function showLoginForm()
    {
        return view('auth.applicant-login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Prevent admin accounts from using the applicant login
            if ($user->isAdmin()) {
                Auth::logout();
                return back()->withErrors(['email' => 'Please use the admin login page.'])->onlyInput('email');
            }

            $this->forgetStaleAdminIntendedUrl($request, $user);

            return redirect()->intended(route('vacancies'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('vacancies');
    }
}
