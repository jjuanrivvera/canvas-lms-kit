<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Rubrics;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating Canvas rubric assessments.
 *
 * @see https://canvas.instructure.com/doc/api/rubrics.html#method.RubricAssessmentsController.update
 */
class UpdateRubricAssessmentDTO extends AbstractBaseDto implements DTOInterface
{
    protected string $apiPropertyName = 'rubric_assessment';

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * The user ID that refers to the person being assessed
     */
    public ?int $userId = null;

    /**
     * Assessment type. There are only three valid types: 'grading', 'peer_review', or 'provisional_grade'
     */
    public ?string $assessmentType = null;

    /**
     * Indicates whether this assessment is provisional, defaults to false
     */
    public ?bool $provisional = null;

    /**
     * Indicates a provisional grade will be marked as final.
     * It only takes effect if the provisional param is passed as true. Defaults to false.
     */
    public ?bool $final = null;

    /**
     * Whether the assessment was done anonymously
     */
    public ?bool $gradedAnonymously = null;

    /**
     * The criterion assessment data
     * Format: ['criterion_id' => ['points' => X, 'comments' => 'Y']]
     *
     * @var array<string, array<string, mixed>>|null
     */
    public ?array $criterionData = null;

    /**
     * Convert the DTO to an array for API requests
     *
     * @return array<int, array<string, mixed>>
     */
    public function toApiArray(): array
    {
        $modifiedProperties = [];

        // Handle simple properties
        if ($this->userId !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_assessment[user_id]',
                'contents' => (string) $this->userId,
            ];
        }

        if ($this->assessmentType !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_assessment[assessment_type]',
                'contents' => $this->assessmentType,
            ];
        }

        if ($this->provisional !== null) {
            $modifiedProperties[] = [
                'name' => 'provisional',
                'contents' => $this->provisional ? 'true' : 'false',
            ];
        }

        if ($this->final !== null) {
            $modifiedProperties[] = [
                'name' => 'final',
                'contents' => $this->final ? 'true' : 'false',
            ];
        }

        if ($this->gradedAnonymously !== null) {
            $modifiedProperties[] = [
                'name' => 'graded_anonymously',
                'contents' => $this->gradedAnonymously ? 'true' : 'false',
            ];
        }

        // Handle criterion data
        if ($this->criterionData !== null) {
            foreach ($this->criterionData as $criterionId => $data) {
                if (isset($data['points'])) {
                    $modifiedProperties[] = [
                        'name' => "rubric_assessment[criterion_$criterionId][points]",
                        'contents' => (string) $data['points'],
                    ];
                }

                if (isset($data['comments'])) {
                    $modifiedProperties[] = [
                        'name' => "rubric_assessment[criterion_$criterionId][comments]",
                        'contents' => $data['comments'],
                    ];
                }

                // Handle rating_id if provided
                if (isset($data['rating_id'])) {
                    $modifiedProperties[] = [
                        'name' => "rubric_assessment[criterion_$criterionId][rating_id]",
                        'contents' => $data['rating_id'],
                    ];
                }

                // Handle save_comment for reusable comments
                if (isset($data['save_comment'])) {
                    $modifiedProperties[] = [
                        'name' => "rubric_assessment[criterion_$criterionId][save_comment]",
                        'contents' => $data['save_comment'] ? '1' : '0',
                    ];
                }
            }
        }

        return $modifiedProperties;
    }
}
