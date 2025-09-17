<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * SubmissionVersion represents a version of a submission with grade change history.
 * This is a read-only object that does not extend AbstractBaseApi.
 *
 * @see https://canvas.instructure.com/doc/api/gradebook_history.html#SubmissionVersion
 */
class SubmissionVersion
{
    public ?int $assignmentId = null;

    public ?string $assignmentName = null;

    public ?string $body = null;

    public ?string $currentGrade = null;

    public ?string $currentGradedAt = null;

    public ?string $currentGrader = null;

    public ?bool $gradeMatchesCurrentSubmission = null;

    public ?string $gradedAt = null;

    public ?string $grader = null;

    public ?int $graderId = null;

    public ?int $id = null;

    public ?string $newGrade = null;

    public ?string $newGradedAt = null;

    public ?string $newGrader = null;

    public ?string $previousGrade = null;

    public ?string $previousGradedAt = null;

    public ?string $previousGrader = null;

    public ?float $score = null;

    public ?string $userName = null;

    public ?string $submissionType = null;

    public ?string $url = null;

    public ?int $userId = null;

    public ?string $workflowState = null;

    /**
     * Constructor to hydrate the object from API response.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->assignmentId = isset($data['assignment_id']) ? (int) $data['assignment_id'] : null;
        $this->assignmentName = $data['assignment_name'] ?? null;
        $this->body = $data['body'] ?? null;
        $this->currentGrade = $data['current_grade'] ?? null;
        $this->currentGradedAt = $data['current_graded_at'] ?? null;
        $this->currentGrader = $data['current_grader'] ?? null;
        $this->gradeMatchesCurrentSubmission = $data['grade_matches_current_submission'] ?? null;
        $this->gradedAt = $data['graded_at'] ?? null;
        $this->grader = $data['grader'] ?? null;
        $this->graderId = isset($data['grader_id']) ? (int) $data['grader_id'] : null;
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->newGrade = $data['new_grade'] ?? null;
        $this->newGradedAt = $data['new_graded_at'] ?? null;
        $this->newGrader = $data['new_grader'] ?? null;
        $this->previousGrade = $data['previous_grade'] ?? null;
        $this->previousGradedAt = $data['previous_graded_at'] ?? null;
        $this->previousGrader = $data['previous_grader'] ?? null;
        $this->score = isset($data['score']) ? (float) $data['score'] : null;
        $this->userName = $data['user_name'] ?? null;
        $this->submissionType = $data['submission_type'] ?? null;
        $this->url = $data['url'] ?? null;
        $this->userId = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $this->workflowState = $data['workflow_state'] ?? null;
    }

    /**
     * Create a SubmissionVersion from an array.
     *
     * @param array<string, mixed> $data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Check if this version represents a grade change.
     *
     * @return bool
     */
    public function hasGradeChange(): bool
    {
        return $this->previousGrade !== null &&
               $this->newGrade !== null &&
               $this->previousGrade !== $this->newGrade;
    }

    /**
     * Get the grade value (for simplified feed responses).
     * Returns new_grade if available, otherwise current_grade.
     *
     * @return string|null
     */
    public function getGrade(): ?string
    {
        return $this->newGrade ?? $this->currentGrade;
    }

    /**
     * Check if the submission is graded.
     *
     * @return bool
     */
    public function isGraded(): bool
    {
        return $this->workflowState === 'graded';
    }

    /**
     * Check if the submission is unsubmitted.
     *
     * @return bool
     */
    public function isUnsubmitted(): bool
    {
        return $this->workflowState === 'unsubmitted';
    }

    /**
     * Get the graded timestamp.
     * Returns new_graded_at if available, otherwise graded_at.
     *
     * @return string|null
     */
    public function getGradedTimestamp(): ?string
    {
        return $this->newGradedAt ?? $this->gradedAt;
    }

    /**
     * Get the grader name.
     * Returns new_grader if available, otherwise grader.
     *
     * @return string|null
     */
    public function getGraderName(): ?string
    {
        return $this->newGrader ?? $this->grader;
    }
}
