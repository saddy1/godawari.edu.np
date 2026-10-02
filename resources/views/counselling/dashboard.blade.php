@extends('counselling.layout')
@section('title', 'Counselling Dashboard')
@section('content')
@php
    $statusStyles = [
        'requested' => 'bg-amber-50 text-amber-700 border-amber-100',
        'scheduled' => 'bg-blue-50 text-blue-700 border-blue-100',
        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'cancelled' => 'bg-gray-100 text-gray-500 border-gray-200',
    ];
@endphp
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-gray-900">{{ $canManage ? 'Counselling Dashboard' : 'My Counselling Dashboard' }}</h1>
            <p class="text-sm font-semibold text-gray-500">{{ $canManage ? 'Booked, counselled, and the full picture across every counsellor.' : 'Your own caseload at a glance.' }}</p>
        </div>
        <a href="{{ route('admin.counselling.sessions') }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-black text-gray-700 hover:border-[#1a5632]/40 hover:text-[#1a5632]">All sessions →</a>
    </div>

    @if(session('success'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">@foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach</div>@endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4"><p class="text-2xl font-black text-amber-700">{{ $stats['requested'] }}</p><p class="text-[10px] font-black uppercase tracking-wider text-amber-600">Requested</p></div>
        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4"><p class="text-2xl font-black text-blue-700">{{ $stats['scheduled'] }}</p><p class="text-[10px] font-black uppercase tracking-wider text-blue-600">Booked</p></div>
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4"><p class="text-2xl font-black text-emerald-700">{{ $stats['completed'] }}</p><p class="text-[10px] font-black uppercase tracking-wider text-emerald-600">Counselled</p></div>
        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4"><p class="text-2xl font-black text-gray-600">{{ $stats['cancelled'] }}</p><p class="text-[10px] font-black uppercase tracking-wider text-gray-500">Cancelled</p></div>
    </div>

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

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-3"><p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Review of counselling — by counsellor</p></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-[9px] font-black uppercase tracking-wider text-gray-400"><tr><th class="px-5 py-2.5">Counsellor</th><th class="px-5 py-2.5">Requested</th><th class="px-5 py-2.5">Booked</th><th class="px-5 py-2.5">Counselled</th></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($counsellorBreakdown as $row)
                        <tr>
                            <td class="px-5 py-3 font-extrabold text-gray-900">{{ $row->counsellor->name }}</td>
                            <td class="px-5 py-3 font-bold text-amber-700">{{ $row->requested }}</td>
                            <td class="px-5 py-3 font-bold text-blue-700">{{ $row->scheduled }}</td>
                            <td class="px-5 py-3 font-bold text-emerald-700">{{ $row->completed }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400">No one has the Counsellor role yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-3"><p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Recent activity</p></div>
        <div class="divide-y divide-gray-50">
            @forelse($recentSessions as $session)
                <a href="{{ route('admin.counselling.show', $session) }}" class="flex flex-wrap items-center gap-3 p-4 hover:bg-gray-50">
                    <div class="min-w-0 flex-1">
                        <p class="font-extrabold text-gray-900">{{ $session->student->full_name }}</p>
                        <p class="text-xs font-semibold text-gray-400">{{ $session->topic ?: 'No topic given' }}@if($session->counsellor) · {{ $session->counsellor->name }}@endif</p>
                    </div>
                    <span class="shrink-0 rounded-full border px-3 py-1 text-[10px] font-black uppercase {{ $statusStyles[$session->status] }}">{{ $session->status }}</span>
                </a>
            @empty
                <p class="p-10 text-center text-sm text-gray-400">No counselling activity yet.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
