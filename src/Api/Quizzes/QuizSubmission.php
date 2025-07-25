<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Quizzes;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Quizzes\CreateQuizSubmissionDTO;
use CanvasLMS\Dto\Quizzes\UpdateQuizSubmissionDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Canvas LMS Quiz Submissions API
 *
 * Provides functionality to manage quiz submissions and attempts in Canvas LMS.
 * This class handles creating, reading, updating quiz submissions for a specific quiz.
 *
 * Usage Examples:
 *
 * ```php
 * // Set course and quiz context (required for all operations)
 * $course = Course::find(123);
 * $quiz = Quiz::find(456);
 * QuizSubmission::setCourse($course);
 * QuizSubmission::setQuiz($quiz);
 *
 * // Start a new quiz submission
 * $submission = QuizSubmission::start(['access_code' => 'secret123']);
 *
 * // Get current user's submission
 * $submission = QuizSubmission::getCurrentUserSubmission();
 *
 * // List all submissions for the quiz
 * $submissions = QuizSubmission::fetchAll();
 *
 * // Get submissions with specific parameters
 * $submissions = QuizSubmission::fetchAll(['include' => ['user', 'quiz']]);
 *
 * // Find specific submission
 * $submission = QuizSubmission::find(789);
 *
 * // Update submission scores manually
 * $updatedSubmission = QuizSubmission::update(789, [
 *     'fudge_points' => 2.5,
 *     'quiz_submissions' => [
 *         ['attempt' => 1, 'fudge_points' => 2.5]
 *     ]
 * ]);
 *
 * // Complete a submission
 * $submission = QuizSubmission::find(789);
 * $success = $submission->complete();
 *
 * // Check submission state
 * if ($submission->isComplete()) {
 *     echo "Submission completed with score: " . $submission->getScore();
 * }
 * ```
 *
 * @package CanvasLMS\Api\Quizzes
 */
class QuizSubmission extends AbstractBaseApi
{
    protected static Course $course;
    protected static Quiz $quiz;

    /**
     * Quiz submission unique identifier
     */
    public ?int $id = null;

    /**
     * Quiz ID this submission belongs to
     */
    public ?int $quizId = null;

    /**
     * User ID who made the submission
     */
    public ?int $userId = null;

    /**
     * Associated submission ID
     */
    public ?int $submissionId = null;

    /**
     * When the submission was started
     */
    public ?string $startedAt = null;

    /**
     * When the submission was finished
     */
    public ?string $finishedAt = null;

    /**
     * When the submission will end (time limit)
     */
    public ?string $endAt = null;

    /**
     * Attempt number (1-based)
     */
    public ?int $attempt = null;

    /**
     * Extra attempts allowed for this user
     */
    public ?int $extraAttempts = null;

    /**
     * Extra time allowed for this user (in minutes)
     */
    public ?int $extraTime = null;

    /**
     * Time spent on the quiz (in seconds)
     */
    public ?int $timeSpent = null;

    /**
     * Current score for the submission
     */
    public ?float $score = null;

    /**
     * Score before any regrade
     */
    public ?float $scoreBeforeRegrade = null;

    /**
     * Score kept after regrade
     */
    public ?float $keptScore = null;

    /**
     * Fudge points added to the score
     */
    public ?float $fudgePoints = null;

    /**
     * Workflow state (untaken, pending_review, complete, settings_only, preview)
     */
    public ?string $workflowState = null;

    /**
     * Whether submission was manually unlocked
     */
    public ?bool $manuallyUnlocked = null;

    /**
     * Whether user has seen the results
     */
    public ?bool $hasSeenResults = null;

    /**
     * Whether submission is overdue and needs submission
     */
    public ?bool $overdueAndNeedsSubmission = null;

    /**
     * Validation token for submission completion
     */
    public ?string $validationToken = null;

    /**
     * Associated submission data (when included)
     * @var mixed[]|null
     */
    public ?array $submission = null;

    /**
     * Associated quiz data (when included)
     * @var mixed[]|null
     */
    public ?array $quizData = null;

    /**
     * Associated user data (when included)
     * @var mixed[]|null
     */
    public ?array $user = null;

    /**
     * Set the course context for quiz submissions
     *
     * @param Course $course The course instance
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Set the quiz context for quiz submissions
     *
     * @param Quiz $quiz The quiz instance
     */
    public static function setQuiz(Quiz $quiz): void
    {
        self::$quiz = $quiz;
    }

