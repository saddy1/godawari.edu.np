{{-- Public-site first-load splash. Styled with plain inline CSS (no Tailwind classes) so it
     renders correctly the instant the HTML paints — it exists specifically to cover the gap
     before the built stylesheet and web fonts have finished loading, so it can't depend on them. --}}
<div id="page-loader" style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:#fff;opacity:1;visibility:visible;transition:opacity .25s ease-out,visibility .25s ease-out;">
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:0 1.5rem;text-align:center;">
        <div style="margin-bottom:1.25rem;display:flex;height:7rem;width:7rem;align-items:center;justify-content:center;border-radius:1rem;border:1px solid #f3f4f6;background:#fff;padding:1rem;box-shadow:0 20px 25px -5px rgb(0 0 0 / .1),0 8px 10px -6px rgb(0 0 0 / .1);">
            @if($logoUrl = isset($siteSettings) ? $siteSettings->logoUrl() : asset('assets/image/logo.png'))
                <img src="{{ $logoUrl }}" alt="School Logo" style="height:100%;width:100%;object-fit:contain;">
            @else
                <svg style="height:5rem;width:5rem;color:var(--theme-primary,#1a5632);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.753 2 16.5S6.5 26.747 12 26.747s10-4.5 10-10.247S17.5 6.253 12 6.253z"></path>
                </svg>
            @endif
        </div>
        <h2 style="max-width:24rem;font-size:1.25rem;font-weight:800;color:var(--theme-dark,#0b2415);margin:0;">
            {{ isset($siteSettings) ? $siteSettings->localized('site_name', 'School') : 'School' }}
        </h2>
        <p style="margin:.5rem 0 0;font-size:.875rem;font-weight:600;color:#6b7280;">Loading page…</p>
    </div>
</div>

<script>
    (function () {
        var loader = document.getElementById('page-loader');
        if (!loader) return;
        function hide() {
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';
            setTimeout(function () { loader.style.display = 'none'; }, 250);
        }
        window.addEventListener('load', hide);
        setTimeout(hide, 5000); // safety net in case 'load' never fires
    })();
</script>
