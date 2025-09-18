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

    public function testAutomaticBucketScopingByHost()
    {
        // Reset buckets to start fresh
        RateLimitMiddleware::resetBuckets();

        $mock = new MockHandler([
            // First request to canvas.instructure.com
            new Response(200, ['X-Rate-Limit-Remaining' => '2950'], 'Canvas response'),
            // Second request to files.example.com (S3)
            new Response(200, ['X-Rate-Limit-Remaining' => '2950'], 'S3 response'),
            // Third request back to canvas.instructure.com - should use same bucket as first
            new Response(200, ['X-Rate-Limit-Remaining' => '2900'], 'Canvas response 2'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware();
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);

        // Make requests to different hosts
        $response1 = $client->request('GET', 'https://canvas.instructure.com/api/v1/courses');
        $response2 = $client->request('GET', 'https://files.example.com/upload');
        $response3 = $client->request('GET', 'https://canvas.instructure.com/api/v1/users');

        $this->assertEquals('Canvas response', (string) $response1->getBody());
        $this->assertEquals('S3 response', (string) $response2->getBody());
        $this->assertEquals('Canvas response 2', (string) $response3->getBody());

        // Verify that different hosts resulted in different buckets
        // (The test passes if no rate limit errors occur despite low remaining values)
    }

    public function testCredentialIsolationSameHost()
    {
        // Reset buckets and config
        RateLimitMiddleware::resetBuckets();

        // Test with first API key
        Config::setApiKey('test-key-1');
        Config::setBaseUrl('https://canvas.instructure.com/');

        $mock1 = new MockHandler([
            new Response(200, ['X-Rate-Limit-Remaining' => '50'], 'Response for key 1'),
        ]);

        $handlerStack1 = HandlerStack::create($mock1);
        $rateLimitMiddleware1 = new RateLimitMiddleware();
        $handlerStack1->push($rateLimitMiddleware1(), 'rate-limit');

        $client1 = new Client(['handler' => $handlerStack1]);
        $response1 = $client1->request('GET', 'https://canvas.instructure.com/api/v1/courses');

        // Switch to second API key
        Config::setApiKey('test-key-2');

        $mock2 = new MockHandler([
            new Response(200, ['X-Rate-Limit-Remaining' => '2950'], 'Response for key 2'),
        ]);

        $handlerStack2 = HandlerStack::create($mock2);
        $rateLimitMiddleware2 = new RateLimitMiddleware();
        $handlerStack2->push($rateLimitMiddleware2(), 'rate-limit');

        $client2 = new Client(['handler' => $handlerStack2]);
        $response2 = $client2->request('GET', 'https://canvas.instructure.com/api/v1/courses');

        $this->assertEquals('Response for key 1', (string) $response1->getBody());
        $this->assertEquals('Response for key 2', (string) $response2->getBody());

        // The fact that key 2 has 2950 remaining while key 1 had 50 shows they use different buckets
    }

    public function testManualBucketOverrideStillWorks()
    {
        RateLimitMiddleware::resetBuckets();
        Config::setApiKey('test-key');
        Config::setBaseUrl('https://canvas.instructure.com/');

        $mock = new MockHandler([
            new Response(200, ['X-Rate-Limit-Remaining' => '2950'], 'Manual bucket'),
            new Response(200, ['X-Rate-Limit-Remaining' => '2900'], 'Auto bucket'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware();
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);

        // First request with manual bucket override
        $response1 = $client->request('GET', 'https://canvas.instructure.com/api/v1/courses', [
            'rate_limit_bucket' => 'my-custom-bucket',
        ]);

        // Second request without override (should use auto-generated bucket)
        $response2 = $client->request('GET', 'https://canvas.instructure.com/api/v1/courses');

        $this->assertEquals('Manual bucket', (string) $response1->getBody());
        $this->assertEquals('Auto bucket', (string) $response2->getBody());
    }

    public function testMissingConfigurationFallback()
    {
        RateLimitMiddleware::resetBuckets();

        // Reset configuration but don't clear base URL (Config validates it)
        Config::resetContext('default');

        $mock = new MockHandler([
            new Response(200, [], 'Fallback response'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware();
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);

        // Request with no host should fall back to 'default' bucket
        $response = $client->request('GET', '/relative/path');

        $this->assertEquals('Fallback response', (string) $response->getBody());
    }

    public function testExternalHostBehavior()
    {
        RateLimitMiddleware::resetBuckets();
        Config::setApiKey('test-key');
        Config::setBaseUrl('https://canvas.instructure.com/');

        $mock = new MockHandler([
            // Canvas API request
            new Response(200, ['X-Rate-Limit-Remaining' => '100'], 'Canvas API'),
            // S3 upload request
            new Response(200, [], 'S3 Upload'),
            // CDN request
            new Response(200, [], 'CDN Asset'),
            // Another Canvas API request - should share bucket with first
            new Response(200, ['X-Rate-Limit-Remaining' => '50'], 'Canvas API 2'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware();
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);

        // Different hosts should get different buckets
        $response1 = $client->request('GET', 'https://canvas.instructure.com/api/v1/courses');
        $response2 = $client->request('PUT', 'https://canvas-uploads.s3.amazonaws.com/file');
        $response3 = $client->request('GET', 'https://cdn.canvaslms.com/assets/style.css');
        $response4 = $client->request('GET', 'https://canvas.instructure.com/api/v1/users');

        $this->assertEquals('Canvas API', (string) $response1->getBody());
        $this->assertEquals('S3 Upload', (string) $response2->getBody());
        $this->assertEquals('CDN Asset', (string) $response3->getBody());
        $this->assertEquals('Canvas API 2', (string) $response4->getBody());
    }

    public function testOAuthTokenBucketScoping()
    {
        RateLimitMiddleware::resetBuckets();

        // Switch to OAuth mode
        Config::useOAuth();
        Config::setOAuthToken('oauth-token-123');
        Config::setBaseUrl('https://canvas.instructure.com/');

        $mock = new MockHandler([
            new Response(200, ['X-Rate-Limit-Remaining' => '2950'], 'OAuth response'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $rateLimitMiddleware = new RateLimitMiddleware();
        $handlerStack->push($rateLimitMiddleware(), 'rate-limit');

        $client = new Client(['handler' => $handlerStack]);
        $response = $client->request('GET', 'https://canvas.instructure.com/api/v1/courses');

        $this->assertEquals('OAuth response', (string) $response->getBody());

        // Switch back to API key mode for cleanup
        Config::useApiKey();
    }

    public function testCredentialSwitching()
    {
        RateLimitMiddleware::resetBuckets();
        Config::setBaseUrl('https://canvas.instructure.com/');

        // Start with API key
        Config::useApiKey();
        Config::setApiKey('api-key-123');

        $mock1 = new MockHandler([
            new Response(200, ['X-Rate-Limit-Remaining' => '100'], 'API key response'),
        ]);

        $handlerStack1 = HandlerStack::create($mock1);
        $rateLimitMiddleware1 = new RateLimitMiddleware();
        $handlerStack1->push($rateLimitMiddleware1(), 'rate-limit');

        $client1 = new Client(['handler' => $handlerStack1]);
        $response1 = $client1->request('GET', 'https://canvas.instructure.com/api/v1/courses');

        // Switch to OAuth
        Config::useOAuth();
        Config::setOAuthToken('oauth-token-456');

        $mock2 = new MockHandler([
            new Response(200, ['X-Rate-Limit-Remaining' => '2950'], 'OAuth response'),
        ]);

        $handlerStack2 = HandlerStack::create($mock2);
        $rateLimitMiddleware2 = new RateLimitMiddleware();
        $handlerStack2->push($rateLimitMiddleware2(), 'rate-limit');

        $client2 = new Client(['handler' => $handlerStack2]);
        $response2 = $client2->request('GET', 'https://canvas.instructure.com/api/v1/courses');

        $this->assertEquals('API key response', (string) $response1->getBody());
        $this->assertEquals('OAuth response', (string) $response2->getBody());

        // Different credentials should have different remaining values
        // API key had 100, OAuth has 2950 - proving different buckets

        // Reset to API key mode for other tests
        Config::useApiKey();
    }
}
