<?php

namespace CanvasLMS\Dto\Files;

use Exception;
use CanvasLMS\Dto\AbstractBaseDto;

/**
 * UploadFileDto Class
 *
 * Data Transfer Object for Canvas LMS file upload initiation.
 * This DTO is used for Step 1 of the Canvas 3-step file upload process.
 *
 * Usage:
 *
 * ```php
 * // Direct file upload
 * $uploadDto = new UploadFileDto([
 *     'name' => 'document.pdf',
 *     'size' => 1024000,
 *     'content_type' => 'application/pdf',
 *     'parent_folder_id' => 123,
 *     'file' => '/path/to/document.pdf'
 * ]);
 *
 * // File resource upload
 * $fileResource = fopen('/path/to/file.txt', 'r');
 * $uploadDto = new UploadFileDto([
 *     'name' => 'file.txt',
 *     'size' => filesize('/path/to/file.txt'),
 *     'file' => $fileResource
 * ]);
 * ```
 *
 * @package CanvasLMS\Dto\Files
 */
class UploadFileDto extends AbstractBaseDto
{
    /**
     * The name of the property in the API
     * @var string
     */
    protected string $apiPropertyName = '';

    /**
     * The filename of the file
     * @var string|null
     */
    public ?string $name = null;

    /**
     * The size of the file, in bytes
     * @var int|null
     */
    public ?int $size = null;

    /**
     * The content type of the file
     * @var string|null
     */
    public ?string $contentType = null;

    /**
     * The id of the folder to store the file in
     * @var int|null
     */
    public ?int $parentFolderId = null;

    /**
     * The path of the folder to store the file in
     * @var string|null
     */
    public ?string $parentFolderPath = null;

    /**
     * How to handle duplicate filenames
     * @var string|null
     */
    public ?string $onDuplicate = null;

    /**
     * The file to upload (file path, resource, or stream)
     * @var mixed
     */
    public mixed $file = null;

    /**
     * URL for URL-based uploads
     * @var string|null
     */
    public ?string $url = null;

    /**
     * Whether to submit assignment after upload (for assignment submissions)
     * @var bool|null
     */
    public ?bool $submitAssignment = null;

    /**
     * Convert the DTO to an array for API requests
     *
     * NOTE: This method intentionally differs from AbstractBaseDto::toApiArray()
     * because file uploads require multipart/form-data format without the
     * apiPropertyName prefix (e.g., 'file[name]'). Canvas file upload API
     * expects flat field names in multipart requests.
     *
     * @return mixed[]
     * @throws Exception
     */
    public function toApiArray(): array
    {
        $properties = get_object_vars($this);
        $modifiedProperties = [];

        foreach ($properties as $property => $value) {
            // Skip internal properties and file data
            if (in_array($property, ['apiPropertyName', 'file']) || is_null($value)) {
                continue;
            }

            $propertyName = str_to_snake_case($property);

            $modifiedProperties[] = [
                "name" => $propertyName,
                "contents" => (string) $value
            ];
        }

        return $modifiedProperties;
    }

    /**
     * Get the file resource for upload
     *
     * IMPORTANT: When this method returns a file resource (from fopen),
     * the caller is responsible for closing it after use to prevent
     * resource leaks. The File API class handles this automatically
     * during the upload process.
     *
     * @return mixed File resource, string path, or other file object
     * @throws Exception
     */
    public function getFileResource(): mixed
    {
        if (is_null($this->file)) {
            throw new Exception('File is required for upload');
        }

        // If it's a file path, validate and open it as a resource
        if (is_string($this->file)) {
            // Security check: prevent path traversal attacks
            if (str_contains($this->file, '..')) {
                throw new Exception("Invalid file path: directory traversal not allowed");
            }

            // Validate file path
            $realPath = realpath($this->file);
            if ($realPath === false || !file_exists($realPath)) {
                throw new Exception("Unable to open file: {$this->file}");
            }

            $resource = fopen($realPath, 'r');
            if ($resource === false) {
                throw new Exception("Unable to open file: {$this->file}");
            }
            return $resource;
        }

        // If it's already a resource or stream, return it directly
        if (is_resource($this->file)) {
            return $this->file;
        }

        // For other types (CURLFile, etc.), return as-is
        return $this->file;
    }

    /**
     * Auto-detect content type from file
     * @return void
     */
    public function autoDetectContentType(): void
    {
        if (!is_null($this->contentType)) {
            return;
        }

        if (is_string($this->file) && file_exists($this->file)) {
            if (function_exists('mime_content_type')) {
                $this->contentType = mime_content_type($this->file);
            } elseif (function_exists('finfo_file')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $this->contentType = finfo_file($finfo, $this->file);
                finfo_close($finfo);
            }
        }
    }

