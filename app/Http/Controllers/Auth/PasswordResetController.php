<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class PasswordResetController extends Controller
{
    public function request(Request $request)
    {
        // Which login page linked here — used only to send the user back to
        // the right portal's login while they're in the reset flow.
        $portal = $request->query('portal');
        if (in_array($portal, ['login', 'student', 'applicant'], true)) {
            $request->session()->put('password_reset_portal', $portal);
        }

        return view('auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::sendResetLink($request->only('email'), function ($user, $token) use ($request) {
                $identity = ['email' => $user->getEmailForPasswordReset(), 'token' => $token];
                $code = (string) random_int(100000, 999999);
                $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);
                $expires = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
                Mail::send(['html' => 'emails.password-reset', 'text' => 'emails.password-reset-text'], compact('resetUrl', 'expires', 'code'), function ($message) use ($user) {
                    $message->to($user->getEmailForPasswordReset())->subject('Reset your password');
                });
                Cache::put($this->codeKey($identity), Hash::make($code), now()->addMinutes(10));
                $request->session()->forget('password_reset_identity');
                $request->session()->put('password_reset_pending', $identity);
            });
        } catch (Throwable $exception) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Password reset email could not be sent. Please try again shortly.']);
        }

        return $status === Password::RESET_LINK_SENT
            ? redirect()->route('password.code.form')->with('status', 'We sent you a verification code and a password reset link.')
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }

    public function reset(Request $request, string $token)
    {
        $email = (string) $request->query('email');
        $user = Password::getUser(['email' => $email]);
        if (!$user || !Password::tokenExists($user, $token)) {
            return redirect()->route('password.request')->withErrors(['email' => 'This reset link is invalid or expired. Please request a new one.']);
        }
        $request->session()->put('password_reset_identity', ['email' => $email, 'token' => $token]);
        return view('auth.reset-password', compact('token', 'email'));
    }

    private function identity(Request $request): array
    {
        $identity = $request->session()->get('password_reset_identity');
        if (!$identity || !hash_equals($identity['token'], (string) $request->input('token'))) {
            throw ValidationException::withMessages(['code' => 'Please reopen your password reset link.']);
        }
        $user = Password::getUser(['email' => $identity['email']]);
        if (!$user || !Password::tokenExists($user, $identity['token'])) {
            throw ValidationException::withMessages(['code' => 'This reset link is invalid or expired. Request a new link.']);
        }
        return $identity;
    }

    private function codeKey(array $identity): string
    {
        return 'password-reset-code:'.hash('sha256', $identity['email'].'|'.$identity['token']);
    }

    public function codeForm(Request $request)
    {
        $identity = $request->session()->get('password_reset_pending');
        if (!$identity) {
            return redirect()->route('password.request');
        }
        return view('auth.reset-code', ['email' => $identity['email']]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $identity = $request->session()->get('password_reset_pending');
        if (!$identity) {
            return redirect()->route('password.request');
        }
        $key = $this->codeKey($identity);
        $attempts = 'password-reset-attempts:'.hash('sha256', $identity['email']);
        if (RateLimiter::tooManyAttempts($attempts, 5)) {
            return back()->withErrors(['code' => 'Too many attempts. Please wait 10 minutes before trying again.']);
        }
        RateLimiter::hit($attempts, 600);
        $hash = Cache::get($key);
        if (!$hash || !Hash::check((string) $request->input('code'), $hash)) {
            return back()->withErrors(['code' => 'The verification code is incorrect or expired.']);
        }
        $user = Password::getUser(['email' => $identity['email']]);
        if (!$user || !Password::tokenExists($user, $identity['token'])) {
            return redirect()->route('password.request')->withErrors(['email' => 'This reset request has expired. Please request a new one.']);
        }
        Cache::forget($key);
        RateLimiter::clear($attempts);
        $request->session()->forget('password_reset_pending');
        $request->session()->put('password_reset_identity', $identity);
        return redirect()->route('password.reset', $identity);
    }

    public function update(Request $request)
    {
        $identity = $this->identity($request);
        $request->validate([
            'token' => ['required'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $resetUser = null;

        $status = Password::reset(
            array_merge($request->only('password', 'password_confirmation'), $identity),
            function ($user, string $password) use (&$resetUser) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                $resetUser = $user;
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Cache::forget($this->codeKey($identity));
            $request->session()->forget('password_reset_pending');
            $request->session()->forget('password_reset_identity');
            $request->session()->forget('password_reset_portal');
        }

        return $status === Password::PASSWORD_RESET
            ? redirect()->route($this->loginRouteFor($resetUser))->with('status', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }

    // Each portal (staff/admin, student, applicant) shares this one reset flow,
    // so route back to whichever login page actually matches the account.
    private function loginRouteFor($user): string
    {
        if (! $user) {
            return 'applicant.login';
        }

        if ($user->isStudent()) {
            return 'student.login';
        }

        if ($user->isAdmin() || $user->isTeacher() || $user->hasRole('staff') || filled($user->device_id)) {
            return 'login';
        }

        return 'applicant.login';
    }
}
