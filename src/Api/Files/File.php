<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Files;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Config;
use CanvasLMS\Dto\Files\UploadFileDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginationResult;
use Exception;

/**
 * File Class
 *
 * Represents a file in the Canvas LMS. This class provides methods to upload,
 * find, and fetch files from the Canvas LMS system. It supports Canvas's
 * 3-step file upload process and multiple upload contexts.
 *
 * Usage:
 *
 * ```php
 * // Upload a file to a course
 * $fileData = [
 *     'name' => 'document.pdf',
 *     'size' => 1024000,
 *     'content_type' => 'application/pdf',
 *     'parent_folder_id' => 123,
 *     'file' => '/path/to/document.pdf'
 * ];
 * $file = File::uploadToCourse(456, $fileData);
 *
 * // Upload via URL
 * $urlData = [
 *     'name' => 'image.jpg',
 *     'url' => 'https://example.com/image.jpg'
 * ];
 * $file = File::uploadUrlToCourse(456, $urlData);
 *
 * // Find a file by ID
 * $file = File::find(789);
 *
 * // Get file download URL
 * $downloadUrl = $file->getDownloadUrl();
 *
 * // Fetch all files from current user (first page only)
 * $files = File::get();
 *
 * // Get paginated files
 * $paginationResult = File::paginate(['per_page' => 10]);
 * $files = $paginationResult->getData();
 * $hasNext = $paginationResult->hasNext();
 *
 * // Fetch all files from all pages
 * $allFiles = File::all(['per_page' => 50]);
 * ```
 *
 * @package CanvasLMS\Api\Files
 */
class File extends AbstractBaseApi
{
    /**
     * The unique identifier for the file
     *
     * @var int|null
     */
    public ?int $id = null;

    /**
     * The UUID of the file
     *
     * @var string|null
     */
    public ?string $uuid = null;

    /**
     * The folder_id of the folder containing the file
     *
     * @var int|null
     */
    public ?int $folderId = null;

    /**
     * The display name of the file
     *
     * @var string|null
     */
    public ?string $displayName = null;

    /**
     * The filename of the file
     *
     * @var string|null
     */
    public ?string $filename = null;

    /**
     * The content-type of the file
     *
     * @var string|null
     */
    public ?string $contentType = null;

    /**
     * The URL to download the file (not present for file upload requests)
     *
     * @var string|null
     */
    public ?string $url = null;

    /**
     * The file size in bytes
     *
     * @var int|null
     */
    public ?int $size = null;

    /**
     * The datetime the file was created
     *
     * @var \DateTime|null
     */
    public ?\DateTime $createdAt = null;

    /**
     * The datetime the file was last updated
     *
     * @var \DateTime|null
     */
    public ?\DateTime $updatedAt = null;

    /**
     * The datetime the file will be deleted (not present for file upload requests)
     *
     * @var \DateTime|null
     */
    public ?\DateTime $unlockAt = null;

    /**
     * Whether the file is locked
     *
     * @var bool
     */
    public bool $locked = false;

    /**
     * Whether the file is hidden
     *
     * @var bool
     */
    public bool $hidden = false;

    /**
     * The datetime the file was locked at
     *
     * @var \DateTime|null
     */
    public ?\DateTime $lockAt = null;

    /**
     * Whether the file is locked for the user
     *
     * @var bool
     */
    public bool $lockedForUser = false;

    /**
     * Explanation of why the file is locked
     *
     * @var string|null
     */
    public ?string $lockExplanation = null;

    /**
     * A URL to the file preview
     *
     * @var string|null
     */
    public ?string $previewUrl = null;

    /**
     * An abbreviated URL to the file that can be inserted into the rich content editor
     *
     * @var string|null
     */
    public ?string $thumbnailUrl = null;

    /**
     * Context type where the file belongs
     *
     * @var string|null
     */
    protected ?string $contextType = null;

    /**
     * Context ID where the file belongs
     *
     * @var int|null
     */
    protected ?int $contextId = null;

    /**
     * Get the context type
     *
     * @return string|null
     */
    public function getContextType(): ?string
    {
        return $this->contextType;
    }

    /**
     * Set the context type
     *
     * @param string|null $contextType
     *
     * @return void
     */
    public function setContextType(?string $contextType): void
    {
        $this->contextType = $contextType;
    }

