<?php

declare(strict_types=1);

namespace Tests\Dto\ContentMigrations;

use CanvasLMS\Dto\ContentMigrations\UpdateMigrationIssueDTO;
use PHPUnit\Framework\TestCase;

class UpdateMigrationIssueDTOTest extends TestCase
{
    public function testBasicUpdate(): void
    {
        $dto = new UpdateMigrationIssueDTO(['workflow_state' => 'resolved']);

        $apiArray = $dto->toApiArray();

        $this->assertCount(1, $apiArray);
        $this->assertEquals(['name' => 'workflow_state', 'contents' => 'resolved'], $apiArray[0]);
    }

    public function testSetResolved(): void
    {
        $dto = new UpdateMigrationIssueDTO();
        $dto->setResolved();

        $this->assertEquals('resolved', $dto->getWorkflowState());

        $apiArray = $dto->toApiArray();
        $this->assertContains(['name' => 'workflow_state', 'contents' => 'resolved'], $apiArray);
    }

    public function testSetActive(): void
    {
        $dto = new UpdateMigrationIssueDTO();
        $dto->setActive();

        $this->assertEquals('active', $dto->getWorkflowState());

        $apiArray = $dto->toApiArray();
        $this->assertContains(['name' => 'workflow_state', 'contents' => 'active'], $apiArray);
    }

    public function testValidation(): void
    {
        // Empty workflow state should fail
        $dto = new UpdateMigrationIssueDTO();
        $this->assertFalse($dto->validate());

        // Invalid workflow state should fail
        $dto = new UpdateMigrationIssueDTO(['workflow_state' => 'invalid']);
        $this->assertFalse($dto->validate());

        // Valid workflow states should pass
        $dto = new UpdateMigrationIssueDTO(['workflow_state' => 'active']);
        $this->assertTrue($dto->validate());

        $dto = new UpdateMigrationIssueDTO(['workflow_state' => 'resolved']);
        $this->assertTrue($dto->validate());
    }

    public function testSetterAndGetter(): void
    {
        $dto = new UpdateMigrationIssueDTO();
        
        $dto->setWorkflowState('resolved');
        $this->assertEquals('resolved', $dto->getWorkflowState());

        $dto->setWorkflowState('active');
        $this->assertEquals('active', $dto->getWorkflowState());
    }
}