<?php

namespace CanvasLMS\Tests\Http;

use CanvasLMS\Config;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Exceptions\MissingApiKeyException;
use CanvasLMS\Exceptions\MissingBaseUrlException;
use CanvasLMS\Http\HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class HttpClientRawUrlTest extends TestCase
{
    private HttpClient $httpClient;
    private ClientInterface $mockGuzzleClient;
    private ResponseInterface $mockResponse;
    private StreamInterface $mockStream;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up Config
        Config::setBaseUrl('https://canvas.example.com/');
        Config::setApiKey('test-api-key');
        
        // Create mocks
        $this->mockGuzzleClient = $this->createMock(ClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);
        
        // Create HttpClient with mock Guzzle client
        $this->httpClient = new HttpClient($this->mockGuzzleClient);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Reset configuration to defaults
        Config::resetContext('default');
        Config::setBaseUrl('https://canvas.example.com/');
        Config::setApiKey('test-api-key');
    }

    public function testRawRequestWithAbsoluteUrl(): void
    {
        $absoluteUrl = 'https://canvas.example.com/api/v1/courses?page=2';
        
        $this->mockStream->method('getContents')
            ->willReturn('{"courses": []}');
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $absoluteUrl,
                $this->callback(function ($options) {
                    return isset($options['headers']['Authorization']) &&
                           $options['headers']['Authorization'] === 'Bearer test-api-key';
                })
            )
            ->willReturn($this->mockResponse);
        
        $response = $this->httpClient->rawRequest($absoluteUrl);
        
        $this->assertSame($this->mockResponse, $response);
    }

    public function testRawRequestWithRelativeUrl(): void
    {
        $relativeUrl = '/api/v1/courses';
        $expectedFullUrl = 'https://canvas.example.com/api/v1/courses';
        
        $this->mockStream->method('getContents')
            ->willReturn('{"courses": []}');
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $expectedFullUrl,
                $this->callback(function ($options) {
                    return isset($options['headers']['Authorization']) &&
                           $options['headers']['Authorization'] === 'Bearer test-api-key';
                })
            )
            ->willReturn($this->mockResponse);
        
        $response = $this->httpClient->rawRequest($relativeUrl);
        
        $this->assertSame($this->mockResponse, $response);
    }

    public function testRawRequestWithDifferentMethods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
        
        $this->mockGuzzleClient->expects($this->exactly(count($methods)))
            ->method('request')
            ->willReturnCallback(function($method, $url, $options) use ($methods) {
                $this->assertContains($method, $methods);
                $this->assertEquals('https://canvas.example.com/api/v1/test', $url);
                return $this->mockResponse;
            });
        
        foreach ($methods as $method) {
            $response = $this->httpClient->rawRequest('/api/v1/test', $method);
            $this->assertSame($this->mockResponse, $response);
        }
    }

    public function testRawRequestWithOptions(): void
    {
        $url = '/api/v1/courses';
        $options = [
            'query' => ['per_page' => 50],
            'headers' => ['X-Custom-Header' => 'value']
        ];
        
        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://canvas.example.com/api/v1/courses',
                $this->callback(function ($opts) {
                    return isset($opts['query']['per_page']) &&
                           $opts['query']['per_page'] === 50 &&
                           isset($opts['headers']['X-Custom-Header']) &&
                           $opts['headers']['X-Custom-Header'] === 'value' &&
                           isset($opts['headers']['Authorization']);
                })
            )
            ->willReturn($this->mockResponse);
        
        $response = $this->httpClient->rawRequest($url, 'GET', $options);
        
        $this->assertSame($this->mockResponse, $response);
    }

    public function testRawRequestWithInvalidDomainThrowsException(): void
    {
        $invalidUrl = 'https://evil.example.com/api/v1/courses';
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('URL domain does not match configured Canvas instance');
        
        $this->httpClient->rawRequest($invalidUrl);
    }

    public function testRawRequestWithHttpUrlThrowsExceptionForProduction(): void
    {
        $httpUrl = 'http://canvas.example.com/api/v1/courses';
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Only HTTPS URLs are allowed for production Canvas instances');
        
        $this->httpClient->rawRequest($httpUrl);
    }

    public function testRawRequestAllowsHttpForLocalhost(): void
    {
        Config::setBaseUrl('http://localhost:3000/');
        
        $httpUrl = 'http://localhost:3000/api/v1/courses';
        $httpClient = new HttpClient($this->mockGuzzleClient);
        
        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->with('GET', $httpUrl, $this->anything())
            ->willReturn($this->mockResponse);
        
        $response = $httpClient->rawRequest($httpUrl);
        
        $this->assertSame($this->mockResponse, $response);
    }

    public function testRawRequestAllowsSubdomains(): void
    {
        $subdomainUrl = 'https://test.canvas.example.com/api/v1/courses';
        
        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->with('GET', $subdomainUrl, $this->anything())
            ->willReturn($this->mockResponse);
        
        $response = $this->httpClient->rawRequest($subdomainUrl);
        
        $this->assertSame($this->mockResponse, $response);
    }

    public function testRawRequestWithoutAuthentication(): void
    {
        $url = '/api/v1/public/courses';
        $options = ['skipAuth' => true];
        
        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://canvas.example.com/api/v1/public/courses',
                $this->callback(function ($opts) {
                    // Verify no Authorization header and no skipAuth in options
                    return !isset($opts['headers']['Authorization']) &&
                           !isset($opts['skipAuth']);
                })
            )
            ->willReturn($this->mockResponse);
        
        $response = $this->httpClient->rawRequest($url, 'GET', $options);
        
        $this->assertSame($this->mockResponse, $response);
    }

    public function testRawRequestWithOAuthAuthentication(): void
    {
        Config::useOAuth();
        Config::setOAuthToken('oauth-token-123');
        Config::setOAuthExpiresAt(time() + 3600);
        
        $url = '/api/v1/courses';
        $httpClient = new HttpClient($this->mockGuzzleClient);
        
        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://canvas.example.com/api/v1/courses',
                $this->callback(function ($options) {
                    return isset($options['headers']['Authorization']) &&
                           $options['headers']['Authorization'] === 'Bearer oauth-token-123';
                })
            )
            ->willReturn($this->mockResponse);
        
        $response = $httpClient->rawRequest($url);
        
        $this->assertSame($this->mockResponse, $response);
    }

    public function testRawRequestHandlesApiErrors(): void
    {
        $url = '/api/v1/courses/999';
        
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockErrorResponse = $this->createMock(ResponseInterface::class);
        $mockErrorStream = $this->createMock(StreamInterface::class);
        
        $mockErrorStream->method('getContents')
            ->willReturn('{"errors": [{"message": "Course not found"}]}');
        
        $mockErrorResponse->method('getBody')
            ->willReturn($mockErrorStream);
        
        $exception = new RequestException(
            'Not Found',
            $mockRequest,
            $mockErrorResponse
        );
        
        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->willThrowException($exception);
        
        try {
            $this->httpClient->rawRequest($url);
            $this->fail('Expected CanvasApiException was not thrown');
        } catch (CanvasApiException $e) {
            $this->assertEquals('Not Found', $e->getMessage());
            $this->assertEquals([['message' => 'Course not found']], $e->getErrors());
        }
    }

    public function testRawRequestWithMissingApiKeyThrowsException(): void
    {
        // Create a new context without an API key
        Config::setContext('test_no_api_key');
        Config::setBaseUrl('https://canvas.example.com/', 'test_no_api_key');
        // No API key set for this context
        
        $httpClient = new HttpClient($this->mockGuzzleClient);
        
        $this->expectException(MissingApiKeyException::class);
        
        $httpClient->rawRequest('/api/v1/courses');
    }

    public function testRawRequestWithMissingBaseUrlThrowsException(): void
    {
        // Create a new context without a base URL
        Config::setContext('test_no_base_url');
        Config::setApiKey('test-api-key', 'test_no_base_url');
        // No base URL set for this context
        
        $httpClient = new HttpClient($this->mockGuzzleClient);
        
        $this->expectException(MissingBaseUrlException::class);
        
        $httpClient->rawRequest('/api/v1/courses');
    }

    public function testRawRequestPreservesRelativePathWithApiVersion(): void
    {
        $url = '/api/v1/courses/123/custom_endpoint';
        $expectedUrl = 'https://canvas.example.com/api/v1/courses/123/custom_endpoint';
        
        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->with('GET', $expectedUrl, $this->anything())
            ->willReturn($this->mockResponse);
        
        $response = $this->httpClient->rawRequest($url);
        
        $this->assertSame($this->mockResponse, $response);
    }

    public function testRawRequestWithCustomPath(): void
    {
        $url = '/custom/analytics/endpoint';
        $expectedUrl = 'https://canvas.example.com/custom/analytics/endpoint';
        
        $this->mockGuzzleClient->expects($this->once())
            ->method('request')
            ->with('GET', $expectedUrl, $this->anything())
            ->willReturn($this->mockResponse);
        
        $response = $this->httpClient->rawRequest($url);
        
        $this->assertSame($this->mockResponse, $response);
    }

    public function testRawRequestWithInvalidUrlFormat(): void
    {
        $invalidUrl = 'not-a-valid-url://invalid';
        
        $this->expectException(CanvasApiException::class);
        
        $this->httpClient->rawRequest($invalidUrl);
    }
}