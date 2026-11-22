@extends('examinations.layouts.app')
@section('title', 'Publish Results — '.$examination->name)
@section('content')
<div class="space-y-4" x-data="{ checked: new Set() }">
    <header class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white p-5 shadow-sm">
        <div>
            <h1 class="text-xl font-black">Publish Results</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $examination->name }} · {{ $examination->organization->name }} · {{ $examination->academicYear->name }}</p>
            <p class="mt-2 text-xs text-gray-500">Choose which faculties can see their results in the student app — publish all at once, or release one faculty at a time.</p>
        </div>
        <a href="{{ route('admin.examinations.results.index') }}" class="rounded-lg border px-4 py-2 text-xs font-bold">Back to Results</a>
    </header>

    @if($examination->departments->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm font-bold text-gray-400 shadow-sm">
            No faculty/department is linked to this exam yet.
        </div>
    @else
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                <div>
                    <p class="text-[9px] font-black uppercase tracking-widest text-emerald-700">Per-faculty control</p>
                    <h2 class="mt-0.5 text-base font-black text-slate-900">Faculties involved in this exam</h2>
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-xs font-bold text-gray-600">
                    <span class="relative inline-flex h-5 w-5 shrink-0 items-center justify-center">
                        <input type="checkbox"
                               :checked="checked.size === {{ $examination->departments->count() }}"
                               @change="checked = $event.target.checked ? new Set(@js($examination->departments->pluck('id')->all())) : new Set()"
                               class="peer sr-only">
                        <span class="absolute inset-0 rounded-md border-2 border-gray-300 bg-white shadow-sm transition peer-checked:border-[#1a5632] peer-checked:bg-[#1a5632] peer-focus-visible:ring-2 peer-focus-visible:ring-[#1a5632]/30"></span>
                        <span class="relative hidden text-[12px] font-black leading-none text-white peer-checked:block">&#10003;</span>
                    </span>
                    Select all
                </label>
            </header>

            <form method="POST" id="publish-form">
                @csrf
                <div class="divide-y divide-slate-100">
                    @foreach($examination->departments as $department)
                        @php $published = $department->pivot->published_at !== null; @endphp
                        <div class="flex items-center justify-between gap-3 p-4">
                            <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                                <span class="relative inline-flex h-5 w-5 shrink-0 items-center justify-center">
                                    <input type="checkbox" name="department_ids[]" value="{{ $department->id }}"
                                           x-bind:checked="checked.has({{ $department->id }})"
                                           @change="$event.target.checked ? checked.add({{ $department->id }}) : checked.delete({{ $department->id }})"
                                           class="peer sr-only">
                                    <span class="absolute inset-0 rounded-md border-2 border-gray-300 bg-white shadow-sm transition peer-checked:border-[#1a5632] peer-checked:bg-[#1a5632] peer-focus-visible:ring-2 peer-focus-visible:ring-[#1a5632]/30"></span>
                                    <span class="relative hidden text-[12px] font-black leading-none text-white peer-checked:block">&#10003;</span>
                                </span>
                                <span class="min-w-0 leading-tight">
                                    <span class="block truncate text-sm font-black text-slate-900">{{ $department->name }}</span>
                                    @if($published)
                                        <span class="mt-0.5 block text-[11px] font-semibold text-emerald-600">Published {{ \Carbon\Carbon::parse($department->pivot->published_at)->diffForHumans() }}</span>
                                    @else
                                        <span class="mt-0.5 block text-[11px] font-semibold text-gray-400">Not published yet</span>
                                    @endif
                                </span>
                            </label>
                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-black {{ $published ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($published)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    @endif
                                </svg>
                                {{ $published ? 'Published' : 'Hidden' }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="mr-auto text-[11px] font-bold text-gray-500" x-text="checked.size + ' selected'"></p>
                    <button type="submit" formaction="{{ route('admin.examinations.results.unpublish', $examination) }}"
                            :disabled="checked.size === 0"
                            class="rounded-lg border border-red-200 px-4 py-2 text-xs font-black text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40">
                        Withdraw selected
                    </button>
                    <button type="submit" formaction="{{ route('admin.examinations.results.publish', $examination) }}"
                            :disabled="checked.size === 0"
                            class="rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-black text-white hover:bg-[#0b2415] disabled:cursor-not-allowed disabled:opacity-40">
                        Publish selected
                    </button>
                </div>
            </form>
        </section>

    @endif
</div>
@endsection
