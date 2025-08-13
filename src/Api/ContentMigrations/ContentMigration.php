<?php

declare(strict_types=1);

namespace CanvasLMS\Api\ContentMigrations;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Progress\Progress;
use CanvasLMS\Config;
use CanvasLMS\Dto\ContentMigrations\CreateContentMigrationDTO;
use CanvasLMS\Dto\ContentMigrations\UpdateContentMigrationDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Objects\Migrator;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use DateTime;

/**
 * Canvas LMS Content Migrations API
 *
 * Provides functionality to manage content migrations in Canvas LMS.
 * Content migrations enable importing and exporting course content from various sources
 * including course copies, ZIP files, QTI packages, and Common Cartridge files.
 *
 * Supports multiple contexts: Account, Course, Group, and User
 *
 * @see https://canvas.instructure.com/doc/api/content_migrations.html
 *
 * @package CanvasLMS\Api\ContentMigrations
 */
class ContentMigration extends AbstractBaseApi
{
    /**
     * Workflow state constants
     */
    public const STATE_PRE_PROCESSING = 'pre_processing';
    public const STATE_PRE_PROCESSED = 'pre_processed';
    public const STATE_RUNNING = 'running';
    public const STATE_WAITING_FOR_SELECT = 'waiting_for_select';
    public const STATE_COMPLETED = 'completed';
    public const STATE_FAILED = 'failed';

    /**
     * Migration type constants
     */
    public const TYPE_CANVAS_CARTRIDGE = 'canvas_cartridge_importer';
    public const TYPE_COMMON_CARTRIDGE = 'common_cartridge_importer';
    public const TYPE_COURSE_COPY = 'course_copy_importer';
    public const TYPE_ZIP_FILE = 'zip_file_importer';
    public const TYPE_QTI_CONVERTER = 'qti_converter';
    public const TYPE_MOODLE_CONVERTER = 'moodle_converter';

    /**
     * Polling configuration constants
     */
    private const EXPONENTIAL_BACKOFF_THRESHOLD = 60; // seconds
    private const MAX_POLLING_INTERVAL = 10; // seconds
    private const INITIAL_POLLING_INTERVAL_MULTIPLIER = 1.2;

    /**
     * The unique identifier for the migration
     */
    public ?int $id = null;

    /**
     * The type of content migration
     */
    public ?string $migrationType = null;

    /**
     * The name of the content migration type
     */
    public ?string $migrationTypeTitle = null;

    /**
     * API url to the content migration's issues
     */
    public ?string $migrationIssuesUrl = null;

    /**
     * Attachment api object for the uploaded file (may not be present for all migrations)
     * @var array<string, mixed>|null
     */
    public ?array $attachment = null;

    /**
     * The api endpoint for polling the current progress
     */
    public ?string $progressUrl = null;

    /**
     * The user who started the migration
     */
    public ?int $userId = null;

    /**
     * Current state of the content migration
     */
    public ?string $workflowState = null;

    /**
     * When the migration started
     */
    public ?DateTime $startedAt = null;

    /**
     * When the migration finished
     */
    public ?DateTime $finishedAt = null;

    /**
     * File uploading data for pre-processing
     * @var array<string, mixed>|null
     */
    public ?array $preAttachment = null;

    /**
     * Migration settings
     * @var array<string, mixed>|null
     */
    public ?array $settings = null;

    /**
     * Progress percentage (if available)
     */
    public ?int $progressPercentage = null;

    /**
     * Error messages (if any)
     * @var array<string>|null
     */
    public ?array $errors = null;

    /**
     * Date shift options (if applicable)
     * @var array<string, mixed>|null
     */
    public ?array $dateShiftOptions = null;

    /**
     * Selection data for selective imports
     * @var array<string, mixed>|null
     */
    public ?array $selectData = null;

    /**
     * Get a single content migration by ID (not supported - use findByContext instead)
     *
     * @param int $id Content migration ID
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        throw new CanvasApiException(
            'Direct find() not supported for ContentMigration. Use findByContext() instead.'
        );
    }

    /**
     * Get a single content migration by ID in a specific context
     *
     * @param string $contextType Context type (accounts, courses, groups, users)
     * @param int $contextId Context ID
     * @param int $id Content migration ID
     * @return self
     * @throws CanvasApiException
     */
    public static function findByContext(string $contextType, int $contextId, int $id): self
    {
        self::checkApiClient();

        $endpoint = sprintf('%s/%d/content_migrations/%d', $contextType, $contextId, $id);
        $response = self::$apiClient->get($endpoint);
        $data = json_decode($response->getBody()->getContents(), true);

        if (!is_array($data)) {
            throw new CanvasApiException('Invalid response data from API');
        }

        return new self($data);
    }

