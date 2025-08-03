<?php

namespace CanvasLMS\Api\Courses;

use Exception;
use CanvasLMS\Config;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Enrollments\Enrollment;
use CanvasLMS\Dto\Courses\CreateCourseDTO;
use CanvasLMS\Dto\Courses\UpdateCourseDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Objects\CalendarLink;
use CanvasLMS\Objects\CourseProgress;
use CanvasLMS\Objects\Term;
use CanvasLMS\Pagination\PaginationResult;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Api\CalendarEvents\CalendarEvent;
use CanvasLMS\Dto\CalendarEvents\CreateCalendarEventDTO;
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Api\Modules\Module;
use CanvasLMS\Api\Pages\Page;
use CanvasLMS\Api\Sections\Section;
use CanvasLMS\Api\DiscussionTopics\DiscussionTopic;
use CanvasLMS\Api\Quizzes\Quiz;
use CanvasLMS\Api\Files\File;
use CanvasLMS\Api\Rubrics\Rubric;
use CanvasLMS\Api\ExternalTools\ExternalTool;
use CanvasLMS\Api\Tabs\Tab;

/**
 * Course Class
 *
 * Represents a course in the Canvas LMS. This class provides methods to create, update,
 * find, and fetch multiple courses from the Canvas LMS system. It utilizes Data Transfer
 * Objects (DTOs) for handling course creation and updates.
 *
 * Usage:
 *
 * ```php
 * // Creating a new course
 * $courseData = [
 *     'name' => 'Introduction to Philosophy',
 *     'courseCode' => 'PHIL101',
 *     // ... other course data ...
 * ];
 * $course = Course::create($courseData);
 *
 * // Updating an existing course statically
 * $updatedData = [
 *     'name' => 'Advanced Philosophy',
 *     'courseCode' => 'PHIL201',
 *     // ... other updated data ...
 * ];
 * $updatedCourse = Course::update(123, $updatedData); // where 123 is the course ID
 *
 * // Updating an existing course instance
 * $course->name = 'Advanced Philosophy';
 * $course->save();
 *
 * // Finding a course by ID
 * $course = Course::find(123);
 *
 * // Fetching all courses (first page only)
 * $courses = Course::fetchAll();
 *
 * // Fetching courses with pagination support
 * $paginatedResponse = Course::fetchAllPaginated(['per_page' => 10]);
 * $courses = $paginatedResponse->getJsonData();
 * $pagination = $paginatedResponse->toPaginationResult($courses);
 *
 * // Fetching a specific page of courses
 * $paginationResult = Course::fetchPage(['page' => 2, 'per_page' => 10]);
 * $courses = $paginationResult->getData();
 * $hasNext = $paginationResult->hasNext();
 *
 * // Fetching all courses from all pages
 * $allCourses = Course::fetchAllPages(['per_page' => 50]);
 *```
 * @package CanvasLMS\Api
 */
class Course extends AbstractBaseApi
{
    /**
     * The unique identifier for the course
     * @var int
     */
    public int $id;

    /**
     * The SIS identifier for the course, if defined. This field is only included if
     * the user has permission to view SIS information.
     * @var string|null
     */
    public ?string $sisCourseId = null;

    /**
     * The UUID of the course
     * @var string
     */
    public string $uuid;

    /**
     * @var string|null
     */
    public ?string $integrationId = null;

    /**
     * The integration identifier for the course, if defined. This field is only
     * included if the user has permission to view SIS information.
     * @var int|null
     */
    public ?int $sisImportId = null;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $courseCode;

    /**
     * @var string|null
     */
    public ?string $originalName = null;

    /**
     * @var string
     */
    public string $workflowState;

    /**
     * @var int
     */
    public int $accountId;

    /**
     * @var int
     */
    public int $rootAccountId;

    /**
     * @var int|null
     */
    public ?int $enrollmentTermId = null;

    /**
     * @var mixed[]|null
     */
    public ?array $gradingPeriods = [];

    /**
     * @var int|null
     */
    public ?int $gradingStandardId = null;

    /**
     * @var string|null
     */
    public ?string $gradePassbackSetting = null;

    /**
     * @var string
     */
    public string $createdAt = '';

    /**
     * @var string|null
     */
    public ?string $startAt = null;

    /**
     * @var string|null
     */
    public ?string $endAt = null;

    /**
     * @var string|null
     */
    public ?string $locale = null;

    /**
     * @var mixed[]|null
     */
    public ?array $enrollments = [];

    /**
     * @var int|null
     */
    public ?int $totalStudents = null;

    /**
     * @var CalendarLink|null
     */
    public ?CalendarLink $calendar = null;

    /**
     * @var string
     */
    public string $defaultView = '';

    /**
     * @var string|null
     */
    public ?string $syllabusBody = null;

    /**
     * @var int|null
     */
    public ?int $needsGradingCount = null;

    /**
     * @var Term|null
     */
    public ?Term $term = null;

    /**
     * @var CourseProgress|null
     */
    public ?CourseProgress $courseProgress = null;

    /**
     * @var bool
     */
    public bool $applyAssignmentGroupWeights = false;

    /**
     * @var mixed[]|null
     */
    public ?array $permissions = [];

    /**
     * @var bool
     */
    public bool $isPublic = false;

    /**
     * @var bool
     */
    public bool $isPublicToAuthUsers = false;

    /**
     * @var bool
     */
    public bool $publicSyllabus = false;

    /**
     * @var bool
     */
    public bool $publicSyllabusToAuth = false;

    /**
     * @var string|null
     */
    public ?string $publicDescription = null;

    /**
     * @var int
     */
    public int $storageQuotaMb = 0;

    /**
     * @var int
     */
    public int $storageQuotaUsedMb = 0;

    /**
     * @var bool
     */
    public bool $hideFinalGrades = false;

    /**
     * @var string|null
     */
    public ?string $license = '';

    /**
     * @var bool
     */
    public bool $allowStudentAssignmentEdits = false;

    /**
     * @var bool
     */
    public bool $allowWikiComments = false;

    /**
     * @var bool
     */
    public bool $allowStudentForumAttachments = false;

    /**
     * @var bool
     */
    public bool $openEnrollment = false;

    /**
     * @var bool
     */
    public bool $selfEnrollment = false;

    /**
     * @var bool
     */
    public bool $restrictEnrollmentsToCourseDates = false;

    /**
     * @var string
     */
    public string $courseFormat = '';

    /**
     * @var bool
     */
    public bool $accessRestrictedByDate = false;

    /**
     * @var string
     */
    public string $timeZone = '';

    /**
     * @var bool
     */
    public bool $blueprint = false;

    /**
     * @var mixed[]|null
     */
    public ?array $blueprintRestrictions = [];

    /**
     * @var mixed[]|null
     */
    public ?array $blueprintRestrictionsByObjectType = [];

    /**
     * @var bool
     */
    public bool $template = false;

    /**
     * Optional: Sets the course as a homeroom course
     * @var bool
     */
    public bool $homeroomCourse = false;

    /**
     * Optional: Course friendly name for Canvas for Elementary
     * @var string|null
     */
    public ?string $friendlyName = null;

