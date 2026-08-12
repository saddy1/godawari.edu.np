@extends('layouts.admin')
@section('title', 'Designations')

@section('content')
<div class="mx-auto max-w-4xl">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Designations</h2>
            <p class="mt-1 text-sm text-gray-500">Manage employee designations used across HR and Attendance modules.</p>
        </div>
        <a href="{{ route('admin.hr.members.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-50">
            ← HR People
        </a>
    </div>

    @if(session('success'))
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-700">
            <svg class="h-4 w-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
            <svg class="h-4 w-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">

        {{-- ── Designation List ── --}}
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-4">
                <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">
                    Designation List ({{ $designations->count() }})
                </p>
            </div>

            @forelse($designations as $d)
            @php $inUse = $usageCounts[$d->id] ?? 0; @endphp
            <div class="flex items-center gap-4 border-b border-gray-50 px-6 py-4 last:border-0 hover:bg-gray-50 transition-colors">

                {{-- Status dot --}}
                <div class="h-2.5 w-2.5 shrink-0 rounded-full {{ $d->status ? 'bg-green-400' : 'bg-red-400' }}"></div>

                {{-- Label + in-use badge --}}
                <div class="flex-1 min-w-0" x-data="{ editing: false, name: '{{ addslashes($d->label) }}' }">
                    <div class="flex items-center gap-2 min-w-0">
                        <span x-show="!editing" class="block truncate text-sm font-semibold text-gray-800" x-text="name"></span>
                        @if($inUse > 0)
                            <span class="shrink-0 inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-extrabold text-blue-600 border border-blue-100"
                                  title="{{ $inUse }} employee(s) currently assigned to this designation">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2a5 5 0 00-10 0v2m10 0H7"/></svg>
                                {{ $inUse }} in use
                            </span>
                        @endif
                    </div>
                    <input x-show="editing" x-model="name" x-cloak
                           class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/10">

                    {{-- Save form --}}
                    <form method="POST" action="{{ route('admin.hr.designations.update', $d->id) }}" x-ref="editForm" class="hidden">
                        @csrf @method('PUT')
                        <input type="hidden" name="name" :value="name">
                        <input type="hidden" name="status" value="{{ $d->status ? 1 : 0 }}">
                    </form>
                </div>

                {{-- Actions --}}
                <div class="flex shrink-0 items-center gap-2" x-data="{ editing: false, name: '{{ addslashes($d->label) }}' }">
                    {{-- Edit / Save --}}
                    <button x-show="!editing"
                            @click="editing = true; $nextTick(() => $el.closest('.flex').previousElementSibling.querySelector('input').focus())"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-500 hover:border-[#1a5632] hover:text-[#1a5632] transition-colors">
                        Edit
                    </button>
                    <button x-show="editing" x-cloak
                            @click="$el.closest('.flex').previousElementSibling.querySelector('form').submit()"
                            class="rounded-lg bg-[#1a5632] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#0b2415] transition-colors">
                        Save
                    </button>
                    <button x-show="editing" x-cloak
                            @click="editing = false"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-400 hover:bg-gray-100 transition-colors">
                        Cancel
                    </button>

                    {{-- Toggle status --}}
                    <form method="POST" action="{{ route('admin.hr.designations.update', $d->id) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="name"   value="{{ $d->label }}">
                        <input type="hidden" name="status" value="{{ $d->status ? 0 : 1 }}">
                        <button type="submit"
                                class="rounded-lg px-3 py-1.5 text-xs font-bold transition-colors {{ $d->status ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-red-50 text-red-600 hover:bg-red-100' }}">
                            {{ $d->status ? 'Active' : 'Inactive' }}
                        </button>
                    </form>

                    {{-- Delete — disabled when designation is assigned to employees --}}
                    @if($inUse > 0)
                        <span class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-bold text-gray-300 cursor-not-allowed select-none"
                              title="Reassign or remove {{ $inUse }} employee(s) before deleting">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Locked
                        </span>
                    @else
                        <form method="POST" action="{{ route('admin.hr.designations.destroy', $d->id) }}"
                              onsubmit="return confirm('Delete « {{ $d->label }} »? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="rounded-lg px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-50 transition-colors">
                                Delete
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-6 py-12 text-center text-sm font-semibold text-gray-400">
                No designations added yet. Use the form to add one.
            </div>
            @endforelse
        </div>

        {{-- ── Add New Designation ── --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm self-start sticky top-24">
            <h3 class="mb-5 text-base font-bold text-[#0b2415] border-b border-gray-100 pb-4">Add New Designation</h3>
            <form method="POST" action="{{ route('admin.hr.designations.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Designation Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required value="{{ old('name') }}"
                           placeholder="e.g. Head Teacher, Accountant"
                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm focus:border-[#1a5632] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1a5632]/10 transition-all">
                </div>
                <button type="submit"
                        class="w-full rounded-xl bg-[#1a5632] py-3 text-sm font-bold text-white hover:bg-[#0b2415] transition-colors">
                    + Add Designation
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
