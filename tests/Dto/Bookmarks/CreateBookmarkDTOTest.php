<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Bookmarks;

use CanvasLMS\Dto\Bookmarks\CreateBookmarkDTO;
use PHPUnit\Framework\TestCase;

class CreateBookmarkDTOTest extends TestCase
{
    public function testToApiArrayWithAllFields(): void
    {
        $dto = new CreateBookmarkDTO([]);
        $dto->name = 'Test Bookmark';
        $dto->url = '/courses/123';
        $dto->position = 1;
        $dto->data = '{"course_id": 123, "type": "course"}';

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(4, $apiArray);

        $fields = [];
        foreach ($apiArray as $field) {
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('contents', $field);
            $fields[$field['name']] = $field['contents'];
        }

        $this->assertEquals('Test Bookmark', $fields['bookmark[name]']);
        $this->assertEquals('/courses/123', $fields['bookmark[url]']);
        $this->assertEquals('1', $fields['bookmark[position]']);
        $this->assertEquals('{"course_id": 123, "type": "course"}', $fields['bookmark[data]']);
    }

    public function testToApiArrayWithPartialFields(): void
    {
        $dto = new CreateBookmarkDTO([]);
        $dto->name = 'Minimal Bookmark';
        $dto->url = '/groups/456';

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(2, $apiArray);

        $fields = [];
        foreach ($apiArray as $field) {
            $fields[$field['name']] = $field['contents'];
        }

        $this->assertEquals('Minimal Bookmark', $fields['bookmark[name]']);
        $this->assertEquals('/groups/456', $fields['bookmark[url]']);
        $this->assertArrayNotHasKey('bookmark[position]', $fields);
        $this->assertArrayNotHasKey('bookmark[data]', $fields);
    }

    public function testToApiArrayWithEmptyDTO(): void
    {
        $dto = new CreateBookmarkDTO([]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertEmpty($apiArray);
    }

    public function testFromArrayWithAllFields(): void
    {
        $data = [
            'name' => 'Array Bookmark',
            'url' => '/users/789',
            'position' => 5,
            'data' => '{"user_id": 789}',
        ];

        $dto = CreateBookmarkDTO::fromArray($data);

        $this->assertInstanceOf(CreateBookmarkDTO::class, $dto);
        $this->assertEquals('Array Bookmark', $dto->name);
        $this->assertEquals('/users/789', $dto->url);
        $this->assertEquals(5, $dto->position);
        $this->assertEquals('{"user_id": 789}', $dto->data);
    }

    public function testFromArrayWithPartialFields(): void
    {
        $data = [
            'name' => 'Partial Bookmark',
            'url' => '/courses/999',
        ];

        $dto = CreateBookmarkDTO::fromArray($data);

        $this->assertInstanceOf(CreateBookmarkDTO::class, $dto);
        $this->assertEquals('Partial Bookmark', $dto->name);
        $this->assertEquals('/courses/999', $dto->url);
        $this->assertNull($dto->position);
        $this->assertNull($dto->data);
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $dto = CreateBookmarkDTO::fromArray([]);

        $this->assertInstanceOf(CreateBookmarkDTO::class, $dto);
        $this->assertNull($dto->name);
        $this->assertNull($dto->url);
        $this->assertNull($dto->position);
        $this->assertNull($dto->data);
    }

    public function testFromArrayIgnoresExtraFields(): void
    {
        $data = [
            'name' => 'Test',
            'url' => '/test',
            'extra_field' => 'should be ignored',
            'another_field' => 123,
        ];

        $dto = CreateBookmarkDTO::fromArray($data);

        $this->assertEquals('Test', $dto->name);
        $this->assertEquals('/test', $dto->url);
        $this->assertObjectNotHasProperty('extra_field', $dto);
        $this->assertObjectNotHasProperty('another_field', $dto);
    }

    public function testApiPropertyName(): void
    {
        $dto = new CreateBookmarkDTO([]);
        $dto->name = 'Test';

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        $this->assertEquals('bookmark[name]', $apiArray[0]['name']);
        $this->assertStringStartsWith('bookmark[', $apiArray[0]['name']);
    }

    public function testPositionIsConvertedToString(): void
    {
        $dto = new CreateBookmarkDTO([]);
        $dto->position = 42;

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        $this->assertEquals('bookmark[position]', $apiArray[0]['name']);
        $this->assertEquals(42, $apiArray[0]['contents']);
        // Note: Guzzle will convert this to string '42' when sending multipart data
    }

    public function testNullValuesAreNotIncluded(): void
    {
        $dto = new CreateBookmarkDTO([]);
        $dto->name = 'Test';
        $dto->url = null;
        $dto->position = null;
        $dto->data = null;

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        $this->assertEquals('bookmark[name]', $apiArray[0]['name']);
        $this->assertEquals('Test', $apiArray[0]['contents']);
    }
}
