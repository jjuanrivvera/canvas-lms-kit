<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Groups;

use CanvasLMS\Dto\Groups\CreateGroupMembershipDTO;
use PHPUnit\Framework\TestCase;

class CreateGroupMembershipDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new CreateGroupMembershipDTO([]);

        $this->assertNull($dto->userId);
        $this->assertNull($dto->workflowState);
        $this->assertNull($dto->moderator);
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'user_id' => 123,
            'workflow_state' => 'accepted',
            'moderator' => true,
        ];

        $dto = new CreateGroupMembershipDTO($data);

        $this->assertEquals(123, $dto->userId);
        $this->assertEquals('accepted', $dto->workflowState);
        $this->assertTrue($dto->moderator);
    }

    public function testSettersAndGetters(): void
    {
        $dto = new CreateGroupMembershipDTO([]);

        $dto->userId = 456;
        $this->assertEquals(456, $dto->userId);

        $dto->workflowState = 'invited';
        $this->assertEquals('invited', $dto->workflowState);

        $dto->moderator = false;
        $this->assertFalse($dto->moderator);
    }

    public function testToArray(): void
    {
        $data = [
            'user_id' => 789,
            'workflow_state' => 'requested',
            'moderator' => true,
        ];

        $dto = new CreateGroupMembershipDTO($data);
        $array = $dto->toArray();

        $this->assertEquals([
            'userId' => 789,
            'workflowState' => 'requested',
            'moderator' => true,
        ], $array);
    }

    public function testToApiArray(): void
    {
        $dto = new CreateGroupMembershipDTO([
            'user_id' => 123,
            'workflow_state' => 'accepted',
            'moderator' => true,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(3, $apiArray);

        // Check multipart format
        $userIdField = array_filter($apiArray, fn ($field) => $field['name'] === 'user_id');
        $this->assertCount(1, $userIdField);
        $this->assertEquals('123', reset($userIdField)['contents']);

        $workflowStateField = array_filter($apiArray, fn ($field) => $field['name'] === 'workflow_state');
        $this->assertCount(1, $workflowStateField);
        $this->assertEquals('accepted', reset($workflowStateField)['contents']);

        $moderatorField = array_filter($apiArray, fn ($field) => $field['name'] === 'moderator');
        $this->assertCount(1, $moderatorField);
        $this->assertEquals('true', reset($moderatorField)['contents']);
    }

    public function testToApiArrayExcludesNullValues(): void
    {
        $dto = new CreateGroupMembershipDTO(['user_id' => 456]);

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);
        $this->assertCount(1, $apiArray);
        $this->assertEquals('user_id', $apiArray[0]['name']);
        $this->assertEquals('456', $apiArray[0]['contents']);
    }

    public function testBooleanConversion(): void
    {
        // Test true boolean
        $dto = new CreateGroupMembershipDTO(['moderator' => true]);
        $apiArray = $dto->toApiArray();
        $moderatorField = array_filter($apiArray, fn ($field) => $field['name'] === 'moderator');
        $this->assertEquals('true', reset($moderatorField)['contents']);

        // Test false boolean
        $dto = new CreateGroupMembershipDTO(['moderator' => false]);
        $apiArray = $dto->toApiArray();
        $moderatorField = array_filter($apiArray, fn ($field) => $field['name'] === 'moderator');
        $this->assertEquals('false', reset($moderatorField)['contents']);
    }

    public function testWorkflowStateValues(): void
    {
        $validStates = ['accepted', 'invited', 'requested', 'deleted'];

        foreach ($validStates as $state) {
            $dto = new CreateGroupMembershipDTO(['workflow_state' => $state]);
            $this->assertEquals($state, $dto->workflowState);

            $apiArray = $dto->toApiArray();
            $stateField = array_filter($apiArray, fn ($field) => $field['name'] === 'workflow_state');
            $this->assertEquals($state, reset($stateField)['contents']);
        }
    }

    public function testUserIdConversion(): void
    {
        // Test integer user_id
        $dto = new CreateGroupMembershipDTO(['user_id' => 123]);
        $apiArray = $dto->toApiArray();
        $userIdField = array_filter($apiArray, fn ($field) => $field['name'] === 'user_id');
        $this->assertEquals('123', reset($userIdField)['contents']);

        // Test string user_id
        $dto = new CreateGroupMembershipDTO(['user_id' => '456']);
        $apiArray = $dto->toApiArray();
        $userIdField = array_filter($apiArray, fn ($field) => $field['name'] === 'user_id');
        $this->assertEquals('456', reset($userIdField)['contents']);
    }

    public function testMinimalMembership(): void
    {
        // Only user_id is typically required
        $dto = new CreateGroupMembershipDTO(['user_id' => 789]);

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        $this->assertEquals('user_id', $apiArray[0]['name']);
        $this->assertEquals('789', $apiArray[0]['contents']);
    }

    public function testFullMembership(): void
    {
        $dto = new CreateGroupMembershipDTO([
            'user_id' => 999,
            'workflow_state' => 'invited',
            'moderator' => true,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertCount(3, $apiArray);

        $fieldNames = array_column($apiArray, 'name');
        $this->assertContains('user_id', $fieldNames);
        $this->assertContains('workflow_state', $fieldNames);
        $this->assertContains('moderator', $fieldNames);
    }
}