    /**
     * Optional: Course color in hex format for Canvas for Elementary
     * @var string|null
     */
    public ?string $courseColor = null;

    /**
     * Optional: Course image URL if set
     * @var string|null
     */
    public ?string $imageUrl = null;

    /**
     * Optional: Course image ID if set
     * @var int|null
     */
    public ?int $imageId = null;

    /**
     * Optional: Course banner image URL for Canvas for Elementary
     * @var string|null
     */
    public ?string $bannerImageUrl = null;

    /**
     * Optional: Course banner image ID for Canvas for Elementary
     * @var int|null
     */
    public ?int $bannerImageId = null;

    /**
     * Optional: Whether course is concluded (computed field)
     * @var bool|null
     */
    public ?bool $concluded = null;

    /**
     * Optional: Whether grades are posted manually or automatically
     * @var bool|null
     */
    public ?bool $postManually = null;

    /**
     * Optional: LTI context ID for the course
     * @var string|null
     */
    public ?string $ltiContextId = null;

    /**
     * Syllabus course summary enabled (for syllabus page)
     * @var bool
     */
    public bool $syllabusCoursesSummary = true;

    /**
     * Whether the course uses blueprint restrictions by object type
     * @var bool
     */
    public bool $useBlueprintRestrictionsByObjectType = false;

    /**
     * Course pacing enabled flag
     * @var bool
     */
    public bool $enableCoursePaces = false;

    /**
     * Conditional release (individual learning paths) enabled
     * @var bool
     */
    public bool $conditionalRelease = false;

    /**
     * Default due time for assignments (HH:MM:SS format)
     * @var string
     */
    public string $defaultDueTime = '23:59:59';

    /**
     * Constructor
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        // Handle object instantiation for specific properties
        if (isset($data['term']) && is_array($data['term'])) {
            $this->term = new Term($data['term']);
            unset($data['term']);
        }

        if (isset($data['course_progress']) && is_array($data['course_progress'])) {
            $this->courseProgress = new CourseProgress($data['course_progress']);
            unset($data['course_progress']);
        }

        if (isset($data['calendar']) && is_array($data['calendar'])) {
            $this->calendar = new CalendarLink($data['calendar']);
            unset($data['calendar']);
        }

        // Call parent constructor for remaining properties
        parent::__construct($data);
    }

    /**
     * Convert the object to an array for DTO operations
     * @return mixed[]
     */
    protected function toDtoArray(): array
    {
        $data = parent::toDtoArray();

        // Convert objects back to arrays
        if ($this->term !== null) {
            $data['term'] = $this->term->toArray();
        }

        if ($this->courseProgress !== null) {
            $data['courseProgress'] = $this->courseProgress->toArray();
        }

        if ($this->calendar !== null) {
            $data['calendar'] = $this->calendar->toArray();
        }

        return $data;
    }

    /**
     * Create a new Course instance
     * @param CreateCourseDTO|mixed[] $courseData
     * @return self
     * @throws Exception
     */
    public static function create(array | CreateCourseDTO $courseData): self
    {
        self::checkApiClient();

        $courseData = is_array($courseData) ? new CreateCourseDTO($courseData) : $courseData;

        return self::createFromDTO($courseData);
    }

    /**
     * Create a new Course instance from a CreateCourseDTO
     * @param CreateCourseDTO $dto
     * @return self
     * @throws CanvasApiException
     */
    private static function createFromDTO(CreateCourseDTO $dto): self
    {
        self::checkApiClient();

        $response = self::$apiClient->post('/accounts/' . Config::getAccountId() . '/courses', [
            'multipart' => $dto->toApiArray()
        ]);

        $courseData = json_decode($response->getBody(), true);

        return new self($courseData);
    }

    /**
     * Update an existing course
     * @param int $id
     * @param UpdateCourseDTO|mixed[] $courseData
     * @return self
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function update(int $id, array | UpdateCourseDTO $courseData): self
    {
        $courseData = is_array($courseData) ? new UpdateCourseDTO($courseData) : $courseData;

        return self::updateFromDTO($id, $courseData);
    }

    /**
     * Update an existing course from a UpdateCourseDTO
     * @param int $id
     * @param UpdateCourseDTO $dto
     * @return self
     * @throws CanvasApiException
     */
    private static function updateFromDTO(int $id, UpdateCourseDTO $dto): self
    {
        self::checkApiClient();

        $response = self::$apiClient->put("/courses/{$id}", [
            'multipart' => $dto->toApiArray()
        ]);

        $courseData = json_decode($response->getBody(), true);

        return new self($courseData);
    }

    /**
     * Find a course by ID
     * @param int $id
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$id}");

        $courseData = json_decode($response->getBody(), true);

        return new self($courseData);
    }

    /**
     * Fetch all courses
     * @param mixed[] $params
     * @return Course[]
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();

        $accountId = Config::getAccountId();

        $response = self::$apiClient->get("/accounts/{$accountId}/courses", [
            'query' => $params
        ]);

        $courses = json_decode($response->getBody(), true);

        return array_map(function ($course) {
            return new self($course);
        }, $courses);
    }

    /**
     * Fetch courses with pagination support
     * @param mixed[] $params Query parameters for the request
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        $accountId = Config::getAccountId();
        return self::getPaginatedResponse("/accounts/{$accountId}/courses", $params);
    }

    /**
     * Fetch courses from a specific page
     * @param mixed[] $params Query parameters for the request
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        $paginatedResponse = self::fetchAllPaginated($params);
        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * Fetch all courses from all pages
     * @param mixed[] $params Query parameters for the request
     * @return Course[]
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        $accountId = Config::getAccountId();
        return self::fetchAllPagesAsModels("/accounts/{$accountId}/courses", $params);
    }

    /**
     * Save the course to the Canvas LMS system
     * @return bool
     * @throws Exception
     */
    public function save(): bool
    {
        self::checkApiClient();

        $data = $this->toDtoArray();
        $accountId = Config::getAccountId();

        // If the course has an ID, update it. Otherwise, create it.
        $dto = $data['id'] ? new UpdateCourseDTO($data) : new CreateCourseDTO($data);
        $path = $data['id'] ? "/courses/{$this->id}" : "/accounts/{$accountId}/courses";
        $method = $data['id'] ? 'PUT' : 'POST';

        try {
            $response = self::$apiClient->request($method, $path, [
                'multipart' => $dto->toApiArray()
            ]);

            $updatedCourseData = json_decode($response->getBody(), true);
            $this->populate($updatedCourseData);
        } catch (CanvasApiException $th) {
            return false;
        }

        return true;
    }

    /**
     * Delete the course from the Canvas LMS system
     * @return bool
     */
    public function delete(): bool
    {
        self::checkApiClient();

        try {
            self::$apiClient->delete("/courses/{$this->id}", [
                "query" => [
                    "event" => "delete"
                ]
            ]);
        } catch (CanvasApiException $th) {
            return false;
        }

        return true;
    }

    /**
     * Get groups in this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<\CanvasLMS\Api\Groups\Group>
     * @throws CanvasApiException
     */
    public function groups(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Course ID is required to fetch groups');
        }

