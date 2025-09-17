<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Override Target Object
 *
 * Represents a target (user or section) for a module assignment override.
 * This is a read-only object that is embedded within ModuleAssignmentOverride responses.
 *
 * @package CanvasLMS\Objects
 */
class OverrideTarget
{
    /**
     * The type of target ('student', 'section', or 'group')
     */
    public ?string $type = null;

    /**
     * The ID of the user or section that the override is targeting
     */
    public ?int $id = null;

    /**
     * The name of the user or section that the override is targeting
     */
    public ?string $name = null;

    /**
     * Constructor
     *
     * @param mixed[] $data
     */
    public function __construct(array $data = [])
    {
        if (isset($data['type'])) {
            $this->type = (string) $data['type'];
        }

        if (isset($data['id'])) {
            $this->id = (int) $data['id'];
        }

        if (isset($data['name'])) {
            $this->name = (string) $data['name'];
        }
    }

    /**
     * Get the type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set the type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the target ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the target ID
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the target name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the target name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Check if this is a student target
     */
    public function isStudent(): bool
    {
        return $this->type === 'student';
    }

    /**
     * Check if this is a section target
     */
    public function isSection(): bool
    {
        return $this->type === 'section';
    }

    /**
     * Check if this is a group target
     */
    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /**
     * Convert to array
     *
     * @return mixed[]
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->type !== null) {
            $data['type'] = $this->type;
        }

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        return $data;
    }
}
