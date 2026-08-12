@extends('card.student-portal.layout')

@section('title', 'Library Book Search')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10">

    <div class="mb-8 text-center">
        <h1 class="text-3xl font-black text-gray-950">Library Catalog</h1>
        <p class="mt-2 text-sm font-semibold text-gray-500">Search books available in the school library.</p>
    </div>

    {{-- Search box --}}
    <form method="GET" action="{{ route('library.public.search') }}" class="mb-8">
        <div class="flex gap-2 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden p-1.5">
            <input type="text" name="q" value="{{ $search }}"
                   placeholder="Search by title, author, ISBN or publisher…"
                   autofocus
                   class="flex-1 px-4 py-2.5 text-sm font-bold outline-none text-gray-900 bg-transparent placeholder:text-gray-400">
            <button class="rounded-xl px-6 py-2.5 text-sm font-black text-white transition-colors"
                    style="background: var(--theme-primary, #1a5632);">
                Search
            </button>
        </div>
    </form>

    @if(strlen($search) >= 2)
        @if($books->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-16 text-center">
                <svg class="mx-auto mb-3 w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="font-black text-slate-400">No books found for "{{ $search }}"</p>
                <p class="mt-1 text-sm text-slate-400">Try a different title, author or ISBN.</p>
            </div>
        @else
            <p class="mb-4 text-xs font-black uppercase tracking-widest text-gray-400">
                {{ $books->total() }} {{ Str::plural('result', $books->total()) }} for "{{ $search }}"
            </p>

            <div class="space-y-3">
                @foreach($books as $book)
                    @php
                        $available = $book->available_copies_count ?? 0;
                        $total     = $book->copies_count ?? 0;
                        $issued    = $book->issued_copies_count ?? 0;
                    @endphp
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col sm:flex-row gap-4">
                        {{-- Book icon placeholder --}}
                        <div class="w-12 h-16 rounded-lg flex items-center justify-center shrink-0"
                             style="background: var(--theme-primary, #1a5632);">
                            <svg class="w-6 h-6 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/></svg>
                        </div>

                        {{-- Details --}}
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-black text-gray-950 leading-snug">{{ $book->title }}</h3>
                            <p class="text-sm font-semibold text-gray-500 mt-0.5">{{ $book->author }}</p>

                            <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-gray-500">
                                @if($book->isbn)
                                    <span>ISBN: {{ $book->isbn }}</span>
                                @endif
                                @if($book->publisher)
                                    <span>· {{ $book->publisher }}</span>
                                @endif
                                @if($book->publication_year)
                                    <span>· {{ $book->publication_year }}</span>
                                @endif
                                @if($book->category)
                                    <span>·
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-gray-600">{{ $book->category->name }}</span>
                                    </span>
                                @endif
                                @if($book->shelf_location)
                                    <span>· Shelf: {{ $book->shelf_location }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Availability --}}
                        <div class="flex flex-col items-end justify-between shrink-0 gap-2">
                            @if($available > 0)
                                <div class="rounded-xl bg-emerald-100 px-3 py-2 text-center">
                                    <p class="text-xl font-black text-emerald-700">{{ $available }}</p>
                                    <p class="text-[10px] font-black uppercase tracking-wide text-emerald-600">Available</p>
                                </div>
                            @else
                                <div class="rounded-xl bg-red-100 px-3 py-2 text-center">
                                    <p class="text-sm font-black text-red-600">Not Available</p>
                                    @if($issued > 0)
                                        <p class="text-[10px] text-red-500">{{ $issued }} / {{ $total }} issued</p>
                                    @endif
                                </div>
                            @endif
                            <p class="text-[10px] font-semibold text-gray-400">{{ $total }} total {{ Str::plural('copy', $total) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $books->withQueryString()->links() }}</div>
        @endif

    @elseif(strlen($search) > 0)
        <div class="py-8 text-center text-sm font-bold text-gray-400">Please enter at least 2 characters to search.</div>

    @else
        {{-- Idle state --}}
        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-16 text-center">
            <svg class="mx-auto mb-4 w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"/></svg>
            <p class="font-black text-slate-400">Enter a title, author, or ISBN to search the catalog</p>
            <p class="mt-1 text-sm text-slate-400">You can also see how many copies are available before visiting the library.</p>
        </div>
    @endif

</div>
@endsection
