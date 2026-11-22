@extends('hr.layouts.app')
@section('title', 'Feedback & Complaint — '.$complaint->subject)
@section('content')
@php
    $statusStyles = [
        'pending' => 'bg-amber-50 text-amber-700',
        'in_review' => 'bg-blue-50 text-blue-700',
        'resolved' => 'bg-emerald-50 text-emerald-700',
        'rejected' => 'bg-red-50 text-red-700',
    ];
    $typeLabels = ['student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff'];
@endphp
<div class="mx-auto max-w-3xl space-y-4">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('admin.hr.complaints.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-700">← Back to all</a>
        <a href="{{ route('admin.hr.members.show', $complaint->student) }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-extrabold text-gray-700 hover:bg-gray-50">View Profile</a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-800">{{ session('success') }}</div>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-extrabold text-gray-600">{{ $complaint->category_label }}</span>
            <span class="rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $statusStyles[$complaint->status] ?? '' }}">{{ $complaint->status_label }}</span>
            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold text-blue-700">{{ $typeLabels[$complaint->student->member_type] ?? ucfirst($complaint->student->member_type) }}</span>
        </div>
        <h1 class="mt-3 text-xl font-extrabold text-gray-950">{{ $complaint->subject }}</h1>
        <p class="mt-1 text-xs font-semibold text-gray-400">
            From {{ $complaint->student->full_name }} ({{ $complaint->student->roll_number }}) · Submitted {{ $complaint->created_at->format('d M Y, h:i A') }}
        </p>
        <p class="mt-4 whitespace-pre-line text-sm font-medium leading-6 text-gray-700">{{ $complaint->message }}</p>
    </section>

    @if($complaint->admin_response)
        <section class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
            <p class="text-[10px] font-extrabold uppercase tracking-wide text-emerald-700">
                Current Response @if($complaint->respondedBy) · by {{ $complaint->respondedBy->name }} @endif @if($complaint->responded_at) · {{ $complaint->responded_at->format('d M Y, h:i A') }} @endif
            </p>
            <p class="mt-2 whitespace-pre-line text-sm font-medium leading-6 text-emerald-900">{{ $complaint->admin_response }}</p>
        </section>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-extrabold text-gray-950">{{ $complaint->admin_response ? 'Update Response' : 'Respond' }}</h2>
        <form method="POST" action="{{ route('admin.hr.complaints.respond', $complaint) }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Set Status</label>
                <select name="status" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                    @foreach(['in_review' => 'In Review', 'resolved' => 'Resolved', 'rejected' => 'Rejected'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $complaint->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Response</label>
                <textarea name="admin_response" required maxlength="3000" rows="4" placeholder="Write what action was taken or your reply" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">{{ old('admin_response', $complaint->admin_response) }}</textarea>
            </div>
            <button type="submit" class="rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415]">Send Response</button>
        </form>
    </section>
</div>
@endsection
