<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\CalendarEvents;

use DateTime;
use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * Data Transfer Object for updating calendar events
 *
 * @package CanvasLMS\Dto\CalendarEvents
 */
class UpdateCalendarEventDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The API property name
     * @var string
     */
    protected string $apiPropertyName = 'calendar_event';

    /**
     * Context code for the event (e.g., "course_123", "user_456", "group_789")
     * @var string|null
     */
    public ?string $contextCode = null;

    /**
     * Event title
     * @var string|null
     */
    public ?string $title = null;

    /**
     * Event description in HTML format
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Start time for the event
     * @var DateTime|null
     */
    public ?DateTime $startAt = null;

    /**
     * End time for the event
     * @var DateTime|null
     */
    public ?DateTime $endAt = null;

    /**
     * Location name for the event
     * @var string|null
     */
    public ?string $locationName = null;

    /**
     * Location address for the event
     * @var string|null
     */
    public ?string $locationAddress = null;

    /**
     * Time zone for the event (IANA format)
     * @var string|null
     */
    public ?string $timeZone = null;

    /**
     * Mark as all-day event
     * @var bool|null
     */
    public ?bool $allDay = null;

    /**
     * RRULE for recurring events (RFC 5545 format)
     * @var string|null
     */
    public ?string $rrule = null;

    /**
     * Natural language description of recurrence
     * @var string|null
     */
    public ?string $seriesNaturalLanguage = null;

    /**
     * Blackout date flag - prevents scheduling of other events
     * @var bool|null
     */
    public ?bool $blackoutDate = null;

    /**
     * Important dates flag
     * @var bool|null
     */
    public ?bool $importantDates = null;

    /**
     * Which events to update (only, following, all)
     * Used for recurring events
     * @var string|null
     */
    public ?string $which = null;

    /**
     * Get context code
     * @return string|null
     */
    public function getContextCode(): ?string
    {
        return $this->contextCode;
    }

    /**
     * Set context code
     * @param string|null $contextCode
     * @return self
     */
    public function setContextCode(?string $contextCode): self
    {
        $this->contextCode = $contextCode;
        return $this;
    }

    /**
     * Get title
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set title
     * @param string|null $title
     * @return self
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get description
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set description
     * @param string|null $description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get start time
     * @return DateTime|null
     */
    public function getStartAt(): ?DateTime
    {
        return $this->startAt;
    }

    /**
     * Set start time
     * @param DateTime|null $startAt
     * @return self
     */
    public function setStartAt(?DateTime $startAt): self
    {
        $this->startAt = $startAt;
        return $this;
    }

    /**
     * Get end time
     * @return DateTime|null
     */
    public function getEndAt(): ?DateTime
    {
        return $this->endAt;
    }

    /**
     * Set end time
     * @param DateTime|null $endAt
     * @return self
     */
    public function setEndAt(?DateTime $endAt): self
    {
        $this->endAt = $endAt;
        return $this;
    }

    /**
     * Get location name
     * @return string|null
     */
    public function getLocationName(): ?string
    {
        return $this->locationName;
    }

    /**
     * Set location name
     * @param string|null $locationName
     * @return self
     */
    public function setLocationName(?string $locationName): self
    {
        $this->locationName = $locationName;
        return $this;
    }

    /**
     * Get location address
     * @return string|null
     */
    public function getLocationAddress(): ?string
    {
        return $this->locationAddress;
    }

    /**
     * Set location address
     * @param string|null $locationAddress
     * @return self
     */
    public function setLocationAddress(?string $locationAddress): self
    {
        $this->locationAddress = $locationAddress;
        return $this;
    }

    /**
     * Get time zone
     * @return string|null
     */
    public function getTimeZone(): ?string
    {
        return $this->timeZone;
    }

    /**
     * Set time zone
     * @param string|null $timeZone
     * @return self
     */
    public function setTimeZone(?string $timeZone): self
    {
        $this->timeZone = $timeZone;
        return $this;
    }

    /**
     * Is all day event
     * @return bool|null
     */
    public function isAllDay(): ?bool
    {
        return $this->allDay;
    }

    /**
     * Set all day flag
     * @param bool|null $allDay
     * @return self
     */
    public function setAllDay(?bool $allDay): self
    {
        $this->allDay = $allDay;
        return $this;
    }

    /**
     * Get RRULE
     * @return string|null
     */
    public function getRrule(): ?string
    {
        return $this->rrule;
    }

    /**
     * Set RRULE
     * @param string|null $rrule
     * @return self
     */
    public function setRrule(?string $rrule): self
    {
        $this->rrule = $rrule;
        return $this;
    }

    /**
     * Get series natural language
     * @return string|null
     */
    public function getSeriesNaturalLanguage(): ?string
    {
        return $this->seriesNaturalLanguage;
    }

    /**
     * Set series natural language
     * @param string|null $seriesNaturalLanguage
     * @return self
     */
    public function setSeriesNaturalLanguage(?string $seriesNaturalLanguage): self
    {
        $this->seriesNaturalLanguage = $seriesNaturalLanguage;
        return $this;
    }

    /**
     * Is blackout date
     * @return bool|null
     */
    public function isBlackoutDate(): ?bool
    {
        return $this->blackoutDate;
    }

    /**
     * Set blackout date flag
     * @param bool|null $blackoutDate
     * @return self
     */
    public function setBlackoutDate(?bool $blackoutDate): self
    {
        $this->blackoutDate = $blackoutDate;
        return $this;
    }

    /**
     * Is important dates
     * @return bool|null
     */
    public function isImportantDates(): ?bool
    {
        return $this->importantDates;
    }

    /**
     * Set important dates flag
     * @param bool|null $importantDates
     * @return self
     */
    public function setImportantDates(?bool $importantDates): self
    {
        $this->importantDates = $importantDates;
        return $this;
    }

    /**
     * Get which events to update
     * @return string|null
     */
    public function getWhich(): ?string
    {
        return $this->which;
    }

    /**
     * Set which events to update
     * @param string|null $which
     * @return self
     */
    public function setWhich(?string $which): self
    {
        $this->which = $which;
        return $this;
    }
}
