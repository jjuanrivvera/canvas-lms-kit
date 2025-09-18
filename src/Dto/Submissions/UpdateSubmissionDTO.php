<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Submissions;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating submissions in Canvas LMS
 *
 * This DTO handles updating/grading existing submissions with all the necessary
 * fields supported by the Canvas Submissions API.
 *
 * @package CanvasLMS\Dto\Submissions
 */
class UpdateSubmissionDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = 'submission';

    /**
     * The grade to assign to the submission (string to support letter grades)
     */
    public ?string $postedGrade = null;

    /**
     * Whether to excuse this submission (removes penalty and hides from student gradebook)
     */
    public ?bool $excuse = null;

    /**
     * Rubric assessment data
     *
     * @var array<string, mixed>|null
     */
    public ?array $rubricAssessment = null;

    /**
     * Comment to add to the submission
     */
    public ?string $comment = null;

    /**
     * Whether the comment should be visible to all group members (for group assignments)
     */
    public ?bool $groupComment = null;

    /**
     * Media comment ID for audio/video feedback
     */
    public ?string $mediaCommentId = null;

    /**
     * Media comment type ('audio' or 'video')
     */
    public ?string $mediaCommentType = null;

    /**
     * Additional attempt count to grant
     */
    public ?int $extraAttempts = null;

    /**
     * Whether to mark the submission as late
     */
    public ?bool $late = null;

    /**
     * Points deducted for late submission
     */
    public ?float $pointsDeducted = null;

    /**
     * Get posted grade
     */
    public function getPostedGrade(): ?string
    {
        return $this->postedGrade;
    }

    /**
     * Set posted grade
     */
    public function setPostedGrade(?string $postedGrade): void
    {
        $this->postedGrade = $postedGrade;
    }

    /**
     * Get excuse status
     */
    public function getExcuse(): ?bool
    {
        return $this->excuse;
    }

    /**
     * Set excuse status
     */
    public function setExcuse(?bool $excuse): void
    {
        $this->excuse = $excuse;
    }

    /**
     * Get rubric assessment
     *
     * @return array<string, mixed>|null
     */
    public function getRubricAssessment(): ?array
    {
        return $this->rubricAssessment;
    }

    /**
     * Set rubric assessment
     *
     * @param array<string, mixed>|null $rubricAssessment
     */
    public function setRubricAssessment(?array $rubricAssessment): void
    {
        $this->rubricAssessment = $rubricAssessment;
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
     * Get extra attempts
     */
    public function getExtraAttempts(): ?int
    {
        return $this->extraAttempts;
    }

    /**
     * Set extra attempts
     */
    public function setExtraAttempts(?int $extraAttempts): void
    {
        $this->extraAttempts = $extraAttempts;
    }

    /**
     * Get late status
     */
    public function getLate(): ?bool
    {
        return $this->late;
    }

    /**
     * Set late status
     */
    public function setLate(?bool $late): void
    {
        $this->late = $late;
    }

    /**
     * Get points deducted
     */
    public function getPointsDeducted(): ?float
    {
        return $this->pointsDeducted;
    }

    /**
     * Set points deducted
     */
    public function setPointsDeducted(?float $pointsDeducted): void
    {
        $this->pointsDeducted = $pointsDeducted;
    }
}
