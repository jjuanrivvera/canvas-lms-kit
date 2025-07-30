<?php

namespace Tests\Http\Middleware;

use CanvasLMS\Config;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use CanvasLMS\Http\Middleware\RetryMiddleware;

class RetryMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        Config::setAppKey('fake-api-key');
        Config::setBaseUrl('https://canvas.instructure.com/');
    }

    public function testRetryOnServerError()
    {
        // This test verifies that the retry middleware properly retries on 500 errors
        // We'll check that multiple attempts are made by providing fewer responses than max_attempts
        
        $mock = new MockHandler([
            new Response(500, [], 'First failure'),
            new Response(500, [], 'Second failure'),
            // Don't provide a third response - let it fail after 2 attempts
        ]);

        $handlerStack = HandlerStack::create($mock);
        $retryMiddleware = new RetryMiddleware([
            'delay' => 10, 
            'jitter' => false,
            'max_attempts' => 2  // Only allow 2 attempts total
        ]);
        $handlerStack->push($retryMiddleware(), 'retry');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        try {
            $httpClient->get('/test');
            $this->fail('Expected CanvasApiException to be thrown');
        } catch (\CanvasLMS\Exceptions\CanvasApiException $e) {
            // With max_attempts=2, it should consume both responses:
            // Attempt 0: First failure
            // Attempt 1: Second failure (then stop)
            $this->assertEquals(0, $mock->count(), 'Expected all mock responses to be consumed by retry middleware');
            $this->assertStringContainsString('500', $e->getMessage());
        }
    }

    public function testRetryOnCanvasRateLimit()
    {
        $mock = new MockHandler([
            new Response(403, ['X-Rate-Limit-Remaining' => '0'], 'Rate Limit Exceeded'),
            new Response(200, [], 'Success'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $retryMiddleware = new RetryMiddleware(['delay' => 10, 'jitter' => false]);
        $handlerStack->push($retryMiddleware(), 'retry');

        $client = new Client([
            'handler' => $handlerStack,
            'http_errors' => false  // Don't throw on 4xx/5xx responses
        ]);
        $httpClient = new HttpClient($client);

        $response = $httpClient->get('/test');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', (string) $response->getBody());
    }

    public function testRetryOnTimeout()
    {
        $request = new Request('GET', 'http://example.com');
        $mock = new MockHandler([
            new ConnectException('Connection timeout', $request),
            new Response(200, [], 'Success'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $retryMiddleware = new RetryMiddleware([
            'delay' => 10,
            'jitter' => false,
            'retry_on_timeout' => true
        ]);
        $handlerStack->push($retryMiddleware(), 'retry');

        $client = new Client([
            'handler' => $handlerStack,
            'http_errors' => false  // Don't throw on 4xx/5xx responses
        ]);
        $httpClient = new HttpClient($client);

        $response = $httpClient->get('/test');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', (string) $response->getBody());
    }

    public function testMaxAttemptsReached()
    {
        $mock = new MockHandler([
            new Response(500),
            new Response(500),
            new Response(500),
            new Response(500), // This should not be reached
        ]);

        $handlerStack = HandlerStack::create($mock);
        $retryMiddleware = new RetryMiddleware([
            'max_attempts' => 3,
            'delay' => 10,
            'jitter' => false
        ]);
        $handlerStack->push($retryMiddleware(), 'retry');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        $this->expectException(\CanvasLMS\Exceptions\CanvasApiException::class);
        $httpClient->get('/test');
    }

    public function testExponentialBackoff()
    {
        $retryMiddleware = new RetryMiddleware([
            'delay' => 1000,
            'multiplier' => 2,
            'jitter' => false,
            'max_delay' => 10000
        ]);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($retryMiddleware);
        $method = $reflection->getMethod('calculateDelay');
        $method->setAccessible(true);

        // Test exponential backoff
        $this->assertEquals(1000, $method->invoke($retryMiddleware, 1)); // 1000 * 2^0
        $this->assertEquals(2000, $method->invoke($retryMiddleware, 2)); // 1000 * 2^1
        $this->assertEquals(4000, $method->invoke($retryMiddleware, 3)); // 1000 * 2^2
        $this->assertEquals(8000, $method->invoke($retryMiddleware, 4)); // 1000 * 2^3
        $this->assertEquals(10000, $method->invoke($retryMiddleware, 5)); // Capped at max_delay
    }

    public function testNoRetryOnSuccessfulResponse()
    {
        $mock = new MockHandler([
            new Response(200, [], 'Success'),
            new Response(500), // This should not be reached
        ]);

        $handlerStack = HandlerStack::create($mock);
        $retryMiddleware = new RetryMiddleware(['delay' => 10]);
        $handlerStack->push($retryMiddleware(), 'retry');

        $client = new Client([
            'handler' => $handlerStack,
            'http_errors' => false  // Don't throw on 4xx/5xx responses
        ]);
        $httpClient = new HttpClient($client);

        $response = $httpClient->get('/test');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', (string) $response->getBody());
    }

    public function testConfigurableRetryStatuses()
    {
        $mock = new MockHandler([
            new Response(429), // Custom retry status
            new Response(200, [], 'Success'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $retryMiddleware = new RetryMiddleware([
            'delay' => 10,
            'jitter' => false,
            'retry_on_status' => [429, 500, 502]
        ]);
        $handlerStack->push($retryMiddleware(), 'retry');

        $client = new Client([
            'handler' => $handlerStack,
            'http_errors' => false  // Don't throw on 4xx/5xx responses
        ]);
        $httpClient = new HttpClient($client);

        $response = $httpClient->get('/test');
        
        $this->assertEquals(200, $response->getStatusCode());
    }
}