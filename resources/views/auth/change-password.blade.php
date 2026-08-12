<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password | {{ $siteSettings->localized("site_name", "School") }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-[#fdfbf7] min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <a href="{{ route('home') }}" class="inline-block mb-4">
            <div class="w-14 h-14 bg-white rounded-2xl shadow-md flex items-center justify-center p-1.5 mx-auto">
                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
            </div>
        </a>
        <h1 class="text-2xl font-bold text-[#0b2415]">Change Password</h1>
        <p class="text-gray-500 text-sm mt-1">{{ auth()->user()->email }}</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        @if(session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm font-bold">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('account.password.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-bold text-gray-700 mb-2">Current Password</label>
                <input id="current_password" type="password" name="current_password" required
                    class="w-full px-5 py-3.5 bg-gray-50 border @error('current_password') border-red-400 @else border-gray-200 @enderror rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
                @error('current_password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-gray-700 mb-2">New Password</label>
                <input id="password" type="password" name="password" required
                    class="w-full px-5 py-3.5 bg-gray-50 border @error('password') border-red-400 @else border-gray-200 @enderror rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
                @error('password')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-bold text-gray-700 mb-2">Confirm New Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20 focus:border-[#1a5632] transition-all">
            </div>

            <button type="submit" class="w-full py-4 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] transition-all duration-300 text-base">
                Save Password
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            @if(auth()->user()?->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="text-[#1a5632] font-bold hover:underline">← Back to Dashboard</a>
            @elseif(session('student_id'))
                <a href="{{ route('student.dashboard') }}" class="text-[#1a5632] font-bold hover:underline">← Back to Student Portal</a>
            @elseif(auth()->user()?->isTeacher() && \App\Services\ModuleService::enabled('learning') && auth()->user()?->assignedLearningClasses()->exists())
                <a href="{{ route('admin.learning.dashboard') }}" class="text-[#1a5632] font-bold hover:underline">← Back to Learning</a>
            @elseif(\App\Services\ModuleService::enabled('hajiri'))
                <a href="{{ route('hajiri.home') }}" class="text-[#1a5632] font-bold hover:underline">← Back to Hajiri</a>
            @else
                <a href="{{ url('/') }}" class="text-[#1a5632] font-bold hover:underline">← Back to Home</a>
            @endif
        </p>
    </div>
</div>
</body>
</html>
