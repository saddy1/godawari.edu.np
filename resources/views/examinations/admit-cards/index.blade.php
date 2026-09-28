@extends('examinations.layouts.app')
@section('title', 'Admit Cards — '.$examination->name)
@section('content')
@php $input='w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold text-gray-800 outline-none transition-colors duration-300 hover:border-gray-300 focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15';$label='mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500'; @endphp
<div class="space-y-4">
    <section class="flex flex-col gap-3 rounded-2xl bg-gradient-to-r from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-300">{{ $examination->organization->name }}</p>
            <h1 class="mt-1 text-2xl font-black">Admit Cards — {{ $examination->name }}</h1>
            <p class="mt-1 text-xs font-semibold text-white/65">{{ $totalStudents }} student(s) expected to sit this exam.</p>
        </div>
        <a href="{{ route('admin.examinations.index', ['exam' => $examination->id]) }}" class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-black text-white hover:bg-white/20">← Back to exam</a>
    </section>

    @if($symbolNumbers->isEmpty())
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <header class="border-b px-4 py-3"><h2 class="text-sm font-black">Assign symbol numbers</h2><p class="text-[10px] font-semibold text-gray-400">Students are numbered alphabetically by name within each faculty/section. @if($examination->organization->type === 'school')Class 11 starts at 110001. Class 12 uses the 12 prefix and continues the same sequence (for example, 110150 → 120151). @endif Assigns numbers to the full exam roster, including students hidden by search.</p></header>
        <form method="POST" action="{{ route('admin.examinations.admit-cards.assign', $examination) }}" onsubmit="return confirm('This will assign symbol numbers for all '+{{ $totalStudents }}+' students in the full exam roster. Continue?')" class="flex flex-wrap items-end gap-3 p-4">
            @csrf
            @if($examination->organization->type !== 'school')
                <div class="w-40"><label class="{{$label}}">Start number</label><input type="number" name="start_number" min="1" required value="{{ old('start_number', 1) }}" class="{{$input}}"></div>
            @endif
            <button class="rounded-xl bg-[#1a5632] px-5 py-2.5 text-xs font-black text-white">Assign symbol numbers</button>
        </form>
    </section>
    @elseif($examination->symbol_numbers_locked)
    <section class="overflow-hidden rounded-2xl border border-red-200 bg-red-50/40 shadow-sm">
        <header class="flex items-center gap-2 border-b border-red-200 px-4 py-3">
            <span class="text-lg">🔒</span>
            <div>
                <h2 class="text-sm font-black text-red-900">Symbol numbers are locked</h2>
                <p class="text-[10px] font-semibold text-red-700">Locked {{ $examination->symbol_numbers_locked_at->format('d M Y, h:i A') }}{{ $examination->symbolNumbersLockedBy?->name ? ' by '.$examination->symbolNumbersLockedBy->name : '' }}. Bulk regeneration is disabled — a student added after locking can still be given a number individually using "Set" in the roster below.</p>
            </div>
        </header>
    </section>
    @else
    <section class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/40 shadow-sm">
        <header class="border-b border-amber-200 px-4 py-3"><h2 class="text-sm font-black text-amber-900">Regenerate symbol numbers</h2><p class="text-[10px] font-semibold text-amber-700">Symbol numbers are already assigned. Regenerating replaces every number using the current roster, sorted alphabetically by name within each faculty/section — any admit cards or marksheets already printed with the old numbers become invalid.</p></header>
        <form method="POST" action="{{ route('admin.examinations.admit-cards.assign', $examination) }}" onsubmit="return confirm('This deletes every existing symbol number for this exam and reassigns fresh ones for all '+{{ $totalStudents }}+' students. Previously printed admit cards / marksheets will no longer match. Continue?')" class="flex flex-wrap items-end gap-3 border-b border-amber-200 p-4">
            @csrf
            <input type="hidden" name="regenerate" value="1">
            @if($examination->organization->type !== 'school')
                <div class="w-40"><label class="{{$label}}">Start number</label><input type="number" name="start_number" min="1" required value="{{ old('start_number', 1) }}" class="{{$input}}"></div>
            @endif
            <button class="rounded-xl bg-amber-600 px-5 py-2.5 text-xs font-black text-white hover:bg-amber-700">Regenerate symbol numbers</button>
        </form>
        <div x-data="{ open: @js($errors->has('confirm')), confirmText: '' }" class="p-4">
            <button type="button" @click="open = true" class="rounded-xl border border-red-300 bg-white px-4 py-2.5 text-xs font-black text-red-700 hover:bg-red-50">🔒 Lock symbol numbers</button>
            <p class="mt-1.5 text-[10px] font-semibold text-gray-400">Locking cannot be undone — regeneration stays disabled afterward. Do this once admit cards start going out.</p>
            <div x-show="open" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4" @keydown.escape.window="open = false">
                <div @click.outside="open = false" class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl">
                    <h3 class="text-sm font-black text-gray-950">Lock symbol numbers?</h3>
                    <p class="mt-1.5 text-xs font-semibold text-gray-500">This permanently disables regeneration for this exam. Type <b class="text-red-600">LOCK</b> to confirm.</p>
                    <form method="POST" action="{{ route('admin.examinations.admit-cards.lock', $examination) }}" class="mt-3 space-y-3">
                        @csrf
                        <input type="text" name="confirm" x-model="confirmText" autocomplete="off" placeholder="Type LOCK" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-black uppercase tracking-widest outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/15">
                        @error('confirm')<p class="text-[10px] font-bold text-red-600">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="open = false" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-bold">Cancel</button>
                            <button type="submit" :disabled="confirmText.trim() !== 'LOCK'" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-black text-white disabled:cursor-not-allowed disabled:opacity-40">Lock permanently</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    @endif

    <section x-data="admitRosterSearch" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <header class="flex flex-col gap-2 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="text-sm font-black">Roster</h2><p class="text-[10px] font-semibold text-gray-400">Print all matching students across all pages. Only students with assigned symbol numbers are included.</p></div>
            <form method="GET" action="{{ route('admin.examinations.admit-cards.print', $examination) }}" class="flex items-center gap-2">
                <input type="hidden" name="q" :value="query">
                <input type="hidden" name="school_class" :value="schoolClass">
                <input type="hidden" name="faculty" :value="faculty">
                <input type="hidden" name="section" :value="section">
                <select name="count" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-black">
                    <option value="1">1 per page</option>
                    <option value="2">2 per page</option>
                    <option value="4">4 per page</option>
                </select>
                <button class="rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-black text-white">Print filtered →</button>
            </form>
            <form method="GET" action="{{ route('admin.examinations.admit-cards.export', $examination) }}" class="flex items-center gap-2">
                <input type="hidden" name="q" :value="query">
                <input type="hidden" name="school_class" :value="schoolClass">
                <input type="hidden" name="faculty" :value="faculty">
                <input type="hidden" name="section" :value="section">
                <button class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-black text-gray-700 hover:border-[#1a5632]/40 hover:text-[#1a5632]">Export Excel ↓</button>
            </form>
        </header>
        <div class="border-t p-4">
            <form method="GET" @submit.prevent="search()" class="flex flex-wrap items-end gap-3 rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                <label class="flex flex-col text-xs font-semibold text-gray-600">Student search<input name="q" x-model="query" @input.debounce.300ms="search()" placeholder="Name, roll, symbol no., stream or section" class="mt-1 w-56 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-[#1a5632] focus:ring-[#1a5632] sm:w-72"></label>
                @if($examination->organization->type === 'school')
                <label class="flex flex-col text-xs font-semibold text-gray-600">Class<select name="school_class" x-model="schoolClass" @change="faculty = ''; section = ''; search()" class="mt-1 w-36 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="">All classes</option><option value="11">Class 11</option><option value="12">Class 12</option>
                </select></label>
                @endif
                <label class="flex flex-col text-xs font-semibold text-gray-600">Faculty<select name="faculty" x-model="faculty" @change="section = ''; search()" class="mt-1 w-44 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="">All faculties</option>
                    <template x-for="name in faculties" :key="name"><option :value="name" x-text="name"></option></template>
                </select></label>
                <label class="flex flex-col text-xs font-semibold text-gray-600">Section<select name="section" x-model="section" @change="search()" class="mt-1 w-36 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
                    <option value="">All sections</option>
                    <template x-for="name in sections" :key="name"><option :value="name" x-text="name"></option></template>
                </select></label>
                <input type="hidden" name="missing_symbol" :value="missingSymbol ? '1' : ''">
                <button type="button" @click="missingSymbol = !missingSymbol; search()"
                        :class="missingSymbol ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white text-gray-600 hover:border-blue-400 hover:text-blue-700'"
                        class="flex items-center gap-1.5 self-end rounded-lg border px-3 py-2.5 text-xs font-bold transition">
                    <svg x-show="missingSymbol" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    <span>Missing symbol no. only</span>
                    @if($missingCount)<span :class="missingSymbol ? 'bg-white/20 text-white' : 'bg-blue-50 text-blue-700'" class="rounded-full px-1.5 py-0.5 text-[9px] font-black">{{ $missingCount }}</span>@endif
                </button>
                <button class="rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-bold text-white">Search</button>
                <button type="button" @click="query = ''; schoolClass = ''; faculty = ''; section = ''; missingSymbol = false; search()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-bold">Clear filters</button>
                <span x-show="loading" x-cloak role="status" class="self-center text-xs text-gray-500">Searching…</span>
                <span x-show="error" x-cloak x-text="error" role="alert" class="self-center text-xs text-red-600"></span>
            </form>
            <div x-ref="results" @click="paginate($event)" :aria-busy="loading" class="mt-4">
                @include('examinations.admit-cards._roster')
            </div>
        </div>
    </section>
</div>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('admitRosterSearch', () => ({
        query: @js(request('q', '')), schoolClass: @js(request('school_class', '')),
        faculty: @js(request('faculty', '')), section: @js(request('section', '')),
        missingSymbol: @js(request()->boolean('missing_symbol')),
        options: @js($filterOptions),
        get faculties() {
            return [...new Set(this.options.filter(row => !this.schoolClass || String(row.school_class) === this.schoolClass).map(row => row.faculty).filter(Boolean))].sort();
        },
        get sections() {
            return [...new Set(this.options.filter(row => (!this.schoolClass || String(row.school_class) === this.schoolClass) && (!this.faculty || row.faculty === this.faculty)).map(row => row.section).filter(Boolean))].sort();
        },
        loading: false, error: '', controller: null,
        async search(url = null) {
            if (this.controller) this.controller.abort();
            const controller = new AbortController();
            this.controller = controller;
            const target = new URL(url || @js(route('admin.examinations.admit-cards.index', $examination)), window.location.origin);
            target.searchParams.set('q', this.query);
            target.searchParams.set('school_class', this.schoolClass);
            target.searchParams.set('faculty', this.faculty);
            target.searchParams.set('section', this.section);
            target.searchParams.set('missing_symbol', this.missingSymbol ? '1' : '');
            this.loading = true; this.error = '';
            try {
                const searchTarget = new URL(@js(route('admin.examinations.admit-cards.search', $examination)), window.location.origin);
                searchTarget.search = target.search;
                const response = await fetch(searchTarget, { cache: 'no-store', headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}, signal: controller.signal });
                if (!response.ok) throw new Error('Search failed');
                const data = await response.json();
                if (controller.signal.aborted) return;
                this.$refs.results.innerHTML = data.html;
                history.replaceState(null, '', target);
            } catch (error) {
                if (error.name !== 'AbortError') this.error = 'Unable to load students. Please try again.';
            } finally {
                if (this.controller === controller) this.loading = false;
            }
        },
        paginate(event) {
            const link = event.target.closest('a');
            if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || !new URL(link.href).searchParams.has('page')) return;
            event.preventDefault(); this.search(link.href);
        }
    }));
});
</script>
@endsection
