@extends(auth()->user()?->isTeacher() ? 'teaching_learning.teacher-workspace.layout' : 'hajiri.layouts.app')

@section('content')

{{-- Hero --}}
<div class="bg-[#1a5632] rounded-2xl p-6 sm:p-8 text-white shadow-lg mb-6 relative overflow-hidden">
    <div class="absolute -top-10 -right-10 w-48 h-48 bg-[#e2a024] rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    <div class="relative z-10">
        <p class="text-[11px] font-bold text-[#e2a024] uppercase tracking-widest mb-1">Staff Services</p>
        <h2 class="text-2xl font-extrabold">ID Card Request</h2>
        <p class="text-green-200 text-sm mt-1">Request a new or replacement ID card</p>
    </div>
</div>

@if(session('message'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl px-4 py-3 mb-4 font-medium">{{ session('message') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-4 font-medium">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Request Form --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-base font-extrabold text-gray-900 mb-4">New Request</h3>

            @if($hasPending)
                <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-sm font-semibold text-amber-800">You have an active request in progress. You can submit a new one once it is collected or closed.</p>
                </div>
            @else
                <form method="POST" action="{{ route('hajiri.staff-card-request.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Reason <span class="text-gray-400 font-normal">(optional)</span></label>
                        <select name="reason" class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-[#1a5632] bg-white">
                            <option value="">Select a reason…</option>
                            <option value="New card (first time)">New card (first time)</option>
                            <option value="Lost card">Lost card</option>
                            <option value="Damaged card">Damaged card</option>
                            <option value="Renewal">Renewal / Expired</option>
                            <option value="Name/photo update">Name / Photo update</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <button type="submit"
                            class="w-full py-2.5 bg-[#1a5632] hover:bg-[#0b2415] text-white text-sm font-extrabold rounded-xl transition-colors shadow-sm">
                        Submit Request
                    </button>
                </form>
            @endif

            {{-- Info card --}}
            <div class="mt-5 bg-gray-50 rounded-xl p-4 space-y-2">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">How it works</p>
                <div class="flex items-start gap-2 text-xs text-gray-600">
                    <span class="w-5 h-5 rounded-full bg-[#1a5632] text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">1</span>
                    <span>Submit your request with a reason</span>
                </div>
                <div class="flex items-start gap-2 text-xs text-gray-600">
                    <span class="w-5 h-5 rounded-full bg-[#1a5632] text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">2</span>
                    <span>Admin reviews and approves your request</span>
                </div>
                <div class="flex items-start gap-2 text-xs text-gray-600">
                    <span class="w-5 h-5 rounded-full bg-[#1a5632] text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">3</span>
                    <span>Your card is printed and ready for collection</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Request History --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-extrabold text-gray-900">My Requests</h3>
            </div>

            @if($requests->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                    <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/></svg>
                    </div>
                    <p class="text-sm font-extrabold text-gray-700">No requests yet</p>
                    <p class="text-xs text-gray-400 mt-1">Submit your first ID card request using the form.</p>
                </div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($requests as $req)
                    @php $c = $req->status_color; @endphp
                    <div class="px-6 py-4 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-900">{{ $req->reason ?: 'No reason specified' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">Submitted {{ $req->created_at->diffForHumans() }}</p>
                            @if($req->admin_note)
                                <p class="text-xs text-gray-500 italic mt-1">Admin: {{ $req->admin_note }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 px-3 py-1 text-[10px] font-extrabold rounded-lg uppercase bg-{{ $c }}-100 text-{{ $c }}-700">
                            {{ $req->status_label }}
                        </span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
