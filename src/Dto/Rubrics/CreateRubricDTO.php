<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Rubrics;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

use function str_to_snake_case;

/**
 * Data Transfer Object for creating Canvas rubrics.
 *
 * @see https://canvas.instructure.com/doc/api/rubrics.html#method.RubricsController.create
 */
class CreateRubricDTO extends AbstractBaseDto implements DTOInterface
{
    protected string $apiPropertyName = 'rubric';

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
     * The title of the rubric
     */
    public ?string $title = null;

    /**
     * Whether or not you can write custom comments in the ratings field for a rubric
     */
    public ?bool $freeFormCriterionComments = null;

    /**
     * Whether or not the score total is displayed within the rubric
     */
    public ?bool $hideScoreTotal = null;

    /**
     * An array of criteria objects for the rubric
     *
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $criteria = null;

    /**
     * The ID of the rubric association (if associating with an existing object)
     */
    public ?int $rubricAssociationId = null;

    /**
     * Association data for creating a rubric association at the same time
     *
     * @var array<string, mixed>|null
     */
    public ?array $association = null;

    /**
     * Convert the DTO to an array for API requests
     * @return array<int, array<string, mixed>>
     */
    public function toApiArray(): array
    {
        $modifiedProperties = [];

        // Handle simple properties
        if ($this->title !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric[title]',
                'contents' => $this->title
            ];
        }

        if ($this->freeFormCriterionComments !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric[free_form_criterion_comments]',
                'contents' => $this->freeFormCriterionComments ? '1' : '0'
            ];
        }

        if ($this->hideScoreTotal !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric[hide_score_total]',
                'contents' => $this->hideScoreTotal ? '1' : '0'
            ];
        }

        // Handle rubric association ID if provided
        if ($this->rubricAssociationId !== null) {
            $modifiedProperties[] = [
                'name' => 'rubric_association_id',
                'contents' => (string) $this->rubricAssociationId
            ];
        }

        // Handle complex criteria structure
        if ($this->criteria !== null) {
            foreach ($this->criteria as $index => $criterion) {
                // Use the criterion ID as the key, or index if not provided
                $criterionKey = $criterion['id'] ?? $index;

                if (isset($criterion['description'])) {
                    $modifiedProperties[] = [
                        'name' => "rubric[criteria][$criterionKey][description]",
                        'contents' => $criterion['description']
                    ];
                }

                if (isset($criterion['long_description'])) {
                    $modifiedProperties[] = [
                        'name' => "rubric[criteria][$criterionKey][long_description]",
                        'contents' => $criterion['long_description']
                    ];
                }

                if (isset($criterion['points'])) {
                    $modifiedProperties[] = [
                        'name' => "rubric[criteria][$criterionKey][points]",
                        'contents' => (string) $criterion['points']
                    ];
                }

                if (isset($criterion['criterion_use_range'])) {
                    $modifiedProperties[] = [
                        'name' => "rubric[criteria][$criterionKey][criterion_use_range]",
                        'contents' => $criterion['criterion_use_range'] ? '1' : '0'
                    ];
                }

                // Handle ratings array
                if (isset($criterion['ratings']) && is_array($criterion['ratings'])) {
                    foreach ($criterion['ratings'] as $ratingIndex => $rating) {
                        $ratingKey = $rating['id'] ?? $ratingIndex;

                        if (isset($rating['description'])) {
                            $modifiedProperties[] = [
                                'name' => "rubric[criteria][$criterionKey][ratings][$ratingKey][description]",
                                'contents' => $rating['description']
                            ];
                        }

                        if (isset($rating['long_description'])) {
                            $modifiedProperties[] = [
                                'name' => "rubric[criteria][$criterionKey][ratings][$ratingKey][long_description]",
                                'contents' => $rating['long_description']
                            ];
                        }

                        if (isset($rating['points'])) {
                            $modifiedProperties[] = [
                                'name' => "rubric[criteria][$criterionKey][ratings][$ratingKey][points]",
                                'contents' => (string) $rating['points']
                            ];
                        }
                    }
                }
            }
        }

        // Handle association data if provided
        if ($this->association !== null) {
            foreach ($this->association as $key => $value) {
                $snakeKey = str_to_snake_case($key);
                if ($value !== null) {
                    if (is_bool($value)) {
                        $value = $value ? '1' : '0';
                    }
                    $modifiedProperties[] = [
                        'name' => "rubric_association[$snakeKey]",
                        'contents' => (string) $value
                    ];
                }
            }
        }

        return $modifiedProperties;
    }
}
