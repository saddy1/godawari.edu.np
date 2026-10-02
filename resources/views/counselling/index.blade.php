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
    <div>
        <h1 class="text-2xl font-black text-gray-900">{{ $canManage ? 'Counselling Sessions' : 'My Counselling Sessions' }}</h1>
        <p class="text-sm font-semibold text-gray-500">{{ $canManage ? 'Book sessions, assign counsellors, and track status.' : 'Sessions assigned to you — open one to write a report.' }}</p>
    </div>

    @if(session('success'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">@foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach</div>@endif

    @if($canManage)
    <section x-data="{ open: false }" class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <button type="button" @click="open = !open" class="flex w-full items-center justify-between px-5 py-4 text-left">
            <h2 class="text-sm font-black text-gray-900">Book a counselling session</h2>
            <span class="text-xs font-black text-[#1a5632]" x-text="open ? 'Hide' : 'Book new →'"></span>
        </button>
        <form x-show="open" x-cloak method="POST" action="{{ route('admin.counselling.store') }}" class="grid gap-3 border-t border-gray-100 p-5 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            <div>
                <label class="mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500">Student</label>
                <select name="student_id" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15">
                    <option value="">Choose student</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}">{{ $student->full_name }}{{ $student->roll_number ? ' · '.$student->roll_number : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500">Counsellor</label>
                <select name="counsellor_id" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15">
                    <option value="">Choose counsellor</option>
                    @foreach($counsellors as $counsellor)
                        <option value="{{ $counsellor->id }}">{{ $counsellor->name }}</option>
                    @endforeach
                </select>
                @if($counsellors->isEmpty())<p class="mt-1 text-[10px] font-semibold text-amber-600">No one has the Counsellor role yet — assign it to a staff account first.</p>@endif
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500">Date &amp; time</label>
                <input type="datetime-local" name="scheduled_at" required class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15">
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500">Topic (optional)</label>
                <input type="text" name="topic" maxlength="255" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15">
            </div>
            <div class="sm:col-span-2 lg:col-span-4"><button class="rounded-xl bg-[#1a5632] px-5 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415]">Book session</button></div>
        </form>
    </section>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">{{ $sessions->total() }} session{{ $sessions->total() === 1 ? '' : 's' }}</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach(['' => 'All', 'requested' => 'Requested', 'scheduled' => 'Scheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                    <a href="{{ route('admin.counselling.index', array_filter(['status' => $value])) }}" class="rounded-full border px-3 py-1.5 text-[11px] font-extrabold {{ ($status ?: '') === $value ? 'border-[#1a5632] bg-[#1a5632] text-white' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">{{ $label }}</a>
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
                        <form method="POST" action="{{ route('admin.counselling.assign', $session) }}" class="flex shrink-0 flex-wrap items-center gap-1.5" onsubmit="return true">
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
