<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Enrollments;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Users\User;
use CanvasLMS\Api\Sections\Section;
use CanvasLMS\Dto\Enrollments\CreateEnrollmentDTO;
use CanvasLMS\Dto\Enrollments\UpdateEnrollmentDTO;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * Canvas LMS Enrollment API Class
 *
 * Represents a user's enrollment in a course, providing CRUD operations and
 * relationship access to related User and Course objects.
 *
 * ## Relationship Methods
 *
 * This class provides explicit relationship methods that avoid conflicts with
 * Canvas API data structure:
 *
 * @example Basic relationship access
 * ```php
 * // Set course context for enrollment operations
 * $course = Course::find(123);
 * Enrollment::setCourse($course);
 *
 * // Find and work with an enrollment
 * $enrollment = Enrollment::find(456);
 *
 * // Access related objects using explicit methods
 * $user = $enrollment->user();      // Returns User object
 * $course = $enrollment->course();  // Returns Course object
 *
 * echo $user->getName();
 * echo $course->getName();
 * ```
 *
 * @example Working with enrollment collections
 * ```php
 * // Get enrollments from course perspective
 * $course = Course::find(123);
 * $enrollments = $course->enrollments();
 *
 * // Get enrollments from user perspective
 * $user = User::find(456);
 * $enrollments = $user->enrollments();
 *
 * foreach ($enrollments as $enrollment) {
 *     echo $enrollment->getTypeName() . ': ' . $enrollment->getStateName();
 * }
 * ```
 *
 * @example Pagination for large datasets (IMPORTANT for large institutions)
 * ```php
 * // ⚠️ CAUTION: Universities can have MILLIONS of enrollments!
 *
 * // ✅ GOOD: Process in batches
 * $page = 1;
 * do {
 *     $batch = Enrollment::paginate(['page' => $page++, 'per_page' => 500]);
 *     foreach ($batch->getData() as $enrollment) {
 *         // Process enrollment...
 *     }
 * } while ($batch->hasNextPage());
 *
 * // ❌ DANGEROUS: Could crash with memory exhaustion
 * // $allEnrollments = Enrollment::all(); // DON'T DO THIS IN PRODUCTION!
 *
 * // ✅ GOOD: Get just what you need
 * $activeEnrollments = Enrollment::get(['state' => ['active'], 'per_page' => 100]);
 * ```
 *
 * @package CanvasLMS\Api\Enrollments
 */
class Enrollment extends AbstractBaseApi
{
    // Course context (required pattern)
    protected static ?Course $course = null;

    // Core enrollment properties
    public ?int $id = null;
    public ?int $userId = null;
    public ?int $courseId = null;
    public ?string $type = null;
    public ?string $enrollmentState = null;
    public ?int $sectionId = null;
    public ?int $roleId = null;
    public ?bool $limitPrivilegesToCourseSection = null;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    // Canvas-specific properties
    public ?string $role = null;
    public ?float $currentScore = null;
    public ?string $currentGrade = null;
    public ?float $finalScore = null;
    public ?string $finalGrade = null;
    /** @var mixed[]|null */
    public ?array $grades = null;
    /** @var mixed[]|null */
    public ?array $user = null;
    public ?int $rootAccountId = null;

    // Extended Canvas API properties
    public ?string $uuid = null;
    public ?float $currentPoints = null;
    public ?float $unpostedCurrentPoints = null;
    public ?float $unpostedCurrentScore = null;
    public ?string $unpostedCurrentGrade = null;
    public ?float $unpostedFinalScore = null;
    public ?string $unpostedFinalGrade = null;
    public ?int $totalActivityTime = null;
    public ?string $lastActivityAt = null;
    public ?string $startAt = null;
    public ?string $endAt = null;
    /** @var mixed[]|null */
    public ?array $observedUsers = null;
    public ?bool $canBeRemoved = null;
    public ?bool $locked = null;
    /** @var int[]|null */
    public ?array $groupIds = null;
    public ?string $sisAccountId = null;
    public ?string $sisCourseId = null;
    public ?string $sisSectionId = null;
    public ?string $sisUserId = null;
    public ?int $enrollmentTermId = null;
    public ?int $gradingPeriodId = null;

    // Canvas enrollment type constants
    public const TYPE_STUDENT = 'StudentEnrollment';
    public const TYPE_TEACHER = 'TeacherEnrollment';
    public const TYPE_TA = 'TaEnrollment';
    public const TYPE_OBSERVER = 'ObserverEnrollment';
    public const TYPE_DESIGNER = 'DesignerEnrollment';

