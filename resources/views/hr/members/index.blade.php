@extends('hr.layouts.app')

@section('title', 'HR Members')

@section('content')
@php
    $typeLabels = ['student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff'];
    $typeStyles = [
        'student' => 'bg-blue-50 text-blue-700 border-blue-100',
        'teacher' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'staff' => 'bg-amber-50 text-amber-700 border-amber-100',
    ];
@endphp

<div class="space-y-6">
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 sm:p-6 text-white shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-widest text-white/50">Human Resource</p>
                <h1 class="mt-1 text-3xl font-extrabold">People Master</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">
                    Add students, teachers, and staff once. HR syncs them to ID Card, Hajiri, Learning, and future ERP modules.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(auth()->user()?->canAccess('hr.members.edit'))
                    <a href="{{ route('admin.hr.members.bulk-edit.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-extrabold text-white hover:bg-white/20">
                        Bulk Edit
                    </a>
                @endif
                @if(auth()->user()?->canAccess('hr.members.create'))
                    <a href="{{ route('admin.hr.members.import') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-extrabold text-white hover:bg-white/20">
                        Bulk Import
                    </a>
                    <a href="{{ route('admin.hr.members.create') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-extrabold text-[#1a5632] hover:bg-gray-100">
                        New Member
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($counts as $key => $count)
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">{{ $key === 'all' ? 'All Members' : $typeLabels[$key] }}</p>
                <p class="mt-2 text-3xl font-black text-gray-950">{{ $count }}</p>
            </div>
        @endforeach
    </div>

    <form id="hr-member-filter-form" method="GET" action="{{ route('admin.hr.members.index') }}" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm" x-data="districtFilter()">
        <div class="grid gap-3">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.8fr_1fr_1fr_1fr_1fr]">
                <input name="search" value="{{ request('search') }}" placeholder="Search name, ID, email, mobile..." autocomplete="off" data-ajax-search class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">

                <select name="type" class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    <option value="">All types</option>
                    @foreach($typeLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="stream" class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15" @change="loadSections($el.value)">
                    <option value="">All classes</option>
                    @foreach($streams ?? [] as $stream)
                        <option value="{{ $stream }}" @selected(request('stream') === $stream)>{{ $stream }}</option>
                    @endforeach
                </select>

                <select name="section" class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15" x-ref="section">
                    <option value="">All sections</option>
                    @foreach($sections ?? [] as $section)
                        <option value="{{ $section }}" @selected(request('section') === $section)>{{ $section }}</option>
                    @endforeach
                </select>

                <select name="per_page" class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    @foreach([10,20,50,100] as $p)
                        <option value="{{ $p }}" @selected((int)request('per_page', 20) === $p)>{{ $p }} per page</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <select name="permanent_district" class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15" @change="loadMunicipalities($el.value)" x-ref="district">
                    <option value="">All districts</option>
                    @foreach($districts ?? [] as $d)
                        <option value="{{ $d }}" @selected(request('permanent_district') === $d)>{{ $d }}</option>
                    @endforeach
                </select>

                <select name="permanent_municipality" class="w-full min-w-0 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15" x-ref="municipality">
                    <option value="">All municipalities</option>
                    @foreach($municipalities ?? [] as $m)
                        <option value="{{ $m }}" @selected(request('permanent_municipality') === $m)>{{ $m }}</option>
                    @endforeach
                </select>

                <button class="w-full rounded-xl bg-[#1a5632] px-4 py-3 text-sm font-extrabold text-white">Filter</button>
            </div>
        </div>
    </form>

    <script>
        function districtFilter() {
            return {
                loadMunicipalities(district) {
                    if (!district) {
                        this.$refs.municipality.innerHTML = '<option value="">All municipalities</option>';
                        return;
                    }

                    fetch(`/api/hr/municipalities-by-district/${encodeURIComponent(district)}`)
                        .then(res => res.json())
                        .then(municipalities => {
                            let options = '<option value="">All municipalities</option>';
                            municipalities.forEach(m => {
                                const selected = '{{ request("permanent_municipality") }}' === m ? ' selected' : '';
                                options += `<option value="${m}"${selected}>${m}</option>`;
                            });
                            this.$refs.municipality.innerHTML = options;
                        })
                        .catch(err => console.error('Error loading municipalities:', err));
                },
                loadSections(stream) {
                    if (!stream) {
                        this.$refs.section.innerHTML = '<option value="">All sections</option>';
                        return;
                    }

                    fetch(`/api/hr/sections-by-stream/${encodeURIComponent(stream)}`)
                        .then(res => res.json())
                        .then(sections => {
                            let options = '<option value="">All sections</option>';
                            sections.forEach(s => {
                                const selected = '{{ request("section") }}' === s ? ' selected' : '';
                                options += `<option value="${s}"${selected}>${s}</option>`;
                            });
                            this.$refs.section.innerHTML = options;
                        })
                        .catch(err => console.error('Error loading sections:', err));
                }
            };
        }
    </script>

    @if($orphanUsers->isNotEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-amber-200 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-extrabold text-amber-900">{{ $orphanUsers->count() }} teacher/staff {{ Str::plural('account', $orphanUsers->count()) }} not yet in HR</p>
                    <p class="mt-0.5 text-xs font-medium text-amber-700">These users were created via Hajiri. Create an HR profile for each to manage them here.</p>
                </div>
            </div>
            <div class="divide-y divide-amber-100">
                @foreach($orphanUsers as $user)
                    @php $role = $user->roles->firstWhere('name', 'teacher') ? 'teacher' : 'staff'; @endphp
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-200 text-sm font-extrabold text-amber-900">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-extrabold text-gray-900 truncate">{{ $user->name }}</p>
                                <p class="text-xs font-medium text-gray-500 truncate">{{ $user->email }}{{ $user->device_id ? ' · Hajiri device #' . $user->device_id : '' }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="rounded-full border px-2.5 py-1 text-xs font-extrabold {{ $typeStyles[$role] ?? 'bg-gray-50 text-gray-600 border-gray-100' }}">
                                {{ $typeLabels[$role] ?? ucfirst($role) }}
                            </span>
                            @if(auth()->user()?->canAccess('hr.members.create'))
                                <a href="{{ route('admin.hr.members.create') }}?prefill_user={{ $user->id }}"
                                   class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-extrabold text-amber-800 hover:bg-amber-100 transition-colors">
                                    Create HR Profile
                                </a>
                            @endif
                            @if(auth()->user()?->canAccess('hr.members.delete'))
                                @if($user->library_clearance_hold)
                                    <button type="button" disabled title="{{ $user->library_clearance_message }}"
                                            class="cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs font-extrabold text-gray-400">
                                        Clearance Pending
                                    </button>
                                @else
                                    <form method="POST" action="{{ route('admin.hr.members.orphan-users.destroy', $user) }}"
                                          onsubmit="return confirm('Delete this user account? This removes the login/Hajiri account because it is not linked to HR yet.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-extrabold text-red-700 hover:bg-red-50 transition-colors">
                                            Delete User
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(auth()->user()?->canAccess('hr.members.delete'))
    {{-- Bulk-action toolbar — separate form to avoid nesting inside results --}}
    <form id="hr-bulk-form" method="POST" action="{{ route('admin.hr.members.bulk-destroy') }}">
        @csrf
        {{-- Hidden id inputs injected by JS before submit --}}
        <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-2.5 shadow-sm">
            <p class="text-sm font-semibold text-gray-500">
                Tick checkboxes in the table to select members.
                <span id="hr-bulk-count-label" class="hidden font-extrabold text-red-700"> <span id="hr-bulk-count">0</span> selected</span>
            </p>
            <button id="hr-bulk-btn" type="button" disabled
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-xs font-extrabold text-red-400 transition-colors disabled:cursor-not-allowed" style="opacity:.5">
                Delete Selected
            </button>
        </div>
    </form>
    @endif

    {{-- Results: standalone div so individual delete forms are never nested --}}
    <div id="hr-member-results" class="mt-3">
        @include('hr.members._table', ['members' => $members])
    </div>
</div>

{{-- Custom Delete Confirmation Modal --}}
<div id="hr-delete-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 hidden">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl mx-4">
        <h3 class="text-lg font-extrabold text-gray-900">Confirm Permanent Deletion</h3>
        <p id="hr-delete-modal-info" class="mt-2 text-sm text-gray-600"></p>
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="text-xs font-extrabold text-red-700 mb-2">Type <span class="font-black tracking-widest">DELETE</span> to confirm:</p>
            <input id="hr-delete-modal-input" type="text" placeholder="DELETE" autocomplete="off" spellcheck="false"
                class="w-full rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-bold tracking-widest focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200">
        </div>
        <div class="mt-4 flex justify-end gap-3">
            <button id="hr-delete-modal-cancel" type="button"
                class="rounded-lg border border-gray-200 px-5 py-2 text-sm font-extrabold text-gray-700 hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            <button id="hr-delete-modal-confirm" type="button" disabled
                class="rounded-lg bg-red-600 px-5 py-2 text-sm font-extrabold text-white transition-colors hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed">
                Delete
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterForm = document.getElementById('hr-member-filter-form');
    const results    = document.getElementById('hr-member-results');
    if (!filterForm || !results) return;

    const searchInput = filterForm.querySelector('[data-ajax-search]');
    let searchTimer = null;
    let controller  = null;

    const escapeRegex = v => String(v).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    const highlightMatches = () => {
        const query = String(searchInput?.value || '').trim();
        results.querySelectorAll('[data-highlight]').forEach(el => {
            const text = el.textContent || '';
            if (!query) { el.textContent = text; return; }
            const pattern = new RegExp(`(${escapeRegex(query)})`, 'ig');
            el.innerHTML = text.replace(pattern, '<mark class="rounded bg-yellow-200 px-0.5 font-black text-gray-950">$1</mark>');
        });
    };

    const currentUrl = pageUrl => {
        const params = new URLSearchParams(new FormData(filterForm));
        [...params.entries()].forEach(([k, v]) => { if (v === '') params.delete(k); });
        if (pageUrl) {
            const p = new URL(pageUrl, window.location.origin).searchParams;
            if (p.get('page')) params.set('page', p.get('page'));
        }
        const url = new URL(filterForm.action || window.location.pathname, window.location.origin);
        url.search = params.toString();
        return `${url.pathname}${url.search}`;
    };

    const loadMembers = async (pageUrl = null) => {
        if (controller) controller.abort();
        controller = new AbortController();
        const url = currentUrl(pageUrl);
        results.classList.add('opacity-60');
        try {
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const payload = await res.json();
            results.innerHTML = payload.html || '';
            window.history.replaceState({}, '', url);
            highlightMatches();
            wireSelectAll();
            clearBulkSelection();
        } catch (err) {
            if (err.name !== 'AbortError') console.error('Unable to load HR members:', err);
        } finally {
            results.classList.remove('opacity-60');
        }
    };

    filterForm.addEventListener('submit', e => { e.preventDefault(); loadMembers(); });
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadMembers(), 250);
    });
    filterForm.querySelectorAll('select').forEach(sel => sel.addEventListener('change', () => loadMembers()));
    results.addEventListener('click', e => {
        const link = e.target.closest('a[href]');
        if (!link || !link.closest('nav')) return;
        e.preventDefault();
        loadMembers(link.href);
    });

    // ── Bulk selection ──────────────────────────────────────────
    const bulkBtn        = document.getElementById('hr-bulk-btn');
    const bulkForm       = document.getElementById('hr-bulk-form');
    const bulkCountEl    = document.getElementById('hr-bulk-count');
    const bulkCountLabel = document.getElementById('hr-bulk-count-label');

    const updateBulkBar = () => {
        if (!bulkBtn) return;
        const n = results.querySelectorAll('.bulk-row-check:checked').length;
        if (n > 0) {
            bulkBtn.disabled = false;
            bulkBtn.style.opacity = '1';
            bulkBtn.className = 'rounded-lg border border-red-500 bg-red-600 px-4 py-2 text-xs font-extrabold text-white hover:bg-red-700 transition-colors';
            if (bulkCountEl) bulkCountEl.textContent = n;
            bulkCountLabel?.classList.remove('hidden');
        } else {
            bulkBtn.disabled = true;
            bulkBtn.style.opacity = '.5';
            bulkBtn.className = 'rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-xs font-extrabold text-red-400 transition-colors disabled:cursor-not-allowed';
            if (bulkCountEl) bulkCountEl.textContent = 0;
            bulkCountLabel?.classList.add('hidden');
        }
    };

    const wireSelectAll = () => {
        const selectAll = document.getElementById('bulk-select-all');
        if (!selectAll) return;
        selectAll.addEventListener('change', () => {
            results.querySelectorAll('.bulk-row-check:not(:disabled)').forEach(cb => { cb.checked = selectAll.checked; });
            updateBulkBar();
        });
    };

    const clearBulkSelection = () => {
        const selectAll = document.getElementById('bulk-select-all');
        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
        results.querySelectorAll('.bulk-row-check').forEach(cb => { cb.checked = false; });
        updateBulkBar();
    };

    results.addEventListener('change', e => {
        if (!e.target.classList.contains('bulk-row-check')) return;
        const allChecks = results.querySelectorAll('.bulk-row-check:not(:disabled)');
        const selectAll = document.getElementById('bulk-select-all');
        if (selectAll) {
            const n = results.querySelectorAll('.bulk-row-check:checked').length;
            selectAll.indeterminate = n > 0 && n < allChecks.length;
            selectAll.checked = n === allChecks.length;
            if (n === 0) { selectAll.checked = false; selectAll.indeterminate = false; }
        }
        updateBulkBar();
    });

    wireSelectAll();
    highlightMatches();
    clearBulkSelection();

    // ── Custom "type DELETE" modal ──────────────────────────────
    const modal        = document.getElementById('hr-delete-modal');
    const modalInput   = document.getElementById('hr-delete-modal-input');
    const modalInfo    = document.getElementById('hr-delete-modal-info');
    const modalConfirm = document.getElementById('hr-delete-modal-confirm');
    const modalCancel  = document.getElementById('hr-delete-modal-cancel');
    if (!modal) return;

    let pendingAction = null;

    const openModal = (info, action) => {
        pendingAction = action;
        modalInfo.textContent = info;
        modalInput.value = '';
        modalConfirm.disabled = true;
        modal.classList.remove('hidden');
        setTimeout(() => modalInput.focus(), 50);
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        pendingAction = null;
        modalInput.value = '';
        modalConfirm.disabled = true;
    };

    modalInput.addEventListener('input', () => {
        modalConfirm.disabled = modalInput.value !== 'DELETE';
    });
    modalInput.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !modalConfirm.disabled) modalConfirm.click();
        if (e.key === 'Escape') closeModal();
    });
    modalConfirm.addEventListener('click', () => {
        if (modalConfirm.disabled || !pendingAction) return;
        const action = pendingAction;
        closeModal();
        action();
    });
    modalCancel.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    // Single-delete forms: intercept submit
    document.addEventListener('submit', e => {
        const f = e.target;
        if (!f.classList.contains('hr-member-delete-form')) return;
        e.preventDefault();
        const name = f.dataset.memberName || 'this member';
        openModal(
            `Permanently remove "${name}" from HR master? This cannot be undone.`,
            () => f.submit()
        );
    });

    // Bulk delete button: gather IDs, inject into form, submit
    if (bulkBtn && bulkForm) {
        bulkBtn.addEventListener('click', () => {
            const n = results.querySelectorAll('.bulk-row-check:checked').length;
            if (n === 0) return;
            openModal(
                `Permanently delete ${n} selected member(s) and their linked logins? This cannot be undone.`,
                () => {
                    bulkForm.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
                    results.querySelectorAll('.bulk-row-check:checked').forEach(cb => {
                        const inp = document.createElement('input');
                        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = cb.value;
                        bulkForm.appendChild(inp);
                    });
                    bulkForm.submit();
                }
            );
        });
    }
});
</script>
@endpush
