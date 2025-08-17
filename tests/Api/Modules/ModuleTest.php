<?php

declare(strict_types=1);

namespace Tests\Api\Modules;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Modules\Module;
use CanvasLMS\Api\Modules\ModuleItem;
use CanvasLMS\Api\Modules\ModuleAssignmentOverride;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Modules\CreateModuleDTO;
use CanvasLMS\Dto\Modules\UpdateModuleDTO;
use CanvasLMS\Dto\Modules\CreateModuleItemDTO;
use CanvasLMS\Dto\Modules\BulkUpdateModuleAssignmentOverridesDTO;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ModuleTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        Module::setApiClient($this->httpClient);
        
        $this->course = new Course(['id' => 1]);
        Module::setCourse($this->course);
    }

    public function testSetCourse(): void
    {
        $course = new Course(['id' => 123]);
        Module::setCourse($course);
        
        // Test that course is required for operations
        $this->expectNotToPerformAssertions();
    }

    public function testCheckCourseThrowsExceptionWhenCourseNotSet(): void
    {
        $this->markTestSkipped('Cannot test unset course with typed static property in PHP 8');
    }

    public function testConstructor(): void
    {
        $data = [
            'id' => 123,
            'workflow_state' => 'active',
            'position' => 1,
            'name' => 'Test Module',
            'unlock_at' => '2024-01-01T00:00:00Z',
            'require_sequential_progress' => true,
            'requirement_type' => 'all',
            'prerequisite_module_ids' => [1, 2],
            'items_count' => 5,
            'items_url' => 'https://canvas.example.com/api/v1/courses/1/modules/123/items',
            'items' => [],
            'state' => 'unlocked',
            'completed_at' => null,
            'publish_final_grade' => false,
            'published' => true
        ];

        $module = new Module($data);

        $this->assertEquals(123, $module->getId());
        $this->assertEquals('active', $module->getWorkflowState());
        $this->assertEquals(1, $module->getPosition());
        $this->assertEquals('Test Module', $module->getName());
        $this->assertEquals('2024-01-01T00:00:00Z', $module->getUnlockAt());
        $this->assertTrue($module->isRequireSequentialProgress());
        $this->assertEquals('all', $module->getRequirementType());
        $this->assertEquals([1, 2], $module->getPrerequisiteModuleIds());
        $this->assertEquals(5, $module->getItemsCount());
        $this->assertEquals('https://canvas.example.com/api/v1/courses/1/modules/123/items', $module->getItemsUrl());
        $this->assertEquals([], $module->getItems());
        $this->assertEquals('unlocked', $module->getState());
        $this->assertNull($module->getCompletedAt());
        $this->assertFalse($module->getPublishFinalGrade());
        $this->assertTrue($module->getPublished());
    }

    public function testGettersAndSetters(): void
    {
        $module = new Module();

        $module->setId(456);
        $this->assertEquals(456, $module->getId());

        $module->setWorkflowState('deleted');
        $this->assertEquals('deleted', $module->getWorkflowState());

        $module->setPosition(3);
        $this->assertEquals(3, $module->getPosition());

        $module->setName('Updated Module');
        $this->assertEquals('Updated Module', $module->getName());

        $module->setUnlockAt('2024-12-31T23:59:59Z');
        $this->assertEquals('2024-12-31T23:59:59Z', $module->getUnlockAt());

        $module->setRequireSequentialProgress(false);
        $this->assertFalse($module->isRequireSequentialProgress());

        $module->setRequirementType('one');
        $this->assertEquals('one', $module->getRequirementType());

        $module->setPrerequisiteModuleIds([3, 4, 5]);
        $this->assertEquals([3, 4, 5], $module->getPrerequisiteModuleIds());

        $module->setItemsCount(10);
        $this->assertEquals(10, $module->getItemsCount());

        $module->setItemsUrl('https://example.com/items');
        $this->assertEquals('https://example.com/items', $module->getItemsUrl());

        $module->setItems([['id' => 1], ['id' => 2]]);
        $this->assertEquals([['id' => 1], ['id' => 2]], $module->getItems());

        $module->setState('completed');
        $this->assertEquals('completed', $module->getState());

        $module->setCompletedAt('2024-06-01T12:00:00Z');
        $this->assertEquals('2024-06-01T12:00:00Z', $module->getCompletedAt());

        $module->setPublishFinalGrade(true);
        $this->assertTrue($module->getPublishFinalGrade());

        $module->setPublished(false);
        $this->assertFalse($module->getPublished());
    }

    public function testFind(): void
    {
        $moduleData = [
            'id' => 123,
            'name' => 'Test Module',
            'workflow_state' => 'active',
            'position' => 1
        ];

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($moduleData));
        $response->method('getBody')->willReturn($stream);
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/1/modules/123', ['query' => []])
            ->willReturn($response);

        $module = Module::find(123);

        $this->assertInstanceOf(Module::class, $module);
        $this->assertEquals(123, $module->getId());
        $this->assertEquals('Test Module', $module->getName());
    }

    public function testFindWithParams(): void
    {
        $moduleData = [
            'id' => 123,
            'name' => 'Test Module',
            'items' => [['id' => 1], ['id' => 2]]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($moduleData));
        $response->method('getBody')->willReturn($stream);
        
        $params = ['include' => ['items']];
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/1/modules/123', ['query' => $params])
            ->willReturn($response);

        $module = Module::find(123, $params);

        $this->assertInstanceOf(Module::class, $module);
        $this->assertEquals([['id' => 1], ['id' => 2]], $module->getItems());
    }

    public function testFetchAll(): void
    {
        $modulesData = [
            ['id' => 1, 'name' => 'Module 1'],
            ['id' => 2, 'name' => 'Module 2']
        ];

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($modulesData));
        $response->method('getBody')->willReturn($stream);
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/1/modules', ['query' => []])
            ->willReturn($response);

        $modules = Module::fetchAll();

        $this->assertCount(2, $modules);
        $this->assertInstanceOf(Module::class, $modules[0]);
        $this->assertEquals('Module 1', $modules[0]->getName());
        $this->assertEquals('Module 2', $modules[1]->getName());
    }

    public function testFetchAllWithParams(): void
    {
        $modulesData = [
            ['id' => 1, 'name' => 'Introduction Module']
        ];

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($modulesData));
        $response->method('getBody')->willReturn($stream);
        
        $params = [
            'include' => ['items', 'content_details'],
            'search_term' => 'Introduction',
            'student_id' => '123'
        ];
        
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/1/modules', ['query' => $params])
            ->willReturn($response);

        $modules = Module::fetchAll($params);

        $this->assertCount(1, $modules);
        $this->assertEquals('Introduction Module', $modules[0]->getName());
    }

    public function testFetchAllPaginated(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode([['id' => 1, 'name' => 'Module 1']]));
        $response->method('getBody')->willReturn($stream);
        $response->method('getHeader')
            ->with('Link')
            ->willReturn(['<https://canvas.example.com/api/v1/courses/1/modules?page=2>; rel="next"']);
        
        $paginatedResponse = new PaginatedResponse($response, $this->httpClient);
        
        $this->httpClient->expects($this->once())
            ->method('getPaginated')
            ->with('courses/1/modules', ['query' => ['per_page' => 10]])
            ->willReturn($paginatedResponse);

        $result = Module::fetchAllPaginated(['per_page' => 10]);

        $this->assertInstanceOf(PaginatedResponse::class, $result);
        $data = $result->getJsonData();
        $this->assertCount(1, $data);
    }

    public function testCreate(): void
    {
        $createData = [
            'name' => 'New Module',
            'position' => 1,
            'requireSequentialProgress' => true,
            'prerequisiteModuleIds' => [1, 2],
            'publishFinalGrade' => false
        ];

        // Mock the fetchAll request for prerequisite validation
        $existingModules = [
            ['id' => 1, 'position' => 0],
            ['id' => 2, 'position' => 0]
        ];
        
        $fetchResponse = $this->createMock(ResponseInterface::class);
        $fetchStream = $this->createMock(StreamInterface::class);
        $fetchStream->method('getContents')->willReturn(json_encode($existingModules));
        $fetchResponse->method('getBody')->willReturn($fetchStream);

        $responseData = array_merge(['id' => 123], $createData);

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($responseData));
        $response->method('getBody')->willReturn($stream);

        // Mock the get request for prerequisite validation
        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/1/modules', ['query' => []])
            ->willReturn($fetchResponse);
            
        $this->httpClient->expects($this->once())
            ->method('post')
            ->with(
                'courses/1/modules',
                $this->callback(function ($options) {
                    $this->assertArrayHasKey('multipart', $options);
                    return true;
                })
            )
            ->willReturn($response);

        $module = Module::create($createData);

        $this->assertInstanceOf(Module::class, $module);
        $this->assertEquals(123, $module->getId());
        $this->assertEquals('New Module', $module->getName());
    }

    public function testCreateWithDTO(): void
    {
        $dto = new CreateModuleDTO([
            'name' => 'New Module',
            'position' => 1
        ]);

        $responseData = ['id' => 123, 'name' => 'New Module', 'position' => 1];

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($responseData));
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $module = Module::create($dto);

        $this->assertInstanceOf(Module::class, $module);
        $this->assertEquals(123, $module->getId());
    }

    public function testCreateWithPrerequisiteValidation(): void
    {
        // First, mock fetching existing modules for validation
        $existingModules = [
            ['id' => 1, 'position' => 1],
            ['id' => 2, 'position' => 2]
        ];

        $fetchResponse = $this->createMock(ResponseInterface::class);
        $fetchStream = $this->createMock(StreamInterface::class);
        
        $fetchStream->method('getContents')->willReturn(json_encode($existingModules));
        $fetchResponse->method('getBody')->willReturn($fetchStream);

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/1/modules', ['query' => []])
            ->willReturn($fetchResponse);

        // Try to create a module with invalid prerequisite (position 1 with prereq at position 2)
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Prerequisite module 2 must have a lower position than 1');

        Module::create([
            'name' => 'New Module',
            'position' => 1,
            'prerequisiteModuleIds' => [2]
        ]);
    }

    public function testUpdate(): void
    {
        $updateData = [
            'name' => 'Updated Module',
            'position' => 2,
            'published' => true
        ];

        $responseData = array_merge(['id' => 123], $updateData);

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($responseData));
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->expects($this->once())
            ->method('put')
            ->with(
                'courses/1/modules/123',
                $this->callback(function ($options) {
                    $this->assertArrayHasKey('multipart', $options);
                    return true;
                })
            )
            ->willReturn($response);

        $module = Module::update(123, $updateData);

        $this->assertInstanceOf(Module::class, $module);
        $this->assertEquals('Updated Module', $module->getName());
        $this->assertEquals(2, $module->getPosition());
    }

    public function testSaveNewModule(): void
    {
        $module = new Module([
            'name' => 'New Module',
            'position' => 1
        ]);

        $responseData = [
            'id' => 123,
            'name' => 'New Module',
            'position' => 1
        ];

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($responseData));
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'courses/1/modules')
            ->willReturn($response);

        $result = $module->save();

        $this->assertInstanceOf(Module::class, $result);
        $this->assertEquals(123, $module->getId());
    }

    public function testSaveExistingModule(): void
    {
        $module = new Module([
            'id' => 123,
            'name' => 'Updated Module',
            'position' => 2
        ]);

        $responseData = [
            'id' => 123,
            'name' => 'Updated Module',
            'position' => 2
        ];

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($responseData));
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('PUT', 'courses/1/modules/123')
            ->willReturn($response);

        $result = $module->save();

        $this->assertInstanceOf(Module::class, $result);
    }

    public function testSaveReturnsFalseOnException(): void
    {
        $module = new Module(['name' => 'Test Module']);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new CanvasApiException('API Error'));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('API Error');
        $module->save();
    }

    public function testDelete(): void
    {
        $module = new Module(['id' => 123]);

        $response = $this->createMock(ResponseInterface::class);
        
        $this->httpClient->expects($this->once())
            ->method('delete')
            ->with('courses/1/modules/123')
            ->willReturn($response);

        $result = $module->delete();

        $this->assertInstanceOf(Module::class, $result);
    }

    public function testDeleteReturnsFalseOnException(): void
    {
        $module = new Module(['id' => 123]);

        $this->httpClient->expects($this->once())
            ->method('delete')
            ->willThrowException(new CanvasApiException('Delete failed'));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Delete failed');
        $module->delete();
    }

    public function testRelock(): void
    {
        $module = new Module(['id' => 123]);

        $responseData = [
            'id' => 123,
            'name' => 'Test Module',
            'state' => 'locked'
        ];

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($responseData));
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->expects($this->once())
            ->method('put')
            ->with('courses/1/modules/123/relock')
            ->willReturn($response);

        $result = $module->relock();

        $this->assertInstanceOf(Module::class, $result);
        $this->assertEquals('locked', $module->getState());
    }

    public function testRelockReturnsFalseOnException(): void
    {
        $module = new Module(['id' => 123]);

        $this->httpClient->expects($this->once())
            ->method('put')
            ->willThrowException(new CanvasApiException('Relock failed'));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Relock failed');
        $module->relock();
    }

    public function testItems(): void
    {
        $module = new Module(['id' => 123]);

        $itemsData = [
            ['id' => 1, 'title' => 'Item 1'],
            ['id' => 2, 'title' => 'Item 2']
        ];

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($itemsData));
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/1/modules/123/items', ['query' => []])
            ->willReturn($response);

        $items = $module->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(ModuleItem::class, $items[0]);
    }

    public function testItemsWithParams(): void
    {
        $module = new Module(['id' => 123]);

        $itemsData = [
            ['id' => 1, 'title' => 'Item 1', 'content_details' => ['points_possible' => 10]]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($itemsData));
        $response->method('getBody')->willReturn($stream);

        $params = ['include' => ['content_details']];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/1/modules/123/items', ['query' => $params])
            ->willReturn($response);

        $items = $module->items($params);

        $this->assertCount(1, $items);
    }

    public function testCreateModuleItem(): void
    {
        $module = new Module(['id' => 123]);

        $itemData = [
            'title' => 'New Assignment',
            'type' => 'Assignment',
            'content_id' => 456
        ];

        $responseData = array_merge(['id' => 789], $itemData);

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($responseData));
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->expects($this->once())
            ->method('post')
            ->with('courses/1/modules/123/items')
            ->willReturn($response);

        $item = $module->createModuleItem($itemData);

        $this->assertInstanceOf(ModuleItem::class, $item);
    }

    public function testListOverrides(): void
    {
        $module = new Module(['id' => 123]);

        $overridesData = [
            ['id' => 1, 'title' => 'Section Override', 'course_section' => ['id' => 10]],
            ['id' => 2, 'title' => 'Student Override', 'students' => [['id' => 20]]]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        
        $stream->method('getContents')->willReturn(json_encode($overridesData));
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with('courses/1/modules/123/assignment_overrides', ['query' => []])
            ->willReturn($response);

        $overrides = $module->listOverrides();

        $this->assertCount(2, $overrides);
        $this->assertInstanceOf(ModuleAssignmentOverride::class, $overrides[0]);
        $this->assertEquals('Section Override', $overrides[0]->getTitle());
    }

    public function testBulkUpdateOverridesWithDTO(): void
    {
        $module = new Module(['id' => 123]);

        $dto = new BulkUpdateModuleAssignmentOverridesDTO();
        $dto->addSectionOverride(10)
            ->addStudentOverride([20, 30], null, 'Special Students');

        $response = $this->createMock(ResponseInterface::class);

        $this->httpClient->expects($this->once())
            ->method('put')
            ->with(
                'courses/1/modules/123/assignment_overrides',
                $this->callback(function ($options) {
                    $this->assertArrayHasKey('json', $options);
                    $this->assertArrayHasKey('overrides', $options['json']);
                    $this->assertCount(2, $options['json']['overrides']);
                    return true;
                })
            )
            ->willReturn($response);

        $result = $module->bulkUpdateOverrides($dto);

        $this->assertInstanceOf(Module::class, $result);
    }

    public function testBulkUpdateOverridesWithArray(): void
    {
        $module = new Module(['id' => 123]);

        $overrides = [
            ['course_section_id' => 10],
            ['student_ids' => [20, 30], 'title' => 'Special Students']
        ];

        $response = $this->createMock(ResponseInterface::class);

        $this->httpClient->expects($this->once())
            ->method('put')
            ->with(
                'courses/1/modules/123/assignment_overrides',
                $this->callback(function ($options) {
                    $this->assertArrayHasKey('json', $options);
                    $this->assertArrayHasKey('overrides', $options['json']);
                    $this->assertCount(2, $options['json']['overrides']);
                    return true;
                })
            )
            ->willReturn($response);

        $result = $module->bulkUpdateOverrides($overrides);

        $this->assertInstanceOf(Module::class, $result);
    }

    public function testBulkUpdateOverridesReturnsFalseOnException(): void
    {
        $module = new Module(['id' => 123]);

        $this->httpClient->expects($this->once())
            ->method('put')
            ->willThrowException(new CanvasApiException('Update failed'));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Update failed');
        $module->bulkUpdateOverrides([]);
    }

    public function testToDtoArray(): void
    {
        $module = new Module([
            'id' => 123,
            'name' => 'Test Module',
            'position' => 1,
            'unlockAt' => '2024-01-01',
            'requireSequentialProgress' => true,
            'prerequisiteModuleIds' => [1, 2],
            'publishFinalGrade' => false,
            'published' => true
        ]);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($module);
        $method = $reflection->getMethod('toDtoArray');
        $method->setAccessible(true);
        $dtoArray = $method->invoke($module);

        $this->assertEquals([
            'id' => 123,
            'name' => 'Test Module',
            'position' => 1,
            'unlockAt' => '2024-01-01',
            'requireSequentialProgress' => true,
            'prerequisiteModuleIds' => [1, 2],
            'publishFinalGrade' => false,
            'published' => true
        ], $dtoArray);
    }
}