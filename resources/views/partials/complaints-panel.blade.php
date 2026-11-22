{{--
    Shared feedback/complaint panel — submit form + own history with status
    and admin response. Used by both the student portal and the teacher
    workspace so the two never drift apart.
    Expects: $complaints, $submitAction.
    Optional: $eyebrow (default 'Feedback & Complaints'), $heading (default 'Feedback & Complaints').
--}}
@php
    $eyebrow = $eyebrow ?? 'Feedback & Complaints';
    $heading = $heading ?? 'Feedback & Complaints';
    $statusStyles = [
        'pending' => 'bg-amber-50 text-amber-700',
        'in_review' => 'bg-blue-50 text-blue-700',
        'resolved' => 'bg-emerald-50 text-emerald-700',
        'rejected' => 'bg-red-50 text-red-700',
    ];
@endphp

<div class="space-y-6">
    <section class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm sm:p-6">
        <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-[#e2a024]">{{ $eyebrow }}</p>
        <h1 class="mt-2 text-2xl font-extrabold">{{ $heading }}</h1>
        <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-white/65">
            Choose a section, describe the issue, and submit. An admin will respond here — you'll see their reply and the status of your request.
        </p>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5 font-semibold">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-extrabold text-gray-950">Submit New Feedback / Complaint</h2>
        <form method="POST" action="{{ $submitAction }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Section</label>
                <select name="category" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    <option value="" disabled @selected(old('category') === null)>Choose a section</option>
                    @foreach(\App\Models\Complaint::CATEGORIES as $value => $label)
                        <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Subject</label>
                <input name="subject" value="{{ old('subject') }}" required maxlength="150" placeholder="Short summary of the issue" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Message</label>
                <textarea name="message" required maxlength="3000" rows="4" placeholder="Describe what happened and what you'd like done about it" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">{{ old('message') }}</textarea>
            </div>
            <button type="submit" class="rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415]">Submit</button>
        </form>
    </section>

    <section>
        <h2 class="mb-3 text-sm font-extrabold text-gray-950">Your History</h2>
        @if($complaints->isEmpty())
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                <p class="text-sm font-bold text-gray-600">Nothing submitted yet</p>
                <p class="mt-1 text-xs font-semibold text-gray-400">Anything you submit above will show up here with its status.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($complaints as $complaint)
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-extrabold text-gray-600">{{ $complaint->category_label }}</span>
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $statusStyles[$complaint->status] ?? 'bg-gray-100 text-gray-500' }}">{{ $complaint->status_label }}</span>
                                </div>
                                <p class="mt-2 text-sm font-extrabold text-gray-950">{{ $complaint->subject }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-400">Submitted {{ $complaint->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm font-medium leading-6 text-gray-700">{{ $complaint->message }}</p>

                        @if($complaint->admin_response)
                            <div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50 p-3">
                                <p class="text-[10px] font-extrabold uppercase tracking-wide text-emerald-700">Admin Response @if($complaint->responded_at) · {{ $complaint->responded_at->format('d M Y, h:i A') }} @endif</p>
                                <p class="mt-1 whitespace-pre-line text-sm font-medium leading-6 text-emerald-900">{{ $complaint->admin_response }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
