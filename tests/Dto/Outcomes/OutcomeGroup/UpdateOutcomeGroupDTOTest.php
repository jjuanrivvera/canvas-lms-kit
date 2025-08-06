<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\Outcomes\OutcomeGroup;

use CanvasLMS\Dto\Outcomes\OutcomeGroup\UpdateOutcomeGroupDTO;
use PHPUnit\Framework\TestCase;

class UpdateOutcomeGroupDTOTest extends TestCase
{
    public function testConstructorWithEmptyData(): void
    {
        $dto = new UpdateOutcomeGroupDTO();

        $this->assertNull($dto->title);
        $this->assertNull($dto->description);
        $this->assertNull($dto->vendorGuid);
        $this->assertNull($dto->parentOutcomeGroupId);
    }

    public function testConstructorWithPartialData(): void
    {
        $data = [
            'title' => 'Updated Group Title',
            'description' => 'Updated description'
        ];

        $dto = new UpdateOutcomeGroupDTO($data);

        $this->assertEquals('Updated Group Title', $dto->title);
        $this->assertEquals('Updated description', $dto->description);
        $this->assertNull($dto->vendorGuid);
        $this->assertNull($dto->parentOutcomeGroupId);
    }

    public function testConstructorWithSnakeCaseData(): void
    {
        $data = [
            'vendor_guid' => 'updated-vendor-guid',
            'parent_outcome_group_id' => 456
        ];

        $dto = new UpdateOutcomeGroupDTO($data);

        $this->assertEquals('updated-vendor-guid', $dto->vendorGuid);
        $this->assertEquals(456, $dto->parentOutcomeGroupId);
        $this->assertNull($dto->title);
        $this->assertNull($dto->description);
    }

