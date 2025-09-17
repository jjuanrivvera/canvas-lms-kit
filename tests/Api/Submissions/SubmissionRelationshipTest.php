<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Submissions;

use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Submissions\Submission;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class SubmissionRelationshipTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;

    private ResponseInterface $mockResponse;

    private StreamInterface $mockStream;

    private Course $mockCourse;

    private Assignment $mockAssignment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);
        $this->mockCourse = $this->createMock(Course::class);
        $this->mockCourse->id = 123;
        $this->mockAssignment = $this->createMock(Assignment::class);
        $this->mockAssignment->id = 456;

        // Set up the API client
        Submission::setApiClient($this->mockHttpClient);
        Submission::setCourse($this->mockCourse);
        Submission::setAssignment($this->mockAssignment);
    }

    protected function tearDown(): void
    {
        // Clear context
        Submission::clearContext();
        parent::tearDown();
    }

    public function testCourseReturnsStaticCourse(): void
    {
        // Create test submission
        $submission = new Submission([
            'id' => 789,
            'assignment_id' => 456,
            'user_id' => 111,
        ]);

        // Test the method
        $course = $submission->course();

        // Assertions
        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals(123, $course->id);
        $this->assertSame($this->mockCourse, $course);
    }

    public function testAssignmentReturnsStaticAssignment(): void
    {
        // Create test submission
        $submission = new Submission([
            'id' => 789,
            'assignment_id' => 456,
            'user_id' => 111,
        ]);

        // Test the method
        $assignment = $submission->assignment();

        // Assertions
        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals(456, $assignment->id);
        $this->assertSame($this->mockAssignment, $assignment);
    }

    public function testUserReturnsUserObject(): void
    {
        // Create test submission
        $submission = new Submission([
            'id' => 789,
            'assignment_id' => 456,
            'user_id' => 111,
        ]);

        // Mock user response
        $userData = [
            'id' => 111,
            'name' => 'Student Name',
            'email' => 'student@example.com',
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($userData));

        $this->mockStream->method('__toString')
            ->willReturn(json_encode($userData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('/users/111')
            ->willReturn($this->mockResponse);

        // Test the method
        $user = $submission->user();

        // Assertions
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(111, $user->id);
        $this->assertEquals('Student Name', $user->name);
    }

    public function testUserReturnsNullWhenNoUserId(): void
    {
        // Create submission without user_id
        $submission = new Submission([
            'id' => 789,
            'assignment_id' => 456,
        ]);

        // Test the method
        $user = $submission->user();

        // Assertions
        $this->assertNull($user);
    }

    public function testUserThrowsExceptionOnApiError(): void
    {
        // Create test submission
        $submission = new Submission([
            'id' => 789,
            'user_id' => 111,
        ]);

        // Set up mock to throw exception
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new CanvasApiException('User not found'));

        // Test the method
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Could not load submission user: User not found');
        $submission->user();
    }

    public function testGraderReturnsUserObject(): void
    {
        // Create test submission
        $submission = new Submission([
            'id' => 789,
            'assignment_id' => 456,
            'grader_id' => 222,
        ]);

        // Mock grader response
        $graderData = [
            'id' => 222,
            'name' => 'Teacher Name',
            'email' => 'teacher@example.com',
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($graderData));

        $this->mockStream->method('__toString')
            ->willReturn(json_encode($graderData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('/users/222')
            ->willReturn($this->mockResponse);

        // Test the method
        $grader = $submission->grader();

        // Assertions
        $this->assertInstanceOf(User::class, $grader);
        $this->assertEquals(222, $grader->id);
        $this->assertEquals('Teacher Name', $grader->name);
    }

    public function testGraderReturnsNullWhenNoGraderId(): void
    {
        // Create submission without grader_id
        $submission = new Submission([
            'id' => 789,
            'assignment_id' => 456,
            'user_id' => 111,
        ]);

        // Test the method
        $grader = $submission->grader();

        // Assertions
        $this->assertNull($grader);
    }

    public function testGraderThrowsExceptionOnApiError(): void
    {
        // Create test submission
        $submission = new Submission([
            'id' => 789,
            'grader_id' => 222,
        ]);

        // Set up mock to throw exception
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new CanvasApiException('Grader not found'));

        // Test the method
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Could not load grader: Grader not found');
        $submission->grader();
    }
}
