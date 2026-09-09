        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-[9px] font-black uppercase tracking-wider text-gray-400"><tr><th class="px-4 py-2.5">Symbol No.</th><th class="px-4 py-2.5">Roll No.</th><th class="px-4 py-2.5">Name</th><th class="px-4 py-2.5">Class / Stream</th><th class="px-4 py-2.5">Section</th><th class="px-4 py-2.5 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($students as $student)
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-4 py-2.5 font-black text-gray-900">{{ $symbolNumbers->get($student->id) ?? '—' }}</td>
                            <td class="px-4 py-2.5 font-semibold text-gray-600">{{ $student->roll_number ?? '—' }}</td>
                            <td class="px-4 py-2.5 font-bold text-gray-800">{{ $student->full_name }}</td>
                            <td class="px-4 py-2.5 font-semibold text-gray-500">{{ $student->school_class ? 'Class '.$student->school_class.' · ' : '' }}{{ $student->stream }}</td>
                            <td class="px-4 py-2.5 font-semibold text-gray-500">{{ $student->academicSection->name ?? $student->section ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right">
                                @if($symbolNumbers->has($student->id))
                                    <a target="_blank" rel="noopener" href="{{ route('admin.examinations.admit-cards.print-one', [$examination, $student]) }}" class="rounded-lg bg-[#1a5632] px-2.5 py-1.5 text-[10px] font-black text-white">Print</a>
                                    <a href="{{ route('admin.examinations.admit-cards.download-one', [$examination, $student]) }}" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-[10px] font-black text-gray-600 hover:border-[#1a5632]/30 hover:text-[#1a5632]">Download</a>
                                @else
                                    <span class="text-[10px] font-semibold text-gray-300">Assign symbol no. first</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm font-bold text-gray-400">No students match your search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
<div class="border-t p-4"><p class="mb-3 text-xs text-gray-500">{{ $students->total() }} matching students</p>{{ $students->links() }}</div>
