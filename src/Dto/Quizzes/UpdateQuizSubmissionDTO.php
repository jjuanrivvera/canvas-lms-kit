<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Quizzes;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * DTO for updating quiz submissions in Canvas LMS
 *
 * This DTO handles the data structure for updating quiz submission scores,
 * fudge points, and manual grading operations.
 *
 * Usage Example:
 * ```php
 * // Manual scoring
 * $updateDto = new UpdateQuizSubmissionDTO([
 *     'attempt' => 1,
 *     'fudge_points' => 2.5,
 *     'quiz_submissions' => [
 *         [
 *             'attempt' => 1,
 *             'fudge_points' => 2.5,
 *             'questions' => [
 *                 'question_1' => ['score' => 5.0, 'comment' => 'Good answer'],
 *                 'question_2' => ['score' => 3.0, 'comment' => 'Partial credit']
 *             ]
 *         ]
 *     ]
 * ]);
 *
 * $submission = QuizSubmission::update(123, $updateDto);
 * ```
 *
 * @package CanvasLMS\Dto\Quizzes
 */
class UpdateQuizSubmissionDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name for quiz submissions
     */
    protected string $apiPropertyName = 'quiz_submission';

    /**
     * Attempt number being updated
     */
    public ?int $attempt = null;

    /**
     * Fudge points to add or subtract from the score
     */
    public ?float $fudgePoints = null;

    /**
     * Quiz submissions array for bulk updates
     * @var mixed[]|null
     */
    public ?array $quizSubmissions = null;

    /**
     * Questions array for manual scoring
     * @var mixed[]|null
     */
    public ?array $questions = null;

    /**
     * Validation token for submission completion
     */
    public ?string $validationToken = null;

    /**
     * Get attempt number
     */
    public function getAttempt(): ?int
    {
        return $this->attempt;
    }

    /**
     * Set attempt number
     *
     * @param int|null $attempt Attempt number (1-based)
     */
    public function setAttempt(?int $attempt): void
    {
        $this->attempt = $attempt;
    }

    /**
     * Get fudge points
     */
    public function getFudgePoints(): ?float
    {
        return $this->fudgePoints;
    }

    /**
     * Set fudge points
     *
     * @param float|null $fudgePoints Points to add or subtract
     */
    public function setFudgePoints(?float $fudgePoints): void
    {
        $this->fudgePoints = $fudgePoints;
    }

    /**
     * Get quiz submissions array
     * @return mixed[]|null
     */
    public function getQuizSubmissions(): ?array
    {
        return $this->quizSubmissions;
    }

    /**
     * Set quiz submissions array
     *
     * @param mixed[]|null $quizSubmissions Array of submission updates
     */
    public function setQuizSubmissions(?array $quizSubmissions): void
    {
        $this->quizSubmissions = $quizSubmissions;
    }

    /**
     * Get questions array
     * @return mixed[]|null
     */
    public function getQuestions(): ?array
    {
        return $this->questions;
    }

    /**
     * Set questions array
     *
     * @param mixed[]|null $questions Array of question scores and comments
     */
    public function setQuestions(?array $questions): void
    {
        $this->questions = $questions;
    }

    /**
     * Get validation token
     */
    public function getValidationToken(): ?string
    {
        return $this->validationToken;
    }

    /**
     * Set validation token
     *
     * @param string|null $validationToken Token for submission validation
     */
    public function setValidationToken(?string $validationToken): void
    {
        $this->validationToken = $validationToken;
    }
}
