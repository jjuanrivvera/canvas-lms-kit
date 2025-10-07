<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\OutcomeResults;

use CanvasLMS\Api\OutcomeResults\OutcomeResult;
use CanvasLMS\Config;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class OutcomeResultTest extends TestCase
{
    private HttpClientInterface $mockClient;

    private ResponseInterface $mockResponse;

    private StreamInterface $mockStream;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);

        OutcomeResult::setApiClient($this->mockClient);
        Config::setAccountId(1);
    }

    public function testGet(): void
    {
        $responseData = [
            [
                'id' => 1,
                'score' => 3.5,
                'submitted_or_assessed_at' => '2024-01-15T10:00:00Z',
                'links' => [
                    'user' => 1001,
                    'learning_outcome' => 2001,
                    'alignment' => 'assignment_123',
                ],
            ],
            [
                'id' => 2,
                'score' => 4.0,
                'submitted_or_assessed_at' => '2024-01-16T10:00:00Z',
                'links' => [
                    'user' => 1002,
                    'learning_outcome' => 2001,
                    'alignment' => 'quiz_456',
                ],
            ],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('courses/123/outcome_results')
            ->willReturn($this->mockResponse);

        $results = OutcomeResult::fetchByContext('courses', 123);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(OutcomeResult::class, $results[0]);
        $this->assertEquals(1, $results[0]->id);
        $this->assertEquals(3.5, $results[0]->score);
        $this->assertInstanceOf(\DateTime::class, $results[0]->submittedOrAssessedAt);
        $this->assertEquals('2024-01-15T10:00:00+00:00', $results[0]->submittedOrAssessedAt->format('c'));
        $this->assertEquals(1001, $results[0]->links['user']);
    }

    public function testGetWithUserAndOutcomeIds(): void
    {
        $responseData = [
            [
                'id' => 3,
                'score' => 4.5,
                'links' => [
                    'user' => 1001,
                    'learning_outcome' => 2002,
                ],
            ],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with(
                'courses/123/outcome_results',
                $this->callback(function ($options) {
                    return isset($options['query']) &&
                           $options['query']['user_ids'] === [1001] &&
                           $options['query']['outcome_ids'] === [2002];
                })
            )
            ->willReturn($this->mockResponse);

        $results = OutcomeResult::fetchByContext('courses', 123, ['user_ids' => [1001], 'outcome_ids' => [2002]]);

        $this->assertCount(1, $results);
        $this->assertEquals(3, $results[0]->id);
        $this->assertEquals(1001, $results[0]->links['user']);
        $this->assertEquals(2002, $results[0]->links['learning_outcome']);
    }

    public function testGetWithIncludeParameter(): void
    {
        $responseData = [
            [
                'id' => 4,
                'score' => 3.0,
                'outcome' => [
                    'id' => 2001,
                    'title' => 'Critical Thinking',
                    'mastery_points' => 3,
                ],
                'alignment' => [
                    'id' => 'assignment_789',
                    'name' => 'Essay Assignment',
                ],
            ],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with(
                'courses/123/outcome_results',
                $this->callback(function ($options) {
                    return isset($options['query']) &&
                           in_array('outcomes', $options['query']['include'], true) &&
                           in_array('alignments', $options['query']['include'], true);
                })
            )
            ->willReturn($this->mockResponse);

        $params = [
            'include' => ['outcomes', 'alignments'],
        ];

        $results = OutcomeResult::fetchByContext('courses', 123, $params);

        $this->assertCount(1, $results);
        $this->assertEquals(4, $results[0]->id);
        $this->assertNotNull($results[0]->outcome);
        $this->assertEquals('Critical Thinking', $results[0]->outcome['title']);
        $this->assertNotNull($results[0]->alignment);
        $this->assertEquals('Essay Assignment', $results[0]->alignment['name']);
    }

    public function testGetReturnsEmptyArrayOnNoResults(): void
    {
        $this->mockStream->method('getContents')
            ->willReturn(json_encode([]));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('courses/123/outcome_results')
            ->willReturn($this->mockResponse);

        $results = OutcomeResult::fetchByContext('courses', 123);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testGetWithPagination(): void
    {
        $responseData = [
            ['id' => 1, 'score' => 3.5],
            ['id' => 2, 'score' => 4.0],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockResponse->method('getHeader')
            ->with('Link')
            ->willReturn([
                '<https://canvas.example.com/api/v1/courses/123/outcome_results?page=2>; rel="next"',
            ]);

        $mockPaginatedResponse = $this->getMockBuilder(PaginatedResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasNext'])
            ->getMock();
        $mockPaginatedResponse->items = $responseData;
        $mockPaginatedResponse->nextUrl = 'https://canvas.example.com/api/v1/courses/123/outcome_results?page=2';
        $mockPaginatedResponse->method('hasNext')
            ->willReturn(true);

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/outcome_results', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $response = OutcomeResult::fetchByContextPaginated('courses', 123);

        $this->assertInstanceOf(PaginatedResponse::class, $response);
        $this->assertCount(2, $response->items);
        $this->assertTrue($response->hasNext());
        $this->assertStringContainsString('page=2', $response->nextUrl);
    }

    public function testInvalidContextTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid context type: invalid');

        OutcomeResult::fetchByContext('invalid', 123);
    }

    public function testSupportsMultipleContextTypes(): void
    {
        $contexts = ['courses', 'users'];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode([]));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockClient->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function ($endpoint) {
                $this->assertMatchesRegularExpression('/^(courses|users)\/123\/outcome_results$/', $endpoint);

                return $this->mockResponse;
            });

        foreach ($contexts as $context) {
            $results = OutcomeResult::fetchByContext($context, 123);
            $this->assertIsArray($results);
        }
    }
}
