<?php

declare(strict_types=1);

namespace CanvasLMS\Api\CalendarEvents;

use DateTime;
use DateTimeInterface;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\CalendarEvents\CreateCalendarEventDTO;
use CanvasLMS\Dto\CalendarEvents\UpdateCalendarEventDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;
use CanvasLMS\Pagination\PaginationResult;
use CanvasLMS\Objects\EventContext;
use CanvasLMS\Objects\EventType;

/**
 * Canvas LMS Calendar Events API
 *
 * Provides functionality to manage calendar events in Canvas LMS.
 * This class handles creating, reading, updating, and deleting calendar events
 * across different contexts (courses, users, groups).
 *
 * Usage Examples:
 *
 * ```php
 * // Create a calendar event for a course
 * $eventData = [
 *     'context_code' => 'course_123',
 *     'title' => 'Midterm Exam',
 *     'start_at' => new DateTime('2025-03-15 10:00:00'),
 *     'end_at' => new DateTime('2025-03-15 12:00:00'),
 *     'location_name' => 'Room 101',
 *     'description' => 'Covers chapters 1-5'
 * ];
 * $event = CalendarEvent::create($eventData);
 *
 * // Find a calendar event by ID
 * $event = CalendarEvent::find(456);
 *
 * // List all calendar events
 * $events = CalendarEvent::fetchAll();
 *
 * // List calendar events for a specific context
 * $courseEvents = CalendarEvent::fetchAllForContext('course_123');
 * $userEvents = CalendarEvent::fetchAllForContext('user_456');
 *
 * // Get paginated calendar events
 * $paginatedEvents = CalendarEvent::fetchAllPaginated();
 * $paginationResult = CalendarEvent::fetchPage();
 *
 * // Update a calendar event
 * $updatedEvent = CalendarEvent::update(456, ['title' => 'Updated Exam Title']);
 *
 * // Update using instance method
 * $event = CalendarEvent::find(456);
 * $event->setTitle('Updated Title');
 * $event->setLocationName('Room 202');
 * $success = $event->save();
 *
 * // Delete a calendar event
 * $event = CalendarEvent::find(456);
 * $success = $event->delete();
 *
 * // Create a recurring event series
 * $recurringData = [
 *     'context_code' => 'course_123',
 *     'title' => 'Weekly Lab Session',
 *     'start_at' => new DateTime('2025-02-01 14:00:00'),
 *     'end_at' => new DateTime('2025-02-01 16:00:00'),
 *     'rrule' => 'FREQ=WEEKLY;COUNT=10;BYDAY=MO'
 * ];
 * $series = CalendarEvent::create($recurringData);
 *
 * // Reserve an appointment slot
 * $event = CalendarEvent::find(789);
 * $reservation = $event->reserve();
 * ```
 *
 * @package CanvasLMS\Api\CalendarEvents
 */
class CalendarEvent extends AbstractBaseApi
{
    /**
     * Calendar event unique identifier
     */
    public ?int $id = null;

    /**
     * Event title
     */
    public ?string $title = null;

    /**
     * Event description (HTML)
     */
    public ?string $description = null;

    /**
     * Start time
     */
    public ?DateTime $startAt = null;

    /**
     * End time
     */
    public ?DateTime $endAt = null;

    /**
     * Location name
     */
    public ?string $locationName = null;

    /**
     * Location address
     */
    public ?string $locationAddress = null;

    /**
     * Context code (e.g., "course_123", "user_456", "group_789")
     */
    public ?string $contextCode = null;

    /**
     * Effective context code
     */
    public ?string $effectiveContextCode = null;

    /**
     * All context codes (comma separated)
     */
    public ?string $allContextCodes = null;

    /**
     * Workflow state (active, deleted)
     */
    public ?string $workflowState = null;

    /**
     * Whether the event is hidden
     */
    public ?bool $hidden = null;

    /**
     * Parent event ID (for event series)
     */
    public ?int $parentEventId = null;

    /**
     * Number of child events
     */
    public ?int $childEventsCount = null;

    /**
     * Child events
     * @var array<mixed>
     */
    public array $childEvents = [];

    /**
     * URL to the event in Canvas API
     */
    public ?string $url = null;

