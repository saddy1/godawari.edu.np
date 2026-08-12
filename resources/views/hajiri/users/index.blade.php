@extends('hajiri.layouts.app')

@section('content')

{{-- Hero --}}
<div class="bg-[#1a5632] rounded-2xl p-6 sm:p-8 text-white shadow-lg mb-6 relative overflow-hidden">
    <div class="absolute -top-10 -right-10 w-48 h-48 bg-[#e2a024] rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-[11px] font-bold text-[#e2a024] uppercase tracking-widest mb-1">Employee Management</p>
            <h2 class="text-2xl font-extrabold">{{ $type }} List</h2>
            <p class="text-green-200 text-sm mt-1">{{ count($users) }} {{ Str::lower($type) }}{{ count($users) == 1 ? '' : 's' }} found</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.hr.members.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-[#e2a024] hover:bg-godawari-gold-light text-white text-sm font-extrabold rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Add from HR
            </a>
            @if(isset($type_id))
                <a href="{{ route('hajiri.users.inactive') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 border border-white/20 hover:bg-white/20 text-white text-sm font-bold rounded-xl transition-colors">
                    Inactive
                </a>
            @else
                <a href="{{ route('hajiri.users.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 border border-white/20 hover:bg-white/20 text-white text-sm font-bold rounded-xl transition-colors">
                    Active
                </a>
            @endif
            <a href="{{ route('hajiri.users.indexsort') }}"
               class="inline-flex items-center gap-2 px-4 py-2 {{ $sort ? 'bg-white text-[#1a5632]' : 'bg-white/10 border border-white/20 text-white' }} text-sm font-bold rounded-xl transition-colors hover:bg-white hover:text-[#1a5632]">
                {{ $sort ? '✓ Sortable ON' : 'Sortable' }}
            </a>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5 mb-6">
    <form action="{{ route('hajiri.users.filter') }}" method="POST">
        @csrf
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs font-extrabold text-gray-500 uppercase tracking-wider">Filter by</span>
            <select name="designation"
                    class="flex-1 min-w-40 text-sm px-3 py-2 border border-gray-200 rounded-xl bg-gray-50 font-semibold focus:outline-none focus:border-[#1a5632]">
                <option value="" disabled selected>Designation</option>
                @foreach($desig as $d)
                    <option value="{{ $d['id'] }}">{{ $d['label'] }}</option>
                @endforeach
            </select>
            <button type="submit"
                    class="px-4 py-2 bg-[#1a5632] hover:bg-[#0b2415] text-white text-sm font-extrabold rounded-xl transition-colors">
                Apply
            </button>
            <a href="{{ route('hajiri.users.index') }}"
               class="px-4 py-2 border border-gray-200 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-50 transition-colors">
                Clear
            </a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    @if(count($users) === 0)
        <div class="flex flex-col items-center justify-center py-20 text-center px-6">
            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h4 class="text-base font-extrabold text-gray-700 mb-1">No employees found</h4>
            <p class="text-sm text-gray-400">Try changing the filter or add a new employee.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="userSort">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider w-10">#</th>
                        <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Employee</th>
                        <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider hidden md:table-cell">Contact</th>
                        <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Address</th>
                        <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider hidden sm:table-cell">Designation</th>
                        <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider hidden xl:table-cell">HR Profile</th>
                        <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider hidden md:table-cell">Role</th>
                        <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider hidden xl:table-cell">Device ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-right text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($users as $key => $user)
                    @php
                        $hajiriProfilePending = blank($user->device_id)
                            || blank($user->designation_id)
                            || blank($user->employment_type_id)
                            || blank($user->work_assigned_id);
                        $roleLabel = $user->role_label;
                        // Also show hajiri-module admin role
                        if ($roleLabel === 'Employee' && $user->hasRole('admin')) $roleLabel = 'Hajiri Admin';
                        $roleBadge = match(true) {
                            $user->isSuperAdmin()            => 'bg-amber-50 text-amber-700 border-amber-200',
                            $user->isPrincipal()             => 'bg-purple-50 text-purple-700 border-purple-200',
                            $user->isAccountant()            => 'bg-teal-50 text-teal-700 border-teal-200',
                            $user->hasRole('administrator')  => 'bg-blue-50 text-blue-700 border-blue-200',
                            $user->hasRole('teacher')        => 'bg-sky-50 text-sky-700 border-sky-200',
                            $user->hasRole('staff')          => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            $user->hasRole('admin')          => 'bg-orange-50 text-orange-700 border-orange-200',
                            default                          => 'bg-gray-50 text-gray-600 border-gray-200',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors" data-id="{{ $user->id }}">
                        <td class="px-4 py-3 text-xs font-bold text-gray-400">{{ $key + 1 }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-[#1a5632] text-white text-sm font-extrabold flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 leading-tight">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-400 font-medium mt-0.5">{{ $user->working_at->label ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <p class="font-semibold text-gray-700">{{ $user->email }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $user->phone ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <p class="text-gray-700">{{ $user->municipal ?? '—' }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $user->district }}, {{ $user->province }}</p>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell text-gray-700 font-medium">
                            {{ $user->designation->label ?? '—' }}
                        </td>
                        <td class="px-4 py-3 hidden xl:table-cell text-xs text-gray-500">
                            @if($user->student)
                                <p class="font-bold text-gray-700">{{ ucfirst($user->student->employee_category ?: $user->student->member_type) }}</p>
                                <p class="mt-0.5">Joined: {{ $user->student->joining_date?->format('M d, Y') ?? '—' }}</p>
                                <p class="mt-0.5">PAN: {{ $user->student->pan_number ?: '—' }}</p>
                            @else
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-extrabold text-amber-700">Not linked to HR</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wide border {{ $roleBadge }}">
                                {{ $roleLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 hidden xl:table-cell font-mono text-xs text-gray-500">
                            {{ $user->device_id ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($hajiriProfilePending)
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-700 text-[10px] font-extrabold rounded-lg uppercase tracking-wide">Profile pending</span>
                            @elseif($user->status)
                                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-[10px] font-extrabold rounded-lg uppercase tracking-wide">Active</span>
                            @else
                                <span class="px-2.5 py-1 bg-red-100 text-red-600 text-[10px] font-extrabold rounded-lg uppercase tracking-wide">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($allowEdit ?? true)
                            <a href="{{ route('hajiri.users.edit', $user) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 text-gray-600 text-xs font-bold rounded-lg hover:border-[#1a5632] hover:text-[#1a5632] hover:bg-green-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit
                            </a>
                            @else
                            <a href="{{ route('admin.hr.members.index') }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 text-gray-500 text-xs font-bold rounded-lg hover:border-blue-300 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/>
                                </svg>
                                Edit in HR
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    @if($sort)
    $.loadScript('https://code.jquery.com/ui/1.12.1/jquery-ui.min.js', function(){
        $('#userSort tbody').sortable({
            placeholder: 'bg-blue-50',
            update: function() {
                var ids = [];
                $('#userSort tbody tr').each(function() { ids.push($(this).data('id')); });
                $.ajax({ url: "{{ route('hajiri.users.savesort') }}", type: 'post', data: { _token: '{{ csrf_token() }}', data: ids } });
            }
        });
    });
    @else
    $.loadScript('https://cdn.datatables.net/1.11.2/js/jquery.dataTables.min.js', function(){
        $('#userSort').DataTable({ searching: false, paging: false, info: false });
    });
    @endif
</script>
@endpush
