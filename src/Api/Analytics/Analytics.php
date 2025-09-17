<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Analytics;

use CanvasLMS\Config;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Http\HttpClient;
use CanvasLMS\Interfaces\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Analytics API for Canvas LMS
 *
 * Provides read-only access to learning analytics data across Account, Course, and User contexts.
 * Returns raw arrays matching Canvas API JSON responses.
 *
 * @see https://canvas.instructure.com/doc/api/analytics.html
 */
class Analytics
{
    private static ?HttpClientInterface $httpClient = null;

    private static LoggerInterface $logger;

    /**
     * Initialize the logger
     */
    private static function initLogger(): void
    {
        if (!isset(self::$logger)) {
            self::$logger = new NullLogger();
        }
    }

    /**
     * Set a custom HTTP client
     *
     * @param HttpClientInterface $client
     */
    public static function setHttpClient(HttpClientInterface $client): void
    {
        self::$httpClient = $client;
    }

    /**
     * Set a logger instance
     *
     * @param LoggerInterface $logger
     */
    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    /**
     * Get the HTTP client instance
     *
     * @return HttpClientInterface
     */
    private static function getHttpClient(): HttpClientInterface
    {
        if (self::$httpClient === null) {
            self::$httpClient = new HttpClient();
        }

        return self::$httpClient;
    }

