@extends('card.layouts.app')
@section('title', 'Import Members')
@section('heading', 'Import Members')

@section('content')
<div class="max-w-5xl space-y-6">

    {{-- ── Alerts ───────────────────────────────────────────────────────── --}}
    @if(isset($errors) && $errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl px-5 py-4 text-sm text-red-700 space-y-1">
        @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
    </div>
    @endif

    @php
        // Which tab and which step are we in?
        $isCsvPreview   = isset($rows);
        $isPhotoPreview = isset($photoRows);
        $tab = $isCsvPreview ? 'csv' : ($isPhotoPreview ? 'photos' : (request('tab') ?: 'excel'));
        $selectedOrg = old('organization', array_key_first($formOptions ?? []) ?: 'college');
    @endphp

    {{-- ── Tab switcher (hidden during preview steps) ─────────────────── --}}
    @if(!$isCsvPreview && !$isPhotoPreview)
    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit">
        <a href="{{ route('import.index') }}?tab=excel"
           class="px-5 py-2 rounded-lg text-sm font-medium transition
                  {{ $tab === 'excel' ? 'bg-white shadow text-primary' : 'text-gray-500 hover:text-gray-700' }}">
            Excel / IEMIS Import
        </a>
        <a href="{{ route('import.index') }}?tab=csv"
           class="px-5 py-2 rounded-lg text-sm font-medium transition
                  {{ $tab === 'csv' ? 'bg-white shadow text-primary' : 'text-gray-500 hover:text-gray-700' }}">
            CSV Import
        </a>
        <a href="{{ route('import.index') }}?tab=photos"
           class="px-5 py-2 rounded-lg text-sm font-medium transition
                  {{ $tab === 'photos' ? 'bg-white shadow text-primary' : 'text-gray-500 hover:text-gray-700' }}">
            Bulk Photos (ZIP)
        </a>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════
         EXCEL / IEMIS TAB
    ════════════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'excel' && !$isCsvPreview && !$isPhotoPreview)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
        <h2 class="font-semibold text-primary text-sm uppercase tracking-wide">Excel / IEMIS Import</h2>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800 space-y-1.5">
            <p class="font-semibold">Upload your IEMIS Excel (.xlsx) export directly — no column renaming needed.</p>
            <p>Recognized columns are mapped automatically:</p>
            <div class="grid grid-cols-2 gap-x-6 gap-y-0.5 mt-1 font-mono text-blue-700">
                <span>FullName → first/middle/last name</span>
                <span>Student Id → Registration No</span>
                <span>DOB → BS Date (dob_bs)</span>
                <span>S.N → Roll Number (auto if missing)</span>
                <span>Father Name / Mother Name</span>
                <span>Guardian Name / Contact Number</span>
                <span>Gender, Permanent Address</span>
                <span>Year → Batch</span>
            </div>
            <p class="mt-1 text-blue-600">Unknown columns are simply ignored. DOB is treated as Nepali (BS) date.</p>
        </div>

        <form method="POST" action="{{ route('import.xlsx.preview') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Organization <span class="text-red-500">*</span></label>
                    <select name="organization" id="xlsxOrgSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                        @foreach($formOptions as $slug => $organization)
                            <option value="{{ $slug }}" @selected($selectedOrg === $slug)>{{ $organization['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Member Type <span class="text-red-500">*</span></label>
                    <select name="member_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Stream / Class</label>
                    <select name="stream" id="xlsxStreamSelect"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"></select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Section</label>
                    <select name="section" id="xlsxSectionSelect"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"></select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Valid Till (all rows)</label>
                    <input type="date" name="valid_till"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Excel File (.xlsx / .xls) <span class="text-red-500">*</span></label>
                    <input type="file" name="xlsx_file" accept=".xlsx,.xls,.ods,.csv" required
                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-primary file:text-white hover:file:bg-primary-light">
                </div>
            </div>
            <button type="submit"
                    class="bg-primary text-white px-8 py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-light transition">
                Preview Import →
            </button>
        </form>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════
         CSV TAB
    ════════════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'csv' || $isCsvPreview)

      @if($isCsvPreview)
      {{-- ── CSV / Excel STEP 2: Preview (shared) ────────────────────── --}}
      @php
          $valid   = collect($rows)->where('error', null);
          $invalid = collect($rows)->whereNotNull('error');
      @endphp
      <div class="flex items-center gap-3 text-sm text-gray-500">
          <a href="{{ route('import.index') }}" class="hover:text-primary">← Back to upload</a>
          <span class="text-gray-300">|</span>
          <span>Preview</span>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">
          <div class="flex items-center justify-between">
              <h2 class="font-semibold text-primary text-sm uppercase tracking-wide">Preview — Review before importing</h2>
              <div class="flex gap-4 text-sm">
                  <span class="text-emerald-600 font-semibold">✓ {{ $valid->count() }} ready</span>
                  @if($invalid->count())
                  <span class="text-red-500 font-semibold">✗ {{ $invalid->count() }} errors (will be skipped)</span>
                  @endif
              </div>
          </div>

          <div class="text-xs text-gray-500 bg-gray-50 rounded-lg p-3 flex flex-wrap gap-4">
              <span><strong>Org:</strong> {{ $context['org'] }}</span>
              <span><strong>Type:</strong> {{ $context['type'] }}</span>
              @if($context['stream']) <span><strong>Stream:</strong> {{ $context['stream'] }}</span> @endif
              @if($context['section']) <span><strong>Section:</strong> {{ $context['section'] }}</span> @endif
              @if($context['validTill']) <span><strong>Valid Till:</strong> {{ $context['validTill'] }}</span> @endif
              @if($context['createLearningAccounts'] ?? false) <span><strong>Portal Login:</strong> Enabled</span> @endif
          </div>

          <div class="overflow-x-auto max-h-96 overflow-y-auto border border-gray-100 rounded-lg">
              <table class="w-full text-xs border-collapse">
                  <thead class="bg-gray-50 sticky top-0 z-10">
                      <tr>
                          <th class="px-3 py-2 text-left text-gray-500 font-semibold">Line</th>
                          <th class="px-3 py-2 text-left text-gray-500 font-semibold">Roll</th>
                          <th class="px-3 py-2 text-left text-gray-500 font-semibold">Full Name</th>
                          <th class="px-3 py-2 text-left text-gray-500 font-semibold">DOB</th>
                          <th class="px-3 py-2 text-left text-gray-500 font-semibold">Mobile</th>
                          <th class="px-3 py-2 text-left text-gray-500 font-semibold">Status</th>
                      </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-50">
                      @foreach($rows as $row)
                      <tr class="{{ $row['error'] ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                          <td class="px-3 py-2 text-gray-400">{{ $row['line'] }}</td>
                          <td class="px-3 py-2 font-mono font-semibold">{{ $row['roll_number'] }}</td>
                          <td class="px-3 py-2">{{ trim("{$row['first_name']} {$row['middle_name']} {$row['last_name']}") }}</td>
                          <td class="px-3 py-2">{{ $row['dob'] }}</td>
                          <td class="px-3 py-2">{{ $row['mobile'] }}</td>
                          <td class="px-3 py-2">
                              @if($row['error'])
                                  <span class="text-red-500 font-medium">✗ {{ $row['error'] }}</span>
                              @else
                                  <span class="text-emerald-600 font-semibold">✓ Ready</span>
                              @endif
                          </td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
          </div>

          @if($valid->count())
          <form method="POST" action="{{ route('import.csv.confirm') }}">
              @csrf
              <div class="flex gap-3 pt-2 border-t border-gray-100">
                  <button type="submit"
                          class="bg-primary text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-light transition">
                      Import {{ $valid->count() }} Valid Row(s)
                  </button>
                  <a href="{{ route('import.index') }}"
                     class="bg-gray-100 text-gray-600 px-5 py-2.5 rounded-lg text-sm hover:bg-gray-200 transition">
                      Cancel
                  </a>
              </div>
          </form>
          @else
          <div class="pt-2 border-t border-gray-100">
              <p class="text-sm text-red-500 mb-3">No valid rows to import. Fix your CSV and try again.</p>
              <a href="{{ route('import.index') }}" class="text-sm text-primary hover:underline">← Back</a>
          </div>
          @endif
      </div>

      @else
      {{-- ── CSV STEP 1: Upload form ──────────────────────────────────── --}}
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
          <div class="flex items-center justify-between">
              <h2 class="font-semibold text-primary text-sm uppercase tracking-wide">Upload CSV File</h2>
              <a href="{{ route('import.template') }}"
                 class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                  </svg>
                  Download Template
              </a>
          </div>

          <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800 space-y-1">
              <p class="font-semibold">Expected CSV headers:</p>
              <p class="font-mono text-gray-600">roll_number, first_name, middle_name, last_name, dob, mobile, email, citizenship_no, designation, employment_type, program, batch</p>
              <p class="mt-1">All rows in one CSV share the same <strong>Organization, Stream, Section &amp; Valid Till</strong> selected below.<br>
              DOB accepts any common format: <code>2005-01-15</code>, <code>15/01/2005</code>, <code>15-01-2005</code>, etc.</p>
          </div>

          <form method="POST" action="{{ route('import.csv.preview') }}" enctype="multipart/form-data" class="space-y-5">
              @csrf
              <div class="grid grid-cols-2 gap-4">
                  <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Organization <span class="text-red-500">*</span></label>
                      <select name="organization" id="csvOrgSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                          @foreach($formOptions as $slug => $organization)
                              <option value="{{ $slug }}" @selected($selectedOrg === $slug)>{{ $organization['label'] }}</option>
                          @endforeach
                      </select>
                  </div>
                  <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Member Type <span class="text-red-500">*</span></label>
                      <select name="member_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                          <option value="student">Student</option>
                          <option value="teacher">Teacher</option>
                          <option value="staff">Staff</option>
                      </select>
                  </div>
                  <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Stream / Class</label>
                      <select name="stream" id="csvStreamSelect"
                             class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"></select>
                  </div>
                  <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Section</label>
                      <select name="section" id="csvSectionSelect"
                             class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"></select>
                  </div>
                  <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Valid Till (all rows)</label>
                      <input type="date" name="valid_till"
                             class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                  </div>
                  <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">CSV File <span class="text-red-500">*</span></label>
                      <input type="file" name="csv_file" accept=".csv,.txt" required
                             class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-primary file:text-white hover:file:bg-primary-light">
                  </div>
              </div>
              <div class="rounded-xl border border-green-100 bg-green-50 p-4">
                  <label class="flex items-start gap-3 cursor-pointer">
                      <input type="checkbox" name="create_learning_accounts" value="1" class="mt-1 accent-primary" id="csvLearningAccounts">
                      <span>
                          <span class="block text-sm font-semibold text-green-900">Enable portal login for imported students/teachers</span>
                          <span class="block text-xs text-green-700 mt-0.5">User ID will be Roll / ID Number. Students enter the learning portal; teachers can be given learning resource permissions.</span>
                      </span>
                  </label>
                  <div id="csvLearningPasswordFields" class="hidden mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                      <div>
                          <label class="block text-xs font-medium text-green-800 mb-1">Default Password</label>
                          <input type="password" name="learning_password"
                                 placeholder="Leave blank to use Roll / ID Number"
                                 class="w-full border border-green-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-200 focus:border-primary bg-white">
                      </div>
                      <div>
                          <label class="block text-xs font-medium text-green-800 mb-1">Confirm Password</label>
                          <input type="password" name="learning_password_confirmation"
                                 class="w-full border border-green-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-200 focus:border-primary bg-white">
                      </div>
                  </div>
              </div>
              <button type="submit"
                      class="bg-primary text-white px-8 py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-light transition">
                  Preview Import →
              </button>
          </form>
      </div>
      @endif

    @endif

    {{-- ═══════════════════════════════════════════════════════════════════
         PHOTOS TAB
    ════════════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'photos' || $isPhotoPreview)

      @if($isPhotoPreview)
      {{-- ── PHOTO STEP 2: Preview ────────────────────────────────────── --}}
      @php
          $toAdd     = collect($photoRows)->where('action', 'add');
          $toReplace = collect($photoRows)->where('action', 'replace');
          $noMatch   = collect($photoRows)->where('action', 'no_match');
      @endphp

      <div class="flex items-center gap-3 text-sm text-gray-500">
          <a href="{{ route('import.index') }}?tab=photos" class="hover:text-primary">← Back to upload</a>
          <span class="text-gray-300">|</span>
          <span>Photo Preview</span>
      </div>

      {{-- Summary cards --}}
      <div class="grid grid-cols-3 gap-4">
          <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-emerald-700">{{ $toAdd->count() }}</p>
              <p class="text-xs text-emerald-600 font-medium mt-1">New Photos (will be added)</p>
          </div>
          <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-amber-700">{{ $toReplace->count() }}</p>
              <p class="text-xs text-amber-600 font-medium mt-1">Existing Photos (will be replaced)</p>
          </div>
          <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-red-600">{{ $noMatch->count() }}</p>
              <p class="text-xs text-red-500 font-medium mt-1">No Student Found (will be skipped)</p>
          </div>
      </div>

      <form method="POST" action="{{ route('import.photos.confirm') }}">
          @csrf

          {{-- ── New Photos section ──────────────────────────────────── --}}
          @if($toAdd->count())
          <div class="bg-white rounded-xl border border-emerald-100 shadow-sm overflow-hidden">
              <div class="bg-emerald-50 px-5 py-3 border-b border-emerald-100">
                  <h3 class="font-semibold text-emerald-700 text-sm">New Photos — {{ $toAdd->count() }} students</h3>
              </div>
              <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                      <thead class="bg-gray-50 border-b border-gray-100">
                          <tr>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">New Photo</th>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">Roll</th>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">Student</th>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">Filename</th>
                              <th class="px-4 py-2.5 text-center text-xs text-gray-500">Skip?</th>
                          </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-50">
                          @foreach($toAdd as $row)
                          <tr class="hover:bg-gray-50">
                              <td class="px-4 py-2.5">
                                  <img src="{{ $row['temp_url'] }}" alt="photo"
                                       class="w-10 h-12 object-cover rounded border border-gray-200">
                              </td>
                              <td class="px-4 py-2.5 font-mono text-xs font-semibold text-gray-700">{{ $row['roll'] }}</td>
                              <td class="px-4 py-2.5 font-medium text-gray-800">{{ $row['student_name'] }}</td>
                              <td class="px-4 py-2.5 text-xs text-gray-500">{{ $row['filename'] }}</td>
                              <td class="px-4 py-2.5 text-center">
                                  <input type="checkbox" name="skip[]" value="{{ $row['roll'] }}" class="accent-red-500">
                              </td>
                          </tr>
                          @endforeach
                      </tbody>
                  </table>
              </div>
          </div>
          @endif

          {{-- ── Replace Photos section ──────────────────────────────── --}}
          @if($toReplace->count())
          <div class="bg-white rounded-xl border border-amber-100 shadow-sm overflow-hidden mt-4">
              <div class="bg-amber-50 px-5 py-3 border-b border-amber-100">
                  <h3 class="font-semibold text-amber-700 text-sm">Replace Existing Photos — {{ $toReplace->count() }} students</h3>
              </div>
              <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                      <thead class="bg-gray-50 border-b border-gray-100">
                          <tr>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">New Photo</th>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">Current Photo</th>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">Roll</th>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">Student</th>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">Filename</th>
                              <th class="px-4 py-2.5 text-center text-xs text-gray-500">Skip?</th>
                          </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-50">
                          @foreach($toReplace as $row)
                          <tr class="hover:bg-gray-50">
                              <td class="px-4 py-2.5">
                                  <img src="{{ $row['temp_url'] }}" alt="new"
                                       class="w-10 h-12 object-cover rounded border-2 border-emerald-400">
                              </td>
                              <td class="px-4 py-2.5">
                                  <img src="{{ $row['current_photo'] }}" alt="current"
                                       class="w-10 h-12 object-cover rounded border border-gray-200 opacity-60">
                              </td>
                              <td class="px-4 py-2.5 font-mono text-xs font-semibold text-gray-700">{{ $row['roll'] }}</td>
                              <td class="px-4 py-2.5 font-medium text-gray-800">{{ $row['student_name'] }}</td>
                              <td class="px-4 py-2.5 text-xs text-gray-500">{{ $row['filename'] }}</td>
                              <td class="px-4 py-2.5 text-center">
                                  <input type="checkbox" name="skip[]" value="{{ $row['roll'] }}" class="accent-red-500">
                              </td>
                          </tr>
                          @endforeach
                      </tbody>
                  </table>
              </div>
          </div>
          @endif

          {{-- ── No Match section ────────────────────────────────────── --}}
          @if($noMatch->count())
          <div class="bg-white rounded-xl border border-red-100 shadow-sm overflow-hidden mt-4">
              <div class="bg-red-50 px-5 py-3 border-b border-red-100">
                  <h3 class="font-semibold text-red-600 text-sm">No Student Found — {{ $noMatch->count() }} files (will be skipped)</h3>
              </div>
              <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                      <thead class="bg-gray-50 border-b border-gray-100">
                          <tr>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">File</th>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">Roll (from filename)</th>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">Preview</th>
                              <th class="px-4 py-2.5 text-left text-xs text-gray-500">Reason</th>
                          </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-50">
                          @foreach($noMatch as $row)
                          <tr class="hover:bg-red-50/40">
                              <td class="px-4 py-2.5 text-xs text-gray-500 font-mono">{{ $row['filename'] }}</td>
                              <td class="px-4 py-2.5 font-mono text-xs font-semibold text-red-600">{{ $row['roll'] }}</td>
                              <td class="px-4 py-2.5">
                                  <img src="{{ $row['temp_url'] }}" alt="photo"
                                       class="w-10 h-12 object-cover rounded border border-gray-200 opacity-50">
                              </td>
                              <td class="px-4 py-2.5 text-xs text-red-400">No student with roll "{{ $row['roll'] }}" in selected group</td>
                          </tr>
                          @endforeach
                      </tbody>
                  </table>
              </div>
          </div>
          @endif

          <div class="flex gap-3 pt-4">
              @if($toAdd->count() + $toReplace->count() > 0)
              <button type="submit"
                      class="bg-emerald-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-emerald-700 transition">
                  Apply Photos ({{ $toAdd->count() + $toReplace->count() }} total)
              </button>
              @endif
              <a href="{{ route('import.index') }}?tab=photos"
                 class="bg-gray-100 text-gray-600 px-5 py-2.5 rounded-lg text-sm hover:bg-gray-200 transition">
                  Cancel
              </a>
          </div>
      </form>

      @else
      {{-- ── PHOTO STEP 1: Upload form ─────────────────────────────────── --}}
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
          <h2 class="font-semibold text-primary text-sm uppercase tracking-wide">Bulk Photo Upload via ZIP</h2>

          <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800 space-y-1">
              <p class="font-semibold">How it works:</p>
              <ul class="list-disc list-inside space-y-0.5">
                  <li>Name each photo file exactly as the <strong>roll number</strong> — e.g. <code>ST-001.jpg</code>, <code>1.png</code></li>
                  <li>Select the <strong>same Organization + Stream + Section</strong> as the target students</li>
                  <li>If the same roll number exists in two sections, upload in <strong>separate ZIPs</strong> selecting the correct section each time — this ensures no ambiguity</li>
                  <li>You will see a full <strong>preview</strong> (new / replace / not found) before anything is saved</li>
              </ul>
          </div>

          <form method="POST" action="{{ route('import.photos.preview') }}" enctype="multipart/form-data" class="space-y-5">
              @csrf
              <div class="grid grid-cols-2 gap-4">
                  <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Organization <span class="text-red-500">*</span></label>
                      <select name="organization" id="photoOrgSelect" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                          @foreach($formOptions as $slug => $organization)
                              <option value="{{ $slug }}" @selected($selectedOrg === $slug)>{{ $organization['label'] }}</option>
                          @endforeach
                      </select>
                  </div>
                  <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Stream / Class</label>
                      <select name="stream" id="photoStreamSelect"
                             class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"></select>
                  </div>
                  <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">Section</label>
                      <select name="section" id="photoSectionSelect"
                             class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"></select>
                  </div>
                  <div>
                      <label class="block text-xs font-medium text-gray-600 mb-1">ZIP File <span class="text-red-500">*</span> (max 100 MB)</label>
                      <input type="file" name="zip_file" accept=".zip" required
                             class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-emerald-600 file:text-white hover:file:bg-emerald-700">
                  </div>
              </div>
              <button type="submit"
                      class="bg-emerald-600 text-white px-8 py-2.5 rounded-lg text-sm font-semibold hover:bg-emerald-700 transition">
                  Preview Photos →
              </button>
          </form>
      </div>
      @endif

    @endif

</div>
@endsection

@push('scripts')
<script>
const importOptions = @json($formOptions ?? []);

function fillImportSelect(select, options, placeholder) {
    if (!select) return;

    select.innerHTML = '';

    const first = document.createElement('option');
    first.value = '';
    first.textContent = placeholder;
    select.appendChild(first);

    options.forEach(function(value) {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        select.appendChild(option);
    });

    select.disabled = options.length === 0;
}

function wireImportSelectors(orgId, streamId, sectionId) {
    const orgSelect = document.getElementById(orgId);
    const streamSelect = document.getElementById(streamId);
    const sectionSelect = document.getElementById(sectionId);
    if (!orgSelect || !streamSelect || !sectionSelect) return;

    function refreshSections() {
        const organization = importOptions[orgSelect.value] || { streams: {} };
        const sections = organization.streams[streamSelect.value] || [];
        fillImportSelect(sectionSelect, sections, sections.length ? '-- Select Section --' : '-- No sections available --');
    }

    function refreshStreams() {
        const organization = importOptions[orgSelect.value] || { streams: {} };
        const streams = Object.keys(organization.streams || {}).sort();
        fillImportSelect(streamSelect, streams, streams.length ? '-- Select Department / Class --' : '-- No departments/classes available --');
        refreshSections();
    }

    orgSelect.addEventListener('change', refreshStreams);
    streamSelect.addEventListener('change', refreshSections);
    refreshStreams();
}

wireImportSelectors('xlsxOrgSelect', 'xlsxStreamSelect', 'xlsxSectionSelect');
wireImportSelectors('csvOrgSelect', 'csvStreamSelect', 'csvSectionSelect');
wireImportSelectors('photoOrgSelect', 'photoStreamSelect', 'photoSectionSelect');

document.getElementById('csvLearningAccounts')?.addEventListener('change', function() {
    document.getElementById('csvLearningPasswordFields')?.classList.toggle('hidden', !this.checked);
});
</script>
@endpush
