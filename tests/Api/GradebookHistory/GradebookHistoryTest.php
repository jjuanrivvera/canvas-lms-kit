<?php

declare(strict_types=1);

namespace Tests\Api\GradebookHistory;

use CanvasLMS\Api\GradebookHistory\GradebookHistory;
use CanvasLMS\Config;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Objects\GradebookHistoryDay;
use CanvasLMS\Objects\GradebookHistoryGrader;
use CanvasLMS\Objects\SubmissionHistory;
use CanvasLMS\Objects\SubmissionVersion;
use CanvasLMS\Pagination\PaginatedResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GradebookHistoryTest extends TestCase
{
    private $httpClientMock;

    private int $testCourseId = 123;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        GradebookHistory::setApiClient($this->httpClientMock);
        Config::setBaseUrl('https://canvas.test.com/api/v1');
        GradebookHistory::setCourse($this->testCourseId);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        GradebookHistory::resetCourse();
    }

    private function createMockResponse($data, array $headers = []): ResponseInterface
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('getContents')
            ->willReturn(json_encode($data));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        foreach ($headers as $name => $value) {
            $mockResponse->method('getHeader')
                ->with($name)
                ->willReturn(is_array($value) ? $value : [$value]);
        }

        return $mockResponse;
    }

    public function testFetchDays(): void
    {
        $mockResponseData = [
            [
                'date' => '2025-01-15',
                'graders' => [
                    [
                        'id' => 456,
                        'name' => 'John Teacher',
                        'assignments' => [789, 790],
                    ],
                ],
            ],
            [
                'date' => '2025-01-14',
                'graders' => [
                    [
                        'id' => 457,
                        'name' => 'Jane Teacher',
                        'assignments' => [791],
                    ],
                ],
            ],
        ];

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with(
                'courses/123/gradebook_history/days',
                ['query' => []]
            )
            ->willReturn($this->createMockResponse($mockResponseData));

        $days = GradebookHistory::fetchDays();

        $this->assertCount(2, $days);
        $this->assertInstanceOf(GradebookHistoryDay::class, $days[0]);
        $this->assertEquals('2025-01-15', $days[0]->date);
        $this->assertCount(1, $days[0]->graders);
        $this->assertEquals('John Teacher', $days[0]->graders[0]->name);
    }

    public function testFetchDay(): void
    {
        $mockResponseData = [
            [
                'id' => 456,
                'name' => 'John Teacher',
                'assignments' => [789, 790],
            ],
            [
                'id' => 457,
                'name' => 'Jane Teacher',
                'assignments' => [791],
            ],
        ];

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with(
                'courses/123/gradebook_history/2025-01-15',
                ['query' => []]
            )
            ->willReturn($this->createMockResponse($mockResponseData));

        $graders = GradebookHistory::fetchDay('2025-01-15');

        $this->assertCount(2, $graders);
        $this->assertInstanceOf(GradebookHistoryGrader::class, $graders[0]);
        $this->assertEquals('John Teacher', $graders[0]->name);
        $this->assertEquals([789, 790], $graders[0]->assignments);
    }

    public function testFetchDayWithInvalidDateFormat(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Date must be in YYYY-MM-DD format');

        GradebookHistory::fetchDay('01-15-2025');
    }

    public function testFetchSubmissions(): void
    {
        $mockResponseData = [
            [
                'submission_id' => 12345,
                'versions' => [
                    [
                        'assignment_id' => 789,
                        'assignment_name' => 'Quiz 1',
                        'current_grade' => '85',
                        'current_graded_at' => '2025-01-15T15:00:00Z',
                        'current_grader' => 'John Teacher',
                        'grade_matches_current_submission' => true,
                        'graded_at' => '2025-01-15T15:00:00Z',
                        'grader' => 'John Teacher',
                        'grader_id' => 456,
                        'id' => 12345,
                        'new_grade' => '85',
                        'new_graded_at' => '2025-01-15T15:00:00Z',
                        'new_grader' => 'John Teacher',
                        'previous_grade' => '80',
                        'previous_graded_at' => '2025-01-14T14:00:00Z',
                        'previous_grader' => 'John Teacher',
                        'score' => 85,
                        'user_id' => 123,
                        'user_name' => 'Alice Student',
                        'submission_type' => 'online_quiz',
                        'workflow_state' => 'graded',
                    ],
                ],
            ],
        ];

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with(
                'courses/123/gradebook_history/2025-01-15/graders/456/assignments/789/submissions',
                ['query' => []]
            )
            ->willReturn($this->createMockResponse($mockResponseData));

        $submissions = GradebookHistory::fetchSubmissions('2025-01-15', 456, 789);

        $this->assertCount(1, $submissions);
        $this->assertInstanceOf(SubmissionHistory::class, $submissions[0]);
        $this->assertEquals(12345, $submissions[0]->submissionId);
        $this->assertCount(1, $submissions[0]->versions);
        $this->assertInstanceOf(SubmissionVersion::class, $submissions[0]->versions[0]);
        $this->assertEquals('85', $submissions[0]->versions[0]->newGrade);
    }

    public function testFetchFeed(): void
    {
        $mockResponseData = [
            [
                'assignment_id' => 789,
                'assignment_name' => 'Quiz 1',
                'grade' => '85',
                'graded_at' => '2025-01-15T15:00:00Z',
                'grader' => 'John Teacher',
                'grader_id' => 456,
                'id' => 12345,
                'score' => 85,
                'user_id' => 123,
                'user_name' => 'Alice Student',
                'submission_type' => 'online_quiz',
                'workflow_state' => 'graded',
            ],
        ];

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with(
                'courses/123/gradebook_history/feed',
                ['query' => ['assignment_id' => 789]]
            )
            ->willReturn($this->createMockResponse($mockResponseData));

        $versions = GradebookHistory::fetchFeed(['assignment_id' => 789]);

        $this->assertCount(1, $versions);
        $this->assertInstanceOf(SubmissionVersion::class, $versions[0]);
        $this->assertEquals(789, $versions[0]->assignmentId);
        $this->assertEquals('Quiz 1', $versions[0]->assignmentName);
    }

    public function testFetchFeedPaginated(): void
    {
        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with(
                'courses/123/gradebook_history/feed',
                ['query' => ['per_page' => 10]]
            )
            ->willReturn($mockPaginatedResponse);

        $response = GradebookHistory::fetchFeedPaginated(['per_page' => 10]);

        $this->assertInstanceOf(PaginatedResponse::class, $response);
    }

    public function testRequiresCourseContext(): void
    {
        GradebookHistory::resetCourse();

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context is required for Gradebook History operations');

        GradebookHistory::fetchDays();
    }

    public function testSetAndGetCourse(): void
    {
        GradebookHistory::setCourse(456);
        $this->assertEquals(456, GradebookHistory::getCourse());
    }

    public function testResetCourse(): void
    {
        GradebookHistory::setCourse(456);
        GradebookHistory::resetCourse();
        $this->assertNull(GradebookHistory::getCourse());
    }
}
