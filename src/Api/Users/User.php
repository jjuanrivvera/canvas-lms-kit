<?php

namespace CanvasLMS\Api\Users;

use Exception;
use CanvasLMS\Config;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Enrollments\Enrollment;
use CanvasLMS\Api\Groups\Group;
use CanvasLMS\Dto\Users\UpdateUserDTO;
use CanvasLMS\Dto\Users\CreateUserDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginationResult;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Objects\ActivityStreamItem;
use CanvasLMS\Objects\ActivityStreamSummary;
use CanvasLMS\Objects\TodoItem;
use CanvasLMS\Objects\UpcomingEvent;
use CanvasLMS\Api\Assignments\Assignment;
use CanvasLMS\Api\Files\File;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Objects\Profile;
use CanvasLMS\Objects\Avatar;
use CanvasLMS\Objects\CourseNickname;
use CanvasLMS\Objects\PageView;
use CanvasLMS\Api\CalendarEvents\CalendarEvent;
use CanvasLMS\Dto\CalendarEvents\CreateCalendarEventDTO;

/**
 * User Class
 *
 * Represents a user in the Canvas LMS. This class provides methods to create, update,
 * and find users from the Canvas LMS system. It utilizes Data Transfer Objects (DTOs)
 * for handling user creation and updates.
 *
 * Usage Examples:
 *
 * ```php
 * // Creating a new user
 * $userData = [
 *     'username' => 'john_doe',
 *     'email' => 'john.doe@example.com',
 *     // ... other user data ...
 * ];
 * $user = User::create($userData);
 *
 * // Updating an existing user
 * $updatedData = [
 *     'email' => 'new_john.doe@example.com',
 *     // ... other updated data ...
 * ];
 * $updatedUser = User::update(123, $updatedData); // where 123 is the user ID
 *
 * // Finding a user by ID
 * $user = User::find(123);
 *
 * // Fetching all users (first page only)
 * $users = User::fetchAll();
 *
 * // Fetching users with pagination support
 * $paginatedResponse = User::fetchAllPaginated(['per_page' => 10]);
 * $users = $paginatedResponse->getJsonData();
 * $pagination = $paginatedResponse->toPaginationResult($users);
 *
 * // Fetching a specific page of users
 * $paginationResult = User::fetchPage(['page' => 2, 'per_page' => 10]);
 * $users = $paginationResult->getData();
 * $hasNext = $paginationResult->hasNext();
 *
 * // Fetching all users from all pages
 * $allUsers = User::fetchAllPages(['per_page' => 50]);
 * ```
 *
 * @package CanvasLMS\Api
 */
class User extends AbstractBaseApi
{
    /**
     * The ID of the user.
     * @var int
     */
    public int $id;

    /**
     * The name of the user.
     * @var string
     */
    public string $name;

    /**
     * The name of the user that it should be used for sorting groups of users, such
     * as in the gradebook.
     * @var string
     */
    public string $sortableName;

    /**
     * The last name of the user.
     * @var string
     */
    public string $lastName;

    /**
     * The first name of the user.
     * @var string
     */
    public string $firstName;

    /**
     * A short name the user has selected, for use in conversations or other less
     * formal places through the site.
     * @var string
     */
    public string $shortName;

    /**
     * The SIS ID associated with the user. This field is only included if the user
     * came from a SIS import and has permissions to view SIS information.
     * @var string|null
     */
    public ?string $sisUserId;

    /**
     * The id of the SIS import. This field is only included if the user came from
     * a SIS import and has permissions to manage SIS information.
     * @var int|null
     */
    public ?int $sisImportId;

    /**
     * The integration_id associated with the user. This field is only included if
     * the user came from a SIS import and has permissions to view SIS information.
     * @var string|null
     */
    public ?string $integrationId;

    /**
     * The unique login id for the user. This is what the user uses to log in to
     * Canvas.
     * @var string
     */
    public string $loginId;

    /**
     * If avatars are enabled, this field will be included and contain a url to
     * retrieve the user's avatar.
     * @var string|null
     */
    public ?string $avatarUrl;

    /**
     * Optional: If avatars are enabled and caller is admin, this field can be
     * requested and will contain the current state of the user's avatar.
     * @var string|null
     */
    public ?string $avatarState;

