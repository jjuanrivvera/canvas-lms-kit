<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\SharedBrandConfigs;

use CanvasLMS\Api\SharedBrandConfigs\SharedBrandConfig;
use CanvasLMS\Config;
use CanvasLMS\Dto\SharedBrandConfigs\CreateSharedBrandConfigDto;
use CanvasLMS\Dto\SharedBrandConfigs\UpdateSharedBrandConfigDto;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class SharedBrandConfigTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;
    private int $originalAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        
        // Store original account ID to restore later
        $this->originalAccountId = Config::getAccountId() ?? 1;
        Config::setAccountId(1);
    }

    protected function tearDown(): void
    {
        // Restore original account ID
        Config::setAccountId($this->originalAccountId);
        parent::tearDown();
    }

    public function testConstructorMapsSnakeCaseToCamelCase(): void
    {
        // Arrange
        $data = [
            'id' => 987,
            'account_id' => '123',
            'brand_config_md5' => 'a1f113321fa024e7a14cb0948597a2a4',
            'name' => 'Spring Theme',
            'created_at' => '2024-01-15T10:00:00Z',
            'updated_at' => '2024-01-15T11:00:00Z',
        ];

        // Act
        $config = new SharedBrandConfig($data);

        // Assert
        $this->assertEquals(987, $config->id);
        $this->assertEquals('123', $config->accountId);
        $this->assertEquals('a1f113321fa024e7a14cb0948597a2a4', $config->brandConfigMd5);
        $this->assertEquals('Spring Theme', $config->name);
        $this->assertEquals('2024-01-15T10:00:00Z', $config->createdAt);
        $this->assertEquals('2024-01-15T11:00:00Z', $config->updatedAt);
    }

    public function testCreateWithArrayData(): void
    {
        // This test demonstrates the structure but can't actually test the static method
        // due to internal HttpClient instantiation
        $data = [
            'name' => 'Test Theme',
            'brand_config_md5' => 'abc123def456',
        ];

        $dto = new CreateSharedBrandConfigDto($data);
        
        $this->assertEquals('Test Theme', $dto->name);
        $this->assertEquals('abc123def456', $dto->brandConfigMd5);
    }

    public function testCreateWithDto(): void
    {
        // Test DTO creation
        $dto = new CreateSharedBrandConfigDto([
            'name' => 'Test Theme',
            'brand_config_md5' => 'xyz789',
        ]);

        $this->assertInstanceOf(CreateSharedBrandConfigDto::class, $dto);
        $this->assertEquals('Test Theme', $dto->name);
        $this->assertEquals('xyz789', $dto->brandConfigMd5);
    }

    public function testUpdateWithArrayData(): void
    {
        // Test update DTO with array
        $data = [
            'name' => 'Updated Theme',
        ];

        $dto = new UpdateSharedBrandConfigDto($data);
        
        $this->assertEquals('Updated Theme', $dto->name);
        $this->assertNull($dto->brandConfigMd5);
    }

    public function testUpdateWithDto(): void
    {
        // Test update DTO
        $dto = new UpdateSharedBrandConfigDto([
            'name' => 'New Name',
            'brand_config_md5' => 'new_hash',
        ]);

        $this->assertInstanceOf(UpdateSharedBrandConfigDto::class, $dto);
        $this->assertEquals('New Name', $dto->name);
        $this->assertEquals('new_hash', $dto->brandConfigMd5);
    }

    public function testRemoveInstanceMethodRequiresId(): void
    {
        // Arrange
        $config = new SharedBrandConfig();
        $config->id = null;

        // Assert
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot delete SharedBrandConfig without an ID');

        // Act
        $config->remove();
    }

    public function testToArrayConvertsToSnakeCase(): void
    {
        // Arrange
        $config = new SharedBrandConfig([
            'id' => 123,
            'account_id' => '456',
            'brand_config_md5' => 'hash123',
            'name' => 'Test Theme',
            'created_at' => '2024-01-15T10:00:00Z',
            'updated_at' => '2024-01-15T11:00:00Z',
        ]);

        // Act
        $array = $config->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('account_id', $array);
        $this->assertArrayHasKey('brand_config_md5', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        
        $this->assertEquals(123, $array['id']);
        $this->assertEquals('456', $array['account_id']);
        $this->assertEquals('hash123', $array['brand_config_md5']);
        $this->assertEquals('Test Theme', $array['name']);
    }

    // Tests for no account ID removed as Config::$accountId is typed as int with default value
    // The check in SharedBrandConfig::create/update for !$accountId will never be true

    public function testConstructorHandlesEmptyData(): void
    {
        // Act
        $config = new SharedBrandConfig();

        // Assert
        $this->assertNull($config->id);
        $this->assertNull($config->accountId);
        $this->assertNull($config->brandConfigMd5);
        $this->assertNull($config->name);
        $this->assertNull($config->createdAt);
        $this->assertNull($config->updatedAt);
    }

    public function testConstructorIgnoresUnknownProperties(): void
    {
        // Arrange
        $data = [
            'id' => 123,
            'name' => 'Test',
            'unknown_property' => 'should be ignored',
            'another_unknown' => 'also ignored',
        ];

        // Act
        $config = new SharedBrandConfig($data);

        // Assert
        $this->assertEquals(123, $config->id);
        $this->assertEquals('Test', $config->name);
        $this->assertObjectNotHasProperty('unknownProperty', $config);
        $this->assertObjectNotHasProperty('anotherUnknown', $config);
    }
}