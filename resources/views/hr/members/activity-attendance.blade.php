@extends('hr.layouts.app')
@section('title', $member->full_name.' — Attendance')
@section('content')
<div class="space-y-4">
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm">
        <div class="flex items-center gap-4">
            <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="h-14 w-14 rounded-xl object-cover ring-2 ring-white/20">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-widest text-white/50">Student 360 · Attendance</p>
                <h1 class="truncate text-xl font-extrabold">{{ $member->full_name }}</h1>
                <p class="text-xs font-semibold text-white/60">{{ $member->roll_number }} · {{ $member->stream }}{{ $member->section ? ' - '.$member->section : '' }}</p>
            </div>
        </div>
    </div>

    @include('hr.members._activity-tabs')

    @include('partials.attendance-year-calendar', [
        'yearFormAction' => route('admin.hr.members.activity.attendance', $member),
        'eyebrow' => 'Student 360',
        'heading' => 'Attendance',
    ])
</div>
@endsection
