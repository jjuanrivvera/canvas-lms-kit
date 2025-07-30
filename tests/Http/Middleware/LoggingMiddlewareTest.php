<?php

namespace Tests\Http\Middleware;

use CanvasLMS\Config;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Exception\RequestException;
use CanvasLMS\Http\Middleware\LoggingMiddleware;

class LoggingMiddlewareTest extends TestCase
{
    private $loggerMock;

    protected function setUp(): void
    {
        Config::setAppKey('fake-api-key');
        Config::setBaseUrl('https://canvas.instructure.com/');
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    public function testLogsSuccessfulRequest()
    {
        $mock = new MockHandler([
            new Response(200, ['X-Custom-Header' => 'value'], 'Success'),
        ]);

        $loggedMessages = [];
        $this->loggerMock->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$loggedMessages) {
                $loggedMessages[] = ['level' => $level, 'message' => $message, 'context' => $context];
            });

        $handlerStack = HandlerStack::create($mock);
        $loggingMiddleware = new LoggingMiddleware($this->loggerMock);
        $handlerStack->push($loggingMiddleware(), 'logging');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        $response = $httpClient->get('/test');
        $this->assertEquals(200, $response->getStatusCode());
        
        // Check request log
        $this->assertEquals('info', $loggedMessages[0]['level']);
        $this->assertStringContainsString('HTTP Request', $loggedMessages[0]['message']);
        $this->assertArrayHasKey('request_id', $loggedMessages[0]['context']);
        $this->assertEquals('GET', $loggedMessages[0]['context']['method']);
        $this->assertStringContainsString('/test', $loggedMessages[0]['context']['uri']);
        
