<?php

declare(strict_types=1);

namespace Tests\Dto\Announcements;

use CanvasLMS\Dto\Announcements\UpdateAnnouncementDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Dto\Announcements\UpdateAnnouncementDTO
 */
class UpdateAnnouncementDTOTest extends TestCase
{
    public function testConstructorMaintainsAnnouncementConstraints(): void
    {
        $dto = new UpdateAnnouncementDTO([
            'title' => 'Updated Announcement',
            'is_announcement' => false,  // Try to change it to non-announcement
            'require_initial_post' => true,  // Try to enable initial post
        ]);

        // Should maintain announcement status even if attempted to change
        $this->assertTrue($dto->isAnnouncement);
        // Should prevent initial post requirement
        $this->assertFalse($dto->requireInitialPost);
    }

    public function testPartialUpdate(): void
    {
        $dto = new UpdateAnnouncementDTO([
            'title' => 'New Title Only',
        ]);

        $this->assertEquals('New Title Only', $dto->title);
        $this->assertNull($dto->message);
        $this->assertNull($dto->locked);
        $this->assertNull($dto->published);
    }

    public function testSetDelayedPostAtPublishesWhenNull(): void
    {
        $dto = new UpdateAnnouncementDTO([
            'title' => 'Post Now',
        ]);

        $dto->setDelayedPostAt(null);

        $this->assertNull($dto->delayedPostAt);
        $this->assertTrue($dto->published);
    }

    public function testSetDelayedPostAtWithDate(): void
    {
        $dto = new UpdateAnnouncementDTO();

        $dto->setDelayedPostAt('2024-07-01T15:00:00Z');

        $this->assertEquals('2024-07-01T15:00:00Z', $dto->delayedPostAt);
        // Should not auto-publish when scheduling
        $this->assertNull($dto->published);
    }

    public function testLockComments(): void
    {
        $dto = new UpdateAnnouncementDTO();

        $this->assertNull($dto->lockComment);

        $dto->lockComments(true);
        $this->assertTrue($dto->lockComment);

        $dto->lockComments(false);
        $this->assertFalse($dto->lockComment);
    }

    public function testLockCommentsDefaultValue(): void
    {
        $dto = new UpdateAnnouncementDTO();

        $dto->lockComments();
        $this->assertTrue($dto->lockComment);
    }

    public function testSetSections(): void
    {
        $dto = new UpdateAnnouncementDTO();

        $this->assertNull($dto->specificSections);

        $sectionIds = [201, 202, 203];
        $dto->setSections($sectionIds);

        $this->assertEquals($sectionIds, $dto->specificSections);
    }

    public function testEmptySectionsArray(): void
    {
        $dto = new UpdateAnnouncementDTO();

        $dto->setSections([]);

        $this->assertEquals([], $dto->specificSections);
    }

    public function testGettersAndSetters(): void
    {
        $dto = new UpdateAnnouncementDTO();

        $dto->setTitle('Updated Title');
        $this->assertEquals('Updated Title', $dto->getTitle());

        $dto->setMessage('Updated Message');
        $this->assertEquals('Updated Message', $dto->getMessage());

        $dto->setLocked(true);
        $this->assertTrue($dto->getLocked());

        $dto->setPinned(false);
        $this->assertFalse($dto->getPinned());

        $dto->setPublished(true);
        $this->assertTrue($dto->getPublished());

        $dto->setDelayedPostAt('2024-08-15T12:00:00Z');
        $this->assertEquals('2024-08-15T12:00:00Z', $dto->getDelayedPostAt());
    }

    public function testToApiArray(): void
    {
        $dto = new UpdateAnnouncementDTO([
            'title' => 'Updated API Announcement',
            'locked' => true,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);

        // Check for multipart format
        $this->assertArrayHasKey(0, $apiArray);

        // Find the title and locked fields
        $titleFound = false;
        $lockedFound = false;

        foreach ($apiArray as $field) {
            if (isset($field['name'])) {
                if ($field['name'] === 'discussion_topic[title]') {
                    $this->assertEquals('Updated API Announcement', $field['contents']);
                    $titleFound = true;
                }
                if ($field['name'] === 'discussion_topic[locked]') {
                    $this->assertEquals('1', $field['contents']);
                    $lockedFound = true;
                }
            }
        }

        $this->assertTrue($titleFound, 'Title field not found in API array');
        $this->assertTrue($lockedFound, 'Locked field not found in API array');
    }

    public function testApiPropertyName(): void
    {
        $dto = new UpdateAnnouncementDTO();

        $reflection = new \ReflectionClass($dto);
        $property = $reflection->getProperty('apiPropertyName');
        $property->setAccessible(true);

        $this->assertEquals('discussion_topic', $property->getValue($dto));
    }

    public function testInheritanceFromUpdateDiscussionTopicDTO(): void
    {
        $dto = new UpdateAnnouncementDTO();

        $this->assertInstanceOf(
            \CanvasLMS\Dto\DiscussionTopics\UpdateDiscussionTopicDTO::class,
            $dto
        );
    }
}
