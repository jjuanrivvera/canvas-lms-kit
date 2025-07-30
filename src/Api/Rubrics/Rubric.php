<?php

namespace CanvasLMS\Api\Rubrics;

use CanvasLMS\Config;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Accounts\Account;
use CanvasLMS\Dto\Rubrics\CreateRubricDTO;
use CanvasLMS\Dto\Rubrics\UpdateRubricDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Objects\RubricCriterion;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;

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
 * // Creating a rubric in a course
 * $dto = new CreateRubricDTO();
 * $dto->title = "Essay Rubric";
 * $dto->criteria = [...];
 * $rubric = Rubric::create($dto, ['course_id' => 123]);
 *
 * // Finding a rubric
 * $rubric = Rubric::find(456, ['course_id' => 123]);
 *
 * // Listing rubrics in an account
 * $rubrics = Rubric::fetchAll(['account_id' => 1]);
 *
 * // Updating a rubric
 * $updateDto = new UpdateRubricDTO();
 * $updateDto->title = "Updated Essay Rubric";
 * $rubric = Rubric::update(456, $updateDto, ['course_id' => 123]);
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
     * Get the resource endpoint based on context
     *
     * @param array<string, mixed> $context
     * @return string
     * @throws CanvasApiException
     */
    protected static function getContextEndpoint(array $context): string
    {
        if (isset($context['course_id'])) {
            return sprintf('courses/%d/rubrics', $context['course_id']);
        }

        if (isset($context['account_id'])) {
            return sprintf('accounts/%d/rubrics', $context['account_id']);
        }

        // Try to use default from Config
        $accountId = Config::getAccountId();
        if ($accountId && $accountId > 0) {
            return sprintf('accounts/%d/rubrics', $accountId);
        }

        throw new CanvasApiException("Either course_id or account_id must be provided for rubric operations");
    }

    /**
     * Create a new rubric
     *
     * @param CreateRubricDTO $dto The rubric data
     * @param array<string, mixed> $context Context parameters (course_id or account_id)
     * @return self
     * @throws CanvasApiException
     */
    public static function create(CreateRubricDTO $dto, array $context = []): self
    {
        self::checkApiClient();

        $endpoint = self::getContextEndpoint($context);
        $response = self::$apiClient->post($endpoint, $dto->toApiArray());
        $data = json_decode($response->getBody(), true);

        // Handle non-standard response format
        if (isset($data['rubric'])) {
            $rubric = new self($data['rubric']);
            if (isset($data['rubric_association'])) {
                $rubric->association = new RubricAssociation($data['rubric_association']);
            }
            return $rubric;
        }

        // Fallback to standard response
        return new self($data);
    }

    /**
     * Find a rubric by ID
     *
     * @param int $id The rubric ID
     * @param array<string, mixed> $params Query parameters including context (course_id or account_id)
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkApiClient();

        $context = array_intersect_key($params, array_flip(['course_id', 'account_id']));
        $queryParams = array_diff_key($params, $context);

        $endpoint = sprintf('%s/%d', self::getContextEndpoint($context), $id);

        $response = self::$apiClient->get($endpoint, ['query' => $queryParams]);
        $data = json_decode($response->getBody(), true);

        return new self($data);
    }

    /**
     * Update a rubric
     *
     * @param int $id The rubric ID
     * @param UpdateRubricDTO $dto The update data
     * @param array<string, mixed> $context Context parameters (course_id or account_id)
     * @return self
     * @throws CanvasApiException
     */
    public static function update(int $id, UpdateRubricDTO $dto, array $context = []): self
    {
        self::checkApiClient();

        $endpoint = sprintf('%s/%d', self::getContextEndpoint($context), $id);
        $response = self::$apiClient->put($endpoint, $dto->toApiArray());
        $data = json_decode($response->getBody(), true);

        // Handle non-standard response format
        if (isset($data['rubric'])) {
            $rubric = new self($data['rubric']);
            if (isset($data['rubric_association'])) {
                $rubric->association = new RubricAssociation($data['rubric_association']);
            }
            return $rubric;
        }

        // Fallback to standard response
        return new self($data);
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

        // Determine context from existing data
        $context = [];
        if ($this->contextType === 'Course' && $this->contextId) {
            $context['course_id'] = $this->contextId;
        } elseif ($this->contextType === 'Account' && $this->contextId) {
            $context['account_id'] = $this->contextId;
        } else {
            throw new CanvasApiException("Cannot determine context for rubric deletion");
        }

        $endpoint = sprintf('%s/%d', self::getContextEndpoint($context), $this->id);
        self::$apiClient->delete($endpoint);

        return true;
    }

    /**
     * Save the rubric (create or update)
     *
     * @param array<string, mixed> $context Context parameters if creating new
     * @return self
     * @throws CanvasApiException
     */
    public function save(array $context = []): self
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

            // Use existing context if not provided
            if (empty($context)) {
                if ($this->contextType === 'Course' && $this->contextId) {
                    $context['course_id'] = $this->contextId;
                } elseif ($this->contextType === 'Account' && $this->contextId) {
                    $context['account_id'] = $this->contextId;
                }
            }

            return self::update($this->id, $dto, $context);
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

            return self::create($dto, $context);
        }
    }

    /**
     * Fetch all rubrics with pagination support
     *
     * @param array<string, mixed> $params Query parameters including context (course_id or account_id)
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();

        $context = array_intersect_key($params, array_flip(['course_id', 'account_id']));
        $queryParams = array_diff_key($params, $context);

        $endpoint = self::getContextEndpoint($context);
        return self::fetchAllPagesAsModels($endpoint, $queryParams);
    }

    /**
     * Fetch all rubrics as a paginated response
     *
     * @param array<string, mixed> $params Query parameters including context
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkApiClient();

        $context = array_intersect_key($params, array_flip(['course_id', 'account_id']));
        $queryParams = array_diff_key($params, $context);

        $endpoint = self::getContextEndpoint($context);
        return self::getPaginatedResponse($endpoint, $queryParams);
    }

    /**
     * Get the courses and assignments where this rubric is used
     *
     * @param array<string, mixed> $context Context parameters (course_id or account_id)
     * @return array<string, mixed>
     * @throws CanvasApiException
     */
    public function getUsedLocations(array $context = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot get used locations without rubric ID");
        }

        // Use existing context if not provided
        if (empty($context)) {
            if ($this->contextType === 'Course' && $this->contextId) {
                $context['course_id'] = $this->contextId;
            } elseif ($this->contextType === 'Account' && $this->contextId) {
                $context['account_id'] = $this->contextId;
            }
        }

        $endpoint = sprintf('%s/%d/used_locations', self::getContextEndpoint($context), $this->id);
        $response = self::$apiClient->get($endpoint);

        return json_decode($response->getBody(), true);
    }

    /**
     * Upload a rubric via CSV file
     *
     * @param string $filePath Path to the CSV file
     * @param array<string, mixed> $context Context parameters (course_id or account_id)
     * @return array<string, mixed> Import status information
     * @throws CanvasApiException
     */
    public static function uploadCsv(string $filePath, array $context = []): array
    {
        self::checkApiClient();

        if (!file_exists($filePath)) {
            throw new CanvasApiException("CSV file not found: $filePath");
        }

        $endpoint = sprintf('%s/upload', self::getContextEndpoint($context));

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
     * @param array<string, mixed> $context Context parameters (course_id or account_id)
     * @return array<string, mixed>
     * @throws CanvasApiException
     */
    public static function getUploadStatus(?int $importId = null, array $context = []): array
    {
        self::checkApiClient();

        $endpoint = self::getContextEndpoint($context) . '/upload';
        if ($importId !== null) {
            $endpoint .= '/' . $importId;
        }

        $response = self::$apiClient->get($endpoint);
        return json_decode($response->getBody(), true);
    }
}
