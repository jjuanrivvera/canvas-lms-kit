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
use CanvasLMS\Pagination\PaginatedResponse;

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
}
