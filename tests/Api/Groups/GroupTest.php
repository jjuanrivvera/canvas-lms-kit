<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Groups;

use CanvasLMS\Api\Groups\Group;
use CanvasLMS\Api\Groups\GroupMembership;
use CanvasLMS\Dto\Groups\CreateGroupDTO;
use CanvasLMS\Dto\Groups\UpdateGroupDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GroupTest extends TestCase
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
        
        Group::setApiClient($this->mockHttpClient);
        \CanvasLMS\Config::setAccountId(1);
    }

    public function testConstructor(): void
    {
        $data = [
            'id' => 123,
            'name' => 'Test Group',
            'description' => 'Test Description',
            'is_public' => true,
            'followed_by_user' => false,
            'join_level' => 'invitation_only',
            'members_count' => 10,
            'avatar_url' => 'https://example.com/avatar.png',
            'context_type' => 'Course',
            'course_id' => 456,
            'role' => 'communities',
            'group_category_id' => 789,
            'sis_group_id' => 'SIS123',
            'sis_import_id' => 999,
            'storage_quota_mb' => 50,
            'permissions' => ['create_discussion_topic' => true],
            'has_submission' => true,
            'context_name' => 'Test Course',
            'users' => [['id' => 1, 'name' => 'User 1']],
            'non_collaborative' => false
        ];

        $group = new Group($data);

        $this->assertEquals(123, $group->id);
        $this->assertEquals('Test Group', $group->name);
        $this->assertEquals('Test Description', $group->description);
        $this->assertTrue($group->isPublic);
        $this->assertFalse($group->followedByUser);
        $this->assertEquals('invitation_only', $group->joinLevel);
        $this->assertEquals(10, $group->membersCount);
        $this->assertEquals('Test Course', $group->contextName);
        $this->assertIsArray($group->users);
        $this->assertFalse($group->nonCollaborative);
    }

    public function testFind(): void
    {
        $groupData = [
            'id' => 123,
            'name' => 'Test Group',
            'description' => 'Test Description'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($groupData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('groups/123')
            ->willReturn($this->mockResponse);

        $group = Group::find(123);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals(123, $group->id);
        $this->assertEquals('Test Group', $group->name);
    }

    public function testGet(): void
    {
        $groupsData = [
            ['id' => 1, 'name' => 'Group 1'],
            ['id' => 2, 'name' => 'Group 2']
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($groupsData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('accounts/1/groups', ['query' => []])
            ->willReturn($this->mockResponse);

        $groups = Group::get();

        $this->assertIsArray($groups);
        $this->assertCount(2, $groups);
        $this->assertInstanceOf(Group::class, $groups[0]);
        $this->assertEquals('Group 1', $groups[0]->name);
    }

    public function testPaginate(): void
    {
        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        
        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('accounts/1/groups', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $result = Group::paginate();

        $this->assertInstanceOf(\CanvasLMS\Pagination\PaginationResult::class, $result);
    }

    public function testCreate(): void
    {
        $createData = [
            'name' => 'New Group',
            'description' => 'New Description',
            'is_public' => true,
            'join_level' => 'parent_context_auto_join'
        ];

        $responseData = array_merge($createData, ['id' => 123]);

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->with($this->stringContains('groups'), $this->callback(function ($options) {
                return isset($options['multipart']) && is_array($options['multipart']);
            }))
            ->willReturn($this->mockResponse);

        $group = Group::create($createData);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals(123, $group->id);
        $this->assertEquals('New Group', $group->name);
    }

    public function testUpdate(): void
    {
        $updateData = [
            'name' => 'Updated Group',
            'description' => 'Updated Description'
        ];

        $responseData = array_merge($updateData, ['id' => 123]);

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('put')
            ->with('groups/123', $this->callback(function ($options) {
                return isset($options['multipart']) && is_array($options['multipart']);
            }))
            ->willReturn($this->mockResponse);

        $group = Group::update(123, $updateData);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('Updated Group', $group->name);
    }

    public function testDelete(): void
    {
        $group = new Group(['id' => 123]);
        
        $this->mockHttpClient->expects($this->once())
            ->method('delete')
            ->with('groups/123')
            ->willReturn($this->mockResponse);

        $result = $group->delete();

        $this->assertInstanceOf(Group::class, $result);
    }

    public function testSave(): void
    {
        $group = new Group(['name' => 'Test Group']);
        
        $responseData = [
            'id' => 123,
            'name' => 'Test Group'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->willReturn($this->mockResponse);

        $result = $group->save();

        $this->assertInstanceOf(Group::class, $result);
        $this->assertEquals(123, $group->id);
    }

    public function testMembers(): void
    {
        $group = new Group(['id' => 123]);
        
        $membersData = [
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2']
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->method('all')
            ->willReturn($membersData);
        
        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('groups/123/users', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $members = $group->members();

        $this->assertIsArray($members);
        $this->assertCount(2, $members);
    }

    public function testActivityStream(): void
    {
        $group = new Group(['id' => 123]);
        
        $activityData = [
            ['id' => 1, 'type' => 'Discussion'],
            ['id' => 2, 'type' => 'Announcement']
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($activityData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('groups/123/activity_stream')
            ->willReturn($this->mockResponse);

        $activities = $group->activityStream();

        $this->assertIsArray($activities);
        $this->assertCount(2, $activities);
    }

    public function testActivityStreamSummary(): void
    {
        $group = new Group(['id' => 123]);
        
        $summaryData = ['unread_count' => 5];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($summaryData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('groups/123/activity_stream/summary')
            ->willReturn($this->mockResponse);

        $summary = $group->activityStreamSummary();

        $this->assertIsArray($summary);
        $this->assertEquals(5, $summary['unread_count']);
    }

    public function testPermissions(): void
    {
        $group = new Group(['id' => 123]);
        
        $permissionsData = [
            'create_discussion_topic' => true,
            'create_announcement' => false
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($permissionsData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('groups/123/permissions')
            ->willReturn($this->mockResponse);

        $permissions = $group->permissions();

        $this->assertIsArray($permissions);
        $this->assertTrue($permissions['create_discussion_topic']);
        $this->assertFalse($permissions['create_announcement']);
    }

    public function testCreateMembership(): void
    {
        $group = new Group(['id' => 123]);
        
        $membershipData = [
            'id' => 456,
            'group_id' => 123,
            'user_id' => 789,
            'workflow_state' => 'accepted'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($membershipData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->with('groups/123/memberships', $this->anything())
            ->willReturn($this->mockResponse);

        $membership = $group->createMembership(['user_id' => 789]);

        $this->assertInstanceOf(GroupMembership::class, $membership);
        $this->assertEquals(456, $membership->id);
    }

    public function testMemberships(): void
    {
        $group = new Group(['id' => 123]);
        
        $membershipsData = [
            ['id' => 1, 'user_id' => 100],
            ['id' => 2, 'user_id' => 200]
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('all')
            ->willReturn($membershipsData);
        
        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('groups/123/memberships', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $memberships = $group->memberships();

        $this->assertIsArray($memberships);
        $this->assertCount(2, $memberships);
        $this->assertInstanceOf(GroupMembership::class, $memberships[0]);
    }

    public function testInvite(): void
    {
        $group = new Group(['id' => 123]);
        
        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->with('groups/123/invite', $this->callback(function ($options) {
                return isset($options['multipart']) && 
                       is_array($options['multipart']) &&
                       count($options['multipart']) === 1 &&
                       $options['multipart'][0]['name'] === 'invitees[]' &&
                       $options['multipart'][0]['contents'] === 'test@example.com';
            }))
            ->willReturn($this->mockResponse);

        $result = $group->invite(['test@example.com']);

        $this->assertInstanceOf(Group::class, $result);
    }

    public function testInviteWithInvalidEmail(): void
    {
        $group = new Group(['id' => 123]);
        
        $this->expectException(\CanvasLMS\Exceptions\CanvasApiException::class);
        $this->expectExceptionMessage('Invalid email address: not-an-email');

        $group->invite(['not-an-email']);
    }

    public function testRemoveUser(): void
    {
        $group = new Group(['id' => 123]);
        
        // Mock finding the membership
        $membershipData = [
            [
                'id' => 456,
                'user_id' => 789,
                'group_id' => 123,
                'workflow_state' => 'accepted'
            ]
        ];
        
        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('all')
            ->willReturn($membershipData);
        
        // Mock fetching memberships
        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('groups/123/memberships', ['query' => ['filter_states[]' => 'accepted']])
            ->willReturn($mockPaginatedResponse);
        
        // Mock deleting the membership
        $this->mockHttpClient->expects($this->once())
            ->method('delete')
            ->with('groups/123/memberships/456')
            ->willReturn($this->mockResponse);

        $result = $group->removeUser(789);

        $this->assertInstanceOf(Group::class, $result);
    }

    public function testFetchByContext(): void
    {
        $groupsData = [
            ['id' => 1, 'name' => 'Course Group 1'],
            ['id' => 2, 'name' => 'Course Group 2']
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->method('all')
            ->willReturn($groupsData);
        
        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('courses/456/groups', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $groups = Group::fetchByContext('courses', 456);

        $this->assertIsArray($groups);
        $this->assertCount(2, $groups);
        $this->assertInstanceOf(Group::class, $groups[0]);
    }

    public function testFetchUserGroups(): void
    {
        $groupsData = [
            ['id' => 1, 'name' => 'User Group 1'],
            ['id' => 2, 'name' => 'User Group 2']
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->method('all')
            ->willReturn($groupsData);
        
        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('users/123/groups', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $groups = Group::fetchUserGroups(123);

        $this->assertIsArray($groups);
        $this->assertCount(2, $groups);
    }


    public function testGettersAndSetters(): void
    {
        $group = new Group([]);
        
        $group->contextName = 'New Context';
        $this->assertEquals('New Context', $group->contextName);
        
        $group->users = [['id' => 1], ['id' => 2]];
        $this->assertCount(2, $group->users);
        
        $group->nonCollaborative = true;
        $this->assertTrue($group->nonCollaborative);
        
        $group->followedByUser = true;
        $this->assertTrue($group->followedByUser);
    }
}