<?php

declare(strict_types=1);

namespace CanvasLMS\Api\GradebookHistory;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Objects\GradebookHistoryDay;
use CanvasLMS\Objects\GradebookHistoryGrader;
use CanvasLMS\Objects\SubmissionHistory;
use CanvasLMS\Objects\SubmissionVersion;
use CanvasLMS\Pagination\PaginatedResponse;

/**
 * GradebookHistory Class
 *
 * Provides access to the versioned history of student submissions and grade changes
 * in Canvas LMS. This API is essential for academic integrity, grade disputes, and
 * compliance requirements as it provides a complete audit trail of all grading activities.
 *
 * The Gradebook History API tracks:
 * - All grade changes with timestamps
 * - Who made each grade change (grader information)
 * - Previous and new grade values
 * - Date-based organization of grading activities
 * - Submission versions and their changes over time
 *
 * Usage:
 *
 * ```php
 * // Set the course context
 * GradebookHistory::setCourse(123);
 *
 * // Get days with grading activity
 * $days = GradebookHistory::fetchDays();
 *
 * // Get details for a specific day
 * $graders = GradebookHistory::fetchDay('2025-01-15');
 *
 * // Get detailed submissions for a specific grader, assignment, and date
 * $submissions = GradebookHistory::fetchSubmissions('2025-01-15', 456, 789);
 *
 * // Get submission versions feed with pagination
 * $feed = GradebookHistory::fetchFeedPaginated([
 *     'assignment_id' => 789,
 *     'user_id' => 456,
 *     'ascending' => true
 * ]);
 *
 * // Access through Course instance
 * $course = Course::find(123);
 * $history = $course->gradebookHistory();
 * $days = $history::fetchDays();
 * ```
 *
 * @see https://canvas.instructure.com/doc/api/gradebook_history.html
 *
 * @package CanvasLMS\Api\GradebookHistory
 */
class GradebookHistory extends AbstractBaseApi
{
    /**
     * The course ID context for gradebook history operations
     *
     * @var int|null
     */
    protected static ?int $courseId = null;

    /**
     * Set the course context for gradebook history operations.
     *
     * @param int $courseId The ID of the course
     *
     * @return void
     */
    public static function setCourse(int $courseId): void
    {
        self::$courseId = $courseId;
    }

    /**
     * Get the current course context.
     *
     * @return int|null
     */
    public static function getCourse(): ?int
    {
        return self::$courseId;
    }

    /**
     * Check if course context is set and throw exception if not.
     *
     * @throws CanvasApiException
     *
     * @return void
     */
    protected static function checkCourse(): void
    {
        if (self::$courseId === null) {
            throw new CanvasApiException(
                'Course context is required for Gradebook History operations. ' .
                'Use GradebookHistory::setCourse() to set the course ID.'
            );
        }
    }

    /**
     * Reset the course context.
     *
     * @return void
     */
    public static function resetCourse(): void
    {
        self::$courseId = null;
    }

    /**
     * Get the base endpoint for gradebook history operations.
     *
     * @throws CanvasApiException
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        self::checkCourse();

        return 'courses/' . self::$courseId . '/gradebook_history';
    }

    /**
     * List days with grading activity in the course.
     *
     * Returns a list of dates that have grading activity in this course.
     * The response is ordered by date, descending (newest first).
     *
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<GradebookHistoryDay>
     */
    public static function fetchDays(array $params = []): array
    {
        $endpoint = self::getEndpoint() . '/days';
        self::checkApiClient();

        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseBody = self::parseJsonResponse($response);

        if (!is_array($responseBody)) {
            return [];
        }

        return array_map(
            fn ($dayData) => new GradebookHistoryDay($dayData),
            $responseBody
        );
    }

    /**
     * Get details for a specific day in gradebook history.
     *
     * Returns the graders who worked on this day, along with the assignments
     * they worked on. More details can be obtained by calling fetchSubmissions()
     * with a specific grader and assignment.
     *
     * @param string $date The date in YYYY-MM-DD format
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<GradebookHistoryGrader>
     */
    public static function fetchDay(string $date, array $params = []): array
    {
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new CanvasApiException('Date must be in YYYY-MM-DD format');
        }

        $endpoint = self::getEndpoint() . '/' . $date;
        self::checkApiClient();

        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseBody = self::parseJsonResponse($response);

        if (!is_array($responseBody)) {
            return [];
        }

