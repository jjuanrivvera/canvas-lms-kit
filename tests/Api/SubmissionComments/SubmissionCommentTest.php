<?php

namespace Tests\Api\SubmissionComments;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\SubmissionComments\SubmissionComment;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Dto\SubmissionComments\CreateSubmissionCommentDTO;
use CanvasLMS\Dto\SubmissionComments\UpdateSubmissionCommentDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Exception;

/**
 * @covers \CanvasLMS\Api\SubmissionComments\SubmissionComment
 */
class SubmissionCommentTest extends TestCase
{
    private HttpClientInterface $httpClientMock;
    private Course $course;
    private Assignment $assignment;
    private int $userId;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->course = new Course(['id' => 123]);
        $this->assignment = new Assignment(['id' => 456]);
        $this->userId = 789;

        SubmissionComment::setApiClient($this->httpClientMock);
        SubmissionComment::setCourse($this->course);
        SubmissionComment::setAssignment($this->assignment);
        SubmissionComment::setUserId($this->userId);
    }

    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(SubmissionComment::class);
        
        $courseProperty = $reflection->getProperty('course');
        $courseProperty->setAccessible(true);
        $courseProperty->setValue(null, new Course(['id' => 0]));
        
        $assignmentProperty = $reflection->getProperty('assignment');
        $assignmentProperty->setAccessible(true);
        $assignmentProperty->setValue(null, new Assignment(['id' => 0]));
        
        $userIdProperty = $reflection->getProperty('userId');
        $userIdProperty->setAccessible(true);
        $userIdProperty->setValue(null, 0);
    }

    public function testSetCourse(): void
    {
        $course = new Course(['id' => 999]);
        SubmissionComment::setCourse($course);

        $this->assertTrue(SubmissionComment::checkCourse());
    }

    public function testSetAssignment(): void
    {
        $assignment = new Assignment(['id' => 888]);
        SubmissionComment::setAssignment($assignment);

        $this->assertTrue(SubmissionComment::checkAssignment());
    }

    public function testSetUserId(): void
    {
        SubmissionComment::setUserId(777);

        $this->assertTrue(SubmissionComment::checkUserId());
    }

    public function testCheckCourseThrowsExceptionWhenCourseNotSet(): void
    {
        $this->markTestSkipped('Cannot easily test unset typed static properties in PHPUnit');
    }

    public function testCheckAssignmentThrowsExceptionWhenAssignmentNotSet(): void
    {
        $this->markTestSkipped('Cannot easily test unset typed static properties in PHPUnit');
    }

    public function testCheckUserIdThrowsExceptionWhenUserIdNotSet(): void
    {
        $this->markTestSkipped('Cannot easily test unset typed static properties in PHPUnit');
    }

    public function testCheckContextsSuccess(): void
    {
        $this->assertTrue(SubmissionComment::checkContexts());
    }

    public function testConstructor(): void
    {
        $data = [
            'id' => 37,
            'author_id' => 134,
            'author_name' => 'Teacher Name',
            'comment' => 'Great work on this assignment!',
            'created_at' => '2024-01-15T10:30:00Z',
            'edited_at' => null,
            'media_comment' => null
        ];

        $comment = new SubmissionComment($data);

        $this->assertEquals(37, $comment->getId());
        $this->assertEquals(134, $comment->getAuthorId());
        $this->assertEquals('Teacher Name', $comment->getAuthorName());
        $this->assertEquals('Great work on this assignment!', $comment->getComment());
        $this->assertEquals('2024-01-15T10:30:00Z', $comment->getCreatedAt());
        $this->assertNull($comment->getEditedAt());
        $this->assertNull($comment->getMediaComment());
    }

    public function testGettersAndSetters(): void
    {
        $comment = new SubmissionComment([]);

        $comment->setId(123);
        $this->assertEquals(123, $comment->getId());

        $comment->setAuthorId(456);
        $this->assertEquals(456, $comment->getAuthorId());

        $comment->setAuthorName('John Doe');
        $this->assertEquals('John Doe', $comment->getAuthorName());

        $comment->setComment('This is a test comment');
        $this->assertEquals('This is a test comment', $comment->getComment());

        $comment->setCreatedAt('2024-01-15T10:30:00Z');
        $this->assertEquals('2024-01-15T10:30:00Z', $comment->getCreatedAt());

        $comment->setEditedAt('2024-01-15T11:30:00Z');
        $this->assertEquals('2024-01-15T11:30:00Z', $comment->getEditedAt());

        $author = (object)['id' => 456, 'display_name' => 'John Doe'];
        $comment->setAuthor($author);
        $this->assertEquals($author, $comment->getAuthor());

        $mediaComment = ['media_id' => 'abc123', 'media_type' => 'audio'];
        $comment->setMediaComment($mediaComment);
        $this->assertEquals($mediaComment, $comment->getMediaComment());
    }

    public function testUpdate(): void
    {
        $commentId = 37;
        $updateData = [
            'text_comment' => 'Updated comment text'
        ];

        $responseData = [
            'id' => 37,
            'author_id' => 134,
            'author_name' => 'Teacher Name',
            'comment' => 'Updated comment text',
            'created_at' => '2024-01-15T10:30:00Z',
            'edited_at' => '2024-01-15T11:45:00Z'
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
            ->with('PUT', 'courses/123/assignments/456/submissions/789/comments/37')
            ->willReturn($responseMock);

        $comment = SubmissionComment::update($commentId, $updateData);

        $this->assertEquals(37, $comment->getId());
        $this->assertEquals('Updated comment text', $comment->getComment());
        $this->assertEquals('2024-01-15T11:45:00Z', $comment->getEditedAt());
    }

    public function testUpdateWithDTO(): void
    {
        $commentId = 37;
        $dto = new UpdateSubmissionCommentDTO([
            'text_comment' => 'DTO updated comment'
        ]);

        $responseData = [
            'id' => 37,
            'author_id' => 134,
            'author_name' => 'Teacher Name',
            'comment' => 'DTO updated comment',
            'created_at' => '2024-01-15T10:30:00Z',
            'edited_at' => '2024-01-15T12:00:00Z'
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
            ->with('PUT', 'courses/123/assignments/456/submissions/789/comments/37')
            ->willReturn($responseMock);

        $comment = SubmissionComment::update($commentId, $dto);

        $this->assertEquals('DTO updated comment', $comment->getComment());
        $this->assertEquals('2024-01-15T12:00:00Z', $comment->getEditedAt());
    }

    public function testDelete(): void
    {
        $commentId = 37;

        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('courses/123/assignments/456/submissions/789/comments/37');

        $result = SubmissionComment::delete($commentId);

        $this->assertTrue($result);
    }

    public function testDeleteHandlesException(): void
    {
        $commentId = 37;

        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('courses/123/assignments/456/submissions/789/comments/37')
            ->willThrowException(new CanvasApiException('Delete failed'));

        $result = SubmissionComment::delete($commentId);

        $this->assertFalse($result);
    }

    public function testUploadFile(): void
    {
        $fileData = [
            'name' => 'feedback.pdf',
            'size' => 1024000,
            'content_type' => 'application/pdf',
            'parent_folder_path' => '/submission_comments'
        ];

        $responseData = [
            'upload_url' => 'https://canvas.example.com/upload',
            'upload_params' => [
                'key' => 'submission_comment_files/123/feedback.pdf',
                'acl' => 'private'
            ],
            'file_param' => 'file'
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
            ->with('POST', 'courses/123/assignments/456/submissions/789/comments/files', ['json' => $fileData])
            ->willReturn($responseMock);

        $uploadInfo = SubmissionComment::uploadFile($fileData);

        $this->assertEquals('https://canvas.example.com/upload', $uploadInfo['upload_url']);
        $this->assertArrayHasKey('upload_params', $uploadInfo);
        $this->assertEquals('file', $uploadInfo['file_param']);
    }

    public function testSave(): void
    {
        $commentData = [
            'id' => 37,
            'author_id' => 134,
            'comment' => 'Original comment'
        ];

        $comment = new SubmissionComment($commentData);
        $comment->setComment('Modified comment text');

        $responseData = [
            'id' => 37,
            'author_id' => 134,
            'author_name' => 'Teacher Name',
            'comment' => 'Modified comment text',
            'created_at' => '2024-01-15T10:30:00Z',
            'edited_at' => '2024-01-15T13:15:00Z'
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
            ->with('PUT', 'courses/123/assignments/456/submissions/789/comments/37')
            ->willReturn($responseMock);

        $result = $comment->save();

        $this->assertTrue($result);
        $this->assertEquals('Modified comment text', $comment->getComment());
        $this->assertEquals('2024-01-15T13:15:00Z', $comment->getEditedAt());
    }

    public function testSaveWithoutIdThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot save comment without ID');

        $comment = new SubmissionComment([]);
        $comment->save();
    }

    public function testSaveHandlesException(): void
    {
        $commentData = [
            'id' => 37,
            'comment' => 'Test comment'
        ];

        $comment = new SubmissionComment($commentData);

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willThrowException(new CanvasApiException('Save failed'));

        $result = $comment->save();

        $this->assertFalse($result);
    }
}