    /**
     * List content migrations in current account
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<ContentMigration>
     * @throws CanvasApiException
     * @deprecated Use fetchAllPaginated(), fetchPage(), or fetchAllPages() for better pagination support
     */
    public static function fetchAll(array $params = []): array
    {
        return self::fetchAllPages($params);
    }

    /**
     * Get paginated content migrations in current account
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        $accountId = Config::getAccountId();
        return self::getPaginatedResponse(sprintf('accounts/%d/content_migrations', $accountId), $params);
    }

    /**
     * Get a single page of content migrations in current account
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        $paginatedResponse = self::fetchAllPaginated($params);
        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Get all pages of content migrations in current account
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<ContentMigration>
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        $accountId = Config::getAccountId();
        return self::fetchAllPagesAsModels(sprintf('accounts/%d/content_migrations', $accountId), $params);
    }

    /**
     * List content migrations for a specific context
     *
     * @param string $contextType 'accounts', 'courses', 'groups', or 'users'
     * @param int $contextId Account, Course, Group, or User ID
     * @param array<string, mixed> $params Query parameters
     * @return array<ContentMigration>
     * @throws CanvasApiException
     */
    public static function fetchByContext(string $contextType, int $contextId, array $params = []): array
    {
        return self::fetchAllPagesAsModels(sprintf('%s/%d/content_migrations', $contextType, $contextId), $params);
    }

    /**
     * Get paginated content migrations for a specific context
     *
     * @param string $contextType 'accounts', 'courses', 'groups', or 'users'
     * @param int $contextId Account, Course, Group, or User ID
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchByContextPaginated(
        string $contextType,
        int $contextId,
        array $params = []
    ): PaginatedResponse {
        return self::getPaginatedResponse(sprintf('%s/%d/content_migrations', $contextType, $contextId), $params);
    }

    /**
     * Create a new content migration in the default account context
     *
     * @param array<string, mixed>|CreateContentMigrationDTO $data Migration data
     * @return self
     * @throws CanvasApiException
     */
    public static function create(array|CreateContentMigrationDTO $data): self
    {
        $accountId = Config::getAccountId();
        return self::createInContext('accounts', $accountId, $data);
    }

    /**
     * Create a new content migration in a specific context
     *
     * @param string $contextType Context type (accounts, courses, groups, users)
     * @param int $contextId Context ID
     * @param array<string, mixed>|CreateContentMigrationDTO $data Migration data
     * @return self
     * @throws CanvasApiException
     */
    public static function createInContext(
        string $contextType,
        int $contextId,
        array|CreateContentMigrationDTO $data
    ): self {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateContentMigrationDTO($data);
        }

        $endpoint = sprintf('%s/%d/content_migrations', $contextType, $contextId);
        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $migrationData = json_decode($response->getBody()->getContents(), true);

        $migration = new self($migrationData);

        // If there's a pre_attachment error, it will be in the response
        if (
            isset($migrationData['pre_attachment']['message']) &&
            !isset($migrationData['pre_attachment']['upload_url'])
        ) {
            throw new CanvasApiException(
                'File upload initialization failed: ' . $migrationData['pre_attachment']['message']
            );
        }

