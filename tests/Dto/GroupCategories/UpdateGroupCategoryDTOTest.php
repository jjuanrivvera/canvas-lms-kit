<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\GroupCategories;

use CanvasLMS\Dto\GroupCategories\UpdateGroupCategoryDTO;
use PHPUnit\Framework\TestCase;

class UpdateGroupCategoryDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new UpdateGroupCategoryDTO([]);

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
            'name' => 'Updated Category',
            'self_signup' => 'disabled',
            'auto_leader' => 'first',
            'group_limit' => 8,
            'sis_group_category_id' => 'UPDATED_SIS',
            'create_group_count' => 4,
            'split_group_count' => 'request',
        ];

        $dto = new UpdateGroupCategoryDTO($data);

        $this->assertEquals('Updated Category', $dto->name);
        $this->assertEquals('disabled', $dto->selfSignup);
        $this->assertEquals('first', $dto->autoLeader);
        $this->assertEquals(8, $dto->groupLimit);
        $this->assertEquals('UPDATED_SIS', $dto->sisGroupCategoryId);
        $this->assertEquals(4, $dto->createGroupCount);
        $this->assertEquals('request', $dto->splitGroupCount);
    }

    public function testSettersAndGetters(): void
    {
        $dto = new UpdateGroupCategoryDTO([]);

        $dto->name = 'New Name';
        $this->assertEquals('New Name', $dto->name);

        $dto->selfSignup = 'enabled';
        $this->assertEquals('enabled', $dto->selfSignup);

        $dto->autoLeader = 'random';
        $this->assertEquals('random', $dto->autoLeader);

        $dto->groupLimit = 15;
        $this->assertEquals(15, $dto->groupLimit);

        $dto->sisGroupCategoryId = 'NEW_ID';
        $this->assertEquals('NEW_ID', $dto->sisGroupCategoryId);

        $dto->createGroupCount = 6;
        $this->assertEquals(6, $dto->createGroupCount);

        $dto->splitGroupCount = 'request';
        $this->assertEquals('request', $dto->splitGroupCount);
    }

    public function testToArray(): void
    {
        $data = [
            'name' => 'Updated Category',
            'self_signup' => 'restricted',
            'auto_leader' => 'first',
            'group_limit' => 10,
        ];

        $dto = new UpdateGroupCategoryDTO($data);
        $array = $dto->toArray();

        $this->assertEquals($data['name'], $array['name']);
        $this->assertEquals($data['self_signup'], $array['selfSignup']);
        $this->assertEquals($data['auto_leader'], $array['autoLeader']);
        $this->assertEquals($data['group_limit'], $array['groupLimit']);
    }

    public function testToApiArray(): void
    {
        $dto = new UpdateGroupCategoryDTO([
            'name' => 'Updated Category',
            'self_signup' => 'restricted',
            'auto_leader' => 'first',
            'group_limit' => 10,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(4, $apiArray);

        // Check multipart format
        $nameField = array_filter($apiArray, fn ($field) => $field['name'] === 'name');
        $this->assertCount(1, $nameField);
        $this->assertEquals('Updated Category', reset($nameField)['contents']);

        $selfSignupField = array_filter($apiArray, fn ($field) => $field['name'] === 'self_signup');
        $this->assertCount(1, $selfSignupField);
        $this->assertEquals('restricted', reset($selfSignupField)['contents']);

        $autoLeaderField = array_filter($apiArray, fn ($field) => $field['name'] === 'auto_leader');
        $this->assertCount(1, $autoLeaderField);
        $this->assertEquals('first', reset($autoLeaderField)['contents']);

        $groupLimitField = array_filter($apiArray, fn ($field) => $field['name'] === 'group_limit');
        $this->assertCount(1, $groupLimitField);
        $this->assertEquals('10', reset($groupLimitField)['contents']);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $dto = new UpdateGroupCategoryDTO(['name' => 'Updated Name']);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(1, $apiArray);
        $this->assertEquals('name', $apiArray[0]['name']);
        $this->assertEquals('Updated Name', $apiArray[0]['contents']);
    }

    public function testPartialUpdate(): void
    {
        // Update DTOs should allow partial updates
        $dto = new UpdateGroupCategoryDTO([
            'name' => 'Only Update Name',
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        $this->assertEquals('name', $apiArray[0]['name']);
        $this->assertEquals('Only Update Name', $apiArray[0]['contents']);
    }

    public function testSelfSignupDisabled(): void
    {
        $dto = new UpdateGroupCategoryDTO(['self_signup' => 'disabled']);

        $apiArray = $dto->toApiArray();

        $selfSignupField = array_filter($apiArray, fn ($field) => $field['name'] === 'self_signup');
        $this->assertEquals('disabled', reset($selfSignupField)['contents']);
    }

    public function testRemoveGroupLimit(): void
    {
        // Setting group_limit to 0 removes the limit
        $dto = new UpdateGroupCategoryDTO(['group_limit' => 0]);

        $apiArray = $dto->toApiArray();

        $groupLimitField = array_filter($apiArray, fn ($field) => $field['name'] === 'group_limit');
        $this->assertEquals('0', reset($groupLimitField)['contents']);
    }

    public function testAllFieldsUpdate(): void
    {
        $dto = new UpdateGroupCategoryDTO([
            'name' => 'Complete Update',
            'self_signup' => 'enabled',
            'auto_leader' => 'random',
            'group_limit' => 20,
            'sis_group_category_id' => 'COMPLETE_SIS',
            'create_group_count' => 10,
            'split_group_count' => 'request',
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertCount(7, $apiArray);

        $fieldNames = array_column($apiArray, 'name');
        $this->assertContains('name', $fieldNames);
        $this->assertContains('self_signup', $fieldNames);
        $this->assertContains('auto_leader', $fieldNames);
        $this->assertContains('group_limit', $fieldNames);
        $this->assertContains('sis_group_category_id', $fieldNames);
        $this->assertContains('create_group_count', $fieldNames);
        $this->assertContains('split_group_count', $fieldNames);
    }
}
