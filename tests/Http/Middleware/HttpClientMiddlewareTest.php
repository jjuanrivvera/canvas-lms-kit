<?php

namespace Tests\Http\Middleware;

use CanvasLMS\Config;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use CanvasLMS\Http\Middleware\MiddlewareInterface;

class HttpClientMiddlewareTest extends TestCase
{
    private $loggerMock;
    private $httpClient;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        Config::setAppKey('fake-api-key');
        Config::setBaseUrl('https://canvas.instructure.com/');
    }

    public function testBackwardCompatibilityWithoutMiddleware()
    {
        // Create a mock and queue responses
        $mock = new MockHandler([
            new Response(200, [], 'test response'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Test old constructor signature still works
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        $response = $this->httpClient->get('/courses');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test response', $response->getBody()->getContents());
    }

    public function testCanAddMiddleware()
    {
        $middlewareMock = $this->createMock(MiddlewareInterface::class);
        $middlewareMock->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('test-middleware');
        
        $middlewareMock->expects($this->once())
            ->method('__invoke')
            ->willReturn(function ($handler) {
                return function ($request, $options) use ($handler) {
                    // Simple middleware that adds a header
                    $request = $request->withHeader('X-Test-Middleware', 'true');
                    return $handler($request, $options);
                };
            });

        $this->httpClient = new HttpClient(null, $this->loggerMock);
        $this->httpClient->addMiddleware($middlewareMock);

        $middleware = $this->httpClient->getMiddleware();
        $this->assertCount(1, $middleware);
        $this->assertArrayHasKey('test-middleware', $middleware);
    }

    public function testCanRemoveMiddleware()
    {
        $middlewareMock = $this->createMock(MiddlewareInterface::class);
        $middlewareMock->method('getName')->willReturn('test-middleware');
        $middlewareMock->method('__invoke')->willReturn(function ($handler) {
            return $handler;
        });

        $this->httpClient = new HttpClient(null, $this->loggerMock);
        $this->httpClient->addMiddleware($middlewareMock);
        
        $this->assertCount(1, $this->httpClient->getMiddleware());
        
        $this->httpClient->removeMiddleware('test-middleware');
        
        $this->assertCount(0, $this->httpClient->getMiddleware());
    }

    public function testConstructorWithMiddleware()
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware1->method('getName')->willReturn('middleware1');
        $middleware1->method('__invoke')->willReturn(function ($handler) {
            return $handler;
        });

        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware2->method('getName')->willReturn('middleware2');
        $middleware2->method('__invoke')->willReturn(function ($handler) {
            return $handler;
        });

        $this->httpClient = new HttpClient(null, $this->loggerMock, [$middleware1, $middleware2]);

        $middleware = $this->httpClient->getMiddleware();
        $this->assertCount(2, $middleware);
        $this->assertArrayHasKey('middleware1', $middleware);
        $this->assertArrayHasKey('middleware2', $middleware);
    }

    public function testGetLogger()
    {
        $this->httpClient = new HttpClient(null, $this->loggerMock);
        $this->assertSame($this->loggerMock, $this->httpClient->getLogger());
    }
}