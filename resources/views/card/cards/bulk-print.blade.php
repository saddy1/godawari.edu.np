@extends('card.layouts.app')
@section('title', request('type') === 'student' ? 'Print Student ID Cards' : (request('type') === 'staff_teacher' ? 'Print Staff / Teacher ID Cards' : 'Bulk Print Cards'))
@section('heading', request('type') === 'student' ? 'Student ID Card Print' : (request('type') === 'staff_teacher' ? 'Staff / Teacher ID Card Print' : 'Bulk Print — ID Cards'))

@php
    $selectedStream  = request('stream', '');
    $selectedSection = request('section', '');
    $selectedType    = request('type', '');
    $selectedStatus  = request('print_status', '');
    $selectedSearch  = request('q', '');
    $totalShown      = $students->count();
    $totalSelected   = $students->filter(fn($s) =>
        !($s->card_printed_at && $s->card_printed_at >= $s->updated_at)
    )->count();
@endphp

@section('sub-header')
<div class="bg-white border-b border-gray-200 shadow-sm">

    {{-- Row 1 — Filters + Action buttons --}}
    <form method="GET" id="bulkFilterForm"
          class="flex flex-wrap items-end gap-2 px-4 sm:px-8 py-3 border-b border-gray-100">

        {{-- Class / Dept --}}
        <div class="flex flex-col gap-0.5">
            <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Class / Dept</label>
            <select name="stream" id="filterStream"
                    class="h-8 border border-gray-200 rounded-lg px-2.5 text-sm text-gray-700
                           focus:outline-none focus:ring-2 focus:ring-blue-300 min-w-[130px]">
                <option value="">All Classes</option>
                @php
                    $allStreams = collect($filterOptions)
                        ->flatMap(fn($o) => array_keys($o['streams'] ?? []))
                        ->unique()->sort()->values();
                @endphp
                @foreach($allStreams as $stream)
                    <option value="{{ $stream }}" @selected($selectedStream === $stream)>{{ $stream }}</option>
                @endforeach
            </select>
        </div>

        {{-- Section --}}
        <div class="flex flex-col gap-0.5">
            <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Section</label>
            <select name="section" id="filterSection"
                    class="h-8 border border-gray-200 rounded-lg px-2.5 text-sm text-gray-700
                           focus:outline-none focus:ring-2 focus:ring-blue-300 min-w-[100px]">
                <option value="">All Sections</option>
                @php
                    $allSections = collect($filterOptions)
                        ->flatMap(fn($o) => collect($o['streams'] ?? [])->flatten())
                        ->unique()->sort()->values();
                @endphp
                @foreach($allSections as $sec)
                    <option value="{{ $sec }}" @selected($selectedSection === $sec)>{{ $sec }}</option>
                @endforeach
            </select>
        </div>

        {{-- Type --}}
        <div class="flex flex-col gap-0.5">
            <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Type</label>
            <select name="type"
                    class="h-8 border border-gray-200 rounded-lg px-2.5 text-sm text-gray-700
                           focus:outline-none focus:ring-2 focus:ring-blue-300">
                <option value="">All Types</option>
                <option value="student" @selected($selectedType === 'student')>Students</option>
                <option value="staff_teacher" @selected($selectedType === 'staff_teacher')>Staff &amp; Teachers</option>
                <option value="teacher" @selected($selectedType === 'teacher')>Teachers</option>
                <option value="staff"   @selected($selectedType === 'staff')>Staff</option>
            </select>
        </div>

        {{-- Search --}}
        <div class="flex flex-col gap-0.5 flex-1 min-w-[240px] max-w-[620px]">
            <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">
                Search <span id="ajaxSearchStatus" class="font-normal normal-case tracking-normal text-gray-300"></span>
            </label>
            <input type="search" name="q" id="ajaxSearch" value="{{ $selectedSearch }}"
                   placeholder="Name, ID or mobile" autocomplete="off"
                   class="h-8 w-full border border-gray-200 rounded-lg px-3 text-sm text-gray-700
                          focus:outline-none focus:ring-2 focus:ring-blue-300">
        </div>

        {{-- Print Status --}}
        <div class="flex flex-col gap-0.5">
            <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Status</label>
            <select name="print_status"
                    class="h-8 border border-gray-200 rounded-lg px-2.5 text-sm text-gray-700
                           focus:outline-none focus:ring-2 focus:ring-blue-300">
                <option value="">All</option>
                <option value="pending" @selected($selectedStatus === 'pending')>Not Printed</option>
                <option value="printed" @selected($selectedStatus === 'printed')>Already Printed</option>
            </select>
        </div>

        <button type="submit"
                class="h-8 px-4 bg-primary text-white rounded-lg text-sm font-medium
                       hover:bg-primary-light transition self-end">
            Filter
        </button>

        @if($selectedStream || $selectedSection || $selectedType || $selectedStatus || $selectedSearch)
        <a href="{{ route('bulk.index') }}"
           class="h-8 px-3 flex items-center rounded-lg text-sm text-gray-400
                  hover:bg-gray-100 transition self-end">
            Clear
        </a>
        @endif

        {{-- Push action buttons to the right --}}
        <div class="flex gap-2 ml-auto self-end">
            <button type="button" onclick="submitBulk('{{ route('bulk.generate') }}')"
                    class="h-8 inline-flex items-center gap-1.5 px-4 rounded-lg border border-gray-200
                           text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download PDF
            </button>

            <button type="button" onclick="submitBulk('{{ route('bulk.preview') }}', '_blank')"
                    class="h-8 inline-flex items-center gap-1.5 px-4 rounded-lg bg-primary text-white
                           text-sm font-semibold hover:bg-primary-light transition shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2
                             m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z
                             m8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Preview
            </button>
        </div>
    </form>

    {{-- Row 2 — Select-all + count + legend --}}
    <div class="flex items-center gap-3 px-4 sm:px-8 py-2 bg-gray-50/80 text-sm">
        <label class="flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" id="checkAll" class="accent-primary w-4 h-4">
            <span class="text-xs font-semibold text-gray-600">Select All</span>
        </label>
        <span class="text-gray-300">|</span>
        <button type="button" onclick="clearSelection()"
                class="text-xs text-gray-400 hover:text-gray-600 transition">Clear</button>
        <span class="text-gray-300">|</span>
        <span class="text-xs text-gray-500">
            <span class="font-semibold text-gray-800" id="selectedCount">{{ $totalSelected }}</span>
            selected across filters
        </span>

        <div class="ml-auto flex gap-4 text-xs text-gray-400">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-orange-400 inline-block"></span>Not printed
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>Printed
            </span>
        </div>
    </div>
