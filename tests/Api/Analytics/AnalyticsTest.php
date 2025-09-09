<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Analytics;

use CanvasLMS\Api\Analytics\Analytics;
use CanvasLMS\Config;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AnalyticsTest extends TestCase
{
    private MockObject $httpClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        Analytics::setHttpClient($this->httpClient);
        
        // Set default config values
        Config::setAccountId(1);
    }

    /**
     * Helper method to create a mock response
     */
    private function createMockResponse(string $body): ResponseInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($body);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        return $response;
    }

    // ========================================
    // Account/Department Level Analytics Tests
    // ========================================

    public function testFetchAccountActivity(): void
    {
        $expectedResponse = [
            'by_date' => [
                '2012-01-24' => 1240,
                '2012-01-27' => 912
            ],
            'by_category' => [
                'announcements' => 54,
                'assignments' => 256,
                'collaborations' => 18,
                'conferences' => 26,
                'discussions' => 354,
                'files' => 132,
                'general' => 59,
                'grades' => 177,
                'groups' => 132,
                'modules' => 71,
                'other' => 412,
                'pages' => 105,
                'quizzes' => 356
            ]
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/accounts/1/analytics/current/activity', [])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchAccountActivity();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('by_date', $result);
        $this->assertArrayHasKey('by_category', $result);
        $this->assertEquals(1240, $result['by_date']['2012-01-24']);
        $this->assertEquals(54, $result['by_category']['announcements']);
    }

    public function testFetchAccountActivityByTerm(): void
    {
        $expectedResponse = [
            'by_date' => ['2012-01-24' => 100],
            'by_category' => ['assignments' => 50]
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/accounts/1/analytics/terms/5/activity', [])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchAccountActivityByTerm(5);

        $this->assertIsArray($result);
        $this->assertEquals(100, $result['by_date']['2012-01-24']);
    }

    public function testFetchAccountGrades(): void
    {
        $expectedResponse = [
            '0' => 95,
            '1' => 1,
            '93' => 125,
            '94' => 110,
            '95' => 142,
            '96' => 157,
            '97' => 116,
            '98' => 85,
            '99' => 63,
            '100' => 190
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/accounts/1/analytics/current/grades', [])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchAccountGrades();

        $this->assertIsArray($result);
        $this->assertEquals(95, $result['0']);
        $this->assertEquals(190, $result['100']);
    }

    public function testFetchAccountStatistics(): void
    {
        $expectedResponse = [
            'courses' => 27,
            'subaccounts' => 3,
            'teachers' => 36,
            'students' => 418,
            'discussion_topics' => 77,
            'media_objects' => 219,
            'attachments' => 1268,
            'assignments' => 290
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/accounts/1/analytics/current/statistics', [])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchAccountStatistics();

        $this->assertIsArray($result);
        $this->assertEquals(27, $result['courses']);
        $this->assertEquals(418, $result['students']);
    }

    public function testFetchAccountStatisticsBySubaccount(): void
    {
        $expectedResponse = [
            'accounts' => [
                [
                    'name' => 'Math Department',
                    'id' => 188,
                    'courses' => 27,
                    'teachers' => 36,
                    'students' => 418,
                    'discussion_topics' => 77,
                    'media_objects' => 219,
                    'attachments' => 1268,
                    'assignments' => 290
                ]
            ]
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/accounts/1/analytics/current/statistics_by_subaccount', [])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchAccountStatisticsBySubaccount();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('accounts', $result);
        $this->assertCount(1, $result['accounts']);
        $this->assertEquals('Math Department', $result['accounts'][0]['name']);
    }

    // ========================================
    // Course Level Analytics Tests
    // ========================================

    public function testFetchCourseActivity(): void
    {
        $expectedResponse = [
            [
                'date' => '2012-01-24',
                'participations' => 3,
                'views' => 10
            ],
            [
                'date' => '2012-01-25',
                'participations' => 5,
                'views' => 15
            ]
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/courses/123/analytics/activity', [])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchCourseActivity(123);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('2012-01-24', $result[0]['date']);
        $this->assertEquals(3, $result[0]['participations']);
    }

    public function testFetchCourseAssignments(): void
    {
        $expectedResponse = [
            [
                'assignment_id' => 1234,
                'title' => 'Assignment 1',
                'points_possible' => 10,
                'due_at' => '2012-01-25T22:00:00-07:00',
                'unlock_at' => '2012-01-20T22:00:00-07:00',
                'muted' => false,
                'min_score' => 2,
                'max_score' => 10,
                'median' => 7,
                'first_quartile' => 4,
                'third_quartile' => 8,
                'tardiness_breakdown' => [
                    'on_time' => 0.75,
                    'missing' => 0.1,
                    'late' => 0.15
                ]
            ]
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/courses/123/analytics/assignments', [])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchCourseAssignments(123);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(1234, $result[0]['assignment_id']);
        $this->assertEquals('Assignment 1', $result[0]['title']);
        $this->assertEquals(0.75, $result[0]['tardiness_breakdown']['on_time']);
    }

    public function testFetchCourseAssignmentsAsync(): void
    {
        $expectedResponse = [
            'progress_url' => 'https://canvas.example.com/api/v1/progress/123'
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/courses/123/analytics/assignments', ['async' => true])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchCourseAssignments(123, ['async' => true]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('progress_url', $result);
    }

    public function testFetchCourseStudentSummaries(): void
    {
        $expectedResponse = [
            [
                'id' => 2346,
                'page_views' => 351,
                'page_views_level' => '1',
                'max_page_view' => 415,
                'participations' => 1,
                'participations_level' => '3',
                'max_participations' => 10,
                'tardiness_breakdown' => [
                    'total' => 5,
                    'on_time' => 3,
                    'late' => 0,
                    'missing' => 2,
                    'floating' => 0
                ]
            ]
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/courses/123/analytics/student_summaries', ['sort_column' => 'name'])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchCourseStudentSummaries(123, ['sort_column' => 'name']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(2346, $result[0]['id']);
        $this->assertEquals(351, $result[0]['page_views']);
    }

    // ========================================
    // User-in-Course Level Analytics Tests
    // ========================================

    public function testFetchUserCourseActivity(): void
    {
        $expectedResponse = [
            'page_views' => [
                '2012-01-24T13:00:00-00:00' => 19,
                '2012-01-24T14:00:00-00:00' => 13,
                '2012-01-27T09:00:00-00:00' => 23
            ],
            'participations' => [
                [
                    'created_at' => '2012-01-21T22:00:00-06:00',
                    'url' => 'https://canvas.example.com/path/to/canvas'
                ],
                [
                    'created_at' => '2012-01-27T22:00:00-06:00',
                    'url' => 'https://canvas.example.com/path/to/canvas'
                ]
            ]
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/courses/123/analytics/users/456/activity', [])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchUserCourseActivity(123, 456);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('page_views', $result);
        $this->assertArrayHasKey('participations', $result);
        $this->assertEquals(19, $result['page_views']['2012-01-24T13:00:00-00:00']);
        $this->assertCount(2, $result['participations']);
    }

    public function testFetchUserCourseAssignments(): void
    {
        $expectedResponse = [
            [
                'assignment_id' => 1234,
                'title' => 'Assignment 1',
                'points_possible' => 10,
                'due_at' => '2012-01-25T22:00:00-07:00',
                'unlock_at' => '2012-01-20T22:00:00-07:00',
                'muted' => false,
                'min_score' => 2,
                'max_score' => 10,
                'median' => 7,
                'first_quartile' => 4,
                'third_quartile' => 8,
                'module_ids' => [1, 2],
                'submission' => [
                    'posted_at' => '2012-01-23T20:00:00-07:00',
                    'submitted_at' => '2012-01-22T22:00:00-07:00',
                    'score' => 10
                ]
            ]
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/courses/123/analytics/users/456/assignments', [])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchUserCourseAssignments(123, 456);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(1234, $result[0]['assignment_id']);
        $this->assertEquals(10, $result[0]['submission']['score']);
    }

    public function testFetchUserCourseCommunication(): void
    {
        $expectedResponse = [
            '2012-01-24' => [
                'instructorMessages' => 1,
                'studentMessages' => 2
            ],
            '2012-01-27' => [
                'studentMessages' => 1
            ]
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/courses/123/analytics/users/456/communication', [])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchUserCourseCommunication(123, 456);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('2012-01-24', $result);
        $this->assertEquals(1, $result['2012-01-24']['instructorMessages']);
        $this->assertEquals(2, $result['2012-01-24']['studentMessages']);
    }

    // ========================================
    // Completed Terms Analytics Tests
    // ========================================

    public function testFetchAccountCompletedActivity(): void
    {
        $expectedResponse = [
            'by_date' => ['2012-01-24' => 500],
            'by_category' => ['assignments' => 100]
        ];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/accounts/1/analytics/completed/activity', [])
            ->willReturn($this->createMockResponse(json_encode($expectedResponse)));

        $result = Analytics::fetchAccountCompletedActivity();

        $this->assertIsArray($result);
        $this->assertEquals(500, $result['by_date']['2012-01-24']);
    }

    // ========================================
    // Error Handling Tests
    // ========================================

    public function testThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Failed to parse Analytics API response');

        $this->httpClient->expects($this->once())
            ->method('get')
            ->willReturn($this->createMockResponse('invalid json {'));

        Analytics::fetchAccountActivity();
    }

    public function testThrowsExceptionOnHttpError(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Analytics API request failed');

        $this->httpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new \Exception('HTTP error occurred'));

        Analytics::fetchAccountActivity();
    }

    public function testUsesCustomAccountId(): void
    {
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/accounts/999/analytics/current/activity', [])
            ->willReturn($this->createMockResponse('{"by_date": {}, "by_category": {}}'));

        Analytics::fetchAccountActivity(999);
    }

    public function testPassesParameters(): void
    {
        $params = ['per_page' => 50, 'page' => 2];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('/accounts/1/analytics/current/activity', $params)
            ->willReturn($this->createMockResponse('{"by_date": {}, "by_category": {}}'));

        Analytics::fetchAccountActivity(null, $params);
    }
}