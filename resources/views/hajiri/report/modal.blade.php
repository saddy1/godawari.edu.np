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

{{-- Monthly report filters --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 mb-5">
    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-1">Monthly Report</p>
    <p class="mb-4 text-sm font-medium text-gray-500">Use this only when you need one Nepali month.</p>
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

{{-- Date-range download --}}
<form id="rangeReportForm" method="GET" action="{{ route('hajiri.report.range.print') }}" target="_blank"
      class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 mb-5">
    <div class="flex flex-col gap-1 mb-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Print Date-Range Report</p>
            <p class="mt-1 text-sm font-medium text-gray-500">Choose up to one BS year. Each employee's 12-month attendance matrix fits on one A4 landscape sheet.</p>
        </div>
        <span class="text-xs font-bold text-gray-400">Maximum range: 1 year</span>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">From Date (BS)</label>
            <input type="text" id="rangeFromBS" name="from_bs" value="{{ old('from_bs', $rangeStartBS) }}" required readonly
                   data-max-bs="{{ $todayBS }}"
                   placeholder="2083-01-01"
                   class="nepali-date-picker w-full cursor-pointer rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">To Date (BS)</label>
            <input type="text" id="rangeToBS" name="to_bs" value="{{ old('to_bs', $todayBS) }}" required readonly
                   data-min-bs="{{ old('from_bs', $rangeStartBS) }}" data-max-bs="today"
                   class="nepali-date-picker w-full cursor-pointer rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-600 mb-1.5">Report Type</label>
            <select name="range_report_type" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                <option value="ap" @selected(old('range_report_type', 'ap') === 'ap')>A/P (Present / Absent)</option>
                <option value="detailed" @selected(old('range_report_type') === 'detailed')>Detailed In / Out</option>
            </select>
        </div>
        @if(auth()->user()?->isAdmin())
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Employee Group</label>
                <select id="rangeEmployeeGroup" name="employee_group" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                    <option value="all" @selected(old('employee_group', 'all') === 'all')>All employees</option>
                    <option value="academic" @selected(old('employee_group') === 'academic')>Academic employees</option>
                    <option value="administrative" @selected(old('employee_group') === 'administrative')>Administrative employees</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Employee (optional)</label>
                <select id="rangeEmployee" name="device_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                    <option value="">All in selected group</option>
                    @foreach($users as $user)
                        @php
                            $optionGroup = match($user->student?->member_type) {
                                'teacher' => 'academic',
                                'staff' => 'administrative',
                                default => strtolower((string) $user->working_at?->label) === 'academic'
                                    ? 'academic'
                                    : (strtolower((string) $user->working_at?->label) === 'administration' ? 'administrative' : ''),
                            };
                        @endphp
                        <option value="{{ $user->device_id }}"
                                data-group="{{ $optionGroup }}"
                                data-department="{{ $user->hajiri_department_id }}"
                                @selected((string) old('device_id') === (string) $user->device_id)>
                            {{ $user->name }} [{{ $user->device_id }}]
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Department (optional)</label>
                <select id="rangeDepartment" name="department_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                    <option value="">All departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->label }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    <div class="mt-4 flex justify-end">
        <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#e2a024] px-5 py-2.5 text-sm font-extrabold text-white transition-colors hover:bg-godawari-gold-light">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/>
            </svg>
            Open Printable Report
        </button>
    </div>
</form>

{{-- Individual Report --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 mb-5">
    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-1">Monthly Individual Report</p>
    <p class="mb-4 text-sm font-medium text-gray-500">Uses the monthly year, month, and report type selected at the top.</p>
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
{{-- Quick annual/date-range group reports --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-1">Quick Date-Range Report by Staff Type</p>
    <p class="mb-4 text-sm font-medium text-gray-500">Uses the Nepali From/To dates and report type selected above.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <button type="button" class="printRangeByGroup inline-flex items-center justify-center gap-2 px-5 py-3 bg-[#1a5632] hover:bg-[#0b2415] text-white text-sm font-extrabold rounded-xl transition-colors" data-group="administrative">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print Administration Date Range
        </button>
        <button type="button" class="printRangeByGroup inline-flex items-center justify-center gap-2 px-5 py-3 bg-[#e2a024] hover:bg-godawari-gold-light text-white text-sm font-extrabold rounded-xl transition-colors" data-group="academic">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print Academic Date Range
        </button>
    </div>
</div>
@endif

@endsection

@push('scripts')
@include('partials.nepali-date-picker')
<script>
var baseURLforUser = '{{ route('hajiri.report.month_user', ['apd' => '__apd__', 'userid' => '__userid__', 'year' => '__year__', 'month' => '__month__']) }}';

$('#printIndividual').on('click', function() {
    var url = baseURLforUser
        .replace('__apd__',   $('#typeHajiri').val())
        .replace('__year__',  $('#yearHajiri').val())
        .replace('__userid__', $('#userid').val())
        .replace('__month__', $('#monthHajiri').val());
    Object.assign(document.createElement('a'), { target: '_blank', href: url }).click();
});

(function () {
    var from = document.getElementById('rangeFromBS');
    var to = document.getElementById('rangeToBS');
    var group = document.getElementById('rangeEmployeeGroup');
    var department = document.getElementById('rangeDepartment');
    var employee = document.getElementById('rangeEmployee');

    function syncDateLimits() {
        if (!from || !to) return;
        to.dataset.minBs = from.value;
        from.dataset.maxBs = to.value || @json($todayBS);
    }

    function filterEmployees() {
        if (!employee) return;
        var selectedGroup = group?.value || 'all';
        var selectedDepartment = department?.value || '';

        Array.from(employee.options).forEach(function (option, index) {
            if (index === 0) return;
            var groupMatches = selectedGroup === 'all' || option.dataset.group === selectedGroup;
            var departmentMatches = !selectedDepartment || option.dataset.department === selectedDepartment;
            option.hidden = !(groupMatches && departmentMatches);
            option.disabled = option.hidden;
        });

        if (employee.selectedOptions[0]?.hidden) {
            employee.value = '';
        }
    }

    from?.addEventListener('change', syncDateLimits);
    to?.addEventListener('change', syncDateLimits);
    group?.addEventListener('change', filterEmployees);
    department?.addEventListener('change', filterEmployees);
    syncDateLimits();
    filterEmployees();
    window.initNepaliDatePickers?.();

    document.querySelectorAll('.printRangeByGroup').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!group) return;
            group.value = button.dataset.group;
            if (employee) employee.value = '';
            filterEmployees();
            document.getElementById('rangeReportForm')?.requestSubmit();
        });
    });
})();
</script>
@endpush
