<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\DeveloperKeys;

use CanvasLMS\Dto\DeveloperKeys\CreateDeveloperKeyDTO;
use PHPUnit\Framework\TestCase;

class CreateDeveloperKeyDTOTest extends TestCase
{
    public function testFromArrayCreatesInstanceWithBasicProperties(): void
    {
        $data = [
            'name' => 'Test Developer Key',
            'email' => 'test@example.com',
            'iconUrl' => 'https://example.com/icon.png',
            'notes' => 'This is a test key',
            'vendorCode' => 'TEST',
            'visible' => true,
        ];

        $dto = CreateDeveloperKeyDTO::fromArray($data);

        $this->assertEquals('Test Developer Key', $dto->name);
        $this->assertEquals('test@example.com', $dto->email);
        $this->assertEquals('https://example.com/icon.png', $dto->iconUrl);
        $this->assertEquals('This is a test key', $dto->notes);
        $this->assertEquals('TEST', $dto->vendorCode);
        $this->assertTrue($dto->visible);
    }

    public function testFromArrayCreatesInstanceWithOAuthProperties(): void
    {
        $data = [
            'redirectUris' => ['https://example.com/callback1', 'https://example.com/callback2'],
            'scopes' => ['url:GET|/api/v1/accounts', 'url:GET|/api/v1/courses'],
            'requireScopes' => true,
            'allowIncludes' => false,
        ];

        $dto = CreateDeveloperKeyDTO::fromArray($data);

        $this->assertEquals(['https://example.com/callback1', 'https://example.com/callback2'], $dto->redirectUris);
        $this->assertEquals(['url:GET|/api/v1/accounts', 'url:GET|/api/v1/courses'], $dto->scopes);
        $this->assertTrue($dto->requireScopes);
        $this->assertFalse($dto->allowIncludes);
    }

    public function testFromArrayCreatesInstanceWithSecurityProperties(): void
    {
        $data = [
            'testClusterOnly' => true,
            'autoExpireTokens' => false,
            'clientCredentialsAudience' => 'external',
        ];

        $dto = CreateDeveloperKeyDTO::fromArray($data);

        $this->assertTrue($dto->testClusterOnly);
        $this->assertFalse($dto->autoExpireTokens);
        $this->assertEquals('external', $dto->clientCredentialsAudience);
    }

    public function testToApiArrayWithBasicProperties(): void
    {
        $dto = new CreateDeveloperKeyDTO([
            'name' => 'API Test Key',
            'email' => 'api@example.com',
            'visible' => false,
        ]);

        $apiArray = $dto->toApiArray();

        $this->assertArrayHasKey('developer_key', $apiArray);
        $developerKeyData = $apiArray['developer_key'];

        $this->assertEquals('API Test Key', $developerKeyData['name']);
        $this->assertEquals('api@example.com', $developerKeyData['email']);
        $this->assertFalse($developerKeyData['visible']);
    }

    public function testToApiArrayWithOAuthProperties(): void
    {
        $dto = new CreateDeveloperKeyDTO([
            'redirectUris' => ['https://example.com/oauth'],
            'scopes' => ['url:GET|/api/v1/users'],
            'requireScopes' => true,
        ]);

        $apiArray = $dto->toApiArray();
        $developerKeyData = $apiArray['developer_key'];

        $this->assertEquals(['https://example.com/oauth'], $developerKeyData['redirect_uris']);
        $this->assertEquals(['url:GET|/api/v1/users'], $developerKeyData['scopes']);
        $this->assertTrue($developerKeyData['require_scopes']);
    }

    public function testToApiArraySkipsNullValues(): void
    {
        $dto = new CreateDeveloperKeyDTO([
            'name' => 'Test Key',
            'email' => null,
            'visible' => null,
        ]);

        $apiArray = $dto->toApiArray();
        $developerKeyData = $apiArray['developer_key'];

        $this->assertArrayHasKey('name', $developerKeyData);
        $this->assertArrayNotHasKey('email', $developerKeyData);
        $this->assertArrayNotHasKey('visible', $developerKeyData);
    }

