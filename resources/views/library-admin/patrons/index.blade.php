@extends('library-admin.layouts.app')

@section('title', 'HR Patrons')

@section('library-content')
@php
    $typeStyles = [
        'student' => 'bg-blue-50 text-blue-700 border-blue-100',
        'teacher' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'staff' => 'bg-amber-50 text-amber-700 border-amber-100',
    ];
    $organizationNames = $organizations->pluck('name', 'slug');
@endphp
<div class="mx-auto max-w-7xl space-y-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-emerald-700">Library Members</p>
            <h1 class="mt-1 text-2xl font-black text-slate-950">HR Patrons</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">{{ number_format($patrons->total()) }} matching HR members available for library circulation.</p>
        </div>
    </div>

    <form method="GET" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-2 xl:grid-cols-[2fr_1fr_1.2fr_1.2fr_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Search name, roll, registration, mobile..." class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:border-emerald-700">
        <select name="type" class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm font-bold outline-none focus:border-emerald-700">
            <option value="">All member types</option>
            @foreach(['student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff'] as $value => $label)
                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="organization" class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm font-bold outline-none focus:border-emerald-700">
            <option value="">All organizations</option>
            @foreach($organizations as $organization)
                <option value="{{ $organization->slug }}" @selected(request('organization') === $organization->slug)>{{ $organization->name }}</option>
            @endforeach
        </select>
        <select name="stream" class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm font-bold outline-none focus:border-emerald-700">
            <option value="">All classes / faculties</option>
            @foreach($classes as $class)
                <option value="{{ $class }}" @selected(request('stream') === $class)>{{ $class }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white hover:bg-emerald-800">Search</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
        <table class="w-full min-w-[1120px] text-left text-sm">
            <thead class="bg-slate-50 text-xs font-black uppercase tracking-widest text-slate-500">
                <tr>
                    <th class="px-5 py-3">Member</th>
                    <th class="px-5 py-3">Type / Organization</th>
                    <th class="px-5 py-3">Roll / Identifier</th>
                    <th class="px-5 py-3">Faculty / Class</th>
                    <th class="px-5 py-3">Contact</th>
                    <th class="px-5 py-3">Loans</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($patrons as $patron)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $patron->photo_url }}" alt="" class="h-10 w-10 rounded-full object-cover ring-1 ring-slate-200">
                                <div>
                                    <p class="font-black text-slate-950">{{ $patron->full_name }}</p>
                                    @if($patron->batch)<p class="text-xs font-semibold text-slate-400">Batch {{ $patron->batch }}</p>@endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black {{ $typeStyles[$patron->member_type] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">{{ ucfirst($patron->member_type) }}</span>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $organizationNames->get($patron->organization, ucfirst((string) $patron->organization)) }}</p>
                        </td>
                        <td class="px-5 py-3">
                            <p class="font-black text-slate-700">{{ $patron->roll_number ?: '-' }}</p>
                            @if($patron->registration_no)<p class="text-xs font-semibold text-slate-400">Reg: {{ $patron->registration_no }}</p>@endif
                        </td>
                        <td class="px-5 py-3 font-semibold text-slate-600">
                            <p>{{ $patron->stream ?: $patron->program ?: $patron->designation ?: 'Not assigned' }}</p>
                            <p class="text-xs text-slate-400">{{ $patron->section ? 'Section '.$patron->section : 'No section' }}</p>
                        </td>
                        <td class="px-5 py-3 text-xs font-semibold text-slate-500">
                            <p>{{ $patron->mobile ?: 'No mobile' }}</p>
                            <p class="mt-0.5 max-w-48 truncate">{{ $patron->email ?: 'No email' }}</p>
                        </td>
                        <td class="px-5 py-3">
                            <p class="font-black text-slate-700">{{ $patron->active_loans_count }} active</p>
                            <p class="text-xs font-semibold text-slate-400">{{ $patron->library_loans_count }} total</p>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.library.issue.index', ['borrower' => 'student:'.$patron->id]) }}" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-black text-white hover:bg-emerald-800">Issue Book</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center font-bold text-slate-400">No HR patrons match the selected search and filters.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($patrons->hasPages())
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">{{ $patrons->links() }}</div>
        @endif
    </div>
</div>
@endsection
