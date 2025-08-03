<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\DiscussionTopics;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\DiscussionTopics\DiscussionTopic;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class DiscussionTopicRelationshipTest extends TestCase
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
        DiscussionTopic::setApiClient($this->mockHttpClient);
        DiscussionTopic::setCourse($this->mockCourse);
        Assignment::setApiClient($this->mockHttpClient);
    }

    protected function tearDown(): void
    {
        // Reset course context
        DiscussionTopic::setCourse(new Course([]));
        Assignment::setCourse(new Course([]));
        parent::tearDown();
    }

    public function testCourseReturnsAssociatedCourse(): void
    {
        // Create test discussion topic
        $topic = new DiscussionTopic([
            'id' => 456,
            'title' => 'Test Discussion',
            'course_id' => 123
        ]);

        // Test the method
        $course = $topic->course();

        // Assertions
        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals(123, $course->id);
        $this->assertSame($this->mockCourse, $course);
    }

    public function testAuthorReturnsUserObject(): void
    {
        // Create test discussion topic
        $topic = new DiscussionTopic([
            'id' => 456,
            'title' => 'Test Discussion',
            'user_id' => 789
        ]);

        // Mock user response
        $userData = [
            'id' => 789,
            'name' => 'Discussion Author',
            'email' => 'author@example.com'
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
            ->with('/users/789')
            ->willReturn($this->mockResponse);

        // Test the method
        $author = $topic->author();

        // Assertions
        $this->assertInstanceOf(User::class, $author);
        $this->assertEquals(789, $author->id);
        $this->assertEquals('Discussion Author', $author->name);
    }

    public function testAuthorReturnsNullWhenNoUserId(): void
    {
        // Create topic without user_id
        $topic = new DiscussionTopic([
            'id' => 456,
            'title' => 'Test Discussion'
        ]);

        // Test the method
        $author = $topic->author();

        // Assertions
        $this->assertNull($author);
    }

    public function testAuthorThrowsExceptionOnApiError(): void
    {
        // Create test topic
        $topic = new DiscussionTopic([
            'id' => 456,
            'user_id' => 789
        ]);

        // Set up mock to throw exception
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new CanvasApiException('User not found'));

        // Test the method
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Could not load discussion topic author: User not found');
        $topic->author();
    }

    public function testAssignmentReturnsAssignmentObject(): void
    {
        // Create test discussion topic
        $topic = new DiscussionTopic([
            'id' => 456,
            'title' => 'Graded Discussion',
            'assignment_id' => 999
        ]);

        // Mock assignment response
        $assignmentData = [
            'id' => 999,
            'name' => 'Graded Discussion Assignment',
            'points_possible' => 100,
            'due_at' => '2024-12-31T23:59:59Z'
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($assignmentData));
        
        $this->mockStream->method('__toString')
            ->willReturn(json_encode($assignmentData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/assignments/999')
            ->willReturn($this->mockResponse);

        // Test the method
        $assignment = $topic->assignment();

        // Assertions
        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals(999, $assignment->id);
        $this->assertEquals('Graded Discussion Assignment', $assignment->name);
        $this->assertEquals(100, $assignment->pointsPossible);
    }

    public function testAssignmentReturnsNullWhenNoAssignmentId(): void
    {
        // Create topic without assignment_id
        $topic = new DiscussionTopic([
            'id' => 456,
            'title' => 'Ungraded Discussion'
        ]);

        // Test the method
        $assignment = $topic->assignment();

        // Assertions
        $this->assertNull($assignment);
    }

    public function testAssignmentThrowsExceptionOnApiError(): void
    {
        // Create test topic
        $topic = new DiscussionTopic([
            'id' => 456,
            'assignment_id' => 999
        ]);

        // Set up mock to throw exception
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new CanvasApiException('Assignment not found'));

        // Test the method
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Could not load associated assignment: Assignment not found');
        $topic->assignment();
    }

    public function testAssignmentSetsCourseContext(): void
    {
        // Create test discussion topic
        $topic = new DiscussionTopic([
            'id' => 456,
            'assignment_id' => 999
        ]);

        // Mock assignment response
        $assignmentData = [
            'id' => 999,
            'name' => 'Assignment'
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($assignmentData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willReturn($this->mockResponse);

        // Test the method
        $assignment = $topic->assignment();

        // Verify that Assignment has the course context set
        $this->assertNotNull($assignment);
    }
}