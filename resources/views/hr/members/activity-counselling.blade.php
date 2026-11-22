@extends('hr.layouts.app')
@section('title', $member->full_name.' — Counselling')
@section('content')
@php
    $statusStyles = [
        'completed' => 'bg-emerald-50 text-emerald-700',
        'scheduled' => 'bg-blue-50 text-blue-700',
        'cancelled' => 'bg-red-50 text-red-700',
    ];
@endphp
<div class="space-y-4">
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm">
        <div class="flex items-center gap-4">
            <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="h-14 w-14 rounded-xl object-cover ring-2 ring-white/20">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-widest text-white/50">Student 360 · Counselling</p>
                <h1 class="truncate text-xl font-extrabold">{{ $member->full_name }}</h1>
                <p class="text-xs font-semibold text-white/60">{{ $member->roll_number }} · {{ $member->stream }}{{ $member->section ? ' - '.$member->section : '' }}</p>
            </div>
        </div>
    </div>

    @include('hr.members._activity-tabs')

    @if($sessions->isEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-sm">
            <p class="text-sm font-bold text-gray-600">No counselling sessions recorded</p>
            <p class="mt-1 text-xs font-semibold text-gray-400">This student has no counselling history on file.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($sessions as $session)
                <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-extrabold text-gray-950">{{ $session->topic ?: 'Counselling session' }}</p>
                            <p class="mt-1 text-xs font-semibold text-gray-400">
                                Counsellor: {{ $session->counsellor?->name ?? '—' }}
                                @if($session->scheduled_at) · Scheduled {{ $session->scheduled_at->format('d M Y, h:i A') }} @endif
                                @if($session->completed_at) · Completed {{ $session->completed_at->format('d M Y') }} @endif
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $statusStyles[$session->status] ?? 'bg-gray-100 text-gray-500' }}">{{ ucfirst($session->status) }}</span>
                    </div>

                    @if($session->canViewReport($viewer))
                        @if($session->report)
                            <div class="mt-3 rounded-xl border border-gray-100 bg-gray-50 p-3">
                                <p class="text-[10px] font-extrabold uppercase tracking-wide text-gray-400">Report</p>
                                <p class="mt-1 whitespace-pre-line text-sm font-medium text-gray-700">{{ $session->report }}</p>
                            </div>
                        @endif
                    @else
                        <p class="mt-3 text-xs font-semibold text-gray-400">The session report is only visible to the assigned counsellor and super admins.</p>
                    @endif
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