    /**
     * Optional: This field can be requested with certain API calls, and will return
     * a list of the users active enrollments. See the List enrollments API for more
     * details about the format of these records.
     * @var mixed[]|null
     */
    public ?array $enrollments;

    /**
     * Optional: This field can be requested with certain API calls, and will return
     * the users primary email address.
     * @var string|null
     */
    public ?string $email;

    /**
     * Optional: This field can be requested with certain API calls, and will return
     * the users locale in RFC 5646 format.
     * @var string|null
     */
    public ?string $locale;

    /**
     * Optional: This field is only returned in certain API calls, and will return a
     * timestamp representing the last time the user logged in to canvas.
     * @var string|null
     */
    public ?string $lastLogin;

    /**
     * Optional: This field is only returned in certain API calls, and will return
     * the IANA time zone name of the user's preferred timezone.
     * @var string|null
     */
    public ?string $timeZone;

    /**
     * Optional: The user's bio.
     * @var string|null
     */
    public ?string $bio;

    /**
     * The user's effective locale.
     * @var string|null
     */
    public ?string $effectiveLocale = null;

    /**
     * Optional: This field is only returned in certain API calls, and will
     * return a boolean value indicating whether or not the user can update their
     * name.
     * @var bool|null
     */
    public ?bool $canUpdateName = null;

    /**
     * Create a new User instance.
     * @param mixed[]|CreateUserDTO $userData
     * @return self
     * @throws Exception
     */
    public static function create(array | CreateUserDTO $userData): self
    {
        self::checkApiClient();

        $userData = is_array($userData) ? new CreateUserDTO($userData) : $userData;

        return self::createFromDTO($userData);
    }

    /**
     * Create a User from a CreateUserDTO.
     * @param CreateUserDTO $dto
     * @return self
     * @throws CanvasApiException
     */
    private static function createFromDTO(CreateUserDTO $dto): self
    {
        self::checkApiClient();

        $response = self::$apiClient->post('/accounts/1/users', [
            'multipart' => $dto->toApiArray()
        ]);
        return new self(json_decode($response->getBody(), true));
    }

    /**
     * Find a single user by ID.
     * @param int $id
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkApiClient();

        $response = self::$apiClient->get("/users/{$id}");
        return new self(json_decode($response->getBody()->getContents(), true));
    }

    /**
     * Update an existing user.
     * @param int $id
     * @param UpdateUserDTO|mixed[] $userData
     * @return self
     * @throws CanvasApiException
     * @throws Exception
     */
    public static function update(int $id, array | UpdateUserDTO $userData): self
    {
        $userData = is_array($userData) ? new UpdateUserDTO($userData) : $userData;

        return self::updateFromDTO($id, $userData);
    }

    /**
     * Update a user from a UpdateUserDTO.
     * @param int $id
     * @param UpdateUserDTO $dto
     * @return self
     * @throws CanvasApiException
     */
    private static function updateFromDTO(int $id, UpdateUserDTO $dto): self
    {
        self::checkApiClient();

        $response = self::$apiClient->put("/users/{$id}", [
            'multipart' => $dto->toApiArray()
        ]);

        return new self(json_decode($response->getBody(), true));
    }

    /**
     * Fetch all users
     * @param mixed[] $params
     * @return User[]
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();

        $accountId = Config::getAccountId();

        $response = self::$apiClient->get("/accounts/{$accountId}/users", [
            'query' => $params
        ]);

        $users = json_decode($response->getBody(), true);

        return array_map(function ($user) {
            return new self($user);
        }, $users);
    }

    /**
     * Fetch users with pagination support
     * @param mixed[] $params Query parameters for the request
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        $accountId = Config::getAccountId();
        return self::getPaginatedResponse("/accounts/{$accountId}/users", $params);
    }

    /**
     * Fetch users from a specific page
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
     * Fetch all users from all pages
     * @param mixed[] $params Query parameters for the request
     * @return User[]
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        $accountId = Config::getAccountId();
        return self::fetchAllPagesAsModels("/accounts/{$accountId}/users", $params);
    }

    /**
     * Save the user to the Canvas LMS.
     * @return bool
     * @throws Exception
     */
    public function save(): bool
    {
        self::checkApiClient();

        $data = $this->toDtoArray();
        $accountId = Config::getAccountId();

        // If the user has an ID, update it. Otherwise, create a new user.
        $dto = $this->id ? new UpdateUserDTO($data) : new CreateUserDTO($data);
        $path = $this->id ? "/users/{$this->id}" : "/accounts/{$accountId}/users";
        $method = $this->id ? 'PUT' : 'POST';

        try {
            $response = self::$apiClient->request($method, $path, [
                'multipart' => $dto->toApiArray()
            ]);

            $updatedUserData = json_decode($response->getBody(), true);
            $this->populate($updatedUserData);
        } catch (CanvasApiException $e) {
            return false;
        }

        return true;
    }

