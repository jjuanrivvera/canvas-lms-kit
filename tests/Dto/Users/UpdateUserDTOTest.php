<?php

declare(strict_types=1);

namespace Tests\Dto\Users;

use CanvasLMS\Dto\Users\UpdateUserDTO;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class UpdateUserDTOTest extends TestCase
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

        $dto = new UpdateUserDTO($data);

        $this->assertInstanceOf(DateTimeInterface::class, $dto->getBirthdate());
        $this->assertEquals($birthdate, $dto->getBirthdate());
    }

    /**
     * Test birthdate setter and getter
     */
    public function testBirthdateSetter(): void
    {
        $dto = new UpdateUserDTO([]);
        $birthdate = new DateTime('1985-06-20');

        $dto->setBirthdate($birthdate);

        $this->assertEquals($birthdate, $dto->getBirthdate());
    }

    /**
     * Test birthdate can be null
     */
    public function testBirthdateCanBeNull(): void
    {
        $dto = new UpdateUserDTO([]);

        $this->assertNull($dto->getBirthdate());

        $dto->setBirthdate(null);

        $this->assertNull($dto->getBirthdate());
    }

    /**
     * Test birthdate formatting in toApiArray
     */
    public function testBirthdateFormattingInApiArray(): void
    {
        $dto = new UpdateUserDTO([]);
        $birthdate = new DateTime('1995-12-25');

        $dto->setBirthdate($birthdate);

        $apiArray = $dto->toApiArray();

        $this->assertArrayHasKey('user[birthdate]', $apiArray);
        $this->assertEquals('1995-12-25', $apiArray['user[birthdate]']);
    }

    /**
     * Test that null birthdate is not included in API array
     */
    public function testNullBirthdateNotIncludedInApiArray(): void
    {
        $dto = new UpdateUserDTO([]);
        $dto->setName('Test User');
        // Birthdate is null

        $apiArray = $dto->toApiArray();

        $this->assertArrayNotHasKey('user[birthdate]', $apiArray);
    }

    /**
     * Test partial update with birthdate
     */
    public function testPartialUpdateWithBirthdate(): void
    {
        $dto = new UpdateUserDTO([]);
        $birthdate = new DateTime('2000-03-10');

        // Only update birthdate
        $dto->setBirthdate($birthdate);

        $apiArray = $dto->toApiArray();

        // Should only contain birthdate
        $this->assertCount(1, $apiArray);
        $this->assertArrayHasKey('user[birthdate]', $apiArray);
        $this->assertEquals('2000-03-10', $apiArray['user[birthdate]']);
    }

    /**
     * Test update with multiple fields including birthdate
     */
    public function testUpdateWithMultipleFieldsIncludingBirthdate(): void
    {
        $dto = new UpdateUserDTO([]);
        $birthdate = new DateTime('1988-07-15');

        $dto->setName('Updated Name');
        $dto->setEmail('updated@example.com');
        $dto->setBirthdate($birthdate);
        $dto->setLocale('es-ES');

        $apiArray = $dto->toApiArray();

        $this->assertArrayHasKey('user[name]', $apiArray);
        $this->assertArrayHasKey('user[email]', $apiArray);
        $this->assertArrayHasKey('user[birthdate]', $apiArray);
        $this->assertArrayHasKey('user[locale]', $apiArray);

        $this->assertEquals('Updated Name', $apiArray['user[name]']);
        $this->assertEquals('updated@example.com', $apiArray['user[email]']);
        $this->assertEquals('1988-07-15', $apiArray['user[birthdate]']);
        $this->assertEquals('es-ES', $apiArray['user[locale]']);
    }

    /**
     * Test birthdate with different DateTime implementations
     */
    public function testBirthdateWithDifferentDateTimeImplementations(): void
    {
        $dto = new UpdateUserDTO([]);

        // Test with DateTime
        $dateTime = new DateTime('1992-11-30');
        $dto->setBirthdate($dateTime);

        $apiArray = $dto->toApiArray();
        $this->assertEquals('1992-11-30', $apiArray['user[birthdate]']);

        // Test with DateTimeImmutable
        $dateTimeImmutable = new \DateTimeImmutable('1993-04-22');
        $dto->setBirthdate($dateTimeImmutable);

        $apiArray = $dto->toApiArray();
        $this->assertEquals('1993-04-22', $apiArray['user[birthdate]']);
    }
}
