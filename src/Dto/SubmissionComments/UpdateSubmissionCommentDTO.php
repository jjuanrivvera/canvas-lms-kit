<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\SubmissionComments;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating submission comments in Canvas LMS
 *
 * This DTO handles updating existing submission comments with all the necessary
 * fields supported by the Canvas Submission Comments API.
 *
 * @package CanvasLMS\Dto\SubmissionComments
 */
class UpdateSubmissionCommentDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for multipart requests
     */
    protected string $apiPropertyName = 'comment';

    /**
     * Updated text content of the comment
     */
    public ?string $textComment = null;

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
}
