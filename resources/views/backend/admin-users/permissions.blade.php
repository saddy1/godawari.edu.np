@extends('layouts.admin')
@section('title', 'Set Staff Permissions')

@php
    $actionLabels = [
        'view' => 'View',
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'general' => 'General',
        'financial' => 'Financial',
        'applications' => 'Applications',
        'export' => 'Export',
        'schedule' => 'Schedule',
        'process' => 'Process',
        'approve' => 'Approve',
        'report' => 'Report',
        'cancel' => 'Cancel',
        'submit' => 'Submit',
        'review' => 'Review',
        'manage' => 'Manage',
    ];
@endphp

@section('content')
<div class="max-w-7xl mx-auto space-y-6"
     x-data="permissionEditor({
        direct: @js($directPermissions),
        role: @js($rolePermissions),
        usingRoleDefaults: @js($usingRoleDefaults),
        modules: @js($modules)
     })">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Staff & Role Management</p>
            <h2 class="text-2xl font-bold text-gray-900 mt-1">Set Permissions</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $user->name }} · {{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3 text-sm font-semibold">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl px-5 py-4 text-sm">
        @if($usingRoleDefaults)
            <p class="font-bold">This user is currently using role-default permissions.</p>
            <p class="mt-1">Checked permissions below are coming from the {{ $user->role_label }} role. Saving this page will create a custom override for this user.</p>
        @else
            <p class="font-bold">This user is using custom permissions.</p>
            <p class="mt-1">Only the checked permissions apply for permission-protected routes. Use "Clear Custom Permissions" to return to role defaults.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <div class="flex flex-wrap gap-2">
                    @foreach($modules as $moduleKey => $module)
                    <button type="button"
                            @click="activeModule = '{{ $moduleKey }}'"
                            :class="activeModule === '{{ $moduleKey }}' ? 'bg-[#1a5632] text-white border-[#1a5632]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                            class="px-4 py-2 rounded-xl border text-sm font-bold transition-colors">
                        {{ $module['label'] }}
                    </button>
                    @endforeach
                </div>
            </div>

            @foreach($modules as $moduleKey => $module)
            <div x-show="activeModule === '{{ $moduleKey }}'" class="divide-y divide-gray-100">
                <div class="px-5 py-4 bg-gray-50/70 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-extrabold text-gray-900">{{ $module['label'] }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Choose access for each component in this module.</p>
                    </div>
                    <button type="button"
                            @click="toggleModule('{{ $moduleKey }}')"
                            class="px-3 py-2 text-xs font-bold rounded-lg border border-gray-200 text-gray-600 hover:bg-white transition-colors">
                        Full Module
                    </button>
                </div>

                @foreach($module['components'] as $componentKey => $component)
                <div class="px-5 py-4">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                        <div class="lg:w-64 min-w-0">
                            <p class="text-sm font-extrabold text-gray-900">{{ $component['label'] }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ count(array_unique($component['permissions'])) }} permission{{ count(array_unique($component['permissions'])) === 1 ? '' : 's' }}</p>
                        </div>

                        <div class="flex flex-wrap gap-2 flex-1">
                            <label class="cursor-pointer">
                                <input type="checkbox"
                                       class="sr-only"
                                       @change="toggleComponent('{{ $moduleKey }}', '{{ $componentKey }}', $event.target.checked)"
                                       :checked="componentFull('{{ $moduleKey }}', '{{ $componentKey }}')">
                                <span :class="componentFull('{{ $moduleKey }}', '{{ $componentKey }}') ? 'bg-[#1a5632] text-white border-[#1a5632]' : 'bg-white text-gray-600 border-gray-200'"
                                      class="inline-flex items-center px-3 py-2 rounded-lg border text-xs font-bold transition-colors">
                                    Full Access
                                </span>
                            </label>

                            @foreach($component['permissions'] as $action => $permission)
                            <label class="cursor-pointer">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $permission }}"
                                       class="sr-only"
                                       x-model="selected">
                                <span :class="selected.includes('{{ $permission }}') ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-white text-gray-600 border-gray-200'"
                                      class="inline-flex items-center px-3 py-2 rounded-lg border text-xs font-bold transition-colors">
                                    {{ $actionLabels[$action] ?? ucfirst($action) }}
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row justify-between gap-3">
            <button type="button"
                    @click="selected = []"
                    class="px-5 py-2.5 border border-gray-200 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-50 transition-colors">
                Clear Custom Permissions
            </button>
            <button type="submit"
                    class="px-6 py-2.5 bg-[#1a5632] hover:bg-[#0b2415] text-white text-sm font-extrabold rounded-xl transition-colors">
                Save Permissions
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function permissionEditor(config) {
    return {
        activeModule: Object.keys(config.modules)[0],
        modules: config.modules,
        selected: [...new Set(config.usingRoleDefaults ? config.role : config.direct)],
        componentPermissions(moduleKey, componentKey) {
            return Object.values(this.modules[moduleKey].components[componentKey].permissions);
        },
        componentFull(moduleKey, componentKey) {
            return this.componentPermissions(moduleKey, componentKey).every(permission => this.selected.includes(permission));
        },
        toggleComponent(moduleKey, componentKey, checked) {
            const permissions = this.componentPermissions(moduleKey, componentKey);
            if (checked) {
                this.selected = [...new Set([...this.selected, ...permissions])];
                return;
            }
            this.selected = this.selected.filter(permission => !permissions.includes(permission));
        },
        toggleModule(moduleKey) {
            const permissions = Object.values(this.modules[moduleKey].components).flatMap(component => Object.values(component.permissions));
            const full = permissions.every(permission => this.selected.includes(permission));
            this.selected = full
                ? this.selected.filter(permission => !permissions.includes(permission))
                : [...new Set([...this.selected, ...permissions])];
        },
    };
}
</script>
@endpush
