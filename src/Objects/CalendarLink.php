<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * CalendarLink Object
 *
 * Represents a calendar link for a course in Canvas LMS.
 * This is a read-only object that provides the ICS format URL for course calendars.
 *
 * @package CanvasLMS\Objects
 */
class CalendarLink
{
    /**
     * The URL of the calendar in ICS format
     */
    public ?string $ics = null;

    /**
     * Constructor
     *
     * @param mixed[] $data
     */
    public function __construct(array $data = [])
    {
        if (isset($data['ics']) && is_scalar($data['ics'])) {
            $this->ics = (string) $data['ics'];
        }
    }

    /**
     * Get the ICS URL
     */
    public function getIcs(): ?string
    {
        return $this->ics;
    }

    /**
     * Set the ICS URL
     */
    public function setIcs(?string $ics): void
    {
        $this->ics = $ics;
    }

    /**
     * Check if calendar link is available
     */
    public function isAvailable(): bool
    {
        return $this->ics !== null && $this->ics !== '';
    }

    /**
     * Convert to array
     *
     * @return mixed[]
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->ics !== null) {
            $data['ics'] = $this->ics;
        }

        return $data;
    }
}