    /**
     * Get the context ID
     *
     * @return int|null
     */
    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    /**
     * Set the context ID
     *
     * @param int|null $contextId
     *
     * @return void
     */
    public function setContextId(?int $contextId): void
    {
        $this->contextId = $contextId;
    }

    /**
     * Upload a file to a specific context
     *
     * @param string $contextType Context type ('courses', 'groups', 'users')
     * @param int $contextId Context ID
     * @param array<string, mixed>|UploadFileDTO $fileData File data to upload
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function uploadToContext(
        string $contextType,
        int $contextId,
        array|UploadFileDTO $fileData
    ): self {
        $fileData = is_array($fileData) ? new UploadFileDTO($fileData) : $fileData;
        $endpoint = sprintf('/%s/%d/files', $contextType, $contextId);

        $file = self::performUpload($endpoint, $fileData);

        // Set context information
        $file->contextType = rtrim($contextType, 's');
        $file->contextId = $contextId;

        return $file;
    }

    /**
     * Upload a file to a course
     *
     * @param int $courseId
     * @param UploadFileDTO|mixed[] $fileData
     *
     * @throws Exception
     *
     * @return self
     */
    public static function uploadToCourse(int $courseId, array | UploadFileDTO $fileData): self
    {
        return self::uploadToContext('courses', $courseId, $fileData);
    }

    /**
     * Upload a file to a user
     *
     * @param int $userId
     * @param UploadFileDTO|mixed[] $fileData
     *
     * @throws Exception
     *
     * @return self
     */
    public static function uploadToUser(int $userId, array | UploadFileDTO $fileData): self
    {
        return self::uploadToContext('users', $userId, $fileData);
    }

    /**
     * Upload a file to a group
     *
     * @param int $groupId
     * @param UploadFileDTO|mixed[] $fileData
     *
     * @throws Exception
     *
     * @return self
     */
    public static function uploadToGroup(int $groupId, array | UploadFileDTO $fileData): self
    {
        return self::uploadToContext('groups', $groupId, $fileData);
    }

    /**
     * Upload a file for an assignment submission
     *
     * @param int $courseId
     * @param int $assignmentId
     * @param UploadFileDTO|mixed[] $fileData
     *
     * @throws Exception
     *
     * @return self
     */
    public static function uploadToAssignmentSubmission(
        int $courseId,
        int $assignmentId,
        array | UploadFileDTO $fileData
    ): self {
        $fileData = is_array($fileData) ? new UploadFileDTO($fileData) : $fileData;

        return self::performUpload(
            "/courses/{$courseId}/assignments/{$assignmentId}/submissions/self/files",
            $fileData
        );
    }

