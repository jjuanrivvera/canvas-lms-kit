<?php

declare(strict_types=1);

namespace Tests\Dto\Announcements;

use CanvasLMS\Dto\Announcements\CreateAnnouncementDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Dto\Announcements\CreateAnnouncementDTO
 */
class CreateAnnouncementDTOTest extends TestCase
{
    public function testConstructorSetsAnnouncementDefaults(): void
    {
        $dto = new CreateAnnouncementDTO([
            'title' => 'Test Announcement',
            'message' => 'Test message content',
        ]);

        $this->assertTrue($dto->isAnnouncement);
        $this->assertEquals('side_comment', $dto->discussionType);
        $this->assertFalse($dto->requireInitialPost);
        $this->assertTrue($dto->published);
    }

    public function testConstructorRespectsProvidedValues(): void
    {
        $dto = new CreateAnnouncementDTO([
            'title' => 'Test Announcement',
            'message' => 'Test message',
            'discussionType' => 'flat',
            'published' => false,
            'locked' => true,
        ]);

        $this->assertEquals('Test Announcement', $dto->title);
        $this->assertEquals('Test message', $dto->message);
        $this->assertEquals('flat', $dto->discussionType);
        $this->assertFalse($dto->published);
        $this->assertTrue($dto->locked);
        $this->assertTrue($dto->isAnnouncement);
        $this->assertFalse($dto->requireInitialPost);
    }

    public function testSetDelayedPostAtUnpublishesAnnouncement(): void
    {
        $dto = new CreateAnnouncementDTO([
            'title' => 'Scheduled Announcement',
            'published' => true,
        ]);

        $this->assertTrue($dto->published);

        $dto->setDelayedPostAt('2024-06-01T10:00:00Z');

        $this->assertEquals('2024-06-01T10:00:00Z', $dto->delayedPostAt);
        $this->assertFalse($dto->published);
    }

    public function testSetDelayedPostAtNull(): void
    {
        $dto = new CreateAnnouncementDTO([
            'title' => 'Immediate Announcement',
        ]);

        $dto->setDelayedPostAt(null);

        $this->assertNull($dto->delayedPostAt);
        $this->assertTrue($dto->published);
    }

    public function testLockComments(): void
    {
        $dto = new CreateAnnouncementDTO([
            'title' => 'No Comments Announcement',
        ]);

        $this->assertNull($dto->lockComment);

        $dto->lockComments(true);
        $this->assertTrue($dto->lockComment);

        $dto->lockComments(false);
        $this->assertFalse($dto->lockComment);
    }

    public function testLockCommentsDefaultValue(): void
    {
        $dto = new CreateAnnouncementDTO([
            'title' => 'Locked Comments Announcement',
        ]);

        $dto->lockComments();
        $this->assertTrue($dto->lockComment);
    }

    public function testSetSections(): void
    {
        $dto = new CreateAnnouncementDTO([
            'title' => 'Section-specific Announcement',
        ]);

        $this->assertNull($dto->specificSections);

        $sectionIds = [101, 102, 103];
        $dto->setSections($sectionIds);

        $this->assertEquals($sectionIds, $dto->specificSections);
    }

    public function testGettersAndSetters(): void
    {
        $dto = new CreateAnnouncementDTO();

        $dto->setTitle('Announcement Title');
        $this->assertEquals('Announcement Title', $dto->getTitle());

        $dto->setMessage('Announcement Message');
        $this->assertEquals('Announcement Message', $dto->getMessage());

        $dto->setLocked(true);
        $this->assertTrue($dto->getLocked());

        $dto->setPinned(true);
        $this->assertTrue($dto->getPinned());

        $dto->setPublished(false);
        $this->assertFalse($dto->getPublished());

        $dto->setDelayedPostAt('2024-12-25T00:00:00Z');
        $this->assertEquals('2024-12-25T00:00:00Z', $dto->getDelayedPostAt());

        $dto->setLockAt('2025-01-01T00:00:00Z');
        $this->assertEquals('2025-01-01T00:00:00Z', $dto->getLockAt());
    }

    public function testToApiArray(): void
    {
        $dto = new CreateAnnouncementDTO([
            'title' => 'API Test Announcement',
            'message' => 'Test content',
            'locked' => true,
            'pinned' => false,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);

        // Check for multipart format
        $this->assertArrayHasKey(0, $apiArray);
        $this->assertArrayHasKey('name', $apiArray[0]);
        $this->assertArrayHasKey('contents', $apiArray[0]);

        // Find the title field
        $titleFound = false;
        $isAnnouncementFound = false;
        $requireInitialPostFound = false;

        foreach ($apiArray as $field) {
            if ($field['name'] === 'discussion_topic[title]') {
                $this->assertEquals('API Test Announcement', $field['contents']);
                $titleFound = true;
            }
            if ($field['name'] === 'discussion_topic[is_announcement]') {
                $this->assertEquals('1', $field['contents']);
                $isAnnouncementFound = true;
            }
            if ($field['name'] === 'discussion_topic[require_initial_post]') {
                $this->assertEquals('', $field['contents']);
                $requireInitialPostFound = true;
            }
        }

        $this->assertTrue($titleFound, 'Title field not found in API array');
        $this->assertTrue($isAnnouncementFound, 'is_announcement field not found in API array');
        $this->assertTrue($requireInitialPostFound, 'require_initial_post field not found in API array');
    }

    public function testApiPropertyName(): void
    {
        $dto = new CreateAnnouncementDTO();

        $reflection = new \ReflectionClass($dto);
        $property = $reflection->getProperty('apiPropertyName');
        $property->setAccessible(true);

        $this->assertEquals('discussion_topic', $property->getValue($dto));
    }
}
