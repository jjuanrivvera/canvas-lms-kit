<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Quizzes;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\QuizSubmissions\QuizSubmission;
use CanvasLMS\Dto\Quizzes\CreateQuizDTO;
use CanvasLMS\Dto\Quizzes\UpdateQuizDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Canvas LMS Quizzes API
 *
 * Provides functionality to manage quizzes and assessments in Canvas LMS.
 * This class handles creating, reading, updating, and deleting quizzes for a specific course.
 *
 * Usage Examples:
 *
 * ```php
 * // Set course context (required for all operations)
 * $course = Course::find(123);
 * Quiz::setCourse($course);
 *
 * // Create a new quiz
 * $quizData = [
 *     'title' => 'Midterm Exam',
 *     'description' => 'Covers chapters 1-5',
 *     'quiz_type' => 'assignment',
 *     'time_limit' => 60,
 *     'points_possible' => 100,
 *     'due_at' => '2024-12-31T23:59:59Z'
 * ];
 * $quiz = Quiz::create($quizData);
 *
 * // Find a quiz by ID
 * $quiz = Quiz::find(456);
 *
 * // List all quizzes for the course
 * $quizzes = Quiz::fetchAll();
 *
 * // Get published quizzes only
 * $publishedQuizzes = Quiz::fetchAll(['published' => true]);
 *
 * // Get paginated quizzes
 * $paginatedQuizzes = Quiz::fetchAllPaginated();
 * $paginationResult = Quiz::fetchPage();
 *
 * // Update a quiz
 * $updatedQuiz = Quiz::update(456, ['time_limit' => 90]);
 *
 * // Update using DTO
 * $updateDto = new UpdateQuizDTO(['title' => 'Updated Quiz Title']);
 * $updatedQuiz = Quiz::update(456, $updateDto);
 *
 * // Update using instance method
 * $quiz = Quiz::find(456);
 * $quiz->setTimeLimit(120);
 * $success = $quiz->save();
 *
 * // Publish/unpublish a quiz
 * $quiz->publish();
 * $quiz->unpublish();
 *
 * // Delete a quiz
 * $quiz = Quiz::find(456);
 * $success = $quiz->delete();
 * ```
 *
 * @package CanvasLMS\Api\Quizzes
 */
class Quiz extends AbstractBaseApi
{
    /**
     * Valid quiz types
     */
    public const VALID_QUIZ_TYPES = [
        'assignment',
        'practice_quiz',
        'survey',
        'graded_survey'
    ];

    /**
     * Valid hide results values
     */
    public const VALID_HIDE_RESULTS = [
        null,
        'always',
        'until_after_last_attempt'
    ];

    protected static Course $course;

    /**
     * Quiz unique identifier
     */
    public ?int $id = null;

    /**
     * Quiz title
     */
    public ?string $title = null;

    /**
     * Quiz description (HTML)
     */
    public ?string $description = null;

    /**
     * Quiz type (assignment, practice_quiz, survey, graded_survey)
     */
    public ?string $quizType = null;

    /**
     * Course ID this quiz belongs to
     */
    public ?int $courseId = null;

    /**
     * Assignment group ID for graded quizzes
     */
    public ?int $assignmentGroupId = null;

    /**
     * Time limit in minutes
     */
    public ?int $timeLimit = null;

    /**
     * Maximum points possible for this quiz
     */
    public ?float $pointsPossible = null;

    /**
     * Quiz due date
     */
    public ?string $dueAt = null;

    /**
     * Date when quiz becomes locked
     */
    public ?string $lockAt = null;

    /**
     * Date when quiz becomes available
     */
    public ?string $unlockAt = null;

    /**
     * Whether the quiz is published
     */
    public ?bool $published = null;

    /**
     * Quiz workflow state (published, unpublished, etc.)
     */
    public ?string $workflowState = null;

    /**
     * Whether to shuffle answers
     */
    public ?bool $shuffleAnswers = null;

    /**
     * Whether to show correct answers after submission
     */
    public ?bool $showCorrectAnswers = null;

