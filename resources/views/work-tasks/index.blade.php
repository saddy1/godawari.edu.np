@extends('work-tasks.layout')

@section('title', 'Work Tasks')

@section('content')
@php
    $input = 'w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
    $label = 'mb-1.5 block text-xs font-extrabold uppercase tracking-wider text-gray-600';
    $hint = 'mt-1 text-[11px] font-semibold leading-4 text-gray-400';
    $canCreate = auth()->user()?->canAccess('work-tasks.create');
@endphp

<div class="space-y-5">
    <div class="rounded-2xl bg-[#0b2415] p-5 sm:p-6 text-white shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-widest text-white/50">Work Management</p>
                <h1 class="mt-1 text-3xl font-extrabold">Task Assignment & Performance</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">
                    Assign measurable work, collect teacher evidence, review submissions, and calculate incentive payout.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:w-[520px]">
                <div class="rounded-xl bg-white/10 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-white/45">Tasks</p>
                    <p class="text-xl font-black">{{ $stats['total'] }}</p>
                </div>
                <div class="rounded-xl bg-white/10 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-white/45">{{ $canCreate ? 'Review' : 'Pending' }}</p>
                    <p class="text-xl font-black">{{ $canCreate ? $stats['pending_review'] : $stats['pending_assigned'] }}</p>
                </div>
                <div class="rounded-xl bg-white/10 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-white/45">Approved</p>
                    <p class="text-xl font-black">{{ $stats['approved'] }}</p>
                </div>
                <div class="rounded-xl bg-white/10 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-white/45">Payout</p>
                    <p class="text-xl font-black">Rs. {{ number_format($stats['payout'], 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    @if($canCreate)
    <div>
        <form
            id="assign-task"
            method="POST"
            action="{{ route('admin.work-tasks.store') }}"
            class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
            x-data="{
                assignmentType: @js(old('assignment_type', 'individual')),
                selectedChecklistItems: @js(collect(old('checklist_item_ids', []))->map(fn ($id) => (int) $id)->values()),
                expandedChecklist: null,
                toggleChecklist(itemIds, checked) {
                    itemIds = itemIds.map(Number);
                    const selected = this.selectedChecklistItems.map(Number);

                    if (checked) {
                        this.selectedChecklistItems = [...new Set([...selected, ...itemIds])];
                        return;
                    }

                    this.selectedChecklistItems = selected.filter((id) => ! itemIds.includes(id));
                },
                checklistComplete(itemIds) {
                    const selected = this.selectedChecklistItems.map(Number);
                    itemIds = itemIds.map(Number);

                    return itemIds.length > 0 && itemIds.every((id) => selected.includes(id));
                },
                checklistPartial(itemIds) {
                    const selected = this.selectedChecklistItems.map(Number);
                    itemIds = itemIds.map(Number);

                    return itemIds.some((id) => selected.includes(id)) && ! this.checklistComplete(itemIds);
                },
                selectedCount(itemIds) {
                    const selected = this.selectedChecklistItems.map(Number);
                    itemIds = itemIds.map(Number);

                    return itemIds.filter((id) => selected.includes(id)).length;
                },
                toggleChecklistBody(id) {
                    this.expandedChecklist = this.expandedChecklist === id ? null : id;
                },
            }"
        >
            @csrf
            <div class="mb-4">
                <div>
                    <h2 class="text-base font-extrabold text-gray-950">Assign New Task</h2>
                    <p class="text-xs font-semibold text-gray-500">Use individual assignment or target a committee/group.</p>
                </div>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="{{ $label }}" for="work-task-title">Task Title</label>
                    <input id="work-task-title" name="title" value="{{ old('title') }}" placeholder="e.g. Routine Management System" class="{{ $input }}">
                    <p class="{{ $hint }}">For a single custom task. Optional when assigning checklist templates.</p>
                </div>
                <div>
                    <label class="{{ $label }}" for="work-task-category">Work Category</label>
                    <input id="work-task-category" name="category" value="{{ old('category') }}" placeholder="e.g. Lesson plan, portfolio, attendance" class="{{ $input }}">
                    <p class="{{ $hint }}">Use the contract responsibility or work area.</p>
                </div>
                <div>
                    <label class="{{ $label }}" for="work-task-due-date">Final Submission Date</label>
                    <input id="work-task-due-date" type="date" name="due_date" required value="{{ old('due_date') }}" class="{{ $input }}">
                    <p class="{{ $hint }}">Late penalty applies after this date.</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $label }}" for="work-task-max-score">Max Marks</label>
                        <input id="work-task-max-score" type="number" name="max_score" min="1" max="1000" required value="{{ old('max_score', 10) }}" placeholder="10" class="{{ $input }}">
                        <p class="{{ $hint }}">Total score available.</p>
                    </div>
                    <div>
                        <label class="{{ $label }}" for="work-task-incentive">Incentive Amount</label>
                        <input id="work-task-incentive" type="number" step="0.01" name="incentive_amount" value="{{ old('incentive_amount') }}" placeholder="1000" class="{{ $input }}">
                        <p class="{{ $hint }}">Amount before scoring.</p>
                    </div>
                </div>
                <div>
                    <label class="{{ $label }}" for="work-task-assignment-type">Assign To</label>
                    <select id="work-task-assignment-type" name="assignment_type" x-model="assignmentType" class="{{ $input }}">
                        <option value="individual">Individual teacher</option>
                        <option value="group">Group / committee</option>
                    </select>
                    <p class="{{ $hint }}">Choose whether one teacher or a group owns this work.</p>
                </div>
                <div>
                    <label class="{{ $label }}" for="work-task-late-penalty">Late Penalty (%)</label>
                    <input id="work-task-late-penalty" type="number" step="0.01" name="late_penalty_percent" value="{{ old('late_penalty_percent', 0) }}" placeholder="0" class="{{ $input }}">
                    <p class="{{ $hint }}">Percentage deducted from calculated payout.</p>
                </div>
                <div x-show="assignmentType === 'individual'">
                    <label class="{{ $label }}" for="work-task-teacher">Teacher</label>
                    <select id="work-task-teacher" name="assigned_user_id" :disabled="assignmentType !== 'individual'" class="{{ $input }}">
                        <option value="">Select teacher</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected(old('assigned_user_id') == $teacher->id)>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $hint }}">The selected teacher can submit evidence.</p>
                </div>
                <div x-show="assignmentType === 'group'">
                    <label class="{{ $label }}" for="work-task-group">Group / Committee</label>
                    <select id="work-task-group" name="work_group_id" :disabled="assignmentType !== 'group'" class="{{ $input }}">
                        <option value="">Select group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected(old('work_group_id') == $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <p class="{{ $hint }}">All group members receive access to this work.</p>
                </div>
                <div x-show="assignmentType === 'group'">
                    <label class="{{ $label }}" for="work-task-submission-mode">Submission Method</label>
                    <select id="work-task-submission-mode" name="group_submission_mode" :disabled="assignmentType !== 'group'" class="{{ $input }}">
                        <option value="individual">Each member submits</option>
                        <option value="shared">One shared submission</option>
                    </select>
                    <p class="{{ $hint }}">Decide if evidence is separate or common for the group.</p>
                </div>
                <div x-show="assignmentType === 'group'">
                    <label class="{{ $label }}" for="work-task-payment-mode">Group Payout Rule</label>
                    <select id="work-task-payment-mode" name="group_payment_mode" :disabled="assignmentType !== 'group'" class="{{ $input }}">
                        <option value="equal">Split payout equally</option>
                        <option value="score">Pay each submission by score</option>
                    </select>
                    <p class="{{ $hint }}">Controls how incentive is calculated after approval.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $label }}" for="work-task-checklists">Task Checklists</label>
                    <div id="work-task-checklists" class="rounded-xl border border-gray-200 bg-gray-50/80 p-2">
                        @forelse($checklists as $checklist)
                            @php
                                $itemIds = $checklist->items->pluck('id')->map(fn ($id) => (int) $id)->values();
                            @endphp
                            <div class="mb-2 overflow-hidden rounded-xl border border-gray-200 bg-white last:mb-0">
                                <div class="flex flex-col gap-2 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex min-w-0 items-start gap-2">
                                        <input
                                            type="checkbox"
                                            class="mt-0.5 rounded border-gray-300 text-[#1a5632] focus:ring-[#1a5632]"
                                            :checked="checklistComplete(@js($itemIds))"
                                            :indeterminate="checklistPartial(@js($itemIds))"
                                            @change="toggleChecklist(@js($itemIds), $event.target.checked)"
                                        >
                                        <button type="button" class="min-w-0 text-left" @click="toggleChecklistBody({{ $checklist->id }})">
                                            <span class="block text-sm font-extrabold text-gray-900">{{ $checklist->name }}</span>
                                            <span class="block text-[11px] font-semibold text-gray-500">
                                                <span x-text="selectedCount(@js($itemIds)) + ' selected'"></span>
                                                · {{ $checklist->items->count() }} task{{ $checklist->items->count() === 1 ? '' : 's' }}
                                            </span>
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-gray-600">
                                            <span x-text="selectedCount(@js($itemIds))"></span>/{{ $checklist->items->count() }}
                                        </span>
                                        <button
                                            type="button"
                                            class="rounded-full border border-[#1a5632]/15 bg-white px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-[#1a5632] transition hover:bg-[#1a5632]/5"
                                            @click="toggleChecklist(@js($itemIds), ! checklistComplete(@js($itemIds)))"
                                            x-text="checklistComplete(@js($itemIds)) ? 'Deselect All' : 'Select All'"
                                        ></button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center gap-1 rounded-full bg-[#1a5632]/10 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-[#1a5632] transition hover:bg-[#1a5632]/15"
                                            @click="toggleChecklistBody({{ $checklist->id }})"
                                        >
                                            <span x-text="expandedChecklist === {{ $checklist->id }} ? 'Hide' : 'Choose'"></span>
                                            <svg class="h-3.5 w-3.5 transition-transform" :class="expandedChecklist === {{ $checklist->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div x-show="expandedChecklist === {{ $checklist->id }}" style="display: none;" class="grid gap-2 border-t border-gray-100 bg-white p-2 lg:grid-cols-2">
                                    @forelse($checklist->items as $item)
                                        <label
                                            class="grid cursor-pointer grid-cols-[2.25rem_1fr_1.75rem] items-center gap-2 rounded-lg border border-gray-100 bg-white px-2.5 py-2 text-gray-900 transition hover:border-[#1a5632]/20 hover:bg-gray-50"
                                            :class="selectedChecklistItems.map(Number).includes({{ $item->id }}) ? 'border-[#1a5632]/30 bg-[#1a5632]/5' : ''"
                                        >
                                            <span class="flex h-8 w-8 items-center justify-center rounded-md bg-violet-50 text-sm font-black text-violet-900">{{ $loop->iteration }}</span>
                                            <span class="min-w-0">
                                                <span class="block text-[13px] font-extrabold leading-5">{{ $item->title }}</span>
                                                <span class="block text-[10px] font-bold text-gray-500">{{ $item->max_score }} marks · Rs. {{ number_format((float) $item->incentive_amount, 0) }}</span>
                                            </span>
                                            <span class="relative flex h-7 w-7 items-center justify-center justify-self-end rounded-md border-2 transition"
                                                  :class="selectedChecklistItems.map(Number).includes({{ $item->id }}) ? 'border-[#1a5632] bg-[#1a5632] text-white' : 'border-green-300 bg-white text-transparent'">
                                                <input
                                                    type="checkbox"
                                                    name="checklist_item_ids[]"
                                                    value="{{ $item->id }}"
                                                    x-model.number="selectedChecklistItems"
                                                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                                >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="rounded-lg border border-dashed border-gray-200 px-3 py-2 text-xs font-semibold text-gray-500">No tasks inside this checklist.</p>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <p class="px-3 py-2 text-sm font-semibold text-gray-500">No checklists yet. Create them from the Checklists sidebar page.</p>
                        @endforelse
                    </div>
                    <p class="{{ $hint }}">Tick a full checklist or choose only the tasks needed for this assignment.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $label }}" for="work-task-description">Task Instructions & Required Evidence</label>
                    <textarea id="work-task-description" name="description" rows="3" placeholder="Write what must be completed and what proof should be uploaded." class="{{ $input }}">{{ old('description') }}</textarea>
                    <p class="{{ $hint }}">Used for custom tasks. Checklist items use their template text.</p>
                </div>
            </div>
            <div class="mt-4 flex justify-end border-t border-gray-100 pt-4">
                <button class="rounded-xl bg-[#1a5632] px-6 py-2.5 text-sm font-extrabold text-white">Assign Task</button>
            </div>
        </form>
    </div>
    @endif

    <div>
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-gray-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-gray-950">Assigned Tasks</h2>
                    <p class="text-xs font-semibold text-gray-500">Open a task to submit or review evidence.</p>
                </div>
                <form method="GET" class="flex gap-2">
                    <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="pending" @selected(request('status') === 'pending')>No submission</option>
                        <option value="submitted" @selected(request('status') === 'submitted')>Submitted</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    </select>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[900px] w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Task</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Assigned To</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Due</th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Progress</th>
                            <th class="px-4 py-2.5 text-right text-[11px] font-extrabold uppercase tracking-wider text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($tasks as $task)
                        @php
                            $submitted = $task->submissions->where('status', 'submitted')->count();
                            $approved = $task->submissions->where('status', 'approved')->count();
                            $late = now()->toDateString() > $task->due_date->toDateString();
                        @endphp
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-4 py-3">
                                <p class="font-extrabold text-gray-950">{{ $task->title }}</p>
                                <p class="mt-1 text-xs font-semibold text-gray-500">{{ $task->category ?: 'General' }} · {{ $task->max_score }} points · Rs. {{ number_format((float) $task->incentive_amount, 0) }}</p>
                                @if($task->checklist)
                                    <p class="mt-1 text-[11px] font-bold text-[#1a5632]">Checklist: {{ $task->checklist->name }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs font-extrabold text-gray-800">{{ $task->assignment_type === 'group' ? $task->group?->name : $task->assignedUser?->name }}</p>
                                <p class="mt-1 text-[11px] font-semibold text-gray-500">{{ $task->assignment_type === 'group' ? ucfirst($task->group_submission_mode).' group task' : 'Individual task' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-extrabold {{ $late ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $task->due_date->format('M d, Y') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-extrabold text-amber-700">{{ $submitted }} submitted</span>
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700">{{ $approved }} approved</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.work-tasks.show', $task) }}" class="inline-flex rounded-lg bg-[#1a5632] px-3 py-1.5 text-xs font-extrabold text-white">Open</a>
                                    @if($canCreate)
                                        <form method="POST" action="{{ route('admin.work-tasks.destroy', $task) }}" onsubmit="return confirm('Delete this assigned task? Any submitted evidence for this task will also be removed.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-extrabold text-red-700">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-sm font-semibold text-gray-500">No work tasks found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-5 py-4">{{ $tasks->links() }}</div>
        </div>

    </div>
</div>
@endsection