        return \CanvasLMS\Api\Groups\Group::fetchByContext('courses', $this->id, $params);
    }

    /**
     * Get paginated groups in this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function groupsPaginated(array $params = []): PaginatedResponse
    {
        if (!$this->id) {
            throw new CanvasApiException('Course ID is required to fetch groups');
        }

        return \CanvasLMS\Api\Groups\Group::fetchByContextPaginated('courses', $this->id, $params);
    }

    /**
     * Get group categories in this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<\CanvasLMS\Api\GroupCategories\GroupCategory>
     * @throws CanvasApiException
     */
    public function groupCategories(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Course ID is required to fetch group categories');
        }

        return \CanvasLMS\Api\GroupCategories\GroupCategory::fetchAllPagesAsModels(
            sprintf('courses/%d/group_categories', $this->id),
            $params
        );
    }

    /**
     * Get paginated group categories in this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function groupCategoriesPaginated(array $params = []): PaginatedResponse
    {
        if (!$this->id) {
            throw new CanvasApiException('Course ID is required to fetch group categories');
        }

        return \CanvasLMS\Api\GroupCategories\GroupCategory::getPaginatedResponse(
            sprintf('courses/%d/group_categories', $this->id),
            $params
        );
    }

    /**
     * Create a group category in this course
     *
     * @param array<string, mixed>|\CanvasLMS\Dto\GroupCategories\CreateGroupCategoryDTO $data Group category data
     * @return \CanvasLMS\Api\GroupCategories\GroupCategory
     * @throws CanvasApiException
     */
    public function createGroupCategory(
        array|\CanvasLMS\Dto\GroupCategories\CreateGroupCategoryDTO $data
    ): \CanvasLMS\Api\GroupCategories\GroupCategory {
        if (!$this->id) {
            throw new CanvasApiException('Course ID is required to create group category');
        }

        // Transform data to include course_id
        if (is_array($data)) {
            $data['course_id'] = $this->id;
        } else {
            $data->courseId = $this->id;
        }

        return \CanvasLMS\Api\GroupCategories\GroupCategory::create($data);
    }

    /**
     * Conclude the course in the Canvas LMS system
     * @return bool
     */
    public function conclude(): bool
    {
        self::checkApiClient();

        try {
            self::$apiClient->delete("/courses/{$this->id}", [
                "query" => [
                    "event" => "conclude"
                ]
            ]);
        } catch (CanvasApiException $th) {
            return false;
        }

        return true;
    }

    /**
     * Deletes the current course, and creates a new equivalent course with no content,
     * but all sections and users moved over
     * @return self
     * @throws CanvasApiException
     * @throws Exception
     */
    public function reset(): self
    {
        self::checkApiClient();

        $response = self::$apiClient->post("/courses/{$this->id}/reset_content");

        $courseData = json_decode($response->getBody(), true);

        $this->populate($courseData);

        return $this;
    }

    /**
     * Get course settings
     * @return mixed[] Course settings array
     * @throws CanvasApiException
     */
    public static function getSettings(int $courseId): array
    {
        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$courseId}/settings");

        return json_decode($response->getBody(), true);
    }

    /**
     * Update course settings
     * @param int $courseId
     * @param mixed[] $settings
     * @return mixed[] Updated settings
     * @throws CanvasApiException
     */
    public static function updateSettings(int $courseId, array $settings): array
    {
        self::checkApiClient();

        $response = self::$apiClient->put("/courses/{$courseId}/settings", [
            'form_params' => $settings
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get user progress in this course
     * @param int $userId
     * @return mixed[] Course progress data
     * @throws CanvasApiException
     */
    public function getUserProgress(int $userId): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get user progress');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/users/{$userId}/progress");

        return json_decode($response->getBody(), true);
    }

    /**
     * Get bulk user progress for all users in this course
     * @return mixed[] Array of user progress data
     * @throws CanvasApiException
     */
    public function getBulkUserProgress(): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get bulk user progress');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/bulk_user_progress");

        return json_decode($response->getBody(), true);
    }

    /**
     * Get effective due dates for assignments in this course
     * @param mixed[] $params Optional parameters like assignment_ids
     * @return mixed[] Effective due dates data
     * @throws CanvasApiException
     */
    public function getEffectiveDueDates(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get effective due dates');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/effective_due_dates", [
            'query' => $params
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get permissions for the current user in this course (relationship method)
     * @param string[] $permissions List of permissions to check
     * @return mixed[] Permissions data
     * @throws CanvasApiException
     */
    public function permissions(array $permissions = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get permissions');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/permissions", [
            'query' => ['permissions' => $permissions]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get current user's course-specific activity stream
     * @param mixed[] $params Optional parameters for filtering
     * @return mixed[] Activity stream data
     * @throws CanvasApiException
     */
    public function getActivityStream(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get activity stream');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/activity_stream", [
            'query' => $params
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get current user's course-specific activity stream summary
     * @return mixed[] Activity stream summary data
     * @throws CanvasApiException
     */
    public function getActivityStreamSummary(): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get activity stream summary');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/activity_stream/summary");

        return json_decode($response->getBody(), true);
    }

    /**
     * Get current user's course-specific TODO items
     * @return mixed[] TODO items data
     * @throws CanvasApiException
     */
    public function getTodoItems(): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get TODO items');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/todo");

        return json_decode($response->getBody(), true);
    }

    /**
     * Get or create a test student for this course
     * @return mixed[] Test student user data
     * @throws CanvasApiException
     */
    public function getTestStudent(): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get test student');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/student_view_student");

        return json_decode($response->getBody(), true);
    }

    /**
     * Preview HTML content processed for this course
     * @param string $html HTML content to preview
     * @return mixed[] Processed HTML data
     * @throws CanvasApiException
     */
    public function previewHtml(string $html): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to preview HTML');
        }

        self::checkApiClient();

        $response = self::$apiClient->post("/courses/{$this->id}/preview_html", [
            'form_params' => ['html' => $html]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Batch update multiple courses
     * @param int $accountId Account ID containing the courses
     * @param int[] $courseIds Array of course IDs to update
     * @param string $event Action to take (offer, conclude, delete, undelete)
     * @return mixed[] Progress object for tracking the batch operation
     * @throws CanvasApiException
     */
    public static function batchUpdate(int $accountId, array $courseIds, string $event): array
    {
        self::checkApiClient();

        if (count($courseIds) > 500) {
            throw new CanvasApiException('Cannot update more than 500 courses at once');
        }

        $allowedEvents = ['offer', 'conclude', 'delete', 'undelete'];
        if (!in_array($event, $allowedEvents)) {
            throw new CanvasApiException('Invalid event. Must be one of: ' . implode(', ', $allowedEvents));
        }

        $response = self::$apiClient->put("/accounts/{$accountId}/courses", [
            'form_params' => [
                'course_ids' => $courseIds,
                'event' => $event
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * List users in this course with optional filtering
     * @param mixed[] $params Parameters for filtering users
     * @return mixed[] Array of user data
     * @throws CanvasApiException
     */
    public function getUsers(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get users');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/users", [
            'query' => $params
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get a single user in this course
     * @param int $userId User ID to retrieve
     * @param mixed[] $params Optional parameters like include[]
     * @return mixed[] User data
     * @throws CanvasApiException
     */
    public function getUser(int $userId, array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get user');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/users/{$userId}", [
            'query' => $params
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get students in this course (legacy method - use getUsers with enrollment_type filter instead)
     * @param mixed[] $params Optional parameters
     * @return mixed[] Array of student data
     * @throws CanvasApiException
     * @deprecated Use getUsers() with enrollment_type[] = ['student'] instead
     */
    public function getStudents(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get students');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/students", [
            'query' => $params
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get recently logged in students in this course
     * @return mixed[] Array of recently logged in user data
     * @throws CanvasApiException
     */
    public function getRecentStudents(): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get recent students');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/recent_students");

        return json_decode($response->getBody(), true);
    }

    /**
     * Search for content share users in this course
     * @param string $searchTerm Term to search for users
     * @return mixed[] Array of users available for content sharing
     * @throws CanvasApiException
     */
    public function searchContentShareUsers(string $searchTerm): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to search content share users');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/content_share_users", [
            'query' => ['search_term' => $searchTerm]
        ]);

        return json_decode($response->getBody(), true);
    }


    /**
     * Create a file upload to the course
     * This API endpoint is the first step in uploading a file to a course
     * @param mixed[] $fileParams File upload parameters
     * @return mixed[] File upload response
     * @throws CanvasApiException
     */
    public function createFile(array $fileParams): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to create file');
        }

        self::checkApiClient();

        $response = self::$apiClient->post("/courses/{$this->id}/files", [
            'form_params' => $fileParams
        ]);

        return json_decode($response->getBody(), true);
    }


    /**
     * Remove quiz migration alert
     * Remove alert about quiz migration limitations displayed to user
     * @return mixed[] Success response
     * @throws CanvasApiException
     */
    public function dismissQuizMigrationAlert(): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to dismiss quiz migration alert');
        }

        self::checkApiClient();

        $response = self::$apiClient->post("/courses/{$this->id}/dismiss_migration_limitation_message");
        return json_decode($response->getBody(), true);
    }

    /**
     * Get course copy status
     * DEPRECATED: Use Content Migrations API instead
     * Retrieve the status of a course copy operation
     * @param int $copyId Course copy ID
     * @return mixed[] Course copy status
     * @throws CanvasApiException
     */
    public function getCourseCopyStatus(int $copyId): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to get course copy status');
        }

        self::checkApiClient();

        $response = self::$apiClient->get("/courses/{$this->id}/course_copy/{$copyId}");
        return json_decode($response->getBody(), true);
    }

    /**
     * Copy course content
     * DEPRECATED: Use Content Migrations API instead
     * Copies content from one course into another
     * @param string $sourceCourse Source course ID or SIS-ID
     * @param mixed[] $options Copy options (except, only)
     * @return mixed[] Course copy response
     * @throws CanvasApiException
     */
    public function copyCourseContent(string $sourceCourse, array $options = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to copy course content');
        }

        self::checkApiClient();

        $params = array_merge(['source_course' => $sourceCourse], $options);

        $response = self::$apiClient->post("/courses/{$this->id}/course_copy", [
            'form_params' => $params
        ]);

        return json_decode($response->getBody(), true);
    }

    // Relationship Method Aliases

    /**
     * Get enrollments for this course (relationship method alias with parameter support)
     *
     * This method provides a clean, explicit way to access course enrollments
     * without conflicts with Canvas API data structure. Supports all Canvas API
     * enrollment parameters for filtering.
     *
     * @example
     * ```php
     * $course = Course::find(123);
     *
     * // Get all enrollments
     * $enrollments = $course->enrollments();
     *
     * // Get active student enrollments
     * $activeStudents = $course->enrollments([
     *     'type[]' => ['StudentEnrollment'],
     *     'state[]' => ['active']
     * ]);
     *
     * // Get enrollments with user data included
     * $enrollmentsWithUsers = $course->enrollments([
     *     'include[]' => ['user']
     * ]);
     * ```
     *
     * @param mixed[] $params Query parameters for filtering enrollments:
     *   - type[]: Filter by enrollment type (e.g., ['StudentEnrollment', 'TeacherEnrollment'])
     *   - role[]: Filter by enrollment role
     *   - state[]: Filter by enrollment state (e.g., ['active', 'invited'])
     *   - user_id: Filter by specific user ID
     *   - include[]: Include additional data (e.g., ['user', 'avatar_url'])
     * @return Enrollment[] Array of Enrollment objects
     * @throws CanvasApiException If the course ID is not set or API request fails
     */
    public function enrollments(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to fetch enrollments');
        }

        // Set this course as the context and fetch enrollments
        Enrollment::setCourse($this);
        return Enrollment::fetchAll($params);
    }

    // Enrollment Relationship Methods


    /**
     * Get active enrollments for this course
     *
     * Convenience method to get only active enrollments in the current course.
     *
     * @param mixed[] $params Additional query parameters
     * @return Enrollment[] Array of active Enrollment objects
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function getActiveEnrollments(array $params = []): array
    {
        $params = array_merge($params, ['state[]' => ['active']]);
        return $this->enrollments($params);
    }

    /**
     * Get student enrollments for this course
     *
     * Convenience method to get only student enrollments in the current course.
     *
     * @param mixed[] $params Additional query parameters
     * @return Enrollment[] Array of student Enrollment objects
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function getStudentEnrollments(array $params = []): array
    {
        $params = array_merge($params, ['type[]' => ['StudentEnrollment']]);
        return $this->enrollments($params);
    }

    /**
     * Get teacher enrollments for this course
     *
     * Convenience method to get only teacher enrollments in the current course.
     *
     * @param mixed[] $params Additional query parameters
     * @return Enrollment[] Array of teacher Enrollment objects
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function getTeacherEnrollments(array $params = []): array
    {
        $params = array_merge($params, ['type[]' => ['TeacherEnrollment']]);
        return $this->enrollments($params);
    }

    /**
     * Get TA enrollments for this course
     *
     * Convenience method to get only TA enrollments in the current course.
     *
     * @param mixed[] $params Additional query parameters
     * @return Enrollment[] Array of TA Enrollment objects
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function getTaEnrollments(array $params = []): array
    {
        $params = array_merge($params, ['type[]' => ['TaEnrollment']]);
        return $this->enrollments($params);
    }

    /**
     * Get observer enrollments for this course
     *
     * Convenience method to get only observer enrollments in the current course.
     *
     * @param mixed[] $params Additional query parameters
     * @return Enrollment[] Array of observer Enrollment objects
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function getObserverEnrollments(array $params = []): array
    {
        $params = array_merge($params, ['type[]' => ['ObserverEnrollment']]);
        return $this->enrollments($params);
    }

    /**
     * Get designer enrollments for this course
     *
     * Convenience method to get only designer enrollments in the current course.
     *
     * @param mixed[] $params Additional query parameters
     * @return Enrollment[] Array of designer Enrollment objects
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function getDesignerEnrollments(array $params = []): array
    {
        $params = array_merge($params, ['type[]' => ['DesignerEnrollment']]);
        return $this->enrollments($params);
    }

    /**
     * Check if a specific user is enrolled in this course
     *
     * @param int $userId The user ID to check enrollment for
     * @param string|null $enrollmentType Optional: specific enrollment type to check for
     * @return bool True if user is enrolled in the course (with optional type filter)
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function hasUserEnrolled(int $userId, ?string $enrollmentType = null): bool
    {
        $params = ['user_id' => $userId];
        if ($enrollmentType) {
            $params['type[]'] = [$enrollmentType];
        }

        $enrollments = $this->enrollments($params);
        return count($enrollments) > 0;
    }

    /**
     * Check if a specific user is a student in this course
     *
     * @param int $userId The user ID to check
     * @return bool True if user has a student enrollment in the course
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function hasStudentEnrolled(int $userId): bool
    {
        return $this->hasUserEnrolled($userId, 'StudentEnrollment');
    }

    /**
     * Check if a specific user is a teacher in this course
     *
     * @param int $userId The user ID to check
     * @return bool True if user has a teacher enrollment in the course
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function hasTeacherEnrolled(int $userId): bool
    {
        return $this->hasUserEnrolled($userId, 'TeacherEnrollment');
    }

    /**
     * Get the count of student enrollments in this course
     *
     * @return int Number of student enrollments
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function getStudentCount(): int
    {
        return count($this->getStudentEnrollments());
    }

    /**
     * Get the count of teacher enrollments in this course
     *
     * @return int Number of teacher enrollments
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function getTeacherCount(): int
    {
        return count($this->getTeacherEnrollments());
    }

    /**
     * Get the count of total enrollments in this course
     *
     * @param mixed[] $params Optional parameters to filter the count
     * @return int Total number of enrollments (with optional filters)
     * @throws CanvasApiException If course ID is not set or API request fails
     */
    public function getTotalEnrollmentCount(array $params = []): int
    {
        return count($this->enrollments($params));
    }

    /**
     * Get the course's enrollment data array (legacy method - uses embedded data)
     *
     * This returns the raw enrollments array that may be embedded in the course object
     * from certain Canvas API calls. For fetching current enrollments from the API,
     * use enrollments() instead.
     *
     * @return mixed[]|null Raw enrollments data array or null
     */
    public function getEnrollmentsData(): ?array
    {
        return $this->enrollments;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getSisCourseId(): ?string
    {
        return $this->sisCourseId;
    }

    /**
     * @param string|null $sisCourseId
     */
    public function setSisCourseId(?string $sisCourseId): void
    {
        $this->sisCourseId = $sisCourseId;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string|null
     */
    public function getIntegrationId(): ?string
    {
        return $this->integrationId;
    }

    /**
     * @param string|null $integrationId
     */
    public function setIntegrationId(?string $integrationId): void
    {
        $this->integrationId = $integrationId;
    }

    /**
     * @return int|null
     */
    public function getSisImportId(): ?int
    {
        return $this->sisImportId;
    }

    /**
     * @param int|null $sisImportId
     */
    public function setSisImportId(?int $sisImportId): void
    {
        $this->sisImportId = $sisImportId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getCourseCode(): string
    {
        return $this->courseCode;
    }

    /**
     * @param string $courseCode
     */
    public function setCourseCode(string $courseCode): void
    {
        $this->courseCode = $courseCode;
    }

    /**
     * @return string|null
     */
    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    /**
     * @param string|null $originalName
     */
    public function setOriginalName(?string $originalName): void
    {
        $this->originalName = $originalName;
    }

    /**
     * @return string
     */
    public function getWorkflowState(): string
    {
        return $this->workflowState;
    }

    /**
     * @param string $workflowState
     */
    public function setWorkflowState(string $workflowState): void
    {
        $this->workflowState = $workflowState;
    }

    /**
     * @return int
     */
    public function getAccountId(): int
    {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     */
    public function setAccountId(int $accountId): void
    {
        $this->accountId = $accountId;
    }

    /**
     * @return int
     */
    public function getRootAccountId(): int
    {
        return $this->rootAccountId;
    }

    /**
     * @param int $rootAccountId
     */
    public function setRootAccountId(int $rootAccountId): void
    {
        $this->rootAccountId = $rootAccountId;
    }

    /**
     * @return int|null
     */
    public function getEnrollmentTermId(): ?int
    {
        return $this->enrollmentTermId;
    }

    /**
     * @param int|null $enrollmentTermId
     */
    public function setEnrollmentTermId(?int $enrollmentTermId): void
    {
        $this->enrollmentTermId = $enrollmentTermId;
    }

    /**
     * @return mixed[]|null
     */
    public function getGradingPeriods(): ?array
    {
        return $this->gradingPeriods;
    }

    /**
     * @param mixed[]|null $gradingPeriods
     */
    public function setGradingPeriods(?array $gradingPeriods): void
    {
        $this->gradingPeriods = $gradingPeriods;
    }

    /**
     * @return int|null
     */
    public function getGradingStandardId(): ?int
    {
        return $this->gradingStandardId;
    }

    /**
     * @param int|null $gradingStandardId
     */
    public function setGradingStandardId(?int $gradingStandardId): void
    {
        $this->gradingStandardId = $gradingStandardId;
    }

    /**
     * @return string|null
     */
    public function getGradePassbackSetting(): ?string
    {
        return $this->gradePassbackSetting;
    }

    /**
     * @param string|null $gradePassbackSetting
     */
    public function setGradePassbackSetting(?string $gradePassbackSetting): void
    {
        $this->gradePassbackSetting = $gradePassbackSetting;
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @param string $createdAt
     */
    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return string|null
     */
    public function getStartAt(): ?string
    {
        return $this->startAt;
    }

    /**
     * @param string|null $startAt
     */
    public function setStartAt(?string $startAt): void
    {
        $this->startAt = $startAt;
    }

    /**
     * @return string|null
     */
    public function getEndAt(): ?string
    {
        return $this->endAt;
    }

    /**
     * @param string|null $endAt
     */
    public function setEndAt(?string $endAt): void
    {
        $this->endAt = $endAt;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     */
    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return mixed[]|null
     */
    public function getEnrollments(): ?array
    {
        return $this->enrollments;
    }

    /**
     * @param mixed[]|null $enrollments
     */
    public function setEnrollments(?array $enrollments): void
    {
        $this->enrollments = $enrollments;
    }

    /**
     * @return int|null
     */
    public function getTotalStudents(): ?int
    {
        return $this->totalStudents;
    }

    /**
     * @param int|null $totalStudents
     */
    public function setTotalStudents(?int $totalStudents): void
    {
        $this->totalStudents = $totalStudents;
    }

    /**
     * @return CalendarLink|null
     */
    public function getCalendar(): ?CalendarLink
    {
        return $this->calendar;
    }

    /**
     * @param CalendarLink|null $calendar
     */
    public function setCalendar(?CalendarLink $calendar): void
    {
        $this->calendar = $calendar;
    }

    /**
     * @return string
     */
    public function getDefaultView(): string
    {
        return $this->defaultView;
    }

    /**
     * @param string $defaultView
     */
    public function setDefaultView(string $defaultView): void
    {
        $this->defaultView = $defaultView;
    }

    /**
     * @return string|null
     */
    public function getSyllabusBody(): ?string
    {
        return $this->syllabusBody;
    }

    /**
     * @param string|null $syllabusBody
     */
    public function setSyllabusBody(?string $syllabusBody): void
    {
        $this->syllabusBody = $syllabusBody;
    }

    /**
     * @return int|null
     */
    public function getNeedsGradingCount(): ?int
    {
        return $this->needsGradingCount;
    }

    /**
     * @param int|null $needsGradingCount
     */
    public function setNeedsGradingCount(?int $needsGradingCount): void
    {
        $this->needsGradingCount = $needsGradingCount;
    }

    /**
     * @return Term|null
     */
    public function getTerm(): ?Term
    {
        return $this->term;
    }

    /**
     * @param Term|null $term
     */
    public function setTerm(?Term $term): void
    {
        $this->term = $term;
    }

    /**
     * @return CourseProgress|null
     */
    public function getCourseProgress(): ?CourseProgress
    {
        return $this->courseProgress;
    }

    /**
     * @param CourseProgress|null $courseProgress
     */
    public function setCourseProgress(?CourseProgress $courseProgress): void
    {
        $this->courseProgress = $courseProgress;
    }

    /**
     * @return bool
     */
    public function isApplyAssignmentGroupWeights(): bool
    {
        return $this->applyAssignmentGroupWeights;
    }

    /**
     * @param bool $applyAssignmentGroupWeights
     */
    public function setApplyAssignmentGroupWeights(bool $applyAssignmentGroupWeights): void
    {
        $this->applyAssignmentGroupWeights = $applyAssignmentGroupWeights;
    }

    /**
     * @return mixed[]|null
     */
    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    /**
     * @param mixed[]|null $permissions
     */
    public function setPermissions(?array $permissions): void
    {
        $this->permissions = $permissions;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    /**
     * @param bool $isPublic
     */
    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
    }

    /**
     * @return bool
     */
    public function isPublicToAuthUsers(): bool
    {
        return $this->isPublicToAuthUsers;
    }

    /**
     * @param bool $isPublicToAuthUsers
     */
    public function setIsPublicToAuthUsers(bool $isPublicToAuthUsers): void
    {
        $this->isPublicToAuthUsers = $isPublicToAuthUsers;
    }

    /**
     * @return bool
     */
    public function isPublicSyllabus(): bool
    {
        return $this->publicSyllabus;
    }

    /**
     * @param bool $publicSyllabus
     */
    public function setPublicSyllabus(bool $publicSyllabus): void
    {
        $this->publicSyllabus = $publicSyllabus;
    }

    /**
     * @return bool
     */
    public function isPublicSyllabusToAuth(): bool
    {
        return $this->publicSyllabusToAuth;
    }

    /**
     * @param bool $publicSyllabusToAuth
     */
    public function setPublicSyllabusToAuth(bool $publicSyllabusToAuth): void
    {
        $this->publicSyllabusToAuth = $publicSyllabusToAuth;
    }

    /**
     * @return string|null
     */
    public function getPublicDescription(): ?string
    {
        return $this->publicDescription;
    }

    /**
     * @param string|null $publicDescription
     */
    public function setPublicDescription(?string $publicDescription): void
    {
        $this->publicDescription = $publicDescription;
    }

    /**
     * @return int
     */
    public function getStorageQuotaMb(): int
    {
        return $this->storageQuotaMb;
    }

    /**
     * @param int $storageQuotaMb
     */
    public function setStorageQuotaMb(int $storageQuotaMb): void
    {
        $this->storageQuotaMb = $storageQuotaMb;
    }

    /**
     * @return int
     */
    public function getStorageQuotaUsedMb(): int
    {
        return $this->storageQuotaUsedMb;
    }

    /**
     * @param int $storageQuotaUsedMb
     */
    public function setStorageQuotaUsedMb(int $storageQuotaUsedMb): void
    {
        $this->storageQuotaUsedMb = $storageQuotaUsedMb;
    }

    /**
     * @return bool
     */
    public function isHideFinalGrades(): bool
    {
        return $this->hideFinalGrades;
    }

    /**
     * @param bool $hideFinalGrades
     */
    public function setHideFinalGrades(bool $hideFinalGrades): void
    {
        $this->hideFinalGrades = $hideFinalGrades;
    }

    /**
     * @return string|null
     */
    public function getLicense(): ?string
    {
        return $this->license;
    }

    /**
     * @param string|null $license
     */
    public function setLicense(?string $license): void
    {
        $this->license = $license;
    }

    /**
     * @return bool
     */
    public function isAllowStudentAssignmentEdits(): bool
    {
        return $this->allowStudentAssignmentEdits;
    }

    /**
     * @param bool $allowStudentAssignmentEdits
     */
    public function setAllowStudentAssignmentEdits(bool $allowStudentAssignmentEdits): void
    {
        $this->allowStudentAssignmentEdits = $allowStudentAssignmentEdits;
    }

    /**
     * @return bool
     */
    public function isAllowWikiComments(): bool
    {
        return $this->allowWikiComments;
    }

    /**
     * @param bool $allowWikiComments
     */
    public function setAllowWikiComments(bool $allowWikiComments): void
    {
        $this->allowWikiComments = $allowWikiComments;
    }

    /**
     * @return bool
     */
    public function isAllowStudentForumAttachments(): bool
    {
        return $this->allowStudentForumAttachments;
    }

    /**
     * @param bool $allowStudentForumAttachments
     */
    public function setAllowStudentForumAttachments(bool $allowStudentForumAttachments): void
    {
        $this->allowStudentForumAttachments = $allowStudentForumAttachments;
    }

    /**
     * @return bool
     */
    public function isOpenEnrollment(): bool
    {
        return $this->openEnrollment;
    }

    /**
     * @param bool $openEnrollment
     */
    public function setOpenEnrollment(bool $openEnrollment): void
    {
        $this->openEnrollment = $openEnrollment;
    }

    /**
     * @return bool
     */
    public function isSelfEnrollment(): bool
    {
        return $this->selfEnrollment;
    }

    /**
     * @param bool $selfEnrollment
     */
    public function setSelfEnrollment(bool $selfEnrollment): void
    {
        $this->selfEnrollment = $selfEnrollment;
    }

    /**
     * @return bool
     */
    public function isRestrictEnrollmentsToCourseDates(): bool
    {
        return $this->restrictEnrollmentsToCourseDates;
    }

    /**
     * @param bool $restrictEnrollmentsToCourseDates
     */
    public function setRestrictEnrollmentsToCourseDates(bool $restrictEnrollmentsToCourseDates): void
    {
        $this->restrictEnrollmentsToCourseDates = $restrictEnrollmentsToCourseDates;
    }

    /**
     * @return string
     */
    public function getCourseFormat(): string
    {
        return $this->courseFormat;
    }

    /**
     * @param string $courseFormat
     */
    public function setCourseFormat(string $courseFormat): void
    {
        $this->courseFormat = $courseFormat;
    }

    /**
     * @return bool
     */
    public function isAccessRestrictedByDate(): bool
    {
        return $this->accessRestrictedByDate;
    }

    /**
     * @param bool $accessRestrictedByDate
     */
    public function setAccessRestrictedByDate(bool $accessRestrictedByDate): void
    {
        $this->accessRestrictedByDate = $accessRestrictedByDate;
    }

    /**
     * @return string
     */
    public function getTimeZone(): string
    {
        return $this->timeZone;
    }

    /**
     * @param string $timeZone
     */
    public function setTimeZone(string $timeZone): void
    {
        $this->timeZone = $timeZone;
    }

    /**
     * @return bool
     */
    public function isBlueprint(): bool
    {
        return $this->blueprint;
    }

    /**
     * @param bool $blueprint
     */
    public function setBlueprint(bool $blueprint): void
    {
        $this->blueprint = $blueprint;
    }

    /**
     * @return mixed[]|null
     */
    public function getBlueprintRestrictions(): ?array
    {
        return $this->blueprintRestrictions;
    }

    /**
     * @param mixed[]|null $blueprintRestrictions
     */
    public function setBlueprintRestrictions(?array $blueprintRestrictions): void
    {
        $this->blueprintRestrictions = $blueprintRestrictions;
    }

    /**
     * @return mixed[]|null
     */
    public function getBlueprintRestrictionsByObjectType(): ?array
    {
        return $this->blueprintRestrictionsByObjectType;
    }

    /**
     * @param mixed[]|null $blueprintRestrictionsByObjectType
     */
    public function setBlueprintRestrictionsByObjectType(?array $blueprintRestrictionsByObjectType): void
    {
        $this->blueprintRestrictionsByObjectType = $blueprintRestrictionsByObjectType;
    }

    /**
     * @return bool
     */
    public function isTemplate(): bool
    {
        return $this->template;
    }

    /**
     * @param bool $template
     */
    public function setTemplate(bool $template): void
    {
        $this->template = $template;
    }

    /**
     * @return bool
     */
    public function isHomeroomCourse(): bool
    {
        return $this->homeroomCourse;
    }

    /**
     * @param bool $homeroomCourse
     */
    public function setHomeroomCourse(bool $homeroomCourse): void
    {
        $this->homeroomCourse = $homeroomCourse;
    }

    /**
     * @return string|null
     */
    public function getFriendlyName(): ?string
    {
        return $this->friendlyName;
    }

    /**
     * @param string|null $friendlyName
     */
    public function setFriendlyName(?string $friendlyName): void
    {
        $this->friendlyName = $friendlyName;
    }

    /**
     * @return string|null
     */
    public function getCourseColor(): ?string
    {
        return $this->courseColor;
    }

    /**
     * @param string|null $courseColor Hex color code (e.g., '#ff0000' or 'ff0000')
     * @throws \InvalidArgumentException If color format is invalid
     */
    public function setCourseColor(?string $courseColor): void
    {
        if ($courseColor !== null && !preg_match('/^#?[0-9a-fA-F]{6}$/', $courseColor)) {
            throw new \InvalidArgumentException(
                'Course color must be a valid hex color format (e.g., "#ff0000" or "ff0000")'
            );
        }
        $this->courseColor = $courseColor;
    }

    /**
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * @param string|null $imageUrl
     */
    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return int|null
     */
    public function getImageId(): ?int
    {
        return $this->imageId;
    }

    /**
     * @param int|null $imageId
     */
    public function setImageId(?int $imageId): void
    {
        $this->imageId = $imageId;
    }

    /**
     * @return string|null
     */
    public function getBannerImageUrl(): ?string
    {
        return $this->bannerImageUrl;
    }

    /**
     * @param string|null $bannerImageUrl
     */
    public function setBannerImageUrl(?string $bannerImageUrl): void
    {
        $this->bannerImageUrl = $bannerImageUrl;
    }

    /**
     * @return int|null
     */
    public function getBannerImageId(): ?int
    {
        return $this->bannerImageId;
    }

    /**
     * @param int|null $bannerImageId
     */
    public function setBannerImageId(?int $bannerImageId): void
    {
        $this->bannerImageId = $bannerImageId;
    }

    /**
     * @return bool|null
     */
    public function isConcluded(): ?bool
    {
        return $this->concluded;
    }

    /**
     * @param bool|null $concluded
     */
    public function setConcluded(?bool $concluded): void
    {
        $this->concluded = $concluded;
    }

    /**
     * @return bool|null
     */
    public function isPostManually(): ?bool
    {
        return $this->postManually;
    }

    /**
     * @param bool|null $postManually
     */
    public function setPostManually(?bool $postManually): void
    {
        $this->postManually = $postManually;
    }

    /**
     * @return string|null
     */
    public function getLtiContextId(): ?string
    {
        return $this->ltiContextId;
    }

    /**
     * @param string|null $ltiContextId
     */
    public function setLtiContextId(?string $ltiContextId): void
    {
        $this->ltiContextId = $ltiContextId;
    }

    /**
     * @return bool
     */
    public function isSyllabusCoursesSummary(): bool
    {
        return $this->syllabusCoursesSummary;
    }

    /**
     * @param bool $syllabusCoursesSummary
     */
    public function setSyllabusCoursesSummary(bool $syllabusCoursesSummary): void
    {
        $this->syllabusCoursesSummary = $syllabusCoursesSummary;
    }

    /**
     * @return bool
     */
    public function isUseBlueprintRestrictionsByObjectType(): bool
    {
        return $this->useBlueprintRestrictionsByObjectType;
    }

    /**
     * @param bool $useBlueprintRestrictionsByObjectType
     */
    public function setUseBlueprintRestrictionsByObjectType(bool $useBlueprintRestrictionsByObjectType): void
    {
        $this->useBlueprintRestrictionsByObjectType = $useBlueprintRestrictionsByObjectType;
    }

    /**
     * @return bool
     */
    public function isEnableCoursePaces(): bool
    {
        return $this->enableCoursePaces;
    }

    /**
     * @param bool $enableCoursePaces
     */
    public function setEnableCoursePaces(bool $enableCoursePaces): void
    {
        $this->enableCoursePaces = $enableCoursePaces;
    }

    /**
     * @return bool
     */
    public function isConditionalRelease(): bool
    {
        return $this->conditionalRelease;
    }

    /**
     * @param bool $conditionalRelease
     */
    public function setConditionalRelease(bool $conditionalRelease): void
    {
        $this->conditionalRelease = $conditionalRelease;
    }

    /**
     * @return string
     */
    public function getDefaultDueTime(): string
    {
        return $this->defaultDueTime;
    }

    /**
     * @param string $defaultDueTime Time in HH:MM:SS format (e.g., '23:59:59') or 'inherit'
     * @throws \InvalidArgumentException If time format is invalid
     */
    public function setDefaultDueTime(string $defaultDueTime): void
    {
        $timePattern = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';
        if ($defaultDueTime !== 'inherit' && !preg_match($timePattern, $defaultDueTime)) {
            throw new \InvalidArgumentException(
                'Default due time must be in HH:MM:SS format (e.g., "23:59:59") or "inherit"'
            );
        }
        $this->defaultDueTime = $defaultDueTime;
    }

    /**
     * Get calendar events for this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return CalendarEvent[]
     * @throws CanvasApiException
     */
    public function getCalendarEvents(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Course ID is required to get calendar events');
        }

        $params['context_codes'] = [sprintf('course_%d', $this->id)];
        return CalendarEvent::fetchAll($params);
    }

    /**
     * Get paginated calendar events for this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function getCalendarEventsPaginated(array $params = []): PaginatedResponse
    {
        if (!$this->id) {
            throw new CanvasApiException('Course ID is required to get calendar events');
        }

        $params['context_codes'] = [sprintf('course_%d', $this->id)];
        return CalendarEvent::fetchAllPaginated($params);
    }

    /**
     * Create a calendar event for this course
     *
     * @param CreateCalendarEventDTO|array<string, mixed> $data
     * @return CalendarEvent
     * @throws CanvasApiException
     */
    public function createCalendarEvent($data): CalendarEvent
    {
        if (!$this->id) {
            throw new CanvasApiException('Course ID is required to create calendar event');
        }

        $dto = $data instanceof CreateCalendarEventDTO ? $data : new CreateCalendarEventDTO($data);
        $dto->contextCode = sprintf('course_%d', $this->id);
        return CalendarEvent::create($dto);
    }

    // Assignment Relationship Methods

    /**
     * Get assignments for this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return Assignment[]
     * @throws CanvasApiException
     */
    public function assignments(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to fetch assignments');
        }

        Assignment::setCourse($this);
        return Assignment::fetchAll($params);
    }


    // Module Relationship Methods

    /**
     * Get modules for this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return Module[]
     * @throws CanvasApiException
     */
    public function modules(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to fetch modules');
        }

        Module::setCourse($this);
        return Module::fetchAll($params);
    }



    // Page Relationship Methods

    /**
     * Get pages for this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return Page[]
     * @throws CanvasApiException
     */
    public function pages(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to fetch pages');
        }

        Page::setCourse($this);
        return Page::fetchAll($params);
    }


    // Section Relationship Methods

    /**
     * Get sections for this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return Section[]
     * @throws CanvasApiException
     */
    public function sections(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to fetch sections');
        }

        Section::setCourse($this);
        return Section::fetchAll($params);
    }


    // Discussion Topic Relationship Methods

    /**
     * Get discussion topics for this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return DiscussionTopic[]
     * @throws CanvasApiException
     */
    public function discussionTopics(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to fetch discussion topics');
        }

        DiscussionTopic::setCourse($this);
        return DiscussionTopic::fetchAll($params);
    }


    // Quiz Relationship Methods

    /**
     * Get quizzes for this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return Quiz[]
     * @throws CanvasApiException
     */
    public function quizzes(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to fetch quizzes');
        }

        Quiz::setCourse($this);
        return Quiz::fetchAll($params);
    }


    // File Relationship Methods

    /**
     * Get files for this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return File[]
     * @throws CanvasApiException
     */
    public function files(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to fetch files');
        }

        return File::fetchCourseFiles($this->id, $params);
    }


    // Rubric Relationship Methods

    /**
     * Get rubrics for this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return Rubric[]
     * @throws CanvasApiException
     */
    public function rubrics(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to fetch rubrics');
        }

        self::checkApiClient();

        $endpoint = sprintf('courses/%d/rubrics', $this->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $rubricsData = json_decode($response->getBody(), true);

        $rubrics = [];
        foreach ($rubricsData as $rubricData) {
            $rubrics[] = new Rubric($rubricData);
        }

        return $rubrics;
    }


    // External Tool Relationship Methods

    /**
     * Get external tools for this course
     *
     * @param array<string, mixed> $params Query parameters
     * @return ExternalTool[]
     * @throws CanvasApiException
     */
    public function externalTools(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to fetch external tools');
        }

        ExternalTool::setCourse($this);
        return ExternalTool::fetchAll($params);
    }


    // Tab Relationship Methods

    /**
     * Get tabs for this course
     *
     * @return Tab[]
     * @throws CanvasApiException
     */
    public function tabs(): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Course ID is required to fetch tabs');
        }

        Tab::setCourse($this);
        return Tab::fetchAll();
    }


    /**
     * Set course timetable
     *
     * @param array<string, array<int, array{
     *   weekdays: string,
     *   start_time: string,
     *   end_time: string,
     *   location_name?: string
     * }>> $timetables
     * @return array<string, mixed>
     * @throws CanvasApiException
     */
    public function setTimetable(array $timetables): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('Course ID is required to set timetable');
        }

        $endpoint = sprintf('courses/%d/calendar_events/timetable', $this->id);

        $data = [];
        foreach ($timetables as $sectionId => $sectionTimetables) {
            foreach ($sectionTimetables as $index => $timetable) {
                $data[] = [
                    'name' => "timetables[$sectionId][$index][weekdays]",
                    'contents' => $timetable['weekdays']
                ];
                $data[] = [
                    'name' => "timetables[$sectionId][$index][start_time]",
                    'contents' => $timetable['start_time']
                ];
                $data[] = [
                    'name' => "timetables[$sectionId][$index][end_time]",
                    'contents' => $timetable['end_time']
                ];
                if (isset($timetable['location_name'])) {
                    $data[] = [
                        'name' => "timetables[$sectionId][$index][location_name]",
                        'contents' => $timetable['location_name']
                    ];
                }
            }
        }

        $response = self::$apiClient->post($endpoint, $data);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get course timetable
     *
     * @return array<string, mixed>
     * @throws CanvasApiException
     */
    public function getTimetable(): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('Course ID is required to get timetable');
        }

        $endpoint = sprintf('courses/%d/calendar_events/timetable', $this->id);
        $response = self::$apiClient->get($endpoint);
        return json_decode($response->getBody(), true);
    }

    /**
     * Set course timetable events directly
     *
     * @param array<int, array{
     *   start_at: \DateTime|string,
     *   end_at: \DateTime|string,
     *   location_name?: string,
     *   code?: string,
     *   title?: string
     * }> $events
     * @param string|null $sectionId
     * @return array<string, mixed>
     * @throws CanvasApiException
     */
    public function setTimetableEvents(array $events, ?string $sectionId = null): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('Course ID is required to set timetable events');
        }

        $endpoint = sprintf('courses/%d/calendar_events/timetable_events', $this->id);

        $data = [];
        if ($sectionId !== null) {
            $data[] = [
                'name' => 'course_section_id',
                'contents' => $sectionId
            ];
        }

        foreach ($events as $index => $event) {
            foreach ($event as $key => $value) {
                $data[] = [
                    'name' => "events[$index][$key]",
                    'contents' => $value instanceof \DateTime ? $value->format(\DateTime::ATOM) : $value
                ];
            }
        }

        $response = self::$apiClient->post($endpoint, $data);
        return json_decode($response->getBody(), true);
    }
}
