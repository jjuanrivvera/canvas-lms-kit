<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Pagination;

use CanvasLMS\Pagination\LinkHeaderParser;
use PHPUnit\Framework\TestCase;

/**
 * LinkHeaderParserTest Class
 *
 * Test cases for LinkHeaderParser class functionality including:
 * - Parsing valid Link headers
 * - Handling malformed headers
 * - Extracting specific relations
 * - Parsing page numbers and per_page values
 * - Edge cases and error handling
 */
class LinkHeaderParserTest extends TestCase
{
    /**
     * @var LinkHeaderParser
     */
    private LinkHeaderParser $parser;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->parser = new LinkHeaderParser();
    }

    /**
     * Test parsing a complete Canvas API Link header
     */
    public function testParseCompleteCanvasLinkHeader(): void
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=2&per_page=10>; rel="next", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1&per_page=10>; rel="prev", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1&per_page=10>; rel="first", ' .
                      '<https://canvas.example.com/api/v1/courses?page=5&per_page=10>; rel="last", ' .
                      '<https://canvas.example.com/api/v1/courses?page=2&per_page=10>; rel="current"';

        $result = $this->parser->parse($linkHeader);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=2&per_page=10', $result['next']);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=1&per_page=10', $result['prev']);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=1&per_page=10', $result['first']);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=5&per_page=10', $result['last']);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=2&per_page=10', $result['current']);
    }

    /**
     * Test parsing Link header with only next relation
     */
    public function testParsePartialLinkHeader(): void
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=2&per_page=50>; rel="next"';

        $result = $this->parser->parse($linkHeader);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=2&per_page=50', $result['next']);
    }

    /**
     * Test parsing empty Link header
     */
    public function testParseEmptyLinkHeader(): void
    {
        $result = $this->parser->parse('');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test parsing malformed Link header
     */
    public function testParseMalformedLinkHeader(): void
    {
        $linkHeader = 'invalid-link-header-format';

        $result = $this->parser->parse($linkHeader);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test parsing Link header with spaces and different formatting
     */
    public function testParseWithVariousSpacing(): void
    {
        $linkHeader = ' <https://canvas.example.com/api/v1/courses?page=2>; rel="next" , ' .
                      '<https://canvas.example.com/api/v1/courses?page=1>;  rel="prev"  ';

        $result = $this->parser->parse($linkHeader);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=2', $result['next']);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=1', $result['prev']);
    }

    /**
     * Test extracting specific relation from Link header
     */
    public function testExtractRelation(): void
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=2>; rel="next", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1>; rel="prev"';

        $nextUrl = $this->parser->extractRelation($linkHeader, 'next');
        $prevUrl = $this->parser->extractRelation($linkHeader, 'prev');
        $lastUrl = $this->parser->extractRelation($linkHeader, 'last');

        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=2', $nextUrl);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=1', $prevUrl);
        $this->assertNull($lastUrl);
    }

    /**
     * Test checking if relation exists in Link header
     */
    public function testHasRelation(): void
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=2>; rel="next"';

        $this->assertTrue($this->parser->hasRelation($linkHeader, 'next'));
        $this->assertFalse($this->parser->hasRelation($linkHeader, 'prev'));
        $this->assertFalse($this->parser->hasRelation($linkHeader, 'last'));
    }

    /**
     * Test getting all relations from Link header
     */
    public function testGetRelations(): void
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=2>; rel="next", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1>; rel="prev", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1>; rel="first"';

        $relations = $this->parser->getRelations($linkHeader);

        $this->assertIsArray($relations);
        $this->assertCount(3, $relations);
        $this->assertContains('next', $relations);
        $this->assertContains('prev', $relations);
        $this->assertContains('first', $relations);
    }

    /**
     * Test extracting page number from URL
     */
    public function testExtractPageNumber(): void
    {
        $url = 'https://canvas.example.com/api/v1/courses?page=5&per_page=10';
        $pageNumber = $this->parser->extractPageNumber($url);

        $this->assertEquals(5, $pageNumber);
    }

    /**
     * Test extracting page number from URL without page parameter
     */
    public function testExtractPageNumberWithoutPageParam(): void
    {
        $url = 'https://canvas.example.com/api/v1/courses?per_page=10';
        $pageNumber = $this->parser->extractPageNumber($url);

        $this->assertNull($pageNumber);
    }

    /**
     * Test extracting page number from URL without query string
     */
    public function testExtractPageNumberWithoutQuery(): void
    {
        $url = 'https://canvas.example.com/api/v1/courses';
        $pageNumber = $this->parser->extractPageNumber($url);

        $this->assertNull($pageNumber);
    }

    /**
     * Test extracting per_page parameter from URL
     */
    public function testExtractPerPage(): void
    {
        $url = 'https://canvas.example.com/api/v1/courses?page=2&per_page=50';
        $perPage = $this->parser->extractPerPage($url);

        $this->assertEquals(50, $perPage);
    }

    /**
     * Test extracting per_page parameter from URL without per_page parameter
     */
    public function testExtractPerPageWithoutPerPageParam(): void
    {
        $url = 'https://canvas.example.com/api/v1/courses?page=2';
        $perPage = $this->parser->extractPerPage($url);

        $this->assertNull($perPage);
    }

    /**
     * Test extracting per_page parameter from URL without query string
     */
    public function testExtractPerPageWithoutQuery(): void
    {
        $url = 'https://canvas.example.com/api/v1/courses';
        $perPage = $this->parser->extractPerPage($url);

        $this->assertNull($perPage);
    }

    /**
     * Test parsing Link header with non-standard rel values
     */
    public function testParseWithNonStandardRelValues(): void
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=2>; rel="custom", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1>; rel="special-page"';

        $result = $this->parser->parse($linkHeader);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=2', $result['custom']);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=1', $result['special-page']);
    }

    /**
     * Test parsing Link header with URLs containing special characters
     */
    public function testParseWithSpecialCharactersInUrl(): void
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?search=test%20query&page=2>; rel="next"';

        $result = $this->parser->parse($linkHeader);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?search=test%20query&page=2', $result['next']);
    }

    /**
     * Test parsing Link header with mixed case rel values
     */
    public function testParseWithMixedCaseRelValues(): void
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=2>; rel="NEXT", ' .
                      '<https://canvas.example.com/api/v1/courses?page=1>; rel="Prev"';

        $result = $this->parser->parse($linkHeader);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=2', $result['NEXT']);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=1', $result['Prev']);
    }

    /**
     * Test parsing Link header with duplicate rel values (last one wins)
     */
    public function testParseWithDuplicateRelValues(): void
    {
        $linkHeader = '<https://canvas.example.com/api/v1/courses?page=2>; rel="next", ' .
                      '<https://canvas.example.com/api/v1/courses?page=3>; rel="next"';

        $result = $this->parser->parse($linkHeader);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('https://canvas.example.com/api/v1/courses?page=3', $result['next']);
    }

    /**
     * Test extracting page number from URL with non-numeric page value
     */
    public function testExtractPageNumberWithNonNumericValue(): void
    {
        $url = 'https://canvas.example.com/api/v1/courses?page=abc&per_page=10';
        $pageNumber = $this->parser->extractPageNumber($url);

        $this->assertNull($pageNumber);
    }

    /**
     * Test extracting per_page from URL with non-numeric value
     */
    public function testExtractPerPageWithNonNumericValue(): void
    {
        $url = 'https://canvas.example.com/api/v1/courses?page=2&per_page=abc';
        $perPage = $this->parser->extractPerPage($url);

        $this->assertNull($perPage);
    }
}