        // Check response log
        $this->assertEquals('info', $loggedMessages[1]['level']);
        $this->assertStringContainsString('HTTP Response', $loggedMessages[1]['message']);
        $this->assertEquals(200, $loggedMessages[1]['context']['status_code']);
        $this->assertArrayHasKey('elapsed_time', $loggedMessages[1]['context']);
    }

    public function testSanitizesAuthorizationHeader()
    {
        $mock = new MockHandler([
            new Response(200),
        ]);

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'info',
                $this->stringContains('HTTP Request'),
                $this->callback(function ($context) {
                    // Check that Authorization header is redacted
                    return isset($context['headers']['Authorization']) &&
                           $context['headers']['Authorization'][0] === '***REDACTED***';
                })
            );

        $handlerStack = HandlerStack::create($mock);
        $loggingMiddleware = new LoggingMiddleware($this->loggerMock, [
            'log_responses' => false // Only log requests for this test
        ]);
        $handlerStack->push($loggingMiddleware(), 'logging');

        $client = new Client(['handler' => $handlerStack]);
        $response = $client->request('GET', 'http://example.com', [
            'headers' => ['Authorization' => 'Bearer secret-token']
        ]);
    }

    public function testLogsErrorResponses()
    {
        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $loggedMessages = [];
        $this->loggerMock->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$loggedMessages) {
                $loggedMessages[] = ['level' => $level, 'message' => $message, 'context' => $context];
            });

        $handlerStack = HandlerStack::create($mock);
        $loggingMiddleware = new LoggingMiddleware($this->loggerMock);
        $handlerStack->push($loggingMiddleware(), 'logging');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        try {
            $httpClient->get('/test');
        } catch (\Exception $e) {
            // Expected exception
        }
        
        // Check that error response is logged at error level
        $this->assertEquals('info', $loggedMessages[0]['level']);
        $this->assertEquals('error', $loggedMessages[1]['level']);
        $this->assertEquals(500, $loggedMessages[1]['context']['status_code']);
        $this->assertArrayHasKey('body', $loggedMessages[1]['context']);
    }

    public function testLogsExceptions()
    {
        $request = new Request('GET', 'http://example.com');
        $mock = new MockHandler([
            new RequestException('Connection error', $request),
        ]);

        $loggedMessages = [];
        $this->loggerMock->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$loggedMessages) {
                $loggedMessages[] = ['level' => $level, 'message' => $message, 'context' => $context];
            });

        $handlerStack = HandlerStack::create($mock);
        $loggingMiddleware = new LoggingMiddleware($this->loggerMock);
        $handlerStack->push($loggingMiddleware(), 'logging');

        $client = new Client(['handler' => $handlerStack]);

        try {
            $client->request('GET', 'http://example.com');
        } catch (\Exception $e) {
            // Expected exception
        }
        
        // Check error log
        $this->assertEquals('error', $loggedMessages[1]['level']);
        $this->assertStringContainsString('HTTP Error', $loggedMessages[1]['message']);
        $this->assertEquals('GuzzleHttp\Exception\RequestException', $loggedMessages[1]['context']['error_type']);
        $this->assertEquals('Connection error', $loggedMessages[1]['context']['error_message']);
    }

    public function testSanitizesJsonBody()
    {
        $mock = new MockHandler([
            new Response(200),
        ]);

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'info',
                $this->stringContains('HTTP Request'),
                $this->callback(function ($context) {
                    if (!isset($context['body'])) {
                        return false;
                    }
                    $body = json_decode($context['body'], true);
                    return $body['password'] === '***REDACTED***' &&
                           $body['username'] === 'john.doe';
                })
            );

        $handlerStack = HandlerStack::create($mock);
        $loggingMiddleware = new LoggingMiddleware($this->loggerMock, [
            'log_responses' => false
        ]);
        $handlerStack->push($loggingMiddleware(), 'logging');

        $client = new Client(['handler' => $handlerStack]);
        $client->request('POST', 'http://example.com', [
            'json' => [
                'username' => 'john.doe',
                'password' => 'secret123'
            ]
        ]);
    }

    public function testTruncatesLargeBody()
    {
        $largeBody = str_repeat('A', 2000);
        $mock = new MockHandler([
            new Response(200),
        ]);

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'info',
                $this->stringContains('HTTP Request'),
                $this->callback(function ($context) {
                    return isset($context['body']) &&
                           strpos($context['body'], '... (truncated)') !== false &&
                           $context['body_length'] === 2000;
                })
            );

        $handlerStack = HandlerStack::create($mock);
        $loggingMiddleware = new LoggingMiddleware($this->loggerMock, [
            'log_responses' => false,
            'max_body_length' => 1000
        ]);
        $handlerStack->push($loggingMiddleware(), 'logging');

        $client = new Client(['handler' => $handlerStack]);
        $client->request('POST', 'http://example.com', [
            'body' => $largeBody
        ]);
    }

    public function testLogsCanvasRateLimitHeaders()
    {
        $mock = new MockHandler([
            new Response(200, [
                'X-Rate-Limit-Remaining' => '2950',
                'X-Request-Cost' => '50'
            ]),
        ]);

        $loggedMessages = [];
        $this->loggerMock->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function ($level, $message, $context) use (&$loggedMessages) {
                $loggedMessages[] = ['level' => $level, 'message' => $message, 'context' => $context];
            });

        $handlerStack = HandlerStack::create($mock);
        $loggingMiddleware = new LoggingMiddleware($this->loggerMock);
        $handlerStack->push($loggingMiddleware(), 'logging');

        $client = new Client(['handler' => $handlerStack]);
        $httpClient = new HttpClient($client);

        $httpClient->get('/test');
        
        // Check Canvas-specific headers are logged
        $this->assertEquals('2950', $loggedMessages[1]['context']['rate_limit_remaining']);
        $this->assertEquals('50', $loggedMessages[1]['context']['request_cost']);
    }

    public function testDisabledLogging()
    {
        $mock = new MockHandler([
            new Response(200),
        ]);

        $this->loggerMock->expects($this->never())
            ->method('log');

        $handlerStack = HandlerStack::create($mock);
        $loggingMiddleware = new LoggingMiddleware($this->loggerMock, [
            'enabled' => false
        ]);
        $handlerStack->push($loggingMiddleware(), 'logging');

        $client = new Client(['handler' => $handlerStack]);
        $client->request('GET', 'http://example.com');
    }

    public function testCustomSanitizeFields()
    {
        $mock = new MockHandler([
            new Response(200),
        ]);

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'info',
                $this->stringContains('HTTP Request'),
                $this->callback(function ($context) {
                    $body = json_decode($context['body'], true);
                    return $body['api_secret'] === '***REDACTED***' &&
                           $body['custom_token'] === '***REDACTED***' &&
                           $body['public_data'] === 'visible';
                })
            );

        $handlerStack = HandlerStack::create($mock);
        $loggingMiddleware = new LoggingMiddleware($this->loggerMock, [
            'log_responses' => false,
            'sanitize_fields' => ['api_secret', 'custom_token']
        ]);
        $handlerStack->push($loggingMiddleware(), 'logging');

        $client = new Client(['handler' => $handlerStack]);
        $client->request('POST', 'http://example.com', [
            'json' => [
                'api_secret' => 'super-secret',
                'custom_token' => 'token123',
                'public_data' => 'visible'
            ]
        ]);
    }
}