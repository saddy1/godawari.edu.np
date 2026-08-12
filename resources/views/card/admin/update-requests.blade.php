@extends('card.layouts.app')
@section('title', 'Update Requests')
@section('heading', 'Student Detail Update Requests')

@section('content')

<div class="space-y-4">

    <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b">
            <p class="font-semibold text-primary text-sm">All Requests ({{ $requests->total() }})</p>
        </div>

        @if($requests->isEmpty())
            <div class="p-12 text-center text-gray-400">No update requests yet.</div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                <tr>
                    <th class="px-6 py-3 text-left">Student</th>
                    <th class="px-6 py-3 text-left">Requested Changes</th>
                    <th class="px-6 py-3 text-left">Submitted</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($requests as $req)
                @php
                    $colors = ['pending'=>'yellow','approved'=>'green','rejected'=>'red'];
                    $c = $colors[$req->status] ?? 'gray';
                    $fieldLabels = [
                        'dob_bs'=>'Date of Birth (BS)', 'father_name'=>'Father Name', 'mother_name'=>'Mother Name',
                        'grandfather_name'=>'Grandfather Name', 'parent_contact'=>'Parent Contact',
                        'mobile'=>'Mobile','email'=>'Email','zone'=>'Province','district'=>'District',
                        'municipality'=>'Municipality','photo'=>'Profile Photo'
                    ];
                @endphp
                <tr class="hover:bg-gray-50" x-data="{ open: false }">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-800">{{ $req->student->full_name }}</p>
                        <p class="text-xs text-gray-400">Roll: {{ $req->student->roll_number }} &middot; {{ $req->student->stream }} {{ $req->student->section }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <div class="space-y-1">
                            @foreach($req->requested_changes as $field => $value)
                                <div class="text-xs">
                                    <span class="font-medium text-gray-600">{{ $fieldLabels[$field] ?? $field }}:</span>
                                    @if($field === 'photo')
                                        <div class="mt-2 flex items-center gap-2">
                                            <img src="{{ $req->student->photo_url }}" alt="Current" class="h-20 w-16 rounded-lg border object-cover">
                                            <span class="text-gray-400">→</span>
                                            <img src="{{ asset($value) }}" alt="Requested" class="h-20 w-16 rounded-lg border border-green-300 object-cover">
                                        </div>
                                    @else
                                        @php $current = $req->student->$field; @endphp
                                        @if($current)
                                            <span class="text-red-500 line-through">{{ $current }}</span>
                                            <span class="text-gray-400 mx-1">→</span>
                                        @endif
                                        <span class="text-green-600 font-medium">{{ $value }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-500">
                        {{ $req->created_at->format('d M Y') }}<br>
                        {{ $req->created_at->diffForHumans() }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700">
                            {{ ucfirst($req->status) }}
                        </span>
                        @if($req->admin_note)
                            <p class="text-xs text-gray-400 mt-1">{{ $req->admin_note }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($req->status === 'pending')
                            <button @click="open = !open"
                                class="text-xs text-primary font-medium hover:underline">Review</button>

                            <div x-show="open" @click.away="open = false" class="mt-2 bg-white border rounded-xl shadow-lg p-4 w-64 z-10 relative">
                                <form method="POST" action="{{ route('admin.update-requests.review', $req) }}" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Note (optional)</label>
                                        <textarea name="admin_note" rows="2" placeholder="Reason for rejection, etc."
                                            class="w-full border rounded-lg px-2 py-1.5 text-xs resize-none"></textarea>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="submit" name="action" value="approve"
                                            class="bg-green-600 text-white text-xs font-semibold py-1.5 rounded-lg hover:bg-green-700 transition">
                                            Approve
                                        </button>
                                        <button type="submit" name="action" value="reject"
                                            class="bg-red-500 text-white text-xs font-semibold py-1.5 rounded-lg hover:bg-red-600 transition">
                                            Reject
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
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

@endsection

@push('scripts')
<script src="//unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush
