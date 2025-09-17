<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Sections;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Sections\CreateSectionDTO;
use CanvasLMS\Dto\Sections\UpdateSectionDTO;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * Canvas LMS Sections API
 *
 * Provides functionality to manage course sections in Canvas LMS.
 * Sections are ways to divide students in a course into smaller groups.
 *
 * Usage Examples:
 *
 * ```php
 * // Set course context (required for all operations)
 * $course = Course::find(123);
 * Section::setCourse($course);
 *
 * // Get all sections for the course
 * $sections = Section::get();
 *
 * // Get sections with parameters
 * $sections = Section::get(['include' => ['students', 'enrollments']]);
 *
 * // Find a specific section
 * $section = Section::find(456);
 *
 * // Create a new section
 * $sectionData = [
 *     'name' => 'Lab Section A',
 *     'sisSectionId' => 'LAB001',
 *     'startAt' => '2024-01-15T00:00:00Z',
 *     'endAt' => '2024-05-15T23:59:59Z'
 * ];
 * $section = Section::create($sectionData);
 *
 * // Update a section
 * $updatedSection = Section::update(456, ['name' => 'Updated Lab Section A']);
 *
 * // Update using instance method
 * $section = Section::find(456);
 * $section->setName('New Section Name');
 * $success = $section->save();
 *
 * // Delete a section
 * $section = Section::find(456);
 * $success = $section->delete();
 * ```
 *
 * @package CanvasLMS\Api\Sections
 */
class Section extends AbstractBaseApi
{
    protected static ?Course $course = null;

    /**
     * Section unique identifier
     */
    public ?int $id = null;

    /**
     * Section name
     */
    public ?string $name = null;

    /**
     * Course ID this section belongs to
     */
    public ?int $courseId = null;

    /**
     * SIS section ID
     */
    public ?string $sisSectionId = null;

    /**
     * SIS course ID
     */
    public ?string $sisCourseId = null;

    /**
     * Integration ID
     */
    public ?string $integrationId = null;

    /**
     * Section start date
     */
    public ?string $startAt = null;

    /**
     * Section end date
     */
    public ?string $endAt = null;

    /**
     * Whether this section restricts enrollments to students
     */
    public ?bool $restrictEnrollmentsToSectionDates = null;

    /**
     * Nonxlist course ID if cross-listed
     */
    public ?int $nonxlistCourseId = null;

    /**
     * SIS import ID
     */
    public ?int $sisImportId = null;

    /**
     * Total number of students in this section
     */
    public ?int $totalStudents = null;

    /**
     * Students array (populated when included)
     * @var mixed[]|null
     */
    public ?array $students = null;

    /**
     * Enrollments array (populated when included)
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $enrollments = null;

    /**
     * Grade passback status
     */
    public ?string $passbackStatus = null;

    /**
     * Section permissions
     * @var array<string, bool>|null
     */
    public ?array $permissions = null;

    /**
     * Create a new Section instance
     *
     * @param array<string, mixed> $data Section data from Canvas API
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Set the course context for section operations
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
            throw new CanvasApiException('Course context is required');
        }
        return true;
    }

    /**
     * Find a single section by ID
     *
     * @param int $id Section ID
     * @param array<string, mixed> $params Optional query parameters
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkApiClient();

        // Use course context if available, otherwise use direct section endpoint
        if (isset(self::$course) && isset(self::$course->id)) {
            $endpoint = sprintf('courses/%d/sections/%d', self::$course->id, $id);
        } else {
            $endpoint = sprintf('sections/%d', $id);
        }

        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = self::parseJsonResponse($response);

        return new self($data);
    }

    /**
     * Get sections for the current course
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<self>
     * @throws CanvasApiException
     */
    public static function get(array $params = []): array
    {
        // Validate search_term parameter
        if (isset($params['search_term']) && strlen($params['search_term']) < 2) {
            throw new CanvasApiException('search_term must be at least 2 characters');
        }

        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/sections', self::$course->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = self::parseJsonResponse($response);

        if (!is_array($data)) {
            return [];
        }

        return array_map(fn($item) => new self($item), $data);
    }

