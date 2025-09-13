<?php

declare(strict_types=1);

namespace Tests\Api\CourseReports;

use CanvasLMS\Api\CourseReports\CourseReports;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class CourseReportsTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private Course $course;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        CourseReports::setApiClient($this->httpClient);

        // Create a mock course
        $this->course = new Course(['id' => 123, 'name' => 'Test Course']);
    }

    protected function tearDown(): void
    {
        // Reset static properties to avoid test interference
        $reflection = new \ReflectionClass(CourseReports::class);
        $courseProperty = $reflection->getProperty('course');
        $courseProperty->setAccessible(true);
        $courseProperty->setValue(null, null);
    }

    public function testSetCourse(): void
    {
        CourseReports::setCourse($this->course);
        
        $this->assertTrue(CourseReports::checkCourse());
    }

    public function testCheckCourseThrowsExceptionWhenNotSet(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context is required for course reports operations');
        
        CourseReports::checkCourse();
    }

    public function testCheckCourseThrowsExceptionWhenCourseHasNoId(): void
    {
        $courseWithoutId = new Course(['name' => 'Test Course']);
        CourseReports::setCourse($courseWithoutId);
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context is required for course reports operations');
        
        CourseReports::checkCourse();
    }

    public function testCreateReport(): void
    {
        CourseReports::setCourse($this->course);

        $reportData = [
            'id' => 456,
            'status' => 'running',
            'progress' => 25,
            'created_at' => '2024-01-15T10:00:00Z',
            'started_at' => '2024-01-15T10:01:00Z',
            'parameters' => ['enrollment_term_id' => 789]
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($reportData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with('courses/123/reports/grade_export', [
                'form_params' => ['enrollment_term_id' => 789]
            ])
            ->willReturn($responseMock);

        $report = CourseReports::create('grade_export', ['enrollment_term_id' => 789]);

        $this->assertInstanceOf(CourseReports::class, $report);
        $this->assertEquals(456, $report->id);
        $this->assertEquals('running', $report->status);
        $this->assertEquals(25, $report->progress);
        $this->assertEquals('2024-01-15T10:00:00Z', $report->createdAt);
        $this->assertEquals('2024-01-15T10:01:00Z', $report->startedAt);
        $this->assertEquals(['enrollment_term_id' => 789], $report->parameters);
    }

    public function testCreateReportThrowsExceptionWithoutCourse(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context is required for course reports operations');
        
        CourseReports::create('grade_export');
    }

    public function testGetReport(): void
    {
        CourseReports::setCourse($this->course);

        $reportData = [
            'id' => 456,
            'status' => 'complete',
            'file_url' => 'https://canvas.example.com/files/report.csv',
            'progress' => 100,
            'created_at' => '2024-01-15T10:00:00Z',
            'started_at' => '2024-01-15T10:01:00Z',
            'ended_at' => '2024-01-15T10:05:00Z',
            'attachment' => ['id' => 789, 'filename' => 'grade_export.csv']
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($reportData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/reports/grade_export/456')
            ->willReturn($responseMock);

        $report = CourseReports::getReport('grade_export', 456);

        $this->assertInstanceOf(CourseReports::class, $report);
        $this->assertEquals(456, $report->id);
        $this->assertEquals('complete', $report->status);
        $this->assertEquals('https://canvas.example.com/files/report.csv', $report->fileUrl);
        $this->assertEquals(100, $report->progress);
        $this->assertEquals('2024-01-15T10:05:00Z', $report->endedAt);
        $this->assertIsArray($report->attachment);
        $this->assertEquals(789, $report->attachment['id']);
    }

    public function testGetReportThrowsExceptionWithoutCourse(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context is required for course reports operations');
        
        CourseReports::getReport('grade_export', 456);
    }

    public function testFindMethodThrowsException(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course reports cannot be found by ID alone. Use getReport($reportType, $reportId) instead.');
        
        CourseReports::find(123);
    }

    public function testLastReport(): void
    {
        CourseReports::setCourse($this->course);

        $reportData = [
            'id' => 789,
            'status' => 'failed',
            'progress' => 50,
            'created_at' => '2024-01-15T09:00:00Z',
            'started_at' => '2024-01-15T09:01:00Z',
            'ended_at' => '2024-01-15T09:03:00Z'
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($reportData));

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with('courses/123/reports/student_assignment_data')
            ->willReturn($responseMock);

        $report = CourseReports::last('student_assignment_data');

        $this->assertInstanceOf(CourseReports::class, $report);
        $this->assertEquals(789, $report->id);
        $this->assertEquals('failed', $report->status);
        $this->assertEquals(50, $report->progress);
        $this->assertEquals('2024-01-15T09:03:00Z', $report->endedAt);
    }

    public function testLastReportThrowsExceptionWithoutCourse(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course context is required for course reports operations');
        
        CourseReports::last('student_assignment_data');
    }

    public function testIsCompletedTrue(): void
    {
        $report = new CourseReports(['status' => 'complete']);
        $this->assertTrue($report->isCompleted());
    }

    public function testIsCompletedFalse(): void
    {
        $report = new CourseReports(['status' => 'running']);
        $this->assertFalse($report->isCompleted());
    }

    public function testIsRunningTrue(): void
    {
        $report = new CourseReports(['status' => 'running']);
        $this->assertTrue($report->isRunning());
    }

    public function testIsRunningFalse(): void
    {
        $report = new CourseReports(['status' => 'complete']);
        $this->assertFalse($report->isRunning());
    }

    public function testIsFailedTrue(): void
    {
        $report = new CourseReports(['status' => 'failed']);
        $this->assertTrue($report->isFailed());
    }

    public function testIsFailedFalse(): void
    {
        $report = new CourseReports(['status' => 'complete']);
        $this->assertFalse($report->isFailed());
    }

    public function testIsReadyWhenCompleteWithFile(): void
    {
        $report = new CourseReports([
            'status' => 'complete',
            'file_url' => 'https://canvas.example.com/files/report.csv'
        ]);
        $this->assertTrue($report->isReady());
    }

    public function testIsReadyWhenCompleteWithoutFile(): void
    {
        $report = new CourseReports(['status' => 'complete']);
        $this->assertFalse($report->isReady());
    }

    public function testIsReadyWhenNotComplete(): void
    {
        $report = new CourseReports([
            'status' => 'running',
            'file_url' => 'https://canvas.example.com/files/report.csv'
        ]);
        $this->assertFalse($report->isReady());
    }

    public function testGetProgressWithValue(): void
    {
        $report = new CourseReports(['progress' => 75]);
        $this->assertEquals(75, $report->getProgress());
    }

    public function testGetProgressWithoutValue(): void
    {
        $report = new CourseReports([]);
        $this->assertEquals(0, $report->getProgress());
    }

    public function testGetStatusDescriptionComplete(): void
    {
        $report = new CourseReports(['status' => 'complete']);
        $this->assertEquals('Report generation completed successfully', $report->getStatusDescription());
    }

    public function testGetStatusDescriptionRunning(): void
    {
        $report = new CourseReports(['status' => 'running']);
        $this->assertEquals('Report generation in progress', $report->getStatusDescription());
    }

    public function testGetStatusDescriptionFailed(): void
    {
        $report = new CourseReports(['status' => 'failed']);
        $this->assertEquals('Report generation failed', $report->getStatusDescription());
    }

    public function testGetStatusDescriptionQueued(): void
    {
        $report = new CourseReports(['status' => 'queued']);
        $this->assertEquals('Report generation queued', $report->getStatusDescription());
    }

    public function testGetStatusDescriptionUnknown(): void
    {
        $report = new CourseReports(['status' => 'unknown_status']);
        $this->assertEquals('Unknown status: unknown_status', $report->getStatusDescription());
    }

    public function testGetStatusDescriptionNull(): void
    {
        $report = new CourseReports([]);
        $this->assertEquals('Unknown status: null', $report->getStatusDescription());
    }

    public function testToArray(): void
    {
        $reportData = [
            'id' => 123,
            'status' => 'complete',
            'file_url' => 'https://canvas.example.com/files/report.csv',
            'attachment' => ['id' => 456],
            'created_at' => '2024-01-15T10:00:00Z',
            'started_at' => '2024-01-15T10:01:00Z',
            'ended_at' => '2024-01-15T10:05:00Z',
            'parameters' => ['term_id' => 789],
            'progress' => 100
        ];

        $report = new CourseReports($reportData);
        $array = $report->toArray();

        $expected = [
            'id' => 123,
            'status' => 'complete',
            'file_url' => 'https://canvas.example.com/files/report.csv',
            'attachment' => ['id' => 456],
            'created_at' => '2024-01-15T10:00:00Z',
            'started_at' => '2024-01-15T10:01:00Z',
            'ended_at' => '2024-01-15T10:05:00Z',
            'parameters' => ['term_id' => 789],
            'progress' => 100
        ];

        $this->assertEquals($expected, $array);
    }

    public function testUnsupportedUpdateMethod(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course reports cannot be updated');
        
        CourseReports::update(123, []);
    }

    public function testUnsupportedSaveMethod(): void
    {
        $report = new CourseReports(['id' => 123]);
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course reports cannot be updated');
        
        $report->save();
    }

    public function testUnsupportedDeleteMethod(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course reports cannot be deleted');
        
        CourseReports::delete(123);
    }

    public function testUnsupportedDestroyMethod(): void
    {
        $report = new CourseReports(['id' => 123]);
        
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Course reports cannot be deleted');
        
        $report->destroy();
    }

    public function testUnsupportedFetchAllMethod(): void
    {
        $this->expectException(CanvasApiException::class);
        $this->expectExceptionMessage('Use specific report type methods (last, getReport) instead of get');
        
        CourseReports::get();
    }

    public function testPropertyConversionFromSnakeCaseToCamelCase(): void
    {
        $apiResponse = [
            'id' => 123,
            'status' => 'complete',
            'file_url' => 'https://canvas.example.com/files/report.csv',
            'created_at' => '2024-01-15T10:00:00Z',
            'started_at' => '2024-01-15T10:01:00Z',
            'ended_at' => '2024-01-15T10:05:00Z'
        ];

        $report = new CourseReports($apiResponse);

        // Verify snake_case properties are converted to camelCase
        $this->assertEquals('https://canvas.example.com/files/report.csv', $report->fileUrl);
        $this->assertEquals('2024-01-15T10:00:00Z', $report->createdAt);
        $this->assertEquals('2024-01-15T10:01:00Z', $report->startedAt);
        $this->assertEquals('2024-01-15T10:05:00Z', $report->endedAt);
    }
}