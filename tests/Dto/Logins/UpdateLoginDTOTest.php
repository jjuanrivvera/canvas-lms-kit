<?php

namespace Tests\Dto\Logins;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Logins\UpdateLoginDTO;

class UpdateLoginDTOTest extends TestCase
{
    /**
     * Test basic DTO creation and transformation
     */
    public function testBasicDTOCreation(): void
    {
        $data = [
            'uniqueId' => 'updated@example.com',
            'password' => 'newpassword123',
            'oldPassword' => 'oldpassword',
            'workflowState' => 'active',
            'declaredUserType' => 'teacher'
        ];

        $dto = new UpdateLoginDTO($data);
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'login[unique_id]', 'contents' => 'updated@example.com'],
            ['name' => 'login[password]', 'contents' => 'newpassword123'],
            ['name' => 'login[old_password]', 'contents' => 'oldpassword'],
            ['name' => 'login[workflow_state]', 'contents' => 'active'],
            ['name' => 'login[declared_user_type]', 'contents' => 'teacher']
        ];

        $this->assertEquals($expected, $apiArray);
    }

    /**
     * Test DTO with SIS fields
     */
    public function testSISFields(): void
    {
        $data = [
            'uniqueId' => 'user@example.com',
            'sisUserId' => 'SIS123',
            'integrationId' => 'INT456',
            'overrideSisStickiness' => true
        ];

        $dto = new UpdateLoginDTO($data);
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'login[unique_id]', 'contents' => 'user@example.com'],
            ['name' => 'login[sis_user_id]', 'contents' => 'SIS123'],
            ['name' => 'login[integration_id]', 'contents' => 'INT456'],
            ['name' => 'override_sis_stickiness', 'contents' => '1']
        ];

        $this->assertEquals($expected, $apiArray);
    }

    /**
     * Test validation - invalid workflow state throws exception
     */
    public function testValidationInvalidWorkflowStateThrowsException(): void
    {
        $dto = new UpdateLoginDTO([
            'uniqueId' => 'user@example.com',
            'workflowState' => 'invalid_state'
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid workflowState. Valid values are: active, suspended');

        $dto->toApiArray();
    }

    /**
     * Test validation - invalid declared user type throws exception
     */
    public function testValidationInvalidDeclaredUserTypeThrowsException(): void
    {
        $dto = new UpdateLoginDTO([
            'uniqueId' => 'user@example.com',
            'declaredUserType' => 'invalid_type'
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid declaredUserType');

        $dto->toApiArray();
    }

    /**
     * Test valid workflow states
     */
    public function testValidWorkflowStates(): void
    {
        $validStates = ['active', 'suspended'];

        foreach ($validStates as $state) {
            $data = [
                'uniqueId' => 'user@example.com',
                'workflowState' => $state
            ];

            $dto = new UpdateLoginDTO($data);
            $apiArray = $dto->toApiArray();

            $this->assertContains(['name' => 'login[workflow_state]', 'contents' => $state], $apiArray);
        }
    }

    /**
     * Test all valid declared user types
     */
    public function testValidDeclaredUserTypes(): void
    {
        $validTypes = ['administrative', 'observer', 'staff', 'student', 'student_other', 'teacher'];

        foreach ($validTypes as $type) {
            $data = [
                'uniqueId' => 'user@example.com',
                'declaredUserType' => $type
            ];

            $dto = new UpdateLoginDTO($data);
            $apiArray = $dto->toApiArray();

            $this->assertContains(['name' => 'login[declared_user_type]', 'contents' => $type], $apiArray);
        }
    }

    /**
     * Test authentication provider ID handling
     */
    public function testAuthenticationProviderHandling(): void
    {
        // Test with provider ID
        $dto = new UpdateLoginDTO([
            'uniqueId' => 'user@example.com',
            'authenticationProviderId' => 'facebook'
        ]);
        
        $apiArray = $dto->toApiArray();
        $this->assertContains(['name' => 'login[authentication_provider_id]', 'contents' => 'facebook'], $apiArray);

        // Test with null to unassociate
        $dto = new UpdateLoginDTO([
            'uniqueId' => 'user@example.com',
            'authenticationProviderId' => null
        ]);
        
        $apiArray = $dto->toApiArray();
        // Null values should be filtered out
        $this->assertCount(1, $apiArray);
        $this->assertEquals(['name' => 'login[unique_id]', 'contents' => 'user@example.com'], $apiArray[0]);
    }

    /**
     * Test getters and setters
     */
    public function testGettersAndSetters(): void
    {
        $dto = new UpdateLoginDTO([]);

        // Test uniqueId
        $dto->setUniqueId('user@example.com');
        $this->assertEquals('user@example.com', $dto->getUniqueId());

        // Test password
        $dto->setPassword('newpassword');
        $this->assertEquals('newpassword', $dto->getPassword());

        // Test oldPassword
        $dto->setOldPassword('oldpassword');
        $this->assertEquals('oldpassword', $dto->getOldPassword());

        // Test sisUserId
        $dto->setSisUserId('SIS123');
        $this->assertEquals('SIS123', $dto->getSisUserId());

        // Test integrationId
        $dto->setIntegrationId('INT456');
        $this->assertEquals('INT456', $dto->getIntegrationId());

        // Test authenticationProviderId
        $dto->setAuthenticationProviderId('facebook');
        $this->assertEquals('facebook', $dto->getAuthenticationProviderId());

        // Test workflowState
        $dto->setWorkflowState('suspended');
        $this->assertEquals('suspended', $dto->getWorkflowState());

        // Test declaredUserType
        $dto->setDeclaredUserType('teacher');
        $this->assertEquals('teacher', $dto->getDeclaredUserType());

        // Test overrideSisStickiness
        $dto->setOverrideSisStickiness(false);
        $this->assertFalse($dto->getOverrideSisStickiness());
    }

    /**
     * Test empty and null values are filtered out
     */
    public function testEmptyValuesFiltered(): void
    {
        $data = [
            'uniqueId' => 'user@example.com',
            'password' => '',
            'oldPassword' => null,
            'sisUserId' => 'SIS123',
            'authenticationProviderId' => ''
        ];

        $dto = new UpdateLoginDTO($data);
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'login[unique_id]', 'contents' => 'user@example.com'],
            ['name' => 'login[sis_user_id]', 'contents' => 'SIS123']
        ];

        $this->assertEquals($expected, $apiArray);
    }

    /**
     * Test minimal update (only required field)
     */
    public function testMinimalUpdate(): void
    {
        $dto = new UpdateLoginDTO(['uniqueId' => 'user@example.com']);
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'login[unique_id]', 'contents' => 'user@example.com']
        ];

        $this->assertEquals($expected, $apiArray);
    }

    /**
     * Test boolean conversion to string
     */
    public function testBooleanConversion(): void
    {
        $dto = new UpdateLoginDTO([
            'uniqueId' => 'user@example.com',
            'overrideSisStickiness' => true
        ]);
        
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'login[unique_id]', 'contents' => 'user@example.com'],
            ['name' => 'override_sis_stickiness', 'contents' => '1']
        ];

        $this->assertEquals($expected, $apiArray);

        // Test false boolean
        $dto = new UpdateLoginDTO([
            'uniqueId' => 'user@example.com',
            'overrideSisStickiness' => false
        ]);
        
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'login[unique_id]', 'contents' => 'user@example.com'],
            ['name' => 'override_sis_stickiness', 'contents' => '']
        ];

        $this->assertEquals($expected, $apiArray);
    }
}