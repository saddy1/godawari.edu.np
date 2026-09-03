@extends('hr.layouts.app')

@section('title', 'Promote Students')

@section('content')
<div class="space-y-6" x-data="promoteApp(@js($academicOptions))" x-init="init()">

    {{-- Header --}}
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 sm:p-6 text-white shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-widest text-white/50">Human Resource</p>
                <h1 class="mt-1 text-3xl font-extrabold">Student Academic Progression</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">
                    Move school students to the next class, BSc CSIT students to the next semester, and BBS students to the next study year.
                </p>
            </div>
            <a href="{{ route('admin.hr.members.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-extrabold text-white hover:bg-white/20">
                ← Back to Members
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
        @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[320px_1fr]">

        {{-- ── LEFT: Class / Section selector ────────────────────────────── --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400 mb-4">Filter Students</p>

                <div class="space-y-3 mb-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Organization</label>
                        <select x-model="organizationFilter" @change="departmentFilter = ''; sectionFilter = ''"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                            <option value="">All organizations</option>
                            <template x-for="organization in organizations" :key="organization.slug">
                                <option :value="organization.slug" x-text="organization.label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Class / Department</label>
                        <select x-model="departmentFilter" @change="sectionFilter = ''"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                            <option value="">All classes</option>
                            <option x-show="hasUnassignedDepartment" value="__none__">Class not assigned</option>
                            <template x-for="department in departments" :key="department">
                                <option :value="department" x-text="department"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Section</label>
                        <select x-model="sectionFilter"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                            <option value="">All sections</option>
                            <option x-show="hasUnassignedSection" value="__none__">No section assigned</option>
                            <template x-for="section in sections" :key="section">
                                <option :value="section" x-text="section"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Search within list --}}
                <input type="text" x-model="groupSearch" placeholder="Search class..."
                       class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold mb-3 focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">

                <div class="space-y-1 max-h-80 overflow-y-auto -mx-1 px-1">
                    @forelse($groups as $g)
                    <button type="button"
                            x-show="groupVisible(@js($g))"
                            @click="selectGroup(@js($g))"
                            :class="selected && selected.organization === @js($g['organization']) && selected.program === @js($g['program']) && selected.stream === @js($g['stream']) && selected.section === @js($g['section']) && selected.semester === @js($g['semester']) && selected.year_level === @js($g['year_level'])
                                ? 'bg-[#1a5632] text-white border-[#1a5632]'
                                : 'bg-white text-gray-700 border-gray-200 hover:border-[#1a5632] hover:bg-emerald-50'"
                            class="w-full flex items-center justify-between rounded-xl border px-3.5 py-3 text-left transition-all">
                        <div>
                            <p class="mb-1 text-[10px] font-bold uppercase tracking-wider opacity-60">{{ $g['organization_label'] }}</p>
                            <p class="text-sm font-extrabold leading-tight">{{ $g['class_name'] }}</p>
                            <p class="text-xs mt-0.5 opacity-70">
                                @if($g['academic_system'] === 'semester')
                                    {{ $g['semester'] ? 'Semester '.$g['semester'] : 'Semester not assigned' }}
                                @elseif($g['academic_system'] === 'year')
                                    {{ $g['year_level'] ? 'Year '.$g['year_level'] : 'Study year not assigned' }}
                                @else
                                    {{ $g['section'] ? 'Section '.$g['section'] : 'Section not assigned' }}
                                @endif
                            </p>
                        </div>
                        <div class="text-right shrink-0 ml-3">
                            <span class="text-lg font-black leading-none">{{ $g['count'] }}</span>
                            <p class="text-[10px] font-bold opacity-60 leading-none mt-0.5">students</p>
                        </div>
                    </button>
                    @empty
                    <p class="text-sm text-gray-400 text-center py-4">No student classes found.</p>
                    @endforelse
                </div>
            </div>

            {{-- How it works --}}
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-xs text-blue-800 space-y-1.5">
                <p class="font-extrabold text-blue-900">How promotion works</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>The department setting decides whether students use class, semester, or year progression.</li>
                    <li>School classes move to the next configured class and section.</li>
                    <li>Semester and year programs keep their department and section while advancing one level.</li>
                    <li>The final class, semester, or year defaults to <strong>Graduate</strong>.</li>
                </ul>
            </div>
        </div>

        {{-- ── RIGHT: Student list + promotion form ────────────────────── --}}
        <div>
            {{-- Placeholder when nothing selected --}}
            <div x-show="!selected" class="rounded-2xl border-2 border-dashed border-gray-200 bg-white p-16 text-center">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <p class="font-extrabold text-gray-400">Select a class on the left</p>
                <p class="text-sm text-gray-300 mt-1">Students will appear here for review</p>
            </div>

            {{-- Promotion panel --}}
            <div x-show="selected" x-cloak class="space-y-4">

                {{-- Selected class header --}}
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-widest text-emerald-600">Selected Student Group</p>
                        <p class="text-xl font-black text-emerald-900 mt-0.5">
                            <span x-text="selected?.class_name ?? '(No class)'"></span>
                            <template x-if="selected?.section">
                                <span class="text-emerald-600"> / Section <span x-text="selected.section"></span></span>
                            </template>
                        </p>
                        <p class="mt-1 text-xs font-bold text-emerald-700">
                            <span x-text="selected?.organization_label"></span>
                            <span class="mx-1">·</span>
                            <span x-text="currentLevelLabel"></span>
                            <template x-if="selected?.section">
                                <span> · Section <span x-text="selected.section"></span></span>
                            </template>
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-3xl font-black text-emerald-700" x-text="totalStudents"></span>
                        <p class="text-xs font-bold text-emerald-600">students</p>
                    </div>
                </div>

                {{-- Student search --}}
                <div class="relative">
                    <input type="text" x-model="studentSearch" @input.debounce.300ms="loadStudents()"
                           placeholder="Search student name or roll number..."
                           class="w-full rounded-xl border border-gray-300 pl-10 pr-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    <svg class="absolute left-3.5 top-3.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <div x-show="loadingStudents" class="absolute right-3.5 top-3.5">
                        <svg class="w-4 h-4 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                    </div>
                </div>

                {{-- Students list --}}
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 bg-gray-50">
                        <p class="text-xs font-extrabold uppercase tracking-widest text-gray-500">Students</p>
                        <div class="flex gap-3 text-xs font-extrabold">
                            <button type="button" @click="checkAll(true)" class="text-[#1a5632] hover:underline">Select All</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="checkAll(false)" class="text-gray-500 hover:underline">Deselect All</button>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
                        <template x-if="students.length === 0 && !loadingStudents">
                            <p class="px-5 py-8 text-center text-sm text-gray-400 font-medium">No students found.</p>
                        </template>
                        <template x-for="s in students" :key="s.id">
                            <label class="flex items-center gap-3 px-5 py-3 cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="checkbox" :value="s.id" x-model="checkedIds"
                                       class="rounded accent-[#1a5632] w-4 h-4 shrink-0">
                                <img :src="s.photo_url" class="w-9 h-9 rounded-full object-cover ring-1 ring-gray-200 shrink-0">
                                <div class="min-w-0 flex-1">
                                    <p class="font-extrabold text-gray-900 text-sm truncate" x-text="s.name"></p>
                                    <p class="text-xs text-gray-500 font-medium" x-text="'Roll: ' + s.roll_number"></p>
                                </div>
                            </label>
                        </template>
                    </div>
                    <div class="px-5 py-2.5 border-t border-gray-100 bg-gray-50 text-xs font-semibold text-gray-400">
                        <span x-text="checkedIds.length"></span> of <span x-text="students.length"></span> selected
                    </div>
                </div>

                {{-- Promotion form --}}
                <form method="POST" action="{{ route('admin.hr.members.promote.apply') }}"
                      @submit.prevent="submitPromotion()">
                    @csrf
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5 space-y-4">
                        <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Progression Settings</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div x-show="selected?.academic_system === 'none'">
                                <label class="block text-xs font-extrabold uppercase tracking-widest text-gray-500 mb-1.5">Promote To (Class)</label>
                                <select x-model="toClass"
                                        @change="selectDefaultTargetSection()"
                                        :disabled="action === 'graduate' || availableClasses.length === 0"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-50 disabled:text-gray-400">
                                    <option value="" disabled x-text="availableClasses.length ? 'Select target class' : 'No next class available'"></option>
                                    <template x-for="className in availableClasses" :key="className">
                                        <option :value="className" x-text="className"></option>
                                    </template>
                                </select>
                                <p x-show="selected && action === 'promote' && availableClasses.length === 0"
                                   class="mt-1.5 text-xs font-semibold text-amber-600">
                                    No higher class could be identified for this group.
                                </p>
                            </div>
                            <div x-show="selected?.academic_system === 'none'">
                                <label class="block text-xs font-extrabold uppercase tracking-widest text-gray-500 mb-1.5">Target Section</label>
                                <select x-model="toSectionId" :disabled="action === 'graduate' || !toClass"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-50 disabled:text-gray-400">
                                    <option value="" x-text="toClass ? (availableTargetSections.length ? 'Select target section' : 'No sections created') : 'Select target class first'"></option>
                                    <template x-for="section in availableTargetSections" :key="section.id">
                                        <option :value="String(section.id)" x-text="sectionLabel(section)"></option>
                                    </template>
                                </select>
                                <p x-show="toClass && availableTargetSections.length === 0" class="mt-1.5 text-xs font-semibold text-amber-600">Create an active section for this class in Student Settings.</p>
                            </div>
                            <div x-show="selected?.academic_system === 'semester'">
                                <label class="block text-xs font-extrabold uppercase tracking-widest text-gray-500 mb-1.5" x-text="selected?.semester ? 'Move To Semester' : 'Assign Semester'"></label>
                                <select x-model="toSemester" :disabled="action === 'graduate'"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-50 disabled:text-gray-400">
                                    <option value="" disabled>No next semester available</option>
                                    <template x-for="semester in availableSemesters" :key="semester">
                                        <option :value="String(semester)" x-text="'Semester ' + semester"></option>
                                    </template>
                                </select>
                                <p class="mt-1.5 text-xs font-semibold text-gray-400">The department and section remain unchanged.</p>
                            </div>
                            <div x-show="selected?.academic_system === 'year'">
                                <label class="block text-xs font-extrabold uppercase tracking-widest text-gray-500 mb-1.5" x-text="selected?.year_level ? 'Move To Study Year' : 'Assign Study Year'"></label>
                                <select x-model="toYearLevel" :disabled="action === 'graduate'"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-50 disabled:text-gray-400">
                                    <option value="" disabled>No next study year available</option>
                                    <template x-for="year in availableYears" :key="year">
                                        <option :value="String(year)" x-text="ordinal(year) + ' year'"></option>
                                    </template>
                                </select>
                                <p class="mt-1.5 text-xs font-semibold text-gray-400">The department and section remain unchanged.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-widest text-gray-500 mb-1.5">Action</label>
                                <select x-model="action" @change="syncProgressionTarget()"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                                    <template x-for="option in actionOptions" :key="option.value">
                                        <option :value="option.value" x-text="option.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-widest text-gray-500 mb-1.5">New Valid Till (optional)</label>
                                <input type="date" x-model="validTill"
                                       class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                            </div>
                        </div>

                        <div x-show="action === 'graduate'" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-800">
                            The student record will be kept and its class will be marked as Graduated.
                        </div>
                        <div x-show="action !== 'graduate' && selected?.academic_system === 'semester' && !selected?.semester"
                             class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm font-semibold text-blue-800">
                            These existing students have no semester recorded. Choose their correct semester now; future progression will allow only the next semester.
                        </div>
                        <div x-show="action !== 'graduate' && selected?.academic_system === 'year' && !selected?.year_level"
                             class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm font-semibold text-blue-800">
                            These existing students have no study year recorded. Choose their correct year now; future progression will allow only the next year.
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center gap-3 pt-1">
                            <button type="submit"
                                    :disabled="!canSubmitProgression"
                                    class="rounded-xl bg-[#1a5632] px-6 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415] disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                Apply to <span x-text="checkedIds.length"></span> student<span x-show="checkedIds.length !== 1">s</span>
                            </button>
                            <p x-show="checkedIds.length === 0" class="text-xs text-amber-600 font-semibold">Select at least one student above.</p>
                            <p x-show="checkedIds.length > 0 && action === 'promote' && !toClass" class="text-xs text-amber-600 font-semibold">Choose a target class.</p>
                            <p x-show="checkedIds.length > 0 && action === 'promote' && toClass && requiresTargetSection && !toSectionId" class="text-xs text-amber-600 font-semibold">Choose a target section.</p>
                            <p x-show="checkedIds.length > 0 && action === 'advance_semester' && !toSemester" class="text-xs text-amber-600 font-semibold">No next semester is available; graduate this group instead.</p>
                            <p x-show="checkedIds.length > 0 && action === 'advance_year' && !toYearLevel" class="text-xs text-amber-600 font-semibold">No next study year is available; graduate this group instead.</p>
                        </div>
                    </div>

                    {{-- Hidden fields built dynamically --}}
                    <div id="hidden-fields"></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function promoteApp(academicOptions) {
    return {
        academicOptions,
        selected:       null,
        groupSearch:    '',
        organizationFilter: '',
        departmentFilter: '',
        sectionFilter:  '',
        studentSearch:  '',
        students:       [],
        totalStudents:  0,
        checkedIds:     [],
        loadingStudents: false,

        // Promotion settings
        toClass:    '',
        toSectionId: '',
        toSemester: '',
        toYearLevel: '',
        action:     'promote',
        validTill:  '',
        gradAction: 'mark',
        allGroups:   @js($groups),

        init() {},

        get organizations() {
            return Object.entries(this.academicOptions || {})
                .map(([slug, organization]) => ({ slug, label: organization.label }))
                .sort((left, right) => left.label.localeCompare(right.label));
        },

        get departments() {
            return [...new Set(this.allGroups
                .filter(group => !this.organizationFilter || group.organization === this.organizationFilter)
                .map(group => group.stream).filter(Boolean))]
                .sort((left, right) => left.localeCompare(right, undefined, { numeric: true }));
        },

        get hasUnassignedDepartment() {
            return this.allGroups
                .filter(group => !this.organizationFilter || group.organization === this.organizationFilter)
                .some(group => !group.stream);
        },

        get sections() {
            return [...new Set(this.allGroups
                .filter(group => !this.organizationFilter || group.organization === this.organizationFilter)
                .filter(group => !this.departmentFilter
                    || (this.departmentFilter === '__none__' ? !group.stream : group.stream === this.departmentFilter))
                .map(group => group.section)
                .filter(Boolean))]
                .sort((left, right) => left.localeCompare(right, undefined, { numeric: true }));
        },

        get hasUnassignedSection() {
            return this.allGroups
                .filter(group => !this.organizationFilter || group.organization === this.organizationFilter)
                .filter(group => !this.departmentFilter
                    || (this.departmentFilter === '__none__' ? !group.stream : group.stream === this.departmentFilter))
                .some(group => !group.section);
        },

        get availableClasses() {
            return this.selected?.target_classes ?? [];
        },

        get availableTargetSections() {
            if (!this.selected?.organization || !this.toClass) return [];
            return this.academicOptions?.[this.selected.organization]?.streams?.[this.toClass] || [];
        },

        get requiresTargetSection() {
            return this.availableTargetSections.length > 0;
        },

        get availableSemesters() {
            if (this.selected?.academic_system !== 'semester') return [];
            if (!this.selected.semester) return Array.from({ length: 8 }, (_, index) => index + 1);
            const next = Number(this.selected.semester || 0) + 1;
            return next >= 1 && next <= 8 ? [next] : [];
        },

        get availableYears() {
            if (this.selected?.academic_system !== 'year') return [];
            if (!this.selected.year_level) return Array.from({ length: 4 }, (_, index) => index + 1);
            const next = Number(this.selected.year_level || 0) + 1;
            return next >= 1 && next <= 4 ? [next] : [];
        },

        get currentLevelLabel() {
            if (this.selected?.academic_system === 'semester') {
                return this.selected.semester ? `Semester ${this.selected.semester}` : 'Semester not assigned';
            }
            if (this.selected?.academic_system === 'year') {
                return this.selected.year_level ? `${this.ordinal(this.selected.year_level)} year` : 'Study year not assigned';
            }
            return 'Class system';
        },

        get canSubmitProgression() {
            if (this.checkedIds.length === 0) return false;
            if (this.action === 'graduate') return true;
            if (this.action === 'advance_semester') return Boolean(this.toSemester);
            if (this.action === 'advance_year') return Boolean(this.toYearLevel);
            return Boolean(this.toClass) && (!this.requiresTargetSection || Boolean(this.toSectionId));
        },

        get actionOptions() {
            const progression = this.selected?.academic_system === 'semester'
                ? { value: 'advance_semester', label: this.selected?.semester ? 'Advance to next semester' : 'Assign semester' }
                : (this.selected?.academic_system === 'year'
                    ? { value: 'advance_year', label: this.selected?.year_level ? 'Advance to next study year' : 'Assign study year' }
                    : { value: 'promote', label: 'Promote to next class' });
            return [progression, { value: 'graduate', label: 'Mark as Graduated' }];
        },

        ordinal(number) {
            const value = Number(number);
            if (value === 1) return '1st';
            if (value === 2) return '2nd';
            if (value === 3) return '3rd';
            return `${value}th`;
        },

        sectionLabel(section) {
            return section.group ? `${section.name} · ${section.group}` : section.name;
        },

        groupVisible(group) {
            const matchesOrganization = !this.organizationFilter || group.organization === this.organizationFilter;
            const matchesDepartment = !this.departmentFilter
                || (this.departmentFilter === '__none__' ? !group.stream : group.stream === this.departmentFilter);
            const matchesSection = !this.sectionFilter
                || (this.sectionFilter === '__none__' ? !group.section : group.section === this.sectionFilter);
            const matchesSearch = !this.groupSearch
                || (group.label ?? '').toLowerCase().includes(this.groupSearch.toLowerCase());

            return matchesOrganization && matchesDepartment && matchesSection && matchesSearch;
        },

        selectGroup(g) {
            this.selected      = g;
            this.toClass       = this.resolveTargetClass(g);
            this.toSectionId   = '';
            this.toSemester    = '';
            this.toYearLevel   = '';
            this.action        = g.is_grad_year
                ? 'graduate'
                : (g.academic_system === 'semester' ? 'advance_semester' : (g.academic_system === 'year' ? 'advance_year' : 'promote'));
            this.syncProgressionTarget();
            this.selectDefaultTargetSection();
            this.studentSearch = '';
            this.checkedIds    = [];
            this.loadStudents();
        },

        syncProgressionTarget() {
            if (this.action === 'advance_semester') this.toSemester = String(this.availableSemesters[0] ?? '');
            if (this.action === 'advance_year') this.toYearLevel = String(this.availableYears[0] ?? '');
        },

        resolveTargetClass(g) {
            const options = g.target_classes ?? [];
            if (g.suggested_class && g.suggested_class !== g.class_name && options.includes(g.suggested_class)) {
                return g.suggested_class;
            }

            return '';
        },

        selectDefaultTargetSection() {
            this.toSectionId = '';
            if (!this.selected?.section) return;
            const matchingSection = this.availableTargetSections.find(section => section.name === this.selected.section);
            if (matchingSection) this.toSectionId = String(matchingSection.id);
        },

        async loadStudents() {
            if (!this.selected) return;
            this.loadingStudents = true;
            const params = new URLSearchParams({
                organization: this.selected.organization ?? '',
                program: this.selected.program ?? '',
                stream:  this.selected.stream  ?? '',
                section: this.selected.section ?? '',
                semester: this.selected.semester ?? '',
                year_level: this.selected.year_level ?? '',
                q:       this.studentSearch,
            });
            try {
                const res  = await fetch(`{{ route('admin.hr.members.promote.students') }}?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.students      = data.students || [];
                this.totalStudents = this.students.length;
                this.checkedIds    = this.students.map(s => s.id);
            } catch { this.students = []; }
            finally { this.loadingStudents = false; }
        },

        checkAll(checked) {
            this.checkedIds = checked ? this.students.map(s => s.id) : [];
        },

        submitPromotion() {
            if (this.checkedIds.length === 0) return;
            if (this.action === 'promote' && !this.toClass) return;
            if (this.action === 'promote' && this.requiresTargetSection && !this.toSectionId) return;
            if (this.action === 'advance_semester' && !this.toSemester) return;
            if (this.action === 'advance_year' && !this.toYearLevel) return;
            if (!confirm(`Apply ${this.action} to ${this.checkedIds.length} student(s)? This cannot be undone.`)) return;

            // Build hidden fields dynamically
            const container = document.getElementById('hidden-fields');
            container.innerHTML = '';
            const add = (name, value) => {
                const i = document.createElement('input');
                i.type = 'hidden'; i.name = name; i.value = value ?? '';
                container.appendChild(i);
            };

            // Submit as a single group for the checked students
            // We piggyback on the existing groups[] structure
            add('groups[0][from_program]', this.selected.program ?? '');
            add('groups[0][from_organization]', this.selected.organization ?? '');
            add('groups[0][from_stream]',  this.selected.stream  ?? '');
            add('groups[0][from_section]', this.selected.section ?? '');
            add('groups[0][from_semester]', this.selected.semester ?? '');
            add('groups[0][from_year_level]', this.selected.year_level ?? '');
            add('groups[0][to_program]',   this.toClass);
            const targetSection = this.availableTargetSections.find(section => String(section.id) === String(this.toSectionId));
            add('groups[0][to_section]',   targetSection?.name ?? '');
            add('groups[0][to_section_id]', this.toSectionId || '');
            add('groups[0][to_semester]', this.toSemester || '');
            add('groups[0][to_year_level]', this.toYearLevel || '');
            add('groups[0][academic_system]', this.selected.academic_system || 'none');
            add('groups[0][action]',       this.action);
            add('grad_action',             this.gradAction);
            if (this.validTill) add('valid_till', this.validTill);

            // Also pass the selected student IDs so controller can scope to them
            this.checkedIds.forEach(id => add('student_ids[]', id));

            container.closest('form').submit();
        },
    };
}
</script>
@endpush
