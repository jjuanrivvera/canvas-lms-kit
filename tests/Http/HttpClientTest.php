<?php

declare(strict_types=1);

namespace Tests\Http;

use CanvasLMS\Config;
use CanvasLMS\Http\HttpClient;
use CanvasLMS\Pagination\PaginatedResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HttpClientTest extends TestCase
{
    private $loggerMock;

    private $httpClient;

    protected function setUp(): void
    {
        // Mock the LoggerInterface
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        Config::setAppKey('fake-api-key');
        Config::setBaseUrl('https://canvas.instructure.com/api/v1');
    }

    public function testGetRequestWithSuccessfulResponse()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], 'body content'), // Success response
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create an instance of HttpClient with mocked dependencies
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Call the get method
        $response = $this->httpClient->get('/endpoint');

        // Assert status code and body of the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('body content', $response->getBody()->getContents());
    }

    public function testPostRequestWithSuccessfulResponse()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], 'body content'), // Success response
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create an instance of HttpClient with mocked dependencies
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Call the post method
        $response = $this->httpClient->post('/endpoint', ['key' => 'value']);

        // Assert status code and body of the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('body content', $response->getBody()->getContents());
    }

    public function testPutRequestWithSuccessfulResponse()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], 'body content'), // Success response
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create an instance of HttpClient with mocked dependencies
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Call the put method
        $response = $this->httpClient->put('/endpoint', ['key' => 'value']);

        // Assert status code and body of the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('body content', $response->getBody()->getContents());
    }

    public function testDeleteRequestWithSuccessfulResponse()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], 'body content'), // Success response
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create an instance of HttpClient with mocked dependencies
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Call the delete method
        $response = $this->httpClient->delete('/endpoint');

        // Assert status code and body of the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('body content', $response->getBody()->getContents());
    }

    public function testGetPaginatedRequestWithSuccessfulResponse()
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=2&per_page=10>; rel="next", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1&per_page=10>; rel="prev", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1&per_page=10>; rel="first", ' .
                      '<https://canvas.example.com/api/v1/courses?page=5&per_page=10>; rel="last", ' .
                      '<https://canvas.example.com/api/v1/courses?page=2&per_page=10>; rel="current"';

        $responseData = [
            ['id' => 1, 'name' => 'Course 1'],
            ['id' => 2, 'name' => 'Course 2'],
        ];

        // Create a mock and queue responses
        $mock = new MockHandler([
            new Response(200, ['Link' => $linkHeader], json_encode($responseData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create an instance of HttpClient with mocked dependencies
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Call the getPaginated method
        $paginatedResponse = $this->httpClient->getPaginated('/courses');

        // Assert the response is a PaginatedResponse
        $this->assertInstanceOf(PaginatedResponse::class, $paginatedResponse);
        $this->assertEquals($linkHeader, $paginatedResponse->getLinkHeader());
        $this->assertEquals($responseData, $paginatedResponse->getJsonData());
        $this->assertEquals(2, $paginatedResponse->getCurrentPage());
        $this->assertEquals(5, $paginatedResponse->getTotalPages());
        $this->assertEquals(10, $paginatedResponse->getPerPage());
        $this->assertTrue($paginatedResponse->hasNext());
        $this->assertTrue($paginatedResponse->hasPrev());
    }

    public function testGetPaginatedRequestWithoutLinkHeader()
    {
        $responseData = [
            ['id' => 1, 'name' => 'Course 1'],
        ];

        // Create a mock and queue responses
        $mock = new MockHandler([
            new Response(200, [], json_encode($responseData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create an instance of HttpClient with mocked dependencies
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Call the getPaginated method
        $paginatedResponse = $this->httpClient->getPaginated('/courses');

        // Assert the response is a PaginatedResponse
        $this->assertInstanceOf(PaginatedResponse::class, $paginatedResponse);
        $this->assertEquals('', $paginatedResponse->getLinkHeader());
        $this->assertEquals($responseData, $paginatedResponse->getJsonData());
        $this->assertEquals(1, $paginatedResponse->getCurrentPage());
        $this->assertNull($paginatedResponse->getTotalPages());
        $this->assertNull($paginatedResponse->getPerPage());
        $this->assertFalse($paginatedResponse->hasNext());
        $this->assertFalse($paginatedResponse->hasPrev());
    }

    public function testRequestPaginatedWithSuccessfulResponse()
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=1&per_page=50>; rel="first", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1&per_page=50>; rel="current"';

        $responseData = [
            ['id' => 1, 'name' => 'Course 1'],
            ['id' => 2, 'name' => 'Course 2'],
            ['id' => 3, 'name' => 'Course 3'],
        ];

        // Create a mock and queue responses
        $mock = new MockHandler([
            new Response(200, ['Link' => $linkHeader], json_encode($responseData)),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create an instance of HttpClient with mocked dependencies
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Call the requestPaginated method
        $paginatedResponse = $this->httpClient->requestPaginated('GET', '/courses');

        // Assert the response is a PaginatedResponse
        $this->assertInstanceOf(PaginatedResponse::class, $paginatedResponse);
        $this->assertEquals($linkHeader, $paginatedResponse->getLinkHeader());
        $this->assertEquals($responseData, $paginatedResponse->getJsonData());
        $this->assertEquals(1, $paginatedResponse->getCurrentPage());
        $this->assertNull($paginatedResponse->getTotalPages());
        $this->assertEquals(50, $paginatedResponse->getPerPage());
        $this->assertFalse($paginatedResponse->hasNext());
        $this->assertFalse($paginatedResponse->hasPrev());
    }

    public function testBackwardCompatibilityMaintained()
    {
        // Create a mock and queue responses
        $mock = new MockHandler([
            new Response(200, [], 'body content'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create an instance of HttpClient with mocked dependencies
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Call the original get method
        $response = $this->httpClient->get('/endpoint');

        // Assert that the original method still works the same way
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('body content', $response->getBody()->getContents());
    }

    /**
     * Test that OAuth endpoints don't get /api/v1/ prefix added
     */
    public function testOAuthEndpointsDoNotGetApiPrefix()
    {
        Config::setBaseUrl('https://canvas.instructure.com');

        // Track the actual request URI that would be sent
        $actualUri = null;

        // Create a mock handler that captures the request
        $mock = new MockHandler([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
        ]);

        $handlerStack = HandlerStack::create($mock);

        // Add middleware to capture the actual request URL
        $handlerStack->push(function ($handler) use (&$actualUri) {
            return function ($request, $options) use ($handler, &$actualUri) {
                $actualUri = (string) $request->getUri();

                return $handler($request, $options);
            };
        });

        $guzzleClient = new Client(['handler' => $handlerStack]);
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Make request to OAuth endpoint with skipAuth
        $this->httpClient->request('POST', '/login/oauth2/token', ['skipAuth' => true]);

        // Assert the URL doesn't have /api/v1/ prefix
        $this->assertEquals('https://canvas.instructure.com/login/oauth2/token', $actualUri);
    }

    /**
     * Test that regular API endpoints still get /api/v1/ prefix
     */
    public function testRegularEndpointsGetApiPrefix()
    {
        Config::setBaseUrl('https://canvas.instructure.com');
        Config::setApiVersion('v1');
        Config::setAppKey('test-key');

        // Track the actual request URI that would be sent
        $actualUri = null;

        // Create a mock handler that captures the request
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 123, 'name' => 'Test User'])),
        ]);

        $handlerStack = HandlerStack::create($mock);

        // Add middleware to capture the actual request URL
        $handlerStack->push(function ($handler) use (&$actualUri) {
            return function ($request, $options) use ($handler, &$actualUri) {
                $actualUri = (string) $request->getUri();

                return $handler($request, $options);
            };
        });

        $guzzleClient = new Client(['handler' => $handlerStack]);
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Make request to regular API endpoint
        $this->httpClient->get('/users/self');

        // Assert the URL has /api/v1/ prefix
        $this->assertEquals('https://canvas.instructure.com/api/v1/users/self', $actualUri);
    }

    /**
     * Test OAuth token refresh endpoint handling
     */
    public function testOAuthRefreshTokenEndpoint()
    {
        Config::setBaseUrl('https://canvas.instructure.com');

        // Track the actual request URI that would be sent
        $actualUri = null;

        // Create a mock handler that captures the request
        $mock = new MockHandler([
            new Response(200, [], json_encode(['access_token' => 'new-token'])),
        ]);

        $handlerStack = HandlerStack::create($mock);

        // Add middleware to capture the actual request URL
        $handlerStack->push(function ($handler) use (&$actualUri) {
            return function ($request, $options) use ($handler, &$actualUri) {
                $actualUri = (string) $request->getUri();

                return $handler($request, $options);
            };
        });

        $guzzleClient = new Client(['handler' => $handlerStack]);
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Make request to OAuth refresh endpoint with skipAuth
        $this->httpClient->request('POST', '/login/oauth2/token', [
            'skipAuth' => true,
            'form_params' => ['grant_type' => 'refresh_token'],
        ]);

        // Assert the URL doesn't have /api/v1/ prefix
        $this->assertEquals('https://canvas.instructure.com/login/oauth2/token', $actualUri);
    }

    /**
     * Test that login/session_token endpoint gets API prefix (it's not an OAuth2 endpoint)
     */
    public function testSessionTokenEndpointGetsApiPrefix()
    {
        Config::setBaseUrl('https://canvas.instructure.com');
        Config::setOAuthToken('test-oauth-token');
        Config::useOAuth();

        // Track the actual request URI that would be sent
        $actualUri = null;

        // Create a mock handler that captures the request
        $mock = new MockHandler([
            new Response(200, [], json_encode(['session_token' => 'session-token'])),
        ]);

        $handlerStack = HandlerStack::create($mock);

        // Add middleware to capture the actual request URL and headers
        $handlerStack->push(function ($handler) use (&$actualUri) {
            return function ($request, $options) use ($handler, &$actualUri) {
                $actualUri = (string) $request->getUri();
                // Also verify auth header is present
                $this->assertTrue($request->hasHeader('Authorization'));

                return $handler($request, $options);
            };
        });

        $guzzleClient = new Client(['handler' => $handlerStack]);
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);

        // Make request to session token endpoint (requires auth)
        $this->httpClient->request('POST', '/login/session_token');

        // Assert the URL DOES have /api/v1/ prefix (it's not an OAuth2 endpoint)
        $this->assertEquals('https://canvas.instructure.com/api/v1/login/session_token', $actualUri);
    }

    /**
     * Test that timeout configuration is applied when creating new client
     */
    public function testTimeoutConfigurationAppliedToNewClient()
    {
        // Set a custom timeout
        Config::setTimeout(45);

        // Create HttpClient without providing a Guzzle client
        // This will trigger the creation of a new client with timeout configuration
        $httpClient = new HttpClient(null, $this->loggerMock);

        // Use reflection to access the private client property
        $reflection = new \ReflectionClass($httpClient);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $client = $clientProperty->getValue($httpClient);

        // Get the client configuration
        $clientConfig = $client->getConfig();

        // Assert that timeout values are set correctly
        $this->assertEquals(45, $clientConfig['timeout']);
        $this->assertEquals(10, $clientConfig['connect_timeout']); // min(10, 45/3) = 10

        // Reset timeout to default
        Config::setTimeout(30);
    }

    /**
     * Test default timeout when Config::getTimeout() returns null
     */
    public function testDefaultTimeoutWhenNotConfigured()
    {
        // Clear any previously set timeout
        Config::resetContext('default');
        Config::setAppKey('fake-api-key');
        Config::setBaseUrl('https://canvas.instructure.com/api/v1');

        // Create HttpClient without providing a Guzzle client
        $httpClient = new HttpClient(null, $this->loggerMock);

        // Use reflection to access the private client property
        $reflection = new \ReflectionClass($httpClient);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $client = $clientProperty->getValue($httpClient);

        // Get the client configuration
        $clientConfig = $client->getConfig();

        // Assert that default timeout values are used (30 seconds)
        $this->assertEquals(30, $clientConfig['timeout']);
        $this->assertEquals(10, $clientConfig['connect_timeout']); // min(10, 30/3) = 10
    }

    /**
     * Test that timeout is not modified when client is provided (backward compatibility)
     */
    public function testTimeoutNotModifiedWhenClientProvided()
    {
        // Set a custom timeout in Config
        Config::setTimeout(60);

        // Create a Guzzle client with different timeout values
        $providedClient = new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);

        // Create HttpClient with the provided client
        $httpClient = new HttpClient($providedClient, $this->loggerMock);

        // Use reflection to access the private client property
        $reflection = new \ReflectionClass($httpClient);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $client = $clientProperty->getValue($httpClient);

        // Assert that the client is the same as provided (not modified)
        $this->assertSame($providedClient, $client);

        // Verify the original timeout values are preserved
        $clientConfig = $client->getConfig();
        $this->assertEquals(15, $clientConfig['timeout']);
        $this->assertEquals(5, $clientConfig['connect_timeout']);

        // Reset timeout to default
        Config::setTimeout(30);
    }

    /**
     * Test connect_timeout calculation with various timeout values
     */
    public function testConnectTimeoutCalculation()
    {
        $testCases = [
            15 => 5,   // 15/3 = 5 (less than 10, so use 5)
            30 => 10,  // 30/3 = 10 (equals 10, so use 10)
            60 => 10,  // 60/3 = 20 (greater than 10, so cap at 10)
            120 => 10, // 120/3 = 40 (greater than 10, so cap at 10)
            9 => 3,    // 9/3 = 3 (less than 10, so use 3)
        ];

        foreach ($testCases as $timeout => $expectedConnectTimeout) {
            Config::setTimeout($timeout);

            $httpClient = new HttpClient(null, $this->loggerMock);

            $reflection = new \ReflectionClass($httpClient);
            $clientProperty = $reflection->getProperty('client');
            $clientProperty->setAccessible(true);
            $client = $clientProperty->getValue($httpClient);

            $clientConfig = $client->getConfig();

            $this->assertEquals($timeout, $clientConfig['timeout'], "Failed for timeout: $timeout");
            $this->assertEquals(
                $expectedConnectTimeout,
                $clientConfig['connect_timeout'],
                "Failed connect_timeout calculation for timeout: $timeout"
            );
        }

        // Reset timeout to default
        Config::setTimeout(30);
    }
}
