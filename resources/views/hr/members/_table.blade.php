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
                    <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-widest text-gray-500">Gender</th>
                    <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-widest text-gray-500">Class / Section</th>
                    <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-widest text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($members as $member)
                    @php
                        $memberElectiveGroups = $member->subjectEnrollments
                            ->filter(fn ($enrollment) => $enrollment->offering?->is_elective)
                            ->pluck('offering.elective_group')->filter()->unique()->values();
                    @endphp
                    <tr class="{{ $member->member_type === 'student' ? 'bg-blue-50/30 hover:bg-blue-50' : ($member->member_type === 'teacher' ? 'bg-emerald-50/30 hover:bg-emerald-50' : 'bg-amber-50/30 hover:bg-amber-50') }}">
                        <td class="w-20 px-5 py-2.5">
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
                        <td class="px-5 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="h-9 w-9 rounded-full object-cover ring-1 ring-gray-200">
                                <div>
                                    <p class="font-extrabold text-gray-950" data-highlight>{{ $member->full_name }}</p>
                                    <p class="text-sm font-medium text-gray-500" data-highlight>{{ $member->roll_number }} · {{ $member->email ?: 'No email' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-2.5">
                            <span class="inline-flex rounded-full border px-3 py-1 text-xs font-extrabold {{ $typeStyles[$member->member_type] ?? 'bg-gray-50 text-gray-600 border-gray-100' }}" data-highlight>
                                {{ $typeLabels[$member->member_type] ?? ucfirst($member->member_type) }}
                            </span>
                            @unless($member->is_active)
                                <span class="ml-1 inline-flex rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[10px] font-extrabold text-red-700">Inactive</span>
                            @endunless
                        </td>
                        <td class="px-5 py-2.5" data-highlight>
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $member->gender === 'Female' ? 'border-pink-100 bg-pink-50 text-pink-700' : ($member->gender === 'Male' ? 'border-sky-100 bg-sky-50 text-sky-700' : 'border-gray-200 bg-gray-50 text-gray-500') }}">
                                {{ $member->gender ?: 'Not set' }}
                            </span>
                        </td>
                        <td class="px-5 py-2.5" data-highlight>
                            <p class="text-sm font-semibold text-gray-700">{{ $member->stream ?: '-' }} <span class="text-gray-400">/</span> {{ $member->section ?: '-' }}</p>
                            @if($member->member_type === 'student' && ($member->academicSection?->group_name || $memberElectiveGroups->isNotEmpty()))
                                <div class="mt-1.5 flex max-w-64 flex-wrap gap-1">
                                    @if($member->academicSection?->group_name)
                                        <span class="rounded-md bg-emerald-50 px-1.5 py-0.5 text-[9px] font-extrabold text-emerald-700" title="Section group">Section: {{ $member->academicSection->group_name }}</span>
                                    @endif
                                    @foreach($memberElectiveGroups as $group)
                                        <span class="rounded-md bg-purple-50 px-1.5 py-0.5 text-[9px] font-extrabold text-purple-700" title="Elective group">{{ $group }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-2.5 text-right">
                            <div class="flex justify-end gap-1.5">
                                <a href="{{ route('admin.hr.members.show', $member) }}" title="View" aria-label="View {{ $member->full_name }}"
                                   class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                @if(auth()->user()?->canAccess('hr.members.edit'))
                                    <a href="{{ route('admin.hr.members.edit', $member) }}" title="Edit" aria-label="Edit {{ $member->full_name }}"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @if($member->user)
                                        <button type="button" onclick="openHrPasswordReset({{ $member->id }}, @js($member->full_name), @js($member->user->student_code), @js($member->user->email))" title="Reset Login" aria-label="Reset login for {{ $member->full_name }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        </button>
                                    @endif
                                @endif
                                @if(auth()->user()?->canAccess('hr.members.delete'))
                                    @if($member->library_clearance_hold)
                                        <button type="button" disabled title="{{ $member->library_clearance_message }}" aria-label="Delete blocked: {{ $member->library_clearance_message }}"
                                                class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-gray-400">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </button>
                                    @else
                                        <form method="POST" action="{{ route('admin.hr.members.destroy', $member) }}"
                                              class="hr-member-delete-form"
                                              data-member-name="{{ $member->full_name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete" aria-label="Delete {{ $member->full_name }}"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
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
