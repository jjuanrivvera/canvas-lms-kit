<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\Groups;

use CanvasLMS\Api\Groups\GroupMembership;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Dto\Groups\CreateGroupMembershipDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GroupMembershipTest extends TestCase
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
        
        GroupMembership::setApiClient($this->mockHttpClient);
        User::setApiClient($this->mockHttpClient);
    }

    public function testConstructor(): void
    {
        $data = [
            'id' => 123,
            'group_id' => 456,
            'user_id' => 789,
            'workflow_state' => 'accepted',
            'moderator' => true,
            'just_created' => false,
            'sis_import_id' => 999
        ];

        $membership = new GroupMembership($data);

        $this->assertEquals(123, $membership->id);
        $this->assertEquals(456, $membership->groupId);
        $this->assertEquals(789, $membership->userId);
        $this->assertEquals('accepted', $membership->workflowState);
        $this->assertTrue($membership->moderator);
        $this->assertFalse($membership->justCreated);
        $this->assertEquals(999, $membership->sisImportId);
    }

    public function testFind(): void
    {
        $membershipData = [
            'id' => 123,
            'group_id' => 456,
            'user_id' => 789,
            'workflow_state' => 'accepted'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($membershipData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('groups/456/memberships/123')
            ->willReturn($this->mockResponse);

        $membership = GroupMembership::find(123, ['group_id' => 456]);

        $this->assertInstanceOf(GroupMembership::class, $membership);
        $this->assertEquals(123, $membership->id);
        $this->assertEquals(456, $membership->groupId);
    }

    public function testGetThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Group ID is required. Use fetchAllForGroup($groupId, $params) instead.');
        
        GroupMembership::get();
    }

    public function testGetForGroup(): void
    {
        $membershipsData = [
            ['id' => 1, 'user_id' => 100, 'workflow_state' => 'accepted'],
            ['id' => 2, 'user_id' => 200, 'workflow_state' => 'invited']
        ];

        $mockPaginatedResponse = $this->createMock(PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('all')
            ->willReturn($membershipsData);
        
        $this->mockHttpClient->expects($this->once())
            ->method('getPaginated')
            ->with('groups/456/memberships', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $memberships = GroupMembership::fetchAllForGroup(456);

        $this->assertIsArray($memberships);
        $this->assertCount(2, $memberships);
        $this->assertInstanceOf(GroupMembership::class, $memberships[0]);
    }

    public function testCreate(): void
    {
        $createData = ['user_id' => 789];
        
        $responseData = [
            'id' => 123,
            'group_id' => 456,
            'user_id' => 789,
            'workflow_state' => 'accepted'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->with('groups/456/memberships', $this->callback(function ($options) {
                return isset($options['multipart']) && 
                       is_array($options['multipart']) &&
                       $options['multipart'][0]['name'] === 'user_id' &&
                       $options['multipart'][0]['contents'] === '789';
            }))
            ->willReturn($this->mockResponse);

        $membership = GroupMembership::create(456, $createData);

        $this->assertInstanceOf(GroupMembership::class, $membership);
        $this->assertEquals(123, $membership->id);
        $this->assertEquals(789, $membership->userId);
    }

    public function testCreateWithDTO(): void
    {
        $dto = new CreateGroupMembershipDTO(['user_id' => 789]);
        
        $responseData = [
            'id' => 123,
            'group_id' => 456,
            'user_id' => 789,
            'workflow_state' => 'accepted'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('post')
            ->willReturn($this->mockResponse);

        $membership = GroupMembership::create(456, $dto);

        $this->assertInstanceOf(GroupMembership::class, $membership);
        $this->assertEquals(789, $membership->userId);
    }

    public function testUpdate(): void
    {
        $updateData = ['workflow_state' => 'accepted', 'moderator' => true];
        
        $responseData = [
            'id' => 123,
            'group_id' => 456,
            'user_id' => 789,
            'workflow_state' => 'accepted',
            'moderator' => true
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('put')
            ->with('groups/456/memberships/123', $this->callback(function ($options) {
                return isset($options['multipart']) && is_array($options['multipart']);
            }))
            ->willReturn($this->mockResponse);

        $membership = GroupMembership::update(456, 123, $updateData);

        $this->assertInstanceOf(GroupMembership::class, $membership);
        $this->assertTrue($membership->moderator);
        $this->assertEquals('accepted', $membership->workflowState);
    }

    public function testDelete(): void
    {
        $this->mockHttpClient->expects($this->once())
            ->method('delete')
            ->with('groups/456/memberships/123')
            ->willReturn($this->mockResponse);

        GroupMembership::deleteMembership(456, 123);

        // No assertion needed as deleteMembership() returns void
    }


    public function testAccept(): void
    {
        $membership = new GroupMembership([
            'id' => 123,
            'group_id' => 456,
            'workflow_state' => 'invited'
        ]);
        
        $responseData = [
            'id' => 123,
            'group_id' => 456,
            'workflow_state' => 'accepted'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('put')
            ->with('groups/456/memberships/123', $this->callback(function ($options) {
                $multipart = $options['multipart'];
                return $multipart[0]['name'] === 'workflow_state' &&
                       $multipart[0]['contents'] === 'accepted';
            }))
            ->willReturn($this->mockResponse);

        $result = $membership->accept();

        $this->assertInstanceOf(GroupMembership::class, $result);
        $this->assertSame($membership, $result);
        $this->assertEquals('accepted', $membership->workflowState);
    }

    public function testReject(): void
    {
        $membership = new GroupMembership([
            'id' => 123,
            'group_id' => 456,
            'workflow_state' => 'invited'
        ]);
        
        $this->mockHttpClient->expects($this->once())
            ->method('delete')
            ->with('groups/456/memberships/123')
            ->willReturn($this->mockResponse);

        $result = $membership->reject();

        $this->assertInstanceOf(GroupMembership::class, $result);
        $this->assertSame($membership, $result);
    }

    public function testMakeModerator(): void
    {
        $membership = new GroupMembership([
            'id' => 123,
            'group_id' => 456,
            'moderator' => false
        ]);
        
        $responseData = [
            'id' => 123,
            'group_id' => 456,
            'moderator' => true
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('put')
            ->with('groups/456/memberships/123', $this->callback(function ($options) {
                $multipart = $options['multipart'];
                return $multipart[0]['name'] === 'moderator' &&
                       $multipart[0]['contents'] === 'true';
            }))
            ->willReturn($this->mockResponse);

        $result = $membership->makeModerator();

        $this->assertInstanceOf(GroupMembership::class, $result);
        $this->assertSame($membership, $result);
        $this->assertTrue($membership->moderator);
    }

    public function testRemoveModerator(): void
    {
        $membership = new GroupMembership([
            'id' => 123,
            'group_id' => 456,
            'moderator' => true
        ]);
        
        $responseData = [
            'id' => 123,
            'group_id' => 456,
            'moderator' => false
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($responseData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('put')
            ->with('groups/456/memberships/123', $this->callback(function ($options) {
                $multipart = $options['multipart'];
                return $multipart[0]['name'] === 'moderator' &&
                       $multipart[0]['contents'] === 'false';
            }))
            ->willReturn($this->mockResponse);

        $result = $membership->removeModerator();

        $this->assertInstanceOf(GroupMembership::class, $result);
        $this->assertSame($membership, $result);
        $this->assertFalse($membership->moderator);
    }

    public function testLeave(): void
    {
        $this->mockHttpClient->expects($this->once())
            ->method('delete')
            ->with('groups/456/memberships/self')
            ->willReturn($this->mockResponse);

        GroupMembership::leave(456);

        // No assertion needed as leave() returns void
    }

    public function testGetUser(): void
    {
        $userData = [
            'id' => 789,
            'name' => 'Test User'
        ];
        
        $membership = new GroupMembership([
            'id' => 123,
            'user_id' => 789,
            'user' => $userData
        ]);
        
        $user = $membership->getUser();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(789, $user->id);
        $this->assertEquals('Test User', $user->name);
    }

    public function testGetUserFetchesFromAPI(): void
    {
        $membership = new GroupMembership([
            'id' => 123,
            'user_id' => 789
        ]);
        
        $userData = [
            'id' => 789,
            'name' => 'API User'
        ];

        $this->mockStream->method('getContents')
            ->willReturn(json_encode($userData));
        
        $this->mockResponse->method('getBody')
            ->willReturn($this->mockStream);
        
        $this->mockHttpClient->expects($this->once())
            ->method('get')
            ->with('/users/789')
            ->willReturn($this->mockResponse);
        
        $user = $membership->getUser();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(789, $user->id);
        $this->assertEquals('API User', $user->name);
    }

    public function testGetUserReturnsNullWithoutUserId(): void
    {
        $membership = new GroupMembership(['id' => 123]);
        
        $user = $membership->getUser();

        $this->assertNull($user);
    }

}