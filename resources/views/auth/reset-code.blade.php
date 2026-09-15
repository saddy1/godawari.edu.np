<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enter Verification Code | {{ $siteSettings->localized("site_name", "School") }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-[#fdfbf7] min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <div class="w-14 h-14 bg-white rounded-2xl shadow-md flex items-center justify-center p-1.5 mx-auto mb-4">
            <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
        </div>
        <h1 class="text-2xl font-bold text-[#0b2415]">Enter Verification Code</h1>
        <p class="text-gray-500 text-sm mt-1">Enter the code from your email, or open the reset link in that email.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <p class="mb-5 break-all text-sm text-gray-600">Code sent to {{ $email }}</p>
        @if(session('status'))<p role="status" class="mb-4 text-sm text-green-700">{{ session('status') }}</p>@endif
        <form method="POST" action="{{ route('password.code.verify') }}" class="space-y-5">
            @csrf
            <label for="code" class="block text-sm font-bold text-gray-700">Verification Code</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus class="w-full rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 text-center text-2xl tracking-widest">
            @error('code')<p role="alert" class="text-sm text-red-600">{{ $message }}</p>@enderror
            <button type="submit" class="w-full rounded-xl bg-[#1a5632] py-4 font-bold text-white">Verify code &amp; continue</button>
        </form>
        <form method="POST" action="{{ route('password.email') }}" class="mt-5 text-center">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="text-sm font-bold text-[#1a5632]">Resend code and link</button>
            @error('email')<p role="alert" class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </form>
        <a href="{{ route('password.request') }}" class="mt-4 block text-center text-sm text-gray-500">Use a different email</a>
    </div>
</div>
</body>
</html>
