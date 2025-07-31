<?php

namespace CanvasLMS\Api\CalendarEvents;

use DateTime;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Dto\CalendarEvents\CreateCalendarEventDTO;
use CanvasLMS\Dto\CalendarEvents\UpdateCalendarEventDTO;
use CanvasLMS\Dto\CalendarEvents\CreateReservationDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;

/**
 * CalendarEvent Class
 *
 * Represents a calendar event in Canvas LMS. Calendar events enable course scheduling,
 * assignment due dates, appointment slots, and general event management. Events can be
 * associated with different contexts (courses, users, groups, accounts) and support
 * recurring patterns, reservations, and time zone handling.
 *
 * Canvas calendar events are versatile and can represent:
 * - Regular calendar events (meetings, deadlines, etc.)
 * - Assignment due dates
 * - Time slots for appointment groups
 * - Reservations for appointment slots
 * - Blackout dates for course pacing
 *
 * Usage:
 *
 * ```php
 * // Creating a calendar event directly
 * $dto = new CreateCalendarEventDTO();
 * $dto->contextCode = 'course_123';
 * $dto->title = 'Midterm Exam';
 * $dto->startAt = new DateTime('2025-03-15 10:00:00');
 * $dto->endAt = new DateTime('2025-03-15 12:00:00');
 * $event = CalendarEvent::create($dto);
 *
 * // Finding a calendar event
 * $event = CalendarEvent::find(456);
 *
 * // Listing all calendar events with filters
 * $events = CalendarEvent::fetchAll([
 *     'start_date' => '2025-03-01',
 *     'end_date' => '2025-03-31',
 *     'context_codes' => ['course_123', 'user_456']
 * ]);
 *
 * // Updating an event
 * $updateDto = new UpdateCalendarEventDTO();
 * $updateDto->title = 'Midterm Exam - Room Changed';
 * $updateDto->locationName = 'Room 301';
 * $event = CalendarEvent::update(456, $updateDto);
 *
 * // Working with recurring events
 * $dto->rrule = 'FREQ=WEEKLY;COUNT=10';
 * $recurringEvent = CalendarEvent::create($dto);
 *
 * // Updating a series
 * $recurringEvent->updateSeries($updateDto, 'following');
 *
 * // Making a reservation
 * $reservation = new CreateReservationDTO();
 * $reservation->participantId = 789;
 * $reservation->comments = 'Looking forward to the meeting';
 * $event->reserve($reservation);
 * ```
 *
 * @see https://canvas.instructure.com/doc/api/calendar_events.html
 *
 * @package CanvasLMS\Api\CalendarEvents
 */
class CalendarEvent extends AbstractBaseApi
{
    /**
     * The ID of the calendar event
     * @var int|null
     */
    public ?int $id = null;

    /**
     * The title of the calendar event
     * @var string|null
     */
    public ?string $title = null;

    /**
     * The start timestamp of the event
     * @var DateTime|null
     */
    public ?DateTime $startAt = null;

    /**
     * The end timestamp of the event
     * @var DateTime|null
     */
    public ?DateTime $endAt = null;

    /**
     * The HTML description of the event
     * @var string|null
     */
    public ?string $description = null;

    /**
     * The location name of the event
     * @var string|null
     */
    public ?string $locationName = null;

    /**
     * The address where the event is taking place
     * @var string|null
     */
    public ?string $locationAddress = null;

    /**
     * The context code of the calendar this event belongs to
     * Format: {type}_{id} (e.g., course_123, user_456, group_789, account_1)
     * @var string|null
     */
    public ?string $contextCode = null;

    /**
     * If specified, indicates which calendar this event should be displayed on
     * (e.g., a section-level event would have the course's context code here)
     * @var string|null
     */
    public ?string $effectiveContextCode = null;

    /**
     * The context name of the calendar this event belongs to
     * @var string|null
     */
    public ?string $contextName = null;

    /**
     * A comma-separated list of all calendar contexts this event is part of
     * @var string|null
     */
    public ?string $allContextCodes = null;

    /**
     * Current state of the event ('active', 'locked' or 'deleted')
     * 'locked' indicates that start_at/end_at cannot be changed
     * @var string|null
     */
    public ?string $workflowState = null;

    /**
     * Whether this event should be displayed on the calendar
     * Only true for course-level events with section-level child events
     * @var bool|null
     */
    public ?bool $hidden = null;

    /**
     * If this is a reservation, the id will indicate the time slot it is for
     * If this is a section-level event, this will be the course-level parent event
     * @var int|null
     */
    public ?int $parentEventId = null;

