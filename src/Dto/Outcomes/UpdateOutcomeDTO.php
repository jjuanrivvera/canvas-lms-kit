<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Outcomes;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Data Transfer Object for updating outcomes.
 */
class UpdateOutcomeDTO extends AbstractBaseDto
{
    protected string $apiPropertyName = 'outcome';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->title = $data['title'] ?? null;
        $this->displayName = $data['displayName'] ?? $data['display_name'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->vendorGuid = $data['vendorGuid'] ?? $data['vendor_guid'] ?? null;
        $this->masteryPoints = isset($data['masteryPoints'])
            ? (float)$data['masteryPoints']
            : (isset($data['mastery_points']) ? (float)$data['mastery_points'] : null);
        $this->ratings = $data['ratings'] ?? null;
        $this->calculationMethod = $data['calculationMethod'] ?? $data['calculation_method'] ?? null;
        $this->calculationInt = isset($data['calculationInt'])
            ? (int)$data['calculationInt']
            : (isset($data['calculation_int']) ? (int)$data['calculation_int'] : null);

        parent::__construct($data);
    }

    public ?string $title = null;
    public ?string $displayName = null;
    public ?string $description = null;
    public ?string $vendorGuid = null;
    public ?float $masteryPoints = null;
    /** @var array<int, array{description: string, points: float}>|null */
    public ?array $ratings = null;
    public ?string $calculationMethod = null;
    public ?int $calculationInt = null;

    /**
     * Convert DTO to array for API request.
     *
     * @return array<int, array{name: string, contents: string}>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data[] = [
                'name' => 'title',
                'contents' => $this->title
            ];
        }

        if ($this->displayName !== null) {
            $data[] = [
                'name' => 'display_name',
                'contents' => $this->displayName
            ];
        }

        if ($this->description !== null) {
            $data[] = [
                'name' => 'description',
                'contents' => $this->description
            ];
        }

        if ($this->vendorGuid !== null) {
            $data[] = [
                'name' => 'vendor_guid',
                'contents' => $this->vendorGuid
            ];
        }

        if ($this->masteryPoints !== null) {
            $data[] = [
                'name' => 'mastery_points',
                'contents' => (string)$this->masteryPoints
            ];
        }

        if ($this->ratings !== null && !empty($this->ratings)) {
            foreach ($this->ratings as $index => $rating) {
                $data[] = [
                    'name' => sprintf('ratings[%d][description]', $index),
                    'contents' => $rating['description']
                ];
                $data[] = [
                    'name' => sprintf('ratings[%d][points]', $index),
                    'contents' => (string)$rating['points']
                ];
            }
        }

        if ($this->calculationMethod !== null) {
            $data[] = [
                'name' => 'calculation_method',
                'contents' => $this->calculationMethod
            ];
        }

        if ($this->calculationInt !== null) {
            $data[] = [
                'name' => 'calculation_int',
                'contents' => (string)$this->calculationInt
            ];
        }

        return $data;
    }

    /**
     * Create an UpdateOutcomeDTO with only specific fields.
     *
     * @param array<string, mixed> $fields
     * @return self
     */
    public static function withFields(array $fields): self
    {
        $dto = new self();

        foreach ($fields as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }

        return $dto;
    }

    /**
     * Update only the title.
     *
     * @param string $title
     * @return self
     */
    public static function updateTitle(string $title): self
    {
        return new self(['title' => $title]);
    }

    /**
     * Update only the description.
     *
     * @param string $description
     * @return self
     */
    public static function updateDescription(string $description): self
    {
        return new self(['description' => $description]);
    }

    /**
     * Update only the mastery points.
     *
     * @param float $masteryPoints
     * @return self
     */
    public static function updateMasteryPoints(float $masteryPoints): self
    {
        return new self(['masteryPoints' => $masteryPoints]);
    }

    /**
     * Update only the ratings.
     *
     * @param array<int, array{description: string, points: float}> $ratings
     * @return self
     */
    public static function updateRatings(array $ratings): self
    {
        return new self(['ratings' => $ratings]);
    }

    /**
     * Update calculation method and optional int parameter.
     *
     * @param string $method
     * @param int|null $calculationInt
     * @return self
     */
    public static function updateCalculationMethod(string $method, ?int $calculationInt = null): self
    {
        return new self(['calculationMethod' => $method, 'calculationInt' => $calculationInt]);
    }

    /**
     * Validate the calculation method.
     *
     * @return bool
     */
    public function validateCalculationMethod(): bool
    {
        if ($this->calculationMethod === null) {
            return true;
        }

        $validMethods = [
            'decaying_average',
            'n_mastery',
            'latest',
            'highest',
            'average'
        ];

        return in_array($this->calculationMethod, $validMethods, true);
    }

    /**
     * Validate the rating scale.
     *
     * @return bool
     */
    public function validateRatings(): bool
    {
        if ($this->ratings === null || empty($this->ratings)) {
            return true;
        }

        // With proper type hints, we only need to validate business logic

        if ($this->masteryPoints !== null) {
            $masteryFound = false;
            foreach ($this->ratings as $rating) {
                if ((float)$rating['points'] === $this->masteryPoints) {
                    $masteryFound = true;
                    break;
                }
            }

            if (!$masteryFound) {
                return false;
            }
        }

        return true;
    }
}
