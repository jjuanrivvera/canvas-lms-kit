<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Sections;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Enrollments\Enrollment;
use CanvasLMS\Dto\Sections\CreateSectionDTO;
use CanvasLMS\Dto\Sections\UpdateSectionDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

/**
 * Section API class for managing course sections in Canvas LMS.
 *
 * @package CanvasLMS\Api\Sections
 * @see https://canvas.instructure.com/doc/api/sections.html
 */
class Section extends AbstractBaseApi
{
    protected static ?Course $course = null;

    // Core properties from Canvas API
    public ?int $id = null;
    public ?string $name = null;
    public ?string $sisSectionId = null;
    public ?string $integrationId = null;
    public ?int $sisImportId = null;
    public ?int $courseId = null;
    public ?string $sisCourseId = null;
    public ?string $startAt = null;
    public ?string $endAt = null;
    public ?bool $restrictEnrollmentsToSectionDates = null;
    public ?int $nonxlistCourseId = null;
    public ?int $totalStudents = null;

    // Additional properties from includes
    /** @var array<int, array<string, mixed>>|null */
    public ?array $students = null;
    /** @var array<int, array<string, mixed>>|null */
    public ?array $enrollments = null;
    public ?string $passbackStatus = null;
    /** @var array<string, bool>|null */
    public ?array $permissions = null;

