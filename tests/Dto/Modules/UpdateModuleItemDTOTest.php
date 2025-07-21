<?php

declare(strict_types=1);

namespace Tests\Dto\Modules;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Modules\UpdateModuleItemDTO;

/**
 * @covers \CanvasLMS\Dto\Modules\UpdateModuleItemDTO
 */
class UpdateModuleItemDTOTest extends TestCase
{
    public function testConstructorWithPartialData(): void
    {
        $data = [
            'title' => 'Updated Assignment Title',
            'position' => 3
        ];
        
        $dto = new UpdateModuleItemDTO($data);
        
        $this->assertEquals('Updated Assignment Title', $dto->getTitle());
        $this->assertEquals(3, $dto->getPosition());
        $this->assertNull($dto->getType());
        $this->assertNull($dto->getContentId());
    }

    public function testConstructorWithCompleteData(): void
    {
        $data = [
            'type' => 'Assignment',
            'content_id' => 456,
            'title' => 'Complete Update',
            'position' => 2,
            'indent' => 1,
            'completion_requirement' => ['type' => 'min_score', 'min_score' => 90]
        ];
        
        $dto = new UpdateModuleItemDTO($data);
        
        $this->assertEquals('Assignment', $dto->getType());
        $this->assertEquals(456, $dto->getContentId());
        $this->assertEquals('Complete Update', $dto->getTitle());
        $this->assertEquals(2, $dto->getPosition());
        $this->assertEquals(1, $dto->getIndent());
        $this->assertEquals(['type' => 'min_score', 'min_score' => 90], $dto->getCompletionRequirement());
    }

    public function testConstructorWithExternalToolUpdate(): void
    {
        $data = [
            'external_url' => 'https://updated-tool.com',
            'new_tab' => false,
            'iframe' => ['width' => 1200, 'height' => 900]
        ];
        
        $dto = new UpdateModuleItemDTO($data);
        
        $this->assertEquals('https://updated-tool.com', $dto->getExternalUrl());
        $this->assertFalse($dto->getNewTab());
        $this->assertEquals(['width' => 1200, 'height' => 900], $dto->getIframe());
    }

    public function testConstructorWithPageUpdate(): void
    {
        $data = [
            'page_url' => 'updated-page-slug',
            'title' => 'Updated Page Title'
        ];
        
        $dto = new UpdateModuleItemDTO($data);
        
        $this->assertEquals('updated-page-slug', $dto->getPageUrl());
        $this->assertEquals('Updated Page Title', $dto->getTitle());
    }

    public function testToApiArrayWithPartialUpdate(): void
    {
        $data = [
            'title' => 'Updated Title',
            'position' => 5
        ];
        
        $dto = new UpdateModuleItemDTO($data);
        $apiArray = $dto->toApiArray();
        
        $this->assertIsArray($apiArray);
        
        // Check that only non-null values are included
        $this->assertContains(['name' => 'module_item[title]', 'contents' => 'Updated Title'], $apiArray);
        $this->assertContains(['name' => 'module_item[position]', 'contents' => 5], $apiArray);
        
        // Ensure null values are not included
        foreach ($apiArray as $item) {
            $this->assertNotNull($item['contents'], 'API array should not contain null values');
        }
    }

    public function testToApiArrayWithCompleteUpdate(): void
    {
        $data = [
            'type' => 'Quiz',
            'content_id' => 789,
            'title' => 'Updated Quiz',
            'position' => 3,
            'indent' => 2
        ];
        
        $dto = new UpdateModuleItemDTO($data);
        $apiArray = $dto->toApiArray();
        
        $this->assertContains(['name' => 'module_item[type]', 'contents' => 'Quiz'], $apiArray);
        $this->assertContains(['name' => 'module_item[content_id]', 'contents' => 789], $apiArray);
        $this->assertContains(['name' => 'module_item[title]', 'contents' => 'Updated Quiz'], $apiArray);
        $this->assertContains(['name' => 'module_item[position]', 'contents' => 3], $apiArray);
        $this->assertContains(['name' => 'module_item[indent]', 'contents' => 2], $apiArray);
    }

