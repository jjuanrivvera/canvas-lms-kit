<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * CourseProgress Object
 *
 * Represents a user's progress through a course in Canvas LMS.
 * This is a read-only object that is embedded within Course responses when requested.
 *
 * @package CanvasLMS\Objects
 */
class CourseProgress
{
    /**
     * Total number of requirements from all modules
     */
    public ?int $requirementCount = null;

    /**
     * Total number of requirements the user has completed from all modules
     */
    public ?int $requirementCompletedCount = null;

    /**
     * URL to next module item that has an unmet requirement
     * Null if the user has completed the course or the current module does not require sequential progress
     */
    public ?string $nextRequirementUrl = null;

    /**
     * Date the course was completed
     * Null if the course has not been completed by this user
     */
    public ?string $completedAt = null;

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
     * Get the total requirement count
     */
    public function getRequirementCount(): ?int
    {
        return $this->requirementCount;
    }

    /**
     * Set the total requirement count
     */
    public function setRequirementCount(?int $requirementCount): void
    {
        $this->requirementCount = $requirementCount;
    }

    /**
     * Get the completed requirement count
     */
    public function getRequirementCompletedCount(): ?int
    {
        return $this->requirementCompletedCount;
    }

    /**
     * Set the completed requirement count
     */
    public function setRequirementCompletedCount(?int $requirementCompletedCount): void
    {
        $this->requirementCompletedCount = $requirementCompletedCount;
    }

    /**
     * Get the next requirement URL
     */
    public function getNextRequirementUrl(): ?string
    {
        return $this->nextRequirementUrl;
    }

    /**
     * Set the next requirement URL
     */
    public function setNextRequirementUrl(?string $nextRequirementUrl): void
    {
        $this->nextRequirementUrl = $nextRequirementUrl;
    }

    /**
     * Get the completion date
     */
    public function getCompletedAt(): ?string
    {
        return $this->completedAt;
    }

    /**
     * Set the completion date
     */
    public function setCompletedAt(?string $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    /**
     * Check if the course is completed
     */
    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    /**
     * Check if there are more requirements to complete
     */
    public function hasMoreRequirements(): bool
    {
        return $this->nextRequirementUrl !== null;
    }

    /**
     * Get the completion percentage
     */
    public function getCompletionPercentage(): float
    {
        if ($this->requirementCount === null || $this->requirementCount === 0) {
            return 0.0;
        }

        if ($this->requirementCompletedCount === null) {
            return 0.0;
        }

        return ($this->requirementCompletedCount / $this->requirementCount) * 100;
    }

    /**
     * Get remaining requirements count
     */
    public function getRemainingRequirements(): int
    {
        if ($this->requirementCount === null || $this->requirementCompletedCount === null) {
            return 0;
        }

        return max(0, $this->requirementCount - $this->requirementCompletedCount);
    }

    /**
     * Convert to array
     *
     * @return mixed[]
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->requirementCount !== null) {
            $data['requirement_count'] = $this->requirementCount;
        }

        if ($this->requirementCompletedCount !== null) {
            $data['requirement_completed_count'] = $this->requirementCompletedCount;
        }

        if ($this->nextRequirementUrl !== null) {
            $data['next_requirement_url'] = $this->nextRequirementUrl;
        }

        if ($this->completedAt !== null) {
            $data['completed_at'] = $this->completedAt;
        }

        return $data;
    }
}
