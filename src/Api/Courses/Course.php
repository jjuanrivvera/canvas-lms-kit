<?php

namespace CanvasLMS\Api\Courses;

use CanvasLMS\Config;
use CanvasLMS\Api\BaseApi;
use CanvasLMS\Dto\Courses\CreateCourseDTO;
use CanvasLMS\Dto\Courses\UpdateCourseDTO;
use CanvasLMS\Exceptions\CanvasApiException;

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
 * // Fetching all courses
 * $courses = Course::fetchAll();
 *```
 * @package CanvasLMS\Api
 */
class Course extends BaseApi
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
     * @var mixed[]|null
     */
    public ?array $calendar = [];

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
     * @var mixed[]|null
     */
    public ?array $term = [];

    /**
     * @var mixed[]|null
     */
    public ?array $courseProgress = [];

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
     * Create a new Course instance
     * @param mixed[]|CreateCourseDTO $courseData
     * @return self
     * @throws \Exception
     */
    public static function create($courseData): self
    {
        self::checkApiClient();

        if (is_array($courseData)) {
            $courseData = new CreateCourseDTO($courseData);
        }

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

        $response = self::$apiClient->post('/accounts/1/courses', [
            'multipart' => $dto->toApiArray()
        ]);

        $courseData = json_decode($response->getBody(), true);

        return new self($courseData);
    }

    /**
     * Update an existing course
     * @param int $id
     * @param mixed[]|CreateCourseDTO $courseData
     * @return self
     */
    public static function update(int $id, $courseData): self
    {
        self::checkApiClient();

        if (is_array($courseData)) {
            $courseData = new UpdateCourseDTO($courseData);
        }

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
     * @return object[]
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();

        $accountId = Config::getAccountId();

        $response = self::$apiClient->get("/accounts/{$accountId}/courses", [
            'query' => $params
        ]);

        $coursesData = json_decode($response->getBody(), true);

        $courses = [];

        foreach ($coursesData as $courseData) {
            $courses[] = new self($courseData);
        }

        return $courses;
    }

    /**
     * Save the course to the Canvas LMS system
     * @return bool
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
        } catch (CanvasApiException $th) {
            return false;
        }

        $updatedCourseData = json_decode($response->getBody(), true);
        $this->populate($updatedCourseData);

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
     * Reset the course in the Canvas LMS system
     * @return void
     * @throws CanvasApiException
     */
    public function reset(): void
    {
        self::checkApiClient();

        $response = self::$apiClient->post("/courses/{$this->id}/reset_content");

        $courseData = json_decode($response->getBody(), true);

        $this->populate($courseData);
    }
}
