@extends('hr.layouts.app')

@section('title', 'Assign Lab Group')

@section('content')
<div class="space-y-4" x-data="labGroupAssignApp(@js($academicOptions))">

    {{-- Header --}}
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-4 text-white shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-white/50">Human Resource</p>
                <h1 class="mt-0.5 text-xl font-extrabold">Assign Lab Group</h1>
                <p class="mt-1 text-xs font-medium text-white/65">Filter students down to a section, then assign them to one of that section's groups.</p>
            </div>
            <a href="{{ route('admin.hr.members.index') }}"
               class="inline-flex items-center justify-center rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-xs font-extrabold text-white hover:bg-white/20">
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

        {{-- ── LEFT: Filter down to a section, then search ─────────────── --}}
        <div class="min-h-0 space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="grid min-w-0 gap-3 sm:grid-cols-3">
                    <select x-model="organization" @change="stream = ''; section = ''; search()"
                            class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">Choose organization</option>
                        <template x-for="entry in organizationOptions" :key="entry[0]"><option :value="entry[0]" x-text="entry[1].label"></option></template>
                    </select>

                    <select x-model="stream" @change="section = ''; search()" :disabled="!organization"
                            class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-100">
                        <option value="" x-text="organization ? 'Choose faculty / class' : 'Choose organization first'"></option>
                        <template x-for="item in streamOptions" :key="item"><option :value="item" x-text="item"></option></template>
                    </select>

                    <select x-model="section" @change="loadLabGroups(); search()" :disabled="!stream"
                            class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-100">
                        <option value="" x-text="stream ? 'Choose section' : 'Choose class first'"></option>
                        <template x-for="item in sectionOptions" :key="item.id"><option :value="item.name" x-text="sectionLabel(item)"></option></template>
                    </select>
                </div>
                <div class="mt-3">
                    <input type="text" x-model="q" @input.debounce.150ms="search()" :disabled="!section"
                           placeholder="Search within this section by name or roll number…" autocomplete="off"
                           class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-100">
                </div>
                <p x-show="!section" class="mt-2 text-[11px] font-semibold text-gray-400">Choose an organization, class, and section above to find students.</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden" x-show="section">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">
                        <span x-show="loading">Searching…</span>
                        <span x-show="!loading"><span x-text="visibleResults.length"></span> available</span>
                    </p>
                    <button type="button" @click="selectAllVisible()" x-show="visibleResults.length > 0"
                            class="text-xs font-extrabold text-[#1a5632] hover:underline">Select all visible</button>
                </div>

                <div class="min-h-0 divide-y divide-gray-50" style="height: min(32rem, 60vh); max-height: 60vh; overflow-y: scroll; overscroll-behavior: contain;">
                    <template x-for="m in visibleResults" :key="m.id">
                        <label class="flex cursor-pointer items-center gap-3 px-5 py-3 hover:bg-gray-50">
                            <input type="checkbox" :checked="selectedIds.includes(m.id)" @change="toggle(m)"
                                   class="h-4 w-4 rounded accent-[#1a5632]">
                            <img :src="m.photo_url" loading="lazy" decoding="async" class="h-9 w-9 shrink-0 rounded-full object-cover bg-gray-100">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-extrabold text-gray-900" x-text="m.name"></p>
                                <p class="truncate text-xs font-medium text-gray-400">
                                    <span x-text="m.roll_number || '—'"></span>
                                    <span x-show="m.lab_group_name"> · Group <span x-text="m.lab_group_name"></span></span>
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-extrabold capitalize bg-blue-50 text-blue-700 border-blue-100" x-text="m.member_type"></span>
                        </label>
                    </template>

                    <p x-show="!loading && results.length === 0" class="px-5 py-10 text-center text-sm font-semibold text-gray-400">No members match.</p>
                    <p x-show="!loading && results.length > 0 && visibleResults.length === 0"
                       class="px-5 py-10 text-center text-sm font-semibold text-emerald-700">All matching students have been moved to the selected list.</p>
                </div>
            </div>
        </div>

        {{-- ── RIGHT: Selection + group assignment ──────────────────────── --}}
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
                    Nothing selected yet. Choose a section on the left, then tick members.
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

            <form method="POST" action="{{ route('admin.hr.members.bulk-update') }}" class="rounded-2xl border border-purple-100 bg-purple-50/40 p-5 shadow-sm space-y-4">
                @csrf
                <template x-for="id in selectedIds" :key="'id-' + id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>

                <div><p class="text-xs font-extrabold uppercase tracking-widest text-purple-700">Set Lab Group</p><p class="mt-1 text-[11px] font-semibold text-gray-400">Groups are defined per section in Student Settings → Sections.</p></div>

                <div x-show="!section" class="rounded-lg border border-dashed border-purple-200 bg-white px-3 py-4 text-center text-xs font-semibold text-gray-400">
                    Choose a section on the left first.
                </div>

                <div x-show="section">
                    <select name="lab_group_id" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">Leave unchanged</option>
                        <template x-for="group in labGroupOptions" :key="group.id"><option :value="group.id" x-text="'Group ' + group.name"></option></template>
                        <option value="__clear__">Clear group</option>
                    </select>
                    <p x-show="!labGroupsLoading && labGroupOptions.length === 0" class="mt-1 text-[10px] font-semibold text-amber-600">No groups defined for this section yet — add some in Student Settings → Sections.</p>
                </div>

                <button type="submit" :disabled="selectedIds.length === 0 || !section"
                        class="w-full rounded-xl bg-purple-600 px-5 py-3 text-sm font-extrabold text-white hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-40">
                    Apply to <span x-text="selectedIds.length"></span> member<span x-show="selectedIds.length !== 1">s</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function labGroupAssignApp(formOptions) {
        return {
            formOptions,
            q: '',
            organization: '',
            stream: '',
            section: '',
            labGroupOptions: [],
            labGroupsLoading: false,
            loading: false,
            results: [],
            selectedIds: [],
            selectedMembers: {},
            controller: null,

            get organizationOptions() {
                return Object.entries(this.formOptions || {});
            },

            get streamOptions() {
                return Object.keys(this.formOptions?.[this.organization]?.streams || {});
            },

            get sectionOptions() {
                return this.formOptions?.[this.organization]?.streams?.[this.stream] || [];
            },

            get visibleResults() {
                return this.results.filter(member => !this.selectedIds.includes(member.id));
            },

            get selectedSection() {
                return this.sectionOptions.find(item => item.name === this.section) || null;
            },

            sectionLabel(item) {
                return item.group ? `${item.name} · ${item.group}` : item.name;
            },

            async loadLabGroups() {
                this.labGroupOptions = [];
                this.selectedIds = [];
                this.selectedMembers = {};
                const sectionId = this.selectedSection?.id;
                if (!sectionId) return;
                this.labGroupsLoading = true;
                try {
                    const url = @js(route('admin.hr.members.bulk-edit.lab-groups', ['section' => '__ID__'])).replace('__ID__', sectionId);
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const payload = await res.json();
                    this.labGroupOptions = payload.groups || [];
                } catch (err) {
                    console.error('Unable to load lab groups:', err);
                } finally {
                    this.labGroupsLoading = false;
                }
            },

            async search() {
                if (this.controller) this.controller.abort();
                this.controller = new AbortController();

                if (!this.section) {
                    this.results = [];
                    return;
                }

                this.loading = true;
                try {
                    const params = new URLSearchParams({ q: this.q, organization: this.organization, stream: this.stream, section: this.section });
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
                [...this.visibleResults].forEach(m => {
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
