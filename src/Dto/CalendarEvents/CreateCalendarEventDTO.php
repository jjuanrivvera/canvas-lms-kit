<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\CalendarEvents;

use CanvasLMS\Dto\AbstractBaseDto;
use DateTime;

/**
 * CreateCalendarEventDTO
 *
 * Data Transfer Object for creating calendar events in Canvas LMS.
 * Handles the transformation of calendar event data into the multipart
 * format expected by the Canvas API.
 *
 * @package CanvasLMS\Dto\CalendarEvents
 */
class CreateCalendarEventDTO extends AbstractBaseDto
{
    /**
     * Context code of the course, group, user, or account whose calendar this event should be added to
     * Format: {type}_{id} (e.g., 'course_123', 'user_456', 'group_789', 'account_1')
     *
     * @var string
     */
    public string $contextCode;

    /**
     * Short title for the calendar event
     *
     * @var string|null
     */
    public ?string $title = null;

    /**
     * Longer HTML description of the event
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Start date/time of the event
     *
     * @var DateTime|null
     */
    public ?DateTime $startAt = null;

    /**
     * End date/time of the event
     *
     * @var DateTime|null
     */
    public ?DateTime $endAt = null;

    /**
     * Location name of the event
     *
     * @var string|null
     */
    public ?string $locationName = null;

    /**
     * Location address
     *
     * @var string|null
     */
    public ?string $locationAddress = null;

    /**
     * Time zone of the user editing the event
     * Allowed time zones are IANA time zones or friendlier Ruby on Rails time zones
     *
     * @var string|null
     */
    public ?string $timeZoneEdited = null;

    /**
     * When true, event is considered to span the whole day and times are ignored
     *
     * @var bool|null
     */
    public ?bool $allDay = null;

    /**
     * Section-level child event data for course events
     * Format: ['X' => ['start_at' => DateTime, 'end_at' => DateTime, 'context_code' => string]]
     *
     * @var array<string, array{start_at?: DateTime, end_at?: DateTime, context_code?: string}>|null
     */
    public ?array $childEventData = null;

    /**
     * Duplication settings
     *
     * @var array{count?: int, interval?: int, frequency?: string, append_iterator?: bool}|null
     */
    public ?array $duplicate = null;

    /**
     * The recurrence rule to create a series of recurring events
     * iCalendar RRULE defining how the event repeats (unending series not supported)
     *
     * @var string|null
     */
    public ?string $rrule = null;

    /**
     * If true, this event represents a holiday or special day that does not count in course pacing
     *
     * @var bool|null
     */
    public ?bool $blackoutDate = null;

    /**
     * Convert DTO to API-compatible array format
     *
     * @return array<int, array{name: string, contents: string}>
     */
    public function toApiArray(): array
    {
        $data = [];

        // Required field
        $data[] = [
            'name' => 'calendar_event[context_code]',
            'contents' => $this->contextCode,
        ];

        // Optional fields
        if ($this->title !== null) {
            $data[] = [
                'name' => 'calendar_event[title]',
                'contents' => $this->title,
            ];
        }

        if ($this->description !== null) {
            $data[] = [
                'name' => 'calendar_event[description]',
                'contents' => $this->description,
            ];
        }

        if ($this->startAt !== null) {
            $data[] = [
                'name' => 'calendar_event[start_at]',
                'contents' => $this->startAt->format(DateTime::ATOM),
            ];
        }

        if ($this->endAt !== null) {
            $data[] = [
                'name' => 'calendar_event[end_at]',
                'contents' => $this->endAt->format(DateTime::ATOM),
            ];
        }

        if ($this->locationName !== null) {
            $data[] = [
                'name' => 'calendar_event[location_name]',
                'contents' => $this->locationName,
            ];
        }

        if ($this->locationAddress !== null) {
            $data[] = [
                'name' => 'calendar_event[location_address]',
                'contents' => $this->locationAddress,
            ];
        }

        if ($this->timeZoneEdited !== null) {
            $data[] = [
                'name' => 'calendar_event[time_zone_edited]',
                'contents' => $this->timeZoneEdited,
            ];
        }

        if ($this->allDay !== null) {
            $data[] = [
                'name' => 'calendar_event[all_day]',
                'contents' => $this->allDay ? '1' : '0',
            ];
        }

        // Handle child event data
        if ($this->childEventData !== null) {
            foreach ($this->childEventData as $identifier => $childData) {
                if (isset($childData['start_at']) && $childData['start_at'] instanceof DateTime) {
                    $data[] = [
                        'name' => "calendar_event[child_event_data][$identifier][start_at]",
                        'contents' => $childData['start_at']->format(DateTime::ATOM),
                    ];
                }

                if (isset($childData['end_at']) && $childData['end_at'] instanceof DateTime) {
                    $data[] = [
                        'name' => "calendar_event[child_event_data][$identifier][end_at]",
                        'contents' => $childData['end_at']->format(DateTime::ATOM),
                    ];
                }

                if (isset($childData['context_code'])) {
                    $data[] = [
                        'name' => "calendar_event[child_event_data][$identifier][context_code]",
                        'contents' => $childData['context_code'],
                    ];
                }
            }
        }

        // Handle duplication settings
        if ($this->duplicate !== null) {
            if (isset($this->duplicate['count'])) {
                $data[] = [
                    'name' => 'calendar_event[duplicate][count]',
                    'contents' => (string) $this->duplicate['count'],
                ];
            }

            if (isset($this->duplicate['interval'])) {
                $data[] = [
                    'name' => 'calendar_event[duplicate][interval]',
                    'contents' => (string) $this->duplicate['interval'],
                ];
            }

            if (isset($this->duplicate['frequency'])) {
                $data[] = [
                    'name' => 'calendar_event[duplicate][frequency]',
                    'contents' => $this->duplicate['frequency'],
                ];
            }

            if (isset($this->duplicate['append_iterator'])) {
                $data[] = [
                    'name' => 'calendar_event[duplicate][append_iterator]',
                    'contents' => $this->duplicate['append_iterator'] ? '1' : '0',
                ];
            }
        }

        if ($this->rrule !== null) {
            $data[] = [
                'name' => 'calendar_event[rrule]',
                'contents' => $this->rrule,
            ];
        }

        if ($this->blackoutDate !== null) {
            $data[] = [
                'name' => 'calendar_event[blackout_date]',
                'contents' => $this->blackoutDate ? '1' : '0',
            ];
        }

        return $data;
    }
}