    /**
     * The number of child_events
     * @var int|null
     */
    public ?int $childEventsCount = null;

    /**
     * If this is a time slot, this will be a list of any reservations
     * If this is a course-level event, this will be a list of section-level events
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $childEvents = null;

    /**
     * URL for this calendar event (to update, delete, etc.)
     * @var string|null
     */
    public ?string $url = null;

    /**
     * URL for a user to view this event
     * @var string|null
     */
    public ?string $htmlUrl = null;

    /**
     * The date of this event (YYYY-MM-DD format)
     * @var string|null
     */
    public ?string $allDayDate = null;

    /**
     * Boolean indicating whether this is an all-day event (midnight to midnight)
     * @var bool|null
     */
    public ?bool $allDay = null;

    /**
     * When the calendar event was created
     * @var DateTime|null
     */
    public ?DateTime $createdAt = null;

    /**
     * When the calendar event was last updated
     * @var DateTime|null
     */
    public ?DateTime $updatedAt = null;

    /**
     * The id of the appointment group
     * @var int|null
     */
    public ?int $appointmentGroupId = null;

    /**
     * The API URL of the appointment group
     * @var string|null
     */
    public ?string $appointmentGroupUrl = null;

    /**
     * If the event is a reservation, whether it is the current user's reservation
     * @var bool|null
     */
    public ?bool $ownReservation = null;

    /**
     * If the event is a time slot, the API URL for reserving it
     * @var string|null
     */
    public ?string $reserveUrl = null;

    /**
     * If the event is a time slot, whether the user has already made a reservation
     * @var bool|null
     */
    public ?bool $reserved = null;

    /**
     * The type of participant to sign up for a slot: 'User' or 'Group'
     * @var string|null
     */
    public ?string $participantType = null;

    /**
     * If the event is a time slot, this is the participant limit
     * @var int|null
     */
    public ?int $participantsPerAppointment = null;

    /**
     * If the event is a time slot with a limit, how many slots are available
     * @var int|null
     */
    public ?int $availableSlots = null;

    /**
     * If the event is a user-level reservation, contains the user participant
     * @var array<string, mixed>|null
     */
    public ?array $user = null;

    /**
     * If the event is a group-level reservation, contains the group participant
     * @var array<string, mixed>|null
     */
    public ?array $group = null;

    /**
     * Boolean indicating whether this has important dates
     * @var bool|null
     */
    public ?bool $importantDates = null;

    /**
     * Identifies the recurring event series this event may belong to
     * @var string|null
     */
    public ?string $seriesUuid = null;

    /**
     * An iCalendar RRULE for defining how events in a recurring event series repeat
     * @var string|null
     */
    public ?string $rrule = null;

    /**
     * Boolean indicating if this is the first event in the series of recurring events
     * @var bool|null
     */
    public ?bool $seriesHead = null;

    /**
     * A natural language expression of how events occur in the series
     * @var string|null
     */
    public ?string $seriesNaturalLanguage = null;

