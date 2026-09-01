@extends('hr.layouts.app')

@section('title', 'Bulk Edit Members')

@section('content')
<div class="space-y-6" x-data="bulkEditApp()">

    {{-- Header --}}
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 sm:p-6 text-white shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-widest text-white/50">Human Resource</p>
                <h1 class="mt-1 text-3xl font-extrabold">Bulk Edit Members</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">
                    Search and pick any members, from as many searches as you need — your picks stay selected until you apply or clear them. Then set a class, section, or valid-till date to apply to everyone selected at once.
                </p>
            </div>
            <a href="{{ route('admin.hr.members.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-extrabold text-white hover:bg-white/20">
                ← Back to Members
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">

        {{-- ── LEFT: Search + results ─────────────────────────────────── --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.6fr_1fr_1fr_1fr]">
                    <input type="text" x-model="q" @input.debounce.300ms="search()"
                           placeholder="Search name, roll number, email, mobile…" autocomplete="off"
                           class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">

                    <select x-model="type" @change="search()"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">All types</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="staff">Staff</option>
                    </select>

                    <select x-model="stream" @change="search()"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">All classes</option>
                        @foreach($streams ?? [] as $streamOption)
                            <option value="{{ $streamOption }}">{{ $streamOption }}</option>
                        @endforeach
                    </select>

                    <select x-model="section" @change="search()"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">All sections</option>
                        @foreach($sections ?? [] as $sectionOption)
                            <option value="{{ $sectionOption }}">{{ $sectionOption }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">
                        <span x-show="loading">Searching…</span>
                        <span x-show="!loading && q === '' && !type && !stream && !section">Search or filter to find members</span>
                        <span x-show="!loading && (q !== '' || type || stream || section)"><span x-text="results.length"></span> result<span x-show="results.length !== 1">s</span></span>
                    </p>
                    <button type="button" @click="selectAllVisible()" x-show="results.length > 0"
                            class="text-xs font-extrabold text-[#1a5632] hover:underline">Select all visible</button>
                </div>

                <div class="divide-y divide-gray-50 max-h-[32rem] overflow-y-auto">
                    <template x-for="m in results" :key="m.id">
                        <label class="flex cursor-pointer items-center gap-3 px-5 py-3 hover:bg-gray-50">
                            <input type="checkbox" :checked="selectedIds.includes(m.id)" @change="toggle(m)"
                                   class="h-4 w-4 rounded accent-[#1a5632]">
                            <img :src="m.photo_url" class="h-9 w-9 shrink-0 rounded-full object-cover bg-gray-100">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-extrabold text-gray-900" x-text="m.name"></p>
                                <p class="truncate text-xs font-medium text-gray-400">
                                    <span x-text="m.roll_number || '—'"></span>
                                    <span x-show="m.stream"> · <span x-text="m.stream"></span></span>
                                    <span x-show="m.section"> · <span x-text="m.section"></span></span>
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-extrabold capitalize"
                                  :class="{
                                      'bg-blue-50 text-blue-700 border-blue-100': m.member_type === 'student',
                                      'bg-emerald-50 text-emerald-700 border-emerald-100': m.member_type === 'teacher',
                                      'bg-amber-50 text-amber-700 border-amber-100': m.member_type === 'staff'
                                  }" x-text="m.member_type"></span>
                        </label>
                    </template>

                    <p x-show="!loading && (q !== '' || type || stream || section) && results.length === 0"
                       class="px-5 py-10 text-center text-sm font-semibold text-gray-400">No members match your search.</p>
                </div>
            </div>
        </div>

        {{-- ── RIGHT: Selection + bulk edit fields ────────────────────── --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">
                        <span x-text="selectedIds.length"></span> selected
                    </p>
                    <button type="button" @click="clearSelection()" x-show="selectedIds.length > 0"
                            class="text-xs font-extrabold text-gray-400 hover:text-red-600 hover:underline">Clear all</button>
                </div>

                <p x-show="selectedIds.length === 0" class="text-sm font-semibold text-gray-400">
                    Nothing selected yet. Search on the left and tick members — selections carry over between searches.
                </p>

                <div class="max-h-56 space-y-1.5 overflow-y-auto" x-show="selectedIds.length > 0">
                    <template x-for="id in selectedIds" :key="id">
                        <div class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2">
                            <span class="min-w-0 flex-1 truncate text-xs font-bold text-gray-700">
                                <span x-text="selectedMembers[id]?.name || ('#' + id)"></span>
                                <span x-show="selectedMembers[id]?.roll_number" class="font-medium text-gray-400"> · <span x-text="selectedMembers[id]?.roll_number"></span></span>
                            </span>
                            <button type="button" @click="deselect(id)" class="shrink-0 text-gray-400 hover:text-red-600">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.hr.members.bulk-update') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
                @csrf
                <template x-for="id in selectedIds" :key="'id-' + id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>

                <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Apply to selected</p>

                <div>
                    <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Set Class</label>
                    <select name="stream" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">— unchanged —</option>
                        @foreach($streams ?? [] as $streamOption)
                            <option value="{{ $streamOption }}">{{ $streamOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Set Section</label>
                    <select name="section" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">— unchanged —</option>
                        @foreach($sections ?? [] as $sectionOption)
                            <option value="{{ $sectionOption }}">{{ $sectionOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Set Valid Till</label>
                    <input type="date" name="valid_till" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                </div>

                <button type="submit" :disabled="selectedIds.length === 0"
                        class="w-full rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415] disabled:cursor-not-allowed disabled:opacity-40">
                    Apply to <span x-text="selectedIds.length"></span> member<span x-show="selectedIds.length !== 1">s</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function bulkEditApp() {
        return {
            q: '',
            type: '',
            stream: '',
            section: '',
            loading: false,
            results: [],
            selectedIds: [],
            selectedMembers: {},
            controller: null,

            async search() {
                if (this.controller) this.controller.abort();
                this.controller = new AbortController();

                if (!this.q && !this.type && !this.stream && !this.section) {
                    this.results = [];
                    return;
                }

                this.loading = true;
                try {
                    const params = new URLSearchParams({ q: this.q, type: this.type, stream: this.stream, section: this.section });
                    const res = await fetch(`{{ route('admin.hr.members.bulk-edit.search') }}?${params}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: this.controller.signal,
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const payload = await res.json();
                    this.results = payload.members || [];
                } catch (err) {
                    if (err.name !== 'AbortError') console.error('Unable to search members:', err);
                } finally {
                    this.loading = false;
                }
            },

            toggle(member) {
                const idx = this.selectedIds.indexOf(member.id);
                if (idx === -1) {
                    this.selectedIds.push(member.id);
                    this.selectedMembers[member.id] = member;
                } else {
                    this.selectedIds.splice(idx, 1);
                    delete this.selectedMembers[member.id];
                }
            },

            selectAllVisible() {
                this.results.forEach(m => {
                    if (!this.selectedIds.includes(m.id)) {
                        this.selectedIds.push(m.id);
                        this.selectedMembers[m.id] = m;
                    }
                });
            },

            deselect(id) {
                this.selectedIds = this.selectedIds.filter(existing => existing !== id);
                delete this.selectedMembers[id];
            },

            clearSelection() {
                this.selectedIds = [];
                this.selectedMembers = {};
            },
        };
    }
</script>
@endpush
