<?php

declare(strict_types=1);

namespace Tests\Api\Courses;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Enrollments\Enrollment;
use CanvasLMS\Config;
use CanvasLMS\Dto\Courses\CreateCourseDTO;
use CanvasLMS\Dto\Courses\UpdateCourseDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Http\HttpClient;
use CanvasLMS\Pagination\PaginatedResponse;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class CourseTest extends TestCase
{
    /**
     * @var Course
     */
    private $course;

    /**
     * @var mixed
     */
    private $httpClientMock;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        // Set up test configuration
        Config::setAccountId(1);

        $this->httpClientMock = $this->createMock(HttpClient::class);
        Course::setApiClient($this->httpClientMock);
        Enrollment::setApiClient($this->httpClientMock);

        // Set up a test course for enrollments if needed
        $testCourse = new Course(['id' => 123, 'name' => 'Test Course']);
        Course::setApiClient($this->httpClientMock);
        $this->course = new Course([]);
    }

    /**
     * Course data provider
     *
     * @return array
     */
    public static function courseDataProvider(): array
    {
        return [
            [
                [
                    'name' => 'Test Course',
                    'courseCode' => 'TC101',
                ],
                [
                    'id' => 1,
                    'name' => 'Test Course',
                    'courseCode' => 'TC101',
                ],
            ],
        ];
    }

    /**
     * Test the create course method
     *
     * @dataProvider courseDataProvider
     *
     * @param array $courseData
     * @param array $expectedResult
     *
     * @return void
     */
    public function testCreateCourse(array $courseData, array $expectedResult): void
    {
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->method('post')
            ->willReturn($response);

        $course = Course::create($courseData);

        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals('Test Course', $course->getName());
    }

    /**
     * Test the create course method with DTO
     *
     * @dataProvider courseDataProvider
     *
     * @param array $courseData
     * @param array $expectedResult
     *
     * @return void
     */
    public function testCreateCourseWithDto(array $courseData, array $expectedResult): void
    {
        $courseData = new CreateCourseDTO($courseData);
        $expectedPayload = $courseData->toApiArray();

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/accounts/1/courses'),
                $this->callback(function ($subject) use ($expectedPayload) {
                    return $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);

        $course = Course::create($courseData);

        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals('Test Course', $course->getName());
    }

    /**
     * Test that Course::create uses the configured account ID
     *
     * @return void
     */
    public function testCreateCourseUsesConfiguredAccountId(): void
    {
        // Set a custom account ID
        Config::setAccountId(456);

        $courseData = [
            'name' => 'Test Course',
            'course_code' => 'TC101',
        ];

        $dto = new CreateCourseDTO($courseData);
        $expectedPayload = $dto->toApiArray();
        $expectedResult = array_merge($courseData, ['id' => 1]);

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/accounts/456/courses'),
                $this->callback(function ($subject) use ($expectedPayload) {
                    return $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);

        $course = Course::create($courseData);

        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals('Test Course', $course->getName());

        // Reset to default for other tests
        Config::setAccountId(1);
    }

    /**
     * Test that Course::create uses default account ID when none is configured
     *
     * @return void
     */
    public function testCreateCourseUsesDefaultAccountId(): void
    {
        // Reset config to ensure we're using defaults
        Config::resetContext(Config::getContext());

        $courseData = [
            'name' => 'Test Course',
            'course_code' => 'TC101',
        ];

        $dto = new CreateCourseDTO($courseData);
        $expectedPayload = $dto->toApiArray();
        $expectedResult = array_merge($courseData, ['id' => 1]);

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/accounts/1/courses'), // Default account ID is 1
                $this->callback(function ($subject) use ($expectedPayload) {
                    return $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);

        // Suppress the warning about using default account ID
        @$course = Course::create($courseData);

        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals('Test Course', $course->getName());

        // Restore configured account ID for other tests
        Config::setAccountId(1);
    }

    /**
     * Test the find course method
     *
     * @return void
     */
    public function testFindCourse(): void
    {
        $response = new Response(200, [], json_encode(['id' => 123, 'name' => 'Found Course']));

        $this->httpClientMock
            ->method('get')
            ->willReturn($response);

        $course = Course::find(123);

        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals(123, $course->getId());
    }

    /**
     * Test the update course method
     *
     * @return void
     */
    public function testUpdateCourse(): void
    {
        $courseData = [
            'name' => 'Updated Course',
        ];

        $response = new Response(200, [], json_encode(['id' => 1, 'name' => 'Updated Course']));

        $this->httpClientMock
            ->method('put')
            ->willReturn($response);

        $course = Course::update(1, $courseData);

        $this->assertEquals('Updated Course', $course->getName());
    }

    /**
     * Test the update course method with DTO
     *
     * @return void
     */
    public function testUpdateCourseWithDto(): void
    {
        $courseData = new UpdateCourseDTO(['name' => 'Updated Course']);

        $response = new Response(200, [], json_encode(['id' => 1, 'name' => 'Updated Course']));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($response);

        $course = Course::update(1, $courseData);

        $this->assertEquals('Updated Course', $course->getName());
    }

    /**
     * Test the save course method
     *
     * @return void
     */
    public function testSaveCourse(): void
    {
        $this->course->setId(1);
        $this->course->setName('Test Course');

        $responseBody = json_encode(['id' => 1, 'name' => 'Test Course']);
        $response = new Response(200, [], $responseBody);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('PUT'),
                $this->stringContains("/courses/{$this->course->getId()}"),
                $this->callback(function ($options) {
                    return true;
                })
            )
            ->willReturn($response);

        $result = $this->course->save();

        $this->assertInstanceOf(Course::class, $result, 'The save method should return Course instance on successful save.');
        $this->assertEquals('Test Course', $this->course->getName(), 'The course name should be updated after saving.');
    }

    /**
     * Test the save course method
     *
     * @return void
     */
    public function testSaveCourseShouldThrowExceptionWhenApiFails(): void
    {
        $this->course->setId(1);
        $this->course->setName('Test Course');

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->will($this->throwException(new CanvasApiException('API Error')));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('API Error');
        $this->course->save();
    }

    /**
     * Test the delete course method
     *
     * @return void
     */
    public function testDeleteCourse(): void
    {
        $response = new Response(200, [], json_encode(['id' => 123, 'name' => 'Found Course']));

        $this->httpClientMock
            ->method('get')
            ->willReturn($response);

        $course = Course::find(123);

        $response = new Response(200, [], json_encode(['deleted' => true]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->willReturn($response);

        $this->assertInstanceOf(Course::class, $course->delete());
    }

    /**
     * Test the conclude course method
     *
     * @return void
     */
    public function testConcludeCourse(): void
    {
        $response = new Response(200, [], json_encode(['id' => 123, 'name' => 'Found Course']));

        $this->httpClientMock
            ->method('get')
            ->willReturn($response);

        $course = Course::find(123);

        $response = new Response(200, [], json_encode(['conclude' => true]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->willReturn($response);

        $this->assertInstanceOf(Course::class, $course->conclude());
    }

    /**
     * Test the reset course method
     *
     * @return void
     */
    public function testResetCourse(): void
    {
        $response = new Response(200, [], json_encode(['id' => 123, 'name' => 'Found Course']));

        $this->httpClientMock
            ->method('get')
            ->willReturn($response);

        $course = Course::find(123);

        $response = new Response(200, [], json_encode(['id' => 123, 'name' => 'Reset Course']));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $resetCourse = $course->reset();

        $this->assertInstanceOf(Course::class, $resetCourse);
        $this->assertEquals('Reset Course', $resetCourse->getName());
    }

    // Enrollment Relationship Tests

    /**
     * Test getting enrollments for a course
     */
    public function testGetEnrollments(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
            [
                'id' => 2,
                'user_id' => 102,
                'course_id' => 123,
                'type' => 'TeacherEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $enrollments = $course->enrollments();

        $this->assertCount(2, $enrollments);
        $this->assertInstanceOf(Enrollment::class, $enrollments[0]);
        $this->assertInstanceOf(Enrollment::class, $enrollments[1]);
        $this->assertEquals(101, $enrollments[0]->getUserId());
        $this->assertEquals(102, $enrollments[1]->getUserId());
    }

    /**
     * Test getting active enrollments for a course
     */
    public function testGetActiveEnrollments(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['state[]' => ['active']]])
            ->willReturn($mockPaginatedResponse);

        $enrollments = $course->getActiveEnrollments();

        $this->assertCount(1, $enrollments);
        $this->assertEquals('active', $enrollments[0]->getEnrollmentState());
    }

    /**
     * Test getting student enrollments for a course
     */
    public function testGetStudentEnrollments(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['StudentEnrollment']]])
            ->willReturn($mockPaginatedResponse);

        $enrollments = $course->getStudentEnrollments();

        $this->assertCount(1, $enrollments);
        $this->assertEquals('StudentEnrollment', $enrollments[0]->getType());
    }

    /**
     * Test getting teacher enrollments for a course
     */
    public function testGetTeacherEnrollments(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'TeacherEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['TeacherEnrollment']]])
            ->willReturn($mockPaginatedResponse);

        $enrollments = $course->getTeacherEnrollments();

        $this->assertCount(1, $enrollments);
        $this->assertEquals('TeacherEnrollment', $enrollments[0]->getType());
    }

    /**
     * Test getting TA enrollments for a course
     */
    public function testGetTaEnrollments(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'TaEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['TaEnrollment']]])
            ->willReturn($mockPaginatedResponse);

        $enrollments = $course->getTaEnrollments();

        $this->assertCount(1, $enrollments);
        $this->assertEquals('TaEnrollment', $enrollments[0]->getType());
    }

    /**
     * Test getting observer enrollments for a course
     */
    public function testGetObserverEnrollments(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'ObserverEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['ObserverEnrollment']]])
            ->willReturn($mockPaginatedResponse);

        $enrollments = $course->getObserverEnrollments();

        $this->assertCount(1, $enrollments);
        $this->assertEquals('ObserverEnrollment', $enrollments[0]->getType());
    }

    /**
     * Test getting designer enrollments for a course
     */
    public function testGetDesignerEnrollments(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'DesignerEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['DesignerEnrollment']]])
            ->willReturn($mockPaginatedResponse);

        $enrollments = $course->getDesignerEnrollments();

        $this->assertCount(1, $enrollments);
        $this->assertEquals('DesignerEnrollment', $enrollments[0]->getType());
    }

    /**
     * Test checking if a user is enrolled in course
     */
    public function testHasUserEnrolled(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['user_id' => 101]])
            ->willReturn($mockPaginatedResponse);

        $hasUser = $course->hasUserEnrolled(101);

        $this->assertTrue($hasUser);
    }

    /**
     * Test checking if a user is enrolled in course with specific type
     */
    public function testHasUserEnrolledWithType(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['user_id' => 101, 'type[]' => ['StudentEnrollment']]])
            ->willReturn($mockPaginatedResponse);

        $hasStudent = $course->hasUserEnrolled(101, 'StudentEnrollment');

        $this->assertTrue($hasStudent);
    }

    /**
     * Test checking if a user is not enrolled in course
     */
    public function testHasUserEnrolledReturnsFalse(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn([]); // Empty array means no enrollments
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['user_id' => 101]])
            ->willReturn($mockPaginatedResponse);

        $hasUser = $course->hasUserEnrolled(101);

        $this->assertFalse($hasUser);
    }

    /**
     * Test checking if a student is enrolled in course
     */
    public function testHasStudentEnrolled(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['user_id' => 101, 'type[]' => ['StudentEnrollment']]])
            ->willReturn($mockPaginatedResponse);

        $hasStudent = $course->hasStudentEnrolled(101);

        $this->assertTrue($hasStudent);
    }

    /**
     * Test checking if a teacher is enrolled in course
     */
    public function testHasTeacherEnrolled(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'TeacherEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['user_id' => 101, 'type[]' => ['TeacherEnrollment']]])
            ->willReturn($mockPaginatedResponse);

        $hasTeacher = $course->hasTeacherEnrolled(101);

        $this->assertTrue($hasTeacher);
    }

    /**
     * Test getting student count
     */
    public function testGetStudentCount(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
            [
                'id' => 2,
                'user_id' => 102,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['StudentEnrollment']]])
            ->willReturn($mockPaginatedResponse);

        $count = $course->getStudentCount();

        $this->assertEquals(2, $count);
    }

    /**
     * Test getting teacher count
     */
    public function testGetTeacherCount(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'TeacherEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['TeacherEnrollment']]])
            ->willReturn($mockPaginatedResponse);

        $count = $course->getTeacherCount();

        $this->assertEquals(1, $count);
    }

    /**
     * Test getting total enrollment count
     */
    public function testGetTotalEnrollmentCount(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
            [
                'id' => 2,
                'user_id' => 102,
                'course_id' => 123,
                'type' => 'TeacherEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $count = $course->getTotalEnrollmentCount();

        $this->assertEquals(2, $count);
    }

    /**
     * Test getting enrollments data (embedded data)
     */
    public function testGetEnrollmentsData(): void
    {
        $enrollmentsData = [
            ['id' => 1, 'user_id' => 101, 'type' => 'StudentEnrollment'],
            ['id' => 2, 'user_id' => 102, 'type' => 'TeacherEnrollment'],
        ];

        $courseData = ['id' => 123, 'name' => 'Test Course', 'enrollments' => $enrollmentsData];
        $course = new Course($courseData);

        $data = $course->getEnrollmentsData();

        $this->assertEquals($enrollmentsData, $data);
    }

    /**
     * Test getting enrollments throws exception when course ID not set
     */
    public function testGetEnrollmentsThrowsExceptionWhenCourseIdNotSet(): void
    {
        $course = new Course([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course ID is required to fetch enrollments');

        $course->enrollments();
    }

    // Relationship Method Tests

    /**
     * Test method alias for enrollments with parameters
     */
    public function testEnrollmentsMethodAlias(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active',
            ],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn($enrollmentData);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['StudentEnrollment']]])
            ->willReturn($mockPaginatedResponse);

        $enrollments = $course->enrollments(['type[]' => ['StudentEnrollment']]); // Method access with params

        $this->assertCount(1, $enrollments);
        $this->assertInstanceOf(Enrollment::class, $enrollments[0]);
    }

    // New API Methods Tests

    /**
     * Test create file upload
     */
    public function testCreateFile(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $fileParams = [
            'name' => 'test.pdf',
            'size' => 1024,
            'content_type' => 'application/pdf',
        ];

        $responseData = [
            'upload_url' => 'https://canvas.example.com/upload',
            'upload_params' => [
                'key' => 'test-key',
                'policy' => 'test-policy',
            ],
        ];

        $response = new Response(200, [], json_encode($responseData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('/courses/123/files', ['form_params' => $fileParams])
            ->willReturn($response);

        $result = $course->createFile($fileParams);

        $this->assertEquals($responseData, $result);
    }

    /**
     * Test create file throws exception when course ID not set
     */
    public function testCreateFileThrowsExceptionWhenCourseIdNotSet(): void
    {
        $course = new Course([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course ID is required to create file');

        $course->createFile(['name' => 'test.pdf']);
    }

    /**
     * Test dismiss quiz migration alert
     */
    public function testDismissQuizMigrationAlert(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $responseData = ['success' => 'true'];
        $response = new Response(200, [], json_encode($responseData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('/courses/123/dismiss_migration_limitation_message')
            ->willReturn($response);

        $result = $course->dismissQuizMigrationAlert();

        $this->assertEquals($responseData, $result);
    }

    /**
     * Test dismiss quiz migration alert throws exception when course ID not set
     */
    public function testDismissQuizMigrationAlertThrowsExceptionWhenCourseIdNotSet(): void
    {
        $course = new Course([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course ID is required to dismiss quiz migration alert');

        $course->dismissQuizMigrationAlert();
    }

    /**
     * Test get course copy status
     */
    public function testGetCourseCopyStatus(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $copyId = 456;
        $responseData = [
            'id' => 456,
            'progress' => 100,
            'workflow_state' => 'completed',
            'created_at' => '2023-01-01T00:00:00Z',
            'status_url' => '/api/v1/courses/123/course_copy/456',
        ];

        $response = new Response(200, [], json_encode($responseData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/courses/123/course_copy/456')
            ->willReturn($response);

        $result = $course->getCourseCopyStatus($copyId);

        $this->assertEquals($responseData, $result);
    }

    /**
     * Test get course copy status throws exception when course ID not set
     */
    public function testGetCourseCopyStatusThrowsExceptionWhenCourseIdNotSet(): void
    {
        $course = new Course([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course ID is required to get course copy status');

        $course->getCourseCopyStatus(456);
    }

    /**
     * Test copy course content
     */
    public function testCopyCourseContent(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $sourceCourse = '789';
        $options = ['except' => ['assignments', 'quizzes']];
        $expectedParams = [
            'source_course' => '789',
            'except' => ['assignments', 'quizzes'],
        ];

        $responseData = [
            'id' => 101,
            'progress' => null,
            'workflow_state' => 'created',
            'created_at' => '2023-01-01T00:00:00Z',
        ];

        $response = new Response(200, [], json_encode($responseData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('/courses/123/course_copy', ['form_params' => $expectedParams])
            ->willReturn($response);

        $result = $course->copyCourseContent($sourceCourse, $options);

        $this->assertEquals($responseData, $result);
    }

    /**
     * Test copy course content without options
     */
    public function testCopyCourseContentWithoutOptions(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $sourceCourse = '789';
        $expectedParams = ['source_course' => '789'];

        $responseData = [
            'id' => 102,
            'progress' => null,
            'workflow_state' => 'created',
        ];

        $response = new Response(200, [], json_encode($responseData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('/courses/123/course_copy', ['form_params' => $expectedParams])
            ->willReturn($response);

        $result = $course->copyCourseContent($sourceCourse);

        $this->assertEquals($responseData, $result);
    }

    /**
     * Test copy course content throws exception when course ID not set
     */
    public function testCopyCourseContentThrowsExceptionWhenCourseIdNotSet(): void
    {
        $course = new Course([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course ID is required to copy course content');

        $course->copyCourseContent('789');
    }

    /**
     * Test date properties are hydrated as DateTime objects from constructor
     */
    public function testDatePropertiesHydratedFromConstructor(): void
    {
        $courseData = [
            'id' => 123,
            'name' => 'Test Course',
            'created_at' => '2024-01-15T10:30:00Z',
            'start_at' => '2024-02-01T08:00:00Z',
            'end_at' => '2024-05-31T17:00:00Z',
        ];

        $course = new Course($courseData);

        $this->assertInstanceOf(\DateTime::class, $course->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $course->getStartAt());
        $this->assertInstanceOf(\DateTime::class, $course->getEndAt());

        $this->assertEquals('2024-01-15', $course->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals('2024-02-01', $course->getStartAt()->format('Y-m-d'));
        $this->assertEquals('2024-05-31', $course->getEndAt()->format('Y-m-d'));
    }

    /**
     * Test date properties handle null values
     */
    public function testDatePropertiesHandleNullValues(): void
    {
        $courseData = [
            'id' => 123,
            'name' => 'Test Course',
            'created_at' => '2024-01-15T10:30:00Z',
            // start_at and end_at are omitted
        ];

        $course = new Course($courseData);

        $this->assertInstanceOf(\DateTime::class, $course->getCreatedAt());
        $this->assertNull($course->getStartAt());
        $this->assertNull($course->getEndAt());
    }

    /**
     * Test find() method correctly assigns DateTime objects to date properties (calls populate internally)
     */
    public function testFindMethodAssignsDateTimeObjects(): void
    {
        $responseData = [
            'id' => 123,
            'name' => 'Test Course',
            'created_at' => '2024-03-20T12:00:00Z',
            'start_at' => '2024-04-01T09:00:00Z',
            'end_at' => '2024-06-30T18:00:00Z',
        ];

        $response = new Response(200, [], json_encode($responseData));

        $this->httpClientMock
            ->method('get')
            ->willReturn($response);

        $course = Course::find(123);

        // After find (which calls populate), dates should be DateTime objects
        $this->assertInstanceOf(\DateTime::class, $course->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $course->getStartAt());
        $this->assertInstanceOf(\DateTime::class, $course->getEndAt());

        $this->assertEquals('2024-03-20', $course->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals('2024-04-01', $course->getStartAt()->format('Y-m-d'));
        $this->assertEquals('2024-06-30', $course->getEndAt()->format('Y-m-d'));
    }

    /**
     * Test save() method works with DateTime properties (triggers populate internally)
     */
    public function testSaveMethodWorksWithDateTimeProperties(): void
    {
        $course = new Course([
            'id' => 1,
            'name' => 'Test Course',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $course->setName('Updated Course Name');

        $responseData = [
            'id' => 1,
            'name' => 'Updated Course Name',
            'created_at' => '2024-01-01T00:00:00Z',
            'start_at' => '2024-02-01T08:00:00Z',
            'end_at' => '2024-05-31T17:00:00Z',
        ];

        $response = new Response(200, [], json_encode($responseData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $course->save();

        // Verify that populate() worked correctly and assigned DateTime objects
        $this->assertInstanceOf(Course::class, $result);
        $this->assertInstanceOf(\DateTime::class, $course->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $course->getStartAt());
        $this->assertInstanceOf(\DateTime::class, $course->getEndAt());
    }

    /**
     * Test DTO serialization converts DateTime back to ISO-8601 strings
     */
    public function testDtoSerializationWithDateTimeProperties(): void
    {
        $startDate = new \DateTime('2024-02-01T08:00:00Z');
        $endDate = new \DateTime('2024-05-31T17:00:00Z');

        $dto = new CreateCourseDTO([
            'name' => 'Test Course',
            'course_code' => 'TC101',
        ]);

        $dto->setStartAt($startDate);
        $dto->setEndAt($endDate);

        $apiArray = $dto->toApiArray();

        // Find the start_at and end_at in the multipart array
        $startAtFound = false;
        $endAtFound = false;

        foreach ($apiArray as $field) {
            if ($field['name'] === 'course[start_at]') {
                $startAtFound = true;
                // Verify it's an ISO-8601 string, not a DateTime object
                $this->assertIsString($field['contents']);
                $this->assertStringContainsString('2024-02-01', $field['contents']);
            }
            if ($field['name'] === 'course[end_at]') {
                $endAtFound = true;
                $this->assertIsString($field['contents']);
                $this->assertStringContainsString('2024-05-31', $field['contents']);
            }
        }

        $this->assertTrue($startAtFound, 'start_at should be in API array');
        $this->assertTrue($endAtFound, 'end_at should be in API array');
    }

    /**
     * Test updating course with DateTime properties
     */
    public function testUpdateCourseWithDateTimeProperties(): void
    {
        $startDate = new \DateTime('2024-09-01T08:00:00Z');
        $endDate = new \DateTime('2024-12-15T17:00:00Z');

        $dto = new UpdateCourseDTO([
            'name' => 'Updated Course',
        ]);
        $dto->setStartAt($startDate);
        $dto->setEndAt($endDate);

        $responseData = [
            'id' => 1,
            'name' => 'Updated Course',
            'created_at' => '2024-01-01T00:00:00Z',
            'start_at' => '2024-09-01T08:00:00Z',
            'end_at' => '2024-12-15T17:00:00Z',
        ];

        $response = new Response(200, [], json_encode($responseData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($response);

        $course = Course::update(1, $dto);

        // Verify DateTime objects are properly hydrated
        $this->assertInstanceOf(\DateTime::class, $course->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $course->getStartAt());
        $this->assertInstanceOf(\DateTime::class, $course->getEndAt());

        $this->assertEquals('2024-09-01', $course->getStartAt()->format('Y-m-d'));
        $this->assertEquals('2024-12-15', $course->getEndAt()->format('Y-m-d'));
    }

    protected function tearDown(): void
    {
        $this->course = null;
        $this->httpClientMock = null;
    }
}
