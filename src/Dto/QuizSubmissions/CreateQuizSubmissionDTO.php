<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\QuizSubmissions;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * DTO for creating quiz submissions in Canvas LMS
 *
 * This DTO handles the data structure for starting a new quiz submission.
 * It provides access code and preview mode configuration.
 *
 * Usage Example:
 * ```php
 * $createDto = new CreateQuizSubmissionDTO([
 *     'access_code' => 'secret123',
 *     'preview' => false
 * ]);
 *
 * $submission = QuizSubmission::create($createDto);
 * ```
 *
 * @package CanvasLMS\Dto\QuizSubmissions
 */
class CreateQuizSubmissionDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for quiz submissions
     */
    protected string $apiPropertyName = 'quiz_submission';

    /**
     * Access code for the quiz (if required)
     */
    public ?string $accessCode = null;

    /**
     * Whether this is a preview submission (for instructors)
     */
    public ?bool $preview = null;

    /**
     * Get access code
     */
    public function getAccessCode(): ?string
    {
        return $this->accessCode;
    }

    /**
     * Set access code
     *
     * @param string|null $accessCode Quiz access code
     */
    public function setAccessCode(?string $accessCode): void
    {
        $this->accessCode = $accessCode;
    }

    /**
     * Get preview mode
     */
    public function getPreview(): ?bool
    {
        return $this->preview;
    }

    /**
     * Set preview mode
     *
     * @param bool|null $preview Whether this is a preview submission
     */
    public function setPreview(?bool $preview): void
    {
        $this->preview = $preview;
    }
}
