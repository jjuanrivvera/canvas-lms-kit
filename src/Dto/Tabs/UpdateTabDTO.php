<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Tabs;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Exceptions\CanvasApiException;

/**
 * Data Transfer Object for updating a Canvas Tab
 *
 * This DTO handles the data structure for updating Canvas tabs.
 * Only position and hidden properties can be updated according to Canvas API.
 *
 * Usage:
 * $updateDto = new UpdateTabDTO(position: 3, hidden: false);
 * $tab = Tab::update('assignments', $updateDto);
 *
 * @package CanvasLMS\Dto\Tabs
 */
class UpdateTabDTO extends AbstractBaseDto
{
    protected string $apiPropertyName = 'tab';

    /**
     * Create a new UpdateTabDTO instance
     *
     * @param int|null $position Position of the tab (1-based, max 50)
     * @param bool|null $hidden Whether the tab should be hidden
     * @throws CanvasApiException If position is invalid
     */
    public function __construct(
        public ?int $position = null,
        public ?bool $hidden = null
    ) {
        if ($position !== null && ($position < 1 || $position > 50)) {
            throw new CanvasApiException('Position must be a positive integer between 1 and 50');
        }
        parent::__construct([]);
    }

    /**
     * Get the position
     *
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Set the position
     *
     * @param int|null $position Position (1-based, max 50)
     * @return void
     * @throws CanvasApiException If position is invalid
     */
    public function setPosition(?int $position): void
    {
        if ($position !== null && ($position < 1 || $position > 50)) {
            throw new CanvasApiException('Position must be a positive integer between 1 and 50');
        }
        $this->position = $position;
    }

    /**
     * Get the hidden status
     *
     * @return bool|null
     */
    public function getHidden(): ?bool
    {
        return $this->hidden;
    }

    /**
     * Set the hidden status
     *
     * @param bool|null $hidden
     * @return void
     */
    public function setHidden(?bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * Convert to array for API requests
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->position !== null) {
            $data['position'] = $this->position;
        }

        if ($this->hidden !== null) {
            $data['hidden'] = $this->hidden;
        }

        return $data;
    }

    /**
     * Convert to API multipart format
     *
     * @return array<array<string, mixed>>
     */
    public function toApiArray(): array
    {
        $modifiedProperties = [];

        if ($this->position !== null) {
            $modifiedProperties[] = [
                'name' => 'tab[position]',
                'contents' => $this->position
            ];
        }

        if ($this->hidden !== null) {
            $modifiedProperties[] = [
                'name' => 'tab[hidden]',
                'contents' => $this->hidden
            ];
        }

        return $modifiedProperties;
    }
}
