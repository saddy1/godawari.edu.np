{{ $siteSettings->localized('site_name', 'Godawari College') }}
@if(isset($code))
Verify your email

Your password reset verification code: {{ $code }}
This code expires in 10 minutes. Enter it on the password reset page to change your password.
Keep this code private.
@else
Reset your password

Open this link to reset your password:
{{ $resetUrl }}

This link expires in {{ $expires }} minutes. Request an email verification code on the reset page to complete the change.
@endif

If you didn't request a password reset, ignore this email. Your password will stay the same.
