{{-- Shared across every module layout — a top progress bar fires the instant a link/form is
     clicked, and a "still working" pill appears on genuinely slow loads, so a multi-second page
     load never looks like the app hung. See resources/views/partials/page-loader.blade.php --}}
<div id="page-loader-bar-track" class="fixed inset-x-0 top-0 z-[9999] h-[3px] w-full" style="display:none">
    <div id="page-loader-bar" class="h-full bg-emerald-600" style="width:0%;transition:width .3s ease-out,opacity .2s ease-in"></div>
</div>
<div id="page-loader-pill" class="fixed bottom-4 right-4 z-[9999] flex items-center gap-2 rounded-full bg-slate-900/90 px-4 py-2.5 text-xs font-bold text-white shadow-lg" style="display:none">
    <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25"></circle><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path></svg>
    <span id="page-loader-pill-text">Loading…</span>
</div>
<script>
(function () {
    var track = document.getElementById('page-loader-bar-track');
    var bar = document.getElementById('page-loader-bar');
    var pill = document.getElementById('page-loader-pill');
    var pillText = document.getElementById('page-loader-pill-text');
    if (!track || !bar || !pill) return;

    var pillTimer, slowTimer, active = false;

    function start() {
        if (active) return;
        active = true;
        track.style.display = 'block';
        bar.style.opacity = '1';
        bar.style.width = '0%';
        requestAnimationFrame(function () { bar.style.width = '40%'; });
        setTimeout(function () { if (active) bar.style.width = '70%'; }, 350);

        // Fast loads never show this — only loads that genuinely take a moment do.
        pillTimer = setTimeout(function () {
            if (!active) return;
            pillText.textContent = 'Loading…';
            pill.style.display = 'flex';
        }, 600);
        slowTimer = setTimeout(function () {
            if (!active) return;
            pillText.textContent = 'Still working, hang on…';
        }, 5000);
    }

    function reset() {
        active = false;
        clearTimeout(pillTimer);
        clearTimeout(slowTimer);
        track.style.display = 'none';
        pill.style.display = 'none';
        bar.style.width = '0%';
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link) return;
        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download') || link.hasAttribute('data-no-page-loader')) return;
        if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button === 1) return;
        var href = link.getAttribute('href') || '';
        if (href === '' || href[0] === '#' || href.indexOf('javascript:') === 0 || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return;
        start();
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form instanceof HTMLFormElement && !form.hasAttribute('data-no-page-loader') && !event.defaultPrevented) start();
    });

    // Back/forward-cache restores (and any leftover state) must never leave a stale bar on screen.
    window.addEventListener('pageshow', reset);
    window.addEventListener('pagehide', reset);
})();
</script>
