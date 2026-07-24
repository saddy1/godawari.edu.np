@extends('library-admin.layouts.app')

@section('title', 'Book Copies')

@push('styles')
<style>
    .library-barcode-checkbox {
        -webkit-appearance: checkbox !important;
        appearance: auto !important;
        width: 18px !important;
        height: 18px !important;
        border: 1px solid #94a3b8 !important;
        border-radius: 4px !important;
        background-color: #fff !important;
        accent-color: #047857;
        cursor: pointer;
    }
</style>
@endpush

@section('library-content')
<div class="mx-auto max-w-7xl space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div>
            <h2 class="text-2xl font-black text-slate-950">{{ $book->title }}</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">{{ $book->author }} @if($book->isbn) · ISBN {{ $book->isbn }} @endif</p>
            <p class="mt-1 text-sm font-semibold text-slate-500">{{ $book->category?->name ?: 'No category' }} · {{ $book->shelf_location ?: 'No shelf location' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.library.books.edit', $book) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Edit</a>
            <a href="{{ route('admin.library.books.index') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-black text-white hover:bg-slate-800">All Books</a>
        </div>
    </div>

    <form method="POST"
          action="{{ route('admin.library.books.copies.store', $book) }}"
          class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
          data-copy-confirm-form
          data-next-accession="{{ $nextAccessionNo }}">
        @csrf
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Add Copies</p>
                <p class="mt-1 text-sm font-semibold text-slate-500">Accession numbers are system managed. Next accession starts from <span class="font-black text-slate-800">{{ $nextAccessionNo }}</span>.</p>
            </div>
            <button type="button" data-reveal-copy-panel class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white hover:bg-emerald-800">Add Copy</button>
        </div>

        <div data-copy-panel class="mt-4 hidden rounded-xl border border-emerald-100 bg-emerald-50 p-4">
            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto]">
                <label class="text-xs font-black uppercase tracking-widest text-emerald-900">Number of Copies
                    <input id="copiesCountInput" name="copies_count" type="number" min="1" max="500" required placeholder="Enter copy quantity" class="mt-1.5 h-12 w-full rounded-xl border border-emerald-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                </label>
                <button class="self-end rounded-xl bg-slate-950 px-5 py-3 text-sm font-black text-white hover:bg-slate-800">Continue</button>
            </div>

            <div class="mt-4 grid gap-3 text-sm font-semibold text-emerald-900 sm:grid-cols-3">
                <div class="rounded-xl bg-white/75 p-3">
                    <p class="text-xs font-black uppercase tracking-widest text-emerald-700">Next Acc. No.</p>
                    <p class="mt-1 text-xl font-black text-slate-950">{{ $nextAccessionNo }}</p>
                </div>
                <div class="rounded-xl bg-white/75 p-3">
                    <p class="text-xs font-black uppercase tracking-widest text-emerald-700">Copies</p>
                    <p class="mt-1 text-xl font-black text-slate-950" data-copy-preview-count>0</p>
                </div>
                <div class="rounded-xl bg-white/75 p-3">
                    <p class="text-xs font-black uppercase tracking-widest text-emerald-700">Accession Range</p>
                    <p class="mt-1 text-xl font-black text-slate-950" data-copy-preview-range>-</p>
                </div>
            </div>
        </div>
    </form>

    <div data-copy-confirm-modal class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/60 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="border-b border-slate-200 p-5">
                <p class="text-xs font-black uppercase tracking-widest text-emerald-700">Confirm Copies</p>
                <h2 class="mt-1 text-xl font-black text-slate-950">Add book copies?</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">The system will assign accession numbers automatically.</p>
            </div>
            <div class="space-y-3 p-5 text-sm font-semibold text-slate-600">
                <div class="flex justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3">
                    <span>Copies</span>
                    <span class="font-black text-slate-950" data-confirm-copy-count>0</span>
                </div>
                <div class="flex justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3">
                    <span>Accession range</span>
                    <span class="font-black text-emerald-800" data-confirm-copy-range>-</span>
                </div>
            </div>
            <div class="flex gap-2 border-t border-slate-200 p-5">
                <button type="button" data-copy-confirm-cancel class="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="button" data-copy-confirm-submit class="flex-1 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-black text-white hover:bg-emerald-800">Confirm</button>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <span class="text-sm font-black text-slate-700">Print Barcodes</span>
        <input type="number" id="fromBarcode" placeholder="From" class="w-32 rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold outline-none focus:border-emerald-700">
        <input type="number" id="toBarcode" placeholder="To" class="w-32 rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold outline-none focus:border-emerald-700">
        <div class="flex overflow-hidden rounded-lg border border-slate-200 text-sm">
            <button type="button" id="layout1Btn" onclick="setLayout(1)" class="bg-slate-950 px-4 py-2 font-black text-white">1-up</button>
            <button type="button" id="layout3Btn" onclick="setLayout(3)" class="bg-white px-4 py-2 font-black text-slate-600 hover:bg-slate-50">3-up</button>
        </div>
        <span id="layoutInfo" class="text-xs font-bold text-slate-400">50mm x 25mm</span>
        <button type="button" onclick="printRange()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-black text-white">Print Range</button>
        <button type="button" onclick="printSelected()" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-black text-white">Print Selected</button>
        <span id="selectedBarcodeCount" class="text-xs font-bold text-slate-500">0 selected</span>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-widest text-slate-500">
                    <tr>
                    <th class="px-5 py-3 text-center">
                        <label for="selectAll" class="inline-flex cursor-pointer items-center gap-2">
                            <input type="checkbox" id="selectAll" class="library-barcode-checkbox">
                            <span>Select</span>
                        </label>
                    </th>
                    <th class="px-5 py-3">Accession</th>
                    <th class="px-5 py-3">Barcode Label</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Issued To</th>
                    <th class="px-5 py-3">Due Date</th>
                    <th class="px-5 py-3 text-center">Print</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $copy)
                    @php($barcodeValue = $copy->barcode ?: $copy->accession_no)
                    <tr>
                        <td class="px-5 py-3 text-center">
                            <input type="checkbox" class="barcode-checkbox library-barcode-checkbox" value="{{ $barcodeValue }}" aria-label="Select barcode {{ $barcodeValue }}">
                        </td>
                        <td class="px-5 py-3 font-black text-slate-950">{{ $copy->accession_no }}</td>
                        <td class="px-5 py-3">
                            <svg class="inline-barcode block max-w-[190px]" data-barcode="{{ $barcodeValue }}" aria-label="Barcode {{ $barcodeValue }}"></svg>
                            <span class="mt-1 block font-mono text-xs font-black tracking-wider text-slate-700">{{ $barcodeValue }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $copy->status === 'available' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ ucfirst($copy->status) }}</span>
                        </td>
                        <td class="px-5 py-3 font-semibold text-slate-600">{{ $copy->activeLoan?->borrower_name ?: '-' }}</td>
                        <td class="px-5 py-3 font-semibold text-slate-600">{{ $copy->activeLoan?->due_date?->format('Y-m-d') ?: '-' }}</td>
                        <td class="px-5 py-3 text-center">
                            <button type="button" onclick="printSingle(@js($barcodeValue))" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50">Print</button>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <form method="POST" action="{{ route('admin.library.copies.destroy', $copy) }}" onsubmit="return confirm('Delete this copy?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-black text-red-700 hover:bg-red-50">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-12 text-center font-bold text-slate-400">No copies added.</td></tr>
                @endforelse
            </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">{{ $items->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
const copyForm = document.querySelector('[data-copy-confirm-form]');
const copyInput = document.getElementById('copiesCountInput');
const copyModal = document.querySelector('[data-copy-confirm-modal]');
const copyPanel = document.querySelector('[data-copy-panel]');
const revealCopyPanelButton = document.querySelector('[data-reveal-copy-panel]');
let copyConfirmReady = false;

function copyCountValue() {
    return Math.max(0, parseInt(copyInput?.value || '0', 10) || 0);
}

function copyRange(count) {
    const start = parseInt(copyForm?.dataset.nextAccession || '1', 10) || 1;
    if (count < 1) return '-';
    const end = start + count - 1;
    return start === end ? String(start) : `${start} - ${end}`;
}

function refreshCopyPreview() {
    const count = copyCountValue();
    document.querySelectorAll('[data-copy-preview-count]').forEach(node => node.textContent = count);
    document.querySelectorAll('[data-copy-preview-range]').forEach(node => node.textContent = copyRange(count));
}

function openCopyConfirm() {
    const count = copyCountValue();
    if (count < 1) return false;
    document.querySelector('[data-confirm-copy-count]').textContent = count;
    document.querySelector('[data-confirm-copy-range]').textContent = copyRange(count);
    copyModal?.classList.remove('hidden');
    copyModal?.classList.add('flex');
    return true;
}

function closeCopyConfirm() {
    copyModal?.classList.add('hidden');
    copyModal?.classList.remove('flex');
}

revealCopyPanelButton?.addEventListener('click', function () {
    copyPanel?.classList.remove('hidden');
    if (copyInput && !copyInput.value) copyInput.value = 1;
    copyInput?.focus();
    refreshCopyPreview();
});

copyInput?.addEventListener('input', refreshCopyPreview);
refreshCopyPreview();

copyForm?.addEventListener('submit', function (event) {
    if (copyConfirmReady) return;
    event.preventDefault();
    if (openCopyConfirm() === false) copyInput?.focus();
});

document.querySelector('[data-copy-confirm-cancel]')?.addEventListener('click', closeCopyConfirm);
document.querySelector('[data-copy-confirm-submit]')?.addEventListener('click', function () {
    copyConfirmReady = true;
    closeCopyConfirm();
    copyForm?.submit();
});

let currentLayout = 1;

function setLayout(n) {
    currentLayout = n;
    document.getElementById('layout1Btn').className = n === 1 ? 'bg-slate-950 px-4 py-2 font-black text-white' : 'bg-white px-4 py-2 font-black text-slate-600 hover:bg-slate-50';
    document.getElementById('layout3Btn').className = n === 3 ? 'bg-slate-950 px-4 py-2 font-black text-white' : 'bg-white px-4 py-2 font-black text-slate-600 hover:bg-slate-50';
    document.getElementById('layoutInfo').textContent = n === 1 ? '50mm x 25mm' : '102mm x 20mm, 3 per row';
}

const selectAllBarcodes = document.getElementById('selectAll');
const barcodeCheckboxes = [...document.querySelectorAll('.barcode-checkbox')];

function refreshBarcodeSelection() {
    const selectedCount = barcodeCheckboxes.filter(checkbox => checkbox.checked).length;
    const selectedCountLabel = document.getElementById('selectedBarcodeCount');

    if (selectedCountLabel) selectedCountLabel.textContent = `${selectedCount} selected`;
    if (!selectAllBarcodes) return;

    selectAllBarcodes.checked = barcodeCheckboxes.length > 0 && selectedCount === barcodeCheckboxes.length;
    selectAllBarcodes.indeterminate = selectedCount > 0 && selectedCount < barcodeCheckboxes.length;
}

selectAllBarcodes?.addEventListener('change', function () {
    barcodeCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
    refreshBarcodeSelection();
});

barcodeCheckboxes.forEach(checkbox => checkbox.addEventListener('change', refreshBarcodeSelection));
refreshBarcodeSelection();

document.querySelectorAll('.inline-barcode').forEach(barcode => {
    if (typeof JsBarcode !== 'function' || !barcode.dataset.barcode) return;

    JsBarcode(barcode, barcode.dataset.barcode, {
        format: 'CODE128',
        width: 1.5,
        height: 36,
        displayValue: false,
        margin: 0
    });
});

function printSingle(barcode) {
    printMultiple([barcode]);
}

function printSelected() {
    const selected = [...document.querySelectorAll('.barcode-checkbox:checked')].map(cb => cb.value);
    if (!selected.length) {
        alert('Select at least one barcode');
        return;
    }
    printMultiple(selected);
}

function printRange() {
    const from = parseInt(document.getElementById('fromBarcode').value);
    const to = parseInt(document.getElementById('toBarcode').value);
    if (!from || !to || from > to) {
        alert('Invalid range');
        return;
    }
    const list = [];
    for (let i = from; i <= to; i++) list.push(i.toString());
    printMultiple(list);
}

function printMultiple(barcodes) {
    const win = window.open('', '_blank');
    if (!win) {
        alert('Allow popups');
        return;
    }

    const content = currentLayout === 1
        ? barcodes.map(code => `<div class="page"><svg id="b-${code}"></svg><div class="num">${code}</div></div>`).join('')
        : (() => {
            const rows = [];
            for (let i = 0; i < barcodes.length; i += 3) rows.push(barcodes.slice(i, i + 3));
            return rows.map(row => {
                while (row.length < 3) row.push('');
                return `<div class="row">` + row.map(code => code ? `<div class="label"><svg id="b-${code}"></svg><div class="num">${code}</div></div>` : `<div class="label"></div>`).join('') + `</div>`;
            }).join('');
        })();

    const css = currentLayout === 1 ? `
        @page { size: 50mm 25mm; margin: 0; }
        body { margin:0; }
        .page { width:100%; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center; page-break-after:always; }
        svg { width:100%; }
        .num { font-size:11px; font-weight:bold; margin-top:4px; }
    ` : `
        @page { size: 102mm 20mm; margin:0; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { width:100mm; }
        .row { width:102mm; height:20mm; display:flex; page-break-after:always; break-after:page; }
        .label { width:34mm; height:20mm; overflow:hidden; padding:1mm; display:flex; flex-direction:column; justify-content:center; align-items:center; }
        .num { font-size:6pt; font-weight:bold; margin-top:.5mm; line-height:1; text-align:center; }
    `;

    const barcodeJS = barcodes.map(code => `
        if (document.getElementById("b-${code}")) {
            JsBarcode("#b-${code}", "${code}", {
                format: "CODE128",
                width: ${currentLayout === 1 ? 2 : 1.5},
                height: ${currentLayout === 1 ? 50 : 32},
                displayValue: false,
                margin: 0
            });
        }
    `).join('');

    win.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script><style>${css}<\/style></head><body>${content}<script>window.onload=function(){${barcodeJS}setTimeout(()=>{window.print();window.close();},600);};<\/script></body></html>`);
    win.document.close();
}
</script>
@endpush
