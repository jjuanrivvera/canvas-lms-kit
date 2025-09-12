<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Progress;

use DateTime;
use Exception;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Exceptions\CanvasApiException;

use function str_to_snake_case;

/**
 * Progress Class
 *
 * Represents an asynchronous operation progress tracker in Canvas LMS. This class provides
 * methods to track the status of long-running operations like content migrations, bulk updates,
 * and file uploads.
 *
 * Usage:
 *
 * ```php
 * // Finding a progress by ID
 * $progress = Progress::find(123);
 *
 * // Checking progress status
 * if ($progress->isCompleted()) {
 *     $results = $progress->results;
 *     echo "Operation completed successfully";
 * } elseif ($progress->isFailed()) {
 *     echo "Operation failed: " . $progress->message;
 * } else {
 *     echo "Progress: " . $progress->completion . "%";
 * }
 *
 * // Polling until completion
 * $progress = Progress::find(123);
 * $completedProgress = $progress->waitForCompletion(300, 2); // 5 minutes max, 2 second intervals
 *
 * // Cancelling an operation
 * $progress->cancel("User requested cancellation");
 *
 * // LTI context progress
 * $progress = Progress::findInLtiContext(456, 123); // course_id, progress_id
 *
 * // Static polling utility
 * $progress = Progress::pollUntilComplete(123, 300, 2);
 * ```
 *
 * @package CanvasLMS\Api\Progress
 */
class Progress extends AbstractBaseApi
{
    /**
     * Workflow state constants
     */
    public const STATE_QUEUED = 'queued';
    public const STATE_RUNNING = 'running';
    public const STATE_COMPLETED = 'completed';
    public const STATE_FAILED = 'failed';

    /**
     * The unique identifier for the progress object
     */
    public ?int $id = null;

    /**
     * The ID of the object the job is associated with
     */
    public ?int $contextId = null;

    /**
     * The type of object (e.g., "Account", "Course", "User")
     */
    public ?string $contextType = null;

    /**
     * The ID of the user who initiated the job
     */
    public ?int $userId = null;

    /**
     * Identifies the type of operation being performed
     */
    public ?string $tag = null;

    /**
     * Percentage completed (0-100)
     */
    public ?int $completion = null;

    /**
     * Current status of the job (queued, running, completed, failed)
     */
    public ?string $workflowState = null;

    /**
     * When the job was created
     */
    public ?DateTime $createdAt = null;

    /**
     * When the progress was last updated
     */
    public ?DateTime $updatedAt = null;

    /**
     * Optional descriptive message about current status
     */
    public ?string $message = null;

    /**
     * Optional results data when job completes
     */
    public mixed $results = null;

    /**
     * Endpoint URL for retrieving progress updates
     */
    public ?string $url = null;

    /**
     * Progress constructor.
     * @param mixed[] $data
     */
    public function __construct(array $data = [])
    {
        // Handle the data population manually to ensure proper type casting
        foreach ($data as $key => $value) {
            $camelKey = lcfirst(str_replace('_', '', ucwords((string) $key, '_')));

            if (property_exists($this, $camelKey) && $value !== null) {
                $this->{$camelKey} = $this->castValue($camelKey, $value);
            }
        }
    }

    /**
     * Cast a value to the correct type
     * @param string $key
     * @param mixed $value
     * @return DateTime|mixed
     * @throws Exception
     */
    protected function castValue(string $key, mixed $value): mixed
    {
        // Handle DateTime fields
        if (in_array($key, ['createdAt', 'updatedAt']) && is_string($value)) {
            try {
                return new DateTime($value);
            } catch (\Throwable $e) {
                // Return null for invalid date formats instead of throwing
                // Using \Throwable to catch both Exception and Error
                // (including DateMalformedStringException in PHP 8.3+)
                return null;
            }
        }

        return parent::castValue($key, $value);
    }

