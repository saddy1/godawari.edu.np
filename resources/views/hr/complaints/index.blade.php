@extends('hr.layouts.app')
@section('title', 'Feedback & Complaints')
@section('content')
@php
    $statusStyles = [
        'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
        'in_review' => 'bg-blue-50 text-blue-700 border-blue-100',
        'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'rejected' => 'bg-red-50 text-red-700 border-red-100',
    ];
    $typeLabels = ['student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff'];
@endphp
<div class="space-y-6">
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm">
        <p class="text-xs font-extrabold uppercase tracking-widest text-white/50">People</p>
        <h1 class="mt-1 text-2xl font-extrabold">Feedback &amp; Complaints</h1>
        <p class="mt-1 text-sm font-medium text-white/65">Submitted by students, teachers and staff — respond here and they'll see your reply and the status.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'in_review' => 'In Review', 'resolved' => 'Resolved', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('admin.hr.complaints.index', array_filter(array_merge(request()->query(), ['status' => $key === 'all' ? null : $key]))) }}"
               class="rounded-2xl border {{ (request('status') ?: 'all') === $key ? 'border-[#1a5632] bg-emerald-50' : 'border-gray-200 bg-white' }} p-4 text-center shadow-sm">
                <p class="text-2xl font-black text-gray-950">{{ $counts[$key] }}</p>
                <p class="mt-1 text-[10px] font-extrabold uppercase tracking-wide text-gray-400">{{ $label }}</p>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.hr.complaints.index') }}" class="flex flex-wrap items-center gap-2 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <input name="search" value="{{ request('search') }}" placeholder="Search subject or name" class="min-w-0 flex-1 rounded-xl border border-gray-300 px-3 py-2 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
        <select name="category" onchange="this.form.submit()" class="rounded-xl border border-gray-300 px-3 py-2 text-xs font-bold text-gray-700">
            <option value="">All sections</option>
            @foreach(\App\Models\Complaint::CATEGORIES as $value => $label)
                <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="member_type" onchange="this.form.submit()" class="rounded-xl border border-gray-300 px-3 py-2 text-xs font-bold text-gray-700">
            <option value="">Everyone</option>
            @foreach($typeLabels as $value => $label)
                <option value="{{ $value }}" @selected(request('member_type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-[#1a5632] px-4 py-2 text-xs font-extrabold text-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="divide-y divide-gray-100">
            @forelse($complaints as $complaint)
                <a href="{{ route('admin.hr.complaints.show', $complaint) }}" class="flex flex-wrap items-center justify-between gap-3 p-4 hover:bg-gray-50/70">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-extrabold text-gray-600">{{ $complaint->category_label }}</span>
                            <span class="rounded-full border px-2.5 py-1 text-[10px] font-extrabold {{ $statusStyles[$complaint->status] ?? '' }}">{{ $complaint->status_label }}</span>
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold text-blue-700">{{ $typeLabels[$complaint->student->member_type] ?? ucfirst($complaint->student->member_type) }}</span>
                        </div>
                        <p class="mt-1.5 truncate text-sm font-extrabold text-gray-950">{{ $complaint->subject }}</p>
                        <p class="mt-0.5 text-xs font-semibold text-gray-400">{{ $complaint->student->full_name }} · {{ $complaint->created_at->diffForHumans() }}</p>
                    </div>
                    <svg class="h-4 w-4 shrink-0 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @empty
                <div class="p-10 text-center">
                    <p class="text-sm font-extrabold text-gray-900">No feedback or complaints found</p>
                    <p class="mt-1 text-sm text-gray-500">Change the filters and try again.</p>
                </div>
            @endforelse
        </div>
        <div class="border-t border-gray-100 px-5 py-4">{{ $complaints->links() }}</div>
    </div>
</div>
@endsection
