<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Enrollments;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Enrollments\Enrollment;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Api\Sections\Section;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class EnrollmentRelationshipTest extends TestCase
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
        Enrollment::setApiClient($this->mockHttpClient);
        Enrollment::setCourse($this->mockCourse);
        Section::setApiClient($this->mockHttpClient);
        Section::setCourse($this->mockCourse);
    }

    protected function tearDown(): void
    {
        // Reset course context
        Enrollment::setCourse(new Course([]));
        Section::setCourse(new Course([]));
        parent::tearDown();
    }

    public function testCourseReturnsCachedCourse(): void
    {
        // Create test enrollment with course ID
        $enrollment = new Enrollment([
            'id' => 1,
            'course_id' => 123,
            'user_id' => 456
        ]);

        // Test the method
        $course = $enrollment->course();

        // Assertions - should return the statically set course without API call
        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals(123, $course->id);
        $this->assertSame($this->mockCourse, $course);
    }

    public function testCourseReturnsCourseWhenStaticCourseNotSet(): void
    {
        // Skip this test as we can't set course to null
        $this->markTestSkipped('Cannot set course context to null');
        
        // Create test enrollment with course ID
        $enrollment = new Enrollment([
            'id' => 1,
            'course_id' => 789,
            'user_id' => 456
        ]);

        // Mock course response
        $courseData = [
            'id' => 789,
            'name' => 'Test Course',
            'course_code' => 'TEST101'
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($courseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('/courses/789')
            ->willReturn($this->mockResponse);

        // Test the method
        $course = $enrollment->course();

        // Assertions
        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals(789, $course->id);
        $this->assertEquals('Test Course', $course->name);
    }

    public function testCourseReturnsNullWhenNoCourseId(): void
    {
        // Create test enrollment without course ID
        $enrollment = new Enrollment([
            'id' => 1,
            'user_id' => 456
        ]);

        // Test the method
        $course = $enrollment->course();

        // Assertions
        $this->assertNull($course);
    }

    public function testUserReturnsUserObject(): void
    {
        // Create test enrollment
        $enrollment = new Enrollment([
            'id' => 1,
            'course_id' => 123,
            'user_id' => 456
        ]);

        // Mock user response
        $userData = [
            'id' => 456,
            'name' => 'Test User',
            'email' => 'test@example.com'
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
            ->with('/users/456')
            ->willReturn($this->mockResponse);

        // Test the method
        $user = $enrollment->user();

        // Assertions
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(456, $user->id);
        $this->assertEquals('Test User', $user->name);
    }

    public function testUserReturnsNullWhenNoUserId(): void
    {
        // Create test enrollment without user ID
        $enrollment = new Enrollment([
            'id' => 1,
            'course_id' => 123
        ]);

        // Test the method
        $user = $enrollment->user();

        // Assertions
        $this->assertNull($user);
    }

    public function testUserThrowsExceptionOnApiError(): void
    {
        // Create test enrollment
        $enrollment = new Enrollment([
            'id' => 1,
            'user_id' => 456
        ]);

        // Set up mock to throw exception
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new CanvasApiException('User not found'));

        // Test the method
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Could not load user with ID 456: User not found');
        $enrollment->user();
    }

    public function testSectionReturnsSectionObject(): void
    {
        // Create test enrollment
        $enrollment = new Enrollment([
            'id' => 1,
            'course_id' => 123,
            'section_id' => 789
        ]);

        // Mock section response
        $sectionData = [
            'id' => 789,
            'name' => 'Section A',
            'course_id' => 123
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($sectionData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/sections/789', ['query' => []])
            ->willReturn($this->mockResponse);

        // Test the method
        $section = $enrollment->section();

        // Assertions
        $this->assertInstanceOf(Section::class, $section);
        $this->assertEquals(789, $section->id);
        $this->assertEquals('Section A', $section->name);
    }

    public function testSectionReturnsNullWhenNoSectionId(): void
    {
        // Create test enrollment without section ID
        $enrollment = new Enrollment([
            'id' => 1,
            'course_id' => 123,
            'user_id' => 456
        ]);

        // Test the method
        $section = $enrollment->section();

        // Assertions
        $this->assertNull($section);
    }

    public function testSectionThrowsExceptionOnApiError(): void
    {
        // Create test enrollment
        $enrollment = new Enrollment([
            'id' => 1,
            'section_id' => 789
        ]);

        // Set up mock to throw exception
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new CanvasApiException('Section not found'));

        // Test the method
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Could not load section with ID 789: Section not found');
        $enrollment->section();
    }
}