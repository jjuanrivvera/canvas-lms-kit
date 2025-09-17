<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\GroupCategories;

use CanvasLMS\Api\GroupCategories\GroupCategory;
use CanvasLMS\Api\Groups\Group;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Dto\GroupCategories\CreateGroupCategoryDTO;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GroupCategoryTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;

    private ResponseInterface $mockResponse;

    private StreamInterface $mockStream;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);

        GroupCategory::setApiClient($this->mockHttpClient);
        \CanvasLMS\Config::setAccountId(1);
    }

    public function testConstructor(): void
    {
        $data = [
            'id' => 123,
            'name' => 'Test Category',
            'role' => 'communities',
            'self_signup' => 'enabled',
            'auto_leader' => 'random',
            'context_type' => 'Course',
            'context_id' => 456,
            'course_id' => 456,
            'group_limit' => 5,
            'sis_group_category_id' => 'SIS123',
            'sis_import_id' => 999,
            'progress' => ['completion' => 50],
            'non_collaborative' => false,
        ];

        $category = new GroupCategory($data);

        $this->assertEquals(123, $category->id);
        $this->assertEquals('Test Category', $category->name);
        $this->assertEquals('communities', $category->role);
        $this->assertEquals('enabled', $category->selfSignup);
        $this->assertEquals('random', $category->autoLeader);
        $this->assertEquals('Course', $category->contextType);
        $this->assertEquals(456, $category->contextId);
        $this->assertEquals(456, $category->courseId);
        $this->assertEquals(5, $category->groupLimit);
        $this->assertEquals('SIS123', $category->sisGroupCategoryId);
        $this->assertEquals(999, $category->sisImportId);
        $this->assertIsArray($category->progress);
        $this->assertFalse($category->nonCollaborative);
    }

    public function testFind(): void
    {
        $categoryData = [
            'id' => 123,
            'name' => 'Test Category',
            'role' => 'communities',
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($categoryData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('group_categories/123')
            ->willReturn($this->mockResponse);

        $category = GroupCategory::find(123);

        $this->assertInstanceOf(GroupCategory::class, $category);
        $this->assertEquals(123, $category->id);
        $this->assertEquals('Test Category', $category->name);
    }

    public function testGet(): void
    {
        $categoriesData = [
            ['id' => 1, 'name' => 'Category 1'],
            ['id' => 2, 'name' => 'Category 2'],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($categoriesData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('accounts/1/group_categories', ['query' => []])
            ->willReturn($this->mockResponse);

        $categories = GroupCategory::get();

        $this->assertIsArray($categories);
        $this->assertCount(2, $categories);
        $this->assertInstanceOf(GroupCategory::class, $categories[0]);
        $this->assertEquals('Category 1', $categories[0]->name);
    }

    public function testGetFromAccount(): void
    {
        $categoriesData = [
            ['id' => 1, 'name' => 'Account Category 1'],
            ['id' => 2, 'name' => 'Account Category 2'],
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($categoriesData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('accounts/1/group_categories', ['query' => []])
            ->willReturn($this->mockResponse);

        $categories = GroupCategory::get();

        $this->assertIsArray($categories);
        $this->assertCount(2, $categories);
        $this->assertInstanceOf(GroupCategory::class, $categories[0]);
        $this->assertEquals('Account Category 1', $categories[0]->name);
    }

    public function testCreate(): void
    {
        $createData = [
            'name' => 'New Category',
            'self_signup' => 'enabled',
            'group_limit' => 5,
        ];

        $responseData = array_merge($createData, ['id' => 123]);

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->with('accounts/1/group_categories', $this->callback(function ($options) {
                return isset($options['multipart']) && is_array($options['multipart']);
            }))
            ->willReturn($this->mockResponse);

        $category = GroupCategory::create($createData);

        $this->assertInstanceOf(GroupCategory::class, $category);
        $this->assertEquals(123, $category->id);
        $this->assertEquals('New Category', $category->name);
    }

    public function testCreateWithDTO(): void
    {
        $dto = new CreateGroupCategoryDTO(['name' => 'DTO Category']);
        $dto->self_signup = 'restricted';
        $dto->group_limit = 3;

        $responseData = [
            'id' => 123,
            'name' => 'DTO Category',
            'self_signup' => 'restricted',
            'group_limit' => 3,
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->willReturn($this->mockResponse);

        $category = GroupCategory::create($dto);

        $this->assertInstanceOf(GroupCategory::class, $category);
        $this->assertEquals('restricted', $category->selfSignup);
    }

    public function testUpdate(): void
    {
        $updateData = [
            'name' => 'Updated Category',
            'self_signup' => 'disabled',
        ];

        $responseData = array_merge($updateData, ['id' => 123]);

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('put')
            ->with('group_categories/123', $this->callback(function ($options) {
                return isset($options['multipart']) && is_array($options['multipart']);
            }))
            ->willReturn($this->mockResponse);

        $category = GroupCategory::update(123, $updateData);

        $this->assertInstanceOf(GroupCategory::class, $category);
        $this->assertEquals('Updated Category', $category->name);
        $this->assertEquals('disabled', $category->selfSignup);
    }

    public function testDelete(): void
    {
        $category = new GroupCategory(['id' => 123]);

        $this->mockHttpClient->expects($this->once())
            ->method('delete')
            ->with('group_categories/123')
            ->willReturn($this->mockResponse);

        $result = $category->delete();

        $this->assertInstanceOf(GroupCategory::class, $result);
    }

    public function testSave(): void
    {
        $category = new GroupCategory(['name' => 'Test Category']);

        $responseData = [
            'id' => 123,
            'name' => 'Test Category',
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->willReturn($this->mockResponse);

        $result = $category->save();

        $this->assertInstanceOf(GroupCategory::class, $result);
        $this->assertEquals(123, $category->id);
    }

    public function testGroups(): void
    {
        $category = new GroupCategory(['id' => 123]);

        $groupsData = [
            ['id' => 1, 'name' => 'Group 1'],
            ['id' => 2, 'name' => 'Group 2'],
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('all')
            ->willReturn($groupsData);

        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('group_categories/123/groups', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $groups = $category->groups();

        $this->assertIsArray($groups);
        $this->assertCount(2, $groups);
        $this->assertInstanceOf(Group::class, $groups[0]);
    }

    public function testGroupsPaginated(): void
    {
        $category = new GroupCategory(['id' => 123]);

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);

        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('group_categories/123/groups', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $result = $category->groupsPaginated();

        $this->assertSame($mockPaginatedResponse, $result);
    }

    public function testUsers(): void
    {
        $category = new GroupCategory(['id' => 123]);

        $usersData = [
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2'],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($usersData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('group_categories/123/users', ['query' => ['unassigned' => true]])
            ->willReturn($this->mockResponse);

        $users = $category->users(['unassigned' => true]);

        $this->assertIsArray($users);
        $this->assertCount(2, $users);
        $this->assertInstanceOf(User::class, $users[0]);
    }

    public function testAssignUnassignedMembers(): void
    {
        $category = new GroupCategory(['id' => 123]);

        $progressData = [
            'id' => 456,
            'workflow_state' => 'running',
            'completion' => 0,
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($progressData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->with('group_categories/123/assign_unassigned_members', [])
            ->willReturn($this->mockResponse);

        $result = $category->assignUnassignedMembers();

        $this->assertIsArray($result);
        $this->assertEquals(456, $result['id']);
        $this->assertEquals('running', $result['workflow_state']);
        $this->assertEquals(0, $result['completion']);
    }

    public function testAssignUnassignedMembersSync(): void
    {
        $category = new GroupCategory(['id' => 123]);

        $groupsData = [
            ['id' => 1, 'name' => 'Group 1'],
            ['id' => 2, 'name' => 'Group 2'],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($groupsData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->with('group_categories/123/assign_unassigned_members', ['multipart' => [['name' => 'sync', 'contents' => 'true']]])
            ->willReturn($this->mockResponse);

        $groups = $category->assignUnassignedMembers(true);

        $this->assertIsArray($groups);
        $this->assertCount(2, $groups);
        $this->assertInstanceOf(Group::class, $groups[0]);
    }

    public function testExport(): void
    {
        $category = new GroupCategory(['id' => 123]);

        $exportData = [
            ['group_name' => 'Group 1', 'user_name' => 'User 1'],
            ['group_name' => 'Group 2', 'user_name' => 'User 2'],
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($exportData));

        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);

        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('group_categories/123/export')
            ->willReturn($this->mockResponse);

        $export = $category->export();

        $this->assertIsArray($export);
        $this->assertCount(2, $export);
        $this->assertEquals('Group 1', $export[0]['group_name']);
        $this->assertEquals('User 1', $export[0]['user_name']);
    }

    public function testToDtoArray(): void
    {
        $category = new GroupCategory([
            'id' => 123,
            'name' => 'Test Category',
            'self_signup' => 'enabled',
            'auto_leader' => 'first',
            'group_limit' => 5,
            'context_type' => 'Course',
            'context_id' => 456,
        ]);

        $array = $category->toDtoArray();

        $this->assertIsArray($array);
        $this->assertEquals('Test Category', $array['name']);
        $this->assertEquals('enabled', $array['self_signup']);
        $this->assertEquals('first', $array['auto_leader']);
        $this->assertEquals(5, $array['group_limit']);
        $this->assertArrayNotHasKey('id', $array);
        $this->assertArrayNotHasKey('context_type', $array);
        $this->assertArrayNotHasKey('context_id', $array);
    }

    public function testGettersAndSetters(): void
    {
        $category = new GroupCategory([]);

        $category->name = 'New Name';
        $this->assertEquals('New Name', $category->name);

        $category->selfSignup = 'enabled';
        $this->assertEquals('enabled', $category->selfSignup);

        $category->groupLimit = 10;
        $this->assertEquals(10, $category->groupLimit);

        $category->autoLeader = 'random';
        $this->assertEquals('random', $category->autoLeader);
    }
}
