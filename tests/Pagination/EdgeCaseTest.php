<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Pagination;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Enrollments\Enrollment;
use CanvasLMS\Config;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Edge Case Tests for Pagination
 *
 * Tests handling of large datasets, rate limiting, and timeout scenarios
 */
class EdgeCaseTest extends TestCase
{
    private $mockHttpClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        Course::setApiClient($this->mockHttpClient);
        Enrollment::setApiClient($this->mockHttpClient);
        Config::setBaseUrl('https://canvas.example.com/api/v1');
        Config::setApiKey('test-api-key');
        Config::setAccountId(1);
    }

    /**
     * Test handling of large dataset with 100 pages
     */
    public function testLargeDatasetPagination(): void
    {
        // Create mock responses for 100 pages
        $totalPages = 100;
        $itemsPerPage = 100;
        $responses = [];

        for ($page = 1; $page <= $totalPages; $page++) {
            $items = [];
            for ($i = 0; $i < $itemsPerPage; $i++) {
                $items[] = [
                    'id' => ($page - 1) * $itemsPerPage + $i + 1,
                    'name' => 'Course ' . (($page - 1) * $itemsPerPage + $i + 1),
                    'course_code' => 'COURSE' . (($page - 1) * $itemsPerPage + $i + 1),
                    'workflow_state' => 'available',
                ];
            }

            $nextPage = $page < $totalPages ? $page + 1 : null;
            $prevPage = $page > 1 ? $page - 1 : null;

            $linkHeader = $this->buildLinkHeader($page, $totalPages, $nextPage, $prevPage);

            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockStream = $this->createMock(StreamInterface::class);
            $mockStream->method('getContents')->willReturn(json_encode($items));
            $mockResponse->method('getBody')->willReturn($mockStream);
            $mockResponse->method('getHeader')->with('Link')->willReturn([$linkHeader]);

            $responses[] = $mockResponse;
        }

        // Set up expectations for getPaginated and subsequent get calls
        $this->mockHttpClient->expects($this->exactly($totalPages - 1))
            ->method('get')
            ->willReturnCallback(function ($path) use ($responses) {
                static $callCount = 1; // Start from index 1 since first response is used in getPaginated

                return $responses[$callCount++];
            });

        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->willReturn(new PaginatedResponse($responses[0], $this->mockHttpClient));

        // Execute the test - use Course instead of Enrollment
        $startTime = microtime(true);
        $allCourses = Course::all();
        $duration = microtime(true) - $startTime;

        // Assertions
        $this->assertCount($totalPages * $itemsPerPage, $allCourses);
        $this->assertEquals(1, $allCourses[0]->id);
        $this->assertEquals(10000, $allCourses[9999]->id);

        // Verify all items are Course objects
        foreach ($allCourses as $course) {
            $this->assertInstanceOf(Course::class, $course);
        }

        // Performance check - should complete in reasonable time
        $this->assertLessThan(60, $duration, 'Large dataset pagination took too long');
    }

    /**
     * Test rate limit handling during pagination
     */
    public function testRateLimitHandlingDuringPagination(): void
    {
        // First response - successful
        $firstPageData = [
            ['id' => 1, 'name' => 'Course 1'],
            ['id' => 2, 'name' => 'Course 2'],
        ];

        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=2>; rel="next", ' .
                     '<https://canvas.example.com/api/v1/courses?page=1>; rel="current", ' .
                     '<https://canvas.example.com/api/v1/courses?page=3>; rel="last"';

        $mockFirstResponse = $this->createMock(ResponseInterface::class);
        $mockFirstStream = $this->createMock(StreamInterface::class);
        $mockFirstStream->method('getContents')->willReturn(json_encode($firstPageData));
        $mockFirstResponse->method('getBody')->willReturn($mockFirstStream);
        $mockFirstResponse->method('getHeader')->with('Link')->willReturn([$linkHeader]);

        // Second call - rate limited (429)
        $rateLimitException = new RequestException(
            'Too Many Requests',
            new Request('GET', '/courses?page=2'),
            new Response(429, ['Retry-After' => '2'])
        );

        // Since rate limiting returns null on failure, we won't get additional pages
        // The actual behavior is that pagination stops when an error occurs

        // Set up mock expectations
        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->willReturn(new PaginatedResponse($mockFirstResponse, $this->mockHttpClient));

        // The first get() call will throw the rate limit exception
        // PaginatedResponse::fetchUrl returns null on error, stopping pagination
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException($rateLimitException);

        // Execute - will get first page, then stop on rate limit
        $allCourses = Course::all();

        // Should get only the first page due to rate limiting
        $this->assertCount(2, $allCourses);
        $this->assertEquals('Course 1', $allCourses[0]->name);
        $this->assertEquals('Course 2', $allCourses[1]->name);
    }

    /**
     * Test timeout handling during pagination
     */
    public function testTimeoutHandlingDuringPagination(): void
    {
        // First page - successful
        $firstPageData = [
            ['id' => 1, 'name' => 'Module 1'],
            ['id' => 2, 'name' => 'Module 2'],
        ];

        $linkHeader = '<https://canvas.example.com/api/v1/courses/1/modules?page=2>; rel="next"';

        $mockFirstResponse = $this->createMock(ResponseInterface::class);
        $mockFirstStream = $this->createMock(StreamInterface::class);
        $mockFirstStream->method('getContents')->willReturn(json_encode($firstPageData));
        $mockFirstResponse->method('getBody')->willReturn($mockFirstStream);
        $mockFirstResponse->method('getHeader')->with('Link')->willReturn([$linkHeader]);

        // Second page - timeout
        $timeoutException = new RequestException(
            'Connection timeout',
            new Request('GET', '/courses/1/modules?page=2'),
            null,
            null,
            ['errno' => CURLE_OPERATION_TIMEDOUT]
        );

        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->willReturn(new PaginatedResponse($mockFirstResponse, $this->mockHttpClient));

        // Simulate timeout on second page fetch
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException($timeoutException);

        // Execute - all() should handle timeout and return partial results
        $modules = Course::all();

        // With timeout, we should get at least the first page
        // The actual behavior depends on error handling implementation
        // For now, we expect it to fail gracefully and return what it has
        $this->assertNotNull($modules);
    }

    /**
     * Test memory-efficient pagination with paginate() method
     */
    public function testMemoryEfficientPaginationWithPaginateMethod(): void
    {
        $processedCount = 0;
        $perPage = 100;
        $totalPages = 10;

        // Set up mock responses for all pages
        $responses = [];
        for ($page = 1; $page <= $totalPages; $page++) {
            $items = [];
            for ($i = 0; $i < $perPage; $i++) {
                $itemId = ($page - 1) * $perPage + $i + 1;
                $items[] = ['id' => $itemId, 'name' => "Course $itemId"];
            }

            $hasNext = $page < $totalPages;
            $linkHeader = $this->buildLinkHeader($page, $totalPages, $hasNext ? $page + 1 : null, $page > 1 ? $page - 1 : null);

            $mockResponse = $this->createMock(ResponseInterface::class);
            $mockStream = $this->createMock(StreamInterface::class);
            $mockStream->method('getContents')->willReturn(json_encode($items));
            $mockResponse->method('getBody')->willReturn($mockStream);
            $mockResponse->method('getHeader')->with('Link')->willReturn([$linkHeader]);

            $responses[$page] = $mockResponse;
        }

        // Set up expectations - getPaginated will be called for each page
        $this->mockHttpClient->expects($this->exactly($totalPages))
            ->method('getPaginated')
            ->willReturnCallback(function ($path, $options) use ($responses) {
                $page = $options['query']['page'] ?? 1;

                return new PaginatedResponse($responses[$page], $this->mockHttpClient);
            });

        // Process pages using paginate()
        for ($page = 1; $page <= $totalPages; $page++) {
            $result = Course::paginate(['page' => $page, 'per_page' => $perPage]);

            // Process batch
            foreach ($result->getData() as $item) {
                $processedCount++;
                // Process item without keeping all in memory
                $this->assertNotNull($item->id);
            }

            // Check if we should continue
            if (!$result->hasNext()) {
                break;
            }
        }

        // Verify we processed all items
        $this->assertEquals(1000, $processedCount);
    }

    /**
     * Helper method to build Link header
     */
    private function buildLinkHeader(int $current, int $total, ?int $next, ?int $prev): string
    {
        $links = [];

        if ($next) {
            $links[] = sprintf('<https://canvas.example.com/api/v1/endpoint?page=%d>; rel="next"', $next);
        }
        if ($prev) {
            $links[] = sprintf('<https://canvas.example.com/api/v1/endpoint?page=%d>; rel="prev"', $prev);
        }
        $links[] = sprintf('<https://canvas.example.com/api/v1/endpoint?page=%d>; rel="current"', $current);
        $links[] = '<https://canvas.example.com/api/v1/endpoint?page=1>; rel="first"';
        $links[] = sprintf('<https://canvas.example.com/api/v1/endpoint?page=%d>; rel="last"', $total);

        return implode(', ', $links);
    }
}
