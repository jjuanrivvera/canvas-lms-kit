<?php

declare(strict_types=1);

namespace Tests\Api\ExternalTools;

use CanvasLMS\Api\ExternalTools\ExternalTool;
use CanvasLMS\Config;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ExternalToolAccountContextTest extends TestCase
{
    private HttpClientInterface&MockObject $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = $this->createMock(HttpClientInterface::class);
        ExternalTool::setApiClient($this->mockClient);
        Config::setAccountId(1);
    }

    public function testGetUsesAccountContext(): void
    {
        $toolsData = [
            ['id' => 1, 'name' => 'Tool 1', 'consumer_key' => 'key1'],
            ['id' => 2, 'name' => 'Tool 2', 'consumer_key' => 'key2'],
        ];

        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('getContents')->willReturn(json_encode($toolsData));

        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('accounts/1/external_tools', ['query' => []])
            ->willReturn($mockResponse);

        $tools = ExternalTool::get();

        $this->assertCount(2, $tools);
        $this->assertInstanceOf(ExternalTool::class, $tools[0]);
        $this->assertEquals('Tool 1', $tools[0]->name);
        $this->assertEquals('account', $tools[0]->getContextType());
        $this->assertEquals(1, $tools[0]->getContextId());
    }

    public function testFetchByContextForCourse(): void
    {
        $mockPaginatedResponse = $this->createMock(\CanvasLMS\Pagination\PaginatedResponse::class);
        $mockPaginatedResponse->expects($this->once())
            ->method('getJsonData')
            ->willReturn([
                ['id' => 3, 'name' => 'Course Tool', 'consumer_key' => 'key3'],
            ]);
        $mockPaginatedResponse->expects($this->once())
            ->method('getNext')
            ->willReturn(null); // No more pages

        $this->mockClient->expects($this->once())
            ->method('getPaginated')
            ->with('courses/123/external_tools', ['query' => []])
            ->willReturn($mockPaginatedResponse);

        $tools = ExternalTool::fetchByContext('courses', 123);

        $this->assertCount(1, $tools);
        $this->assertEquals('Course Tool', $tools[0]->name);
        $this->assertEquals('course', $tools[0]->getContextType());
        $this->assertEquals(123, $tools[0]->getContextId());
    }

    public function testFindByContextForAccount(): void
    {
        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('getContents')->willReturn(json_encode([
            'id' => 10,
            'name' => 'Account Tool',
            'consumer_key' => 'key10',
        ]));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockBody);

        $this->mockClient->expects($this->once())
            ->method('get')
            ->with('accounts/1/external_tools/10')
            ->willReturn($mockResponse);

        $tool = ExternalTool::findByContext('accounts', 1, 10);

        $this->assertInstanceOf(ExternalTool::class, $tool);
        $this->assertEquals(10, $tool->id);
        $this->assertEquals('Account Tool', $tool->name);
        $this->assertEquals('account', $tool->getContextType());
        $this->assertEquals(1, $tool->getContextId());
    }

    public function testCreateInContextForCourse(): void
    {
        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('getContents')->willReturn(json_encode([
            'id' => 20,
            'name' => 'New Course Tool',
            'consumer_key' => 'newkey',
            'shared_secret' => 'secret',
        ]));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockBody);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                'courses/456/external_tools',
                $this->callback(function ($data) {
                    $this->assertArrayHasKey('multipart', $data);
                    $multipart = $data['multipart'];

                    // Check that all required fields are present
                    $fields = [];
                    foreach ($multipart as $field) {
                        $fields[$field['name']] = $field['contents'];
                    }

                    $this->assertEquals('New Course Tool', $fields['external_tool[name]']);
                    $this->assertEquals('newkey', $fields['external_tool[consumer_key]']);
                    $this->assertEquals('secret', $fields['external_tool[shared_secret]']);
                    $this->assertEquals('public', $fields['external_tool[privacy_level]']);

                    return true;
                })
            )
            ->willReturn($mockResponse);

        $tool = ExternalTool::createInContext('courses', 456, [
            'name' => 'New Course Tool',
            'consumerKey' => 'newkey',
            'sharedSecret' => 'secret',
            'privacyLevel' => 'public',
        ]);

        $this->assertEquals(20, $tool->id);
        $this->assertEquals('New Course Tool', $tool->name);
        $this->assertEquals('course', $tool->getContextType());
        $this->assertEquals(456, $tool->getContextId());
    }

    public function testCreateUsesAccountContextByDefault(): void
    {
        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('getContents')->willReturn(json_encode([
            'id' => 30,
            'name' => 'Account Tool',
            'consumer_key' => 'accountkey',
        ]));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockBody);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                'accounts/1/external_tools',
                $this->callback(function ($data) {
                    $this->assertArrayHasKey('multipart', $data);
                    $multipart = $data['multipart'];

                    // Check that all required fields are present
                    $fields = [];
                    foreach ($multipart as $field) {
                        $fields[$field['name']] = $field['contents'];
                    }

                    $this->assertEquals('Account Tool', $fields['external_tool[name]']);
                    $this->assertEquals('accountkey', $fields['external_tool[consumer_key]']);
                    $this->assertEquals('secret', $fields['external_tool[shared_secret]']);
                    $this->assertEquals('public', $fields['external_tool[privacy_level]']);

                    return true;
                })
            )
            ->willReturn($mockResponse);

        $tool = ExternalTool::create([
            'name' => 'Account Tool',
            'consumerKey' => 'accountkey',
            'sharedSecret' => 'secret',
            'privacyLevel' => 'public',
        ]);

        $this->assertEquals(30, $tool->id);
        $this->assertEquals('account', $tool->getContextType());
        $this->assertEquals(1, $tool->getContextId());
    }

    public function testDeleteUsesStoredContext(): void
    {
        $tool = new ExternalTool([
            'id' => 40,
            'name' => 'Tool to Delete',
        ]);
        $tool->setContextType('course');
        $tool->setContextId(789);

        $mockResponse = $this->createMockResponse(['success' => true]);

        $this->mockClient->expects($this->once())
            ->method('delete')
            ->with('courses/789/external_tools/40', [])
            ->willReturn($mockResponse);

        $result = $tool->delete();
        $this->assertInstanceOf(ExternalTool::class, $result);
    }

    public function testDeleteThrowsExceptionWithoutContext(): void
    {
        $tool = new ExternalTool(['id' => 50]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Context information required for deletion');

        $tool->delete();
    }

    public function testSaveCreatesNewToolWithAccountContext(): void
    {
        $tool = new ExternalTool([
            'name' => 'New Tool',
            'consumerKey' => 'key',
            'sharedSecret' => 'secret',
            'privacyLevel' => 'public',
            'url' => 'https://example.com/tool',
        ]);

        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('getContents')->willReturn(json_encode([
            'id' => 60,
            'name' => 'New Tool',
            'consumer_key' => 'key',
        ]));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockBody);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with($this->stringStartsWith('accounts/1/external_tools'), $this->anything())
            ->willReturn($mockResponse);

        $result = $tool->save();

        $this->assertInstanceOf(ExternalTool::class, $result);
        $this->assertEquals(60, $tool->id);
    }

    public function testSaveUpdatesExistingToolWithStoredContext(): void
    {
        $tool = new ExternalTool([
            'id' => 70,
            'name' => 'Updated Tool',
        ]);
        $tool->setContextType('course');
        $tool->setContextId(999);

        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('getContents')->willReturn(json_encode([
            'id' => 70,
            'name' => 'Updated Tool',
        ]));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockBody);

        $this->mockClient->expects($this->once())
            ->method('put')
            ->with('courses/999/external_tools/70', $this->anything())
            ->willReturn($mockResponse);

        $result = $tool->save();

        $this->assertInstanceOf(ExternalTool::class, $result);
        $this->assertEquals(70, $tool->id);
    }

    private function createMockResponse($data): ResponseInterface&MockObject
    {
        $mockBody = $this->createMock(StreamInterface::class);
        $mockBody->method('__toString')->willReturn(json_encode($data));
        $mockBody->method('getContents')->willReturn(json_encode($data));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockBody);

        return $mockResponse;
    }
}