        return $migration;
    }

    /**
     * Update content migration in the default account context
     *
     * @param int $id Migration ID
     * @param array<string, mixed>|UpdateContentMigrationDTO $data Update data
     * @return self
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateContentMigrationDTO $data): self
    {
        $accountId = Config::getAccountId();
        return self::updateInContext('accounts', $accountId, $id, $data);
    }

    /**
     * Update content migration in a specific context
     *
     * @param string $contextType Context type (accounts, courses, groups, users)
     * @param int $contextId Context ID
     * @param int $id Migration ID
     * @param array<string, mixed>|UpdateContentMigrationDTO $data Update data
     * @return self
     * @throws CanvasApiException
     */
    public static function updateInContext(
        string $contextType,
        int $contextId,
        int $id,
        array|UpdateContentMigrationDTO $data
    ): self {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateContentMigrationDTO($data);
        }

        $endpoint = sprintf('%s/%d/content_migrations/%d', $contextType, $contextId, $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $data->toApiArray()]);
        $migrationData = json_decode($response->getBody()->getContents(), true);

        return new self($migrationData);
    }

    /**
     * Get the Progress object for this migration
     *
     * @return Progress|null
     * @throws CanvasApiException
     */
    public function getProgress(): ?Progress
    {
        if (!$this->progressUrl) {
            return null;
        }

        // Extract progress ID from URL
        if (preg_match('/progress\/(\d+)/', $this->progressUrl, $matches)) {
            $progressId = (int) $matches[1];
            return Progress::find($progressId);
        }

        return null;
    }

    /**
     * Wait for the migration to complete
     *
     * @param int $maxWaitSeconds Maximum time to wait in seconds (default: 300)
     * @param int $intervalSeconds Polling interval in seconds (default: 2)
     * @return self
     * @throws CanvasApiException
     */
    public function waitForCompletion(int $maxWaitSeconds = 300, int $intervalSeconds = 2): self
    {
        $startTime = time();
        $currentInterval = $intervalSeconds;

        while (time() - $startTime < $maxWaitSeconds) {
            // Check if already completed or failed before refreshing
            if ($this->isCompleted() || $this->isFailed()) {
                return $this;
            }

            $this->refresh();

            // Check again after refresh - state may have changed
            /** @phpstan-ignore-next-line */
            if ($this->isCompleted() || $this->isFailed()) {
                return $this;
            }

            // If we have a progress object, check its status too
            $progress = $this->getProgress();
            if ($progress) {
                $progress->refresh();

                // Update our progress percentage if available
                if ($progress->completion !== null) {
                    $this->progressPercentage = $progress->completion;
                }

                // Check if progress indicates failure
                if ($progress->isFailed()) {
                    $this->workflowState = self::STATE_FAILED;
                    if ($progress->message) {
                        $this->errors = [$progress->message];
                    }
                    return $this;
                }
            }

            sleep($currentInterval);

            // Exponential backoff for long-running operations
            if (time() - $startTime > self::EXPONENTIAL_BACKOFF_THRESHOLD) {
                $currentInterval = min(
                    self::MAX_POLLING_INTERVAL,
                    (int) ($currentInterval * self::INITIAL_POLLING_INTERVAL_MULTIPLIER)
                );
            }
        }

        throw new CanvasApiException(
            sprintf('Timeout waiting for migration to complete after %d seconds', $maxWaitSeconds)
        );
    }

    /**
     * Get current completion percentage
     *
     * @return int|null Percentage (0-100) or null if not available
     * @throws CanvasApiException
     */
    public function getCompletionPercentage(): ?int
    {
        $progress = $this->getProgress();
        if ($progress) {
            $progress->refresh();
            return $progress->completion;
        }
        return $this->progressPercentage;
    }

    /**
     * Check if the migration is finished (completed or failed)
     *
     * @return bool
     */
    public function isFinished(): bool
    {
        return in_array($this->workflowState, [self::STATE_COMPLETED, self::STATE_FAILED]);
    }

    /**
     * Wait for the migration to reach a specific state
     *
     * @param string $targetState The state to wait for
     * @param int $maxWaitSeconds Maximum time to wait in seconds (default: 300)
     * @param int $intervalSeconds Polling interval in seconds (default: 2)
     * @return self
     * @throws CanvasApiException
     */
    public function waitForState(string $targetState, int $maxWaitSeconds = 300, int $intervalSeconds = 2): self
    {
        $startTime = time();
        $currentInterval = $intervalSeconds;

        while (time() - $startTime < $maxWaitSeconds) {
            $this->refresh();

            if ($this->workflowState === $targetState) {
                return $this;
            }

            // Exit early if failed
            if ($this->workflowState === self::STATE_FAILED) {
                throw new CanvasApiException('Content migration failed');
            }

            sleep($currentInterval);

            // Exponential backoff for long-running operations
            if (time() - $startTime > self::EXPONENTIAL_BACKOFF_THRESHOLD) {
                $currentInterval = min(
                    self::MAX_POLLING_INTERVAL,
                    (int) ($currentInterval * self::INITIAL_POLLING_INTERVAL_MULTIPLIER)
                );
            }
        }

        throw new CanvasApiException(
            sprintf('Timeout waiting for migration to reach state: %s', $targetState)
        );
    }

    /**
     * Refresh the migration data from the API
     *
     * @return self
     * @throws CanvasApiException
     */
    public function refresh(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Migration ID is required to refresh');
        }

        // We need to determine the context from the migration issues URL
        $pattern = '/(\w+)\/(\d+)\/content_migrations/';
        if ($this->migrationIssuesUrl && preg_match($pattern, $this->migrationIssuesUrl, $matches)) {
            $contextType = $matches[1];
            $contextId = (int) $matches[2];
            $refreshed = self::findByContext($contextType, $contextId, $this->id);
            $this->populate(get_object_vars($refreshed));
        }

        return $this;
    }

    /**
     * Check if the migration is completed
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->workflowState === self::STATE_COMPLETED;
    }

    /**
     * Check if the migration has failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->workflowState === self::STATE_FAILED;
    }

    /**
     * Check if the migration is running
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return in_array($this->workflowState, [
            self::STATE_PRE_PROCESSING,
            self::STATE_PRE_PROCESSED,
            self::STATE_RUNNING
        ]);
    }

    /**
     * Check if the migration is waiting for selection
     *
     * @return bool
     */
    public function isWaitingForSelect(): bool
    {
        return $this->workflowState === self::STATE_WAITING_FOR_SELECT;
    }

    /**
     * Get migration issues
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<MigrationIssue>
     * @throws CanvasApiException
     */
    public function getMigrationIssues(array $params = []): array
    {
        if (!$this->id || !$this->migrationIssuesUrl) {
            throw new CanvasApiException('Migration ID and issues URL are required');
        }

        // Extract context from URL
        if (preg_match('/(\w+)\/(\d+)\/content_migrations/', $this->migrationIssuesUrl, $matches)) {
            $contextType = $matches[1];
            $contextId = (int) $matches[2];

            return MigrationIssue::fetchAllInMigration($contextType, $contextId, $this->id, $params);
        }

        throw new CanvasApiException('Unable to determine context from migration issues URL');
    }

    /**
     * Get selective import data
     *
     * @param string|null $type The type of content to enumerate
     * @return array<mixed>
     * @throws CanvasApiException
     */
    public function getSelectiveData(?string $type = null): array
    {
        if (!$this->id || !$this->migrationIssuesUrl) {
            throw new CanvasApiException('Migration ID is required');
        }

        // Extract context from URL
        if (preg_match('/(\w+)\/(\d+)\/content_migrations/', $this->migrationIssuesUrl, $matches)) {
            $contextType = $matches[1];
            $contextId = (int) $matches[2];

            self::checkApiClient();

            $endpoint = sprintf('%s/%d/content_migrations/%d/selective_data', $contextType, $contextId, $this->id);
            $params = $type ? ['type' => $type] : [];
            $response = self::$apiClient->get($endpoint, ['query' => $params]);

            return json_decode($response->getBody()->getContents(), true);
        }

        throw new CanvasApiException('Unable to determine context from migration issues URL');
    }

    /**
     * Get asset ID mapping (course context only)
     *
     * @param int $courseId Course ID
     * @return array<string, array<string, string>>
     * @throws CanvasApiException
     */
    public static function getAssetIdMapping(int $courseId, int $migrationId): array
    {
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/content_migrations/%d/asset_id_mapping', $courseId, $migrationId);
        $response = self::$apiClient->get($endpoint);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * List available migration systems
     *
     * @param string $contextType Context type (accounts, courses, groups, users)
     * @param int $contextId Context ID
     * @return array<Migrator>
     * @throws CanvasApiException
     */
    public static function getMigrators(string $contextType, int $contextId): array
    {
        self::checkApiClient();

        $endpoint = sprintf('%s/%d/content_migrations/migrators', $contextType, $contextId);
        $response = self::$apiClient->get($endpoint);
        $data = json_decode($response->getBody()->getContents(), true);

        return array_map(fn($item) => new Migrator($item), $data);
    }

    /**
     * List available migration systems in account context
     *
     * @return array<Migrator>
     * @throws CanvasApiException
     */
    public static function getAccountMigrators(): array
    {
        $accountId = Config::getAccountId();
        return self::getMigrators('accounts', $accountId);
    }

    /**
     * Build multipart data for file upload
     *
     * @param array<string, mixed> $uploadParams Upload parameters from pre_attachment
     * @param string $filePath Path to the file to upload
     * @param resource|null &$fileResource Reference to store the file resource
     * @return array<array<string, mixed>> Multipart data array
     * @throws CanvasApiException
     */
    private function buildMultipartData(array $uploadParams, string $filePath, &$fileResource): array
    {
        $multipartData = [];
        // Add upload parameters
        foreach ($uploadParams as $key => $value) {
            $multipartData[] = [
                'name' => $key,
                'contents' => $value
            ];
        }

        // Open file resource
        $fileResource = fopen($filePath, 'rb');
        if (!$fileResource) {
            throw new CanvasApiException("Failed to open file: {$filePath}");
        }

        // Add file data
        $multipartData[] = [
            'name' => 'file',
            'contents' => $fileResource,
            'filename' => basename($filePath)
        ];

        return $multipartData;
    }

    /**
     * Process file upload for migration if needed
     *
     * This handles the 3-step file upload process for migrations that require files.
     * Should be called after create() if the migration has pre_attachment upload data.
     *
     * @param string $filePath Path to the file to upload
     * @return self
     * @throws CanvasApiException
     */
    public function processFileUpload(string $filePath): self
    {
        if (!$this->preAttachment || !isset($this->preAttachment['upload_url'])) {
            throw new CanvasApiException('No file upload URL available. Migration may not require file upload.');
        }

        if (!file_exists($filePath)) {
            throw new CanvasApiException("File not found: {$filePath}");
        }

        $uploadUrl = $this->preAttachment['upload_url'];
        $uploadParams = $this->preAttachment['upload_params'] ?? [];

        $fileResource = null;
        try {
            // Build multipart data for upload
            $multipartData = $this->buildMultipartData($uploadParams, $filePath, $fileResource);

            self::checkApiClient();
            $uploadResponse = self::$apiClient->post($uploadUrl, [
                'multipart' => $multipartData
            ]);

            // Check for HTTP errors
            $statusCode = $uploadResponse->getStatusCode();
            if ($statusCode >= 400) {
                throw new CanvasApiException(
                    "File upload failed with status {$statusCode}: " . $uploadResponse->getReasonPhrase()
                );
            }
        } finally {
            if (is_resource($fileResource)) {
                fclose($fileResource);
            }
        }

        // Refresh migration status after upload
        return $this->refresh();
    }

    /**
     * Check if file upload is required and pending
     *
     * @return bool
     */
    public function isFileUploadPending(): bool
    {
        return isset($this->preAttachment['upload_url']) &&
               !empty($this->preAttachment['upload_url']) &&
               $this->workflowState === self::STATE_PRE_PROCESSING;
    }

    /**
     * Check if file upload had an error
     *
     * @return bool
     */
    public function hasFileUploadError(): bool
    {
        return isset($this->preAttachment['message']) &&
               !isset($this->preAttachment['upload_url']);
    }

    /**
     * Get file upload error message if any
     *
     * @return string|null
     */
    public function getFileUploadError(): ?string
    {
        if ($this->hasFileUploadError()) {
            return $this->preAttachment['message'] ?? 'Unknown upload error';
        }
        return null;
    }

    /**
     * Create a course copy migration
     *
     * @param int $targetCourseId The course to copy content TO
     * @param int $sourceCourseId The course to copy content FROM
     * @param array<string, mixed> $options Additional options
     * @return self
     * @throws CanvasApiException
     */
    public static function createCourseCopy(int $targetCourseId, int $sourceCourseId, array $options = []): self
    {
        $data = array_merge($options, [
            'migration_type' => self::TYPE_COURSE_COPY,
            'settings' => array_merge($options['settings'] ?? [], [
                'source_course_id' => $sourceCourseId
            ])
        ]);

        return self::createInContext('courses', $targetCourseId, $data);
    }

    /**
     * Create a Common Cartridge import migration
     *
     * @param int $courseId The course to import content TO
     * @param string $filePath Path to the .imscc file
     * @param array<string, mixed> $options Additional options
     * @return self
     * @throws CanvasApiException
     */
    public static function importCommonCartridge(int $courseId, string $filePath, array $options = []): self
    {
        if (!file_exists($filePath)) {
            throw new CanvasApiException("File not found: {$filePath}");
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            throw new CanvasApiException("Unable to determine file size: {$filePath}");
        }

        $data = array_merge($options, [
            'migration_type' => self::TYPE_COMMON_CARTRIDGE,
            'pre_attachment' => [
                'name' => basename($filePath),
                'size' => $fileSize
            ]
        ]);

        $migration = self::createInContext('courses', $courseId, $data);

        // Process file upload if needed
        if ($migration->isFileUploadPending()) {
            $migration->processFileUpload($filePath);
        }

        return $migration;
    }

    /**
     * Create a ZIP file import migration
     *
     * @param int $courseId The course to import content TO
     * @param string $filePath Path to the .zip file
     * @param array<string, mixed> $options Additional options
     * @return self
     * @throws CanvasApiException
     */
    public static function importZipFile(int $courseId, string $filePath, array $options = []): self
    {
        if (!file_exists($filePath)) {
            throw new CanvasApiException("File not found: {$filePath}");
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            throw new CanvasApiException("Unable to determine file size: {$filePath}");
        }

        $data = array_merge($options, [
            'migration_type' => self::TYPE_ZIP_FILE,
            'pre_attachment' => [
                'name' => basename($filePath),
                'size' => $fileSize
            ]
        ]);

        $migration = self::createInContext('courses', $courseId, $data);

        // Process file upload if needed
        if ($migration->isFileUploadPending()) {
            $migration->processFileUpload($filePath);
        }

        return $migration;
    }

    /**
     * Create a selective course copy migration
     *
     * @param int $targetCourseId The course to copy content TO
     * @param int $sourceCourseId The course to copy content FROM
     * @param array<string, array<string|int>> $selections Items to copy (e.g., ['assignments' => [1, 2, 3]])
     * @param array<string, mixed> $options Additional options
     * @return self
     * @throws CanvasApiException
     */
    public static function createSelectiveCourseCopy(
        int $targetCourseId,
        int $sourceCourseId,
        array $selections,
        array $options = []
    ): self {
        $data = array_merge($options, [
            'migration_type' => self::TYPE_COURSE_COPY,
            'settings' => array_merge($options['settings'] ?? [], [
                'source_course_id' => $sourceCourseId
            ]),
            'select' => $selections
        ]);

        return self::createInContext('courses', $targetCourseId, $data);
    }

    /**
     * Create a course copy with date shifting
     *
     * @param int $targetCourseId The course to copy content TO
     * @param int $sourceCourseId The course to copy content FROM
     * @param string $oldStartDate Original course start date (Y-m-d)
     * @param string $newStartDate New course start date (Y-m-d)
     * @param array<string, mixed> $options Additional options
     * @return self
     * @throws CanvasApiException
     */
    public static function createCourseCopyWithDateShift(
        int $targetCourseId,
        int $sourceCourseId,
        string $oldStartDate,
        string $newStartDate,
        array $options = []
    ): self {
        $data = array_merge($options, [
            'migration_type' => self::TYPE_COURSE_COPY,
            'settings' => array_merge($options['settings'] ?? [], [
                'source_course_id' => $sourceCourseId
            ]),
            'date_shift_options' => array_merge($options['date_shift_options'] ?? [], [
                'shift_dates' => true,
                'old_start_date' => $oldStartDate,
                'new_start_date' => $newStartDate
            ])
        ]);

        return self::createInContext('courses', $targetCourseId, $data);
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

    public function getMigrationType(): ?string
    {
        return $this->migrationType;
    }

    public function setMigrationType(?string $migrationType): void
    {
        $this->migrationType = $migrationType;
    }

    public function getMigrationTypeTitle(): ?string
    {
        return $this->migrationTypeTitle;
    }

    public function setMigrationTypeTitle(?string $migrationTypeTitle): void
    {
        $this->migrationTypeTitle = $migrationTypeTitle;
    }

    public function getMigrationIssuesUrl(): ?string
    {
        return $this->migrationIssuesUrl;
    }

    public function setMigrationIssuesUrl(?string $migrationIssuesUrl): void
    {
        $this->migrationIssuesUrl = $migrationIssuesUrl;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAttachment(): ?array
    {
        return $this->attachment;
    }

    /**
     * @param array<string, mixed>|null $attachment
     */
    public function setAttachment(?array $attachment): void
    {
        $this->attachment = $attachment;
    }

    public function getProgressUrl(): ?string
    {
        return $this->progressUrl;
    }

    public function setProgressUrl(?string $progressUrl): void
    {
        $this->progressUrl = $progressUrl;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTime $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getFinishedAt(): ?DateTime
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTime $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPreAttachment(): ?array
    {
        return $this->preAttachment;
    }

    /**
     * @param array<string, mixed>|null $preAttachment
     */
    public function setPreAttachment(?array $preAttachment): void
    {
        $this->preAttachment = $preAttachment;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSettings(): ?array
    {
        return $this->settings;
    }

    /**
     * @param array<string, mixed>|null $settings
     */
    public function setSettings(?array $settings): void
    {
        $this->settings = $settings;
    }
}
