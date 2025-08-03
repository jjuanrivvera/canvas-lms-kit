<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Modules;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Modules\Module;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Modules\ModuleItem;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ModuleRelationshipTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;
    private ResponseInterface $mockResponse;
    private StreamInterface $mockStream;
    private Course $mockCourse;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);
        $this->mockCourse = $this->createMock(Course::class);
        $this->mockCourse->id = 123;

        // Set up the API client
        Module::setApiClient($this->mockHttpClient);
        Module::setCourse($this->mockCourse);
    }

    protected function tearDown(): void
    {
        // Reset course context by setting a new empty course
        Module::setCourse(new Course([]));
        parent::tearDown();
    }

    public function testCourseReturnsAssociatedCourse(): void
    {
        // Create test module
        $module = new Module(['id' => 456, 'name' => 'Test Module']);

        // Test the method
        $course = $module->course();

        // Assertions
        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals(123, $course->id);
        $this->assertSame($this->mockCourse, $course);
    }

    public function testItemsReturnsArrayOfModuleItemObjects(): void
    {
        // Create test module
        $module = new Module(['id' => 456, 'name' => 'Test Module']);

        // Mock response data
        $itemsData = [
            [
                'id' => 1,
                'module_id' => 456,
                'title' => 'Item 1',
                'type' => 'Assignment',
                'content_id' => 789
            ],
            [
                'id' => 2,
                'module_id' => 456,
                'title' => 'Item 2',
                'type' => 'Page',
                'content_id' => 790
            ]
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($itemsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/modules/456/items', ['query' => []])
            ->willReturn($this->mockResponse);

        // Test the method
        $items = $module->items();

        // Assertions
        $this->assertIsArray($items);
        $this->assertCount(2, $items);
        $this->assertInstanceOf(ModuleItem::class, $items[0]);
        $this->assertEquals(1, $items[0]->id);
        $this->assertEquals('Item 1', $items[0]->title);
    }

    public function testItemsWithParametersPassesQueryParams(): void
    {
        // Create test module
        $module = new Module(['id' => 456, 'name' => 'Test Module']);

        // Mock response data
        $itemsData = [
            ['id' => 1, 'module_id' => 456, 'title' => 'Item 1']
        ];

        // Set up mock expectations
        $this->mockStream->method('getContents')
            ->willReturn(json_encode($itemsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $params = ['include' => ['content_details'], 'per_page' => 50];

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('courses/123/modules/456/items', ['query' => $params])
            ->willReturn($this->mockResponse);

        // Test the method
        $items = $module->items($params);

        // Assertions
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
    }

    public function testPrerequisitesReturnsArrayOfModuleObjects(): void
    {
        // Create test module with prerequisite IDs
        $module = new Module([
            'id' => 456,
            'name' => 'Test Module',
            'prerequisite_module_ids' => [100, 200]
        ]);

        // Mock response data for first prerequisite
        $module1Data = [
            'id' => 100,
            'name' => 'Prerequisite Module 1',
            'position' => 1
        ];

        // Mock response data for second prerequisite
        $module2Data = [
            'id' => 200,
            'name' => 'Prerequisite Module 2',
            'position' => 2
        ];

        // Set up mock expectations for both module fetches
        $callCount = 0;
        $this->mockStream->expects($this->exactly(2))
            ->method('getContents')
            ->willReturnCallback(function () use (&$callCount, $module1Data, $module2Data) {
                $callCount++;
                return $callCount === 1 ? json_encode($module1Data) : json_encode($module2Data);
            });

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function ($endpoint) {
                if ($endpoint === 'courses/123/modules/100' || $endpoint === 'courses/123/modules/200') {
                    return $this->mockResponse;
                }
                throw new \Exception('Unexpected endpoint: ' . $endpoint);
            });

        // Test the method
        $prerequisites = $module->prerequisites();

        // Assertions
        $this->assertIsArray($prerequisites);
        $this->assertCount(2, $prerequisites);
        $this->assertInstanceOf(Module::class, $prerequisites[0]);
        $this->assertEquals(100, $prerequisites[0]->id);
        $this->assertEquals('Prerequisite Module 1', $prerequisites[0]->name);
        $this->assertInstanceOf(Module::class, $prerequisites[1]);
        $this->assertEquals(200, $prerequisites[1]->id);
        $this->assertEquals('Prerequisite Module 2', $prerequisites[1]->name);
    }

    public function testPrerequisitesReturnsEmptyArrayWhenNoPrerequisites(): void
    {
        // Create test module without prerequisite IDs
        $module = new Module(['id' => 456, 'name' => 'Test Module']);

        // Test the method
        $prerequisites = $module->prerequisites();

        // Assertions
        $this->assertIsArray($prerequisites);
        $this->assertEmpty($prerequisites);
    }

    public function testPrerequisitesHandlesErrorsGracefully(): void
    {
        // Create test module with prerequisite IDs
        $module = new Module([
            'id' => 456,
            'name' => 'Test Module',
            'prerequisite_module_ids' => [100, 200]
        ]);

        // Mock first call to succeed
        $module1Data = ['id' => 100, 'name' => 'Prerequisite Module 1'];
        
        $this->mockStream->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($module1Data));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        // Set up mock to succeed on first call, throw exception on second
        $this->mockHttpClient->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function ($endpoint) {
                if ($endpoint === 'courses/123/modules/100') {
                    return $this->mockResponse;
                } else {
                    throw new CanvasApiException('Module not found');
                }
            });

        // Test the method
        $prerequisites = $module->prerequisites();

        // Assertions - should only have the successful module
        $this->assertIsArray($prerequisites);
        $this->assertCount(1, $prerequisites);
        $this->assertEquals(100, $prerequisites[0]->id);
    }

    public function testItemsThrowsExceptionWhenModuleIdMissing(): void
    {
        // Create module without ID
        $module = new Module(['name' => 'Test Module']);

        // Test items
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Module is required');
        $module->items();
    }

}