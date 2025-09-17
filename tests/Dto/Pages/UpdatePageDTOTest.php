<?php

declare(strict_types=1);

namespace Tests\Dto\Pages;

use CanvasLMS\Dto\Pages\UpdatePageDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Dto\Pages\UpdatePageDTO
 */
class UpdatePageDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new UpdatePageDTO([]);

        $this->assertNull($dto->getTitle());
        $this->assertNull($dto->getBody());
        $this->assertNull($dto->getPublished());
        $this->assertNull($dto->getFrontPage());
        $this->assertNull($dto->getEditingRoles());
        $this->assertNull($dto->getNotifyOfUpdate());
        $this->assertNull($dto->getPublishAt());
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'title' => 'Updated Page',
            'body' => '<p>Updated content</p>',
            'published' => false,
            'front_page' => true,
            'editing_roles' => 'students',
            'notify_of_update' => false,
            'publish_at' => '2024-02-15T12:00:00Z',
        ];

        $dto = new UpdatePageDTO($data);

        $this->assertEquals('Updated Page', $dto->getTitle());
        $this->assertEquals('<p>Updated content</p>', $dto->getBody());
        $this->assertFalse($dto->getPublished());
        $this->assertTrue($dto->getFrontPage());
        $this->assertEquals('students', $dto->getEditingRoles());
        $this->assertFalse($dto->getNotifyOfUpdate());
        $this->assertEquals('2024-02-15T12:00:00Z', $dto->getPublishAt());
    }

    public function testSettersAndGetters(): void
    {
        $dto = new UpdatePageDTO([]);

        $dto->setTitle('Updated Title');
        $this->assertEquals('Updated Title', $dto->getTitle());

        $dto->setBody('<h1>Updated Content</h1>');
        $this->assertEquals('<h1>Updated Content</h1>', $dto->getBody());

        $dto->setPublished(false);
        $this->assertFalse($dto->getPublished());

        $dto->setFrontPage(false);
        $this->assertFalse($dto->getFrontPage());

        $dto->setEditingRoles('members');
        $this->assertEquals('members', $dto->getEditingRoles());

        $dto->setNotifyOfUpdate(true);
        $this->assertTrue($dto->getNotifyOfUpdate());

        $dto->setPublishAt('2024-04-01T08:00:00Z');
        $this->assertEquals('2024-04-01T08:00:00Z', $dto->getPublishAt());
    }

    public function testToArray(): void
    {
        $data = [
            'title' => 'Updated Page',
            'body' => '<p>Updated content</p>',
            'published' => true,
            'front_page' => false,
            'editing_roles' => 'public',
            'notify_of_update' => true,
        ];

        $dto = new UpdatePageDTO($data);
        $result = $dto->toArray();

        $this->assertIsArray($result);
        $this->assertEquals('Updated Page', $result['title']);
        $this->assertEquals('<p>Updated content</p>', $result['body']);
        $this->assertTrue($result['published']);
        $this->assertFalse($result['frontPage']);
        $this->assertEquals('public', $result['editingRoles']);
        $this->assertTrue($result['notifyOfUpdate']);
    }

    public function testToApiArray(): void
    {
        $data = [
            'title' => 'Updated Page',
            'body' => '<p>Updated content</p>',
            'published' => true,
            'editing_roles' => 'teachers',
        ];

        $dto = new UpdatePageDTO($data);
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $resultKeys = array_column($result, 'name');
        $this->assertContains('wiki_page[title]', $resultKeys);
        $this->assertContains('wiki_page[body]', $resultKeys);
        $this->assertContains('wiki_page[published]', $resultKeys);
        $this->assertContains('wiki_page[editing_roles]', $resultKeys);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $data = [
            'title' => 'Updated Title Only',
            'body' => null,
            'published' => null,
            'front_page' => null,
            'editing_roles' => null,
        ];

        $dto = new UpdatePageDTO($data);
        $result = $dto->toApiArray();

        $resultKeys = array_column($result, 'name');
        $this->assertContains('wiki_page[title]', $resultKeys);
        $this->assertNotContains('wiki_page[body]', $resultKeys);
        $this->assertNotContains('wiki_page[published]', $resultKeys);
        $this->assertNotContains('wiki_page[front_page]', $resultKeys);
        $this->assertNotContains('wiki_page[editing_roles]', $resultKeys);
    }

    public function testToApiArrayHandlesBooleanValues(): void
    {
        $dto = new UpdatePageDTO([
            'published' => false,
            'front_page' => true,
            'notify_of_update' => false,
        ]);

        $result = $dto->toApiArray();

        $valueMap = [];
        foreach ($result as $item) {
            $valueMap[$item['name']] = $item['contents'];
        }

        $this->assertEquals(false, $valueMap['wiki_page[published]']);
        $this->assertEquals(true, $valueMap['wiki_page[front_page]']);
        $this->assertEquals(false, $valueMap['wiki_page[notify_of_update]']);
    }

    public function testApiPropertyName(): void
    {
        $dto = new UpdatePageDTO([]);

        $reflection = new \ReflectionClass($dto);
        $property = $reflection->getProperty('apiPropertyName');
        $property->setAccessible(true);

        $this->assertEquals('wiki_page', $property->getValue($dto));
    }

    public function testPartialUpdate(): void
    {
        $dto = new UpdatePageDTO([
            'body' => '<p>Only updating the body</p>',
        ]);

        $this->assertNull($dto->getTitle());
        $this->assertEquals('<p>Only updating the body</p>', $dto->getBody());
        $this->assertNull($dto->getPublished());

        $result = $dto->toApiArray();
        $resultKeys = array_column($result, 'name');

        $this->assertContains('wiki_page[body]', $resultKeys);
        $this->assertNotContains('wiki_page[title]', $resultKeys);
        $this->assertNotContains('wiki_page[published]', $resultKeys);
    }

    public function testEditingRolesValues(): void
    {
        $validRoles = ['teachers', 'students', 'members', 'public'];

        foreach ($validRoles as $role) {
            $dto = new UpdatePageDTO(['editing_roles' => $role]);
            $this->assertEquals($role, $dto->getEditingRoles());
        }
    }

    public function testAllFieldsNull(): void
    {
        $dto = new UpdatePageDTO([]);
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
