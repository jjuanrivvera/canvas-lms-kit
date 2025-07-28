<?php

declare(strict_types=1);

namespace Tests\Dto\Modules;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Modules\BulkUpdateModuleAssignmentOverridesDTO;

class BulkUpdateModuleAssignmentOverridesDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $this->assertEquals([], $dto->getOverrides());
    }

    public function testConstructorWithData(): void
    {
        $overrides = [
            ['course_section_id' => 123],
            ['student_ids' => [456, 789]]
        ];
        
        $dto = new BulkUpdateModuleAssignmentOverridesDTO(['overrides' => $overrides]);
        
        $this->assertEquals($overrides, $dto->getOverrides());
    }

    public function testAddOverride(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $override = ['course_section_id' => 123, 'title' => 'Section A'];
        $dto->addOverride($override);
        
        $this->assertCount(1, $dto->getOverrides());
        $this->assertEquals($override, $dto->getOverrides()[0]);
    }

    public function testAddSectionOverride(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $dto->addSectionOverride(123);
        
        $overrides = $dto->getOverrides();
        $this->assertCount(1, $overrides);
        $this->assertEquals(['course_section_id' => 123], $overrides[0]);
    }

    public function testAddSectionOverrideWithIdAndTitle(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $dto->addSectionOverride(123, 456, 'Section A');
        
        $overrides = $dto->getOverrides();
        $this->assertCount(1, $overrides);
        $this->assertEquals([
            'course_section_id' => 123,
            'id' => 456,
            'title' => 'Section A'
        ], $overrides[0]);
    }

    public function testAddStudentOverride(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $dto->addStudentOverride([456, 789]);
        
        $overrides = $dto->getOverrides();
        $this->assertCount(1, $overrides);
        $this->assertEquals(['student_ids' => [456, 789]], $overrides[0]);
    }

    public function testAddStudentOverrideWithIdAndTitle(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $dto->addStudentOverride([456, 789], 999, 'Special Students');
        
        $overrides = $dto->getOverrides();
        $this->assertCount(1, $overrides);
        $this->assertEquals([
            'student_ids' => [456, 789],
            'id' => 999,
            'title' => 'Special Students'
        ], $overrides[0]);
    }

    public function testAddGroupOverride(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $dto->addGroupOverride(321);
        
        $overrides = $dto->getOverrides();
        $this->assertCount(1, $overrides);
        $this->assertEquals(['group_id' => 321], $overrides[0]);
    }

    public function testAddGroupOverrideWithIdAndTitle(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $dto->addGroupOverride(321, 654, 'Group Project Team');
        
        $overrides = $dto->getOverrides();
        $this->assertCount(1, $overrides);
        $this->assertEquals([
            'group_id' => 321,
            'id' => 654,
            'title' => 'Group Project Team'
        ], $overrides[0]);
    }

    public function testMethodChaining(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $result = $dto->addSectionOverride(123)
            ->addStudentOverride([456, 789])
            ->addGroupOverride(321);
        
        $this->assertSame($dto, $result);
        $this->assertCount(3, $dto->getOverrides());
    }

    public function testClearOverrides(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $dto->addSectionOverride(123)
            ->addStudentOverride([456, 789])
            ->clearOverrides();
        
        $this->assertEquals([], $dto->getOverrides());
    }

    public function testSetOverrides(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $overrides = [
            ['course_section_id' => 123],
            ['student_ids' => [456, 789]],
            ['group_id' => 321]
        ];
        
        $dto->setOverrides($overrides);
        
        $this->assertEquals($overrides, $dto->getOverrides());
    }

    public function testToApiArray(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $dto->addSectionOverride(123, 999, 'Section A')
            ->addStudentOverride([456, 789])
            ->addGroupOverride(321);
        
        $apiArray = $dto->toApiArray();
        
        $this->assertArrayHasKey('overrides', $apiArray);
        $this->assertCount(3, $apiArray['overrides']);
        
        $this->assertEquals([
            'course_section_id' => 123,
            'id' => 999,
            'title' => 'Section A'
        ], $apiArray['overrides'][0]);
        
        $this->assertEquals([
            'student_ids' => [456, 789]
        ], $apiArray['overrides'][1]);
        
        $this->assertEquals([
            'group_id' => 321
        ], $apiArray['overrides'][2]);
    }

    public function testEmptyOverridesApiArray(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $apiArray = $dto->toApiArray();
        
        $this->assertEquals(['overrides' => []], $apiArray);
    }

    public function testComplexScenario(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        // Add multiple overrides
        $dto->addSectionOverride(100, null, 'Morning Section')
            ->addSectionOverride(200, 1001, 'Afternoon Section')
            ->addStudentOverride([300, 301, 302], null, 'Remote Students')
            ->addStudentOverride([400], 1002)
            ->addGroupOverride(500, null, 'Lab Group A')
            ->addGroupOverride(600);
        
        $overrides = $dto->getOverrides();
        
        $this->assertCount(6, $overrides);
        
        // Verify first section override
        $this->assertEquals([
            'course_section_id' => 100,
            'title' => 'Morning Section'
        ], $overrides[0]);
        
        // Verify second section override with ID
        $this->assertEquals([
            'course_section_id' => 200,
            'id' => 1001,
            'title' => 'Afternoon Section'
        ], $overrides[1]);
        
        // Verify student override without ID
        $this->assertEquals([
            'student_ids' => [300, 301, 302],
            'title' => 'Remote Students'
        ], $overrides[2]);
        
        // Verify student override with ID but no title
        $this->assertEquals([
            'student_ids' => [400],
            'id' => 1002
        ], $overrides[3]);
        
        // Verify group overrides
        $this->assertEquals([
            'group_id' => 500,
            'title' => 'Lab Group A'
        ], $overrides[4]);
        
        $this->assertEquals([
            'group_id' => 600
        ], $overrides[5]);
    }

    public function testApiPropertyName(): void
    {
        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        
        $reflection = new \ReflectionClass($dto);
        $property = $reflection->getProperty('apiPropertyName');
        $property->setAccessible(true);
        
        $this->assertEquals('overrides', $property->getValue($dto));
    }
}