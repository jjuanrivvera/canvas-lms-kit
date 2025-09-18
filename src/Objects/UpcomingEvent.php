<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

use DateTime;
use DateTimeInterface;

/**
 * Upcoming event for users
 */
class UpcomingEvent
{
    /**
     * @var string Event title
     */
    public string $title;

    /**
     * @var string|null Event description
     */
    public ?string $description = null;

    /**
     * @var string Start time (ISO 8601)
     */
    public string $startAt;

    /**
     * @var string End time (ISO 8601)
     */
    public string $endAt;

    /**
     * @var string|null Location name
     */
    public ?string $locationName = null;

    /**
     * @var string|null Location address
     */
    public ?string $locationAddress = null;

    /**
     * @var string Context code (e.g., "course_123")
     */
    public string $contextCode;

    /**
     * @var string|null Effective context code
     */
    public ?string $effectiveContextCode = null;

    /**
     * @var string All context codes (comma separated)
     */
    public string $allContextCodes;

    /**
     * @var string Workflow state
     */
    public string $workflowState;

    /**
     * @var bool Whether the event is hidden
     */
    public bool $hidden;

    /**
     * @var int|null Parent event ID
     */
    public ?int $parentEventId = null;

    /**
     * @var int Number of child events
     */
    public int $childEventsCount;

    /**
     * @var array<mixed> Child events
     */
    public array $childEvents = [];

    /**
     * @var string URL to the event in Canvas API
     */
    public string $url;

    /**
     * @var string URL to the event in Canvas web UI
     */
    public string $htmlUrl;

    /**
     * @var string Created timestamp
     */
    public string $createdAt;

    /**
     * @var string Updated timestamp
     */
    public string $updatedAt;

    /**
     * @var int|null Appointment group ID
     */
    public ?int $appointmentGroupId = null;

    /**
     * @var string|null Appointment group URL
     */
    public ?string $appointmentGroupUrl = null;

    /**
     * @var bool Whether this is user's own reservation
     */
    public bool $ownReservation;

    /**
     * @var string|null Reserve URL
     */
    public ?string $reserveUrl = null;

    /**
     * @var bool Whether the event is reserved
     */
    public bool $reserved;

    /**
     * @var string Participant type
     */
    public string $participantType;

    /**
     * @var int|null Participants per appointment
     */
    public ?int $participantsPerAppointment = null;

    /**
     * @var int|null Available slots
     */
    public ?int $availableSlots = null;

    /**
     * @var mixed User data
     */
    public $user = null;

    /**
     * @var mixed Group data
     */
    public $group = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $property = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Get start time as DateTime
     *
     * @return DateTimeInterface|null
     */
    public function getStartAtDate(): ?DateTimeInterface
    {
        return isset($this->startAt) ? new DateTime($this->startAt) : null;
    }

    /**
     * Get end time as DateTime
     *
     * @return DateTimeInterface|null
     */
    public function getEndAtDate(): ?DateTimeInterface
    {
        return isset($this->endAt) ? new DateTime($this->endAt) : null;
    }

    /**
     * Check if event is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->workflowState === 'active';
    }

    /**
     * Check if event has location
     *
     * @return bool
     */
    public function hasLocation(): bool
    {
        return !empty($this->locationName) || !empty($this->locationAddress);
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
