@php
    $school = $siteSettings->localized('site_name', 'Godawari College');
    $color = fn($key, $fallback) => preg_match('/^#[0-9a-fA-F]{6}$/', $siteSettings->get($key) ?? '') ? $siteSettings->get($key) : $fallback;
    $primary = $color('primary_color', '#0d5963');
    $accent = $color('secondary_color', '#f2a51a');
    $dark = $color('dark_color', '#071d2b');
    $isCode = isset($code);
    $heading = $isCode ? 'Verify your email' : 'Reset your password';
@endphp
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $heading }} | {{ $school }}</title></head>
<body style="margin:0;padding:0;background-color:#f3f5f7;color:#334155;font-family:Arial,Helvetica,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $isCode ? 'Your password reset verification code is ready. Valid for 10 minutes.' : 'Use this secure link to reset your account password.' }}</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f3f5f7;"><tr><td align="center" style="padding:32px 12px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;">
    <tr><td align="center" style="padding:30px 24px;background-color:{{ $dark }};border-bottom:5px solid {{ $accent }};">
        <table role="presentation" cellspacing="0" cellpadding="0"><tr><td style="padding:10px;background-color:#ffffff;border-radius:16px;"><img src="{{ $siteSettings->logoUrl() }}" width="72" height="72" alt="{{ $school }} logo" style="display:block;object-fit:contain;border:0;"></td></tr></table>
        <p style="margin:16px 0 0;color:#ffffff;font-size:21px;font-weight:bold;">{{ $school }}</p>
        <p style="margin:7px 0 0;color:{{ $accent }};font-size:11px;letter-spacing:2px;text-transform:uppercase;">Account security</p>
    </td></tr>
    <tr><td style="padding:32px 28px;">
        <h1 style="margin:0 0 16px;font-size:27px;line-height:1.25;color:{{ $dark }};">{{ $heading }}</h1>
        @if($isCode)
            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;">Enter the code below on the password reset page, then choose your new password.</p>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center" style="padding:24px 12px;background-color:#f0f7f8;border:1px solid #d5e7e9;border-radius:12px;">
                <p style="margin:0 0 12px;font-size:11px;font-weight:bold;letter-spacing:2px;color:{{ $primary }};">YOUR VERIFICATION CODE</p>
                <p style="margin:0;font-family:Consolas,monospace;font-size:36px;font-weight:bold;letter-spacing:6px;color:{{ $dark }};">{{ $code }}</p>
                <p style="margin:14px 0 0;font-size:12px;color:#64748b;">Valid for 10 minutes</p>
            </td></tr></table>
            <p style="margin:22px 0 0;font-size:13px;line-height:1.7;">Keep this code private. Our team will never ask you to share it.</p>
        @else
            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;">We received a request to reset your account password. Use the button below to get started.</p>
            <table role="presentation" cellspacing="0" cellpadding="0"><tr><td align="center" bgcolor="{{ $primary }}" style="border-radius:10px;"><a href="{{ $resetUrl }}" style="display:inline-block;padding:16px 26px;border:1px solid {{ $primary }};border-radius:10px;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;">Reset password &rarr;</a></td></tr></table>
            <p style="margin:20px 0 0;font-size:13px;line-height:1.7;">This link expires in {{ $expires }} minutes. On the reset page, request an email verification code to complete your password change.</p>
        @endif
        <p style="margin:24px 0 0;padding-top:20px;border-top:1px solid #e2e8f0;font-size:13px;line-height:1.7;color:#64748b;">If you didn’t request a password reset, you can ignore this email. Your password will stay the same.</p>
        @unless($isCode)
            <p style="margin:20px 0 6px;font-size:12px;color:#64748b;">Button not working? Copy this link into your browser:</p>
            <a href="{{ $resetUrl }}" style="font-size:11px;line-height:1.6;color:{{ $primary }};word-break:break-all;overflow-wrap:anywhere;">{{ $resetUrl }}</a>
        @endunless
    </td></tr>
    <tr><td align="center" style="padding:20px 24px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
        <p style="margin:0;font-size:12px;font-weight:bold;color:{{ $dark }};">{{ $school }}</p>
        <p style="margin:6px 0 0;font-size:11px;color:#64748b;">{{ $siteSettings->localized('site_address') }}</p>
    </td></tr>
</table>
<p style="margin:18px 0 0;font-size:11px;color:#94a3b8;">This is an automated account security email.</p>
</td></tr></table>
</body>
</html>
