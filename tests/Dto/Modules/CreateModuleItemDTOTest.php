<?php

declare(strict_types=1);

namespace Tests\Dto\Modules;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Modules\CreateModuleItemDTO;

/**
 * @covers \CanvasLMS\Dto\Modules\CreateModuleItemDTO
 */
class CreateModuleItemDTOTest extends TestCase
{
    public function testConstructorWithAssignmentData(): void
    {
        $data = [
            'type' => 'Assignment',
            'content_id' => 123,
            'title' => 'Test Assignment',
            'position' => 1,
            'indent' => 0
        ];
        
        $dto = new CreateModuleItemDTO($data);
        
        $this->assertEquals('Assignment', $dto->getType());
        $this->assertEquals(123, $dto->getContentId());
        $this->assertEquals('Test Assignment', $dto->getTitle());
        $this->assertEquals(1, $dto->getPosition());
        $this->assertEquals(0, $dto->getIndent());
    }

    public function testConstructorWithPageData(): void
    {
        $data = [
            'type' => 'Page',
            'page_url' => 'course-introduction',
            'title' => 'Course Introduction',
            'position' => 1
        ];
        
        $dto = new CreateModuleItemDTO($data);
        
        $this->assertEquals('Page', $dto->getType());
        $this->assertEquals('course-introduction', $dto->getPageUrl());
        $this->assertEquals('Course Introduction', $dto->getTitle());
        $this->assertEquals(1, $dto->getPosition());
        $this->assertNull($dto->getContentId());
    }

    public function testConstructorWithExternalToolData(): void
    {
        $data = [
            'type' => 'ExternalTool',
            'external_url' => 'https://example.com/tool',
            'title' => 'Learning Tool',
            'new_tab' => true,
            'iframe' => ['width' => 800, 'height' => 600]
        ];
        
        $dto = new CreateModuleItemDTO($data);
        
        $this->assertEquals('ExternalTool', $dto->getType());
        $this->assertEquals('https://example.com/tool', $dto->getExternalUrl());
        $this->assertEquals('Learning Tool', $dto->getTitle());
        $this->assertTrue($dto->getNewTab());
        $this->assertEquals(['width' => 800, 'height' => 600], $dto->getIframe());
    }

    public function testConstructorWithCompletionRequirement(): void
    {
        $completionRequirement = [
            'type' => 'min_score',
            'min_score' => 80
        ];
        
        $data = [
            'type' => 'Assignment',
            'content_id' => 123,
            'completion_requirement' => $completionRequirement
        ];
        
        $dto = new CreateModuleItemDTO($data);
        
        $this->assertEquals($completionRequirement, $dto->getCompletionRequirement());
    }

    public function testToApiArrayWithAssignmentType(): void
    {
        $data = [
            'type' => 'Assignment',
            'content_id' => 123,
            'title' => 'Test Assignment',
            'position' => 1,
            'indent' => 0
        ];
        
        $dto = new CreateModuleItemDTO($data);
        $apiArray = $dto->toApiArray();
        
        $this->assertIsArray($apiArray);
        
        // Check that array contains proper multipart format
        $this->assertContains(['name' => 'module_item[type]', 'contents' => 'Assignment'], $apiArray);
        $this->assertContains(['name' => 'module_item[content_id]', 'contents' => 123], $apiArray);
        $this->assertContains(['name' => 'module_item[title]', 'contents' => 'Test Assignment'], $apiArray);
        $this->assertContains(['name' => 'module_item[position]', 'contents' => 1], $apiArray);
        $this->assertContains(['name' => 'module_item[indent]', 'contents' => 0], $apiArray);
    }

    public function testToApiArrayWithPageType(): void
    {
        $data = [
            'type' => 'Page',
            'page_url' => 'course-introduction',
            'title' => 'Course Introduction'
        ];
        
        $dto = new CreateModuleItemDTO($data);
        $apiArray = $dto->toApiArray();
        
        $this->assertContains(['name' => 'module_item[type]', 'contents' => 'Page'], $apiArray);
        $this->assertContains(['name' => 'module_item[page_url]', 'contents' => 'course-introduction'], $apiArray);
        $this->assertContains(['name' => 'module_item[title]', 'contents' => 'Course Introduction'], $apiArray);
        
        // Ensure content_id is not included for Page type
        $contentIdExists = false;
        foreach ($apiArray as $item) {
            if ($item['name'] === 'module_item[content_id]') {
                $contentIdExists = true;
                break;
            }
        }
        $this->assertFalse($contentIdExists);
    }

    public function testToApiArrayWithExternalToolType(): void
    {
        $data = [
            'type' => 'ExternalTool',
            'external_url' => 'https://example.com/tool',
            'title' => 'Learning Tool',
            'new_tab' => true,
            'iframe' => ['width' => 800, 'height' => 600]
        ];
        
        $dto = new CreateModuleItemDTO($data);
        $apiArray = $dto->toApiArray();
        
        $this->assertContains(['name' => 'module_item[type]', 'contents' => 'ExternalTool'], $apiArray);
        $this->assertContains(['name' => 'module_item[external_url]', 'contents' => 'https://example.com/tool'], $apiArray);
        $this->assertContains(['name' => 'module_item[new_tab]', 'contents' => true], $apiArray);
        
        // Check iframe array conversion
        $this->assertContains(['name' => 'module_item[iframe][width]', 'contents' => 800], $apiArray);
        $this->assertContains(['name' => 'module_item[iframe][height]', 'contents' => 600], $apiArray);
    }

