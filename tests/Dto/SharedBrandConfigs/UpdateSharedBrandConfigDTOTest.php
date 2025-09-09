<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\SharedBrandConfigs;

use CanvasLMS\Dto\SharedBrandConfigs\UpdateSharedBrandConfigDTO;
use PHPUnit\Framework\TestCase;

class UpdateSharedBrandConfigDTOTest extends TestCase
{
    public function testConstructorSetsName(): void
    {
        // Arrange & Act
        $dto = new UpdateSharedBrandConfigDTO([
            'name' => 'Updated Theme Name',
        ]);

        // Assert
        $this->assertEquals('Updated Theme Name', $dto->name);
        $this->assertNull($dto->brandConfigMd5);
    }

    public function testConstructorSetsMd5(): void
    {
        // Arrange & Act
        $dto = new UpdateSharedBrandConfigDTO([
            'brand_config_md5' => 'new_hash_456',
        ]);

        // Assert
        $this->assertNull($dto->name);
        $this->assertEquals('new_hash_456', $dto->brandConfigMd5);
    }

    public function testConstructorSetsBothFields(): void
    {
        // Arrange & Act
        $dto = new UpdateSharedBrandConfigDTO([
            'name' => 'New Name',
            'brand_config_md5' => 'new_hash',
        ]);

        // Assert
        $this->assertEquals('New Name', $dto->name);
        $this->assertEquals('new_hash', $dto->brandConfigMd5);
    }

    public function testConstructorHandlesCamelCaseMd5(): void
    {
        // Arrange & Act
        $dto = new UpdateSharedBrandConfigDTO([
            'brandConfigMd5' => 'camel_case_hash',
        ]);

        // Assert
        $this->assertEquals('camel_case_hash', $dto->brandConfigMd5);
    }

    public function testValidateThrowsExceptionWhenNoFieldsProvided(): void
    {
        // Arrange
        $dto = new UpdateSharedBrandConfigDTO([]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one field (name or brand_config_md5) must be provided for update');

        // Act
        $dto->validate();
    }

    public function testValidatePassesWithOnlyName(): void
    {
        // Arrange
        $dto = new UpdateSharedBrandConfigDTO([
            'name' => 'Valid Name',
        ]);

        // Act & Assert - No exception should be thrown
        $dto->validate();
        $this->assertTrue(true);
    }

    public function testValidatePassesWithOnlyMd5(): void
    {
        // Arrange
        $dto = new UpdateSharedBrandConfigDTO([
            'brand_config_md5' => 'valid_hash',
        ]);

        // Act & Assert - No exception should be thrown
        $dto->validate();
        $this->assertTrue(true);
    }

    public function testValidatePassesWithBothFields(): void
    {
        // Arrange
        $dto = new UpdateSharedBrandConfigDTO([
            'name' => 'Valid Name',
            'brand_config_md5' => 'valid_hash',
        ]);

        // Act & Assert - No exception should be thrown
        $dto->validate();
        $this->assertTrue(true);
    }

    public function testToApiArrayWithOnlyName(): void
    {
        // Arrange
        $dto = new UpdateSharedBrandConfigDTO([
            'name' => 'Updated Name Only',
        ]);

        // Act
        $result = $dto->toApiArray();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertEquals('shared_brand_config[name]', $result[0]['name']);
        $this->assertArrayHasKey('contents', $result[0]);
        $this->assertEquals('Updated Name Only', $result[0]['contents']);
    }

    public function testToApiArrayWithOnlyMd5(): void
    {
        // Arrange
        $dto = new UpdateSharedBrandConfigDTO([
            'brand_config_md5' => 'updated_hash_only',
        ]);

        // Act
        $result = $dto->toApiArray();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertEquals('shared_brand_config[brand_config_md5]', $result[0]['name']);
        $this->assertArrayHasKey('contents', $result[0]);
        $this->assertEquals('updated_hash_only', $result[0]['contents']);
    }

    public function testToApiArrayWithBothFields(): void
    {
        // Arrange
        $dto = new UpdateSharedBrandConfigDTO([
            'name' => 'Both Fields',
            'brand_config_md5' => 'both_hash',
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
        $this->assertEquals('Both Fields', $result[0]['contents']);
        
        // Check brand_config_md5 field
        $this->assertArrayHasKey('name', $result[1]);
        $this->assertEquals('shared_brand_config[brand_config_md5]', $result[1]['name']);
        $this->assertArrayHasKey('contents', $result[1]);
        $this->assertEquals('both_hash', $result[1]['contents']);
    }

    public function testToApiArrayValidatesBeforeConversion(): void
    {
        // Arrange
        $dto = new UpdateSharedBrandConfigDTO([]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $dto->toApiArray();
    }

    public function testEmptyConstructor(): void
    {
        // Arrange & Act
        $dto = new UpdateSharedBrandConfigDTO();

        // Assert
        $this->assertNull($dto->name);
        $this->assertNull($dto->brandConfigMd5);
    }

    public function testConstructorIgnoresExtraFields(): void
    {
        // Arrange & Act
        $dto = new UpdateSharedBrandConfigDTO([
            'name' => 'Test Theme',
            'extra_field' => 'should be ignored',
            'id' => 123, // Should be ignored
        ]);

        // Assert
        $this->assertEquals('Test Theme', $dto->name);
        $this->assertNull($dto->brandConfigMd5);
        // Extra fields should not cause errors
    }

    public function testEmptyStringConsideredEmpty(): void
    {
        // Arrange
        $dto = new UpdateSharedBrandConfigDTO([
            'name' => '',
            'brand_config_md5' => '',
        ]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one field (name or brand_config_md5) must be provided for update');

        // Act
        $dto->validate();
    }
}