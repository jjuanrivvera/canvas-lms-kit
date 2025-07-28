<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Submissions;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;
use InvalidArgumentException;

/**
 * Data Transfer Object for creating submissions in Canvas LMS
 *
 * This DTO handles the creation of new submissions with all the necessary
 * fields supported by the Canvas Submissions API.
 *
 * @package CanvasLMS\Dto\Submissions
 */
class CreateSubmissionDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = 'submission';

    /**
     * Submission type (required)
     * Valid values: online_text_entry, online_url, online_upload, media_recording, basic_lti_launch, student_annotation
     */
    public ?string $submissionType = null;

    /**
     * Text content for online_text_entry submissions (HTML content)
     */
    public ?string $body = null;

    /**
     * URL for online_url submissions
     */
    public ?string $url = null;

    /**
     * Array of file IDs for online_upload submissions
     * @var array<int>|null
     */
    public ?array $fileIds = null;

    /**
     * Media comment ID for media submissions
     */
    public ?string $mediaCommentId = null;

    /**
     * Media comment type ('audio' or 'video')
     */
    public ?string $mediaCommentType = null;

    /**
     * User ID for the submission (when submitting on behalf of another user)
     */
    public ?int $userId = null;

    /**
     * Comment to include with the submission
     */
    public ?string $comment = null;

    /**
     * Get submission type
     */
    public function getSubmissionType(): ?string
    {
        return $this->submissionType;
    }

    /**
     * Set submission type
     * @throws InvalidArgumentException
     */
    public function setSubmissionType(?string $submissionType): void
    {
        if ($submissionType !== null) {
            $validTypes = [
                'online_text_entry',
                'online_url',
                'online_upload',
                'media_recording',
                'basic_lti_launch',
                'student_annotation'
            ];

            if (!in_array($submissionType, $validTypes, true)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid submission type "%s". Valid types are: %s',
                        $submissionType,
                        implode(', ', $validTypes)
                    )
                );
            }
        }

        $this->submissionType = $submissionType;
    }

    /**
     * Get submission body content
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Set submission body content
     * Sanitizes HTML to prevent XSS attacks
     */
    public function setBody(?string $body): void
    {
        if ($body !== null) {
            // Basic HTML sanitization - remove script tags and dangerous attributes
            $body = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $body);
            $body = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $body);
            $body = preg_replace('/javascript:/i', '', $body);
            $body = preg_replace('/vbscript:/i', '', $body);

            // Trim whitespace
            $body = trim($body);
        }

        $this->body = $body;
    }

    /**
     * Get submission URL
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set submission URL
     * @throws InvalidArgumentException
     */
    public function setUrl(?string $url): void
    {
        if ($url !== null) {
            // Validate URL format
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Invalid URL format');
            }

            // Prevent internal/localhost URLs for security
            $parsedUrl = parse_url($url);
            if ($parsedUrl === false) {
                throw new InvalidArgumentException('Invalid URL format');
            }

            $host = strtolower($parsedUrl['host'] ?? '');
            $internalHosts = ['localhost', '127.0.0.1', '0.0.0.0'];
            $isInternal = in_array($host, $internalHosts) ||
                         preg_match('/^192\.168\./', $host) ||
                         preg_match('/^10\./', $host) ||
                         preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $host);

            if ($isInternal) {
                throw new InvalidArgumentException('Internal URLs are not allowed');
            }

            // Ensure HTTPS for security
            if (($parsedUrl['scheme'] ?? '') !== 'https') {
                throw new InvalidArgumentException('Only HTTPS URLs are allowed');
            }
        }

        $this->url = $url;
    }

    /**
     * Get file IDs
     * @return array<int>|null
     */
    public function getFileIds(): ?array
    {
        return $this->fileIds;
    }

    /**
     * Set file IDs
     * @param array<int>|null $fileIds
     * @throws InvalidArgumentException
     */
    public function setFileIds(?array $fileIds): void
    {
        if ($fileIds !== null) {
            // Validate that all file IDs are positive integers
            foreach ($fileIds as $fileId) {
                if (!is_int($fileId) || $fileId <= 0) {
                    throw new InvalidArgumentException('File IDs must be positive integers');
                }
            }

            // Limit the number of files to prevent abuse
            if (count($fileIds) > 50) {
                throw new InvalidArgumentException('Cannot attach more than 50 files to a submission');
            }
        }

        $this->fileIds = $fileIds;
    }

    /**
     * Get media comment ID
     */
    public function getMediaCommentId(): ?string
    {
        return $this->mediaCommentId;
    }

    /**
     * Set media comment ID
     */
    public function setMediaCommentId(?string $mediaCommentId): void
    {
        $this->mediaCommentId = $mediaCommentId;
    }

    /**
     * Get media comment type
     */
    public function getMediaCommentType(): ?string
    {
        return $this->mediaCommentType;
    }

    /**
     * Set media comment type
     */
    public function setMediaCommentType(?string $mediaCommentType): void
    {
        $this->mediaCommentType = $mediaCommentType;
    }

    /**
     * Get user ID
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Set user ID
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Get comment
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Set comment
     */
    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }
}
