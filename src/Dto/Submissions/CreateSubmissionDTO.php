<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Submissions;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

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
     */
    public function setSubmissionType(?string $submissionType): void
    {
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
     */
    public function setBody(?string $body): void
    {
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
     */
    public function setUrl(?string $url): void
    {
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
     */
    public function setFileIds(?array $fileIds): void
    {
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
