<?php

namespace CanvasLMS\Api\Rubrics;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Rubrics\CreateRubricDTO;
use CanvasLMS\Dto\Rubrics\UpdateRubricDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Objects\RubricCriterion;
use CanvasLMS\Pagination\PaginatedResponse;

/**
 * Rubric Class
 *
 * Represents a rubric in Canvas LMS. Rubrics are assessment tools that define
 * standardized grading criteria. They can exist at both the course and account
 * levels and can be associated with assignments, discussions, and other assessable items.
 *
 * Rubrics contain criteria with ratings that define performance levels and point values.
 * They provide consistent grading standards and clear expectations for students.
 *
 * Usage:
 *
 * ```php
 * // Creating a rubric in a course (using array)
 * $course = Course::find(123);
 * Rubric::setCourse($course);
 * $rubric = Rubric::create([
 *     'title' => 'Essay Rubric',
 *     'criteria' => [...]
 * ]);
 *
 * // Creating a rubric using DTO (still supported)
 * $dto = new CreateRubricDTO();
 * $dto->title = "Essay Rubric";
 * $dto->criteria = [...];
 * $rubric = Rubric::create($dto);
 *
 * // Finding a rubric
 * $rubric = Rubric::find(456);
 *
 * // Listing rubrics in a course
 * $rubrics = Rubric::fetchAll();
 *
 * // Updating a rubric (using array)
 * $rubric = Rubric::update(456, [
 *     'title' => 'Updated Essay Rubric'
 * ]);
 *
 * // Updating a rubric using DTO (still supported)
 * $updateDto = new UpdateRubricDTO();
 * $updateDto->title = "Updated Essay Rubric";
 * $rubric = Rubric::update(456, $updateDto);
 *
 * // Account-scoped rubrics (use Account class)
 * $account = Account::find(1);
 * $rubrics = $account->getRubrics();
 * $rubric = $account->createRubric(['title' => 'Account Rubric']);
 * ```
 *
 * @package CanvasLMS\Api\Rubrics
 */
class Rubric extends AbstractBaseApi
{
    /**
     * The ID of the rubric
     *
     * @var int|null
     */
    public ?int $id = null;

    /**
     * Title of the rubric
     *
     * @var string|null
     */
    public ?string $title = null;

    /**
     * The context owning the rubric (course_id)
     *
     * @var int|null
     */
    public ?int $contextId = null;

    /**
     * The context type owning the rubric
     *
     * @var string|null
     */
    public ?string $contextType = null;

    /**
     * Total points possible for the rubric
     *
     * @var float|null
     */
    public ?float $pointsPossible = null;

    /**
     * Whether the rubric is reusable
     *
     * @var bool|null
     */
    public ?bool $reusable = null;

    /**
     * Whether the rubric is read-only
     *
     * @var bool|null
     */
    public ?bool $readOnly = null;

    /**
     * Whether free-form comments are used
     *
     * @var bool|null
     */
    public ?bool $freeFormCriterionComments = null;

    /**
     * Whether to hide the score total
     *
     * @var bool|null
     */
    public ?bool $hideScoreTotal = null;

    /**
     * Array of rubric criteria
     *
     * @var array<int, RubricCriterion>|null
     */
    public ?array $data = null;

    /**
     * Array of rubric assessments (when included)
     *
     * @var array<int, mixed>|null
     */
    public ?array $assessments = null;

    /**
     * Array of rubric associations (when included)
     *
     * @var array<int, mixed>|null
     */
    public ?array $associations = null;

    /**
     * Associated RubricAssociation from create/update responses
     *
     * @var RubricAssociation|null
     */
    public ?RubricAssociation $association = null;

