<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email | {{ $siteSettings->localized("site_name", "School") }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-[#fdfbf7] min-h-screen flex items-center justify-center p-4">

@php($mailDriver = config('mail.default'))

<div class="w-full max-w-md text-center">
    <div class="w-20 h-20 bg-[#1a5632]/10 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-[#1a5632]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-[#0b2415] mb-3">Verify Your Email Address</h1>
    <p class="text-gray-500 text-sm mb-6 leading-relaxed">
        Thanks for registering! Before you can apply for vacancies, please verify your email address by clicking the link we just sent to <strong>{{ auth()->user()->email }}</strong>.
    </p>

    @if(session('status') === 'verification-link-sent')
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm font-bold">
        @if($mailDriver === 'smtp')
            A new verification link has been sent to your email address.
        @else
            A new verification link was generated and saved to the Laravel log. Current mailer: {{ $mailDriver }}.
        @endif
    </div>
    @endif

    @if(session('mail_error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm font-bold text-left">
        {{ session('mail_error') }}
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                class="w-full py-3.5 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] transition-colors">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('applicant.logout') }}">
            @csrf
            <button type="submit"
                class="w-full py-3.5 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors">
                Log Out
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        <a href="{{ route('home') }}" class="hover:text-[#1a5632]">← Back to School Website</a>
    </p>
</div>

</body>
</html>
