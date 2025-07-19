<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Assignments;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Assignments\CreateAssignmentDTO;
use CanvasLMS\Dto\Assignments\UpdateAssignmentDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Canvas LMS Assignments API
 *
 * Provides functionality to manage assignments in Canvas LMS.
 * This class handles creating, reading, updating, and deleting assignments for a specific course.
 *
 * Usage Examples:
 *
 * // Set course context (required for all operations)
 * $course = Course::find(123);
 * Assignment::setCourse($course);
 *
 * // Create a new assignment
 * $assignmentData = [
 *     'name' => 'Homework Assignment 1',
 *     'description' => 'Complete exercises 1-10',
 *     'points_possible' => 100,
 *     'due_at' => '2024-12-31T23:59:59Z'
 * ];
 * $assignment = Assignment::create($assignmentData);
 *
 * // Find an assignment by ID
 * $assignment = Assignment::find(456);
 *
 * // List all assignments for the course
 * $assignments = Assignment::fetchAll();
 *
 * // Get paginated assignments
 * $paginatedAssignments = Assignment::fetchAllPaginated();
 * $paginationResult = Assignment::fetchPage();
 *
 * // Update an assignment
 * $updatedAssignment = Assignment::update(456, ['points_possible' => 150]);
 *
 * // Update using DTO
 * $updateDto = new UpdateAssignmentDTO(['name' => 'Updated Assignment Name']);
 * $updatedAssignment = Assignment::update(456, $updateDto);
 *
 * // Update using instance method
 * $assignment = Assignment::find(456);
 * $assignment->setPointsPossible(125);
 * $success = $assignment->save();
 *
 * // Delete an assignment
 * $assignment = Assignment::find(456);
 * $success = $assignment->delete();
 *
 * // Duplicate an assignment
 * $duplicatedAssignment = Assignment::duplicate(456);
 *
 * @package CanvasLMS\Api\Assignments
 */
class Assignment extends AbstractBaseApi
{
    protected static Course $course;

    /**
     * Assignment unique identifier
     */
    public ?int $id = null;

    /**
     * Assignment name
     */
    public ?string $name = null;

    /**
     * Course ID this assignment belongs to
     */
    public ?int $courseId = null;

    /**
     * Assignment group ID
     */
    public ?int $assignmentGroupId = null;

    /**
     * Assignment description (HTML)
     */
    public ?string $description = null;

    /**
     * Assignment position in the group
     */
    public ?int $position = null;

    /**
     * Maximum points possible for this assignment
     */
    public ?float $pointsPossible = null;

    /**
     * Grading type (points, percent, pass_fail, etc.)
     */
    public ?string $gradingType = null;

    /**
     * Allowed submission types
     * @var array<string>|null
     */
    public ?array $submissionTypes = null;

    /**
     * Allowed file extensions for submissions
     * @var array<string>|null
     */
    public ?array $allowedExtensions = null;

    /**
     * Maximum number of submission attempts allowed
     */
    public ?int $allowedAttempts = null;

    /**
     * Assignment due date
     */
    public ?string $dueAt = null;

    /**
     * Date when assignment becomes locked
     */
    public ?string $lockAt = null;

    /**
     * Date when assignment becomes available
     */
    public ?string $unlockAt = null;

    /**
     * All date variations for the assignment
     * @var array<string, mixed>|null
     */
    public ?array $allDates = null;

    /**
     * Whether the assignment is published
     */
    public ?bool $published = null;

    /**
     * Assignment workflow state (published, unpublished, etc.)
     */
    public ?string $workflowState = null;

    /**
     * Whether assignment is locked for the current user
     */
    public ?bool $lockedForUser = null;

    /**
     * Whether assignment is only visible to users with overrides
     */
    public ?bool $onlyVisibleToOverrides = null;

    /**
     * Whether peer reviews are enabled
     */
    public ?bool $peerReviews = null;

    /**
     * Whether grading is anonymous
     */
    public ?bool $anonymousGrading = null;

    /**
     * Whether moderated grading is enabled
     */
    public ?bool $moderatedGrading = null;

    /**
     * Group category ID for group assignments
     */
    public ?int $groupCategoryId = null;

