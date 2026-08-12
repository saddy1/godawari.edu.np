@extends('layouts.app')
@section('title', 'Privacy Policy')
@section('meta_description', 'Privacy Policy. Learn how we collect, use, and protect your personal information.')
@section('content')
<section class="relative py-24 bg-forest-DEFAULT">
    <div class="max-w-7xl mx-auto px-4 relative">
        <h1 class="font-display text-4xl text-white mb-2">Privacy Policy</h1>
        <p class="font-body text-green-200">Last updated: {{ now()->format('F Y') }}</p>
    </div>
</section>
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 prose prose-green max-w-none">
        <h2 class="font-display text-2xl text-forest-dark">Information We Collect</h2>
        <p class="font-body text-gray-600 leading-relaxed">{{ $siteSettings->localized('site_name', config('app.name')) }} collects personal information such as your name, contact details, and student information only when you submit admission inquiries or contact forms on this website. This information is used solely to respond to your requests and is never sold to third parties.</p>

        <h2 class="font-display text-2xl text-forest-dark mt-8">How We Use Your Information</h2>
        <p class="font-body text-gray-600 leading-relaxed">Information submitted through our forms is used to process admission inquiries, respond to messages, and send relevant school updates to interested parties.</p>

        <h2 class="font-display text-2xl text-forest-dark mt-8">Contact</h2>
        <p class="font-body text-gray-600 leading-relaxed">For any privacy-related concerns, please contact us at <a href="mailto:{{ $siteSettings->get('school_email') }}" class="text-forest-DEFAULT">{{ $siteSettings->get('school_email') }}</a>.</p>
    </div>
</section>
@endsection
