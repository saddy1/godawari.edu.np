@extends('hajiri.layouts.app')

@section('content')

{{-- Hero --}}
<div class="bg-[#1a5632] rounded-2xl p-6 sm:p-8 text-white shadow-lg mb-6 relative overflow-hidden">
    <div class="absolute -top-10 -right-10 w-48 h-48 bg-[#e2a024] rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    <div class="relative z-10">
        <p class="text-[11px] font-bold text-[#e2a024] uppercase tracking-widest mb-1">Attendance Module</p>
        <h2 class="text-2xl font-extrabold">Print Attendance Report</h2>
        <p class="text-green-200 text-sm mt-1">Select filters and print or export the report</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 mb-5">
    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-4">Report Filters</p>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Year (BS)</label>
            <select id="yearHajiri" class="w-full text-sm px-3 py-2.5 border border-gray-200 rounded-xl bg-white font-semibold focus:outline-none focus:border-[#1a5632]">
                @foreach($npCal->bs as $bsDate)
                    <option {{ $nowData['yearBS'] == $bsDate[0] ? 'selected' : '' }} value="{{ $bsDate[0] }}">{{ $bsDate[0] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Month</label>
            <select id="monthHajiri" class="w-full text-sm px-3 py-2.5 border border-gray-200 rounded-xl bg-white font-semibold focus:outline-none focus:border-[#1a5632]">
                @for($i = 1; $i <= 12; $i++)
                    <option {{ $nowData['monthBS'] == $i ? 'selected' : '' }} value="{{ $i }}">{{ $npCal->get_nepali_month($i) }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Report Type</label>
            <select id="typeHajiri" class="w-full text-sm px-3 py-2.5 border border-gray-200 rounded-xl bg-white font-semibold focus:outline-none focus:border-[#1a5632]">
                <option value="ap" selected>A/P (Present / Absent)</option>
                <option value="d">Detailed Attendance</option>
            </select>
        </div>
    </div>
</div>

{{-- Individual Report --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 mb-5">
    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-4">Individual Report</p>
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1 min-w-0">
            <label class="block text-xs font-bold text-gray-600 mb-1.5">कर्मचारी / शिक्षकको नाम</label>
            <select id="userid" class="w-full text-sm px-3 py-2.5 border border-gray-200 rounded-xl bg-white font-semibold focus:outline-none focus:border-[#1a5632]">
                <option disabled selected>Select Staff / Teacher</option>
                @foreach($users as $user)
                    <option value="{{ $user['device_id'] }}">{{ $user['name'] }} [{{ $user['device_id'] }}]</option>
                @endforeach
            </select>
        </div>
        <div class="sm:flex sm:items-end">
            <button id="printIndividual"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#1a5632] hover:bg-[#0b2415] text-white text-sm font-extrabold rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Individual
            </button>
        </div>
    </div>
</div>

@if(auth()->user()?->isAdmin())
{{-- Bulk Report --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-4">Bulk Report by Staff Type</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <button class="printByType inline-flex items-center justify-center gap-2 px-5 py-3 bg-[#1a5632] hover:bg-[#0b2415] text-white text-sm font-extrabold rounded-xl transition-colors" data-type="1">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print Administration Logs
        </button>
        <button class="printByType inline-flex items-center justify-center gap-2 px-5 py-3 bg-[#e2a024] hover:bg-godawari-gold-light text-white text-sm font-extrabold rounded-xl transition-colors" data-type="2">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print Academic Logs
        </button>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
var baseURLforType = '{{ route('hajiri.report.month_type', ['apd' => '__apd__', 'typeid' => '__typeid__', 'year' => '__year__', 'month' => '__month__']) }}';
var baseURLforUser = '{{ route('hajiri.report.month_user', ['apd' => '__apd__', 'userid' => '__userid__', 'year' => '__year__', 'month' => '__month__']) }}';

$('.printByType').on('click', function() {
    var url = baseURLforType
        .replace('__apd__',    $('#typeHajiri').val())
        .replace('__year__',   $('#yearHajiri').val())
        .replace('__typeid__', $(this).data('type'))
        .replace('__month__',  $('#monthHajiri').val());
    Object.assign(document.createElement('a'), { target: '_blank', href: url }).click();
});

$('#printIndividual').on('click', function() {
    var url = baseURLforUser
        .replace('__apd__',   $('#typeHajiri').val())
        .replace('__year__',  $('#yearHajiri').val())
        .replace('__userid__', $('#userid').val())
        .replace('__month__', $('#monthHajiri').val());
    Object.assign(document.createElement('a'), { target: '_blank', href: url }).click();
});
</script>
@endpush
