@php
    $compactInputClass = 'w-full sm:w-48 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Student</th>
                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">User ID</th>
                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Class</th>
                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-gray-500">Reset Password</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($students as $student)
                <tr>
                    <td class="px-5 py-4">
                        <p class="font-extrabold text-gray-900">{{ $student->name }}</p>
                        <p class="text-sm text-gray-500">{{ $student->email }}</p>
                    </td>
                    <td class="px-5 py-4 text-sm font-bold text-gray-700">{{ $student->student_code }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $student->class_grade ?? '-' }}{{ $student->section ? ' · '.$student->section : '' }}</td>
                    <td class="px-5 py-4">
                        @if(auth()->user()?->canAccess('learning.students.edit'))
                            <form method="POST" action="{{ route('admin.learning.students.password', $student) }}" class="flex flex-col sm:flex-row gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="password" name="password" placeholder="New password" required class="{{ $compactInputClass }}">
                                <input type="password" name="password_confirmation" placeholder="Confirm" required class="{{ $compactInputClass }}">
                                <button class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-extrabold text-gray-700 shadow-sm hover:bg-gray-50">Reset</button>
                            </form>
                        @else
                            <span class="text-sm text-gray-400">No access</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-5 py-10 text-center text-gray-500">No student accounts found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="px-5 py-4 border-t border-gray-100">{{ $students->links() }}</div>
