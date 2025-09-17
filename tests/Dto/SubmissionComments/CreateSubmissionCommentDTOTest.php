<?php

declare(strict_types=1);

namespace Tests\Dto\SubmissionComments;

use CanvasLMS\Dto\SubmissionComments\CreateSubmissionCommentDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Dto\SubmissionComments\CreateSubmissionCommentDTO
 */
class CreateSubmissionCommentDTOTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $data = [
            'text_comment' => 'Great work on this assignment!',
            'group_comment' => true,
            'media_comment_id' => 'audio123',
            'media_comment_type' => 'audio',
            'file_ids' => [123, 456],
        ];

        $dto = new CreateSubmissionCommentDTO($data);

        $this->assertEquals('Great work on this assignment!', $dto->getTextComment());
        $this->assertTrue($dto->getGroupComment());
        $this->assertEquals('audio123', $dto->getMediaCommentId());
        $this->assertEquals('audio', $dto->getMediaCommentType());
        $this->assertEquals([123, 456], $dto->getFileIds());
    }

    public function testConstructorWithEmptyData(): void
    {
        $dto = new CreateSubmissionCommentDTO([]);

        $this->assertNull($dto->getTextComment());
        $this->assertNull($dto->getGroupComment());
        $this->assertNull($dto->getMediaCommentId());
        $this->assertNull($dto->getMediaCommentType());
        $this->assertNull($dto->getFileIds());
    }

    public function testGettersAndSetters(): void
    {
        $dto = new CreateSubmissionCommentDTO([]);

        // Test text comment
        $dto->setTextComment('This is a test comment');
        $this->assertEquals('This is a test comment', $dto->getTextComment());

        // Test group comment
        $dto->setGroupComment(false);
        $this->assertFalse($dto->getGroupComment());

        // Test media comment ID
        $dto->setMediaCommentId('video456');
        $this->assertEquals('video456', $dto->getMediaCommentId());

        // Test media comment type
        $dto->setMediaCommentType('video');
        $this->assertEquals('video', $dto->getMediaCommentType());

        // Test file IDs
        $fileIds = [789, 101112];
        $dto->setFileIds($fileIds);
        $this->assertEquals($fileIds, $dto->getFileIds());
    }

    public function testSettersWithNullValues(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'text_comment' => 'Initial comment',
            'group_comment' => true,
        ]);

        // Set all values to null
        $dto->setTextComment(null);
        $dto->setGroupComment(null);
        $dto->setMediaCommentId(null);
        $dto->setMediaCommentType(null);
        $dto->setFileIds(null);

        $this->assertNull($dto->getTextComment());
        $this->assertNull($dto->getGroupComment());
        $this->assertNull($dto->getMediaCommentId());
        $this->assertNull($dto->getMediaCommentType());
        $this->assertNull($dto->getFileIds());
    }

    public function testToApiArrayForTextComment(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'text_comment' => 'Excellent analysis of the topic!',
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => 'Excellent analysis of the topic!'], $apiArray);
    }

    public function testToApiArrayForGroupComment(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'text_comment' => 'Good teamwork everyone',
            'group_comment' => true,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => 'Good teamwork everyone'], $apiArray);
        $this->assertContains(['name' => 'comment[group_comment]', 'contents' => true], $apiArray);
    }

    public function testToApiArrayForMediaComment(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'media_comment_id' => 'audio_feedback_789',
            'media_comment_type' => 'audio',
            'text_comment' => 'Please listen to my audio feedback',
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'comment[media_comment_id]', 'contents' => 'audio_feedback_789'], $apiArray);
        $this->assertContains(['name' => 'comment[media_comment_type]', 'contents' => 'audio'], $apiArray);
        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => 'Please listen to my audio feedback'], $apiArray);
    }

    public function testToApiArrayForFileAttachments(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'text_comment' => 'See attached files for detailed feedback',
            'file_ids' => [123, 456, 789],
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => 'See attached files for detailed feedback'], $apiArray);
        $this->assertContains(['name' => 'comment[file_ids][]', 'contents' => 123], $apiArray);
        $this->assertContains(['name' => 'comment[file_ids][]', 'contents' => 456], $apiArray);
        $this->assertContains(['name' => 'comment[file_ids][]', 'contents' => 789], $apiArray);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'text_comment' => 'Only text comment provided',
            // All other fields are null
        ]);

        $apiArray = $dto->toApiArray();

        // Should only contain non-null values
        $fieldNames = array_column($apiArray, 'name');
        $this->assertContains('comment[text_comment]', $fieldNames);
        $this->assertNotContains('comment[group_comment]', $fieldNames);
        $this->assertNotContains('comment[media_comment_id]', $fieldNames);
        $this->assertNotContains('comment[media_comment_type]', $fieldNames);
        $this->assertNotContains('comment[file_ids]', $fieldNames);
    }

    public function testToArrayIncludesAllSetValues(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'text_comment' => 'Great work!',
            'group_comment' => false,
            'file_ids' => [123, 456],
        ]);

        $array = $dto->toArray();

        $this->assertEquals('Great work!', $array['textComment']);
        $this->assertFalse($array['groupComment']);
        $this->assertEquals([123, 456], $array['fileIds']);
        $this->assertArrayNotHasKey('mediaCommentId', $array);
        $this->assertArrayNotHasKey('mediaCommentType', $array);
    }

    public function testSnakeCaseToApiPropertyConversion(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'text_comment' => 'Test comment',
            'group_comment' => true,
            'media_comment_id' => 'media123',
            'media_comment_type' => 'video',
            'file_ids' => [123],
        ]);

        $apiArray = $dto->toApiArray();
        $fieldNames = array_column($apiArray, 'name');

        // Verify snake_case conversion in API property names
        $this->assertContains('comment[text_comment]', $fieldNames);
        $this->assertContains('comment[group_comment]', $fieldNames);
        $this->assertContains('comment[media_comment_id]', $fieldNames);
        $this->assertContains('comment[media_comment_type]', $fieldNames);
        $this->assertContains('comment[file_ids][]', $fieldNames);
    }

    public function testBooleanValuesInApiArray(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'text_comment' => 'Boolean test',
            'group_comment' => false,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => 'Boolean test'], $apiArray);
        $this->assertContains(['name' => 'comment[group_comment]', 'contents' => false], $apiArray);
    }

    public function testMediaCommentTypes(): void
    {
        // Test audio
        $audioDto = new CreateSubmissionCommentDTO([
            'media_comment_id' => 'audio123',
            'media_comment_type' => 'audio',
        ]);

        $audioArray = $audioDto->toApiArray();
        $this->assertContains(['name' => 'comment[media_comment_type]', 'contents' => 'audio'], $audioArray);

        // Test video
        $videoDto = new CreateSubmissionCommentDTO([
            'media_comment_id' => 'video456',
            'media_comment_type' => 'video',
        ]);

        $videoArray = $videoDto->toApiArray();
        $this->assertContains(['name' => 'comment[media_comment_type]', 'contents' => 'video'], $videoArray);
    }

    public function testEmptyFileIdsArray(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'text_comment' => 'No files attached',
            'file_ids' => [],
        ]);

        $apiArray = $dto->toApiArray();
        $fieldNames = array_column($apiArray, 'name');

        $this->assertContains('comment[text_comment]', $fieldNames);
        // Empty array should not appear in API array since it's effectively empty
        $this->assertNotContains('comment[file_ids][]', $fieldNames);
    }

    public function testSingleFileId(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'text_comment' => 'Single file attached',
            'file_ids' => [999],
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => 'Single file attached'], $apiArray);
        $this->assertContains(['name' => 'comment[file_ids][]', 'contents' => 999], $apiArray);
    }

    public function testComplexCommentScenario(): void
    {
        $dto = new CreateSubmissionCommentDTO([
            'text_comment' => 'See attached rubric and listen to audio feedback',
            'group_comment' => true,
            'media_comment_id' => 'detailed_feedback_123',
            'media_comment_type' => 'audio',
            'file_ids' => [111, 222, 333],
        ]);

        $apiArray = $dto->toApiArray();

        // Verify all components are present
        $this->assertContains(['name' => 'comment[text_comment]', 'contents' => 'See attached rubric and listen to audio feedback'], $apiArray);
        $this->assertContains(['name' => 'comment[group_comment]', 'contents' => true], $apiArray);
        $this->assertContains(['name' => 'comment[media_comment_id]', 'contents' => 'detailed_feedback_123'], $apiArray);
        $this->assertContains(['name' => 'comment[media_comment_type]', 'contents' => 'audio'], $apiArray);
        $this->assertContains(['name' => 'comment[file_ids][]', 'contents' => 111], $apiArray);
        $this->assertContains(['name' => 'comment[file_ids][]', 'contents' => 222], $apiArray);
        $this->assertContains(['name' => 'comment[file_ids][]', 'contents' => 333], $apiArray);
    }
}
