<?php

namespace Tests\Dto\Tabs;

use PHPUnit\Framework\TestCase;
use CanvasLMS\Dto\Tabs\UpdateTabDTO;

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
            'hidden' => true
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
            ['name' => 'tab[hidden]', 'contents' => false]
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
}