    /**
     * Merge current user into another one
     * @param int $destinationUserId The ID of the user to merge into
     * @return bool
     * @throws Exception
     */
    public function mergeInto(int $destinationUserId): bool
    {
        self::checkApiClient();

        try {
            $response = self::$apiClient->put("/users/{$this->id}/merge_into/{$destinationUserId}");

            $this->populate(json_decode($response->getBody(), true));
        } catch (CanvasApiException $e) {
            return false;
        }

        return true;
    }

    // Relationship Method Aliases

    /**
     * Get enrollments for this user (relationship method alias with parameter support)
     *
     * This method provides a clean, explicit way to access user enrollments
     * across all courses without conflicts with Canvas API data structure.
     * Supports all Canvas API enrollment parameters for filtering.
     *
     * @example
     * ```php
     * $user = User::find(456);
     *
     * // Get all enrollments for this user
     * $enrollments = $user->enrollments();
     *
     * // Get active enrollments only
     * $activeEnrollments = $user->enrollments([
     *     'state[]' => ['active']
     * ]);
     *
     * // Get student enrollments only
     * $studentEnrollments = $user->enrollments([
     *     'type[]' => ['StudentEnrollment']
     * ]);
     *
     * // Get enrollments with course data included
     * $enrollmentsWithCourses = $user->enrollments([
     *     'include[]' => ['course']
     * ]);
     * ```
     *
     * @param mixed[] $params Query parameters for filtering enrollments:
     *   - type[]: Filter by enrollment type (e.g., ['StudentEnrollment', 'TeacherEnrollment'])
     *   - role[]: Filter by enrollment role
     *   - state[]: Filter by enrollment state (e.g., ['active', 'invited', 'completed'])
     *   - include[]: Include additional data (e.g., ['course', 'avatar_url'])
     * @return Enrollment[] Array of Enrollment objects
     * @throws CanvasApiException If the user ID is not set or API request fails
     */
    public function enrollments(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('User ID is required to fetch enrollments');
        }

