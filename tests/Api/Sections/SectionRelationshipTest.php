<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Sections;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Enrollments\Enrollment;
use CanvasLMS\Api\Sections\Section;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class SectionRelationshipTest extends TestCase
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
        Section::setApiClient($this->mockHttpClient);
        Section::setCourse($this->mockCourse);
    }

    protected function tearDown(): void
    {
        // Reset course context
        Section::setCourse(new Course([]));
        parent::tearDown();
    }

    public function testCourseReturnsAssociatedCourse(): void
    {
        // Create test section
        $section = new Section(['id' => 456, 'name' => 'Section A']);

        // Test the method
        $course = $section->course();

        // Assertions
        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals(123, $course->id);
        $this->assertSame($this->mockCourse, $course);
    }

    public function testEnrollmentsReturnsArrayOfEnrollmentObjects(): void
    {
        // Create test section
        $section = new Section(['id' => 456, 'name' => 'Section A']);

        // Mock response data
        $enrollmentsData = [
            [
                'id' => 1,
                'user_id' => 100,
                'section_id' => 456,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
            [
                'id' => 2,
                'user_id' => 101,
                'section_id' => 456,
                'type' => 'TeacherEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($enrollmentsData));

        $this->mockStream->method('__toString')
            ->willReturn(json_encode($enrollmentsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('sections/456/enrollments', ['query' => []])
            ->willReturn($this->mockResponse);

        // Test the method
        $enrollments = $section->enrollments();

        // Assertions
        $this->assertIsArray($enrollments);
        $this->assertCount(2, $enrollments);
        $this->assertInstanceOf(Enrollment::class, $enrollments[0]);
        $this->assertEquals(1, $enrollments[0]->id);
        $this->assertEquals('StudentEnrollment', $enrollments[0]->type);
    }

    public function testEnrollmentsWithParametersPassesQueryParams(): void
    {
        // Create test section
        $section = new Section(['id' => 456]);

        // Mock response data
        $enrollmentsData = [
            ['id' => 1, 'user_id' => 100, 'section_id' => 456],
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($enrollmentsData));

        $this->mockStream->method('__toString')
            ->willReturn(json_encode($enrollmentsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $params = ['state' => ['active'], 'type' => ['StudentEnrollment']];

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('sections/456/enrollments', ['query' => $params])
            ->willReturn($this->mockResponse);

        // Test the method
        $enrollments = $section->enrollments($params);

        // Assertions
        $this->assertIsArray($enrollments);
        $this->assertCount(1, $enrollments);
    }

    public function testStudentsReturnsArrayOfEnrollmentObjects(): void
    {
        // Create test section
        $section = new Section(['id' => 456]);

        // Mock enrollments response with student enrollments
        $enrollmentsData = [
            [
                'id' => 1,
                'user_id' => 100,
                'section_id' => 456,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
            [
                'id' => 2,
                'user_id' => 101,
                'section_id' => 456,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($enrollmentsData));

        $this->mockStream->method('__toString')
            ->willReturn(json_encode($enrollmentsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('sections/456/enrollments', [
                'query' => [
                    'type[]' => ['StudentEnrollment'],
                ],
            ])
            ->willReturn($this->mockResponse);

        // Test the method
        $students = $section->students();

        // Assertions
        $this->assertIsArray($students);
        $this->assertCount(2, $students);
        $this->assertInstanceOf(Enrollment::class, $students[0]);
        $this->assertEquals(1, $students[0]->id);
        $this->assertEquals('StudentEnrollment', $students[0]->type);
    }

    public function testStudentsReturnsEmptyArrayWhenNoStudents(): void
    {
        // Create test section
        $section = new Section(['id' => 456]);

        // Mock empty enrollments response
        $this->mockStream->method('getContents')
            ->willReturn(json_encode([]));

        $this->mockStream->method('__toString')
            ->willReturn(json_encode([]));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->willReturn($this->mockResponse);

        // Test the method
        $students = $section->students();

        // Assertions
        $this->assertIsArray($students);
        $this->assertEmpty($students);
    }

    public function testEnrollmentsThrowsExceptionWhenSectionIdMissing(): void
    {
        // Create section without ID
        $section = new Section(['name' => 'Test Section']);

        // Test enrollments
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Section ID is required to fetch enrollments');
        $section->enrollments();
    }

    public function testStudentsThrowsExceptionWhenSectionIdMissing(): void
    {
        // Create section without ID
        $section = new Section(['name' => 'Test Section']);

        // Test students
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Section ID is required to fetch enrollments');
        $section->students();
    }
}
