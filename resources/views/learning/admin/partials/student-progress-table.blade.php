@php
    $progressColor = $progressColor ?? function (int $value): string {
        if ($value >= 80) return 'bg-emerald-500';
        if ($value >= 40) return 'bg-amber-500';
        return 'bg-rose-500';
    };
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Student</th>
                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Class</th>
                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Courses</th>
                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Progress</th>
                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Last Activity</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($studentRows as $row)
                @php($student = $row['student'])
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-4">
                        <p class="font-extrabold text-gray-950">{{ $student->name }}</p>
                        <p class="text-xs font-semibold text-gray-500">{{ $student->student_code ?: $student->email }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-sm font-bold text-gray-800">{{ $student->class_grade ?: '-' }}</p>
                        <p class="text-xs text-gray-400">{{ $student->section ?: 'No section' }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-sm font-bold text-gray-800">{{ $row['started'] }} / {{ $row['course_count'] }}</p>
                        <p class="text-xs text-gray-400">{{ $row['completed'] }} completed</p>
                    </td>
                    <td class="px-5 py-4 min-w-[190px]">
                        <div class="flex items-center gap-3">
                            <div class="h-2 flex-1 rounded-full bg-gray-100">
                                <div class="h-2 rounded-full {{ $progressColor($row['avg_progress']) }}" style="width: {{ min(100, $row['avg_progress']) }}%"></div>
                            </div>
                            <span class="w-10 text-right text-sm font-extrabold text-gray-700">{{ $row['avg_progress'] }}%</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm font-semibold text-gray-500">
                        {{ $row['last_activity'] ? $row['last_activity']->diffForHumans() : 'Not started' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-10 text-center text-sm font-semibold text-gray-400">
                        No students found in this dashboard scope.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-xs font-bold text-gray-400">
        Showing {{ $studentRows->firstItem() ?? 0 }}-{{ $studentRows->lastItem() ?? 0 }} of {{ $studentRows->total() }} students
    </p>
    <div>
        {{ $studentRows->links() }}
    </div>
</div>