    /**
     * Make a GET request to the Analytics API
     *
     * @param string $endpoint
     * @param array<string, mixed> $params
     *
     * @throws CanvasApiException
     *
     * @return mixed[]
     */
    private static function get(string $endpoint, array $params = []): array
    {
        self::initLogger();
        $client = self::getHttpClient();

        self::$logger->info('Analytics API GET request', [
            'endpoint' => $endpoint,
            'params' => $params,
        ]);

        try {
            $response = $client->get($endpoint, $params);
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new CanvasApiException('Failed to parse Analytics API response: ' . json_last_error_msg());
            }

            return $data;
        } catch (\Exception $e) {
            self::$logger->error('Analytics API request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof CanvasApiException) {
                throw $e;
            }

            throw new CanvasApiException('Analytics API request failed: ' . $e->getMessage());
        }
    }

    // ========================================
    // Account/Department Level Analytics
    // ========================================

    /**
     * Get department-level participation data for current term
     *
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns ['by_date' => [...], 'by_category' => [...]]
     */
    public static function fetchAccountActivity(?int $accountId = null, array $params = []): array
    {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/current/activity";

        return self::get($endpoint, $params);
    }

    /**
     * Get department-level participation data for a specific term
     *
     * @param int $termId Term ID
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns ['by_date' => [...], 'by_category' => [...]]
     */
    public static function fetchAccountActivityByTerm(int $termId, ?int $accountId = null, array $params = []): array
    {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/terms/{$termId}/activity";

        return self::get($endpoint, $params);
    }

    /**
     * Get department-level grade distribution for current term
     *
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns grade distribution with scores as keys
     */
    public static function fetchAccountGrades(?int $accountId = null, array $params = []): array
    {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/current/grades";

        return self::get($endpoint, $params);
    }

    /**
     * Get department-level grade distribution for a specific term
     *
     * @param int $termId Term ID
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns grade distribution with scores as keys
     */
    public static function fetchAccountGradesByTerm(int $termId, ?int $accountId = null, array $params = []): array
    {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/terms/{$termId}/grades";

        return self::get($endpoint, $params);
    }

    /**
     * Get department-level statistics for current term
     *
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns statistics array
     */
    public static function fetchAccountStatistics(?int $accountId = null, array $params = []): array
    {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/current/statistics";

        return self::get($endpoint, $params);
    }

    /**
     * Get department-level statistics for a specific term
     *
     * @param int $termId Term ID
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns statistics array
     */
    public static function fetchAccountStatisticsByTerm(int $termId, ?int $accountId = null, array $params = []): array
    {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/terms/{$termId}/statistics";

        return self::get($endpoint, $params);
    }

    /**
     * Get department-level statistics broken down by subaccount for current term
     *
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns ['accounts' => [...]]
     */
    public static function fetchAccountStatisticsBySubaccount(?int $accountId = null, array $params = []): array
    {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/current/statistics_by_subaccount";

        return self::get($endpoint, $params);
    }

    /**
     * Get department-level statistics broken down by subaccount for a specific term
     *
     * @param int $termId Term ID
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns ['accounts' => [...]]
     */
    public static function fetchAccountStatisticsBySubaccountByTerm(
        int $termId,
        ?int $accountId = null,
        array $params = []
    ): array {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/terms/{$termId}/statistics_by_subaccount";

        return self::get($endpoint, $params);
    }

    // ========================================
    // Course Level Analytics
    // ========================================

    /**
     * Get course-level participation data
     *
     * @param int $courseId Course ID
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, array<string, mixed>> Returns array of activity data by date
     */
    public static function fetchCourseActivity(int $courseId, array $params = []): array
    {
        $endpoint = "/courses/{$courseId}/analytics/activity";

        return self::get($endpoint, $params);
    }

    /**
     * Get course-level assignment data
     *
     * @param int $courseId Course ID
     * @param array<string, mixed> $params Additional parameters (e.g., ['async' => true])
     *
     * @throws CanvasApiException
     *
     * @return array<int, array<string, mixed>>|array<string, string> Array of assignments or progress URL
     */
    public static function fetchCourseAssignments(int $courseId, array $params = []): array
    {
        $endpoint = "/courses/{$courseId}/analytics/assignments";

        return self::get($endpoint, $params);
    }

    /**
     * Get course-level student summary data
     *
     * @param int $courseId Course ID
     * @param array<string, mixed> $params Additional parameters (e.g., ['sort_column' => 'name'])
     *
     * @throws CanvasApiException
     *
     * @return array<int, array<string, mixed>> Returns array of student summaries
     */
    public static function fetchCourseStudentSummaries(int $courseId, array $params = []): array
    {
        $endpoint = "/courses/{$courseId}/analytics/student_summaries";

        return self::get($endpoint, $params);
    }

    // ========================================
    // User-in-Course Level Analytics
    // ========================================

    /**
     * Get user-in-course participation data
     *
     * @param int $courseId Course ID
     * @param int $userId User/Student ID
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns ['page_views' => [...], 'participations' => [...]]
     */
    public static function fetchUserCourseActivity(int $courseId, int $userId, array $params = []): array
    {
        $endpoint = "/courses/{$courseId}/analytics/users/{$userId}/activity";

        return self::get($endpoint, $params);
    }

    /**
     * Get user-in-course assignment data
     *
     * @param int $courseId Course ID
     * @param int $userId User/Student ID
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, array<string, mixed>> Returns array of assignment data with submission info
     */
    public static function fetchUserCourseAssignments(int $courseId, int $userId, array $params = []): array
    {
        $endpoint = "/courses/{$courseId}/analytics/users/{$userId}/assignments";

        return self::get($endpoint, $params);
    }

    /**
     * Get user-in-course messaging data
     *
     * @param int $courseId Course ID
     * @param int $userId User/Student ID
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns messaging data grouped by date
     */
    public static function fetchUserCourseCommunication(int $courseId, int $userId, array $params = []): array
    {
        $endpoint = "/courses/{$courseId}/analytics/users/{$userId}/communication";

        return self::get($endpoint, $params);
    }

    // ========================================
    // Completed Terms Analytics (Account Level)
    // ========================================

    /**
     * Get department-level participation data for completed courses
     *
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns ['by_date' => [...], 'by_category' => [...]]
     */
    public static function fetchAccountCompletedActivity(?int $accountId = null, array $params = []): array
    {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/completed/activity";

        return self::get($endpoint, $params);
    }

    /**
     * Get department-level grade distribution for completed courses
     *
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns grade distribution with scores as keys
     */
    public static function fetchAccountCompletedGrades(?int $accountId = null, array $params = []): array
    {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/completed/grades";

        return self::get($endpoint, $params);
    }

    /**
     * Get department-level statistics for completed courses
     *
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns statistics array
     */
    public static function fetchAccountCompletedStatistics(?int $accountId = null, array $params = []): array
    {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/completed/statistics";

        return self::get($endpoint, $params);
    }

    /**
     * Get department-level statistics by subaccount for completed courses
     *
     * @param int|null $accountId Account ID (defaults to Config::getAccountId())
     * @param array<string, mixed> $params Additional parameters
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Returns ['accounts' => [...]]
     */
    public static function fetchAccountCompletedStatisticsBySubaccount(
        ?int $accountId = null,
        array $params = []
    ): array {
        $accountId = $accountId ?? Config::getAccountId();
        $endpoint = "/accounts/{$accountId}/analytics/completed/statistics_by_subaccount";

        return self::get($endpoint, $params);
    }
}
