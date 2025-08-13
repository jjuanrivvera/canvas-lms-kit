<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Assignments;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Submissions\Submission;
use CanvasLMS\Api\Rubrics\Rubric;
use CanvasLMS\Api\Rubrics\RubricAssociation;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AssignmentRelationshipTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;
    private ResponseInterface $mockResponse;
    private StreamInterface $mockStream;
    private Course $mockCourse;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);
        $this->mockCourse = $this->createMock(Course::class);
        $this->mockCourse->id = 123;

        // Set up the API client
        Assignment::setApiClient($this->mockHttpClient);
        Assignment::setCourse($this->mockCourse);
    }

    protected function tearDown(): void
    {
        // Reset course context by setting a new empty course
        Assignment::setCourse(new Course([]));
        parent::tearDown();
    }

    public function testSubmissionsReturnsArrayOfSubmissionObjects(): void
    {
        // Create test assignment
        $assignment = new Assignment(['id' => 456]);

        // Mock response data
        $submissionsData = [
            ['id' => 1, 'user_id' => 100, 'assignment_id' => 456, 'score' => 95],
            ['id' => 2, 'user_id' => 101, 'assignment_id' => 456, 'score' => 88],
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($submissionsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/assignments/456/submissions', ['query' => []])
            ->willReturn($this->mockResponse);

        // Test the method  
        $submissions = $assignment->submissions();

        // Assertions
        $this->assertIsArray($submissions);
        $this->assertCount(2, $submissions);
        $this->assertInstanceOf(Submission::class, $submissions[0]);
        $this->assertEquals(1, $submissions[0]->id);
        $this->assertEquals(100, $submissions[0]->userId);
    }

    public function testGetSubmissionForUserReturnsSubmissionObject(): void
    {
        // Create test assignment
        $assignment = new Assignment(['id' => 456]);

        // Mock response data
        $submissionData = [
            'id' => 1,
            'user_id' => 100,
            'assignment_id' => 456,
            'score' => 95,
            'grade' => 'A'
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($submissionData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/assignments/456/submissions/100')
            ->willReturn($this->mockResponse);

        // Test the method
        $submission = $assignment->getSubmissionForUser(100);

        // Assertions
        $this->assertInstanceOf(Submission::class, $submission);
        $this->assertEquals(1, $submission->id);
        $this->assertEquals(100, $submission->userId);
        $this->assertEquals(95, $submission->score);
    }

    public function testGetSubmissionForUserReturnsNullOnException(): void
    {
        // Create test assignment
        $assignment = new Assignment(['id' => 456]);

        // Set up mock to throw exception with 404 in message
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new CanvasApiException('404 Not Found'));

        // Test the method
        $submission = $assignment->getSubmissionForUser(100);

        // Assertions
        $this->assertNull($submission);
    }

    public function testGetSubmissionCountReturnsCount(): void
    {
        // Create test assignment
        $assignment = new Assignment(['id' => 456]);

        // Mock response data with multiple submissions
        $submissionsData = [
            ['id' => 1, 'user_id' => 100],
            ['id' => 2, 'user_id' => 101],
            ['id' => 3, 'user_id' => 102],
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($submissionsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/assignments/456/submissions', ['query' => ['per_page' => 100]])
            ->willReturn($this->mockResponse);

        // Test the method
        $count = $assignment->getSubmissionCount();

        // Assertions
        $this->assertEquals(3, $count);
    }

    public function testRubricReturnsRubricObjectFromEmbeddedData(): void
    {
        // Create test assignment with embedded rubric
        $rubricData = [
            'id' => 789,
            'title' => 'Test Rubric',
            'data' => [],
            'points_possible' => 100
        ];
        
        $assignment = new Assignment([
            'id' => 456,
            'rubric' => $rubricData
        ]);

        // Test the method
        $rubric = $assignment->rubric();

        // Assertions
        $this->assertInstanceOf(Rubric::class, $rubric);
        $this->assertEquals(789, $rubric->id);
        $this->assertEquals('Test Rubric', $rubric->title);
    }

    public function testRubricReturnsNullWhenNoRubric(): void
    {
        // Create test assignment without rubric
        $assignment = new Assignment(['id' => 456]);

        // Test the method
        $rubric = $assignment->rubric();

        // Assertions
        $this->assertNull($rubric);
    }

    public function testOverridesReturnsArray(): void
    {
        // Create test assignment
        $assignment = new Assignment(['id' => 456]);

        // Mock response data
        $overridesData = [
            ['id' => 1, 'assignment_id' => 456, 'student_ids' => [100, 101]],
            ['id' => 2, 'assignment_id' => 456, 'student_ids' => [102]],
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($overridesData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/assignments/456/overrides')
            ->willReturn($this->mockResponse);

        // Test the method
        $overrides = $assignment->overrides();

        // Assertions
        $this->assertIsArray($overrides);
        $this->assertCount(2, $overrides);
        $this->assertEquals(1, $overrides[0]['id']);
        $this->assertContains(100, $overrides[0]['student_ids']);
    }

    public function testRubricAssociationReturnsRubricAssociationObject(): void
    {
        // Create test assignment with rubric data
        $assignment = new Assignment([
            'id' => 456,
            'rubric' => [
                'id' => 789,
                'title' => 'Test Rubric'
            ]
        ]);

        // Mock response data - returns an array of associations
        $associationsData = [
            [
                'id' => 999,
                'rubric_id' => 789,
                'association_id' => 456,
                'association_type' => 'Assignment'
            ]
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($associationsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with(
                'courses/123/rubric_associations',
                [
                    'query' => [
                        'include' => ['association_object'],
                        'association_type' => 'Assignment',
                        'association_id' => 456
                    ]
                ]
            )
            ->willReturn($this->mockResponse);

        // Test the method
        $association = $assignment->rubricAssociation();

        // Assertions
        $this->assertInstanceOf(RubricAssociation::class, $association);
        $this->assertEquals(999, $association->id);
        $this->assertEquals(789, $association->rubricId);
    }

    public function testRubricAssociationReturnsNullWhenNoAssociation(): void
    {
        // Create test assignment without rubric association
        $assignment = new Assignment(['id' => 456]);

        // Test the method
        $association = $assignment->rubricAssociation();

        // Assertions
        $this->assertNull($association);
    }

    public function testAllMethodsThrowExceptionWhenAssignmentIdMissing(): void
    {
        // Create assignment without ID
        $assignment = new Assignment([]);

        // Test submissions
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Assignment ID is required to fetch submissions');
        $assignment->submissions();
    }

    public function testGetSubmissionCountThrowsExceptionWhenAssignmentIdMissing(): void
    {
        // Create assignment without ID
        $assignment = new Assignment([]);

        // Test getSubmissionCount
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Assignment ID is required to get submission count');
        $assignment->getSubmissionCount();
    }

    public function testOverridesThrowsExceptionWhenAssignmentIdMissing(): void
    {
        // Create assignment without ID
        $assignment = new Assignment([]);

        // Test overrides
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Assignment ID is required to fetch overrides');
        $assignment->overrides();
    }
}