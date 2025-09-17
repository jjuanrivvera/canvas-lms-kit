<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Submissions;

use Exception;
use InvalidArgumentException;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Dto\Submissions\CreateSubmissionDTO;
use CanvasLMS\Dto\Submissions\UpdateSubmissionDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Canvas LMS Submissions API
 *
 * Provides functionality to manage assignment submissions in Canvas LMS.
 * This class handles creating, reading, updating submissions for specific assignments.
 * Requires both Course and Assignment context for all operations.
 *
 * Usage Examples:
 *
 * ```php
 * // Set dual context (required for all operations)
 * $course = Course::find(123);
 * $assignment = Assignment::find(456);
 * Submission::setCourse($course);
 * Submission::setAssignment($assignment);
 *
 * // Submit an assignment
 * $submissionData = [
 *     'submission_type' => 'online_text_entry',
 *     'body' => 'My essay content...'
 * ];
 * $submission = Submission::create($submissionData);
 *
 * // Submit with file upload
 * $submissionData = [
 *     'submission_type' => 'online_upload',
 *     'file_ids' => [123, 456]
 * ];
 * $submission = Submission::create($submissionData);
 *
 * // Submit with URL
 * $submissionData = [
 *     'submission_type' => 'online_url',
 *     'url' => 'https://example.com/my-project'
 * ];
 * $submission = Submission::create($submissionData);
 *
 * // Find a specific submission by user ID
 * $submission = Submission::find(789);
 *
 * // List all submissions for the assignment
 * $submissions = Submission::get();
 *
 * // Grade a submission
 * $gradedSubmission = Submission::update(789, [
 *     'posted_grade' => '85',
 *     'comment' => 'Great work!'
 * ]);
 *
 * // Excuse a submission
 * $excusedSubmission = Submission::update(789, ['excuse' => true]);
 *
 * // Mark submission as read/unread
 * Submission::markAsRead(789);
 * Submission::markAsUnread(789);
 *
 * // Bulk grade update
 * $gradeData = [
 *     'grade_data' => [
 *         ['user_id' => 123, 'posted_grade' => '90'],
 *         ['user_id' => 456, 'posted_grade' => '85']
 *     ]
 * ];
 * Submission::updateGrades($gradeData);
 * ```
 *
 * @package CanvasLMS\Api\Submissions
 */
class Submission extends AbstractBaseApi
{
    /**
     * Course context (required)
     * @var Course
     */
    protected static ?Course $course = null;

    /**
     * Assignment context (required)
     * @var Assignment
     */
    protected static ?Assignment $assignment = null;

    /**
     * Submission unique identifier
     */
    public ?int $id = null;

    /**
     * Assignment ID this submission belongs to
     */
    public ?int $assignmentId = null;

    /**
     * User ID who made the submission
     */
    public ?int $userId = null;

    /**
     * Type of submission
     * Valid values: online_text_entry, online_url, online_upload, media_recording, basic_lti_launch, student_annotation
     */
    public ?string $submissionType = null;

    /**
     * Text content for online_text_entry submissions
     */
    public ?string $body = null;

    /**
     * URL for online_url submissions
     */
    public ?string $url = null;

    /**
     * Submission attempt number
     */
    public ?int $attempt = null;

    /**
     * Date and time when submission was submitted
     */
    public ?string $submittedAt = null;

    /**
     * Canvas URL for viewing this submission
     */
    public ?string $htmlUrl = null;

    /**
     * Preview URL for the submission
     */
    public ?string $previewUrl = null;

    /**
     * Numeric score for the submission
     */
    public ?float $score = null;

    /**
     * Grade for the submission (can be letter grade or numeric)
     */
    public ?string $grade = null;

    /**
     * Whether the grade matches the current submission
     */
    public ?bool $gradeMatchesCurrentSubmission = null;

    /**
     * ID of the user who graded this submission
     */
    public ?int $graderId = null;

    /**
     * Date and time when submission was graded
     */
    public ?string $gradedAt = null;

    /**
     * Workflow state of the submission
     * Values: submitted, unsubmitted, graded, pending_review
     */
    public ?string $workflowState = null;

    /**
     * Whether the submission was late
     */
    public ?bool $late = null;

    /**
     * Whether the submission is excused
     */
    public ?bool $excused = null;

    /**
     * Whether the submission is missing
     */
    public ?bool $missing = null;

    /**
     * Whether the assignment is visible to the student
     */
    public ?bool $assignmentVisible = null;

    /**
     * Late policy status
     */
    public ?string $latePolicyStatus = null;

    /**
     * Points deducted for late submission
     */
    public ?float $pointsDeducted = null;

    /**
     * Seconds late for the submission
     */
    public ?int $secondsLate = null;

    /**
     * Extra attempts granted for this submission
     */
    public ?int $extraAttempts = null;

