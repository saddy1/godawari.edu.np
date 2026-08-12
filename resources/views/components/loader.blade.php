{{-- Public-site first-load splash. It appears only when the page takes noticeable time to finish loading. --}}
<div id="page-loader" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-white">
    <div class="flex flex-col items-center justify-center px-6 text-center">
        <div class="mb-5 flex h-28 w-28 items-center justify-center rounded-2xl border border-gray-100 bg-white p-4 shadow-xl">
            @if($logoUrl = isset($siteSettings) ? $siteSettings->logoUrl() : asset('assets/image/logo.png'))
                <img src="{{ $logoUrl }}" alt="School Logo" class="h-full w-full object-contain">
            @else
                <svg class="h-20 w-20 text-[#1a5632]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.753 2 16.5S6.5 26.747 12 26.747s10-4.5 10-10.247S17.5 6.253 12 6.253z"></path>
                </svg>
            @endif
        </div>
        <h2 class="max-w-sm text-xl font-extrabold text-[#0b2415]">
            {{ isset($siteSettings) ? $siteSettings->localized('site_name', 'School') : 'School' }}
        </h2>
        <p class="mt-2 text-sm font-semibold text-gray-500">Loading page...</p>
    </div>
</div>

<style>
    #page-loader {
        opacity: 0;
        transition: opacity 0.25s ease-out, visibility 0.25s ease-out;
    }

    #page-loader.is-visible {
        display: flex;
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    let publicLoaderTimer = setTimeout(() => {
        const loader = document.getElementById('page-loader');
        if (loader) {
            loader.classList.remove('hidden');
            loader.classList.add('is-visible');
        }
    }, 350);

    window.addEventListener('load', function() {
        clearTimeout(publicLoaderTimer);
        const loader = document.getElementById('page-loader');
        if (loader) {
            loader.classList.remove('is-visible');
            setTimeout(() => loader.classList.add('hidden'), 250);
        }
    });

    setTimeout(() => {
        clearTimeout(publicLoaderTimer);
        const loader = document.getElementById('page-loader');
        if (loader) {
            loader.classList.remove('is-visible');
            loader.classList.add('hidden');
        }
    }, 5000);
</script>
