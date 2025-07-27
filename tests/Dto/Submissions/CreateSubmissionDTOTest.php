<?php

namespace Tests\Dto\Submissions;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Submissions\CreateSubmissionDTO;

/**
 * @covers \CanvasLMS\Dto\Submissions\CreateSubmissionDTO
 */
class CreateSubmissionDTOTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'submission_type' => 'online_text_entry',
            'body' => 'My essay content for this assignment',
            'url' => 'https://example.com/my-project',
            'file_ids' => [123, 456, 789],
            'media_comment_id' => 'audio123',
            'media_comment_type' => 'audio',
            'user_id' => 789,
            'comment' => 'Please review my submission'
        ];

        $dto = new CreateSubmissionDTO($data);

        $this->assertEquals('online_text_entry', $dto->getSubmissionType());
        $this->assertEquals('My essay content for this assignment', $dto->getBody());
        $this->assertEquals('https://example.com/my-project', $dto->getUrl());
        $this->assertEquals([123, 456, 789], $dto->getFileIds());
        $this->assertEquals('audio123', $dto->getMediaCommentId());
        $this->assertEquals('audio', $dto->getMediaCommentType());
        $this->assertEquals(789, $dto->getUserId());
        $this->assertEquals('Please review my submission', $dto->getComment());
    }

    public function testConstructorWithEmptyData(): void
    {
        $dto = new CreateSubmissionDTO([]);

        $this->assertNull($dto->getSubmissionType());
        $this->assertNull($dto->getBody());
        $this->assertNull($dto->getUrl());
        $this->assertNull($dto->getFileIds());
        $this->assertNull($dto->getMediaCommentId());
        $this->assertNull($dto->getMediaCommentType());
        $this->assertNull($dto->getUserId());
        $this->assertNull($dto->getComment());
    }

    public function testGettersAndSetters(): void
    {
        $dto = new CreateSubmissionDTO([]);

        // Test submission type
        $dto->setSubmissionType('online_url');
        $this->assertEquals('online_url', $dto->getSubmissionType());

        // Test body
        $dto->setBody('Essay content here');
        $this->assertEquals('Essay content here', $dto->getBody());

        // Test URL
        $dto->setUrl('https://github.com/user/repo');
        $this->assertEquals('https://github.com/user/repo', $dto->getUrl());

        // Test file IDs
        $fileIds = [111, 222, 333];
        $dto->setFileIds($fileIds);
        $this->assertEquals($fileIds, $dto->getFileIds());

        // Test media comment ID
        $dto->setMediaCommentId('video456');
        $this->assertEquals('video456', $dto->getMediaCommentId());

        // Test media comment type
        $dto->setMediaCommentType('video');
        $this->assertEquals('video', $dto->getMediaCommentType());

        // Test user ID
        $dto->setUserId(999);
        $this->assertEquals(999, $dto->getUserId());

        // Test comment
        $dto->setComment('Additional notes');
        $this->assertEquals('Additional notes', $dto->getComment());
    }

    public function testSettersWithNullValues(): void
    {
        $dto = new CreateSubmissionDTO([
            'submission_type' => 'online_text_entry',
            'body' => 'Initial content'
        ]);

        // Set all values to null
        $dto->setSubmissionType(null);
        $dto->setBody(null);
        $dto->setUrl(null);
        $dto->setFileIds(null);
        $dto->setMediaCommentId(null);
        $dto->setMediaCommentType(null);
        $dto->setUserId(null);
        $dto->setComment(null);

        $this->assertNull($dto->getSubmissionType());
        $this->assertNull($dto->getBody());
        $this->assertNull($dto->getUrl());
        $this->assertNull($dto->getFileIds());
        $this->assertNull($dto->getMediaCommentId());
        $this->assertNull($dto->getMediaCommentType());
        $this->assertNull($dto->getUserId());
        $this->assertNull($dto->getComment());
    }

    public function testToApiArrayForTextSubmission(): void
    {
        $dto = new CreateSubmissionDTO([
            'submission_type' => 'online_text_entry',
            'body' => 'My essay content',
            'comment' => 'Please grade this'
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'submission[submission_type]', 'contents' => 'online_text_entry'], $apiArray);
        $this->assertContains(['name' => 'submission[body]', 'contents' => 'My essay content'], $apiArray);
        $this->assertContains(['name' => 'submission[comment]', 'contents' => 'Please grade this'], $apiArray);
    }

    public function testToApiArrayForUrlSubmission(): void
    {
        $dto = new CreateSubmissionDTO([
            'submission_type' => 'online_url',
            'url' => 'https://example.com/project'
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'submission[submission_type]', 'contents' => 'online_url'], $apiArray);
        $this->assertContains(['name' => 'submission[url]', 'contents' => 'https://example.com/project'], $apiArray);
    }

    public function testToApiArrayForFileSubmission(): void
    {
        $dto = new CreateSubmissionDTO([
            'submission_type' => 'online_upload',
            'file_ids' => [123, 456]
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'submission[submission_type]', 'contents' => 'online_upload'], $apiArray);
        $this->assertContains(['name' => 'submission[file_ids][]', 'contents' => 123], $apiArray);
        $this->assertContains(['name' => 'submission[file_ids][]', 'contents' => 456], $apiArray);
    }

    public function testToApiArrayForMediaSubmission(): void
    {
        $dto = new CreateSubmissionDTO([
            'submission_type' => 'media_recording',
            'media_comment_id' => 'audio123',
            'media_comment_type' => 'audio'
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'submission[submission_type]', 'contents' => 'media_recording'], $apiArray);
        $this->assertContains(['name' => 'submission[media_comment_id]', 'contents' => 'audio123'], $apiArray);
        $this->assertContains(['name' => 'submission[media_comment_type]', 'contents' => 'audio'], $apiArray);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $dto = new CreateSubmissionDTO([
            'submission_type' => 'online_text_entry',
            'body' => 'Content here'
            // url, file_ids, etc. are null
        ]);

        $apiArray = $dto->toApiArray();

        // Should only contain non-null values
        $fieldNames = array_column($apiArray, 'name');
        $this->assertContains('submission[submission_type]', $fieldNames);
        $this->assertContains('submission[body]', $fieldNames);
        $this->assertNotContains('submission[url]', $fieldNames);
        $this->assertNotContains('submission[file_ids]', $fieldNames);
        $this->assertNotContains('submission[media_comment_id]', $fieldNames);
    }

    public function testToArrayIncludesAllSetValues(): void
    {
        $dto = new CreateSubmissionDTO([
            'submission_type' => 'online_text_entry',
            'body' => 'Essay content',
            'user_id' => 789,
            'comment' => 'Review please'
        ]);

        $array = $dto->toArray();

        $this->assertEquals('online_text_entry', $array['submissionType']);
        $this->assertEquals('Essay content', $array['body']);
        $this->assertEquals(789, $array['userId']);
        $this->assertEquals('Review please', $array['comment']);
        $this->assertArrayNotHasKey('url', $array);
        $this->assertArrayNotHasKey('fileIds', $array);
    }

    public function testSnakeCaseToApiPropertyConversion(): void
    {
        $dto = new CreateSubmissionDTO([
            'submission_type' => 'online_text_entry',
            'media_comment_id' => 'test123',
            'media_comment_type' => 'audio',
            'user_id' => 456
        ]);

        $apiArray = $dto->toApiArray();
        $fieldNames = array_column($apiArray, 'name');

        // Verify snake_case conversion in API property names
        $this->assertContains('submission[submission_type]', $fieldNames);
        $this->assertContains('submission[media_comment_id]', $fieldNames);
        $this->assertContains('submission[media_comment_type]', $fieldNames);
        $this->assertContains('submission[user_id]', $fieldNames);
    }
}