    /**
     * Check if course context is set
     *
     * @return bool True if course is set
     * @throws CanvasApiException If course is not set
     */
    protected static function checkCourse(): bool
    {
        if (!isset(self::$course)) {
            throw new CanvasApiException(
                'Course must be set before performing quiz submission operations. ' .
                'Use QuizSubmission::setCourse($course)'
            );
        }
        return true;
    }

    /**
     * Check if quiz context is set
     *
     * @return bool True if quiz is set
     * @throws CanvasApiException If quiz is not set
     */
    protected static function checkQuiz(): bool
    {
        if (!isset(self::$quiz)) {
            throw new CanvasApiException(
                'Quiz must be set before performing quiz submission operations. Use QuizSubmission::setQuiz($quiz)'
            );
        }
        return true;
    }

    /**
     * Check if both course and quiz contexts are set
     *
     * @return bool True if both are set
     * @throws CanvasApiException If course or quiz is not set
     */
    protected static function checkContext(): bool
    {
        self::checkCourse();
        self::checkQuiz();
        return true;
    }

    /**
     * Get quiz submission ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set quiz submission ID
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get quiz ID
     */
    public function getQuizId(): ?int
    {
        return $this->quizId;
    }

    /**
     * Set quiz ID
     */
    public function setQuizId(?int $quizId): void
    {
        $this->quizId = $quizId;
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
     * Get submission ID
     */
    public function getSubmissionId(): ?int
    {
        return $this->submissionId;
    }

    /**
     * Set submission ID
     */
    public function setSubmissionId(?int $submissionId): void
    {
        $this->submissionId = $submissionId;
    }

    /**
     * Get started at timestamp
     */
    public function getStartedAt(): ?string
    {
        return $this->startedAt;
    }

    /**
     * Set started at timestamp
     */
    public function setStartedAt(?string $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    /**
     * Get finished at timestamp
     */
    public function getFinishedAt(): ?string
    {
        return $this->finishedAt;
    }

    /**
     * Set finished at timestamp
     */
    public function setFinishedAt(?string $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    /**
     * Get end at timestamp
     */
    public function getEndAt(): ?string
    {
        return $this->endAt;
    }

    /**
     * Set end at timestamp
     */
    public function setEndAt(?string $endAt): void
    {
        $this->endAt = $endAt;
    }

    /**
     * Get attempt number
     */
    public function getAttempt(): ?int
    {
        return $this->attempt;
    }

    /**
     * Set attempt number
     */
    public function setAttempt(?int $attempt): void
    {
        $this->attempt = $attempt;
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
     * Get extra time in minutes
     */
    public function getExtraTime(): ?int
    {
        return $this->extraTime;
    }

    /**
     * Set extra time in minutes
     */
    public function setExtraTime(?int $extraTime): void
    {
        $this->extraTime = $extraTime;
    }

    /**
     * Get time spent in seconds
     */
    public function getTimeSpent(): ?int
    {
        return $this->timeSpent;
    }

    /**
     * Set time spent in seconds
     */
    public function setTimeSpent(?int $timeSpent): void
    {
        $this->timeSpent = $timeSpent;
    }

    /**
     * Get current score
     */
    public function getScore(): ?float
    {
        return $this->score;
    }

    /**
     * Set current score
     */
    public function setScore(?float $score): void
    {
        $this->score = $score;
    }

    /**
     * Get score before regrade
     */
    public function getScoreBeforeRegrade(): ?float
    {
        return $this->scoreBeforeRegrade;
    }

    /**
     * Set score before regrade
     */
    public function setScoreBeforeRegrade(?float $scoreBeforeRegrade): void
    {
        $this->scoreBeforeRegrade = $scoreBeforeRegrade;
    }

    /**
     * Get kept score
     */
    public function getKeptScore(): ?float
    {
        return $this->keptScore;
    }

    /**
     * Set kept score
     */
    public function setKeptScore(?float $keptScore): void
    {
        $this->keptScore = $keptScore;
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
     */
    public function setFudgePoints(?float $fudgePoints): void
    {
        $this->fudgePoints = $fudgePoints;
    }

    /**
     * Get workflow state
     */
    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    /**
     * Set workflow state
     */
    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    /**
     * Get manually unlocked status
     */
    public function getManuallyUnlocked(): ?bool
    {
        return $this->manuallyUnlocked;
    }

    /**
     * Set manually unlocked status
     */
    public function setManuallyUnlocked(?bool $manuallyUnlocked): void
    {
        $this->manuallyUnlocked = $manuallyUnlocked;
    }

    /**
     * Get has seen results status
     */
    public function getHasSeenResults(): ?bool
    {
        return $this->hasSeenResults;
    }

    /**
     * Set has seen results status
     */
    public function setHasSeenResults(?bool $hasSeenResults): void
    {
        $this->hasSeenResults = $hasSeenResults;
    }

    /**
     * Get overdue and needs submission status
     */
    public function getOverdueAndNeedsSubmission(): ?bool
    {
        return $this->overdueAndNeedsSubmission;
    }

    /**
     * Set overdue and needs submission status
     */
    public function setOverdueAndNeedsSubmission(?bool $overdueAndNeedsSubmission): void
    {
        $this->overdueAndNeedsSubmission = $overdueAndNeedsSubmission;
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
     */
    public function setValidationToken(?string $validationToken): void
    {
        $this->validationToken = $validationToken;
    }

    /**
     * Get associated submission data
     * @return mixed[]|null
     */
    public function getSubmission(): ?array
    {
        return $this->submission;
    }

    /**
     * Set associated submission data
     * @param mixed[]|null $submission
     */
    public function setSubmission(?array $submission): void
    {
        $this->submission = $submission;
    }

    /**
     * Get associated quiz data
     * @return mixed[]|null
     */
    public function getQuizData(): ?array
    {
        return $this->quizData;
    }

    /**
     * Set associated quiz data
     * @param mixed[]|null $quizData
     */
    public function setQuizData(?array $quizData): void
    {
        $this->quizData = $quizData;
    }

    /**
     * Get associated user data
     * @return mixed[]|null
     */
    public function getUser(): ?array
    {
        return $this->user;
    }

    /**
     * Set associated user data
     * @param mixed[]|null $user
     */
    public function setUser(?array $user): void
    {
        $this->user = $user;
    }

    /**
     * Find a specific quiz submission by ID
     *
     * @param int $id Quiz submission ID
     * @return self Quiz submission instance
     * @throws CanvasApiException If course/quiz not set or API error
     */
    public static function find(int $id): self
    {
        self::checkContext();

        self::checkApiClient();

        $endpoint = sprintf(
            'courses/%d/quizzes/%d/submissions/%d',
            self::$course->getId(),
            self::$quiz->getId(),
            $id
        );
        $response = self::$apiClient->get($endpoint);
        $responseData = json_decode($response->getBody()->getContents(), true);

        $data = $responseData['quiz_submissions'][0] ?? $responseData;

        return new self($data);
    }

    /**
     * Get current user's submission for the quiz
     *
     * @return self|null Quiz submission instance or null if no submission
     * @throws CanvasApiException If course/quiz not set or API error
     */
    public static function getCurrentUserSubmission(): ?self
    {
        self::checkContext();

        try {
            self::checkApiClient();

            $endpoint = sprintf(
                'courses/%d/quizzes/%d/submission',
                self::$course->getId(),
                self::$quiz->getId()
            );
            $response = self::$apiClient->get($endpoint);
            $responseData = json_decode($response->getBody()->getContents(), true);

            $data = $responseData['quiz_submissions'][0] ?? $responseData;

            return new self($data);
        } catch (CanvasApiException $e) {
            // If no submission exists, Canvas returns 404
            if (str_contains($e->getMessage(), '404')) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Fetch all quiz submissions for the quiz
     *
     * @param mixed[] $params Optional parameters for filtering
     * @return self[] Array of quiz submission instances
     * @throws CanvasApiException If course/quiz not set or API error
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkContext();

        self::checkApiClient();

        $endpoint = sprintf(
            'courses/%d/quizzes/%d/submissions',
            self::$course->getId(),
            self::$quiz->getId()
        );
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseData = json_decode($response->getBody()->getContents(), true);

        $submissions = [];
        foreach ($responseData['quiz_submissions'] as $submissionData) {
            $submissions[] = new self($submissionData);
        }

        return $submissions;
    }

    /**
     * Fetch paginated quiz submissions
     *
     * @param mixed[] $params Optional parameters for filtering
     * @return PaginatedResponse Paginated response instance
     * @throws CanvasApiException If course/quiz not set or API error
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkContext();

        $endpoint = sprintf(
            'courses/%d/quizzes/%d/submissions',
            self::$course->getId(),
            self::$quiz->getId()
        );
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Fetch a single page of quiz submissions
     *
     * @param mixed[] $params Optional parameters for filtering
     * @return PaginationResult Pagination result with submissions
     * @throws CanvasApiException If course/quiz not set or API error
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        $paginatedResponse = self::fetchAllPaginated($params);
        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch all pages of quiz submissions
     *
     * @param mixed[] $params Optional parameters for filtering
     * @return self[] Array of all quiz submission instances
     * @throws CanvasApiException If course/quiz not set or API error
     */
    public static function fetchAllPages(array $params = []): array
    {
        $paginatedResponse = self::fetchAllPaginated($params);
        $endpoint = sprintf(
            'courses/%d/quizzes/%d/submissions',
            self::$course->getId(),
            self::$quiz->getId()
        );
        return self::fetchAllPagesAsModels($endpoint, $params);
    }

    /**
     * Start a new quiz submission or create one
     *
     * @param array|CreateQuizSubmissionDTO $data Submission data
     * @return self Created quiz submission instance
     * @throws CanvasApiException If course/quiz not set or API error
     */
    /**
     * @param mixed[]|CreateQuizSubmissionDTO $data
     */
    public static function create(array|CreateQuizSubmissionDTO $data): self
    {
        self::checkContext();

        if ($data instanceof CreateQuizSubmissionDTO) {
            $requestData = $data->toApiArray();
        } else {
            $requestData = [];
            foreach ($data as $key => $value) {
                $requestData[] = [
                    'name' => $key,
                    'contents' => $value
                ];
            }
        }

        self::checkApiClient();

        $endpoint = sprintf(
            'courses/%d/quizzes/%d/submissions',
            self::$course->getId(),
            self::$quiz->getId()
        );
        $response = self::$apiClient->post($endpoint, ['multipart' => $requestData]);
        $responseData = json_decode($response->getBody()->getContents(), true);

        $submissionData = $responseData['quiz_submissions'][0] ?? $responseData;

        return new self($submissionData);
    }

    /**
     * Start a quiz submission (alias for create)
     *
     * @param mixed[] $params Optional parameters like access_code
     * @return self Created quiz submission instance
     * @throws CanvasApiException If course/quiz not set or API error
     */
    public static function start(array $params = []): self
    {
        return self::create($params);
    }

    /**
     * Update a quiz submission
     *
     * @param int $id Quiz submission ID
     * @param array|UpdateQuizSubmissionDTO $data Update data
     * @return self Updated quiz submission instance
     * @throws CanvasApiException If course/quiz not set or API error
     */
    /**
     * @param mixed[]|UpdateQuizSubmissionDTO $data
     */
    public static function update(int $id, array|UpdateQuizSubmissionDTO $data): self
    {
        self::checkContext();

        if ($data instanceof UpdateQuizSubmissionDTO) {
            $requestData = $data->toApiArray();
        } else {
            $requestData = [];
            foreach ($data as $key => $value) {
                $requestData[] = [
                    'name' => $key,
                    'contents' => is_array($value) ? json_encode($value) : (string)$value
                ];
            }
        }

        self::checkApiClient();

        $endpoint = sprintf(
            'courses/%d/quizzes/%d/submissions/%d',
            self::$course->getId(),
            self::$quiz->getId(),
            $id
        );
        $response = self::$apiClient->put($endpoint, ['multipart' => $requestData]);
        $responseData = json_decode($response->getBody()->getContents(), true);

        $submissionData = $responseData['quiz_submissions'][0] ?? $responseData;

        return new self($submissionData);
    }

    /**
     * Complete a quiz submission
     *
     * @return bool True if completed successfully, false on API error
     * @throws CanvasApiException If no ID set
     */
    public function complete(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Quiz submission ID is required for completion');
        }

        self::checkContext();

        try {
            $requestData = [];
            if ($this->validationToken) {
                $requestData[] = [
                    'name' => 'validation_token',
                    'contents' => $this->validationToken
                ];
            }
            if ($this->attempt) {
                $requestData[] = [
                    'name' => 'attempt',
                    'contents' => (string)$this->attempt
                ];
            }

            self::checkApiClient();

            $endpoint = sprintf(
                'courses/%d/quizzes/%d/submissions/%d/complete',
                self::$course->getId(),
                self::$quiz->getId(),
                $this->id
            );
            $response = self::$apiClient->post($endpoint, ['multipart' => $requestData]);
            $responseData = json_decode($response->getBody()->getContents(), true);

            $submissionData = $responseData['quiz_submissions'][0] ?? $responseData;

            // Update current instance with response data
            $this->workflowState = $submissionData['workflow_state'] ?? $this->workflowState;
            $this->finishedAt = $submissionData['finished_at'] ?? $this->finishedAt;
            $this->score = $submissionData['score'] ?? $this->score;

            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Save the quiz submission (update if exists)
     *
     * @return bool True if saved successfully, false on API error
     * @throws CanvasApiException If no ID set or validation fails
     */
    public function save(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException(
                'Quiz submission ID is required for saving. Use QuizSubmission::create() for new submissions.'
            );
        }

        try {
            $updateData = $this->toArray();
            $updatedSubmission = self::update($this->id, $updateData);

            // Update current instance properties
            foreach (get_object_vars($updatedSubmission) as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->$property = $value;
                }
            }

            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Check if submission is complete
     */
    public function isComplete(): bool
    {
        return $this->workflowState === 'complete';
    }

    /**
     * Check if submission is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->workflowState, ['untaken', 'pending_review'], true);
    }

    /**
     * Check if submission can be retaken
     */
    public function canBeRetaken(): bool
    {
        if ($this->workflowState !== 'complete') {
            return false;
        }

        $allowedAttempts = self::$quiz->getAllowedAttempts();

        // -1 means unlimited attempts
        if ($allowedAttempts === -1) {
            return true;
        }

        // If allowed attempts is null or 1, no retakes allowed
        if ($allowedAttempts === null || $allowedAttempts <= 1) {
            return false;
        }

        // Check if current attempt is less than allowed attempts
        return ($this->attempt ?? 1) < $allowedAttempts;
    }

    /**
     * Get remaining time in seconds
     */
    public function getRemainingTime(): ?int
    {
        if (!$this->endAt) {
            return null;
        }

        $endTime = strtotime($this->endAt);
        $currentTime = time();

        return max(0, $endTime - $currentTime);
    }

    /**
     * Check if quiz has time limit
     */
    public function hasTimeLimit(): bool
    {
        return $this->endAt !== null;
    }

    /**
     * Check if submission is overdue
     */
    public function isOverdue(): bool
    {
        return $this->overdueAndNeedsSubmission === true;
    }

    /**
     * Convert quiz submission to array
     *
     * @return mixed[] Quiz submission data
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'quizId' => $this->quizId,
            'userId' => $this->userId,
            'submissionId' => $this->submissionId,
            'startedAt' => $this->startedAt,
            'finishedAt' => $this->finishedAt,
            'endAt' => $this->endAt,
            'attempt' => $this->attempt,
            'extraAttempts' => $this->extraAttempts,
            'extraTime' => $this->extraTime,
            'timeSpent' => $this->timeSpent,
            'score' => $this->score,
            'scoreBeforeRegrade' => $this->scoreBeforeRegrade,
            'keptScore' => $this->keptScore,
            'fudgePoints' => $this->fudgePoints,
            'workflowState' => $this->workflowState,
            'manuallyUnlocked' => $this->manuallyUnlocked,
            'hasSeenResults' => $this->hasSeenResults,
            'overdueAndNeedsSubmission' => $this->overdueAndNeedsSubmission,
            'validationToken' => $this->validationToken,
            'submission' => $this->submission,
            'quizData' => $this->quizData,
            'user' => $this->user
        ];
    }

    /**
     * Convert quiz submission to DTO-compatible array
     *
     * @return mixed[] DTO-compatible data
     */
    public function toDtoArray(): array
    {
        return [
            'quiz_id' => $this->quizId,
            'user_id' => $this->userId,
            'submission_id' => $this->submissionId,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'end_at' => $this->endAt,
            'attempt' => $this->attempt,
            'extra_attempts' => $this->extraAttempts,
            'extra_time' => $this->extraTime,
            'time_spent' => $this->timeSpent,
            'score' => $this->score,
            'score_before_regrade' => $this->scoreBeforeRegrade,
            'kept_score' => $this->keptScore,
            'fudge_points' => $this->fudgePoints,
            'workflow_state' => $this->workflowState,
            'manually_unlocked' => $this->manuallyUnlocked,
            'has_seen_results' => $this->hasSeenResults,
            'overdue_and_needs_submission' => $this->overdueAndNeedsSubmission,
            'validation_token' => $this->validationToken
        ];
    }
}
