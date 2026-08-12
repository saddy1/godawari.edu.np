@extends('layouts.admin')
@section('title', 'Staff & Role Management')

@php
$isSuperAdmin = auth()->user()?->isSuperAdmin();
$isOwner      = auth()->user()?->isOwner();   // only this user can grant/revoke super-admin
$isPrincipal  = auth()->user()?->isPrincipal();

$roleConfig = [
    'super-admin'   => ['label'=>'Super Admin',   'color'=>'amber',  'desc'=>'Unrestricted system access',          'icon'=>'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
    'principal'     => ['label'=>'Principal',     'color'=>'purple', 'desc'=>'Full access · final approval authority', 'icon'=>'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    'administrator' => ['label'=>'Administrator', 'color'=>'blue',   'desc'=>'Content management — posts, gallery…', 'icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
    'accountant'    => ['label'=>'Accountant',    'color'=>'teal',   'desc'=>'Payroll & financial reports',           'icon'=>'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
    'store-keeper'  => ['label'=>'Store Keeper',  'color'=>'green',  'desc'=>'Store issue, dakhila, stock and reports', 'icon'=>'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
    'librarian'     => ['label'=>'Librarian',     'color'=>'indigo', 'desc'=>'Library responsibility',                 'icon'=>'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253'],
    'technical'     => ['label'=>'Technical',     'color'=>'slate',  'desc'=>'Technical support responsibility',       'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37'],
];
$colorMap = [
    'amber'  => ['badge'=>'bg-amber-50 text-amber-700 border border-amber-200',   'avatar'=>'bg-amber-500',   'info'=>'bg-amber-50 border-amber-200 text-amber-700'],
    'purple' => ['badge'=>'bg-purple-50 text-purple-700 border border-purple-200','avatar'=>'bg-purple-500',  'info'=>'bg-purple-50 border-purple-200 text-purple-700'],
    'blue'   => ['badge'=>'bg-blue-50 text-blue-700 border border-blue-200',      'avatar'=>'bg-blue-500',    'info'=>'bg-blue-50 border-blue-200 text-blue-700'],
    'teal'   => ['badge'=>'bg-teal-50 text-teal-700 border border-teal-200',      'avatar'=>'bg-teal-500',    'info'=>'bg-teal-50 border-teal-200 text-teal-700'],
    'green'  => ['badge'=>'bg-green-50 text-green-700 border border-green-200',   'avatar'=>'bg-green-600',   'info'=>'bg-green-50 border-green-200 text-green-700'],
    'indigo' => ['badge'=>'bg-indigo-50 text-indigo-700 border border-indigo-200','avatar'=>'bg-indigo-500',  'info'=>'bg-indigo-50 border-indigo-200 text-indigo-700'],
    'slate'  => ['badge'=>'bg-slate-50 text-slate-700 border border-slate-200',   'avatar'=>'bg-slate-500',   'info'=>'bg-slate-50 border-slate-200 text-slate-700'],
];
$showForm = $errors->any() || old('hr_member_id');
@endphp

@section('content')
<div class="max-w-7xl mx-auto space-y-6"
     x-data="{ formOpen: {{ $showForm ? 'true' : 'false' }} }">

    {{-- ── Page Header ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Staff & Role Management</h2>
            <p class="text-sm text-gray-500 mt-1">
                @if($isSuperAdmin)
                    Add admin access to HR members and assign roles dynamically.
                @else
                    Assign the Administrator role to HR members.
                @endif
            </p>
        </div>
        <button @click="formOpen = !formOpen"
                class="flex items-center gap-2 px-4 py-2.5 bg-[#1a5632] text-white text-sm font-bold rounded-xl hover:bg-[#0b2415] transition-colors shadow-sm shrink-0">
            <svg class="w-4 h-4 transition-transform duration-200" :class="formOpen ? 'rotate-45' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span x-text="formOpen ? 'Cancel' : 'Add Admin From HR'">Add Admin From HR</span>
        </button>
    </div>

    {{-- ── Flash messages ── --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3.5 text-sm font-semibold flex items-center gap-3">
        <svg class="w-5 h-5 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3.5 text-sm">
        <p class="font-bold mb-1">Please fix the following:</p>
        <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- ── Role legend ── --}}
    <div class="flex flex-wrap gap-2">
        @foreach($roleConfig as $role => $cfg)
        @php $col = $colorMap[$cfg['color']]; @endphp
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs {{ $col['info'] }}">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg['icon'] }}"/></svg>
            <span class="font-bold">{{ $cfg['label'] }}:</span>
            <span class="hidden sm:inline text-gray-600">{{ $cfg['desc'] }}</span>
        </div>
        @endforeach
    </div>

    {{-- ── Collapsible Create Form ── --}}
    <div x-show="formOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         style="display:none;">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center gap-3">
                <svg class="w-5 h-5 text-[#1a5632]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Add Admin From HR</h3>
                    <p class="text-xs text-gray-500">
                        @if($isSuperAdmin) Select an HR member, then assign Principal, Administrator, or Accountant access.
                        @else You can assign the Administrator role.
                        @endif
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="p-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5">

                    {{-- Role selector --}}
                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                        <div class="flex flex-wrap gap-3">
                            @foreach($assignableRoles as $role)
                            @php $cfg = $roleConfig[$role]; @endphp
                            <label class="cursor-pointer">
                                <input type="radio" name="role" value="{{ $role }}" class="sr-only peer"
                                       {{ old('role', $assignableRoles[0]) === $role ? 'checked' : '' }}>
                                <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 border-gray-200 bg-white transition-all
                                            peer-checked:border-[#1a5632] peer-checked:bg-[#1a5632]/5 hover:border-gray-300 cursor-pointer">
                                    <svg class="w-4 h-4 text-gray-400 peer-checked:text-[#1a5632]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg['icon'] }}"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-bold text-gray-700">{{ $cfg['label'] }}</p>
                                        <p class="text-[10px] text-gray-400 hidden sm:block">{{ $cfg['desc'] }}</p>
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="md:col-span-2"
                         x-data="hrMemberSearch(@js(route('admin.users.hr-members.search')), @js($selectedHrMember))">
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">HR Member <span class="text-red-500">*</span></label>
                        <input type="hidden" name="hr_member_id" :value="selected ? selected.id : ''" required>
                        <div class="relative" @click.away="open = false">
                            <input type="search"
                                   x-model="query"
                                   @input.debounce.250ms="search"
                                   @focus="open = true; if (!loaded) search()"
                                   placeholder="Search teacher or staff by name, ID, phone, email"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#1a5632] focus:ring-[#1a5632] text-sm transition-colors">
                            <div x-show="open"
                                 x-cloak
                                 class="relative z-10 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg">
                                <template x-if="loading">
                                    <div class="px-4 py-3 text-sm font-semibold text-gray-500">Searching...</div>
                                </template>
                                <template x-if="!loading && results.length === 0">
                                    <div class="px-4 py-3 text-sm font-semibold text-gray-500">No HR members found.</div>
                                </template>
                                <template x-for="member in results" :key="member.id">
                                    <button type="button"
                                            @click="choose(member)"
                                            class="block w-full border-b border-gray-50 px-4 py-3 text-left hover:bg-green-50 last:border-b-0">
                                        <span class="block text-sm font-extrabold text-gray-900" x-text="member.name"></span>
                                        <span class="mt-0.5 block text-xs font-semibold text-gray-500" x-text="member.meta"></span>
                                        <span x-show="member.admin_role" class="mt-1 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-blue-700" x-text="member.admin_role"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div x-show="selected" x-cloak class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-green-100 bg-green-50 px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-extrabold text-[#1a5632]" x-text="selected?.name"></p>
                                <p class="truncate text-xs font-semibold text-gray-500" x-text="selected?.meta"></p>
                            </div>
                            <button type="button" @click="clear" class="shrink-0 text-xs font-extrabold text-red-600 hover:text-red-700">Clear</button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Create and maintain member details in HR first. This page only gives admin access.</p>
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 px-5 py-2.5 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] shadow-sm transition-all text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Assign Role
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Staff List ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-gray-900">All Staff Accounts</h3>
                <p class="text-sm text-gray-500 mt-0.5">{{ $admins->total() }} account{{ $admins->total() !== 1 ? 's' : '' }}</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                <label class="text-xs font-bold uppercase tracking-wide text-gray-400">Rows</label>
                <select name="per_page" onchange="this.form.submit()" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold text-gray-700 outline-none focus:border-[#1a5632]">
                    @foreach([10, 20, 40, 50] as $size)
                        <option value="{{ $size }}" @selected(($perPage ?? 10) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden divide-y divide-gray-100">
            @forelse($admins as $admin)
            @php
                $aRole = $admin->getRoleNames()->first(fn ($role) => in_array($role, array_keys($roleConfig), true)) ?? 'administrator';
                $cfg   = $roleConfig[$aRole] ?? $roleConfig['administrator'];
                $col   = $colorMap[$cfg['color']] ?? $colorMap['blue'];
                $canEdit = !$admin->isSuperAdmin() && !$admin->is(auth()->user())
                           && !($admin->isPrincipal() && !$isSuperAdmin);
            @endphp
            <div class="p-4 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 shrink-0 rounded-full {{ $col['avatar'] }} text-white flex items-center justify-center font-bold text-sm">
                        {{ strtoupper(substr($admin->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-gray-900 text-sm truncate">{{ $admin->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $admin->email }}</p>
                    </div>
                    <span class="shrink-0 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide {{ $col['badge'] }}">{{ $cfg['label'] }}</span>
                </div>
                @if($canEdit)
                <div class="flex items-center gap-2">
                    @if($isSuperAdmin)
                    <a href="{{ route('admin.users.permissions', $admin) }}"
                       class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                       title="Set permissions">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-6H4a1 1 0 00-1 1v14a1 1 0 001 1h16a1 1 0 001-1V5a1 1 0 00-1-1z"/></svg>
                    </a>
                    @endif
                    @if($admin->student)
                        <a href="{{ route('admin.hr.members.edit', $admin->student) }}"
                           class="p-1.5 text-[#1a5632] hover:bg-green-50 rounded-lg transition-colors"
                           title="Edit in HR">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h6v6m2 4H7a2 2 0 01-2-2V7a2 2 0 012-2h3l2-2 2 2h3a2 2 0 012 2v12a2 2 0 01-2 2z"/></svg>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('admin.users.update-role', $admin) }}" class="flex-1 flex items-center gap-2">
                        @csrf @method('PATCH')
                        <select name="role" class="flex-1 text-xs px-3 py-2 rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#1a5632]">
                            @foreach($assignableRoles as $r)
                                <option value="{{ $r }}" @selected($aRole === $r)>{{ $roleConfig[$r]['label'] }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-3 py-1.5 text-xs font-bold bg-[#1a5632]/10 text-[#1a5632] rounded-lg hover:bg-[#1a5632]/20 transition-colors">Save</button>
                    </form>
                    <button @click="$store.resetPw.open=true; $store.resetPw.id={{ $admin->id }}; $store.resetPw.name='{{ addslashes($admin->name) }}'"
                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Reset password">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </button>
                    <form method="POST" action="{{ route('admin.users.destroy', $admin) }}" onsubmit="return confirm('Remove {{ addslashes($admin->name) }}?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
                @else
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs text-gray-400 italic">
                        {{ $admin->isSuperAdmin() ? 'Protected — Super Admin' : ($admin->is(auth()->user()) ? 'Current session' : 'Protected — Principal') }}
                    </p>
                    @if($admin->student)
                        <a href="{{ route('admin.hr.members.edit', $admin->student) }}"
                           class="text-xs font-bold text-[#1a5632] hover:underline">Edit in HR</a>
                    @endif
                </div>
                @endif
            </div>
            @empty
            <div class="py-12 text-center text-gray-500">
                <p class="font-semibold">No accounts yet</p>
                <p class="text-sm mt-1">Add a teacher or staff member from HR.</p>
            </div>
            @endforelse
        </div>

        {{-- Desktop table --}}
        <div class="hidden sm:block">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-gray-100 text-[11px] font-extrabold text-gray-400 uppercase tracking-widest bg-gray-50/60">
                        <th class="w-14 px-4 py-3.5">#</th>
                        <th class="px-4 py-3.5">Staff Member</th>
                        <th class="w-40 px-4 py-3.5">Change Role</th>
                        <th class="w-72 px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($admins as $i => $admin)
                    @php
                        $aRole   = $admin->getRoleNames()->first(fn ($role) => in_array($role, array_keys($roleConfig), true)) ?? 'administrator';
                        $cfg     = $roleConfig[$aRole] ?? $roleConfig['administrator'];
                        $col     = $colorMap[$cfg['color']] ?? $colorMap['blue'];
                        $canEdit = !$admin->isSuperAdmin() && !$admin->is(auth()->user())
                                   && !($admin->isPrincipal() && !$isSuperAdmin);
                    @endphp
                    <tr class="hover:bg-gray-50/60 transition-colors">

                        <td class="px-4 py-4 text-sm text-gray-400 font-medium">
                            {{ $admins->firstItem() + $i }}
                        </td>

                        <td class="px-4 py-4">
                            <div class="flex min-w-0 items-start gap-3">
                                <div class="w-10 h-10 shrink-0 rounded-full {{ $col['avatar'] }} text-white flex items-center justify-center font-bold text-sm">
                                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-bold text-gray-900 text-sm">{{ $admin->name }}</p>
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wide {{ $col['badge'] }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg['icon'] }}"/></svg>
                                            {{ $cfg['label'] }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                                        <span class="max-w-[260px] truncate">{{ $admin->email }}</span>
                                        <span>{{ $admin->phone ?: 'No phone' }}</span>
                                        <span>Joined {{ $admin->created_at?->format('M d, Y') ?? '—' }}</span>
                                        <span class="text-gray-400">{{ $admin->created_at?->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4">
                            @if($canEdit)
                                <form method="POST" action="{{ route('admin.users.update-role', $admin) }}">
                                    @csrf @method('PATCH')
                                    <select name="role"
                                            onchange="this.form.submit()"
                                            class="w-full text-xs px-3 py-2 rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#1a5632] transition-colors cursor-pointer">
                                        @foreach($assignableRoles as $r)
                                            <option value="{{ $r }}" @selected($aRole === $r)>{{ $roleConfig[$r]['label'] }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <span class="text-xs text-gray-400 italic">
                                    {{ $admin->isSuperAdmin() ? 'Protected' : ($admin->is(auth()->user()) ? 'Current user' : 'Protected') }}
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-4 text-right">
                            @if($canEdit)
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if($isSuperAdmin)
                                <a href="{{ route('admin.users.permissions', $admin) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors"
                                   title="Set module permissions">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-6H4a1 1 0 00-1 1v14a1 1 0 001 1h16a1 1 0 001-1V5a1 1 0 00-1-1z"/></svg>
                                    Permissions
                                </a>
                                @endif
                                @if($admin->student)
                                    <a href="{{ route('admin.hr.members.edit', $admin->student) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-[#1a5632] border border-green-200 rounded-lg hover:bg-green-50 transition-colors"
                                       title="Edit in HR">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h6v6m2 4H7a2 2 0 01-2-2V7a2 2 0 012-2h3l2-2 2 2h3a2 2 0 012 2v12a2 2 0 01-2 2z"/></svg>
                                        HR
                                    </a>
                                @endif
                                <button @click="$store.resetPw.open=true; $store.resetPw.id={{ $admin->id }}; $store.resetPw.name='{{ addslashes($admin->name) }}'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-amber-700 border border-amber-200 rounded-lg hover:bg-amber-50 transition-colors"
                                        title="Reset password">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    Reset
                                </button>
                                @if($isOwner && !$admin->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.users.toggle-super-admin', $admin) }}"
                                      onsubmit="return confirm('Grant Super Admin to {{ addslashes($admin->name) }}? They will have unrestricted access to everything.')">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-purple-700 border border-purple-200 rounded-lg hover:bg-purple-50 transition-colors"
                                            title="Grant Super Admin">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                        Super Admin
                                    </button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('admin.users.destroy', $admin) }}"
                                      onsubmit="return confirm('Remove {{ addslashes($admin->name) }}? This cannot be undone.');">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-red-600 border border-red-200 rounded-lg hover:bg-red-50 hover:border-red-300 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Delete
                                    </button>
                                </form>
                            </div>
                            @else
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if($isSuperAdmin && ! $admin->isSuperAdmin())
                                <a href="{{ route('admin.users.permissions', $admin) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors">
                                    Permissions
                                </a>
                                @endif
                                @if($admin->student)
                                    <a href="{{ route('admin.hr.members.edit', $admin->student) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-[#1a5632] border border-green-200 rounded-lg hover:bg-green-50 transition-colors">
                                        HR
                                    </a>
                                @endif
                                @if($isOwner && $admin->isSuperAdmin() && !$admin->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.users.toggle-super-admin', $admin) }}"
                                      onsubmit="return confirm('Revoke Super Admin from {{ addslashes($admin->name) }}? They will be downgraded to Principal.')">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors"
                                            title="Revoke Super Admin">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        Revoke Super Admin
                                    </button>
                                </form>
                                @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-400 bg-gray-100 rounded-lg">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                    Protected
                                </span>
                                @endif
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <p class="font-semibold text-gray-700">No admin accounts yet</p>
                                <p class="text-sm text-gray-400">Add a teacher or staff member from HR.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($admins->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $admins->links() }}
        </div>
        @endif
    </div>

</div>

{{-- ── Reset Password Modal ── --}}
@push('modals')
<div x-data="{ get open() { return $store.resetPw.open; } }"
     x-show="$store.resetPw.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @keydown.escape.window="$store.resetPw.open = false">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="$store.resetPw.open = false"></div>
    <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 z-10" @click.stop>

        <div class="flex items-start justify-between mb-5">
            <div>
                <p class="text-[10px] font-extrabold text-amber-600 uppercase tracking-widest mb-0.5">Reset Password</p>
                <h3 class="text-base font-extrabold text-gray-900" x-text="$store.resetPw.name"></h3>
            </div>
            <button @click="$store.resetPw.open = false" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" :action="'/admin/users/' + $store.resetPw.id + '/password'" @submit="$store.resetPw.open = false">
            @csrf @method('PATCH')
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">New Password</label>
                    <input name="password" type="password" required minlength="8" placeholder="Min. 8 characters"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/10">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Confirm Password</label>
                    <input name="password_confirmation" type="password" required minlength="8" placeholder="Re-enter password"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/10">
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button type="button" @click="$store.resetPw.open = false"
                        class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl transition-colors">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('resetPw', { open: false, id: null, name: '' });

    Alpine.data('hrMemberSearch', (url, initialSelected = null) => ({
        url,
        query: initialSelected?.name || '',
        selected: initialSelected,
        results: [],
        open: false,
        loading: false,
        loaded: false,

        async search() {
            this.loading = true;
            this.open = true;

            try {
                const response = await fetch(`${this.url}?q=${encodeURIComponent(this.query)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();
                this.results = data.results || [];
                this.loaded = true;
            } catch (error) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        choose(member) {
            this.selected = member;
            this.query = member.name;
            this.open = false;
        },

        clear() {
            this.selected = null;
            this.query = '';
            this.results = [];
            this.loaded = false;
            this.open = false;
        },
    }));
});
</script>
@endpush

@endsection