    /**
     * Perform the 3-step Canvas file upload process
     *
     * @param string $endpoint
     * @param UploadFileDTO $dto
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    private static function performUpload(string $endpoint, UploadFileDTO $dto): self
    {
        self::checkApiClient();

        $logger = Config::getLogger();
        $logger->info('File Upload: Starting 3-step upload process', [
            'endpoint' => $endpoint,
            'file_name' => $dto->name ?? 'unknown',
            'file_size' => $dto->size ?? null,
            'content_type' => $dto->contentType ?? null,
        ]);

        // Step 1: Initialize upload
        $logger->debug('File Upload: Step 1 - Initializing upload with Canvas API');
        $response = self::getApiClient()->post($endpoint, [
            'multipart' => $dto->toApiArray(),
        ]);

        $uploadData = self::parseJsonResponse($response);
        $logger->debug('File Upload: Step 1 complete - Received upload URL and parameters');

        // Step 2: Upload file data
        $uploadParams = $uploadData['upload_params'] ?? [];
        $uploadUrl = $uploadData['upload_url'] ?? '';

        if (empty($uploadUrl) || empty($uploadParams)) {
            throw new CanvasApiException('Invalid upload response from Canvas API');
        }

        // Build multipart data for actual upload
        $multipartData = [];
        foreach ($uploadParams as $key => $value) {
            $multipartData[] = [
                'name' => $key,
                'contents' => $value,
            ];
        }

        // Add file data as last parameter
        $fileResource = $dto->getFileResource();

        // Ensure we have a valid filename
        if (empty($dto->name)) {
            $logger->error('File Upload: File name is required for upload');

            throw new CanvasApiException('File name is required for upload');
        }

        $multipartData[] = [
            'name' => 'file',
            'contents' => $fileResource,
            'filename' => $dto->name,
        ];

        try {
            $logger->debug('File Upload: Step 2 - Uploading file to external storage', [
                'upload_url_host' => parse_url($uploadUrl, PHP_URL_HOST),
            ]);

            $startTime = microtime(true);
            // Use rawRequest with skipAuth and skipDomainValidation for external uploads (e.g., S3)
            $uploadResponse = self::getApiClient()->rawRequest($uploadUrl, 'POST', [
                'multipart' => $multipartData,
                'skipAuth' => true,  // Don't send Canvas Bearer token to external storage
                'skipDomainValidation' => true,  // Allow external storage URLs
            ]);
            $uploadDuration = microtime(true) - $startTime;

            // Check for HTTP errors in external storage upload
            $statusCode = $uploadResponse->getStatusCode();
            if ($statusCode >= 400) {
                $logger->error('File Upload: External storage upload failed', [
                    'status_code' => $statusCode,
                    'reason' => $uploadResponse->getReasonPhrase(),
                ]);

                throw new CanvasApiException(
                    "External storage upload failed with status {$statusCode}: " .
                    $uploadResponse->getReasonPhrase()
                );
            }

            $logger->debug('File Upload: Step 2 complete - File uploaded to external storage', [
                'duration_ms' => round($uploadDuration * 1000, 2),
                'status_code' => $statusCode,
            ]);
        } finally {
            // Close file resource if it was opened by getFileResource()
            if (is_resource($fileResource)) {
                fclose($fileResource);
            }
        }

        // Step 3: Confirm upload (follow redirect if present)
        $location = $uploadResponse->getHeader('Location')[0] ?? '';
        if (!empty($location)) {
            $logger->debug('File Upload: Step 3 - Following redirect to confirm upload');
            // Use rawRequest with skipAuth and skipDomainValidation for external redirect URLs
            $confirmResponse = self::getApiClient()->rawRequest($location, 'GET', [
                'skipAuth' => true,  // Don't send Canvas Bearer token to external location
                'skipDomainValidation' => true,  // Allow external redirect URLs
            ]);
            $fileData = self::parseJsonResponse($confirmResponse);
        } else {
            $logger->debug('File Upload: Step 3 - Processing upload response directly');
            $fileData = self::parseJsonResponse($uploadResponse);
        }

        $logger->info('File Upload: Successfully completed 3-step upload process', [
            'file_id' => $fileData['id'] ?? null,
            'file_name' => $fileData['display_name'] ?? $fileData['filename'] ?? null,
            'file_size' => $fileData['size'] ?? null,
            'content_type' => $fileData['content-type'] ?? null,
        ]);

        return new self($fileData);
    }

    /**
     * Find a file by ID
     *
     * @param int $id
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkApiClient();

        $response = self::getApiClient()->get("/files/{$id}");

        $fileData = self::parseJsonResponse($response);

        return new self($fileData);
    }

    /**
     * Get the endpoint for this resource.
     * Files default to current user context.
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        return '/users/self/files';
    }

    /**
     * Get first page of files from current user's personal files.
     * Overrides base to set context information.
     *
     * @param array<string, mixed> $params Query parameters
     *
     * @return array<static>
     */
    public static function get(array $params = []): array
    {
        $files = parent::get($params);

        // Set context information on each file
        foreach ($files as $file) {
            $file->contextType = 'user';
            $file->contextId = null; // 'self' user ID is not known at this point
        }

        return $files;
    }

    /**
     * Get paginated files from current user's personal files.
     * Overrides base to set context information.
     *
     * @param array<string, mixed> $params Query parameters
     *
     * @return PaginationResult
     */
    public static function paginate(array $params = []): PaginationResult
    {
        $paginatedResponse = parent::getPaginatedResponse(self::getEndpoint(), $params);

        // Convert data to models with context information
        $data = [];
        foreach ($paginatedResponse->getJsonData() as $item) {
            $file = new static($item);
            $file->contextType = 'user';
            $file->contextId = null; // 'self' user ID is not known at this point
            $data[] = $file;
        }

        return $paginatedResponse->toPaginationResult($data);
    }

    /**
     * Get all files from current user's personal files.
     * Overrides base to set context information.
     *
     * @param array<string, mixed> $params Query parameters
     *
     * @return array<static>
     */
    public static function all(array $params = []): array
    {
        $files = parent::all($params);

        // Set context information on each file
        foreach ($files as $file) {
            $file->contextType = 'user';
            $file->contextId = null; // 'self' user ID is not known at this point
        }

        return $files;
    }

