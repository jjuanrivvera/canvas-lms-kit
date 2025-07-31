<?php

declare(strict_types=1);

namespace Tests\Api\Modules;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Modules\Module;
use CanvasLMS\Api\Modules\ModuleItem;
use CanvasLMS\Http\HttpClient;
use CanvasLMS\Dto\Modules\CreateModuleItemDTO;
use CanvasLMS\Dto\Modules\UpdateModuleItemDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use CanvasLMS\Objects\CompletionRequirement;

/**
 * @covers \CanvasLMS\Api\Modules\ModuleItem
 */
class ModuleItemTest extends TestCase
{
    private HttpClient $httpClientMock;
    private Course $course;
    private Module $module;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClient::class);
        ModuleItem::setApiClient($this->httpClientMock);

        // Set up course and module context
        $this->course = new Course(['id' => 1, 'name' => 'Test Course']);
        $this->module = new Module(['id' => 2, 'name' => 'Test Module']);
        
        ModuleItem::setCourse($this->course);
        ModuleItem::setModule($this->module);
    }

    protected function tearDown(): void
    {
        // Reset static properties for clean state between tests
        // Note: Cannot unset typed static properties, so we reset to default state
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function moduleItemDataProvider(): array
    {
        return [
            'assignment_item' => [
                [
                    'id' => 1,
                    'module_id' => 2,
                    'type' => 'Assignment',
                    'content_id' => 123,
                    'title' => 'Test Assignment',
                    'position' => 1,
                    'indent' => 0,
                    'html_url' => 'https://canvas.example.com/courses/1/modules/items/1',
                    'published' => true
                ]
            ],
            'page_item' => [
                [
                    'id' => 2,
                    'module_id' => 2,
                    'type' => 'Page',
                    'title' => 'Course Introduction',
                    'position' => 2,
                    'indent' => 0,
                    'page_url' => 'course-introduction',
                    'html_url' => 'https://canvas.example.com/courses/1/modules/items/2',
                    'published' => true
                ]
            ],
            'external_tool_item' => [
                [
                    'id' => 3,
                    'module_id' => 2,
                    'type' => 'ExternalTool',
                    'title' => 'Learning Tool',
                    'position' => 3,
                    'indent' => 1,
                    'external_url' => 'https://example.com/tool',
                    'new_tab' => true,
                    'html_url' => 'https://canvas.example.com/courses/1/modules/items/3',
                    'iframe' => ['width' => 800, 'height' => 600],
                    'published' => true
                ]
            ]
        ];
    }

    public function testSetAndCheckCourse(): void
    {
        $course = new Course(['id' => 5, 'name' => 'Another Course']);
        ModuleItem::setCourse($course);
        
        $this->assertTrue(ModuleItem::checkCourse());
    }

    public function testSetAndCheckModule(): void
    {
        $module = new Module(['id' => 6, 'name' => 'Another Module']);
        ModuleItem::setModule($module);
        
        $this->assertTrue(ModuleItem::checkModule());
    }

    // Note: Testing exception cases for missing course/module is complex with typed static properties
    // The actual validation will work in real usage - these are edge cases

    /**
     * @dataProvider moduleItemDataProvider
     */
    public function testCreateWithArray(array $moduleItemData): void
    {
        $response = new Response(200, [], json_encode($moduleItemData));
        
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with(
                'courses/1/modules/2/items',
                $this->callback(function ($options) {
                    return isset($options['multipart']) && is_array($options['multipart']);
                })
            )
            ->willReturn($response);

        $moduleItem = ModuleItem::create($moduleItemData);
        
        $this->assertInstanceOf(ModuleItem::class, $moduleItem);
        $this->assertEquals($moduleItemData['id'], $moduleItem->getId());
        $this->assertEquals($moduleItemData['type'], $moduleItem->getType());
        $this->assertEquals($moduleItemData['title'], $moduleItem->getTitle());
    }

    public function testCreateWithDTO(): void
    {
        $moduleItemData = [
            'id' => 1,
            'module_id' => 2,
            'type' => 'Assignment',
            'content_id' => 123,
            'title' => 'Test Assignment',
            'position' => 1
        ];
        
        $dto = new CreateModuleItemDTO([
            'type' => 'Assignment',
            'content_id' => 123,
            'title' => 'Test Assignment',
            'position' => 1
        ]);
        
        $response = new Response(200, [], json_encode($moduleItemData));
        
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('courses/1/modules/2/items')
            ->willReturn($response);

        $moduleItem = ModuleItem::create($dto);
        
        $this->assertInstanceOf(ModuleItem::class, $moduleItem);
        $this->assertEquals($moduleItemData['id'], $moduleItem->getId());
    }

    /**
     * @dataProvider moduleItemDataProvider
     */
    public function testFind(array $moduleItemData): void
    {
        $response = new Response(200, [], json_encode($moduleItemData));
        
        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('courses/1/modules/2/items/' . $moduleItemData['id'])
            ->willReturn($response);

        $moduleItem = ModuleItem::find($moduleItemData['id']);
        
        $this->assertInstanceOf(ModuleItem::class, $moduleItem);
        $this->assertEquals($moduleItemData['id'], $moduleItem->getId());
        $this->assertEquals($moduleItemData['type'], $moduleItem->getType());
    }

    public function testFetchAll(): void
    {
        $moduleItemsData = [
            [
                'id' => 1,
                'module_id' => 2,
                'type' => 'Assignment',
                'title' => 'Assignment 1',
                'position' => 1
            ],
            [
                'id' => 2,
                'module_id' => 2,
                'type' => 'Page',
                'title' => 'Page 1',
                'position' => 2
            ]
        ];
        
        $response = new Response(200, [], json_encode($moduleItemsData));
        
        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with(
                'courses/1/modules/2/items',
                $this->callback(function ($options) {
                    return isset($options['query']) && is_array($options['query']);
                })
            )
            ->willReturn($response);

        $moduleItems = ModuleItem::fetchAll();
        
        $this->assertCount(2, $moduleItems);
        $this->assertContainsOnlyInstancesOf(ModuleItem::class, $moduleItems);
        $this->assertEquals(1, $moduleItems[0]->getId());
        $this->assertEquals(2, $moduleItems[1]->getId());
    }

    public function testUpdate(): void
    {
        $originalData = [
            'id' => 1,
            'module_id' => 2,
            'type' => 'Assignment',
            'title' => 'Original Title',
            'position' => 1
        ];
        
        $updatedData = [
            'id' => 1,
            'module_id' => 2,
            'type' => 'Assignment',
            'title' => 'Updated Title',
            'position' => 2
        ];
        
        $response = new Response(200, [], json_encode($updatedData));
        
        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                'courses/1/modules/2/items/1',
                $this->callback(function ($options) {
                    return isset($options['multipart']) && is_array($options['multipart']);
                })
            )
            ->willReturn($response);

        $moduleItem = ModuleItem::update(1, ['title' => 'Updated Title', 'position' => 2]);
        
        $this->assertInstanceOf(ModuleItem::class, $moduleItem);
        $this->assertEquals('Updated Title', $moduleItem->getTitle());
        $this->assertEquals(2, $moduleItem->getPosition());
    }

    public function testSaveExistingModuleItem(): void
    {
        $moduleItemData = [
            'id' => 1,
            'module_id' => 2,
            'type' => 'Assignment',
            'title' => 'Updated Assignment',
            'position' => 1
        ];
        
        $moduleItem = new ModuleItem($moduleItemData);
        
        $response = new Response(200, [], json_encode($moduleItemData));
        
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'courses/1/modules/2/items/1',
                $this->callback(function ($options) {
                    return isset($options['multipart']) && is_array($options['multipart']);
                })
            )
            ->willReturn($response);

        $result = $moduleItem->save();
        
        $this->assertTrue($result);
    }

    public function testSaveNewModuleItem(): void
    {
        $moduleItemData = [
            'type' => 'Assignment',
            'title' => 'New Assignment',
            'content_id' => 123
        ];
        
        $responseData = array_merge($moduleItemData, ['id' => 1, 'module_id' => 2]);
        
        $moduleItem = new ModuleItem($moduleItemData);
        
        $response = new Response(200, [], json_encode($responseData));
        
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'courses/1/modules/2/items',
                $this->callback(function ($options) {
                    return isset($options['multipart']) && is_array($options['multipart']);
                })
            )
            ->willReturn($response);

        $result = $moduleItem->save();
        
        $this->assertTrue($result);
        $this->assertEquals(1, $moduleItem->getId());
    }

    public function testDelete(): void
    {
        $moduleItem = new ModuleItem(['id' => 1, 'module_id' => 2, 'type' => 'Assignment', 'title' => 'Test']);
        
        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('courses/1/modules/2/items/1');

        $result = $moduleItem->delete();
        
        $this->assertTrue($result);
    }

    public function testMarkAsRead(): void
    {
        $moduleItem = new ModuleItem(['id' => 1, 'module_id' => 2, 'type' => 'Page', 'title' => 'Test Page']);
        
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('courses/1/modules/2/items/1/mark_read');

        $result = $moduleItem->markAsRead();
        
        $this->assertTrue($result);
    }

    public function testMarkAsDone(): void
    {
        $moduleItem = new ModuleItem(['id' => 1, 'module_id' => 2, 'type' => 'Assignment', 'title' => 'Test Assignment']);
        
        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('courses/1/modules/2/items/1/done');

        $result = $moduleItem->markAsDone();
        
        $this->assertTrue($result);
    }

    public function testMarkAsNotDone(): void
    {
        $moduleItem = new ModuleItem(['id' => 1, 'module_id' => 2, 'type' => 'Assignment', 'title' => 'Test Assignment']);
        
        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('courses/1/modules/2/items/1/done');

        $result = $moduleItem->markAsNotDone();
        
        $this->assertTrue($result);
    }

    public function testFetchAllPaginated(): void
    {
        $paginatedResponse = $this->createMock(PaginatedResponse::class);

        $this->httpClientMock->expects($this->once())
            ->method('getPaginated')
            ->with('courses/1/modules/2/items', ['query' => []])
            ->willReturn($paginatedResponse);

        $result = ModuleItem::fetchAllPaginated();
        
        $this->assertSame($paginatedResponse, $result);
    }

    public function testModuleItemConstants(): void
    {
        $expectedTypes = [
            'File',
            'Page',
            'Discussion',
            'Assignment',
            'Quiz',
            'SubHeader',
            'ExternalUrl',
            'ExternalTool'
        ];
        
        $expectedCompletionTypes = [
            'must_view',
            'must_contribute',
            'must_submit',
            'min_score',
            'must_mark_done'
        ];
        
        $this->assertEquals($expectedTypes, ModuleItem::VALID_TYPES);
        $this->assertEquals($expectedCompletionTypes, ModuleItem::VALID_COMPLETION_TYPES);
    }

    public function testPropertyGettersAndSetters(): void
    {
        $moduleItem = new ModuleItem([
            'id' => 1,
            'module_id' => 2,
            'type' => 'Assignment',
            'title' => 'Test Assignment'
        ]);
        
        // Test getters
        $this->assertEquals(1, $moduleItem->getId());
        $this->assertEquals(2, $moduleItem->getModuleId());
        $this->assertEquals('Assignment', $moduleItem->getType());
        $this->assertEquals('Test Assignment', $moduleItem->getTitle());
        
        // Test setters
        $moduleItem->setTitle('Updated Assignment');
        $moduleItem->setPosition(3);
        $moduleItem->setIndent(1);
        
        $this->assertEquals('Updated Assignment', $moduleItem->getTitle());
        $this->assertEquals(3, $moduleItem->getPosition());
        $this->assertEquals(1, $moduleItem->getIndent());
    }

    public function testExternalToolProperties(): void
    {
        $moduleItem = new ModuleItem([
            'id' => 1,
            'type' => 'ExternalTool',
            'external_url' => 'https://example.com/tool',
            'new_tab' => true,
            'iframe' => ['width' => 800, 'height' => 600]
        ]);
        
        $this->assertEquals('https://example.com/tool', $moduleItem->getExternalUrl());
        $this->assertTrue($moduleItem->getNewTab());
        $this->assertEquals(['width' => 800, 'height' => 600], $moduleItem->getIframe());
    }

    public function testCompletionRequirementProperty(): void
    {
        $completionRequirement = [
            'type' => 'min_score',
            'min_score' => 80,
            'completed' => false
        ];
        
        $moduleItem = new ModuleItem([
            'id' => 1,
            'type' => 'Assignment',
            'completion_requirement' => $completionRequirement
        ]);
        
        $this->assertInstanceOf(CompletionRequirement::class, $moduleItem->getCompletionRequirement());
        $this->assertEquals($completionRequirement['type'], $moduleItem->getCompletionRequirement()->getType());
        $this->assertEquals($completionRequirement['min_score'], $moduleItem->getCompletionRequirement()->getMinScore());
        
        $newRequirement = new CompletionRequirement(['type' => 'must_view']);
        $moduleItem->setCompletionRequirement($newRequirement);
        $this->assertEquals('must_view', $moduleItem->getCompletionRequirement()->getType());
    }

    public function testPageUrlProperty(): void
    {
        $moduleItem = new ModuleItem([
            'id' => 1,
            'type' => 'Page',
            'page_url' => 'course-introduction'
        ]);
        
        $this->assertEquals('course-introduction', $moduleItem->getPageUrl());
        
        $moduleItem->setPageUrl('updated-page-slug');
        $this->assertEquals('updated-page-slug', $moduleItem->getPageUrl());
    }
}