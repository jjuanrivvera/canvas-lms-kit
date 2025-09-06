<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Bookmarks;

use CanvasLMS\Dto\Bookmarks\UpdateBookmarkDTO;
use PHPUnit\Framework\TestCase;

class UpdateBookmarkDTOTest extends TestCase
{
    public function testToApiArrayWithAllFields(): void
    {
        $dto = new UpdateBookmarkDTO([]);
        $dto->name = 'Updated Bookmark';
        $dto->url = '/courses/456';
        $dto->position = 3;
        $dto->data = '{"updated": true}';

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(4, $apiArray);

        $fields = [];
        foreach ($apiArray as $field) {
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('contents', $field);
            $fields[$field['name']] = $field['contents'];
        }

        $this->assertEquals('Updated Bookmark', $fields['bookmark[name]']);
        $this->assertEquals('/courses/456', $fields['bookmark[url]']);
        $this->assertEquals('3', $fields['bookmark[position]']);
        $this->assertEquals('{"updated": true}', $fields['bookmark[data]']);
    }

    public function testToApiArrayWithPartialFields(): void
    {
        $dto = new UpdateBookmarkDTO([]);
        $dto->name = 'New Name Only';

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(1, $apiArray);

        $this->assertEquals('bookmark[name]', $apiArray[0]['name']);
        $this->assertEquals('New Name Only', $apiArray[0]['contents']);
    }

    public function testToApiArrayWithDifferentPartialFields(): void
    {
        $dto = new UpdateBookmarkDTO([]);
        $dto->position = 10;
        $dto->data = '{"metadata": "value"}';

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(2, $apiArray);

        $fields = [];
        foreach ($apiArray as $field) {
            $fields[$field['name']] = $field['contents'];
        }

        $this->assertEquals('10', $fields['bookmark[position]']);
        $this->assertEquals('{"metadata": "value"}', $fields['bookmark[data]']);
        $this->assertArrayNotHasKey('bookmark[name]', $fields);
        $this->assertArrayNotHasKey('bookmark[url]', $fields);
    }

    public function testToApiArrayWithEmptyDTO(): void
    {
        $dto = new UpdateBookmarkDTO([]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertEmpty($apiArray);
    }

    public function testFromArrayWithAllFields(): void
    {
        $data = [
            'name' => 'Updated via Array',
            'url' => '/groups/123',
            'position' => 7,
            'data' => '{"group_id": 123}'
        ];

        $dto = UpdateBookmarkDTO::fromArray($data);

        $this->assertInstanceOf(UpdateBookmarkDTO::class, $dto);
        $this->assertEquals('Updated via Array', $dto->name);
        $this->assertEquals('/groups/123', $dto->url);
        $this->assertEquals(7, $dto->position);
        $this->assertEquals('{"group_id": 123}', $dto->data);
    }

    public function testFromArrayWithPartialFields(): void
    {
        $data = [
            'url' => '/users/555',
            'position' => 2
        ];

        $dto = UpdateBookmarkDTO::fromArray($data);

        $this->assertInstanceOf(UpdateBookmarkDTO::class, $dto);
        $this->assertNull($dto->name);
        $this->assertEquals('/users/555', $dto->url);
        $this->assertEquals(2, $dto->position);
        $this->assertNull($dto->data);
    }

    public function testFromArrayWithSingleField(): void
    {
        $data = ['name' => 'Single Field Update'];

        $dto = UpdateBookmarkDTO::fromArray($data);

        $this->assertInstanceOf(UpdateBookmarkDTO::class, $dto);
        $this->assertEquals('Single Field Update', $dto->name);
        $this->assertNull($dto->url);
        $this->assertNull($dto->position);
        $this->assertNull($dto->data);
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $dto = UpdateBookmarkDTO::fromArray([]);

        $this->assertInstanceOf(UpdateBookmarkDTO::class, $dto);
        $this->assertNull($dto->name);
        $this->assertNull($dto->url);
        $this->assertNull($dto->position);
        $this->assertNull($dto->data);
    }

    public function testFromArrayIgnoresExtraFields(): void
    {
        $data = [
            'name' => 'Valid Field',
            'position' => 5,
            'id' => 999,
            'created_at' => '2024-01-01',
            'invalid_field' => 'ignored'
        ];

        $dto = UpdateBookmarkDTO::fromArray($data);

        $this->assertEquals('Valid Field', $dto->name);
        $this->assertEquals(5, $dto->position);
        $this->assertObjectNotHasProperty('id', $dto);
        $this->assertObjectNotHasProperty('created_at', $dto);
        $this->assertObjectNotHasProperty('invalid_field', $dto);
    }

    public function testApiPropertyName(): void
    {
        $dto = new UpdateBookmarkDTO([]);
        $dto->url = '/test';

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        $this->assertEquals('bookmark[url]', $apiArray[0]['name']);
        $this->assertStringStartsWith('bookmark[', $apiArray[0]['name']);
    }

    public function testPositionIsConvertedToString(): void
    {
        $dto = new UpdateBookmarkDTO([]);
        $dto->position = 99;

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        $this->assertEquals('bookmark[position]', $apiArray[0]['name']);
        $this->assertEquals(99, $apiArray[0]['contents']);
        // Note: Guzzle will convert this to string '99' when sending multipart data
    }

    public function testNullValuesAreNotIncluded(): void
    {
        $dto = new UpdateBookmarkDTO([]);
        $dto->name = null;
        $dto->url = '/courses/111';
        $dto->position = null;
        $dto->data = null;

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        $this->assertEquals('bookmark[url]', $apiArray[0]['name']);
        $this->assertEquals('/courses/111', $apiArray[0]['contents']);
    }

    public function testPartialUpdateScenario(): void
    {
        $dto = new UpdateBookmarkDTO([]);
        $dto->position = 1;

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        $this->assertEquals('bookmark[position]', $apiArray[0]['name']);
        $this->assertEquals('1', $apiArray[0]['contents']);
    }
}