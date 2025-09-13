<?php

namespace Tests\Api\Accounts;

use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Accounts\Account;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Accounts\CreateAccountDTO;
use CanvasLMS\Dto\Accounts\UpdateAccountDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Config;

class AccountTest extends TestCase
{
    /**
     * @var Account
     */
    private Account $account;

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
        Account::setApiClient($this->httpClientMock);
        Course::setApiClient($this->httpClientMock);
        
        // Set default account ID in config
        Config::setAccountId(1);
        
        $this->account = new Account([]);
    }

    /**
     * Account data provider
     * @return array
     */
    public static function accountDataProvider(): array
    {
        return [
            'basic_account' => [
                [
                    'name' => 'Mathematics Department',
                    'sisAccountId' => 'MATH_DEPT',
                ],
                [
                    'id' => 2,
                    'name' => 'Mathematics Department',
                    'uuid' => 'WvAHhY5FINzq5IyRIJybGeiXyFkG3SqHUPb7jZY5',
                    'parent_account_id' => 1,
                    'root_account_id' => 1,
                    'sis_account_id' => 'MATH_DEPT',
                    'workflow_state' => 'active'
                ]
            ],
            'account_with_storage' => [
                [
                    'name' => 'Science Department',
                    'sisAccountId' => 'SCI_DEPT',
                    'defaultStorageQuotaMb' => 1000,
                    'defaultUserStorageQuotaMb' => 100,
                    'defaultGroupStorageQuotaMb' => 200
                ],
                [
                    'id' => 3,
                    'name' => 'Science Department',
                    'uuid' => 'XvBHhY5FINzq5IyRIJybGeiXyFkG3SqHUPb7jZY6',
                    'parent_account_id' => 1,
                    'root_account_id' => 1,
                    'sis_account_id' => 'SCI_DEPT',
                    'default_storage_quota_mb' => 1000,
                    'default_user_storage_quota_mb' => 100,
                    'default_group_storage_quota_mb' => 200,
                    'workflow_state' => 'active'
                ]
            ]
        ];
    }

    /**
     * Test the create account method
     * @dataProvider accountDataProvider
     * @param array $accountData
     * @param array $expectedResult
     * @return void
     */
    public function testCreateAccount(array $accountData, array $expectedResult): void
    {
        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('accounts/1/sub_accounts'),
                $this->anything()
            )
            ->willReturn($response);

        $account = Account::create($accountData);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals($expectedResult['name'], $account->getName());
        $this->assertEquals($expectedResult['sis_account_id'], $account->getSisAccountId());
    }

    /**
     * Test the create account method with DTO
     * @dataProvider accountDataProvider
     * @param array $accountData
     * @param array $expectedResult
     * @return void
     */
    public function testCreateAccountWithDto(array $accountData, array $expectedResult): void
    {
        $accountDto = new CreateAccountDTO($accountData);
        $expectedPayload = $accountDto->toApiArray();

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('accounts/1/sub_accounts'),
                $this->callback(function ($subject) use ($expectedPayload) {
                    return $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);

        $account = Account::create($accountDto);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals($expectedResult['name'], $account->getName());
    }

    /**
     * Test create account with explicit parent ID
     * @return void
     */
    public function testCreateAccountWithParentId(): void
    {
        $accountData = [
            'name' => 'Sub Department',
            'sisAccountId' => 'SUB_DEPT'
        ];

        $expectedResult = [
            'id' => 4,
            'name' => 'Sub Department',
            'parent_account_id' => 2,
            'root_account_id' => 1,
            'sis_account_id' => 'SUB_DEPT'
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('accounts/2/sub_accounts'),
                $this->anything()
            )
            ->willReturn($response);

        $account = Account::create($accountData, 2);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals(2, $account->parentAccountId);
    }

    /**
     * Test create account without parent ID and no config
     * @return void
     */
    public function testCreateAccountWithoutParentIdThrowsException(): void
    {
        // Save current account ID and clear it
        $originalAccountId = Config::getAccountId();
        Config::setAccountId(0); // Set to 0 to simulate no account ID
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Parent account ID must be provided or set in Config');

        try {
            Account::create(['name' => 'Test']);
        } finally {
            // Restore original account ID
            if ($originalAccountId) {
                Config::setAccountId($originalAccountId);
            }
        }
    }

    /**
     * Test the find account method
     * @return void
     */
    public function testFindAccount(): void
    {
        $expectedResult = [
            'id' => 123,
            'name' => 'Found Account',
            'uuid' => 'ABC123',
            'workflow_state' => 'active'
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts/123'))
            ->willReturn($response);

        $account = Account::find(123);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals(123, $account->getId());
        $this->assertEquals('Found Account', $account->getName());
    }

    /**
     * Test find account with SIS ID
     * @return void
     */
    public function testFindAccountWithSisId(): void
    {
        $expectedResult = [
            'id' => 456,
            'name' => 'SIS Account',
            'sis_account_id' => 'SIS_ACCT_123'
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts/sis_account_id:SIS_ACCT_123'))
            ->willReturn($response);

        $account = Account::find('sis_account_id:SIS_ACCT_123');

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals('SIS_ACCT_123', $account->getSisAccountId());
    }

    /**
     * Test the fetchAll method
     * @return void
     */
    public function testGet(): void
    {
        $expectedResult = [
            ['id' => 1, 'name' => 'Account 1'],
            ['id' => 2, 'name' => 'Account 2'],
            ['id' => 3, 'name' => 'Account 3']
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts'))
            ->willReturn($response);

        $accounts = Account::get();

        $this->assertIsArray($accounts);
        $this->assertCount(3, $accounts);
        $this->assertInstanceOf(Account::class, $accounts[0]);
        $this->assertEquals('Account 1', $accounts[0]->getName());
    }

    /**
     * Test the update account method
     * @return void
     */
    public function testUpdateAccount(): void
    {
        $updateData = [
            'name' => 'Updated Department',
            'defaultStorageQuotaMb' => 2000
        ];

        $expectedResult = [
            'id' => 1,
            'name' => 'Updated Department',
            'default_storage_quota_mb' => 2000
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with($this->equalTo('accounts/1'))
            ->willReturn($response);

        $account = Account::update(1, $updateData);

        $this->assertEquals('Updated Department', $account->getName());
        $this->assertEquals(2000, $account->defaultStorageQuotaMb);
    }

    /**
     * Test the update account method with DTO
     * @return void
     */
    public function testUpdateAccountWithDto(): void
    {
        $updateDto = new UpdateAccountDTO(['name' => 'Updated Department']);

        $expectedResult = [
            'id' => 1,
            'name' => 'Updated Department'
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($response);

        $account = Account::update(1, $updateDto);

        $this->assertEquals('Updated Department', $account->getName());
    }

    /**
     * Test the save account method
     * @return void
     */
    public function testSaveAccount(): void
    {
        $this->account->setId(1);
        $this->account->setName('Test Account');

        $responseBody = json_encode(['id' => 1, 'name' => 'Test Account']);
        $response = new Response(200, [], $responseBody);

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                $this->equalTo('accounts/1'),
                $this->anything()
            )
            ->willReturn($response);

        $result = $this->account->save();

        $this->assertInstanceOf(Account::class, $result, 'The save method should return Account instance on successful save.');
        $this->assertEquals('Test Account', $this->account->getName());
    }

    /**
     * Test save without ID throws exception
     * @return void
     */
    public function testSaveWithoutIdThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot save account without ID');

        $this->account->save();
    }

    /**
     * Test the delete account method
     * @return void
     */
    public function testDeleteAccount(): void
    {
        $this->account->setId(2);
        $this->account->parentAccountId = 1;

        $response = new Response(200, [], json_encode(['id' => 2, 'workflow_state' => 'deleted']));

        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with($this->equalTo('accounts/1/sub_accounts/2'))
            ->willReturn($response);

        $result = $this->account->delete();

        $this->assertInstanceOf(Account::class, $result);
    }

    /**
     * Test delete root account throws exception
     * @return void
     */
    public function testDeleteRootAccountThrowsException(): void
    {
        $this->account->setId(1);
        $this->account->parentAccountId = null;

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot delete root account');

        $this->account->delete();
    }

    /**
     * Test get sub accounts
     * @return void
     */
    public function testGetSubAccounts(): void
    {
        $this->account->setId(1);

        $expectedResult = [
            ['id' => 2, 'name' => 'Sub Account 1', 'parent_account_id' => 1],
            ['id' => 3, 'name' => 'Sub Account 2', 'parent_account_id' => 1]
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts/1/sub_accounts'))
            ->willReturn($response);

        $subAccounts = $this->account->subAccounts();

        $this->assertIsArray($subAccounts);
        $this->assertCount(2, $subAccounts);
        $this->assertInstanceOf(Account::class, $subAccounts[0]);
        $this->assertEquals(1, $subAccounts[0]->parentAccountId);
    }

    /**
     * Test get parent account
     * @return void
     */
    public function testGetParentAccount(): void
    {
        $this->account->parentAccountId = 1;

        $expectedResult = [
            'id' => 1,
            'name' => 'Root Account',
            'parent_account_id' => null
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts/1'))
            ->willReturn($response);

        $parentAccount = $this->account->parentAccount();

        $this->assertInstanceOf(Account::class, $parentAccount);
        $this->assertEquals(1, $parentAccount->getId());
        $this->assertNull($parentAccount->parentAccountId);
    }

    /**
     * Test get parent account returns null for root account
     * @return void
     */
    public function testGetParentAccountReturnsNullForRootAccount(): void
    {
        $this->account->parentAccountId = null;

        $parentAccount = $this->account->parentAccount();

        $this->assertNull($parentAccount);
    }

    /**
     * Test is root account
     * @return void
     */
    public function testIsRootAccount(): void
    {
        $this->account->parentAccountId = null;
        $this->account->rootAccountId = null;

        $this->assertTrue($this->account->isRootAccount());

        $this->account->parentAccountId = 1;
        $this->assertFalse($this->account->isRootAccount());
    }

    /**
     * Test get account settings
     * @return void
     */
    public function testGetSettings(): void
    {
        $this->account->setId(1);

        $expectedSettings = [
            'microsoft_sync_enabled' => true,
            'microsoft_sync_login_attribute_suffix' => false,
            'restrict_student_past_view' => [
                'value' => true,
                'locked' => false
            ]
        ];

        $response = new Response(200, [], json_encode($expectedSettings));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts/1/settings'))
            ->willReturn($response);

        $settings = $this->account->getSettings();

        $this->assertIsArray($settings);
        $this->assertTrue($settings['microsoft_sync_enabled']);
        $this->assertIsArray($settings['restrict_student_past_view']);
    }

    /**
     * Test update account settings
     * @return void
     */
    public function testUpdateSettings(): void
    {
        $this->account->setId(1);

        $newSettings = [
            'microsoft_sync_enabled' => false,
            'restrict_student_past_view' => [
                'value' => false,
                'locked' => true
            ]
        ];

        $updateResponse = new Response(200, [], json_encode(['id' => 1]));
        $settingsResponse = new Response(200, [], json_encode($newSettings));

        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($updateResponse);

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->willReturn($settingsResponse);

        $result = $this->account->updateSettings($newSettings);

        $this->assertInstanceOf(Account::class, $result);
        $this->assertEquals($newSettings, $this->account->settings);
    }

    /**
     * Test get permissions
     * @return void
     */
    public function testGetPermissions(): void
    {
        $this->account->setId(1);

        $expectedPermissions = [
            'manage_account_memberships' => false,
            'become_user' => true
        ];

        $response = new Response(200, [], json_encode($expectedPermissions));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('accounts/1/permissions'),
                $this->callback(function ($options) {
                    return isset($options['query']['permissions']) &&
                           in_array('manage_account_memberships', $options['query']['permissions']) &&
                           in_array('become_user', $options['query']['permissions']);
                })
            )
            ->willReturn($response);

        $permissions = $this->account->getPermissions(['manage_account_memberships', 'become_user']);

        $this->assertIsArray($permissions);
        $this->assertFalse($permissions['manage_account_memberships']);
        $this->assertTrue($permissions['become_user']);
    }

    /**
     * Test get courses for account
     * @return void
     */
    public function testGetCourses(): void
    {
        $this->account->setId(1);

        $expectedCourses = [
            ['id' => 1, 'name' => 'Course 1', 'account_id' => 1],
            ['id' => 2, 'name' => 'Course 2', 'account_id' => 1]
        ];

        $response = new Response(200, [], json_encode($expectedCourses));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts/1/courses'))
            ->willReturn($response);

        $courses = $this->account->courses();

        $this->assertIsArray($courses);
        $this->assertCount(2, $courses);
        $this->assertInstanceOf(Course::class, $courses[0]);
        $this->assertEquals('Course 1', $courses[0]->getName());
    }

    /**
     * Test get manageable accounts
     * @return void
     */
    public function testGetManageableAccounts(): void
    {
        $expectedResult = [
            ['id' => 1, 'name' => 'Account 1'],
            ['id' => 2, 'name' => 'Account 2']
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('manageable_accounts'))
            ->willReturn($response);

        $accounts = Account::getManageableAccounts();

        $this->assertIsArray($accounts);
        $this->assertCount(2, $accounts);
        $this->assertInstanceOf(Account::class, $accounts[0]);
    }

    /**
     * Test get course creation accounts
     * @return void
     */
    public function testGetCourseCreationAccounts(): void
    {
        $expectedResult = [
            ['id' => 1, 'name' => 'Account 1'],
            ['id' => 3, 'name' => 'Account 3']
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('course_creation_accounts'))
            ->willReturn($response);

        $accounts = Account::getCourseCreationAccounts();

        $this->assertIsArray($accounts);
        $this->assertCount(2, $accounts);
        $this->assertInstanceOf(Account::class, $accounts[0]);
    }

    /**
     * Test get root account
     * @return void
     */
    public function testGetRootAccount(): void
    {
        Config::setAccountId(1);

        $expectedResult = [
            'id' => 1,
            'name' => 'Root Account',
            'parent_account_id' => null,
            'root_account_id' => null
        ];

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('accounts/1'))
            ->willReturn($response);

        $rootAccount = Account::getRootAccount();

        $this->assertInstanceOf(Account::class, $rootAccount);
        $this->assertEquals(1, $rootAccount->getId());
        $this->assertTrue($rootAccount->isRootAccount());
    }

    /**
     * Test fetchSubAccounts static method
     */
    public function testFetchSubAccounts(): void
    {
        $subAccountsData = [
            ['id' => 2, 'name' => 'Sub Account 1', 'parent_account_id' => 1],
            ['id' => 3, 'name' => 'Sub Account 2', 'parent_account_id' => 1],
            ['id' => 4, 'name' => 'Sub Account 3', 'parent_account_id' => 1]
        ];

        $response = new Response(200, [], json_encode($subAccountsData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('accounts/1/sub_accounts', ['query' => []])
            ->willReturn($response);

        $subAccounts = Account::fetchSubAccounts(1);

        $this->assertIsArray($subAccounts);
        $this->assertCount(3, $subAccounts);
        $this->assertInstanceOf(Account::class, $subAccounts[0]);
        $this->assertEquals('Sub Account 1', $subAccounts[0]->getName());
        $this->assertEquals(2, $subAccounts[0]->getId());
    }

    /**
     * Test fetchSubAccounts with recursive parameter
     */
    public function testFetchSubAccountsWithRecursive(): void
    {
        $subAccountsData = [
            ['id' => 2, 'name' => 'Sub Account 1', 'parent_account_id' => 1],
            ['id' => 3, 'name' => 'Sub Account 1.1', 'parent_account_id' => 2],
            ['id' => 4, 'name' => 'Sub Account 1.2', 'parent_account_id' => 2]
        ];

        $response = new Response(200, [], json_encode($subAccountsData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('accounts/1/sub_accounts', ['query' => ['recursive' => true]])
            ->willReturn($response);

        $subAccounts = Account::fetchSubAccounts(1, ['recursive' => true]);

        $this->assertIsArray($subAccounts);
        $this->assertCount(3, $subAccounts);
        $this->assertInstanceOf(Account::class, $subAccounts[0]);
    }

    /**
     * Test fetchSubAccounts returns empty array when no sub-accounts
     */
    public function testFetchSubAccountsEmptyResult(): void
    {
        $response = new Response(200, [], json_encode([]));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('accounts/99/sub_accounts', ['query' => []])
            ->willReturn($response);

        $subAccounts = Account::fetchSubAccounts(99);

        $this->assertIsArray($subAccounts);
        $this->assertCount(0, $subAccounts);
    }
}