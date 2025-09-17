<?php

declare(strict_types=1);

namespace CanvasLMS\Api\ContentMigrations;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\ContentMigrations\UpdateMigrationIssueDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use DateTime;

/**
 * Canvas LMS Migration Issues API
 *
 * Represents issues that occur during content migrations. Issues can be warnings,
 * errors, or todos that need resolution. This is a nested resource under ContentMigration.
 *
 * @see https://canvas.instructure.com/doc/api/content_migrations.html
 *
 * @package CanvasLMS\Api\ContentMigrations
 */
class MigrationIssue extends AbstractBaseApi
{
    /**
     * Workflow state constants
     */
    public const STATE_ACTIVE = 'active';
    public const STATE_RESOLVED = 'resolved';

    /**
     * Issue type constants
     */
    public const TYPE_TODO = 'todo';
    public const TYPE_WARNING = 'warning';
    public const TYPE_ERROR = 'error';

    /**
     * The unique identifier for the issue
     */
    public ?int $id = null;

    /**
     * API url to the content migration
     */
    public ?string $contentMigrationUrl = null;

    /**
     * Description of the issue for the end-user
     */
    public ?string $description = null;

    /**
     * Current state of the issue: active, resolved
     */
    public ?string $workflowState = null;

    /**
     * HTML URL to the Canvas page to investigate the issue
     */
    public ?string $fixIssueHtmlUrl = null;

    /**
     * Severity of the issue: todo, warning, error
     */
    public ?string $issueType = null;

    /**
     * Link to a Canvas error report if present (requires permissions)
     */
    public ?string $errorReportHtmlUrl = null;

    /**
     * Site administrator error message (requires permissions)
     */
    public ?string $errorMessage = null;

    /**
     * When the issue was created
     */
    public ?DateTime $createdAt = null;

    /**
     * When the issue was last updated
     */
    public ?DateTime $updatedAt = null;

    /**
     * Set the parent content migration
     *
     * @param ContentMigration $_contentMigration
     * @deprecated This method is no longer used and will be removed in a future version
     */
    public static function setContentMigration(ContentMigration $_contentMigration): void
    {
        // No-op: This method is deprecated and does nothing
    }

    /**
     * Check if content migration is set
     *
     * @return bool
     * @deprecated This method is no longer used and will be removed in a future version
     */
    public static function checkContentMigration(): bool
    {
        return false;
    }

    /**
     * Get a single migration issue by ID (not supported - use findInMigration instead)
     *
     * @param int $id Migration issue ID
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): self
    {
        throw new CanvasApiException(
            'Direct find() not supported for MigrationIssue. Use findInMigration() instead.'
        );
    }

    /**
     * Get a single migration issue by ID in a specific migration
     *
     * @param string $contextType Context type (accounts, courses, groups, users)
     * @param int $contextId Context ID
     * @param int $migrationId Content migration ID
     * @param int $id Migration issue ID
     * @return self
     * @throws CanvasApiException
     */
    public static function findInMigration(string $contextType, int $contextId, int $migrationId, int $id): self
    {
        self::checkApiClient();

        $endpoint = sprintf(
            '%s/%d/content_migrations/%d/migration_issues/%d',
            $contextType,
            $contextId,
            $migrationId,
            $id
        );
        $response = self::$apiClient->get($endpoint);
        $data = self::parseJsonResponse($response);

        if (!is_array($data)) {
            throw new CanvasApiException('Invalid response data from API');
        }

        return new self($data);
    }

    /**
     * List migration issues (not supported - use fetchAllInMigration instead)
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<MigrationIssue>
     * @throws CanvasApiException
     */
    public static function get(array $params = []): array
    {
        throw new CanvasApiException(
            'Direct get() not supported for MigrationIssue. Use fetchAllInMigration() instead.'
        );
    }

    /**
     * Get all pages of records (interface method)
     * @param array<string, mixed> $params
     * @return array<MigrationIssue>
     * @throws CanvasApiException
     */
    public static function all(array $params = []): array
    {
        throw new CanvasApiException(
            'Direct all() not supported for MigrationIssue. Use allInMigration() instead.'
        );
    }

    /**
     * List migration issues in a specific migration
     *
     * @param string $contextType Context type (accounts, courses, groups, users)
     * @param int $contextId Context ID
     * @param int $migrationId Content migration ID
     * @param array<string, mixed> $params Query parameters
     * @return array<MigrationIssue>
     * @throws CanvasApiException
     */
    public static function fetchAllInMigration(
        string $contextType,
        int $contextId,
        int $migrationId,
        array $params = []
    ): array {
        return self::allInMigration($contextType, $contextId, $migrationId, $params);
    }

    /**
     * Get paginated migration issues
     *
     * @param string $contextType Context type (accounts, courses, groups, users)
     * @param int $contextId Context ID
     * @param int $migrationId Content migration ID
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function paginateInMigration(
        string $contextType,
        int $contextId,
        int $migrationId,
        array $params = []
    ): PaginatedResponse {
        $endpoint = sprintf(
            '%s/%d/content_migrations/%d/migration_issues',
            $contextType,
            $contextId,
            $migrationId
        );
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Get all pages of migration issues
     *
     * @param string $contextType Context type (accounts, courses, groups, users)
     * @param int $contextId Context ID
     * @param int $migrationId Content migration ID
     * @param array<string, mixed> $params Query parameters
     * @return array<MigrationIssue>
     * @throws CanvasApiException
     */
    public static function allInMigration(
        string $contextType,
        int $contextId,
        int $migrationId,
        array $params = []
    ): array {
        $endpoint = sprintf(
            '%s/%d/content_migrations/%d/migration_issues',
            $contextType,
            $contextId,
            $migrationId
        );
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();
        return array_map(fn($data) => new self($data), $allData);
    }

