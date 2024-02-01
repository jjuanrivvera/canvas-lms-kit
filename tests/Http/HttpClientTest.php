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
}
