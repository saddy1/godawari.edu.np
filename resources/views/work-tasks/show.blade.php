@extends('work-tasks.layout')

@section('title', 'Work Task')

@section('content')
@php
    $input = 'w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
    $canSubmit = auth()->user()?->canAccess('work-tasks.submit') && $task->isAssignedTo(auth()->user());
    $canReview = auth()->user()?->canAccess('work-tasks.review');
    $targetName = $task->assignment_type === 'group' ? $task->group?->name : $task->assignedUser?->name;
@endphp

<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.work-tasks.index') }}" class="text-sm font-extrabold text-[#1a5632]">Back to work tasks</a>
            <h1 class="mt-2 text-3xl font-black text-gray-950">{{ $task->title }}</h1>
            <p class="mt-1 text-sm font-semibold text-gray-500">{{ $targetName }} · Due {{ $task->due_date->format('M d, Y') }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-right shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Incentive</p>
            <p class="text-2xl font-black text-gray-950">Rs. {{ number_format((float) $task->incentive_amount, 0) }}</p>
            <p class="text-xs font-semibold text-gray-500">{{ $task->max_score }} max score</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="text-base font-extrabold text-gray-950">Task Details</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-gray-50 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Category</p>
                    <p class="mt-1 text-sm font-extrabold text-gray-900">{{ $task->category ?: 'General' }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Assignment</p>
                    <p class="mt-1 text-sm font-extrabold text-gray-900">{{ ucfirst($task->assignment_type) }}{{ $task->group_submission_mode ? ' · '.ucfirst($task->group_submission_mode) : '' }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Late Penalty</p>
                    <p class="mt-1 text-sm font-extrabold text-gray-900">{{ number_format((float) $task->late_penalty_percent, 2) }}%</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Assigned By</p>
                    <p class="mt-1 text-sm font-extrabold text-gray-900">{{ $task->creator?->name ?: 'System' }}</p>
                </div>
            </div>
            <div class="mt-4 rounded-xl bg-gray-50 p-4 text-sm font-medium leading-6 text-gray-700">
                {{ $task->description ?: 'No extra description added.' }}
            </div>
        </div>

        @if($canSubmit)
        <form method="POST" action="{{ route('admin.work-tasks.submit', $task) }}" enctype="multipart/form-data" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            @csrf
            <h2 class="text-base font-extrabold text-gray-950">Submit Evidence</h2>
            <div class="mt-4 space-y-3">
                <textarea name="comment" rows="4" placeholder="Comment or summary" class="{{ $input }}"></textarea>
                <input type="url" name="evidence_link" placeholder="Evidence link" class="{{ $input }}">
                <input type="file" name="evidence_file" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#1a5632] file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-white">
                <button class="w-full rounded-xl bg-[#1a5632] px-4 py-2.5 text-sm font-extrabold text-white">Submit for Review</button>
            </div>
        </form>
        @endif
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-base font-extrabold text-gray-950">Submissions</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($task->submissions as $submission)
            @php
                $late = $submission->submitted_at && $submission->submitted_at->toDateString() > $task->due_date->toDateString();
            @endphp
            <div class="grid gap-4 p-4 lg:grid-cols-[1fr_340px]">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-extrabold text-gray-950">{{ $submission->user?->name ?? $submission->group?->name ?? 'Shared group submission' }}</p>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-extrabold uppercase {{ $submission->status === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($submission->status === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">{{ $submission->status }}</span>
                        @if($late)<span class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-extrabold uppercase text-red-700">Late</span>@endif
                    </div>
                    <p class="mt-1 text-xs font-semibold text-gray-500">Submitted by {{ $submission->submittedBy?->name ?: 'Unknown' }} · {{ $submission->submitted_at?->format('M d, Y h:i A') }}</p>
                    <p class="mt-3 rounded-xl bg-gray-50 p-3 text-sm font-medium leading-6 text-gray-700">{{ $submission->comment ?: 'No comment added.' }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($submission->evidence_link)
                            <a href="{{ $submission->evidence_link }}" target="_blank" class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-extrabold text-blue-700">Open Link</a>
                        @endif
                        @if($submission->evidence_url)
                            <a href="{{ $submission->evidence_url }}" target="_blank" class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-extrabold text-emerald-700">Open File</a>
                        @endif
                    </div>
                    @if($submission->reviewed_at)
                        <div class="mt-3 rounded-xl border border-gray-100 p-3">
                            <p class="text-xs font-extrabold text-gray-500">Review by {{ $submission->reviewer?->name ?: 'Reviewer' }} · Score {{ $submission->score ?? 0 }}/{{ $task->max_score }} · Payout Rs. {{ number_format((float) $submission->payout_amount, 2) }}</p>
                            <p class="mt-1 text-sm font-medium text-gray-600">{{ $submission->review_note ?: 'No review note.' }}</p>
                        </div>
                    @endif
                </div>
                @if($canReview && $submission->status === 'submitted')
                <form method="POST" action="{{ route('admin.work-tasks.review', [$task, $submission]) }}" class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                    @csrf
                    <p class="text-sm font-extrabold text-gray-950">Review Submission</p>
                    <div class="mt-3 space-y-2">
                        <input type="number" name="score" min="0" max="{{ $task->max_score }}" placeholder="Score out of {{ $task->max_score }}" class="{{ $input }}">
                        <textarea name="review_note" rows="3" placeholder="Review note" class="{{ $input }}"></textarea>
                        <div class="grid grid-cols-2 gap-2">
                            <button name="status" value="approved" class="rounded-xl bg-[#1a5632] px-3 py-2 text-xs font-extrabold text-white">Approve</button>
                            <button name="status" value="rejected" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-extrabold text-red-700">Reject</button>
                        </div>
                    </div>
                </form>
                @endif
            </div>
            @empty
                <p class="p-5 text-sm font-semibold text-gray-500">No submissions yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
