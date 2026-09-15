<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password | {{ $siteSettings->localized("site_name", "School") }}</title>
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
        <h1 class="text-2xl font-bold text-[#0b2415]">Set New Password</h1>
        <p class="text-gray-500 text-sm mt-1">Request an email code, then enter it below to set your new password.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        @if(session('status'))<p role="status" class="mb-4 rounded-xl bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</p>@endif
        <form method="POST" action="{{ route('password.code') }}" class="mb-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <p class="text-sm font-bold text-gray-700">Email Address</p>
            <p class="mt-2 break-all rounded-xl bg-gray-50 p-3 text-sm">{{ $email }}</p>
            <button type="submit" class="mt-3 rounded-xl border border-green-700 px-4 py-2 text-sm font-bold text-green-800">Send verification code</button>
        </form>
        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="code" class="block text-sm font-bold text-gray-700 mb-2">Verification Code</label>
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm">
                @error('code')<p role="alert" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('email')<p role="alert" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-gray-700 mb-2">New Password</label>
                <input id="password" type="password" name="password" autocomplete="new-password" required
                    class="w-full px-5 py-3.5 bg-gray-50 border @error('password') border-red-400 @else border-gray-200 @enderror rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
                @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-bold text-gray-700 mb-2">Confirm New Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
            </div>

            <button type="submit" class="w-full py-4 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] transition-all duration-300 text-base">
                Update Password
            </button>
        </form>
    </div>
</div>
</body>
</html>
