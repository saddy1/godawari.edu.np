{{-- Shared PWA head tags. Include with: manifest, appleIcon, icon192, themeColor, appTitle --}}
<meta name="theme-color" content="{{ $themeColor }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ $appTitle }}">
<link rel="manifest" href="{{ asset($manifest) }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset($appleIcon) }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset($icon192) }}">
