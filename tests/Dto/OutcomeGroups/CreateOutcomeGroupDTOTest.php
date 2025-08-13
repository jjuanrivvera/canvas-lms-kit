<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\OutcomeGroups;

use CanvasLMS\Dto\OutcomeGroups\CreateOutcomeGroupDTO;
use PHPUnit\Framework\TestCase;

class CreateOutcomeGroupDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new CreateOutcomeGroupDTO();

        $this->assertNull($dto->title);
        $this->assertNull($dto->description);
        $this->assertNull($dto->vendorGuid);
        $this->assertNull($dto->parentOutcomeGroupId);
    }

    public function testConstructorWithData(): void
    {
        $data = [
            'title' => 'Mathematics Standards',
            'description' => 'Learning outcomes for mathematics curriculum',
            'vendorGuid' => 'math-standards-2024',
            'parentOutcomeGroupId' => 123
        ];

        $dto = new CreateOutcomeGroupDTO($data);

        $this->assertEquals('Mathematics Standards', $dto->title);
        $this->assertEquals('Learning outcomes for mathematics curriculum', $dto->description);
        $this->assertEquals('math-standards-2024', $dto->vendorGuid);
        $this->assertEquals(123, $dto->parentOutcomeGroupId);
    }

    public function testConstructorWithSnakeCaseData(): void
    {
        $data = [
            'title' => 'Snake Case Group',
            'vendor_guid' => 'snake-case-guid',
            'parent_outcome_group_id' => 456
        ];

        $dto = new CreateOutcomeGroupDTO($data);

        $this->assertEquals('Snake Case Group', $dto->title);
        $this->assertEquals('snake-case-guid', $dto->vendorGuid);
        $this->assertEquals(456, $dto->parentOutcomeGroupId);
    }

    public function testToArrayWithMinimalData(): void
    {
        $dto = new CreateOutcomeGroupDTO(['title' => 'Basic Group']);

        $result = $dto->toArray();

        $expected = [
            [
                'name' => 'title',
                'contents' => 'Basic Group'
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testToArrayWithAllFields(): void
    {
        $dto = new CreateOutcomeGroupDTO([
            'title' => 'Complete Group',
            'description' => 'A complete group with all fields',
            'vendorGuid' => 'complete-group-001',
            'parentOutcomeGroupId' => 789
        ]);

        $result = $dto->toArray();

        $this->assertCount(4, $result);

        // Check each field
        $this->assertEquals('title', $result[0]['name']);
        $this->assertEquals('Complete Group', $result[0]['contents']);

        $this->assertEquals('description', $result[1]['name']);
        $this->assertEquals('A complete group with all fields', $result[1]['contents']);

        $this->assertEquals('vendor_guid', $result[2]['name']);
        $this->assertEquals('complete-group-001', $result[2]['contents']);

        $this->assertEquals('parent_outcome_group_id', $result[3]['name']);
        $this->assertEquals('789', $result[3]['contents']);
    }

    public function testToArrayExcludesNullFields(): void
    {
        $dto = new CreateOutcomeGroupDTO([
            'title' => 'Group with nulls',
            'description' => null,
            'vendorGuid' => null
        ]);

        $result = $dto->toArray();

        $this->assertCount(1, $result);
        $this->assertEquals('title', $result[0]['name']);

        // Ensure no null fields are included
        foreach ($result as $field) {
            $this->assertNotNull($field['contents']);
        }
    }

    public function testWithTitleStaticMethod(): void
    {
        $dto = CreateOutcomeGroupDTO::withTitle('Quick Group');

        $this->assertEquals('Quick Group', $dto->title);
        $this->assertNull($dto->description);
        $this->assertNull($dto->vendorGuid);
        $this->assertNull($dto->parentOutcomeGroupId);

        $result = $dto->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals('title', $result[0]['name']);
    }

    public function testWithTitleAndDescriptionStaticMethod(): void
    {
        $dto = CreateOutcomeGroupDTO::withTitleAndDescription(
            'Science Standards',
            'Core science learning outcomes'
        );

        $this->assertEquals('Science Standards', $dto->title);
        $this->assertEquals('Core science learning outcomes', $dto->description);
        $this->assertNull($dto->vendorGuid);
        $this->assertNull($dto->parentOutcomeGroupId);

        $result = $dto->toArray();
        $this->assertCount(2, $result);
        
        $fields = array_column($result, 'name');
        $this->assertContains('title', $fields);
        $this->assertContains('description', $fields);
    }

    public function testAsSubgroupStaticMethod(): void
    {
        $dto = CreateOutcomeGroupDTO::asSubgroup('Algebra Subgroup', 100);

        $this->assertEquals('Algebra Subgroup', $dto->title);
        $this->assertEquals(100, $dto->parentOutcomeGroupId);
        $this->assertNull($dto->description);
        $this->assertNull($dto->vendorGuid);

        $result = $dto->toArray();
        $this->assertCount(2, $result);
        
        $fields = array_column($result, 'name');
        $this->assertContains('title', $fields);
        $this->assertContains('parent_outcome_group_id', $fields);
    }

    public function testWithVendorGuidMethod(): void
    {
        $dto = CreateOutcomeGroupDTO::withTitle('Test Group')
            ->withVendorGuid('vendor-123');

        $this->assertEquals('Test Group', $dto->title);
        $this->assertEquals('vendor-123', $dto->vendorGuid);

        // Test builder pattern returns same instance
        $this->assertSame($dto, $dto->withVendorGuid('updated-guid'));
        $this->assertEquals('updated-guid', $dto->vendorGuid);

        $result = $dto->toArray();
        $this->assertCount(2, $result);
        
        $fields = array_column($result, 'name');
        $this->assertContains('title', $fields);
        $this->assertContains('vendor_guid', $fields);
    }

    public function testValidateWithValidTitle(): void
    {
        $dto = new CreateOutcomeGroupDTO(['title' => 'Valid Title']);
        $this->assertTrue($dto->validate());
    }

    public function testValidateWithEmptyTitle(): void
    {
        $dto = new CreateOutcomeGroupDTO();
        $this->assertFalse($dto->validate());
    }

    public function testValidateWithWhitespaceOnlyTitle(): void
    {
        $dto = new CreateOutcomeGroupDTO(['title' => '   ']);
        $this->assertFalse($dto->validate());
    }

    public function testValidateWithEmptyStringTitle(): void
    {
        $dto = new CreateOutcomeGroupDTO(['title' => '']);
        $this->assertFalse($dto->validate());
    }

    public function testValidateWithOtherFieldsButNoTitle(): void
    {
        $dto = new CreateOutcomeGroupDTO([
            'description' => 'Has description',
            'vendorGuid' => 'has-guid'
        ]);

        $this->assertFalse($dto->validate());
    }

    public function testNumericCasting(): void
    {
        $dto = new CreateOutcomeGroupDTO([
            'parentOutcomeGroupId' => '789'  // String should be cast to int
        ]);

        $this->assertSame(789, $dto->parentOutcomeGroupId);
    }

    public function testApiPropertyName(): void
    {
        $dto = new CreateOutcomeGroupDTO(['title' => 'Test Group']);
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals('outcome_group[title]', $result[0]['name']);
        $this->assertEquals('Test Group', $result[0]['contents']);
    }

    public function testBuilderPatternChaining(): void
    {
        $dto = CreateOutcomeGroupDTO::withTitle('Chained Group')
            ->withVendorGuid('chain-001');

        $this->assertEquals('Chained Group', $dto->title);
        $this->assertEquals('chain-001', $dto->vendorGuid);

        // Test multiple chaining
        $dto->withVendorGuid('chain-002')
            ->withVendorGuid('chain-003');

        $this->assertEquals('chain-003', $dto->vendorGuid);
    }

    public function testComplexGroupCreation(): void
    {
        // Test creating a complex subgroup with all features
        $dto = CreateOutcomeGroupDTO::asSubgroup('Advanced Mathematics', 500)
            ->withVendorGuid('advanced-math-v2');

        $dto->description = 'Advanced mathematical concepts and problem solving';

        $this->assertEquals('Advanced Mathematics', $dto->title);
        $this->assertEquals('Advanced mathematical concepts and problem solving', $dto->description);
        $this->assertEquals('advanced-math-v2', $dto->vendorGuid);
        $this->assertEquals(500, $dto->parentOutcomeGroupId);

        $this->assertTrue($dto->validate());

        $result = $dto->toArray();
        $this->assertCount(4, $result);

        // Verify all fields are properly formatted
        $fields = array_column($result, 'name');
        $this->assertContains('title', $fields);
        $this->assertContains('description', $fields);
        $this->assertContains('vendor_guid', $fields);
        $this->assertContains('parent_outcome_group_id', $fields);

        // Verify API format
        $apiResult = $dto->toApiArray();
        $this->assertIsArray($apiResult);
        $this->assertNotEmpty($apiResult);
        
        // Check that API fields are properly formatted with outcome_group[] prefix
        $apiFields = array_column($apiResult, 'name');
        $this->assertContains('outcome_group[title]', $apiFields);
    }

    public function testRootGroupCreation(): void
    {
        // Test creating a root group (no parent)
        $dto = CreateOutcomeGroupDTO::withTitleAndDescription(
            'Institution Standards',
            'Top-level learning outcome standards for the institution'
        );

        $this->assertEquals('Institution Standards', $dto->title);
        $this->assertEquals('Top-level learning outcome standards for the institution', $dto->description);
        $this->assertNull($dto->parentOutcomeGroupId);

        $this->assertTrue($dto->validate());

        $result = $dto->toArray();
        $this->assertCount(2, $result); // Only title and description

        $fields = array_column($result, 'name');
        $this->assertContains('title', $fields);
        $this->assertContains('description', $fields);
        $this->assertNotContains('parent_outcome_group_id', $fields);
    }

    public function testStaticFactoryMethodsReturnDifferentInstances(): void
    {
        $dto1 = CreateOutcomeGroupDTO::withTitle('Group 1');
        $dto2 = CreateOutcomeGroupDTO::withTitle('Group 2');

        $this->assertNotSame($dto1, $dto2);
        $this->assertEquals('Group 1', $dto1->title);
        $this->assertEquals('Group 2', $dto2->title);
    }

    public function testParentIdIsStringifiedInToArray(): void
    {
        $dto = new CreateOutcomeGroupDTO([
            'title' => 'Child Group',
            'parentOutcomeGroupId' => 999
        ]);

        $result = $dto->toArray();

        $parentField = array_filter($result, fn($item) => $item['name'] === 'parent_outcome_group_id');
        $parentValue = array_values($parentField)[0]['contents'];

        $this->assertIsString($parentValue);
        $this->assertEquals('999', $parentValue);
    }
}