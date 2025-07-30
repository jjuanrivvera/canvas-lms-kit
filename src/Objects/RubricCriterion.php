<?php

namespace CanvasLMS\Objects;

/**
 * RubricCriterion Object
 *
 * Represents a single criterion within a rubric. Each criterion defines
 * a specific aspect of performance to be evaluated, along with its
 * possible ratings and point values.
 *
 * This is a read-only object returned as part of Rubric responses.
 * It does not have its own API endpoints.
 *
 * @package CanvasLMS\Objects
 */
class RubricCriterion
{
    /**
     * The unique identifier for this criterion
     *
     * @var string|null
     */
    public ?string $id = null;

    /**
     * Alternative criterion ID (sometimes returned as criterion_id)
     *
     * @var string|null
     */
    public ?string $criterionId = null;

    /**
     * Position of this criterion in the rubric
     *
     * @var int|null
     */
    public ?int $position = null;

    /**
     * Short description of the criterion
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Detailed description of the criterion
     *
     * @var string|null
     */
    public ?string $longDescription = null;

    /**
     * Maximum points possible for this criterion
     *
     * @var float|null
     */
    public ?float $points = null;

    /**
     * Whether to use the criterion range for scoring
     *
     * @var bool|null
     */
    public ?bool $criterionUseRange = null;

    /**
     * The possible ratings for this criterion
     *
     * @var array<int, RubricRating>|null
     */
    public ?array $ratings = null;

    /**
     * Learning outcome ID if linked
     *
     * @var string|null
     */
    public ?string $learningOutcomeId = null;

    /**
     * Creation timestamp
     *
     * @var string|null
     */
    public ?string $createdAt = null;

    /**
     * Last update timestamp
     *
     * @var string|null
     */
    public ?string $updatedAt = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        if (isset($data['id'])) {
            $this->id = (string) $data['id'];
        }
        if (isset($data['criterion_id'])) {
            $this->criterionId = (string) $data['criterion_id'];
        }
        if (isset($data['position'])) {
            $this->position = (int) $data['position'];
        }
        if (isset($data['description'])) {
            $this->description = $data['description'];
        }
        if (isset($data['long_description'])) {
            $this->longDescription = $data['long_description'];
        }
        if (isset($data['points'])) {
            $this->points = (float) $data['points'];
        }
        if (isset($data['criterion_use_range'])) {
            $this->criterionUseRange = (bool) $data['criterion_use_range'];
        }
        if (isset($data['ratings']) && is_array($data['ratings'])) {
            $this->ratings = [];
            foreach ($data['ratings'] as $rating) {
                if (is_array($rating)) {
                    $this->ratings[] = new RubricRating($rating);
                }
            }
        }
        if (isset($data['learning_outcome_id'])) {
            $this->learningOutcomeId = (string) $data['learning_outcome_id'];
        }
        if (isset($data['created_at'])) {
            $this->createdAt = $data['created_at'];
        }
        if (isset($data['updated_at'])) {
            $this->updatedAt = $data['updated_at'];
        }
    }

    /**
     * Convert to array for DTO operations
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }
        if ($this->criterionId !== null) {
            $data['criterion_id'] = $this->criterionId;
        }
        if ($this->position !== null) {
            $data['position'] = $this->position;
        }
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->longDescription !== null) {
            $data['long_description'] = $this->longDescription;
        }
        if ($this->points !== null) {
            $data['points'] = $this->points;
        }
        if ($this->criterionUseRange !== null) {
            $data['criterion_use_range'] = $this->criterionUseRange;
        }
        if ($this->ratings !== null) {
            $data['ratings'] = array_map(function (RubricRating $rating) {
                return $rating->toArray();
            }, $this->ratings);
        }
        if ($this->learningOutcomeId !== null) {
            $data['learning_outcome_id'] = $this->learningOutcomeId;
        }
        if ($this->createdAt !== null) {
            $data['created_at'] = $this->createdAt;
        }
        if ($this->updatedAt !== null) {
            $data['updated_at'] = $this->updatedAt;
        }

        return $data;
    }
}
