@extends('library-admin.layouts.app')

@section('title', $book->exists ? 'Edit Book' : 'Add Book')

@php
    $isEditing = $book->exists;
    $pageTitle = $isEditing ? 'Edit Book Record' : 'Add New Book';
    $submitLabel = $isEditing ? 'Update Book' : 'Save Book';
    $backRoute = $isEditing ? route('admin.library.books.show', $book) : route('admin.library.books.index');
@endphp

@section('library-content')
<form method="POST"
      action="{{ $isEditing ? route('admin.library.books.update', $book) : route('admin.library.books.store') }}"
      class="mx-auto max-w-7xl space-y-6"
      data-copy-confirm-form
      data-next-accession="{{ $nextAccessionNo ?? 1 }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-emerald-950 via-emerald-900 to-slate-900 px-5 py-5 text-white sm:px-7">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-2 flex flex-wrap items-center gap-2 text-xs font-black uppercase tracking-[0.24em] text-emerald-100">
                        <span>Library</span>
                        <span class="h-1 w-1 rounded-full bg-amber-400"></span>
                        <span>Catalog</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight sm:text-3xl">{{ $pageTitle }}</h1>
                    <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-emerald-50">
                        Maintain book details, classification, shelf information, and copy quantity from one place.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ $backRoute }}" class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-black text-white hover:bg-white/15">Cancel</a>
                    <button class="rounded-xl bg-amber-400 px-5 py-2.5 text-sm font-black text-slate-950 shadow-sm hover:bg-amber-300">{{ $submitLabel }}</button>
                </div>
            </div>
        </div>

        <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-6 p-5 sm:p-7">
                <section class="space-y-4">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-lg font-black text-slate-950">Core Details</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Required information used in search, issue, and report screens.</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-widest text-emerald-800">Required</span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="relative text-xs font-black uppercase tracking-widest text-slate-500 md:col-span-2">Book Title
                            <input id="titleInput" name="title" value="{{ old('title', $book->title) }}" required autocomplete="off" placeholder="Enter book title"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                            <div id="titleSuggest" class="relative normal-case tracking-normal"></div>
                        </label>

                        <label class="relative text-xs font-black uppercase tracking-widest text-slate-500">Author
                            <input id="authorInput" name="author" value="{{ old('author', $book->author) }}" required autocomplete="off" placeholder="Author name"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                            <div id="authorSuggest" class="relative normal-case tracking-normal"></div>
                        </label>

                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Category
                            <select name="library_category_id"
                                    class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                                <option value="">No category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('library_category_id', $book->library_category_id) === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-lg font-black text-slate-950">Publication Details</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Optional metadata for catalog filtering and physical identification.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">ISBN
                            <input name="isbn" value="{{ old('isbn', $book->isbn) }}" placeholder="ISBN number"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                        </label>

                        <label class="relative text-xs font-black uppercase tracking-widest text-slate-500">Publisher
                            <input id="publisherInput" name="publisher" value="{{ old('publisher', $book->publisher) }}" autocomplete="off" placeholder="Publisher name"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                            <div id="publisherSuggest" class="relative normal-case tracking-normal"></div>
                        </label>

                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Publication Year
                            <input name="publication_year" value="{{ old('publication_year', $book->publication_year) }}" type="number" min="1000" max="{{ now()->year + 1 }}" placeholder="{{ now()->year }}"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                        </label>

                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Edition
                            <input name="edition" value="{{ old('edition', $book->edition) }}" placeholder="Example: 2nd"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                        </label>

                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Pages
                            <input name="pages" value="{{ old('pages', $book->pages) }}" type="number" min="1" placeholder="Total pages"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                        </label>

                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Price
                            <input name="price" value="{{ old('price', $book->price) }}" type="number" step="0.01" min="0" placeholder="0.00"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                        </label>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-lg font-black text-slate-950">Location & Notes</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Use shelf and source fields to keep the physical collection easy to audit.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Shelf Location
                            <input name="shelf_location" value="{{ old('shelf_location', $book->shelf_location) }}" placeholder="Rack / shelf / section"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                        </label>

                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Source
                            <input name="source" value="{{ old('source', $book->source) }}" placeholder="Purchased, donated, grant..."
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                        </label>

                        <label class="text-xs font-black uppercase tracking-widest text-slate-500 md:col-span-2">Description
                            <textarea name="description" rows="4" placeholder="Short notes about this title"
                                      class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">{{ old('description', $book->description) }}</textarea>
                        </label>
                    </div>
                </section>
            </div>

            <aside class="border-t border-slate-200 bg-slate-50 p-5 sm:p-7 lg:border-l lg:border-t-0">
                <div class="sticky top-6 space-y-4">
                    @unless($isEditing)
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-black text-slate-950">Initial Copies</h2>
                                    <p class="mt-1 text-sm font-semibold text-slate-500">Enter quantity. Accession numbers are assigned automatically.</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">Stock</span>
                            </div>

                            <div class="space-y-4">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Number of Copies
                                    <input id="copiesCountInput" name="copies_count" value="{{ old('copies_count', 1) }}" type="number" min="1" max="500" required
                                           class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                                </label>
                            </div>

                            <div class="mt-4 grid gap-3 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-semibold leading-6 text-emerald-900">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Next accession no.</span>
                                    <span class="font-black">{{ $nextAccessionNo ?? 1 }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Will create</span>
                                    <span class="font-black"><span data-copy-preview-count>1</span> copy/copies</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Accession range</span>
                                    <span class="font-black" data-copy-preview-range>{{ $nextAccessionNo ?? 1 }}</span>
                                </div>
                            </div>
                        </section>
                    @else
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-black text-slate-950">Book Copies</h2>
                                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">Next accession number is <span class="font-black text-slate-800">{{ $nextAccessionNo ?? 1 }}</span>.</p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-800">System managed</span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Copies</p>
                                    <p class="mt-1 text-2xl font-black text-slate-950">{{ $book->copies()->count() }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Next Acc. No.</p>
                                    <p class="mt-1 text-2xl font-black text-emerald-800">{{ $nextAccessionNo ?? 1 }}</p>
                                </div>
                            </div>

                            <button type="button" data-reveal-copy-panel class="mt-4 w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-black text-white hover:bg-emerald-800">Add Copy</button>

                            <div data-copy-panel class="mt-4 hidden space-y-4 rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                                <label class="block text-xs font-black uppercase tracking-widest text-emerald-900">Number of Copies
                                    <input id="copiesCountInput" name="copies_count" value="{{ old('copies_count') }}" type="number" min="1" max="500" placeholder="Enter copy quantity"
                                           class="mt-1.5 h-12 w-full rounded-xl border border-emerald-200 bg-white px-4 text-sm font-bold normal-case tracking-normal text-slate-900 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                                </label>
                                <div class="space-y-2 text-sm font-semibold text-emerald-900">
                                    <div class="flex items-center justify-between gap-3">
                                        <span>Will create</span>
                                        <span class="font-black"><span data-copy-preview-count>0</span> copy/copies</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span>Accession range</span>
                                        <span class="font-black" data-copy-preview-range>-</span>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('admin.library.books.show', $book) }}" class="mt-4 inline-flex w-full justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 hover:bg-slate-100">View Copies</a>
                        </section>
                    @endunless

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-black text-slate-950">Catalog Checklist</h2>
                        <div class="mt-4 space-y-3 text-sm font-semibold text-slate-600">
                            <div class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 rounded-full bg-emerald-600"></span>
                                <span>Title and author appear in quick search.</span>
                            </div>
                            <div class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 rounded-full bg-emerald-600"></span>
                                <span>Category and shelf help staff locate books quickly.</span>
                            </div>
                            <div class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                <span>ISBN, publisher, price, and source improve audit reports.</span>
                            </div>
                        </div>
                    </section>

                    <div class="flex gap-2">
                        <a href="{{ $backRoute }}" class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-black text-slate-700 hover:bg-slate-100">Cancel</a>
                        <button class="flex-1 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-black text-white hover:bg-emerald-800">{{ $submitLabel }}</button>
                    </div>
                </div>
            </aside>
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
@endsection

