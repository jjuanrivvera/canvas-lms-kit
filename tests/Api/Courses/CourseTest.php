<?php

namespace Tests\Api\Courses;

use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Enrollments\Enrollment;
use CanvasLMS\Dto\Courses\CreateCourseDTO;
use CanvasLMS\Dto\Courses\UpdateCourseDTO;
use CanvasLMS\Exceptions\CanvasApiException;

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
                ]
            ],
        ];
    }

    /**
     * Test the create course method
     * @dataProvider courseDataProvider
     * @param array $courseData
     * @param array $expectedResult
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
     * @dataProvider courseDataProvider
     * @param array $courseData
     * @param array $expectedResult
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
     * Test the find course method
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

        $this->assertTrue($result, 'The save method should return true on successful save.');
        $this->assertEquals('Test Course', $this->course->getName(), 'The course name should be updated after saving.');
    }

    /**
     * Test the save course method
     * @return void
     */
    public function testSaveCourseShouldReturnFalseWhenApiThrowsException(): void
    {
        $this->course->setId(1);
        $this->course->setName('Test Course');

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->will($this->throwException(new CanvasApiException()));

        $this->assertFalse($this->course->save());
    }

    /**
     * Test the delete course method
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

        $this->assertTrue($course->delete());
    }


    /**
     * Test the conclude course method
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

        $this->assertTrue($course->conclude());
    }


    /**
     * Test the reset course method
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
     * Test getting enrollments as objects for a course
     */
    public function testGetEnrollmentsAsObjects(): void
    {
        $courseData = ['id' => 123, 'name' => 'Test Course'];
        $course = new Course($courseData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 101,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active'
            ],
            [
                'id' => 2,
                'user_id' => 102,
                'course_id' => 123,
                'type' => 'TeacherEnrollment',
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => []])
            ->willReturn($response);

        $enrollments = $course->getEnrollmentsAsObjects();

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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['state[]' => ['active']]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['StudentEnrollment']]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['TeacherEnrollment']]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['TaEnrollment']]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['ObserverEnrollment']]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['DesignerEnrollment']]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['user_id' => 101]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['user_id' => 101, 'type[]' => ['StudentEnrollment']]])
            ->willReturn($response);

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

        $response = new Response(200, [], json_encode([]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['user_id' => 101]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['user_id' => 101, 'type[]' => ['StudentEnrollment']]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['user_id' => 101, 'type[]' => ['TeacherEnrollment']]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ],
            [
                'id' => 2,
                'user_id' => 102,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['StudentEnrollment']]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['TeacherEnrollment']]])
            ->willReturn($response);

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
                'enrollment_state' => 'active'
            ],
            [
                'id' => 2,
                'user_id' => 102,
                'course_id' => 123,
                'type' => 'TeacherEnrollment',
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => []])
            ->willReturn($response);

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
            ['id' => 2, 'user_id' => 102, 'type' => 'TeacherEnrollment']
        ];

        $courseData = ['id' => 123, 'name' => 'Test Course', 'enrollments' => $enrollmentsData];
        $course = new Course($courseData);

        $data = $course->getEnrollmentsData();

        $this->assertEquals($enrollmentsData, $data);
    }

    /**
     * Test getting enrollments as objects throws exception when course ID not set
     */
    public function testGetEnrollmentsAsObjectsThrowsExceptionWhenCourseIdNotSet(): void
    {
        $course = new Course([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course ID is required to fetch enrollments');

        $course->getEnrollmentsAsObjects();
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
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => ['type[]' => ['StudentEnrollment']]])
            ->willReturn($response);

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
            'content_type' => 'application/pdf'
        ];

        $responseData = [
            'upload_url' => 'https://canvas.example.com/upload',
            'upload_params' => [
                'key' => 'test-key',
                'policy' => 'test-policy'
            ]
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
            'status_url' => '/api/v1/courses/123/course_copy/456'
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
            'except' => ['assignments', 'quizzes']
        ];

        $responseData = [
            'id' => 101,
            'progress' => null,
            'workflow_state' => 'created',
            'created_at' => '2023-01-01T00:00:00Z'
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
            'workflow_state' => 'created'
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

    protected function tearDown(): void
    {
        $this->course = null;
        $this->httpClientMock = null;
    }
}
