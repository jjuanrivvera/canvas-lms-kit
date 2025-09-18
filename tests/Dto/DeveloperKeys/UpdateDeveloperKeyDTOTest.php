<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\DeveloperKeys;

use CanvasLMS\Dto\DeveloperKeys\UpdateDeveloperKeyDTO;
use PHPUnit\Framework\TestCase;

class UpdateDeveloperKeyDTOTest extends TestCase
{
    public function testFromArrayCreatesInstanceWithPartialProperties(): void
    {
        $data = [
            'name' => 'Updated Key Name',
            'visible' => false,
        ];

        $dto = UpdateDeveloperKeyDTO::fromArray($data);

        $this->assertEquals('Updated Key Name', $dto->name);
        $this->assertFalse($dto->visible);
        $this->assertNull($dto->email); // Should remain null
        $this->assertNull($dto->scopes); // Should remain null
    }

    public function testToApiArrayWithPartialData(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'name' => 'Partial Update',
            'email' => 'updated@example.com',
        ]);

        $apiArray = $dto->toApiArray();
        $developerKeyData = $apiArray['developer_key'];

        $this->assertArrayHasKey('name', $developerKeyData);
        $this->assertArrayHasKey('email', $developerKeyData);
        $this->assertArrayNotHasKey('visible', $developerKeyData); // Not set, should not be present
        $this->assertArrayNotHasKey('scopes', $developerKeyData); // Not set, should not be present

        $this->assertEquals('Partial Update', $developerKeyData['name']);
        $this->assertEquals('updated@example.com', $developerKeyData['email']);
    }

    public function testToApiArrayWithEmptyArraysIncluded(): void
    {
        // Unlike CreateDTO, UpdateDTO should include empty arrays to allow clearing
        $dto = new UpdateDeveloperKeyDTO([
            'name' => 'Clear Arrays Test',
            'redirectUris' => [], // Explicitly setting to empty to clear
            'scopes' => [], // Explicitly setting to empty to clear
        ]);

        $apiArray = $dto->toApiArray();
        $developerKeyData = $apiArray['developer_key'];

        $this->assertArrayHasKey('redirect_uris', $developerKeyData);
        $this->assertArrayHasKey('scopes', $developerKeyData);
        $this->assertEquals([], $developerKeyData['redirect_uris']);
        $this->assertEquals([], $developerKeyData['scopes']);
    }

    public function testHasUpdatesReturnsFalseWhenEmpty(): void
    {
        $dto = new UpdateDeveloperKeyDTO([]);

        $this->assertFalse($dto->hasUpdates());
    }

    public function testHasUpdatesReturnsTrueWhenFieldsSet(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'name' => 'Has Updates Test',
        ]);

        $this->assertTrue($dto->hasUpdates());
    }

    public function testGetUpdatedFieldsReturnsEmptyArrayWhenNoUpdates(): void
    {
        $dto = new UpdateDeveloperKeyDTO([]);

        $updatedFields = $dto->getUpdatedFields();

        $this->assertIsArray($updatedFields);
        $this->assertEmpty($updatedFields);
    }

    public function testGetUpdatedFieldsReturnsCorrectFields(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'name' => 'Field Test',
            'visible' => false,
            'requireScopes' => true,
        ]);

        $updatedFields = $dto->getUpdatedFields();

        $this->assertCount(3, $updatedFields);
        $this->assertContains('name', $updatedFields);
        $this->assertContains('visible', $updatedFields);
        $this->assertContains('requireScopes', $updatedFields);
        $this->assertNotContains('email', $updatedFields);
    }

    public function testClearField(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'name' => 'Clear Test',
            'email' => 'clear@example.com',
            'visible' => true,
        ]);

        $this->assertEquals('Clear Test', $dto->name);
        $this->assertTrue($dto->hasUpdates());

        $result = $dto->clearField('name');

        $this->assertSame($dto, $result); // Test fluent interface
        $this->assertNull($dto->name);
        $this->assertEquals('clear@example.com', $dto->email); // Other fields unchanged
        $this->assertTrue($dto->hasUpdates()); // Still has email and visible
    }

    public function testClearFieldIgnoresProtectedProperty(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'name' => 'Protected Test',
        ]);

        // Try to clear the protected apiPropertyName - should be ignored
        $dto->clearField('apiPropertyName');

        // Property should remain unchanged
        $this->assertTrue($dto->hasUpdates());
    }

    public function testClearFieldIgnoresNonexistentProperty(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'name' => 'Nonexistent Test',
        ]);

        // Try to clear a nonexistent property - should not cause error
        $dto->clearField('nonexistentProperty');

        $this->assertEquals('Nonexistent Test', $dto->name);
        $this->assertTrue($dto->hasUpdates());
    }

    public function testAddRedirectUri(): void
    {
        $dto = new UpdateDeveloperKeyDTO([]);

        $dto->addRedirectUri('https://example.com/new-callback');

        $this->assertEquals(['https://example.com/new-callback'], $dto->redirectUris);
        $this->assertTrue($dto->hasUpdates());
    }

    public function testAddRedirectUriToExistingArray(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'redirectUris' => ['https://existing.com/callback'],
        ]);

        $dto->addRedirectUri('https://new.com/callback');

        $this->assertEquals([
            'https://existing.com/callback',
            'https://new.com/callback',
        ], $dto->redirectUris);
    }

    public function testAddRedirectUriAvoidsDuplicates(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'redirectUris' => ['https://example.com/callback'],
        ]);

        $dto->addRedirectUri('https://example.com/callback'); // Duplicate

        $this->assertEquals(['https://example.com/callback'], $dto->redirectUris);
    }

    public function testRemoveRedirectUri(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'redirectUris' => [
                'https://keep.com/callback',
                'https://remove.com/callback',
                'https://alsokeep.com/callback',
            ],
        ]);

        $result = $dto->removeRedirectUri('https://remove.com/callback');

        $this->assertSame($dto, $result); // Test fluent interface
        $this->assertEquals([
            'https://keep.com/callback',
            'https://alsokeep.com/callback',
        ], $dto->redirectUris);
    }

    public function testRemoveRedirectUriFromNullArray(): void
    {
        $dto = new UpdateDeveloperKeyDTO([]);

        $dto->removeRedirectUri('https://example.com/callback');

        $this->assertNull($dto->redirectUris);
    }

    public function testAddScope(): void
    {
        $dto = new UpdateDeveloperKeyDTO([]);

        $dto->addScope('url:GET|/api/v1/new-endpoint');

        $this->assertEquals(['url:GET|/api/v1/new-endpoint'], $dto->scopes);
        $this->assertTrue($dto->hasUpdates());
    }

    public function testAddScopeToExistingArray(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'scopes' => ['url:GET|/api/v1/accounts'],
        ]);

        $dto->addScope('url:GET|/api/v1/courses');

        $this->assertEquals([
            'url:GET|/api/v1/accounts',
            'url:GET|/api/v1/courses',
        ], $dto->scopes);
    }

    public function testAddScopeAvoidsDuplicates(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'scopes' => ['url:GET|/api/v1/accounts'],
        ]);

        $dto->addScope('url:GET|/api/v1/accounts'); // Duplicate

        $this->assertEquals(['url:GET|/api/v1/accounts'], $dto->scopes);
    }

    public function testRemoveScope(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'scopes' => [
                'url:GET|/api/v1/accounts',
                'url:GET|/api/v1/courses',
                'url:GET|/api/v1/users',
            ],
        ]);

        $result = $dto->removeScope('url:GET|/api/v1/courses');

        $this->assertSame($dto, $result); // Test fluent interface
        $this->assertEquals([
            'url:GET|/api/v1/accounts',
            'url:GET|/api/v1/users',
        ], $dto->scopes);
    }

    public function testRemoveScopeFromNullArray(): void
    {
        $dto = new UpdateDeveloperKeyDTO([]);

        $dto->removeScope('url:GET|/api/v1/accounts');

        $this->assertNull($dto->scopes);
    }

    public function testToMultipartArrayWithPartialData(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'name' => 'Multipart Update',
            'visible' => false,
        ]);

        $multipart = $dto->toMultipartArray();

        $this->assertIsArray($multipart);
        $this->assertCount(2, $multipart);

        // Check that only the set fields are included
        $fieldNames = array_map(fn ($field) => $field['name'], $multipart);
        $this->assertContains('developer_key[name]', $fieldNames);
        $this->assertContains('developer_key[visible]', $fieldNames);
        $this->assertNotContains('developer_key[email]', $fieldNames);
    }

    public function testAllSetterMethods(): void
    {
        $dto = new UpdateDeveloperKeyDTO([]);

        $dto->setName('Update Test')
            ->setEmail('update@example.com')
            ->setIconUrl('https://example.com/new-icon.png')
            ->setNotes('Updated notes')
            ->setVendorCode('UPDATED')
            ->setVisible(false)
            ->setRedirectUris(['https://example.com/updated-callback'])
            ->setScopes(['url:GET|/api/v1/updated-endpoint'])
            ->setRequireScopes(false)
            ->setAllowIncludes(true)
            ->setTestClusterOnly(false)
            ->setAutoExpireTokens(true)
            ->setClientCredentialsAudience('internal');

        $this->assertEquals('Update Test', $dto->name);
        $this->assertEquals('update@example.com', $dto->email);
        $this->assertEquals('https://example.com/new-icon.png', $dto->iconUrl);
        $this->assertEquals('Updated notes', $dto->notes);
        $this->assertEquals('UPDATED', $dto->vendorCode);
        $this->assertFalse($dto->visible);
        $this->assertEquals(['https://example.com/updated-callback'], $dto->redirectUris);
        $this->assertEquals(['url:GET|/api/v1/updated-endpoint'], $dto->scopes);
        $this->assertFalse($dto->requireScopes);
        $this->assertTrue($dto->allowIncludes);
        $this->assertFalse($dto->testClusterOnly);
        $this->assertTrue($dto->autoExpireTokens);
        $this->assertEquals('internal', $dto->clientCredentialsAudience);

        $this->assertTrue($dto->hasUpdates());
        $this->assertCount(13, $dto->getUpdatedFields());
    }

    public function testSnakeCaseConversionInApiArray(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'iconUrl' => 'https://example.com/update-icon.png',
            'vendorCode' => 'UPDATE_VENDOR',
            'redirectUris' => ['https://example.com/update-callback'],
            'requireScopes' => false,
            'allowIncludes' => true,
            'testClusterOnly' => false,
            'autoExpireTokens' => true,
            'clientCredentialsAudience' => 'internal',
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
        $this->assertEquals('https://example.com/update-icon.png', $data['icon_url']);
        $this->assertEquals('UPDATE_VENDOR', $data['vendor_code']);
        $this->assertEquals(['https://example.com/update-callback'], $data['redirect_uris']);
        $this->assertFalse($data['require_scopes']);
        $this->assertTrue($data['allow_includes']);
        $this->assertFalse($data['test_cluster_only']);
        $this->assertTrue($data['auto_expire_tokens']);
        $this->assertEquals('internal', $data['client_credentials_audience']);
    }

    public function testComplexUpdateScenario(): void
    {
        // Test a realistic update scenario
        $dto = new UpdateDeveloperKeyDTO([]);

        // Start with some initial values
        $dto->setName('Initial Name')
            ->setScopes(['url:GET|/api/v1/accounts']);

        $this->assertTrue($dto->hasUpdates());
        $this->assertCount(2, $dto->getUpdatedFields());

        // Add more scopes
        $dto->addScope('url:GET|/api/v1/courses');
        $dto->addScope('url:GET|/api/v1/users');

        $this->assertEquals([
            'url:GET|/api/v1/accounts',
            'url:GET|/api/v1/courses',
            'url:GET|/api/v1/users',
        ], $dto->scopes);

        // Remove one scope
        $dto->removeScope('url:GET|/api/v1/courses');

        $this->assertEquals([
            'url:GET|/api/v1/accounts',
            'url:GET|/api/v1/users',
        ], $dto->scopes);

        // Clear a field
        $dto->clearField('name');

        $this->assertNull($dto->name);
        $this->assertTrue($dto->hasUpdates()); // Still has scopes

        // Verify final state
        $updatedFields = $dto->getUpdatedFields();
        $this->assertCount(1, $updatedFields);
        $this->assertContains('scopes', $updatedFields);
        $this->assertNotContains('name', $updatedFields);
    }
}
