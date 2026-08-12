@extends('layouts.app')

@section('title', 'Application Details | ' . $siteSettings->localized('site_name', 'School'))

@section('content')
<section class="pt-36 pb-16 bg-[#fdfbf7] min-h-screen">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('account.applications.index') }}" class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-[#1a5632] mb-6">Back to applications</a>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm font-bold">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8 border-b border-gray-100">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <p class="text-[#e2a024] font-bold text-sm uppercase tracking-widest mb-2">Application</p>
                        <h1 class="text-2xl font-bold text-[#0b2415]">{{ $application->vacancy?->title ?? 'Vacancy removed' }}</h1>
                        <p class="text-gray-500 text-sm mt-2">Submitted on {{ $application->created_at->format('F d, Y h:i A') }}</p>
                    </div>
                    <span class="inline-flex px-4 py-2 rounded-xl bg-yellow-50 text-yellow-700 border border-yellow-200 text-sm font-bold">{{ $application->status }}</span>
                </div>
            </div>

            <div class="p-6 sm:p-8 grid lg:grid-cols-[1fr_320px] gap-8">
                <div class="space-y-8">
                    <div>
                        <h2 class="text-sm font-bold text-[#1a5632] uppercase tracking-widest mb-4">Personal Information</h2>
                        <div class="grid sm:grid-cols-2 gap-4 text-sm">
                            @foreach([
                                'Full Name' => $application->full_name,
                                'Email' => $application->email,
                                'Phone' => $application->phone,
                                'Date of Birth' => optional($application->date_of_birth)->format('M d, Y') ?: $application->date_of_birth,
                                'Gender' => $application->gender,
                                'Citizenship No.' => $application->citizenship_no,
                                'Father Name' => $application->father_name,
                                'Mother Name' => $application->mother_name,
                                'Permanent Address' => $application->permanent_address,
                                'Temporary Address' => $application->temporary_address,
                            ] as $label => $value)
                                <div class="rounded-xl bg-gray-50 p-4">
                                    <p class="text-xs font-bold text-gray-400 uppercase">{{ $label }}</p>
                                    <p class="mt-1 font-semibold text-gray-800">{{ $value ?: 'Not provided' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h2 class="text-sm font-bold text-[#1a5632] uppercase tracking-widest mb-4">Qualification</h2>
                        <div class="grid sm:grid-cols-2 gap-4 text-sm">
                            <div class="rounded-xl bg-gray-50 p-4">
                                <p class="text-xs font-bold text-gray-400 uppercase">Highest Qualification</p>
                                <p class="mt-1 font-semibold text-gray-800">{{ $application->qualification }}</p>
                            </div>
                            <div class="rounded-xl bg-gray-50 p-4">
                                <p class="text-xs font-bold text-gray-400 uppercase">Experience</p>
                                <p class="mt-1 font-semibold text-gray-800">{{ $application->experience ?: 'Not provided' }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-sm font-bold text-[#1a5632] uppercase tracking-widest mb-4">Motivation</h2>
                        <div class="rounded-xl bg-gray-50 p-5 text-sm leading-relaxed text-gray-700 whitespace-pre-line">{{ $application->motivation }}</div>
                    </div>

                    @if($application->admin_remarks)
                        <div>
                            <h2 class="text-sm font-bold text-[#1a5632] uppercase tracking-widest mb-4">Admin Remarks</h2>
                            <div class="rounded-xl bg-blue-50 border border-blue-100 p-5 text-sm leading-relaxed text-blue-800 whitespace-pre-line">{{ $application->admin_remarks }}</div>
                        </div>
                    @endif
                </div>

                <aside class="space-y-4">
                    @if($application->profile_photo)
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                            <p class="text-xs font-bold text-gray-400 uppercase mb-3">Profile Photo</p>
                            <img src="{{ asset($application->profile_photo) }}" alt="Profile photo" class="w-full aspect-square object-cover rounded-xl bg-white">
                        </div>
                    @endif

                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-bold text-gray-400 uppercase mb-3">Uploaded Files</p>
                        <div class="space-y-2">
                            @foreach([
                                'CV / Resume' => $application->cv_path,
                                'Citizenship Front' => $application->citizen_front_path,
                                'Citizenship Back' => $application->citizen_back_path,
                                'Signature' => $application->signature_path,
                            ] as $label => $path)
                                @if($path)
                                    <a href="{{ asset($path) }}" target="_blank" class="flex items-center justify-between gap-3 rounded-xl bg-white px-4 py-3 text-sm font-bold text-gray-700 hover:text-[#1a5632] hover:shadow-sm transition-all">
                                        <span>{{ $label }}</span>
                                        <span class="text-xs text-[#1a5632]">Open</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>
@endsection
