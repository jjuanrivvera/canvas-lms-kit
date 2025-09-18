<?php

declare(strict_types=1);

namespace Tests\Dto\Users;

use CanvasLMS\Dto\Users\CreateUserDTO;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class CreateUserDTOTest extends TestCase
{
    /**
     * Test constructor with birthdate
     */
    public function testConstructorWithBirthdate(): void
    {
        $birthdate = new DateTime('1990-01-15');
        $data = [
            'name' => 'Test User',
            'birthdate' => $birthdate,
        ];

        $dto = new CreateUserDTO($data);

        $this->assertInstanceOf(DateTimeInterface::class, $dto->getBirthdate());
        $this->assertEquals($birthdate, $dto->getBirthdate());
    }

    /**
     * Test birthdate setter and getter
     */
    public function testBirthdateSetter(): void
    {
        $dto = new CreateUserDTO([]);
        $birthdate = new DateTime('1985-06-20');

        $dto->setBirthdate($birthdate);

        $this->assertEquals($birthdate, $dto->getBirthdate());
    }

    /**
     * Test birthdate can be null
     */
    public function testBirthdateCanBeNull(): void
    {
        $dto = new CreateUserDTO([]);

        $this->assertNull($dto->getBirthdate());

        $dto->setBirthdate(null);

        $this->assertNull($dto->getBirthdate());
    }

    /**
     * Test birthdate formatting in toApiArray
     */
    public function testBirthdateFormattingInApiArray(): void
    {
        $dto = new CreateUserDTO([]);
        $birthdate = new DateTime('1995-12-25');

        $dto->setName('Test User');
        $dto->setBirthdate($birthdate);
        $dto->setUniqueId('test@example.com');
        $dto->setCommunicationType('email');
        $dto->setCommunicationAddress('test@example.com');

        $apiArray = $dto->toApiArray();

        $birthdateFound = false;
        foreach ($apiArray as $field) {
            if ($field['name'] === 'user[birthdate]') {
                $this->assertEquals('1995-12-25', $field['contents']);
                $birthdateFound = true;
                break;
            }
        }

        $this->assertTrue($birthdateFound, 'Birthdate field not found in API array');
    }

    /**
     * Test declared_user_type property
     */
    public function testDeclaredUserType(): void
    {
        $dto = new CreateUserDTO([]);

        $this->assertNull($dto->getDeclaredUserType());

        $dto->setDeclaredUserType('student');

        $this->assertEquals('student', $dto->getDeclaredUserType());
    }

    /**
     * Test declared_user_type in toApiArray
     */
    public function testDeclaredUserTypeInApiArray(): void
    {
        $dto = new CreateUserDTO([]);
        $dto->setName('Test User');
        $dto->setUniqueId('test@example.com');
        $dto->setDeclaredUserType('teacher');
        $dto->setCommunicationType('email');
        $dto->setCommunicationAddress('test@example.com');

        $apiArray = $dto->toApiArray();

        $declaredUserTypeFound = false;
        foreach ($apiArray as $field) {
            if ($field['name'] === 'pseudonym[declared_user_type]') {
                $this->assertEquals('teacher', $field['contents']);
                $declaredUserTypeFound = true;
                break;
            }
        }

        $this->assertTrue($declaredUserTypeFound, 'Declared user type field not found in API array');
    }

    /**
     * Test force_validations is at root level
     */
    public function testForceValidationsAtRootLevel(): void
    {
        $dto = new CreateUserDTO([]);
        $dto->setName('Test User');
        $dto->setUniqueId('test@example.com');
        $dto->setForceValidations(true);
        $dto->setCommunicationType('email');
        $dto->setCommunicationAddress('test@example.com');

        $apiArray = $dto->toApiArray();

        $forceValidationsFound = false;
        foreach ($apiArray as $field) {
            if ($field['name'] === 'force_validations') {
                $this->assertEquals(true, $field['contents']);
                $forceValidationsFound = true;
                break;
            }
        }

        $this->assertTrue($forceValidationsFound, 'Force validations field not found at root level');

        // Ensure it's NOT under pseudonym scope
        foreach ($apiArray as $field) {
            $this->assertNotEquals(
                'pseudonym[force_validations]',
                $field['name'],
                'Force validations should not be under pseudonym scope'
            );
        }
    }

    /**
     * Test that null values are not included in API array
     */
    public function testNullValuesNotIncludedInApiArray(): void
    {
        $dto = new CreateUserDTO([]);
        $dto->setName('Test User');
        $dto->setUniqueId('test@example.com');
        $dto->setCommunicationType('email');
        $dto->setCommunicationAddress('test@example.com');
        // Birthdate and declared_user_type are null

        $apiArray = $dto->toApiArray();

        foreach ($apiArray as $field) {
            $this->assertNotEquals('user[birthdate]', $field['name']);
            $this->assertNotEquals('pseudonym[declared_user_type]', $field['name']);
        }
    }
}