    /**
     * URL to the event in Canvas web UI
     */
    public ?string $htmlUrl = null;

    /**
     * Created timestamp
     */
    public ?DateTime $createdAt = null;

    /**
     * Updated timestamp
     */
    public ?DateTime $updatedAt = null;

    /**
     * All day event flag
     */
    public ?bool $allDay = null;

    /**
     * All day date (for all-day events)
     */
    public ?string $allDayDate = null;

    /**
     * Event type (event, assignment, quiz, discussion_topic)
     */
    public ?string $type = null;

    /**
     * Appointment group ID
     */
    public ?int $appointmentGroupId = null;

    /**
     * Appointment group URL
     */
    public ?string $appointmentGroupUrl = null;

    /**
     * Whether this is user's own reservation
     */
    public ?bool $ownReservation = null;

    /**
     * Reserve URL for appointments
     */
    public ?string $reserveUrl = null;

    /**
     * Whether the appointment is reserved
     */
    public ?bool $reserved = null;

    /**
     * Participant type
     */
    public ?string $participantType = null;

    /**
     * Participants per appointment
     */
    public ?int $participantsPerAppointment = null;

    /**
     * Available slots
     */
    public ?int $availableSlots = null;

    /**
     * User associated with the event
     * @var mixed
     */
    public $user = null;

    /**
     * Group associated with the event
     * @var mixed
     */
    public $group = null;

    /**
     * Recurrence rule (RRULE format)
     */
    public ?string $rrule = null;

    /**
     * Series UUID for recurring events
     */
    public ?string $seriesUuid = null;

    /**
     * Series natural language description
     */
    public ?string $seriesNaturalLanguage = null;

    /**
     * Series head (first event in series)
     */
    public ?bool $seriesHead = null;

    /**
     * Duplicate options
     * @var array<string, mixed>
     */
    public array $duplicates = [];

    /**
     * Override event (for exceptions in series)
     */
    public ?bool $override = null;

    /**
     * Important dates flag
     */
    public ?bool $importantDates = null;

    /**
     * Blackout date flag
     */
    public ?bool $blackoutDate = null;

    /**
     * Constructor to handle date conversions
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $camelKey) && !is_null($value)) {
                $this->{$camelKey} = $this->castValue($camelKey, $value);
            }
        }
    }

    /**
     * Create a new calendar event
     * @param CreateCalendarEventDTO|array<string, mixed> $data
     * @return self
     * @throws CanvasApiException
     */
    public static function create(CreateCalendarEventDTO|array $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateCalendarEventDTO($data);
        }

        $response = self::$apiClient->post('/calendar_events', [
            'multipart' => $data->toApiArray()
        ]);

