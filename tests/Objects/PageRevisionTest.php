<?php

declare(strict_types=1);

namespace Tests\Objects;

use CanvasLMS\Objects\PageRevision;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Objects\PageRevision
 */
class PageRevisionTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $revision = new PageRevision();

        $this->assertNull($revision->getRevisionId());
        $this->assertNull($revision->getUpdatedAt());
        $this->assertNull($revision->getLatest());
        $this->assertNull($revision->getEditedBy());
        $this->assertNull($revision->getUrl());
        $this->assertNull($revision->getTitle());
        $this->assertNull($revision->getBody());
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'revision_id' => 7,
            'updated_at' => '2024-01-15T10:30:00Z',
            'latest' => true,
            'edited_by' => ['id' => 123, 'name' => 'John Doe'],
            'url' => 'old-page-title',
            'title' => 'Old Page Title',
            'body' => '<p>Old Page Content</p>',
        ];

        $revision = new PageRevision($data);

        $this->assertEquals(7, $revision->getRevisionId());
        $this->assertEquals('2024-01-15T10:30:00Z', $revision->getUpdatedAt());
        $this->assertTrue($revision->getLatest());
        $this->assertEquals(['id' => 123, 'name' => 'John Doe'], $revision->getEditedBy());
        $this->assertEquals('old-page-title', $revision->getUrl());
        $this->assertEquals('Old Page Title', $revision->getTitle());
        $this->assertEquals('<p>Old Page Content</p>', $revision->getBody());
    }

    public function testGettersAndSetters(): void
    {
        $revision = new PageRevision();

        $revision->setRevisionId(10);
        $this->assertEquals(10, $revision->getRevisionId());

        $revision->setUpdatedAt('2024-01-20T15:00:00Z');
        $this->assertEquals('2024-01-20T15:00:00Z', $revision->getUpdatedAt());

        $revision->setLatest(false);
        $this->assertFalse($revision->getLatest());

        $editedBy = ['id' => 456, 'name' => 'Jane Smith', 'avatar_url' => 'https://example.com/avatar.jpg'];
        $revision->setEditedBy($editedBy);
        $this->assertEquals($editedBy, $revision->getEditedBy());

        $revision->setUrl('updated-page-url');
        $this->assertEquals('updated-page-url', $revision->getUrl());

        $revision->setTitle('Updated Page Title');
        $this->assertEquals('Updated Page Title', $revision->getTitle());

        $revision->setBody('<h1>Updated Content</h1>');
        $this->assertEquals('<h1>Updated Content</h1>', $revision->getBody());
    }

    public function testIsLatest(): void
    {
        $revision = new PageRevision();

        $revision->setLatest(true);
        $this->assertTrue($revision->isLatest());

        $revision->setLatest(false);
        $this->assertFalse($revision->isLatest());

        $revision->setLatest(null);
        $this->assertFalse($revision->isLatest());
    }

    public function testToArray(): void
    {
        $data = [
            'revision_id' => 15,
            'updated_at' => '2024-01-25T12:00:00Z',
            'latest' => false,
            'edited_by' => ['id' => 789, 'name' => 'Test User'],
            'url' => 'test-page',
            'title' => 'Test Page',
            'body' => '<p>Test Content</p>',
        ];

        $revision = new PageRevision($data);
        $array = $revision->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(15, $array['revision_id']);
        $this->assertEquals('2024-01-25T12:00:00Z', $array['updated_at']);
        $this->assertFalse($array['latest']);
        $this->assertEquals(['id' => 789, 'name' => 'Test User'], $array['edited_by']);
        $this->assertEquals('test-page', $array['url']);
        $this->assertEquals('Test Page', $array['title']);
        $this->assertEquals('<p>Test Content</p>', $array['body']);
    }

    public function testSnakeCaseToCamelCaseConversion(): void
    {
        $data = [
            'revision_id' => 20,
            'updated_at' => '2024-02-01T08:00:00Z',
            'edited_by' => ['id' => 111],
        ];

        $revision = new PageRevision($data);

        $this->assertEquals(20, $revision->revisionId);
        $this->assertEquals('2024-02-01T08:00:00Z', $revision->updatedAt);
        $this->assertEquals(['id' => 111], $revision->editedBy);
    }

    public function testSettersWithNull(): void
    {
        $revision = new PageRevision([
            'revision_id' => 25,
            'title' => 'Initial Title',
            'body' => 'Initial Body',
        ]);

        $revision->setRevisionId(null);
        $revision->setTitle(null);
        $revision->setBody(null);

        $this->assertNull($revision->getRevisionId());
        $this->assertNull($revision->getTitle());
        $this->assertNull($revision->getBody());
    }

    public function testSummaryRevision(): void
    {
        // When summary=true is used, body might be excluded
        $data = [
            'revision_id' => 30,
            'updated_at' => '2024-02-05T10:00:00Z',
            'latest' => true,
            'url' => 'page-url',
            'title' => 'Page Title',
            // Note: body is not included
        ];

        $revision = new PageRevision($data);

        $this->assertEquals(30, $revision->getRevisionId());
        $this->assertEquals('Page Title', $revision->getTitle());
        $this->assertNull($revision->getBody());
    }
}