</div>
@endsection

@section('content')

{{-- ════════════════════════════════════════════════════════════
     MEMBER LIST
     ══════════════════════════════════════════════════════════ --}}
<div class="-mx-4 sm:-mx-8 bg-white mb-4 sm:mb-8" id="studentList">

    @forelse($students as $student)
    @php
        $isPrinted  = $student->card_printed_at && $student->card_printed_at >= $student->updated_at;
        $typeColors = [
            'student' => 'bg-blue-100 text-blue-700',
            'teacher' => 'bg-purple-100 text-purple-700',
            'staff'   => 'bg-green-100 text-green-700',
        ];
        $tc = $typeColors[strtolower($student->member_type)] ?? 'bg-gray-100 text-gray-600';
    @endphp

    <div class="student-row flex items-center gap-3 px-4 sm:px-8 py-3
                border-b border-gray-100 transition
                {{ $isPrinted ? 'hover:bg-gray-50' : 'bg-orange-50/20 hover:bg-orange-50/40' }}"
         data-id="{{ $student->id }}"
         data-name="{{ $student->full_name }}"
         data-roll="{{ $student->roll_number }}"
         data-meta="{{ trim(($student->department_label ?: '') . ($student->section_label ? ' · ' . $student->section_label : '')) }}">

        {{-- Checkbox --}}
        <input type="checkbox" value="{{ $student->id }}"
               class="student-check accent-primary w-4 h-4 flex-shrink-0 cursor-pointer"
               {{ $isPrinted ? '' : 'checked' }}>

        {{-- Avatar --}}
        <img src="{{ $student->photo_url }}" alt=""
             class="w-9 h-9 rounded-full object-cover bg-gray-100 flex-shrink-0 ring-1 ring-gray-200">

        {{-- Name + meta --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-800 truncate leading-tight">
                {{ $student->full_name }}
            </p>
            <p class="text-xs text-gray-400 truncate mt-0.5">
                {{ $student->roll_number }}
                @if($student->department_label) · {{ $student->department_label }} @endif
                @if($student->section_label) · {{ $student->section_label }} @endif
            </p>
        </div>

        {{-- Type badge --}}
        <span class="hidden sm:inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0 {{ $tc }}">
            {{ ucfirst($student->member_type) }}
        </span>

        {{-- Print status --}}
        @if($isPrinted)
            <span class="hidden sm:inline-flex items-center gap-1 text-[11px] font-semibold
                         bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full flex-shrink-0">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                {{ $student->card_printed_at->format('d M Y') }}
            </span>
        @else
            <span class="hidden sm:inline-flex items-center gap-1 text-[11px] font-semibold
                         bg-orange-100 text-orange-600 px-2.5 py-1 rounded-full flex-shrink-0">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Not Printed
            </span>
        @endif

        {{-- Updated --}}
        <span class="hidden lg:block text-[11px] text-gray-400 flex-shrink-0 w-20 text-right">
            {{ $student->updated_at->diffForHumans(null, true) }}
        </span>

        {{-- Remove --}}
        <button type="button" onclick="removeRow(this)"
                title="Remove from queue"
                class="w-7 h-7 flex items-center justify-center rounded-lg
                       text-gray-300 hover:text-red-400 hover:bg-red-50 transition flex-shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    @empty
    <div class="py-24 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857
                     M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857
                     m0 0a5.002 5.002 0 019.288 0"/>
        </svg>
        <p class="text-gray-500 font-semibold">No members match the current filters</p>
        <a href="{{ route('bulk.index') }}" class="mt-2 inline-block text-sm text-blue-500 hover:underline">
            Clear filters
        </a>
    </div>
    @endforelse

    {{-- Footer count --}}
    @if($totalShown > 0)
    <div class="px-4 sm:px-8 py-3 bg-gray-50 border-t border-gray-100 text-xs text-gray-400 text-right">
        <span class="font-semibold text-gray-600" id="footerCount">{{ $totalSelected }}</span>
        selected across filters
    </div>
    @endif
</div>

{{-- Hidden form — card_types is always "id" --}}
<form id="bulkForm" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="card_types[]" value="id">
    <div id="hiddenStudents"></div>
</form>

@endsection

@push('scripts')
<script>
const selectionKey = 'bulkPrintSelectedStudents:' + @json($selectedType ?: 'all');
let selectedStudents = {};

function loadSelection() {
    try {
        selectedStudents = JSON.parse(localStorage.getItem(selectionKey) || '{}') || {};
    } catch (e) {
        selectedStudents = {};
    }

    if (Object.keys(selectedStudents).length === 0) {
        document.querySelectorAll('.student-check:checked').forEach(rememberStudent);
    }
}

function saveSelection() {
    localStorage.setItem(selectionKey, JSON.stringify(selectedStudents));
}

function rememberStudent(cb) {
    const row = cb.closest('.student-row');
    if (!row) return;

    selectedStudents[cb.value] = {
        id: cb.value,
        name: row.dataset.name || '',
        roll: row.dataset.roll || '',
        meta: row.dataset.meta || ''
    };
}

function forgetStudent(id) {
    delete selectedStudents[id];
}

function syncVisibleChecks() {
    document.querySelectorAll('.student-check').forEach(cb => {
        cb.checked = Object.prototype.hasOwnProperty.call(selectedStudents, cb.value);
    });
}

function syncCount() {
    const n = Object.keys(selectedStudents).length;
    document.getElementById('selectedCount').textContent = n;
    const fc = document.getElementById('footerCount');
    if (fc) fc.textContent = n;

    const all = [...document.querySelectorAll('.student-check')]
        .filter(c => c.closest('.student-row')?.style.display !== 'none');
    const chk = document.getElementById('checkAll');
    const visibleChecked = all.filter(c => c.checked).length;
    chk.checked = all.length > 0 && visibleChecked === all.length;
    chk.indeterminate = !chk.checked && visibleChecked > 0;
}

function bindStudentChecks() {
    document.querySelectorAll('.student-check').forEach(cb => {
        if (cb.dataset.bound === '1') return;
        cb.dataset.bound = '1';
        cb.addEventListener('change', function () {
            if (this.checked) rememberStudent(this);
            else forgetStudent(this.value);
            saveSelection();
            syncCount();
        });
    });
}

document.getElementById('checkAll').addEventListener('change', function () {
    selectAll(this.checked);
});

loadSelection();
bindStudentChecks();
syncVisibleChecks();
syncCount();

function selectAll(val) {
    document.querySelectorAll('.student-check').forEach(cb => {
        const row = cb.closest('.student-row');
        if (row && row.style.display !== 'none') {
            cb.checked = val;
            if (val) rememberStudent(cb);
            else forgetStudent(cb.value);
        }
    });
    saveSelection();
    document.getElementById('checkAll').checked = val;
    syncCount();
}

function clearSelection() {
    selectedStudents = {};
    saveSelection();
    syncVisibleChecks();
    syncCount();
}

function removeRow(btn) {
    const row = btn.closest('.student-row');
    if (row) {
        const cb = row.querySelector('.student-check');
        if (cb) {
            cb.checked = false;
            forgetStudent(cb.value);
            saveSelection();
        }
        row.style.display = 'none';
        syncCount();
    }
}

function submitBulk(action, target = '_self') {
    const ids = Object.keys(selectedStudents);

    if (ids.length === 0) {
        alert('Please select at least one member.');
        return;
    }

    const form = document.getElementById('bulkForm');
    form.action = action;
    form.target = target;
    document.getElementById('hiddenStudents').innerHTML =
        ids.map(id => `<input type="hidden" name="student_ids[]" value="${id}">`).join('');
    if (target === '_self') localStorage.removeItem(selectionKey);
    form.submit();
}

const filterForm = document.getElementById('bulkFilterForm');
const ajaxSearch = document.getElementById('ajaxSearch');
const ajaxSearchStatus = document.getElementById('ajaxSearchStatus');
let ajaxSearchTimer = null;
let ajaxSearchController = null;

function setAjaxSearchStatus(text) {
    if (ajaxSearchStatus) ajaxSearchStatus.textContent = text ? `(${text})` : '';
}

function refreshStudentsAjax() {
    const params = new URLSearchParams(new FormData(filterForm));
    const action = filterForm.getAttribute('action') || window.location.pathname;
    const url = `${action}?${params.toString()}`;

    if (ajaxSearchController) ajaxSearchController.abort();
    ajaxSearchController = new AbortController();
    setAjaxSearchStatus('searching');

    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        signal: ajaxSearchController.signal
    })
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextList = doc.getElementById('studentList');
            const currentList = document.getElementById('studentList');
            if (!nextList || !currentList) return;

            currentList.innerHTML = nextList.innerHTML;
            bindStudentChecks();
            syncVisibleChecks();
            syncCount();
            window.history.replaceState({}, '', url);
            setAjaxSearchStatus('');
        })
        .catch(err => {
            if (err.name !== 'AbortError') setAjaxSearchStatus('failed');
        });
}

ajaxSearch?.addEventListener('input', function () {
    clearTimeout(ajaxSearchTimer);
    ajaxSearchTimer = setTimeout(refreshStudentsAjax, 300);
});

// Dynamic section filter
(function () {
    const opts       = @json($filterOptions);
    const streamSel  = document.getElementById('filterStream');
    const sectionSel = document.getElementById('filterSection');
    const selSec     = @json($selectedSection);

    function refresh() {
        const sections = [];
        Object.values(opts).forEach(org => {
            (org.streams[streamSel.value] || []).forEach(s => {
                if (s && !sections.includes(s)) sections.push(s);
            });
        });
        sections.sort();
        sectionSel.innerHTML = '<option value="">All Sections</option>' +
            sections.map(s =>
                `<option value="${s}"${s === selSec ? ' selected' : ''}>${s}</option>`
            ).join('');
        sectionSel.disabled = sections.length === 0 && streamSel.value !== '';
    }

    streamSel.addEventListener('change', refresh);
    refresh();
})();
</script>
@endpush