    // Canvas enrollment states (complete list from Canvas API)
    public const STATE_ACTIVE = 'active';
    public const STATE_INVITED = 'invited';
    public const STATE_CREATION_PENDING = 'creation_pending';
    public const STATE_DELETED = 'deleted';
    public const STATE_REJECTED = 'rejected';
    public const STATE_COMPLETED = 'completed';
    public const STATE_INACTIVE = 'inactive';

    // Notification preferences
    public const NOTIFY_TRUE = true;
    public const NOTIFY_FALSE = false;


    /**
     * Set the course context for enrollment operations
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Check if course context is set and valid
     */
    public static function checkCourse(): bool
    {
        if (!isset(self::$course) || !isset(self::$course->id)) {
            throw new CanvasApiException('Course is required for enrollment operations');
        }
        return true;
    }

    /**
     * Find a specific enrollment by ID
     */
    /**
     * @return self
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/enrollments/%d', self::$course->id, $id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode((string) $response->getBody(), true);

        return new self($data);
    }

    /**
     * Fetch all enrollments for the current course
     */
    /**
     * @param mixed[] $params
     * @return self[]
     */
    /**
     * @param array<string, mixed> $params
     * @return array<self>
     */
    public static function get(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/enrollments', self::$course->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode((string) $response->getBody(), true);

        $enrollments = [];
        foreach ($data as $item) {
            $enrollments[] = new self($item);
        }

        return $enrollments;
    }


    /**
     * Create a new enrollment
     */
    /**
     * @return self
     */
    /**
     * @param array<string, mixed>|CreateEnrollmentDTO $data
     * @return self
     */
    public static function create(array|CreateEnrollmentDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateEnrollmentDTO($data);
        }

        $endpoint = sprintf('courses/%d/enrollments', self::$course->id);
        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $responseData = json_decode((string) $response->getBody(), true);

