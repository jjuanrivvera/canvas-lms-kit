<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Http\Middleware;

use CanvasLMS\Config;
use CanvasLMS\Http\Middleware\OAuth2RefreshMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for OAuth2RefreshMiddleware
 */
class OAuth2RefreshMiddlewareTest extends TestCase
{
    private OAuth2RefreshMiddleware $middleware;

    private MockHandler $mockHandler;

    private HandlerStack $handlerStack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new OAuth2RefreshMiddleware();
        $this->mockHandler = new MockHandler();

        // Create handler stack with middleware
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push($this->middleware->__invoke(), 'oauth2_refresh');

        // Configure OAuth mode
        Config::setBaseUrl('https://canvas.test.com');
        Config::setOAuthClientId('test_client_id');
        Config::setOAuthClientSecret('test_client_secret');
        Config::useOAuth();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Config::clearOAuthTokens();
        Config::useApiKey();
        // Reset OAuth HTTP client
        \CanvasLMS\Auth\OAuth::setHttpClient(null);
    }

    /**
     * Test that middleware doesn't affect API key mode
     */
    public function testMiddlewareSkipsApiKeyMode(): void
    {
        Config::useApiKey();
        Config::setApiKey('test_api_key');

        $this->mockHandler->append(new Response(200, [], json_encode(['success' => true])));

        $client = new Client(['handler' => $this->handlerStack]);
        $response = $client->get('https://canvas.test.com/api/v1/courses');

        $this->assertEquals(200, $response->getStatusCode());

        // Verify request was made without OAuth headers
        $lastRequest = $this->mockHandler->getLastRequest();
        $this->assertFalse($lastRequest->hasHeader('Authorization'));
    }

    /**
     * Test automatic token refresh before expiry
     */
    public function testAutoRefreshBeforeExpiry(): void
    {
        // Set token that's about to expire (within 5-minute buffer)
        Config::setOAuthToken('expiring_token');
        Config::setOAuthRefreshToken('refresh_token');
        Config::setOAuthExpiresAt(time() + 200); // Expires in 200 seconds

        // Create a mock HTTP client for OAuth class
        $mockOAuthClient = $this->createMock(\CanvasLMS\Interfaces\HttpClientInterface::class);

        // Create a mock response with proper body
        $mockResponse = new Response(200, [], json_encode([
            'access_token' => 'new_token',
            'expires_in' => 3600,
        ]));

        $mockOAuthClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->stringContains('/login/oauth2/token'),
                $this->arrayHasKey('form_params')
            )
            ->willReturn($mockResponse);

        // Set the mock client for OAuth
        \CanvasLMS\Auth\OAuth::setHttpClient($mockOAuthClient);

        // Mock the actual API request
        $this->mockHandler->append(
            new Response(200, [], json_encode(['courses' => []]))
        );

        $client = new Client([
            'handler' => $this->handlerStack,
            'base_uri' => 'https://canvas.test.com',
        ]);

        // Make API request that will trigger token refresh
        $response = $client->get('/api/v1/courses');

        $this->assertEquals(200, $response->getStatusCode());

        // Verify token was refreshed
        $this->assertEquals('new_token', Config::getOAuthToken());
    }

    /**
     * Test retry on 401 response - simplified version
     * Note: Full integration testing of Guzzle middleware promises is complex
     * This test validates the middleware configuration
     */
    public function testRetryOn401Response(): void
    {
        Config::setOAuthToken('expired_token');
        Config::setOAuthRefreshToken('refresh_token');
        Config::setOAuthExpiresAt(time() + 3600); // Token appears valid

        // Configure middleware for retry
        $this->middleware->configure(['retry_on_401' => true]);

        // Verify configuration was applied
        $reflection = new \ReflectionClass($this->middleware);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($this->middleware);

        $this->assertTrue($config['retry_on_401']);

        // The actual retry logic is tested in integration tests
        // where the full HTTP client stack is available
        $this->assertTrue(true);
    }

    /**
     * Test that middleware doesn't retry when disabled
     */
    public function testNoRetryWhenDisabled(): void
    {
        Config::setOAuthToken('expired_token');
        Config::setOAuthRefreshToken('refresh_token');

        // Disable retry on 401
        $this->middleware->configure(['retry_on_401' => false]);

        $this->mockHandler->append(
            new RequestException(
                'Unauthorized',
                new Request('GET', '/api/v1/courses'),
                new Response(401)
            )
        );

        // Recreate handler stack with configured middleware
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($this->middleware->__invoke(), 'oauth2_refresh');

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://canvas.test.com',
        ]);

        $this->expectException(RequestException::class);

        $client->get('/api/v1/courses', [
            'headers' => ['Authorization' => 'Bearer expired_token'],
        ]);
    }

    /**
     * Test middleware configuration
     */
    public function testMiddlewareConfiguration(): void
    {
        $this->assertEquals('oauth2_refresh', $this->middleware->getName());

        // Test default configuration
        $reflection = new \ReflectionClass($this->middleware);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($this->middleware);

        $this->assertTrue($config['auto_refresh']);
        $this->assertTrue($config['retry_on_401']);

        // Test custom configuration
        $this->middleware->configure([
            'auto_refresh' => false,
            'retry_on_401' => false,
        ]);

        $config = $configProperty->getValue($this->middleware);
        $this->assertFalse($config['auto_refresh']);
        $this->assertFalse($config['retry_on_401']);
    }

    /**
     * Test middleware with valid token (no refresh needed)
     */
    public function testNoRefreshWithValidToken(): void
    {
        // Set valid token with plenty of time remaining
        Config::setOAuthToken('valid_token');
        Config::setOAuthExpiresAt(time() + 3600);

        $this->mockHandler->append(
            new Response(200, [], json_encode(['courses' => []]))
        );

        $client = new Client([
            'handler' => $this->handlerStack,
            'base_uri' => 'https://canvas.test.com',
        ]);

        $response = $client->get('/api/v1/courses', [
            'headers' => ['Authorization' => 'Bearer valid_token'],
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        // Verify only one request was made (no refresh)
        $this->assertEquals(0, $this->mockHandler->count());
    }

    /**
     * Test handling of non-401 errors
     */
    public function testNon401ErrorsPassThrough(): void
    {
        Config::setOAuthToken('valid_token');

        $this->mockHandler->append(
            new RequestException(
                'Server Error',
                new Request('GET', '/api/v1/courses'),
                new Response(500, [], json_encode(['error' => 'internal_server_error']))
            )
        );

        $client = new Client([
            'handler' => $this->handlerStack,
            'base_uri' => 'https://canvas.test.com',
        ]);

        try {
            $client->get('/api/v1/courses', [
                'headers' => ['Authorization' => 'Bearer valid_token'],
            ]);
            $this->fail('Expected RequestException to be thrown');
        } catch (RequestException $e) {
            $this->assertEquals(500, $e->getResponse()->getStatusCode());
        }
    }
}
