<?php

namespace CanvasLMS\Tests\Pagination;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use CanvasLMS\Interfaces\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PaginatedResponseTest Class
 *
 * Test cases for PaginatedResponse class functionality including:
 * - Wrapping HTTP responses with pagination
 * - Parsing Link headers from responses
 * - Navigation through paginated results
 * - Converting to PaginationResult objects
 * - Fetching all pages
 */
class PaginatedResponseTest extends TestCase
{
    /**
     * Mock HTTP client
     * @var HttpClientInterface
     */
    private HttpClientInterface $mockHttpClient;

    /**
     * Mock HTTP response
     * @var ResponseInterface
     */
    private ResponseInterface $mockResponse;

    /**
     * Mock response stream
     * @var StreamInterface
     */
    private StreamInterface $mockStream;

    /**
     * Sample response data
     * @var mixed[]
     */
    private array $sampleData;

    /**
     * Sample Link header
     * @var string
     */
    private string $sampleLinkHeader;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);

        $this->sampleData = [
            ['id' => 1, 'name' => 'Course 1'],
            ['id' => 2, 'name' => 'Course 2'],
            ['id' => 3, 'name' => 'Course 3'],
        ];

        $this->sampleLinkHeader = '<https://canvas.example.com/api/v1/courses?page=3&per_page=10>; rel="next", ' .
                                  '<https://canvas.example.com/api/v1/courses?page=1&per_page=10>; rel="prev", ' .
                                  '<https://canvas.example.com/api/v1/courses?page=1&per_page=10>; rel="first", ' .
                                  '<https://canvas.example.com/api/v1/courses?page=5&per_page=10>; rel="last", ' .
                                  '<https://canvas.example.com/api/v1/courses?page=2&per_page=10>; rel="current"';
    }

    /**
     * Test creating PaginatedResponse with Link header
     */
    public function testCreateWithLinkHeader(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertEquals($this->mockResponse, $paginatedResponse->getResponse());
        $this->assertEquals($this->sampleLinkHeader, $paginatedResponse->getLinkHeader());
    }

    /**
     * Test creating PaginatedResponse without Link header
     */
    public function testCreateWithoutLinkHeader(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertEquals('', $paginatedResponse->getLinkHeader());
        $this->assertEmpty($paginatedResponse->getNavigationUrls());
    }

    /**
     * Test getting response body
     */
    public function testGetBody(): void
    {
        $responseBody = json_encode($this->sampleData);

        $this->mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn($responseBody);

        $this->mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertEquals($responseBody, $paginatedResponse->getBody());
    }

    /**
     * Test getting JSON data
     */
    public function testGetJsonData(): void
    {
        $responseBody = json_encode($this->sampleData);

        $this->mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn($responseBody);

        $this->mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertEquals($this->sampleData, $paginatedResponse->getJsonData());
    }

    /**
     * Test getting JSON data with invalid JSON
     */
    public function testGetJsonDataWithInvalidJson(): void
    {
        $invalidJson = '{invalid json}';

        $this->mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn($invalidJson);

        $this->mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertEquals([], $paginatedResponse->getJsonData());
    }

    /**
     * Test navigation URL methods
     */
    public function testNavigationUrlMethods(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=3&per_page=10', $paginatedResponse->getNextUrl());
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=1&per_page=10', $paginatedResponse->getPrevUrl());
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=1&per_page=10', $paginatedResponse->getFirstUrl());
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=5&per_page=10', $paginatedResponse->getLastUrl());
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=2&per_page=10', $paginatedResponse->getCurrentUrl());
    }

    /**
     * Test navigation state methods
     */
    public function testNavigationStateMethods(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertTrue($paginatedResponse->hasNext());
        $this->assertTrue($paginatedResponse->hasPrev());
        $this->assertTrue($paginatedResponse->hasRelation('next'));
        $this->assertTrue($paginatedResponse->hasRelation('prev'));
        $this->assertFalse($paginatedResponse->hasRelation('nonexistent'));
    }

    /**
     * Test getting current page number
     */
    public function testGetCurrentPage(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertEquals(2, $paginatedResponse->getCurrentPage());
    }

    /**
     * Test getting current page number without current URL
     */
    public function testGetCurrentPageWithoutCurrentUrl(): void
    {
        $linkHeaderWithoutCurrent = '<https://canvas.example.com/api/v1/courses?page=2&per_page=10>; rel="next"';

        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$linkHeaderWithoutCurrent]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertEquals(1, $paginatedResponse->getCurrentPage());
    }

    /**
     * Test getting total pages
     */
    public function testGetTotalPages(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertEquals(5, $paginatedResponse->getTotalPages());
    }

    /**
     * Test getting total pages without last URL
     */
    public function testGetTotalPagesWithoutLastUrl(): void
    {
        $linkHeaderWithoutLast = '<https://canvas.example.com/api/v1/courses?page=2&per_page=10>; rel="next"';

        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$linkHeaderWithoutLast]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertNull($paginatedResponse->getTotalPages());
    }

    /**
     * Test getting per_page parameter
     */
    public function testGetPerPage(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $this->assertEquals(10, $paginatedResponse->getPerPage());
    }

    /**
     * Test converting to PaginationResult
     */
    public function testToPaginationResult(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $result = $paginatedResponse->toPaginationResult($this->sampleData);

        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertEquals($this->sampleData, $result->getData());
        $this->assertEquals(2, $result->getCurrentPage());
        $this->assertEquals(5, $result->getTotalPages());
        $this->assertEquals(10, $result->getPerPage());
    }

    /**
     * Test getting pagination info
     */
    public function testGetPaginationInfo(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $info = $paginatedResponse->getPaginationInfo();

        $this->assertIsArray($info);
        $this->assertEquals(2, $info['current_page']);
        $this->assertEquals(5, $info['total_pages']);
        $this->assertEquals(10, $info['per_page']);
        $this->assertTrue($info['has_next']);
        $this->assertTrue($info['has_prev']);
        $this->assertIsArray($info['navigation_urls']);
    }

    /**
     * Test getting next page
     */
    public function testGetNext(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $nextResponseBody = json_encode([['id' => 4, 'name' => 'Course 4']]);
        $nextMockStream = $this->createMock(StreamInterface::class);
        $nextMockResponse = $this->createMock(ResponseInterface::class);

        $nextMockStream->expects($this->once())
            ->method('getContents')
            ->willReturn($nextResponseBody);

        $nextMockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($nextMockStream);

        $nextMockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([]);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('/courses', ['query' => ['page' => '3', 'per_page' => '10']])
            ->willReturn($nextMockResponse);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $nextResponse = $paginatedResponse->getNext();

        $this->assertInstanceOf(PaginatedResponse::class, $nextResponse);
        $this->assertEquals([['id' => 4, 'name' => 'Course 4']], $nextResponse->getJsonData());
    }

    /**
     * Test getting next page when no next URL exists
     */
    public function testGetNextWithoutNextUrl(): void
    {
        $linkHeaderWithoutNext = '<https://canvas.example.com/api/v1/courses?page=1&per_page=10>; rel="prev"';

        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$linkHeaderWithoutNext]);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $nextResponse = $paginatedResponse->getNext();

        $this->assertNull($nextResponse);
    }

    /**
     * Test getting previous page
     */
    public function testGetPrev(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $prevResponseBody = json_encode([['id' => 0, 'name' => 'Course 0']]);
        $prevMockStream = $this->createMock(StreamInterface::class);
        $prevMockResponse = $this->createMock(ResponseInterface::class);

        $prevMockStream->expects($this->once())
            ->method('getContents')
            ->willReturn($prevResponseBody);

        $prevMockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($prevMockStream);

        $prevMockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([]);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('/courses', ['query' => ['page' => '1', 'per_page' => '10']])
            ->willReturn($prevMockResponse);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $prevResponse = $paginatedResponse->getPrev();

        $this->assertInstanceOf(PaginatedResponse::class, $prevResponse);
        $this->assertEquals([['id' => 0, 'name' => 'Course 0']], $prevResponse->getJsonData());
    }

    /**
     * Test getting first page
     */
    public function testGetFirst(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $firstResponseBody = json_encode([['id' => 1, 'name' => 'First Course']]);
        $firstMockStream = $this->createMock(StreamInterface::class);
        $firstMockResponse = $this->createMock(ResponseInterface::class);

        $firstMockStream->expects($this->once())
            ->method('getContents')
            ->willReturn($firstResponseBody);

        $firstMockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($firstMockStream);

        $firstMockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([]);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('/courses', ['query' => ['page' => '1', 'per_page' => '10']])
            ->willReturn($firstMockResponse);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $firstResponse = $paginatedResponse->getFirst();

        $this->assertInstanceOf(PaginatedResponse::class, $firstResponse);
        $this->assertEquals([['id' => 1, 'name' => 'First Course']], $firstResponse->getJsonData());
    }

    /**
     * Test getting last page
     */
    public function testGetLast(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $lastResponseBody = json_encode([['id' => 50, 'name' => 'Last Course']]);
        $lastMockStream = $this->createMock(StreamInterface::class);
        $lastMockResponse = $this->createMock(ResponseInterface::class);

        $lastMockStream->expects($this->once())
            ->method('getContents')
            ->willReturn($lastResponseBody);

        $lastMockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($lastMockStream);

        $lastMockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([]);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('/courses', ['query' => ['page' => '5', 'per_page' => '10']])
            ->willReturn($lastMockResponse);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $lastResponse = $paginatedResponse->getLast();

        $this->assertInstanceOf(PaginatedResponse::class, $lastResponse);
        $this->assertEquals([['id' => 50, 'name' => 'Last Course']], $lastResponse->getJsonData());
    }

    /**
     * Test fetch all pages
     */
    public function testFetchAllPages(): void
    {
        // Mock the current response
        $currentResponseBody = json_encode([['id' => 1, 'name' => 'Course 1']]);
        $currentMockStream = $this->createMock(StreamInterface::class);

        $currentMockStream->expects($this->once())
            ->method('getContents')
            ->willReturn($currentResponseBody);

        $this->mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($currentMockStream);

        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn(['<https://canvas.example.com/api/v1/courses?page=2&per_page=10>; rel="next"']);

        // Mock the next response
        $nextResponseBody = json_encode([['id' => 2, 'name' => 'Course 2']]);
        $nextMockStream = $this->createMock(StreamInterface::class);
        $nextMockResponse = $this->createMock(ResponseInterface::class);

        $nextMockStream->expects($this->once())
            ->method('getContents')
            ->willReturn($nextResponseBody);

        $nextMockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($nextMockStream);

        $nextMockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([]); // No more pages

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('/courses', ['query' => ['page' => '2', 'per_page' => '10']])
            ->willReturn($nextMockResponse);

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $allData = $paginatedResponse->fetchAllPages();

        $expectedData = [
            ['id' => 1, 'name' => 'Course 1'],
            ['id' => 2, 'name' => 'Course 2'],
        ];

        $this->assertEquals($expectedData, $allData);
    }

    /**
     * Test fetch all pages with single page
     */
    public function testFetchAllPagesWithSinglePage(): void
    {
        $responseBody = json_encode($this->sampleData);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn($responseBody);

        $this->mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([]); // No pagination links

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $allData = $paginatedResponse->fetchAllPages();

        $this->assertEquals($this->sampleData, $allData);
    }

    /**
     * Test error handling when fetching next page fails
     */
    public function testGetNextWithException(): void
    {
        $this->mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([$this->sampleLinkHeader]);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new \Exception('Network error'));

        $paginatedResponse = new PaginatedResponse($this->mockResponse, $this->mockHttpClient);

        $nextResponse = $paginatedResponse->getNext();

        $this->assertNull($nextResponse);
    }
}