        return new self($responseData);
    }

    /**
     * Update an existing enrollment
     */
    /**
     * @return self
     */
    /**
     * @param array<string, mixed>|UpdateEnrollmentDTO $data
     * @return self
     */
    public static function update(int $id, array|UpdateEnrollmentDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateEnrollmentDTO($data);
        }

        $endpoint = sprintf('courses/%d/enrollments/%d', self::$course->id, $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $data->toApiArray()]);
        $responseData = json_decode((string) $response->getBody(), true);

        return new self($responseData);
    }

    /**
     * Accept an enrollment invitation
     */
    /**
     * @return self
     */
    public static function accept(int $enrollmentId): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/enrollments/%d/accept', self::$course->id, $enrollmentId);
        $response = self::$apiClient->post($endpoint);
        $data = json_decode((string) $response->getBody(), true);

        return new self($data);
    }

    /**
     * Reject an enrollment invitation
     */
    /**
     * @return self
     */
    public static function reject(int $enrollmentId): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/enrollments/%d/reject', self::$course->id, $enrollmentId);
        $response = self::$apiClient->post($endpoint);
        $data = json_decode((string) $response->getBody(), true);

        return new self($data);
    }

    /**
     * Reactivate a deleted enrollment
     */
    /**
     * @return self
     */
    public static function reactivate(int $enrollmentId): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/enrollments/%d/reactivate', self::$course->id, $enrollmentId);
        $response = self::$apiClient->put($endpoint);
        $data = json_decode((string) $response->getBody(), true);

        return new self($data);
    }

    /**
     * Fetch enrollments by section
     */
    /**
     * @param mixed[] $params
     * @return self[]
     */
    /**
     * @param array<string, mixed> $params
     * @return array<self>
     */
    public static function fetchAllBySection(int $sectionId, array $params = []): array
    {
        self::checkApiClient();

        $endpoint = sprintf('sections/%d/enrollments', $sectionId);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode((string) $response->getBody(), true);

        $enrollments = [];
        foreach ($data as $item) {
            $enrollments[] = new self($item);
        }

        return $enrollments;
    }

    /**
     * Fetch enrollments by user
     */
    /**
     * @param mixed[] $params
     * @return self[]
     */
    /**
     * @param array<string, mixed> $params
     * @return array<self>
     */
    public static function fetchAllByUser(int $userId, array $params = []): array
    {
        self::checkApiClient();

        $endpoint = sprintf('users/%d/enrollments', $userId);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode((string) $response->getBody(), true);

        $enrollments = [];
        foreach ($data as $item) {
            $enrollments[] = new self($item);
        }

        return $enrollments;
    }

    /**
     * Save the current enrollment (create or update)
     *
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        // Validation - user ID and type are required for new enrollments
        if (!$this->id && (empty($this->userId) || empty($this->type))) {
            throw new CanvasApiException('User ID and enrollment type are required for new enrollments');
        }

        // Validate enrollment type
        if ($this->type && !$this->validateEnrollmentType($this->type)) {
            throw new CanvasApiException('Invalid enrollment type: ' . $this->type);
        }

        // Validate enrollment state
        if ($this->enrollmentState && !$this->validateEnrollmentState($this->enrollmentState)) {
            throw new CanvasApiException('Invalid enrollment state: ' . $this->enrollmentState);
        }

        if ($this->id) {
            // Update existing enrollment
            $updateData = $this->toDtoArray();
            if (empty($updateData)) {
                return $this; // Nothing to update
            }
            $updated = self::update($this->id, $updateData);
            $this->populate($updated->toArray());
        } else {
            // Create new enrollment
            $createData = $this->toDtoArray();
            $new = self::create($createData);
            $this->populate($new->toArray());
        }
        return $this;
    }

    /**
     * Delete the current enrollment
     *
     * @return self
     * @throws CanvasApiException
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Enrollment ID is required for deletion');
        }

        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/enrollments/%d', self::$course->id, $this->id);
        self::$apiClient->delete($endpoint);

        return $this;
    }

    /**
     * Validate enrollment type
     */
    private function validateEnrollmentType(string $type): bool
    {
        return in_array($type, [
            self::TYPE_STUDENT,
            self::TYPE_TEACHER,
            self::TYPE_TA,
            self::TYPE_OBSERVER,
            self::TYPE_DESIGNER,
        ], true);
    }

    /**
     * Validate enrollment state
     */
    private function validateEnrollmentState(string $state): bool
    {
        return in_array($state, [
            self::STATE_ACTIVE,
            self::STATE_INVITED,
            self::STATE_CREATION_PENDING,
            self::STATE_DELETED,
            self::STATE_REJECTED,
            self::STATE_COMPLETED,
            self::STATE_INACTIVE,
        ], true);
    }

    /**
     * Convert enrollment to array format
     */
    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'course_id' => $this->courseId,
            'type' => $this->type,
            'enrollment_state' => $this->enrollmentState,
            'section_id' => $this->sectionId,
            'role_id' => $this->roleId,
            'limit_privileges_to_course_section' => $this->limitPrivilegesToCourseSection,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'role' => $this->role,
            'current_score' => $this->currentScore,
            'current_grade' => $this->currentGrade,
            'final_score' => $this->finalScore,
            'final_grade' => $this->finalGrade,
            'grades' => $this->grades,
            'user' => $this->user,
            'root_account_id' => $this->rootAccountId,
            'uuid' => $this->uuid,
            'current_points' => $this->currentPoints,
            'unposted_current_points' => $this->unpostedCurrentPoints,
            'unposted_current_score' => $this->unpostedCurrentScore,
            'unposted_current_grade' => $this->unpostedCurrentGrade,
            'unposted_final_score' => $this->unpostedFinalScore,
            'unposted_final_grade' => $this->unpostedFinalGrade,
            'total_activity_time' => $this->totalActivityTime,
            'last_activity_at' => $this->lastActivityAt,
            'start_at' => $this->startAt,
            'end_at' => $this->endAt,
            'observed_users' => $this->observedUsers,
            'can_be_removed' => $this->canBeRemoved,
            'locked' => $this->locked,
            'group_ids' => $this->groupIds,
            'sis_account_id' => $this->sisAccountId,
            'sis_course_id' => $this->sisCourseId,
            'sis_section_id' => $this->sisSectionId,
            'sis_user_id' => $this->sisUserId,
            'enrollment_term_id' => $this->enrollmentTermId,
            'grading_period_id' => $this->gradingPeriodId,
        ];
    }

    /**
     * Convert to DTO array format for API operations
     */
    /**
     * @return mixed[]
     */
    protected function toDtoArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'type' => $this->type,
            'enrollment_state' => $this->enrollmentState,
            'course_section_id' => $this->sectionId,
            'role_id' => $this->roleId,
            'limit_privileges_to_course_section' => $this->limitPrivilegesToCourseSection,
            'start_at' => $this->startAt,
            'end_at' => $this->endAt,
            'sis_user_id' => $this->sisUserId,
        ], fn($value) => $value !== null);
    }

    // Getter methods
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getCourseId(): ?int
    {
        return $this->courseId;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getEnrollmentState(): ?string
    {
        return $this->enrollmentState;
    }

    public function getSectionId(): ?int
    {
        return $this->sectionId;
    }

    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public function isLimitPrivilegesToCourseSection(): ?bool
    {
        return $this->limitPrivilegesToCourseSection;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getCurrentScore(): ?float
    {
        return $this->currentScore;
    }

    public function getCurrentGrade(): ?string
    {
        return $this->currentGrade;
    }

    public function getFinalScore(): ?float
    {
        return $this->finalScore;
    }

    public function getFinalGrade(): ?string
    {
        return $this->finalGrade;
    }

    /**
     * @return mixed[]|null
     */
    public function getGrades(): ?array
    {
        return $this->grades;
    }

    /**
     * Get the user data array (raw Canvas API data)
     *
     * @return mixed[]|null
     */
    public function getUserData(): ?array
    {
        return $this->user;
    }

    public function getRootAccountId(): ?int
    {
        return $this->rootAccountId;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getCurrentPoints(): ?float
    {
        return $this->currentPoints;
    }

    public function getUnpostedCurrentPoints(): ?float
    {
        return $this->unpostedCurrentPoints;
    }

    public function getUnpostedCurrentScore(): ?float
    {
        return $this->unpostedCurrentScore;
    }

    public function getUnpostedCurrentGrade(): ?string
    {
        return $this->unpostedCurrentGrade;
    }

    public function getUnpostedFinalScore(): ?float
    {
        return $this->unpostedFinalScore;
    }

    public function getUnpostedFinalGrade(): ?string
    {
        return $this->unpostedFinalGrade;
    }

    public function getTotalActivityTime(): ?int
    {
        return $this->totalActivityTime;
    }

    public function getLastActivityAt(): ?string
    {
        return $this->lastActivityAt;
    }

    public function getStartAt(): ?string
    {
        return $this->startAt;
    }

    public function getEndAt(): ?string
    {
        return $this->endAt;
    }

    /**
     * @return mixed[]|null
     */
    public function getObservedUsers(): ?array
    {
        return $this->observedUsers;
    }

    public function canBeRemoved(): ?bool
    {
        return $this->canBeRemoved;
    }

    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    /**
     * @return int[]|null
     */
    public function getGroupIds(): ?array
    {
        return $this->groupIds;
    }

    public function getSisAccountId(): ?string
    {
        return $this->sisAccountId;
    }

    public function getSisCourseId(): ?string
    {
        return $this->sisCourseId;
    }

    public function getSisSectionId(): ?string
    {
        return $this->sisSectionId;
    }

    public function getSisUserId(): ?string
    {
        return $this->sisUserId;
    }

    public function getEnrollmentTermId(): ?int
    {
        return $this->enrollmentTermId;
    }

    public function getGradingPeriodId(): ?int
    {
        return $this->gradingPeriodId;
    }

    // Setter methods
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function setEnrollmentState(?string $enrollmentState): void
    {
        $this->enrollmentState = $enrollmentState;
    }

    public function setSectionId(?int $sectionId): void
    {
        $this->sectionId = $sectionId;
    }

    public function setRoleId(?int $roleId): void
    {
        $this->roleId = $roleId;
    }

    public function setLimitPrivilegesToCourseSection(?bool $limitPrivilegesToCourseSection): void
    {
        $this->limitPrivilegesToCourseSection = $limitPrivilegesToCourseSection;
    }

    public function setStartAt(?string $startAt): void
    {
        $this->startAt = $startAt;
    }

    public function setEndAt(?string $endAt): void
    {
        $this->endAt = $endAt;
    }

    public function setSisUserId(?string $sisUserId): void
    {
        $this->sisUserId = $sisUserId;
    }

    // Relationship Method Aliases

    /**
     * Get the associated Course object (relationship method alias)
     *
     * This method provides a clean, explicit way to access the related course
     * without conflicts with Canvas API data structure.
     *
     * @example
     * ```php
     * $enrollment = Enrollment::find(123);
     * $course = $enrollment->course(); // Returns Course object or null
     * echo $course->getName();
     * ```
     *
     * @return Course|null The course object or null if no courseId is set
     * @throws CanvasApiException If course cannot be loaded
     */
    public function course(): ?Course
    {
        return $this->getCourse();
    }

    /**
     * Get the associated User object (relationship method alias)
     *
     * This method provides a clean, explicit way to access the related user
     * without conflicts with Canvas API data structure.
     *
     * @example
     * ```php
     * $enrollment = Enrollment::find(123);
     * $user = $enrollment->user(); // Returns User object or null
     * echo $user->getName();
     * ```
     *
     * @return User|null The user object or null if no userId is set
     * @throws CanvasApiException If user cannot be loaded
     */
    public function user(): ?User
    {
        return $this->getUser();
    }

    // Relationship Methods

    /**
     * Get the associated User object
     *
     * If user data is embedded in the enrollment (from Canvas API include),
     * creates a User instance from that data. Otherwise, fetches the user
     * from the API using the userId.
     *
     * @return User|null The user object or null if no userId is set
     * @throws CanvasApiException If user cannot be loaded
     */
    public function getUser(): ?User
    {
        if (!$this->userId) {
            return null;
        }

        // If we have embedded user data from the API response, use it
        if (is_array($this->user ?? null)) {
            return new User($this->user);
        }

        // Otherwise, fetch the user from the API
        try {
            return User::find($this->userId);
        } catch (\Exception $e) {
            throw new CanvasApiException("Could not load user with ID {$this->userId}: " . $e->getMessage());
        }
    }

    /**
     * Get the associated Course object
     *
     * If the static course context is set and matches this enrollment's courseId,
     * returns that course. Otherwise, fetches the course from the API.
     *
     * @return Course|null The course object or null if no courseId is set
     * @throws CanvasApiException If course cannot be loaded
     */
    public function getCourse(): ?Course
    {
        if (!$this->courseId) {
            return null;
        }

        // If the static course context matches this enrollment's course, use it
        if (isset(self::$course) && self::$course->id === $this->courseId) {
            return self::$course;
        }

        // Otherwise, fetch the course from the API
        try {
            return Course::find($this->courseId);
        } catch (\Exception $e) {
            throw new CanvasApiException("Could not load course with ID {$this->courseId}: " . $e->getMessage());
        }
    }

    /**
     * Check if this enrollment is for a student
     */
    public function isStudent(): bool
    {
        return $this->type === self::TYPE_STUDENT;
    }

    /**
     * Check if this enrollment is for a teacher
     */
    public function isTeacher(): bool
    {
        return $this->type === self::TYPE_TEACHER;
    }

    /**
     * Check if this enrollment is for a TA
     */
    public function isTa(): bool
    {
        return $this->type === self::TYPE_TA;
    }

    /**
     * Check if this enrollment is for an observer
     */
    public function isObserver(): bool
    {
        return $this->type === self::TYPE_OBSERVER;
    }

    /**
     * Check if this enrollment is for a designer
     */
    public function isDesigner(): bool
    {
        return $this->type === self::TYPE_DESIGNER;
    }

    /**
     * Check if this enrollment is active
     */
    public function isActive(): bool
    {
        return $this->enrollmentState === self::STATE_ACTIVE;
    }

    /**
     * Check if this enrollment is pending (invited)
     */
    public function isPending(): bool
    {
        return $this->enrollmentState === self::STATE_INVITED ||
               $this->enrollmentState === self::STATE_CREATION_PENDING;
    }

    /**
     * Check if this enrollment is completed
     */
    public function isCompleted(): bool
    {
        return $this->enrollmentState === self::STATE_COMPLETED;
    }

    /**
     * Check if this enrollment is inactive
     */
    public function isInactive(): bool
    {
        return $this->enrollmentState === self::STATE_INACTIVE;
    }

    /**
     * Get a human-readable enrollment type name
     */
    public function getTypeName(): string
    {
        return match ($this->type) {
            self::TYPE_STUDENT => 'Student',
            self::TYPE_TEACHER => 'Teacher',
            self::TYPE_TA => 'Teaching Assistant',
            self::TYPE_OBSERVER => 'Observer',
            self::TYPE_DESIGNER => 'Designer',
            default => $this->type ?? 'Unknown'
        };
    }

    /**
     * Get a human-readable enrollment state name
     */
    public function getStateName(): string
    {
        return match ($this->enrollmentState) {
            self::STATE_ACTIVE => 'Active',
            self::STATE_INVITED => 'Invited',
            self::STATE_CREATION_PENDING => 'Creation Pending',
            self::STATE_DELETED => 'Deleted',
            self::STATE_REJECTED => 'Rejected',
            self::STATE_COMPLETED => 'Completed',
            self::STATE_INACTIVE => 'Inactive',
            default => $this->enrollmentState ?? 'Unknown'
        };
    }

    /**
     * Get the section for this enrollment
     *
     * @return Section|null The section object or null if no section ID is set
     * @throws CanvasApiException If the section cannot be loaded
     */
    public function section(): ?Section
    {
        if (!$this->sectionId) {
            return null;
        }

        try {
            return Section::find($this->sectionId);
        } catch (\Exception $e) {
            throw new CanvasApiException("Could not load section with ID {$this->sectionId}: " . $e->getMessage());
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
        return sprintf('courses/%d/enrollments', self::$course->getId());
    }
}