    /**
     * Set the course context for section operations.
     */
    public static function setCourse(Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Check if course context is set.
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
     * Get first page of sections
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function get(array $params = []): array
    {
        self::checkApiClient();

        self::checkCourse();

        // Validate search_term if provided
        if (isset($params['search_term']) && strlen($params['search_term']) < 2) {
            throw new CanvasApiException('search_term must be at least 2 characters');
        }

        $endpoint = sprintf('courses/%d/sections', self::$course->getId());
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $responseData = json_decode($response->getBody()->getContents(), true);

        return array_map(function ($item) {
            return new self($item);
        }, $responseData);
    }

    /**
     * Get all sections from all pages
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function all(array $params = []): array
    {
        self::checkApiClient();

        self::checkCourse();

        $endpoint = sprintf('courses/%d/sections', self::$course->getId());
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);
        $allData = $paginatedResponse->all();

        $sections = [];
        foreach ($allData as $item) {
            $sections[] = new self($item);
        }

        return $sections;
    }

    /**
     * Get paginated sections
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function paginate(array $params = []): PaginationResult
    {
        self::checkApiClient();

        self::checkCourse();

        $endpoint = sprintf('courses/%d/sections', self::$course->getId());
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        // Convert data to models
        $data = [];
        foreach ($paginatedResponse->getJsonData() as $item) {
            $data[] = new self($item);
        }

        return $paginatedResponse->toPaginationResult($data);
    }

    /**
     * Get a single section by ID.
     * Supports both course-scoped and direct access endpoints.
     *
     * @param int $id Section ID
     * @param array<string, mixed> $params Optional parameters (include[])
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): self
    {
        // Use direct endpoint if no course context, otherwise use course-scoped endpoint
        try {
            self::checkCourse();
            $endpoint = sprintf('courses/%d/sections/%d', self::$course->id, $id);
        } catch (CanvasApiException $e) {
            $endpoint = sprintf('sections/%d', $id);
        }

        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode($response->getBody()->getContents(), true);

        $section = new self($data);
        try {
            self::checkCourse();
            $section->courseId = self::$course->id;
        } catch (CanvasApiException $e) {
            // No course context, skip setting courseId
        }

        return $section;
    }





    /**
     * Create a new section.
     *
     * @param array<string, mixed>|CreateSectionDTO $data Section data
     * @return self
     * @throws CanvasApiException
     */
    public static function create(array|CreateSectionDTO $data): self
    {
        self::checkCourse(); // Throws exception if course not set

        $dto = $data instanceof CreateSectionDTO ? $data : new CreateSectionDTO($data);
        $apiData = $dto->toApiArray();

        $endpoint = sprintf('courses/%d/sections', self::$course->id);
        $response = self::$apiClient->post($endpoint, ['multipart' => $apiData]);
        $responseData = json_decode($response->getBody()->getContents(), true);

        $section = new self($responseData);
        $section->courseId = self::$course->id;
        return $section;
    }

    /**
     * Update a section.
     *
     * @param int $id Section ID
     * @param array<string, mixed>|UpdateSectionDTO $data Update data
     * @return self
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateSectionDTO $data): self
    {
        $dto = $data instanceof UpdateSectionDTO ? $data : new UpdateSectionDTO($data);
        $apiData = $dto->toApiArray();

        $endpoint = sprintf('sections/%d', $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $apiData]);
        $responseData = json_decode($response->getBody()->getContents(), true);

        return new self($responseData);
    }

    /**
     * Cross-list a section to another course.
     *
     * @param int $sectionId Section ID to cross-list
     * @param int $newCourseId Target course ID
     * @param bool $overrideSisStickiness Override SIS stickiness (default: true)
     * @return self
     * @throws CanvasApiException
     */
    public static function crossList(int $sectionId, int $newCourseId, bool $overrideSisStickiness = true): self
    {
        $endpoint = sprintf('sections/%d/crosslist/%d', $sectionId, $newCourseId);

        // Create multipart format for the parameter
        $params = [
            [
                'name' => 'override_sis_stickiness',
                'contents' => $overrideSisStickiness ? 'true' : 'false'
            ]
        ];

        $response = self::$apiClient->post($endpoint, ['multipart' => $params]);
        $data = json_decode($response->getBody()->getContents(), true);

        return new self($data);
    }

    /**
     * De-cross-list a section, returning it to its original course.
     *
     * @param int $sectionId Section ID to de-cross-list
     * @param bool $overrideSisStickiness Override SIS stickiness (default: true)
     * @return self
     * @throws CanvasApiException
     */
    public static function deCrossList(int $sectionId, bool $overrideSisStickiness = true): self
    {
        $endpoint = sprintf('sections/%d/crosslist', $sectionId);
        $params = ['override_sis_stickiness' => $overrideSisStickiness];

        $response = self::$apiClient->delete($endpoint, ['query' => $params]);
        $data = json_decode($response->getBody()->getContents(), true);

        return new self($data);
    }

    /**
     * Save the section (create or update).
     *
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        if ($this->id) {
            // Update existing section
            $dto = new UpdateSectionDTO($this->toDtoArray());
            $updated = self::update($this->id, $dto);
            $this->populate(get_object_vars($updated));
        } else {
            // Create new section
            if (!self::checkCourse()) {
                throw new CanvasApiException('Course context is required for creating sections');
            }

            $dto = new CreateSectionDTO($this->toDtoArray());
            $created = self::create($dto);
            $this->populate(get_object_vars($created));
        }
        return $this;
    }

    /**
     * Delete the section.
     *
     * @return self
     * @throws CanvasApiException
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Section ID is required for deletion');
        }

        $endpoint = sprintf('sections/%d', $this->id);
        self::$apiClient->delete($endpoint);
        return $this;
    }

    /**
     * Convert section to array for DTO.
     *
     * @return array<string, mixed>
     */
    public function toDtoArray(): array
    {
        return [
            'name' => $this->name,
            'sis_section_id' => $this->sisSectionId,
            'integration_id' => $this->integrationId,
            'start_at' => $this->startAt,
            'end_at' => $this->endAt,
            'restrict_enrollments_to_section_dates' => $this->restrictEnrollmentsToSectionDates,
        ];
    }

    // Relationship Methods

    /**
     * Get the course this section belongs to
     *
     * @return Course|null
     * @throws CanvasApiException
     */
    public function course(): ?Course
    {
        self::checkCourse();
        return self::$course;
    }

    /**
     * Get enrollments for this section
     *
     * @param array<string, mixed> $params Query parameters
     * @return Enrollment[]
     * @throws CanvasApiException
     */
    public function enrollments(array $params = []): array
    {
        if (!isset($this->id) || !$this->id) {
            throw new CanvasApiException('Section ID is required to fetch enrollments');
        }

        return Enrollment::fetchAllBySection($this->id, $params);
    }


    /**
     * Get student enrollments for this section
     *
     * @param array<string, mixed> $params Query parameters
     * @return Enrollment[]
     * @throws CanvasApiException
     */
    public function students(array $params = []): array
    {
        $params = array_merge($params, ['type[]' => ['StudentEnrollment']]);
        return $this->enrollments($params);
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
