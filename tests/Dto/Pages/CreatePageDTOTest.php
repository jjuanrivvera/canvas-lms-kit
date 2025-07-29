<?php

namespace Tests\Dto\Pages;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Pages\CreatePageDTO;

/**
 * @covers \CanvasLMS\Dto\Pages\CreatePageDTO
 */
class CreatePageDTOTest extends TestCase
{
    public function testConstructorWithEmptyDataThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page title is required');

        new CreatePageDTO([]);
    }

    public function testConstructorWithEmptyTitleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page title is required');

        new CreatePageDTO(['title' => '']);
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'title' => 'Test Page',
            'body' => '<p>Test content</p>',
            'published' => true,
            'front_page' => false,
            'editing_roles' => 'teachers',
            'notify_of_update' => true,
            'publish_at' => '2024-02-01T00:00:00Z'
        ];

        $dto = new CreatePageDTO($data);

        $this->assertEquals('Test Page', $dto->getTitle());
        $this->assertEquals('<p>Test content</p>', $dto->getBody());
        $this->assertTrue($dto->getPublished());
        $this->assertFalse($dto->getFrontPage());
        $this->assertEquals('teachers', $dto->getEditingRoles());
        $this->assertTrue($dto->getNotifyOfUpdate());
        $this->assertEquals('2024-02-01T00:00:00Z', $dto->getPublishAt());
    }

    public function testSettersAndGetters(): void
    {
        $dto = new CreatePageDTO(['title' => 'Initial Title']);

        $dto->setTitle('Test Page');
        $this->assertEquals('Test Page', $dto->getTitle());

        $dto->setBody('<h1>Page Content</h1>');
        $this->assertEquals('<h1>Page Content</h1>', $dto->getBody());

        $dto->setPublished(true);
        $this->assertTrue($dto->getPublished());

        $dto->setFrontPage(true);
        $this->assertTrue($dto->getFrontPage());

        $dto->setEditingRoles('students');
        $this->assertEquals('students', $dto->getEditingRoles());

        $dto->setNotifyOfUpdate(false);
        $this->assertFalse($dto->getNotifyOfUpdate());

        $dto->setPublishAt('2024-03-01T00:00:00Z');
        $this->assertEquals('2024-03-01T00:00:00Z', $dto->getPublishAt());
    }

    public function testToArray(): void
    {
        $data = [
            'title' => 'Test Page',
            'body' => '<p>Test content</p>',
            'published' => true,
            'front_page' => false,
            'editing_roles' => 'teachers',
            'notify_of_update' => true
        ];

        $dto = new CreatePageDTO($data);
        $result = $dto->toArray();

        $this->assertIsArray($result);
        $this->assertEquals('Test Page', $result['title']);
        $this->assertEquals('<p>Test content</p>', $result['body']);
        $this->assertTrue($result['published']);
        $this->assertFalse($result['frontPage']);
        $this->assertEquals('teachers', $result['editingRoles']);
        $this->assertTrue($result['notifyOfUpdate']);
    }

    public function testToApiArray(): void
    {
        $data = [
            'title' => 'Test Page',
            'body' => '<p>Test content</p>',
            'published' => true,
            'front_page' => false,
            'editing_roles' => 'teachers'
        ];

        $dto = new CreatePageDTO($data);
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $resultKeys = array_column($result, 'name');
        $this->assertContains('wiki_page[title]', $resultKeys);
        $this->assertContains('wiki_page[body]', $resultKeys);
        $this->assertContains('wiki_page[published]', $resultKeys);
        $this->assertContains('wiki_page[front_page]', $resultKeys);
        $this->assertContains('wiki_page[editing_roles]', $resultKeys);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $data = [
            'title' => 'Test Page',
            'body' => 'Some content',
            'published' => null,
            'front_page' => null,
            'editing_roles' => 'teachers'
        ];

        $dto = new CreatePageDTO($data);
        $result = $dto->toApiArray();

        $resultKeys = array_column($result, 'name');
        $this->assertContains('wiki_page[title]', $resultKeys);
        $this->assertContains('wiki_page[body]', $resultKeys);
        $this->assertNotContains('wiki_page[published]', $resultKeys);
        $this->assertNotContains('wiki_page[front_page]', $resultKeys);
        $this->assertContains('wiki_page[editing_roles]', $resultKeys);
    }

    public function testToApiArrayHandlesBooleanValues(): void
    {
        $dto = new CreatePageDTO([
            'title' => 'Test Page',
            'body' => 'Content',
            'published' => true,
            'front_page' => false,
            'notify_of_update' => true
        ]);

        $result = $dto->toApiArray();

        $valueMap = [];
        foreach ($result as $item) {
            $valueMap[$item['name']] = $item['contents'];
        }

        $this->assertEquals(true, $valueMap['wiki_page[published]']);
        $this->assertEquals(false, $valueMap['wiki_page[front_page]']);
        $this->assertEquals(true, $valueMap['wiki_page[notify_of_update]']);
    }

    public function testApiPropertyName(): void
    {
        $dto = new CreatePageDTO(['title' => 'Test']);
        
        $reflection = new \ReflectionClass($dto);
        $property = $reflection->getProperty('apiPropertyName');
        $property->setAccessible(true);
        
        $this->assertEquals('wiki_page', $property->getValue($dto));
    }

    public function testMinimalRequiredData(): void
    {
        $dto = new CreatePageDTO([
            'title' => 'Minimal Page'
        ]);

        $this->assertEquals('Minimal Page', $dto->getTitle());
        $this->assertEquals('', $dto->getBody());
        
        $result = $dto->toApiArray();
        $resultKeys = array_column($result, 'name');
        
        $this->assertContains('wiki_page[title]', $resultKeys);
        $this->assertContains('wiki_page[body]', $resultKeys);
    }

    public function testEditingRolesValues(): void
    {
        $validRoles = ['teachers', 'students', 'members', 'public'];
        
        foreach ($validRoles as $role) {
            $dto = new CreatePageDTO(['title' => 'Test', 'editing_roles' => $role]);
            $this->assertEquals($role, $dto->getEditingRoles());
        }
    }
}