    public function testToApiArrayWithExternalToolUpdate(): void
    {
        $data = [
            'external_url' => 'https://new-tool.example.com',
            'new_tab' => true,
            'iframe' => ['width' => 1000, 'height' => 700]
        ];
        
        $dto = new UpdateModuleItemDTO($data);
        $apiArray = $dto->toApiArray();
        
        $this->assertContains(['name' => 'module_item[external_url]', 'contents' => 'https://new-tool.example.com'], $apiArray);
        $this->assertContains(['name' => 'module_item[new_tab]', 'contents' => true], $apiArray);
        $this->assertContains(['name' => 'module_item[iframe][width]', 'contents' => 1000], $apiArray);
        $this->assertContains(['name' => 'module_item[iframe][height]', 'contents' => 700], $apiArray);
    }

    public function testToApiArrayWithCompletionRequirementUpdate(): void
    {
        $data = [
            'completion_requirement' => [
                'type' => 'must_contribute'
            ]
        ];
        
        $dto = new UpdateModuleItemDTO($data);
        $apiArray = $dto->toApiArray();
        
        $this->assertContains(['name' => 'module_item[completion_requirement][type]', 'contents' => 'must_contribute'], $apiArray);
    }

    public function testToApiArrayWithNullCompletionRequirement(): void
    {
        $data = [
            'title' => 'Test Title',
            'completion_requirement' => null
        ];
        
        $dto = new UpdateModuleItemDTO($data);
        $apiArray = $dto->toApiArray();
        
        // Should only contain title, completion_requirement should be skipped
        $this->assertContains(['name' => 'module_item[title]', 'contents' => 'Test Title'], $apiArray);
        
        // Ensure completion_requirement is not in the array
        $completionRequirementExists = false;
        foreach ($apiArray as $item) {
            if (strpos($item['name'], 'completion_requirement') !== false) {
                $completionRequirementExists = true;
                break;
            }
        }
        $this->assertFalse($completionRequirementExists);
    }

    public function testAllPropertiesAreOptional(): void
    {
        $dto = new UpdateModuleItemDTO([]);
        
        $this->assertNull($dto->getType());
        $this->assertNull($dto->getContentId());
        $this->assertNull($dto->getPageUrl());
        $this->assertNull($dto->getExternalUrl());
        $this->assertNull($dto->getTitle());
        $this->assertNull($dto->getPosition());
        $this->assertNull($dto->getIndent());
        $this->assertNull($dto->getNewTab());
        $this->assertNull($dto->getCompletionRequirement());
        $this->assertNull($dto->getIframe());
    }

    public function testPropertyGettersAndSetters(): void
    {
        $dto = new UpdateModuleItemDTO([]);
        
        // Test type (nullable)
        $dto->setType('Discussion');
        $this->assertEquals('Discussion', $dto->getType());
        $dto->setType(null);
        $this->assertNull($dto->getType());
        
        // Test content_id (nullable)
        $dto->setContentId(999);
        $this->assertEquals(999, $dto->getContentId());
        $dto->setContentId(null);
        $this->assertNull($dto->getContentId());
        
        // Test page_url (nullable)
        $dto->setPageUrl('new-page');
        $this->assertEquals('new-page', $dto->getPageUrl());
        $dto->setPageUrl(null);
        $this->assertNull($dto->getPageUrl());
        
        // Test external_url (nullable)
        $dto->setExternalUrl('https://example.com');
        $this->assertEquals('https://example.com', $dto->getExternalUrl());
        $dto->setExternalUrl(null);
        $this->assertNull($dto->getExternalUrl());
        
        // Test title (nullable)
        $dto->setTitle('New Title');
        $this->assertEquals('New Title', $dto->getTitle());
        $dto->setTitle(null);
        $this->assertNull($dto->getTitle());
        
        // Test position (nullable)
        $dto->setPosition(10);
        $this->assertEquals(10, $dto->getPosition());
        $dto->setPosition(null);
        $this->assertNull($dto->getPosition());
        
        // Test indent (nullable)
        $dto->setIndent(4);
        $this->assertEquals(4, $dto->getIndent());
        $dto->setIndent(null);
        $this->assertNull($dto->getIndent());
        
        // Test new_tab (nullable)
        $dto->setNewTab(true);
        $this->assertTrue($dto->getNewTab());
        $dto->setNewTab(null);
        $this->assertNull($dto->getNewTab());
        
        // Test completion_requirement (nullable)
        $requirement = ['type' => 'must_submit'];
        $dto->setCompletionRequirement($requirement);
        $this->assertEquals($requirement, $dto->getCompletionRequirement());
        $dto->setCompletionRequirement(null);
        $this->assertNull($dto->getCompletionRequirement());
        
        // Test iframe (nullable)
        $iframe = ['width' => 500, 'height' => 400];
        $dto->setIframe($iframe);
        $this->assertEquals($iframe, $dto->getIframe());
        $dto->setIframe(null);
        $this->assertNull($dto->getIframe());
    }

