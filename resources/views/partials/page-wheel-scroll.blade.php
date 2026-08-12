<script>
document.addEventListener('DOMContentLoaded', () => {
    const innerScrollSelector = 'main [class*="overflow-y-auto"], main [class*="overflow-y-scroll"], main [class*="overflow-x-auto"], main [class*="overflow-x-scroll"]';

    document.addEventListener('wheel', (event) => {
        if (event.defaultPrevented || Math.abs(event.deltaY) <= Math.abs(event.deltaX)) {
            return;
        }

        const innerScroller = event.target.closest(innerScrollSelector);

        if (! innerScroller || innerScroller.dataset.keepInnerScroll === 'true') {
            return;
        }

        if (innerScroller.closest('[role="dialog"], [aria-modal="true"], .fixed')) {
            return;
        }

        const pageScroller = innerScroller.closest('[data-page-scroll-root]');

        if (! pageScroller || pageScroller === innerScroller) {
            return;
        }

        pageScroller.scrollTop += event.deltaY;
        event.preventDefault();
    }, { passive: false });
});
</script>
