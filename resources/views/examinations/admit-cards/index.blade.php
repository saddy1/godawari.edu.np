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
        <header class="border-b px-4 py-3"><h2 class="text-sm font-black">Assign symbol numbers</h2><p class="text-[10px] font-semibold text-gray-400">@if($examination->organization->type === 'school')Class 11 starts at 110001. Class 12 uses the 12 prefix and continues the same sequence (for example, 110150 → 120151). @endif Assigns numbers to the full exam roster, including students hidden by search.</p></header>
        <form method="POST" action="{{ route('admin.examinations.admit-cards.assign', $examination) }}" onsubmit="return confirm('This will assign symbol numbers for all '+{{ $totalStudents }}+' students in the full exam roster. Continue?')" class="flex flex-wrap items-end gap-3 p-4">
            @csrf
            @if($examination->organization->type !== 'school')
                <div class="w-40"><label class="{{$label}}">Start number</label><input type="number" name="start_number" min="1" required value="{{ old('start_number', 1) }}" class="{{$input}}"></div>
            @endif
            <button class="rounded-xl bg-[#1a5632] px-5 py-2.5 text-xs font-black text-white">Assign symbol numbers</button>
        </form>
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
        </header>
        <div class="border-t">
            <form method="GET" @submit.prevent="search()" class="flex flex-wrap gap-3 p-4">
                <input aria-label="Search students" name="q" x-model="query" @input.debounce.300ms="search()" placeholder="Search name, roll, symbol no., stream or section" class="{{$input}} sm:!w-96">
                @if($examination->organization->type === 'school')
                <select aria-label="Filter class" name="school_class" x-model="schoolClass" @change="faculty = ''; section = ''; search()" class="rounded-xl border-gray-200 text-sm">
                    <option value="">All classes</option><option value="11">Class 11</option><option value="12">Class 12</option>
                </select>
                @endif
                <select aria-label="Filter faculty" name="faculty" x-model="faculty" @change="section = ''; search()" class="rounded-xl border-gray-200 text-sm">
                    <option value="">All faculties</option>
                    <template x-for="name in faculties" :key="name"><option :value="name" x-text="name"></option></template>
                </select>
                <select aria-label="Filter section" name="section" x-model="section" @change="search()" class="rounded-xl border-gray-200 text-sm">
                    <option value="">All sections</option>
                    <template x-for="name in sections" :key="name"><option :value="name" x-text="name"></option></template>
                </select>
                <button class="rounded-xl border px-4 py-2 text-xs font-bold">Search</button>
                <button type="button" @click="query = ''; schoolClass = ''; faculty = ''; section = ''; search()" class="rounded-xl border px-4 py-2 text-xs font-bold">Clear filters</button>
                <span x-show="loading" x-cloak role="status" class="self-center text-xs text-gray-500">Searching…</span>
                <span x-show="error" x-cloak x-text="error" role="alert" class="self-center text-xs text-red-600"></span>
            </form>
            <div x-ref="results" @click="paginate($event)" :aria-busy="loading">
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
            this.loading = true; this.error = '';
            try {
                const response = await fetch(target, { headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}, signal: controller.signal });
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
            if (!link || !new URL(link.href).searchParams.has('page')) return;
            event.preventDefault(); this.search(link.href);
        }
    }));
});
</script>
@endsection
