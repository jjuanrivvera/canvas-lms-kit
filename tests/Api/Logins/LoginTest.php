<?php

namespace Tests\Api\Logins;

use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Api\Logins\Login;
use CanvasLMS\Dto\Logins\CreateLoginDTO;
use CanvasLMS\Dto\Logins\UpdateLoginDTO;
use CanvasLMS\Dto\Logins\PasswordResetDTO;
use CanvasLMS\Config;

class LoginTest extends TestCase
{
    private $httpClientMock;

    protected function setUp(): void
    {
        // Set up test configuration
        Config::setAccountId(1);
        
        $this->httpClientMock = $this->createMock(HttpClient::class);
        Login::setApiClient($this->httpClientMock);
    }

    /**
     * Mock login data for testing
     */
    public static function loginDataProvider(): array
    {
        return [
            [
                [
                    'account_id' => 1,
                    'id' => 2,
                    'sis_user_id' => null,
                    'unique_id' => 'belieber@example.com',
                    'user_id' => 2,
                    'authentication_provider_id' => 1,
                    'authentication_provider_type' => 'facebook',
                    'workflow_state' => 'active',
                    'declared_user_type' => null,
                    'created_at' => '2024-01-01T00:00:00Z'
                ]
            ]
        ];
    }

    /**
     * Test the get method (account context)
     * @dataProvider loginDataProvider
     */
    public function testGet(array $loginData): void
    {
        $response = new Response(200, [], json_encode([$loginData]));

        $this->httpClientMock
            ->method('get')
            ->with('accounts/1/logins')
            ->willReturn($response);

        $logins = Login::get();

        $this->assertIsArray($logins);
        $this->assertCount(1, $logins);
        $this->assertInstanceOf(Login::class, $logins[0]);
        $this->assertEquals('belieber@example.com', $logins[0]->uniqueId);
        $this->assertEquals('active', $logins[0]->workflowState);
    }

    /**
     * Test the find method
     * @dataProvider loginDataProvider
     */
    public function testFind(array $loginData): void
    {
        $response = new Response(200, [], json_encode([$loginData]));

        $this->httpClientMock
            ->method('get')
            ->with('accounts/1/logins')
            ->willReturn($response);

        $login = Login::find(2);

        $this->assertInstanceOf(Login::class, $login);
        $this->assertEquals(2, $login->id);
        $this->assertEquals('belieber@example.com', $login->uniqueId);
    }

    /**
     * Test find throws exception when login not found
     */
    public function testFindThrowsExceptionWhenNotFound(): void
    {
        $response = new Response(200, [], json_encode([]));

        $this->httpClientMock
            ->method('get')
            ->willReturn($response);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Login with ID 999 not found');

        Login::find(999);
    }

    /**
     * Test fetchByContext method for accounts
     * @dataProvider loginDataProvider
     */
    public function testFetchByContextAccounts(array $loginData): void
    {
        $response = new Response(200, [], json_encode([$loginData]));

        $this->httpClientMock
            ->method('get')
            ->with('accounts/1/logins')
            ->willReturn($response);

        $logins = Login::fetchByContext('accounts', 1);

        $this->assertIsArray($logins);
        $this->assertCount(1, $logins);
        $this->assertInstanceOf(Login::class, $logins[0]);
    }

    /**
     * Test fetchByContext method for users
     * @dataProvider loginDataProvider
     */
    public function testFetchByContextUsers(array $loginData): void
    {
        $response = new Response(200, [], json_encode([$loginData]));

        $this->httpClientMock
            ->method('get')
            ->with('users/2/logins')
            ->willReturn($response);

        $logins = Login::fetchByContext('users', 2);

        $this->assertIsArray($logins);
        $this->assertCount(1, $logins);
        $this->assertInstanceOf(Login::class, $logins[0]);
    }

    /**
     * Test create method with array data
     * @dataProvider loginDataProvider
     */
    public function testCreateWithArray(array $loginData): void
    {
        $createData = [
            'userId' => 2,
            'uniqueId' => 'belieber@example.com',
            'password' => 'secret123',
            'authenticationProviderId' => 'facebook'
        ];

        $response = new Response(200, [], json_encode($loginData));

        $this->httpClientMock
            ->method('post')
            ->with('accounts/1/logins')
            ->willReturn($response);

        $login = Login::create(1, $createData);

        $this->assertInstanceOf(Login::class, $login);
        $this->assertEquals('belieber@example.com', $login->uniqueId);
    }

