<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true) || config('app.env') !== 'production') {
            return $next($request);
        }

        $canonicalRoot = rtrim((string) config('seo.canonical_url'), '/');
        $canonicalParts = parse_url($canonicalRoot);

        if (! isset($canonicalParts['scheme'], $canonicalParts['host'])) {
            return $next($request);
        }

        $canonicalPort = $canonicalParts['port'] ?? null;
        $requestPort = $request->getPort();
        $defaultRequestPort = $request->getScheme() === 'https' ? 443 : 80;
        $requestPort = $requestPort === $defaultRequestPort ? null : $requestPort;

        if (
            $request->getScheme() === $canonicalParts['scheme']
            && strcasecmp($request->getHost(), $canonicalParts['host']) === 0
            && $requestPort === $canonicalPort
        ) {
            return $next($request);
        }

        $path = '/'.ltrim($request->getRequestUri(), '/');

        return redirect()->away($canonicalRoot.($path === '/' ? '' : $path), 301);
    }
}
