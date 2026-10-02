@extends('examinations.layouts.app')
@section('title', 'Exam Attendance — '.$examination->name)
@section('content')
<div class="space-y-4">
    <section class="flex flex-col gap-3 rounded-2xl bg-gradient-to-r from-[#0b2415] to-[#1a5632] p-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-300">{{ $examination->organization->name }}</p>
            <h1 class="mt-1 text-2xl font-black">Exam Attendance — {{ $examination->name }}</h1>
            <p class="mt-1 text-xs font-semibold text-white/65">Everyone defaults to present — just mark who's absent.</p>
        </div>
        <a href="{{ route('admin.examinations.show', $examination) }}" class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-black text-white hover:bg-white/20">← Back to exam</a>
    </section>

    @if(session('success'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700">@foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach</div>@endif

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.examinations.attendance.index', $examination) }}" class="grid gap-3 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500">Exam date</label>
                <x-nepali-date-input name="date" :value="$date" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15" />
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500">Faculty / class with exam this date</label>
                <select name="department_id" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15">
                    <option value="">{{ $departments->isEmpty() ? 'No exams scheduled this date' : 'Choose faculty / class' }}</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected($selectedDepartment?->id === $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[10px] font-black uppercase tracking-wider text-gray-500">Section</label>
                <select name="section_id" onchange="this.form.submit()" @disabled($sections->isEmpty()) class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm font-semibold outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/15 disabled:bg-gray-50 disabled:text-gray-400">
                    <option value="">{{ $selectedDepartment ? 'Choose section' : 'Choose faculty first' }}</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec->id }}" @selected($selectedSection?->id === $sec->id)>{{ $sec->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </section>

    @if($selectedDepartment && $selectedSection)
        @if($roster->isEmpty())
            <div class="rounded-2xl border border-dashed bg-white p-10 text-center text-sm text-gray-400">No students in this section have an exam scheduled on this date.</div>
        @else
            <section x-data="examAttendance(@js($roster->map(fn ($row) => ['id' => $row->student->id, 'status' => $row->status, 'reason' => $row->reason])->values()))" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <form method="POST" action="{{ route('admin.examinations.attendance.store', $examination) }}" @submit="saving = true">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                    <input type="hidden" name="department_id" value="{{ $selectedDepartment->id }}">
                    <input type="hidden" name="section_id" value="{{ $selectedSection->id }}">
                    <template x-for="id in absentIds" :key="'absent-'+id"><input type="hidden" name="absent_student_ids[]" :value="id"></template>
                    <template x-for="id in absentIds" :key="'reason-'+id"><input type="hidden" :name="`reasons[${id}]`" :value="reasonFor(id)"></template>

                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-[#1a5632]">{{ $selectedDepartment->name }} · Section {{ $selectedSection->name }}</p>
                            <h2 class="text-sm font-black text-gray-900">{{ $roster->count() }} student{{ $roster->count() === 1 ? '' : 's' }} with an exam on {{ $date->format('d M Y') }}</h2>
                        </div>
                        <p class="text-xs font-bold text-gray-400"><span x-text="absentIds.length"></span> marked absent</p>
                    </header>

                    <div class="divide-y divide-gray-50">
                        @foreach($roster as $row)
                            <div class="flex flex-wrap items-center gap-3 p-4 sm:p-5">
                                <div class="min-w-0 flex-1">
                                    <p class="font-extrabold text-gray-900">{{ $row->student->full_name }}</p>
                                    <p class="text-xs font-semibold text-gray-400">{{ $row->student->roll_number ?: '—' }} · {{ $row->subject_name }}</p>
                                </div>
                                <input type="text" x-show="isAbsent({{ $row->student->id }})" x-cloak
                                       :value="reasonFor({{ $row->student->id }})" @input="setReason({{ $row->student->id }}, $event.target.value)"
                                       placeholder="Reason (optional)" maxlength="255"
                                       class="w-48 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900 outline-none focus:border-amber-500">
                                <button type="button" @click="toggle({{ $row->student->id }})"
                                        :class="isAbsent({{ $row->student->id }}) ? 'border-red-600 bg-red-600 text-white' : 'border-gray-200 bg-white text-gray-500 hover:border-red-300 hover:text-red-600'"
                                        class="shrink-0 rounded-lg border px-4 py-2 text-xs font-black transition">
                                    <span x-text="isAbsent({{ $row->student->id }}) ? 'Absent' : 'Mark Absent'"></span>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <footer class="flex items-center justify-between border-t border-gray-100 bg-gray-50/60 px-5 py-4">
                        <p class="text-[10px] font-semibold text-gray-400">Everyone not marked absent is saved as present.</p>
                        <button :disabled="saving" class="rounded-xl bg-[#1a5632] px-5 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415] disabled:opacity-50">
                            <span x-show="!saving">Save Attendance</span>
                            <span x-show="saving" x-cloak>Saving…</span>
                        </button>
                    </footer>
                </form>
            </section>
        @endif
    @endif
</div>
@endsection
@push('scripts')
<script>
function examAttendance(initial) {
    return {
        saving: false,
        statuses: Object.fromEntries((initial || []).map(row => [row.id, { absent: row.status === 'absent', reason: row.reason || '' }])),
        get absentIds() { return Object.keys(this.statuses).filter(id => this.statuses[id].absent).map(Number) },
        isAbsent(id) { return !!this.statuses[id]?.absent },
        reasonFor(id) { return this.statuses[id]?.reason || '' },
        setReason(id, value) { if (!this.statuses[id]) this.statuses[id] = { absent: false, reason: '' }; this.statuses[id].reason = value },
        toggle(id) {
            if (!this.statuses[id]) this.statuses[id] = { absent: false, reason: '' };
            this.statuses[id].absent = !this.statuses[id].absent;
            if (!this.statuses[id].absent) this.statuses[id].reason = '';
        },
    }
}
</script>
@endpush
