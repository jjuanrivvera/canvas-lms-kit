<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Integration;

use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Config;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class MultiTenantConfigTest extends TestCase
{
    /**
     * @var string
     */
    private string $originalContext;

    protected function setUp(): void
    {
        parent::setUp();

        // Save original context
        $this->originalContext = Config::getContext();

        // Reset to default context for each test
        Config::setContext('default');
        Config::resetContext('default');
    }

    protected function tearDown(): void
    {
        // Clean up any test contexts
        foreach (Config::getAllContexts() as $context) {
            if ($context !== 'default') {
                Config::resetContext($context);
            }
        }

        // Restore original context
        Config::setContext($this->originalContext);

        parent::tearDown();
    }

    public function testMultipleTenantApiCalls(): void
    {
        // Set up two different Canvas instances
        Config::setContext('university1');
        Config::setApiKey('uni1-api-key');
        Config::setBaseUrl('https://university1.instructure.com');
        Config::setAccountId(100);

        Config::setContext('university2');
        Config::setApiKey('uni2-api-key');
        Config::setBaseUrl('https://university2.instructure.com');
        Config::setAccountId(200);

        // Mock HTTP client for testing
        $mockClient = $this->createMock(HttpClientInterface::class);

        // Create response mocks
        $response1 = $this->createMockResponse(['id' => 1, 'name' => 'Course from Uni 1']);
        $response2 = $this->createMockResponse(['id' => 2, 'name' => 'Course from Uni 2']);

        // Set up expectations for different contexts
        $mockClient->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function ($url) use ($response1, $response2) {
                // Check which context is active by inspecting the current config
                if (Config::getApiKey() === 'uni1-api-key') {
                    return $response1;
                } else {
                    return $response2;
                }
            });

        // Set the mock client
        Course::setApiClient($mockClient);

        // Make API calls with different contexts
        Config::setContext('university1');
        $course1 = Course::find(1);
        $this->assertEquals('Course from Uni 1', $course1->name);

        Config::setContext('university2');
        $course2 = Course::find(2);
        $this->assertEquals('Course from Uni 2', $course2->name);
    }

    public function testConcurrentContextUsage(): void
    {
        // Set up multiple contexts
        Config::setApiKey('context1-key', 'context1');
        Config::setBaseUrl('https://context1.canvas.com', 'context1');

        Config::setApiKey('context2-key', 'context2');
        Config::setBaseUrl('https://context2.canvas.com', 'context2');

        Config::setApiKey('context3-key', 'context3');
        Config::setBaseUrl('https://context3.canvas.com', 'context3');

        // Simulate rapid context switching
        $iterations = 100;
        for ($i = 0; $i < $iterations; $i++) {
            $context = 'context' . (($i % 3) + 1);
            Config::setContext($context);

            // Verify correct values are retrieved
            $expectedKey = $context . '-key';
            $expectedUrl = 'https://' . $context . '.canvas.com/';

            $this->assertEquals($expectedKey, Config::getApiKey());
            $this->assertEquals($expectedUrl, Config::getBaseUrl());
        }
    }

    public function testContextIsolationInTests(): void
    {
        // Test 1: Set up first test context
        Config::setContext('test1');
        Config::setApiKey('test1-key');
        Config::setBaseUrl('https://test1.canvas.com');

        // Test 2: Set up second test context
        Config::setContext('test2');
        Config::setApiKey('test2-key');
        Config::setBaseUrl('https://test2.canvas.com');

        // Verify contexts are isolated
        $this->assertEquals('test1-key', Config::getApiKey('test1'));
        $this->assertEquals('test2-key', Config::getApiKey('test2'));

        // Clean up test1
        Config::resetContext('test1');

        // Verify test1 is cleared but test2 remains
        $this->assertNull(Config::getApiKey('test1'));
        $this->assertEquals('test2-key', Config::getApiKey('test2'));
    }

    public function testEnvironmentBasedMultiTenant(): void
    {
        // Clean environment first to avoid interference from other tests
        unset($_ENV['CANVAS_API_KEY'], $_ENV['CANVAS_BASE_URL'], $_ENV['CANVAS_ACCOUNT_ID'], $_ENV['CANVAS_API_VERSION'], $_ENV['CANVAS_TIMEOUT']);

        // Simulate different environments with different Canvas instances

        // Development environment
        $_ENV['CANVAS_API_KEY'] = 'dev-api-key';
        $_ENV['CANVAS_BASE_URL'] = 'https://dev.canvas.local';
        $_ENV['CANVAS_ACCOUNT_ID'] = '1001';

        Config::autoDetect('development');

        // Staging environment
        $_ENV['CANVAS_API_KEY'] = 'staging-api-key';
        $_ENV['CANVAS_BASE_URL'] = 'https://staging.canvas.example.com';
        $_ENV['CANVAS_ACCOUNT_ID'] = '2001';

        Config::autoDetect('staging');

        // Production environment
        $_ENV['CANVAS_API_KEY'] = 'prod-api-key';
        $_ENV['CANVAS_BASE_URL'] = 'https://canvas.example.com';
        $_ENV['CANVAS_ACCOUNT_ID'] = '3001';

        Config::autoDetect('production');

        // Verify all environments are configured correctly
        $this->assertEquals('dev-api-key', Config::getApiKey('development'));
        $this->assertEquals('staging-api-key', Config::getApiKey('staging'));
        $this->assertEquals('prod-api-key', Config::getApiKey('production'));

        $this->assertEquals(1001, Config::getAccountId('development'));
        $this->assertEquals(2001, Config::getAccountId('staging'));
        $this->assertEquals(3001, Config::getAccountId('production'));

        // Clean up environment
        unset($_ENV['CANVAS_API_KEY']);
        unset($_ENV['CANVAS_BASE_URL']);
        unset($_ENV['CANVAS_ACCOUNT_ID']);
    }

    /**
     * Helper method to create mock response
     *
     * @param array<string, mixed> $data
     *
     * @return ResponseInterface
     */
    private function createMockResponse(array $data): ResponseInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($data));
        $stream->method('__toString')->willReturn(json_encode($data));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->method('getHeader')->willReturn([]);

        return $response;
    }
}
