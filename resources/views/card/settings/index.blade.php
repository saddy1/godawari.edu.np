@extends(request('from') === 'hr' ? 'hr.layouts.app' : 'card.layouts.app')
@section('title', 'System Settings')
@section('heading', 'System Settings')

@section('content')

@php
    $tab    = request('tab', 'organizations');
    $orgId  = request('org');
    $deptId = request('dept');
@endphp

{{-- Tab bar --}}
<div class="mb-5 flex w-fit max-w-full gap-1 overflow-x-auto rounded-xl border bg-white p-1">
    @foreach([
        'organizations' => 'Organizations',
        'departments'   => 'Departments',
        'sections'      => 'Sections',
        'member_types'  => 'Member Types',
        'assets'        => 'Card Assets',
        'backgrounds'   => 'Card Backgrounds',
    ] as $key => $label)
        <a href="{{ route('settings.index', array_merge(request()->only('org','dept','from'), ['tab' => $key])) }}"
            class="px-4 py-2 rounded-lg text-sm font-medium transition
                {{ $tab === $key ? 'bg-primary text-white shadow-sm' : 'text-gray-500 hover:text-primary' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@unless($masterDataAvailable)
<div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl p-5">
    <h3 class="font-semibold">Master data tables are not available yet.</h3>
    <p class="text-sm mt-1">This settings screen requires the `organizations`, `departments`, `sections`, and `member_types` tables. Run the new migrations and seeders, then reload this page.</p>
</div>
@endunless

@if($masterDataAvailable)

{{-- ── ORGANIZATIONS ──────────────────────────────────────────────────────── --}}
@if($tab === 'organizations')
@php $assetsByType = $assets->groupBy('type'); @endphp
<div class="space-y-4">
    <details class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" @if($errors->any()) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
            <div><h3 class="text-sm font-bold text-gray-900">Add organization</h3><p class="text-[11px] text-gray-400">Create a school, college, or another unit.</p></div>
            <span class="rounded-lg bg-primary px-3 py-2 text-xs font-bold text-white">+ Add new</span>
        </summary>
        <form method="POST" action="{{ route('settings.organizations.store') }}" class="grid gap-3 border-t border-gray-100 p-4 sm:grid-cols-2 xl:grid-cols-3">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. School Name" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Slug</label>
                <input type="text" name="slug" value="{{ old('slug') }}" placeholder="e.g. college" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Type</label>
                <select name="type" class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                    <option value="college">College</option>
                    <option value="school">School</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="grid gap-3 rounded-xl border border-gray-200 bg-gray-50/60 p-3 sm:grid-cols-3 sm:col-span-2 xl:col-span-3">
                @foreach(['logo_asset_id' => 'Logo', 'signature_asset_id' => 'Signature', 'stamp_asset_id' => 'Stamp'] as $field => $label)
                @php $atype = str_replace('_asset_id', '', $field); @endphp
                <div>
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-gray-500">{{ $label }} asset</label>
                    <select name="{{ $field }}" class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs focus:border-primary focus:ring-primary">
                        <option value="">— none —</option>
                        @foreach($assetsByType->get($atype, collect()) as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach
            </div>
            <label class="flex items-center gap-2 text-xs font-semibold text-gray-600"><input type="checkbox" name="is_active" value="1" checked class="rounded text-primary focus:ring-primary"> Active</label>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-light sm:col-start-2 xl:col-start-3">Add organization</button>
        </form>
    </details>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @forelse($organizations as $org)
            <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary/25 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-xs font-black text-primary">{{ Str::upper(Str::substr($org->name, 0, 2)) }}</span><div class="min-w-0"><h3 class="truncate text-sm font-bold text-gray-900">{{ $org->name }}</h3><p class="truncate font-mono text-[10px] text-gray-400">{{ $org->slug }}</p></div></div>
                    <span class="h-2.5 w-2.5 rounded-full {{ $org->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                </div>
                <div class="mt-3 flex items-center gap-2 text-[10px] font-bold"><span class="rounded-md bg-gray-100 px-2 py-1 capitalize text-gray-600">{{ $org->type }}</span><span class="text-gray-400">{{ $org->departments_count }} {{ Str::plural('department', $org->departments_count) }}</span><span class="text-gray-400">{{ $org->member_types_count }} {{ Str::plural('type', $org->member_types_count) }}</span></div>
                @php $orgStudentCount = $org->studentsQuery()->count(); @endphp
                <div class="mt-3 flex gap-1.5 border-t border-gray-100 pt-3">
                    <a href="{{ route('settings.index', array_filter(['tab' => 'departments', 'org' => $org->id, 'from' => request('from')])) }}" class="flex-1 rounded-lg bg-primary/5 px-2 py-1.5 text-center text-[11px] font-bold text-primary">Departments</a>
                    <button type="button" onclick="document.getElementById('edit-org-{{ $org->id }}').showModal()" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-[11px] font-bold text-gray-600">Edit</button>
                    <form method="POST" action="{{ route('settings.organizations.destroy', $org) }}" onsubmit="return confirm('Delete this organization and all its departments and sections?')">@csrf @method('DELETE')<button @disabled($orgStudentCount > 0) title="{{ $orgStudentCount > 0 ? $orgStudentCount.' '.Str::plural('student', $orgStudentCount).' still belong to this organization' : 'Delete organization' }}" class="rounded-lg border border-red-100 px-2.5 py-1.5 text-[11px] font-bold text-red-500 disabled:cursor-not-allowed disabled:border-gray-100 disabled:text-gray-300">Delete</button></form>
                </div>
            </article>

            <dialog id="edit-org-{{ $org->id }}" class="m-auto w-[calc(100%_-_2rem)] max-w-xl rounded-2xl p-0 shadow-2xl backdrop:bg-gray-950/55">
                <form method="POST" action="{{ route('settings.organizations.update', $org) }}">@csrf @method('PATCH')
                    <div class="flex items-center justify-between border-b px-5 py-4"><div><p class="text-[10px] font-bold uppercase tracking-widest text-primary">Edit organization</p><h3 class="font-bold text-gray-900">{{ $org->name }}</h3></div><button type="button" onclick="this.closest('dialog').close()" class="h-8 w-8 rounded-full bg-gray-100 text-xl text-gray-500">&times;</button></div>
                    <div class="grid gap-3 p-5 sm:grid-cols-2">
                        <div><label class="mb-1 block text-xs font-semibold text-gray-600">Name</label><input name="name" value="{{ $org->name }}" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary"></div>
                        <div><label class="mb-1 block text-xs font-semibold text-gray-600">Slug</label><input name="slug" value="{{ $org->slug }}" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary"></div>
                        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-semibold text-gray-600">Type</label><select name="type" class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm">@foreach(['college','school','other'] as $type)<option value="{{ $type }}" @selected($org->type === $type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
                        @foreach(['logo_asset_id' => 'Logo', 'signature_asset_id' => 'Signature', 'stamp_asset_id' => 'Stamp'] as $field => $label)
                            @php $atype = str_replace('_asset_id', '', $field); @endphp
                            <div><label class="mb-1 block text-xs font-semibold text-gray-600">{{ $label }} asset</label><select name="{{ $field }}" class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs"><option value="">— none —</option>@foreach($assetsByType->get($atype, collect()) as $assetOption)<option value="{{ $assetOption->id }}" @selected($org->{$field} == $assetOption->id)>{{ $assetOption->name }}</option>@endforeach</select></div>
                        @endforeach
                        <label class="flex items-center gap-2 text-xs font-semibold text-gray-600"><input type="checkbox" name="is_active" value="1" @checked($org->is_active) class="rounded text-primary"> Active</label>
                    </div>
                    <div class="flex justify-end gap-2 border-t px-5 py-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border px-4 py-2 text-xs font-bold text-gray-600">Cancel</button><button class="rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white">Save changes</button></div>
                </form>
            </dialog>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed bg-white py-12 text-center text-sm text-gray-400">No organizations yet.</div>
        @endforelse
    </div>
</div>
@endif

{{-- ── DEPARTMENTS ─────────────────────────────────────────────────────────── --}}
@if($tab === 'departments')
@php
    $defaultDepartmentOrg = $organizations->firstWhere('id', (int) old('organization_id', $orgId)) ?? $organizations->first();
    $defaultAcademicSystem = old('academic_system', $defaultDepartmentOrg?->type === 'school' ? 'none' : 'semester');
    $depts = \App\Models\Card\Department::with('organization')->withCount('sections')
        ->when($orgId, fn($query) => $query->where('organization_id', $orgId))
        ->orderBy('name')->get();
    $departmentGroups = $depts->groupBy('organization_id');
    $displayOrganizations = $orgId
        ? $organizations->where('id', (int) $orgId)
        : $organizations->filter(fn($organization) => $departmentGroups->has($organization->id));
    $departmentEditorData = $depts->mapWithKeys(fn($department) => [$department->id => [
        'name' => $department->name,
        'organization' => $department->organization->name,
        'academic_system' => $department->academic_system,
        'university' => $department->university ?? '',
        'university_college' => $department->university_college ?? '',
        'university_logo' => $department->university_logo ?? '',
        'is_active' => (bool) $department->is_active,
        'update_url' => route('settings.departments.update', $department),
    ]]);
    $systemLabels = ['semester' => 'Semester', 'year' => 'Year', 'none' => 'Class-based'];
@endphp

<div
    x-data="{
        editOpen: false,
        departments: @js($departmentEditorData),
        editing: {},
        openEdit(department) {
            this.editing = { ...department };
            this.editOpen = true;
            document.body.classList.add('overflow-hidden');
        },
        closeEdit() {
            this.editOpen = false;
            document.body.classList.remove('overflow-hidden');
        }
    }"
    @keydown.escape.window="closeEdit()"
    class="space-y-6"
>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-primary to-primary-light px-6 py-5 text-white">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Academic structure</p>
                    <h2 class="mt-1 text-xl font-bold">Classes & Departments</h2>
                    <p class="mt-1 text-sm text-white/75">Each organization is shown separately for quicker management.</p>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 ring-1 ring-white/20 backdrop-blur-sm">
                    <span class="text-2xl font-bold">{{ $depts->count() }}</span>
                    <span class="text-xs leading-4 text-white/75">{{ Str::plural('department', $depts->count()) }}<br>shown</span>
                </div>
            </div>
        </div>

        <div class="flex gap-2 overflow-x-auto border-t border-gray-100 bg-gray-50/70 px-5 py-3">
            <a href="{{ route('settings.index', array_filter(['tab' => 'departments', 'from' => request('from')])) }}"
                class="whitespace-nowrap rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ !$orgId ? 'bg-primary text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-600 hover:border-primary/30 hover:text-primary' }}">
                All organizations
            </a>
            @foreach($organizations as $organization)
                <a href="{{ route('settings.index', array_filter(['tab' => 'departments', 'org' => $organization->id, 'from' => request('from')])) }}"
                    class="whitespace-nowrap rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ (int) $orgId === $organization->id ? 'bg-primary text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-600 hover:border-primary/30 hover:text-primary' }}">
                    {{ $organization->name }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="flex flex-col gap-4">
        <div class="order-last space-y-4">
            @forelse($displayOrganizations as $organization)
                @php $organizationDepartments = $departmentGroups->get($organization->id, collect()); @endphp
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <header class="flex flex-col gap-2 border-b border-gray-100 bg-gray-50/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-xs font-bold text-primary">
                                {{ Str::upper(Str::substr($organization->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate font-bold text-gray-900">{{ $organization->name }}</h3>
                                <p class="text-xs capitalize text-gray-500">{{ $organization->type }} organization</p>
                            </div>
                        </div>
                        <span class="w-fit rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-600">
                            {{ $organizationDepartments->count() }} {{ Str::plural('class / department', $organizationDepartments->count()) }}
                        </span>
                    </header>

                    @if($organizationDepartments->isEmpty())
                        <div class="px-6 py-12 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-xl text-gray-400">＋</div>
                            <p class="mt-3 text-sm font-semibold text-gray-700">No departments in this organization</p>
                            <p class="mt-1 text-xs text-gray-400">Use the add form to create the first one.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-3 p-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            @foreach($organizationDepartments as $dept)
                                @php $deptStudentCount = $dept->studentsQuery()->count(); @endphp
                                <article class="group rounded-xl border border-gray-200 bg-white p-3 transition hover:-translate-y-0.5 hover:border-primary/25 hover:shadow-md">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="truncate text-sm font-bold text-gray-900">{{ $dept->name }}</h4>
                                                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold {{ $dept->academic_system === 'semester' ? 'bg-blue-50 text-blue-700' : ($dept->academic_system === 'year' ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                                                    {{ $systemLabels[$dept->academic_system] ?? 'Class-based' }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-400">{{ $dept->sections_count }} {{ Str::plural('section', $dept->sections_count) }}</p>
                                        </div>
                                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $dept->is_active ? 'bg-emerald-500 ring-4 ring-emerald-50' : 'bg-gray-300 ring-4 ring-gray-100' }}" title="{{ $dept->is_active ? 'Active' : 'Inactive' }}"></span>
                                    </div>

                                    @if($dept->university)
                                        <p class="mt-2 truncate text-[10px] font-semibold text-gray-400" title="{{ $dept->university }}{{ $dept->university_college ? ' · '.$dept->university_college : '' }}">
                                            {{ $dept->university }}{{ $dept->university_college ? ' · '.$dept->university_college : '' }}
                                        </p>
                                    @endif

                                    <div class="mt-3 flex items-center gap-1.5 border-t border-gray-100 pt-2.5">
                                        <a href="{{ route('settings.index', array_filter(['tab' => 'sections', 'org' => $dept->organization_id, 'dept' => $dept->id, 'from' => request('from')])) }}"
                                            class="flex-1 rounded-lg bg-primary/5 px-2 py-1.5 text-center text-[11px] font-semibold text-primary transition hover:bg-primary/10">
                                            Sections
                                        </a>
                                        <button type="button" @click="openEdit(departments[{{ $dept->id }}])"
                                            class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-[11px] font-semibold text-gray-600 transition hover:border-primary/30 hover:text-primary">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('settings.departments.destroy', $dept) }}" onsubmit="return confirm('Delete this department?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" @disabled($deptStudentCount > 0) title="{{ $deptStudentCount > 0 ? $deptStudentCount.' '.Str::plural('student', $deptStudentCount).' still belong to this department' : 'Delete department' }}" class="rounded-lg border border-red-100 px-2.5 py-1.5 text-[11px] font-semibold text-red-500 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:border-gray-100 disabled:text-gray-300">Delete</button>
                                        </form>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/5 text-2xl text-primary">＋</div>
                    <h3 class="mt-4 font-bold text-gray-800">No departments yet</h3>
                    <p class="mt-1 text-sm text-gray-400">Create your first class or department using the form.</p>
                </div>
            @endforelse
        </div>

        <aside x-data="{ addOpen: @js($errors->any()) }" class="order-first h-fit rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-4 px-4 py-3">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Add class / department</h3>
                    <p class="mt-0.5 text-[11px] text-gray-400">Create only when you need a new one.</p>
                </div>
                <button type="button" @click="addOpen = !addOpen" class="rounded-lg bg-primary px-3 py-2 text-xs font-bold text-white transition hover:bg-primary-light" x-text="addOpen ? 'Close' : '+ Add new'"></button>
            </div>
            <form x-cloak x-show="addOpen" x-transition method="POST" action="{{ route('settings.departments.store') }}" class="grid gap-3 border-t border-gray-100 p-4 sm:grid-cols-2 xl:grid-cols-4"
                x-data="{ organizationId: @js((string) ($defaultDepartmentOrg?->id ?? '')), academicSystem: @js($defaultAcademicSystem), organizationTypes: @js($organizations->mapWithKeys(fn($item) => [(string) $item->id => $item->type])) }">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Organization</label>
                    <select name="organization_id" required x-model="organizationId" @change="academicSystem = organizationTypes[organizationId] === 'school' ? 'none' : (academicSystem === 'none' ? 'semester' : academicSystem)"
                        class="w-full rounded-xl border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:ring-primary">
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. BBS or Class 10" required
                        class="w-full rounded-xl border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Academic system</label>
                    <select name="academic_system" x-model="academicSystem" required class="w-full rounded-xl border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:ring-primary">
                        <option value="semester">Semester system</option>
                        <option value="year">Year system</option>
                        <option value="none">Class-based (no semester/year)</option>
                    </select>
                    <p class="mt-1.5 text-[11px] leading-4 text-gray-400">Use Year for BBS and Class-based for school classes.</p>
                </div>

                <details class="rounded-xl border border-gray-200 bg-gray-50/60">
                    <summary class="cursor-pointer select-none px-3 py-2.5 text-xs font-semibold text-gray-600">Optional card header</summary>
                    <div class="space-y-3 border-t border-gray-200 p-3">
                        <input type="text" name="university" value="{{ old('university') }}" placeholder="University name"
                            class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs focus:border-primary focus:ring-primary">
                        <input type="text" name="university_college" value="{{ old('university_college') }}" placeholder="College name"
                            class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs focus:border-primary focus:ring-primary">
                        <input type="text" name="university_logo" value="{{ old('university_logo') }}" placeholder="Logo path under public/"
                            class="w-full rounded-lg border-gray-200 px-3 py-2 text-xs focus:border-primary focus:ring-primary">
                    </div>
                </details>

                <label class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 px-3 py-2.5 xl:col-span-3">
                    <span class="text-xs font-semibold text-gray-600">Active</span>
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-primary focus:ring-primary">
                </label>
                <button type="submit" class="w-full rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-light">
                    Add department
                </button>
            </form>
        </aside>
    </div>

    {{-- One reusable editor keeps every card compact. --}}
    <div x-cloak x-show="editOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-gray-950/55 backdrop-blur-sm" @click="closeEdit()"></div>
        <div x-show="editOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="relative z-10 max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="sticky top-0 z-10 flex items-start justify-between border-b border-gray-100 bg-white px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-primary">Edit department</p>
                    <h3 class="mt-1 text-lg font-bold text-gray-900" x-text="editing.name"></h3>
                    <p class="mt-0.5 text-xs text-gray-400" x-text="editing.organization"></p>
                </div>
                <button type="button" @click="closeEdit()" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-xl text-gray-500 transition hover:bg-gray-200 hover:text-gray-800" aria-label="Close">&times;</button>
            </div>

            <form method="POST" :action="editing.update_url || '#'" class="space-y-5 p-6">
                @csrf @method('PATCH')
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Department / class name</label>
                    <input type="text" name="name" x-model="editing.name" required class="w-full rounded-xl border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Academic system</label>
                    <select name="academic_system" x-model="editing.academic_system" required class="w-full rounded-xl border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:ring-primary">
                        <option value="semester">Semester system</option>
                        <option value="year">Year system</option>
                        <option value="none">Class-based (no semester/year)</option>
                    </select>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                    <div class="mb-3">
                        <p class="text-xs font-bold text-gray-700">Card header</p>
                        <p class="mt-0.5 text-[11px] text-gray-400">Optional university branding shown on the ID card.</p>
                    </div>
                    <div class="space-y-3">
                        <input type="text" name="university" x-model="editing.university" placeholder="University name"
                            class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                        <input type="text" name="university_college" x-model="editing.university_college" placeholder="College name"
                            class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                        <input type="text" name="university_logo" x-model="editing.university_logo" placeholder="Logo path under public/"
                            class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                    </div>
                </div>
                <label class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 px-4 py-3">
                    <span>
                        <span class="block text-xs font-semibold text-gray-700">Active department</span>
                        <span class="mt-0.5 block text-[11px] text-gray-400">Available in student and subject forms.</span>
                    </span>
                    <input type="checkbox" name="is_active" value="1" x-model="editing.is_active" class="rounded border-gray-300 text-primary focus:ring-primary">
                </label>
                <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                    <button type="button" @click="closeEdit()" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-light">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ── SECTIONS ────────────────────────────────────────────────────────────── --}}
@if($tab === 'sections')
@php
    $secQuery = \App\Models\Card\Section::with('department.organization');
    if ($deptId) $secQuery->where('department_id', $deptId);
    elseif ($orgId) $secQuery->whereHas('department', fn($q) => $q->where('organization_id', $orgId));
    $secs = $secQuery->orderBy('name')->get();
    $initialSectionOrg = $organizations->firstWhere('id', (int) $orgId) ?? $organizations->first();
@endphp
<div class="space-y-4">
    <details class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" @if($errors->any()) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3"><div><h3 class="text-sm font-bold text-gray-900">Add section</h3><p class="text-[11px] text-gray-400">Create a section inside a class or department.</p></div><span class="rounded-lg bg-primary px-3 py-2 text-xs font-bold text-white">+ Add new</span></summary>
        <form method="POST" action="{{ route('settings.sections.store') }}" class="grid gap-3 border-t border-gray-100 p-4 sm:grid-cols-2 xl:grid-cols-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Organization</label>
                <select name="_org_id" id="sec-org" class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                    @foreach($organizations as $o)
                        <option value="{{ $o->id }}" @selected($initialSectionOrg?->id === $o->id)>{{ $o->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Class / Department</label>
                <select name="department_id" id="sec-dept" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                    <option value="">— select —</option>
                    @foreach($initialSectionOrg?->departments ?? collect() as $department)<option value="{{ $department->id }}" @selected((int) $deptId === $department->id)>{{ $department->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Section name</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. A, Mercury" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Elective group <span class="font-normal text-gray-400">(optional)</span></label>
                <input type="text" name="group_name" value="{{ old('group_name') }}" placeholder="e.g. Bio or Computer" class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
            </div>
            <label class="flex items-center gap-2 text-xs font-semibold text-gray-600"><input type="checkbox" name="is_active" value="1" checked class="rounded text-primary"> Active</label>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-primary-light sm:col-start-2 xl:col-start-4">Add section</button>
        </form>
    </details>

    <div class="flex gap-2 overflow-x-auto rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
        <a href="{{ route('settings.index', array_filter(['tab'=>'sections','from'=>request('from')])) }}" class="whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold {{ !$orgId ? 'bg-primary text-white' : 'border bg-white text-gray-500' }}">All</a>
        @foreach($organizations as $organization)<a href="{{ route('settings.index', array_filter(['tab'=>'sections','org'=>$organization->id,'from'=>request('from')])) }}" class="whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold {{ (int)$orgId === $organization->id && !$deptId ? 'bg-primary text-white' : 'border bg-white text-gray-500' }}">{{ $organization->name }}</a>@endforeach
        @if($initialSectionOrg && $orgId) @foreach($initialSectionOrg->departments as $department)<a href="{{ route('settings.index', array_filter(['tab'=>'sections','org'=>$initialSectionOrg->id,'dept'=>$department->id,'from'=>request('from')])) }}" class="whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold {{ (int)$deptId === $department->id ? 'bg-primary-light text-white' : 'border bg-gray-50 text-gray-500' }}">{{ $department->name }}</a>@endforeach @endif
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($secs as $sec)
            @php $secStudentCount = $sec->studentsQuery()->count(); @endphp
            <article class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between"><div><h3 class="text-sm font-bold text-gray-900">Section {{ $sec->name }}</h3><p class="mt-0.5 text-[11px] text-gray-400">{{ $sec->department->name }} · {{ $sec->department->organization->name }}</p></div><span class="h-2.5 w-2.5 rounded-full {{ $sec->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}"></span></div>
                @if($sec->group_name)<span class="mt-2 inline-flex rounded-md bg-purple-50 px-2 py-1 text-[10px] font-bold text-purple-700">{{ $sec->group_name }}</span>@endif
                <p class="mt-2 text-[10px] font-semibold text-gray-400">{{ $secStudentCount }} {{ Str::plural('student', $secStudentCount) }}</p>
                <div class="mt-3 flex justify-end gap-1.5 border-t border-gray-100 pt-2.5"><button type="button" onclick="document.getElementById('edit-section-{{ $sec->id }}').showModal()" class="rounded-lg border px-3 py-1.5 text-[11px] font-bold text-gray-600">Edit</button><form method="POST" action="{{ route('settings.sections.destroy', $sec) }}" onsubmit="return confirm('Delete this section?')">@csrf @method('DELETE')<button @disabled($secStudentCount > 0) title="{{ $secStudentCount > 0 ? $secStudentCount.' '.Str::plural('student', $secStudentCount).' still belong to this section' : 'Delete section' }}" class="rounded-lg border border-red-100 px-3 py-1.5 text-[11px] font-bold text-red-500 disabled:cursor-not-allowed disabled:border-gray-100 disabled:text-gray-300">Delete</button></form></div>
            </article>
            <dialog id="edit-section-{{ $sec->id }}" class="m-auto w-[calc(100%_-_2rem)] max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-gray-950/55"><form method="POST" action="{{ route('settings.sections.update', $sec) }}">@csrf @method('PATCH')<div class="flex items-center justify-between border-b px-5 py-4"><div><p class="text-[10px] font-bold uppercase tracking-widest text-primary">Edit section</p><h3 class="font-bold">{{ $sec->department->name }} · {{ $sec->name }}</h3></div><button type="button" onclick="this.closest('dialog').close()" class="h-8 w-8 rounded-full bg-gray-100 text-xl text-gray-500">&times;</button></div><div class="space-y-3 p-5"><div><label class="mb-1 block text-xs font-semibold text-gray-600">Section name</label><input name="name" value="{{ $sec->name }}" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm"></div><div><label class="mb-1 block text-xs font-semibold text-gray-600">Elective group</label><input name="group_name" value="{{ $sec->group_name }}" placeholder="Optional" class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm"></div><label class="flex items-center gap-2 text-xs font-semibold text-gray-600"><input type="checkbox" name="is_active" value="1" @checked($sec->is_active) class="rounded text-primary"> Active</label></div><div class="flex justify-end gap-2 border-t px-5 py-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border px-4 py-2 text-xs font-bold text-gray-600">Cancel</button><button class="rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white">Save changes</button></div></form></dialog>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed bg-white py-12 text-center text-sm text-gray-400">No sections found for this filter.</div>
        @endforelse
    </div>
</div>
@endif

{{-- ── MEMBER TYPES ────────────────────────────────────────────────────────── --}}
@if($tab === 'member_types')
@php
    $mtQuery = \App\Models\Card\MemberType::with('organization');
    if ($orgId) $mtQuery->where('organization_id', $orgId);
    $memberTypes = $mtQuery->orderBy('name')->get();
@endphp
<div class="space-y-4">
    <details class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" @if($errors->any()) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3"><div><h3 class="text-sm font-bold text-gray-900">Add member type</h3><p class="text-[11px] text-gray-400">Create a student, teacher, staff, or custom card category.</p></div><span class="rounded-lg bg-primary px-3 py-2 text-xs font-bold text-white">+ Add new</span></summary>
        <form method="POST" action="{{ route('settings.member-types.store') }}" class="grid gap-3 border-t border-gray-100 p-4 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Organization</label>
                <select name="organization_id" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                    @foreach($organizations as $o)
                        <option value="{{ $o->id }}" @selected($orgId == $o->id)>{{ $o->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Type name</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Student, Teacher, Staff" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
            </div>
            <label class="flex h-[38px] items-center gap-2 rounded-lg border px-3 text-xs font-semibold text-gray-600"><input type="checkbox" name="is_active" value="1" checked class="rounded text-primary"> Active</label>
            <button type="submit" class="h-[38px] rounded-lg bg-primary px-4 text-xs font-bold text-white hover:bg-primary-light">Add type</button>
        </form>
    </details>

    <div class="flex gap-2 overflow-x-auto rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
        <a href="{{ route('settings.index', array_filter(['tab'=>'member_types','from'=>request('from')])) }}" class="whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold {{ !$orgId ? 'bg-primary text-white' : 'border text-gray-500' }}">All organizations</a>
        @foreach($organizations as $organization)<a href="{{ route('settings.index', array_filter(['tab'=>'member_types','org'=>$organization->id,'from'=>request('from')])) }}" class="whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold {{ (int)$orgId === $organization->id ? 'bg-primary text-white' : 'border text-gray-500' }}">{{ $organization->name }}</a>@endforeach
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($memberTypes as $mt)
            <article class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm"><div class="flex items-start justify-between gap-3"><div class="flex min-w-0 items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-xs font-black text-primary">{{ Str::upper(Str::substr($mt->name,0,2)) }}</span><div class="min-w-0"><h3 class="truncate text-sm font-bold text-gray-900">{{ $mt->name }}</h3><p class="truncate text-[11px] text-gray-400">{{ $mt->organization->name }}</p></div></div><span class="h-2.5 w-2.5 rounded-full {{ $mt->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}"></span></div><div class="mt-3 flex justify-end gap-1.5 border-t border-gray-100 pt-2.5"><button type="button" onclick="document.getElementById('edit-member-type-{{ $mt->id }}').showModal()" class="rounded-lg border px-3 py-1.5 text-[11px] font-bold text-gray-600">Edit</button><form method="POST" action="{{ route('settings.member-types.destroy', $mt) }}" onsubmit="return confirm('Delete this member type?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-100 px-3 py-1.5 text-[11px] font-bold text-red-500">Delete</button></form></div></article>
            <dialog id="edit-member-type-{{ $mt->id }}" class="m-auto w-[calc(100%_-_2rem)] max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-gray-950/55"><form method="POST" action="{{ route('settings.member-types.update', $mt) }}">@csrf @method('PATCH')<div class="flex items-center justify-between border-b px-5 py-4"><div><p class="text-[10px] font-bold uppercase tracking-widest text-primary">Edit member type</p><h3 class="font-bold">{{ $mt->name }}</h3></div><button type="button" onclick="this.closest('dialog').close()" class="h-8 w-8 rounded-full bg-gray-100 text-xl text-gray-500">&times;</button></div><div class="space-y-3 p-5"><div><label class="mb-1 block text-xs font-semibold text-gray-600">Type name</label><input name="name" value="{{ $mt->name }}" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm"></div><label class="flex items-center gap-2 text-xs font-semibold text-gray-600"><input type="checkbox" name="is_active" value="1" @checked($mt->is_active) class="rounded text-primary"> Active</label></div><div class="flex justify-end gap-2 border-t px-5 py-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border px-4 py-2 text-xs font-bold text-gray-600">Cancel</button><button class="rounded-lg bg-primary px-4 py-2 text-xs font-bold text-white">Save changes</button></div></form></dialog>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed bg-white py-12 text-center text-sm text-gray-400">No member types found.</div>
        @endforelse
    </div>
</div>
@endif

{{-- ── CARD ASSETS ──────────────────────────────────────────────────────────── --}}
@if($tab === 'assets')
<div class="space-y-4">
    <details class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" @if($errors->any()) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3"><div><h3 class="text-sm font-bold text-gray-900">Upload card asset</h3><p class="text-[11px] text-gray-400">Upload once and reuse across organizations.</p></div><span class="rounded-lg bg-primary px-3 py-2 text-xs font-bold text-white">+ Upload new</span></summary>
        <form method="POST" action="{{ route('settings.assets.store') }}" enctype="multipart/form-data" class="grid gap-3 border-t border-gray-100 p-4 sm:grid-cols-2 xl:grid-cols-[1fr_180px_1fr_auto] xl:items-end">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Asset name</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. College Stamp 2026" required class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Type</label>
                <select name="type" class="w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                    <option value="logo">Logo</option>
                    <option value="signature">Signature</option>
                    <option value="stamp">Stamp</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Image file</label>
                <input type="file" name="file" accept="image/*" required
                    class="w-full rounded-lg border border-gray-200 bg-white p-1 text-xs text-gray-500 file:mr-2 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs">
            </div>
            <button type="submit" class="h-[38px] rounded-lg bg-primary px-5 text-xs font-bold text-white hover:bg-primary-light">Upload asset</button>
        </form>
    </details>

    @forelse($assets->groupBy('type') as $assetType => $typeAssets)
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <header class="flex items-center justify-between border-b border-gray-100 bg-gray-50/70 px-4 py-3"><div><h3 class="text-sm font-bold capitalize text-gray-800">{{ $assetType }} library</h3><p class="text-[10px] text-gray-400">Reusable organization {{ $assetType }} assets</p></div><span class="rounded-full border bg-white px-2.5 py-1 text-[10px] font-bold text-gray-500">{{ $typeAssets->count() }} {{ Str::plural('asset', $typeAssets->count()) }}</span></header>
            <div class="grid gap-3 p-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($typeAssets as $asset)
                    @php
                        $usedBy = \App\Models\Card\Organization::where('logo_asset_id', $asset->id)->orWhere('signature_asset_id', $asset->id)->orWhere('stamp_asset_id', $asset->id)->pluck('name');
                    @endphp
                    <article class="rounded-xl border border-gray-200 p-3">
                        <div class="flex h-24 items-center justify-center rounded-lg bg-gray-50 p-3"><img src="{{ asset($asset->path) }}" alt="{{ $asset->name }}" class="max-h-full max-w-full object-contain"></div>
                        <div class="mt-3 flex items-start justify-between gap-2"><div class="min-w-0"><h4 class="truncate text-sm font-bold text-gray-800">{{ $asset->name }}</h4><p class="mt-0.5 truncate text-[10px] text-gray-400" title="{{ $usedBy->join(', ') }}">{{ $usedBy->isEmpty() ? 'Not assigned' : 'Used by '.$usedBy->join(', ') }}</p></div><span class="rounded-md bg-gray-100 px-2 py-1 text-[9px] font-bold capitalize text-gray-500">{{ $asset->type }}</span></div>
                        <div class="mt-3 flex justify-end border-t border-gray-100 pt-2.5"><form method="POST" action="{{ route('settings.assets.destroy', $asset) }}" onsubmit="return confirm('Delete this asset?')">@csrf @method('DELETE')<button @disabled($usedBy->isNotEmpty()) title="{{ $usedBy->isNotEmpty() ? 'Cannot delete an asset currently in use' : 'Delete asset' }}" class="rounded-lg border border-red-100 px-3 py-1.5 text-[11px] font-bold text-red-500 disabled:cursor-not-allowed disabled:border-gray-100 disabled:text-gray-300">Delete</button></form></div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-dashed bg-white py-12 text-center text-sm text-gray-400">No assets uploaded yet.</div>
    @endforelse
</div>
@endif

{{-- ── CARD BACKGROUNDS ─────────────────────────────────────────────────────── --}}
@if($tab === 'backgrounds')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Upload form --}}
    <div class="bg-white rounded-2xl border p-6">
        <h3 class="font-bold text-primary text-sm mb-1">Upload Card Background</h3>
        <p class="text-xs text-gray-400 mb-4">Upload a full-card background image (54×85.6 mm, PNG recommended). Set it active to use it on printed cards.</p>
        <form method="POST" action="{{ route('settings.backgrounds.store') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                <input type="text" name="name" placeholder="e.g. School Student BG v2" required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Organization Type</label>
                <select name="org_type" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="school">School</option>
                    <option value="college">College</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Member Type</label>
                <select name="member_type" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="student">Student</option>
                    <option value="staff">Staff</option>
                    <option value="teacher">Teacher / Faculty</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Image File</label>
                <input type="file" name="file" accept="image/*" required
                    class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100">
            </div>
            <button type="submit" class="w-full bg-primary text-white text-sm font-semibold py-2 rounded-lg hover:bg-primary-light transition">
                Upload Background
            </button>
        </form>

        <div class="mt-5 pt-4 border-t text-xs text-gray-500 space-y-1">
            <p class="font-semibold text-gray-600">How it works</p>
            <p>Each org type + member type slot can have multiple uploaded backgrounds. Mark one as <strong>Active</strong> to use it on all new cards. The built-in defaults are used when no active background is set.</p>
        </div>
    </div>

    {{-- Background library --}}
    <div class="lg:col-span-2 space-y-4">
        @php
            $bgGroups = $cardBackgrounds->groupBy(fn($b) => $b->org_type . '/' . $b->member_type);
        @endphp
        @forelse($bgGroups as $groupKey => $bgs)
        @php [$gOrg, $gMember] = explode('/', $groupKey); @endphp
        <div class="bg-white rounded-2xl border overflow-hidden">
            <div class="bg-gray-50 px-5 py-3 flex items-center gap-2 border-b">
                <span class="text-xs font-bold uppercase tracking-wide text-primary">{{ ucfirst($gOrg) }}</span>
                <span class="text-gray-300">/</span>
                <span class="text-xs font-semibold text-gray-600">{{ ucfirst($gMember) }}</span>
                @php $activeCount = $bgs->where('is_active', true)->count(); @endphp
                @if($activeCount)
                <span class="ml-auto text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">1 active</span>
                @else
                <span class="ml-auto text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">no active — using default</span>
                @endif
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 p-4">
                @foreach($bgs as $bg)
                <div class="relative group border rounded-xl overflow-hidden {{ $bg->is_active ? 'ring-2 ring-primary' : 'border-gray-200' }}">
                    <img src="{{ asset($bg->file_path) }}" alt="{{ $bg->name }}"
                         class="w-full object-cover aspect-[54/86] bg-gray-100">
                    @if($bg->is_active)
                    <div class="absolute top-1.5 left-1.5 bg-primary text-white text-[10px] font-bold px-1.5 py-0.5 rounded">ACTIVE</div>
                    @endif
                    <div class="p-2 bg-white border-t">
                        <p class="text-xs font-medium text-gray-700 truncate">{{ $bg->name }}</p>
                        <div class="flex gap-1 mt-1.5">
                            @if(!$bg->is_active)
                            <form method="POST" action="{{ route('settings.backgrounds.activate', $bg) }}">
                                @csrf
                                <button class="text-[11px] bg-primary text-white px-2 py-0.5 rounded hover:bg-primary-light transition">Set Active</button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('settings.backgrounds.destroy', $bg) }}"
                                  onsubmit="return confirm('Delete background {{ addslashes($bg->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="text-[11px] text-red-500 border border-red-200 px-2 py-0.5 rounded hover:bg-red-50 transition">Del</button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border px-5 py-10 text-center text-gray-400">
            <p class="font-medium">No card backgrounds uploaded yet.</p>
            <p class="text-sm mt-1">Upload backgrounds using the form on the left. Built-in default images are used until you set one active.</p>
        </div>
        @endforelse
    </div>

</div>
@endif

@endsection
@endif

@push('scripts')
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
// Dynamic dept dropdown when org changes on sections tab
document.getElementById('sec-org')?.addEventListener('change', function () {
    const orgId = this.value;
    const deptSel = document.getElementById('sec-dept');
    deptSel.innerHTML = '<option value="">Loading...</option>';
    const departmentsUrl = @js(route('settings.api.departments', ['organization' => '__ORG_ID__']));
    fetch(departmentsUrl.replace('__ORG_ID__', encodeURIComponent(orgId)))
        .then(r => r.json())
        .then(depts => {
            deptSel.innerHTML = '<option value="">-- select dept --</option>' +
                depts.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
        });
});
</script>
@endpush
