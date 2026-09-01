<?php

namespace Tests\Unit;

use App\Http\Middleware\EnforceCanonicalHost;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EnforceCanonicalHostTest extends TestCase
{
    public function test_it_permanently_redirects_safe_requests_to_the_canonical_host(): void
    {
        config(['app.env' => 'production', 'seo.canonical_url' => 'https://www.godawari.edu.np']);
        $request = Request::create('https://card.godawari.edu.np/pages/bsc-csit?source=test');

        $response = (new EnforceCanonicalHost())->handle(
            $request,
            fn () => new Response('next')
        );

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(
            'https://www.godawari.edu.np/pages/bsc-csit?source=test',
            $response->headers->get('Location')
        );
    }

    public function test_it_does_not_redirect_non_safe_requests(): void
    {
        config(['app.env' => 'production', 'seo.canonical_url' => 'https://www.godawari.edu.np']);
        $request = Request::create('https://card.godawari.edu.np/admissions', 'POST');

        $response = (new EnforceCanonicalHost())->handle(
            $request,
            fn () => new Response('next')
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('next', $response->getContent());
    }
}
