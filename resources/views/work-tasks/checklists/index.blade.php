@extends('work-tasks.layout')

@section('title', 'Work Checklists')

@section('content')
@php
    $input = 'w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
    $label = 'mb-1.5 block text-xs font-extrabold uppercase tracking-wider text-gray-600';
    $hint = 'mt-1 text-[11px] font-semibold leading-4 text-gray-400';
@endphp

<div class="space-y-5">
    <div class="rounded-2xl bg-[#0b2415] p-5 sm:p-6 text-white shadow-sm">
        <p class="text-sm font-bold uppercase tracking-widest text-white/50">Work Templates</p>
        <h1 class="mt-1 text-3xl font-extrabold">Checklist Templates</h1>
        <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">
            Create common sets of work once, then tick them while assigning tasks.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[420px_1fr]">
        <form method="POST" action="{{ route('admin.work-tasks.checklists.store') }}" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            @csrf
            <h2 class="text-base font-extrabold text-gray-950">Create Checklist</h2>
            <p class="mt-1 text-xs font-semibold text-gray-500">Add one work item per line.</p>

            <div class="mt-4 space-y-3">
                <div>
                    <label class="{{ $label }}" for="work-checklist-name">Checklist Name</label>
                    <input id="work-checklist-name" name="name" required placeholder="e.g. Monthly Teacher Duties" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}" for="work-checklist-category">Default Category</label>
                    <input id="work-checklist-category" name="category" placeholder="e.g. Lesson plan, portfolio" class="{{ $input }}">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $label }}" for="work-checklist-score">Default Marks</label>
                        <input id="work-checklist-score" type="number" name="default_max_score" min="1" max="1000" value="10" required class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}" for="work-checklist-incentive">Default Rs.</label>
                        <input id="work-checklist-incentive" type="number" step="0.01" name="default_incentive_amount" placeholder="1000" class="{{ $input }}">
                    </div>
                </div>
                <div>
                    <label class="{{ $label }}" for="work-checklist-items">Checklist Items</label>
                    <textarea id="work-checklist-items" name="items" rows="6" required placeholder="Submit lesson plan&#10;Update portfolio&#10;Prepare attendance summary" class="{{ $input }}"></textarea>
                    <p class="{{ $hint }}">Each line becomes one assignable task.</p>
                </div>
                <div>
                    <label class="{{ $label }}" for="work-checklist-description">Instructions</label>
                    <textarea id="work-checklist-description" name="description" rows="2" placeholder="Common evidence or review instruction" class="{{ $input }}"></textarea>
                </div>
            </div>

            <div class="mt-4 flex justify-end border-t border-gray-100 pt-4">
                <button class="rounded-xl bg-[#1a5632] px-5 py-2.5 text-sm font-extrabold text-white">Create Checklist</button>
            </div>
        </form>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-base font-extrabold text-gray-950">Checklist Library</h2>
                <p class="text-xs font-semibold text-gray-500">These appear as tick boxes on the task assignment form.</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($checklists as $checklist)
                    <div class="p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-extrabold text-gray-950">{{ $checklist->name }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500">
                                    {{ $checklist->items->count() }} item{{ $checklist->items->count() === 1 ? '' : 's' }}
                                    @if($checklist->category) · {{ $checklist->category }} @endif
                                </p>
                            </div>
                            <form method="POST" action="{{ route('admin.work-tasks.checklists.destroy', $checklist) }}" onsubmit="return confirm('Delete this checklist template? Existing assigned tasks will remain.')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-extrabold text-red-700">Delete</button>
                            </form>
                        </div>
                        <div class="mt-3 grid gap-1.5 md:grid-cols-2">
                            @foreach($checklist->items as $item)
                                <div class="flex items-start gap-2 rounded-lg border border-gray-100 bg-white px-2.5 py-1.5 text-gray-800">
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400"></span>
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold leading-5">{{ $item->title }}</p>
                                        <p class="text-[10px] font-semibold text-gray-500">{{ $item->max_score }} marks · Rs. {{ number_format((float) $item->incentive_amount, 0) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="p-5 text-sm font-semibold text-gray-500">No checklist templates yet.</p>
                @endforelse
            </div>
            <div class="border-t border-gray-100 px-5 py-4">{{ $checklists->links() }}</div>
        </div>
    </div>
</div>
@endsection
