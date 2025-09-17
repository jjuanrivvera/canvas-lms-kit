<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Completion Requirement Object
 *
 * Represents a completion requirement for a module item in Canvas LMS.
 * This is a read-only object that is embedded within ModuleItem responses.
 *
 * @package CanvasLMS\Objects
 */
class CompletionRequirement
{
    /**
     * The type of completion requirement
     * One of: 'must_view', 'must_submit', 'must_contribute', 'min_score', 'min_percentage', 'must_mark_done'
     */
    public ?string $type = null;

    /**
     * Minimum score required to complete (only present when type == 'min_score')
     */
    public ?float $minScore = null;

    /**
     * Minimum percentage required to complete (only present when type == 'min_percentage')
     */
    public ?float $minPercentage = null;

    /**
     * Whether the calling user has met this requirement
     * (Optional; present only if the caller is a student or if the optional parameter 'student_id' is included)
     */
    public ?bool $completed = null;

    /**
     * Constructor
     *
     * @param mixed[] $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $property = lcfirst(str_replace('_', '', ucwords((string) $key, '_')));
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Get the type of completion requirement
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set the type of completion requirement
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the minimum score required
     */
    public function getMinScore(): ?float
    {
        return $this->minScore;
    }

    /**
     * Set the minimum score required
     */
    public function setMinScore(?float $minScore): void
    {
        $this->minScore = $minScore;
    }

    /**
     * Get the minimum percentage required
     */
    public function getMinPercentage(): ?float
    {
        return $this->minPercentage;
    }

    /**
     * Set the minimum percentage required
     */
    public function setMinPercentage(?float $minPercentage): void
    {
        $this->minPercentage = $minPercentage;
    }

    /**
     * Get whether the requirement is completed
     */
    public function getCompleted(): ?bool
    {
        return $this->completed;
    }

    /**
     * Set whether the requirement is completed
     */
    public function setCompleted(?bool $completed): void
    {
        $this->completed = $completed;
    }

    /**
     * Check if this is a score-based requirement
     */
    public function isScoreBased(): bool
    {
        return $this->type === 'min_score';
    }

    /**
     * Check if this is a percentage-based requirement
     */
    public function isPercentageBased(): bool
    {
        return $this->type === 'min_percentage';
    }

    /**
     * Convert to array
     *
     * @return mixed[]
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
        ];

        if ($this->minScore !== null) {
            $data['min_score'] = $this->minScore;
        }

        if ($this->minPercentage !== null) {
            $data['min_percentage'] = $this->minPercentage;
        }

        if ($this->completed !== null) {
            $data['completed'] = $this->completed;
        }

        return $data;
    }
}