    /**
     * Find a progress object by ID
     * @param int $id Progress ID
     * @return Progress
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): Progress
    {
        self::checkApiClient();

        $response = self::$apiClient->get("/progress/{$id}");
        $data = json_decode($response->getBody()->getContents(), true);

        return new self($data ?? []);
    }

    /**
     * Find a progress object in LTI context
     * @param int $courseId Course ID for LTI context
     * @param int $id Progress ID
     * @return Progress
     * @throws CanvasApiException
     */
    public static function findInLtiContext(int $courseId, int $id): Progress
    {
        self::checkApiClient();

        $response = self::$apiClient->get("/lti/courses/{$courseId}/progress/{$id}");
        $data = json_decode($response->getBody()->getContents(), true);

        return new self($data ?? []);
    }

    /**
     * Cancel a running operation
     * @param string|null $message Optional message to distinguish the cancellation reason
     * @return Progress Updated progress object
     * @throws CanvasApiException
     */
    public function cancel(?string $message = null): Progress
    {
        if ($this->id === null) {
            throw new CanvasApiException('Cannot cancel progress: ID is not set');
        }

        self::checkApiClient();

        $params = [];
        if ($message !== null) {
            $params['message'] = $message;
        }

        $response = self::$apiClient->post("/progress/{$this->id}/cancel", [
            'form_params' => $params
        ]);

        $data = json_decode($response->getBody()->getContents(), true) ?? [];

        // Update current instance with new data
        foreach ($data as $key => $value) {
            $camelKey = lcfirst(str_replace('_', '', ucwords((string) $key, '_')));
            if (property_exists($this, $camelKey) && $value !== null) {
                $this->{$camelKey} = $this->castValue($camelKey, $value);
            }
        }

        return $this;
    }

