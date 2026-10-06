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
            <p class="mt-1 text-sm font-semibold text-slate-500">{{ $book->author }} @if($book->isbn) · ISBN {{ $book->isbn }} @endif @if($book->edition) · Edition {{ $book->edition }} @endif @if($book->volume) · {{ $book->volume }} @endif @if($book->language) · {{ $book->language }} @endif</p>
            <p class="mt-1 text-sm font-semibold text-slate-500">{{ $book->category?->name ?: 'No category' }} · {{ $book->shelf_location ?: 'No shelf location' }}</p>

            @if($book->precededBy)
                <p class="mt-2 text-sm font-semibold text-amber-700">New edition of
                    <a href="{{ route('admin.library.books.show', $book->precededBy) }}" class="underline hover:no-underline">{{ $book->precededBy->title }}</a>
                    @if($book->precededBy->edition) (Edition {{ $book->precededBy->edition }}) @endif
                </p>
            @endif

            @if($book->laterEditions->isNotEmpty())
                <div class="mt-2 text-sm font-semibold text-emerald-700">
                    Other editions in catalog:
                    @foreach($book->laterEditions as $later)
                        <a href="{{ route('admin.library.books.show', $later) }}" class="underline hover:no-underline">{{ $later->title }}{{ $later->edition ? ' (Edition '.$later->edition.')' : '' }}</a>{{ !$loop->last ? ',' : '' }}
                    @endforeach
                </div>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.library.books.edit', $book) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Edit</a>
            <a href="{{ route('admin.library.books.index') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-black text-white hover:bg-slate-800">All Books</a>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Accession Log</p>
            <span class="text-xs font-bold text-slate-400">{{ $accessionBatches->count() }} batch{{ $accessionBatches->count() === 1 ? '' : 'es' }} · {{ $accessionBatches->sum('copy_count') }} total copies</span>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($accessionBatches as $index => $batch)
                <div class="flex flex-wrap items-center justify-between gap-3 py-2.5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-xs font-black text-emerald-700">{{ $index + 1 }}</span>
                        <div>
                            <p class="text-sm font-black text-slate-900">Acc. {{ (int) $batch->min_acc === (int) $batch->max_acc ? $batch->min_acc : $batch->min_acc.' – '.$batch->max_acc }}</p>
                            <p class="text-xs font-semibold text-slate-400">{{ $index === 0 ? 'First entry' : 'Entry #'.($index + 1) }} · {{ $batch->copy_count }} cop{{ (int) $batch->copy_count === 1 ? 'y' : 'ies' }} added</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-slate-400">{{ \Illuminate\Support\Carbon::parse($batch->created_at)->format('d M Y, h:i A') }}</span>
                </div>
            @empty
                <p class="py-6 text-center text-sm font-bold text-slate-400">No copies added yet.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Add Copies</p>
                <p class="mt-1 text-sm font-semibold text-slate-500">Accession numbers are system managed. Next accession starts from <span class="font-black text-slate-800">{{ $nextAccessionNo }}</span>.</p>
            </div>
            <button type="button" data-reveal-copy-panel class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white hover:bg-emerald-800">Add Copy</button>
        </div>

        <div data-copy-panel class="mt-4 hidden">
            <div class="flex gap-2 rounded-xl border border-slate-200 bg-slate-50 p-1.5">
                <button type="button" data-copy-mode-btn="same" class="flex-1 rounded-lg px-4 py-2.5 text-sm font-black transition">Same edition</button>
                <button type="button" data-copy-mode-btn="edition" class="flex-1 rounded-lg px-4 py-2.5 text-sm font-black transition">New edition</button>
            </div>

            <form method="POST"
                  action="{{ route('admin.library.books.copies.store', $book) }}"
                  data-copy-mode-panel="same"
                  data-copy-confirm-form
                  data-next-accession="{{ $nextAccessionNo }}"
                  class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                @csrf
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
            </form>

            <form method="POST"
                  action="{{ route('admin.library.books.editions.store', $book) }}"
                  data-copy-mode-panel="edition"
                  class="mt-4 hidden rounded-xl border border-amber-100 bg-amber-50 p-4">
                @csrf
                <p class="mb-3 text-sm font-semibold leading-6 text-amber-900">This creates a <span class="font-black">new, separate catalog record</span> linked to this one as its previous edition. Fields below are pre-filled from the current record — edit whatever changed (ISBN, edition, author, etc.).</p>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900 md:col-span-2">Book Title
                        <input name="title" required value="{{ $book->title }}" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Author
                        <input name="author" required value="{{ $book->author }}" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Category
                        <select name="library_category_id" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                            <option value="">No category</option>
                            @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}" @selected($book->library_category_id === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">ISBN
                        <input name="isbn" value="{{ $book->isbn }}" placeholder="New ISBN, if any" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Edition
                        <input name="edition" value="{{ $book->edition }}" placeholder="Example: 2nd" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Volume
                        <input name="volume" value="{{ $book->volume }}" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Language
                        <input name="language" value="{{ $book->language }}" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Publisher
                        <input name="publisher" value="{{ $book->publisher }}" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Publication Year
                        <input name="publication_year" type="number" min="1000" max="{{ now()->year + 1 }}" value="{{ $book->publication_year }}" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Pages
                        <input name="pages" type="number" min="1" value="{{ $book->pages }}" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Price
                        <input name="price" type="number" step="0.01" min="0" value="{{ $book->price }}" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Shelf Location
                        <input name="shelf_location" value="{{ $book->shelf_location }}" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Source
                        <input name="source" value="{{ $book->source }}" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900 md:col-span-2">Description
                        <textarea name="description" rows="3" class="mt-1.5 w-full rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">{{ $book->description }}</textarea>
                    </label>
                    <label class="text-xs font-black uppercase tracking-widest text-amber-900">Number of Copies
                        <input name="copies_count" type="number" min="1" max="500" required value="1" class="mt-1.5 h-12 w-full rounded-xl border border-amber-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none focus:border-amber-700 focus:ring-4 focus:ring-amber-100">
                    </label>
                </div>

                <button class="mt-4 w-full rounded-xl bg-amber-600 px-5 py-3 text-sm font-black text-white hover:bg-amber-700">Add as New Edition</button>
            </form>
        </div>
    </div>

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

const copyModeButtons = [...document.querySelectorAll('[data-copy-mode-btn]')];
const copyModePanels = [...document.querySelectorAll('[data-copy-mode-panel]')];

function setCopyMode(mode) {
    copyModePanels.forEach(panel => panel.classList.toggle('hidden', panel.dataset.copyModePanel !== mode));
    copyModeButtons.forEach(button => {
        const active = button.dataset.copyModeBtn === mode;
        button.classList.toggle('bg-white', active);
        button.classList.toggle('shadow-sm', active);
        button.classList.toggle('text-slate-950', active);
        button.classList.toggle('text-slate-500', !active);
    });
    if (mode === 'same') copyInput?.focus();
}

copyModeButtons.forEach(button => button.addEventListener('click', () => setCopyMode(button.dataset.copyModeBtn)));
setCopyMode('same');

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
