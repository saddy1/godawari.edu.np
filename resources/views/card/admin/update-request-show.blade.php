@extends('card.layouts.app')
@section('title', 'Update Request Details')
@section('heading', 'Update Request Details')

@section('content')
@php
    $student = $updateRequest->student;
    $labels = [
        'dob_bs' => 'Date of Birth (BS)', 'father_name' => 'Father Name', 'mother_name' => 'Mother Name',
        'grandfather_name' => 'Grandfather Name', 'parent_contact' => 'Parent Contact', 'guardian_contact' => 'Guardian Contact',
        'mobile' => 'Mobile', 'email' => 'Email', 'zone' => 'Province', 'district' => 'District',
        'municipality' => 'Municipality', 'permanent_province' => 'Permanent Province', 'permanent_district' => 'Permanent District',
        'permanent_municipality' => 'Permanent Municipality', 'permanent_ward' => 'Permanent Ward', 'permanent_tole' => 'Permanent Tole',
        'temporary_province' => 'Temporary Province', 'temporary_district' => 'Temporary District',
        'temporary_municipality' => 'Temporary Municipality', 'temporary_ward' => 'Temporary Ward', 'temporary_tole' => 'Temporary Tole',
        'photo' => 'Profile Photo',
    ];
@endphp
<div class="max-w-5xl space-y-5">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('admin.update-requests') }}" class="text-sm font-medium text-primary hover:underline">← Back to update requests</a>
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $updateRequest->status === 'approved' ? 'bg-green-100 text-green-700' : ($updateRequest->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ ucfirst($updateRequest->status) }}</span>
    </div>

    <section class="rounded-2xl border bg-white p-6 shadow-sm">
        <h2 class="text-base font-bold text-gray-800">Student details</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
            <div><p class="text-xs text-gray-400">Name</p><p class="font-semibold">{{ $student->full_name }}</p></div>
            <div><p class="text-xs text-gray-400">Roll number</p><p class="font-semibold">{{ $student->roll_number ?: '—' }}</p></div>
            <div><p class="text-xs text-gray-400">Class / section</p><p class="font-semibold">{{ $student->stream ?: '—' }} {{ $student->section ?: '' }}</p></div>
            <div><p class="text-xs text-gray-400">Submitted</p><p class="font-semibold">{{ $updateRequest->created_at->format('d M Y, h:i A') }}</p></div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border bg-white shadow-sm">
        <div class="border-b px-6 py-4"><h2 class="text-base font-bold text-gray-800">Requested changes</h2></div>
        <div class="divide-y">
            @forelse($updateRequest->requested_changes ?? [] as $field => $value)
                <div class="grid gap-2 px-6 py-4 sm:grid-cols-3">
                    <p class="text-sm font-semibold text-gray-700">{{ $labels[$field] ?? $field }}</p>
                    @if($field === 'photo')
                        <div class="sm:col-span-2 flex items-center gap-3"><img src="{{ $student->photo_url }}" alt="Current profile" class="h-20 w-16 rounded-lg border object-cover"><span class="text-gray-400">→</span><img src="{{ asset($value) }}" alt="Requested profile" class="h-20 w-16 rounded-lg border border-green-300 object-cover"></div>
                    @else
                        <p class="sm:col-span-2 break-words text-sm text-green-700 font-medium">{{ is_array($value) ? json_encode($value) : $value }}</p>
                    @endif
                </div>
            @empty
                <p class="px-6 py-8 text-sm text-gray-400">No requested changes were recorded.</p>
            @endforelse
        </div>
    </section>

    @if($updateRequest->admin_note)
        <section class="rounded-2xl border bg-white p-6 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Administrator note</p><p class="mt-2 text-sm text-gray-700">{{ $updateRequest->admin_note }}</p></section>
    @endif
</div>
@endsection
