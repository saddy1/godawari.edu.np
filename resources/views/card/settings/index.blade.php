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
<div class="flex gap-1 bg-white border rounded-xl p-1 mb-6 w-fit">
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
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Add form --}}
    <div class="bg-white rounded-2xl border p-6">
        <h3 class="font-bold text-primary text-sm mb-4">Add Organization</h3>
        <form method="POST" action="{{ route('settings.organizations.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                <input type="text" name="name" placeholder="e.g. School Name" required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Slug <span class="text-gray-400">(used in student records)</span></label>
                <input type="text" name="slug" placeholder="e.g. college" required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                <select name="type" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="college">College</option>
                    <option value="school">School</option>
                    <option value="other">Other</option>
                </select>
            </div>
            @php $assetsByType = $assets->groupBy('type'); @endphp
            <div class="pt-2 border-t space-y-2">
                <p class="text-xs font-semibold text-gray-500">Card Assets <span class="font-normal text-gray-400">(pick from library)</span></p>
                @foreach(['logo_asset_id' => 'Logo', 'signature_asset_id' => 'Signature', 'stamp_asset_id' => 'Stamp'] as $field => $label)
                @php $atype = str_replace('_asset_id', '', $field); @endphp
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
                    <select name="{{ $field }}" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">— none —</option>
                        @foreach($assetsByType->get($atype, collect()) as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Active</label>
            <button type="submit" class="w-full bg-primary text-white text-sm font-semibold py-2 rounded-lg hover:bg-primary-light transition">
                Add Organization
            </button>
        </form>
        <p class="text-xs text-gray-400 mt-3">Upload new assets first in the <a href="{{ route('settings.index', array_filter(['tab'=>'assets', 'from' => request('from')])) }}" class="text-primary underline">Card Assets</a> tab.</p>
    </div>

    {{-- List --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-5 py-3 text-left">Name</th>
                    <th class="px-5 py-3 text-left">Slug</th>
                    <th class="px-5 py-3 text-left">Type</th>
                    <th class="px-5 py-3 text-left">Depts</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($organizations as $org)
                <tr class="hover:bg-gray-50" x-data="{ edit: false }">
                    <td class="px-5 py-3 font-medium">{{ $org->name }}</td>
                    <td class="px-5 py-3 text-gray-500 font-mono text-xs">{{ $org->slug }}</td>
                    <td class="px-5 py-3"><span class="text-xs bg-gray-100 px-2 py-0.5 rounded">{{ $org->type }}</span></td>
                    <td class="px-5 py-3 text-gray-500">{{ $org->departments_count }}</td>
                    <td class="px-5 py-3">
                        <span class="text-xs {{ $org->is_active ? 'text-green-600' : 'text-red-500' }}">
                            {{ $org->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('settings.index', array_filter(['tab' => 'departments', 'org' => $org->id, 'from' => request('from')])) }}"
                                class="text-xs text-blue-600 hover:underline">Depts</a>
                            <button @click="edit=!edit" class="text-xs text-primary hover:underline">Edit</button>
                            <form method="POST" action="{{ route('settings.organizations.destroy', $org) }}"
                                onsubmit="return confirm('Delete {{ addslashes($org->name) }}? All departments/sections will be removed.')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:underline">Del</button>
                            </form>
                        </div>
                        <div x-show="edit" class="mt-2 p-3 bg-gray-50 rounded-lg">
                            <form method="POST" action="{{ route('settings.organizations.update', $org) }}" class="space-y-2">
                                @csrf @method('PATCH')
                                <input type="text" name="name" value="{{ $org->name }}" class="w-full border rounded px-2 py-1 text-xs">
                                <input type="text" name="slug" value="{{ $org->slug }}" class="w-full border rounded px-2 py-1 text-xs font-mono">
                                <select name="type" class="w-full border rounded px-2 py-1 text-xs">
                                    @foreach(['college','school','other'] as $t)
                                        <option value="{{ $t }}" @selected($org->type === $t)>{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs font-semibold text-gray-500 pt-1">Card Assets</p>
                                @foreach(['logo_asset_id' => ['label'=>'Logo','rel'=>'logoAsset'], 'signature_asset_id' => ['label'=>'Signature','rel'=>'signatureAsset'], 'stamp_asset_id' => ['label'=>'Stamp','rel'=>'stampAsset']] as $field => $cfg)
                                @php $atype = str_replace('_asset_id', '', $field); @endphp
                                <div>
                                    <label class="block text-xs text-gray-500 mb-0.5">{{ $cfg['label'] }}
                                        @if($org->{$cfg['rel']})
                                            <span class="text-green-600">✓ {{ $org->{$cfg['rel']}->name }}</span>
                                            <img src="{{ asset($org->{$cfg['rel']}->path) }}" class="inline h-5 ml-1 rounded border">
                                        @endif
                                    </label>
                                    <select name="{{ $field }}" class="w-full border rounded px-2 py-1 text-xs">
                                        <option value="">— none —</option>
                                        @foreach($assetsByType->get($atype, collect()) as $a)
                                            <option value="{{ $a->id }}" @selected($org->{$field} == $a->id)>{{ $a->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endforeach
                                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($org->is_active)> Active</label>
                                <button type="submit" class="w-full bg-primary text-white text-xs py-1 rounded">Save</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">No organizations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── DEPARTMENTS ─────────────────────────────────────────────────────────── --}}
@if($tab === 'departments')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="bg-white rounded-2xl border p-6">
        <h3 class="font-bold text-primary text-sm mb-4">Add Class / Department</h3>
        <form method="POST" action="{{ route('settings.departments.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Organization</label>
                <select name="organization_id" required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    @foreach($organizations as $o)
                        <option value="{{ $o->id }}" @selected($orgId == $o->id)>{{ $o->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Department/Class Name</label>
                <input type="text" name="name" placeholder="e.g. Class 1" required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="pt-2 border-t">
                <p class="text-xs font-semibold text-gray-500 mb-2">Card Header (optional)</p>
                <div class="space-y-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">University Name <span class="text-gray-400">(line 1)</span></label>
                        <input type="text" name="university" placeholder="e.g. TRIBHUVAN UNIVERSITY"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">College Name <span class="text-gray-400">(line 2)</span></label>
                        <input type="text" name="university_college" placeholder="e.g. College Name"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Logo Path <span class="text-gray-400">(under public/)</span></label>
                        <input type="text" name="university_logo" placeholder="e.g. assets/image/logo.png"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Active</label>
            <button type="submit" class="w-full bg-primary text-white text-sm font-semibold py-2 rounded-lg hover:bg-primary-light transition">
                Add Department
            </button>
        </form>

        {{-- Filter --}}
        <div class="mt-5 pt-4 border-t">
            <p class="text-xs font-medium text-gray-500 mb-2">Filter by Organization</p>
            @foreach($organizations as $o)
                <a href="{{ route('settings.index', array_filter(['tab' => 'departments', 'org' => $o->id, 'from' => request('from')])) }}"
                    class="block text-sm py-1 px-2 rounded {{ $orgId == $o->id ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    {{ $o->name }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="lg:col-span-2 bg-white rounded-2xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-5 py-3 text-left">Class / Department</th>
                    <th class="px-5 py-3 text-left">University (card header)</th>
                    <th class="px-5 py-3 text-left">Organization</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @php
                    $depts = $orgId
                        ? \App\Models\Card\Department::with('organization')->where('organization_id', $orgId)->orderBy('name')->get()
                        : \App\Models\Card\Department::with('organization')->orderBy('name')->get();
                @endphp
                @forelse($depts as $dept)
                <tr class="hover:bg-gray-50" x-data="{ edit: false }">
                    <td class="px-5 py-3 font-medium">{{ $dept->name }}</td>
                    <td class="px-5 py-3 text-xs text-gray-500">
                        @if($dept->university)
                            <span class="font-medium text-gray-700">{{ $dept->university }}</span><br>
                            <span class="text-gray-400">{{ $dept->university_college }}</span>
                        @else
                            <span class="text-gray-300 italic">not set</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500 text-xs">{{ $dept->organization->name }}</td>
                    <td class="px-5 py-3"><span class="text-xs {{ $dept->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $dept->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('settings.index', array_filter(['tab' => 'sections', 'org' => $dept->organization_id, 'dept' => $dept->id, 'from' => request('from')])) }}"
                                class="text-xs text-blue-600 hover:underline">Sections</a>
                            <button @click="edit=!edit" class="text-xs text-primary hover:underline">Edit</button>
                            <form method="POST" action="{{ route('settings.departments.destroy', $dept) }}"
                                onsubmit="return confirm('Delete this department?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:underline">Del</button>
                            </form>
                        </div>
                        <div x-show="edit" class="mt-2 p-3 bg-gray-50 rounded-lg">
                            <form method="POST" action="{{ route('settings.departments.update', $dept) }}" class="space-y-2">
                                @csrf @method('PATCH')
                                <input type="text" name="name" value="{{ $dept->name }}" placeholder="Department name" class="w-full border rounded px-2 py-1 text-xs">
                                <p class="text-xs font-semibold text-gray-500 pt-1">Card Header</p>
                                <input type="text" name="university" value="{{ $dept->university }}" placeholder="University name (line 1)" class="w-full border rounded px-2 py-1 text-xs">
                                <input type="text" name="university_college" value="{{ $dept->university_college }}" placeholder="College name (line 2)" class="w-full border rounded px-2 py-1 text-xs">
                                <input type="text" name="university_logo" value="{{ $dept->university_logo }}" placeholder="Logo path e.g. assets/image/logo.png" class="w-full border rounded px-2 py-1 text-xs font-mono">
                                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($dept->is_active)> Active</label>
                                <button type="submit" class="w-full bg-primary text-white text-xs py-1 rounded">Save</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">No departments. Select an organization or add one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── SECTIONS ────────────────────────────────────────────────────────────── --}}
@if($tab === 'sections')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="bg-white rounded-2xl border p-6">
        <h3 class="font-bold text-primary text-sm mb-4">Add Section</h3>
        <form method="POST" action="{{ route('settings.sections.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Organization</label>
                <select name="_org_id" id="sec-org"
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    @foreach($organizations as $o)
                        <option value="{{ $o->id }}" @selected($orgId == $o->id)>{{ $o->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Class / Department</label>
                <select name="department_id" id="sec-dept" required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    @if($deptId)
                        @php $selDept = \App\Models\Card\Department::find($deptId); @endphp
                        @if($selDept)
                            <option value="{{ $selDept->id }}" selected>{{ $selDept->name }}</option>
                        @endif
                    @else
                        <option value="">-- select organization first --</option>
                    @endif
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Section Name</label>
                <input type="text" name="name" placeholder="e.g. A" required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Active</label>
            <button type="submit" class="w-full bg-primary text-white text-sm font-semibold py-2 rounded-lg hover:bg-primary-light transition">
                Add Section
            </button>
        </form>

        {{-- Drill-down filter --}}
        <div class="mt-5 pt-4 border-t space-y-1">
            @foreach($organizations as $o)
                <a href="{{ route('settings.index', array_filter(['tab' => 'sections', 'org' => $o->id, 'from' => request('from')])) }}"
                    class="block text-sm py-1 px-2 rounded {{ $orgId == $o->id ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    {{ $o->name }}
                </a>
                @if($orgId == $o->id)
                    @foreach($o->departments as $d)
                        <a href="{{ route('settings.index', array_filter(['tab' => 'sections', 'org' => $o->id, 'dept' => $d->id, 'from' => request('from')])) }}"
                            class="block text-xs py-1 pl-5 pr-2 rounded {{ $deptId == $d->id ? 'bg-primary-light text-white' : 'text-gray-500 hover:bg-gray-100' }}">
                            &mdash; {{ $d->name }}
                        </a>
                    @endforeach
                @endif
            @endforeach
        </div>
    </div>

    <div class="lg:col-span-2 bg-white rounded-2xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-5 py-3 text-left">Section</th>
                    <th class="px-5 py-3 text-left">Class / Department</th>
                    <th class="px-5 py-3 text-left">Organization</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @php
                    $secQuery = \App\Models\Card\Section::with('department.organization');
                    if ($deptId) $secQuery->where('department_id', $deptId);
                    elseif ($orgId) $secQuery->whereHas('department', fn($q) => $q->where('organization_id', $orgId));
                    $secs = $secQuery->orderBy('name')->get();
                @endphp
                @forelse($secs as $sec)
                <tr class="hover:bg-gray-50" x-data="{ edit: false }">
                    <td class="px-5 py-3 font-medium">{{ $sec->name }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $sec->department->name }}</td>
                    <td class="px-5 py-3 text-xs text-gray-400">{{ $sec->department->organization->name }}</td>
                    <td class="px-5 py-3"><span class="text-xs {{ $sec->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $sec->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <button @click="edit=!edit" class="text-xs text-primary hover:underline">Edit</button>
                            <form method="POST" action="{{ route('settings.sections.destroy', $sec) }}"
                                onsubmit="return confirm('Delete this section?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:underline">Del</button>
                            </form>
                        </div>
                        <div x-show="edit" class="mt-2 p-3 bg-gray-50 rounded-lg">
                            <form method="POST" action="{{ route('settings.sections.update', $sec) }}" class="space-y-2">
                                @csrf @method('PATCH')
                                <input type="text" name="name" value="{{ $sec->name }}" class="w-full border rounded px-2 py-1 text-xs">
                                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($sec->is_active)> Active</label>
                                <button type="submit" class="w-full bg-primary text-white text-xs py-1 rounded">Save</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">No sections. Select a department to filter or add one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── MEMBER TYPES ────────────────────────────────────────────────────────── --}}
@if($tab === 'member_types')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="bg-white rounded-2xl border p-6">
        <h3 class="font-bold text-primary text-sm mb-4">Add Member Type</h3>
        <form method="POST" action="{{ route('settings.member-types.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Organization</label>
                <select name="organization_id" required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    @foreach($organizations as $o)
                        <option value="{{ $o->id }}" @selected($orgId == $o->id)>{{ $o->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Type Name</label>
                <input type="text" name="name" placeholder="e.g. Student, Teacher, Staff" required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Active</label>
            <button type="submit" class="w-full bg-primary text-white text-sm font-semibold py-2 rounded-lg hover:bg-primary-light transition">
                Add Member Type
            </button>
        </form>

        <div class="mt-5 pt-4 border-t space-y-1">
            @foreach($organizations as $o)
                <a href="{{ route('settings.index', array_filter(['tab' => 'member_types', 'org' => $o->id, 'from' => request('from')])) }}"
                    class="block text-sm py-1 px-2 rounded {{ $orgId == $o->id ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    {{ $o->name }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="lg:col-span-2 bg-white rounded-2xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-5 py-3 text-left">Type</th>
                    <th class="px-5 py-3 text-left">Organization</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @php
                    $mtQuery = \App\Models\Card\MemberType::with('organization');
                    if ($orgId) $mtQuery->where('organization_id', $orgId);
                    $memberTypes = $mtQuery->orderBy('name')->get();
                @endphp
                @forelse($memberTypes as $mt)
                <tr class="hover:bg-gray-50" x-data="{ edit: false }">
                    <td class="px-5 py-3 font-medium">{{ $mt->name }}</td>
                    <td class="px-5 py-3 text-xs text-gray-400">{{ $mt->organization->name }}</td>
                    <td class="px-5 py-3"><span class="text-xs {{ $mt->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $mt->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <button @click="edit=!edit" class="text-xs text-primary hover:underline">Edit</button>
                            <form method="POST" action="{{ route('settings.member-types.destroy', $mt) }}"
                                onsubmit="return confirm('Delete this member type?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:underline">Del</button>
                            </form>
                        </div>
                        <div x-show="edit" class="mt-2 p-3 bg-gray-50 rounded-lg">
                            <form method="POST" action="{{ route('settings.member-types.update', $mt) }}" class="space-y-2">
                                @csrf @method('PATCH')
                                <input type="text" name="name" value="{{ $mt->name }}" class="w-full border rounded px-2 py-1 text-xs">
                                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($mt->is_active)> Active</label>
                                <button type="submit" class="w-full bg-primary text-white text-xs py-1 rounded">Save</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">No member types yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── CARD ASSETS ──────────────────────────────────────────────────────────── --}}
@if($tab === 'assets')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Upload form --}}
    <div class="bg-white rounded-2xl border p-6">
        <h3 class="font-bold text-primary text-sm mb-1">Upload New Asset</h3>
        <p class="text-xs text-gray-400 mb-4">Upload once, assign to any number of organizations.</p>
        <form method="POST" action="{{ route('settings.assets.store') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Asset Name</label>
                <input type="text" name="name" placeholder="e.g.   Stamp 2026" required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                <select name="type" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="logo">Logo</option>
                    <option value="signature">Signature</option>
                    <option value="stamp">Stamp</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Image File</label>
                <input type="file" name="file" accept="image/*" required
                    class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100">
            </div>
            <button type="submit" class="w-full bg-primary text-white text-sm font-semibold py-2 rounded-lg hover:bg-primary-light transition">
                Upload Asset
            </button>
        </form>
    </div>

    {{-- Asset library --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Preview</th>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Used by</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($assets as $asset)
                @php
                    $usedBy = \App\Models\Card\Organization::where('logo_asset_id', $asset->id)
                        ->orWhere('signature_asset_id', $asset->id)
                        ->orWhere('stamp_asset_id', $asset->id)
                        ->pluck('name');
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <img src="{{ asset($asset->path) }}" class="h-10 w-auto rounded border object-contain bg-gray-50">
                    </td>
                    <td class="px-4 py-3 font-medium">{{ $asset->name }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600">{{ $asset->type }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        @if($usedBy->isEmpty())
                            <span class="text-gray-300 italic">unused</span>
                        @else
                            {{ $usedBy->join(', ') }}
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('settings.assets.destroy', $asset) }}"
                            onsubmit="return confirm('Delete asset {{ addslashes($asset->name) }}?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:underline"
                                {{ $usedBy->isNotEmpty() ? 'disabled title=Cannot delete: in use' : '' }}>
                                Del
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No assets uploaded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
    fetch(`/settings/api/departments/${orgId}`)
        .then(r => r.json())
        .then(depts => {
            deptSel.innerHTML = '<option value="">-- select dept --</option>' +
                depts.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
        });
});
</script>
@endpush
