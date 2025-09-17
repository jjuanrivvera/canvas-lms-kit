<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

use CanvasLMS\Api\OutcomeGroups\OutcomeGroup;
use CanvasLMS\Api\Outcomes\Outcome;

/**
 * OutcomeLink represents the relationship between an outcome and a group.
 * This is a read-only object that does not extend AbstractBaseApi.
 *
 * @see https://canvas.instructure.com/doc/api/outcome_groups.html#OutcomeLink
 */
class OutcomeLink
{
    public ?string $url = null;

    public ?int $contextId = null;

    public ?string $contextType = null;

    public ?OutcomeGroup $outcomeGroup = null;

    public ?Outcome $outcome = null;

    public ?bool $assessed = null;

    public ?bool $canUnlink = null;

    /**
     * Constructor to hydrate the object from API response.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->url = $data['url'] ?? null;
        $this->contextId = $data['context_id'] ?? null;
        $this->contextType = $data['context_type'] ?? null;
        $this->assessed = $data['assessed'] ?? null;
        $this->canUnlink = $data['can_unlink'] ?? null;

        if (isset($data['outcome_group'])) {
            $this->outcomeGroup = new OutcomeGroup($data['outcome_group']);
        }

        if (isset($data['outcome'])) {
            $this->outcome = new Outcome($data['outcome']);
        }
    }

    /**
     * Check if this outcome has been assessed.
     *
     * @return bool
     */
    public function isAssessed(): bool
    {
        return $this->assessed ?? false;
    }

    /**
     * Check if this outcome link can be unlinked.
     *
     * @return bool
     */
    public function canBeUnlinked(): bool
    {
        return $this->canUnlink ?? false;
    }

    /**
     * Get the outcome ID if available.
     *
     * @return int|null
     */
    public function getOutcomeId(): ?int
    {
        return $this->outcome?->id;
    }

    /**
     * Get the outcome group ID if available.
     *
     * @return int|null
     */
    public function getOutcomeGroupId(): ?int
    {
        return $this->outcomeGroup?->id;
    }
}