    /**
     * Auto-detect file size
     * @return void
     */
    public function autoDetectSize(): void
    {
        if (!is_null($this->size)) {
            return;
        }

        if (is_string($this->file) && file_exists($this->file)) {
            $this->size = filesize($this->file);
        }
    }

    /**
     * Auto-detect filename from file path
     * @return void
     */
    public function autoDetectName(): void
    {
        if (!is_null($this->name)) {
            return;
        }

        if (is_string($this->file) && file_exists($this->file)) {
            $this->name = basename($this->file);
        }
    }

    /**
     * Auto-detect all file properties
     *
     * This method optimizes file system access by checking file existence
     * once and reusing that information for all auto-detection operations.
     *
     * @return void
     */
    public function autoDetectFileProperties(): void
    {
        // Only proceed if file is a string path and exists
        if (!is_string($this->file) || !file_exists($this->file)) {
            return;
        }

        // Get file info once to minimize filesystem calls
        $fileInfo = new \SplFileInfo($this->file);

        // Auto-detect name if not set
        if (is_null($this->name)) {
            $this->name = $fileInfo->getBasename();
        }

        // Auto-detect size if not set
        if (is_null($this->size)) {
            $this->size = $fileInfo->getSize();
        }

        // Auto-detect content type if not set
        // This still requires a separate call as SplFileInfo doesn't provide MIME type
        if (is_null($this->contentType)) {
            if (function_exists('mime_content_type')) {
                $this->contentType = mime_content_type($this->file);
            } elseif (function_exists('finfo_file')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo !== false) {
                    $this->contentType = finfo_file($finfo, $this->file);
                    finfo_close($finfo);
                }
            }
        }
    }

    /**
     * Validate the upload data
     * @return bool
     * @throws Exception
     */
    public function validate(): bool
    {
        if (is_null($this->name) || empty($this->name)) {
            throw new Exception('File name is required');
        }

        if (is_null($this->file) && is_null($this->url)) {
            throw new Exception('Either file or URL is required');
        }

        if (!is_null($this->parentFolderId) && !is_null($this->parentFolderPath)) {
            throw new Exception('Cannot specify both parent_folder_id and parent_folder_path');
        }

        if (!is_null($this->onDuplicate) && !in_array($this->onDuplicate, ['overwrite', 'rename'])) {
            throw new Exception('on_duplicate must be either "overwrite" or "rename"');
        }

        return true;
    }

    /**
     * Get the file name
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the file name
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the file size
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Set the file size
     * @param int|null $size
     */
    public function setSize(?int $size): void
    {
        $this->size = $size;
    }

    /**
     * Get the content type
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * Set the content type
     * @param string|null $contentType
     */
    public function setContentType(?string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * Get the parent folder ID
     * @return int|null
     */
    public function getParentFolderId(): ?int
    {
        return $this->parentFolderId;
    }

    /**
     * Set the parent folder ID
     * @param int|null $parentFolderId
     */
    public function setParentFolderId(?int $parentFolderId): void
    {
        $this->parentFolderId = $parentFolderId;
    }

    /**
     * Get the parent folder path
     * @return string|null
     */
    public function getParentFolderPath(): ?string
    {
        return $this->parentFolderPath;
    }

    /**
     * Set the parent folder path
     * @param string|null $parentFolderPath
     */
    public function setParentFolderPath(?string $parentFolderPath): void
    {
        $this->parentFolderPath = $parentFolderPath;
    }

    /**
     * Get the on duplicate setting
     * @return string|null
     */
    public function getOnDuplicate(): ?string
    {
        return $this->onDuplicate;
    }

    /**
     * Set the on duplicate setting
     * @param string|null $onDuplicate
     */
    public function setOnDuplicate(?string $onDuplicate): void
    {
        $this->onDuplicate = $onDuplicate;
    }

    /**
     * Get the file
     * @return mixed
     */
    public function getFile(): mixed
    {
        return $this->file;
    }

    /**
     * Set the file
     * @param mixed $file
     */
    public function setFile(mixed $file): void
    {
        $this->file = $file;
    }

    /**
     * Get the URL
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set the URL
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * Get the submit assignment flag
     * @return bool|null
     */
    public function getSubmitAssignment(): ?bool
    {
        return $this->submitAssignment;
    }

    /**
     * Set the submit assignment flag
     * @param bool|null $submitAssignment
     */
    public function setSubmitAssignment(?bool $submitAssignment): void
    {
        $this->submitAssignment = $submitAssignment;
    }
}
