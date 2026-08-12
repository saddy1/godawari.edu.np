@extends('learning.layouts.admin')

@section('title', 'Student Learning Accounts')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
            <div>
                <p class="text-sm font-bold uppercase tracking-widest text-gray-400">E-Learning</p>
                <h1 class="text-3xl font-extrabold text-gray-950 mt-1">Student Accounts</h1>
                <p class="text-gray-500 mt-2">Issue User IDs and passwords for students to access the learning portal.</p>
            </div>
            <a href="{{ route('admin.learning.dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 hover:bg-gray-50">Back</a>
        </div>

        

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        @if(auth()->user()?->canAccess('hr.members.create') && \App\Services\ModuleService::enabled('hr'))
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-extrabold text-emerald-950">Student creation is centralized in HR</h2>
                        <p class="mt-1 text-sm font-medium text-emerald-800">Create or import students from HR People Master. Learning uses the linked login account automatically.</p>
                    </div>
                    <a href="{{ route('admin.hr.members.create') }}" class="inline-flex justify-center rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415]">Create in HR</a>
                </div>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-extrabold text-gray-950">Student List</h2>
                        <p class="mt-1 text-sm font-medium text-gray-500">Search by name, user ID, email, class, or section.</p>
                    </div>
                    <div class="relative sm:w-[360px]">
                        <input
                            id="student-search"
                            type="search"
                            value="{{ $search ?? '' }}"
                            placeholder="Search students..."
                            autocomplete="off"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 pr-10 text-sm font-bold text-gray-800 focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/10"
                        >
                        <span id="student-search-status" class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 text-[10px] font-extrabold uppercase tracking-wider text-gray-400">...</span>
                    </div>
                </div>
            </div>
            <div id="student-results">
                @include('learning.admin.students.partials.table', ['students' => $students])
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('student-search');
    const results = document.getElementById('student-results');
    const status = document.getElementById('student-search-status');

    if (! input || ! results) return;

    let timer = null;
    let controller = null;

    const loadStudents = (url = null) => {
        if (controller) controller.abort();
        controller = new AbortController();

        const target = new URL(url || @js(route('admin.learning.students.index')), window.location.origin);
        target.searchParams.set('q', input.value.trim());

        status?.classList.remove('hidden');

        fetch(target.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
        })
            .then((response) => response.text())
            .then((html) => {
                results.innerHTML = html;
                window.history.replaceState({}, '', target.toString());
            })
            .catch((error) => {
                if (error.name !== 'AbortError') console.error(error);
            })
            .finally(() => status?.classList.add('hidden'));
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => loadStudents(), 250);
    });

    results.addEventListener('click', (event) => {
        const link = event.target.closest('nav a[href]');
        if (! link) return;

        event.preventDefault();
        loadStudents(link.href);
    });
});
</script>
@endpush