    /**
     * Number of allowed attempts (-1 for unlimited)
     */
    public ?int $allowedAttempts = null;

    /**
     * Whether to display one question at a time
     */
    public ?bool $oneQuestionAtATime = null;

    /**
     * When to hide quiz results (null, always, until_after_last_attempt)
     */
    public ?string $hideResults = null;

    /**
     * IP address filter/restriction
     */
    public ?string $ipFilter = null;

    /**
     * Access code/password for the quiz
     */
    public ?string $accessCode = null;

    /**
     * HTML URL to the quiz
     */
    public ?string $htmlUrl = null;

    /**
     * Mobile URL to the quiz
     */
    public ?string $mobileUrl = null;

    /**
     * Number of questions in the quiz
     */
    public ?int $questionCount = null;

    /**
     * Whether the quiz requires lockdown browser
     */
    public ?bool $requireLockdownBrowser = null;

    /**
     * Whether the quiz requires lockdown browser for results
     */
    public ?bool $requireLockdownBrowserForResults = null;

    /**
     * Whether the quiz requires lockdown browser monitor
     */
    public ?bool $requireLockdownBrowserMonitor = null;

    /**
     * Lockdown browser monitor data
     */
    public ?string $lockdownBrowserMonitorData = null;

    /**
     * All date variations for the quiz
     * @var array<string, mixed>|null
     */
    public ?array $allDates = null;

    /**
     * Quiz creation timestamp
     */
    public ?string $createdAt = null;

    /**
     * Quiz last update timestamp
     */
    public ?string $updatedAt = null;

    /**
     * Create a new Quiz instance
     *
     * @param array<string, mixed> $data Quiz data from Canvas API
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Set the course context for quiz operations
     *
     * @param Course $course The course to operate on
     * @return void
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Check if course context is set
     *
     * @return bool
     * @throws CanvasApiException If course is not set
     */
    public static function checkCourse(): bool
    {
        if (!isset(self::$course) || !isset(self::$course->id)) {
            throw new CanvasApiException('Course is required');
        }
        return true;
    }

    /**
     * Get quiz ID
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set quiz ID
     *
     * @param int|null $id
     * @return void
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get quiz title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set quiz title
     *
     * @param string|null $title
     * @return void
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get quiz description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set quiz description
     *
     * @param string|null $description
     * @return void
     * @throws CanvasApiException If description contains potentially dangerous content
     */
    public function setDescription(?string $description): void
    {
        if ($description !== null) {
            $this->validateDescription($description);
        }
        $this->description = $description;
    }