    /**
     * Anonymous ID for anonymous assignments
     */
    public ?string $anonymousId = null;

    /**
     * Date and time when grades were posted
     */
    public ?string $postedAt = null;

    /**
     * Array of submission comments
     * @var array<mixed>|null
     */
    public ?array $submissionComments = null;

    /**
     * Array of file attachments
     * @var array<mixed>|null
     */
    public ?array $attachments = null;

    /**
     * Set the course context
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Set the assignment context
     */
    public static function setAssignment(Assignment $assignment): void
    {
        self::$assignment = $assignment;
    }

    /**
     * Check if course context is set
     * @throws Exception
     */
    public static function checkCourse(): bool
    {
        if (!isset(self::$course)) {
            throw new Exception(
                'Course context must be set before calling Submission methods. Use Submission::setCourse($course)'
            );
        }
        return true;
    }

    /**
     * Check if assignment context is set
     * @throws Exception
     */
    public static function checkAssignment(): bool
    {
        if (!isset(self::$assignment)) {
            throw new Exception(
                'Assignment context must be set before calling Submission methods. ' .
                'Use Submission::setAssignment($assignment)'
            );
        }
        return true;
    }

    /**
     * Check if both contexts are set
     * @throws Exception
     */
    public static function checkContexts(): bool
    {
        self::checkCourse();
        self::checkAssignment();
        return true;
    }

    /**
     * Clear static contexts to prevent memory leaks in long-running processes
     */
    public static function clearContext(): void
    {
        // Note: Cannot unset static properties in PHP
        // Setting to null would require making them nullable
        // For now, this method is a no-op to prevent errors
        // TODO: Consider refactoring to use instance properties or a context manager
    }

