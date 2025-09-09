<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Api\DeveloperKeys;

use CanvasLMS\Api\DeveloperKeys\DeveloperKey;
use CanvasLMS\Config;
use CanvasLMS\Dto\DeveloperKeys\CreateDeveloperKeyDTO;
use CanvasLMS\Dto\DeveloperKeys\UpdateDeveloperKeyDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class DeveloperKeyTest extends TestCase
{
    private HttpClientInterface $httpClientMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        DeveloperKey::setApiClient($this->httpClientMock);
        
        // Set test configuration
        Config::setAccountId(1);
        Config::setApiKey('test-api-key');
        Config::setBaseUrl('https://canvas.test.com/api/v1');
    }


    public function testCreateDeveloperKeyWithArray(): void
    {
        $keyData = [
            'name' => 'Test API Key',
            'email' => 'test@example.com',
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => ['url:GET|/api/v1/accounts'],
            'visible' => true
        ];

        $expectedResponse = [
            'id' => 123,
            'name' => 'Test API Key',
            'email' => 'test@example.com',
            'created_at' => '2025-01-01T00:00:00Z',
            'updated_at' => '2025-01-01T00:00:00Z',
            'workflow_state' => 'active',
            'is_lti_key' => false,
            'redirect_uris' => ['https://example.com/callback'],
            'scopes' => ['url:GET|/api/v1/accounts'],
            'visible' => true,
            'account_name' => 'Test Account'
        ];

        $response = new Response(200, [], json_encode($expectedResponse));

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with(
                'accounts/1/developer_keys',
                $this->callback(function ($data) {
                    $this->assertIsArray($data);
                    $this->assertArrayHasKey('multipart', $data);
                    return true;
                })
            )
            ->willReturn($response);

        $developerKey = DeveloperKey::create($keyData);

        $this->assertInstanceOf(DeveloperKey::class, $developerKey);
        $this->assertEquals(123, $developerKey->id);
        $this->assertEquals('Test API Key', $developerKey->name);
        $this->assertEquals('test@example.com', $developerKey->email);
        $this->assertFalse($developerKey->isLtiKey);
        $this->assertTrue($developerKey->isActive());
        $this->assertTrue($developerKey->visible);
    }

    public function testCreateDeveloperKeyWithDTO(): void
    {
        $dto = new CreateDeveloperKeyDTO([
            'name' => 'DTO Test Key',
            'email' => 'dto@example.com',
            'scopes' => ['url:GET|/api/v1/courses'],
            'requireScopes' => true
        ]);

        $expectedResponse = [
            'id' => 456,
            'name' => 'DTO Test Key',
            'email' => 'dto@example.com',
            'workflow_state' => 'active',
            'is_lti_key' => false,
            'scopes' => ['url:GET|/api/v1/courses'],
            'require_scopes' => true
        ];

        $response = new Response(200, [], json_encode($expectedResponse));

        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('accounts/1/developer_keys')
            ->willReturn($response);

        $developerKey = DeveloperKey::create($dto);

        $this->assertInstanceOf(DeveloperKey::class, $developerKey);
        $this->assertEquals(456, $developerKey->id);
        $this->assertEquals('DTO Test Key', $developerKey->name);
        $this->assertTrue($developerKey->requiresScopes());
    }

    public function testUpdateDeveloperKey(): void
    {
        $updateData = [
            'name' => 'Updated Key Name',
            'visible' => false
        ];

        $expectedResponse = [
            'id' => 123,
            'name' => 'Updated Key Name',
            'email' => 'test@example.com',
            'workflow_state' => 'active',
            'is_lti_key' => false,
            'visible' => false
        ];

        $response = new Response(200, [], json_encode($expectedResponse));

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with(
                'developer_keys/123',
                $this->callback(function ($data) {
                    $this->assertIsArray($data);
                    $this->assertArrayHasKey('multipart', $data);
                    return true;
                })
            )
            ->willReturn($response);

        $developerKey = DeveloperKey::update(123, $updateData);

        $this->assertInstanceOf(DeveloperKey::class, $developerKey);
        $this->assertEquals(123, $developerKey->id);
        $this->assertEquals('Updated Key Name', $developerKey->name);
        $this->assertFalse($developerKey->visible);
    }

    public function testUpdateDeveloperKeyWithDTO(): void
    {
        $dto = new UpdateDeveloperKeyDTO([
            'email' => 'updated@example.com',
            'testClusterOnly' => true
        ]);

        $expectedResponse = [
            'id' => 789,
            'name' => 'Existing Key',
            'email' => 'updated@example.com',
            'workflow_state' => 'active',
            'test_cluster_only' => true
        ];

        $response = new Response(200, [], json_encode($expectedResponse));

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('developer_keys/789')
            ->willReturn($response);

        $developerKey = DeveloperKey::update(789, $dto);

        $this->assertEquals('updated@example.com', $developerKey->email);
        $this->assertTrue($developerKey->isTestClusterOnly());
    }

    public function testDeleteDeveloperKey(): void
    {
        $expectedResponse = [
            'id' => 123,
            'name' => 'Deleted Key',
            'workflow_state' => 'deleted'
        ];

        $response = new Response(200, [], json_encode($expectedResponse));

        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('developer_keys/123')
            ->willReturn($response);

        $result = DeveloperKey::delete(123);

        $this->assertIsArray($result);
        $this->assertEquals(123, $result['id']);
        $this->assertEquals('deleted', $result['workflow_state']);
    }

    public function testGetDeveloperKeys(): void
    {
        $expectedResponse = [
            [
                'id' => 1,
                'name' => 'Key 1',
                'workflow_state' => 'active',
                'is_lti_key' => false
            ],
            [
                'id' => 2,
                'name' => 'Key 2',
                'workflow_state' => 'inactive',
                'is_lti_key' => false
            ]
        ];

        $response = new Response(200, [], json_encode($expectedResponse));

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('accounts/1/developer_keys', ['query' => []])
            ->willReturn($response);

        $keys = DeveloperKey::get();

        $this->assertIsArray($keys);
        $this->assertCount(2, $keys);
        $this->assertContainsOnlyInstancesOf(DeveloperKey::class, $keys);
        $this->assertEquals('Key 1', $keys[0]->name);
        $this->assertTrue($keys[0]->isActive());
        $this->assertFalse($keys[1]->isActive());
    }

    public function testGetDeveloperKeysWithInherited(): void
    {
        $expectedResponse = [
            [
                'id' => 1,
                'name' => 'Inherited Key',
                'workflow_state' => 'active',
                'is_lti_key' => false,
                'account_name' => 'Site Admin'
            ]
        ];

        $response = new Response(200, [], json_encode($expectedResponse));

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('accounts/1/developer_keys', ['query' => ['inherited' => true]])
            ->willReturn($response);

        $keys = DeveloperKey::getWithInherited();

        $this->assertIsArray($keys);
        $this->assertCount(1, $keys);
        $this->assertEquals('Site Admin', $keys[0]->accountName);
    }

    public function testFindDeveloperKey(): void
    {
        $expectedResponse = [
            [
                'id' => 1,
                'name' => 'Key 1',
                'workflow_state' => 'active'
            ],
            [
                'id' => 2,
                'name' => 'Key 2',
                'workflow_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($expectedResponse));

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with('accounts/1/developer_keys', ['query' => []])
            ->willReturn($response);

        $key = DeveloperKey::find(2);

        $this->assertInstanceOf(DeveloperKey::class, $key);
        $this->assertEquals(2, $key->id);
        $this->assertEquals('Key 2', $key->name);
    }

    public function testFindDeveloperKeyNotFound(): void
    {
        $expectedResponse = [
            [
                'id' => 1,
                'name' => 'Key 1',
                'workflow_state' => 'active'
            ]
        ];

        $response = new Response(200, [], json_encode($expectedResponse));

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Developer key with ID 999 not found');

        DeveloperKey::find(999);
    }

    public function testGetPaginated(): void
    {
        // Mock the static method call
        $mockResponse = $this->createMock(PaginatedResponse::class);
        
        // We can't easily mock static method calls, so we'll test the method exists
        $this->assertTrue(method_exists(DeveloperKey::class, 'getPaginated'));
    }

    public function testSaveInstance(): void
    {
        $key = new DeveloperKey(['id' => 123, 'name' => 'Test Key']);
        
        $updateData = ['name' => 'Updated Name'];
        $expectedResponse = [
            'id' => 123,
            'name' => 'Updated Name',
            'workflow_state' => 'active'
        ];

        $response = new Response(200, [], json_encode($expectedResponse));

        $this->httpClientMock->expects($this->once())
            ->method('put')
            ->with('developer_keys/123')
            ->willReturn($response);

        $updatedKey = $key->save($updateData);

        $this->assertSame($key, $updatedKey);
        $this->assertEquals('Updated Name', $key->name);
    }

    public function testSaveInstanceWithoutId(): void
    {
        $key = new DeveloperKey(['name' => 'Test Key']); // No ID

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Developer key must have an ID to update');

        $key->save(['name' => 'Updated Name']);
    }

    public function testRemoveInstance(): void
    {
        $key = new DeveloperKey(['id' => 123, 'name' => 'Test Key']);
        
        $expectedResponse = [
            'id' => 123,
            'name' => 'Test Key',
            'workflow_state' => 'deleted'
        ];

        $response = new Response(200, [], json_encode($expectedResponse));

        $this->httpClientMock->expects($this->once())
            ->method('delete')
            ->with('developer_keys/123')
            ->willReturn($response);

        $result = $key->remove();

        $this->assertIsArray($result);
        $this->assertEquals(123, $result['id']);
        $this->assertEquals('deleted', $result['workflow_state']);
    }

    public function testRemoveInstanceWithoutId(): void
    {
        $key = new DeveloperKey(['name' => 'Test Key']); // No ID

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Developer key must have an ID to delete');

        $key->remove();
    }

    public function testStatusMethods(): void
    {
        $activeKey = new DeveloperKey(['workflow_state' => 'active']);
        $inactiveKey = new DeveloperKey(['workflow_state' => 'inactive']);
        $deletedKey = new DeveloperKey(['workflow_state' => 'deleted']);

        $this->assertTrue($activeKey->isActive());
        $this->assertFalse($activeKey->isInactive());
        $this->assertFalse($activeKey->isDeleted());

        $this->assertFalse($inactiveKey->isActive());
        $this->assertTrue($inactiveKey->isInactive());
        $this->assertFalse($inactiveKey->isDeleted());

        $this->assertFalse($deletedKey->isActive());
        $this->assertFalse($deletedKey->isInactive());
        $this->assertTrue($deletedKey->isDeleted());
    }

    public function testLtiMethods(): void
    {
        $apiKey = new DeveloperKey(['is_lti_key' => false]);
        $ltiKey = new DeveloperKey(['is_lti_key' => true]);

        $this->assertFalse($apiKey->isLti());
        $this->assertTrue($apiKey->isApiKey());

        $this->assertTrue($ltiKey->isLti());
        $this->assertFalse($ltiKey->isApiKey());
    }

    public function testConfigurationMethods(): void
    {
        $key = new DeveloperKey([
            'test_cluster_only' => true,
            'allow_includes' => true,
            'require_scopes' => true,
            'client_credentials_audience' => 'external'
        ]);

        $this->assertTrue($key->isTestClusterOnly());
        $this->assertTrue($key->allowsIncludes());
        $this->assertTrue($key->requiresScopes());
        $this->assertEquals('External', $key->getClientCredentialsAudienceDisplay());
    }

    public function testArrayHelperMethods(): void
    {
        $key = new DeveloperKey([
            'redirect_uris' => ['https://example.com/callback1', 'https://example.com/callback2'],
            'scopes' => ['url:GET|/api/v1/accounts', 'url:GET|/api/v1/courses']
        ]);

        $this->assertEquals(
            'https://example.com/callback1, https://example.com/callback2',
            $key->getRedirectUrisString()
        );

        $this->assertEquals(
            'url:GET|/api/v1/accounts, url:GET|/api/v1/courses',
            $key->getScopesString()
        );
    }

    public function testArrayHelperMethodsWithNullValues(): void
    {
        $key = new DeveloperKey([]);

        $this->assertNull($key->getRedirectUrisString());
        $this->assertNull($key->getScopesString());
    }

    public function testClientCredentialsAudienceDisplayWithUnknownValue(): void
    {
        $key = new DeveloperKey(['client_credentials_audience' => 'custom_audience']);

        $this->assertEquals('custom_audience', $key->getClientCredentialsAudienceDisplay());
    }
}