<?php

namespace Tests\Http;

use CanvasLMS\Config;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;

/**
 * Test masquerading functionality in HttpClient
 */
class HttpClientMasqueradingTest extends TestCase
{
    private $loggerMock;
    private $httpClient;
    private $container = [];

    protected function setUp(): void
    {
        // Reset Config to default state
        Config::setContext('default');
        Config::resetContext('default');
        Config::stopMasquerading();

        // Mock the LoggerInterface
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        Config::setAppKey('fake-api-key');
        Config::setBaseUrl('https://canvas.instructure.com/');

        // Clear container for each test
        $this->container = [];
    }

    protected function tearDown(): void
    {
        // Clean up masquerading state
        Config::stopMasquerading();
        
        parent::tearDown();
    }

    /**
     * Create HttpClient with request history tracking
     */
    private function createHttpClientWithHistory(): HttpClient
    {
        $history = Middleware::history($this->container);
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 1, 'name' => 'Test User'])),
        ]);
        
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        
        $guzzleClient = new Client(['handler' => $handlerStack]);
        
        return new HttpClient($guzzleClient, $this->loggerMock);
    }

    /**
     * Test that masquerading parameter is added when global masquerading is active
     */
    public function testGlobalMasqueradingAddsParameter()
    {
        $this->httpClient = $this->createHttpClientWithHistory();
        
        // Enable global masquerading
        Config::asUser(12345);
        
        // Make a request
        $this->httpClient->get('/users/1');
        
        // Check that the request included the as_user_id parameter
        $this->assertCount(1, $this->container);
        /** @var Request $request */
        $request = $this->container[0]['request'];
        
        parse_str($request->getUri()->getQuery(), $queryParams);
        $this->assertArrayHasKey('as_user_id', $queryParams);
        $this->assertEquals('12345', $queryParams['as_user_id']);
    }

    /**
     * Test that masquerading parameter is not added when masquerading is disabled
     */
    public function testNoMasqueradingWhenDisabled()
    {
        $this->httpClient = $this->createHttpClientWithHistory();
        
        // Ensure masquerading is disabled
        Config::stopMasquerading();
        
        // Make a request
        $this->httpClient->get('/users/1');
        
        // Check that the request did not include the as_user_id parameter
        $this->assertCount(1, $this->container);
        /** @var Request $request */
        $request = $this->container[0]['request'];
        
        parse_str($request->getUri()->getQuery(), $queryParams);
        $this->assertArrayNotHasKey('as_user_id', $queryParams);
    }

    /**
     * Test that masquerading works with existing query parameters
     */
    public function testMasqueradingWithExistingQueryParameters()
    {
        $this->httpClient = $this->createHttpClientWithHistory();
        
        // Enable masquerading
        Config::asUser(67890);
        
        // Make a request with existing query parameters
        $this->httpClient->get('/users', [
            'query' => [
                'enrollment_type' => 'student',
                'per_page' => 50
            ]
        ]);
        
        // Check that all parameters are present
        $this->assertCount(1, $this->container);
        /** @var Request $request */
        $request = $this->container[0]['request'];
        
        parse_str($request->getUri()->getQuery(), $queryParams);
        $this->assertArrayHasKey('as_user_id', $queryParams);
        $this->assertEquals('67890', $queryParams['as_user_id']);
        $this->assertArrayHasKey('enrollment_type', $queryParams);
        $this->assertEquals('student', $queryParams['enrollment_type']);
        $this->assertArrayHasKey('per_page', $queryParams);
        $this->assertEquals('50', $queryParams['per_page']);
    }

    /**
     * Test masquerading with POST requests
     */
    public function testMasqueradingWithPostRequest()
    {
        $this->httpClient = $this->createHttpClientWithHistory();
        
        // Enable masquerading
        Config::asUser(11111);
        
        // Make a POST request
        $this->httpClient->post('/courses', [
            'json' => ['name' => 'Test Course']
        ]);
        
        // Check that the request included the as_user_id parameter
        $this->assertCount(1, $this->container);
        /** @var Request $request */
        $request = $this->container[0]['request'];
        
        parse_str($request->getUri()->getQuery(), $queryParams);
        $this->assertArrayHasKey('as_user_id', $queryParams);
        $this->assertEquals('11111', $queryParams['as_user_id']);
    }

    /**
     * Test masquerading with raw requests
     */
    public function testMasqueradingWithRawRequest()
    {
        $this->httpClient = $this->createHttpClientWithHistory();
        
        // Enable masquerading
        Config::asUser(22222);
        
        // Make a raw request
        $this->httpClient->rawRequest('https://canvas.instructure.com/api/v1/users/1');
        
        // Check that the request included the as_user_id parameter
        $this->assertCount(1, $this->container);
        /** @var Request $request */
        $request = $this->container[0]['request'];
        
        parse_str($request->getUri()->getQuery(), $queryParams);
        $this->assertArrayHasKey('as_user_id', $queryParams);
        $this->assertEquals('22222', $queryParams['as_user_id']);
    }

    /**
     * Test switching masquerading users
     */
    public function testSwitchingMasqueradeUsers()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 1])),
            new Response(200, [], json_encode(['id' => 2])),
        ]);
        
        $history = Middleware::history($this->container);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        
        $guzzleClient = new Client(['handler' => $handlerStack]);
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);
        
        // First request as user 33333
        Config::asUser(33333);
        $this->httpClient->get('/users/1');
        
        // Second request as user 44444
        Config::asUser(44444);
        $this->httpClient->get('/users/2');
        
        // Check both requests
        $this->assertCount(2, $this->container);
        
        // First request
        /** @var Request $request1 */
        $request1 = $this->container[0]['request'];
        parse_str($request1->getUri()->getQuery(), $queryParams1);
        $this->assertEquals('33333', $queryParams1['as_user_id']);
        
        // Second request
        /** @var Request $request2 */
        $request2 = $this->container[1]['request'];
        parse_str($request2->getUri()->getQuery(), $queryParams2);
        $this->assertEquals('44444', $queryParams2['as_user_id']);
    }

    /**
     * Test that stopping masquerading removes the parameter
     */
    public function testStopMasquerading()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 1])),
            new Response(200, [], json_encode(['id' => 2])),
        ]);
        
        $history = Middleware::history($this->container);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        
        $guzzleClient = new Client(['handler' => $handlerStack]);
        $this->httpClient = new HttpClient($guzzleClient, $this->loggerMock);
        
        // First request with masquerading
        Config::asUser(55555);
        $this->httpClient->get('/users/1');
        
        // Stop masquerading and make second request
        Config::stopMasquerading();
        $this->httpClient->get('/users/2');
        
        // Check both requests
        $this->assertCount(2, $this->container);
        
        // First request should have masquerading
        /** @var Request $request1 */
        $request1 = $this->container[0]['request'];
        parse_str($request1->getUri()->getQuery(), $queryParams1);
        $this->assertArrayHasKey('as_user_id', $queryParams1);
        $this->assertEquals('55555', $queryParams1['as_user_id']);
        
        // Second request should not have masquerading
        /** @var Request $request2 */
        $request2 = $this->container[1]['request'];
        parse_str($request2->getUri()->getQuery(), $queryParams2);
        $this->assertArrayNotHasKey('as_user_id', $queryParams2);
    }

    /**
     * Test masquerading with different contexts
     */
    public function testMasqueradingWithContexts()
    {
        $this->httpClient = $this->createHttpClientWithHistory();
        
        // Set up two contexts
        Config::setContext('production');
        Config::setAppKey('prod-key', 'production');
        Config::setBaseUrl('https://prod.instructure.com/', 'production');
        Config::asUser(66666, 'production');
        
        Config::setContext('staging');
        Config::setAppKey('staging-key', 'staging');
        Config::setBaseUrl('https://staging.instructure.com/', 'staging');
        Config::asUser(77777, 'staging');
        
        // Make request in staging context (current)
        $this->httpClient->get('/users/1');
        
        // Check that staging masquerade user was used
        $this->assertCount(1, $this->container);
        /** @var Request $request */
        $request = $this->container[0]['request'];
        
        parse_str($request->getUri()->getQuery(), $queryParams);
        $this->assertArrayHasKey('as_user_id', $queryParams);
        $this->assertEquals('77777', $queryParams['as_user_id']);
        
        // Clean up contexts
        Config::resetContext('production');
        Config::resetContext('staging');
        Config::setContext('default');
    }
}