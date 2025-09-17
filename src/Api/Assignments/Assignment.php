<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Assignments;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Rubrics\Rubric;
use CanvasLMS\Api\Rubrics\RubricAssociation;
use CanvasLMS\Api\Submissions\Submission;
use CanvasLMS\Dto\Assignments\CreateAssignmentDTO;
use CanvasLMS\Dto\Assignments\UpdateAssignmentDTO;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * Canvas LMS Assignments API
 *
 * Provides functionality to manage assignments in Canvas LMS.
 * This class handles creating, reading, updating, and deleting assignments for a specific course.
 *
 * Usage Examples:
 *
 * ```php
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
 * // Get first page of assignments (memory efficient)
 * $assignments = Assignment::get();
 * $assignments = Assignment::get(['order_by' => 'due_at']);
 *
 * // Get ALL assignments from all pages (be mindful of memory)
 * $allAssignments = Assignment::all();
 *
 * // Get paginated results with metadata (recommended)
 * $paginated = Assignment::paginate(['per_page' => 25]);
 * echo "Page {$paginated->getCurrentPage()} of {$paginated->getTotalPages()}";
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
 * ```
 *
 * @package CanvasLMS\Api\Assignments
 */
class Assignment extends AbstractBaseApi
{
    protected static ?Course $course = null;

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
     *
     * @var array<string>|null
     */
    public ?array $submissionTypes = null;

    /**
     * Allowed file extensions for submissions
     *
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
     *
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
     * URL to download all submissions as a zip
     */
    public ?string $submissionsDownloadUrl = null;

    /**
     * Boolean flag indicating whether assignment requires due date
     */
    public ?bool $dueDateRequired = null;

    /**
     * Integer indicating maximum assignment name length
     */
    public ?int $maxNameLength = null;

    /**
     * Whether Turnitin is enabled for this assignment
     */
    public ?bool $turnitinEnabled = null;

    /**
     * Whether VeriCite is enabled for this assignment
     */
    public ?bool $vericiteEnabled = null;

    /**
     * Turnitin settings object
     *
     * @var array<string, mixed>|null
     */
    public ?array $turnitinSettings = null;

    /**
     * Whether group students are graded individually
     */
    public ?bool $gradeGroupStudentsIndividually = null;

    /**
     * External tool tag attributes
     *
     * @var array<string, mixed>|null
     */
    public ?array $externalToolTagAttributes = null;

    /**
     * Whether automatic peer reviews are enabled
     */
    public ?bool $automaticPeerReviews = null;

    /**
     * Number of peer reviews per user
     */
    public ?int $peerReviewCount = null;

    /**
     * Date peer reviews are assigned
     */
    public ?string $peerReviewsAssignAt = null;

    /**
     * Whether intra-group peer reviews are allowed
     */
    public ?bool $intraGroupPeerReviews = null;

    /**
     * Number of submissions needing grading
     */
    public ?int $needsGradingCount = null;

    /**
     * Grading count by section
     *
     * @var array<array<string, mixed>>|null
     */
    public ?array $needsGradingCountBySection = null;

    /**
     * Whether to post to SIS
     */
    public ?bool $postToSis = null;

    /**
     * Third-party integration ID
     */
    public ?string $integrationId = null;

    /**
     * Third-party integration data
     *
     * @var array<string, mixed>|null
     */
    public ?array $integrationData = null;

    /**
     * Whether assignment has submitted submissions
     */
    public ?bool $hasSubmittedSubmissions = null;

    /**
     * Grading standard ID
     */
    public ?int $gradingStandardId = null;

    /**
     * Whether assignment can be unpublished
     */
    public ?bool $unpublishable = null;

    /**
     * Lock information object
     *
     * @var array<string, mixed>|null
     */
    public ?array $lockInfo = null;

    /**
     * Lock explanation text
     */
    public ?string $lockExplanation = null;

    /**
     * Associated quiz ID
     */
    public ?int $quizId = null;

    /**
     * Whether anonymous submissions are allowed
     */
    public ?bool $anonymousSubmissions = null;

    /**
     * Associated discussion topic
     *
     * @var array<string, mixed>|null
     */
    public ?array $discussionTopic = null;

    /**
     * Whether assignment is frozen on copy
     */
    public ?bool $freezeOnCopy = null;

    /**
     * Whether assignment is frozen
     */
    public ?bool $frozen = null;

    /**
     * Array of frozen attributes
     *
     * @var array<string>|null
     */
    public ?array $frozenAttributes = null;

