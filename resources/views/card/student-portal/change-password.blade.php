@extends('card.student-portal.layout')

@section('title', 'Change Password')

@section('content')
<div class="mx-auto max-w-md">

    <div class="mb-6">
        <h1 class="text-xl font-extrabold text-gray-950">Change Password</h1>
        <p class="text-sm font-semibold text-gray-400 mt-0.5">Update your portal login password</p>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('student.change-password.update') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-1.5">
                    Current Password
                </label>
                <input type="password" name="current_password" required
                       class="w-full rounded-xl border @error('current_password') border-red-400 @else border-gray-200 @enderror bg-gray-50 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/20 transition-all">
                @error('current_password')
                    <p class="mt-1.5 text-xs font-bold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-1.5">
                    New Password
                </label>
                <input type="password" name="password" required
                       class="w-full rounded-xl border @error('password') border-red-400 @else border-gray-200 @enderror bg-gray-50 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/20 transition-all">
                @error('password')
                    <p class="mt-1.5 text-xs font-bold text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs font-semibold text-gray-400">Minimum 8 characters</p>
            </div>

            <div>
                <label class="block text-xs font-black uppercase tracking-widest text-gray-500 mb-1.5">
                    Confirm New Password
                </label>
                <input type="password" name="password_confirmation" required
                       class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/20 transition-all">
            </div>

            <button type="submit"
                    class="w-full rounded-xl py-3.5 text-sm font-extrabold text-white transition-all hover:opacity-90"
                    style="background-color: var(--sp-primary, #1a5632);">
                Save New Password
            </button>
        </form>
    </div>

    <p class="text-center text-xs font-semibold text-gray-400 mt-5">
        Use your new password next time you log in.
    </p>
</div>
@endsection
