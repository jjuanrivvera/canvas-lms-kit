<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\GroupCategories;

use CanvasLMS\Dto\GroupCategories\CreateGroupCategoryDTO;
use PHPUnit\Framework\TestCase;

class CreateGroupCategoryDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new CreateGroupCategoryDTO([]);

        $this->assertNull($dto->name);
        $this->assertNull($dto->selfSignup);
        $this->assertNull($dto->autoLeader);
        $this->assertNull($dto->groupLimit);
        $this->assertNull($dto->sisGroupCategoryId);
        $this->assertNull($dto->createGroupCount);
        $this->assertNull($dto->splitGroupCount);
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'name' => 'Test Category',
            'self_signup' => 'enabled',
            'auto_leader' => 'random',
            'group_limit' => 5,
            'sis_group_category_id' => 'SIS123',
            'create_group_count' => 3,
            'split_group_count' => 'request',
        ];

        $dto = new CreateGroupCategoryDTO($data);

        $this->assertEquals('Test Category', $dto->name);
        $this->assertEquals('enabled', $dto->selfSignup);
        $this->assertEquals('random', $dto->autoLeader);
        $this->assertEquals(5, $dto->groupLimit);
        $this->assertEquals('SIS123', $dto->sisGroupCategoryId);
        $this->assertEquals(3, $dto->createGroupCount);
        $this->assertEquals('request', $dto->splitGroupCount);
    }

    public function testSettersAndGetters(): void
    {
        $dto = new CreateGroupCategoryDTO([]);

        $dto->name = 'Updated Category';
        $this->assertEquals('Updated Category', $dto->name);

        $dto->selfSignup = 'restricted';
        $this->assertEquals('restricted', $dto->selfSignup);

        $dto->autoLeader = 'first';
        $this->assertEquals('first', $dto->autoLeader);

        $dto->groupLimit = 10;
        $this->assertEquals(10, $dto->groupLimit);

        $dto->sisGroupCategoryId = 'NEW_SIS';
        $this->assertEquals('NEW_SIS', $dto->sisGroupCategoryId);

        $dto->createGroupCount = 5;
        $this->assertEquals(5, $dto->createGroupCount);

        $dto->splitGroupCount = 'request';
        $this->assertEquals('request', $dto->splitGroupCount);
    }

    public function testToArray(): void
    {
        $data = [
            'name' => 'Test Category',
            'self_signup' => 'enabled',
            'auto_leader' => 'random',
            'group_limit' => 5,
            'sis_group_category_id' => 'SIS123',
            'create_group_count' => 3,
            'split_group_count' => 'request',
        ];

        $dto = new CreateGroupCategoryDTO($data);
        $array = $dto->toArray();

        $expected = [
            'name' => 'Test Category',
            'selfSignup' => 'enabled',
            'autoLeader' => 'random',
            'groupLimit' => 5,
            'sisGroupCategoryId' => 'SIS123',
            'createGroupCount' => 3,
            'splitGroupCount' => 'request',
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToApiArray(): void
    {
        $dto = new CreateGroupCategoryDTO([
            'name' => 'Test Category',
            'self_signup' => 'enabled',
            'auto_leader' => 'random',
            'group_limit' => 5,
            'sis_group_category_id' => 'SIS123',
            'create_group_count' => 3,
            'split_group_count' => 'request',
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(7, $apiArray);

        // Check multipart format
        $nameField = array_filter($apiArray, fn ($field) => $field['name'] === 'name');
        $this->assertCount(1, $nameField);
        $this->assertEquals('Test Category', reset($nameField)['contents']);

        $selfSignupField = array_filter($apiArray, fn ($field) => $field['name'] === 'self_signup');
        $this->assertCount(1, $selfSignupField);
        $this->assertEquals('enabled', reset($selfSignupField)['contents']);

        $autoLeaderField = array_filter($apiArray, fn ($field) => $field['name'] === 'auto_leader');
        $this->assertCount(1, $autoLeaderField);
        $this->assertEquals('random', reset($autoLeaderField)['contents']);

        $groupLimitField = array_filter($apiArray, fn ($field) => $field['name'] === 'group_limit');
        $this->assertCount(1, $groupLimitField);
        $this->assertEquals('5', reset($groupLimitField)['contents']);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $dto = new CreateGroupCategoryDTO(['name' => 'Test Category']);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(1, $apiArray);
        $this->assertEquals('name', $apiArray[0]['name']);
        $this->assertEquals('Test Category', $apiArray[0]['contents']);
    }

    public function testSelfSignupValues(): void
    {
        $validValues = ['enabled', 'restricted', null];

        foreach ($validValues as $value) {
            $dto = new CreateGroupCategoryDTO(['self_signup' => $value]);
            $this->assertEquals($value, $dto->selfSignup);
        }
    }

    public function testAutoLeaderValues(): void
    {
        $validValues = ['first', 'random', null];

        foreach ($validValues as $value) {
            $dto = new CreateGroupCategoryDTO(['auto_leader' => $value]);
            $this->assertEquals($value, $dto->autoLeader);
        }
    }

    public function testNumericFieldConversion(): void
    {
        $dto = new CreateGroupCategoryDTO([
            'group_limit' => '10',
            'create_group_count' => '5',
        ]);

        $apiArray = $dto->toApiArray();

        $groupLimitField = array_filter($apiArray, fn ($field) => $field['name'] === 'group_limit');
        $this->assertEquals('10', reset($groupLimitField)['contents']);

        $createGroupCountField = array_filter($apiArray, fn ($field) => $field['name'] === 'create_group_count');
        $this->assertEquals('5', reset($createGroupCountField)['contents']);
    }

    public function testSnakeCaseConversion(): void
    {
        $dto = new CreateGroupCategoryDTO([
            'self_signup' => 'enabled',
            'auto_leader' => 'first',
            'group_limit' => 5,
            'sis_group_category_id' => 'SIS123',
            'create_group_count' => 3,
            'split_group_count' => 'request',
        ]);

        $apiArray = $dto->toApiArray();

        $fieldNames = array_column($apiArray, 'name');

        $this->assertContains('self_signup', $fieldNames);
        $this->assertContains('auto_leader', $fieldNames);
        $this->assertContains('group_limit', $fieldNames);
        $this->assertContains('sis_group_category_id', $fieldNames);
        $this->assertContains('create_group_count', $fieldNames);
        $this->assertContains('split_group_count', $fieldNames);
    }
}
