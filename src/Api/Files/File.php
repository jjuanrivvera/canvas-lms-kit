<?php

namespace CanvasLMS\Api\Files;

use Exception;
use CanvasLMS\Config;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\Files\UploadFileDto;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginationResult;
use CanvasLMS\Pagination\PaginatedResponse;

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
 * $files = File::fetchAll();
 *
 * // Fetch files with pagination support
 * $paginatedResponse = File::fetchAllPaginated(['per_page' => 10]);
 * $files = $paginatedResponse->getJsonData();
 * $pagination = $paginatedResponse->toPaginationResult($files);
 *
 * // Fetch a specific page of files
 * $paginationResult = File::fetchPage(['page' => 2, 'per_page' => 10]);
 * $files = $paginationResult->getData();
 * $hasNext = $paginationResult->hasNext();
 *
 * // Fetch all files from all pages
 * $allFiles = File::fetchAllPages(['per_page' => 50]);
 * ```
 *
 * @package CanvasLMS\Api\Files
 */
class File extends AbstractBaseApi
{
    /**
     * The unique identifier for the file
     * @var int
     */
    public int $id;

    /**
     * The UUID of the file
     * @var string
     */
    public string $uuid;

    /**
     * The folder_id of the folder containing the file
     * @var int
     */
    public int $folderId;

    /**
     * The display name of the file
     * @var string
     */
    public string $displayName;

    /**
     * The filename of the file
     * @var string
     */
    public string $filename;

    /**
     * The content-type of the file
     * @var string
     */
    public string $contentType;

    /**
     * The URL to download the file (not present for file upload requests)
     * @var string|null
     */
    public ?string $url = null;

    /**
     * The file size in bytes
     * @var int
     */
    public int $size;

    /**
     * The datetime the file was created
     * @var string
     */
    public string $createdAt;

    /**
     * The datetime the file was last updated
     * @var string
     */
    public string $updatedAt;

    /**
     * The datetime the file will be deleted (not present for file upload requests)
     * @var string|null
     */
    public ?string $unlockAt = null;

    /**
     * Whether the file is locked
     * @var bool
     */
    public bool $locked = false;

    /**
     * Whether the file is hidden
     * @var bool
     */
    public bool $hidden = false;

    /**
     * The datetime the file was locked at
     * @var string|null
     */
    public ?string $lockAt = null;

    /**
     * Whether the file is locked for the user
     * @var bool
     */
    public bool $lockedForUser = false;

    /**
     * Explanation of why the file is locked
     * @var string|null
     */
    public ?string $lockExplanation = null;

    /**
     * A URL to the file preview
     * @var string|null
     */
    public ?string $previewUrl = null;

    /**
     * An abbreviated URL to the file that can be inserted into the rich content editor
     * @var string|null
     */
    public ?string $thumbnailUrl = null;

    /**
     * Context type where the file belongs
     * @var string|null
     */
    protected ?string $contextType = null;

    /**
     * Context ID where the file belongs
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
     * @param array<string, mixed>|UploadFileDto $fileData File data to upload
     * @return self
     * @throws CanvasApiException
     */
    public static function uploadToContext(
        string $contextType,
        int $contextId,
        array|UploadFileDto $fileData
    ): self {
        $fileData = is_array($fileData) ? new UploadFileDto($fileData) : $fileData;
        $endpoint = sprintf('/%s/%d/files', $contextType, $contextId);

        $file = self::performUpload($endpoint, $fileData);

        // Set context information
        $file->contextType = rtrim($contextType, 's');
        $file->contextId = $contextId;

        return $file;
    }

    /**
     * Upload a file to a course
     * @param int $courseId
     * @param UploadFileDto|mixed[] $fileData
     * @return self
     * @throws Exception
     */
    public static function uploadToCourse(int $courseId, array | UploadFileDto $fileData): self
    {
        return self::uploadToContext('courses', $courseId, $fileData);
    }

    /**
     * Upload a file to a user
     * @param int $userId
     * @param UploadFileDto|mixed[] $fileData
     * @return self
     * @throws Exception
     */
    public static function uploadToUser(int $userId, array | UploadFileDto $fileData): self
    {
        return self::uploadToContext('users', $userId, $fileData);
    }

    /**
     * Upload a file to a group
     * @param int $groupId
     * @param UploadFileDto|mixed[] $fileData
     * @return self
     * @throws Exception
     */
    public static function uploadToGroup(int $groupId, array | UploadFileDto $fileData): self
    {
        return self::uploadToContext('groups', $groupId, $fileData);
    }

    /**
     * Upload a file for an assignment submission
     * @param int $courseId
     * @param int $assignmentId
     * @param UploadFileDto|mixed[] $fileData
     * @return self
     * @throws Exception
     */
    public static function uploadToAssignmentSubmission(
        int $courseId,
        int $assignmentId,
        array | UploadFileDto $fileData
    ): self {
        $fileData = is_array($fileData) ? new UploadFileDto($fileData) : $fileData;

        return self::performUpload(
            "/courses/{$courseId}/assignments/{$assignmentId}/submissions/self/files",
            $fileData
        );
    }

    /**
     * Perform the 3-step Canvas file upload process
     * @param string $endpoint
     * @param UploadFileDto $dto
     * @return self
     * @throws CanvasApiException
     */
    private static function performUpload(string $endpoint, UploadFileDto $dto): self
    {
        self::checkApiClient();

        // Step 1: Initialize upload
        $response = self::$apiClient->post($endpoint, [
            'multipart' => $dto->toApiArray()
        ]);

        $uploadData = json_decode($response->getBody(), true);

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
                'contents' => $value
            ];
        }

        // Add file data as last parameter
        $fileResource = $dto->getFileResource();

        // Ensure we have a valid filename
        if (empty($dto->name)) {
            throw new CanvasApiException('File name is required for upload');
        }

        $multipartData[] = [
            'name' => 'file',
            'contents' => $fileResource,
            'filename' => $dto->name
        ];

        try {
            $uploadResponse = self::$apiClient->post($uploadUrl, [
                'multipart' => $multipartData
            ]);

            // Check for HTTP errors in external storage upload
            $statusCode = $uploadResponse->getStatusCode();
            if ($statusCode >= 400) {
                throw new CanvasApiException(
                    "External storage upload failed with status {$statusCode}: " .
                    $uploadResponse->getReasonPhrase()
                );
            }
        } finally {
            // Close file resource if it was opened by getFileResource()
            if (is_resource($fileResource)) {
                fclose($fileResource);
            }
        }

        // Step 3: Confirm upload (follow redirect if present)
        $location = $uploadResponse->getHeader('Location')[0] ?? '';
        if (!empty($location)) {
            $confirmResponse = self::$apiClient->get($location);
            $fileData = json_decode($confirmResponse->getBody(), true);
        } else {
            $fileData = json_decode($uploadResponse->getBody(), true);
        }

        return new self($fileData);
    }

    /**
     * Find a file by ID
     * @param int $id
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkApiClient();

        $response = self::$apiClient->get("/files/{$id}");

        $fileData = json_decode($response->getBody(), true);

        return new self($fileData);
    }

    /**
     * Fetch all files from current user's personal files
     *
     * NOTE: Unlike other API classes (Course, User) that fetch from the account
     * level, File::fetchAll() returns the current user's personal files. This is
     * because files in Canvas are inherently context-specific and there is no
     * global "all files" endpoint.
     *
     * For specific contexts, use:
     * - fetchCourseFiles() for course files
     * - fetchUserFiles() for a specific user's files
     * - fetchGroupFiles() for group files
     *
     * @param mixed[] $params Query parameters for filtering/pagination
     * @return File[]
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();

        // Fetch files from current user's personal files as default
        $response = self::$apiClient->get('/users/self/files', [
            'query' => $params
        ]);

        $files = json_decode($response->getBody(), true);

        return array_map(function ($fileData) {
            $file = new self($fileData);
            // Set context information
            $file->contextType = 'user';
            $file->contextId = null; // 'self' user ID is not known at this point
            return $file;
        }, $files);
    }

    /**
     * Fetch files with pagination support from current user's personal files
     * @param mixed[] $params Query parameters for the request
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        return self::getPaginatedResponse('/users/self/files', $params);
    }

    /**
     * Fetch files from a specific page from current user's personal files
     * @param mixed[] $params Query parameters for the request
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        $paginatedResponse = self::fetchAllPaginated($params);
        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch all files from all pages from current user's personal files
     * @param mixed[] $params Query parameters for the request
     * @return File[]
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        $files = self::fetchAllPagesAsModels('/users/self/files', $params);

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
     * @return array<self>
     * @throws CanvasApiException
     */
    public static function fetchByContext(string $contextType, int $contextId, array $params = []): array
    {
        $endpoint = sprintf('%s/%d/files', $contextType, $contextId);
        $files = self::fetchAllPagesAsModels($endpoint, $params);

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
     * @param int $courseId
     * @param mixed[] $params
     * @return File[]
     * @throws CanvasApiException
     */
    public static function fetchCourseFiles(int $courseId, array $params = []): array
    {
        return self::fetchByContext('courses', $courseId, $params);
    }

    /**
     * Fetch files for a user
     * @param int $userId
     * @param mixed[] $params
     * @return File[]
     * @throws CanvasApiException
     */
    public static function fetchUserFiles(int $userId, array $params = []): array
    {
        return self::fetchByContext('users', $userId, $params);
    }

    /**
     * Fetch files for a group
     * @param int $groupId
     * @param mixed[] $params
     * @return File[]
     * @throws CanvasApiException
     */
    public static function fetchGroupFiles(int $groupId, array $params = []): array
    {
        return self::fetchByContext('groups', $groupId, $params);
    }

    /**
     * Get file download URL
     * @return string
     * @throws CanvasApiException
     */
    public function getDownloadUrl(): string
    {
        self::checkApiClient();

        $response = self::$apiClient->get("/files/{$this->id}");

        $fileData = json_decode($response->getBody(), true);

        return $fileData['url'] ?? '';
    }

    /**
     * Delete the file
     * @return bool
     */
    public function delete(): bool
    {
        self::checkApiClient();

        try {
            self::$apiClient->delete("/files/{$this->id}");
            return true;
        } catch (CanvasApiException $e) {
            return false;
        }
    }

    /**
     * Get the file ID
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the file ID
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the file UUID
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Set the file UUID
     * @param string $uuid
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * Get the folder ID
     * @return int
     */
    public function getFolderId(): int
    {
        return $this->folderId;
    }

    /**
     * Set the folder ID
     * @param int $folderId
     */
    public function setFolderId(int $folderId): void
    {
        $this->folderId = $folderId;
    }

    /**
     * Get the display name
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * Set the display name
     * @param string $displayName
     */
    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    /**
     * Get the filename
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Set the filename
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * Get the content type
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Set the content type
     * @param string $contentType
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * Get the file URL
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set the file URL
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get the file size
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Set the file size
     * @param int $size
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * Get the created at timestamp
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Set the created at timestamp
     * @param string $createdAt
     */
    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get the updated at timestamp
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * Set the updated at timestamp
     * @param string $updatedAt
     */
    public function setUpdatedAt(string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Check if the file is locked
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * Set the locked status
     * @param bool $locked
     */
    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    /**
     * Check if the file is hidden
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Set the hidden status
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
