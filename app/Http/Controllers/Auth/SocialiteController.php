<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\ForgetsStaleIntendedUrl;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    use ForgetsStaleIntendedUrl;

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['google' => 'Google sign-in failed. Please try again.']);
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
            // Mark email as verified since Google already verified it
            if (!$user->email_verified_at) {
                $user->markEmailAsVerified();
            }
        } else {
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'password'          => bcrypt(\Str::random(32)),
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user);

        // Redirect admins to dashboard, applicants back to vacancies
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $this->forgetStaleAdminIntendedUrl(request(), $user);

        return redirect()->intended(route('vacancies'));
    }
}
