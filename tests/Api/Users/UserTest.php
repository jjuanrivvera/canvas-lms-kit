<?php

namespace Tests\Api\Users;

use CanvasLMS\Api\Users\User;
use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Users\CreateUserDTO;
use CanvasLMS\Dto\Users\UpdateUserDTO;
use CanvasLMS\Exceptions\CanvasApiException;

class UserTest extends TestCase
{
    /**
     * @var User
     */
    private $user;

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
        User::setApiClient($this->httpClientMock);
        $this->user = new User([]);
    }

    /**
     * User data provider
     * @return array
     */
    public static function userDataProvider(): array
    {
        return [
            [
                [
                    'name' => 'Test User',
                ],
                [
                    'id' => 1,
                    'name' => 'Test User',
                ]
            ],
        ];
    }

    /**
     * Test the create user method
     * @dataProvider userDataProvider
     * @param array $userData
     * @param array $expectedResult
     * @return void
     */
    public function testCreateUser(array $userData, array $expectedResult): void
    {
        $response = new Response(200, [], json_encode($expectedResult));
        
        $this->httpClientMock
            ->method('post')
            ->willReturn($response);

        $user = User::create($userData);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->getName());
    }

    /**
     * Test the create user method with DTO
     * @dataProvider userDataProvider
     * @param array $userData
     * @param array $expectedResult
     * @return void
     */
    public function testCreateUserWithDto(array $userData, array $expectedResult): void
    {
        $userData = new CreateUserDTO($userData);
        $expectedPayload = $userData->toApiArray();
    
        $response = new Response(200, [], json_encode($expectedResult));
    
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/accounts/1/users'),
                $this->callback(function ($subject) use ($expectedPayload) {
                    return $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);
    
        $user = User::create($userData);
    
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->getName());
    }

    /**
     * Test the find user method
     * @return void
     */
    public function testFindUser(): void
    {
        $response = new Response(200, [], json_encode(['id' => 123, 'name' => 'Found User']));
        
        $this->httpClientMock
            ->method('get')
            ->willReturn($response);

        $user = User::find(123);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(123, $user->getId());
    }

    /**
     * Test the update user method
     * @return void
     */
    public function testUpdateUser(): void
    {
        $userData = [
            'name' => 'Updated User',
        ];

        $response = new Response(200, [], json_encode(['id' => 1, 'name' => 'Updated User']));
        
        $this->httpClientMock
            ->method('put')
            ->willReturn($response);

        $user = User::update(1, $userData);
        
        $this->assertEquals('Updated User', $user->getName());
    }

    /**
     * Test the update user method with DTO
     * @return void
     */
    public function testUpdateUserWithDto(): void
    {
        $userData = new UpdateUserDTO(['name' => 'Updated User']);

        $response = new Response(200, [], json_encode(['id' => 1, 'name' => 'Updated User']));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($response);

        $user = User::update(1, $userData);
        
        $this->assertEquals('Updated User', $user->getName());
    }

    /**
     * Test the save user method
     * @return void
     */
    public function testSaveUser(): void
    {
        $this->user->setId(1);
        $this->user->setName('Test User');

        $responseBody = json_encode(['id' => 1, 'name' => 'Test User']);
        $response = new Response(200, [], $responseBody);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('PUT'),
                $this->stringContains("/users/{$this->user->getId()}"),
                $this->callback(function ($options) {
                    return true;
                })
            )
            ->willReturn($response);

        $result = $this->user->save();

        $this->assertTrue($result, 'The save method should return true on successful save.');
        $this->assertEquals('Test User', $this->user->getName(), 'The user name should be updated after saving.');
    }

    /**
     * Test the save user method
     * @return void
     */
    public function testSaveUserShouldReturnFalseWhenApiThrowsException(): void
    {
        $this->user->setId(1);
        $this->user->setName('Test User');

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->will($this->throwException(new CanvasApiException()));

        $this->assertFalse($this->user->save());
    }

    protected function tearDown(): void
    {
        $this->user = null;
        $this->httpClientMock = null;
    }
}