    /**
     * HTML URL to the assignment
     */
    public ?string $htmlUrl = null;

    /**
     * Whether the assignment has date overrides
     */
    public ?bool $hasOverrides = null;

    /**
     * Assignment creation timestamp
     */
    public ?string $createdAt = null;

    /**
     * Assignment last update timestamp
     */
    public ?string $updatedAt = null;

    /**
     * Create a new Assignment instance
     *
     * @param array<string, mixed> $data Assignment data from Canvas API
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Set the course context for assignment operations
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
     * Get assignment ID
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set assignment ID
     *
     * @param int|null $id
     * @return void
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get assignment name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set assignment name
     *
     * @param string|null $name
     * @return void
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
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
     * Get assignment description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set assignment description
     *
     * @param string|null $description
     * @return void
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get assignment position
     *
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Set assignment position
     *
     * @param int|null $position
     * @return void
     */
    public function setPosition(?int $position): void
    {
        $this->position = $position;
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
     * Get grading type
     *
     * @return string|null
     */
    public function getGradingType(): ?string
    {
        return $this->gradingType;
    }

    /**
     * Set grading type
     *
     * @param string|null $gradingType
     * @return void
     */
    public function setGradingType(?string $gradingType): void
    {
        $this->gradingType = $gradingType;
    }

    /**
     * Get submission types
     *
     * @return array<string>|null
     */
    public function getSubmissionTypes(): ?array
    {
        return $this->submissionTypes;
    }

    /**
     * Set submission types
     *
     * @param array<string>|null $submissionTypes
     * @return void
     */
    public function setSubmissionTypes(?array $submissionTypes): void
    {
        $this->submissionTypes = $submissionTypes;
    }

    /**
     * Get allowed extensions
     *
     * @return array<string>|null
     */
    public function getAllowedExtensions(): ?array
    {
        return $this->allowedExtensions;
    }

    /**
     * Set allowed extensions
     *
     * @param array<string>|null $allowedExtensions
     * @return void
     */
    public function setAllowedExtensions(?array $allowedExtensions): void
    {
        $this->allowedExtensions = $allowedExtensions;
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
     * Get locked for user status
     *
     * @return bool|null
     */
    public function getLockedForUser(): ?bool
    {
        return $this->lockedForUser;
    }

    /**
     * Set locked for user status
     *
     * @param bool|null $lockedForUser
     * @return void
     */
    public function setLockedForUser(?bool $lockedForUser): void
    {
        $this->lockedForUser = $lockedForUser;
    }

    /**
     * Get only visible to overrides status
     *
     * @return bool|null
     */
    public function getOnlyVisibleToOverrides(): ?bool
    {
        return $this->onlyVisibleToOverrides;
    }

    /**
     * Set only visible to overrides status
     *
     * @param bool|null $onlyVisibleToOverrides
     * @return void
     */
    public function setOnlyVisibleToOverrides(?bool $onlyVisibleToOverrides): void
    {
        $this->onlyVisibleToOverrides = $onlyVisibleToOverrides;
    }

    /**
     * Get peer reviews status
     *
     * @return bool|null
     */
    public function getPeerReviews(): ?bool
    {
        return $this->peerReviews;
    }

    /**
     * Set peer reviews status
     *
     * @param bool|null $peerReviews
     * @return void
     */
    public function setPeerReviews(?bool $peerReviews): void
    {
        $this->peerReviews = $peerReviews;
    }

    /**
     * Get anonymous grading status
     *
     * @return bool|null
     */
    public function getAnonymousGrading(): ?bool
    {
        return $this->anonymousGrading;
    }

    /**
     * Set anonymous grading status
     *
     * @param bool|null $anonymousGrading
     * @return void
     */
    public function setAnonymousGrading(?bool $anonymousGrading): void
    {
        $this->anonymousGrading = $anonymousGrading;
    }

    /**
     * Get moderated grading status
     *
     * @return bool|null
     */
    public function getModeratedGrading(): ?bool
    {
        return $this->moderatedGrading;
    }

    /**
     * Set moderated grading status
     *
     * @param bool|null $moderatedGrading
     * @return void
     */
    public function setModeratedGrading(?bool $moderatedGrading): void
    {
        $this->moderatedGrading = $moderatedGrading;
    }

    /**
     * Get group category ID
     *
     * @return int|null
     */
    public function getGroupCategoryId(): ?int
    {
        return $this->groupCategoryId;
    }

    /**
     * Set group category ID
     *
     * @param int|null $groupCategoryId
     * @return void
     */
    public function setGroupCategoryId(?int $groupCategoryId): void
    {
        $this->groupCategoryId = $groupCategoryId;
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
     * Get has overrides status
     *
     * @return bool|null
     */
    public function getHasOverrides(): ?bool
    {
        return $this->hasOverrides;
    }

    /**
     * Set has overrides status
     *
     * @param bool|null $hasOverrides
     * @return void
     */
    public function setHasOverrides(?bool $hasOverrides): void
    {
        $this->hasOverrides = $hasOverrides;
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
     * Convert assignment to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'course_id' => $this->courseId,
            'assignment_group_id' => $this->assignmentGroupId,
            'description' => $this->description,
            'position' => $this->position,
            'points_possible' => $this->pointsPossible,
            'grading_type' => $this->gradingType,
            'submission_types' => $this->submissionTypes,
            'allowed_extensions' => $this->allowedExtensions,
            'allowed_attempts' => $this->allowedAttempts,
            'due_at' => $this->dueAt,
            'lock_at' => $this->lockAt,
            'unlock_at' => $this->unlockAt,
            'all_dates' => $this->allDates,
            'published' => $this->published,
            'workflow_state' => $this->workflowState,
            'locked_for_user' => $this->lockedForUser,
            'only_visible_to_overrides' => $this->onlyVisibleToOverrides,
            'peer_reviews' => $this->peerReviews,
            'anonymous_grading' => $this->anonymousGrading,
            'moderated_grading' => $this->moderatedGrading,
            'group_category_id' => $this->groupCategoryId,
            'html_url' => $this->htmlUrl,
            'has_overrides' => $this->hasOverrides,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Convert assignment to DTO array format
     *
     * @return array<string, mixed>
     */
    public function toDtoArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'points_possible' => $this->pointsPossible,
            'grading_type' => $this->gradingType,
            'submission_types' => $this->submissionTypes,
            'allowed_extensions' => $this->allowedExtensions,
            'allowed_attempts' => $this->allowedAttempts,
            'due_at' => $this->dueAt,
            'lock_at' => $this->lockAt,
            'unlock_at' => $this->unlockAt,
            'published' => $this->published,
            'assignment_group_id' => $this->assignmentGroupId,
            'position' => $this->position,
            'only_visible_to_overrides' => $this->onlyVisibleToOverrides,
            'peer_reviews' => $this->peerReviews,
            'anonymous_grading' => $this->anonymousGrading,
            'moderated_grading' => $this->moderatedGrading,
            'group_category_id' => $this->groupCategoryId,
        ], fn($value) => $value !== null);
    }

    /**
     * Find a single assignment by ID
     *
     * @param int $id Assignment ID
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/assignments/%d', self::$course->id, $id);
        $response = self::$apiClient->get($endpoint);
        $assignmentData = json_decode($response->getBody()->getContents(), true);

        return new self($assignmentData);
    }

    /**
     * Fetch all assignments for the course
     *
     * @param array<string, mixed> $params Optional parameters
     * @return array<Assignment> Array of Assignment objects
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/assignments', self::$course->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $assignmentsData = json_decode($response->getBody()->getContents(), true);

        $assignments = [];
        foreach ($assignmentsData as $assignmentData) {
            $assignments[] = new self($assignmentData);
        }

        return $assignments;
    }

    /**
     * Fetch all assignments with pagination support
     *
     * @param array<string, mixed> $params Optional parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/assignments', self::$course->id);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Fetch a single page of assignments
     *
     * @param array<string, mixed> $params Optional parameters
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/assignments', self::$course->id);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch all pages of assignments
     *
     * @param array<string, mixed> $params Optional parameters
     * @return array<Assignment> Array of Assignment objects from all pages
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/assignments', self::$course->id);
        return self::fetchAllPagesAsModels($endpoint, $params);
    }

    /**
     * Create a new assignment
     *
     * @param array<string, mixed>|CreateAssignmentDTO $data Assignment data
     * @return self Created Assignment object
     * @throws CanvasApiException
     */
    public static function create(array|CreateAssignmentDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateAssignmentDTO($data);
        }

        $endpoint = sprintf('courses/%d/assignments', self::$course->id);
        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $assignmentData = json_decode($response->getBody()->getContents(), true);

        return new self($assignmentData);
    }

