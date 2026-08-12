@extends('hajiri.layouts.app')

@section('content')

{{-- Hero --}}
<div class="bg-[#1a5632] rounded-2xl p-6 sm:p-8 text-white shadow-lg mb-6 relative overflow-hidden">
    <div class="absolute -top-10 -right-10 w-48 h-48 bg-[#e2a024] rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-[11px] font-bold text-[#e2a024] uppercase tracking-widest mb-1">Leave Management</p>
            <h2 class="text-2xl font-extrabold">Leave Records</h2>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select id="yearCal" class="changeCalendarDate text-sm border border-white/20 bg-white/10 text-white rounded-xl px-3 py-2 focus:outline-none focus:border-white min-w-0">
                @foreach($npCal->bs as $bsDate)
                    <option class="text-gray-800" {{ $nowData['yearBS'] == $bsDate[0] ? 'selected' : '' }} value="{{ $bsDate[0] }}">{{ $bsDate[0] }}</option>
                @endforeach
            </select>
            <select id="monthCal" class="changeCalendarDate text-sm border border-white/20 bg-white/10 text-white rounded-xl px-3 py-2 focus:outline-none focus:border-white min-w-0">
                @for($i = 1; $i <= 12; $i++)
                    <option class="text-gray-800" {{ $nowData['monthBS'] == $i ? 'selected' : '' }} value="{{ $i }}">{{ $npCal->get_nepali_month($i) }}</option>
                @endfor
            </select>
            <button id="addLeaveModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-[#e2a024] hover:bg-godawari-gold-light text-white text-sm font-extrabold rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Leave
            </button>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-4">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif
@if (Session::has('message'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl px-4 py-3 mb-4">{{ Session::get('message') }}</div>
@endif

{{-- Mobile card list --}}
<div class="block md:hidden space-y-3 mb-4">
    @php $key = 0; @endphp
    @forelse($leaves as $leave)
        @if($leave['alias'] == 'DEFAULT') @continue @endif
        @php $key++; @endphp
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div>
                    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-0.5">Date</p>
                    <p class="font-bold text-gray-900 text-sm">{{ $leave['date'] }}</p>
                </div>
                <span class="shrink-0 px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-extrabold rounded-lg uppercase">{{ $leave['type']['name'] ?? '—' }}</span>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-0.5">Remarks</p>
                    <p class="text-sm text-gray-700 font-medium">{{ $leave['name'] ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider mb-0.5">Employee</p>
                    <p class="text-sm text-gray-700 font-medium">{{ $leave['user']['name'] ?? '—' }}</p>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center justify-center py-14 text-center px-6">
            <div class="w-12 h-12 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h4 class="text-sm font-extrabold text-gray-700 mb-1">No leave records</h4>
            <p class="text-xs text-gray-400">Add a leave entry using the button above.</p>
        </div>
    @endforelse
</div>

{{-- Desktop table --}}
<div class="hidden md:block bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    @if(count($leaves) === 0)
        <div class="flex flex-col items-center justify-center py-16 text-center px-6">
            <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h4 class="text-base font-extrabold text-gray-700 mb-1">No leave records</h4>
            <p class="text-sm text-gray-400">Add a leave entry using the button above.</p>
        </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider w-12">#</th>
                    <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Remarks</th>
                    <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Employee</th>
                    <th class="px-4 py-3 text-right text-[10px] font-extrabold text-gray-400 uppercase tracking-wider w-44">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @php $key = 0; @endphp
                @foreach($leaves as $leave)
                    @if($leave['alias'] == 'DEFAULT') @continue @endif
                    @php $key++; @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <form method="post" action="{{ route('hajiri.holidays.update', $leave->id) }}">
                            @csrf @method('put')
                            <td class="px-4 py-3 text-xs font-bold text-gray-400">{{ $key }}</td>
                            <td class="px-4 py-3">
                                <span id="date_{{ $key }}" class="font-medium text-gray-700">{{ $leave['date'] }}</span>
                                <input id="input_date_{{ $key }}" value="{{ $leave['date'] }}"
                                       class="hidden w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#1a5632]"
                                       type="text" name="date" required/>
                            </td>
                            <td class="px-4 py-3">
                                <span id="type_{{ $key }}" class="inline-flex items-center px-2 py-0.5 bg-blue-50 text-blue-700 text-xs font-bold rounded">{{ $leave['type']['name'] ?? '—' }}</span>
                                <select id="input_type_{{ $key }}" name="type_id" class="hidden w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#1a5632]">
                                    <option value="" disabled selected>Select Type</option>
                                    @foreach($leave_type as $lt)
                                        <option value="{{ $lt['id'] }}" {{ $lt['id'] == $leave['type_id'] ? 'selected' : '' }}>{{ $lt['name'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <span id="label_{{ $key }}" class="font-medium text-gray-700">{{ $leave['name'] }}</span>
                                <input id="input_label_{{ $key }}" value="{{ $leave['name'] }}"
                                       class="hidden w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#1a5632]"
                                       type="text" name="name" required/>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <span id="user_{{ $key }}" class="font-medium text-gray-700">{{ $leave['user']['name'] ?? '—' }}</span>
                                <select id="input_user_{{ $key }}" name="user_id" class="hidden w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#1a5632]">
                                    <option value="" disabled selected>Select Employee</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user['id'] }}" {{ $user['id'] == $leave['user_id'] ? 'selected' : '' }}>{{ $user['name'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button id="editBtn_{{ $key }}"
                                            onclick="$('#date_{{ $key }},#type_{{ $key }},#label_{{ $key }},#user_{{ $key }}').addClass('hidden'); $('#input_date_{{ $key }},#input_type_{{ $key }},#input_label_{{ $key }},#input_user_{{ $key }}').removeClass('hidden'); $('#saveBtn_{{ $key }}').css('display','inline-flex'); $(this).hide();"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 text-gray-600 text-xs font-bold rounded-lg hover:border-[#1a5632] hover:text-[#1a5632] hover:bg-green-50 transition-colors" type="button">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </button>
                                    <button id="saveBtn_{{ $key }}" style="display:none;"
                                            class="items-center gap-1.5 px-3 py-1.5 bg-[#1a5632] text-white text-xs font-bold rounded-lg hover:bg-[#0b2415] transition-colors" type="submit">
                                        <svg class="w-3.5 h-3.5 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Save
                                    </button>
                                </div>
                            </td>
                        </form>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Add Leave Modal --}}
<div id="leaveModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" id="leaveModalBackdrop"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-extrabold text-gray-900">Add Leave Entry</h3>
            <button id="leaveModalClose" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="leaveModalBody" class="px-6 py-5 min-h-24 flex items-center justify-center">
            <svg class="w-8 h-8 animate-spin text-[#1a5632]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openLeaveModal() {
        $('#leaveModal').css('display', 'flex');
        $('#leaveModalBody').html('<div class="flex items-center justify-center py-6"><svg class="w-8 h-8 animate-spin text-[#1a5632]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></div>');
        $('#leaveModalBody').load('{{ route('hajiri.leaves.addleave') }}', { '_token': '{{ csrf_token() }}' });
    }

    $('#addLeaveModal').on('click', function() { openLeaveModal(); });
    $('#leaveModalClose, #leaveModalBackdrop').on('click', function() { $('#leaveModal').hide(); });

    $(document).ready(function() {
        $('select.changeCalendarDate').on('change', function() {
            var y = $('#yearCal').val();
            var m = $('#monthCal').val();
            document.location = "{{ url('/admin/hajiri/leaves/custom') }}/" + y + '/' + m;
        });
    });
</script>
@endpush
