<?php

namespace CanvasLMS\Api\Courses;

use Exception;
use CanvasLMS\Config;
use CanvasLMS\Api\AbstractBaseApi;
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
     * @param CreateCourseDTO|mixed[] $courseData
     * @return self
     * @throws Exception
     */
    public static function create(array | CreateCourseDTO $courseData): self
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
     * @return mixed[]|null
     */
    public function getCalendar(): ?array
    {
        return $this->calendar;
    }

    /**
     * @param mixed[]|null $calendar
     */
    public function setCalendar(?array $calendar): void
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
     * @return mixed[]|null
     */
    public function getTerm(): ?array
    {
        return $this->term;
    }

    /**
     * @param mixed[]|null $term
     */
    public function setTerm(?array $term): void
    {
        $this->term = $term;
    }

    /**
     * @return mixed[]|null
     */
    public function getCourseProgress(): ?array
    {
        return $this->courseProgress;
    }

    /**
     * @param mixed[]|null $courseProgress
     */
    public function setCourseProgress(?array $courseProgress): void
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
}
