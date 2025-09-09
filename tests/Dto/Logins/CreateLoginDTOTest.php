<?php

namespace Tests\Dto\Logins;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Logins\CreateLoginDTO;

class CreateLoginDTOTest extends TestCase
{
    /**
     * Test basic DTO creation and transformation
     */
    public function testBasicDTOCreation(): void
    {
        $data = [
            'userId' => 123,
            'uniqueId' => 'user@example.com',
            'password' => 'secret123',
            'authenticationProviderId' => 'facebook',
            'declaredUserType' => 'student'
        ];

        $dto = new CreateLoginDTO($data);
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'user[id]', 'contents' => '123'],
            ['name' => 'login[unique_id]', 'contents' => 'user@example.com'],
            ['name' => 'login[password]', 'contents' => 'secret123'],
            ['name' => 'login[authentication_provider_id]', 'contents' => 'facebook'],
            ['name' => 'login[declared_user_type]', 'contents' => 'student']
        ];

        $this->assertEquals($expected, $apiArray);
    }

    /**
     * Test DTO with trusted account fields
     */
    public function testTrustedAccountFields(): void
    {
        $data = [
            'existingUserId' => 'existing123',
            'trustedAccount' => 'example.edu',
            'uniqueId' => 'user@example.com'
        ];

        $dto = new CreateLoginDTO($data);
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'user[existing_user_id]', 'contents' => 'existing123'],
            ['name' => 'user[trusted_account]', 'contents' => 'example.edu'],
            ['name' => 'login[unique_id]', 'contents' => 'user@example.com']
        ];

        $this->assertEquals($expected, $apiArray);
    }

    /**
     * Test validation - no user identification throws exception
     */
    public function testValidationNoUserIdThrowsException(): void
    {
        $dto = new CreateLoginDTO(['uniqueId' => 'user@example.com']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('One user identification method is required');

        $dto->toApiArray();
    }

    /**
     * Test validation - multiple user identification throws exception
     */
    public function testValidationMultipleUserIdThrowsException(): void
    {
        $data = [
            'userId' => 123,
            'existingUserId' => 'existing123',
            'uniqueId' => 'user@example.com'
        ];

        $dto = new CreateLoginDTO($data);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one user identification method should be provided');

        $dto->toApiArray();
    }

    /**
     * Test validation - existing user field requires trusted account
     */
    public function testValidationExistingUserRequiresTrustedAccount(): void
    {
        $data = [
            'existingUserId' => 'existing123',
            'uniqueId' => 'user@example.com'
        ];

        $dto = new CreateLoginDTO($data);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('trustedAccount is required when using existing user identification fields');

        $dto->toApiArray();
    }

    /**
     * Test validation - uniqueId is required
     */
    public function testValidationUniqueIdRequired(): void
    {
        $dto = new CreateLoginDTO(['userId' => 123]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('uniqueId is required for login creation');

        $dto->toApiArray();
    }

    /**
     * Test validation - invalid declared user type
     */
    public function testValidationInvalidDeclaredUserType(): void
    {
        $data = [
            'userId' => 123,
            'uniqueId' => 'user@example.com',
            'declaredUserType' => 'invalid_type'
        ];

        $dto = new CreateLoginDTO($data);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid declaredUserType');

        $dto->toApiArray();
    }

    /**
     * Test all valid declared user types
     */
    public function testValidDeclaredUserTypes(): void
    {
        $validTypes = ['administrative', 'observer', 'staff', 'student', 'student_other', 'teacher'];

        foreach ($validTypes as $type) {
            $data = [
                'userId' => 123,
                'uniqueId' => 'user@example.com',
                'declaredUserType' => $type
            ];

            $dto = new CreateLoginDTO($data);
            $apiArray = $dto->toApiArray();

            $this->assertContains(['name' => 'login[declared_user_type]', 'contents' => $type], $apiArray);
        }
    }

    /**
     * Test getters and setters
     */
    public function testGettersAndSetters(): void
    {
        $dto = new CreateLoginDTO([]);

        // Test userId
        $dto->setUserId(123);
        $this->assertEquals(123, $dto->getUserId());

        // Test uniqueId
        $dto->setUniqueId('user@example.com');
        $this->assertEquals('user@example.com', $dto->getUniqueId());

        // Test password
        $dto->setPassword('secret123');
        $this->assertEquals('secret123', $dto->getPassword());

        // Test existingUserId
        $dto->setExistingUserId('existing123');
        $this->assertEquals('existing123', $dto->getExistingUserId());

        // Test trustedAccount
        $dto->setTrustedAccount('example.edu');
        $this->assertEquals('example.edu', $dto->getTrustedAccount());

        // Test authenticationProviderId
        $dto->setAuthenticationProviderId('facebook');
        $this->assertEquals('facebook', $dto->getAuthenticationProviderId());

        // Test declaredUserType
        $dto->setDeclaredUserType('student');
        $this->assertEquals('student', $dto->getDeclaredUserType());
    }

    /**
     * Test empty values are filtered out
     */
    public function testEmptyValuesFiltered(): void
    {
        $data = [
            'userId' => 123,
            'uniqueId' => 'user@example.com',
            'password' => '',
            'sisUserId' => null,
            'authenticationProviderId' => 'facebook'
        ];

        $dto = new CreateLoginDTO($data);
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'user[id]', 'contents' => '123'],
            ['name' => 'login[unique_id]', 'contents' => 'user@example.com'],
            ['name' => 'login[authentication_provider_id]', 'contents' => 'facebook']
        ];

        $this->assertEquals($expected, $apiArray);
    }
}