    public function testToApiArraySkipsEmptyArrays(): void
    {
        $dto = new CreateDeveloperKeyDTO([
            'name' => 'Test Key',
            'redirectUris' => [],
            'scopes' => [],
        ]);

        $apiArray = $dto->toApiArray();
        $developerKeyData = $apiArray['developer_key'];

        $this->assertArrayHasKey('name', $developerKeyData);
        $this->assertArrayNotHasKey('redirect_uris', $developerKeyData);
        $this->assertArrayNotHasKey('scopes', $developerKeyData);
    }

    public function testToMultipartArrayWithBasicData(): void
    {
        $dto = new CreateDeveloperKeyDTO([
            'name' => 'Multipart Test',
            'email' => 'multipart@example.com',
        ]);

        $multipart = $dto->toMultipartArray();

        $this->assertIsArray($multipart);
        $this->assertCount(2, $multipart);

        // Check name field
        $nameField = array_filter($multipart, fn ($field) => $field['name'] === 'developer_key[name]');
        $this->assertNotEmpty($nameField);
        $nameField = array_values($nameField)[0];
        $this->assertEquals('Multipart Test', $nameField['contents']);

        // Check email field
        $emailField = array_filter($multipart, fn ($field) => $field['name'] === 'developer_key[email]');
        $this->assertNotEmpty($emailField);
        $emailField = array_values($emailField)[0];
        $this->assertEquals('multipart@example.com', $emailField['contents']);
    }

    public function testToMultipartArrayWithArrayFields(): void
    {
        $dto = new CreateDeveloperKeyDTO([
            'name' => 'Array Test',
            'redirectUris' => ['https://example.com/callback1', 'https://example.com/callback2'],
            'scopes' => ['url:GET|/api/v1/accounts'],
        ]);

        $multipart = $dto->toMultipartArray();

        // Should have: name + 2 redirect URIs + 1 scope = 4 fields
        $this->assertCount(4, $multipart);

        // Check redirect URI fields
        $redirectFields = array_filter($multipart, fn ($field) => strpos($field['name'], 'redirect_uris') !== false);
        $this->assertCount(2, $redirectFields);

        // Check scope fields
        $scopeFields = array_filter($multipart, fn ($field) => strpos($field['name'], 'scopes') !== false);
        $this->assertCount(1, $scopeFields);
    }

    public function testSetterMethods(): void
    {
        $dto = new CreateDeveloperKeyDTO([]);

        $result = $dto->setName('Fluent Test')
                      ->setEmail('fluent@example.com')
                      ->setVisible(true)
                      ->setRequireScopes(false);

        $this->assertSame($dto, $result); // Test fluent interface
        $this->assertEquals('Fluent Test', $dto->name);
        $this->assertEquals('fluent@example.com', $dto->email);
        $this->assertTrue($dto->visible);
        $this->assertFalse($dto->requireScopes);
    }

    public function testAddRedirectUri(): void
    {
        $dto = new CreateDeveloperKeyDTO([]);

        $dto->addRedirectUri('https://example1.com/callback');
        $dto->addRedirectUri('https://example2.com/callback');

        $this->assertEquals([
            'https://example1.com/callback',
            'https://example2.com/callback',
        ], $dto->redirectUris);
    }

    public function testAddRedirectUriToExistingArray(): void
    {
        $dto = new CreateDeveloperKeyDTO([
            'redirectUris' => ['https://existing.com/callback'],
        ]);

        $dto->addRedirectUri('https://new.com/callback');

        $this->assertEquals([
            'https://existing.com/callback',
            'https://new.com/callback',
        ], $dto->redirectUris);
    }

