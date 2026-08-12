@extends('card.layouts.app')

@section('title', 'Certificates')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl bg-gradient-to-br from-[#0b2415] to-[#1a5632] p-5 sm:p-6 text-white shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-widest text-white/50">Student Module</p>
                <h1 class="mt-1 text-3xl font-extrabold">Certificates</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium text-white/70">
                    Issue and reprint character or provisional certificates for students.
                </p>
            </div>
            @if(auth()->user()?->canAccess('hr.certificates.create'))
                <a href="{{ route('certificates.create') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-extrabold text-[#1a5632] hover:bg-gray-100">
                    Issue Certificate
                </a>
            @endif
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        @foreach(['all' => ['All', 'bg-gray-900'], 'character' => ['Character', 'bg-emerald-700'], 'provisional' => ['Provisional', 'bg-amber-600']] as $key => [$label, $color])
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">{{ $label }}</p>
            <p class="mt-2 text-3xl font-black text-gray-950">{{ $counts[$key] }}</p>
        </div>
        @endforeach
    </div>

    <form method="GET" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[1fr_180px_120px]">
            <input name="search" value="{{ request('search') }}" placeholder="Search by name, cert no., symbol no., reg no...."
                   class="rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
            <select name="type" class="rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                <option value="">All types</option>
                <option value="character" @selected(request('type') === 'character')>Character</option>
                <option value="provisional" @selected(request('type') === 'provisional')>Provisional</option>
            </select>
            <button class="rounded-xl bg-[#1a5632] px-4 py-3 text-sm font-extrabold text-white">Filter</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-widest text-gray-500">Cert No.</th>
                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-widest text-gray-500">Student</th>
                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-widest text-gray-500">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-widest text-gray-500">Exam</th>
                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-widest text-gray-500">Issue Date</th>
                        <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-widest text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($certificates as $cert)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4 font-mono text-sm font-bold text-gray-700">{{ $cert->certificate_number }}</td>
                        <td class="px-5 py-4">
                            <p class="font-extrabold text-gray-950">{{ $cert->student_name }}</p>
                            @if($cert->symbol_no)
                                <p class="text-xs text-gray-500">Symbol: {{ $cert->symbol_no }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-full border px-3 py-1 text-xs font-extrabold
                                {{ $cert->certificate_type === 'character' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-100' }}">
                                {{ ucfirst($cert->certificate_type) }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-700">
                            {{ $cert->exam_name ?: '—' }}
                            @if($cert->pass_year_bs)
                                <span class="text-gray-400">· {{ $cert->pass_year_bs }} B.S.</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-700">
                            {{ $cert->issue_date?->format('d M Y') }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('certificates.print', $cert) }}" target="_blank"
                                   class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-extrabold text-gray-700 hover:bg-gray-50">
                                    Print
                                </a>
                                @if(auth()->user()?->canAccess('hr.certificates.delete'))
                                <form method="POST" action="{{ route('certificates.destroy', $cert) }}"
                                      onsubmit="return confirm('Delete certificate {{ $cert->certificate_number }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-extrabold text-red-600 hover:bg-red-100">Delete</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <p class="font-extrabold text-gray-900">No certificates issued yet.</p>
                            <p class="mt-1 text-sm text-gray-500">Issue the first certificate from the button above.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-5 py-4">{{ $certificates->links() }}</div>
    </div>
</div>
@endsection