    /**
     * Refresh the progress object with latest data from API
     * @return Progress
     * @throws CanvasApiException
     */
    public function refresh(): Progress
    {
        if ($this->id === null) {
            throw new CanvasApiException('Cannot refresh progress: ID is not set');
        }

        $updated = self::find($this->id);

        // Update current instance with new data
        foreach ($updated->toDtoArray() as $key => $value) {
            if (property_exists($this, $key) && $value !== null) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Check if the progress is in queued state
     */
    public function isQueued(): bool
    {
        return $this->workflowState === self::STATE_QUEUED;
    }

    /**
     * Check if the progress is in running state
     */
    public function isRunning(): bool
    {
        return $this->workflowState === self::STATE_RUNNING;
    }

    /**
     * Check if the progress is completed
     */
    public function isCompleted(): bool
    {
        return $this->workflowState === self::STATE_COMPLETED;
    }

    /**
     * Check if the progress has failed
     */
    public function isFailed(): bool
    {
        return $this->workflowState === self::STATE_FAILED;
    }

    /**
     * Check if the operation is currently in progress (queued or running)
     */
    public function isInProgress(): bool
    {
        return $this->isQueued() || $this->isRunning();
    }

    /**
     * Check if the operation is finished (completed or failed)
     */
    public function isFinished(): bool
    {
        return $this->isCompleted() || $this->isFailed();
    }

    /**
     * Check if the operation completed successfully
     */
    public function isSuccessful(): bool
    {
        return $this->isCompleted();
    }

    /**
     * Get the completion percentage as a float (0.0 to 100.0)
     */
    public function getCompletionPercentage(): float
    {
        return (float) ($this->completion ?? 0);
    }

    /**
     * Get a human-readable status description
     */
    public function getStatusDescription(): string
    {
        $status = match ($this->workflowState) {
            self::STATE_QUEUED => 'Waiting to start',
            self::STATE_RUNNING => 'In progress',
            self::STATE_COMPLETED => 'Completed successfully',
            self::STATE_FAILED => 'Failed',
            default => 'Unknown status'
        };

        if ($this->completion !== null && $this->isRunning()) {
            $status .= " ({$this->completion}%)";
        }

        if ($this->message !== null) {
            $status .= " - {$this->message}";
        }

        return $status;
    }

    /**
     * Wait for operation to complete with configurable polling
     * @param int $maxWaitSeconds Maximum time to wait in seconds (default: 300 = 5 minutes)
     * @param int $intervalSeconds Initial polling interval in seconds (default: 2)
     * @return Progress The completed progress object
     * @throws CanvasApiException If operation times out or fails
     */
    public function waitForCompletion(int $maxWaitSeconds = 300, int $intervalSeconds = 2): Progress
    {
        $startTime = time();
        $currentInterval = $intervalSeconds;

        // Check if already finished before starting the polling loop
        if ($this->isFinished()) {
            if ($this->isFailed()) {
                throw new CanvasApiException("Operation failed: {$this->message}");
            }
            return $this;
        }

        while (time() - $startTime < $maxWaitSeconds) {
            $this->refresh();

            // @phpstan-ignore-next-line This check is needed after refresh() updates the state
            if ($this->isFinished()) {
                if ($this->isFailed()) {
                    throw new CanvasApiException("Operation failed: {$this->message}");
                }
                return $this;
            }

            sleep($currentInterval);

            // Exponential backoff for long-running operations
            if (time() - $startTime > 60) {
                $currentInterval = min(10, (int) ($currentInterval * 1.2));
            }
        }

        throw new CanvasApiException("Progress polling timed out after {$maxWaitSeconds} seconds");
    }

    /**
     * Static utility to poll a progress until completion
     * @param int $id Progress ID to poll
     * @param int $maxWaitSeconds Maximum time to wait in seconds (default: 300)
     * @param int $intervalSeconds Initial polling interval in seconds (default: 2)
     * @return Progress The completed progress object
     * @throws CanvasApiException
     */
    public static function pollUntilComplete(int $id, int $maxWaitSeconds = 300, int $intervalSeconds = 2): Progress
    {
        $progress = self::find($id);
        return $progress->waitForCompletion($maxWaitSeconds, $intervalSeconds);
    }

    /**
     * Get the unique identifier
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the unique identifier
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the context ID
     */
    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    /**
     * Set the context ID
     */
    public function setContextId(?int $contextId): void
    {
        $this->contextId = $contextId;
    }

    /**
     * Get the context type
     */
    public function getContextType(): ?string
    {
        return $this->contextType;
    }

    /**
     * Set the context type
     */
    public function setContextType(?string $contextType): void
    {
        $this->contextType = $contextType;
    }

    /**
     * Get the user ID
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Set the user ID
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Get the operation tag
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * Set the operation tag
     */
    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * Get the completion percentage
     */
    public function getCompletion(): ?int
    {
        return $this->completion;
    }

    /**
     * Set the completion percentage
     */
    public function setCompletion(?int $completion): void
    {
        $this->completion = $completion;
    }

    /**
     * Get the workflow state
     */
    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    /**
     * Set the workflow state
     */
    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    /**
     * Get the creation date
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set the creation date
     */
    public function setCreatedAt(?DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get the last update date
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set the last update date
     */
    public function setUpdatedAt(?DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get the status message
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set the status message
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * Get the operation results
     */
    public function getResults(): mixed
    {
        return $this->results;
    }

    /**
     * Set the operation results
     */
    public function setResults(mixed $results): void
    {
        $this->results = $results;
    }

    /**
     * Get the progress URL
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set the progress URL
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * Required by ApiInterface - fetch all progress objects (not typically used)
     * @param mixed[] $params
     * @return Progress[]
     * @throws CanvasApiException
     */
    public static function get(array $params = []): array
    {
        throw new CanvasApiException(
            'Progress API does not support listing all progress objects. Use find() with specific ID.'
        );
    }

    /**
     * Get the API endpoint for this resource
     * @return string
     */
    protected static function getEndpoint(): string
    {
        return 'progress';
    }
}
