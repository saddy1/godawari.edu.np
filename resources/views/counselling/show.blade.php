@extends('counselling.layout')
@section('title', 'Counselling Session')
@section('content')
<div class="max-w-2xl space-y-4">
    <a href="{{ route('admin.counselling.index') }}" class="inline-flex items-center gap-1.5 text-xs font-extrabold text-gray-500 hover:text-gray-700">← Back to sessions</a>

    @if(session('success'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">@foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach</div>@endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-[10px] font-black uppercase tracking-widest text-[#1a5632]">{{ ucfirst($counsellingSession->status) }}</p>
        <h1 class="mt-1 text-xl font-black text-gray-900">{{ $counsellingSession->student->full_name }}</h1>
        <dl class="mt-3 grid gap-3 text-xs sm:grid-cols-2">
            <div><dt class="font-bold uppercase tracking-wider text-gray-400">Topic</dt><dd class="mt-0.5 font-semibold text-gray-700">{{ $counsellingSession->topic ?: '—' }}</dd></div>
            <div><dt class="font-bold uppercase tracking-wider text-gray-400">Counsellor</dt><dd class="mt-0.5 font-semibold text-gray-700">{{ $counsellingSession->counsellor->name ?? 'Not yet assigned' }}</dd></div>
            <div><dt class="font-bold uppercase tracking-wider text-gray-400">Scheduled</dt><dd class="mt-0.5 font-semibold text-gray-700">{{ $counsellingSession->scheduled_at?->format('d M Y, h:i A') ?: '—' }}</dd></div>
            <div><dt class="font-bold uppercase tracking-wider text-gray-400">Requested by</dt><dd class="mt-0.5 font-semibold text-gray-700">{{ $counsellingSession->requestedBy->name ?? 'Booked by admin' }}</dd></div>
        </dl>
    </section>

    @if($canViewReport)
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50/30 p-5 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-700">Private report</p>
            <p class="mb-3 text-[10px] font-semibold text-emerald-600">Visible only to you and Super Admin — the student and other staff cannot see this.</p>
            <form method="POST" action="{{ route('admin.counselling.report.save', $counsellingSession) }}">
                @csrf @method('PUT')
                <textarea name="report" rows="6" required maxlength="5000" placeholder="What was discussed, observations, recommendations…" class="w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15">{{ old('report', $counsellingSession->report) }}</textarea>
                <button class="mt-3 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-extrabold text-white hover:bg-emerald-800">Save report</button>
            </form>
        </section>
    @else
        <section class="rounded-2xl border border-gray-200 bg-gray-50 p-5 text-center text-xs font-semibold text-gray-400">
            The counselling report for this session is private.
        </section>
    @endif
</div>
@endsection
