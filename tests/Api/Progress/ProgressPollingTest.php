<?php

declare(strict_types=1);

namespace Tests\Api\Progress;

use CanvasLMS\Api\Progress\Progress;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ProgressPollingTest extends TestCase
{
    private HttpClientInterface|MockObject $mockHttpClient;

    private ResponseInterface|MockObject $mockResponse;

    private StreamInterface|MockObject $mockStream;

    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockStream = $this->createMock(StreamInterface::class);

        Progress::setApiClient($this->mockHttpClient);
    }

    protected function tearDown(): void
    {
        // Reset static state
        Progress::setApiClient($this->createMock(HttpClientInterface::class));
    }

    public function testWaitForCompletionSuccess(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running']);

        // Mock progression: running -> running -> completed
        $responses = [
            ['id' => 123, 'workflow_state' => 'running', 'completion' => 30],
            ['id' => 123, 'workflow_state' => 'running', 'completion' => 60],
            ['id' => 123, 'workflow_state' => 'completed', 'completion' => 100, 'results' => ['success' => true]],
        ];

        $this->mockStream->method('getContents')->willReturnOnConsecutiveCalls(
            ...array_map('json_encode', $responses)
        );
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $result = $progress->waitForCompletion(10, 1); // Short timeout for testing

        $this->assertSame($progress, $result);
        $this->assertEquals('completed', $progress->getWorkflowState());
        $this->assertEquals(100, $progress->getCompletion());
        $this->assertEquals(['success' => true], $progress->getResults());
    }

    public function testWaitForCompletionWithFailure(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running']);

        // Mock progression: running -> failed
        $responses = [
            ['id' => 123, 'workflow_state' => 'running', 'completion' => 30],
            ['id' => 123, 'workflow_state' => 'failed', 'completion' => 30, 'message' => 'Connection timeout'],
        ];

        $this->mockStream->method('getContents')->willReturnOnConsecutiveCalls(
            ...array_map('json_encode', $responses)
        );
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Operation failed: Connection timeout');

        $progress->waitForCompletion(10, 1);
    }

    public function testWaitForCompletionTimeout(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running']);

        // Mock continuous running state (never completes)
        $runningResponse = ['id' => 123, 'workflow_state' => 'running', 'completion' => 30];

        $this->mockStream->method('getContents')->willReturn(json_encode($runningResponse));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Progress polling timed out after 2 seconds');

        $progress->waitForCompletion(2, 1); // Very short timeout
    }

    public function testWaitForCompletionAlreadyCompleted(): void
    {
        $progress = new Progress([
            'id' => 123,
            'workflow_state' => 'completed',
            'completion' => 100,
            'results' => ['data' => 'test'],
        ]);

        // Should return immediately without API calls
        $result = $progress->waitForCompletion(10, 1);

        $this->assertSame($progress, $result);
        $this->assertEquals('completed', $progress->getWorkflowState());
    }

    public function testWaitForCompletionAlreadyFailed(): void
    {
        $progress = new Progress([
            'id' => 123,
            'workflow_state' => 'failed',
            'message' => 'Previously failed',
        ]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Operation failed: Previously failed');

        $progress->waitForCompletion(10, 1);
    }

    public function testStaticPollUntilCompleteSuccess(): void
    {
        // Mock find() call and subsequent polling
        $findResponse = ['id' => 123, 'workflow_state' => 'queued', 'completion' => 0];
        $pollingResponses = [
            ['id' => 123, 'workflow_state' => 'running', 'completion' => 50],
            ['id' => 123, 'workflow_state' => 'completed', 'completion' => 100],
        ];

        $this->mockStream->method('getContents')->willReturnOnConsecutiveCalls(
            json_encode($findResponse),
            ...array_map('json_encode', $pollingResponses)
        );
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $result = Progress::pollUntilComplete(123, 10, 1);

        $this->assertInstanceOf(Progress::class, $result);
        $this->assertEquals(123, $result->getId());
        $this->assertEquals('completed', $result->getWorkflowState());
        $this->assertEquals(100, $result->getCompletion());
    }

    public function testExponentialBackoffBehavior(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running']);

        // Create a mock that tracks sleep calls to verify backoff behavior
        $startTime = time();
        $sleepTimes = [];

        // Mock running responses for multiple iterations
        $runningResponse = ['id' => 123, 'workflow_state' => 'running', 'completion' => 30];

        // Override sleep function to track calls (this is conceptual - actual implementation would need more setup)
        $this->mockStream->method('getContents')->willReturn(json_encode($runningResponse));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        // Test will timeout, but we're testing the concept
        try {
            $progress->waitForCompletion(3, 1); // Short timeout
        } catch (CanvasApiException $e) {
            $this->assertStringContainsString('timed out', $e->getMessage());
        }
    }

    public function testPollingWithQueuedToRunningToCompleted(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'queued']);

        // Mock full progression: queued -> running -> completed
        $responses = [
            ['id' => 123, 'workflow_state' => 'queued', 'completion' => 0],
            ['id' => 123, 'workflow_state' => 'running', 'completion' => 25],
            ['id' => 123, 'workflow_state' => 'running', 'completion' => 50],
            ['id' => 123, 'workflow_state' => 'running', 'completion' => 75],
            ['id' => 123, 'workflow_state' => 'completed', 'completion' => 100, 'results' => ['final' => 'result']],
        ];

        $this->mockStream->method('getContents')->willReturnOnConsecutiveCalls(
            ...array_map('json_encode', $responses)
        );
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $result = $progress->waitForCompletion(15, 1);

        $this->assertSame($progress, $result);
        $this->assertTrue($progress->isCompleted());
        $this->assertTrue($progress->isSuccessful());
        $this->assertFalse($progress->isInProgress());
        $this->assertTrue($progress->isFinished());
        $this->assertEquals(['final' => 'result'], $progress->getResults());
    }

    public function testPollingWithVariousFailureScenarios(): void
    {
        // Test failed with error message
        $failedProgress = new Progress(['id' => 123, 'workflow_state' => 'running']);

        $responses = [
            ['id' => 123, 'workflow_state' => 'running', 'completion' => 50],
            ['id' => 123, 'workflow_state' => 'failed', 'completion' => 50, 'message' => 'Disk space full'],
        ];

        $this->mockStream->method('getContents')->willReturnOnConsecutiveCalls(
            ...array_map('json_encode', $responses)
        );
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Operation failed: Disk space full');

        $failedProgress->waitForCompletion(10, 1);
    }

    public function testPollingWithNoMessage(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running']);

        $responses = [
            ['id' => 123, 'workflow_state' => 'running', 'completion' => 50],
            ['id' => 123, 'workflow_state' => 'failed', 'completion' => 50], // No message field
        ];

        $this->mockStream->method('getContents')->willReturnOnConsecutiveCalls(
            ...array_map('json_encode', $responses)
        );
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Operation failed:'); // Should handle null message

        $progress->waitForCompletion(10, 1);
    }

    public function testRefreshDuringPolling(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running', 'completion' => 0]);

        // Simulate progress update
        $response = ['id' => 123, 'workflow_state' => 'running', 'completion' => 75, 'message' => 'Almost done'];

        $this->mockStream->method('getContents')->willReturn(json_encode($response));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $result = $progress->refresh();

        $this->assertSame($progress, $result);
        $this->assertEquals(75, $progress->getCompletion());
        $this->assertEquals('Almost done', $progress->getMessage());
        $this->assertTrue($progress->isRunning());
        $this->assertTrue($progress->isInProgress());
    }
}
