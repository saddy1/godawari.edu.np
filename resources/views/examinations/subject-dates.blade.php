@extends('examinations.layouts.app')
@section('title', 'Subject exam dates — '.$examination->name)
@section('content')
@php
    $input = 'w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:ring-[#1a5632]/15';
    $label = 'mb-1 block text-xs font-bold text-gray-600';
@endphp
<div class="mx-auto w-full max-w-5xl space-y-4">
    <header class="flex flex-col gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-emerald-700">{{ $examination->organization->name }} · {{ $examination->name }}</p>
            <h1 class="mt-1 text-xl font-black">Subject exam dates</h1>
            <p class="mt-2 text-sm text-gray-600">Set each subject once. The same date applies to every class listed under it.</p>
        </div>
        <a href="{{ route('admin.examinations.index', ['exam' => $examination->id]) }}" class="shrink-0 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700">← Back to exam</a>
    </header>
    @if($examination->is_locked)
        <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">This exam is locked. Subject dates are read-only.</p>
    @endif
    <section class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5">
        @include('examinations.partials.subject-dates')
    </section>
</div>
@endsection