    /**
     * Current user's submission
     *
     * @var array<string, mixed>|null
     */
    public ?array $submission = null;

    /**
     * Whether rubric is used for grading
     */
    public ?bool $useRubricForGrading = null;

    /**
     * Rubric settings
     *
     * @var array<string, mixed>|null
     */
    public ?array $rubricSettings = null;

    /**
     * Rubric criteria and ratings
     *
     * @var array<string, mixed>|null
     */
    public ?array $rubric = null;

    /**
     * Array of student IDs who can see assignment
     *
     * @var array<int>|null
     */
    public ?array $assignmentVisibility = null;

    /**
     * Array of assignment override objects
     *
     * @var array<array<string, mixed>>|null
     */
    public ?array $overrides = null;

    /**
     * Whether assignment is omitted from final grade
     */
    public ?bool $omitFromFinalGrade = null;

    /**
     * Whether assignment is hidden in gradebook
     */
    public ?bool $hideInGradebook = null;

    /**
     * Number of provisional graders
     */
    public ?int $graderCount = null;

    /**
     * Final grader user ID
     */
    public ?int $finalGraderId = null;

    /**
     * Whether grader comments are visible to graders
     */
    public ?bool $graderCommentsVisibleToGraders = null;

    /**
     * Whether graders are anonymous to other graders
     */
    public ?bool $gradersAnonymousToGraders = null;

    /**
     * Whether grader names are visible to final grader
     */
    public ?bool $gradersNamesVisibleToFinalGrader = null;

    /**
     * Whether assignment posts grades manually
     */
    public ?bool $postManually = null;

    /**
     * Score statistics
     *
     * @var array<string, mixed>|null
     */
    public ?array $scoreStatistics = null;

    /**
     * Whether user can submit
     */
    public ?bool $canSubmit = null;

    /**
     * Academic benchmark GUIDs
     *
     * @var array<string>|null
     */
    public ?array $abGuid = null;

    /**
     * Annotatable attachment ID
     */
    public ?int $annotatableAttachmentId = null;

    /**
     * Whether student names are anonymized
     */
    public ?bool $anonymizeStudents = null;

    /**
     * Whether LockDown Browser is required
     */
    public ?bool $requireLockdownBrowser = null;

    /**
     * Whether assignment has important dates
     */
    public ?bool $importantDates = null;

    /**
     * Whether notifications are muted (deprecated)
     */
    public ?bool $muted = null;

    /**
     * Whether peer reviews are anonymous
     */
    public ?bool $anonymousPeerReviews = null;

    /**
     * Whether instructor annotations are anonymous
     */
    public ?bool $anonymousInstructorAnnotations = null;

    /**
     * Whether assignment has graded submissions
     */
    public ?bool $gradedSubmissionsExist = null;

    /**
     * Whether this is a quiz assignment
     */
    public ?bool $isQuizAssignment = null;

    /**
     * Whether assignment is in closed grading period
     */
    public ?bool $inClosedGradingPeriod = null;

    /**
     * Whether assignment can be duplicated
     */
    public ?bool $canDuplicate = null;

    /**
     * Original course ID if duplicated
     */
    public ?int $originalCourseId = null;

    /**
     * Original assignment ID if duplicated
     */
    public ?int $originalAssignmentId = null;

    /**
     * Original LTI resource link ID if duplicated
     */
    public ?int $originalLtiResourceLinkId = null;

    /**
     * Original assignment name if duplicated
     */
    public ?string $originalAssignmentName = null;

