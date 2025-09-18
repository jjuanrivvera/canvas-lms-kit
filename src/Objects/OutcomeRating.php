<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * OutcomeRating object for rating scale definitions.
 *
 * This is a read-only object that represents a single rating level
 * within an outcome's rating scale.
 */
class OutcomeRating
{
    public ?int $id = null;

    public ?string $description = null;

    public ?float $points = null;

    public ?string $color = null;

    public ?bool $mastery = null;

    /**
     * Create an OutcomeRating instance.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Check if this rating represents mastery level.
     *
     * @return bool
     */
    public function isMastery(): bool
    {
        return $this->mastery ?? false;
    }

    /**
     * Create a rating from simple data.
     *
     * @param string $description
     * @param float $points
     * @param bool $mastery
     *
     * @return self
     */
    public static function create(string $description, float $points, bool $mastery = false): self
    {
        return new self([
            'description' => $description,
            'points' => $points,
            'mastery' => $mastery,
        ]);
    }

    /**
     * Convert to array for API requests.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'description' => $this->description,
            'points' => $this->points,
            'color' => $this->color,
            'mastery' => $this->mastery,
        ], fn ($value) => $value !== null);
    }
}
