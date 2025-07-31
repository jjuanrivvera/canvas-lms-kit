<?php

namespace CanvasLMS\Dto\CalendarEvents;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * CreateReservationDTO
 *
 * Data Transfer Object for creating reservations on calendar event time slots.
 * Used when reserving appointment slots created by appointment groups.
 *
 * @package CanvasLMS\Dto\CalendarEvents
 */
class CreateReservationDTO extends AbstractBaseDto
{
    /**
     * User or group id for whom you are making the reservation
     * Depends on the participant type of the appointment group
     * Defaults to the current user (or user's candidate group)
     * @var int|null
     */
    public ?int $participantId = null;

    /**
     * Comments to associate with this reservation
     * @var string|null
     */
    public ?string $comments = null;

    /**
     * Defaults to false
     * If true, cancel any previous reservation(s) for this participant and appointment group
     * @var bool|null
     */
    public ?bool $cancelExisting = null;

    /**
     * Convert DTO to API-compatible array format
     *
     * @return array<int, array{name: string, contents: string}>
     */
    public function toApiArray(): array
    {
        $data = [];

        // Note: participant_id is typically passed in the URL, not the body
        // But including it here in case it's needed for the non-participant-specific endpoint
        if ($this->participantId !== null) {
            $data[] = [
                'name' => 'participant_id',
                'contents' => (string)$this->participantId
            ];
        }

        if ($this->comments !== null) {
            $data[] = [
                'name' => 'comments',
                'contents' => $this->comments
            ];
        }

        if ($this->cancelExisting !== null) {
            $data[] = [
                'name' => 'cancel_existing',
                'contents' => $this->cancelExisting ? '1' : '0'
            ];
        }

        return $data;
    }
}
