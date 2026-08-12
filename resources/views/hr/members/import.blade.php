@extends('hr.layouts.app')
@section('title', 'Bulk Import Members')
@section('content')

@php
    $input  = 'w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15';
    $label  = 'block text-xs font-extrabold uppercase tracking-widest text-gray-500 mb-1.5';
    $isPreview      = isset($rows);
    $isPhotoPreview = isset($photoRows);
    $previewKind = $context['import_kind'] ?? 'standard';
    $tab = $isPreview
        ? ($previewKind === 'class_list' ? 'class-list' : 'excel')
        : ($isPhotoPreview ? 'photos' : (request('tab') ?: 'excel'));
    $selectedOrg = old('organization', array_key_first($formOptions ?? []));
@endphp

<div class="space-y-6">

    {{-- Header --}}
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 sm:p-6 text-white shadow-sm">
        <p class="text-sm font-bold uppercase tracking-widest text-white/50">Human Resource</p>
        <h1 class="mt-1 text-3xl font-extrabold">Bulk Import Members</h1>
        <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">
            Import students, teachers, and staff from Excel / IEMIS export or CSV. Records sync to ID Card, Hajiri, and Learning.
        </p>
    </div>

    @if($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 space-y-1">
        @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
    @endif

    {{-- Tab switcher (hidden during preview) --}}
    @if(!$isPreview && !$isPhotoPreview)
    <div class="flex flex-wrap gap-1 bg-gray-100 rounded-xl p-1 w-fit">
        <a href="{{ route('admin.hr.members.import') }}?tab=excel"
           class="px-5 py-2 rounded-lg text-sm font-semibold transition
                  {{ $tab === 'excel' ? 'bg-white shadow text-[#1a5632]' : 'text-gray-500 hover:text-gray-700' }}">
            Excel / IEMIS
        </a>
        <a href="{{ route('admin.hr.members.import') }}?tab=csv"
           class="px-5 py-2 rounded-lg text-sm font-semibold transition
                  {{ $tab === 'csv' ? 'bg-white shadow text-[#1a5632]' : 'text-gray-500 hover:text-gray-700' }}">
            CSV
        </a>
        <a href="{{ route('admin.hr.members.import') }}?tab=class-list"
           class="px-5 py-2 rounded-lg text-sm font-semibold transition
                  {{ $tab === 'class-list' ? 'bg-white shadow text-[#1a5632]' : 'text-gray-500 hover:text-gray-700' }}">
            Class List
        </a>
        <a href="{{ route('admin.hr.members.import') }}?tab=photos"
           class="px-5 py-2 rounded-lg text-sm font-semibold transition
                  {{ $tab === 'photos' ? 'bg-white shadow text-[#1a5632]' : 'text-gray-500 hover:text-gray-700' }}">
            Bulk Photos (ZIP)
        </a>
    </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════════════
         PREVIEW STEP (shared by Excel and CSV)
    ═════════════════════════════════════════════════════════════════════ --}}
    @if($isPreview)
    @php
        $valid   = collect($rows)->where('error', null);
        $invalid = collect($rows)->whereNotNull('error');
    @endphp

    <div class="flex items-center gap-3 text-sm text-gray-500">
        <a href="{{ route('admin.hr.members.import', ['tab' => $previewKind === 'class_list' ? 'class-list' : 'excel']) }}" class="hover:text-[#1a5632]">← Back to upload</a>
        <span class="text-gray-300">|</span>
        <span>Preview — {{ $context['organization'] }} / {{ $context['stream'] ?: 'All streams' }} / {{ $context['section'] ?: 'All sections' }}</span>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-extrabold text-gray-950">Review before importing</h2>
            <div class="flex gap-4 text-sm">
                <span class="text-emerald-600 font-bold">✓ {{ $valid->count() }} ready</span>
                @if($invalid->count())
                <span class="text-red-500 font-bold">✗ {{ $invalid->count() }} errors (skipped)</span>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto max-h-[28rem] overflow-y-auto border border-gray-100 rounded-xl">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-3 py-2 text-left text-gray-500 font-semibold">#</th>
                        <th class="px-3 py-2 text-left text-gray-500 font-semibold">{{ $previewKind === 'class_list' ? 'Admission No → ERP Roll' : 'Roll' }}</th>
                        @if($previewKind === 'class_list')
                        <th class="px-3 py-2 text-left text-gray-500 font-semibold">Sheet Roll</th>
                        @endif
                        <th class="px-3 py-2 text-left text-gray-500 font-semibold">Full Name</th>
                        <th class="px-3 py-2 text-left text-gray-500 font-semibold">DOB (BS)</th>
                        @if($previewKind === 'class_list')
                        <th class="px-3 py-2 text-left text-gray-500 font-semibold">DOB (AD)</th>
                        <th class="px-3 py-2 text-left text-gray-500 font-semibold">Gender</th>
                        <th class="px-3 py-2 text-left text-gray-500 font-semibold">Section</th>
                        @endif
                        <th class="px-3 py-2 text-left text-gray-500 font-semibold">Mobile</th>
                        @if($previewKind !== 'class_list')
                        <th class="px-3 py-2 text-left text-gray-500 font-semibold">Father</th>
                        @endif
                        <th class="px-3 py-2 text-left text-gray-500 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($rows as $row)
                    <tr class="{{ $row['error'] ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                        <td class="px-3 py-2 text-gray-400">{{ $row['line'] }}</td>
                        <td class="px-3 py-2 font-mono font-semibold">{{ $row['roll_number'] }}</td>
                        @if($previewKind === 'class_list')
                        <td class="px-3 py-2 font-mono text-gray-500">{{ $row['source_roll_number'] }}</td>
                        @endif
                        <td class="px-3 py-2">{{ trim("{$row['first_name']} {$row['middle_name']} {$row['last_name']}") }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $row['dob_bs'] }}</td>
                        @if($previewKind === 'class_list')
                        <td class="px-3 py-2 text-gray-500">{{ $row['dob'] }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $row['gender'] }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $context['section'] }}</td>
                        @endif
                        <td class="px-3 py-2 text-gray-500">{{ $row['mobile'] }}</td>
                        @if($previewKind !== 'class_list')
                        <td class="px-3 py-2 text-gray-500">{{ $row['father_name'] }}</td>
                        @endif
                        <td class="px-3 py-2">
                            @if($row['error'])
                                <span class="text-red-500 font-semibold">✗ {{ $row['error'] }}</span>
                            @else
                                <span class="text-emerald-600 font-bold">✓ Ready</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($valid->count())
        <form method="POST" action="{{ route('admin.hr.members.import.confirm') }}">
            @csrf
            <div class="flex gap-3 pt-3 border-t border-gray-100">
                <button type="submit"
                        class="rounded-xl bg-[#1a5632] px-6 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415]">
                    Import {{ $valid->count() }} Member(s)
                </button>
                <a href="{{ route('admin.hr.members.import') }}"
                   class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
            </div>
        </form>
        @else
        <div class="pt-3 border-t border-gray-100">
            <p class="text-sm text-red-500 mb-3">No valid rows to import. Fix your file and try again.</p>
            <a href="{{ route('admin.hr.members.import', ['tab' => $previewKind === 'class_list' ? 'class-list' : 'excel']) }}" class="text-sm text-[#1a5632] hover:underline">← Back</a>
        </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════════════════
         PHOTO PREVIEW STEP
    ═════════════════════════════════════════════════════════════════════ --}}
    @elseif($isPhotoPreview)
    @php
        $toAdd     = collect($photoRows)->where('action', 'add');
        $toReplace = collect($photoRows)->where('action', 'replace');
        $noMatch   = collect($photoRows)->where('action', 'no_match');
    @endphp

    <div class="flex items-center gap-3 text-sm text-gray-500">
        <a href="{{ route('admin.hr.members.import') }}?tab=photos" class="hover:text-[#1a5632]">← Back to upload</a>
        <span class="text-gray-300">|</span>
        <span>Photo Preview</span>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-center">
            <p class="text-2xl font-bold text-emerald-700">{{ $toAdd->count() }}</p>
            <p class="text-xs text-emerald-600 font-semibold mt-1">New Photos</p>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-center">
            <p class="text-2xl font-bold text-amber-700">{{ $toReplace->count() }}</p>
            <p class="text-xs text-amber-600 font-semibold mt-1">Replacing Existing</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $noMatch->count() }}</p>
            <p class="text-xs text-red-500 font-semibold mt-1">No Member Found (skipped)</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.hr.members.import.photos.confirm') }}">
        @csrf
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
            @if($toAdd->count() + $toReplace->count() > 0)
            <div class="overflow-x-auto max-h-96 overflow-y-auto border border-gray-100 rounded-xl">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 sticky top-0">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs text-gray-500">Photo</th>
                            <th class="px-4 py-2.5 text-left text-xs text-gray-500">Roll</th>
                            <th class="px-4 py-2.5 text-left text-xs text-gray-500">Member</th>
                            <th class="px-4 py-2.5 text-left text-xs text-gray-500">Action</th>
                            <th class="px-4 py-2.5 text-center text-xs text-gray-500">Skip?</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($photoRows as $row)
                        @if($row['action'] !== 'no_match')
                        <tr>
                            <td class="px-4 py-2">
                                <img src="{{ $row['temp_url'] }}" class="h-12 w-10 object-cover rounded">
                            </td>
                            <td class="px-4 py-2 font-mono text-xs font-semibold">{{ $row['roll'] }}</td>
                            <td class="px-4 py-2 text-sm">{{ $row['student_name'] }}</td>
                            <td class="px-4 py-2">
                                @if($row['action'] === 'replace')
                                <span class="text-xs font-semibold text-amber-600">Replace</span>
                                @else
                                <span class="text-xs font-semibold text-emerald-600">Add</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                <input type="checkbox" name="skip[]" value="{{ $row['roll'] }}">
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex gap-3 pt-3 border-t border-gray-100">
                <button type="submit"
                        class="rounded-xl bg-[#1a5632] px-6 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415]">
                    Confirm ({{ $toAdd->count() + $toReplace->count() }} photos)
                </button>
                <a href="{{ route('admin.hr.members.import') }}?tab=photos"
                   class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
            </div>
            @else
            <p class="text-sm text-red-500">No matching members found for any photos in the ZIP.</p>
            <a href="{{ route('admin.hr.members.import') }}?tab=photos" class="text-sm text-[#1a5632] hover:underline">← Back</a>
            @endif
        </div>
    </form>

    {{-- ════════════════════════════════════════════════════════════════════
         UPLOAD FORMS (Excel / CSV / Photos)
    ═════════════════════════════════════════════════════════════════════ --}}
    @else

    {{-- Shared org/stream/section selector template --}}
    @php
        $selectorFields = fn(string $prefix) => view()->make('hr.members._import_selectors', [
            'prefix' => $prefix, 'input' => $input, 'label' => $label,
            'formOptions' => $formOptions, 'selectedOrg' => $selectedOrg,
        ])->render();
    @endphp

    @if($tab === 'excel')
    {{-- ─── Excel / IEMIS Tab ─────────────────────────────────────────── --}}
    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
        <form method="POST" action="{{ route('admin.hr.members.import.xlsx.preview') }}" enctype="multipart/form-data"
              class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-5" x-data="hrImportSelectors(@js($formOptions), @js($selectedOrg))">
            @csrf
            <h2 class="text-lg font-extrabold text-gray-950">Excel / IEMIS Import</h2>
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-3 text-xs text-blue-800 space-y-1">
                <p class="font-semibold">Upload your IEMIS .xlsx export directly — columns are auto-mapped.</p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-0.5 mt-1 font-mono text-blue-700">
                    <span>FullName → first/middle/last name</span><span>Student Id → Registration No</span>
                    <span>DOB → BS date (auto-converts to AD)</span><span>S.N → Roll (auto if missing)</span>
                    <span>Father Name / Mother Name / Gender</span><span>Guardian Name + Contact Number</span>
                    <span>Permanent Address → address</span><span>Year → Batch</span>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="{{ $label }}">Default Member Type</label>
                    <select name="member_type" class="{{ $input }}">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher / Academic</option>
                        <option value="staff">Staff / Administrative</option>
                    </select></div>
                <div><label class="{{ $label }}">Organization</label>
                    <select name="organization" x-model="organization" class="{{ $input }}">
                        @foreach($formOptions as $slug => $org)<option value="{{ $slug }}">{{ $org['label'] }}</option>@endforeach
                    </select></div>
                <div><label class="{{ $label }}">Class / Stream <span class="text-red-500">*</span></label>
                    <select name="stream" x-model="stream" required class="{{ $input }}">
                        <option value="">-- Select --</option>
                        <template x-for="s in streams" :key="s"><option :value="s" x-text="s"></option></template>
                    </select></div>
                <div><label class="{{ $label }}">Section <span class="text-red-500">*</span></label>
                    <select name="section" x-model="section" required class="{{ $input }}">
                        <option value="">-- Select --</option>
                        <template x-for="s in sections" :key="s"><option :value="s" x-text="s"></option></template>
                    </select></div>
                <div><label class="{{ $label }}">Excel File (.xlsx / .xls / .ods)</label>
                    <input type="file" name="xlsx_file" required accept=".xlsx,.xls,.ods,.csv"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#1a5632] file:px-4 file:py-2 file:text-sm file:font-bold file:text-white"></div>
                <div><label class="{{ $label }}">Employee Category</label>
                    <select name="employee_category" class="{{ $input }}">
                        <option value="">Use CSV / none</option>
                        <option value="academic">Academic</option>
                        <option value="administrative">Administrative</option>
                    </select></div>
            </div>
            @include('hr.members._import_hajiri_fields', ['input' => $input, 'label' => $label, 'hajiriOptions' => $hajiriOptions])
            <div class="flex gap-3 pt-3 border-t border-gray-100">
                <button class="rounded-xl bg-[#1a5632] px-6 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415]">Preview Import →</button>
                <a href="{{ route('admin.hr.members.index') }}" class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">Cancel</a>
            </div>
        </form>
        @include('hr.members._import_sidebar', ['hajiriOptions' => $hajiriOptions])
    </div>

    @elseif($tab === 'csv')
    {{-- ─── CSV Tab ────────────────────────────────────────────────────── --}}
    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
        <form method="POST" action="{{ route('admin.hr.members.import.csv.preview') }}" enctype="multipart/form-data"
              class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-5" x-data="hrImportSelectors(@js($formOptions), @js($selectedOrg))">
            @csrf
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-extrabold text-gray-950">CSV Import</h2>
                <a href="{{ route('admin.hr.members.template') }}" class="text-xs text-[#1a5632] hover:underline font-semibold">↓ Download Template</a>
            </div>
            <div class="rounded-xl border border-amber-100 bg-amber-50 p-3 text-xs text-amber-800">
                <p class="font-semibold">Required columns: <span class="font-mono">roll_number, first_name, last_name</span></p>
                <p class="mt-1">Optional: <span class="font-mono">dob_bs, dob, gender, father_name, mother_name, guardian_name, guardian_contact, mobile, email, stream, section, member_type, registration_no, address_en, …</span></p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="{{ $label }}">Default Member Type</label>
                    <select name="member_type" class="{{ $input }}">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher / Academic</option>
                        <option value="staff">Staff / Administrative</option>
                    </select></div>
                <div><label class="{{ $label }}">Organization</label>
                    <select name="organization" x-model="organization" class="{{ $input }}">
                        @foreach($formOptions as $slug => $org)<option value="{{ $slug }}">{{ $org['label'] }}</option>@endforeach
                    </select></div>
                <div><label class="{{ $label }}">Class / Stream <span class="text-red-500">*</span></label>
                    <select name="stream" x-model="stream" required class="{{ $input }}">
                        <option value="">-- Select --</option>
                        <template x-for="s in streams" :key="s"><option :value="s" x-text="s"></option></template>
                    </select></div>
                <div><label class="{{ $label }}">Section <span class="text-red-500">*</span></label>
                    <select name="section" x-model="section" required class="{{ $input }}">
                        <option value="">-- Select --</option>
                        <template x-for="s in sections" :key="s"><option :value="s" x-text="s"></option></template>
                    </select></div>
                <div><label class="{{ $label }}">CSV File</label>
                    <input type="file" name="csv_file" required accept=".csv,text/csv,text/plain"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#1a5632] file:px-4 file:py-2 file:text-sm file:font-bold file:text-white"></div>
                <div><label class="{{ $label }}">Employee Category</label>
                    <select name="employee_category" class="{{ $input }}">
                        <option value="">Use CSV / none</option>
                        <option value="academic">Academic</option>
                        <option value="administrative">Administrative</option>
                    </select></div>
            </div>
            @include('hr.members._import_hajiri_fields', ['input' => $input, 'label' => $label, 'hajiriOptions' => $hajiriOptions])
            <div class="flex gap-3 pt-3 border-t border-gray-100">
                <button class="rounded-xl bg-[#1a5632] px-6 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415]">Preview Import →</button>
                <a href="{{ route('admin.hr.members.index') }}" class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">Cancel</a>
            </div>
        </form>
        @include('hr.members._import_sidebar', ['hajiriOptions' => $hajiriOptions])
    </div>

    @elseif($tab === 'class-list')
    {{-- ─── School Class List Tab ─────────────────────────────────────── --}}
    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
        <form method="POST" action="{{ route('admin.hr.members.import.class-list.preview') }}" enctype="multipart/form-data"
              class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-5" x-data="hrImportSelectors(@js($formOptions), @js($selectedOrg))">
            @csrf
            <h2 class="text-lg font-extrabold text-gray-950">Class / Section Student Import</h2>
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-xs text-emerald-900 space-y-1">
                <p class="font-bold">Made for school class-list Excel files with school details above the table.</p>
                <p><span class="font-mono font-bold">Admission No</span> becomes the ERP <span class="font-mono font-bold">Roll Number</span>. The sheet's separate “Roll No” is shown in preview for checking but is not saved as the ERP roll.</p>
                <p>The importer automatically finds the heading row and reads Name, DOB BS/AD, Gender and Contact No. The sheet's Section column is ignored.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="{{ $label }}">Organization <span class="text-red-500">*</span></label>
                    <select name="organization" x-model="organization" required class="{{ $input }}">
                        @foreach($formOptions as $slug => $org)<option value="{{ $slug }}">{{ $org['label'] }}</option>@endforeach
                    </select></div>
                <div><label class="{{ $label }}">Class / Stream <span class="text-red-500">*</span></label>
                    <select name="stream" x-model="stream" required class="{{ $input }}">
                        <option value="">-- Select class --</option>
                        <template x-for="s in streams" :key="s"><option :value="s" x-text="s"></option></template>
                    </select></div>
                <div><label class="{{ $label }}">Section <span class="text-red-500">*</span></label>
                    <select name="section" x-model="section" required class="{{ $input }}">
                        <option value="">-- Select section --</option>
                        <template x-for="s in sections" :key="s"><option :value="s" x-text="s"></option></template>
                    </select></div>
                <div><label class="{{ $label }}">Class List File (.xls / .xlsx / .ods)</label>
                    <input type="file" name="class_list_file" required accept=".xlsx,.xls,.ods,.csv"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#1a5632] file:px-4 file:py-2 file:text-sm file:font-bold file:text-white"></div>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                Nothing is saved yet. The next screen shows every row, highlights errors in red, and imports only rows marked Ready after confirmation.
            </div>
            <div class="flex gap-3 pt-3 border-t border-gray-100">
                <button class="rounded-xl bg-[#1a5632] px-6 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415]">Preview &amp; Validate →</button>
                <a href="{{ route('admin.hr.members.index') }}" class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">Cancel</a>
            </div>
        </form>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-extrabold text-gray-950">Expected headings</h2>
            <div class="mt-3 space-y-2 text-sm text-gray-600">
                <p><strong>Required:</strong> Name, Admission No</p>
                <p><strong>Optional:</strong> Roll No, Date Of Birth BS, Date Of Birth AD, Gender, House, Fee Category, Student Category, Contact No</p>
                <p><strong>Section:</strong> Always taken from the section selected in this upload form. Any Section value in Excel is ignored.</p>
                <p class="rounded-lg bg-gray-50 p-3 text-xs">Rows 1–50 may contain the school name, address, PAN, contact details, or blank lines. They are safely skipped.</p>
            </div>
        </div>
    </div>

    @elseif($tab === 'photos')
    {{-- ─── Photos Tab ─────────────────────────────────────────────────── --}}
    <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
        <form method="POST" action="{{ route('admin.hr.members.import.photos.preview') }}" enctype="multipart/form-data"
              class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-5" x-data="hrImportSelectors(@js($formOptions), @js($selectedOrg))">
            @csrf
            <h2 class="text-lg font-extrabold text-gray-950">Bulk Photo Import (ZIP)</h2>
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-3 text-xs text-blue-800">
                <p class="font-semibold">Name each image file after the member's Roll Number.</p>
                <p class="mt-1">Example: <span class="font-mono">1.jpg, 2.png, ST-001.jpg</span> — then zip them all together.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="{{ $label }}">Organization</label>
                    <select name="organization" x-model="organization" class="{{ $input }}">
                        @foreach($formOptions as $slug => $org)<option value="{{ $slug }}">{{ $org['label'] }}</option>@endforeach
                    </select></div>
                <div><label class="{{ $label }}">Class / Stream (optional filter)</label>
                    <select name="stream" x-model="stream" class="{{ $input }}">
                        <option value="">All streams</option>
                        <template x-for="s in streams" :key="s"><option :value="s" x-text="s"></option></template>
                    </select></div>
                <div><label class="{{ $label }}">Section (optional filter)</label>
                    <select name="section" x-model="section" class="{{ $input }}">
                        <option value="">All sections</option>
                        <template x-for="s in sections" :key="s"><option :value="s" x-text="s"></option></template>
                    </select></div>
                <div><label class="{{ $label }}">ZIP File</label>
                    <input type="file" name="zip_file" required accept=".zip"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#1a5632] file:px-4 file:py-2 file:text-sm file:font-bold file:text-white"></div>
            </div>
            <div class="flex gap-3 pt-3 border-t border-gray-100">
                <button class="rounded-xl bg-[#1a5632] px-6 py-2.5 text-sm font-extrabold text-white hover:bg-[#0b2415]">Preview Photos →</button>
                <a href="{{ route('admin.hr.members.index') }}" class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">Cancel</a>
            </div>
        </form>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-extrabold text-gray-950">Instructions</h2>
            <ol class="mt-3 space-y-2 text-sm font-medium text-gray-600 list-decimal list-inside">
                <li>Rename each photo to the member's <strong>Roll Number</strong> (e.g. <code>1.jpg</code>)</li>
                <li>Select all photos and create a ZIP archive</li>
                <li>Choose the Organization (and optionally Stream/Section) to narrow the match</li>
                <li>Upload and preview — you can skip individual photos before confirming</li>
            </ol>
        </div>
    </div>
    @endif

    @endif {{-- end upload forms --}}
</div>

@push('scripts')
<script>
function hrImportSelectors(options, defaultOrg) {
    return {
        options,
        organization: defaultOrg,
        stream: '',
        section: '',
        get streams() { return Object.keys(this.options[this.organization]?.streams || {}); },
        get sections() { return this.options[this.organization]?.streams?.[this.stream] || []; },
        init() {
            this.$watch('organization', () => { this.stream = ''; this.section = ''; });
            this.$watch('stream', () => { this.section = ''; });
        }
    };
}
</script>
@endpush
@endsection
