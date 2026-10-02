@extends('examinations.layouts.app')
@section('title', 'Exam Attendance — '.$examination->name)
@section('content')
<div class="space-y-4">
    <section class="flex flex-col gap-3 rounded-2xl bg-gradient-to-r from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-300">{{ $examination->organization->name }}</p>
            <h1 class="mt-1 text-2xl font-black">Exam Attendance — {{ $examination->name }}</h1>
            <p class="mt-1 text-xs font-semibold text-white/65">Everyone defaults to present — just mark who's absent.</p>
        </div>
        <a href="{{ route('admin.examinations.show', $examination) }}" class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-black text-white hover:bg-white/20">← Back to exam</a>
    </section>

    @if(session('success'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">@foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach</div>@endif

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.examinations.attendance.index', $examination) }}" onchange="this.submit()" class="grid gap-3 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500">Exam date</label>
                <x-nepali-date-input name="date" :value="$date" :max-bs="$todayBs" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15" />
                <p class="mt-1 text-[10px] font-semibold text-gray-400">Today or earlier only.</p>
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500">Faculty / class with exam this date</label>
                <select name="department_id" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15">
                    <option value="">{{ $departments->isEmpty() ? 'No exams scheduled this date' : 'Choose faculty / class' }}</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected($selectedDepartment?->id === $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500">Section</label>
                <select name="section_id" @disabled($sections->isEmpty()) class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-50 disabled:text-gray-400">
                    <option value="">{{ $selectedDepartment ? 'Choose section' : 'Choose faculty first' }}</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec->id }}" @selected($selectedSection?->id === $sec->id)>{{ $sec->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </section>

    <section x-data="examStudentSearch(@js($query))" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-gray-500">Find a student directly (today's exam faculties — no need to pick a section first)</label>
        <div class="relative w-full">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-300 transition-colors duration-300" :class="loading ? 'text-[#1a5632]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.65a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/></svg>
            <input type="text" x-model="query" @input.debounce.300ms="search()" placeholder="Type a name or roll no. — results update as you type" autocomplete="off"
                   class="search-glow w-full rounded-2xl border-2 border-gray-200 bg-white py-3.5 pl-11 pr-4 text-sm font-semibold text-gray-900 outline-none transition-all duration-300 focus:border-[#1a5632] focus:shadow-[0_0_0_6px_rgba(26,86,50,0.12)]">
            <span x-show="loading" x-cloak class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black uppercase tracking-wider text-[#1a5632]">Searching…</span>
        </div>
        <div x-ref="results">
            @include('examinations.attendance._search-results')
        </div>
    </section>

    <style>
        .search-glow:focus { animation: exam-search-glow 1.6s ease-in-out infinite; }
        @keyframes exam-search-glow {
            0%, 100% { border-color: #1a5632; }
            50% { border-color: #4fae78; }
        }
    </style>

    @if($selectedDepartment && $selectedSection)
        @if($roster->isEmpty())
            <div class="rounded-2xl border border-dashed bg-white p-10 text-center text-sm text-gray-400">No students in this section have an exam scheduled on this date.</div>
        @else
            <section x-data="examAttendance(@js($roster->map(fn ($row) => ['id' => $row->student->id, 'status' => $row->status, 'reason' => $row->reason])->values()), @js($query))" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <form method="POST" action="{{ route('admin.examinations.attendance.store', $examination) }}" @submit="saving = true">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                    <input type="hidden" name="department_id" value="{{ $selectedDepartment->id }}">
                    <input type="hidden" name="section_id" value="{{ $selectedSection->id }}">
                    <template x-for="id in absentIds" :key="'absent-'+id"><input type="hidden" name="absent_student_ids[]" :value="id"></template>
                    <template x-for="id in absentIds" :key="'reason-'+id"><input type="hidden" :name="`reasons[${id}]`" :value="reasonFor(id)"></template>

                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-[#1a5632]">{{ $selectedDepartment->name }} · Section {{ $selectedSection->name }}</p>
                            <h2 class="text-sm font-black text-gray-900">{{ $roster->count() }} student{{ $roster->count() === 1 ? '' : 's' }} with an exam on {{ $date->format('d M Y') }}</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700"><span x-text="{{ $roster->count() }} - absentIds.length"></span> present</span>
                            <span class="rounded-full bg-red-50 px-3 py-1.5 text-xs font-black text-red-700"><span x-text="absentIds.length"></span> absent</span>
                        </div>
                    </header>
                    <div class="border-b border-gray-100 px-5 py-3">
                        <input type="text" x-model="query" placeholder="Search by name or roll no." class="w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15 sm:w-64">
                    </div>

                    <div class="divide-y divide-gray-50">
                        @foreach($roster as $row)
                            <div x-show="matches({{ \Illuminate\Support\Js::from(Str::lower($row->student->full_name.' '.$row->student->roll_number)) }})" class="flex flex-wrap items-center gap-3 p-4 sm:p-5">
                                <div class="min-w-0 flex-1">
                                    <p class="font-extrabold text-gray-900">{{ $row->student->full_name }}</p>
                                    <p class="text-xs font-semibold text-gray-400">{{ $row->student->roll_number ?: '—' }} · {{ $row->subject_name }}</p>
                                </div>
                                <input type="text" x-show="isAbsent({{ $row->student->id }})" x-cloak
                                       :value="reasonFor({{ $row->student->id }})" @input="setReason({{ $row->student->id }}, $event.target.value)"
                                       placeholder="Reason (optional)" maxlength="255"
                                       class="w-48 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900 outline-none focus:border-amber-500">
                                <button type="button" @click="toggle({{ $row->student->id }})"
                                        :class="isAbsent({{ $row->student->id }}) ? 'border-red-600 bg-red-600 text-white' : 'border-gray-200 bg-white text-gray-500 hover:border-red-300 hover:text-red-600'"
                                        class="shrink-0 rounded-lg border px-4 py-2 text-xs font-black transition">
                                    <span x-text="isAbsent({{ $row->student->id }}) ? 'Absent' : 'Mark Absent'"></span>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <footer class="flex items-center justify-between border-t border-gray-100 bg-gray-50/60 px-5 py-4">
                        <p class="text-[10px] font-semibold text-gray-400">Everyone not marked absent is saved as present.</p>
                        <button :disabled="saving" class="rounded-xl bg-[#1a5632] px-5 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415] disabled:opacity-50">
                            <span x-show="!saving">Save Attendance</span>
                            <span x-show="saving" x-cloak>Saving…</span>
                        </button>
                    </footer>
                </form>
            </section>
        @endif
    @endif
</div>
@endsection
@push('scripts')
<script>
function examStudentSearch(initialQuery) {
    return {
        query: initialQuery || '',
        loading: false,
        controller: null,
        async search() {
            if (this.controller) this.controller.abort();
            const controller = new AbortController();
            this.controller = controller;
            this.loading = true;
            try {
                const target = new URL(@js(route('admin.examinations.attendance.search', $examination)), window.location.origin);
                target.searchParams.set('date', @js($date->toDateString()));
                target.searchParams.set('q', this.query);
                const response = await fetch(target, { cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, signal: controller.signal });
                const data = await response.json();
                if (controller.signal.aborted) return;
                this.$refs.results.innerHTML = data.html;
                const url = new URL(window.location);
                url.searchParams.set('q', this.query);
                history.replaceState(null, '', url);
            } catch (error) {
                if (error.name !== 'AbortError') console.error('Student search failed', error);
            } finally {
                if (this.controller === controller) this.loading = false;
            }
        },
    }
}

function examAttendance(initial, initialQuery) {
    return {
        saving: false,
        query: initialQuery || '',
        statuses: Object.fromEntries((initial || []).map(row => [row.id, { absent: row.status === 'absent', reason: row.reason || '' }])),
        matches(haystack) { return !this.query.trim() || haystack.includes(this.query.trim().toLowerCase()) },
        get absentIds() { return Object.keys(this.statuses).filter(id => this.statuses[id].absent).map(Number) },
        isAbsent(id) { return !!this.statuses[id]?.absent },
        reasonFor(id) { return this.statuses[id]?.reason || '' },
        setReason(id, value) { if (!this.statuses[id]) this.statuses[id] = { absent: false, reason: '' }; this.statuses[id].reason = value },
        toggle(id) {
            if (!this.statuses[id]) this.statuses[id] = { absent: false, reason: '' };
            this.statuses[id].absent = !this.statuses[id].absent;
            if (!this.statuses[id].absent) this.statuses[id].reason = '';
        },
    }
}
</script>
@endpush
