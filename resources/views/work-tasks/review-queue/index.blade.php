@extends('work-tasks.layout')

@section('title', 'Review Queue')

@section('content')
<div class="space-y-5">
    <div class="rounded-2xl bg-[#0b2415] p-5 sm:p-6 text-white shadow-sm">
        <p class="text-sm font-bold uppercase tracking-widest text-white/50">Work Review</p>
        <h1 class="mt-1 text-3xl font-extrabold">Review Queue</h1>
        <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">Submissions waiting for principal/admin scoring.</p>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-base font-extrabold text-gray-950">Pending Submissions</h2>
            <p class="text-xs font-semibold text-gray-500">Open a task to approve, reject, score, and calculate payout.</p>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($submissions as $submission)
                <a href="{{ route('admin.work-tasks.show', $submission->task) }}" class="grid gap-3 px-4 py-3 hover:bg-gray-50 sm:grid-cols-[1fr_180px_160px] sm:items-center">
                    <div>
                        <p class="text-sm font-extrabold text-gray-950">{{ $submission->task?->title }}</p>
                        <p class="mt-1 text-xs font-semibold text-gray-500">
                            {{ $submission->user?->name ?? $submission->group?->name ?? 'Shared group submission' }}
                            · submitted by {{ $submission->submittedBy?->name ?: 'Unknown' }}
                        </p>
                    </div>
                    <div class="text-xs font-semibold text-gray-500">
                        Due {{ $submission->task?->due_date?->format('M d, Y') }}
                    </div>
                    <div class="text-xs font-bold text-[#1a5632] sm:text-right">
                        {{ $submission->submitted_at?->diffForHumans() }}
                    </div>
                </a>
            @empty
                <p class="p-5 text-sm font-semibold text-gray-500">No submissions waiting for review.</p>
            @endforelse
        </div>
        <div class="border-t border-gray-100 px-5 py-4">{{ $submissions->links() }}</div>
    </div>
</div>
@endsection
