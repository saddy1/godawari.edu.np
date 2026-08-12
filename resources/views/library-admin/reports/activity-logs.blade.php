@extends('library-admin.layouts.app')

@section('title', 'Activity Logs')

@section('library-content')
<div class="mx-auto max-w-7xl space-y-5">

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.library.activity-logs.index') }}"
          class="flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search borrower, book, details…"
               class="flex-1 min-w-48 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
        <select name="action" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold outline-none focus:border-emerald-700">
            <option value="">All actions</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                    {{ ucwords(str_replace('_', ' ', $action)) }}
                </option>
            @endforeach
        </select>
        <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white hover:bg-slate-700">Filter</button>
        @if(request()->hasAny(['search', 'action']))
            <a href="{{ route('admin.library.activity-logs.index') }}"
               class="text-sm font-black text-slate-400 hover:text-slate-700">Clear</a>
        @endif
    </form>

    {{-- Log list --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs font-black uppercase tracking-widest text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Time</th>
                    <th class="px-4 py-3 text-left">Action</th>
                    <th class="px-4 py-3 text-left">Book</th>
                    <th class="px-4 py-3 text-left">Borrower</th>
                    <th class="px-4 py-3 text-right">Fine</th>
                    <th class="px-4 py-3 text-left">Details</th>
                    <th class="px-4 py-3 text-left">By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    @php
                        $color = match($log->action) {
                            'book_issued'    => 'blue',
                            'book_returned'  => 'green',
                            'fine_collected' => 'red',
                            'book_added', 'copies_added' => 'purple',
                            default => 'gray',
                        };
                        $colorMap = [
                            'blue'   => 'bg-blue-100 text-blue-700',
                            'green'  => 'bg-emerald-100 text-emerald-700',
                            'red'    => 'bg-red-100 text-red-700',
                            'purple' => 'bg-purple-100 text-purple-700',
                            'gray'   => 'bg-slate-100 text-slate-600',
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 text-xs text-slate-400 whitespace-nowrap">
                            {{ $log->created_at->format('d M Y') }}<br>
                            <span class="font-black text-slate-600">{{ $log->created_at->format('H:i') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-black {{ $colorMap[$color] }}">
                                {{ $log->action_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($log->book_title)
                                <p class="font-bold text-slate-900 leading-snug">{{ $log->book_title }}</p>
                                @if($log->accession_no)
                                    <p class="text-xs text-slate-400">Acc# {{ $log->accession_no }}</p>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($log->borrower_name)
                                <p class="font-bold text-slate-900">{{ $log->borrower_name }}</p>
                                <p class="text-xs text-slate-400">{{ $log->borrower_identifier }} · {{ $log->borrower_type }}</p>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-black {{ $log->fine_amount > 0 ? 'text-red-600' : 'text-slate-400' }}">
                            {{ $log->fine_amount > 0 ? 'Rs. ' . number_format($log->fine_amount, 2) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500 max-w-xs">{{ $log->details }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $log->performer?->name ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-16 text-center font-bold text-slate-400">No activity logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->withQueryString()->links() }}

</div>
@endsection
