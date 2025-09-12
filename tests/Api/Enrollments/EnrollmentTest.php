<?php

declare(strict_types=1);

namespace Tests\Api\Enrollments;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Enrollments\Enrollment;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Dto\Enrollments\CreateEnrollmentDTO;
use CanvasLMS\Dto\Enrollments\UpdateEnrollmentDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class EnrollmentTest extends TestCase
{
    private HttpClientInterface $httpClientMock;
    private Course $course;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->course = new Course(['id' => 123]);

        Enrollment::setApiClient($this->httpClientMock);
        Enrollment::setCourse($this->course);
        User::setApiClient($this->httpClientMock);
    }

    public function testConstructorPopulatesProperties(): void
    {
        $data = [
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'active',
            'section_id' => 456,
            'role_id' => 789,
            'limit_privileges_to_course_section' => false,
            'created_at' => '2023-01-01T00:00:00Z',
            'updated_at' => '2023-01-02T00:00:00Z',
            'role' => 'StudentEnrollment',
            'current_score' => 85.5,
            'current_grade' => 'B',
            'final_score' => 88.0,
            'final_grade' => 'B+',
            'uuid' => 'test-uuid-123',
            'current_points' => 855.0,
            'unposted_current_points' => 850.0,
            'total_activity_time' => 3600,
            'last_activity_at' => '2023-01-03T00:00:00Z',
            'start_at' => '2023-01-01T00:00:00Z',
            'end_at' => '2023-06-01T00:00:00Z',
            'can_be_removed' => true,
            'locked' => false,
            'sis_account_id' => 'account123',
            'sis_course_id' => 'course123',
            'sis_section_id' => 'section123',
            'sis_user_id' => 'user123',
            'enrollment_term_id' => 1,
            'grading_period_id' => 2
        ];

        $enrollment = new Enrollment($data);

        $this->assertEquals(1, $enrollment->getId());
        $this->assertEquals(100, $enrollment->getUserId());
        $this->assertEquals(123, $enrollment->getCourseId());
        $this->assertEquals('StudentEnrollment', $enrollment->getType());
        $this->assertEquals('active', $enrollment->getEnrollmentState());
        $this->assertEquals(456, $enrollment->getSectionId());
        $this->assertEquals(789, $enrollment->getRoleId());
        $this->assertFalse($enrollment->isLimitPrivilegesToCourseSection());
        $this->assertEquals('2023-01-01T00:00:00Z', $enrollment->getCreatedAt());
        $this->assertEquals('2023-01-02T00:00:00Z', $enrollment->getUpdatedAt());
        $this->assertEquals('StudentEnrollment', $enrollment->getRole());
        $this->assertEquals(85.5, $enrollment->getCurrentScore());
        $this->assertEquals('B', $enrollment->getCurrentGrade());
        $this->assertEquals(88.0, $enrollment->getFinalScore());
        $this->assertEquals('B+', $enrollment->getFinalGrade());
        $this->assertEquals('test-uuid-123', $enrollment->getUuid());
        $this->assertEquals(855.0, $enrollment->getCurrentPoints());
        $this->assertEquals(850.0, $enrollment->getUnpostedCurrentPoints());
        $this->assertEquals(3600, $enrollment->getTotalActivityTime());
        $this->assertEquals('2023-01-03T00:00:00Z', $enrollment->getLastActivityAt());
        $this->assertEquals('2023-01-01T00:00:00Z', $enrollment->getStartAt());
        $this->assertEquals('2023-06-01T00:00:00Z', $enrollment->getEndAt());
        $this->assertTrue($enrollment->canBeRemoved());
        $this->assertFalse($enrollment->isLocked());
        $this->assertEquals('account123', $enrollment->getSisAccountId());
        $this->assertEquals('course123', $enrollment->getSisCourseId());
        $this->assertEquals('section123', $enrollment->getSisSectionId());
        $this->assertEquals('user123', $enrollment->getSisUserId());
        $this->assertEquals(1, $enrollment->getEnrollmentTermId());
        $this->assertEquals(2, $enrollment->getGradingPeriodId());
    }

    public function testSetCourse(): void
    {
        $course = new Course(['id' => 456]);
        Enrollment::setCourse($course);
        $this->assertTrue(Enrollment::checkCourse());
    }

    public function testCheckCourseThrowsExceptionWhenCourseNotSet(): void
    {
        // Create a new enrollment instance to avoid static property issues
        $reflection = new \ReflectionClass(Enrollment::class);
        $courseProperty = $reflection->getProperty('course');
        $courseProperty->setAccessible(true);
        
        // Create a course with no ID to trigger the exception
        $courseWithoutId = new Course([]);
        $courseProperty->setValue(null, $courseWithoutId);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course is required for enrollment operations');
        
        Enrollment::checkCourse();
    }

    public function testFind(): void
    {
        $responseBody = json_encode([
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'active'
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments/1')
            ->willReturn($response);

        $enrollment = Enrollment::find(1);

        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertEquals(1, $enrollment->getId());
        $this->assertEquals(100, $enrollment->getUserId());
        $this->assertEquals('StudentEnrollment', $enrollment->getType());
    }

    public function testGet(): void
    {
        $responseBody = json_encode([
            [
                'id' => 1,
                'user_id' => 100,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active'
            ],
            [
                'id' => 2,
                'user_id' => 200,
                'course_id' => 123,
                'type' => 'TeacherEnrollment',
                'enrollment_state' => 'active'
            ]
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => []])
            ->willReturn($response);

        $enrollments = Enrollment::get();

        $this->assertIsArray($enrollments);
        $this->assertCount(2, $enrollments);
        $this->assertInstanceOf(Enrollment::class, $enrollments[0]);
        $this->assertInstanceOf(Enrollment::class, $enrollments[1]);
        $this->assertEquals('StudentEnrollment', $enrollments[0]->getType());
        $this->assertEquals('TeacherEnrollment', $enrollments[1]->getType());
    }

    public function testGetWithParameters(): void
    {
        $params = [
            'type[]' => ['StudentEnrollment'],
            'state[]' => ['active'],
            'user_id' => 100
        ];

        $responseBody = json_encode([
            [
                'id' => 1,
                'user_id' => 100,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active'
            ]
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/enrollments', ['query' => $params])
            ->willReturn($response);

        $enrollments = Enrollment::get($params);

        $this->assertIsArray($enrollments);
        $this->assertCount(1, $enrollments);
        $this->assertEquals('StudentEnrollment', $enrollments[0]->getType());
    }

    public function testCreate(): void
    {
        $createData = [
            'userId' => '100',
            'type' => 'StudentEnrollment',
            'enrollmentState' => 'active'
        ];

        $responseBody = json_encode([
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'active'
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('courses/123/enrollments', $this->anything())
            ->willReturn($response);

        $enrollment = Enrollment::create($createData);

        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertEquals(1, $enrollment->getId());
        $this->assertEquals('StudentEnrollment', $enrollment->getType());
    }

    public function testCreateWithDTO(): void
    {
        $dto = new CreateEnrollmentDTO([
            'userId' => '100',
            'type' => 'StudentEnrollment',
            'enrollmentState' => 'active'
        ]);

        $responseBody = json_encode([
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'active'
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('courses/123/enrollments', $this->anything())
            ->willReturn($response);

        $enrollment = Enrollment::create($dto);

        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertEquals(1, $enrollment->getId());
    }

    public function testUpdate(): void
    {
        $updateData = [
            'enrollmentState' => 'completed'
        ];

        $responseBody = json_encode([
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'completed'
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('courses/123/enrollments/1', $this->anything())
            ->willReturn($response);

        $enrollment = Enrollment::update(1, $updateData);

        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertEquals('completed', $enrollment->getEnrollmentState());
    }

    public function testUpdateWithDTO(): void
    {
        $dto = new UpdateEnrollmentDTO([
            'enrollmentState' => 'completed'
        ]);

        $responseBody = json_encode([
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'completed'
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('courses/123/enrollments/1', $this->anything())
            ->willReturn($response);

        $enrollment = Enrollment::update(1, $dto);

        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertEquals('completed', $enrollment->getEnrollmentState());
    }

    public function testAccept(): void
    {
        $responseBody = json_encode([
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'active'
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('courses/123/enrollments/1/accept')
            ->willReturn($response);

        $enrollment = Enrollment::accept(1);

        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertEquals('active', $enrollment->getEnrollmentState());
    }

    public function testReject(): void
    {
        $responseBody = json_encode([
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'rejected'
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('courses/123/enrollments/1/reject')
            ->willReturn($response);

        $enrollment = Enrollment::reject(1);

        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertEquals('rejected', $enrollment->getEnrollmentState());
    }

    public function testReactivate(): void
    {
        $responseBody = json_encode([
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'active'
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('courses/123/enrollments/1/reactivate')
            ->willReturn($response);

        $enrollment = Enrollment::reactivate(1);

        $this->assertInstanceOf(Enrollment::class, $enrollment);
        $this->assertEquals('active', $enrollment->getEnrollmentState());
    }

    public function testGetBySection(): void
    {
        $responseBody = json_encode([
            [
                'id' => 1,
                'user_id' => 100,
                'course_id' => 123,
                'section_id' => 456,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active'
            ]
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('sections/456/enrollments', ['query' => []])
            ->willReturn($response);

        $enrollments = Enrollment::fetchAllBySection(456);

        $this->assertIsArray($enrollments);
        $this->assertCount(1, $enrollments);
        $this->assertEquals(456, $enrollments[0]->getSectionId());
    }

    public function testGetByUser(): void
    {
        $responseBody = json_encode([
            [
                'id' => 1,
                'user_id' => 100,
                'course_id' => 123,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active'
            ]
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('users/100/enrollments', ['query' => []])
            ->willReturn($response);

        $enrollments = Enrollment::fetchAllByUser(100);

        $this->assertIsArray($enrollments);
        $this->assertCount(1, $enrollments);
        $this->assertEquals(100, $enrollments[0]->getUserId());
    }

    public function testSaveCreateNewEnrollment(): void
    {
        $enrollment = new Enrollment([
            'userId' => 100,
            'type' => 'StudentEnrollment',
            'enrollmentState' => 'active'
        ]);

        $responseBody = json_encode([
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'active'
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with('courses/123/enrollments', $this->anything())
            ->willReturn($response);

        $result = $enrollment->save();

        $this->assertInstanceOf(Enrollment::class, $result);
        $this->assertEquals(1, $enrollment->getId());
    }

    public function testSaveUpdateExistingEnrollment(): void
    {
        $enrollment = new Enrollment([
            'id' => 1,
            'userId' => 100,
            'type' => 'StudentEnrollment',
            'enrollmentState' => 'completed'
        ]);

        $responseBody = json_encode([
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'completed'
        ]);

        $response = new Response(200, [], $responseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('courses/123/enrollments/1', $this->anything())
            ->willReturn($response);

        $result = $enrollment->save();

        $this->assertInstanceOf(Enrollment::class, $result);
        $this->assertEquals('completed', $enrollment->getEnrollmentState());
    }

    public function testSaveThrowsExceptionForInvalidType(): void
    {
        $enrollment = new Enrollment([
            'userId' => 100,
            'type' => 'InvalidEnrollment'
        ]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Invalid enrollment type: InvalidEnrollment');

        $enrollment->save();
    }

    public function testSaveThrowsExceptionForInvalidState(): void
    {
        $enrollment = new Enrollment([
            'userId' => 100,
            'type' => 'StudentEnrollment',
            'enrollmentState' => 'invalid_state'
        ]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Invalid enrollment state: invalid_state');

        $enrollment->save();
    }

    public function testSaveThrowsExceptionForMissingRequiredFields(): void
    {
        $enrollment = new Enrollment([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID and enrollment type are required for new enrollments');

        $enrollment->save();
    }

    public function testDelete(): void
    {
        $enrollment = new Enrollment(['id' => 1]);

        $response = new Response(200);
        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('courses/123/enrollments/1')
            ->willReturn($response);

        $result = $enrollment->delete();

        $this->assertInstanceOf(Enrollment::class, $result);
    }

    public function testDeleteThrowsExceptionWithoutId(): void
    {
        $enrollment = new Enrollment([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Enrollment ID is required for deletion');

        $enrollment->delete();
    }

    public function testToArray(): void
    {
        $data = [
            'id' => 1,
            'user_id' => 100,
            'course_id' => 123,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'active'
        ];

        $enrollment = new Enrollment($data);
        $array = $enrollment->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals(100, $array['user_id']);
        $this->assertEquals(123, $array['course_id']);
        $this->assertEquals('StudentEnrollment', $array['type']);
        $this->assertEquals('active', $array['enrollment_state']);
    }

    public function testEnrollmentTypeConstants(): void
    {
        $this->assertEquals('StudentEnrollment', Enrollment::TYPE_STUDENT);
        $this->assertEquals('TeacherEnrollment', Enrollment::TYPE_TEACHER);
        $this->assertEquals('TaEnrollment', Enrollment::TYPE_TA);
        $this->assertEquals('ObserverEnrollment', Enrollment::TYPE_OBSERVER);
        $this->assertEquals('DesignerEnrollment', Enrollment::TYPE_DESIGNER);
    }

    public function testEnrollmentStateConstants(): void
    {
        $this->assertEquals('active', Enrollment::STATE_ACTIVE);
        $this->assertEquals('invited', Enrollment::STATE_INVITED);
        $this->assertEquals('creation_pending', Enrollment::STATE_CREATION_PENDING);
        $this->assertEquals('deleted', Enrollment::STATE_DELETED);
        $this->assertEquals('rejected', Enrollment::STATE_REJECTED);
        $this->assertEquals('completed', Enrollment::STATE_COMPLETED);
        $this->assertEquals('inactive', Enrollment::STATE_INACTIVE);
    }

    public function testSetterMethods(): void
    {
        $enrollment = new Enrollment([]);

        $enrollment->setUserId(100);
        $enrollment->setType('StudentEnrollment');
        $enrollment->setEnrollmentState('active');
        $enrollment->setSectionId(456);
        $enrollment->setRoleId(789);
        $enrollment->setLimitPrivilegesToCourseSection(true);
        $enrollment->setStartAt('2023-01-01T00:00:00Z');
        $enrollment->setEndAt('2023-06-01T00:00:00Z');
        $enrollment->setSisUserId('user123');

        $this->assertEquals(100, $enrollment->getUserId());
        $this->assertEquals('StudentEnrollment', $enrollment->getType());
        $this->assertEquals('active', $enrollment->getEnrollmentState());
        $this->assertEquals(456, $enrollment->getSectionId());
        $this->assertEquals(789, $enrollment->getRoleId());
        $this->assertTrue($enrollment->isLimitPrivilegesToCourseSection());
        $this->assertEquals('2023-01-01T00:00:00Z', $enrollment->getStartAt());
        $this->assertEquals('2023-06-01T00:00:00Z', $enrollment->getEndAt());
        $this->assertEquals('user123', $enrollment->getSisUserId());
    }

    // Relationship Method Tests

    public function testGetUserWithEmbeddedData(): void
    {
        $userData = [
            'id' => 100,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $enrollment = new Enrollment([
            'id' => 1,
            'userId' => 100,
            'user' => $userData
        ]);

        $user = $enrollment->getUser();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(100, $user->getId());
        $this->assertEquals('John Doe', $user->getName());
        $this->assertEquals('john@example.com', $user->getEmail());
    }

    public function testGetUserFromAPI(): void
    {
        $enrollment = new Enrollment([
            'id' => 1,
            'userId' => 100,
            'user' => null // No embedded data
        ]);

        $userResponseBody = json_encode([
            'id' => 100,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);

        $response = new Response(200, [], $userResponseBody);
        $this->httpClientMock
            ->expects($this->atLeastOnce())
            ->method('get')
            ->willReturn($response);

        $user = $enrollment->getUser();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(100, $user->getId());
        $this->assertEquals('Jane Smith', $user->getName());
        $this->assertEquals('jane@example.com', $user->getEmail());
    }

    public function testGetUserReturnsNullWhenNoUserId(): void
    {
        $enrollment = new Enrollment(['id' => 1, 'userId' => null]);

        $user = $enrollment->getUser();

        $this->assertNull($user);
    }

    public function testGetUserThrowsExceptionOnAPIError(): void
    {
        $enrollment = new Enrollment([
            'id' => 1,
            'userId' => 999,
            'user' => null
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/users/999')
            ->willThrowException(new \Exception('User not found'));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Could not load user with ID 999');

        $enrollment->getUser();
    }

    public function testGetCourseFromStaticContext(): void
    {
        $course = new Course(['id' => 123, 'name' => 'Test Course']);
        Enrollment::setCourse($course);

        $enrollment = new Enrollment([
            'id' => 1,
            'courseId' => 123
        ]);

        $retrievedCourse = $enrollment->getCourse();

        $this->assertInstanceOf(Course::class, $retrievedCourse);
        $this->assertEquals(123, $retrievedCourse->getId());
        $this->assertEquals('Test Course', $retrievedCourse->getName());
    }

    public function testGetCourseFromAPI(): void
    {
        // Set a different course in static context
        $differentCourse = new Course(['id' => 456, 'name' => 'Different Course']);
        Enrollment::setCourse($differentCourse);

        $enrollment = new Enrollment([
            'id' => 1,
            'courseId' => 789 // Different from static context
        ]);

        $courseResponseBody = json_encode([
            'id' => 789,
            'name' => 'API Course'
        ]);

        $response = new Response(200, [], $courseResponseBody);
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/courses/789')
            ->willReturn($response);

        $course = $enrollment->getCourse();

        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals(789, $course->getId());
        $this->assertEquals('API Course', $course->getName());
    }

    public function testGetCourseReturnsNullWhenNoCourseId(): void
    {
        $enrollment = new Enrollment(['id' => 1, 'courseId' => null]);

        $course = $enrollment->getCourse();

        $this->assertNull($course);
    }

    public function testGetCourseThrowsExceptionOnAPIError(): void
    {
        $enrollment = new Enrollment([
            'id' => 1,
            'courseId' => 999
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/courses/999')
            ->willThrowException(new \Exception('Course not found'));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Could not load course with ID 999');

        $enrollment->getCourse();
    }

    // Enrollment Type Check Tests

    public function testIsStudent(): void
    {
        $enrollment = new Enrollment(['type' => Enrollment::TYPE_STUDENT]);
        $this->assertTrue($enrollment->isStudent());
        $this->assertFalse($enrollment->isTeacher());
    }

    public function testIsTeacher(): void
    {
        $enrollment = new Enrollment(['type' => Enrollment::TYPE_TEACHER]);
        $this->assertTrue($enrollment->isTeacher());
        $this->assertFalse($enrollment->isStudent());
    }

    public function testIsTa(): void
    {
        $enrollment = new Enrollment(['type' => Enrollment::TYPE_TA]);
        $this->assertTrue($enrollment->isTa());
        $this->assertFalse($enrollment->isStudent());
    }

    public function testIsObserver(): void
    {
        $enrollment = new Enrollment(['type' => Enrollment::TYPE_OBSERVER]);
        $this->assertTrue($enrollment->isObserver());
        $this->assertFalse($enrollment->isStudent());
    }

    public function testIsDesigner(): void
    {
        $enrollment = new Enrollment(['type' => Enrollment::TYPE_DESIGNER]);
        $this->assertTrue($enrollment->isDesigner());
        $this->assertFalse($enrollment->isStudent());
    }

    // Enrollment State Check Tests

    public function testIsActive(): void
    {
        $enrollment = new Enrollment(['enrollmentState' => Enrollment::STATE_ACTIVE]);
        $this->assertTrue($enrollment->isActive());
        $this->assertFalse($enrollment->isPending());
    }

    public function testIsPending(): void
    {
        $enrollmentInvited = new Enrollment(['enrollmentState' => Enrollment::STATE_INVITED]);
        $enrollmentCreationPending = new Enrollment(['enrollmentState' => Enrollment::STATE_CREATION_PENDING]);
        
        $this->assertTrue($enrollmentInvited->isPending());
        $this->assertTrue($enrollmentCreationPending->isPending());
        $this->assertFalse($enrollmentInvited->isActive());
    }

    public function testIsCompleted(): void
    {
        $enrollment = new Enrollment(['enrollmentState' => Enrollment::STATE_COMPLETED]);
        $this->assertTrue($enrollment->isCompleted());
        $this->assertFalse($enrollment->isActive());
    }

    public function testIsInactive(): void
    {
        $enrollment = new Enrollment(['enrollmentState' => Enrollment::STATE_INACTIVE]);
        $this->assertTrue($enrollment->isInactive());
        $this->assertFalse($enrollment->isActive());
    }

    // Human-readable Name Tests

    public function testGetTypeName(): void
    {
        $this->assertEquals('Student', (new Enrollment(['type' => Enrollment::TYPE_STUDENT]))->getTypeName());
        $this->assertEquals('Teacher', (new Enrollment(['type' => Enrollment::TYPE_TEACHER]))->getTypeName());
        $this->assertEquals('Teaching Assistant', (new Enrollment(['type' => Enrollment::TYPE_TA]))->getTypeName());
        $this->assertEquals('Observer', (new Enrollment(['type' => Enrollment::TYPE_OBSERVER]))->getTypeName());
        $this->assertEquals('Designer', (new Enrollment(['type' => Enrollment::TYPE_DESIGNER]))->getTypeName());
        $this->assertEquals('CustomType', (new Enrollment(['type' => 'CustomType']))->getTypeName());
        $this->assertEquals('Unknown', (new Enrollment([]))->getTypeName());
    }

    public function testGetStateName(): void
    {
        $this->assertEquals('Active', (new Enrollment(['enrollmentState' => Enrollment::STATE_ACTIVE]))->getStateName());
        $this->assertEquals('Invited', (new Enrollment(['enrollmentState' => Enrollment::STATE_INVITED]))->getStateName());
        $this->assertEquals('Creation Pending', (new Enrollment(['enrollmentState' => Enrollment::STATE_CREATION_PENDING]))->getStateName());
        $this->assertEquals('Deleted', (new Enrollment(['enrollmentState' => Enrollment::STATE_DELETED]))->getStateName());
        $this->assertEquals('Rejected', (new Enrollment(['enrollmentState' => Enrollment::STATE_REJECTED]))->getStateName());
        $this->assertEquals('Completed', (new Enrollment(['enrollmentState' => Enrollment::STATE_COMPLETED]))->getStateName());
        $this->assertEquals('Inactive', (new Enrollment(['enrollmentState' => Enrollment::STATE_INACTIVE]))->getStateName());
        $this->assertEquals('custom_state', (new Enrollment(['enrollmentState' => 'custom_state']))->getStateName());
        $this->assertEquals('Unknown', (new Enrollment([]))->getStateName());
    }

    public function testGetUserData(): void
    {
        $userData = ['id' => 100, 'name' => 'John Doe'];
        $enrollment = new Enrollment(['user' => $userData]);

        $this->assertEquals($userData, $enrollment->getUserData());
    }

    // Relationship Method Tests

    /**
     * Test method alias for user relationship
     */
    public function testUserMethodAlias(): void
    {
        $userData = ['id' => 456, 'name' => 'John Doe'];
        $enrollmentData = [
            'id' => 123,
            'user_id' => 456,
            'course_id' => 789,
            'type' => 'StudentEnrollment',
            'user' => $userData
        ];

        $enrollment = new Enrollment($enrollmentData);
        $user = $enrollment->user(); // Method access

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(456, $user->getId());
    }

    /**
     * Test method alias for course relationship
     */
    public function testCourseMethodAlias(): void
    {
        $enrollmentData = [
            'id' => 123,
            'user_id' => 456,
            'course_id' => 789,
            'type' => 'StudentEnrollment'
        ];

        $courseData = ['id' => 789, 'name' => 'Test Course'];
        $course = new Course($courseData);
        Enrollment::setCourse($course);

        $enrollment = new Enrollment($enrollmentData);
        $retrievedCourse = $enrollment->course(); // Method access

        $this->assertInstanceOf(Course::class, $retrievedCourse);
        $this->assertEquals(789, $retrievedCourse->getId());
    }
}