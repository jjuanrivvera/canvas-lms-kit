<?php

namespace Tests\Dto\Logins;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Logins\PasswordResetDTO;

class PasswordResetDTOTest extends TestCase
{
    /**
     * Test basic DTO creation and transformation
     */
    public function testBasicDTOCreation(): void
    {
        $dto = new PasswordResetDTO(['email' => 'user@example.com']);
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'pseudonym_session[unique_id_forgot]', 'contents' => 'user@example.com']
        ];

        $this->assertEquals($expected, $apiArray);
    }

    /**
     * Test validation - empty email throws exception
     */
    public function testValidationEmptyEmailThrowsException(): void
    {
        $dto = new PasswordResetDTO(['email' => '']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is required for password reset');

        $dto->toApiArray();
    }

    /**
     * Test validation - null email throws exception
     */
    public function testValidationNullEmailThrowsException(): void
    {
        $dto = new PasswordResetDTO([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is required for password reset');

        $dto->toApiArray();
    }

    /**
     * Test validation - invalid email format throws exception
     */
    public function testValidationInvalidEmailFormatThrowsException(): void
    {
        $dto = new PasswordResetDTO(['email' => 'invalid-email']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $dto->toApiArray();
    }

    /**
     * Test valid email formats
     */
    public function testValidEmailFormats(): void
    {
        $validEmails = [
            'user@example.com',
            'test.user@domain.org',
            'user+tag@example.co.uk',
            'user123@test-domain.com'
        ];

        foreach ($validEmails as $email) {
            $dto = new PasswordResetDTO(['email' => $email]);
            $apiArray = $dto->toApiArray();

            $expected = [
                ['name' => 'pseudonym_session[unique_id_forgot]', 'contents' => $email]
            ];

            $this->assertEquals($expected, $apiArray);
        }
    }

    /**
     * Test invalid email formats
     */
    public function testInvalidEmailFormats(): void
    {
        $invalidEmails = [
            'plaintext',
            '@domain.com',
            'user@',
            'user..user@domain.com',
            'user@domain',
            'user@.com',
            'user name@domain.com'
        ];

        foreach ($invalidEmails as $email) {
            $dto = new PasswordResetDTO(['email' => $email]);

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Invalid email format');

            $dto->toApiArray();
        }
    }

    /**
     * Test getters and setters
     */
    public function testGettersAndSetters(): void
    {
        $dto = new PasswordResetDTO([]);

        // Test email
        $dto->setEmail('test@example.com');
        $this->assertEquals('test@example.com', $dto->getEmail());

        // Test null email
        $dto->setEmail(null);
        $this->assertNull($dto->getEmail());
    }

    /**
     * Test DTO creation with constructor parameter
     */
    public function testConstructorWithData(): void
    {
        $dto = new PasswordResetDTO(['email' => 'constructor@example.com']);
        
        $this->assertEquals('constructor@example.com', $dto->getEmail());
    }

    /**
     * Test DTO creation without constructor parameter
     */
    public function testConstructorWithoutData(): void
    {
        $dto = new PasswordResetDTO([]);
        
        $this->assertNull($dto->getEmail());
    }

    /**
     * Test case sensitivity in email validation
     */
    public function testEmailCaseSensitivity(): void
    {
        $dto = new PasswordResetDTO(['email' => 'User@EXAMPLE.COM']);
        $apiArray = $dto->toApiArray();

        $expected = [
            ['name' => 'pseudonym_session[unique_id_forgot]', 'contents' => 'User@EXAMPLE.COM']
        ];

        $this->assertEquals($expected, $apiArray);
    }

    /**
     * Test international domain names
     */
    public function testInternationalDomains(): void
    {
        // Note: PHP's FILTER_VALIDATE_EMAIL may not fully support all international domains
        // but we test common cases that should work
        $internationalEmails = [
            'user@domain.co.uk',
            'test@example.org',
            'user@sub.domain.com'
        ];

        foreach ($internationalEmails as $email) {
            $dto = new PasswordResetDTO(['email' => $email]);
            $apiArray = $dto->toApiArray();

            $expected = [
                ['name' => 'pseudonym_session[unique_id_forgot]', 'contents' => $email]
            ];

            $this->assertEquals($expected, $apiArray);
        }
    }
}