    /**
     * Test create method with DTO
     * @dataProvider loginDataProvider
     */
    public function testCreateWithDTO(array $loginData): void
    {
        $createData = new CreateLoginDTO([
            'userId' => 2,
            'uniqueId' => 'belieber@example.com',
            'password' => 'secret123'
        ]);

        $response = new Response(200, [], json_encode($loginData));

        $this->httpClientMock
            ->method('post')
            ->with('accounts/1/logins')
            ->willReturn($response);

        $login = Login::create(1, $createData);

        $this->assertInstanceOf(Login::class, $login);
        $this->assertEquals('belieber@example.com', $login->uniqueId);
    }

    /**
     * Test update method
     * @dataProvider loginDataProvider
     */
    public function testUpdate(array $loginData): void
    {
        $login = new Login($loginData);

        $updateData = ['uniqueId' => 'updated@example.com'];
        $updatedData = array_merge($loginData, ['unique_id' => 'updated@example.com']);

        $response = new Response(200, [], json_encode($updatedData));

        $this->httpClientMock
            ->method('put')
            ->with('accounts/1/logins/2')
            ->willReturn($response);

        $updatedLogin = $login->update($updateData);

        $this->assertInstanceOf(Login::class, $updatedLogin);
        $this->assertEquals('updated@example.com', $updatedLogin->uniqueId);
    }

    /**
     * Test delete method
     * @dataProvider loginDataProvider
     */
    public function testDelete(array $loginData): void
    {
        $login = new Login($loginData);

        $deleteResponse = [
            'unique_id' => 'belieber@example.com',
            'sis_user_id' => null,
            'account_id' => 1,
            'id' => 2,
            'user_id' => 2
        ];

        $response = new Response(200, [], json_encode($deleteResponse));

        $this->httpClientMock
            ->method('delete')
            ->with('users/2/logins/2')
            ->willReturn($response);

        $result = $login->delete();

        $this->assertIsArray($result);
        $this->assertEquals('belieber@example.com', $result['unique_id']);
    }

    /**
     * Test password reset method with string email
     */
    public function testResetPasswordWithString(): void
    {
        $resetResponse = ['requested' => true];
        $response = new Response(200, [], json_encode($resetResponse));

        $this->httpClientMock
            ->method('post')
            ->with('users/reset_password')
            ->willReturn($response);

        $result = Login::resetPassword('test@example.com');

        $this->assertIsArray($result);
        $this->assertTrue($result['requested']);
    }

    /**
     * Test password reset method with array
     */
    public function testResetPasswordWithArray(): void
    {
        $resetResponse = ['requested' => true];
        $response = new Response(200, [], json_encode($resetResponse));

        $this->httpClientMock
            ->method('post')
            ->with('users/reset_password')
            ->willReturn($response);

        $result = Login::resetPassword(['email' => 'test@example.com']);

        $this->assertIsArray($result);
        $this->assertTrue($result['requested']);
    }

    /**
     * Test helper methods
     * @dataProvider loginDataProvider
     */
    public function testHelperMethods(array $loginData): void
    {
        $login = new Login($loginData);

        // Test isActive
        $this->assertTrue($login->isActive());
        $this->assertFalse($login->isSuspended());

        // Test getDeclaredUserTypeDisplay
        $this->assertNull($login->getDeclaredUserTypeDisplay());

        // Test with different workflow state
        $suspendedLogin = new Login(array_merge($loginData, ['workflow_state' => 'suspended']));
        $this->assertFalse($suspendedLogin->isActive());
        $this->assertTrue($suspendedLogin->isSuspended());

        // Test with declared user type
        $studentLogin = new Login(array_merge($loginData, ['declared_user_type' => 'student']));
        $this->assertEquals('Student', $studentLogin->getDeclaredUserTypeDisplay());
    }

    /**
     * Test workflow state constants
     */
    public function testWorkflowStateConstants(): void
    {
        $this->assertEquals('active', Login::STATE_ACTIVE);
        $this->assertEquals('suspended', Login::STATE_SUSPENDED);
    }

    /**
     * Test declared user type constants
     */
    public function testDeclaredUserTypeConstants(): void
    {
        $this->assertEquals('administrative', Login::TYPE_ADMINISTRATIVE);
        $this->assertEquals('observer', Login::TYPE_OBSERVER);
        $this->assertEquals('staff', Login::TYPE_STAFF);
        $this->assertEquals('student', Login::TYPE_STUDENT);
        $this->assertEquals('student_other', Login::TYPE_STUDENT_OTHER);
        $this->assertEquals('teacher', Login::TYPE_TEACHER);
    }
}