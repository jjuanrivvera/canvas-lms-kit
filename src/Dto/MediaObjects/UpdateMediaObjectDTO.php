<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\MediaObjects;

use CanvasLMS\Dto\AbstractBaseDto;
use InvalidArgumentException;

/**
 * UpdateMediaObjectDTO
 *
 * Data Transfer Object for updating a MediaObject
 *
 * @package CanvasLMS\Dto\MediaObjects
 */
class UpdateMediaObjectDTO extends AbstractBaseDto
{
    /**
     * Constructor
     *
     * @param string|null $userEnteredTitle The new title for the media object
     */
    public function __construct(
        public ?string $userEnteredTitle = null
    ) {
        $this->validate();
    }

    /**
     * Validate the DTO data
     *
     * @throws InvalidArgumentException
     */
    protected function validate(): void
    {
        // Title is optional but if provided, should not be empty
        if ($this->userEnteredTitle !== null && trim($this->userEnteredTitle) === '') {
            throw new InvalidArgumentException('User entered title cannot be empty if provided');
        }
    }

    /**
     * Convert the DTO to an array for API request
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'user_entered_title' => $this->userEnteredTitle,
        ], fn($value) => !is_null($value));
    }

    /**
     * Create DTO from array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userEnteredTitle: $data['user_entered_title'] ?? $data['userEnteredTitle'] ?? null
        );
    }
}
