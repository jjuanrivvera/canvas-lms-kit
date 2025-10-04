<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Rubrics;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Rubrics\CreateRubricAssociationDTO;
use CanvasLMS\Dto\Rubrics\UpdateRubricAssociationDTO;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * RubricAssociation Class
 *
 * Represents the association between a rubric and an assessable item in Canvas LMS.
 * RubricAssociations link rubrics to assignments, discussions, or other contexts
 * and define how the rubric is used for grading.
 *
 * Usage:
 *
 * ```php
 * // Set course context first
 * $course = Course::find(123);
 * RubricAssociation::setCourse($course);
 *
 * // Creating a rubric association (using array)
 * $association = RubricAssociation::create([
 *     'rubricId' => 123,
 *     'associationId' => 456,
 *     'associationType' => 'Assignment',
 *     'useForGrading' => true
 * ]);
 *
 * // Creating using DTO (still supported)
 * $dto = new CreateRubricAssociationDTO();
 * $dto->rubricId = 123;
 * $dto->associationId = 456;
 * $dto->associationType = 'Assignment';
 * $dto->useForGrading = true;
 * $association = RubricAssociation::create($dto);
 *
 * // Updating an association (using array)
 * $association = RubricAssociation::update(111, [
 *     'useForGrading' => false
 * ]);
 *
 * // Updating using DTO (still supported)
 * $updateDto = new UpdateRubricAssociationDTO();
 * $updateDto->useForGrading = false;
 * $association = RubricAssociation::update(111, $updateDto);
 * ```
 *
 * @package CanvasLMS\Api\Rubrics
 */
class RubricAssociation extends AbstractBaseApi
{
    /**
     * The ID of the association
     *
     * @var int|null
     */
    public ?int $id = null;

    /**
     * The ID of the rubric
     *
     * @var int|null
     */
    public ?int $rubricId = null;

    /**
     * The ID of the object this association links to
     *
     * @var int|null
     */
    public ?int $associationId = null;

    /**
     * The type of object this association links to
     *
     * @var string|null
     */
    public ?string $associationType = null;

    /**
     * Whether or not the associated rubric is used for grade calculation
     *
     * @var bool|null
     */
    public ?bool $useForGrading = null;

    /**
     * Summary data for the association
     *
     * @var array<string, mixed>|null
     */
    public ?array $summaryData = null;

    /**
     * Purpose of the association (grading or bookmark)
     *
     * @var string|null
     */
    public ?string $purpose = null;

    /**
     * Whether or not the score total is displayed within the rubric
     *
     * @var bool|null
     */
    public ?bool $hideScoreTotal = null;

    /**
     * Whether or not points are hidden
     *
     * @var bool|null
     */
    public ?bool $hidePoints = null;

    /**
     * Whether or not outcome results are hidden
     *
     * @var bool|null
     */
    public ?bool $hideOutcomeResults = null;

    /**
     * The title of the object this rubric is associated with
     *
     * @var string|null
     */
    public ?string $title = null;

    /**
     * Whether or not the rubric is bookmarked
     *
     * @var bool|null
     */
    public ?bool $bookmarked = null;

    /**
     * The context ID owning the association
     *
     * @var int|null
     */
    public ?int $contextId = null;

    /**
     * The context type owning the association
     *
     * @var string|null
     */
    public ?string $contextType = null;

    /**
     * Created timestamp
     *
     * @var \DateTime|null
     */
    public ?\DateTime $createdAt = null;

    /**
     * Updated timestamp
     *
     * @var \DateTime|null
     */
    public ?\DateTime $updatedAt = null;

    /**
     * Course context for operations
     *
     * @var Course|null
     */
    protected static ?Course $course = null;

    /**
     * Set the course context for rubric association operations
     *
     * @param Course|null $course
     *
     * @return void
     */
    public static function setCourse(?Course $course): void
    {
        self::$course = $course;
    }

    /**
     * Get the Course instance, ensuring it is set
     *
     * @throws CanvasApiException if course is not set
     *
     * @return Course
     */
    protected static function getCourse(): Course
    {
        if (self::$course === null) {
            throw new CanvasApiException('Course context not set. Call ' . static::class . '::setCourse() first.');
        }

        return self::$course;
    }

    /**
     * Get the Course ID from context, ensuring course is set
     *
     * @throws CanvasApiException if course is not set
     *
     * @return int
     */
    protected static function getContextCourseId(): int
    {
        return self::getCourse()->id;
    }

    /**
     * Get the resource identifier for API endpoints
     *
     * @return string
     */
    protected static function getResourceIdentifier(): string
    {
        return 'rubric_associations';
    }

