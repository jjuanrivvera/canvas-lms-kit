<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\SharedBrandConfigs;

use CanvasLMS\Dto\SharedBrandConfigs\CreateSharedBrandConfigDto;
use PHPUnit\Framework\TestCase;

class CreateSharedBrandConfigDtoTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        // Arrange & Act
        $dto = new CreateSharedBrandConfigDto([
            'name' => 'Spring 2024 Theme',
            'brand_config_md5' => 'abc123def456',
        ]);

        // Assert
        $this->assertEquals('Spring 2024 Theme', $dto->name);
        $this->assertEquals('abc123def456', $dto->brandConfigMd5);
    }

    public function testConstructorHandlesSnakeCaseMd5(): void
    {
        // Arrange & Act
        $dto = new CreateSharedBrandConfigDto([
            'name' => 'Test Theme',
            'brand_config_md5' => 'hash_with_snake_case',
        ]);

        // Assert
        $this->assertEquals('Test Theme', $dto->name);
        $this->assertEquals('hash_with_snake_case', $dto->brandConfigMd5);
    }

    public function testConstructorHandlesCamelCaseMd5(): void
    {
        // Arrange & Act
        $dto = new CreateSharedBrandConfigDto([
            'name' => 'Test Theme',
            'brandConfigMd5' => 'hash_with_camel_case',
        ]);

        // Assert
        $this->assertEquals('Test Theme', $dto->name);
        $this->assertEquals('hash_with_camel_case', $dto->brandConfigMd5);
    }

    public function testValidateThrowsExceptionWhenNameMissing(): void
    {
        // Arrange
        $dto = new CreateSharedBrandConfigDto([
            'brand_config_md5' => 'abc123',
        ]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required for creating a shared brand config');

        // Act
        $dto->validate();
    }

    public function testValidateThrowsExceptionWhenMd5Missing(): void
    {
        // Arrange
        $dto = new CreateSharedBrandConfigDto([
            'name' => 'Test Theme',
        ]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Brand config MD5 is required for creating a shared brand config');

        // Act
        $dto->validate();
    }

    public function testValidateThrowsExceptionWhenBothFieldsMissing(): void
    {
        // Arrange
        $dto = new CreateSharedBrandConfigDto([]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        // Should throw for name first
        $this->expectExceptionMessage('Name is required for creating a shared brand config');

        // Act
        $dto->validate();
    }

    public function testValidatePassesWithAllRequiredFields(): void
    {
        // Arrange
        $dto = new CreateSharedBrandConfigDto([
            'name' => 'Valid Theme',
            'brand_config_md5' => 'valid_hash',
        ]);

        // Act & Assert - No exception should be thrown
        $dto->validate();
        $this->assertTrue(true); // Test passes if no exception
    }

    public function testToApiArrayReturnsCorrectFormat(): void
    {
        // Arrange
        $dto = new CreateSharedBrandConfigDto([
            'name' => 'Test Theme',
            'brand_config_md5' => 'test_hash_123',
        ]);

        // Act
        $result = $dto->toApiArray();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        
        // Check name field
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertEquals('shared_brand_config[name]', $result[0]['name']);
        $this->assertArrayHasKey('contents', $result[0]);
        $this->assertEquals('Test Theme', $result[0]['contents']);
        
        // Check brand_config_md5 field (should be snake_case)
        $this->assertArrayHasKey('name', $result[1]);
        $this->assertEquals('shared_brand_config[brand_config_md5]', $result[1]['name']);
        $this->assertArrayHasKey('contents', $result[1]);
        $this->assertEquals('test_hash_123', $result[1]['contents']);
    }

    public function testToApiArrayValidatesBeforeConversion(): void
    {
        // Arrange
        $dto = new CreateSharedBrandConfigDto([]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $dto->toApiArray();
    }

    public function testEmptyConstructor(): void
    {
        // Arrange & Act
        $dto = new CreateSharedBrandConfigDto();

        // Assert
        $this->assertNull($dto->name);
        $this->assertNull($dto->brandConfigMd5);
    }

    public function testConstructorIgnoresExtraFields(): void
    {
        // Arrange & Act
        $dto = new CreateSharedBrandConfigDto([
            'name' => 'Test Theme',
            'brand_config_md5' => 'test_hash',
            'extra_field' => 'should be ignored',
            'another_extra' => 'also ignored',
        ]);

        // Assert
        $this->assertEquals('Test Theme', $dto->name);
        $this->assertEquals('test_hash', $dto->brandConfigMd5);
        // Extra fields should not cause errors
    }
}