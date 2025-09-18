<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Traits;

use CanvasLMS\Config;
use CanvasLMS\Traits\ActivityLoggingTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ActivityLoggingTraitTest extends TestCase
{
    use ActivityLoggingTrait;

    private $mockLogger;

    private $originalContext;

    protected function setUp(): void
    {
        parent::setUp();

        // Save original context
        $this->originalContext = Config::getContext();

        // Create mock logger
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        Config::setLogger($this->mockLogger);
    }

    protected function tearDown(): void
    {
        // Reset configuration
        Config::setContext($this->originalContext);
        Config::resetContext('default');

        parent::tearDown();
    }

    public function testLogActivity(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('Canvas API Activity: create'),
                $this->callback(function ($context) {
                    return isset($context['timestamp']) &&
                           isset($context['class']) &&
                           isset($context['action']) &&
                           isset($context['context']) &&
                           $context['action'] === 'create' &&
                           $context['test_key'] === 'test_value';
                })
            );

        $this->logActivity('create', ['test_key' => 'test_value']);
    }

    public function testLogPerformanceUnderOneSecond(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Performance:'),
                $this->callback(function ($context) {
                    return isset($context['duration_ms']) &&
                           isset($context['duration_s']) &&
                           $context['duration_ms'] === 500.0 &&
                           $context['operation'] === 'fast_operation';
                })
            );

        $this->logPerformance('fast_operation', 0.5);
    }

    public function testLogPerformanceBetweenOneAndFiveSeconds(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Performance:'),
                $this->callback(function ($context) {
                    return isset($context['duration_s']) &&
                           $context['duration_s'] === 2.5 &&
                           $context['operation'] === 'slow_operation';
                })
            );

        $this->logPerformance('slow_operation', 2.5);
    }

    public function testLogPerformanceOverFiveSeconds(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Performance:'),
                $this->callback(function ($context) {
                    return isset($context['duration_s']) &&
                           $context['duration_s'] === 6.0 &&
                           $context['operation'] === 'very_slow_operation';
                })
            );

        $this->logPerformance('very_slow_operation', 6.0);
    }

    public function testLogError(): void
    {
        $exception = new \Exception('Test error', 500);

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('API Error in test_operation:'),
                $this->callback(function ($context) use ($exception) {
                    return $context['operation'] === 'test_operation' &&
                           $context['error_class'] === get_class($exception) &&
                           $context['error_message'] === 'Test error' &&
                           $context['error_code'] === 500 &&
                           isset($context['stack_trace']);
                })
            );

        $this->logError('test_operation', $exception);
    }

    public function testLogSuccess(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('API Success: upload completed successfully'),
                $this->callback(function ($context) {
                    return $context['operation'] === 'upload' &&
                           $context['status'] === 'success' &&
                           $context['file_id'] === 123;
                })
            );

        $this->logSuccess('upload', ['file_id' => 123]);
    }

    public function testLogPagination(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('Pagination:'),
                $this->callback(function ($context) {
                    return $context['page'] === 2 &&
                           $context['per_page'] === 50 &&
                           $context['operation'] === 'fetch_courses';
                })
            );

        $this->logPagination('fetch_courses', 2, 50);
    }

    public function testLogOAuthOperation(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('OAuth Operation: refresh'),
                $this->callback(function ($context) {
                    return $context['operation'] === 'oauth_refresh' &&
                           $context['auth_mode'] === Config::getAuthMode() &&
                           $context['token'] === '***5678' && // Sanitized
                           $context['user_id'] === 999;
                })
            );

        $this->logOAuthOperation('refresh', [
            'token' => 'abc12345678',
            'user_id' => 999,
        ]);
    }

    public function testLogFileUpload(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo("File Upload: Step 'initiate'"),
                $this->callback(function ($context) {
                    return $context['operation'] === 'file_upload' &&
                           $context['step'] === 'initiate' &&
                           $context['file_name'] === 'test.pdf';
                })
            );

        $this->logFileUpload('initiate', ['file_name' => 'test.pdf']);
    }

    public function testSanitizeOAuthContext(): void
    {
        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                $this->anything(),
                $this->callback(function ($context) {
                    // Check that sensitive fields are sanitized
                    return $context['access_token'] === '***9012' &&
                           $context['refresh_token'] === '***5678' &&
                           $context['client_secret'] === '***' &&
                           $context['password'] === '***' &&
                           $context['api_key'] === '***defg' &&
                           // Non-sensitive fields should remain
                           $context['user_id'] === 123 &&
                           $context['scope'] === 'read write';
                })
            );

        $this->logOAuthOperation('authenticate', [
            'access_token' => 'token789012',
            'refresh_token' => 'refresh345678',
            'client_secret' => 'sec',
            'password' => 'pwd',
            'api_key' => 'abcdefg',
            'user_id' => 123,
            'scope' => 'read write',
        ]);
    }

    public function testTimerMethods(): void
    {
        $startTime = $this->startTimer();
        $this->assertIsFloat($startTime);

        // Simulate some work
        usleep(100000); // 100ms

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Performance:'),
                $this->callback(function ($context) {
                    // Should be at least 100ms
                    return $context['duration_ms'] >= 100.0 &&
                           $context['operation'] === 'timed_operation';
                })
            );

        $this->endTimer($startTime, 'timed_operation');
    }
}