    public function testToArrayWithSingleField(): void
    {
        $dto = new UpdateOutcomeGroupDTO(['title' => 'New Title']);

        $result = $dto->toArray();

        $expected = [
            [
                'name' => 'title',
                'contents' => 'New Title'
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testToArrayWithMultipleFields(): void
    {
        $dto = new UpdateOutcomeGroupDTO([
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'vendorGuid' => 'new-guid'
        ]);

        $result = $dto->toArray();

        $this->assertCount(3, $result);

        // Check each field
        $fields = array_column($result, 'name');
        $this->assertContains('title', $fields);
        $this->assertContains('description', $fields);
        $this->assertContains('vendor_guid', $fields);
    }

    public function testToArrayWithAllFields(): void
    {
        $dto = new UpdateOutcomeGroupDTO([
            'title' => 'Complete Update',
            'description' => 'Complete description update',
            'vendorGuid' => 'complete-guid',
            'parentOutcomeGroupId' => 789
        ]);

        $result = $dto->toArray();

        $this->assertCount(4, $result);

        $this->assertEquals('title', $result[0]['name']);
        $this->assertEquals('Complete Update', $result[0]['contents']);

        $this->assertEquals('description', $result[1]['name']);
        $this->assertEquals('Complete description update', $result[1]['contents']);

        $this->assertEquals('vendor_guid', $result[2]['name']);
        $this->assertEquals('complete-guid', $result[2]['contents']);

        $this->assertEquals('parent_outcome_group_id', $result[3]['name']);
        $this->assertEquals('789', $result[3]['contents']);
    }

    public function testToArrayExcludesNullFields(): void
    {
        $dto = new UpdateOutcomeGroupDTO([
            'title' => 'Updated Title',
            'description' => null,
            'vendorGuid' => null
        ]);

        $result = $dto->toArray();

        $this->assertCount(1, $result);
        $this->assertEquals('title', $result[0]['name']);
    }

    public function testUpdateTitleStaticMethod(): void
    {
        $dto = UpdateOutcomeGroupDTO::updateTitle('New Group Title');

        $this->assertEquals('New Group Title', $dto->title);
        $this->assertNull($dto->description);
        $this->assertNull($dto->vendorGuid);
        $this->assertNull($dto->parentOutcomeGroupId);

        $result = $dto->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals('title', $result[0]['name']);
    }

    public function testUpdateDescriptionStaticMethod(): void
    {
        $dto = UpdateOutcomeGroupDTO::updateDescription('New description text');

        $this->assertEquals('New description text', $dto->description);
        $this->assertNull($dto->title);
        $this->assertNull($dto->vendorGuid);
        $this->assertNull($dto->parentOutcomeGroupId);

        $result = $dto->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals('description', $result[0]['name']);
    }

    public function testMoveToParentStaticMethod(): void
    {
        $dto = UpdateOutcomeGroupDTO::moveToParent(123);

        $this->assertEquals(123, $dto->parentOutcomeGroupId);
        $this->assertNull($dto->title);
        $this->assertNull($dto->description);
        $this->assertNull($dto->vendorGuid);

        $result = $dto->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals('parent_outcome_group_id', $result[0]['name']);
        $this->assertEquals('123', $result[0]['contents']);
    }

    public function testUpdateVendorGuidStaticMethod(): void
    {
        $dto = UpdateOutcomeGroupDTO::updateVendorGuid('new-vendor-guid-123');

        $this->assertEquals('new-vendor-guid-123', $dto->vendorGuid);
        $this->assertNull($dto->title);
        $this->assertNull($dto->description);
        $this->assertNull($dto->parentOutcomeGroupId);

        $result = $dto->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals('vendor_guid', $result[0]['name']);
    }

    public function testWithFieldsStaticMethod(): void
    {
        $fields = [
            'title' => 'Fields Title',
            'vendorGuid' => 'fields-guid',
            'description' => 'Fields Description'
        ];

        $dto = UpdateOutcomeGroupDTO::withFields($fields);

        $this->assertEquals('Fields Title', $dto->title);
        $this->assertEquals('fields-guid', $dto->vendorGuid);
        $this->assertEquals('Fields Description', $dto->description);
        $this->assertNull($dto->parentOutcomeGroupId);
    }

    public function testWithFieldsIgnoresInvalidProperties(): void
    {
        $fields = [
            'title' => 'Valid Title',
            'invalidProperty' => 'Should be ignored',
            'anotherInvalid' => 456
        ];

        $dto = UpdateOutcomeGroupDTO::withFields($fields);

        $this->assertEquals('Valid Title', $dto->title);
        $this->assertObjectNotHasProperty('invalidProperty', $dto);
        $this->assertObjectNotHasProperty('anotherInvalid', $dto);
    }

    public function testHasUpdatesReturnsTrueWithTitle(): void
    {
        $dto = UpdateOutcomeGroupDTO::updateTitle('Has Title');
        $this->assertTrue($dto->hasUpdates());
    }

    public function testHasUpdatesReturnsTrueWithDescription(): void
    {
        $dto = UpdateOutcomeGroupDTO::updateDescription('Has Description');
        $this->assertTrue($dto->hasUpdates());
    }

    public function testHasUpdatesReturnsTrueWithVendorGuid(): void
    {
        $dto = UpdateOutcomeGroupDTO::updateVendorGuid('has-guid');
        $this->assertTrue($dto->hasUpdates());
    }

    public function testHasUpdatesReturnsTrueWithParentId(): void
    {
        $dto = UpdateOutcomeGroupDTO::moveToParent(789);
        $this->assertTrue($dto->hasUpdates());
    }

    public function testHasUpdatesReturnsFalseWithNoFields(): void
    {
        $dto = new UpdateOutcomeGroupDTO();
        $this->assertFalse($dto->hasUpdates());
    }

    public function testHasUpdatesReturnsTrueWithMultipleFields(): void
    {
        $dto = UpdateOutcomeGroupDTO::withFields([
            'title' => 'Multiple Fields',
            'description' => 'Multiple Description'
        ]);

        $this->assertTrue($dto->hasUpdates());
    }

    public function testNumericCasting(): void
    {
        $dto = new UpdateOutcomeGroupDTO([
            'parentOutcomeGroupId' => '999'  // String should be cast to int
        ]);

        $this->assertSame(999, $dto->parentOutcomeGroupId);
    }

    public function testApiPropertyName(): void
    {
        $dto = new UpdateOutcomeGroupDTO(['title' => 'Test']);
        $result = $dto->toApiArray();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals('outcome_group[title]', $result[0]['name']);
        $this->assertEquals('Test', $result[0]['contents']);
    }

    public function testPartialUpdateScenarios(): void
    {
        // Test updating only title
        $titleOnlyDto = UpdateOutcomeGroupDTO::updateTitle('Title Only Update');
        $this->assertTrue($titleOnlyDto->hasUpdates());
        $this->assertCount(1, $titleOnlyDto->toArray());

        // Test updating only description
        $descOnlyDto = UpdateOutcomeGroupDTO::updateDescription('Description Only Update');
        $this->assertTrue($descOnlyDto->hasUpdates());
        $this->assertCount(1, $descOnlyDto->toArray());

        // Test moving to new parent
        $moveOnlyDto = UpdateOutcomeGroupDTO::moveToParent(555);
        $this->assertTrue($moveOnlyDto->hasUpdates());
        $this->assertCount(1, $moveOnlyDto->toArray());

        // Test updating vendor GUID
        $guidOnlyDto = UpdateOutcomeGroupDTO::updateVendorGuid('guid-only-update');
        $this->assertTrue($guidOnlyDto->hasUpdates());
        $this->assertCount(1, $guidOnlyDto->toArray());
    }

    public function testComplexUpdateScenario(): void
    {
        // Test updating multiple fields at once
        $dto = UpdateOutcomeGroupDTO::withFields([
            'title' => 'Complex Updated Group',
            'description' => 'This group has been significantly updated with new content',
            'vendorGuid' => 'complex-update-v2',
            'parentOutcomeGroupId' => 888
        ]);

        $this->assertEquals('Complex Updated Group', $dto->title);
        $this->assertEquals('This group has been significantly updated with new content', $dto->description);
        $this->assertEquals('complex-update-v2', $dto->vendorGuid);
        $this->assertEquals(888, $dto->parentOutcomeGroupId);

        $this->assertTrue($dto->hasUpdates());

        $result = $dto->toArray();
        $this->assertCount(4, $result);

        // Verify all fields are present
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

    public function testStaticMethodsReturnDifferentInstances(): void
    {
        $dto1 = UpdateOutcomeGroupDTO::updateTitle('Title 1');
        $dto2 = UpdateOutcomeGroupDTO::updateTitle('Title 2');

        $this->assertNotSame($dto1, $dto2);
        $this->assertEquals('Title 1', $dto1->title);
        $this->assertEquals('Title 2', $dto2->title);
    }

    public function testParentIdIsStringifiedInToArray(): void
    {
        $dto = new UpdateOutcomeGroupDTO(['parentOutcomeGroupId' => 12345]);

        $result = $dto->toArray();

        $parentField = array_filter($result, fn($item) => $item['name'] === 'parent_outcome_group_id');
        $parentValue = array_values($parentField)[0]['contents'];

        $this->assertIsString($parentValue);
        $this->assertEquals('12345', $parentValue);
    }

    public function testEmptyDtoHasNoUpdates(): void
    {
        $dto = new UpdateOutcomeGroupDTO();

        $this->assertFalse($dto->hasUpdates());
        $this->assertEmpty($dto->toArray());
    }

    public function testUpdateSequence(): void
    {
        // Test a sequence of updates that might happen in practice
        
        // 1. Update title
        $dto = UpdateOutcomeGroupDTO::updateTitle('Initial Title Update');
        $this->assertTrue($dto->hasUpdates());
        $this->assertCount(1, $dto->toArray());

        // 2. Create a new DTO for description update
        $dto2 = UpdateOutcomeGroupDTO::updateDescription('Follow-up description update');
        $this->assertTrue($dto2->hasUpdates());
        $this->assertCount(1, $dto2->toArray());

        // 3. Create combined update
        $dto3 = UpdateOutcomeGroupDTO::withFields([
            'title' => 'Combined Title',
            'description' => 'Combined Description'
        ]);
        $this->assertTrue($dto3->hasUpdates());
        $this->assertCount(2, $dto3->toArray());

        // Ensure they're independent instances
        $this->assertNotSame($dto, $dto2);
        $this->assertNotSame($dto2, $dto3);
    }

    public function testMoveGroupBetweenParents(): void
    {
        // Test moving a group from one parent to another
        $moveToParent1 = UpdateOutcomeGroupDTO::moveToParent(100);
        $this->assertEquals(100, $moveToParent1->parentOutcomeGroupId);

        $moveToParent2 = UpdateOutcomeGroupDTO::moveToParent(200);
        $this->assertEquals(200, $moveToParent2->parentOutcomeGroupId);

        // Verify they're different instances
        $this->assertNotSame($moveToParent1, $moveToParent2);
        $this->assertNotEquals($moveToParent1->parentOutcomeGroupId, $moveToParent2->parentOutcomeGroupId);
    }
}