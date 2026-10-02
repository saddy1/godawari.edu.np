@if($query !== '')
    <div class="mt-3 divide-y divide-gray-50 border-t border-gray-100">
        @forelse($searchResults as $result)
            <a href="{{ route('admin.examinations.attendance.index', ['examination' => $examination, 'date' => $date->toDateString(), 'department_id' => $result->department->id, 'section_id' => $result->section->id, 'q' => $query]) }}" class="flex items-center justify-between gap-3 py-3 hover:bg-gray-50">
                <div>
                    <p class="font-extrabold text-gray-900">{{ $result->student->full_name }}</p>
                    <p class="text-xs font-semibold text-gray-400">{{ $result->student->roll_number ?: '—' }} · {{ $result->department->name }} · Section {{ $result->section->name }} · {{ $result->subject_name }}</p>
                </div>
                <span class="shrink-0 text-xs font-black text-[#1a5632]">Open section →</span>
            </a>
        @empty
            <p class="py-6 text-center text-sm text-gray-400">No student with an exam on this date matches "{{ $query }}".</p>
        @endforelse
    </div>
@endif
