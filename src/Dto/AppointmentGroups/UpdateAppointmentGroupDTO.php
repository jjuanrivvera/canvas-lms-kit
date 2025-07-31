<?php

namespace CanvasLMS\Dto\AppointmentGroups;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * UpdateAppointmentGroupDTO
 *
 * Data Transfer Object for updating appointment groups in Canvas LMS.
 * Handles the transformation of appointment group update data into the multipart
 * format expected by the Canvas API.
 *
 * @package CanvasLMS\Dto\AppointmentGroups
 */
class UpdateAppointmentGroupDTO extends AbstractBaseDto
{
    /**
     * Array of context codes (courses, e.g. course_1) this group should be linked to
     * Users in the course(s) with appropriate permissions will be able to sign up
     * @var array<string>|null
     */
    public ?array $contextCodes = null;

    /**
     * Array of sub context codes (course sections or a single group category)
     * Used to limit the appointment group to particular sections
     * @var array<string>|null
     */
    public ?array $subContextCodes = null;

    /**
     * Short title for the appointment group
     * @var string|null
     */
    public ?string $title = null;

    /**
     * Longer text description of the appointment group
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Location name of the appointment group
     * @var string|null
     */
    public ?string $locationName = null;

    /**
     * Location address
     * @var string|null
     */
    public ?string $locationAddress = null;

    /**
     * Whether this appointment group should be published (made available for signup)
     * Once published, an appointment group cannot be unpublished
     * @var bool|null
     */
    public ?bool $publish = null;

    /**
     * Maximum number of participants that may register for each time slot
     * Defaults to null (no limit)
     * @var int|null
     */
    public ?int $participantsPerAppointment = null;

    /**
     * Minimum number of time slots a user must register for
     * If not set, users do not need to sign up for any time slots
     * @var int|null
     */
    public ?int $minAppointmentsPerParticipant = null;

    /**
     * Maximum number of time slots a user may register for
     * @var int|null
     */
    public ?int $maxAppointmentsPerParticipant = null;

    /**
     * Nested array of start time/end time pairs indicating time slots
     * Format: [['start_at' => '2012-07-19T21:00:00Z', 'end_at' => '2012-07-19T22:00:00Z'], ...]
     * @var array<int, array{start_at?: string, end_at?: string}>|null
     */
    public ?array $newAppointments = null;

    /**
     * 'private' - participants cannot see who has signed up for a particular time slot
     * 'protected' - participants can see who has signed up
     * @var string|null
     */
    public ?string $participantVisibility = null;

    /**
     * Whether observer users can sign-up for an appointment
     * @var bool|null
     */
    public ?bool $allowObserverSignup = null;

    /**
     * Convert DTO to API-compatible array format
     *
     * @return array<int, array{name: string, contents: string}>
     */
    public function toApiArray(): array
    {
        $data = [];

        // All fields are optional for updates
        if ($this->contextCodes !== null) {
            foreach ($this->contextCodes as $index => $code) {
                $data[] = [
                    'name' => "appointment_group[context_codes][$index]",
                    'contents' => $code
                ];
            }
        }

        if ($this->title !== null) {
            $data[] = [
                'name' => 'appointment_group[title]',
                'contents' => $this->title
            ];
        }

        if ($this->subContextCodes !== null) {
            foreach ($this->subContextCodes as $index => $code) {
                $data[] = [
                    'name' => "appointment_group[sub_context_codes][$index]",
                    'contents' => $code
                ];
            }
        }

        if ($this->description !== null) {
            $data[] = [
                'name' => 'appointment_group[description]',
                'contents' => $this->description
            ];
        }

        if ($this->locationName !== null) {
            $data[] = [
                'name' => 'appointment_group[location_name]',
                'contents' => $this->locationName
            ];
        }

        if ($this->locationAddress !== null) {
            $data[] = [
                'name' => 'appointment_group[location_address]',
                'contents' => $this->locationAddress
            ];
        }

        if ($this->publish !== null) {
            $data[] = [
                'name' => 'appointment_group[publish]',
                'contents' => $this->publish ? '1' : '0'
            ];
        }

        if ($this->participantsPerAppointment !== null) {
            $data[] = [
                'name' => 'appointment_group[participants_per_appointment]',
                'contents' => (string)$this->participantsPerAppointment
            ];
        }

        if ($this->minAppointmentsPerParticipant !== null) {
            $data[] = [
                'name' => 'appointment_group[min_appointments_per_participant]',
                'contents' => (string)$this->minAppointmentsPerParticipant
            ];
        }

        if ($this->maxAppointmentsPerParticipant !== null) {
            $data[] = [
                'name' => 'appointment_group[max_appointments_per_participant]',
                'contents' => (string)$this->maxAppointmentsPerParticipant
            ];
        }

        // Handle new appointments array
        if ($this->newAppointments !== null) {
            foreach ($this->newAppointments as $index => $appointment) {
                if (isset($appointment['start_at'])) {
                    $data[] = [
                        'name' => "appointment_group[new_appointments][$index][0]",
                        'contents' => $appointment['start_at']
                    ];
                }
                if (isset($appointment['end_at'])) {
                    $data[] = [
                        'name' => "appointment_group[new_appointments][$index][1]",
                        'contents' => $appointment['end_at']
                    ];
                }
            }
        }

        if ($this->participantVisibility !== null) {
            $data[] = [
                'name' => 'appointment_group[participant_visibility]',
                'contents' => $this->participantVisibility
            ];
        }

        if ($this->allowObserverSignup !== null) {
            $data[] = [
                'name' => 'appointment_group[allow_observer_signup]',
                'contents' => $this->allowObserverSignup ? '1' : '0'
            ];
        }

        return $data;
    }
}
