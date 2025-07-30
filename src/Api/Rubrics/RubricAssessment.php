<?php

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
 * // Creating a rubric assessment
 * $dto = new CreateRubricAssessmentDTO();
 * $dto->userId = 123;
 * $dto->assessmentType = 'grading';
 * $dto->criterionData = [...];
 * $assessment = RubricAssessment::create($dto, 789, 456); // Course ID, Association ID
 *
 * // Updating an assessment
 * $updateDto = new UpdateRubricAssessmentDTO();
 * $updateDto->criterionData = [...];
 * $assessment = RubricAssessment::update(111, $updateDto, 789, 456);
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
     * @var string|null
     */
    public ?string $createdAt = null;

    /**
     * Updated timestamp
     *
     * @var string|null
     */
    public ?string $updatedAt = null;

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
     * @return void
     */
    public static function setCourse(?Course $course): void
    {
        self::$course = $course;
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
     * Get the resource endpoint from context
     *
     * @param array<string, mixed> $context Context parameters
     * @return string
     * @throws CanvasApiException
     */
    protected static function getResourceEndpointFromContext(array $context): string
    {
        if (isset($context['rubric_association_id'])) {
            return self::getResourceEndpoint(null, $context['rubric_association_id']);
        }

        if (isset($context['assignment_id']) && isset($context['provisional_grade_id'])) {
            $courseId = self::$course ? self::$course->id : null;
            if ($courseId === null) {
                throw new CanvasApiException(
                    "Course context must be set for moderated grading operations"
                );
            }
            return sprintf(
                'courses/%d/assignments/%d/moderated_grading/provisional_grades/%d/rubric_assessments',
                $courseId,
                $context['assignment_id'],
                $context['provisional_grade_id']
            );
        }

        throw new CanvasApiException(
            "Either rubric_association_id or both assignment_id and provisional_grade_id must be provided"
        );
    }

    /**
     * Get the resource endpoint
     *
     * @param int|null $courseId Optional course ID override
     * @param int $rubricAssociationId The rubric association ID
     * @return string
     * @throws CanvasApiException
     */
    protected static function getResourceEndpoint(?int $courseId, int $rubricAssociationId): string
    {
        if ($courseId === null) {
            if (self::$course === null) {
                throw new CanvasApiException(
                    "Course context must be set for RubricAssessment operations"
                );
            }
            $courseId = self::$course->id;
        }

        return sprintf(
            'courses/%d/rubric_associations/%d/rubric_assessments',
            $courseId,
            $rubricAssociationId
        );
    }

    /**
     * Create a new rubric assessment
     *
     * @param CreateRubricAssessmentDTO $dto The assessment data
     * @param array<string, mixed> $context Context parameters (rubric_association_id
     *                                     or assignment_id + provisional_grade_id)
     * @return self
     * @throws CanvasApiException
     */
    public static function create(CreateRubricAssessmentDTO $dto, array $context = []): self
    {
        self::checkApiClient();

        $endpoint = self::getResourceEndpointFromContext($context);
        $response = self::$apiClient->post($endpoint, $dto->toApiArray());
        $data = json_decode($response->getBody(), true);

        return new self($data);
    }

    /**
     * Update a rubric assessment
     *
     * @param int $id The assessment ID
     * @param UpdateRubricAssessmentDTO $dto The update data
     * @param array<string, mixed> $context Context parameters (rubric_association_id
     *                                     or assignment_id + provisional_grade_id)
     * @return self
     * @throws CanvasApiException
     */
    public static function update(
        int $id,
        UpdateRubricAssessmentDTO $dto,
        array $context = []
    ): self {
        self::checkApiClient();

        $baseEndpoint = self::getResourceEndpointFromContext($context);
        $endpoint = sprintf('%s/%d', $baseEndpoint, $id);
        $response = self::$apiClient->put($endpoint, $dto->toApiArray());
        $data = json_decode($response->getBody(), true);

        return new self($data);
    }

    /**
     * Delete a rubric assessment
     *
     * @return bool
     * @throws CanvasApiException
     */
    public function delete(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot delete rubric assessment without ID");
        }

        // Check for moderated grading context first
        if ($this->artifactType === 'ModeratedGrading' && $this->artifactId && $this->provisionalGradeId) {
            if (self::$course === null) {
                throw new CanvasApiException("Course context must be set for delete operation");
            }

            $endpoint = sprintf(
                'courses/%d/assignments/%d/moderated_grading/provisional_grades/%d/rubric_assessments/%d',
                self::$course->id,
                $this->artifactId,
                $this->provisionalGradeId,
                $this->id
            );
        } else {
            // Regular rubric association context
            if (!$this->rubricAssociationId) {
                throw new CanvasApiException("Cannot delete rubric assessment without association ID");
            }

            if (self::$course === null) {
                throw new CanvasApiException("Course context must be set for delete operation");
            }

            $baseEndpoint = self::getResourceEndpoint(null, $this->rubricAssociationId);
            $endpoint = sprintf('%s/%d', $baseEndpoint, $this->id);
        }

        $response = self::$apiClient->delete($endpoint);
        json_decode($response->getBody(), true);
        return true;
    }

    /**
     * Save the rubric assessment (create or update)
     *
     * @param int|null $courseId Optional course ID for create operation
     * @param int|null $rubricAssociationId Required for create operation
     * @return self
     * @throws CanvasApiException
     */
    public function save(?int $courseId = null, ?int $rubricAssociationId = null): self
    {
        if ($this->id) {
            // Update existing
            if (!$this->rubricAssociationId && !$rubricAssociationId) {
                throw new CanvasApiException("Rubric association ID required for update");
            }

            $associationId = $rubricAssociationId ?? $this->rubricAssociationId;

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

            $context = ['rubric_association_id' => $associationId];
            return self::update($this->id, $dto, $context);
        } else {
            // Create new
            if (!$this->rubricAssociationId && !$rubricAssociationId) {
                throw new CanvasApiException("Rubric association ID required for create");
            }

            $associationId = $rubricAssociationId ?? $this->rubricAssociationId;

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

            $context = ['rubric_association_id' => $associationId];
            return self::create($dto, $context);
        }
    }

    /**
     * Get the associated rubric
     *
     * @return Rubric
     * @throws CanvasApiException
     */
    public function rubric(): Rubric
    {
        if (!$this->rubricId) {
            throw new CanvasApiException("No rubric ID associated");
        }

        if (!self::$course) {
            throw new CanvasApiException("Course context must be set");
        }

        return Rubric::find($this->rubricId, ['course_id' => self::$course->id]);
    }

    /**
     * Get the rubric association
     *
     * @return RubricAssociation
     * @throws CanvasApiException
     */
    public function rubricAssociation(): RubricAssociation
    {
        if (!$this->rubricAssociationId) {
            throw new CanvasApiException("No rubric association ID");
        }

        // RubricAssociation doesn't have a find method in the API
        // We would need to implement a different approach or add this method
        throw new CanvasApiException("Fetching individual rubric associations is not supported by the Canvas API");
    }

    /**
     * Find a rubric assessment by ID
     *
     * Note: Canvas API does not support fetching individual rubric assessments
     *
     * @param int $id The assessment ID
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        throw new CanvasApiException(
            "Finding individual rubric assessments is not supported by the Canvas API. " .
            "Rubric assessments must be accessed through rubric associations."
        );
    }

    /**
     * Fetch all rubric assessments
     *
     * Note: Canvas API does not support listing all rubric assessments directly
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        throw new CanvasApiException(
            "Fetching all rubric assessments is not supported by the Canvas API. " .
            "Rubric assessments must be accessed through specific rubric associations."
        );
    }
}
