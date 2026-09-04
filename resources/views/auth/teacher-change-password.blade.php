@extends('teaching_learning.teacher-workspace.layout')

@section('title', 'Change password')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-5">
        <p class="text-[10px] font-black uppercase tracking-[.22em] text-emerald-700">My account</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Change password</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">Keep your teaching account secure with a password only you know.</p>
    </div>

    <div class="grid overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm md:grid-cols-[.75fr_1.25fr]">
        <section class="relative overflow-hidden p-6 text-white sm:p-8" style="background:linear-gradient(145deg,var(--teacher-dark),var(--teacher-primary))">
            <div class="relative z-10">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/15 text-2xl">⌁</span>
                <h2 class="mt-5 text-xl font-black">Password safety</h2>
                <p class="mt-2 text-sm leading-6 text-white/65">Use at least 8 characters and avoid passwords used for your email or social accounts.</p>
                <div class="mt-6 rounded-2xl border border-white/10 bg-white/10 p-4"><p class="text-[9px] font-black uppercase tracking-widest text-emerald-200">Signed in as</p><p class="mt-1 truncate text-sm font-bold">{{auth()->user()->email}}</p></div>
            </div>
            <div class="absolute -bottom-16 -right-16 h-52 w-52 rounded-full bg-emerald-300/10"></div>
        </section>

        <form method="POST" action="{{route('account.password.update')}}" class="space-y-5 p-5 sm:p-8">
            @csrf
            @method('PUT')
            <div><label for="current_password" class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-600">Current password</label><input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-xl border @error('current_password') border-red-400 @else border-slate-200 @enderror bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100">@error('current_password')<p class="mt-1.5 text-xs font-bold text-red-600">{{$message}}</p>@enderror</div>
            <div><label for="password" class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-600">New password</label><input id="password" type="password" name="password" required autocomplete="new-password" minlength="8" class="w-full rounded-xl border @error('password') border-red-400 @else border-slate-200 @enderror bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100">@error('password')<p class="mt-1.5 text-xs font-bold text-red-600">{{$message}}</p>@enderror</div>
            <div><label for="password_confirmation" class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-600">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" minlength="8" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"></div>
            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center sm:justify-end"><a href="{{route('admin.teacher.workspace')}}" class="rounded-xl px-5 py-3 text-center text-sm font-black text-slate-500 hover:bg-slate-100">Cancel</a><button type="submit" class="rounded-xl px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-900/15 transition hover:-translate-y-0.5" style="background:var(--teacher-primary)">Update password</button></div>
        </form>
    </div>
</div>
@endsection
