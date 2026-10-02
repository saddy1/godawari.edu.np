@extends('counselling.layout')
@section('title', 'Counselling Sessions')
@section('content')
@php
    $statusStyles = [
        'requested' => 'bg-amber-50 text-amber-700 border-amber-100',
        'scheduled' => 'bg-blue-50 text-blue-700 border-blue-100',
        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'cancelled' => 'bg-gray-100 text-gray-500 border-gray-200',
    ];
@endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-gray-900">{{ $canManage ? 'Counselling Sessions' : 'My Counselling Sessions' }}</h1>
            <p class="text-sm font-semibold text-gray-500">{{ $canManage ? 'Assign counsellors and track status.' : 'Sessions assigned to you — open one to write a report.' }}</p>
        </div>
        <a href="{{ route('admin.counselling.index') }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-black text-gray-700 hover:border-[#1a5632]/40 hover:text-[#1a5632]">← Dashboard</a>
    </div>

    @if(session('success'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">@foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach</div>@endif

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">{{ $sessions->total() }} session{{ $sessions->total() === 1 ? '' : 's' }}</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach(['' => 'All', 'requested' => 'Requested', 'scheduled' => 'Scheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                    <a href="{{ route('admin.counselling.sessions', array_filter(['status' => $value])) }}" class="rounded-full border px-3 py-1.5 text-[11px] font-extrabold {{ ($status ?: '') === $value ? 'border-[#1a5632] bg-[#1a5632] text-white' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($sessions as $session)
                <div class="flex flex-wrap items-center gap-3 p-4 sm:p-5">
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.counselling.show', $session) }}" class="font-extrabold text-gray-900 hover:underline">{{ $session->student->full_name }}</a>
                        <p class="text-xs font-semibold text-gray-400">
                            {{ $session->topic ?: 'No topic given' }}
                            @if($session->counsellor) · Counsellor: {{ $session->counsellor->name }} @endif
                            @if($session->scheduled_at) · {{ $session->scheduled_at->format('d M Y, h:i A') }} @endif
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full border px-3 py-1 text-[10px] font-black uppercase {{ $statusStyles[$session->status] }}">{{ $session->status }}</span>

                    @if($canManage && $session->status === 'requested')
                        <form method="POST" action="{{ route('admin.counselling.assign', $session) }}" class="flex shrink-0 flex-wrap items-center gap-1.5">
                            @csrf @method('PATCH')
                            <select name="counsellor_id" required class="rounded-lg border border-gray-300 px-2 py-1.5 text-[11px] font-bold">
                                <option value="">Counsellor</option>
                                @foreach($counsellors as $counsellor)<option value="{{ $counsellor->id }}">{{ $counsellor->name }}</option>@endforeach
                            </select>
                            <input type="datetime-local" name="scheduled_at" required class="rounded-lg border border-gray-300 px-2 py-1.5 text-[11px] font-bold">
                            <button class="rounded-lg bg-[#1a5632] px-3 py-1.5 text-[11px] font-black text-white">Assign</button>
                        </form>
                    @endif
                    @if($canManage && in_array($session->status, ['requested', 'scheduled']))
                        <form method="POST" action="{{ route('admin.counselling.cancel', $session) }}" onsubmit="return confirm('Cancel this counselling session?')">
                            @csrf @method('PATCH')
                            <button class="shrink-0 rounded-lg border border-red-200 px-3 py-1.5 text-[11px] font-black text-red-600 hover:bg-red-50">Cancel</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="p-10 text-center text-sm text-gray-400">No counselling sessions match this filter.</p>
            @endforelse
        </div>
        <div class="border-t border-gray-100 p-4">{{ $sessions->links() }}</div>
    </section>
</div>
@endsection