    /**
     * Boolean indicating whether this has blackout date
     * @var bool|null
     */
    public ?bool $blackoutDate = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $key) && !is_null($value)) {
                // Use the magic setter to ensure proper casting
                $this->__set($key, $value);
            }
        }
    }

    /**
     * Create a new calendar event
     *
     * @param array<string, mixed>|CreateCalendarEventDTO $data The event data
     * @return self
     * @throws CanvasApiException
     */
    public static function create(array|CreateCalendarEventDTO $data): self
    {
        if (is_array($data)) {
            $data = new CreateCalendarEventDTO($data);
        }

        self::checkApiClient();
        $response = self::$apiClient->post('calendar_events', ['multipart' => $data->toApiArray()]);
        $responseData = json_decode($response->getBody(), true);
        return new self($responseData);
    }

    /**
     * Find a calendar event by ID
     *
     * @param int $id The calendar event ID
     * @param array<string, mixed> $params Query parameters
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkApiClient();
        $endpoint = sprintf('calendar_events/%d', $id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode($response->getBody(), true);
        return new self($data);
    }

    /**
     * Update a calendar event
     *
     * @param int $id The calendar event ID
     * @param array<string, mixed>|UpdateCalendarEventDTO $updateData The update data
     * @param array<string, mixed> $params Additional parameters (e.g., 'which' for series)
     * @return self
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateCalendarEventDTO $updateData, array $params = []): self
    {
        if (is_array($updateData)) {
            $updateData = new UpdateCalendarEventDTO($updateData);
        }

        self::checkApiClient();
        $endpoint = sprintf('calendar_events/%d', $id);

        // Merge params into the DTO array if needed
        $data = $updateData->toApiArray();

        // Whitelist allowed parameters for security
        $allowedParams = ['which'];

        if (!empty($params)) {
            foreach ($params as $key => $value) {
                if (!in_array($key, $allowedParams)) {
                    $allowed = implode(', ', $allowedParams);
                    throw new CanvasApiException("Invalid parameter '$key'. Allowed parameters: $allowed");
                }

                $data[] = [
                    'name' => $key,
                    'contents' => (string)$value
                ];
            }
        }

        $response = self::$apiClient->put($endpoint, $data);
        $responseData = json_decode($response->getBody(), true);
        return new self($responseData);
    }

    /**
     * Delete a calendar event
     *
     * @param array<string, mixed> $params Parameters like 'cancel_reason', 'which'
     * @return bool
     * @throws CanvasApiException
     */
    public function delete(array $params = []): bool
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot delete calendar event without ID");
        }

        self::checkApiClient();
        $endpoint = sprintf('calendar_events/%d', $this->id);

        $queryParams = [];
        if (!empty($params)) {
            $queryParams['query'] = $params;
        }

        self::$apiClient->delete($endpoint, $queryParams);
        return true;
    }

    /**
     * List calendar events
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        self::checkApiClient();
        $response = self::$apiClient->get('calendar_events', ['query' => $params]);
        $data = json_decode($response->getBody(), true);

        return array_map(function ($item) {
            return new self($item);
        }, $data);
    }

    /**
     * Get paginated calendar events
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public static function fetchAllPaginated(array $params = []): PaginatedResponse
    {
        self::checkApiClient();
        return self::getPaginatedResponse('calendar_events', $params);
    }

    /**
     * Save the calendar event (create or update)
     *
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        if ($this->id) {
            // Update existing event
            $data = [];

            // Map properties to DTO data
            $properties = [
                'contextCode', 'title', 'description', 'startAt', 'endAt',
                'locationName', 'locationAddress', 'allDay', 'blackoutDate'
            ];

            foreach ($properties as $property) {
                if (property_exists($this, $property) && $this->{$property} !== null) {
                    $data[$property] = $this->{$property};
                }
            }

            $dto = new UpdateCalendarEventDTO($data);

            $updated = self::update($this->id, $dto);

            // Update current instance with response data
            foreach (get_object_vars($updated) as $key => $value) {
                $this->{$key} = $value;
            }

            return $this;
        } else {
            // Create new event
            $dto = new CreateCalendarEventDTO([]);

            // Context code is required for creation
            if (!$this->contextCode) {
                throw new CanvasApiException("Context code is required to create a calendar event");
            }

            // Map properties to DTO
            $properties = [
                'contextCode', 'title', 'description', 'startAt', 'endAt',
                'locationName', 'locationAddress', 'allDay', 'rrule', 'blackoutDate'
            ];

            foreach ($properties as $property) {
                if (
                    property_exists($this, $property) &&
                    property_exists($dto, $property) &&
                    $this->{$property} !== null
                ) {
                    $dto->{$property} = $this->{$property};
                }
            }

            $created = self::create($dto);

            // Update current instance with response data
            foreach (get_object_vars($created) as $key => $value) {
                $this->{$key} = $value;
            }

            return $this;
        }
    }

    /**
     * Reserve a time slot
     *
     * @param array<string, mixed>|CreateReservationDTO $data Reservation data
     * @return self
     * @throws CanvasApiException
     */
    public function reserve(array|CreateReservationDTO $data): self
    {
        if (is_array($data)) {
            $data = new CreateReservationDTO($data);
        }

        if (!$this->id) {
            throw new CanvasApiException("Cannot reserve without event ID");
        }

        return self::reserveSlot($this->id, $data, $data->participantId);
    }

    /**
     * Reserve a time slot (static method)
     *
     * @param int $eventId The calendar event ID
     * @param array<string, mixed>|CreateReservationDTO $data Reservation data
     * @param int|null $participantId Optional participant ID
     * @return self
     * @throws CanvasApiException
     */
    public static function reserveSlot(int $eventId, array|CreateReservationDTO $data, ?int $participantId = null): self
    {
        if (is_array($data)) {
            $data = new CreateReservationDTO($data);
        }

        self::checkApiClient();

        $endpoint = $participantId
            ? sprintf('calendar_events/%d/reservations/%d', $eventId, $participantId)
            : sprintf('calendar_events/%d/reservations', $eventId);

        $response = self::$apiClient->post($endpoint, ['multipart' => $data->toApiArray()]);
        $responseData = json_decode($response->getBody(), true);
        return new self($responseData);
    }

    /**
     * Save enabled account calendars
     *
     * @param array<int> $accountIds Array of account IDs
     * @param bool $markAsSeen Whether to mark the feature as seen
     * @return array<string, mixed>
     * @throws CanvasApiException
     */
    public static function saveEnabledAccountCalendars(array $accountIds, bool $markAsSeen = false): array
    {
        self::checkApiClient();

        $data = [];
        if ($markAsSeen) {
            $data[] = [
                'name' => 'mark_feature_as_seen',
                'contents' => '1'
            ];
        }

        foreach ($accountIds as $index => $accountId) {
            $data[] = [
                'name' => "enabled_account_calendars[$index]",
                'contents' => (string)$accountId
            ];
        }

        $response = self::$apiClient->post('calendar_events/save_enabled_account_calendars', $data);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get next available appointment
     *
     * @param array<int> $appointmentGroupIds Optional array of appointment group IDs to search
     * @return self|null
     * @throws CanvasApiException
     */
    public static function getNextAvailableAppointment(array $appointmentGroupIds = []): ?self
    {
        self::checkApiClient();

        $params = [];
        if (!empty($appointmentGroupIds)) {
            $params['appointment_group_ids'] = $appointmentGroupIds;
        }

        $response = self::$apiClient->get('appointment_groups/next_appointment', ['query' => $params]);
        $data = json_decode($response->getBody(), true);

        if (empty($data)) {
            return null;
        }

        // Response is an array with one element
        return new self($data[0]);
    }

    /**
     * Update a series of recurring events
     *
     * @param array<string, mixed>|UpdateCalendarEventDTO $updateData Update data
     * @param string $which Which events to update: 'one', 'all', 'following'
     * @return self
     * @throws CanvasApiException
     */
    public function updateSeries(array|UpdateCalendarEventDTO $updateData, string $which = 'one'): self
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot update series without event ID");
        }

        if (!in_array($which, ['one', 'all', 'following'])) {
            throw new CanvasApiException("Invalid 'which' parameter. Must be 'one', 'all', or 'following'");
        }

        return self::update($this->id, $updateData, ['which' => $which]);
    }

    /**
     * Delete a series of recurring events
     *
     * @param string $which Which events to delete: 'one', 'all', 'following'
     * @param string|null $cancelReason Optional reason for cancellation
     * @return bool
     * @throws CanvasApiException
     */
    public function deleteSeries(string $which = 'one', ?string $cancelReason = null): bool
    {
        if (!in_array($which, ['one', 'all', 'following'])) {
            throw new CanvasApiException("Invalid 'which' parameter. Must be 'one', 'all', or 'following'");
        }

        $params = ['which' => $which];
        if ($cancelReason !== null) {
            $params['cancel_reason'] = $cancelReason;
        }

        return $this->delete($params);
    }

    /**
     * Parse a context code into type and ID
     *
     * @param string $contextCode Context code (e.g., 'course_123')
     * @return array{type: string, id: int}
     * @throws CanvasApiException
     */
    public static function parseContextCode(string $contextCode): array
    {
        $parts = explode('_', $contextCode, 2);
        if (count($parts) !== 2) {
            throw new CanvasApiException("Invalid context code format: $contextCode");
        }

        return [
            'type' => $parts[0],
            'id' => (int)$parts[1]
        ];
    }

    /**
     * Cast value to appropriate type based on property
     *
     * @param string $key Property name
     * @param mixed $value Value to cast
     * @return mixed
     */
    protected function castValue(string $key, mixed $value): mixed
    {
        $dateTimeFields = ['startAt', 'endAt', 'createdAt', 'updatedAt'];

        if (in_array($key, $dateTimeFields) && is_string($value) && !empty($value)) {
            try {
                return new DateTime($value);
            } catch (\Exception $e) {
                // Log the parsing error for debugging
                error_log(sprintf(
                    'CalendarEvent: Failed to parse DateTime for field "%s" with value "%s": %s',
                    $key,
                    $value,
                    $e->getMessage()
                ));

                // Return null for invalid dates to maintain consistency
                return null;
            }
        }

        return $value;
    }

    /**
     * Magic setter to handle property casting
     *
     * @param string $name Property name
     * @param mixed $value Property value
     */
    public function __set($name, $value)
    {
        $this->{$name} = $this->castValue($name, $value);
    }
}
