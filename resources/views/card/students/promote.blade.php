@extends('card.layouts.app')
@section('title', 'Promote Students')
@section('heading', 'Annual Class Promotion')

@section('content')
<div class="max-w-4xl space-y-6">

    @if(isset($errors) && $errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl px-5 py-4 text-sm text-red-700">
        @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
    </div>
    @endif

    {{-- ── Info banner ──────────────────────────────────────────────────── --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800 space-y-1">
        <p class="font-semibold">How promotion works:</p>
        <ul class="list-disc list-inside space-y-0.5 text-xs">
            <li>Each class group below shows the current stream and auto-suggested next class.</li>
            <li>You can edit the <strong>"Promote To"</strong> field if the suggestion is wrong.</li>
            <li>Students in Class {{ \App\Http\Controllers\Card\PromoteController::MAX_CLASS ?? 12 }} (the final year) default to <strong>Graduate</strong> action.</li>
            <li>Graduated students are kept and marked as <strong>Graduated</strong>. Delete members only from the HR Members module.</li>
            <li>Uncheck groups you don't want to promote this cycle.</li>
        </ul>
    </div>

    @if($groups->isEmpty())
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-10 text-center text-gray-400">
        <p class="font-medium text-gray-500">No school students found.</p>
        <a href="{{ route('students.index') }}" class="text-sm text-primary hover:underline mt-2 inline-block">← Back to Members</a>
    </div>
    @else

    <form method="POST" action="{{ route('promote.apply') }}" id="promoteForm">
        @csrf

        {{-- ── Global options ───────────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex flex-wrap gap-6 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">New Valid Till (optional — applies to all promoted)</label>
                <input type="date" name="valid_till"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            </div>
            <input type="hidden" name="grad_action" value="mark">
            <div class="flex gap-2">
                <button type="button" onclick="selectAll(true)"
                        class="text-xs text-blue-600 hover:underline">Select All</button>
                <span class="text-gray-300">|</span>
                <button type="button" onclick="selectAll(false)"
                        class="text-xs text-gray-500 hover:underline">Deselect All</button>
            </div>
        </div>

        {{-- ── Groups table ─────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 w-8"></th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Current Class / Stream</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Section</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Students</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Promote To</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($groups as $i => $g)
                    @php
                        $defaultAction = $g['is_grad_year'] ? 'graduate' : 'promote';
                    @endphp
                    <tr class="{{ $g['is_grad_year'] ? 'bg-orange-50' : 'hover:bg-gray-50/60' }} transition" id="row-{{ $i }}">

                        {{-- Checkbox --}}
                        <td class="px-4 py-3">
                            <input type="checkbox" class="group-check accent-primary"
                                   data-row="{{ $i }}" checked
                                   onchange="toggleRow({{ $i }}, this.checked)">
                        </td>

                        {{-- Hidden fields --}}
                        <input type="hidden" name="groups[{{ $i }}][from_stream]"  value="{{ $g['stream'] }}">
                        <input type="hidden" name="groups[{{ $i }}][from_section]" value="{{ $g['section'] }}">

                        {{-- Current stream --}}
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-800">{{ $g['stream'] ?? '(No class set)' }}</span>
                            @if($g['is_grad_year'])
                                <span class="ml-2 text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full font-medium">Final Year</span>
                            @endif
                        </td>

                        {{-- Section --}}
                        <td class="px-4 py-3 text-gray-500">{{ $g['section'] ?? '—' }}</td>

                        {{-- Count --}}
                        <td class="px-4 py-3">
                            <span class="font-mono font-semibold text-gray-700">{{ $g['count'] }}</span>
                        </td>

                        {{-- To stream (editable) --}}
                        <td class="px-4 py-3" id="to-cell-{{ $i }}">
                            <input type="text" name="groups[{{ $i }}][to_stream]"
                                   value="{{ $g['suggested_stream'] }}"
                                   placeholder="New class/stream"
                                   class="border border-gray-200 rounded-lg px-2 py-1 text-sm w-36
                                          focus:outline-none focus:ring-2 focus:ring-blue-300
                                          {{ $g['is_grad_year'] ? 'opacity-40' : '' }}"
                                   {{ $g['is_grad_year'] ? 'disabled' : '' }}>
                            <input type="hidden" name="groups[{{ $i }}][to_section]" value="{{ $g['section'] }}">
                        </td>

                        {{-- Action --}}
                        <td class="px-4 py-3">
                            <select name="groups[{{ $i }}][action]"
                                    class="border border-gray-200 rounded-lg px-2 py-1 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-blue-300"
                                    onchange="handleActionChange({{ $i }}, this.value)">
                                <option value="promote" {{ $defaultAction === 'promote' ? 'selected' : '' }}>Promote</option>
                                <option value="graduate" {{ $defaultAction === 'graduate' ? 'selected' : '' }}>Graduate</option>
                                <option value="skip">Skip (don't change)</option>
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── Submit ───────────────────────────────────────────────── --}}
        <div class="flex gap-3">
            <button type="submit"
                    onclick="return confirm('Apply promotion to all selected groups? This cannot be undone.')"
                    class="bg-primary text-white px-8 py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-light transition">
                Apply Promotion
            </button>
            <a href="{{ route('students.index') }}"
               class="bg-gray-100 text-gray-600 px-6 py-2.5 rounded-lg text-sm hover:bg-gray-200 transition">
                Cancel
            </a>
        </div>

    </form>
    @endif

</div>
@endsection

@push('scripts')
<script>
function toggleRow(i, enabled) {
    const action = document.querySelector(`[name="groups[${i}][action]"]`);
    if (!enabled) action.value = 'skip';
    action.disabled = !enabled;
}

function handleActionChange(i, val) {
    const toInput = document.querySelector(`#to-cell-${i} input[type=text]`);
    if (!toInput) return;
    if (val === 'graduate' || val === 'skip') {
        toInput.disabled = true;
        toInput.classList.add('opacity-40');
    } else {
        toInput.disabled = false;
        toInput.classList.remove('opacity-40');
    }
}

function selectAll(checked) {
    document.querySelectorAll('.group-check').forEach(function(cb) {
        cb.checked = checked;
        toggleRow(cb.dataset.row, checked);
    });
}
</script>
@endpush
