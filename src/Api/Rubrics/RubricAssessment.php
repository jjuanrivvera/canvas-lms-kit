<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Rubrics;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Dto\Rubrics\CreateRubricAssessmentDTO;
use CanvasLMS\Dto\Rubrics\UpdateRubricAssessmentDTO;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * RubricAssessment Class
 *
 * Represents a rubric assessment in Canvas LMS. Rubric assessments are the actual
 * evaluations made using a rubric on a submission. They contain the scores and
 * comments for each criterion in the rubric.
 *
 * Usage:
 *
 * ```php
 * // Set course context first
 * $course = Course::find(123);
 * RubricAssessment::setCourse($course);
 *
 * // Creating a rubric assessment (using array)
 * $assessment = RubricAssessment::create([
 *     'userId' => 123,
 *     'assessmentType' => 'grading',
 *     'criterionData' => [...]
 * ], 456); // rubric_association_id
 *
 * // Creating using DTO (still supported)
 * $dto = new CreateRubricAssessmentDTO();
 * $dto->userId = 123;
 * $dto->assessmentType = 'grading';
 * $dto->criterionData = [...];
 * $assessment = RubricAssessment::create($dto, 456);
 *
 * // Updating an assessment (using array)
 * $assessment = RubricAssessment::update(111, [
 *     'criterionData' => [...]
 * ], 456); // rubric_association_id
 *
 * // Updating using DTO (still supported)
 * $updateDto = new UpdateRubricAssessmentDTO();
 * $updateDto->criterionData = [...];
 * $assessment = RubricAssessment::update(111, $updateDto, 456);
 * ```
 *
 * @package CanvasLMS\Api\Rubrics
 */
class RubricAssessment extends AbstractBaseApi
{
    /**
     * The ID of the rubric assessment
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
     * The ID of the rubric association
     *
     * @var int|null
     */
    public ?int $rubricAssociationId = null;

    /**
     * The overall score for this assessment
     *
     * @var float|null
     */
    public ?float $score = null;

    /**
     * User ID of the person being assessed
     *
     * @var int|null
     */
    public ?int $userId = null;

    /**
     * The type of the artifact being assessed
     *
     * @var string|null
     */
    public ?string $artifactType = null;

    /**
     * The ID of the artifact being assessed
     *
     * @var int|null
     */
    public ?int $artifactId = null;

    /**
     * The current number of attempts made on the object of the assessment
     *
     * @var int|null
     */
    public ?int $artifactAttempt = null;

    /**
     * The type of assessment
     * Values: 'grading', 'peer_review', or 'provisional_grade'
     *
     * @var string|null
     */
    public ?string $assessmentType = null;

    /**
     * User ID of the person who made the assessment
     *
     * @var int|null
     */
    public ?int $assessorId = null;

    /**
     * Full assessment data (when style='full' is requested)
     *
     * @var array<string, mixed>|null
     */
    public ?array $data = null;

    /**
     * Overall comments for the assessment
     *
     * @var string|null
     */
    public ?string $comments = null;

    /**
     * Additional assessment details
     *
     * @var array<int, mixed>|null
     */
    public ?array $ratings = null;

    /**
     * Name of the assessor
     *
     * @var string|null
     */
    public ?string $assessorName = null;

    /**
     * Related group submissions and assessments
     *
     * @var array<int, mixed>|null
     */
    public ?array $relatedGroupSubmissionsAndAssessments = null;

    /**
     * The artifact being assessed
     *
     * @var array<string, mixed>|null
     */
    public ?array $artifact = null;

    /**
     * Criterion data for assessment creation/updates
     *
     * @var array<string, mixed>|null
     */
    public ?array $criterionData = null;

