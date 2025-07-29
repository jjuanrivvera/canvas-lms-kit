<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Term Object
 *
 * Represents an enrollment term in Canvas LMS.
 * This is a read-only object that is embedded within Course responses.
 *
 * @package CanvasLMS\Objects
 */
class Term
{
    /**
     * The unique identifier for the term
     */
    public ?int $id = null;

    /**
     * The name of the term
     */
    public ?string $name = null;

    /**
     * The start date of the term
     */
    public ?string $startAt = null;

    /**
     * The end date of the term
     */
    public ?string $endAt = null;

    /**
     * Constructor
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
     * Get the term ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the term ID
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the term name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the term name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the start date
     */
    public function getStartAt(): ?string
    {
        return $this->startAt;
    }

    /**
     * Set the start date
     */
    public function setStartAt(?string $startAt): void
    {
        $this->startAt = $startAt;
    }

    /**
     * Get the end date
     */
    public function getEndAt(): ?string
    {
        return $this->endAt;
    }

    /**
     * Set the end date
     */
    public function setEndAt(?string $endAt): void
    {
        $this->endAt = $endAt;
    }

    /**
     * Check if term has started
     */
    public function hasStarted(): bool
    {
        if ($this->startAt === null) {
            return true; // No start date means always started
        }

        return strtotime($this->startAt) <= time();
    }

    /**
     * Check if term has ended
     */
    public function hasEnded(): bool
    {
        if ($this->endAt === null) {
            return false; // No end date means never ends
        }

        return strtotime($this->endAt) < time();
    }

    /**
     * Check if term is active
     */
    public function isActive(): bool
    {
        return $this->hasStarted() && !$this->hasEnded();
    }

    /**
     * Convert to array
     * @return mixed[]
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->startAt !== null) {
            $data['start_at'] = $this->startAt;
        }

        if ($this->endAt !== null) {
            $data['end_at'] = $this->endAt;
        }

        return $data;
    }
}