        $eventData = json_decode($response->getBody()->getContents(), true);
        return new self($eventData);
    }

    /**
     * Find a calendar event by ID
     * @param int $id
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id): self
    {
        self::checkApiClient();

        $response = self::$apiClient->get("/calendar_events/{$id}");

        $eventData = json_decode($response->getBody()->getContents(), true);
        return new self($eventData);
    }

    /**
     * List all calendar events
     * @param array<string, mixed> $params
     * @return array<self>
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();

        $response = self::$apiClient->get('/calendar_events', [
            'query' => $params
        ]);

        $eventsData = json_decode($response->getBody()->getContents(), true);
        return array_map(function ($event) {
            return new self($event);
        }, $eventsData);
    }

    /**
     * Get paginated calendar events
     * @param array<string, mixed> $params
     * @return array<self>
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): array
    {
        $paginatedResponse = self::getPaginatedResponse('/calendar_events', $params);
        return self::convertPaginatedResponseToModels($paginatedResponse);
    }

    /**
     * Fetch all pages of calendar events
     * @param array<string, mixed> $params
     * @return array<self>
     * @throws CanvasApiException
     */
    public static function fetchAllPages(array $params = []): array
    {
        return self::fetchAllPagesAsModels('/calendar_events', $params);
    }

    /**
     * Fetch a single page of calendar events
     * @param array<string, mixed> $params
     * @return PaginationResult
     * @throws CanvasApiException
     */
    public static function fetchPage(array $params = []): PaginationResult
    {
        $paginatedResponse = self::getPaginatedResponse('/calendar_events', $params);
        return self::createPaginationResult($paginatedResponse);
    }

    /**
     * List calendar events for a specific context
     * @param string $contextCode Context code (e.g., "course_123", "user_456", "group_789")
     * @param array<string, mixed> $params Additional parameters
     * @return array<self>
     * @throws CanvasApiException
     */
    public static function fetchAllForContext(string $contextCode, array $params = []): array
    {
        $params['context_codes'] = [$contextCode];
        return self::fetchAll($params);
    }

    /**
     * Create a calendar event for a specific context
     * @param string $contextCode Context code (e.g., "course_123", "user_456", "group_789")
     * @param array<string, mixed> $data Event data
     * @return self
     * @throws CanvasApiException
     */
    public static function createForContext(string $contextCode, array $data): self
    {
        $data['context_code'] = $contextCode;
        return self::create($data);
    }

    /**
     * Update a calendar event
     * @param int $id
     * @param UpdateCalendarEventDTO|array<string, mixed> $data
     * @return self
     * @throws CanvasApiException
     */
    public static function update(int $id, UpdateCalendarEventDTO|array $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateCalendarEventDTO($data);
        }

        $response = self::$apiClient->put("/calendar_events/{$id}", [
            'multipart' => $data->toApiArray()
        ]);

        $eventData = json_decode($response->getBody()->getContents(), true);
        return new self($eventData);
    }

    /**
     * Save the current calendar event
     * @return bool
     * @throws CanvasApiException
     */
    public function save(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Cannot save calendar event without ID');
        }

        $data = $this->toDtoArray();
        unset($data['id']);

        $response = self::$apiClient->put("/calendar_events/{$this->id}", [
            'multipart' => array_map(function ($key, $value) {
                if ($value instanceof DateTimeInterface) {
                    $value = $value->format(DateTimeInterface::ATOM);
                }
                return [
                    'name' => "calendar_event[{$key}]",
                    'contents' => (string) $value
                ];
            }, array_keys($data), $data)
        ]);

        $eventData = json_decode($response->getBody()->getContents(), true);
        $this->populate($eventData);

        return true;
    }

    /**
     * Delete a calendar event
     * @return bool
     * @throws CanvasApiException
     */
    public function delete(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Cannot delete calendar event without ID');
        }

        self::checkApiClient();

        self::$apiClient->delete("/calendar_events/{$this->id}");

        return true;
    }

    /**
     * Reserve an appointment slot
     * @param array<string, mixed> $params Optional parameters
     * @return self
     * @throws CanvasApiException
     */
    public function reserve(array $params = []): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Cannot reserve appointment without event ID');
        }

        self::checkApiClient();

        $response = self::$apiClient->post("/calendar_events/{$this->id}/reservations", [
            'form_params' => $params
        ]);

        $eventData = json_decode($response->getBody()->getContents(), true);
        return new self($eventData);
    }

    /**
     * Cancel a reservation
     * @return bool
     * @throws CanvasApiException
     */
    public function unreserve(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Cannot cancel reservation without event ID');
        }

        self::checkApiClient();

        self::$apiClient->delete("/calendar_events/{$this->id}/reservations");

        return true;
    }

    /**
     * Duplicate this calendar event
     * @param array<string, mixed> $data Override data for the duplicate
     * @return self
     * @throws CanvasApiException
     */
    public function duplicate(array $data = []): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Cannot duplicate event without ID');
        }

        // Start with current event data
        $duplicateData = $this->toDtoArray();
        unset($duplicateData['id']);

        // Apply overrides
        foreach ($data as $key => $value) {
            $duplicateData[$key] = $value;
        }

        return self::create($duplicateData);
    }

    /**
     * Create a series of recurring events
     * @param array<DateTime> $dates Array of dates for the series
     * @return array<self>
     * @throws CanvasApiException
     */
    public function createSeries(array $dates): array
    {
        if (!$this->contextCode) {
            throw new CanvasApiException('Cannot create series without context code');
        }

        $baseData = $this->toDtoArray();
        unset($baseData['id'], $baseData['startAt'], $baseData['endAt']);

        $events = [];
        $duration = null;

        // Calculate duration if both start and end times exist
        if ($this->startAt && $this->endAt) {
            $duration = $this->endAt->getTimestamp() - $this->startAt->getTimestamp();
        }

        foreach ($dates as $date) {
            $eventData = $baseData;
            $eventData['start_at'] = $date;

            if ($duration !== null) {
                $endDate = clone $date;
                $endDate->setTimestamp($date->getTimestamp() + $duration);
                $eventData['end_at'] = $endDate;
            }

            $events[] = self::create($eventData);
        }

        return $events;
    }

    /**
     * Get the calendar event's context type
     * @return string|null
     */
    public function getContextType(): ?string
    {
        if (!$this->contextCode) {
            return null;
        }

        $parts = explode('_', $this->contextCode);
        return $parts[0] ?? null;
    }

    /**
     * Get the calendar event's context ID
     * @return int|null
     */
    public function getContextId(): ?int
    {
        if (!$this->contextCode) {
            return null;
        }

        $parts = explode('_', $this->contextCode);
        return isset($parts[1]) ? (int) $parts[1] : null;
    }

    /**
     * Check if event is active
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->workflowState === 'active';
    }

    /**
     * Check if event has location
     * @return bool
     */
    public function hasLocation(): bool
    {
        return !empty($this->locationName) || !empty($this->locationAddress);
    }

    /**
     * Check if event is all day
     * @return bool
     */
    public function isAllDay(): bool
    {
        return $this->allDay === true;
    }

    /**
     * Check if event is part of a series
     * @return bool
     */
    public function isPartOfSeries(): bool
    {
        return !empty($this->seriesUuid) || !empty($this->rrule) || !empty($this->parentEventId);
    }

    /**
     * Check if event is an appointment
     * @return bool
     */
    public function isAppointment(): bool
    {
        return !empty($this->appointmentGroupId);
    }

    /**
     * Check if event has started
     * @return bool
     */
    public function hasStarted(): bool
    {
        if (!$this->startAt) {
            return false;
        }

        return $this->startAt <= new DateTime();
    }

    /**
     * Check if event has ended
     * @return bool
     */
    public function hasEnded(): bool
    {
        if (!$this->endAt) {
            return false;
        }

        return $this->endAt <= new DateTime();
    }

    /**
     * Check if event is ongoing
     * @return bool
     */
    public function isOngoing(): bool
    {
        return $this->hasStarted() && !$this->hasEnded();
    }

    /**
     * Set the event title
     * @param string $title
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the event description
     * @param string $description
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set the event start time
     * @param DateTime $startAt
     * @return self
     */
    public function setStartAt(DateTime $startAt): self
    {
        $this->startAt = $startAt;
        return $this;
    }

    /**
     * Set the event end time
     * @param DateTime $endAt
     * @return self
     */
    public function setEndAt(DateTime $endAt): self
    {
        $this->endAt = $endAt;
        return $this;
    }

    /**
     * Set the event location name
     * @param string $locationName
     * @return self
     */
    public function setLocationName(string $locationName): self
    {
        $this->locationName = $locationName;
        return $this;
    }

    /**
     * Set the event location address
     * @param string $locationAddress
     * @return self
     */
    public function setLocationAddress(string $locationAddress): self
    {
        $this->locationAddress = $locationAddress;
        return $this;
    }

    /**
     * Cast a value to the correct type
     * @param string $key
     * @param mixed $value
     * @return DateTime|mixed
     * @throws \Exception
     */
    protected function castValue(string $key, mixed $value): mixed
    {
        if (in_array($key, ['startAt', 'endAt', 'createdAt', 'updatedAt']) && is_string($value)) {
            return new DateTime($value);
        }

        $booleanFields = [
            'hidden', 'allDay', 'ownReservation', 'reserved',
            'seriesHead', 'override', 'importantDates', 'blackoutDate'
        ];
        if (in_array($key, $booleanFields, true) && !is_null($value)) {
            return (bool) $value;
        }

        $integerFields = [
            'id', 'parentEventId', 'childEventsCount',
            'appointmentGroupId', 'participantsPerAppointment', 'availableSlots'
        ];
        if (in_array($key, $integerFields, true) && !is_null($value)) {
            return (int) $value;
        }

        return $value;
    }
}
