{{-- Remembers scroll position across a form submit + redirect-back to the
     same page, so saving something near the bottom doesn't bounce you to
     the top. --}}
<script>
(function () {
    var KEY = 'scrollPos:' + location.pathname;
    var saved = sessionStorage.getItem(KEY);
    if (saved !== null) {
        requestAnimationFrame(function () { window.scrollTo(0, parseInt(saved, 10)); });
        sessionStorage.removeItem(KEY);
    }
    document.addEventListener('submit', function (event) {
        if (event.target && event.target.tagName === 'FORM') {
            sessionStorage.setItem(KEY, String(window.scrollY));
        }
    }, true);
})();
</script>
