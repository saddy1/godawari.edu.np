<?php

namespace Tests\Unit;

use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PasswordResetVerificationTest extends TestCase
{
    private function requestForReset(array $input = []): Request
    {
        $request = Request::create('/reset-password', 'POST', array_merge([
            'token' => 'reset-token', 'email' => 'tampered@example.com',
            'code' => '123456', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ], $input));
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('password_reset_identity', ['email' => 'owner@example.com', 'token' => 'reset-token']);
        return $request;
    }

    public function test_modified_token_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new PasswordResetController)->update($this->requestForReset(['token' => 'other-token']));
    }

    public function test_missing_code_cannot_reset_password(): void
    {
        Password::shouldReceive('reset')->never();
        $request = $this->requestForReset();
        $request->session()->put('password_reset_pending', $request->session()->pull('password_reset_identity'));
        (new PasswordResetController)->verifyCode($request);
        $this->assertNotEmpty(session('errors')->get('code'));
    }

    public function test_reset_link_allows_password_change_without_code(): void
    {
        Password::shouldReceive('getUser')->with(['email' => 'owner@example.com'])->once()->andReturn((object) []);
        Password::shouldReceive('tokenExists')->once()->andReturn(true);
        Password::shouldReceive('reset')->once()->withArgs(function ($credentials, $callback) {
            return $credentials['email'] === 'owner@example.com' && $credentials['token'] === 'reset-token';
        })->andReturn(Password::PASSWORD_RESET);
        $key = 'password-reset-code:'.hash('sha256', 'owner@example.com|reset-token');
        Cache::put($key, Hash::make('123456'), 600);
        $request = $this->requestForReset();
        $request->request->remove('code');
        (new PasswordResetController)->update($request);
        $this->assertNull(Cache::get($key));
        $this->assertNull(session('password_reset_identity'));
    }

    public function test_verified_code_opens_password_form_and_is_consumed(): void
    {
        $request = $this->requestForReset();
        $identity = $request->session()->pull('password_reset_identity');
        $request->session()->put('password_reset_pending', $identity);
        $key = 'password-reset-code:'.hash('sha256', 'owner@example.com|reset-token');
        Cache::put($key, Hash::make('123456'), 600);
        Password::shouldReceive('getUser')->once()->andReturn((object) []);
        Password::shouldReceive('tokenExists')->once()->andReturn(true);
        $response = (new PasswordResetController)->verifyCode($request);
        $this->assertSame(route('password.reset', $identity), $response->getTargetUrl());
        $this->assertNull(Cache::get($key));
        $this->assertNull(session('password_reset_pending'));
        $this->assertSame($identity, session('password_reset_identity'));
    }

    public function test_pending_code_request_does_not_authorize_password_change(): void
    {
        $request = $this->requestForReset();
        $request->session()->put('password_reset_pending', $request->session()->pull('password_reset_identity'));
        $this->expectException(ValidationException::class);
        (new PasswordResetController)->update($request);
    }
}
