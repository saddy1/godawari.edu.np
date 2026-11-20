@extends('hr.layouts.app')
@section('title', 'Export Members')
@section('content')

<div class="space-y-6" x-data="{
        selected: new Set(),
        groups: @js($attributeGroups),
        init() {
            // Start with a sensible default tick-set instead of everything or nothing.
            ['full_name', 'member_type', 'roll_number', 'mobile', 'email'].forEach(k => this.selected.add(k));
        },
        toggle(key) {
            this.selected.has(key) ? this.selected.delete(key) : this.selected.add(key);
            this.selected = new Set(this.selected);
        },
        groupChecked(keys) {
            return keys.every(k => this.selected.has(k));
        },
        toggleGroup(keys) {
            const allChecked = this.groupChecked(keys);
            keys.forEach(k => allChecked ? this.selected.delete(k) : this.selected.add(k));
            this.selected = new Set(this.selected);
        },
        selectAll() {
            Object.values(this.groups).forEach(attrs => Object.keys(attrs).forEach(k => this.selected.add(k)));
            this.selected = new Set(this.selected);
        },
        clearAll() {
            this.selected.clear();
            this.selected = new Set(this.selected);
        },
    }">

    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 sm:p-6 text-white shadow-sm">
        <p class="text-sm font-bold uppercase tracking-widest text-white/50">Human Resource</p>
        <h1 class="mt-1 text-3xl font-extrabold">Export Members</h1>
        <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">
            Tick the attributes you want, and download a CSV of the matching members.
        </p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 space-y-1">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-sm font-semibold text-gray-700">
            <span class="font-black text-gray-950">{{ number_format($matchCount) }}</span>
            member{{ $matchCount === 1 ? '' : 's' }} will be exported with the current filters.
        </p>
        <a href="{{ route('admin.hr.members.index', $filters) }}" class="text-sm font-bold text-[#1a5632] hover:underline">Change filters</a>
    </div>

    <form method="POST" action="{{ route('admin.hr.members.export.download') }}" class="space-y-6">
        @csrf
        @foreach($filters as $key => $value)
            @if(is_array($value))
                @foreach($value as $v)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-bold text-gray-700" x-text="selected.size + ' attribute' + (selected.size === 1 ? '' : 's') + ' selected'"></p>
            <div class="flex gap-2">
                <button type="button" @click="selectAll()" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-600 hover:bg-gray-50">Select all</button>
                <button type="button" @click="clearAll()" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-600 hover:bg-gray-50">Clear</button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($attributeGroups as $groupName => $attrs)
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-extrabold uppercase tracking-widest text-gray-500">{{ $groupName }}</p>
                        <button type="button" @click="toggleGroup(@js(array_keys($attrs)))" class="text-[11px] font-bold text-[#1a5632] hover:underline">
                            <span x-text="groupChecked(@js(array_keys($attrs))) ? 'Unselect' : 'Select all'"></span>
                        </button>
                    </div>
                    <div class="mt-3 space-y-2">
                        @foreach($attrs as $key => $label)
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                <input type="checkbox" name="attributes[]" value="{{ $key }}"
                                       :checked="selected.has('{{ $key }}')" @change="toggle('{{ $key }}')"
                                       class="h-4 w-4 rounded border-gray-300 text-[#1a5632] focus:ring-[#1a5632]/30">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="sticky bottom-4 flex justify-end">
            <button type="submit" :disabled="selected.size === 0"
                    class="inline-flex items-center gap-2 rounded-xl bg-[#1a5632] px-6 py-3 text-sm font-bold text-white shadow-lg hover:bg-[#0b2415] disabled:cursor-not-allowed disabled:opacity-40">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-8-4V4m0 8l-3.5-3.5M12 12l3.5-3.5"/></svg>
                Download CSV
            </button>
        </div>
    </form>
</div>
@endsection
