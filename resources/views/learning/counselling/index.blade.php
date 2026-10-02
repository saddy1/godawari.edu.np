@extends('learning.layouts.app')
@section('title', 'Counselling')
@section('content')
@php
    $statusStyles = [
        'requested' => 'bg-amber-50 text-amber-700 border-amber-100',
        'scheduled' => 'bg-blue-50 text-blue-700 border-blue-100',
        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'cancelled' => 'bg-gray-100 text-gray-500 border-gray-200',
    ];
@endphp
<div class="mb-5">
    <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Student Support</p>
    <h1 class="mt-1 text-2xl font-extrabold text-gray-950 sm:text-3xl">Counselling</h1>
    <p class="mt-1 text-sm text-gray-500">Request a session with a counsellor. What you discuss stays private between you, the counsellor and the school's super admin.</p>
</div>

@if(session('success'))<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>@endif

<section class="mb-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
    <h2 class="text-sm font-black text-gray-900">Request a counselling session</h2>
    <form method="POST" action="{{ route('learning.counselling.store') }}" class="mt-3 flex flex-col gap-3 sm:flex-row">
        @csrf
        <input type="text" name="topic" maxlength="255" placeholder="What would you like to talk about? (optional)" class="flex-1 rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-medium outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15">
        <button class="shrink-0 rounded-xl bg-[#1a5632] px-5 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415]">Send request</button>
    </form>
</section>

<section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="border-b border-gray-100 px-5 py-3"><p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Your requests</p></div>
    <div class="divide-y divide-gray-50">
        @forelse($sessions as $session)
            <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                <div>
                    <p class="font-bold text-gray-800">{{ $session->topic ?: 'General counselling' }}</p>
                    <p class="text-xs font-semibold text-gray-400">
                        Requested {{ $session->created_at->format('d M Y') }}
                        @if($session->counsellor) · {{ $session->counsellor->name }} @endif
                        @if($session->scheduled_at) · {{ $session->scheduled_at->format('d M Y, h:i A') }} @endif
                    </p>
                </div>
                <span class="shrink-0 rounded-full border px-3 py-1 text-[10px] font-black uppercase {{ $statusStyles[$session->status] }}">{{ $session->status }}</span>
            </div>
        @empty
            <p class="p-10 text-center text-sm text-gray-400">You haven't requested counselling yet.</p>
        @endforelse
    </div>
</section>
@endsection
