<?php

namespace Tests\Api\Assignments;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Assignments\CreateAssignmentDTO;
use CanvasLMS\Dto\Assignments\UpdateAssignmentDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \CanvasLMS\Api\Assignments\Assignment
 */
class AssignmentTest extends TestCase
{
    private HttpClientInterface $httpClientMock;
    private Course $course;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->course = new Course(['id' => 123]);

        Assignment::setApiClient($this->httpClientMock);
        Assignment::setCourse($this->course);
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(Assignment::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, new Course(['id' => 0]));
    }

    public function testSetCourse(): void
    {
        $course = new Course(['id' => 456]);
        Assignment::setCourse($course);

        $this->assertTrue(Assignment::checkCourse());
    }

    public function testCheckCourseThrowsExceptionWhenCourseNotSet(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course is required');

        $reflection = new \ReflectionClass(Assignment::class);
        $property = $reflection->getProperty('course');
        $property->setAccessible(true);
        $property->setValue(null, new Course([]));

        Assignment::checkCourse();
    }

    public function testConstructor(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Assignment',
            'course_id' => 123,
            'assignment_group_id' => 456,
            'description' => 'Test description',
            'position' => 1,
            'points_possible' => 100.0,
            'grading_type' => 'points',
            'submission_types' => ['online_text_entry'],
            'due_at' => '2024-12-31T23:59:59Z',
            'published' => true,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z'
        ];

        $assignment = new Assignment($data);

        $this->assertEquals(1, $assignment->getId());
        $this->assertEquals('Test Assignment', $assignment->getName());
        $this->assertEquals(123, $assignment->getCourseId());
        $this->assertEquals(456, $assignment->getAssignmentGroupId());
        $this->assertEquals('Test description', $assignment->getDescription());
        $this->assertEquals(1, $assignment->getPosition());
        $this->assertEquals(100.0, $assignment->getPointsPossible());
        $this->assertEquals('points', $assignment->getGradingType());
        $this->assertEquals(['online_text_entry'], $assignment->getSubmissionTypes());
        $this->assertEquals('2024-12-31T23:59:59Z', $assignment->getDueAt());
        $this->assertTrue($assignment->getPublished());
        $this->assertEquals('2024-01-01T00:00:00Z', $assignment->getCreatedAt());
        $this->assertEquals('2024-01-01T00:00:00Z', $assignment->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $assignment = new Assignment();

        $assignment->setId(1);
        $this->assertEquals(1, $assignment->getId());

        $assignment->setName('Test Assignment');
        $this->assertEquals('Test Assignment', $assignment->getName());

        $assignment->setCourseId(123);
        $this->assertEquals(123, $assignment->getCourseId());

        $assignment->setAssignmentGroupId(456);
        $this->assertEquals(456, $assignment->getAssignmentGroupId());

        $assignment->setDescription('Test description');
        $this->assertEquals('Test description', $assignment->getDescription());

        $assignment->setPosition(1);
        $this->assertEquals(1, $assignment->getPosition());

        $assignment->setPointsPossible(100.0);
        $this->assertEquals(100.0, $assignment->getPointsPossible());

        $assignment->setGradingType('points');
        $this->assertEquals('points', $assignment->getGradingType());

        $assignment->setSubmissionTypes(['online_text_entry']);
        $this->assertEquals(['online_text_entry'], $assignment->getSubmissionTypes());

        $assignment->setAllowedExtensions(['pdf', 'doc']);
        $this->assertEquals(['pdf', 'doc'], $assignment->getAllowedExtensions());

        $assignment->setAllowedAttempts(3);
        $this->assertEquals(3, $assignment->getAllowedAttempts());

        $assignment->setDueAt('2024-12-31T23:59:59Z');
        $this->assertEquals('2024-12-31T23:59:59Z', $assignment->getDueAt());

        $assignment->setLockAt('2025-01-01T00:00:00Z');
        $this->assertEquals('2025-01-01T00:00:00Z', $assignment->getLockAt());

        $assignment->setUnlockAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $assignment->getUnlockAt());

        $assignment->setPublished(true);
        $this->assertTrue($assignment->getPublished());

        $assignment->setWorkflowState('published');
        $this->assertEquals('published', $assignment->getWorkflowState());

        $assignment->setLockedForUser(false);
        $this->assertFalse($assignment->getLockedForUser());

        $assignment->setOnlyVisibleToOverrides(true);
        $this->assertTrue($assignment->getOnlyVisibleToOverrides());

        $assignment->setPeerReviews(true);
        $this->assertTrue($assignment->getPeerReviews());

        $assignment->setAnonymousGrading(false);
        $this->assertFalse($assignment->getAnonymousGrading());

        $assignment->setModeratedGrading(false);
        $this->assertFalse($assignment->getModeratedGrading());

        $assignment->setGroupCategoryId(789);
        $this->assertEquals(789, $assignment->getGroupCategoryId());

        $assignment->setHtmlUrl('/courses/123/assignments/1');
        $this->assertEquals('/courses/123/assignments/1', $assignment->getHtmlUrl());

        $assignment->setHasOverrides(true);
        $this->assertTrue($assignment->getHasOverrides());

        $assignment->setCreatedAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $assignment->getCreatedAt());

        $assignment->setUpdatedAt('2024-01-01T00:00:00Z');
        $this->assertEquals('2024-01-01T00:00:00Z', $assignment->getUpdatedAt());
    }

    public function testFind(): void
    {
        $responseData = [
            'id' => 1,
            'name' => 'Test Assignment',
            'course_id' => 123,
            'points_possible' => 100.0,
            'published' => true
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/assignments/1')
            ->willReturn($responseMock);

        $assignment = Assignment::find(1);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals(1, $assignment->getId());
        $this->assertEquals('Test Assignment', $assignment->getName());
        $this->assertEquals(123, $assignment->getCourseId());
        $this->assertEquals(100.0, $assignment->getPointsPossible());
        $this->assertTrue($assignment->getPublished());
    }

    public function testFetchAll(): void
    {
        $responseData = [
            [
                'id' => 1,
                'name' => 'Assignment 1',
                'course_id' => 123,
                'points_possible' => 100.0,
                'published' => true
            ],
            [
                'id' => 2,
                'name' => 'Assignment 2',
                'course_id' => 123,
                'points_possible' => 50.0,
                'published' => false
            ]
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/assignments', ['query' => []])
            ->willReturn($responseMock);

        $assignments = Assignment::fetchAll();

        $this->assertCount(2, $assignments);
        $this->assertInstanceOf(Assignment::class, $assignments[0]);
        $this->assertInstanceOf(Assignment::class, $assignments[1]);
        $this->assertEquals(1, $assignments[0]->getId());
        $this->assertEquals(2, $assignments[1]->getId());
        $this->assertEquals('Assignment 1', $assignments[0]->getName());
        $this->assertEquals('Assignment 2', $assignments[1]->getName());
    }

    public function testFetchAllWithParams(): void
    {
        $params = ['include' => ['submission'], 'order_by' => 'name'];
        $responseData = [];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/assignments', ['query' => $params])
            ->willReturn($responseMock);

        $assignments = Assignment::fetchAll($params);

        $this->assertCount(0, $assignments);
    }

    public function testFetchAllPaginated(): void
    {
        $this->assertTrue(method_exists(Assignment::class, 'fetchAllPaginated'));
    }

    public function testCreate(): void
    {
        $assignmentData = [
            'name' => 'New Assignment',
            'description' => 'New description',
            'points_possible' => 75.0,
            'published' => true
        ];

        $responseData = [
            'id' => 3,
            'name' => 'New Assignment',
            'course_id' => 123,
            'description' => 'New description',
            'points_possible' => 75.0,
            'published' => true
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('courses/123/assignments', $this->anything())
            ->willReturn($responseMock);

        $assignment = Assignment::create($assignmentData);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals(3, $assignment->getId());
        $this->assertEquals('New Assignment', $assignment->getName());
        $this->assertEquals(75.0, $assignment->getPointsPossible());
        $this->assertTrue($assignment->getPublished());
    }

    public function testCreateWithDTO(): void
    {
        $createDto = new CreateAssignmentDTO([
            'name' => 'New Assignment',
            'description' => 'New description',
            'points_possible' => 75.0,
            'published' => true
        ]);

        $responseData = [
            'id' => 3,
            'name' => 'New Assignment',
            'course_id' => 123,
            'description' => 'New description',
            'points_possible' => 75.0,
            'published' => true
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('courses/123/assignments', ['multipart' => $createDto->toApiArray()])
            ->willReturn($responseMock);

        $assignment = Assignment::create($createDto);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals(3, $assignment->getId());
        $this->assertEquals('New Assignment', $assignment->getName());
    }

    public function testUpdate(): void
    {
        $assignmentId = 1;
        $updateData = [
            'name' => 'Updated Assignment',
            'points_possible' => 90.0
        ];

        $responseData = [
            'id' => 1,
            'name' => 'Updated Assignment',
            'course_id' => 123,
            'points_possible' => 90.0,
            'published' => true
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('courses/123/assignments/1', $this->anything())
            ->willReturn($responseMock);

        $assignment = Assignment::update($assignmentId, $updateData);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals(1, $assignment->getId());
        $this->assertEquals('Updated Assignment', $assignment->getName());
        $this->assertEquals(90.0, $assignment->getPointsPossible());
    }

    public function testUpdateWithDTO(): void
    {
        $assignmentId = 1;
        $updateDto = new UpdateAssignmentDTO([
            'name' => 'Updated Assignment',
            'points_possible' => 90.0
        ]);

        $responseData = [
            'id' => 1,
            'name' => 'Updated Assignment',
            'course_id' => 123,
            'points_possible' => 90.0,
            'published' => true
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('courses/123/assignments/1', ['multipart' => $updateDto->toApiArray()])
            ->willReturn($responseMock);

        $assignment = Assignment::update($assignmentId, $updateDto);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals(1, $assignment->getId());
        $this->assertEquals('Updated Assignment', $assignment->getName());
    }

    public function testSaveNewAssignment(): void
    {
        $assignment = new Assignment();
        $assignment->setName('Test Assignment');
        $assignment->setPointsPossible(100.0);

        $responseData = [
            'id' => 1,
            'name' => 'Test Assignment',
            'course_id' => 123,
            'points_possible' => 100.0,
            'published' => false
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('courses/123/assignments', $this->anything())
            ->willReturn($responseMock);

        $result = $assignment->save();

        $this->assertTrue($result);
        $this->assertEquals(1, $assignment->getId());
        $this->assertEquals('Test Assignment', $assignment->getName());
        $this->assertEquals(100.0, $assignment->getPointsPossible());
    }

    public function testSaveExistingAssignment(): void
    {
        $assignment = new Assignment([
            'id' => 1,
            'name' => 'Test Assignment',
            'course_id' => 123,
            'points_possible' => 100.0
        ]);

        $assignment->setName('Updated Assignment');
        $assignment->setPointsPossible(125.0);

        $responseData = [
            'id' => 1,
            'name' => 'Updated Assignment',
            'course_id' => 123,
            'points_possible' => 125.0,
            'published' => false
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('courses/123/assignments/1', $this->anything())
            ->willReturn($responseMock);

        $result = $assignment->save();

        $this->assertTrue($result);
        $this->assertEquals('Updated Assignment', $assignment->getName());
        $this->assertEquals(125.0, $assignment->getPointsPossible());
    }

    public function testSaveWithoutNameThrowsException(): void
    {
        $assignment = new Assignment();
        $assignment->setDescription('Test description');

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Assignment name is required');

        $assignment->save();
    }

    public function testSaveWithNoChanges(): void
    {
        $assignment = new Assignment(['id' => 1]);
        $result = $assignment->save();

        $this->assertTrue($result);
    }

    public function testSaveReturnsFalseOnException(): void
    {
        $assignment = new Assignment();
        $assignment->setName('Test Assignment');

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->willThrowException(new CanvasApiException('API Error'));

        $result = $assignment->save();

        $this->assertFalse($result);
    }

    public function testDelete(): void
    {
        $assignment = new Assignment(['id' => 1]);

        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('courses/123/assignments/1');

        $result = $assignment->delete();

        $this->assertTrue($result);
    }

    public function testDeleteWithoutIdThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Assignment ID is required for deletion');

        $assignment = new Assignment();
        $assignment->delete();
    }

    public function testDeleteReturnsFalseOnException(): void
    {
        $assignment = new Assignment(['id' => 1]);

        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->willThrowException(new CanvasApiException('API Error'));

        $result = $assignment->delete();

        $this->assertFalse($result);
    }

    public function testDuplicate(): void
    {
        $assignmentId = 1;
        $options = ['name' => 'Duplicated Assignment'];

        $responseData = [
            'id' => 2,
            'name' => 'Duplicated Assignment',
            'course_id' => 123,
            'points_possible' => 100.0,
            'published' => false
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($responseData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('courses/123/assignments/1/duplicate', ['multipart' => $options])
            ->willReturn($responseMock);

        $assignment = Assignment::duplicate($assignmentId, $options);

        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals(2, $assignment->getId());
        $this->assertEquals('Duplicated Assignment', $assignment->getName());
    }

    public function testToArray(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Assignment',
            'course_id' => 123,
            'assignment_group_id' => 456,
            'description' => 'Test description',
            'position' => 1,
            'points_possible' => 100.0,
            'grading_type' => 'points',
            'submission_types' => ['online_text_entry'],
            'allowed_extensions' => ['pdf', 'doc'],
            'allowed_attempts' => 3,
            'due_at' => '2024-12-31T23:59:59Z',
            'lock_at' => '2025-01-01T00:00:00Z',
            'unlock_at' => '2024-01-01T00:00:00Z',
            'all_dates' => [],
            'published' => true,
            'workflow_state' => 'published',
            'locked_for_user' => false,
            'only_visible_to_overrides' => false,
            'peer_reviews' => false,
            'anonymous_grading' => false,
            'moderated_grading' => false,
            'group_category_id' => null,
            'html_url' => '/courses/123/assignments/1',
            'has_overrides' => false,
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z'
        ];

        $assignment = new Assignment($data);
        $result = $assignment->toArray();

        $this->assertEquals($data, $result);
    }

    public function testToDtoArray(): void
    {
        $assignment = new Assignment([
            'id' => 1,
            'name' => 'Test Assignment',
            'description' => 'Test description',
            'points_possible' => 100.0,
            'grading_type' => 'points',
            'submission_types' => ['online_text_entry'],
            'due_at' => '2024-12-31T23:59:59Z',
            'lock_at' => '2025-01-01T00:00:00Z',
            'unlock_at' => '2024-01-01T00:00:00Z',
            'published' => true,
            'assignment_group_id' => 456
        ]);

        $result = $assignment->toDtoArray();

        $expected = [
            'name' => 'Test Assignment',
            'description' => 'Test description',
            'points_possible' => 100.0,
            'grading_type' => 'points',
            'submission_types' => ['online_text_entry'],
            'due_at' => '2024-12-31T23:59:59Z',
            'lock_at' => '2025-01-01T00:00:00Z',
            'unlock_at' => '2024-01-01T00:00:00Z',
            'published' => true,
            'assignment_group_id' => 456
        ];

        $this->assertEquals($expected, $result);
    }
}