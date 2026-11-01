@extends('card.layouts.app')
@section('title', 'Card Requests')
@section('heading', 'ID Card Requests')

@section('content')

<div class="space-y-4">

    @if($orphanedRequestCount > 0)
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="font-semibold text-amber-900 text-sm">{{ $orphanedRequestCount }} invalid card {{ Str::plural('request', $orphanedRequestCount) }} found</p>
            <p class="mt-1 text-xs text-amber-800">These requests belong to students that no longer exist. They are excluded from the list until cleaned up.</p>
        </div>
        <form method="POST" action="{{ route('admin.card-requests.cleanup-orphaned') }}" onsubmit="return confirm('Remove {{ $orphanedRequestCount }} invalid card {{ Str::plural('request', $orphanedRequestCount) }}? This removes only the requests; it cannot restore deleted students.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-600 hover:bg-amber-700 px-4 py-2 text-xs font-semibold text-white transition">
                Remove invalid requests
            </button>
        </form>
    </div>
    @endif

    {{-- Stats --}}
    @php
        $counts = $requests->getCollection()->groupBy('status');
    @endphp
    <div class="grid grid-cols-4 gap-4">
        @foreach(['pending' => ['yellow','Pending'], 'approved' => ['green','Approved'], 'collected' => ['blue','Collected'], 'rejected' => ['red','Rejected']] as $status => [$color, $label])
        <div class="bg-white border rounded-xl p-4">
            <p class="text-xs text-gray-400">{{ $label }}</p>
            <p class="text-2xl font-bold text-{{ $color }}-600">
                {{ $requests->getCollection()->where('status', $status)->count() }}
            </p>
        </div>
        @endforeach
    </div>

    {{-- Bulk Action Bar (sticky, shown when items selected) --}}
    <div id="bulkBar" style="display:none;" class="sticky top-4 z-30 bg-white border border-green-200 rounded-2xl shadow-lg px-6 py-4 flex items-center gap-6 flex-wrap">
        <div class="flex items-center gap-2 font-semibold text-sm text-gray-700">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            <span id="bulkCount">0</span> request(s) selected
        </div>

        <div class="flex items-center gap-4 text-xs text-gray-600 border-l pl-6">
            <span class="font-medium text-gray-500">Print:</span>
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="bulkTypeId" value="id" checked class="bulk-type-check accent-green-600">
                ID Card
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="bulkTypeLibrary" value="library" class="bulk-type-check accent-green-600">
                Library Card
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="bulkTypeBus" value="bus" class="bulk-type-check accent-green-600">
                Bus Pass
            </label>
        </div>

        <div class="flex items-center gap-2 ml-auto">
            <button onclick="submitBulkPrint()" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Selected
            </button>
            <button onclick="clearSelection()" class="text-xs text-gray-400 hover:text-gray-600 px-3 py-2 rounded-lg hover:bg-gray-100 transition">
                Clear
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <p class="font-semibold text-primary text-sm">All Requests ({{ $requests->total() }})</p>
            @if($requests->isNotEmpty())
            <button onclick="selectAllPrintable()" class="text-xs text-green-600 hover:underline font-medium">
                Select All Approved / Collected
            </button>
            @endif
        </div>

        @if($requests->isEmpty())
            <div class="p-12 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                No card requests yet.
            </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3 text-left w-8"></th>
                    <th class="px-6 py-3 text-left">Student</th>
                    <th class="px-6 py-3 text-left">Roll / Faculty / Section</th>
                    <th class="px-6 py-3 text-left">Requested</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($requests as $req)
                @php
                    $colors = ['pending'=>'yellow','approved'=>'green','collected'=>'blue','rejected'=>'red'];
                    $c = $colors[$req->status] ?? 'gray';
                    $printable = in_array($req->status, ['approved', 'collected']);
                @endphp
                <tr class="hover:bg-gray-50 {{ $printable ? 'printable-row' : '' }}" x-data="{ open: false }">
                    {{-- Checkbox column --}}
                    <td class="px-4 py-4">
                        @if($printable)
                            <input type="checkbox"
                                class="req-check accent-green-600 rounded"
                                value="{{ $req->student->id }}"
                                onchange="updateBulkBar()">
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if($req->student->photo && file_exists(public_path($req->student->photo)))
                                <img src="{{ asset($req->student->photo) }}" class="w-9 h-9 rounded-lg object-cover">
                            @else
                                <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-xs text-gray-400 font-bold">
                                    {{ strtoupper(substr($req->student->first_name,0,1)) }}
                                </div>
                            @endif
                            <div>
                                <p class="font-medium text-gray-800">{{ $req->student->full_name }}</p>
                                <p class="text-xs text-gray-400">{{ $req->student->mobile }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        <p>{{ $req->student->roll_number }}</p>
                        <p class="text-xs text-gray-400">{{ $req->student->stream }} &middot; Sec {{ $req->student->section }}</p>
                    </td>
                    <td class="px-6 py-4 text-gray-500 text-xs">
                        {{ $req->created_at->format('d M Y') }}<br>
                        {{ $req->created_at->format('h:i A') }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700">
                            {{ ucfirst($req->status) }}
                        </span>
                        {{-- Card badges --}}
                        <div class="flex gap-1 mt-1 flex-wrap">
                            <span class="text-xs bg-blue-50 text-blue-500 px-1.5 py-0.5 rounded">ID</span>
                            @if($req->student->has_library_card)
                                <span class="text-xs bg-emerald-50 text-emerald-500 px-1.5 py-0.5 rounded">Lib</span>
                            @endif
                            @if($req->student->has_bus_pass)
                                <span class="text-xs bg-orange-50 text-orange-500 px-1.5 py-0.5 rounded">Bus</span>
                            @endif
                        </div>
                        @if($req->admin_note)
                            <p class="text-xs text-gray-400 mt-1">{{ $req->admin_note }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-2">
                            {{-- Per-row quick print — shown for approved/collected --}}
                            @if($printable)
                                <div x-data="{ printOpen: false }">
                                    <button @click="printOpen = !printOpen"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 px-3 py-1.5 rounded-lg transition w-full justify-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        Print
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <div x-show="printOpen" @click.away="printOpen = false"
                                        class="mt-1 bg-white border rounded-xl shadow-lg p-3 w-48 z-20 relative">
                                        <p class="text-xs font-semibold text-gray-600 mb-2">Select cards to print:</p>
                                        <form method="POST" action="{{ route('bulk.preview') }}" target="_blank" class="space-y-2">
                                            @csrf
                                            <input type="hidden" name="student_ids[]" value="{{ $req->student->id }}">
                                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                <input type="checkbox" name="card_types[]" value="id" checked class="rounded border-gray-300">
                                                ID Card
                                            </label>
                                            @if($req->student->has_library_card)
                                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                <input type="checkbox" name="card_types[]" value="library" checked class="rounded border-gray-300">
                                                Library Card
                                            </label>
                                            @endif
                                            @if($req->student->has_bus_pass)
                                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                                                <input type="checkbox" name="card_types[]" value="bus" checked class="rounded border-gray-300">
                                                Bus Pass
                                            </label>
                                            @endif
                                            <button type="submit"
                                                class="w-full bg-green-600 hover:bg-green-700 text-white text-xs font-semibold py-1.5 rounded-lg transition">
                                                Print Selected
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif

                            <button @click="open = !open"
                                class="text-xs text-primary font-medium hover:underline text-left">Update Status</button>
                        </div>

                        <div x-show="open" @click.away="open = false" class="mt-2 bg-white border rounded-xl shadow-lg p-4 w-64 z-10 relative">
                            <form method="POST" action="{{ route('admin.card-requests.update', $req) }}" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                    <select name="status" class="w-full border rounded-lg px-2 py-1.5 text-xs">
                                        @foreach(['pending','approved','collected','rejected'] as $s)
                                            <option value="{{ $s }}" @selected($req->status === $s)>{{ ucfirst($s) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Note (optional)</label>
                                    <textarea name="admin_note" rows="2" placeholder="e.g. Payment verified"
                                        class="w-full border rounded-lg px-2 py-1.5 text-xs resize-none">{{ $req->admin_note }}</textarea>
                                </div>
                                <button type="submit" class="w-full bg-primary text-white text-xs font-semibold py-1.5 rounded-lg hover:bg-primary-light transition">
                                    Save
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">
            {{ $requests->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Hidden form for bulk print submission --}}
<form id="bulkPrintForm" method="POST" action="{{ route('bulk.preview') }}" target="_blank" style="display:none;">
    @csrf
    <div id="bulkStudentInputs"></div>
    <div id="bulkTypeInputs"></div>
</form>

@endsection

@push('scripts')
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function updateBulkBar() {
    const checked = document.querySelectorAll('.req-check:checked');
    const bar = document.getElementById('bulkBar');
    document.getElementById('bulkCount').textContent = checked.length;
    bar.style.display = checked.length > 0 ? 'flex' : 'none';
}

function selectAllPrintable() {
    document.querySelectorAll('.req-check').forEach(cb => cb.checked = true);
    updateBulkBar();
}

function clearSelection() {
    document.querySelectorAll('.req-check').forEach(cb => cb.checked = false);
    updateBulkBar();
}

function submitBulkPrint() {
    const studentIds = [...document.querySelectorAll('.req-check:checked')].map(cb => cb.value);
    const cardTypes  = [...document.querySelectorAll('.bulk-type-check:checked')].map(cb => cb.value);

    if (studentIds.length === 0) {
        alert('Please select at least one request.');
        return;
    }
    if (cardTypes.length === 0) {
        alert('Please select at least one card type to print.');
        return;
    }

    const studentInputs = studentIds.map(id => `<input type="hidden" name="student_ids[]" value="${id}">`).join('');
    const typeInputs    = cardTypes.map(t  => `<input type="hidden" name="card_types[]" value="${t}">`).join('');

    document.getElementById('bulkStudentInputs').innerHTML = studentInputs;
    document.getElementById('bulkTypeInputs').innerHTML    = typeInputs;
    document.getElementById('bulkPrintForm').submit();
}
</script>
@endpush
