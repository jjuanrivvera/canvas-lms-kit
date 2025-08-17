<?php

namespace Tests\Api\Admins;

use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Admins\Admin;
use CanvasLMS\Api\Accounts\Account;
use CanvasLMS\Dto\Admins\CreateAdminDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Config;

class AdminTest extends TestCase
{
    /**
     * @var Admin
     */
    private Admin $admin;

    /**
     * @var mixed
     */
    private $httpClientMock;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClient::class);
        Admin::setApiClient($this->httpClientMock);
        Account::setApiClient($this->httpClientMock);

        // Set default account ID in config
        Config::setAccountId(1);

        $this->admin = new Admin([]);
    }

    /**
     * Admin data provider
     * @return array<string, array<int, mixed>>
     */
    public static function adminDataProvider(): array
    {
        return [
            'basic_admin' => [
                [
                    'userId' => 123,
                    'role' => 'AccountAdmin',
                ],
                [
                    'id' => 123,
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'role' => 'AccountAdmin',
                    'role_id' => 1,
                    'workflow_state' => 'active'
                ]
            ],
            'admin_with_role_id' => [
                [
                    'userId' => 456,
                    'roleId' => 2,
                    'sendConfirmation' => false
                ],
                [
                    'id' => 456,
                    'name' => 'Jane Smith',
                    'email' => 'jane.smith@example.com',
                    'role' => 'SubAccountAdmin',
                    'role_id' => 2,
                    'workflow_state' => 'active'
                ]
            ]
        ];
    }

    /**
     * Test the create admin method
     * @dataProvider adminDataProvider
     * @param array<string, mixed> $adminData
     * @param array<string, mixed> $expectedResult
     * @return void
     */
    public function testCreateAdmin(array $adminData, array $expectedResult): void
    {
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('accounts/1/admins'),
                $this->anything()
            )
            ->willReturn($response);

        $admin = Admin::create($adminData);

        $this->assertInstanceOf(Admin::class, $admin);
        $this->assertEquals($expectedResult['name'], $admin->getName());
        $this->assertEquals($expectedResult['role'], $admin->getRole());
        $this->assertEquals(1, $admin->getAccountId());
    }

    /**
     * Test the create admin method with DTO
     * @dataProvider adminDataProvider
     * @param array<string, mixed> $adminData
     * @param array<string, mixed> $expectedResult
     * @return void
     */
    public function testCreateAdminWithDto(array $adminData, array $expectedResult): void
    {
        $adminDto = new CreateAdminDTO($adminData);
        $expectedPayload = $adminDto->toApiArray();

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('accounts/1/admins'),
                $this->callback(function ($subject) use ($expectedPayload) {
                    return $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);

        $admin = Admin::create($adminDto);

        $this->assertInstanceOf(Admin::class, $admin);
        $this->assertEquals($expectedResult['name'], $admin->getName());
    }

    /**
     * Test create admin with explicit account ID
     * @return void
     */
    public function testCreateAdminWithAccountId(): void
    {
        $adminData = [
            'userId' => 789,
            'role' => 'AccountAdmin'
        ];

        $expectedResult = [
            'id' => 789,
            'name' => 'Test Admin',
            'role' => 'AccountAdmin'
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('accounts/2/admins'),
                $this->anything()
            )
            ->willReturn($response);

        $admin = Admin::create($adminData, 2);

        $this->assertInstanceOf(Admin::class, $admin);
        $this->assertEquals(2, $admin->getAccountId());
    }

    /**
     * Test create admin without account ID throws exception
     * @return void
     */
    public function testCreateAdminWithoutAccountIdThrowsException(): void
    {
        // Save current account ID and clear it
        $originalAccountId = Config::getAccountId();
        Config::setAccountId(0);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Account ID must be provided or set in Config');

        try {
            Admin::create(['userId' => 123, 'role' => 'AccountAdmin']);
        } finally {
            // Restore original account ID
            if ($originalAccountId) {
                Config::setAccountId($originalAccountId);
            }
        }
    }

    /**
     * Test the find admin method
     * @return void
     */
    public function testFindAdmin(): void
    {
        $expectedResult = [
            [
                'id' => 123,
                'name' => 'Found Admin',
                'role' => 'AccountAdmin'
            ]
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('accounts/1/admins'),
                $this->callback(function ($options) {
                    return isset($options['query']['user_id']) &&
                           $options['query']['user_id'] === [123];
                })
            )
            ->willReturn($response);

        $admin = Admin::find(123, ['account_id' => 1]);

        $this->assertInstanceOf(Admin::class, $admin);
        $this->assertEquals(123, $admin->getId());
        $this->assertEquals('Found Admin', $admin->getName());
        $this->assertEquals(1, $admin->getAccountId());
    }

    /**
     * Test find admin not found throws exception
     * @return void
     */
    public function testFindAdminNotFoundThrowsException(): void
    {
        $response = new Response(200, [], json_encode([]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Admin with user ID 999 not found in account 1');

        Admin::find(999);
    }

    /**
     * Test the fetchAll method
     * @return void
     */
    public function testFetchAll(): void
    {
        $expectedResult = [
            ['id' => 1, 'name' => 'Admin 1', 'role' => 'AccountAdmin'],
            ['id' => 2, 'name' => 'Admin 2', 'role' => 'SubAccountAdmin'],
            ['id' => 3, 'name' => 'Admin 3', 'role' => 'AccountAdmin']
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts/1/admins'))
            ->willReturn($response);

        $admins = Admin::fetchAll();

        $this->assertIsArray($admins);
        $this->assertCount(3, $admins);
        $this->assertInstanceOf(Admin::class, $admins[0]);
        $this->assertEquals('Admin 1', $admins[0]->getName());
        $this->assertEquals(1, $admins[0]->getAccountId());
    }

    /**
     * Test the delete admin method
     * @return void
     */
    public function testDeleteAdmin(): void
    {
        $this->admin->setId(123);
        $this->admin->setAccountId(1);

        $response = new Response(200, [], json_encode(['success' => true]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with($this->equalTo('accounts/1/admins/123'))
            ->willReturn($response);

        $result = $this->admin->delete();

        $this->assertInstanceOf(Admin::class, $result);
    }

    /**
     * Test delete without ID throws exception
     * @return void
     */
    public function testDeleteWithoutIdThrowsException(): void
    {
        $this->admin->setAccountId(1);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot delete admin without user ID');

        $this->admin->delete();
    }

    /**
     * Test delete without account ID throws exception
     * @return void
     */
    public function testDeleteWithoutAccountIdThrowsException(): void
    {
        $this->admin->setId(123);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot delete admin without account ID');

        $this->admin->delete();
    }

    /**
     * Test get self admin roles
     * @return void
     */
    public function testGetSelfAdminRoles(): void
    {
        $expectedRoles = [
            ['id' => 1, 'label' => 'Account Admin', 'base_role_type' => 'AccountMembership'],
            ['id' => 2, 'label' => 'Sub-Account Admin', 'base_role_type' => 'AccountMembership']
        ];

        $response = new Response(200, [], json_encode($expectedRoles));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts/1/admins/self/roles'))
            ->willReturn($response);

        $roles = Admin::getSelfAdminRoles();

        $this->assertIsArray($roles);
        $this->assertCount(2, $roles);
        $this->assertEquals('Account Admin', $roles[0]['label']);
    }

    /**
     * Test get account
     * @return void
     */
    public function testGetAccount(): void
    {
        $this->admin->setAccountId(1);

        $expectedAccount = [
            'id' => 1,
            'name' => 'Test Account',
            'parent_account_id' => null
        ];

        $response = new Response(200, [], json_encode($expectedAccount));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts/1'))
            ->willReturn($response);

        $account = $this->admin->getAccount();

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals(1, $account->getId());
        $this->assertEquals('Test Account', $account->getName());
    }

    /**
     * Test get account returns null when no account ID
     * @return void
     */
    public function testGetAccountReturnsNullWhenNoAccountId(): void
    {
        $account = $this->admin->getAccount();

        $this->assertNull($account);
    }

    /**
     * Test DTO creation for role name
     * @return void
     */
    public function testCreateAdminDtoWithRoleName(): void
    {
        $dto = new CreateAdminDTO([
            'userId' => 123,
            'role' => 'AccountAdmin',
            'sendConfirmation' => true
        ]);

        $array = $dto->toApiArray();

        $this->assertCount(3, $array);
        $this->assertEquals('user_id', $array[0]['name']);
        $this->assertEquals(123, $array[0]['contents']);
        $this->assertEquals('role', $array[1]['name']);
        $this->assertEquals('AccountAdmin', $array[1]['contents']);
        $this->assertEquals('send_confirmation', $array[2]['name']);
        $this->assertEquals('1', $array[2]['contents']);
    }

    /**
     * Test DTO creation for role ID
     * @return void
     */
    public function testCreateAdminDtoWithRoleId(): void
    {
        $dto = new CreateAdminDTO([
            'userId' => 456,
            'roleId' => 2,
            'sendConfirmation' => false
        ]);

        $array = $dto->toApiArray();

        $this->assertCount(3, $array);
        $this->assertEquals('user_id', $array[0]['name']);
        $this->assertEquals(456, $array[0]['contents']);
        $this->assertEquals('role_id', $array[1]['name']);
        $this->assertEquals(2, $array[1]['contents']);
        $this->assertEquals('send_confirmation', $array[2]['name']);
        $this->assertEquals('0', $array[2]['contents']);
    }

    /**
     * Test fetchAll with custom account ID
     * @return void
     */
    public function testFetchAllWithAccountId(): void
    {
        $expectedResult = [
            ['id' => 1, 'name' => 'Admin 1', 'role' => 'AccountAdmin']
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts/5/admins'))
            ->willReturn($response);

        $admins = Admin::fetchAll(['account_id' => 5]);

        $this->assertIsArray($admins);
        $this->assertCount(1, $admins);
        $this->assertEquals(5, $admins[0]->getAccountId());
    }

    /**
     * Test pagination methods throw proper exceptions without account ID
     * @return void
     */
    public function testPaginationMethodsRequireAccountId(): void
    {
        Config::setAccountId(0);

        $this->expectException(CanvasApiException::class);
        Admin::fetchAllPaginated();
    }
}