    /**
     * Fetch files from a specific context
     *
     * @param string $contextType Context type ('courses', 'groups', 'users', 'folders')
     * @param int $contextId Context ID (course_id, group_id, user_id, or folder_id)
     * @param array<string, mixed> $params Query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<self>
     */
    public static function fetchByContext(string $contextType, int $contextId, array $params = []): array
    {
        $endpoint = sprintf('%s/%d/files', $contextType, $contextId);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        $allData = [];
        do {
            $data = $paginatedResponse->getJsonData();
            foreach ($data as $item) {
                $allData[] = $item;
            }
            $paginatedResponse = $paginatedResponse->getNext();
        } while ($paginatedResponse !== null);

        $files = array_map(fn ($data) => new self($data), $allData);

        // Set context information on each file
        $singularContext = rtrim($contextType, 's');
        foreach ($files as $file) {
            $file->contextType = $singularContext;
            $file->contextId = $contextId;
        }

        return $files;
    }

    /**
     * Fetch files for a course
     *
     * @param int $courseId
     * @param mixed[] $params
     *
     * @throws CanvasApiException
     *
     * @return File[]
     */
    public static function fetchCourseFiles(int $courseId, array $params = []): array
    {
        return self::fetchByContext('courses', $courseId, $params);
    }

    /**
     * Fetch files for a user
     *
     * @param int $userId
     * @param mixed[] $params
     *
     * @throws CanvasApiException
     *
     * @return File[]
     */
    public static function fetchUserFiles(int $userId, array $params = []): array
    {
        return self::fetchByContext('users', $userId, $params);
    }

    /**
     * Fetch files for a group
     *
     * @param int $groupId
     * @param mixed[] $params
     *
     * @throws CanvasApiException
     *
     * @return File[]
     */
    public static function fetchGroupFiles(int $groupId, array $params = []): array
    {
        return self::fetchByContext('groups', $groupId, $params);
    }

    /**
     * Get file download URL
     *
     * @throws CanvasApiException
     *
     * @return string
     */
    public function getDownloadUrl(): string
    {
        self::checkApiClient();

        $response = self::getApiClient()->get("/files/{$this->id}");

        $fileData = self::parseJsonResponse($response);

        return $fileData['url'] ?? '';
    }

    /**
     * Delete the file
     *
     * @return self
     */
    public function delete(): self
    {
        self::checkApiClient();
        self::getApiClient()->delete("/files/{$this->id}");

        return $this;
    }

    /**
     * Get the file ID
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id ?? 0;
    }

    /**
     * Set the file ID
     *
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the file UUID
     *
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid ?? '';
    }

    /**
     * Set the file UUID
     *
     * @param string $uuid
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * Get the folder ID
     *
     * @return int
     */
    public function getFolderId(): int
    {
        return $this->folderId ?? 0;
    }

    /**
     * Set the folder ID
     *
     * @param int $folderId
     */
    public function setFolderId(int $folderId): void
    {
        $this->folderId = $folderId;
    }

    /**
     * Get the display name
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName ?? '';
    }

    /**
     * Set the display name
     *
     * @param string $displayName
     */
    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    /**
     * Get the filename
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename ?? '';
    }

    /**
     * Set the filename
     *
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * Get the content type
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType ?? '';
    }

    /**
     * Set the content type
     *
     * @param string $contentType
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * Get the file URL
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set the file URL
     *
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get the file size
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size ?? 0;
    }

    /**
     * Set the file size
     *
     * @param int $size
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * Get the created at timestamp
     *
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * Set the created at timestamp
     *
     * @param \DateTime|null $createdAt
     */
    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get the updated at timestamp
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Set the updated at timestamp
     *
     * @param \DateTime|null $updatedAt
     */
    public function setUpdatedAt(?\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Check if the file is locked
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * Set the locked status
     *
     * @param bool $locked
     */
    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    /**
     * Check if the file is hidden
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Set the hidden status
     *
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    // Relationship Methods

    /**
     * Get the folder containing this file
     *
     * NOTE: The Folder API class is not yet implemented in this SDK.
     * This method serves as a placeholder for future implementation.
     *
     * @return null
     */
    public function folder(): ?object
    {
        // Folder class not yet implemented
        return null;
    }
}
