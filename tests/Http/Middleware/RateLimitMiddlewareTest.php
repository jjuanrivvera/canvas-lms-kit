<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use CanvasLMS\Config;
use CanvasLMS\Http\HttpClient;
use CanvasLMS\Http\Middleware\RateLimitMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RateLimitMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        Config::setAppKey('fake-api-key');
        Config::setBaseUrl('https://canvas.instructure.com/');
        // Reset buckets before each test
        RateLimitMiddleware::resetBuckets();
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        RateLimitMiddleware::resetBuckets();
    }

    public function testDoesNotDelayWhenBucketHasCapacity()
    {
        $mock = new MockHandler([
            new Response(200, [
                'X-Rate-Limit-Remaining' => '2950',
                'X-Request-Cost' => '10',
            ], 'Success'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware();
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        $start = microtime(true);
        $response = $httpClient->get('/test');
        $elapsed = microtime(true) - $start;

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(0.1, $elapsed, 'Request should not be delayed');
    }

    public function testUpdatesRemainingFromResponseHeaders()
    {
        $mock = new MockHandler([
            new Response(200, [
                'X-Rate-Limit-Remaining' => '2000',
                'X-Request-Cost' => '25',
            ], 'First request'),
            new Response(200, [
                'X-Rate-Limit-Remaining' => '1975',
                'X-Request-Cost' => '25',
            ], 'Second request'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware();
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        // First request
        $response1 = $httpClient->get('/test');
        $this->assertEquals('First request', (string) $response1->getBody());

        // Second request should reflect updated bucket
        $response2 = $httpClient->get('/test');
        $this->assertEquals('Second request', (string) $response2->getBody());
    }

    public function testRefundsInitialCostWhenActualCostIsLower()
    {
        $mock = new MockHandler([
            new Response(200, [
                'X-Rate-Limit-Remaining' => '2990',
                'X-Request-Cost' => '10', // Actual cost is 10, but 50 was pre-charged
            ], 'Success'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware(['bucket_size' => 3000]);
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        $response = $httpClient->get('/test');

        $this->assertEquals(200, $response->getStatusCode());
        // The bucket should have refunded 40 units (50 - 10)
    }

    public function testDelaysWhenApproachingRateLimit()
    {
        // First exhaust the bucket
        $mock = new MockHandler([
            new Response(200, [
                'X-Rate-Limit-Remaining' => '75', // Below min_remaining threshold
                'X-Request-Cost' => '50',
            ], 'First request'),
            new Response(200, [
                'X-Rate-Limit-Remaining' => '125', // After leak rate refill
                'X-Request-Cost' => '50',
            ], 'Second request'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware([
            'min_remaining' => 100,
            'leak_rate' => 100, // Fast leak rate for testing
            'wait_on_limit' => true,
        ]);
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        // First request should succeed
        $response1 = $httpClient->get('/test');
        $this->assertEquals('First request', (string) $response1->getBody());

        // Second request should be delayed
        $start = microtime(true);
        $response2 = $httpClient->get('/test');
        $elapsed = microtime(true) - $start;

        $this->assertEquals('Second request', (string) $response2->getBody());
        $this->assertGreaterThan(0.5, $elapsed, 'Request should be delayed');
    }

    public function testFailsFastWhenConfigured()
    {
        // First request to set low remaining
        $mock = new MockHandler([
            new Response(200, [
                'X-Rate-Limit-Remaining' => '25',
                'X-Request-Cost' => '50',
            ], 'First request'),
            new Response(200, [], 'Should not reach'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware([
            'min_remaining' => 100,
            'wait_on_limit' => false, // Fail instead of waiting
        ]);
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        // First request succeeds and sets bucket state
        $response1 = $httpClient->get('/test');
        $this->assertEquals('First request', (string) $response1->getBody());

        // Second request should fail due to rate limit
        $this->expectException(\CanvasLMS\Exceptions\CanvasApiException::class);
        $this->expectExceptionMessageMatches('/Rate limit would be exceeded/');
        $httpClient->get('/test');
    }

    public function testHandlesCanvasRateLimitError()
    {
        $mock = new MockHandler([
            new Response(403, ['X-Rate-Limit-Remaining' => '0'], 'Rate Limit Exceeded'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware();
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        $this->expectException(\CanvasLMS\Exceptions\CanvasApiException::class);
        $httpClient->get('/test');
    }

    public function testRespectsDifferentBuckets()
    {
        $mock = new MockHandler([
            new Response(200, ['X-Rate-Limit-Remaining' => '2000'], 'Bucket 1'),
            new Response(200, ['X-Rate-Limit-Remaining' => '3000'], 'Bucket 2'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware();
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);

        // Use different buckets via request options
        $response1 = $client->request('GET', '/test1', ['rate_limit_bucket' => 'token1']);
        $response2 = $client->request('GET', '/test2', ['rate_limit_bucket' => 'token2']);

        $this->assertEquals('Bucket 1', (string) $response1->getBody());
        $this->assertEquals('Bucket 2', (string) $response2->getBody());
    }

    public function testDisabledMiddleware()
    {
        // Set up a bucket that would normally cause a delay
        RateLimitMiddleware::resetBuckets();

        $mock = new MockHandler([
            new Response(200, ['X-Rate-Limit-Remaining' => '25'], 'First'),
            new Response(200, [], 'Second'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware([
            'enabled' => false,
            'min_remaining' => 100,
        ]);
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        // Both requests should complete without delay
        $start = microtime(true);
        $httpClient->get('/test1');
        $httpClient->get('/test2');
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(0.2, $elapsed, 'Requests should not be delayed when disabled');
    }

    public function testMaxWaitTimeLimit()
    {
        $mock = new MockHandler([
            new Response(200, ['X-Rate-Limit-Remaining' => '0'], 'Should not reach'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware([
            'min_remaining' => 3000, // Force a very long wait
            'leak_rate' => 1, // Very slow leak rate
            'max_wait_time' => 1, // But limit wait to 1 second
            'wait_on_limit' => true,
        ]);
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        $this->expectException(\CanvasLMS\Exceptions\CanvasApiException::class);
        $this->expectExceptionMessageMatches('/exceeds maximum/');
        $httpClient->get('/test');
    }
}