    /**
     * Course context for operations
     *
     * @var Course|null
     */
    protected static ?Course $course = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Handle nested objects
        if (isset($data['data']) && is_array($data['data'])) {
            $this->data = [];
            foreach ($data['data'] as $criterion) {
                if (is_array($criterion)) {
                    $this->data[] = new RubricCriterion($criterion);
                }
            }
        }
    }

    /**
     * Set the course context for rubric operations
     *
     * @param Course|null $course
     * @return void
     */
    public static function setCourse(?Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Check if course context is set
     *
     * @return bool
     * @throws CanvasApiException
     */
    protected static function checkCourse(): bool
    {
        if (self::$course === null) {
            throw new CanvasApiException('Course context is required for course-scoped rubric operations');
        }
        return true;
    }

    /**
     * Get the resource identifier for API endpoints
     *
     * @return string
     */
    protected static function getResourceIdentifier(): string
    {
        return 'rubrics';
    }

    /**
     * Get the resource endpoint
     *
     * @return string
     * @throws CanvasApiException
     */
    protected static function getResourceEndpoint(): string
    {
        self::checkCourse();
        return sprintf('courses/%d/rubrics', self::$course->id);
    }

    /**
     * Create a new rubric
     *
     * @param array<string, mixed>|CreateRubricDTO $data The rubric data
     * @return self
     * @throws CanvasApiException
     */
    public static function create(array|CreateRubricDTO $data): self
    {
        self::checkApiClient();
        self::checkCourse();

        if (is_array($data)) {
            $data = new CreateRubricDTO($data);
        }

        $endpoint = self::getResourceEndpoint();
        $response = self::$apiClient->post($endpoint, $data->toApiArray());
        $responseData = json_decode($response->getBody(), true);

        // Handle non-standard response format
        if (isset($responseData['rubric'])) {
            $rubric = new self($responseData['rubric']);
            if (isset($responseData['rubric_association'])) {
                $rubric->association = new RubricAssociation($responseData['rubric_association']);
            }
            return $rubric;
        }

        // Fallback to standard response
        return new self($responseData);
    }

    /**
     * Find a rubric by ID
     *
     * @param int $id The rubric ID
     * @param array<string, mixed> $params Query parameters
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkApiClient();
        self::checkCourse();

        $endpoint = sprintf('%s/%d', self::getResourceEndpoint(), $id);

        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode($response->getBody(), true);

        return new self($data);
    }

    /**
     * Update a rubric
     *
     * @param int $id The rubric ID
     * @param array<string, mixed>|UpdateRubricDTO $data The update data
     * @return self
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateRubricDTO $data): self
    {
        self::checkApiClient();
        self::checkCourse();

        if (is_array($data)) {
            $data = new UpdateRubricDTO($data);
        }

        $endpoint = sprintf('%s/%d', self::getResourceEndpoint(), $id);
        $response = self::$apiClient->put($endpoint, $data->toApiArray());
        $responseData = json_decode($response->getBody(), true);

        // Handle non-standard response format
        if (isset($responseData['rubric'])) {
            $rubric = new self($responseData['rubric']);
            if (isset($responseData['rubric_association'])) {
                $rubric->association = new RubricAssociation($responseData['rubric_association']);
            }
            return $rubric;
        }

        // Fallback to standard response
        return new self($responseData);
    }

    /**
     * Delete a rubric
     *
     * @return bool
     * @throws CanvasApiException
     */
    public function delete(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot delete rubric without ID");
        }

        self::checkApiClient();
        self::checkCourse();

        $endpoint = sprintf('courses/%d/rubrics/%d', self::$course->id, $this->id);
        self::$apiClient->delete($endpoint);

        return true;
    }

    /**
     * Save the rubric (create or update)
     *
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        if ($this->id) {
            // Update existing
            $dto = new UpdateRubricDTO();
            $dto->title = $this->title;
            $dto->freeFormCriterionComments = $this->freeFormCriterionComments;
            $dto->hideScoreTotal = $this->hideScoreTotal;

            if ($this->data !== null) {
                $dto->criteria = array_map(function (RubricCriterion $criterion) {
                    return $criterion->toArray();
                }, $this->data);
            }

            return self::update($this->id, $dto);
        } else {
            // Create new
            $dto = new CreateRubricDTO();
            $dto->title = $this->title;
            $dto->freeFormCriterionComments = $this->freeFormCriterionComments;

            if ($this->data !== null) {
                $dto->criteria = array_map(function (RubricCriterion $criterion) {
                    return $criterion->toArray();
                }, $this->data);
            }

            return self::create($dto);
        }
    }

    /**
     * Fetch all rubrics with pagination support
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();
        self::checkCourse();

        $endpoint = self::getResourceEndpoint();
        return self::fetchAllPagesAsModels($endpoint, $params);
    }

    /**
     * Fetch all rubrics as a paginated response
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkApiClient();
        self::checkCourse();

        $endpoint = self::getResourceEndpoint();
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Get the courses and assignments where this rubric is used
     *
     * @return array<string, mixed>
     * @throws CanvasApiException
     */
    public function getUsedLocations(): array
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot get used locations without rubric ID");
        }

        self::checkApiClient();
        self::checkCourse();

        $endpoint = sprintf('courses/%d/rubrics/%d/used_locations', self::$course->id, $this->id);
        $response = self::$apiClient->get($endpoint);

        return json_decode($response->getBody(), true);
    }

    /**
     * Upload a rubric via CSV file
     *
     * @param string $filePath Path to the CSV file
     * @return array<string, mixed> Import status information
     * @throws CanvasApiException
     */
    public static function uploadCsv(string $filePath): array
    {
        self::checkApiClient();
        self::checkCourse();

        if (!file_exists($filePath)) {
            throw new CanvasApiException("CSV file not found: $filePath");
        }

        $endpoint = sprintf('%s/upload', self::getResourceEndpoint());

        $multipart = [
            [
                'name' => 'attachment',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath)
            ]
        ];

        $response = self::$apiClient->post($endpoint, $multipart);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get CSV template for rubric import
     *
     * @return string CSV content
     * @throws CanvasApiException
     */
    public static function getUploadTemplate(): string
    {
        self::checkApiClient();

        $response = self::$apiClient->get('rubrics/upload_template');
        return $response->getBody();
    }

    /**
     * Get the status of a rubric import
     *
     * @param int|null $importId The import ID (optional, returns latest if not provided)
     * @return array<string, mixed>
     * @throws CanvasApiException
     */
    public static function getUploadStatus(?int $importId = null): array
    {
        self::checkApiClient();
        self::checkCourse();

        $endpoint = self::getResourceEndpoint() . '/upload';
        if ($importId !== null) {
            $endpoint .= '/' . $importId;
        }

        $response = self::$apiClient->get($endpoint);
        return json_decode($response->getBody(), true);
    }
}