    /**
     * Provisional grade ID for moderated grading
     *
     * @var int|null
     */
    public ?int $provisionalGradeId = null;

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
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Handle criterion_data conversion
        if (isset($data['criterion_data'])) {
            $this->criterionData = $data['criterion_data'];
        }
    }

    /**
     * Set the course context for rubric assessment operations
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
    protected static function getContextCourseId(): ?int
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
        return 'rubric_assessments';
    }

    /**
     * Get the resource endpoint
     *
     * @param int $rubricAssociationId The rubric association ID
     *
     * @throws CanvasApiException
     *
     * @return string
     */
    protected static function getResourceEndpoint(int $rubricAssociationId): string
    {
        if (self::$course === null) {
            throw new CanvasApiException(
                'Course context must be set for RubricAssessment operations'
            );
        }

        return sprintf(
            'courses/%d/rubric_associations/%d/rubric_assessments',
            self::getContextCourseId(),
            $rubricAssociationId
        );
    }

    /**
     * Create a new rubric assessment
     *
     * @param array<string, mixed>|CreateRubricAssessmentDTO $data The assessment data
     * @param int $rubricAssociationId The rubric association ID
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function create(array|CreateRubricAssessmentDTO $data, int $rubricAssociationId): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateRubricAssessmentDTO($data);
        }

        $endpoint = self::getResourceEndpoint($rubricAssociationId);
        $response = self::getApiClient()->post($endpoint, $data->toApiArray());
        $responseData = self::parseJsonResponse($response);

        return new self($responseData);
    }

    /**
     * Update a rubric assessment
     *
     * @param int $id The assessment ID
     * @param array<string, mixed>|UpdateRubricAssessmentDTO $data The update data
     * @param int $rubricAssociationId The rubric association ID
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function update(
        int $id,
        array|UpdateRubricAssessmentDTO $data,
        int $rubricAssociationId
    ): self {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateRubricAssessmentDTO($data);
        }

        $baseEndpoint = self::getResourceEndpoint($rubricAssociationId);
        $endpoint = sprintf('%s/%d', $baseEndpoint, $id);
        $response = self::getApiClient()->put($endpoint, $data->toApiArray());
        $responseData = self::parseJsonResponse($response);

        return new self($responseData);
    }

    /**
     * Delete a rubric assessment
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Cannot delete rubric assessment without ID');
        }

        if (!$this->rubricAssociationId) {
            throw new CanvasApiException('Cannot delete rubric assessment without association ID');
        }

        self::checkApiClient();

        $baseEndpoint = self::getResourceEndpoint($this->rubricAssociationId);
        $endpoint = sprintf('%s/%d', $baseEndpoint, $this->id);

        $response = self::getApiClient()->delete($endpoint);
        self::parseJsonResponse($response);

        return $this;
    }

    /**
     * Save the rubric assessment (create or update)
     *
     * @param int|null $rubricAssociationId Required for create operation
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public function save(?int $rubricAssociationId = null): self
    {
        if ($this->id) {
            // Update existing
            if (!$this->rubricAssociationId && !$rubricAssociationId) {
                throw new CanvasApiException('Rubric association ID required for update');
            }

            $associationId = $rubricAssociationId ?? $this->rubricAssociationId;

            // Ensure associationId is not null before calling update
            if ($associationId === null) {
                throw new CanvasApiException('Rubric association ID cannot be null');
            }

            $dto = new UpdateRubricAssessmentDTO();
            $dto->userId = $this->userId ?? $this->assessorId;
            $dto->assessmentType = $this->assessmentType;
            $dto->provisional = false;
            $dto->final = false;
            $dto->gradedAnonymously = false;

            if ($this->criterionData !== null) {
                $dto->criterionData = $this->criterionData;
            } elseif ($this->data !== null) {
                $dto->criterionData = $this->data;
            }

            return self::update($this->id, $dto, $associationId);
        } else {
            // Create new
            if (!$this->rubricAssociationId && !$rubricAssociationId) {
                throw new CanvasApiException('Rubric association ID required for create');
            }

            $associationId = $rubricAssociationId ?? $this->rubricAssociationId;

            // Ensure associationId is not null before calling create
            if ($associationId === null) {
                throw new CanvasApiException('Rubric association ID cannot be null');
            }

            $dto = new CreateRubricAssessmentDTO();
            $dto->userId = $this->userId ?? $this->assessorId;
            $dto->assessmentType = $this->assessmentType;
            $dto->provisional = false;
            $dto->final = false;
            $dto->gradedAnonymously = false;

            if ($this->criterionData !== null) {
                $dto->criterionData = $this->criterionData;
            } elseif ($this->data !== null) {
                $dto->criterionData = $this->data;
            }

            return self::create($dto, $associationId);
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
     * Get the rubric association
     *
     * @throws CanvasApiException
     *
     * @return RubricAssociation
     */
    public function rubricAssociation(): RubricAssociation
    {
        if (!$this->rubricAssociationId) {
            throw new CanvasApiException('No rubric association ID');
        }

        // RubricAssociation doesn't have a find method in the API
        // We would need to implement a different approach or add this method
        throw new CanvasApiException('Fetching individual rubric associations is not supported by the Canvas API');
    }

    /**
     * Find a rubric assessment by ID
     *
     * Note: Canvas API does not support fetching individual rubric assessments
     *
     * @param int $id The assessment ID
     *
     * @throws CanvasApiException
     *
     * @return self
     */
    public static function find(int $id, array $params = []): self
    {
        throw new CanvasApiException(
            'Finding individual rubric assessments is not supported by the Canvas API. ' .
            'Rubric assessments must be accessed through rubric associations.'
        );
    }

    /**
     * Fetch all rubric assessments
     *
     * Note: Canvas API does not support listing all rubric assessments directly
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
            'Fetching all rubric assessments is not supported by the Canvas API. ' .
            'Rubric assessments must be accessed through specific rubric associations.'
        );
    }

    /**
     * Get the API endpoint for this resource
     * Note: RubricAssessment is a nested resource under RubricAssociation
     *
     * @throws CanvasApiException
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        throw new CanvasApiException(
            'RubricAssessment does not support direct endpoint access. Use context-specific methods.'
        );
    }
}
