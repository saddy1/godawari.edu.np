@extends(auth()->user()?->isTeacher() ? 'teaching_learning.teacher-workspace.layout' : 'hajiri.layouts.app')

@section('content')

{{-- Hero --}}
<div class="bg-[#1a5632] rounded-2xl p-6 sm:p-8 text-white shadow-lg mb-6 relative overflow-hidden">
    <div class="absolute -top-10 -right-10 w-48 h-48 bg-[#e2a024] rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    <div class="relative z-10">
        <p class="text-[11px] font-bold text-[#e2a024] uppercase tracking-widest mb-1">Leave Management</p>
        <h2 class="text-2xl font-extrabold">My Leaves</h2>
        <p class="text-green-200 text-sm mt-1">View your balance and apply for leave</p>
    </div>
</div>

@if (session('message'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl px-4 py-3 mb-5">{{ session('message') }}</div>
@endif
@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-5">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

{{-- Leave Balance Cards --}}
@if($balances->isNotEmpty())
<div class="mb-6">
    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Leave Balance — Current Fiscal Year</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
        @foreach($balances as $b)
        @php
            $pct = $b['total'] > 0 ? min(100, round(($b['used'] / $b['total']) * 100)) : 0;
            $barColor = $pct >= 100 ? 'bg-red-400' : ($pct >= 70 ? 'bg-amber-400' : 'bg-[#1a5632]');
        @endphp
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-[11px] font-extrabold bg-[#1a5632] text-white">
                    {{ strtoupper($b['policy']->short_code) }}
                </span>
                <span class="text-[10px] font-bold {{ $b['policy']->period_type === 'annual' ? 'text-blue-500' : 'text-purple-500' }}">
                    {{ $b['policy']->period_type === 'annual' ? 'Annual' : 'Tenure' }}
                </span>
            </div>
            <p class="text-xs font-bold text-gray-700 leading-tight mb-3">{{ $b['policy']->name }}</p>
            <div class="flex items-end justify-between mb-1.5">
                <span class="text-2xl font-black {{ $b['remaining'] == 0 ? 'text-red-500' : 'text-[#1a5632]' }}">{{ $b['remaining'] }}</span>
                <span class="text-[11px] text-gray-400 font-medium">/ {{ $b['total'] }} days</span>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ $pct }}%"></div>
            </div>
            <p class="text-[10px] text-gray-400 mt-1.5">{{ $b['used'] }} used · {{ $b['remaining'] }} left</p>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

    {{-- Apply Form --}}
    <div class="xl:col-span-2">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
            <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-4">Apply for Leave</p>
            <form method="POST" action="{{ route('hajiri.leave-requests.store') }}" class="space-y-4" id="leaveApplyForm">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Leave Type</label>
                    <select name="leave_policy_id" id="leavePolicySelect" required
                            class="w-full px-3 py-2.5 text-sm border {{ $errors->has('leave_policy_id') ? 'border-red-400' : 'border-gray-200' }} rounded-xl bg-white focus:outline-none focus:border-[#1a5632]">
                        <option value="" disabled selected>Select leave type</option>
                        @foreach($policies as $p)
                            <option value="{{ $p->id }}"
                                    data-remaining="{{ $p->id }}"
                                    {{ old('leave_policy_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->days_allowed }} days)
                            </option>
                        @endforeach
                    </select>
                    @error('leave_policy_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Balance hint --}}
                @foreach($balances as $b)
                <div id="balance_{{ $b['policy']->id }}" class="balance-hint hidden">
                    <div class="bg-{{ $b['remaining'] == 0 ? 'red' : 'green' }}-50 border border-{{ $b['remaining'] == 0 ? 'red' : 'green' }}-100 rounded-xl px-3 py-2 text-xs font-semibold {{ $b['remaining'] == 0 ? 'text-red-600' : 'text-green-700' }}">
                        @if($b['remaining'] == 0)
                            No balance remaining for this leave type.
                        @else
                            Balance: <strong>{{ $b['remaining'] }}</strong> of {{ $b['total'] }} days remaining
                        @endif
                    </div>
                </div>
                @endforeach

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Start Date (BS)</label>
                        @php $oldStart = old('start_date_bs'); $oldStart = is_array($oldStart) ? ($oldStart[0] ?? '') : ($oldStart ?? ''); @endphp
                        <input type="text" name="start_date_bs" id="startDateBS" value="{{ $oldStart }}" required readonly
                               class="date-picker w-full px-3 py-2.5 text-sm border {{ $errors->has('start_date_bs') ? 'border-red-400' : 'border-gray-200' }} rounded-xl focus:outline-none focus:border-[#1a5632] bg-white cursor-pointer"
                               placeholder="YYYY-MM-DD"/>
                        @error('start_date_bs')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">End Date (BS)</label>
                        @php $oldEnd = old('end_date_bs'); $oldEnd = is_array($oldEnd) ? ($oldEnd[0] ?? '') : ($oldEnd ?? ''); @endphp
                        <input type="text" name="end_date_bs" id="endDateBS" value="{{ $oldEnd }}" required readonly
                               class="date-picker w-full px-3 py-2.5 text-sm border {{ $errors->has('end_date_bs') ? 'border-red-400' : 'border-gray-200' }} rounded-xl focus:outline-none focus:border-[#1a5632] bg-white cursor-pointer"
                               placeholder="YYYY-MM-DD"/>
                        @error('end_date_bs')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Days preview --}}
                <div id="daysPreview" class="hidden bg-blue-50 border border-blue-100 rounded-xl px-3 py-2 text-sm font-semibold text-blue-800">
                    Duration: <span id="daysCount" class="font-extrabold text-[#1a5632]">0</span> day(s)
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Reason <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea name="reason" rows="3" maxlength="500"
                              class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-[#1a5632] resize-none"
                              placeholder="Brief reason for leave…">{{ old('reason') }}</textarea>
                </div>

                <button type="submit"
                        class="w-full py-2.5 bg-[#1a5632] hover:bg-[#0b2415] text-white text-sm font-extrabold rounded-xl transition-colors">
                    Submit Leave Request
                </button>
            </form>
        </div>
    </div>

    {{-- Request History --}}
    <div class="xl:col-span-3">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">My Leave History</p>
            </div>

            @if($myRequests->isEmpty())
                <div class="flex flex-col items-center justify-center py-14 text-center px-6">
                    <div class="w-12 h-12 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="text-sm font-extrabold text-gray-700">No leave requests yet</p>
                    <p class="text-xs text-gray-400 mt-1">Use the form to apply for leave.</p>
                </div>
            @else
                {{-- Mobile: cards --}}
                <div class="block md:hidden divide-y divide-gray-50">
                    @foreach($myRequests as $req)
                    @php $c = ['pending' => 'amber', 'approved' => 'green', 'rejected' => 'red'][$req->status] ?? 'gray'; @endphp
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="font-bold text-gray-900 text-sm">{{ $req->policy->name ?? '—' }}</p>
                                <p class="text-[11px] text-gray-400">{{ $req->start_date->format('Y-m-d') }} → {{ $req->end_date->format('Y-m-d') }}</p>
                            </div>
                            <div class="text-right">
                                <span class="block text-xl font-extrabold text-[#1a5632]">{{ $req->days_count }}<span class="text-xs font-normal text-gray-400"> days</span></span>
                                <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-lg uppercase bg-{{ $c }}-100 text-{{ $c }}-700">{{ $req->status }}</span>
                            </div>
                        </div>
                        @if($req->admin_remarks)
                            <p class="text-[11px] text-gray-500 italic bg-gray-50 rounded-lg px-2 py-1.5 mb-2">Remarks: {{ $req->admin_remarks }}</p>
                        @endif
                        @if($req->status === 'pending')
                        <form method="POST" action="{{ route('hajiri.my-leaves.destroy', $req->id) }}"
                              onsubmit="return confirm('Cancel this leave request?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-bold text-red-400 hover:text-red-600 underline">Cancel Request</button>
                        </form>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Desktop: table --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Leave Type</th>
                                <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Period</th>
                                <th class="px-4 py-3 text-center text-[10px] font-extrabold text-gray-400 uppercase tracking-wider w-14">Days</th>
                                <th class="px-4 py-3 text-center text-[10px] font-extrabold text-gray-400 uppercase tracking-wider w-24">Status</th>
                                <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Remarks</th>
                                <th class="px-4 py-3 text-right text-[10px] font-extrabold text-gray-400 uppercase tracking-wider w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($myRequests as $req)
                            @php $c = ['pending' => 'amber', 'approved' => 'green', 'rejected' => 'red'][$req->status] ?? 'gray'; @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-900">{{ $req->policy->name ?? '—' }}</p>
                                    <p class="text-[11px] text-gray-400">{{ strtoupper($req->policy->short_code ?? '') }}</p>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 font-medium">
                                    {{ $req->start_date->format('Y-m-d') }}<br>
                                    <span class="text-gray-400">to</span> {{ $req->end_date->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-3 text-center font-extrabold text-[#1a5632] text-base">{{ $req->days_count }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-1 text-[10px] font-extrabold rounded-lg uppercase bg-{{ $c }}-100 text-{{ $c }}-700">
                                        {{ $req->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 hidden lg:table-cell max-w-xs">
                                    {{ $req->admin_remarks ?: ($req->reason ?: '—') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($req->status === 'pending')
                                    <form method="POST" action="{{ route('hajiri.my-leaves.destroy', $req->id) }}"
                                          onsubmit="return confirm('Cancel this leave request?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 border border-red-200 text-red-500 text-xs font-bold rounded-lg hover:bg-red-50 transition-colors">
                                            Cancel
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-xs text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    var balanceMap = {
        @foreach($balances as $b)
        {{ $b['policy']->id }}: {{ $b['remaining'] }},
        @endforeach
    };

    $('#leavePolicySelect').on('change', function() {
        var id = $(this).val();
        $('.balance-hint').addClass('hidden');
        if (id) { $('#balance_' + id).removeClass('hidden'); }
        updateDays();
    });

    function parseBsDate(str) {
        if (!str) return null;
        var parts = str.split('-');
        if (parts.length !== 3) return null;
        return new Date(str); // rough approximation for day diff only
    }

    function updateDays() {
        var start = $('#startDateBS').val();
        var end   = $('#endDateBS').val();
        if (start && end) {
            // Count days: send to server or compute roughly via dates
            // Simple approximation: use string-based calculation
            var s = start.split('-').map(Number);
            var e = end.split('-').map(Number);
            // Use a simple day-difference for BS (good enough for display)
            var sD = new Date(s[0], s[1]-1, s[2]);
            var eD = new Date(e[0], e[1]-1, e[2]);
            var diff = Math.round((eD - sD) / 86400000) + 1;
            if (diff > 0) {
                $('#daysCount').text(diff);
                $('#daysPreview').removeClass('hidden');
            } else {
                $('#daysPreview').addClass('hidden');
            }
        }
    }

    $('#startDateBS, #endDateBS').on('change', updateDays);
</script>
@endpush
