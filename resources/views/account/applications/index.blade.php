@extends('layouts.app')

@section('title', 'My Applications | ' . $siteSettings->localized('site_name', 'School'))

@section('content')
<section class="pt-36 pb-16 bg-[#fdfbf7] min-h-screen">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
            <div>
                <p class="text-[#e2a024] font-bold text-sm uppercase tracking-widest mb-2">Applicant Account</p>
                <h1 class="text-3xl font-bold text-[#0b2415]">My Applications</h1>
                <p class="text-gray-500 text-sm mt-2">Track submitted vacancy applications and view uploaded details.</p>
            </div>
            <a href="{{ route('vacancies') }}" class="inline-flex items-center justify-center px-5 py-3 bg-[#1a5632] text-white rounded-xl font-bold text-sm hover:bg-[#0b2415] transition-colors">
                Apply for Another Vacancy
            </a>
        </div>

        @if($applications->count())
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                <div class="divide-y divide-gray-100">
                    @foreach($applications as $application)
                        @php
                            $statusClass = match($application->status) {
                                'Shortlisted' => 'bg-green-50 text-green-700 border-green-200',
                                'Rejected' => 'bg-red-50 text-red-700 border-red-200',
                                'Reviewed' => 'bg-blue-50 text-blue-700 border-blue-200',
                                default => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                            };
                        @endphp
                        <a href="{{ route('account.applications.show', $application) }}" class="block p-5 sm:p-6 hover:bg-green-50/40 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <span class="inline-flex px-3 py-1 rounded-full border text-xs font-bold {{ $statusClass }}">{{ $application->status }}</span>
                                        <span class="text-xs font-semibold text-gray-400">Submitted {{ $application->created_at->format('M d, Y') }}</span>
                                    </div>
                                    <h2 class="text-lg font-bold text-[#0b2415] truncate">{{ $application->vacancy?->title ?? 'Vacancy removed' }}</h2>
                                    <p class="text-sm text-gray-500 mt-1 truncate">{{ $application->qualification }} @if($application->experience) · {{ $application->experience }} @endif</p>
                                </div>
                                <span class="text-sm font-bold text-[#1a5632]">View Details</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="mt-6">{{ $applications->links() }}</div>
        @else
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-10 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-green-50 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-[#1a5632]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h2 class="text-xl font-bold text-[#0b2415]">No applications yet</h2>
                <p class="text-gray-500 text-sm mt-2 mb-6">Open vacancies are listed on the careers page.</p>
                <a href="{{ route('vacancies') }}" class="inline-flex px-6 py-3 bg-[#1a5632] text-white rounded-xl font-bold text-sm hover:bg-[#0b2415] transition-colors">Browse Vacancies</a>
            </div>
        @endif
    </div>
</section>
@endsection
