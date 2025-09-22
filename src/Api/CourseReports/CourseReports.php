<?php

declare(strict_types=1);

namespace CanvasLMS\Api\CourseReports;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * Canvas Course Reports API
 *
 * Manages course report generation and status tracking in Canvas LMS.
 * Reports are asynchronous operations that generate downloadable files
 * containing course data such as grades, student activity, assignments, etc.
 *
 * @see https://canvas.instructure.com/doc/api/course_reports.html
 */
class CourseReports extends AbstractBaseApi
{
    /**
     * Course context for report operations
     */
    protected static ?Course $course = null;

    /**
     * Report ID
     */
    public ?int $id = null;

    /**
     * Report status (complete, running, failed, etc.)
     */
    public ?string $status = null;

    /**
     * URL to download the completed report file
     */
    public ?string $fileUrl = null;

    /**
     * Report attachment details
     */
    public mixed $attachment = null;

    /**
     * Report creation timestamp
     */
    public ?string $createdAt = null;

    /**
     * Report start timestamp
     */
    public ?string $startedAt = null;

    /**
     * Report completion timestamp
     */
    public ?string $endedAt = null;

    /**
     * Parameters used to generate the report
     *
     * @var array<string, mixed>|null
     */
    public ?array $parameters = null;

    /**
     * Report generation progress (0-100)
     */
    public ?int $progress = null;

    /**
     * Set the course context for report operations
     *
     * @param Course $course Course instance to operate on
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Get the Course instance, ensuring it is set
     *
     * @throws CanvasApiException if course is not set
     *
     * @return Course
     */
    protected static function getCourse(): Course
    {
        if (self::$course === null) {
            throw new CanvasApiException('Course context not set. Call ' . static::class . '::setCourse() first.');
        }

        return self::$course;
    }

    /**
     * Get the Course ID from context, ensuring course is set
     *
     * @throws CanvasApiException if course is not set
     *
     * @return int
     */
    protected static function getContextCourseId(): int
    {
        return self::getCourse()->id;
    }

    /**
     * Check if course context is set and valid
     *
     * @throws CanvasApiException If course context is not set or invalid
     *
     * @return bool True if course context is valid
     */
    public static function checkCourse(): bool
    {
        if (!isset(self::$course) || !isset(self::$course->id)) {
            throw new CanvasApiException('Course context is required for course reports operations');
        }

        return true;
    }

    /**
     * Start generating a new report for the course
     *
     * @param string $reportType Type of report to generate (e.g., 'grade_export', 'student_assignment_data')
     * @param array<string, mixed> $parameters Additional parameters for report generation
     *
     * @throws CanvasApiException If course context not set or API call fails
     *
     * @return self New CourseReports instance with report details
     */
    public static function create(string $reportType, array $parameters = []): self
    {
        self::checkCourse();

        $endpoint = sprintf('courses/%d/reports/%s', self::getContextCourseId(), $reportType);

        self::checkApiClient();

        $response = self::getApiClient()->post($endpoint, [
            'form_params' => $parameters,
        ]);

        $reportData = self::parseJsonResponse($response);

        return new self($reportData);
    }

    /**
     * Interface-required find method (not supported for course reports)
     * Use getReport() method instead with report type and ID
     *
     * @param int $id Report ID (not used)
     *
     * @throws CanvasApiException Always thrown - use getReport() instead
     *
     * @return static Never returns, always throws exception
     */
    public static function find(int $id, array $params = []): static
    {
        throw new CanvasApiException(
            'Course reports cannot be found by ID alone. Use getReport($reportType, $reportId) instead.'
        );
    }

    /**
     * Get the status of a specific report
     *
     * @param string $reportType Type of report to check
     * @param int $reportId ID of the specific report
     *
     * @throws CanvasApiException If course context not set or API call fails
     *
     * @return self CourseReports instance with report status
     */
    public static function getReport(string $reportType, int $reportId): self
    {
        self::checkCourse();

        $endpoint = sprintf('courses/%d/reports/%s/%d', self::getContextCourseId(), $reportType, $reportId);

        self::checkApiClient();

        $response = self::getApiClient()->get($endpoint);
        $reportData = self::parseJsonResponse($response);

        return new self($reportData);
    }

    /**
     * Get the status of the last report of the specified type
     *
     * @param string $reportType Type of report to check
     *
     * @throws CanvasApiException If course context not set or API call fails
     *
     * @return self CourseReports instance with last report status
     */
    public static function last(string $reportType): self
    {
        self::checkCourse();

        $endpoint = sprintf('courses/%d/reports/%s', self::getContextCourseId(), $reportType);

        self::checkApiClient();

        $response = self::getApiClient()->get($endpoint);
        $reportData = self::parseJsonResponse($response);

        return new self($reportData);
    }

    /**
     * Check if the report generation is completed
     *
     * @return bool True if report status is 'complete'
     */
    public function isCompleted(): bool
    {
        return $this->status === 'complete';
    }

    /**
     * Check if the report generation is currently running
     *
     * @return bool True if report status is 'running'
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if the report generation failed
     *
     * @return bool True if report status is 'failed'
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the report is ready for download
     *
     * @return bool True if report is completed and has a download URL
     */
    public function isReady(): bool
    {
        return $this->isCompleted() && !empty($this->fileUrl);
    }

    /**
     * Get the report progress as a percentage
     *
     * @return int Progress percentage (0-100)
     */
    public function getProgress(): int
    {
        return $this->progress ?? 0;
    }

    /**
     * Get a human-readable status description
     *
     * @return string Status description
     */
    public function getStatusDescription(): string
    {
        return match ($this->status) {
            'complete' => 'Report generation completed successfully',
            'running' => 'Report generation in progress',
            'failed' => 'Report generation failed',
            'queued' => 'Report generation queued',
            default => 'Unknown status: ' . ($this->status ?? 'null')
        };
    }

    /**
     * Convert the report data to array format
     *
     * @return array<string, mixed> Report data as associative array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'file_url' => $this->fileUrl,
            'attachment' => $this->attachment,
            'created_at' => $this->createdAt,
            'started_at' => $this->startedAt,
            'ended_at' => $this->endedAt,
            'parameters' => $this->parameters,
            'progress' => $this->progress,
        ];
    }

    /**
     * Methods not supported for Course Reports API
     * Course reports cannot be updated through the API
     */

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): self
    {
        throw new CanvasApiException('Course reports cannot be updated');
    }

    public function save(): bool
    {
        throw new CanvasApiException('Course reports cannot be updated');
    }

    public static function delete(int $id): bool
    {
        throw new CanvasApiException('Course reports cannot be deleted');
    }

    public function destroy(): bool
    {
        throw new CanvasApiException('Course reports cannot be deleted');
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws CanvasApiException Always thrown
     *
     * @return array<static>
     */
    public static function get(array $params = []): array
    {
        throw new CanvasApiException('Use specific report type methods (last, getReport) instead of get');
    }

    /**
     * Get the base API endpoint for reports (requires course context)
     *
     * @return string The API endpoint pattern
     */
    protected static function getEndpoint(): string
    {
        return 'courses/reports';
    }
}
