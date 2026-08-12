@php
    $typeLabels = ['student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff'];
    $typeStyles = [
        'student' => 'bg-blue-50 text-blue-700 border-blue-100',
        'teacher' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'staff' => 'bg-amber-50 text-amber-700 border-amber-100',
    ];
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-20 px-5 py-3 text-left">
                        <label for="bulk-select-all" class="inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-widest text-gray-500">
                            <span class="relative inline-flex h-5 w-5 shrink-0 items-center justify-center">
                                <input type="checkbox" id="bulk-select-all" class="peer sr-only">
                                <span class="absolute inset-0 rounded-md border-2 border-gray-400 bg-white shadow-sm transition peer-checked:border-[#1a5632] peer-checked:bg-[#1a5632] peer-focus:ring-2 peer-focus:ring-[#1a5632]/30"></span>
                                <span class="relative hidden text-[13px] font-black leading-none text-white peer-checked:block">&#10003;</span>
                            </span>
                            Select
                        </label>
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-widest text-gray-500">Member</th>
                    <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-widest text-gray-500">Type</th>
                    <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-widest text-gray-500">Class / Section</th>
                    <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-widest text-gray-500">Linked Modules</th>
                    <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-widest text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($members as $member)
                    <tr class="hover:bg-gray-50">
                        <td class="w-20 px-5 py-4">
                            <label class="inline-flex items-center">
                                <span class="relative inline-flex h-5 w-5 shrink-0 items-center justify-center">
                                    <input type="checkbox" name="ids[]" value="{{ $member->id }}" class="bulk-row-check peer sr-only"
                                           @disabled($member->library_clearance_hold)
                                           title="{{ $member->library_clearance_hold ? $member->library_clearance_message : 'Select member' }}">
                                    <span class="absolute inset-0 rounded-md border-2 bg-white shadow-sm transition peer-checked:border-[#1a5632] peer-checked:bg-[#1a5632] peer-focus:ring-2 peer-focus:ring-[#1a5632]/30 {{ $member->library_clearance_hold ? 'border-gray-200 bg-gray-100 cursor-not-allowed' : 'border-gray-400' }}"></span>
                                    <span class="relative hidden text-[13px] font-black leading-none text-white peer-checked:block">&#10003;</span>
                                </span>
                                <span class="sr-only">Select {{ $member->full_name }}</span>
                            </label>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="h-11 w-11 rounded-full object-cover ring-1 ring-gray-200">
                                <div>
                                    <p class="font-extrabold text-gray-950" data-highlight>{{ $member->full_name }}</p>
                                    <p class="text-sm font-medium text-gray-500" data-highlight>{{ $member->roll_number }} · {{ $member->email ?: 'No email' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-full border px-3 py-1 text-xs font-extrabold {{ $typeStyles[$member->member_type] ?? 'bg-gray-50 text-gray-600 border-gray-100' }}" data-highlight>
                                {{ $typeLabels[$member->member_type] ?? ucfirst($member->member_type) }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-700" data-highlight>
                            {{ $member->stream ?: '-' }}
                            <span class="text-gray-400">/</span>
                            {{ $member->section ?: '-' }}
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">ID Card</span>
                                @if($member->user)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Login</span>
                                    @if($member->user->device_id)
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">Hajiri</span>
                                    @endif
                                    @if($member->user->hasAnyRole(['student', 'teacher']))
                                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">Learning</span>
                                    @endif
                                @else
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-500">No Login</span>
                                @endif
                                @if($member->library_clearance_hold)
                                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700" title="{{ $member->library_clearance_message }}">
                                        Library clearance pending
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.hr.members.show', $member) }}" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-extrabold text-blue-700 hover:bg-blue-100">View</a>
                                @if(auth()->user()?->canAccess('hr.members.edit'))
                                    <a href="{{ route('admin.hr.members.edit', $member) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-extrabold text-gray-700 hover:bg-gray-50">Edit</a>
                                @endif
                                @if(auth()->user()?->canAccess('hr.members.delete'))
                                    @if($member->library_clearance_hold)
                                        <button type="button" disabled title="{{ $member->library_clearance_message }}"
                                                class="cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-xs font-extrabold text-gray-400">
                                            Clearance Pending
                                        </button>
                                    @else
                                        <form method="POST" action="{{ route('admin.hr.members.destroy', $member) }}"
                                              class="hr-member-delete-form"
                                              data-member-name="{{ $member->full_name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-extrabold text-red-600 hover:bg-red-100">Delete</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <p class="font-extrabold text-gray-900">No HR members found.</p>
                            <p class="mt-1 text-sm text-gray-500">Change the search or filter and try again.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-100 px-5 py-4">{{ $members->links() }}</div>
</div>