        return Enrollment::fetchAllByUser($this->id, $params);
    }

    // Enrollment Relationship Methods


    /**
     * Get enrollments for this user in a specific course
     *
     * Returns only the enrollments for this user in the specified course.
     * This is useful for checking a user's role(s) in a particular course.
     *
     * @param int $courseId The course ID to filter enrollments by
     * @param mixed[] $params Additional query parameters
     * @return Enrollment[] Array of Enrollment objects for the specified course
     * @throws CanvasApiException If user ID is not set or API request fails
     */
    public function getEnrollmentsInCourse(int $courseId, array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('User ID is required to fetch enrollments');
        }

        // Add course_id filter to params
        $params = array_merge($params, ['course_id' => $courseId]);

        return Enrollment::fetchAllByUser($this->id, $params);
    }

    /**
     * Get active enrollments for this user
     *
     * Convenience method to get only active enrollments across all courses.
     *
     * @param mixed[] $params Additional query parameters
     * @return Enrollment[] Array of active Enrollment objects
     * @throws CanvasApiException If user ID is not set or API request fails
     */
    public function getActiveEnrollments(array $params = []): array
    {
        $params = array_merge($params, ['state[]' => ['active']]);
        return $this->enrollments($params);
    }

    /**
     * Get student enrollments for this user
     *
     * Convenience method to get only student enrollments across all courses.
     *
     * @param mixed[] $params Additional query parameters
     * @return Enrollment[] Array of student Enrollment objects
     * @throws CanvasApiException If user ID is not set or API request fails
     */
    public function getStudentEnrollments(array $params = []): array
    {
        $params = array_merge($params, ['type[]' => ['StudentEnrollment']]);
        return $this->enrollments($params);
    }

    /**
     * Get teacher enrollments for this user
     *
     * Convenience method to get only teacher enrollments across all courses.
     *
     * @param mixed[] $params Additional query parameters
     * @return Enrollment[] Array of teacher Enrollment objects
     * @throws CanvasApiException If user ID is not set or API request fails
     */
    public function getTeacherEnrollments(array $params = []): array
    {
        $params = array_merge($params, ['type[]' => ['TeacherEnrollment']]);
        return $this->enrollments($params);
    }

    /**
     * Check if this user is enrolled in a specific course
     *
     * @param int $courseId The course ID to check enrollment in
     * @param string|null $enrollmentType Optional: specific enrollment type to check for
     * @return bool True if user is enrolled in the course (with optional type filter)
     * @throws CanvasApiException If user ID is not set or API request fails
     */
    public function isEnrolledInCourse(int $courseId, ?string $enrollmentType = null): bool
    {
        $params = [];
        if ($enrollmentType) {
            $params['type[]'] = [$enrollmentType];
        }

        $enrollments = $this->getEnrollmentsInCourse($courseId, $params);
        return count($enrollments) > 0;
    }

    /**
     * Check if this user is a student in a specific course
     *
     * @param int $courseId The course ID to check
     * @return bool True if user has a student enrollment in the course
     * @throws CanvasApiException If user ID is not set or API request fails
     */
    public function isStudentInCourse(int $courseId): bool
    {
        return $this->isEnrolledInCourse($courseId, 'StudentEnrollment');
    }

    /**
     * Check if this user is a teacher in a specific course
     *
     * @param int $courseId The course ID to check
     * @return bool True if user has a teacher enrollment in the course
     * @throws CanvasApiException If user ID is not set or API request fails
     */
    public function isTeacherInCourse(int $courseId): bool
    {
        return $this->isEnrolledInCourse($courseId, 'TeacherEnrollment');
    }

    /**
     * Get the user's enrollment data array (legacy method - uses embedded data)
     *
     * This returns the raw enrollments array that may be embedded in the user object
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
    public function getSortableName(): string
    {
        return $this->sortableName;
    }

    /**
     * @param string $sortableName
     */
    public function setSortableName(string $sortableName): void
    {
        $this->sortableName = $sortableName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getShortName(): string
    {
        return $this->shortName;
    }

    /**
     * @param string $shortName
     */
    public function setShortName(string $shortName): void
    {
        $this->shortName = $shortName;
    }

    /**
     * @return string|null
     */
    public function getSisUserId(): ?string
    {
        return $this->sisUserId;
    }

    /**
     * @param string|null $sisUserId
     */
    public function setSisUserId(?string $sisUserId): void
    {
        $this->sisUserId = $sisUserId;
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
     * @return string
     */
    public function getLoginId(): string
    {
        return $this->loginId;
    }

    /**
     * @param string $loginId
     */
    public function setLoginId(string $loginId): void
    {
        $this->loginId = $loginId;
    }

    /**
     * @return string|null
     */
    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    /**
     * @param string|null $avatarUrl
     */
    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    /**
     * @return string|null
     */
    public function getAvatarState(): ?string
    {
        return $this->avatarState;
    }

    /**
     * @param string|null $avatarState
     */
    public function setAvatarState(?string $avatarState): void
    {
        $this->avatarState = $avatarState;
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
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
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
     * @return string|null
     */
    public function getLastLogin(): ?string
    {
        return $this->lastLogin;
    }

    /**
     * @param string|null $lastLogin
     */
    public function setLastLogin(?string $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return string|null
     */
    public function getTimeZone(): ?string
    {
        return $this->timeZone;
    }

    /**
     * @param string|null $timeZone
     */
    public function setTimeZone(?string $timeZone): void
    {
        $this->timeZone = $timeZone;
    }

    /**
     * @return string|null
     */
    public function getBio(): ?string
    {
        return $this->bio;
    }

    /**
     * @param string|null $bio
     */
    public function setBio(?string $bio): void
    {
        $this->bio = $bio;
    }

    /**
     * @return string|null
     */
    public function getEffectiveLocale(): ?string
    {
        return $this->effectiveLocale;
    }

    /**
     * @param string|null $effectiveLocale
     */
    public function setEffectiveLocale(?string $effectiveLocale): void
    {
        $this->effectiveLocale = $effectiveLocale;
    }

    /**
     * @return bool|null
     */
    public function getCanUpdateName(): ?bool
    {
        return $this->canUpdateName;
    }

    /**
     * @param bool|null $canUpdateName
     */
    public function setCanUpdateName(?bool $canUpdateName): void
    {
        $this->canUpdateName = $canUpdateName;
    }

    // Activity Stream Methods

    /**
     * Get the user's activity stream
     *
     * Returns the current user's global activity stream, paginated.
     * Use 'self' as the user ID to get the current authenticated user's stream.
     *
     * @param array<string, mixed> $params Optional parameters
     * @return ActivityStreamItem[]
     * @throws CanvasApiException
     */
    public function getActivityStream(array $params = []): array
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';
        $response = self::$apiClient->get("/users/{$userId}/activity_stream", [
            'query' => $params
        ]);

        $items = json_decode($response->getBody(), true);
        $activityItems = [];

        foreach ($items as $item) {
            try {
                $activityItems[] = ActivityStreamItem::createFromData($item);
            } catch (\InvalidArgumentException $e) {
                // Skip unknown activity stream item types
                continue;
            }
        }

        return $activityItems;
    }

    /**
     * Get activity stream summary
     *
     * Returns a summary of the current user's global activity stream.
     * Use 'self' as the user ID to get the current authenticated user's summary.
     *
     * @return ActivityStreamSummary[]
     * @throws CanvasApiException
     */
    public function getActivityStreamSummary(): array
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';
        $response = self::$apiClient->get("/users/{$userId}/activity_stream/summary");

        $summaries = json_decode($response->getBody(), true);

        return array_map(function ($summary) {
            return new ActivityStreamSummary($summary);
        }, $summaries);
    }

    /**
     * Hide a stream item
     *
     * Hide the given stream item for the user.
     * Use 'self' as the user ID for the current authenticated user.
     *
     * @param int $streamItemId The ID of the stream item to hide
     * @return bool True if successful
     * @throws CanvasApiException
     */
    public function hideStreamItem(int $streamItemId): bool
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';

        try {
            self::$apiClient->delete("/users/{$userId}/activity_stream/{$streamItemId}");
            return true;
        } catch (CanvasApiException $e) {
            return false;
        }
    }

    /**
     * Hide all stream items
     *
     * Hide all stream items for the user.
     * Use 'self' as the user ID for the current authenticated user.
     *
     * @return bool True if successful
     * @throws CanvasApiException
     */
    public function hideAllStreamItems(): bool
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';

        try {
            self::$apiClient->delete("/users/{$userId}/activity_stream");
            return true;
        } catch (CanvasApiException $e) {
            return false;
        }
    }

    // TODO & Events Methods

    /**
     * Get TODO items
     *
     * Get a paginated list of the current user's list of todo items,
     * as seen on the user dashboard.
     *
     * @param array<string, mixed> $params Optional parameters:
     *   - include[]: 'ungraded_quizzes' to include ungraded quizzes
     * @return TodoItem[]
     * @throws CanvasApiException
     */
    public function getTodoItems(array $params = []): array
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';
        $response = self::$apiClient->get("/users/{$userId}/todo", [
            'query' => $params
        ]);

        $items = json_decode($response->getBody(), true);

        return array_map(function ($item) {
            return new TodoItem($item);
        }, $items);
    }

    /**
     * Get upcoming events
     *
     * Get a paginated list of the current user's upcoming events.
     *
     * @return UpcomingEvent[]
     * @throws CanvasApiException
     */
    public function getUpcomingEvents(): array
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';
        $response = self::$apiClient->get("/users/{$userId}/upcoming_events");

        $events = json_decode($response->getBody(), true);

        return array_map(function ($event) {
            return new UpcomingEvent($event);
        }, $events);
    }

    // Missing Submissions

    /**
     * Get missing submissions
     *
     * Returns a paginated list of assignments for which the student has not yet
     * created a submission. The user sending the request must either be the
     * student, or must have permission to view the student's grades.
     *
     * @param array<string, mixed> $params Optional parameters:
     *   - filter[]: Array of assignment IDs to filter by
     *   - filter[course_ids][]: Array of course IDs to filter by
     *   - include[]: Array of additional fields to include: ['planner_overrides', 'course']
     *   - filter[submittable_types][]: Only return assignments that the user can submit online.
     *     Excludes assignments with no submission type.
     * @return Assignment[]
     * @throws CanvasApiException
     */
    public function getMissingSubmissions(array $params = []): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to fetch missing submissions');
        }

        $response = self::$apiClient->get("/users/{$this->id}/missing_submissions", [
            'query' => $params
        ]);

        $assignments = json_decode($response->getBody(), true);

        return array_map(function ($assignment) {
            return new Assignment($assignment);
        }, $assignments);
    }

    // File Upload

    /**
     * Upload a file to the user's personal files
     *
     * Uploads a file to the user's personal files area using Canvas's 3-step upload process.
     * The file will be placed in the user's root folder unless a parent_folder_id is specified.
     *
     * @param array<string, mixed> $fileData File upload data with the following possible keys:
     *   - name: Required. The file name
     *   - size: File size in bytes (optional if uploading from local file)
     *   - content_type: MIME type (optional, will be detected if not provided)
     *   - parent_folder_id: ID of the folder to upload to (optional, defaults to root)
     *   - file: Path to local file to upload
     *   - url: URL to download and upload (alternative to 'file')
     *   - on_duplicate: What to do if file already exists ('overwrite' or 'rename', defaults to 'overwrite')
     * @return File The uploaded File object
     * @throws CanvasApiException
     */
    public function uploadFile(array $fileData): File
    {
        if (!$this->id) {
            throw new CanvasApiException('User ID is required to upload files');
        }

        return File::uploadToUser($this->id, $fileData);
    }

    // Profile & Avatar Methods

    /**
     * Get user profile
     *
     * Returns the full profile information for the user.
     * This includes extended information beyond the basic User object.
     *
     * @return Profile The user's profile
     * @throws CanvasApiException
     */
    public function getProfile(): Profile
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';
        $response = self::$apiClient->get("/users/{$userId}/profile");

        $profileData = json_decode($response->getBody(), true);

        return new Profile($profileData);
    }

    /**
     * Get avatar options
     *
     * Retrieve the possible user avatar options that can be set for the user.
     * This includes uploaded files, Gravatar, and social media avatars.
     *
     * @return Avatar[] Array of Avatar objects
     * @throws CanvasApiException
     */
    public function getAvatarOptions(): array
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';
        $response = self::$apiClient->get("/users/{$userId}/avatars");

        $avatars = json_decode($response->getBody(), true);

        return array_map(function ($avatar) {
            return new Avatar($avatar);
        }, $avatars);
    }

    // Custom Data Storage Methods

    /**
     * Store custom data for the user
     *
     * Store arbitrary user data. Arbitrary JSON data can be stored as a hash,
     * and a namespace parameter is required to isolate different sets of data.
     * The scope parameter is optional and allows for additional segmentation.
     *
     * @param string $namespace Namespace to store the data under
     * @param array<string, mixed> $data Arbitrary data to store
     * @param string|null $scope Optional scope for additional data segmentation
     * @return array<string, mixed> The stored data
     * @throws CanvasApiException
     */
    public function setCustomData(string $namespace, array $data, ?string $scope = null): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to set custom data');
        }

        $path = "/users/{$this->id}/custom_data";
        if ($scope) {
            $path .= "/{$scope}";
        }

        $response = self::$apiClient->put($path, [
            'form_params' => [
                'ns' => $namespace,
                'data' => json_encode($data)
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get custom data for the user
     *
     * Retrieve arbitrary user data that was previously stored.
     * The namespace parameter is required to identify the data set.
     * The scope parameter is optional and must match what was used when storing.
     *
     * @param string $namespace Namespace to retrieve data from
     * @param string|null $scope Optional scope that was used when storing
     * @return array<string, mixed> The stored data
     * @throws CanvasApiException
     */
    public function getCustomData(string $namespace, ?string $scope = null): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to get custom data');
        }

        $path = "/users/{$this->id}/custom_data";
        if ($scope) {
            $path .= "/{$scope}";
        }

        $response = self::$apiClient->get($path, [
            'query' => ['ns' => $namespace]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Delete custom data for the user
     *
     * Delete arbitrary user data that was previously stored.
     * The namespace parameter is required to identify the data set.
     * The scope parameter is optional and must match what was used when storing.
     *
     * @param string $namespace Namespace to delete data from
     * @param string|null $scope Optional scope that was used when storing
     * @return bool True if successful
     * @throws CanvasApiException
     */
    public function deleteCustomData(string $namespace, ?string $scope = null): bool
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to delete custom data');
        }

        $path = "/users/{$this->id}/custom_data";
        if ($scope) {
            $path .= "/{$scope}";
        }

        try {
            self::$apiClient->delete($path, [
                'query' => ['ns' => $namespace]
            ]);
            return true;
        } catch (CanvasApiException $e) {
            return false;
        }
    }

    // Course Nickname Methods

    /**
     * Get all course nicknames for the user
     *
     * Returns a list of all course nicknames set by the user.
     *
     * @return CourseNickname[] Array of CourseNickname objects
     * @throws CanvasApiException
     */
    public function getCourseNicknames(): array
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';
        $response = self::$apiClient->get("/users/{$userId}/course_nicknames");

        $nicknames = json_decode($response->getBody(), true);

        return array_map(function ($nickname) {
            return new CourseNickname($nickname);
        }, $nicknames);
    }

    /**
     * Get course nickname for a specific course
     *
     * Returns the nickname for the specified course, or null if no nickname is set.
     *
     * @param int $courseId Course ID to get nickname for
     * @return CourseNickname|null The course nickname or null
     * @throws CanvasApiException
     */
    public function getCourseNickname(int $courseId): ?CourseNickname
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';

        try {
            $response = self::$apiClient->get("/users/{$userId}/course_nicknames/{$courseId}");
            $nicknameData = json_decode($response->getBody(), true);
            return new CourseNickname($nicknameData);
        } catch (CanvasApiException $e) {
            // Return null if no nickname is set (404 response)
            if ($e->getCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Set course nickname for a specific course
     *
     * Set or update the nickname for the specified course.
     *
     * @param int $courseId Course ID to set nickname for
     * @param string $nickname The nickname to set
     * @return CourseNickname The updated course nickname
     * @throws CanvasApiException
     */
    public function setCourseNickname(int $courseId, string $nickname): CourseNickname
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';
        $response = self::$apiClient->put("/users/{$userId}/course_nicknames/{$courseId}", [
            'form_params' => [
                'nickname' => $nickname
            ]
        ]);

        $nicknameData = json_decode($response->getBody(), true);
        return new CourseNickname($nicknameData);
    }

    /**
     * Remove course nickname for a specific course
     *
     * Remove the nickname for the specified course, reverting to the original course name.
     *
     * @param int $courseId Course ID to remove nickname for
     * @return bool True if successful
     * @throws CanvasApiException
     */
    public function removeCourseNickname(int $courseId): bool
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';

        try {
            self::$apiClient->delete("/users/{$userId}/course_nicknames/{$courseId}");
            return true;
        } catch (CanvasApiException $e) {
            return false;
        }
    }

    /**
     * Clear all course nicknames for the user
     *
     * Remove all course nicknames set by the user, reverting all courses to their original names.
     *
     * @return bool True if successful
     * @throws CanvasApiException
     */
    public function clearAllCourseNicknames(): bool
    {
        self::checkApiClient();

        $userId = $this->id ?? 'self';

        try {
            self::$apiClient->delete("/users/{$userId}/course_nicknames");
            return true;
        } catch (CanvasApiException $e) {
            return false;
        }
    }

    // User Management Methods

    /**
     * Self-register a new user
     *
     * Self register and return a new user and pseudonym for the account.
     * If self-registration is enabled on the Canvas account, a user can use
     * this endpoint to self register.
     *
     * @param array<string, mixed> $userData User registration data
     * @return self The newly registered user
     * @throws CanvasApiException
     */
    public static function selfRegister(array $userData): self
    {
        self::checkApiClient();

        $accountId = Config::getAccountId();
        $response = self::$apiClient->post("/accounts/{$accountId}/self_registration", [
            'form_params' => $userData
        ]);

        $newUserData = json_decode($response->getBody(), true);
        return new self($newUserData);
    }

    /**
     * Split a merged user
     *
     * Split a merged user into separate user accounts.
     * This operation is typically used to reverse a user merge operation.
     *
     * @return array<self> Array of User objects created from the split
     * @throws CanvasApiException
     */
    public function split(): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to split user');
        }

        $response = self::$apiClient->post("/users/{$this->id}/split");
        $splitUsers = json_decode($response->getBody(), true);

        return array_map(function ($userData) {
            return new self($userData);
        }, $splitUsers);
    }

    /**
     * Terminate all sessions for the user
     *
     * Terminate all active sessions for the user.
     * This will force the user to log in again on all devices.
     *
     * @return bool True if successful
     * @throws CanvasApiException
     */
    public function terminateAllSessions(): bool
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to terminate sessions');
        }

        try {
            self::$apiClient->delete("/users/{$this->id}/sessions");
            return true;
        } catch (CanvasApiException $e) {
            return false;
        }
    }

    // Analytics & Tracking Methods

    /**
     * Get page views for the user
     *
     * Return a paginated list of the user's page view history in Canvas.
     * Page views provide detailed tracking of user interactions within Canvas.
     *
     * @param array<string, mixed> $params Optional parameters:
     *   - start_time: DateTime to start the search from (ISO 8601 format)
     *   - end_time: DateTime to end the search at (ISO 8601 format)
     * @return PageView[] Array of PageView objects
     * @throws CanvasApiException
     */
    public function getPageViews(array $params = []): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to get page views');
        }

        $response = self::$apiClient->get("/users/{$this->id}/page_views", [
            'query' => $params
        ]);

        $pageViews = json_decode($response->getBody(), true);

        return array_map(function ($pageView) {
            return new PageView($pageView);
        }, $pageViews);
    }

    /**
     * Get pandata events token
     *
     * Return the user's pandata events token.
     * This token is used for analytics and tracking purposes.
     *
     * @return array<string, mixed> The pandata events token data
     * @throws CanvasApiException
     */
    public function getPandataEventsToken(): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to get pandata events token');
        }

        $response = self::$apiClient->get("/users/{$this->id}/pandata_events_token");

        return json_decode($response->getBody(), true);
    }

    /**
     * Get calendar events for this user
     *
     * @param array<string, mixed> $params Query parameters
     * @return CalendarEvent[]
     * @throws CanvasApiException
     */
    public function getCalendarEvents(array $params = []): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to get calendar events');
        }

        $endpoint = sprintf('users/%d/calendar_events', $this->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode($response->getBody(), true);

        return array_map(function ($item) {
            return new CalendarEvent($item);
        }, $data);
    }

    /**
     * Get paginated calendar events for this user
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function getCalendarEventsPaginated(array $params = []): PaginatedResponse
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to get calendar events');
        }

        $endpoint = sprintf('users/%d/calendar_events', $this->id);
        return CalendarEvent::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Create a calendar event for this user
     *
     * @param CreateCalendarEventDTO|array<string, mixed> $data
     * @return CalendarEvent
     * @throws CanvasApiException
     */
    public function createCalendarEvent($data): CalendarEvent
    {
        if (!$this->id) {
            throw new CanvasApiException('User ID is required to create calendar event');
        }

        $dto = $data instanceof CreateCalendarEventDTO ? $data : new CreateCalendarEventDTO($data);
        $dto->contextCode = sprintf('user_%d', $this->id);
        return CalendarEvent::create($dto);
    }

    // Additional Relationship Methods


    /**
     * Get groups for this user
     *
     * @param array<string, mixed> $params Query parameters
     * @return Group[]
     * @throws CanvasApiException
     */
    public function groups(array $params = []): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to get groups');
        }

        return Group::fetchUserGroups($this->id, $params);
    }


    /**
     * Get courses for this user
     *
     * @param array<string, mixed> $params Query parameters
     * @return Course[]
     * @throws CanvasApiException
     */
    public function courses(array $params = []): array
    {
        self::checkApiClient();

        if (!$this->id) {
            throw new CanvasApiException('User ID is required to get courses');
        }

        $endpoint = sprintf('users/%d/courses', $this->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $coursesData = json_decode($response->getBody(), true);

        $courses = [];
        foreach ($coursesData as $courseData) {
            $courses[] = new Course($courseData);
        }

        return $courses;
    }


    /**
     * Get files for this user
     *
     * @param array<string, mixed> $params Query parameters
     * @return File[]
     * @throws CanvasApiException
     */
    public function files(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('User ID is required to get files');
        }

        return File::fetchUserFiles($this->id, $params);
    }
}
