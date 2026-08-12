@extends('library-admin.layouts.app')

@section('title', 'Categories & Rules')

@section('library-content')

@php
    $patronTypes = ['' => 'All patrons', 'student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff'];
@endphp

<div class="mx-auto max-w-7xl space-y-6" x-data="ruleForm()">

    {{-- ── ADD / EDIT FORM ── --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-black text-slate-950" x-text="editId ? 'Edit Patron Rule' : 'Add Patron Rule'"></h2>
                <p class="text-xs font-semibold text-slate-400 mt-0.5" x-show="editId" x-cloak>
                    Editing — <span class="font-black text-slate-600" x-text="form.name"></span>
                </p>
            </div>
            <button x-show="editId" x-cloak
                    @click="reset()"
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-black text-slate-600 hover:bg-slate-50">
                ✕ Cancel Edit
            </button>
        </div>

        <form method="POST"
              :action="editId
                  ? '{{ url('admin/library/patron-categories') }}/' + editId
                  : '{{ route('admin.library.patron-categories.store') }}'"
              id="ruleForm">
            @csrf
            <input type="hidden" name="_method" :value="editId ? 'PUT' : 'POST'">

            {{-- Row 1: name · patron type · catalog category --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 mb-3">
                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    Name *
                    <input name="name" required x-model="form.name"
                           placeholder="e.g. Class 9–10 Students"
                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold normal-case tracking-normal outline-none focus:border-emerald-700 @error('name') border-red-400 @enderror">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </label>

                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    Patron Type
                    <select name="patron_type" x-model="form.patron_type"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
                        @foreach($patronTypes as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    Book Catalog Category
                    <select name="library_category_id" x-model="form.library_category_id"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
                        <option value="">All categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            {{-- Row 2: department scope · class from/to · numeric rules --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-6 mb-3">
                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    Class / Department / Program
                    <select name="department_scope" x-model="form.department_scope"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold normal-case tracking-normal outline-none focus:border-emerald-700">
                        <option value="">All departments and programs</option>
                        @foreach($departmentOptions->groupBy('organization') as $organization => $options)
                            <optgroup label="{{ $organization }}">
                                @foreach($options as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('department_scope')<p class="mt-1 text-xs normal-case tracking-normal text-red-600">{{ $message }}</p>@enderror
                </label>

                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    Class From
                    <select name="class_from" x-model="form.class_from"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
                        <option value="">Any</option>
                        @foreach($classOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('class_from')<p class="mt-1 text-xs normal-case tracking-normal text-red-600">{{ $message }}</p>@enderror
                </label>

                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    Class To
                    <select name="class_to" x-model="form.class_to"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
                        <option value="">Any</option>
                        @foreach($classOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('class_to')<p class="mt-1 text-xs normal-case tracking-normal text-red-600">{{ $message }}</p>@enderror
                </label>

                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    Max Books *
                    <input name="max_active_books" type="number" min="1" max="50" required
                           x-model="form.max_active_books"
                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
                </label>

                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    Loan Days *
                    <input name="loan_days" type="number" min="1" max="365" required
                           x-model="form.loan_days"
                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
                </label>

                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    Fine/Day (Rs) *
                    <input name="fine_per_day" type="number" min="0" step="0.5" required
                           x-model="form.fine_per_day"
                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
                </label>
            </div>

            <p class="-mt-1 mb-3 text-xs font-semibold text-slate-400">
                Departments and programs come directly from Student Settings. Numeric ranges use only configured school grades.
                @if($classOptions->isEmpty())
                    <span class="font-black text-amber-600">Add an active numbered class there before creating a class-specific rule.</span>
                @endif
            </p>

            {{-- Row 3: description · toggles · submit --}}
            <div class="flex flex-wrap items-center gap-4">
                <input name="description" x-model="form.description"
                       placeholder="Description (optional)"
                       class="flex-1 min-w-48 rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">

                <label class="flex items-center gap-2 text-xs font-black text-slate-600 cursor-pointer select-none">
                    <input type="checkbox" name="block_same_title" value="1"
                           :checked="form.block_same_title"
                           @change="form.block_same_title = $event.target.checked"
                           class="rounded">
                    Block same title twice
                </label>

                <label x-show="editId" x-cloak class="flex items-center gap-2 text-xs font-black text-slate-600 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1"
                           :checked="form.is_active"
                           @change="form.is_active = $event.target.checked"
                           class="rounded">
                    Active
                </label>

                <button type="submit"
                        class="rounded-xl px-6 py-2.5 text-sm font-black text-white hover:opacity-90 ml-auto"
                        style="background: var(--theme-primary, #1a5632);"
                        x-text="editId ? 'Save Changes' : 'Add Rule'">
                </button>
            </div>
        </form>
    </section>

    {{-- ── PATRON CATEGORIES TABLE ── --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-lg font-black text-slate-950">Patron Categories & Issue Rules</h2>
            <p class="text-xs font-semibold text-slate-400 mt-0.5">
                Rules are matched top-to-bottom by sort order. First match wins.
                <span class="font-black text-slate-600">All Patrons (Default)</span> is always the global fallback.
            </p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[11px] font-black uppercase tracking-widest text-slate-400">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Patron Type</th>
                        <th class="px-4 py-3 text-left">Book Category</th>
                        <th class="px-4 py-3 text-left">Class / Department</th>
                        <th class="px-4 py-3 text-center">Max Books</th>
                        <th class="px-4 py-3 text-center">Loan Days</th>
                        <th class="px-4 py-3 text-center">Fine/Day</th>
                        <th class="px-4 py-3 text-center">Block Dupe</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($patronCategories as $pc)
                        @php $isDefault = $pc->slug === 'default-all'; @endphp
                        <tr class="transition-colors {{ $isDefault ? 'bg-slate-50/70' : 'hover:bg-slate-50' }}"
                            :class="editId == {{ $pc->id }} ? 'bg-emerald-50 ring-1 ring-inset ring-emerald-200' : ''">

                            <td class="px-4 py-3">
                                <p class="font-black text-slate-900">{{ $pc->name }}</p>
                                @if($isDefault)
                                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Global fallback</span>
                                @elseif($pc->description)
                                    <p class="text-xs text-slate-400">{{ Str::limit($pc->description, 40) }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @if($pc->patron_type)
                                    <span class="rounded-full px-2 py-0.5 text-xs font-black
                                        {{ $pc->patron_type === 'student' ? 'bg-blue-100 text-blue-700' : ($pc->patron_type === 'teacher' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600') }}">
                                        {{ ucfirst($pc->patron_type) }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">All</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-600">{{ $pc->catalogCategory?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $pc->class_range_label }}</td>
                            <td class="px-4 py-3 text-center font-black text-slate-900">{{ $pc->max_active_books }}</td>
                            <td class="px-4 py-3 text-center font-black text-slate-900">{{ $pc->loan_days }}d</td>
                            <td class="px-4 py-3 text-center font-black text-slate-900">Rs.{{ number_format($pc->fine_per_day, 2) }}</td>

                            <td class="px-4 py-3 text-center">
                                <span class="text-xs font-black {{ $pc->block_same_title ? 'text-emerald-600' : 'text-slate-400' }}">
                                    {{ $pc->block_same_title ? 'Yes' : 'No' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                @if($pc->is_active)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-black text-emerald-700">Active</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-black text-slate-500">Off</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1.5">
                                    <button
                                        @click="edit({
                                            id:                  {{ $pc->id }},
                                            name:                {{ Js::from($pc->name) }},
                                            patron_type:         {{ Js::from($pc->patron_type ?? '') }},
                                            library_category_id: {{ Js::from((string)($pc->library_category_id ?? '')) }},
                                            department_scope:    {{ Js::from($pc->organization_slug && $pc->department_name ? $pc->organization_slug.'::'.$pc->department_name : '') }},
                                            class_from:          {{ Js::from((string)($pc->class_from ?? '')) }},
                                            class_to:            {{ Js::from((string)($pc->class_to ?? '')) }},
                                            max_active_books:    {{ $pc->max_active_books }},
                                            loan_days:           {{ $pc->loan_days }},
                                            fine_per_day:        {{ $pc->fine_per_day }},
                                            block_same_title:    {{ $pc->block_same_title ? 'true' : 'false' }},
                                            is_active:           {{ $pc->is_active ? 'true' : 'false' }},
                                            description:         {{ Js::from($pc->description ?? '') }}
                                        })"
                                        class="rounded-lg border px-3 py-1.5 text-xs font-black transition-colors"
                                        :class="editId == {{ $pc->id }}
                                            ? 'border-emerald-300 bg-emerald-100 text-emerald-800'
                                            : 'border-slate-200 text-slate-700 hover:bg-slate-100'">
                                        <span x-text="editId == {{ $pc->id }} ? 'Editing…' : 'Edit'"></span>
                                    </button>

                                    @if(!$isDefault)
                                    <form method="POST"
                                          action="{{ route('admin.library.patron-categories.destroy', $pc) }}"
                                          onsubmit="return confirm('Delete {{ addslashes($pc->name) }}?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg border border-red-100 px-3 py-1.5 text-xs font-black text-red-600 hover:bg-red-50">
                                            Delete
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center font-bold text-slate-400">
                                No patron rules defined yet. Use the form above to add one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- ── CATALOG CATEGORIES ── --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-lg font-black text-slate-950">Book Catalog Categories</h2>
        <div class="flex flex-wrap items-start gap-5">
            <form method="POST" action="{{ route('admin.library.categories.store') }}"
                  class="flex gap-2 items-end flex-wrap">
                @csrf
                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    Name
                    <input name="name" required placeholder="e.g. Science, Literature…"
                           class="mt-1 block w-52 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
                </label>
                <label class="text-xs font-black uppercase tracking-widest text-slate-500">
                    Description
                    <input name="description" placeholder="Optional"
                           class="mt-1 block w-44 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
                </label>
                <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-black text-white hover:bg-slate-700">Add</button>
            </form>

            <div class="flex flex-wrap gap-2 items-center pt-1">
                @foreach($categories as $category)
                    <div class="flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 pl-3 pr-1.5 py-1.5 text-sm font-bold text-slate-700">
                        {{ $category->name }}
                        @if($category->description)
                            <span class="text-xs text-slate-400">({{ $category->description }})</span>
                        @endif
                        <form method="POST" action="{{ route('admin.library.categories.destroy', $category) }}"
                              onsubmit="return confirm('Delete {{ addslashes($category->name) }}?')">
                            @csrf @method('DELETE')
                            <button class="w-5 h-5 rounded-full flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endforeach
                @if($categories->isEmpty())
                    <span class="text-sm font-bold text-slate-400">No categories yet.</span>
                @endif
            </div>
        </div>
    </section>

</div>

@push('scripts')
<script>
function ruleForm() {
    const empty = {
        name: '', patron_type: '', library_category_id: '',
        department_scope: '', class_from: '', class_to: '',
        max_active_books: 3, loan_days: 14, fine_per_day: 2,
        block_same_title: true, is_active: true, description: ''
    };

    return {
        editId: null,
        form: { ...empty },

        edit(data) {
            this.editId = data.id;
            this.form   = { ...data };
            document.getElementById('ruleForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        },

        reset() {
            this.editId = null;
            this.form   = { ...empty };
        }
    };
}
</script>
@endpush

@endsection
