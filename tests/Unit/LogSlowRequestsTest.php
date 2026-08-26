<?php

namespace Tests\Unit;

use App\Http\Middleware\LogSlowRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class LogSlowRequestsTest extends TestCase
{
    public function test_it_adds_the_timing_header_to_streamed_downloads(): void
    {
        $response = (new LogSlowRequests)->handle(
            Request::create('/download-template'),
            fn () => new StreamedResponse(fn () => print 'download')
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertMatchesRegularExpression('/^\d+(?:\.\d+)?ms$/', (string) $response->headers->get('X-Response-Time'));
    }
}
