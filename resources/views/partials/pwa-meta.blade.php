{{-- Shared PWA head tags. Include with: manifest, appleIcon, icon192, themeColor, appTitle --}}
@php
    // Cache-bust the icon links off the file's own mtime, so a regenerated icon
    // (e.g. after the site logo changes) is picked up without a hard refresh.
    $appleIconVersion = is_file(public_path($appleIcon)) ? filemtime(public_path($appleIcon)) : null;
    $icon192Version = is_file(public_path($icon192)) ? filemtime(public_path($icon192)) : null;
@endphp
<meta name="theme-color" content="{{ $themeColor }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ $appTitle }}">
<link rel="manifest" href="{{ asset($manifest) }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset($appleIcon) }}{{ $appleIconVersion ? '?v='.$appleIconVersion : '' }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset($icon192) }}{{ $icon192Version ? '?v='.$icon192Version : '' }}">