    public function testToApiArrayWithCompletionRequirement(): void
    {
        $data = [
            'type' => 'Assignment',
            'content_id' => 123,
            'completion_requirement' => [
                'type' => 'min_score',
                'min_score' => 80
            ]
        ];
        
        $dto = new CreateModuleItemDTO($data);
        $apiArray = $dto->toApiArray();
        
        $this->assertContains(['name' => 'module_item[completion_requirement][type]', 'contents' => 'min_score'], $apiArray);
        $this->assertContains(['name' => 'module_item[completion_requirement][min_score]', 'contents' => 80], $apiArray);
    }

    public function testToApiArraySkipsNullValues(): void
    {
        $data = [
            'type' => 'SubHeader',
            'title' => 'Section Header'
            // content_id, external_url, etc. are null
        ];
        
        $dto = new CreateModuleItemDTO($data);
        $apiArray = $dto->toApiArray();
        
        // Check that null values are not included
        foreach ($apiArray as $item) {
            $this->assertNotNull($item['contents'], 'API array should not contain null values');
        }
        
        $this->assertContains(['name' => 'module_item[type]', 'contents' => 'SubHeader'], $apiArray);
        $this->assertContains(['name' => 'module_item[title]', 'contents' => 'Section Header'], $apiArray);
    }

    public function testPropertyGettersAndSetters(): void
    {
        $dto = new CreateModuleItemDTO([]);
        
        // Test type
        $dto->setType('Quiz');
        $this->assertEquals('Quiz', $dto->getType());
        
        // Test content_id
        $dto->setContentId(456);
        $this->assertEquals(456, $dto->getContentId());
        
        // Test page_url
        $dto->setPageUrl('test-page');
        $this->assertEquals('test-page', $dto->getPageUrl());
        
        // Test external_url
        $dto->setExternalUrl('https://test.com');
        $this->assertEquals('https://test.com', $dto->getExternalUrl());
        
        // Test title
        $dto->setTitle('Test Title');
        $this->assertEquals('Test Title', $dto->getTitle());
        
        // Test position
        $dto->setPosition(5);
        $this->assertEquals(5, $dto->getPosition());
        
        // Test indent
        $dto->setIndent(3);
        $this->assertEquals(3, $dto->getIndent());
        
        // Test new_tab
        $dto->setNewTab(false);
        $this->assertFalse($dto->getNewTab());
        
        // Test completion_requirement
        $requirement = ['type' => 'must_view'];
        $dto->setCompletionRequirement($requirement);
        $this->assertEquals($requirement, $dto->getCompletionRequirement());
        
        // Test iframe
        $iframe = ['width' => 1000, 'height' => 800];
        $dto->setIframe($iframe);
        $this->assertEquals($iframe, $dto->getIframe());
    }

    public function testConstructorWithCamelCaseConversion(): void
    {
        $data = [
            'type' => 'Assignment',
            'content_id' => 123,
            'page_url' => 'test-page',
            'external_url' => 'https://test.com',
            'new_tab' => true,
            'completion_requirement' => ['type' => 'must_view']
        ];
        
        $dto = new CreateModuleItemDTO($data);
        
        $this->assertEquals('Assignment', $dto->getType());
        $this->assertEquals(123, $dto->getContentId());
        $this->assertEquals('test-page', $dto->getPageUrl());
        $this->assertEquals('https://test.com', $dto->getExternalUrl());
        $this->assertTrue($dto->getNewTab());
        $this->assertEquals(['type' => 'must_view'], $dto->getCompletionRequirement());
    }

    public function testApiPropertyName(): void
    {
        $dto = new CreateModuleItemDTO(['type' => 'Assignment']);
        $reflection = new \ReflectionClass($dto);
        $property = $reflection->getProperty('apiPropertyName');
        $property->setAccessible(true);
        
        $this->assertEquals('module_item', $property->getValue($dto));
    }

    public function testInvalidUrlValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format for externalUrl');
        
        $dto = new CreateModuleItemDTO(['type' => 'ExternalTool']);
        $dto->setExternalUrl('not-a-valid-url');
    }

    public function testValidUrlValidation(): void
    {
        $dto = new CreateModuleItemDTO(['type' => 'ExternalTool']);
        $dto->setExternalUrl('https://example.com/tool');
        
        $this->assertEquals('https://example.com/tool', $dto->getExternalUrl());
    }

    public function testNullUrlValidation(): void
    {
        $dto = new CreateModuleItemDTO(['type' => 'Assignment']);
        $dto->setExternalUrl(null);
        
        $this->assertNull($dto->getExternalUrl());
    }

    public function testInvalidTypeValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid module item type: InvalidType');
        
        $dto = new CreateModuleItemDTO([]);
        $dto->setType('InvalidType');
    }

    public function testValidTypeValidation(): void
    {
        $dto = new CreateModuleItemDTO([]);
        $validTypes = ['File', 'Page', 'Discussion', 'Assignment', 'Quiz', 'SubHeader', 'ExternalUrl', 'ExternalTool'];
        
        foreach ($validTypes as $type) {
            $dto->setType($type);
            $this->assertEquals($type, $dto->getType());
        }
    }
}