    /**
     * Original quiz ID if duplicated
     */
    public ?int $originalQuizId = null;

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
     *
     * @return void
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Check if course context is set
     *
     * @throws CanvasApiException If course is not set
     *
     * @return bool
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
            'submissions_download_url' => $this->submissionsDownloadUrl,
            'due_date_required' => $this->dueDateRequired,
            'max_name_length' => $this->maxNameLength,
            'turnitin_enabled' => $this->turnitinEnabled,
            'vericite_enabled' => $this->vericiteEnabled,
            'turnitin_settings' => $this->turnitinSettings,
            'grade_group_students_individually' => $this->gradeGroupStudentsIndividually,
            'external_tool_tag_attributes' => $this->externalToolTagAttributes,
            'automatic_peer_reviews' => $this->automaticPeerReviews,
            'peer_review_count' => $this->peerReviewCount,
            'peer_reviews_assign_at' => $this->peerReviewsAssignAt,
            'intra_group_peer_reviews' => $this->intraGroupPeerReviews,
            'needs_grading_count' => $this->needsGradingCount,
            'needs_grading_count_by_section' => $this->needsGradingCountBySection,
            'post_to_sis' => $this->postToSis,
            'integration_id' => $this->integrationId,
            'integration_data' => $this->integrationData,
            'has_submitted_submissions' => $this->hasSubmittedSubmissions,
            'grading_standard_id' => $this->gradingStandardId,
            'unpublishable' => $this->unpublishable,
            'lock_info' => $this->lockInfo,
            'lock_explanation' => $this->lockExplanation,
            'quiz_id' => $this->quizId,
            'anonymous_submissions' => $this->anonymousSubmissions,
            'discussion_topic' => $this->discussionTopic,
            'freeze_on_copy' => $this->freezeOnCopy,
            'frozen' => $this->frozen,
            'frozen_attributes' => $this->frozenAttributes,
            'submission' => $this->submission,
            'use_rubric_for_grading' => $this->useRubricForGrading,
            'rubric_settings' => $this->rubricSettings,
            'rubric' => $this->rubric,
            'assignment_visibility' => $this->assignmentVisibility,
            'overrides' => $this->overrides,
            'omit_from_final_grade' => $this->omitFromFinalGrade,
            'hide_in_gradebook' => $this->hideInGradebook,
            'grader_count' => $this->graderCount,
            'final_grader_id' => $this->finalGraderId,
            'grader_comments_visible_to_graders' => $this->graderCommentsVisibleToGraders,
            'graders_anonymous_to_graders' => $this->gradersAnonymousToGraders,
            'graders_names_visible_to_final_grader' => $this->gradersNamesVisibleToFinalGrader,
            'post_manually' => $this->postManually,
            'score_statistics' => $this->scoreStatistics,
            'can_submit' => $this->canSubmit,
            'ab_guid' => $this->abGuid,
            'annotatable_attachment_id' => $this->annotatableAttachmentId,
            'anonymize_students' => $this->anonymizeStudents,
            'require_lockdown_browser' => $this->requireLockdownBrowser,
            'important_dates' => $this->importantDates,
            'muted' => $this->muted,
            'anonymous_peer_reviews' => $this->anonymousPeerReviews,
            'anonymous_instructor_annotations' => $this->anonymousInstructorAnnotations,
            'graded_submissions_exist' => $this->gradedSubmissionsExist,
            'is_quiz_assignment' => $this->isQuizAssignment,
            'in_closed_grading_period' => $this->inClosedGradingPeriod,
            'can_duplicate' => $this->canDuplicate,
            'original_course_id' => $this->originalCourseId,
            'original_assignment_id' => $this->originalAssignmentId,
            'original_lti_resource_link_id' => $this->originalLtiResourceLinkId,
            'original_assignment_name' => $this->originalAssignmentName,
            'original_quiz_id' => $this->originalQuizId,
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
        ], fn ($value) => $value !== null);
    }

    /**
     * Find a single assignment by ID
     *
     * @param int $id Assignment ID
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/assignments/%d', self::$course->id, $id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $assignmentData = self::parseJsonResponse($response);

        return new self($assignmentData);
    }

    /**
     * Create a new assignment
     *
     * @param array<string, mixed>|CreateAssignmentDTO $data Assignment data
     *
     * @throws CanvasApiException
     *
     * @return self Created Assignment object
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
        $assignmentData = self::parseJsonResponse($response);

        return new self($assignmentData);
    }

    /**
     * Update an assignment
     *
     * @param int $id Assignment ID
     * @param array<string, mixed>|UpdateAssignmentDTO $data Assignment data
     *
     * @throws CanvasApiException
     *
     * @return self Updated Assignment object
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
        $assignmentData = self::parseJsonResponse($response);

        return new self($assignmentData);
    }

    /**
     * Save the current assignment (create or update)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function save(): self
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
                'online_text_entry', 'online_url', 'online_upload', 'media_recording',
                'student_annotation',
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

        if ($this->id) {
            // Update existing assignment
            $updateData = $this->toDtoArray();
            if (empty($updateData)) {
                return $this; // Nothing to update
            }

            $updatedAssignment = self::update($this->id, $updateData);
            $this->populate($updatedAssignment->toArray());
        } else {
            // Create new assignment
            $createData = $this->toDtoArray();

            $newAssignment = self::create($createData);
            $this->populate($newAssignment->toArray());
        }

        return $this;
    }

    /**
     * Delete the assignment
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Assignment ID is required for deletion');
        }

        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/assignments/%d', self::$course->id, $this->id);
        self::$apiClient->delete($endpoint);

        return $this;
    }

    /**
     * Duplicate an assignment
     *
     * @param int $id Assignment ID to duplicate
     * @param array<string, mixed> $options Duplication options
     *
     * @throws CanvasApiException
     *
     * @return self Duplicated Assignment object
     */
    public static function duplicate(int $id, array $options = []): self
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/assignments/%d/duplicate', self::$course->id, $id);
        $response = self::$apiClient->post($endpoint, ['multipart' => $options]);
        $assignmentData = self::parseJsonResponse($response);

        return new self($assignmentData);
    }

    // Relationship Methods

    /**
     * Get submissions for this assignment
     *
     * @param array<string, mixed> $params Query parameters
     *
     * @throws CanvasApiException
     *
     * @return Submission[]
     */
    public function submissions(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Assignment ID is required to fetch submissions');
        }

        self::checkCourse();

        Submission::setCourse(self::$course);
        Submission::setAssignment($this);

        return Submission::all($params);
    }

    /**
     * Get submission for a specific user
     *
     * @param int $userId User ID
     *
     * @throws CanvasApiException
     *
     * @return Submission|null
     */
    public function submissionForUser(int $userId): ?Submission
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Assignment ID is required to fetch submission');
        }

        self::checkCourse();
        self::checkApiClient();

        try {
            $endpoint = sprintf('courses/%d/assignments/%d/submissions/%d', self::$course->id, $this->id, $userId);
            $response = self::$apiClient->get($endpoint);
            $submissionData = self::parseJsonResponse($response);

            return new Submission($submissionData);
        } catch (CanvasApiException $e) {
            // If submission not found, return null
            if (strpos($e->getMessage(), '404') !== false) {
                return null;
            }
            $msg = "Failed to get submission for user {$userId} on assignment {$this->id}: ";

            throw new CanvasApiException($msg . $e->getMessage());
        }
    }

    /**
     * Get count of submissions for this assignment
     *
     * @throws CanvasApiException
     *
     * @return int
     */
    public function getSubmissionCount(): int
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Assignment ID is required to get submission count');
        }

        // In a real implementation, we would use the Link header to get total count
        // For now, we'll fetch all and count (not optimal for large datasets)
        $allSubmissions = $this->submissions(['per_page' => 100]);

        return count($allSubmissions);
    }

    /**
     * Get rubric associated with this assignment
     *
     * @throws CanvasApiException
     *
     * @return Rubric|null
     */
    public function rubric(): ?Rubric
    {
        if (!isset($this->rubric) || empty($this->rubric)) {
            return null;
        }

        self::checkCourse();

        // If rubric data is embedded, create object from it
        if (isset($this->rubric['id'])) {
            return new Rubric($this->rubric);
        }

        return null;
    }

    /**
     * Get assignment overrides
     *
     * @throws CanvasApiException
     *
     * @return array<mixed>
     */
    public function overrides(): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Assignment ID is required to fetch overrides');
        }

        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/assignments/%d/overrides', self::$course->id, $this->id);
        $response = self::$apiClient->get($endpoint);
        $overridesData = self::parseJsonResponse($response);

        // Return raw override data for now, could create Override objects in future
        return $overridesData;
    }

    /**
     * Get rubric association for this assignment
     *
     * @throws CanvasApiException
     *
     * @return RubricAssociation|null
     */
    public function rubricAssociation(): ?RubricAssociation
    {
        if (!isset($this->rubric) || empty($this->rubric)) {
            return null;
        }

        self::checkCourse();
        self::checkApiClient();

        try {
            // Get rubric associations for this assignment
            $endpoint = sprintf('courses/%d/rubric_associations', self::$course->id);
            $response = self::$apiClient->get($endpoint, [
                'query' => [
                    'include' => ['association_object'],
                    'association_type' => 'Assignment',
                    'association_id' => $this->id,
                ],
            ]);

            $associations = self::parseJsonResponse($response);

            // Find the association for this assignment
            foreach ($associations as $assocData) {
                if ($assocData['association_id'] == $this->id && $assocData['association_type'] == 'Assignment') {
                    return new RubricAssociation($assocData);
                }
            }

            return null;
        } catch (CanvasApiException $e) {
            $msg = "Failed to get rubric association for assignment {$this->id}: ";

            throw new CanvasApiException($msg . $e->getMessage());
        }
    }

    /**
     * Get the API endpoint for this resource
     *
     * @throws CanvasApiException
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        self::checkCourse();

        return sprintf('courses/%d/assignments', self::$course->getId());
    }
}
