<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Request Needed | {{ $siteSettings->localized('site_name', 'School') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $siteSettings->faviconUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes float-card {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse-ring {
            0% { transform: scale(.9); opacity: .55; }
            70% { transform: scale(1.25); opacity: 0; }
            100% { transform: scale(1.25); opacity: 0; }
        }

        .access-card { animation: float-card 4s ease-in-out infinite; }
        .pulse-ring { animation: pulse-ring 2.2s ease-out infinite; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-2xl text-center">
            <div class="access-card relative mx-auto mb-7 flex h-28 w-28 items-center justify-center rounded-3xl bg-white shadow-xl ring-1 ring-gray-100">
                <span class="pulse-ring absolute inset-0 rounded-3xl bg-[#1a5632]/20"></span>
                <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->localized('site_name', 'School') }}" class="relative h-16 w-16 rounded-2xl object-contain">
            </div>

            <div class="rounded-3xl border border-gray-200 bg-white p-7 shadow-sm sm:p-10">
                <p class="text-sm font-extrabold uppercase tracking-[0.35em] text-[#e2a024]">403</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-gray-950 sm:text-5xl">Oops!</h1>
                <p class="mt-3 text-xl font-extrabold text-gray-800">Ask admin for this access.</p>
                <p class="mx-auto mt-3 max-w-xl text-sm font-medium leading-6 text-gray-500">
                    Your account is active, but this page needs extra permission. Please contact Super Admin, Principal, or Administrator to assign the correct module access.
                </p>

                <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-extrabold text-gray-700 hover:bg-gray-50">
                        Go Back
                    </a>
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415]">
                        Open Dashboard
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
