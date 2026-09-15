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
    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::sendResetLink($request->only('email'), function ($user, $token) {
                $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);
                $expires = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
                Mail::send(['html' => 'emails.password-reset', 'text' => 'emails.password-reset-text'], compact('resetUrl', 'expires'), function ($message) use ($user) {
                    $message->to($user->getEmailForPasswordReset())->subject('Reset your password');
                });
            });
        } catch (Throwable $exception) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Password reset email could not be sent: ' . $exception->getMessage()]);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
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

    public function sendCode(Request $request)
    {
        $identity = $this->identity($request);
        $key = $this->codeKey($identity);
        $limit = 'password-reset-send:'.hash('sha256', $identity['email']);
        if (RateLimiter::tooManyAttempts($limit, 1)) {
            return back()->withErrors(['code' => 'Please wait a minute before requesting another code.']);
        }
        RateLimiter::hit($limit, 60);
        $code = (string) random_int(100000, 999999);
        try {
            Mail::send(['html' => 'emails.password-reset', 'text' => 'emails.password-reset-text'], ['code' => $code], function ($message) use ($identity) {
                $message->to($identity['email'])->subject('Password reset verification code');
            });
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['code' => 'The code could not be sent. Please try again shortly.']);
        }
        Cache::put($key, Hash::make($code), now()->addMinutes(10));
        return back()->with('status', 'A verification code has been sent to your email. It expires in 10 minutes.');
    }

    public function update(Request $request)
    {
        $identity = $this->identity($request);
        $request->validate([
            'token' => ['required'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $key = $this->codeKey($identity);
        $attempts = $key.':attempts';
        if (RateLimiter::tooManyAttempts($attempts, 5)) {
            return back()->withErrors(['code' => 'Too many attempts. Please wait 10 minutes before trying again.']);
        }
        RateLimiter::hit($attempts, 600);
        $hash = Cache::get($key);
        if (!$hash || !Hash::check((string) $request->input('code'), $hash)) {
            return back()->withErrors(['code' => 'The verification code is incorrect or expired.']);
        }
        $status = Password::reset(
            array_merge($request->only('password', 'password_confirmation'), $identity),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Cache::forget($key);
            RateLimiter::clear($attempts);
            $request->session()->forget('password_reset_identity');
        }

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('applicant.login')->with('status', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
