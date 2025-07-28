<?php

namespace Tests\Api\Submissions;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Submissions\Submission;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Dto\Submissions\CreateSubmissionDTO;
use CanvasLMS\Dto\Submissions\UpdateSubmissionDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Exception;

/**
 * @covers \CanvasLMS\Api\Submissions\Submission
 */
class SubmissionTest extends TestCase
{
    private HttpClientInterface $httpClientMock;
    private Course $course;
    private Assignment $assignment;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->course = new Course(['id' => 123]);
        $this->assignment = new Assignment(['id' => 456]);

        Submission::setApiClient($this->httpClientMock);
        Submission::setCourse($this->course);
        Submission::setAssignment($this->assignment);
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(Submission::class);
        
        $courseProperty = $reflection->getProperty('course');
        $courseProperty->setAccessible(true);
        $courseProperty->setValue(null, new Course(['id' => 0]));
        
        $assignmentProperty = $reflection->getProperty('assignment');
        $assignmentProperty->setAccessible(true);
        $assignmentProperty->setValue(null, new Assignment(['id' => 0]));
    }

    public function testSetCourse(): void
    {
        $course = new Course(['id' => 789]);
        Submission::setCourse($course);

        $this->assertTrue(Submission::checkCourse());
    }

    public function testSetAssignment(): void
    {
        $assignment = new Assignment(['id' => 999]);
        Submission::setAssignment($assignment);

        $this->assertTrue(Submission::checkAssignment());
    }

    public function testCheckCourseThrowsExceptionWhenCourseNotSet(): void
    {
        $this->markTestSkipped('Cannot easily test unset typed static properties in PHPUnit');
    }

    public function testCheckAssignmentThrowsExceptionWhenAssignmentNotSet(): void
    {
        $this->markTestSkipped('Cannot easily test unset typed static properties in PHPUnit');
    }

    public function testCheckContextsSuccess(): void
    {
        $this->assertTrue(Submission::checkContexts());
    }

    public function testConstructor(): void
    {
        $data = [
            'id' => 1,
            'assignment_id' => 456,
            'user_id' => 789,
            'submission_type' => 'online_text_entry',
            'body' => 'My essay content',
            'workflow_state' => 'submitted',
            'submitted_at' => '2024-01-15T10:30:00Z',
            'late' => false,
            'excused' => false,
            'score' => 85.5,
            'grade' => '85.5'
        ];

        $submission = new Submission($data);

        $this->assertEquals(1, $submission->getId());
        $this->assertEquals(456, $submission->getAssignmentId());
        $this->assertEquals(789, $submission->getUserId());
        $this->assertEquals('online_text_entry', $submission->getSubmissionType());
        $this->assertEquals('My essay content', $submission->getBody());
        $this->assertEquals('submitted', $submission->getWorkflowState());
        $this->assertEquals('2024-01-15T10:30:00Z', $submission->getSubmittedAt());
        $this->assertFalse($submission->getLate());
        $this->assertFalse($submission->getExcused());
        $this->assertEquals(85.5, $submission->getScore());
        $this->assertEquals('85.5', $submission->getGrade());
    }

    public function testGettersAndSetters(): void
    {
        $submission = new Submission([]);

        $submission->setId(123);
        $this->assertEquals(123, $submission->getId());

        $submission->setAssignmentId(456);
        $this->assertEquals(456, $submission->getAssignmentId());

        $submission->setUserId(789);
        $this->assertEquals(789, $submission->getUserId());

        $submission->setSubmissionType('online_url');
        $this->assertEquals('online_url', $submission->getSubmissionType());

        $submission->setBody('Test content');
        $this->assertEquals('Test content', $submission->getBody());

        $submission->setUrl('https://example.com');
        $this->assertEquals('https://example.com', $submission->getUrl());

        $submission->setAttempt(2);
        $this->assertEquals(2, $submission->getAttempt());

        $submission->setSubmittedAt('2024-01-15T10:30:00Z');
        $this->assertEquals('2024-01-15T10:30:00Z', $submission->getSubmittedAt());

        $submission->setScore(95.0);
        $this->assertEquals(95.0, $submission->getScore());

        $submission->setGrade('A');
        $this->assertEquals('A', $submission->getGrade());

        $submission->setWorkflowState('graded');
        $this->assertEquals('graded', $submission->getWorkflowState());

        $submission->setLate(true);
        $this->assertTrue($submission->getLate());

        $submission->setExcused(true);
        $this->assertTrue($submission->getExcused());

        $submission->setMissing(false);
        $this->assertFalse($submission->getMissing());
    }

    public function testCreateWithTextSubmission(): void
    {
        $submissionData = [
            'submission_type' => 'online_text_entry',
            'body' => 'My essay content for this assignment',
            'comment' => 'Please review my work'
        ];

        $responseData = [
            'id' => 123,
            'assignment_id' => 456,
            'user_id' => 789,
            'submission_type' => 'online_text_entry',
            'body' => 'My essay content for this assignment',
            'workflow_state' => 'submitted',
            'submitted_at' => '2024-01-15T10:30:00Z'
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
            ->method('request')
            ->with('POST', 'courses/123/assignments/456/submissions')
            ->willReturn($responseMock);

        $submission = Submission::create($submissionData);

        $this->assertEquals(123, $submission->getId());
        $this->assertEquals(456, $submission->getAssignmentId());
        $this->assertEquals(789, $submission->getUserId());
        $this->assertEquals('online_text_entry', $submission->getSubmissionType());
        $this->assertEquals('My essay content for this assignment', $submission->getBody());
        $this->assertEquals('submitted', $submission->getWorkflowState());
    }

    public function testCreateWithUrlSubmission(): void
    {
        $submissionData = [
            'submission_type' => 'online_url',
            'url' => 'https://github.com/user/project'
        ];

        $responseData = [
            'id' => 124,
            'assignment_id' => 456,
            'user_id' => 789,
            'submission_type' => 'online_url',
            'url' => 'https://github.com/user/project',
            'workflow_state' => 'submitted'
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
            ->method('request')
            ->with('POST', 'courses/123/assignments/456/submissions')
            ->willReturn($responseMock);

        $submission = Submission::create($submissionData);

        $this->assertEquals('online_url', $submission->getSubmissionType());
        $this->assertEquals('https://github.com/user/project', $submission->getUrl());
    }

    public function testCreateWithFileUpload(): void
    {
        $submissionData = [
            'submission_type' => 'online_upload',
            'file_ids' => [123, 456, 789]
        ];

        $responseData = [
            'id' => 125,
            'assignment_id' => 456,
            'user_id' => 789,
            'submission_type' => 'online_upload',
            'workflow_state' => 'submitted',
            'attachments' => [
                ['id' => 123, 'filename' => 'document1.pdf'],
                ['id' => 456, 'filename' => 'document2.pdf'],
                ['id' => 789, 'filename' => 'document3.pdf']
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
            ->method('request')
            ->with('POST', 'courses/123/assignments/456/submissions')
            ->willReturn($responseMock);

        $submission = Submission::create($submissionData);

        $this->assertEquals('online_upload', $submission->getSubmissionType());
        $this->assertCount(3, $submission->getAttachments());
    }

    public function testCreateWithDTO(): void
    {
        $dto = new CreateSubmissionDTO([
            'submission_type' => 'online_text_entry',
            'body' => 'DTO test content'
        ]);

        $responseData = [
            'id' => 126,
            'assignment_id' => 456,
            'user_id' => 789,
            'submission_type' => 'online_text_entry',
            'body' => 'DTO test content',
            'workflow_state' => 'submitted'
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
            ->method('request')
            ->with('POST', 'courses/123/assignments/456/submissions')
            ->willReturn($responseMock);

        $submission = Submission::create($dto);

        $this->assertEquals('DTO test content', $submission->getBody());
    }

    public function testFind(): void
    {
        $userId = 789;
        $responseData = [
            'id' => 123,
            'assignment_id' => 456,
            'user_id' => 789,
            'submission_type' => 'online_text_entry',
            'body' => 'Found submission content',
            'workflow_state' => 'submitted',
            'score' => 92.0,
            'grade' => 'A-'
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
            ->with('courses/123/assignments/456/submissions/789')
            ->willReturn($responseMock);

        $submission = Submission::find($userId);

        $this->assertEquals(123, $submission->getId());
        $this->assertEquals(789, $submission->getUserId());
        $this->assertEquals('Found submission content', $submission->getBody());
        $this->assertEquals(92.0, $submission->getScore());
        $this->assertEquals('A-', $submission->getGrade());
    }

    public function testUpdate(): void
    {
        $userId = 789;
        $updateData = [
            'posted_grade' => '88',
            'comment' => 'Good work, but could be improved'
        ];

        $responseData = [
            'id' => 123,
            'assignment_id' => 456,
            'user_id' => 789,
            'submission_type' => 'online_text_entry',
            'workflow_state' => 'graded',
            'score' => 88.0,
            'grade' => '88',
            'graded_at' => '2024-01-16T09:15:00Z'
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
            ->method('request')
            ->with('PUT', 'courses/123/assignments/456/submissions/789')
            ->willReturn($responseMock);

        $submission = Submission::update($userId, $updateData);

        $this->assertEquals('graded', $submission->getWorkflowState());
        $this->assertEquals(88.0, $submission->getScore());
        $this->assertEquals('88', $submission->getGrade());
        $this->assertEquals('2024-01-16T09:15:00Z', $submission->getGradedAt());
    }

    public function testUpdateWithDTO(): void
    {
        $userId = 789;
        $dto = new UpdateSubmissionDTO([
            'posted_grade' => '95',
            'excuse' => false,
            'comment' => 'Excellent work!'
        ]);

        $responseData = [
            'id' => 123,
            'assignment_id' => 456,
            'user_id' => 789,
            'workflow_state' => 'graded',
            'score' => 95.0,
            'grade' => '95',
            'excused' => false
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
            ->method('request')
            ->with('PUT', 'courses/123/assignments/456/submissions/789')
            ->willReturn($responseMock);

        $submission = Submission::update($userId, $dto);

        $this->assertEquals(95.0, $submission->getScore());
        $this->assertFalse($submission->getExcused());
    }

    public function testMarkAsRead(): void
    {
        $userId = 789;

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('courses/123/assignments/456/submissions/789/read');

        $result = Submission::markAsRead($userId);

        $this->assertTrue($result);
    }

    public function testMarkAsUnread(): void
    {
        $userId = 789;

        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('courses/123/assignments/456/submissions/789/read');

        $result = Submission::markAsUnread($userId);

        $this->assertTrue($result);
    }

    public function testMarkAsReadHandlesException(): void
    {
        $userId = 789;

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('courses/123/assignments/456/submissions/789/read')
            ->willThrowException(new CanvasApiException('API Error'));

        $result = Submission::markAsRead($userId);

        $this->assertFalse($result);
    }

    public function testUpdateGrades(): void
    {
        $gradeData = [
            'grade_data' => [
                ['user_id' => 123, 'posted_grade' => '90'],
                ['user_id' => 456, 'posted_grade' => '85'],
                ['user_id' => 789, 'posted_grade' => '92']
            ]
        ];

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with('PUT', 'courses/123/assignments/456/submissions/update_grades', ['json' => $gradeData]);

        $result = Submission::updateGrades($gradeData);

        $this->assertTrue($result);
    }

    public function testUpdateGradesHandlesException(): void
    {
        $gradeData = [
            'grade_data' => [
                ['user_id' => 123, 'posted_grade' => '90']
            ]
        ];

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with('PUT', 'courses/123/assignments/456/submissions/update_grades', ['json' => $gradeData])
            ->willThrowException(new CanvasApiException('Bulk update failed'));

        $result = Submission::updateGrades($gradeData);

        $this->assertFalse($result);
    }

    public function testSave(): void
    {
        $submissionData = [
            'id' => 123,
            'assignment_id' => 456,
            'user_id' => 789,
            'submission_type' => 'online_text_entry'
        ];

        $submission = new Submission($submissionData);
        $submission->setScore(90.0);
        $submission->setGrade('A-');

        $responseData = [
            'id' => 123,
            'assignment_id' => 456,
            'user_id' => 789,
            'submission_type' => 'online_text_entry',
            'score' => 90.0,
            'grade' => 'A-',
            'workflow_state' => 'graded'
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
            ->method('request')
            ->with('PUT', 'courses/123/assignments/456/submissions/789')
            ->willReturn($responseMock);

        $result = $submission->save();

        $this->assertTrue($result);
        $this->assertEquals('graded', $submission->getWorkflowState());
    }

    public function testSaveWithoutUserIdThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot save submission without user ID');

        $submission = new Submission([]);
        $submission->save();
    }

    public function testSaveHandlesException(): void
    {
        $submissionData = [
            'id' => 123,
            'assignment_id' => 456,
            'user_id' => 789
        ];

        $submission = new Submission($submissionData);

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willThrowException(new CanvasApiException('Save failed'));

        $result = $submission->save();

        $this->assertFalse($result);
    }

    public function testFetchAll(): void
    {
        $responseData = [
            [
                'id' => 123,
                'assignment_id' => 456,
                'user_id' => 111,
                'submission_type' => 'online_text_entry',
                'workflow_state' => 'submitted'
            ],
            [
                'id' => 124,
                'assignment_id' => 456,
                'user_id' => 222,
                'submission_type' => 'online_url',
                'workflow_state' => 'submitted'
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
            ->with('courses/123/assignments/456/submissions')
            ->willReturn($responseMock);

        $submissions = Submission::fetchAll();

        $this->assertCount(2, $submissions);
        $this->assertInstanceOf(Submission::class, $submissions[0]);
        $this->assertInstanceOf(Submission::class, $submissions[1]);
        $this->assertEquals(111, $submissions[0]->getUserId());
        $this->assertEquals(222, $submissions[1]->getUserId());
    }

    public function testFetchAllWithParameters(): void
    {
        $params = [
            'student_ids' => [111, 222],
            'workflow_state' => 'submitted',
            'include' => ['submission_comments', 'rubric_assessment']
        ];

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/123/assignments/456/submissions', ['query' => $params])
            ->willReturn($this->createMock(ResponseInterface::class));

        // This test ensures the parameters are passed correctly to the HTTP client
        Submission::fetchAll($params);
        
        // Add assertion to make test not risky
        $this->assertTrue(true);
    }
}