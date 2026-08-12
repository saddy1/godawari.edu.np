@extends('hajiri.layouts.app')

@section('content')

<div class="mb-6 overflow-hidden rounded-2xl bg-[#1a5632] p-6 text-white shadow-lg sm:p-8">
    <p class="text-[11px] font-bold uppercase tracking-widest text-[#e2a024]">Attendance Module</p>
    <h2 class="mt-1 text-2xl font-extrabold">Attendance Reports</h2>
    <p class="mt-1 text-sm text-green-200">Choose a monthly or date-range report</p>
</div>

{{-- Monthly workflow --}}
<section class="mb-5 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6">
    <div class="mb-4">
        <p class="text-[10px] font-extrabold uppercase tracking-widest text-[#1a5632]">1. Monthly Report</p>
        <h3 class="mt-1 text-lg font-extrabold text-gray-900">Print one Nepali month</h3>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-600">Year (BS)</label>
            <select id="yearHajiri" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                @foreach($npCal->bs as $bsDate)
                    <option value="{{ $bsDate[0] }}" @selected($nowData['yearBS'] == $bsDate[0])>{{ $bsDate[0] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-600">Month</label>
            <select id="monthHajiri" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                @for($month = 1; $month <= 12; $month++)
                    <option value="{{ $month }}" @selected($nowData['monthBS'] == $month)>{{ $npCal->get_nepali_month($month) }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-600">Report Type</label>
            <select id="typeHajiri" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                <option value="ap">A/P (Present / Absent)</option>
                <option value="d">Detailed In / Out</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-600">Individual Employee (optional)</label>
            <select id="userid" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                <option value="">Type a name or device ID to search…</option>
            </select>
        </div>
    </div>

    <p id="monthlyError" class="mt-3 hidden text-sm font-bold text-red-600">Select an employee before printing an individual report.</p>

    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:justify-end">
        <button type="button" id="printIndividual"
                class="rounded-xl border border-[#1a5632] bg-white px-5 py-3 text-sm font-extrabold text-[#1a5632] hover:bg-emerald-50">
            Print Selected Employee
        </button>
        @if(auth()->user()?->isAdmin())
            <button type="button" class="printMonthlyByType rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415]" data-type="1">
                Print All Administration
            </button>
            <button type="button" class="printMonthlyByType rounded-xl bg-[#e2a024] px-5 py-3 text-sm font-extrabold text-white hover:bg-godawari-gold-light" data-type="2">
                Print All Academic
            </button>
        @endif
    </div>
</section>

{{-- Date-range workflow --}}
<form id="rangeReportForm" method="GET" action="{{ route('hajiri.report.range.print') }}" target="_blank"
      class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6">
    <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[10px] font-extrabold uppercase tracking-widest text-[#1a5632]">2. Date-Range Report</p>
            <h3 class="mt-1 text-lg font-extrabold text-gray-900">Print up to one Nepali year</h3>
        </div>
        <span class="text-xs font-bold text-gray-400">One landscape sheet per employee</span>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-600">From Date (BS)</label>
            <input type="text" id="rangeFromBS" name="from_bs" value="{{ old('from_bs', $rangeStartBS) }}" required readonly
                   data-max-bs="{{ $todayBS }}"
                   class="nepali-date-picker w-full cursor-pointer rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-600">To Date (BS)</label>
            <input type="text" id="rangeToBS" name="to_bs" value="{{ old('to_bs', $todayBS) }}" required readonly
                   data-min-bs="{{ old('from_bs', $rangeStartBS) }}" data-max-bs="today"
                   class="nepali-date-picker w-full cursor-pointer rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-600">Report Type</label>
            <select name="range_report_type" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                <option value="ap" @selected(old('range_report_type', 'ap') === 'ap')>A/P (Present / Absent)</option>
                <option value="detailed" @selected(old('range_report_type') === 'detailed')>Detailed In / Out</option>
            </select>
        </div>

        @if(auth()->user()?->isAdmin())
            <div>
                <label class="mb-1.5 block text-xs font-bold text-gray-600">Employee Group</label>
                <select id="rangeEmployeeGroup" name="employee_group" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                    <option value="all" @selected(old('employee_group', 'all') === 'all')>All employees</option>
                    <option value="academic" @selected(old('employee_group') === 'academic')>Academic employees</option>
                    <option value="administrative" @selected(old('employee_group') === 'administrative')>Administrative employees</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-gray-600">Department (optional)</label>
                <select id="rangeDepartment" name="department_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                    <option value="">All departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-gray-600">Employee (optional)</label>
                <select id="rangeEmployee" name="device_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                    <option value="">All matching employees</option>
                    @foreach($users as $user)
                        @php
                            $optionGroup = match($user->student?->member_type) {
                                'teacher' => 'academic',
                                'staff' => 'administrative',
                                default => (int) $user->work_assigned_id === (int) $academicWorkAreaId
                                    ? 'academic'
                                    : ((int) $user->work_assigned_id === (int) $administrativeWorkAreaId ? 'administrative' : ''),
                            };
                        @endphp
                        <option value="{{ $user->device_id }}" data-group="{{ $optionGroup }}" data-department="{{ $user->hajiri_department_id }}"
                                @selected((string) old('device_id') === (string) $user->device_id)>
                            {{ $user->name }} [{{ $user->device_id }}]
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    <div class="mt-4 flex justify-end">
        <button type="submit" class="rounded-xl bg-[#e2a024] px-5 py-3 text-sm font-extrabold text-white hover:bg-godawari-gold-light">
            Open Date-Range Report
        </button>
    </div>
</form>

@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
@endpush

@push('scripts')
@include('partials.nepali-date-picker')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var employeeSearchURL = {{ Illuminate\Support\Js::from(route('hajiri.report.user.search')) }};

$('#userid').select2({
    placeholder: 'Type a name or device ID to search…',
    allowClear: true,
    minimumInputLength: 0,
    ajax: {
        url: employeeSearchURL,
        dataType: 'json',
        delay: 250,
        data: function (params) {
            return { q: params.term || '' };
        },
        processResults: function (data) {
            return { results: data };
        },
        cache: true
    }
});

$('#rangeEmployee').select2({
    placeholder: 'All matching employees',
    allowClear: true,
    width: '100%'
});

var monthlyTypeURL = {{ Illuminate\Support\Js::from(route('hajiri.report.month_type', [
    'apd' => '__apd__',
    'typeid' => '__typeid__',
    'year' => '__year__',
    'month' => '__month__',
])) }};
var monthlyUserURL = {{ Illuminate\Support\Js::from(route('hajiri.report.month_user', [
    'apd' => '__apd__',
    'userid' => '__userid__',
    'year' => '__year__',
    'month' => '__month__',
])) }};

function openMonthly(url) {
    window.open(
        url.replace('__apd__', $('#typeHajiri').val())
           .replace('__year__', $('#yearHajiri').val())
           .replace('__month__', $('#monthHajiri').val()),
        '_blank'
    );
}

$('.printMonthlyByType').on('click', function () {
    openMonthly(monthlyTypeURL.replace('__typeid__', $(this).data('type')));
});

$('#printIndividual').on('click', function () {
    var employee = $('#userid').val();
    $('#monthlyError').toggleClass('hidden', !!employee);
    if (!employee) return;
    openMonthly(monthlyUserURL.replace('__userid__', employee));
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
            var visible = (selectedGroup === 'all' || option.dataset.group === selectedGroup)
                && (!selectedDepartment || option.dataset.department === selectedDepartment);
            option.hidden = !visible;
            option.disabled = !visible;
        });
        if (employee.selectedOptions[0]?.disabled) employee.value = '';
    }

    from?.addEventListener('change', syncDateLimits);
    to?.addEventListener('change', syncDateLimits);
    group?.addEventListener('change', filterEmployees);
    department?.addEventListener('change', filterEmployees);
    syncDateLimits();
    filterEmployees();
    window.initNepaliDatePickers?.();
})();
</script>
@endpush
