{{ $siteSettings->localized('site_name', 'Godawari College') }}
@if(isset($code))
Verify your email

Your password reset verification code: {{ $code }}
This code expires in 10 minutes. Enter it on the verification page to continue to your new password.
Keep this code private.

Or open this link to change your password directly, without a code:
{{ $resetUrl }}
This link expires in {{ $expires }} minutes.
@else
Reset your password

Open this link to reset your password:
{{ $resetUrl }}

This link expires in {{ $expires }} minutes. No code is needed when using this link.
@endif

If you didn't request a password reset, ignore this email. Your password will stay the same.
