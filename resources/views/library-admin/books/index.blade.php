@extends('library-admin.layouts.app')

@section('title', 'Books')

@section('library-content')
<div class="mx-auto max-w-7xl space-y-4">
    <div class="relative rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <label class="text-sm font-black text-slate-800">Search Book</label>
        <input type="text" id="bookSearch"
               value="{{ request('search') }}"
               placeholder="Type title, author, ISBN or scan barcode..."
               class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:border-emerald-700"
               autocomplete="off">
        <div id="searchResult"></div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="w-full sm:w-96">
            <input name="search" value="{{ request('search') }}" placeholder="Filter table" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold outline-none focus:border-emerald-700">
        </form>
        <a href="{{ route('admin.library.books.create') }}" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-black text-white hover:bg-emerald-800">Add Book</a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[860px] text-left text-sm">
            <thead class="bg-slate-50 text-xs font-black uppercase tracking-widest text-slate-500">
                <tr>
                    <th class="px-5 py-3">Book</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3 text-right">Available</th>
                    <th class="px-5 py-3 text-right">Issued</th>
                    <th class="px-5 py-3 text-right">Total</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($books as $book)
                    <tr>
                        <td class="px-5 py-3">
                            <p class="font-black text-slate-950">{{ $book->title }}</p>
                            <p class="text-xs font-semibold text-slate-500">{{ $book->author }} @if($book->isbn) · ISBN {{ $book->isbn }} @endif</p>
                        </td>
                        <td class="px-5 py-3 font-semibold text-slate-600">{{ $book->category?->name ?: 'No category' }}</td>
                        <td class="px-5 py-3 text-right font-black text-emerald-700">{{ $book->available_copies_count }}</td>
                        <td class="px-5 py-3 text-right font-black text-amber-700">{{ $book->issued_copies_count }}</td>
                        <td class="px-5 py-3 text-right font-black text-slate-950">{{ $book->copies_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.library.books.show', $book) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50">View Copies</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center font-bold text-slate-400">No books found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($books->hasPages())
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">{{ $books->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
let searchInput = document.getElementById('bookSearch');
let resultBox = document.getElementById('searchResult');
let lastResults = [];
let debounce;

if (searchInput && resultBox) {
    searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            if (lastResults.length > 0) {
                goToLibraryBook(lastResults[0].id);
            }
        }
    });

    searchInput.addEventListener('input', function () {
        clearTimeout(debounce);
        let query = this.value.trim();

        if (query.length < 1) {
            resultBox.innerHTML = '';
            lastResults = [];
            return;
        }

        debounce = setTimeout(function () {
            fetch(`{{ route('admin.library.books.search') }}?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    lastResults = data;
                    if (!data.length) {
                        resultBox.innerHTML = `<div class="absolute z-50 mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-bold text-slate-500 shadow-xl">No book found</div>`;
                        return;
                    }

                    let html = `<ul class="absolute z-50 mt-1 max-h-72 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">`;
                    data.forEach(book => {
                        let barcodeHint = book.matched_barcode
                            ? `<span class="ml-2 rounded bg-amber-100 px-1 text-xs text-amber-800">Barcode: ${book.matched_barcode}</span>`
                            : '';
                        html += `
                            <li class="cursor-pointer border-b border-slate-100 px-4 py-3 hover:bg-emerald-50" onclick="goToLibraryBook(${book.id})">
                                <div class="font-black text-slate-900">${book.title} ${barcodeHint}</div>
                                <div class="text-xs font-semibold text-slate-500">${book.author || '-'} | ISBN: ${book.isbn || '-'}</div>
                            </li>
                        `;
                    });
                    html += `</ul>`;
                    resultBox.innerHTML = html;
                });
        }, 250);
    });

    document.addEventListener('click', function (event) {
        if (!searchInput.contains(event.target) && !resultBox.contains(event.target)) {
            resultBox.innerHTML = '';
        }
    });
}

function goToLibraryBook(id) {
    resultBox.innerHTML = '';
    window.location.href = `{{ url('/admin/library/books') }}/${id}`;
}
</script>
@endpush
