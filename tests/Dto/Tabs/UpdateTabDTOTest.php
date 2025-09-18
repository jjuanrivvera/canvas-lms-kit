<?php

declare(strict_types=1);

namespace Tests\Dto\Tabs;

use CanvasLMS\Dto\Tabs\UpdateTabDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Dto\Tabs\UpdateTabDTO
 */
class UpdateTabDTOTest extends TestCase
{
    public function testConstruction(): void
    {
        $dto = new UpdateTabDTO(position: 3, hidden: true);

        $this->assertEquals(3, $dto->getPosition());
        $this->assertTrue($dto->getHidden());
    }

    public function testConstructionWithNullValues(): void
    {
        $dto = new UpdateTabDTO();

        $this->assertNull($dto->getPosition());
        $this->assertNull($dto->getHidden());
    }

    public function testGettersAndSetters(): void
    {
        $dto = new UpdateTabDTO();

        $dto->setPosition(5);
        $this->assertEquals(5, $dto->getPosition());

        $dto->setHidden(false);
        $this->assertFalse($dto->getHidden());

        $dto->setPosition(null);
        $this->assertNull($dto->getPosition());

        $dto->setHidden(null);
        $this->assertNull($dto->getHidden());
    }

    public function testToArray(): void
    {
        $dto = new UpdateTabDTO(position: 2, hidden: true);
        $result = $dto->toArray();

        $expected = [
            'position' => 2,
            'hidden' => true,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testToArrayWithNullValues(): void
    {
        $dto = new UpdateTabDTO();
        $result = $dto->toArray();

        $this->assertEquals([], $result);
    }

    public function testToArrayWithPartialData(): void
    {
        $dto = new UpdateTabDTO(position: 1);
        $result = $dto->toArray();

        $expected = ['position' => 1];

        $this->assertEquals($expected, $result);
    }

    public function testToApiArray(): void
    {
        $dto = new UpdateTabDTO(position: 3, hidden: false);
        $result = $dto->toApiArray();

        // The AbstractBaseDto creates multipart form data format
        $expected = [
            ['name' => 'tab[position]', 'contents' => 3],
            ['name' => 'tab[hidden]', 'contents' => false],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testToApiArrayWithEmptyData(): void
    {
        $dto = new UpdateTabDTO();
        $result = $dto->toApiArray();

        // When no data is provided, no multipart fields are created
        $expected = [];

        $this->assertEquals($expected, $result);
    }

    public function testConstructorThrowsExceptionForInvalidPosition(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Position must be a positive integer between 1 and 50');

        new UpdateTabDTO(position: 0);
    }

    public function testConstructorThrowsExceptionForNegativePosition(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Position must be a positive integer between 1 and 50');

        new UpdateTabDTO(position: -1);
    }

    public function testSetPositionThrowsExceptionForInvalidPosition(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Position must be a positive integer between 1 and 50');

        $dto = new UpdateTabDTO();
        $dto->setPosition(0);
    }

    public function testSetPositionThrowsExceptionForNegativePosition(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Position must be a positive integer between 1 and 50');

        $dto = new UpdateTabDTO();
        $dto->setPosition(-5);
    }

    public function testSetPositionAllowsValidPosition(): void
    {
        $dto = new UpdateTabDTO();
        $dto->setPosition(1);

        $this->assertEquals(1, $dto->getPosition());
    }

    public function testSetPositionAllowsNull(): void
    {
        $dto = new UpdateTabDTO(position: 5);
        $dto->setPosition(null);

        $this->assertNull($dto->getPosition());
    }

    public function testConstructorThrowsExceptionForPositionTooHigh(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Position must be a positive integer between 1 and 50');

        new UpdateTabDTO(position: 51);
    }

    public function testSetPositionThrowsExceptionForPositionTooHigh(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Position must be a positive integer between 1 and 50');

        $dto = new UpdateTabDTO();
        $dto->setPosition(100);
    }

    public function testValidPositionBoundaries(): void
    {
        // Test position 1 (minimum valid)
        $dto1 = new UpdateTabDTO(position: 1);
        $this->assertEquals(1, $dto1->getPosition());

        // Test position 50 (maximum valid)
        $dto50 = new UpdateTabDTO(position: 50);
        $this->assertEquals(50, $dto50->getPosition());

        // Test setting boundary values
        $dto = new UpdateTabDTO();
        $dto->setPosition(1);
        $this->assertEquals(1, $dto->getPosition());

        $dto->setPosition(50);
        $this->assertEquals(50, $dto->getPosition());
    }
}
