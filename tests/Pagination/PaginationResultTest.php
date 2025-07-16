<?php

namespace CanvasLMS\Tests\Pagination;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Pagination\PaginationResult;

/**
 * PaginationResultTest Class
 *
 * Test cases for PaginationResult class functionality including:
 * - Creating results with navigation links
 * - Creating results from Link headers
 * - Accessing pagination metadata
 * - Navigation and state checking
 * - Edge cases and error handling
 */
class PaginationResultTest extends TestCase
{
    /**
     * Sample data for testing
     * @var mixed[]
     */
    private array $sampleData;

    /**
     * Sample navigation links for testing
     * @var string[]
     */
    private array $sampleLinks;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->sampleData = [
            ['id' => 1, 'name' => 'Course 1'],
            ['id' => 2, 'name' => 'Course 2'],
            ['id' => 3, 'name' => 'Course 3'],
        ];

        $this->sampleLinks = [
            'first' => 'https://canvas.example.com/api/v1/courses?page=1&per_page=10',
            'prev' => 'https://canvas.example.com/api/v1/courses?page=1&per_page=10',
            'current' => 'https://canvas.example.com/api/v1/courses?page=2&per_page=10',
            'next' => 'https://canvas.example.com/api/v1/courses?page=3&per_page=10',
            'last' => 'https://canvas.example.com/api/v1/courses?page=5&per_page=10',
        ];
    }

    /**
     * Test creating PaginationResult with basic data
     */
    public function testCreateWithBasicData(): void
    {
        $result = new PaginationResult($this->sampleData);

        $this->assertEquals($this->sampleData, $result->getData());
        $this->assertEquals(1, $result->getCurrentPage());
        $this->assertNull($result->getTotalPages());
        $this->assertNull($result->getPerPage());
        $this->assertFalse($result->hasNext());
        $this->assertFalse($result->hasPrev());
    }

    /**
     * Test creating PaginationResult with navigation links
     */
    public function testCreateWithNavigationLinks(): void
    {
        $result = new PaginationResult($this->sampleData, $this->sampleLinks, 2, 5, 10);

        $this->assertEquals($this->sampleData, $result->getData());
        $this->assertEquals(2, $result->getCurrentPage());
        $this->assertEquals(5, $result->getTotalPages());
        $this->assertEquals(10, $result->getPerPage());
        $this->assertTrue($result->hasNext());
        $this->assertTrue($result->hasPrev());
    }

    /**
     * Test creating PaginationResult from Link header
     */
    public function testCreateFromLinkHeader(): void
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=3&per_page=10>; rel="next", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1&per_page=10>; rel="prev", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1&per_page=10>; rel="first", ' .
                      '<https://canvas.example.com/api/v1/courses?page=5&per_page=10>; rel="last", ' .
                      '<https://canvas.example.com/api/v1/courses?page=2&per_page=10>; rel="current"';

        $result = PaginationResult::fromLinkHeader($this->sampleData, $linkHeader);

        $this->assertEquals($this->sampleData, $result->getData());
        $this->assertEquals(2, $result->getCurrentPage());
        $this->assertEquals(5, $result->getTotalPages());
        $this->assertEquals(10, $result->getPerPage());
        $this->assertTrue($result->hasNext());
        $this->assertTrue($result->hasPrev());
    }

    /**
     * Test navigation URL getters
     */
    public function testNavigationUrlGetters(): void
    {
        $result = new PaginationResult($this->sampleData, $this->sampleLinks, 2, 5, 10);

        $this->assertEquals($this->sampleLinks['first'], $result->getFirstUrl());
        $this->assertEquals($this->sampleLinks['prev'], $result->getPrevUrl());
        $this->assertEquals($this->sampleLinks['current'], $result->getCurrentUrl());
        $this->assertEquals($this->sampleLinks['next'], $result->getNextUrl());
        $this->assertEquals($this->sampleLinks['last'], $result->getLastUrl());
    }

    /**
     * Test pagination state checks
     */
    public function testPaginationStateChecks(): void
    {
        // Test first page
        $firstPageResult = new PaginationResult($this->sampleData, [
            'current' => 'https://canvas.example.com/api/v1/courses?page=1&per_page=10',
            'next' => 'https://canvas.example.com/api/v1/courses?page=2&per_page=10',
            'last' => 'https://canvas.example.com/api/v1/courses?page=5&per_page=10',
        ], 1, 5, 10);

        $this->assertTrue($firstPageResult->isFirstPage());
        $this->assertFalse($firstPageResult->isLastPage());
        $this->assertTrue($firstPageResult->hasNext());
        $this->assertFalse($firstPageResult->hasPrev());

        // Test last page
        $lastPageResult = new PaginationResult($this->sampleData, [
            'first' => 'https://canvas.example.com/api/v1/courses?page=1&per_page=10',
            'prev' => 'https://canvas.example.com/api/v1/courses?page=4&per_page=10',
            'current' => 'https://canvas.example.com/api/v1/courses?page=5&per_page=10',
        ], 5, 5, 10);

        $this->assertFalse($lastPageResult->isFirstPage());
        $this->assertTrue($lastPageResult->isLastPage());
        $this->assertFalse($lastPageResult->hasNext());
        $this->assertTrue($lastPageResult->hasPrev());
    }

    /**
     * Test middle page state
     */
    public function testMiddlePageState(): void
    {
        $result = new PaginationResult($this->sampleData, $this->sampleLinks, 2, 5, 10);

        $this->assertFalse($result->isFirstPage());
        $this->assertFalse($result->isLastPage());
        $this->assertTrue($result->hasNext());
        $this->assertTrue($result->hasPrev());
        $this->assertTrue($result->hasMore());
    }

    /**
     * Test single page result
     */
    public function testSinglePageResult(): void
    {
        $result = new PaginationResult($this->sampleData, [
            'current' => 'https://canvas.example.com/api/v1/courses?page=1&per_page=10',
        ], 1, 1, 10);

        $this->assertTrue($result->isFirstPage());
        $this->assertTrue($result->isLastPage());
        $this->assertFalse($result->hasNext());
        $this->assertFalse($result->hasPrev());
        $this->assertFalse($result->hasMore());
    }

    /**
     * Test empty result
     */
    public function testEmptyResult(): void
    {
        $result = new PaginationResult([]);

        $this->assertTrue($result->isEmpty());
        $this->assertEquals(0, $result->getCount());
        $this->assertEmpty($result->getData());
    }

    /**
     * Test non-empty result
     */
    public function testNonEmptyResult(): void
    {
        $result = new PaginationResult($this->sampleData);

        $this->assertFalse($result->isEmpty());
        $this->assertEquals(3, $result->getCount());
        $this->assertEquals($this->sampleData, $result->getData());
    }

    /**
     * Test pagination summary
     */
    public function testPaginationSummary(): void
    {
        $result = new PaginationResult($this->sampleData, $this->sampleLinks, 2, 5, 10);

        $summary = $result->getSummary();
        $this->assertEquals('Page 2 of 5 (3 items)', $summary);
    }

    /**
     * Test pagination summary without total pages
     */
    public function testPaginationSummaryWithoutTotalPages(): void
    {
        $result = new PaginationResult($this->sampleData, [], 2, null, 10);

        $summary = $result->getSummary();
        $this->assertEquals('Page 2 (3 items)', $summary);
    }

    /**
     * Test navigation URLs getter
     */
    public function testGetNavigationUrls(): void
    {
        $result = new PaginationResult($this->sampleData, $this->sampleLinks, 2, 5, 10);

        $navigationUrls = $result->getNavigationUrls();
        $this->assertEquals($this->sampleLinks, $navigationUrls);
    }

    /**
     * Test navigation URLs getter with null values filtered out
     */
    public function testGetNavigationUrlsWithNullValues(): void
    {
        $linksWithNulls = [
            'first' => 'https://canvas.example.com/api/v1/courses?page=1&per_page=10',
            'prev' => null,
            'current' => 'https://canvas.example.com/api/v1/courses?page=1&per_page=10',
            'next' => 'https://canvas.example.com/api/v1/courses?page=2&per_page=10',
            'last' => null,
        ];

        $result = new PaginationResult($this->sampleData, $linksWithNulls, 1, null, 10);

        $navigationUrls = $result->getNavigationUrls();
        $expectedUrls = [
            'first' => 'https://canvas.example.com/api/v1/courses?page=1&per_page=10',
            'current' => 'https://canvas.example.com/api/v1/courses?page=1&per_page=10',
            'next' => 'https://canvas.example.com/api/v1/courses?page=2&per_page=10',
        ];

        $this->assertEquals($expectedUrls, $navigationUrls);
    }

    /**
     * Test toArray method
     */
    public function testToArray(): void
    {
        $result = new PaginationResult($this->sampleData, $this->sampleLinks, 2, 5, 10);

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('pagination', $array);

        $this->assertEquals($this->sampleData, $array['data']);

        $pagination = $array['pagination'];
        $this->assertEquals(2, $pagination['current_page']);
        $this->assertEquals(5, $pagination['total_pages']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(3, $pagination['count']);
        $this->assertTrue($pagination['has_next']);
        $this->assertTrue($pagination['has_prev']);
        $this->assertFalse($pagination['is_first_page']);
        $this->assertFalse($pagination['is_last_page']);
        $this->assertEquals($this->sampleLinks, $pagination['navigation_urls']);
    }

    /**
     * Test creating from Link header with missing current URL
     */
    public function testCreateFromLinkHeaderWithoutCurrentUrl(): void
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=2&per_page=10>; rel="next"';

        $result = PaginationResult::fromLinkHeader($this->sampleData, $linkHeader);

        $this->assertEquals(1, $result->getCurrentPage()); // Should default to 1
        $this->assertNull($result->getTotalPages());
        $this->assertEquals(10, $result->getPerPage());
    }

    /**
     * Test creating from empty Link header
     */
    public function testCreateFromEmptyLinkHeader(): void
    {
        $result = PaginationResult::fromLinkHeader($this->sampleData, '');

        $this->assertEquals($this->sampleData, $result->getData());
        $this->assertEquals(1, $result->getCurrentPage());
        $this->assertNull($result->getTotalPages());
        $this->assertNull($result->getPerPage());
        $this->assertFalse($result->hasNext());
        $this->assertFalse($result->hasPrev());
    }

    /**
     * Test isLastPage when total pages is unknown
     */
    public function testIsLastPageWithUnknownTotalPages(): void
    {
        // Test with next URL available
        $resultWithNext = new PaginationResult($this->sampleData, [
            'next' => 'https://canvas.example.com/api/v1/courses?page=3&per_page=10',
        ], 2, null, 10);

        $this->assertFalse($resultWithNext->isLastPage());

        // Test without next URL
        $resultWithoutNext = new PaginationResult($this->sampleData, [], 2, null, 10);

        $this->assertTrue($resultWithoutNext->isLastPage());
    }

    /**
     * Test hasMore method
     */
    public function testHasMore(): void
    {
        $resultWithNext = new PaginationResult($this->sampleData, [
            'next' => 'https://canvas.example.com/api/v1/courses?page=3&per_page=10',
        ], 2, 5, 10);

        $this->assertTrue($resultWithNext->hasMore());

        $resultWithoutNext = new PaginationResult($this->sampleData, [], 2, 5, 10);

        $this->assertFalse($resultWithoutNext->hasMore());
    }

    /**
     * Test with complex data structure
     */
    public function testWithComplexData(): void
    {
        $complexData = [
            [
                'id' => 1,
                'name' => 'Course 1',
                'enrollments' => [
                    ['role' => 'student', 'user_id' => 123],
                    ['role' => 'teacher', 'user_id' => 456],
                ],
                'term' => ['name' => 'Fall 2023'],
            ],
            [
                'id' => 2,
                'name' => 'Course 2',
                'enrollments' => [],
                'term' => ['name' => 'Spring 2024'],
            ],
        ];

        $result = new PaginationResult($complexData, $this->sampleLinks, 2, 5, 10);

        $this->assertEquals($complexData, $result->getData());
        $this->assertEquals(2, $result->getCount());
        $this->assertFalse($result->isEmpty());
    }
}