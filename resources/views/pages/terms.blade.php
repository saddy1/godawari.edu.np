@extends('layouts.app')
@section('title', 'Terms of Service')
@section('meta_description', 'Terms of Service for this school website.')
@section('content')
<section class="relative py-24 bg-forest-DEFAULT">
    <div class="max-w-7xl mx-auto px-4 relative">
        <h1 class="font-display text-4xl text-white mb-2">Terms of Service</h1>
        <p class="font-body text-green-200">Last updated: {{ now()->format('F Y') }}</p>
    </div>
</section>
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="font-body text-gray-600 leading-relaxed mb-6">By using this website you agree to the following terms. The content on this website is for general informational purposes about {{ $siteSettings->localized('site_name', config('app.name')) }}. All information is subject to change without notice.</p>
        <p class="font-body text-gray-600 leading-relaxed">For questions, contact <a href="mailto:{{ $siteSettings->get('school_email') }}" class="text-forest-DEFAULT">{{ $siteSettings->get('school_email') }}</a>.</p>
    </div>
</section>
@endsection