    public function testConstructorWithCamelCaseConversion(): void
    {
        $data = [
            'content_id' => 123,
            'page_url' => 'test-page',
            'external_url' => 'https://test.com',
            'new_tab' => false,
            'completion_requirement' => ['type' => 'must_view']
        ];
        
        $dto = new UpdateModuleItemDTO($data);
        
        $this->assertEquals(123, $dto->getContentId());
        $this->assertEquals('test-page', $dto->getPageUrl());
        $this->assertEquals('https://test.com', $dto->getExternalUrl());
        $this->assertFalse($dto->getNewTab());
        $this->assertEquals(['type' => 'must_view'], $dto->getCompletionRequirement());
    }

    public function testApiPropertyName(): void
    {
        $dto = new UpdateModuleItemDTO([]);
        $reflection = new \ReflectionClass($dto);
        $property = $reflection->getProperty('apiPropertyName');
        $property->setAccessible(true);
        
        $this->assertEquals('module_item', $property->getValue($dto));
    }

    public function testEmptyUpdateProducesEmptyApiArray(): void
    {
        $dto = new UpdateModuleItemDTO([]);
        $apiArray = $dto->toApiArray();
        
        $this->assertIsArray($apiArray);
        $this->assertEmpty($apiArray, 'Empty update should produce empty API array');
    }

    public function testBooleanFalseIsIncludedInApiArray(): void
    {
        $data = [
            'new_tab' => false,
            'title' => 'Test Title'
        ];
        
        $dto = new UpdateModuleItemDTO($data);
        $apiArray = $dto->toApiArray();
        
        // Both false boolean and string should be included
        $this->assertContains(['name' => 'module_item[new_tab]', 'contents' => false], $apiArray);
        $this->assertContains(['name' => 'module_item[title]', 'contents' => 'Test Title'], $apiArray);
    }

    public function testInvalidUrlValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format for externalUrl');
        
        $dto = new UpdateModuleItemDTO(['type' => 'ExternalTool']);
        $dto->setExternalUrl('not-a-valid-url');
    }

    public function testValidUrlValidation(): void
    {
        $dto = new UpdateModuleItemDTO(['type' => 'ExternalTool']);
        $dto->setExternalUrl('https://example.com/tool');
        
        $this->assertEquals('https://example.com/tool', $dto->getExternalUrl());
    }

    public function testNullUrlValidation(): void
    {
        $dto = new UpdateModuleItemDTO(['type' => 'Assignment']);
        $dto->setExternalUrl(null);
        
        $this->assertNull($dto->getExternalUrl());
    }

    public function testInvalidTypeValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid module item type: InvalidType');
        
        $dto = new UpdateModuleItemDTO([]);
        $dto->setType('InvalidType');
    }

    public function testValidTypeValidation(): void
    {
        $dto = new UpdateModuleItemDTO([]);
        $validTypes = ['File', 'Page', 'Discussion', 'Assignment', 'Quiz', 'SubHeader', 'ExternalUrl', 'ExternalTool'];
        
        foreach ($validTypes as $type) {
            $dto->setType($type);
            $this->assertEquals($type, $dto->getType());
        }
    }

    public function testNullTypeValidation(): void
    {
        $dto = new UpdateModuleItemDTO([]);
        $dto->setType(null);
        
        $this->assertNull($dto->getType());
    }
}