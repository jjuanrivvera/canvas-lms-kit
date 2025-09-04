<?php

declare(strict_types=1);

namespace Tests\Dto\MediaObjects;

use CanvasLMS\Dto\MediaObjects\UpdateMediaObjectDTO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CanvasLMS\Dto\MediaObjects\UpdateMediaObjectDTO
 */
class UpdateMediaObjectDTOTest extends TestCase
{
    /**
     * Test creating DTO with valid title
     */
    public function testCreateWithValidTitle(): void
    {
        $dto = new UpdateMediaObjectDTO('My New Title');
        
        $this->assertEquals('My New Title', $dto->userEnteredTitle);
        
        $array = $dto->toArray();
        $this->assertEquals(['user_entered_title' => 'My New Title'], $array);
    }

    /**
     * Test creating DTO with null title
     */
    public function testCreateWithNullTitle(): void
    {
        $dto = new UpdateMediaObjectDTO(null);
        
        $this->assertNull($dto->userEnteredTitle);
        
        $array = $dto->toArray();
        $this->assertEmpty($array);
    }

    /**
     * Test creating DTO with empty string throws exception
     */
    public function testCreateWithEmptyStringThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User entered title cannot be empty if provided');
        
        new UpdateMediaObjectDTO('');
    }

    /**
     * Test creating DTO with whitespace-only string throws exception
     */
    public function testCreateWithWhitespaceOnlyStringThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User entered title cannot be empty if provided');
        
        new UpdateMediaObjectDTO('   ');
    }

    /**
     * Test fromArray with snake_case key
     */
    public function testFromArrayWithSnakeCase(): void
    {
        $data = ['user_entered_title' => 'Title from Array'];
        
        $dto = UpdateMediaObjectDTO::fromArray($data);
        
        $this->assertEquals('Title from Array', $dto->userEnteredTitle);
    }

    /**
     * Test fromArray with camelCase key
     */
    public function testFromArrayWithCamelCase(): void
    {
        $data = ['userEnteredTitle' => 'Title from Camel'];
        
        $dto = UpdateMediaObjectDTO::fromArray($data);
        
        $this->assertEquals('Title from Camel', $dto->userEnteredTitle);
    }

    /**
     * Test fromArray with missing key
     */
    public function testFromArrayWithMissingKey(): void
    {
        $data = [];
        
        $dto = UpdateMediaObjectDTO::fromArray($data);
        
        $this->assertNull($dto->userEnteredTitle);
        $this->assertEmpty($dto->toArray());
    }

    /**
     * Test fromArray with both snake_case and camelCase (snake_case takes precedence)
     */
    public function testFromArrayWithBothFormats(): void
    {
        $data = [
            'user_entered_title' => 'Snake Case Title',
            'userEnteredTitle' => 'Camel Case Title'
        ];
        
        $dto = UpdateMediaObjectDTO::fromArray($data);
        
        // Snake case should take precedence
        $this->assertEquals('Snake Case Title', $dto->userEnteredTitle);
    }

    /**
     * Test toArray filters null values
     */
    public function testToArrayFiltersNullValues(): void
    {
        $dto = new UpdateMediaObjectDTO(null);
        
        $array = $dto->toArray();
        
        $this->assertIsArray($array);
        $this->assertEmpty($array);
        $this->assertArrayNotHasKey('user_entered_title', $array);
    }

    /**
     * Test validation with valid long title
     */
    public function testValidLongTitle(): void
    {
        $longTitle = str_repeat('a', 1000); // Very long title
        
        $dto = new UpdateMediaObjectDTO($longTitle);
        
        $this->assertEquals($longTitle, $dto->userEnteredTitle);
        $array = $dto->toArray();
        $this->assertEquals($longTitle, $array['user_entered_title']);
    }

    /**
     * Test validation with special characters
     */
    public function testTitleWithSpecialCharacters(): void
    {
        $title = 'Test Title with 特殊文字 and émojis 🎬 and symbols @#$%';
        
        $dto = new UpdateMediaObjectDTO($title);
        
        $this->assertEquals($title, $dto->userEnteredTitle);
        $array = $dto->toArray();
        $this->assertEquals($title, $array['user_entered_title']);
    }
}