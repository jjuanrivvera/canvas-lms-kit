<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Rubrics;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating Canvas rubric associations.
 *
 * @see https://canvas.instructure.com/doc/api/rubrics.html#method.RubricAssociationsController.update
 */
class UpdateRubricAssociationDTO extends AbstractBaseDto implements DTOInterface
{
    protected string $apiPropertyName = 'rubric_association';

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
     * The ID of the Rubric
     */
    public ?int $rubricId = null;

    /**
     * The ID of the object with which this rubric is associated
     */
    public ?int $associationId = null;

    /**
     * The type of object this rubric is associated with
     * Allowed values: Assignment, Course, Account
     */
    public ?string $associationType = null;

    /**
     * The name of the object this rubric is associated with
     */
    public ?string $title = null;

    /**
     * Whether or not the associated rubric is used for grade calculation
     */
    public ?bool $useForGrading = null;

    /**
     * Whether or not the score total is displayed within the rubric.
     * This option is only available if the rubric is not used for grading.
     */
    public ?bool $hideScoreTotal = null;

    /**
     * Whether or not the association is for grading (and thus linked to an assignment)
     * or if it's to indicate the rubric should appear in its context
     * Allowed values: grading, bookmark
     */
    public ?string $purpose = null;

    /**
     * Whether or not the associated rubric appears in its context
     */
    public ?bool $bookmarked = null;

    /**
     * Whether or not points are hidden in the rubric
     */
    public ?bool $hidePoints = null;

    /**
     * Whether or not outcome results are hidden in the rubric
     */
    public ?bool $hideOutcomeResults = null;

    /**
     * Convert the DTO to an array for API requests
     *
     * @return array<int, array<string, mixed>>
     */
    public function toApiArray(): array
    {
        $modifiedProperties = [];

        // Handle simple properties
        if ($this->rubricId !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_association[rubric_id]',
                'contents' => (string) $this->rubricId,
            ];
        }

        if ($this->associationId !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_association[association_id]',
                'contents' => (string) $this->associationId,
            ];
        }

        if ($this->associationType !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_association[association_type]',
                'contents' => $this->associationType,
            ];
        }

        if ($this->title !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_association[title]',
                'contents' => $this->title,
            ];
        }

        if ($this->useForGrading !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_association[use_for_grading]',
                'contents' => $this->useForGrading ? '1' : '0',
            ];
        }

        if ($this->purpose !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_association[purpose]',
                'contents' => $this->purpose,
            ];
        }

        if ($this->bookmarked !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_association[bookmarked]',
                'contents' => $this->bookmarked ? '1' : '0',
            ];
        }

        if ($this->hideScoreTotal !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_association[hide_score_total]',
                'contents' => $this->hideScoreTotal ? '1' : '0',
            ];
        }

        if ($this->hidePoints !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_association[hide_points]',
                'contents' => $this->hidePoints ? '1' : '0',
            ];
        }

        if ($this->hideOutcomeResults !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_association[hide_outcome_results]',
                'contents' => $this->hideOutcomeResults ? '1' : '0',
            ];
        }

        return $modifiedProperties;
    }
}