    /**
     * Update migration issue
     *
     * @param string $contextType Context type (accounts, courses, groups, users)
     * @param int $contextId Context ID
     * @param int $migrationId Content migration ID
     * @param int $id Migration issue ID
     * @param array<string, mixed>|UpdateMigrationIssueDTO $data Update data
     * @return self
     * @throws CanvasApiException
     */
    public static function update(
        string $contextType,
        int $contextId,
        int $migrationId,
        int $id,
        array|UpdateMigrationIssueDTO $data
    ): self {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateMigrationIssueDTO($data);
        }

        $endpoint = sprintf(
            '%s/%d/content_migrations/%d/migration_issues/%d',
            $contextType,
            $contextId,
            $migrationId,
            $id
        );
        $response = self::$apiClient->put($endpoint, ['multipart' => $data->toApiArray()]);
        $issueData = self::parseJsonResponse($response);

        if (!is_array($issueData)) {
            throw new CanvasApiException('Invalid response data from API');
        }

        return new self($issueData);
    }

    /**
     * Resolve this issue
     *
     * @return bool
     * @throws CanvasApiException
     */
    public function resolve(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Issue ID is required');
        }

        // Extract context from content migration URL
        if (
            $this->contentMigrationUrl && preg_match(
                '/(\w+)\/(\d+)\/content_migrations\/(\d+)/',
                $this->contentMigrationUrl,
                $matches
            )
        ) {
            $contextType = $matches[1];
            $contextId = (int) $matches[2];
            $migrationId = (int) $matches[3];

            try {
                $updated = self::update(
                    $contextType,
                    $contextId,
                    $migrationId,
                    $this->id,
                    ['workflow_state' => self::STATE_RESOLVED]
                );
                // Copy properties from the updated object
                $this->workflowState = $updated->getWorkflowState();
                $this->updatedAt = $updated->getUpdatedAt();
                return true;
            } catch (\Exception $_e) {
                return false;
            }
        }

        throw new CanvasApiException('Unable to determine context from content migration URL');
    }

    /**
     * Reactivate this issue
     *
     * @return bool
     * @throws CanvasApiException
     */
    public function reactivate(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Issue ID is required');
        }

        // Extract context from content migration URL
        if (
            $this->contentMigrationUrl && preg_match(
                '/(\w+)\/(\d+)\/content_migrations\/(\d+)/',
                $this->contentMigrationUrl,
                $matches
            )
        ) {
            $contextType = $matches[1];
            $contextId = (int) $matches[2];
            $migrationId = (int) $matches[3];

            try {
                $updated = self::update(
                    $contextType,
                    $contextId,
                    $migrationId,
                    $this->id,
                    ['workflow_state' => self::STATE_ACTIVE]
                );
                // Copy properties from the updated object
                $this->workflowState = $updated->getWorkflowState();
                $this->updatedAt = $updated->getUpdatedAt();
                return true;
            } catch (\Exception $_e) {
                return false;
            }
        }

        throw new CanvasApiException('Unable to determine context from content migration URL');
    }

    /**
     * Check if the issue is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->workflowState === self::STATE_ACTIVE;
    }

    /**
     * Check if the issue is resolved
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->workflowState === self::STATE_RESOLVED;
    }

    /**
     * Check if this is an error
     *
     * @return bool
     */
    public function isError(): bool
    {
        return $this->issueType === self::TYPE_ERROR;
    }

    /**
     * Check if this is a warning
     *
     * @return bool
     */
    public function isWarning(): bool
    {
        return $this->issueType === self::TYPE_WARNING;
    }

    /**
     * Check if this is a todo
     *
     * @return bool
     */
    public function isTodo(): bool
    {
        return $this->issueType === self::TYPE_TODO;
    }

    // Getter and setter methods

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getContentMigrationUrl(): ?string
    {
        return $this->contentMigrationUrl;
    }

    public function setContentMigrationUrl(?string $contentMigrationUrl): void
    {
        $this->contentMigrationUrl = $contentMigrationUrl;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    public function getFixIssueHtmlUrl(): ?string
    {
        return $this->fixIssueHtmlUrl;
    }

    public function setFixIssueHtmlUrl(?string $fixIssueHtmlUrl): void
    {
        $this->fixIssueHtmlUrl = $fixIssueHtmlUrl;
    }

    public function getIssueType(): ?string
    {
        return $this->issueType;
    }

    public function setIssueType(?string $issueType): void
    {
        $this->issueType = $issueType;
    }

    public function getErrorReportHtmlUrl(): ?string
    {
        return $this->errorReportHtmlUrl;
    }

    public function setErrorReportHtmlUrl(?string $errorReportHtmlUrl): void
    {
        $this->errorReportHtmlUrl = $errorReportHtmlUrl;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get the API endpoint for this resource
     * Note: MigrationIssue is a nested resource under ContentMigration
     * and requires context, so this returns a placeholder that should not be used directly
     * @return string
     * @throws CanvasApiException
     */
    protected static function getEndpoint(): string
    {
        throw new CanvasApiException(
            'MigrationIssue does not support direct endpoint access. ' .
            'Use context-specific methods like fetchAllInMigration()'
        );
    }
}
