<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Account | {{ $siteSettings->localized("site_name", "School") }} Jobs</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-[#fdfbf7] min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-lg">
    {{-- Logo --}}
    <div class="text-center mb-8">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-3 mb-4">
            <div class="w-14 h-14 bg-white rounded-2xl shadow-md flex items-center justify-center p-1.5">
                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
            </div>
        </a>
        <h1 class="text-2xl font-bold text-[#0b2415]">Create Job Portal Account</h1>
        <p class="text-gray-500 text-sm mt-1">Register to apply for vacancies at {{ $siteSettings->localized("site_name", "School") }}</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">

        @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">
            <ul class="space-y-1">@foreach($errors->all() as $error)<li>• {{ $error }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-bold text-gray-700 mb-2">Full Name *</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                    placeholder="e.g. Ram Prasad Sharma"
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
            </div>

            <div>
                <label for="email" class="block text-sm font-bold text-gray-700 mb-2">Email Address *</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                    placeholder="your@email.com"
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
                <p class="text-xs text-gray-400 mt-1.5">A verification link will be sent to this address.</p>
            </div>

            <div>
                <label for="phone" class="block text-sm font-bold text-gray-700 mb-2">Phone Number *</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required
                    placeholder="98XXXXXXXX"
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-gray-700 mb-2">Password *</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                    placeholder="Minimum 8 characters"
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-bold text-gray-700 mb-2">Confirm Password *</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    placeholder="Re-enter your password"
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
            </div>

            <button type="submit"
                class="w-full py-4 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] hover:shadow-lg transition-all duration-300 text-base flex items-center justify-center gap-2 mt-2">
                Create Account & Verify Email
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>

        {{-- Google Login --}}
        @if(config('services.google.client_id'))
        <div class="mt-6">
            <div class="relative flex items-center">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="mx-4 text-xs text-gray-400 font-medium">OR</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>
            <a href="{{ route('auth.google') }}"
                class="mt-4 w-full flex items-center justify-center gap-3 px-5 py-3.5 border border-gray-200 rounded-xl bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all">
                <svg class="w-5 h-5" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.34-8.16 2.34-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                Continue with Google
            </a>
        </div>
        @endif

        <p class="text-center text-sm text-gray-500 mt-6">
            Already have an account?
            <a href="{{ route('applicant.login') }}" class="text-[#1a5632] font-bold hover:underline">Sign in</a>
        </p>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        <a href="{{ route('home') }}" class="hover:text-[#1a5632]">← Back to School Website</a>
    </p>
</div>

</body>
</html>
