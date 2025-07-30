<?php

namespace CanvasLMS\Objects;

/**
 * RubricRating Object
 *
 * Represents a single rating level within a rubric criterion.
 * Each rating defines a performance level with its associated
 * description and point value.
 *
 * This is a read-only object returned as part of RubricCriterion responses.
 * It does not have its own API endpoints.
 *
 * @package CanvasLMS\Objects
 */
class RubricRating
{
    /**
     * The unique identifier for this rating
     *
     * @var string|null
     */
    public ?string $id = null;

    /**
     * The ID of the criterion this rating belongs to
     *
     * @var string|null
     */
    public ?string $criterionId = null;

    /**
     * Short description of the rating level
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Detailed description of the rating level
     *
     * @var string|null
     */
    public ?string $longDescription = null;

    /**
     * Points awarded for this rating level
     *
     * @var float|null
     */
    public ?float $points = null;

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
        if (isset($data['description'])) {
            $this->description = $data['description'];
        }
        if (isset($data['long_description'])) {
            $this->longDescription = $data['long_description'];
        }
        if (isset($data['points'])) {
            $this->points = (float) $data['points'];
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
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->longDescription !== null) {
            $data['long_description'] = $this->longDescription;
        }
        if ($this->points !== null) {
            $data['points'] = $this->points;
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
