@extends('examinations.layouts.app')
@section('title', 'Subjects & Marks Setup — '.$examination->name)
@section('content')
<div class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white p-5 shadow-sm">
        <div>
            <h1 class="text-xl font-black">Subjects & Marks Setup</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $examination->name }} · {{ $examination->organization->name }}</p>
        </div>
        <a href="{{ route('admin.examinations.index', ['exam' => $examination->id]) }}" class="rounded-lg border px-4 py-2 text-xs font-bold">Back to exam</a>
    </header>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @include('examinations.partials.subject-table')
    </section>
</div>
@endsection
