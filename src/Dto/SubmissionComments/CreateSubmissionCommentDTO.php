<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\SubmissionComments;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for creating submission comments in Canvas LMS
 *
 * This DTO handles the creation of new submission comments with all the necessary
 * fields supported by the Canvas Submission Comments API.
 *
 * @package CanvasLMS\Dto\SubmissionComments
 */
class CreateSubmissionCommentDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = 'comment';

    /**
     * Text content of the comment
     */
    public ?string $textComment = null;

    /**
     * Whether the comment should be visible to all group members (for group assignments)
     */
    public ?bool $groupComment = null;

    /**
     * Media comment ID for audio/video comments
     */
    public ?string $mediaCommentId = null;

    /**
     * Media comment type ('audio' or 'video')
     */
    public ?string $mediaCommentType = null;

    /**
     * Array of file IDs to attach to the comment
     *
     * @var array<int>|null
     */
    public ?array $fileIds = null;

    /**
     * Get text comment
     */
    public function getTextComment(): ?string
    {
        return $this->textComment;
    }

    /**
     * Set text comment
     */
    public function setTextComment(?string $textComment): void
    {
        $this->textComment = $textComment;
    }

    /**
     * Get group comment status
     */
    public function getGroupComment(): ?bool
    {
        return $this->groupComment;
    }

    /**
     * Set group comment status
     */
    public function setGroupComment(?bool $groupComment): void
    {
        $this->groupComment = $groupComment;
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
     * Get file IDs
     *
     * @return array<int>|null
     */
    public function getFileIds(): ?array
    {
        return $this->fileIds;
    }

    /**
     * Set file IDs
     *
     * @param array<int>|null $fileIds
     */
    public function setFileIds(?array $fileIds): void
    {
        $this->fileIds = $fileIds;
    }
}
