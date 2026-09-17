<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ $siteSettings->localized('site_name', 'School') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $siteSettings->faviconUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-lg text-center">
            <div class="relative mx-auto mb-7 flex h-24 w-24 items-center justify-center rounded-3xl bg-white shadow-xl ring-1 ring-gray-100">
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->localized('site_name', 'School') }}" class="h-14 w-14 rounded-2xl object-contain">
            </div>

            <div class="rounded-3xl border border-gray-200 bg-white p-7 shadow-sm sm:p-10">
                <h1 class="text-2xl font-black tracking-tight text-gray-950 sm:text-3xl">{{ $title }}</h1>
                <p class="mx-auto mt-3 max-w-md text-sm font-medium leading-6 text-gray-500">
                    {{ $message }}
                </p>

                <div class="mt-7 flex justify-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415]">
                        Back to Home
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