    public function testAddScope(): void
    {
        $dto = new CreateDeveloperKeyDTO([]);

        $dto->addScope('url:GET|/api/v1/accounts');
        $dto->addScope('url:GET|/api/v1/courses');

        $this->assertEquals([
            'url:GET|/api/v1/accounts',
            'url:GET|/api/v1/courses',
        ], $dto->scopes);
    }

    public function testAddScopeToExistingArray(): void
    {
        $dto = new CreateDeveloperKeyDTO([
            'scopes' => ['url:GET|/api/v1/users'],
        ]);

        $dto->addScope('url:GET|/api/v1/accounts');

        $this->assertEquals([
            'url:GET|/api/v1/users',
            'url:GET|/api/v1/accounts',
        ], $dto->scopes);
    }

    public function testAllSetterMethods(): void
    {
        $dto = new CreateDeveloperKeyDTO([]);

        $dto->setName('Complete Test')
            ->setEmail('complete@example.com')
            ->setIconUrl('https://example.com/icon.png')
            ->setNotes('Complete test notes')
            ->setVendorCode('COMPLETE')
            ->setVisible(true)
            ->setRedirectUris(['https://example.com/callback'])
            ->setScopes(['url:GET|/api/v1/accounts'])
            ->setRequireScopes(true)
            ->setAllowIncludes(false)
            ->setTestClusterOnly(true)
            ->setAutoExpireTokens(false)
            ->setClientCredentialsAudience('external');

        $this->assertEquals('Complete Test', $dto->name);
        $this->assertEquals('complete@example.com', $dto->email);
        $this->assertEquals('https://example.com/icon.png', $dto->iconUrl);
        $this->assertEquals('Complete test notes', $dto->notes);
        $this->assertEquals('COMPLETE', $dto->vendorCode);
        $this->assertTrue($dto->visible);
        $this->assertEquals(['https://example.com/callback'], $dto->redirectUris);
        $this->assertEquals(['url:GET|/api/v1/accounts'], $dto->scopes);
        $this->assertTrue($dto->requireScopes);
        $this->assertFalse($dto->allowIncludes);
        $this->assertTrue($dto->testClusterOnly);
        $this->assertFalse($dto->autoExpireTokens);
        $this->assertEquals('external', $dto->clientCredentialsAudience);
    }

    public function testSnakeCaseConversionInApiArray(): void
    {
        $dto = new CreateDeveloperKeyDTO([
            'iconUrl' => 'https://example.com/icon.png',
            'vendorCode' => 'TEST_VENDOR',
            'redirectUris' => ['https://example.com/callback'],
            'requireScopes' => true,
            'allowIncludes' => false,
            'testClusterOnly' => true,
            'autoExpireTokens' => false,
            'clientCredentialsAudience' => 'external',
        ]);

        $apiArray = $dto->toApiArray();
        $data = $apiArray['developer_key'];

        // Verify camelCase properties are converted to snake_case in API
        $this->assertArrayHasKey('icon_url', $data);
        $this->assertArrayHasKey('vendor_code', $data);
        $this->assertArrayHasKey('redirect_uris', $data);
        $this->assertArrayHasKey('require_scopes', $data);
        $this->assertArrayHasKey('allow_includes', $data);
        $this->assertArrayHasKey('test_cluster_only', $data);
        $this->assertArrayHasKey('auto_expire_tokens', $data);
        $this->assertArrayHasKey('client_credentials_audience', $data);

        // Verify values
        $this->assertEquals('https://example.com/icon.png', $data['icon_url']);
        $this->assertEquals('TEST_VENDOR', $data['vendor_code']);
        $this->assertEquals(['https://example.com/callback'], $data['redirect_uris']);
        $this->assertTrue($data['require_scopes']);
        $this->assertFalse($data['allow_includes']);
        $this->assertTrue($data['test_cluster_only']);
        $this->assertFalse($data['auto_expire_tokens']);
        $this->assertEquals('external', $data['client_credentials_audience']);
    }
}