    /**
     * Create a new submission
     * @param array<string, mixed>|CreateSubmissionDTO $data
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function create(array|CreateSubmissionDTO $data): self
    {
        self::checkApiClient();
        self::checkContexts();

        $dto = $data instanceof CreateSubmissionDTO ? $data : new CreateSubmissionDTO($data);

        $endpoint = sprintf('courses/%d/assignments/%d/submissions', self::$course->id, self::$assignment->id);

        $response = self::$apiClient->request('POST', $endpoint, [
            'multipart' => $dto->toApiArray()
        ]);

        $submissionData = self::parseJsonResponse($response);

        return new self($submissionData);
    }

    /**
     * Find a submission by user ID
     * @param int $id User ID (submissions are fetched by user ID)
     * @param array<string, mixed> $params Optional query parameters
     * @return static
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function find(int $id, array $params = []): static
    {
        self::checkApiClient();
        self::checkContexts();

        $endpoint = sprintf(
            'courses/%d/assignments/%d/submissions/%d',
            self::$course->id,
            self::$assignment->id,
            $id
        );

        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $submissionData = self::parseJsonResponse($response);

        /** @phpstan-ignore-next-line */
        return new self($submissionData);
    }

    /**
     * Fetch all submissions for the assignment
     * @param array<string, mixed> $params Query parameters
     * @return Submission[]
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function get(array $params = []): array
    {
        self::checkApiClient();
        self::checkContexts();

        $endpoint = sprintf('courses/%d/assignments/%d/submissions', self::$course->id, self::$assignment->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $submissionsData = self::parseJsonResponse($response);

        $submissions = [];
        foreach ($submissionsData as $submissionData) {
            $submissions[] = new self($submissionData);
        }

        return $submissions;
    }



    /**
     * Get paginated submissions
     * @param array<string, mixed> $params Query parameters
     * @return PaginationResult
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function paginate(array $params = []): PaginationResult
    {
        self::checkApiClient();
        self::checkContexts();

        $endpoint = sprintf('courses/%d/assignments/%d/submissions', self::$course->id, self::$assignment->id);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch all submissions from all pages
     * @param array<string, mixed> $params Query parameters
     * @return Submission[]
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function all(array $params = []): array
    {
        self::checkApiClient();
        self::checkContexts();

        $endpoint = sprintf('courses/%d/assignments/%d/submissions', self::$course->id, self::$assignment->id);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        $submissions = [];
        foreach ($allData as $item) {
            $submissions[] = new self($item);
        }

        return $submissions;
    }

    /**
     * Update/grade a submission
     * @param array<string, mixed>|UpdateSubmissionDTO $data
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function update(int $userId, array|UpdateSubmissionDTO $data): self
    {
        self::checkApiClient();
        self::checkContexts();

        $dto = $data instanceof UpdateSubmissionDTO ? $data : new UpdateSubmissionDTO($data);

        $endpoint = sprintf(
            'courses/%d/assignments/%d/submissions/%d',
            self::$course->id,
            self::$assignment->id,
            $userId
        );

        $response = self::$apiClient->request('PUT', $endpoint, [
            'multipart' => $dto->toApiArray()
        ]);

        $submissionData = self::parseJsonResponse($response);

        return new self($submissionData);
    }

    /**
     * Mark submission as read
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function markAsRead(int $userId): self
    {
        self::checkApiClient();
        self::checkContexts();

        $endpoint = sprintf(
            'courses/%d/assignments/%d/submissions/%d/read',
            self::$course->id,
            self::$assignment->id,
            $userId
        );
        self::$apiClient->put($endpoint);
        return new self([]);
    }

    /**
     * Mark submission as unread
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function markAsUnread(int $userId): self
    {
        self::checkApiClient();
        self::checkContexts();

        $endpoint = sprintf(
            'courses/%d/assignments/%d/submissions/%d/read',
            self::$course->id,
            self::$assignment->id,
            $userId
        );
        self::$apiClient->delete($endpoint);
        return new self([]);
    }

    /**
     * Bulk update grades for multiple submissions
     * @param array<string, mixed> $gradeData
     * @throws CanvasApiException
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public static function updateGrades(array $gradeData): self
    {
        self::checkApiClient();
        self::checkContexts();

        // Validate batch size to prevent timeouts and Canvas API limits
        if (isset($gradeData['grade_data']) && is_array($gradeData['grade_data'])) {
            $batchSize = count($gradeData['grade_data']);
            if ($batchSize > 100) {
                throw new InvalidArgumentException(
                    sprintf('Batch size cannot exceed 100 items. Got %d items.', $batchSize)
                );
            }

            // Validate each grade data entry
            foreach ($gradeData['grade_data'] as $index => $entry) {
                if (!is_array($entry) || !isset($entry['user_id'])) {
                    throw new InvalidArgumentException(
                        sprintf('Grade data entry at index %d must contain user_id', $index)
                    );
                }

                if (!is_int($entry['user_id']) || $entry['user_id'] <= 0) {
                    throw new InvalidArgumentException(
                        sprintf('Invalid user_id at index %d: must be positive integer', $index)
                    );
                }
            }
        }

        $endpoint = sprintf(
            'courses/%d/assignments/%d/submissions/update_grades',
            self::$course->id,
            self::$assignment->id
        );

        self::$apiClient->request('PUT', $endpoint, [
            'json' => $gradeData
        ]);
        return new self([]);
    }

    /**
     * Save the submission (update only - submissions are created via static create method)
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        if (!isset($this->userId)) {
            throw new CanvasApiException('Cannot save submission without user ID');
        }

        self::checkApiClient();
        self::checkContexts();

        $data = $this->toDtoArray();
        $dto = new UpdateSubmissionDTO($data);

        $endpoint = sprintf(
            'courses/%d/assignments/%d/submissions/%d',
            self::$course->id,
            self::$assignment->id,
            $this->userId
        );

        $response = self::$apiClient->request('PUT', $endpoint, [
            'multipart' => $dto->toApiArray()
        ]);

        $submissionData = self::parseJsonResponse($response);

        // Update the current instance with the response data
        foreach ($submissionData as $key => $value) {
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $camelKey) && !is_null($value)) {
                $this->{$camelKey} = $value;
            }
        }

        return $this;
    }

    // Getter and setter methods for all properties

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getAssignmentId(): ?int
    {
        return $this->assignmentId;
    }

    public function setAssignmentId(?int $assignmentId): void
    {
        $this->assignmentId = $assignmentId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function getSubmissionType(): ?string
    {
        return $this->submissionType;
    }

    public function setSubmissionType(?string $submissionType): void
    {
        $this->submissionType = $submissionType;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getAttempt(): ?int
    {
        return $this->attempt;
    }

    public function setAttempt(?int $attempt): void
    {
        $this->attempt = $attempt;
    }

    public function getSubmittedAt(): ?string
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?string $submittedAt): void
    {
        $this->submittedAt = $submittedAt;
    }

    public function getHtmlUrl(): ?string
    {
        return $this->htmlUrl;
    }

    public function setHtmlUrl(?string $htmlUrl): void
    {
        $this->htmlUrl = $htmlUrl;
    }

    public function getPreviewUrl(): ?string
    {
        return $this->previewUrl;
    }

    public function setPreviewUrl(?string $previewUrl): void
    {
        $this->previewUrl = $previewUrl;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): void
    {
        $this->score = $score;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): void
    {
        $this->grade = $grade;
    }

    public function getGradeMatchesCurrentSubmission(): ?bool
    {
        return $this->gradeMatchesCurrentSubmission;
    }

    public function setGradeMatchesCurrentSubmission(?bool $gradeMatchesCurrentSubmission): void
    {
        $this->gradeMatchesCurrentSubmission = $gradeMatchesCurrentSubmission;
    }

    public function getGraderId(): ?int
    {
        return $this->graderId;
    }

    public function setGraderId(?int $graderId): void
    {
        $this->graderId = $graderId;
    }

    public function getGradedAt(): ?string
    {
        return $this->gradedAt;
    }

    public function setGradedAt(?string $gradedAt): void
    {
        $this->gradedAt = $gradedAt;
    }

    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    public function getLate(): ?bool
    {
        return $this->late;
    }

    public function setLate(?bool $late): void
    {
        $this->late = $late;
    }

    public function getExcused(): ?bool
    {
        return $this->excused;
    }

    public function setExcused(?bool $excused): void
    {
        $this->excused = $excused;
    }

    public function getMissing(): ?bool
    {
        return $this->missing;
    }

    public function setMissing(?bool $missing): void
    {
        $this->missing = $missing;
    }

    public function getAssignmentVisible(): ?bool
    {
        return $this->assignmentVisible;
    }

    public function setAssignmentVisible(?bool $assignmentVisible): void
    {
        $this->assignmentVisible = $assignmentVisible;
    }

    public function getLatePolicyStatus(): ?string
    {
        return $this->latePolicyStatus;
    }

    public function setLatePolicyStatus(?string $latePolicyStatus): void
    {
        $this->latePolicyStatus = $latePolicyStatus;
    }

    public function getPointsDeducted(): ?float
    {
        return $this->pointsDeducted;
    }

    public function setPointsDeducted(?float $pointsDeducted): void
    {
        $this->pointsDeducted = $pointsDeducted;
    }

    public function getSecondsLate(): ?int
    {
        return $this->secondsLate;
    }

    public function setSecondsLate(?int $secondsLate): void
    {
        $this->secondsLate = $secondsLate;
    }

    public function getExtraAttempts(): ?int
    {
        return $this->extraAttempts;
    }

    public function setExtraAttempts(?int $extraAttempts): void
    {
        $this->extraAttempts = $extraAttempts;
    }

    public function getAnonymousId(): ?string
    {
        return $this->anonymousId;
    }

    public function setAnonymousId(?string $anonymousId): void
    {
        $this->anonymousId = $anonymousId;
    }

    public function getPostedAt(): ?string
    {
        return $this->postedAt;
    }

    public function setPostedAt(?string $postedAt): void
    {
        $this->postedAt = $postedAt;
    }

    /**
     * @return array<mixed>|null
     */
    public function getSubmissionComments(): ?array
    {
        return $this->submissionComments;
    }

    /**
     * @param array<mixed>|null $submissionComments
     */
    public function setSubmissionComments(?array $submissionComments): void
    {
        $this->submissionComments = $submissionComments;
    }

    /**
     * @return array<mixed>|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    /**
     * @param array<mixed>|null $attachments
     */
    public function setAttachments(?array $attachments): void
    {
        $this->attachments = $attachments;
    }

    /**
     * Convert submission to array for DTO
     * @return array<string, mixed>
     */
    protected function toDtoArray(): array
    {
        return array_filter([
            'comment' => null, // Set in DTO if needed
            'submission_type' => $this->submissionType,
            'body' => $this->body,
            'url' => $this->url,
            'posted_grade' => $this->grade,
            'excuse' => $this->excused,
        ], fn($value) => $value !== null);
    }

    // Relationship Methods

    /**
     * Get the course this submission belongs to
     *
     * @return Course|null
     */
    public function course(): ?Course
    {
        return isset(self::$course) ? self::$course : null;
    }

    /**
     * Get the assignment this submission belongs to
     *
     * @return Assignment|null
     */
    public function assignment(): ?Assignment
    {
        return isset(self::$assignment) ? self::$assignment : null;
    }

    /**
     * Get the user who made this submission
     *
     * @return User|null
     * @throws CanvasApiException
     */
    public function user(): ?User
    {
        if (!$this->userId) {
            return null;
        }

        try {
            return User::find($this->userId);
        } catch (\Exception $e) {
            throw new CanvasApiException("Could not load submission user: " . $e->getMessage());
        }
    }

    /**
     * Get the user who graded this submission
     *
     * @return User|null
     * @throws CanvasApiException
     */
    public function grader(): ?User
    {
        if (!$this->graderId) {
            return null;
        }

        try {
            return User::find($this->graderId);
        } catch (\Exception $e) {
            throw new CanvasApiException("Could not load grader: " . $e->getMessage());
        }
    }

    /**
     * Get the API endpoint for this resource
     * @return string
     * @throws CanvasApiException
     */
    protected static function getEndpoint(): string
    {
        self::checkCourse();
        self::checkAssignment();
        return sprintf('courses/%d/assignments/%d/submissions', self::$course->getId(), self::$assignment->getId());
    }
}