    /**
     * Get the resource endpoint
     *
     * @throws CanvasApiException
     *
     * @return string
     */
    protected static function getResourceEndpoint(): string
    {
        if (self::$course === null) {
            throw new CanvasApiException('Course context must be set for RubricAssociation operations');
        }

        return sprintf('courses/%d/rubric_associations', self::getContextCourseId());
    }

    /**
     * Create a new rubric association
     *
     * @param array<string, mixed>|CreateRubricAssociationDTO $data The association data
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function create(array|CreateRubricAssociationDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateRubricAssociationDTO($data);
        }

        $endpoint = self::getResourceEndpoint();
        $response = self::getApiClient()->post($endpoint, $data->toApiArray());
        $responseData = self::parseJsonResponse($response);

        return new self($responseData);
    }

    /**
     * Update a rubric association
     *
     * @param int $id The association ID
     * @param array<string, mixed>|UpdateRubricAssociationDTO $data The update data
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function update(int $id, array|UpdateRubricAssociationDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateRubricAssociationDTO($data);
        }

        $endpoint = sprintf('%s/%d', self::getResourceEndpoint(), $id);
        $response = self::getApiClient()->put($endpoint, $data->toApiArray());
        $responseData = self::parseJsonResponse($response);

        return new self($responseData);
    }

    /**
     * Delete a rubric association
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Cannot delete rubric association without ID');
        }

        if (self::$course === null) {
            throw new CanvasApiException('Course context must be set for delete operation');
        }

        $endpoint = sprintf('%s/%d', self::getResourceEndpoint(), $this->id);
        $response = self::getApiClient()->delete($endpoint);

        self::parseJsonResponse($response);

        return $this;
    }

    /**
     * Save the rubric association (create or update)
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function save(): self
    {
        // Validate required fields for new associations
        if (!$this->id && !$this->rubricId) {
            throw new CanvasApiException('Rubric ID is required for creating a new association');
        }

        if ($this->id) {
            // Update existing
            $dto = new UpdateRubricAssociationDTO();
            $dto->rubricId = $this->rubricId;
            $dto->associationId = $this->associationId;
            $dto->associationType = $this->associationType;
            $dto->title = $this->title;
            $dto->useForGrading = $this->useForGrading;
            $dto->hideScoreTotal = $this->hideScoreTotal;
            $dto->purpose = $this->purpose;
            $dto->bookmarked = $this->bookmarked;

            return self::update($this->id, $dto);
        } else {
            // Create new
            $dto = new CreateRubricAssociationDTO();
            $dto->rubricId = $this->rubricId;
            $dto->associationId = $this->associationId;
            $dto->associationType = $this->associationType;
            $dto->title = $this->title;
            $dto->useForGrading = $this->useForGrading;
            $dto->hideScoreTotal = $this->hideScoreTotal;
            $dto->purpose = $this->purpose;
            $dto->bookmarked = $this->bookmarked;

            return self::create($dto);
        }
    }

    /**
     * Get the associated rubric
     *
     * @throws CanvasApiException
     *
     * @return Rubric
     */
    public function rubric(): Rubric
    {
        if (!$this->rubricId) {
            throw new CanvasApiException('No rubric ID associated');
        }

        if (!self::$course) {
            throw new CanvasApiException('Course context must be set');
        }

        // Use the new context-based find method
        return Rubric::findByContext('courses', self::getContextCourseId(), $this->rubricId);
    }

    /**
     * Find a rubric association by ID
     *
     * Note: Canvas API does not support fetching individual rubric associations
     *
     * @param int $id The association ID
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function find(int $id, array $params = []): self
    {
        throw new CanvasApiException(
            'Finding individual rubric associations is not supported by the Canvas API. ' .
            'Rubric associations must be fetched through rubrics or created/updated directly.'
        );
    }

    /**
     * Fetch all rubric associations
     *
     * Note: Canvas API does not support listing all rubric associations directly
     *
     * @param array<string, mixed> $params Query parameters
     *
     * @throws CanvasApiException
     *
     * @return array<int, self>
     */
    public static function get(array $params = []): array
    {
        throw new CanvasApiException(
            'Fetching all rubric associations is not supported by the Canvas API. ' .
            'Rubric associations must be accessed through rubrics.'
        );
    }

    /**
     * Get the API endpoint for this resource
     * Note: RubricAssociation is a nested resource under Rubric
     *
     * @throws CanvasApiException
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        throw new CanvasApiException(
            'RubricAssociation does not support direct endpoint access. Use context-specific methods.'
        );
    }
}