    /**
     * Validate quiz description for XSS prevention
     *
     * @param string $description
     * @return void
     * @throws CanvasApiException If description contains potentially dangerous content
     */
    private function validateDescription(string $description): void
    {
        $dangerousPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/on\w+\s*=\s*["\'].*?["\']/i',
            '/javascript\s*:/i',
            '/data\s*:\s*text\/html/i',
            '/vbscript\s*:/i',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>.*?<\/embed>/is',
            '/<applet[^>]*>.*?<\/applet>/is',
            '/<form[^>]*>.*?<\/form>/is'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $description)) {
                throw new CanvasApiException(
                    'Quiz description contains potentially dangerous content. ' .
                    'Please remove scripts, event handlers, or other executable content.'
                );
            }
        }

        if (strlen($description) > 65535) {
            throw new CanvasApiException('Quiz description is too long. Maximum length is 65535 characters.');
        }
    }

    /**
     * Check if a property is safe to update from API response
     *
     * @param string $property
     * @return bool
     */
    private function isSafeToUpdateProperty(string $property): bool
    {
        if (!property_exists($this, $property)) {
            return false;
        }

        $reflection = new \ReflectionProperty($this, $property);

        // Only update public properties
        if (!$reflection->isPublic()) {
            return false;
        }

        // Don't update static properties
        if ($reflection->isStatic()) {
            return false;
        }

        return true;
    }

    /**
     * Get quiz type
     *
     * @return string|null
     */
    public function getQuizType(): ?string
    {
        return $this->quizType;
    }

    /**
     * Set quiz type
     *
     * @param string|null $quizType
     * @return void
     */
    public function setQuizType(?string $quizType): void
    {
        $this->quizType = $quizType;
    }

    /**
     * Get course ID
     *
     * @return int|null
     */
    public function getCourseId(): ?int
    {
        return $this->courseId;
    }

    /**
     * Set course ID
     *
     * @param int|null $courseId
     * @return void
     */
    public function setCourseId(?int $courseId): void
    {
        $this->courseId = $courseId;
    }

    /**
     * Get assignment group ID
     *
     * @return int|null
     */
    public function getAssignmentGroupId(): ?int
    {
        return $this->assignmentGroupId;
    }

    /**
     * Set assignment group ID
     *
     * @param int|null $assignmentGroupId
     * @return void
     */
    public function setAssignmentGroupId(?int $assignmentGroupId): void
    {
        $this->assignmentGroupId = $assignmentGroupId;
    }

    /**
     * Get time limit
     *
     * @return int|null
     */
    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    /**
     * Set time limit
     *
     * @param int|null $timeLimit
     * @return void
     */
    public function setTimeLimit(?int $timeLimit): void
    {
        $this->timeLimit = $timeLimit;
    }

    /**
     * Get points possible
     *
     * @return float|null
     */
    public function getPointsPossible(): ?float
    {
        return $this->pointsPossible;
    }

    /**
     * Set points possible
     *
     * @param float|null $pointsPossible
     * @return void
     */
    public function setPointsPossible(?float $pointsPossible): void
    {
        $this->pointsPossible = $pointsPossible;
    }

    /**
     * Get due date
     *
     * @return string|null
     */
    public function getDueAt(): ?string
    {
        return $this->dueAt;
    }

    /**
     * Set due date
     *
     * @param string|null $dueAt
     * @return void
     */
    public function setDueAt(?string $dueAt): void
    {
        $this->dueAt = $dueAt;
    }

    /**
     * Get lock date
     *
     * @return string|null
     */
    public function getLockAt(): ?string
    {
        return $this->lockAt;
    }

    /**
     * Set lock date
     *
     * @param string|null $lockAt
     * @return void
     */
    public function setLockAt(?string $lockAt): void
    {
        $this->lockAt = $lockAt;
    }

    /**
     * Get unlock date
     *
     * @return string|null
     */
    public function getUnlockAt(): ?string
    {
        return $this->unlockAt;
    }

    /**
     * Set unlock date
     *
     * @param string|null $unlockAt
     * @return void
     */
    public function setUnlockAt(?string $unlockAt): void
    {
        $this->unlockAt = $unlockAt;
    }

    /**
     * Get published status
     *
     * @return bool|null
     */
    public function getPublished(): ?bool
    {
        return $this->published;
    }

    /**
     * Set published status
     *
     * @param bool|null $published
     * @return void
     */
    public function setPublished(?bool $published): void
    {
        $this->published = $published;
    }

    /**
     * Get workflow state
     *
     * @return string|null
     */
    public function getWorkflowState(): ?string
    {
        return $this->workflowState;
    }

    /**
     * Set workflow state
     *
     * @param string|null $workflowState
     * @return void
     */
    public function setWorkflowState(?string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    /**
     * Get shuffle answers status
     *
     * @return bool|null
     */
    public function getShuffleAnswers(): ?bool
    {
        return $this->shuffleAnswers;
    }

    /**
     * Set shuffle answers status
     *
     * @param bool|null $shuffleAnswers
     * @return void
     */
    public function setShuffleAnswers(?bool $shuffleAnswers): void
    {
        $this->shuffleAnswers = $shuffleAnswers;
    }

    /**
     * Get show correct answers status
     *
     * @return bool|null
     */
    public function getShowCorrectAnswers(): ?bool
    {
        return $this->showCorrectAnswers;
    }

    /**
     * Set show correct answers status
     *
     * @param bool|null $showCorrectAnswers
     * @return void
     */
    public function setShowCorrectAnswers(?bool $showCorrectAnswers): void
    {
        $this->showCorrectAnswers = $showCorrectAnswers;
    }

    /**
     * Get allowed attempts
     *
     * @return int|null
     */
    public function getAllowedAttempts(): ?int
    {
        return $this->allowedAttempts;
    }

    /**
     * Set allowed attempts
     *
     * @param int|null $allowedAttempts
     * @return void
     */
    public function setAllowedAttempts(?int $allowedAttempts): void
    {
        $this->allowedAttempts = $allowedAttempts;
    }

    /**
     * Get one question at a time status
     *
     * @return bool|null
     */
    public function getOneQuestionAtATime(): ?bool
    {
        return $this->oneQuestionAtATime;
    }

    /**
     * Set one question at a time status
     *
     * @param bool|null $oneQuestionAtATime
     * @return void
     */
    public function setOneQuestionAtATime(?bool $oneQuestionAtATime): void
    {
        $this->oneQuestionAtATime = $oneQuestionAtATime;
    }

    /**
     * Get hide results setting
     *
     * @return string|null
     */
    public function getHideResults(): ?string
    {
        return $this->hideResults;
    }

    /**
     * Set hide results setting
     *
     * @param string|null $hideResults
     * @return void
     */
    public function setHideResults(?string $hideResults): void
    {
        $this->hideResults = $hideResults;
    }

    /**
     * Get IP filter
     *
     * @return string|null
     */
    public function getIpFilter(): ?string
    {
        return $this->ipFilter;
    }

    /**
     * Set IP filter
     *
     * @param string|null $ipFilter
     * @return void
     */
    public function setIpFilter(?string $ipFilter): void
    {
        $this->ipFilter = $ipFilter;
    }

    /**
     * Get access code
     *
     * @return string|null
     */
    public function getAccessCode(): ?string
    {
        return $this->accessCode;
    }

    /**
     * Set access code
     *
     * @param string|null $accessCode
     * @return void
     */
    public function setAccessCode(?string $accessCode): void
    {
        $this->accessCode = $accessCode;
    }

    /**
     * Get HTML URL
     *
     * @return string|null
     */
    public function getHtmlUrl(): ?string
    {
        return $this->htmlUrl;
    }

    /**
     * Set HTML URL
     *
     * @param string|null $htmlUrl
     * @return void
     */
    public function setHtmlUrl(?string $htmlUrl): void
    {
        $this->htmlUrl = $htmlUrl;
    }

    /**
     * Get mobile URL
     *
     * @return string|null
     */
    public function getMobileUrl(): ?string
    {
        return $this->mobileUrl;
    }

    /**
     * Set mobile URL
     *
     * @param string|null $mobileUrl
     * @return void
     */
    public function setMobileUrl(?string $mobileUrl): void
    {
        $this->mobileUrl = $mobileUrl;
    }

    /**
     * Get question count
     *
     * @return int|null
     */
    public function getQuestionCount(): ?int
    {
        return $this->questionCount;
    }

    /**
     * Set question count
     *
     * @param int|null $questionCount
     * @return void
     */
    public function setQuestionCount(?int $questionCount): void
    {
        $this->questionCount = $questionCount;
    }

    /**
     * Get require lockdown browser status
     *
     * @return bool|null
     */
    public function getRequireLockdownBrowser(): ?bool
    {
        return $this->requireLockdownBrowser;
    }

    /**
     * Set require lockdown browser status
     *
     * @param bool|null $requireLockdownBrowser
     * @return void
     */
    public function setRequireLockdownBrowser(?bool $requireLockdownBrowser): void
    {
        $this->requireLockdownBrowser = $requireLockdownBrowser;
    }

    /**
     * Get require lockdown browser for results status
     *
     * @return bool|null
     */
    public function getRequireLockdownBrowserForResults(): ?bool
    {
        return $this->requireLockdownBrowserForResults;
    }

    /**
     * Set require lockdown browser for results status
     *
     * @param bool|null $requireLockdownBrowserForResults
     * @return void
     */
    public function setRequireLockdownBrowserForResults(?bool $requireLockdownBrowserForResults): void
    {
        $this->requireLockdownBrowserForResults = $requireLockdownBrowserForResults;
    }

    /**
     * Get require lockdown browser monitor status
     *
     * @return bool|null
     */
    public function getRequireLockdownBrowserMonitor(): ?bool
    {
        return $this->requireLockdownBrowserMonitor;
    }

    /**
     * Set require lockdown browser monitor status
     *
     * @param bool|null $requireLockdownBrowserMonitor
     * @return void
     */
    public function setRequireLockdownBrowserMonitor(?bool $requireLockdownBrowserMonitor): void
    {
        $this->requireLockdownBrowserMonitor = $requireLockdownBrowserMonitor;
    }

    /**
     * Get lockdown browser monitor data
     *
     * @return string|null
     */
    public function getLockdownBrowserMonitorData(): ?string
    {
        return $this->lockdownBrowserMonitorData;
    }

    /**
     * Set lockdown browser monitor data
     *
     * @param string|null $lockdownBrowserMonitorData
     * @return void
     */
    public function setLockdownBrowserMonitorData(?string $lockdownBrowserMonitorData): void
    {
        $this->lockdownBrowserMonitorData = $lockdownBrowserMonitorData;
    }

    /**
     * Get all dates
     *
     * @return array<string, mixed>|null
     */
    public function getAllDates(): ?array
    {
        return $this->allDates;
    }

    /**
     * Set all dates
     *
     * @param array<string, mixed>|null $allDates
     * @return void
     */
    public function setAllDates(?array $allDates): void
    {
        $this->allDates = $allDates;
    }

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Set created at timestamp
     *
     * @param string|null $createdAt
     * @return void
     */
    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * Set updated at timestamp
     *
     * @param string|null $updatedAt
     * @return void
     */
    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Convert quiz to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'quiz_type' => $this->quizType,
            'course_id' => $this->courseId,
            'assignment_group_id' => $this->assignmentGroupId,
            'time_limit' => $this->timeLimit,
            'points_possible' => $this->pointsPossible,
            'due_at' => $this->dueAt,
            'lock_at' => $this->lockAt,
            'unlock_at' => $this->unlockAt,
            'published' => $this->published,
            'workflow_state' => $this->workflowState,
            'shuffle_answers' => $this->shuffleAnswers,
            'show_correct_answers' => $this->showCorrectAnswers,
            'allowed_attempts' => $this->allowedAttempts,
            'one_question_at_a_time' => $this->oneQuestionAtATime,
            'hide_results' => $this->hideResults,
            'ip_filter' => $this->ipFilter,
            'access_code' => $this->accessCode,
            'html_url' => $this->htmlUrl,
            'mobile_url' => $this->mobileUrl,
            'question_count' => $this->questionCount,
            'require_lockdown_browser' => $this->requireLockdownBrowser,
            'require_lockdown_browser_for_results' => $this->requireLockdownBrowserForResults,
            'require_lockdown_browser_monitor' => $this->requireLockdownBrowserMonitor,
            'lockdown_browser_monitor_data' => $this->lockdownBrowserMonitorData,
            'all_dates' => $this->allDates,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Convert quiz to DTO array format
     *
     * @return array<string, mixed>
     */
    public function toDtoArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'quiz_type' => $this->quizType,
            'assignment_group_id' => $this->assignmentGroupId,
            'time_limit' => $this->timeLimit,
            'points_possible' => $this->pointsPossible,
            'due_at' => $this->dueAt,
            'lock_at' => $this->lockAt,
            'unlock_at' => $this->unlockAt,
            'published' => $this->published,
            'shuffle_answers' => $this->shuffleAnswers,
            'show_correct_answers' => $this->showCorrectAnswers,
            'allowed_attempts' => $this->allowedAttempts,
            'one_question_at_a_time' => $this->oneQuestionAtATime,
            'hide_results' => $this->hideResults,
            'ip_filter' => $this->ipFilter,
            'access_code' => $this->accessCode,
            'require_lockdown_browser' => $this->requireLockdownBrowser,
            'require_lockdown_browser_for_results' => $this->requireLockdownBrowserForResults,
            'require_lockdown_browser_monitor' => $this->requireLockdownBrowserMonitor,
            'lockdown_browser_monitor_data' => $this->lockdownBrowserMonitorData,
        ], fn($value) => $value !== null);
    }

    /**
     * Find a single quiz by ID
     *
     * @param int $id Quiz ID
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/quizzes/%d', self::$course->id, $id);
        $response = self::$apiClient->get($endpoint);
        $quizData = json_decode($response->getBody()->getContents(), true);

        return new self($quizData);
    }

    /**
     * Fetch all quizzes for the course
     *
     * @param array<string, mixed> $params Optional parameters
     * @return array<Quiz> Array of Quiz objects
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/quizzes', self::$course->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $quizzesData = json_decode($response->getBody()->getContents(), true);

        $quizzes = [];
        foreach ($quizzesData as $quizData) {
            $quizzes[] = new self($quizData);
        }

        return $quizzes;
    }

    /**
     * Fetch all quizzes with pagination support
     *
     * @param array<string, mixed> $params Optional parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/quizzes', self::$course->id);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Fetch a single page of quizzes
     *
     * @param array<string, mixed> $params Optional parameters
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/quizzes', self::$course->id);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch all pages of quizzes
     *
     * @param array<string, mixed> $params Optional parameters
     * @return array<Quiz> Array of Quiz objects from all pages
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/quizzes', self::$course->id);
        return self::fetchAllPagesAsModels($endpoint, $params);
    }

    /**
     * Create a new quiz
     *
     * @param array<string, mixed>|CreateQuizDTO $data Quiz data
     * @return self Created Quiz object
     * @throws CanvasApiException
     */
    public static function create(array|CreateQuizDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateQuizDTO($data);
        }

        $endpoint = sprintf('courses/%d/quizzes', self::$course->id);
        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $quizData = json_decode($response->getBody()->getContents(), true);

        return new self($quizData);
    }

    /**
     * Update a quiz
     *
     * @param int $id Quiz ID
     * @param array<string, mixed>|UpdateQuizDTO $data Quiz data
     * @return self Updated Quiz object
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateQuizDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateQuizDTO($data);
        }

        $endpoint = sprintf('courses/%d/quizzes/%d', self::$course->id, $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $data->toApiArray()]);
        $quizData = json_decode($response->getBody()->getContents(), true);

        return new self($quizData);
    }

    /**
     * Save the current quiz (create or update)
     *
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        // Check for required fields before trying to save
        if (!$this->id && empty($this->title)) {
            throw new CanvasApiException('Quiz title is required');
        }

        // Validate points possible
        if ($this->pointsPossible !== null && $this->pointsPossible < 0) {
            throw new CanvasApiException('Points possible must be non-negative');
        }

        // Validate quiz type
        if ($this->quizType !== null) {
            if (!in_array($this->quizType, self::VALID_QUIZ_TYPES, true)) {
                throw new CanvasApiException(
                    'Invalid quiz type. Must be one of: ' . implode(', ', self::VALID_QUIZ_TYPES)
                );
            }
        }

        // Validate time limit
        if ($this->timeLimit !== null && $this->timeLimit < 0) {
            throw new CanvasApiException('Time limit must be non-negative');
        }

        // Validate allowed attempts
        if ($this->allowedAttempts !== null && $this->allowedAttempts < -1) {
            throw new CanvasApiException('Allowed attempts must be -1 (unlimited) or greater');
        }

        // Validate hide results
        if ($this->hideResults !== null) {
            if (!in_array($this->hideResults, self::VALID_HIDE_RESULTS, true)) {
                throw new CanvasApiException(
                    'Invalid hide results value. Must be one of: null, always, until_after_last_attempt'
                );
            }
        }

        if ($this->id) {
            // Update existing quiz
            $updateData = $this->toDtoArray();
            if (empty($updateData)) {
                return $this; // Nothing to update
            }

            $updatedQuiz = self::update($this->id, $updateData);
            // Update current instance with response data
            foreach ($updatedQuiz->toArray() as $key => $value) {
                $property = lcfirst(str_replace('_', '', ucwords($key, '_')));
                if ($this->isSafeToUpdateProperty($property)) {
                    $this->{$property} = $value;
                }
            }
        } else {
            // Create new quiz
            $createData = $this->toDtoArray();

            $newQuiz = self::create($createData);
            // Update current instance with response data
            foreach ($newQuiz->toArray() as $key => $value) {
                $property = lcfirst(str_replace('_', '', ucwords($key, '_')));
                if ($this->isSafeToUpdateProperty($property)) {
                    $this->{$property} = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Delete the quiz
     *
     * @return self
     * @throws CanvasApiException
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Quiz ID is required for deletion');
        }

        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/quizzes/%d', self::$course->id, $this->id);
        self::$apiClient->delete($endpoint);

        return $this;
    }

    /**
     * Publish the quiz
     *
     * @return self
     * @throws CanvasApiException
     */
    public function publish(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Quiz ID is required for publishing');
        }

        $updatedQuiz = self::update($this->id, ['published' => true]);
        $this->published = $updatedQuiz->published;
        $this->workflowState = $updatedQuiz->workflowState;
        return $this;
    }

    /**
     * Unpublish the quiz
     *
     * @return self
     * @throws CanvasApiException
     */
    public function unpublish(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Quiz ID is required for unpublishing');
        }

        $updatedQuiz = self::update($this->id, ['published' => false]);
        $this->published = $updatedQuiz->published;
        $this->workflowState = $updatedQuiz->workflowState;
        return $this;
    }

    /**
     * Check if the quiz is published
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->published === true || $this->workflowState === 'published';
    }

    /**
     * Get all submissions for this quiz
     *
     * @param mixed[] $params Optional parameters for filtering submissions
     * @return QuizSubmission[] Array of quiz submission instances
     * @throws CanvasApiException If course not set or API error
     */
    public function getSubmissions(array $params = []): array
    {
        QuizSubmission::setCourse(self::$course);
        QuizSubmission::setQuiz($this);
        return QuizSubmission::fetchAll($params);
    }

    /**
     * Get current user's submission for this quiz
     *
     * @return QuizSubmission|null Quiz submission instance or null if no submission
     * @throws CanvasApiException If course not set or API error
     */
    public function getCurrentUserSubmission(): ?QuizSubmission
    {
        QuizSubmission::setCourse(self::$course);
        QuizSubmission::setQuiz($this);
        return QuizSubmission::getCurrentUserSubmission();
    }

    /**
     * Start a new submission for this quiz
     *
     * @param mixed[] $params Optional parameters like access_code
     * @return QuizSubmission Created quiz submission instance
     * @throws CanvasApiException If course not set or API error
     */
    public function startSubmission(array $params = []): QuizSubmission
    {
        QuizSubmission::setCourse(self::$course);
        QuizSubmission::setQuiz($this);
        return QuizSubmission::start($params);
    }

    /**
     * Get paginated submissions for this quiz
     *
     * @param mixed[] $params Optional parameters for filtering
     * @return PaginationResult Pagination result with submissions
     * @throws CanvasApiException If course not set or API error
     */
    public function getSubmissionsPaginated(array $params = []): PaginationResult
    {
        QuizSubmission::setCourse(self::$course);
        QuizSubmission::setQuiz($this);
        return QuizSubmission::fetchPage($params);
    }

    /**
     * Get the API endpoint for this resource
     * @return string
     * @throws CanvasApiException
     */
    protected static function getEndpoint(): string
    {
        self::checkCourse();
        return sprintf('courses/%d/quizzes', self::$course->getId());
    }
}
