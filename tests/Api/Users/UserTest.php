<?php

namespace Tests\Api\Users;

use CanvasLMS\Api\Users\User;
use CanvasLMS\Api\Enrollments\Enrollment;
use CanvasLMS\Api\Groups\Group;
use GuzzleHttp\Psr7\Response;
use CanvasLMS\Http\HttpClient;
use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Users\CreateUserDTO;
use CanvasLMS\Dto\Users\UpdateUserDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Config;

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
        // Set up test configuration
        Config::setAccountId(1);
        
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
     * Test that User::create uses the configured account ID
     * @return void
     */
    public function testCreateUserUsesConfiguredAccountId(): void
    {
        // Set a custom account ID
        Config::setAccountId(789);
        
        $userData = [
            'name' => 'Test User',
            'unique_id' => 'testuser789'
        ];

        $dto = new CreateUserDTO($userData);
        $expectedPayload = $dto->toApiArray();
        $expectedResult = array_merge($userData, ['id' => 1]);

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/accounts/789/users'),
                $this->callback(function ($subject) use ($expectedPayload) {
                    return $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->getName());
        
        // Reset to default for other tests
        Config::setAccountId(1);
    }

    /**
     * Test that User::create uses default account ID when none is configured
     * @return void
     */
    public function testCreateUserUsesDefaultAccountId(): void
    {
        // Reset config to ensure we're using defaults
        Config::resetContext(Config::getContext());
        
        $userData = [
            'name' => 'Test User',
            'unique_id' => 'testuser_default'
        ];

        $dto = new CreateUserDTO($userData);
        $expectedPayload = $dto->toApiArray();
        $expectedResult = array_merge($userData, ['id' => 1]);

        $response = new Response(200, [], json_encode($expectedResult));

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/accounts/1/users'), // Default account ID is 1
                $this->callback(function ($subject) use ($expectedPayload) {
                    return $subject['multipart'] === $expectedPayload;
                })
            )
            ->willReturn($response);

        // Suppress the warning about using default account ID
        @$user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->getName());
        
        // Restore configured account ID for other tests
        Config::setAccountId(1);
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

        $this->assertInstanceOf(User::class, $result, 'The save method should return User instance on successful save.');
        $this->assertEquals('Test User', $this->user->getName(), 'The user name should be updated after saving.');
    }

    /**
     * Test the save user method
     * @return void
     */
    public function testSaveUserShouldThrowExceptionWhenApiFails(): void
    {
        $this->user->setId(1);
        $this->user->setName('Test User');

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->will($this->throwException(new CanvasApiException('API Error')));

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('API Error');
        $this->user->save();
    }

    // Relationship Method Tests

    /**
     * Test method alias for user enrollments with parameters
     */
    public function testUserEnrollmentsMethodAlias(): void
    {
        $userData = ['id' => 123, 'name' => 'Test User'];
        $user = new User($userData);

        $enrollmentData = [
            [
                'id' => 1,
                'user_id' => 123,
                'course_id' => 456,
                'type' => 'StudentEnrollment',
                'enrollment_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($enrollmentData));

        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('users/123/enrollments', ['query' => ['state[]' => ['active']]])
            ->willReturn($response);

        $enrollments = $user->enrollments(['state[]' => ['active']]); // Method access with params

        $this->assertCount(1, $enrollments);
        $this->assertInstanceOf(Enrollment::class, $enrollments[0]);
    }

    /**
     * Test new properties effective_locale and can_update_name
     */
    public function testNewProperties(): void
    {
        $userData = [
            'id' => 123,
            'name' => 'Test User',
            'effective_locale' => 'en-US',
            'can_update_name' => true
        ];

        $user = new User($userData);

        $this->assertEquals('en-US', $user->getEffectiveLocale());
        $this->assertTrue($user->getCanUpdateName());

        // Test setters
        $user->setEffectiveLocale('es-ES');
        $user->setCanUpdateName(false);

        $this->assertEquals('es-ES', $user->getEffectiveLocale());
        $this->assertFalse($user->getCanUpdateName());
    }

    /**
     * Test that new properties can be null
     */
    public function testNewPropertiesCanBeNull(): void
    {
        $userData = [
            'id' => 123,
            'name' => 'Test User'
        ];

        $user = new User($userData);

        $this->assertNull($user->getEffectiveLocale());
        $this->assertNull($user->getCanUpdateName());
    }

    // Tests for User::self() pattern

    /**
     * Test User::self() returns a User instance without ID
     */
    public function testSelfReturnsUserInstanceWithoutId(): void
    {
        $currentUser = User::self();
        
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertFalse(isset($currentUser->id), 'self() should return a User instance without ID set');
    }

    /**
     * Test getProfile uses 'self' when ID is not set
     */
    public function testGetProfileUsesSelfWhenNoId(): void
    {
        $profileData = [
            'id' => 123,
            'name' => 'Current User',
            'short_name' => 'Current',
            'login_id' => 'current@example.com'
        ];
        
        $response = new Response(200, [], json_encode($profileData));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/users/self/profile')
            ->willReturn($response);
        
        $currentUser = User::self();
        $profile = $currentUser->getProfile();
        
        $this->assertEquals('Current User', $profile->name);
    }

    /**
     * Test getMissingSubmissions throws exception when ID is not set
     */
    public function testGetMissingSubmissionsThrowsExceptionWhenNoId(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to fetch missing submissions');
        
        $currentUser = User::self();
        $currentUser->getMissingSubmissions();
    }

    /**
     * Test fetchSelf() retrieves complete user data for authenticated user
     */
    public function testFetchSelfRetrievesFullUserData(): void
    {
        $userData = [
            'id' => 54699,
            'name' => 'Juan Rivera',
            'sortable_name' => 'Rivera, Juan',
            'short_name' => 'Juan',
            'sis_user_id' => 'juan123',
            'integration_id' => null,
            'login_id' => 'jrivera@example.com',
            'email' => 'jrivera@example.com',
            'avatar_url' => 'https://canvas.example.com/images/messages/avatar-50.png',
            'locale' => 'en',
            'effective_locale' => 'en',
            'last_login' => '2025-01-20T10:30:00Z',
            'time_zone' => 'America/New_York',
            'bio' => null
        ];
        
        $response = new Response(200, [], json_encode($userData));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/users/self')
            ->willReturn($response);
        
        $currentUser = User::fetchSelf();
        
        // Assert user instance is properly populated
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertEquals(54699, $currentUser->id);
        $this->assertEquals('Juan Rivera', $currentUser->name);
        $this->assertEquals('jrivera@example.com', $currentUser->email);
        $this->assertEquals('https://canvas.example.com/images/messages/avatar-50.png', $currentUser->avatarUrl);
        $this->assertEquals('America/New_York', $currentUser->timeZone);
    }

    /**
     * Test fetchSelf() works with OAuth authentication
     */
    public function testFetchSelfWorksWithOAuth(): void
    {
        // Set up OAuth authentication
        Config::setOAuthToken('test-oauth-token');
        Config::useOAuth();
        
        $userData = [
            'id' => 12345,
            'name' => 'OAuth User',
            'email' => 'oauth@example.com'
        ];
        
        $response = new Response(200, [], json_encode($userData));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/users/self')
            ->willReturn($response);
        
        $currentUser = User::fetchSelf();
        
        $this->assertEquals(12345, $currentUser->id);
        $this->assertEquals('OAuth User', $currentUser->name);
        $this->assertEquals('oauth@example.com', $currentUser->email);
        
        // Reset to API key auth
        Config::useApiKey();
    }

    /**
     * Test that fetchSelf() populated user can use all User methods
     */
    public function testFetchSelfUserCanUseAllMethods(): void
    {
        $userData = [
            'id' => 789,
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];
        
        $profileData = [
            'id' => 789,
            'name' => 'Test User',
            'bio' => 'Test bio'
        ];
        
        $userResponse = new Response(200, [], json_encode($userData));
        $profileResponse = new Response(200, [], json_encode($profileData));
        
        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($userResponse, $profileResponse);
        
        // First fetch the user
        $currentUser = User::fetchSelf();
        
        // Now use a method that requires ID
        $profile = $currentUser->getProfile();
        
        $this->assertEquals('Test bio', $profile->bio);
    }

    /**
     * Test setCustomData throws exception when ID is not set
     */
    public function testSetCustomDataThrowsExceptionWhenNoId(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to set custom data');
        
        $currentUser = User::self();
        $currentUser->setCustomData('namespace', ['data' => 'value']);
    }

    /**
     * Test getCustomData throws exception when ID is not set
     */
    public function testGetCustomDataThrowsExceptionWhenNoId(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to get custom data');
        
        $currentUser = User::self();
        $currentUser->getCustomData('namespace');
    }

    /**
     * Test courses() throws exception when ID is not set
     */
    public function testCoursesThrowsExceptionWhenNoId(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to get courses');
        
        $currentUser = User::self();
        $currentUser->courses();
    }

    /**
     * Test that methods still work with explicit user ID
     */
    public function testMethodsStillWorkWithExplicitUserId(): void
    {
        $userId = 456;
        $userData = ['id' => $userId, 'name' => 'Specific User'];
        $profileData = ['id' => $userId, 'name' => 'Specific User', 'short_name' => 'Specific'];
        
        // First mock for User::find()
        $userResponse = new Response(200, [], json_encode($userData));
        // Second mock for getProfile()
        $profileResponse = new Response(200, [], json_encode($profileData));
        
        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function ($path) use ($userResponse, $profileResponse) {
                if ($path === '/users/456') {
                    return $userResponse;
                } elseif ($path === '/users/456/profile') {
                    return $profileResponse;
                }
            });
        
        $user = User::find($userId);
        $this->assertEquals($userId, $user->getId());
        
        $profile = $user->getProfile();
        $this->assertEquals('Specific User', $profile->name);
    }

    /**
     * Test getCalendarEvents throws exception when ID is not set
     */
    public function testGetCalendarEventsThrowsExceptionWhenNoId(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to get calendar events');
        
        $currentUser = User::self();
        $currentUser->getCalendarEvents();
    }

    /**
     * Test split throws exception when ID is not set
     */
    public function testSplitThrowsExceptionWhenNoId(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to split user');
        
        $currentUser = User::self();
        $currentUser->split();
    }

    /**
     * Test groups() uses 'self' endpoint for current user
     */
    public function testGroupsMethodUsesSelfForCurrentUser(): void
    {
        $groupsData = [
            ['id' => 1, 'name' => 'Group 1'],
            ['id' => 2, 'name' => 'Group 2']
        ];
        
        $response = new Response(200, [], json_encode($groupsData));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('users/self/groups', $this->anything())
            ->willReturn($response);
        
        $currentUser = User::self();
        $groups = $currentUser->groups();
        
        $this->assertCount(2, $groups);
        $this->assertInstanceOf(Group::class, $groups[0]);
        $this->assertEquals('Group 1', $groups[0]->name);
    }

    /**
     * Test files() throws exception when ID is not set
     */
    public function testFilesThrowsExceptionWhenNoId(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('User ID is required to get files');
        
        $currentUser = User::self();
        $currentUser->files();
    }

    /**
     * Test getTodo() method delegates to getTodoItems()
     */
    public function testGetTodoMethodAlias(): void
    {
        $todoData = [
            [
                'type' => 'grading',
                'assignment' => ['id' => 1, 'name' => 'Assignment 1'],
                'needs_grading_count' => 5
            ],
            [
                'type' => 'submitting',
                'assignment' => ['id' => 2, 'name' => 'Assignment 2']
            ]
        ];
        
        $response = new Response(200, [], json_encode($todoData));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/users/self/todo', $this->anything())
            ->willReturn($response);
        
        $currentUser = User::self();
        $todos = $currentUser->getTodo();
        
        $this->assertCount(2, $todos);
        $this->assertEquals('grading', $todos[0]->type);
        $this->assertEquals('submitting', $todos[1]->type);
    }

    /**
     * Test getTodo() with specific user ID
     */
    public function testGetTodoWithUserId(): void
    {
        $todoData = [
            [
                'type' => 'grading',
                'assignment' => ['id' => 1, 'name' => 'Assignment 1']
            ]
        ];
        
        $response = new Response(200, [], json_encode($todoData));
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('/users/123/todo', $this->anything())
            ->willReturn($response);
        
        $user = new User(['id' => 123]);
        $todos = $user->getTodo();
        
        $this->assertCount(1, $todos);
        $this->assertEquals('grading', $todos[0]->type);
    }

    protected function tearDown(): void
    {
        $this->user = null;
        $this->httpClientMock = null;
    }
}