    /**
     * Update an assignment
     *
     * @param int $id Assignment ID
     * @param array<string, mixed>|UpdateAssignmentDTO $data Assignment data
     * @return self Updated Assignment object
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateAssignmentDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateAssignmentDTO($data);
        }

        $endpoint = sprintf('courses/%d/assignments/%d', self::$course->id, $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $data->toApiArray()]);
        $assignmentData = json_decode($response->getBody()->getContents(), true);

        return new self($assignmentData);
    }

    /**
     * Save the current assignment (create or update)
     *
     * @return bool True if save was successful, false otherwise
     * @throws CanvasApiException
     */
    public function save(): bool
    {
        // Check for required fields before trying to save
        if (!$this->id && empty($this->name)) {
            throw new CanvasApiException('Assignment name is required');
        }

        // Validate points possible
        if ($this->pointsPossible !== null && $this->pointsPossible < 0) {
            throw new CanvasApiException('Points possible must be non-negative');
        }

        // Validate grading type
        if ($this->gradingType !== null) {
            $validGradingTypes = ['pass_fail', 'percent', 'letter_grade', 'gpa_scale', 'points'];
            if (!in_array($this->gradingType, $validGradingTypes, true)) {
                throw new CanvasApiException(
                    'Invalid grading type. Must be one of: ' . implode(', ', $validGradingTypes)
                );
            }
        }

        // Validate submission types
        if ($this->submissionTypes !== null && !empty($this->submissionTypes)) {
            $validSubmissionTypes = [
                'discussion_topic', 'online_quiz', 'on_paper', 'none', 'external_tool',
                'online_text_entry', 'online_url', 'online_upload', 'media_recording'
            ];
            foreach ($this->submissionTypes as $type) {
                if (!in_array($type, $validSubmissionTypes, true)) {
                    throw new CanvasApiException("Invalid submission type: {$type}");
                }
            }
        }

        // Validate allowed file extensions
        if ($this->allowedExtensions !== null && !empty($this->allowedExtensions)) {
            foreach ($this->allowedExtensions as $extension) {
                // Basic validation for file extensions
                if (!is_string($extension) || !preg_match('/^[a-zA-Z0-9]+$/', $extension)) {
                    throw new CanvasApiException("Invalid file extension: {$extension}");
                }
            }
        }

        try {
            if ($this->id) {
                // Update existing assignment
                $updateData = $this->toDtoArray();
                if (empty($updateData)) {
                    return true; // Nothing to update
                }

                $updatedAssignment = self::update($this->id, $updateData);
                $this->populate($updatedAssignment->toArray());
            } else {
                // Create new assignment
                $createData = $this->toDtoArray();

                $newAssignment = self::create($createData);
                $this->populate($newAssignment->toArray());
            }

            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Delete the assignment
     *
     * @return bool True if deletion was successful, false otherwise
     * @throws CanvasApiException
     */
    public function delete(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Assignment ID is required for deletion');
        }

        try {
            self::checkCourse();
            self::checkApiClient();

            $endpoint = sprintf('courses/%d/assignments/%d', self::$course->id, $this->id);
            self::$apiClient->delete($endpoint);

            return true;
        } catch (CanvasApiException) {
            return false;
        }
    }

    /**
     * Duplicate an assignment
     *
     * @param int $id Assignment ID to duplicate
     * @param array<string, mixed> $options Duplication options
     * @return self Duplicated Assignment object
     * @throws CanvasApiException
     */
    public static function duplicate(int $id, array $options = []): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/assignments/%d/duplicate', self::$course->id, $id);
        $response = self::$apiClient->post($endpoint, ['multipart' => $options]);
        $assignmentData = json_decode($response->getBody()->getContents(), true);

        return new self($assignmentData);
    }
}