    /**
     * Get all sections for the current course (paginated)
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<self>
     * @throws CanvasApiException
     */
    public static function all(array $params = []): array
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/sections', self::$course->id);
        $paginatedResponse = self::$apiClient->getPaginated($endpoint, ['query' => $params]);
        $data = $paginatedResponse->all();

        if (!is_array($data)) {
            return [];
        }

        return array_map(fn($item) => new self($item), $data);
    }

    /**
     * Get sections with pagination support
     *
     * @param array<string, mixed> $params Query parameters
     * @return \CanvasLMS\Pagination\PaginationResult
     * @throws CanvasApiException
     */
    public static function paginate(array $params = []): \CanvasLMS\Pagination\PaginationResult
    {
        self::checkCourse();
        self::checkApiClient();

        $endpoint = sprintf('courses/%d/sections', self::$course->id);
        $paginatedResponse = self::$apiClient->getPaginated($endpoint, ['query' => $params]);

        $data = $paginatedResponse->getJsonData();
        $sections = array_map(fn($item) => new self($item), $data);

        return $paginatedResponse->toPaginationResult($sections);
    }

    /**
     * Create a new section
     *
     * @param array<string, mixed>|CreateSectionDTO $data Section data
     * @return self Created Section object
     * @throws CanvasApiException
     */
    public static function create(array|CreateSectionDTO $data): self
    {
        self::checkCourse();
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateSectionDTO($data);
        }

        $endpoint = sprintf('courses/%d/sections', self::$course->id);
        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $sectionData = self::parseJsonResponse($response);

        return new self($sectionData);
    }

    /**
     * Update a section
     *
     * @param int $id Section ID
     * @param array<string, mixed>|UpdateSectionDTO $data Section data
     * @return self Updated Section object
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateSectionDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateSectionDTO($data);
        }

        $endpoint = sprintf('sections/%d', $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $data->toApiArray()]);
        $sectionData = self::parseJsonResponse($response);

        return new self($sectionData);
    }

    /**
     * Save the current section (create or update)
     *
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        // Check for required fields before trying to save
        if (!$this->id && empty($this->name)) {
            throw new CanvasApiException('Section name is required');
        }

        if ($this->id) {
            // Update existing section
            $updateData = $this->toDtoArray();
            if (empty($updateData)) {
                return $this; // Nothing to update
            }

            $updatedSection = self::update($this->id, $updateData);
            $this->populate($updatedSection->toArray());
        } else {
            // Create new section
            $createData = $this->toDtoArray();

            $newSection = self::create($createData);
            $this->populate($newSection->toArray());
        }

        return $this;
    }

    /**
     * Delete the section
     *
     * @return self
     * @throws CanvasApiException
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Section ID is required for deletion');
        }

        self::checkApiClient();

        // Use direct section endpoint for deletion
        $endpoint = sprintf('sections/%d', $this->id);
        self::$apiClient->delete($endpoint);

        return $this;
    }

    /**
     * Convert section to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'course_id' => $this->courseId,
            'sis_section_id' => $this->sisSectionId,
            'sis_course_id' => $this->sisCourseId,
            'integration_id' => $this->integrationId,
            'start_at' => $this->startAt,
            'end_at' => $this->endAt,
            'restrict_enrollments_to_section_dates' => $this->restrictEnrollmentsToSectionDates,
            'nonxlist_course_id' => $this->nonxlistCourseId,
            'sis_import_id' => $this->sisImportId,
            'total_students' => $this->totalStudents,
            'students' => $this->students,
            'enrollments' => $this->enrollments,
            'passback_status' => $this->passbackStatus,
            'permissions' => $this->permissions,
        ];
    }

    /**
     * Convert section to DTO array format
     *
     * @return array<string, mixed>
     */
    public function toDtoArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'sis_section_id' => $this->sisSectionId,
            'integration_id' => $this->integrationId,
            'start_at' => $this->startAt,
            'end_at' => $this->endAt,
            'restrict_enrollments_to_section_dates' => $this->restrictEnrollmentsToSectionDates,
        ], fn($value) => $value !== null);
    }

    // Getter methods
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCourseId(): ?int
    {
        return $this->courseId;
    }

    public function getSisSectionId(): ?string
    {
        return $this->sisSectionId;
    }

    public function getIntegrationId(): ?string
    {
        return $this->integrationId;
    }

    public function getStartAt(): ?string
    {
        return $this->startAt;
    }

    public function getEndAt(): ?string
    {
        return $this->endAt;
    }

    public function getRestrictEnrollmentsToSectionDates(): ?bool
    {
        return $this->restrictEnrollmentsToSectionDates;
    }

    public function getNonxlistCourseId(): ?int
    {
        return $this->nonxlistCourseId;
    }

    public function getSisImportId(): ?int
    {
        return $this->sisImportId;
    }

    public function getTotalStudents(): ?int
    {
        return $this->totalStudents;
    }

    // Setter methods
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function setSisSectionId(?string $sisSectionId): void
    {
        $this->sisSectionId = $sisSectionId;
    }

    public function setIntegrationId(?string $integrationId): void
    {
        $this->integrationId = $integrationId;
    }

    public function setStartAt(?string $startAt): void
    {
        $this->startAt = $startAt;
    }

    public function setEndAt(?string $endAt): void
    {
        $this->endAt = $endAt;
    }

    public function setRestrictEnrollmentsToSectionDates(?bool $restrictEnrollmentsToSectionDates): void
    {
        $this->restrictEnrollmentsToSectionDates = $restrictEnrollmentsToSectionDates;
    }

    /**
     * Get the associated course for this section
     *
     * @return Course
     * @throws CanvasApiException
     */
    public function course(): Course
    {
        self::checkCourse();
        return self::$course;
    }

    /**
     * Get enrollments for this section
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<\CanvasLMS\Api\Enrollments\Enrollment>
     * @throws CanvasApiException
     */
    public function enrollments(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Section ID is required to fetch enrollments');
        }

        return \CanvasLMS\Api\Enrollments\Enrollment::fetchAllBySection($this->id, $params);
    }

    /**
     * Get student enrollments for this section
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<\CanvasLMS\Api\Enrollments\Enrollment>
     * @throws CanvasApiException
     */
    public function students(array $params = []): array
    {
        $params['type[]'] = ['StudentEnrollment'];
        return $this->enrollments($params);
    }

    /**
     * Cross-list a section to another course
     *
     * @param int $sectionId Section ID to cross-list
     * @param int $newCourseId Target course ID
     * @param bool $overrideSisStickiness Override SIS stickiness
     * @return self Cross-listed Section object
     * @throws CanvasApiException
     */
    public static function crossList(int $sectionId, int $newCourseId, bool $overrideSisStickiness = true): self
    {
        self::checkApiClient();

        $endpoint = sprintf('sections/%d/crosslist/%d', $sectionId, $newCourseId);
        $multipart = [
            [
                'name' => 'override_sis_stickiness',
                'contents' => $overrideSisStickiness ? 'true' : 'false'
            ]
        ];

        $response = self::$apiClient->post($endpoint, ['multipart' => $multipart]);
        $data = self::parseJsonResponse($response);

        return new self($data);
    }

    /**
     * De-cross-list a section (return it to its original course)
     *
     * @param int $sectionId Section ID to de-cross-list
     * @param bool $overrideSisStickiness Override SIS stickiness
     * @return self De-cross-listed Section object
     * @throws CanvasApiException
     */
    public static function deCrossList(int $sectionId, bool $overrideSisStickiness = true): self
    {
        self::checkApiClient();

        $endpoint = sprintf('sections/%d/crosslist', $sectionId);
        $queryParams = ['override_sis_stickiness' => $overrideSisStickiness];

        $response = self::$apiClient->delete($endpoint, ['query' => $queryParams]);
        $data = self::parseJsonResponse($response);

        return new self($data);
    }

    /**
     * Get the API endpoint for this resource
     * @return string
     * @throws CanvasApiException
     */
    protected static function getEndpoint(): string
    {
        self::checkCourse();
        return sprintf('courses/%d/sections', self::$course->getId());
    }
}
