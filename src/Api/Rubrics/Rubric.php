<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Rubrics;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Config;
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
 * // Account context (default)
 * $rubrics = Rubric::get();
 * $rubric = Rubric::create([
 *     'title' => 'Account Rubric',
 *     'criteria' => [...]
 * ]);
 *
 * // Course context via Course instance
 * $course = Course::find(123);
 * $rubrics = $course->rubrics();
 *
 * // Direct context access
 * $rubrics = Rubric::fetchByContext('course', 123);
 * $rubric = Rubric::createInContext('course', 123, [
 *     'title' => 'Course Rubric',
 *     'criteria' => [...]
 * ]);
 *
 * // Finding and updating rubrics
 * $rubric = Rubric::find(456); // Searches in account context
 * $rubric = Rubric::findByContext('course', 123, 456); // Course-specific
 *
 * $rubric = Rubric::update(456, [
 *     'title' => 'Updated Rubric'
 * ]);
 *
 * // Using DTOs
 * $dto = new CreateRubricDTO();
 * $dto->title = "Essay Rubric";
 * $dto->criteria = [...];
 * $rubric = Rubric::create($dto);
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
     * Get the resource identifier for API endpoints
     *
     * @return string
     */
    protected static function getResourceIdentifier(): string
    {
        return 'rubrics';
    }

    /**
     * Create a new rubric in the default account context
     *
     * @param array<string, mixed>|CreateRubricDTO $data The rubric data
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function create(array|CreateRubricDTO $data): self
    {
        $accountId = Config::getAccountId();

        return self::createInContext('accounts', $accountId, $data);
    }

    /**
     * Create a new rubric in a specific context
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param array<string, mixed>|CreateRubricDTO $data The rubric data
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function createInContext(
        string $contextType,
        int $contextId,
        array|CreateRubricDTO $data
    ): self {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateRubricDTO($data);
        }

        $endpoint = sprintf('%s/%d/rubrics', $contextType, $contextId);
        $response = self::getApiClient()->post($endpoint, $data->toApiArray());
        $responseData = self::parseJsonResponse($response);

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
     * Find a rubric by ID in the default account context
     *
     * @param int $id The rubric ID
     * @param array<string, mixed> $params Query parameters
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function find(int $id, array $params = []): self
    {
        $accountId = Config::getAccountId();

        return self::findByContext('accounts', $accountId, $id, $params);
    }

    /**
     * Find a rubric by ID in a specific context
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param int $id The rubric ID
     * @param array<string, mixed> $params Query parameters
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function findByContext(
        string $contextType,
        ?int $contextId,
        int $id,
        array $params = []
    ): self {
        self::checkApiClient();

        $endpoint = sprintf('%s/%d/rubrics/%d', $contextType, $contextId, $id);
        $response = self::getApiClient()->get($endpoint, ['query' => $params]);
        $data = self::parseJsonResponse($response);

        return new self($data);
    }

    /**
     * Update a rubric in the default account context
     *
     * @param int $id The rubric ID
     * @param array<string, mixed>|UpdateRubricDTO $data The update data
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function update(int $id, array|UpdateRubricDTO $data): self
    {
        $accountId = Config::getAccountId();

        return self::updateInContext('accounts', $accountId, $id, $data);
    }

    /**
     * Update a rubric in a specific context
     *
     * @param string $contextType Context type (accounts, courses)
     * @param int $contextId Context ID
     * @param int $id The rubric ID
     * @param array<string, mixed>|UpdateRubricDTO $data The update data
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function updateInContext(
        string $contextType,
        int $contextId,
        int $id,
        array|UpdateRubricDTO $data
    ): self {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateRubricDTO($data);
        }

        $endpoint = sprintf('%s/%d/rubrics/%d', $contextType, $contextId, $id);
        $response = self::getApiClient()->put($endpoint, $data->toApiArray());
        $responseData = self::parseJsonResponse($response);

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
     * @throws CanvasApiException
     *
     * @return self
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Cannot delete rubric without ID');
        }

        if (!$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Cannot delete rubric without context information');
        }

        self::checkApiClient();

        $endpoint = sprintf('%ss/%d/rubrics/%d', $this->contextType, $this->contextId, $this->id);
        self::getApiClient()->delete($endpoint);

        return $this;
    }

    /**
     * Save the rubric (create or update)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function save(): self
    {
        if ($this->id) {
            // Update existing
            if (!$this->contextType || !$this->contextId) {
                throw new CanvasApiException('Context information required for update');
            }

            $dto = new UpdateRubricDTO();
            $dto->title = $this->title;
            $dto->freeFormCriterionComments = $this->freeFormCriterionComments;
            $dto->hideScoreTotal = $this->hideScoreTotal;

            if ($this->data !== null) {
                $dto->criteria = array_map(function (RubricCriterion $criterion) {
                    return $criterion->toArray();
                }, $this->data);
            }

            return self::updateInContext($this->contextType, $this->contextId, $this->id, $dto);
        } else {
            // Create new - default to account context
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
     * Get the API endpoint for this resource
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        $accountId = Config::getAccountId();

        return sprintf('accounts/%d/rubrics', $accountId);
    }

    /**
     * List rubrics for a specific context
     *
     * @param string $contextType 'accounts' or 'courses'
     * @param int $contextId Account or Course ID
     * @param array<string, mixed> $params Query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<self>
     */
    public static function fetchByContext(string $contextType, int $contextId, array $params = []): array
    {
        $endpoint = sprintf('%s/%d/rubrics', $contextType, $contextId);
        $paginatedResponse = self::getPaginatedResponse($endpoint, $params);

        $allData = [];
        do {
            $data = $paginatedResponse->getJsonData();
            foreach ($data as $item) {
                $allData[] = $item;
            }
            $paginatedResponse = $paginatedResponse->getNext();
        } while ($paginatedResponse !== null);

        return array_map(fn ($data) => new self($data), $allData);
    }

    /**
     * Get paginated rubrics for a specific context
     *
     * @param string $contextType 'accounts' or 'courses'
     * @param int $contextId Account or Course ID
     * @param array<string, mixed> $params Query parameters
     *
     * @throws CanvasApiException
     *
     * @return PaginatedResponse
     */
    public static function fetchByContextPaginated(
        string $contextType,
        int $contextId,
        array $params = []
    ): PaginatedResponse {
        return self::getPaginatedResponse(sprintf('%s/%d/rubrics', $contextType, $contextId), $params);
    }

    /**
     * Get the courses and assignments where this rubric is used
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed>
     */
    public function getUsedLocations(): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Cannot get used locations without rubric ID');
        }

        if (!$this->contextType || !$this->contextId) {
            throw new CanvasApiException('Context information required for getting used locations');
        }

        self::checkApiClient();

        $endpoint = sprintf('%ss/%d/rubrics/%d/used_locations', $this->contextType, $this->contextId, $this->id);
        $response = self::getApiClient()->get($endpoint);

        return self::parseJsonResponse($response);
    }

    /**
     * Upload a rubric via CSV file to account context
     *
     * @param string $filePath Path to the CSV file
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Import status information
     */
    public static function uploadCsv(string $filePath): array
    {
        $accountId = Config::getAccountId();

        return self::uploadCsvToContext('accounts', $accountId, $filePath);
    }

    /**
     * Upload a rubric via CSV file to a specific context
     *
     * @param string $contextType 'accounts' or 'courses'
     * @param int $contextId Context ID
     * @param string $filePath Path to the CSV file
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed> Import status information
     */
    public static function uploadCsvToContext(string $contextType, int $contextId, string $filePath): array
    {
        self::checkApiClient();

        if (!file_exists($filePath)) {
            throw new CanvasApiException("CSV file not found: $filePath");
        }

        $endpoint = sprintf('%s/%d/rubrics/upload', $contextType, $contextId);

        $multipart = [
            [
                'name' => 'attachment',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ],
        ];

        $response = self::getApiClient()->post($endpoint, $multipart);

        return self::parseJsonResponse($response);
    }

    /**
     * Get CSV template for rubric import
     *
     * @throws CanvasApiException
     *
     * @return string CSV content
     */
    public static function getUploadTemplate(): string
    {
        self::checkApiClient();

        $response = self::getApiClient()->get('rubrics/upload_template');

        return $response->getBody()->getContents();
    }

    /**
     * Get the status of a rubric import in account context
     *
     * @param int|null $importId The import ID (optional, returns latest if not provided)
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed>
     */
    public static function getUploadStatus(?int $importId = null): array
    {
        $accountId = Config::getAccountId();

        return self::getUploadStatusInContext('accounts', $accountId, $importId);
    }

    /**
     * Get the status of a rubric import in a specific context
     *
     * @param string $contextType 'accounts' or 'courses'
     * @param int $contextId Context ID
     * @param int|null $importId The import ID (optional, returns latest if not provided)
     *
     * @throws CanvasApiException
     *
     * @return array<string, mixed>
     */
    public static function getUploadStatusInContext(
        string $contextType,
        int $contextId,
        ?int $importId = null
    ): array {
        self::checkApiClient();

        $endpoint = sprintf('%s/%d/rubrics/upload', $contextType, $contextId);
        if ($importId !== null) {
            $endpoint .= '/' . $importId;
        }

        $response = self::getApiClient()->get($endpoint);

        return self::parseJsonResponse($response);
    }
}
