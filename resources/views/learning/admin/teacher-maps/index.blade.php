@extends('learning.layouts.admin')

@section('title', 'Teacher Allocation')

@section('content')
@php
    $subjectsByClass = $classes->mapWithKeys(fn ($class) => [
        $class->id => $class->subjects
            ->where('is_active', true)
            ->map(fn ($subject) => ['id' => $subject->id, 'name' => $subject->name, 'code' => $subject->code])
            ->values(),
    ]);
    $allocationMap = $teachers->mapWithKeys(function ($teacher) {
        $byClass = $teacher->assignedLearningClasses->mapWithKeys(function ($class) use ($teacher) {
            $subjectIds = $teacher->assignedLearningSubjects
                ->where('learning_class_id', $class->id)
                ->pluck('id')
                ->values();
            return [$class->id => $subjectIds];
        });
        return [$teacher->id => $byClass];
    });
    $allocationCount = $teachers->sum(fn ($teacher) => $teacher->assignedLearningClasses->count());
@endphp

<div class="space-y-6"
     x-data="teacherAllocation(@js($subjectsByClass), @js($allocationMap), @js(url('/admin/learning/teacher-maps'))) ">
    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-[#e2a024]">E-Learning Access</p>
                <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">Teacher Allocation</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-white/70">
                    Allocate a teacher to a faculty or program and its subjects—for example, <span class="font-extrabold text-white">B.Sc. CSIT → C Programming</span>. Teachers can create courses only inside these allocations.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:w-72">
                <div class="rounded-xl border border-white/10 bg-white/10 px-4 py-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-white/50">Teachers</p>
                    <p class="mt-1 text-2xl font-black">{{ $teachers->count() }}</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/10 px-4 py-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-white/50">Allocations</p>
                    <p class="mt-1 text-2xl font-black">{{ $allocationCount }}</p>
                </div>
            </div>
        </div>
    </section>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <h2 class="text-lg font-extrabold text-gray-950">Assign Faculty and Subject</h2>
            <p class="mt-1 text-sm font-medium text-gray-500">Selecting an existing teacher and program loads its current subjects for editing.</p>
        </div>

        <form method="POST" :action="formAction" class="space-y-5 p-5 sm:p-6">
            @csrf
            @method('PATCH')

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label for="allocation-teacher" class="mb-1.5 block text-xs font-extrabold uppercase tracking-wider text-gray-500">Teacher</label>
                    <select id="allocation-teacher" x-model="teacherId" @change="loadExisting" required
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-bold focus:border-[#1a5632] focus:ring-[#1a5632]">
                        <option value="">Select teacher</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}{{ $teacher->email ? ' · '.$teacher->email : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="allocation-class" class="mb-1.5 block text-xs font-extrabold uppercase tracking-wider text-gray-500">Faculty / Program</label>
                    <select id="allocation-class" name="learning_class_id" x-model="classId" @change="loadExisting" required
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-bold focus:border-[#1a5632] focus:ring-[#1a5632]">
                        <option value="">Select faculty or program</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-gray-500">Subjects</label>
                    <span class="text-xs font-bold text-gray-400" x-show="classId"><span x-text="selectedSubjects.length"></span> selected</span>
                </div>

                <div x-show="!classId" class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm font-semibold text-gray-400">
                    Select a faculty or program to load its subjects.
                </div>
                <div x-show="classId && subjectOptions.length === 0" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm font-semibold text-amber-800">
                    No active subjects exist under this program. Add subjects first, then return to Teacher Allocation.
                </div>
                <div x-show="subjectOptions.length > 0" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <template x-for="subject in subjectOptions" :key="subject.id">
                        <label class="cursor-pointer">
                            <input type="checkbox" name="subject_ids[]" :value="subject.id" x-model.number="selectedSubjects" class="peer sr-only">
                            <span class="flex min-h-14 items-center gap-3 rounded-xl border-2 border-gray-200 bg-white px-3 py-2.5 transition peer-checked:border-[#1a5632] peer-checked:bg-green-50">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2"
                                      :class="selectedSubjects.includes(subject.id) ? 'border-[#1a5632] bg-[#1a5632] text-white' : 'border-gray-300 bg-white text-transparent'">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="min-w-0"><span class="block truncate text-sm font-extrabold text-gray-900" x-text="subject.name"></span><span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400" x-text="subject.code || 'Subject'"></span></span>
                            </span>
                        </label>
                    </template>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs font-medium text-gray-500">Saving again for the same teacher and program updates that program’s subject allocation.</p>
                <button type="submit" :disabled="!canSave"
                        class="rounded-xl bg-[#1a5632] px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#0b2415] disabled:cursor-not-allowed disabled:opacity-40">
                    Save Allocation
                </button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-gray-950">Current Allocations</h2>
                    <p class="mt-1 text-sm font-medium text-gray-500">Faculty and subject responsibility grouped by teacher.</p>
                </div>
                <input type="search" x-model.debounce.150ms="teacherSearch" placeholder="Search teacher or program..."
                       class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-bold focus:border-[#1a5632] focus:ring-[#1a5632] sm:w-80">
            </div>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($teachers as $teacher)
                @php
                    $teacherSearchText = strtolower($teacher->name.' '.$teacher->email.' '.$teacher->assignedLearningClasses->pluck('name')->implode(' ').' '.$teacher->assignedLearningSubjects->pluck('name')->implode(' '));
                @endphp
                <article class="p-5 sm:p-6" x-show="@js($teacherSearchText).includes(teacherSearch.toLowerCase())">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#1a5632] text-sm font-black text-white">{{ strtoupper(substr($teacher->name, 0, 1)) }}</div>
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-extrabold text-gray-950">{{ $teacher->name }}</h3>
                            <p class="truncate text-xs font-medium text-gray-400">{{ $teacher->email ?: 'Teacher account' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                        @forelse($teacher->assignedLearningClasses as $class)
                            @php $assignedSubjects = $teacher->assignedLearningSubjects->where('learning_class_id', $class->id); @endphp
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-extrabold uppercase tracking-widest text-[#1a5632]">Faculty / Program</p>
                                        <p class="mt-1 text-base font-extrabold text-gray-950">{{ $class->name }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1">
                                        <button type="button" @click="editAllocation({{ $teacher->id }}, {{ $class->id }})"
                                                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-extrabold text-[#1a5632] hover:bg-green-50">Edit</button>
                                        <form method="POST" action="{{ route('admin.learning.teacher-maps.destroy', [$teacher, $class]) }}" onsubmit="return confirm('Remove this faculty and subject allocation?')">
                                            @csrf @method('DELETE')
                                            <button class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-extrabold text-red-600 hover:bg-red-50">Remove</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @forelse($assignedSubjects as $subject)
                                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-extrabold text-gray-700 ring-1 ring-gray-200">{{ $subject->name }}</span>
                                    @empty
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-extrabold text-amber-800">No subject selected</span>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-200 px-4 py-5 text-sm font-semibold text-gray-400 lg:col-span-2">No faculty or subject allocated yet.</div>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="px-6 py-14 text-center"><p class="font-extrabold text-gray-800">No teacher accounts found.</p><p class="mt-1 text-sm text-gray-500">Create teachers in HR first.</p></div>
            @endforelse
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
function teacherAllocation(subjectsByClass, allocations, baseUrl) {
    return {
        teacherId: '',
        classId: '',
        selectedSubjects: [],
        teacherSearch: '',
        subjectsByClass,
        allocations,
        baseUrl,
        get subjectOptions() { return this.subjectsByClass[this.classId] || []; },
        get formAction() { return this.teacherId ? `${this.baseUrl}/${this.teacherId}/allocation` : '#'; },
        get canSave() { return this.teacherId && this.classId && this.selectedSubjects.length > 0; },
        loadExisting() {
            const saved = this.allocations[this.teacherId]?.[this.classId] || [];
            this.selectedSubjects = saved.map(Number);
        },
        editAllocation(teacherId, classId) {
            this.teacherId = String(teacherId);
            this.classId = String(classId);
            this.loadExisting();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    };
}
</script>
@endpush
