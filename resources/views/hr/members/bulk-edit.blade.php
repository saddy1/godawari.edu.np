@extends('hr.layouts.app')

@section('title', 'Bulk Edit Members')

@section('content')
<div class="space-y-4" x-data="bulkEditApp(@js($academicOptions), @js($batchOptions))">

    {{-- Header --}}
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-4 text-white shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-white/50">Human Resource</p>
                <h1 class="mt-0.5 text-xl font-extrabold">Assign Students to Sections</h1>
                <p class="mt-1 text-xs font-medium text-white/65">Find students, select them, and assign a section from the academic master list.</p>
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

        {{-- ── LEFT: Search + results ─────────────────────────────────── --}}
        <div class="min-h-0 space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="grid min-w-0 gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,2fr)_repeat(6,minmax(0,1fr))]">
                    <input type="text" x-model="q" @input.debounce.150ms="search()"
                           placeholder="Search name, roll number, email, mobile…" autocomplete="off"
                           class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">

                    <select x-model="type" @change="search()"
                            class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">All types</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="staff">Staff</option>
                    </select>

                    <select x-model="gender" @change="search()"
                            class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">All genders</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>

                    <select x-model="batch" @change="search()"
                            class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">All batches</option>
                        <option value="__none__">No batch assigned</option>
                        <template x-for="item in batchOptions" :key="item"><option :value="item" x-text="item"></option></template>
                    </select>

                    <select x-model="organization" @change="stream = ''; section = ''; batch = ''; search()"
                            class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">All organizations</option>
                        <template x-for="entry in organizationOptions" :key="entry[0]"><option :value="entry[0]" x-text="entry[1].label"></option></template>
                    </select>

                    <select x-model="stream" @change="section = ''; search()" :disabled="!organization"
                            class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="" x-text="organization ? 'All classes' : 'Choose organization'"></option>
                        <template x-for="item in streamOptions" :key="item"><option :value="item" x-text="item"></option></template>
                    </select>

                    <select x-model="section" @change="search()" :disabled="!stream"
                            class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="" x-text="stream ? 'All sections' : 'Choose class'"></option>
                        <template x-for="item in sectionOptions" :key="item.id"><option :value="item.name" x-text="sectionLabel(item)"></option></template>
                    </select>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">
                        <span x-show="loading">Searching…</span>
                        <span x-show="!loading && q === '' && !type && !gender && !batch && !organization && !stream && !section">Search or filter to find members</span>
                        <span x-show="!loading && (q !== '' || type || gender || batch || organization || stream || section)">
                            <span x-text="visibleResults.length"></span> available
                            <span x-show="results.length === resultLimit" class="font-medium text-amber-600"> · Refine search to see more</span>
                        </span>
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
                                    <span x-show="m.stream"> · <span x-text="m.stream"></span></span>
                                    <span x-show="m.section"> · <span x-text="m.section"></span></span>
                                    <span x-show="m.batch"> · Batch <span x-text="m.batch"></span></span>
                                    <span x-show="m.gender"> · <span x-text="m.gender"></span></span>
                                    <span x-show="m.lab_group_name"> · Group <span x-text="m.lab_group_name"></span></span>
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

                    <p x-show="!loading && (q !== '' || type || gender || batch || organization || stream || section) && results.length === 0"
                       class="px-5 py-10 text-center text-sm font-semibold text-gray-400">No members match your search.</p>
                    <p x-show="!loading && results.length > 0 && visibleResults.length === 0"
                       class="px-5 py-10 text-center text-sm font-semibold text-emerald-700">All matching students have been moved to the selected list.</p>
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
                                <span x-show="selectedMembers[id]?.lab_group_name" class="font-medium text-gray-400"> · Group <span x-text="selectedMembers[id]?.lab_group_name"></span></span>
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

                <div><p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Assign academic section</p><p class="mt-1 text-[11px] font-semibold text-gray-400">Sections come directly from Student Settings.</p></div>

                <div>
                    <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Organization</label>
                    <select x-model="targetOrganization" @change="targetStream = ''; targetSectionId = ''" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">Choose organization</option>
                        <template x-for="entry in organizationOptions" :key="entry[0]"><option :value="entry[0]" x-text="entry[1].label"></option></template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Faculty / Class</label>
                    <select x-model="targetStream" @change="targetSectionId = ''" :disabled="!targetOrganization" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-100">
                        <option value="" x-text="targetOrganization ? 'Choose faculty / class' : 'Choose organization first'"></option>
                        <template x-for="item in targetStreamOptions" :key="item"><option :value="item" x-text="item"></option></template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Section</label>
                    <select name="section_id" x-model="targetSectionId" :disabled="!targetStream" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-100">
                        <option value="" x-text="targetStream ? 'Choose section' : 'Choose class first'"></option>
                        <template x-for="item in targetSectionOptions" :key="item.id"><option :value="item.id" x-text="sectionLabel(item)"></option></template>
                    </select>
                    <p x-show="targetStream && targetSectionOptions.length === 0" class="mt-1 text-[10px] font-semibold text-amber-600">No active sections created for this class.</p>
                    <p x-show="targetSectionId" class="mt-1 text-[10px] font-semibold text-emerald-700">This replaces the student's previous section; it does not add a second section.</p>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Set Batch</label>
                    <input name="batch" x-model="targetBatch" placeholder="e.g. 2083" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    <p class="mt-1 text-[10px] font-semibold text-gray-400">Students only. Leave blank to keep existing batches.</p>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Set Gender</label>
                    <select name="gender" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        <option value="">Leave unchanged</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                    <p class="mt-1 text-[10px] font-semibold text-gray-400">This changes gender for every selected member.</p>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Set Valid Till</label>
                    <input type="date" name="valid_till" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                </div>
                <p class="text-[11px] font-semibold text-gray-400">Need to assign a Lab Group instead? Use <a href="{{ route('admin.hr.members.lab-groups.index') }}" class="font-extrabold text-purple-700 hover:underline">Assign Lab Group</a> in the sidebar.</p>

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
    function bulkEditApp(formOptions, batchOptions) {
        return {
            formOptions,
            q: '',
            type: '',
            gender: '',
            batch: '',
            organization: '',
            stream: '',
            section: '',
            targetOrganization: '',
            targetStream: '',
            targetSectionId: '',
            targetBatch: '',
            batchOptions: batchOptions || [],
            loading: false,
            results: [],
            selectedIds: [],
            selectedMembers: {},
            controller: null,
            resultLimit: 250,

            get organizationOptions() {
                return Object.entries(this.formOptions || {});
            },

            get visibleResults() {
                return this.results.filter(member => !this.selectedIds.includes(member.id));
            },

            get streamOptions() {
                return Object.keys(this.formOptions?.[this.organization]?.streams || {});
            },

            get sectionOptions() {
                return this.formOptions?.[this.organization]?.streams?.[this.stream] || [];
            },

            get targetStreamOptions() {
                return Object.keys(this.formOptions?.[this.targetOrganization]?.streams || {});
            },

            get targetSectionOptions() {
                return this.formOptions?.[this.targetOrganization]?.streams?.[this.targetStream] || [];
            },

            sectionLabel(item) {
                return item.group ? `${item.name} · ${item.group}` : item.name;
            },

            async search() {
                if (this.controller) this.controller.abort();
                this.controller = new AbortController();

                if (!this.q && !this.type && !this.gender && !this.batch && !this.organization && !this.stream && !this.section) {
                    this.results = [];
                    return;
                }

                this.loading = true;
                try {
                    const params = new URLSearchParams({ q: this.q, type: this.type, gender: this.gender, batch: this.batch, organization: this.organization, stream: this.stream, section: this.section });
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
