@extends('layouts.app')

@section('title', __('site.vacancy.page_title'))
@section('meta_description', __('site.vacancy.meta_desc'))
@section('meta_keywords', 'school jobs, teaching jobs Nepal, teacher vacancy Nepal, school staff jobs, education jobs')

@section('schema')
@if($vacancies->count())
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ItemList",
    "name": {{ json_encode('Job Vacancies at ' . $siteSettings->localized('site_name', config('app.name'))) }},
    "itemListElement": [
        @foreach($vacancies as $i => $v)
        {
            "@type": "ListItem",
            "position": {{ $i + 1 }},
            "item": {
                "@type": "JobPosting",
                "title": "{{ $v->title }}",
                "description": "{{ strip_tags($v->description) }}",
                "hiringOrganization": {
                    "@type": "Organization",
                    "name": {{ json_encode($siteSettings->localized('site_name', config('app.name'))) }},
                    "sameAs": "{{ url('/') }}",
                    "logo": "{{ $siteSettings->logoUrl() }}"
                },
                "jobLocation": {
                    "@type": "Place",
                    "address": {
                        "@type": "PostalAddress",
                        "streetAddress": {{ json_encode($siteSettings->get('school_street', 'Nepal')) }},
                        "addressLocality": {{ json_encode($siteSettings->get('school_locality', '')) }},
                        "addressRegion": {{ json_encode($siteSettings->get('school_region', '')) }},
                        "addressCountry": "NP"
                    }
                },
                "employmentType": "{{ strtoupper(str_replace(' ', '_', $v->type)) }}",
                "datePosted": "{{ $v->created_at->toIso8601String() }}",
                @if($v->deadline)"validThrough": "{{ $v->deadline->toIso8601String() }}",@endif
                "directApply": true
            }
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
@endif
@endsection

@section('content')

{{-- ============================================================ --}}
{{-- HERO SECTION --}}
{{-- ============================================================ --}}
<section class="relative py-12 sm:py-16 overflow-hidden bg-linear-to-br from-[#0b2415] via-[#1a5632] to-[#0b2415]">
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 30px 30px;"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-[#e2a024]/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 animate-[pulse_6s_infinite]"></div>
    <div class="absolute bottom-0 left-0 w-80 h-80 bg-white/10 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <nav class="flex items-center gap-2 text-green-200 text-sm font-medium mb-8" aria-label="Breadcrumb" data-aos="fade-down">
            <a href="{{ route('home') }}" class="hover:text-[#e2a024] hover:underline transition-colors">{{ __('site.common.home') }}</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-white">{{ __('site.vacancy.breadcrumb') }}</span>
        </nav>

        <div class="inline-flex items-center gap-2 bg-[#e2a024] text-[#0b2415] font-bold text-sm px-6 py-2.5 rounded-full mb-6 shadow-[0_0_15px_rgba(226,160,36,0.4)]" data-aos="fade-up" data-aos-delay="50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            {{ $vacancies->count() }} {{ __('site.vacancy.open_label') }}
        </div>

        <h1 class="text-4xl lg:text-5xl xl:text-6xl font-bold text-white mb-6 tracking-tight" data-aos="fade-up" data-aos-delay="100">
            {{ __('site.vacancy.hero_h1') }}
        </h1>

        <p class="text-green-100/90 text-lg md:text-xl max-w-2xl leading-relaxed" data-aos="fade-up" data-aos-delay="150">
            {{ __('site.vacancy.hero_sub', ['school_name' => $siteSettings->localized('site_name', config('app.name'))]) }}
        </p>

        @if($user)
        <div class="mt-6 inline-flex items-center gap-3 bg-white/10 backdrop-blur-sm text-white text-sm font-medium px-5 py-2.5 rounded-full border border-white/20" data-aos="fade-up" data-aos-delay="200">
            <svg class="w-4 h-4 text-[#e2a024]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Logged in as <strong>{{ $user->name }}</strong>
            &nbsp;·&nbsp;
            <form method="POST" action="{{ route('applicant.logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-red-300 hover:text-red-200 font-bold underline">Logout</button>
            </form>
        </div>
        @endif
    </div>
</section>

{{-- ============================================================ --}}
{{-- VACANCY LISTINGS --}}
{{-- ============================================================ --}}
<section class="py-16 bg-[#fdfbf7]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12" data-aos="fade-up">
            <p class="text-[#e2a024] font-bold text-sm uppercase tracking-widest mb-3 flex items-center justify-center gap-2">
                <span class="w-8 h-0.5 bg-[#e2a024]"></span> Current Openings <span class="w-8 h-0.5 bg-[#e2a024]"></span>
            </p>
            <h2 class="text-3xl font-bold text-[#0b2415]">{{ __('site.vacancy.open_h2') }}</h2>
        </div>

        @forelse($vacancies as $vacancy)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden hover:shadow-md transition-shadow duration-300" data-aos="fade-up" id="vacancy-{{ $vacancy->id }}">

            {{-- Featured Image --}}
            @if($vacancy->featured_image)
            <div class="w-full h-48 overflow-hidden">
                <img src="{{ asset($vacancy->featured_image) }}" alt="{{ $vacancy->title }}"
                    class="w-full h-full object-cover">
            </div>
            @endif

            <div class="p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="bg-[#1a5632]/10 text-[#1a5632] text-xs font-bold px-3 py-1 rounded-full">{{ $vacancy->type }}</span>
                            @if($vacancy->department)
                            <span class="bg-[#e2a024]/10 text-[#b07d10] text-xs font-bold px-3 py-1 rounded-full">{{ $vacancy->department }}</span>
                            @endif
                            @if($vacancy->deadline)
                                @if($vacancy->isExpired())
                                <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-1 rounded-full">Closed</span>
                                @else
                                <span class="bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1 rounded-full">{{ __('site.vacancy.deadline') }}: {{ $vacancy->deadline->format('M d, Y') }}</span>
                                @endif
                            @endif
                        </div>
                        <h3 class="text-xl font-bold text-[#0b2415]">{{ $vacancy->title }}</h3>
                    </div>

                    {{-- Document Buttons --}}
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if($vacancy->document_path)
                        <a href="{{ asset($vacancy->document_path) }}" target="_blank" download
                           class="flex items-center gap-2 px-4 py-2.5 bg-[#e2a024] text-[#0b2415] font-bold text-sm rounded-xl hover:bg-[#c8891a] transition-colors duration-200 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Download Notice
                        </a>
                        <button onclick="printDoc('{{ asset($vacancy->document_path) }}')"
                           class="flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-200 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Print Notice
                        </button>
                        @endif
                    </div>
                </div>

                <div class="text-gray-600 text-sm leading-relaxed mb-4 prose prose-sm max-w-none">
                    {!! nl2br(e($vacancy->description)) !!}
                </div>

                @if($vacancy->requirements)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="font-bold text-[#0b2415] text-sm mb-2">Requirements:</p>
                    <div class="text-gray-600 text-sm leading-relaxed">
                        {!! nl2br(e($vacancy->requirements)) !!}
                    </div>
                </div>
                @endif

                {{-- Apply Section --}}
                @if(!$vacancy->isExpired())
                <div class="mt-6">
                    @if(!$user)
                    {{-- Not logged in --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div class="flex-1">
                            <p class="font-bold text-blue-900 text-sm">Register or log in to apply for this position</p>
                            <p class="text-blue-700 text-xs mt-1">Create a free account, verify your email, then submit your application.</p>
                        </div>
                        <div class="flex gap-3 shrink-0">
                            <a href="{{ route('register') }}"
                                class="px-5 py-2.5 bg-[#1a5632] text-white font-bold text-sm rounded-xl hover:bg-[#0b2415] transition-colors">
                                Register
                            </a>
                            <a href="{{ route('applicant.login') }}"
                                class="px-5 py-2.5 bg-white border border-[#1a5632] text-[#1a5632] font-bold text-sm rounded-xl hover:bg-[#1a5632]/5 transition-colors">
                                Login
                            </a>
                        </div>
                    </div>

                    @elseif(!$user->hasVerifiedEmail())
                    {{-- Logged in but email not verified --}}
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5">
                        <p class="font-bold text-yellow-900 text-sm">Please verify your email to apply</p>
                        <p class="text-yellow-700 text-xs mt-1 mb-3">Check your inbox for a verification link.</p>
                        <a href="{{ route('verification.notice') }}" class="text-sm font-bold text-[#1a5632] hover:underline">Resend verification email →</a>
                    </div>

                    @elseif(in_array($vacancy->id, $appliedVacancyIds))
                    {{-- Already applied --}}
                    <div class="inline-flex items-center gap-2 px-5 py-3 bg-green-50 border border-green-200 text-green-700 font-bold text-sm rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        You have already applied for this position
                    </div>

                    @else
                    <a href="{{ route('vacancy.apply.create', $vacancy) }}"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-[#1a5632] text-white font-bold text-sm rounded-xl hover:bg-[#0b2415] transition-colors duration-200 shadow-sm">
                        Start Application
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    @endif
                </div>

                @else
                <div class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 text-gray-500 font-bold text-sm rounded-xl cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ __('site.vacancy.deadline') }} {{ __('site.common.past') ?? 'Passed' }}
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-20" data-aos="fade-up">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-[#0b2415] mb-2">{{ __('site.vacancy.no_vacancies') }}</h3>
            <p class="text-gray-500 text-sm max-w-md mx-auto">There are currently no open vacancies. Please check back later or send your CV to
                <a href="mailto:{{ $siteSettings->get('school_email') }}" class="text-[#1a5632] font-bold hover:underline">{{ $siteSettings->get('school_email') }}</a>
                for future consideration.
            </p>
        </div>
        @endforelse
    </div>
</section>

{{-- ============================================================ --}}
{{-- WHY JOIN US SECTION --}}
{{-- ============================================================ --}}
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12" data-aos="fade-up">
            <p class="text-[#e2a024] font-bold text-sm uppercase tracking-widest mb-3 flex items-center justify-center gap-2">
                <span class="w-8 h-0.5 bg-[#e2a024]"></span> Why Join Us <span class="w-8 h-0.5 bg-[#e2a024]"></span>
            </p>
            <h2 class="text-3xl font-bold text-[#0b2415]">A Great Place to Teach & Grow</h2>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'title' => 'Professional Development', 'desc' => 'Regular trainings, workshops, and opportunities to upgrade your skills and teaching methods.'],
                ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'title' => 'Collaborative Community', 'desc' => 'Work alongside experienced and passionate educators in a supportive, team-oriented environment.'],
                ['icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', 'title' => 'Recognized Institution', 'desc' => 'Join a historic community government school established since 2017 B.S. with general, technical, and inclusive education.'],
                ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'Competitive Salary', 'desc' => 'Attractive and competitive remuneration packages with timely disbursement and performance incentives.'],
                ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'title' => 'Modern Infrastructure', 'desc' => 'Access to well-equipped classrooms, laboratories, libraries, and modern teaching tools and resources.'],
                ['icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'title' => 'Meaningful Impact', 'desc' => 'Make a real difference in the lives of students and contribute to building the future of our community.'],
            ] as $i => $benefit)
            <div class="group bg-[#fdfbf7] rounded-2xl p-6 border border-gray-100 hover:border-[#1a5632]/20 hover:shadow-md transition-all duration-300" data-aos="fade-up" data-aos-delay="{{ $i * 50 }}">
                <div class="w-12 h-12 bg-[#1a5632]/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-[#1a5632] transition-colors duration-300">
                    <svg class="w-6 h-6 text-[#1a5632] group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $benefit['icon'] }}"/>
                    </svg>
                </div>
                <h3 class="font-bold text-[#0b2415] mb-2 group-hover:text-[#1a5632] transition-colors">{{ $benefit['title'] }}</h3>
                <p class="text-gray-500 text-sm leading-relaxed">{{ $benefit['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- CTA SECTION --}}
{{-- ============================================================ --}}
<section class="py-16 bg-linear-to-r from-[#0b2415] to-[#1a5632]" data-aos="fade-up">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Don't See Your Role?</h2>
        <p class="text-green-100/80 text-lg mb-8 max-w-xl mx-auto">Send your CV and a brief cover letter to our email. We keep promising candidates on file for future openings.</p>
        <a href="mailto:{{ $siteSettings->get('school_email') }}"
           class="inline-flex items-center gap-2 px-8 py-4 bg-[#e2a024] text-[#0b2415] font-bold rounded-xl hover:bg-white transition-colors duration-300 shadow-lg text-base">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Send Your CV
        </a>
    </div>
</section>

@endsection

@push('scripts')
<script>
    function printDoc(url) {
        var iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = url;
        document.body.appendChild(iframe);
        iframe.onload = function() {
            try {
                iframe.contentWindow.print();
            } catch (e) {
                // Fallback: open in new tab so browser can print
                window.open(url, '_blank');
            }
        };
    }
</script>
@endpush
