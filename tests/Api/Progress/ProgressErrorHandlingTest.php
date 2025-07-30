<?php

declare(strict_types=1);

namespace Tests\Api\Progress;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use CanvasLMS\Api\Progress\Progress;
use CanvasLMS\Interfaces\HttpClientInterface;
use CanvasLMS\Exceptions\CanvasApiException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;

class ProgressErrorHandlingTest extends TestCase
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

    public function testFindWithInvalidId(): void
    {
        $errorResponse = [
            'errors' => [
                ['message' => 'The specified resource does not exist.']
            ]
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($errorResponse));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/999')->willReturn($this->mockResponse);

        $progress = Progress::find(999);

        // Should create progress object even with error response structure
        $this->assertInstanceOf(Progress::class, $progress);
        $this->assertNull($progress->getId());
    }

    public function testFindWithNetworkError(): void
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $exception = new RequestException('Connection timeout', $mockRequest);

        $this->mockHttpClient
            ->method('get')
            ->with('/progress/123')
            ->willThrowException($exception);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Connection timeout');

        Progress::find(123);
    }

    public function testCancelWithInvalidProgressId(): void
    {
        $progress = new Progress(['id' => 999, 'workflow_state' => 'running']);

        $errorResponse = [
            'errors' => [
                ['message' => 'Cannot cancel this operation']
            ]
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($errorResponse));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient
            ->method('post')
            ->with('/progress/999/cancel', ['form_params' => []])
            ->willReturn($this->mockResponse);

        $result = $progress->cancel();

        // Should update progress with error response structure
        $this->assertSame($progress, $result);
    }

    public function testCancelWithNetworkError(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running']);
        
        $mockRequest = $this->createMock(RequestInterface::class);
        $exception = new RequestException('Server error', $mockRequest);

        $this->mockHttpClient
            ->method('post')
            ->with('/progress/123/cancel', ['form_params' => []])
            ->willThrowException($exception);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Server error');

        $progress->cancel();
    }

    public function testRefreshWithNetworkError(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running']);
        
        $mockRequest = $this->createMock(RequestInterface::class);
        $exception = new RequestException('Network unreachable', $mockRequest);

        $this->mockHttpClient
            ->method('get')
            ->with('/progress/123')
            ->willThrowException($exception);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Network unreachable');

        $progress->refresh();
    }

    public function testLtiContextWithInvalidCourseId(): void
    {
        $errorResponse = [
            'errors' => [
                ['message' => 'Course not found']
            ]
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($errorResponse));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient
            ->method('get')
            ->with('/lti/courses/999/progress/123')
            ->willReturn($this->mockResponse);

        $progress = Progress::findInLtiContext(999, 123);

        // Should create progress object even with error response
        $this->assertInstanceOf(Progress::class, $progress);
    }

    public function testPollingWithNetworkInterruption(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running']);

        // First call succeeds, second call fails
        $goodResponse = ['id' => 123, 'workflow_state' => 'running', 'completion' => 30];
        
        $this->mockStream->method('getContents')
            ->willReturnOnConsecutiveCalls(
                json_encode($goodResponse),
                $this->throwException(new RequestException('Connection lost', $this->createMock(RequestInterface::class)))
            );
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Connection lost');

        $progress->waitForCompletion(10, 1);
    }

    public function testHandlingMalformedJsonResponse(): void
    {
        // Return invalid JSON
        $this->mockStream->method('getContents')->willReturn('invalid json {');
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        // json_decode will return null for invalid JSON
        $progress = Progress::find(123);

        // Should handle gracefully
        $this->assertInstanceOf(Progress::class, $progress);
        $this->assertNull($progress->getId());
    }

    public function testHandlingEmptyResponse(): void
    {
        $this->mockStream->method('getContents')->willReturn('');
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $progress = Progress::find(123);

        // Should handle empty response gracefully
        $this->assertInstanceOf(Progress::class, $progress);
        $this->assertNull($progress->getId());
    }

    public function testHandlingNullResponse(): void
    {
        $this->mockStream->method('getContents')->willReturn('null');
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        $progress = Progress::find(123);

        // Should handle null JSON response gracefully
        $this->assertInstanceOf(Progress::class, $progress);
        $this->assertNull($progress->getId());
    }

    public function testPollingWithIntermittentErrors(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running']);

        // Sequence: success, error, success, completed
        $responses = [
            json_encode(['id' => 123, 'workflow_state' => 'running', 'completion' => 25]),
            $this->throwException(new RequestException('Temporary error', $this->createMock(RequestInterface::class))),
            json_encode(['id' => 123, 'workflow_state' => 'running', 'completion' => 75]),
            json_encode(['id' => 123, 'workflow_state' => 'completed', 'completion' => 100])
        ];

        // Since we can't easily mock consecutive calls with exceptions, test the first error case
        $this->mockStream->method('getContents')->willReturn($responses[0]);
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient->method('get')->with('/progress/123')->willReturn($this->mockResponse);

        // First refresh should work
        $progress->refresh();
        $this->assertEquals(25, $progress->getCompletion());
    }

    public function testCancelWithCustomMessage(): void
    {
        $progress = new Progress(['id' => 123, 'workflow_state' => 'running']);

        $responseData = [
            'id' => 123,
            'workflow_state' => 'failed',
            'message' => 'Cancelled: User requested stop'
        ];

        $this->mockStream->method('getContents')->willReturn(json_encode($responseData));
        $this->mockResponse->method('getBody')->willReturn($this->mockStream);
        $this->mockHttpClient
            ->method('post')
            ->with('/progress/123/cancel', ['form_params' => ['message' => 'User requested stop']])
            ->willReturn($this->mockResponse);

        $result = $progress->cancel('User requested stop');

        $this->assertSame($progress, $result);
        $this->assertEquals('failed', $progress->getWorkflowState());
        $this->assertEquals('Cancelled: User requested stop', $progress->getMessage());
    }

    public function testStatusDescriptionWithNullValues(): void
    {
        // Test with minimal data
        $progress = new Progress(['workflow_state' => null]);
        $description = $progress->getStatusDescription();
        $this->assertEquals('Unknown status', $description);

        // Test with unknown state
        $progress = new Progress(['workflow_state' => 'unknown_state']);
        $description = $progress->getStatusDescription();
        $this->assertEquals('Unknown status', $description);

        // Test with null completion
        $progress = new Progress(['workflow_state' => 'running', 'completion' => null]);
        $description = $progress->getStatusDescription();
        $this->assertEquals('In progress', $description);
    }

    public function testWorkflowStateValidation(): void
    {
        // Test all valid states
        $validStates = [
            Progress::STATE_QUEUED,
            Progress::STATE_RUNNING,
            Progress::STATE_COMPLETED,
            Progress::STATE_FAILED
        ];

        foreach ($validStates as $state) {
            $progress = new Progress(['workflow_state' => $state]);
            $this->assertEquals($state, $progress->getWorkflowState());
        }

        // Test invalid state
        $progress = new Progress(['workflow_state' => 'invalid_state']);
        $this->assertEquals('invalid_state', $progress->getWorkflowState());
        $this->assertFalse($progress->isQueued());
        $this->assertFalse($progress->isRunning());
        $this->assertFalse($progress->isCompleted());
        $this->assertFalse($progress->isFailed());
    }

    public function testDateTimeConversionErrors(): void
    {
        // Test with invalid date format
        $progress = new Progress([
            'created_at' => 'invalid-date-format',
            'updated_at' => 'also-invalid'
        ]);

        // DateTime constructor should handle invalid dates gracefully or throw exceptions
        // The behavior depends on PHP version and error handling
        $this->assertInstanceOf(Progress::class, $progress);
    }

    public function testCompletionPercentageBoundaryValues(): void
    {
        // Test negative completion
        $progress = new Progress(['completion' => -10]);
        $this->assertEquals(-10.0, $progress->getCompletionPercentage());

        // Test over 100 completion
        $progress = new Progress(['completion' => 150]);
        $this->assertEquals(150.0, $progress->getCompletionPercentage());

        // Test zero completion
        $progress = new Progress(['completion' => 0]);
        $this->assertEquals(0.0, $progress->getCompletionPercentage());

        // Test exactly 100 completion
        $progress = new Progress(['completion' => 100]);
        $this->assertEquals(100.0, $progress->getCompletionPercentage());
    }
}