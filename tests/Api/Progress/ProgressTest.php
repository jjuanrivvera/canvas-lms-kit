<?php

declare(strict_types=1);

namespace Tests\Api\Progress;

use DateTime;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use CanvasLMS\Api\Progress\Progress;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Exceptions\CanvasApiException;

class ProgressTest extends TestCase
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

    public function testConstructorPopulatesProperties(): void
    {
        $data = [
            'id' => 123,
            'context_id' => 456,
            'context_type' => 'Course',
            'user_id' => 789,
            'tag' => 'course_batch_update',
            'completion' => 75,
            'workflow_state' => 'running',
            'created_at' => '2023-01-15T15:00:00Z',
            'updated_at' => '2023-01-15T15:03:00Z',
            'message' => 'Processing course updates...',
            'results' => null,
            'url' => 'https://canvas.example.com/api/v1/progress/123'
        ];

        $progress = new Progress($data);

        $this->assertEquals(123, $progress->getId());
        $this->assertEquals(456, $progress->getContextId());
        $this->assertEquals('Course', $progress->getContextType());
        $this->assertEquals(789, $progress->getUserId());
        $this->assertEquals('course_batch_update', $progress->getTag());
        $this->assertEquals(75, $progress->getCompletion());
        $this->assertEquals('running', $progress->getWorkflowState());
        $this->assertInstanceOf(DateTime::class, $progress->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $progress->getUpdatedAt());
        $this->assertEquals('Processing course updates...', $progress->getMessage());
        $this->assertNull($progress->getResults());
        $this->assertEquals('https://canvas.example.com/api/v1/progress/123', $progress->getUrl());
    }

    public function testFindSuccess(): void
    {
        $responseData = [
            'id' => 123,
            'context_id' => 456,
            'context_type' => 'Course',
            'workflow_state' => 'completed',
            'completion' => 100
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $progress = Progress::find(123);

        $this->assertInstanceOf(Progress::class, $progress);
        $this->assertEquals(123, $progress->getId());
        $this->assertEquals('completed', $progress->getWorkflowState());
        $this->assertEquals(100, $progress->getCompletion());
    }

    public function testFindInLtiContextSuccess(): void
    {
        $responseData = [
            'id' => 123,
            'context_id' => 456,
            'context_type' => 'Course',
            'workflow_state' => 'running',
            'completion' => 50
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/lti/courses/456/progress/123')->willReturn($this->mockResponse);

        $progress = Progress::findInLtiContext(456, 123);

        $this->assertInstanceOf(Progress::class, $progress);
        $this->assertEquals(123, $progress->getId());
        $this->assertEquals('running', $progress->getWorkflowState());
    }

    public function testWorkflowStateCheckers(): void
    {
        $queuedProgress = new Progress(['workflow_state' => Progress::STATE_QUEUED]);
        $this->assertTrue($queuedProgress->isQueued());
        $this->assertFalse($queuedProgress->isRunning());
        $this->assertFalse($queuedProgress->isCompleted());
        $this->assertFalse($queuedProgress->isFailed());
        $this->assertTrue($queuedProgress->isInProgress());
        $this->assertFalse($queuedProgress->isFinished());
        $this->assertFalse($queuedProgress->isSuccessful());

        $runningProgress = new Progress(['workflow_state' => Progress::STATE_RUNNING]);
        $this->assertFalse($runningProgress->isQueued());
        $this->assertTrue($runningProgress->isRunning());
        $this->assertFalse($runningProgress->isCompleted());
        $this->assertFalse($runningProgress->isFailed());
        $this->assertTrue($runningProgress->isInProgress());
        $this->assertFalse($runningProgress->isFinished());
        $this->assertFalse($runningProgress->isSuccessful());

        $completedProgress = new Progress(['workflow_state' => Progress::STATE_COMPLETED]);
        $this->assertFalse($completedProgress->isQueued());
        $this->assertFalse($completedProgress->isRunning());
        $this->assertTrue($completedProgress->isCompleted());
        $this->assertFalse($completedProgress->isFailed());
        $this->assertFalse($completedProgress->isInProgress());
        $this->assertTrue($completedProgress->isFinished());
        $this->assertTrue($completedProgress->isSuccessful());

        $failedProgress = new Progress(['workflow_state' => Progress::STATE_FAILED]);
        $this->assertFalse($failedProgress->isQueued());
        $this->assertFalse($failedProgress->isRunning());
        $this->assertFalse($failedProgress->isCompleted());
        $this->assertTrue($failedProgress->isFailed());
        $this->assertFalse($failedProgress->isInProgress());
        $this->assertTrue($failedProgress->isFinished());
        $this->assertFalse($failedProgress->isSuccessful());
    }

    public function testGetCompletionPercentage(): void
    {
        $progress = new Progress(['completion' => 75]);
        $this->assertEquals(75.0, $progress->getCompletionPercentage());

        $progressNoCompletion = new Progress([]);
        $this->assertEquals(0.0, $progressNoCompletion->getCompletionPercentage());
    }

    public function testGetStatusDescription(): void
    {
        $queuedProgress = new Progress([
            'workflow_state' => Progress::STATE_QUEUED,
            'message' => 'Waiting for resources'
        ]);
        $this->assertEquals('Waiting to start - Waiting for resources', $queuedProgress->getStatusDescription());

        $runningProgress = new Progress([
            'workflow_state' => Progress::STATE_RUNNING,
            'completion' => 45,
            'message' => 'Processing data'
        ]);
        $this->assertEquals('In progress (45%) - Processing data', $runningProgress->getStatusDescription());

        $completedProgress = new Progress(['workflow_state' => Progress::STATE_COMPLETED]);
        $this->assertEquals('Completed successfully', $completedProgress->getStatusDescription());

        $failedProgress = new Progress([
            'workflow_state' => Progress::STATE_FAILED,
            'message' => 'Connection timeout'
        ]);
        $this->assertEquals('Failed - Connection timeout', $failedProgress->getStatusDescription());
    }

    public function testCancelSuccess(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running']);

        $responseData = [
            'id' => 123,
            'workflow_state' => 'failed',
            'message' => 'Cancelled by user'
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient
            ->method('post')
            ->with('/progress/123/cancel', ['form_params' => ['message' => 'User requested']])
            ->willReturn($this->mockResponse);

        $result = $progress->cancel('User requested');

        $this->assertSame($progress, $result);
        $this->assertEquals('failed', $progress->getWorkflowState());
        $this->assertEquals('Cancelled by user', $progress->getMessage());
    }

    public function testCancelWithoutId(): void
    {
        $progress = new Progress([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot cancel progress: ID is not set');

        $progress->cancel();
    }

    public function testRefreshSuccess(): void
    {
        $progress = new Progress(['id' => 123, 'completion' => 50]);

        $responseData = [
            'id' => 123,
            'completion' => 75,
            'workflow_state' => 'running',
            'message' => 'Updated progress'
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $result = $progress->refresh();

        $this->assertSame($progress, $result);
        $this->assertEquals(75, $progress->getCompletion());
        $this->assertEquals('running', $progress->getWorkflowState());
        $this->assertEquals('Updated progress', $progress->getMessage());
    }

    public function testRefreshWithoutId(): void
    {
        $progress = new Progress([]);

        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Cannot refresh progress: ID is not set');

        $progress->refresh();
    }

    public function testPollUntilCompleteSuccess(): void
    {
        // Mock multiple API calls showing progress from running to completed
        $responses = [
            ['id' => 123, 'workflow_state' => 'running', 'completion' => 30],
            ['id' => 123, 'workflow_state' => 'running', 'completion' => 60],
            ['id' => 123, 'workflow_state' => 'completed', 'completion' => 100, 'results' => ['success' => true]]
        ];

        $this->mockStream->method('getContents')->willReturnOnConsecutiveCalls(
            ...array_map('json_encode', $responses)
        );
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);

        $this->mockHttpClient
            ->method('get')
            ->with('/progress/123')
            ->willReturn($this->mockResponse);

        $progress = Progress::pollUntilComplete(123, 10, 1); // Short timeout for testing

        $this->assertEquals(123, $progress->getId());
        $this->assertEquals('completed', $progress->getWorkflowState());
        $this->assertEquals(100, $progress->getCompletion());
        $this->assertEquals(['success' => true], $progress->getResults());
    }

    public function testFetchAllThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Progress API does not support listing all progress objects. Use find() with specific ID.');

        Progress::fetchAll();
    }

    public function testAllGettersAndSetters(): void
    {
        $progress = new Progress();
        $createdAt = new DateTime();
        $updatedAt = new DateTime();
        $results = ['test' => 'data'];

        $progress->setId(123);
        $progress->setContextId(456);
        $progress->setContextType('Course');
        $progress->setUserId(789);
        $progress->setTag('test_operation');
        $progress->setCompletion(75);
        $progress->setWorkflowState(Progress::STATE_RUNNING);
        $progress->setCreatedAt($createdAt);
        $progress->setUpdatedAt($updatedAt);
        $progress->setMessage('Test message');
        $progress->setResults($results);
        $progress->setUrl('https://example.com/progress/123');

        $this->assertEquals(123, $progress->getId());
        $this->assertEquals(456, $progress->getContextId());
        $this->assertEquals('Course', $progress->getContextType());
        $this->assertEquals(789, $progress->getUserId());
        $this->assertEquals('test_operation', $progress->getTag());
        $this->assertEquals(75, $progress->getCompletion());
        $this->assertEquals(Progress::STATE_RUNNING, $progress->getWorkflowState());
        $this->assertSame($createdAt, $progress->getCreatedAt());
        $this->assertSame($updatedAt, $progress->getUpdatedAt());
        $this->assertEquals('Test message', $progress->getMessage());
        $this->assertEquals($results, $progress->getResults());
        $this->assertEquals('https://example.com/progress/123', $progress->getUrl());
    }
}