@push('scripts')
<script>
function selectLibrarySuggestValue(inputId, value) {
    const input = document.getElementById(inputId);
    if (input) input.value = value;
    document.querySelectorAll('.library-suggest-box').forEach(box => box.innerHTML = '');
}

function escapeLibraryHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setupLibraryAutoSuggest(inputId, boxId, fieldName) {
    const input = document.getElementById(inputId);
    const box = document.getElementById(boxId);
    if (!input || !box) return;
    box.classList.add('library-suggest-box');

    box.addEventListener('click', function (event) {
        const button = event.target.closest('[data-suggest-value]');
        if (!button) return;
        selectLibrarySuggestValue(inputId, button.dataset.suggestValue || '');
    });

    input.addEventListener('keyup', function () {
        const query = this.value.trim();
        if (query.length < 2) {
            box.innerHTML = '';
            return;
        }

        box.innerHTML = `<div class="absolute z-50 mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-bold text-slate-400 shadow-xl">Searching...</div>`;

        fetch(`{{ route('admin.library.books.search-field') }}?query=${encodeURIComponent(query)}&field=${fieldName}`)
            .then(response => response.json())
            .then(data => {
                if (!data.length) {
                    box.innerHTML = `<div class="absolute z-50 mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-bold text-slate-400 shadow-xl">No results found</div>`;
                    return;
                }

                box.innerHTML = `<div class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">`
                    + data.map(value => {
                        const safeValue = escapeLibraryHtml(value);
                        return `<button type="button" data-suggest-value="${safeValue}" class="block w-full px-4 py-3 text-left text-sm font-bold text-slate-700 hover:bg-emerald-50">${safeValue}</button>`;
                    }).join('')
                    + `</div>`;
            });
    });

    document.addEventListener('click', function (event) {
        if (!input.contains(event.target) && !box.contains(event.target)) {
            box.innerHTML = '';
        }
    });
}

setupLibraryAutoSuggest('titleInput', 'titleSuggest', 'title');
setupLibraryAutoSuggest('authorInput', 'authorSuggest', 'author');
setupLibraryAutoSuggest('publisherInput', 'publisherSuggest', 'publisher');

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
    if (copyConfirmReady || copyCountValue() < 1) return;
    event.preventDefault();
    openCopyConfirm();
});

document.querySelector('[data-copy-confirm-cancel]')?.addEventListener('click', closeCopyConfirm);
document.querySelector('[data-copy-confirm-submit]')?.addEventListener('click', function () {
    copyConfirmReady = true;
    closeCopyConfirm();
    copyForm?.submit();
});
</script>
@endpush
