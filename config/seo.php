<?php

return [
    // All public pages, sitemaps, schema identifiers, and host redirects use
    // this single origin in production so authority is not split by subdomains.
    'canonical_url' => env('SEO_CANONICAL_URL', 'https://www.godawari.edu.np'),
];
