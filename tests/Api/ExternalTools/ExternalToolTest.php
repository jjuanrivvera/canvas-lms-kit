<?php

declare(strict_types=1);

namespace Tests\Api\ExternalTools;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\ExternalTools\ExternalTool;
use CanvasLMS\Dto\ExternalTools\CreateExternalToolDTO;
use CanvasLMS\Dto\ExternalTools\UpdateExternalToolDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Test class for External Tools API
 *
 * @covers \CanvasLMS\Api\ExternalTools\ExternalTool
 */
class ExternalToolTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClientMock;
    private ResponseInterface&MockObject $mockResponse;
    private StreamInterface&MockObject $mockStream;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);
        
        ExternalTool::setApiClient($this->httpClientMock);
        \CanvasLMS\Config::setAccountId(1);
        
        $this->course = new Course(['id' => 123, 'name' => 'Test Course']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }


    public static function externalToolDataProvider(): array
    {
        return [
            'basic external tool' => [
                [
                    'id' => 1,
                    'name' => 'Test Tool',
                    'description' => 'A test external tool',
                    'url' => 'https://example.com/lti/launch',
                    'consumer_key' => 'test_key',
                    'privacy_level' => 'public',
                    'custom_fields' => ['key1' => 'value1'],
                    'is_rce_favorite' => false,
                    'is_top_nav_favorite' => false,
                    'course_navigation' => [
                        'enabled' => true,
                        'text' => 'Test Tool',
                        'visibility' => 'public'
                    ],
                    'editor_button' => [
                        'enabled' => true,
                        'icon_url' => 'https://example.com/icon.png',
                        'selection_width' => 500,
                        'selection_height' => 400
                    ],
                    'selection_width' => 800,
                    'selection_height' => 600,
                    'icon_url' => 'https://example.com/icon.png',
                    'not_selectable' => false,
                    'workflow_state' => 'active',
                    'created_at' => '2024-01-01T12:00:00Z',
                    'updated_at' => '2024-01-02T12:00:00Z',
                    'deployment_id' => 'deployment123',
                    'unified_tool_id' => 'unified123'
                ]
            ]
        ];
    }

    /**
     * @dataProvider externalToolDataProvider
     */
    public function testConstructor(array $data): void
    {
        $tool = new ExternalTool($data);
        
        $this->assertEquals($data['id'], $tool->getId());
        $this->assertEquals($data['name'], $tool->getName());
        $this->assertEquals($data['description'], $tool->getDescription());
        $this->assertEquals($data['url'], $tool->getUrl());
        $this->assertEquals($data['consumer_key'], $tool->getConsumerKey());
        $this->assertEquals($data['privacy_level'], $tool->getPrivacyLevel());
        $this->assertEquals($data['custom_fields'], $tool->getCustomFields());
        $this->assertEquals($data['course_navigation'], $tool->getCourseNavigation());
        $this->assertEquals($data['editor_button'], $tool->getEditorButton());
    }

    public function testFind(): void
    {
        $toolData = [
            'id' => 1,
            'name' => 'Test Tool',
            'consumer_key' => 'test_key',
            'privacy_level' => 'public'
        ];
        
        $this->mockStream->method('getContents')->willReturn(json_encode($toolData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('accounts/1/external_tools/1')
            ->willReturn($this->mockResponse);
        
        $tool = ExternalTool::find(1);
        
        $this->assertInstanceOf(ExternalTool::class, $tool);
        $this->assertEquals(1, $tool->getId());
        $this->assertEquals('Test Tool', $tool->getName());
    }

    public function testFetchAll(): void
    {
        $toolsData = [
            ['id' => 1, 'name' => 'Tool 1', 'privacy_level' => 'public'],
            ['id' => 2, 'name' => 'Tool 2', 'privacy_level' => 'anonymous']
        ];
        
        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('fetchAllPages')
            ->willReturn($toolsData);

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('accounts/1/external_tools', ['query' => []])
            ->willReturn($mockPaginatedResponse);
        
        $tools = ExternalTool::fetchAll();
        
        $this->assertIsArray($tools);
        $this->assertCount(2, $tools);
        $this->assertInstanceOf(ExternalTool::class, $tools[0]);
        $this->assertEquals('Tool 1', $tools[0]->getName());
    }

    public function testFetchAllWithParams(): void
    {
        $params = ['include_parents' => true, 'placement' => 'editor_button'];
        $toolsData = [['id' => 1, 'name' => 'Tool 1', 'privacy_level' => 'public']];
        
        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('fetchAllPages')
            ->willReturn($toolsData);

        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('accounts/1/external_tools', ['query' => $params])
            ->willReturn($mockPaginatedResponse);
        
        $tools = ExternalTool::fetchAll($params);
        
        $this->assertIsArray($tools);
        $this->assertCount(1, $tools);
    }

    public function testFetchAllPaginated(): void
    {
        $paginatedResponse = $this->createMock(PaginatedResponse::class);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('getPaginated')
            ->with('accounts/1/external_tools', ['query' => []])
            ->willReturn($paginatedResponse);
        
        $result = ExternalTool::fetchAllPaginated();
        
        $this->assertInstanceOf(PaginatedResponse::class, $result);
    }

    public function testCreateWithArray(): void
    {
        $toolData = [
            'name' => 'New Tool',
            'consumer_key' => 'new_key',
            'shared_secret' => 'new_secret',
            'url' => 'https://example.com/lti/launch',
            'privacy_level' => 'public'
        ];
        
        $responseData = array_merge($toolData, ['id' => 1]);
        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->with(
                'accounts/1/external_tools',
                $this->callback(function ($options) {
                    return isset($options['multipart']) && is_array($options['multipart']);
                })
            )
            ->willReturn($this->mockResponse);
        
        $tool = ExternalTool::create($toolData);
        
        $this->assertInstanceOf(ExternalTool::class, $tool);
        $this->assertEquals(1, $tool->getId());
        $this->assertEquals('New Tool', $tool->getName());
    }

    public function testCreateWithDTO(): void
    {
        $dto = new CreateExternalToolDTO([
            'name' => 'New Tool',
            'consumer_key' => 'new_key',
            'shared_secret' => 'new_secret',
            'url' => 'https://example.com/lti/launch',
            'privacy_level' => 'public'
        ]);
        
        $responseData = [
            'id' => 1,
            'name' => 'New Tool',
            'consumer_key' => 'new_key',
            'privacy_level' => 'public'
        ];
        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->willReturn($this->mockResponse);
        
        $tool = ExternalTool::create($dto);
        
        $this->assertInstanceOf(ExternalTool::class, $tool);
        $this->assertEquals(1, $tool->getId());
    }

    public function testUpdateWithArray(): void
    {
        $updateData = ['name' => 'Updated Tool'];
        $responseData = [
            'id' => 1,
            'name' => 'Updated Tool',
            'consumer_key' => 'test_key',
            'privacy_level' => 'public'
        ];
        
        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with(
                'accounts/1/external_tools/1',
                $this->callback(function ($options) {
                    return isset($options['multipart']) && is_array($options['multipart']);
                })
            )
            ->willReturn($this->mockResponse);
        
        $tool = ExternalTool::update(1, $updateData);
        
        $this->assertInstanceOf(ExternalTool::class, $tool);
        $this->assertEquals('Updated Tool', $tool->getName());
    }

    public function testUpdateWithDTO(): void
    {
        $dto = new UpdateExternalToolDTO(['name' => 'Updated Tool']);
        $responseData = [
            'id' => 1,
            'name' => 'Updated Tool',
            'consumer_key' => 'test_key',
            'privacy_level' => 'public'
        ];
        
        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->willReturn($this->mockResponse);
        
        $tool = ExternalTool::update(1, $dto);
        
        $this->assertInstanceOf(ExternalTool::class, $tool);
    }

    public function testGenerateSessionlessLaunch(): void
    {
        $params = ['id' => 1];
        $launchData = [
            'id' => 1,
            'name' => 'Test Tool',
            'url' => 'https://canvas.example.com/api/v1/external_tools/sessionless_launch?token=abc123'
        ];
        
        $this->mockStream->method('getContents')->willReturn(json_encode($launchData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('accounts/1/external_tools/sessionless_launch', ['query' => $params])
            ->willReturn($this->mockResponse);
        
        $result = ExternalTool::generateSessionlessLaunch($params);
        
        $this->assertIsArray($result);
        $this->assertEquals($launchData, $result);
    }

    public function testSaveNewTool(): void
    {
        $tool = new ExternalTool([
            'name' => 'New Tool',
            'consumer_key' => 'new_key',
            'shared_secret' => 'new_secret',
            'url' => 'https://example.com/lti/launch',
            'privacy_level' => 'public'
        ]);
        
        $responseData = [
            'id' => 1,
            'name' => 'New Tool',
            'consumer_key' => 'new_key',
            'privacy_level' => 'public'
        ];
        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->willReturn($this->mockResponse);
        
        $result = $tool->save();
        
        $this->assertInstanceOf(ExternalTool::class, $result);
        $this->assertEquals(1, $tool->getId());
    }

    public function testSaveExistingTool(): void
    {
        $tool = new ExternalTool([
            'id' => 1,
            'name' => 'Updated Tool',
            'consumer_key' => 'test_key',
            'privacy_level' => 'public',
            'context_type' => 'account',
            'context_id' => 1
        ]);
        
        $responseData = [
            'id' => 1,
            'name' => 'Updated Tool',
            'consumer_key' => 'test_key',
            'privacy_level' => 'public'
        ];
        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('put')
            ->with('accounts/1/external_tools/1', $this->anything())
            ->willReturn($this->mockResponse);
        
        $result = $tool->save();
        
        $this->assertInstanceOf(ExternalTool::class, $result);
    }

    public function testSaveThrowsExceptionForMissingName(): void
    {
        $tool = new ExternalTool();
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('External tool name is required');
        
        $tool->save();
    }

    public function testSaveThrowsExceptionForMissingConsumerKey(): void
    {
        $tool = new ExternalTool(['name' => 'Test Tool']);
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Consumer key is required');
        
        $tool->save();
    }

    public function testSaveThrowsExceptionForInvalidPrivacyLevel(): void
    {
        $tool = new ExternalTool([
            'name' => 'Test Tool',
            'consumer_key' => 'test_key',
            'shared_secret' => 'test_secret',
            'privacy_level' => 'invalid',
            'url' => 'https://example.com'
        ]);
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Invalid privacy level');
        
        $tool->save();
    }

    public function testDelete(): void
    {
        $tool = new ExternalTool([
            'id' => 1,
            'context_type' => 'account',
            'context_id' => 1
        ]);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('delete')
            ->with('accounts/1/external_tools/1');
        
        $result = $tool->delete();
        
        $this->assertInstanceOf(ExternalTool::class, $result);
    }

    public function testDeleteThrowsExceptionWithoutId(): void
    {
        $tool = new ExternalTool();
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('External tool ID is required for deletion');
        
        $tool->delete();
    }

    public function testGetLaunchUrl(): void
    {
        $tool = new ExternalTool([
            'id' => 1,
            'context_type' => 'account', 
            'context_id' => 1
        ]);
        
        $launchData = [
            'id' => 1,
            'name' => 'Test Tool',
            'url' => 'https://canvas.example.com/api/v1/external_tools/sessionless_launch?token=abc123'
        ];
        
        $this->mockStream->method('getContents')->willReturn(json_encode($launchData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        
        $this->httpClientMock
            ->expects($this->once())
            ->method('get')
            ->with('accounts/1/external_tools/sessionless_launch', ['query' => ['id' => 1]])
            ->willReturn($this->mockResponse);
        
        $url = $tool->getLaunchUrl();
        
        $this->assertEquals($launchData['url'], $url);
    }

    public function testGetLaunchUrlThrowsExceptionWithoutId(): void
    {
        $tool = new ExternalTool();
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('External tool ID is required to generate launch URL');
        
        $tool->getLaunchUrl();
    }

    public function testValidateConfiguration(): void
    {
        $validTool = new ExternalTool([
            'name' => 'Test Tool',
            'consumer_key' => 'test_key',
            'privacy_level' => 'public',
            'url' => 'https://example.com'
        ]);
        
        $this->assertTrue($validTool->validateConfiguration());
        
        $invalidTool = new ExternalTool([
            'name' => 'Test Tool',
            'privacy_level' => 'invalid'
        ]);
        
        $this->assertFalse($invalidTool->validateConfiguration());
    }

    public function testToArray(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Tool',
            'description' => 'A test tool',
            'url' => 'https://example.com',
            'consumer_key' => 'test_key',
            'privacy_level' => 'public'
        ];
        
        $tool = new ExternalTool($data);
        $array = $tool->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals($data['id'], $array['id']);
        $this->assertEquals($data['name'], $array['name']);
        $this->assertEquals($data['description'], $array['description']);
    }
    
    public function testToArrayExcludesSharedSecret(): void
    {
        $tool = new ExternalTool([
            'id' => 1,
            'name' => 'Test Tool',
            'consumer_key' => 'test_key',
            'privacy_level' => 'public'
        ]);
        
        // Set shared secret to verify it's not included in array output
        $tool->setSharedSecret('secret123');
        
        $array = $tool->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayNotHasKey('shared_secret', $array);
        $this->assertEquals('secret123', $tool->getSharedSecret()); // Verify it's still accessible via getter
    }

    public function testToDtoArray(): void
    {
        $tool = new ExternalTool([
            'id' => 1,
            'name' => 'Test Tool',
            'consumer_key' => 'test_key',
            'privacy_level' => 'public',
            'url' => 'https://example.com'
        ]);
        
        $dtoArray = $tool->toDtoArray();
        
        $this->assertIsArray($dtoArray);
        $this->assertArrayNotHasKey('id', $dtoArray);
        $this->assertEquals('Test Tool', $dtoArray['name']);
        $this->assertEquals('test_key', $dtoArray['consumer_key']);
    }

    public function testGettersAndSetters(): void
    {
        $tool = new ExternalTool();
        
        $tool->setName('Test Tool');
        $this->assertEquals('Test Tool', $tool->getName());
        
        $tool->setDescription('Test Description');
        $this->assertEquals('Test Description', $tool->getDescription());
        
        $tool->setUrl('https://example.com');
        $this->assertEquals('https://example.com', $tool->getUrl());
        
        $tool->setDomain('example.com');
        $this->assertEquals('example.com', $tool->getDomain());
        
        $tool->setConsumerKey('test_key');
        $this->assertEquals('test_key', $tool->getConsumerKey());
        
        $tool->setPrivacyLevel('public');
        $this->assertEquals('public', $tool->getPrivacyLevel());
        
        $customFields = ['key1' => 'value1'];
        $tool->setCustomFields($customFields);
        $this->assertEquals($customFields, $tool->getCustomFields());
        
        $tool->setIsRceFavorite(true);
        $this->assertTrue($tool->getIsRceFavorite());
        
        $courseNavigation = ['enabled' => true, 'text' => 'Test'];
        $tool->setCourseNavigation($courseNavigation);
        $this->assertEquals($courseNavigation, $tool->getCourseNavigation());
        
        $tool->setSelectionWidth(800);
        $this->assertEquals(800, $tool->getSelectionWidth());
        
        $tool->setSelectionHeight(600);
        $this->assertEquals(600, $tool->getSelectionHeight());
        
        $tool->setIconUrl('https://example.com/icon.png');
        $this->assertEquals('https://example.com/icon.png', $tool->getIconUrl());
        
        $tool->setNotSelectable(true);
        $this->assertTrue($tool->getNotSelectable());
        
        $tool->setWorkflowState('active');
        $this->assertEquals('active', $tool->getWorkflowState());
        
        $tool->setDeploymentId('deploy123');
        $this->assertEquals('deploy123', $tool->getDeploymentId());
        
        $tool->setUnifiedToolId('unified123');
        $this->assertEquals('unified123', $tool->getUnifiedToolId());
    }

    public static function validPrivacyLevelsProvider(): array
    {
        return [
            ['anonymous'],
            ['name_only'],
            ['email_only'],
            ['public']
        ];
    }

    /**
     * @dataProvider validPrivacyLevelsProvider
     */
    public function testValidPrivacyLevels(string $privacyLevel): void
    {
        $tool = new ExternalTool([
            'name' => 'Test Tool',
            'consumer_key' => 'test_key',
            'privacy_level' => $privacyLevel,
            'url' => 'https://example.com'
        ]);
        
        $this->assertTrue($tool->validateConfiguration());
    }

    public static function invalidUrlProvider(): array
    {
        return [
            'javascript scheme' => ['javascript:alert("xss")'],
            'data scheme' => ['data:text/html,<script>alert("xss")</script>'],
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://example.com/file'],
            'no scheme' => ['example.com/path'],
            'invalid url' => ['not-a-url'],
            'http in production' => ['http://example.com'],
        ];
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testInvalidUrlValidation(string $invalidUrl): void
    {
        $tool = new ExternalTool([
            'name' => 'Test Tool',
            'consumer_key' => 'test_key',
            'privacy_level' => 'public',
            'url' => $invalidUrl
        ]);

        $this->assertFalse($tool->validateConfiguration());
    }

    public function testSaveThrowsExceptionForInvalidUrl(): void
    {
        $tool = new ExternalTool([
            'name' => 'Test Tool',
            'consumer_key' => 'test_key',
            'shared_secret' => 'test_secret',
            'privacy_level' => 'public',
            'url' => 'javascript:alert("xss")'
        ]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Invalid or insecure URL');

        $tool->save();
    }

    public function testSaveThrowsExceptionForInvalidIconUrl(): void
    {
        $tool = new ExternalTool([
            'name' => 'Test Tool',
            'consumer_key' => 'test_key',
            'shared_secret' => 'test_secret',
            'privacy_level' => 'public',
            'url' => 'https://example.com',
            'icon_url' => 'data:image/png;base64,malicious'
        ]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Invalid or insecure icon URL');

        $tool->save();
    }

    public static function validUrlProvider(): array
    {
        return [
            'https url' => ['https://example.com/lti/launch'],
            'https with port' => ['https://example.com:8080/lti'],
            'https with path and query' => ['https://tool.example.com/lti/launch?param=value'],
        ];
    }

    /**
     * @dataProvider validUrlProvider
     */
    public function testValidUrlValidation(string $validUrl): void
    {
        $tool = new ExternalTool([
            'name' => 'Test Tool',
            'consumer_key' => 'test_key',
            'privacy_level' => 'public',
            'url' => $validUrl
        ]);

        $this->assertTrue($tool->validateConfiguration());
    }
}