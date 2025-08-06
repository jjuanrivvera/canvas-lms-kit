<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Outcomes\OutcomeResult;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;

/**
 * OutcomeResult API class for tracking student mastery of outcomes.
 *
 * Outcome results represent individual assessments of student performance
 * against specific learning outcomes, including scores and mastery status.
 *
 * Note: OutcomeResult is a read-only resource that requires context (Course or User).
 * Direct fetching without context is not supported by Canvas API.
 *
 * @see https://canvas.instructure.com/doc/api/outcome_results.html
 */
class OutcomeResult extends AbstractBaseApi
{
    public ?int $id = null;
    public ?float $score = null;
    public ?string $submittedOrAssessedAt = null;
    /** @var array<string, mixed>|null */
    public ?array $links = null;
    public ?float $percent = null;
    public ?float $possiblePoints = null;
    public ?bool $mastery = null;
    public ?bool $hidden = null;
    public ?string $attemptedAt = null;
    public ?string $assessedAt = null;
    public ?int $assignmentId = null;
    public ?string $assignmentName = null;
    public ?int $userId = null;
    public ?string $userName = null;
    public ?int $outcomeId = null;
    public ?string $outcomeTitle = null;
    /** @var array<string, mixed>|null */
    public ?array $outcome = null;
    /** @var array<string, mixed>|null */
    public ?array $alignment = null;
    /** @var array<string, mixed>|null */
    public ?array $user = null;

    /**
     * Fetch all outcome results - NOT SUPPORTED.
     * OutcomeResult requires context. Use fetchByContext() instead.
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, static> Never returns - always throws exception
     * @throws CanvasApiException Always thrown as method requires context
     */
    public static function fetchAll(array $params = []): array
    {
        throw new CanvasApiException(
            'OutcomeResult requires context. Use fetchByContext() instead.'
        );
    }

    /**
     * Fetch all outcome results by context.
     *
     * @param string $contextType Context type (courses, users)
     * @param int $contextId Context ID
     * @param array<string, mixed> $params Query parameters
     * @return array<int, OutcomeResult> Array of OutcomeResult objects
     * @throws CanvasApiException
     */
    public static function fetchByContext(string $contextType, int $contextId, array $params = []): array
    {
        // Validate context type
        $validContexts = ['courses', 'users'];
        if (!in_array($contextType, $validContexts, true)) {
            throw new \InvalidArgumentException("Invalid context type: {$contextType}");
        }

        $endpoint = sprintf('%s/%d/outcome_results', $contextType, $contextId);

        $response = self::$apiClient->get($endpoint, [
            'query' => $params
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $results = [];
        // Check if data is wrapped in 'outcome_results' or is a direct array
        if (isset($data['outcome_results'])) {
            // Wrapped response (pagination or specific endpoints)
            foreach ($data['outcome_results'] as $resultData) {
                $results[] = new self($resultData);
            }
        } elseif (is_array($data) && !empty($data)) {
            // Direct array response
            foreach ($data as $resultData) {
                if (is_array($resultData)) {
                    $results[] = new self($resultData);
                }
            }
        }

        return $results;
    }

    /**
     * Find method - NOT SUPPORTED for OutcomeResult.
     *
     * @param int $id ID
     * @return static Never returns - always throws exception
     * @throws CanvasApiException Always thrown as OutcomeResult doesn't support direct find
     */
    public static function find(int $id): static
    {
        throw new CanvasApiException(
            'OutcomeResult does not support find(). Results are accessed through course or user context.'
        );
    }

    /**
     * Fetch outcome results for a course.
     *
     * @param int $courseId Course ID
     * @param array<string, mixed> $params Query parameters (user_ids, outcome_ids, include, etc.)
     * @return array<int, OutcomeResult> Array of OutcomeResult objects
     * @throws CanvasApiException
     */
    public static function fetchByCourse(int $courseId, array $params = []): array
    {
        return self::fetchByContext('courses', $courseId, $params);
    }

    /**
     * Fetch outcome results for a user.
     *
     * @param int $userId User ID
     * @param array<string, mixed> $params Query parameters
     * @return array<int, OutcomeResult> Array of OutcomeResult objects
     * @throws CanvasApiException
     */
    public static function fetchByUser(int $userId, array $params = []): array
    {
        return self::fetchByContext('users', $userId, $params);
    }

    /**
     * Fetch paginated outcome results by context.
     *
     * @param string $contextType Context type (courses, users)
     * @param int $contextId Context ID
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchByContextPaginated(
        string $contextType,
        int $contextId,
        array $params = []
    ): PaginatedResponse {
        // Validate context type
        $validContexts = ['courses', 'users'];
        if (!in_array($contextType, $validContexts, true)) {
            throw new \InvalidArgumentException("Invalid context type: {$contextType}");
        }

        $endpoint = sprintf('%s/%d/outcome_results', $contextType, $contextId);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Check if the result represents mastery.
     *
     * @return bool
     */
    public function isMastery(): bool
    {
        return $this->mastery ?? false;
    }

    /**
     * Check if the result is hidden.
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden ?? false;
    }

    /**
     * Get the mastery percentage.
     *
     * @return float|null
     */
    public function getMasteryPercentage(): ?float
    {
        if ($this->percent !== null) {
            return $this->percent;
        }

        if ($this->score !== null && $this->possiblePoints !== null && $this->possiblePoints > 0) {
            return ($this->score / $this->possiblePoints) * 100;
        }

        return null;
    }

    /**
     * Get the linked user ID.
     *
     * @return int|null
     */
    public function getLinkedUserId(): ?int
    {
        if ($this->links && isset($this->links['user'])) {
            return (int) $this->links['user'];
        }
        return null;
    }

    /**
     * Get the linked outcome ID.
     *
     * @return int|null
     */
    public function getLinkedOutcomeId(): ?int
    {
        if ($this->links && isset($this->links['learning_outcome'])) {
            return (int) $this->links['learning_outcome'];
        }
        return null;
    }

    /**
     * Get the linked alignment.
     *
     * @return string|null
     */
    public function getLinkedAlignment(): ?string
    {
        if ($this->links && isset($this->links['alignment'])) {
            return (string) $this->links['alignment'];
        }
        return null;
    }
}