        return array_map(
            fn ($graderData) => new GradebookHistoryGrader($graderData),
            $responseBody
        );
    }

    /**
     * Get detailed submissions for a specific date, grader, and assignment.
     *
     * Returns a nested list of submission versions for all submissions
     * graded by the specified grader for the specified assignment on the
     * specified date.
     *
     * @param string $date The date in YYYY-MM-DD format
     * @param int $graderId The ID of the grader
     * @param int $assignmentId The ID of the assignment
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<SubmissionHistory>
     */
    public static function fetchSubmissions(
        string $date,
        int $graderId,
        int $assignmentId,
        array $params = []
    ): array {
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new CanvasApiException('Date must be in YYYY-MM-DD format');
        }

        $endpoint = self::getEndpoint() . '/' . $date . '/graders/' . $graderId .
                    '/assignments/' . $assignmentId . '/submissions';
        self::checkApiClient();

        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseBody = self::parseJsonResponse($response);

        if (!is_array($responseBody)) {
            return [];
        }

        return array_map(
            fn ($historyData) => new SubmissionHistory($historyData),
            $responseBody
        );
    }

    /**
     * Get a paginated feed of submission versions.
     *
     * Returns a paginated, uncollated list of submission versions for all
     * matching submissions in the course. The SubmissionVersion objects
     * returned by this endpoint will not include the new_grade or previous_grade
     * keys, only the grade; same for graded_at and grader.
     *
     * @param array<string, mixed> $params Optional query parameters:
     *   - assignment_id (int): Filter by assignment ID
     *   - user_id (int): Filter by user ID
     *   - ascending (bool): Return in ascending date order (oldest first)
     *
     * @throws CanvasApiException
     *
     * @return array<SubmissionVersion>
     */
    public static function fetchFeed(array $params = []): array
    {
        $endpoint = self::getEndpoint() . '/feed';
        self::checkApiClient();

        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseBody = self::parseJsonResponse($response);

        if (!is_array($responseBody)) {
            return [];
        }

        return array_map(
            fn ($versionData) => new SubmissionVersion($versionData),
            $responseBody
        );
    }

    /**
     * Get a paginated feed of submission versions with pagination support.
     *
     * Returns a PaginatedResponse containing submission versions and pagination
     * metadata. This is useful for processing large datasets efficiently.
     *
     * @param array<string, mixed> $params Optional query parameters:
     *   - assignment_id (int): Filter by assignment ID
     *   - user_id (int): Filter by user ID
     *   - ascending (bool): Return in ascending date order (oldest first)
     *   - per_page (int): Number of items per page (default: 10, max: 100)
     *
     * @throws CanvasApiException
     *
     * @return PaginatedResponse
     */
    public static function fetchFeedPaginated(array $params = []): PaginatedResponse
    {
        $endpoint = self::getEndpoint() . '/feed';

        self::checkApiClient();

        return self::$apiClient->getPaginated($endpoint, [
            'query' => $params,
        ]);
    }

    /**
     * Get all submission versions from the feed (memory intensive).
     *
     * This method fetches all pages of submission versions. Use with caution
     * on large datasets as it loads all results into memory.
     *
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<SubmissionVersion>
     */
    public static function fetchAllFeed(array $params = []): array
    {
        $endpoint = self::getEndpoint() . '/feed';
        $allVersions = [];

        self::checkApiClient();
        $nextUrl = $endpoint;
        $nextParams = ['query' => $params];

        do {
            $response = self::$apiClient->get($nextUrl, $nextParams);
            $responseBody = self::parseJsonResponse($response);

            if (is_array($responseBody)) {
                foreach ($responseBody as $versionData) {
                    $allVersions[] = new SubmissionVersion($versionData);
                }
            }

            // Get next page URL from Link header
            $linkHeader = $response->getHeader('Link');
            $nextUrl = null;

            if (!empty($linkHeader)) {
                $links = $linkHeader[0] ?? '';
                if (preg_match('/<([^>]+)>;\s*rel="next"/', $links, $matches)) {
                    // Extract just the path from the full URL
                    $parsedUrl = parse_url($matches[1]);
                    $nextUrl = $parsedUrl['path'] ?? null;
                    if ($nextUrl && isset($parsedUrl['query'])) {
                        $queryParams = [];
                        parse_str($parsedUrl['query'], $queryParams);
                        $nextParams = ['query' => $queryParams];
                    } else {
                        $nextParams = ['query' => []];
                    }
                }
            }
        } while ($nextUrl !== null);

        return $allVersions;
    }

    /**
     * Find a specific resource by ID.
     * Note: Gradebook History API doesn't support finding individual records by ID.
     * This method is required by the interface but not applicable for this resource.
     *
     * @param int $id The ID to search for
     *
     * @throws CanvasApiException
     *
     * @return static
     */
    public static function find(int $id, array $params = []): static
    {
        throw new CanvasApiException(
            'The Gradebook History API does not support finding individual records by ID. ' .
            'Use fetchDays(), fetchDay(), fetchSubmissions(), or fetchFeed() instead.'
        );
    }

    /**
     * Get the API endpoint name for this resource.
     *
     * @return string
     */
    protected static function getApiEndpoint(): string
    {
        return 'gradebook_history';
    }
}
