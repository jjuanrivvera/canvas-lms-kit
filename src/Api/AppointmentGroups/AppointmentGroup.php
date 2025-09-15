<?php

namespace CanvasLMS\Api\AppointmentGroups;

use DateTime;
use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\CalendarEvents\CalendarEvent;
use CanvasLMS\Dto\AppointmentGroups\CreateAppointmentGroupDTO;
use CanvasLMS\Dto\AppointmentGroups\UpdateAppointmentGroupDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Pagination\PaginatedResponse;

/**
 * AppointmentGroup Class
 *
 * Represents an appointment group in Canvas LMS. Appointment groups provide a way of
 * creating a bundle of time slots that users can sign up for (e.g., "Office Hours" or
 * "Meet with professor about Final Project"). Both time slots and reservations of time
 * slots are stored as Calendar Events.
 *
 * Appointment groups enable:
 * - Creating multiple time slots at once
 * - Setting participant limits per slot
 * - Restricting sign-ups to specific courses or sections
 * - Managing visibility of participant information
 * - Supporting both individual and group sign-ups
 *
 * Usage:
 *
 * ```php
 * // Creating an appointment group
 * $dto = new CreateAppointmentGroupDTO();
 * $dto->contextCodes = ['course_123'];
 * $dto->title = 'Office Hours';
 * $dto->description = 'Weekly office hours for questions';
 * $dto->newAppointments = [
 *     ['start_at' => '2025-03-15T10:00:00Z', 'end_at' => '2025-03-15T10:30:00Z'],
 *     ['start_at' => '2025-03-15T10:30:00Z', 'end_at' => '2025-03-15T11:00:00Z']
 * ];
 * $dto->participantsPerAppointment = 1;
 * $appointmentGroup = AppointmentGroup::create($dto);
 *
 * // Finding an appointment group
 * $appointmentGroup = AppointmentGroup::find(543);
 *
 * // Listing appointment groups
 * $groups = AppointmentGroup::get(['scope' => 'manageable']);
 *
 * // Publishing an appointment group
 * $appointmentGroup->publish();
 *
 * // Listing participants
 * $users = $appointmentGroup->listUsers();
 * ```
 *
 * @see https://canvas.instructure.com/doc/api/appointment_groups.html
 *
 * @package CanvasLMS\Api\AppointmentGroups
 */
class AppointmentGroup extends AbstractBaseApi
{
    /**
     * The ID of the appointment group
     * @var int|null
     */
    public ?int $id = null;

    /**
     * The title of the appointment group
     * @var string|null
     */
    public ?string $title = null;

    /**
     * The start of the first time slot in the appointment group
     * @var DateTime|null
     */
    public ?DateTime $startAt = null;

    /**
     * The end of the last time slot in the appointment group
     * @var DateTime|null
     */
    public ?DateTime $endAt = null;

    /**
     * The text description of the appointment group
     * @var string|null
     */
    public ?string $description = null;

    /**
     * The location name of the appointment group
     * @var string|null
     */
    public ?string $locationName = null;

    /**
     * The address of the appointment group's location
     * @var string|null
     */
    public ?string $locationAddress = null;

    /**
     * The number of participants who have reserved slots
     * @var int|null
     */
    public ?int $participantCount = null;

    /**
     * The start and end times of slots reserved by the current user
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $reservedTimes = null;

    /**
     * Boolean indicating whether observer users should be able to sign-up for an appointment
     * @var bool|null
     */
    public ?bool $allowObserverSignup = null;

    /**
     * The context codes this appointment group belongs to
     * @var array<int, string>|null
     */
    public ?array $contextCodes = null;

    /**
     * The sub-context codes this appointment group is restricted to
     * @var array<int, string>|null
     */
    public ?array $subContextCodes = null;

    /**
     * Current state of the appointment group ('pending', 'active' or 'deleted')
     * @var string|null
     */
    public ?string $workflowState = null;

    /**
     * Boolean indicating whether the current user needs to sign up
     * @var bool|null
     */
    public ?bool $requiringAction = null;

    /**
     * Number of time slots in this appointment group
     * @var int|null
     */
    public ?int $appointmentsCount = null;

    /**
     * Calendar Events representing the time slots
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $appointments = null;

    /**
     * Newly created time slots (only in create/update responses)
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $newAppointments = null;

    /**
     * Maximum number of time slots a user may register for
     * @var int|null
     */
    public ?int $maxAppointmentsPerParticipant = null;

    /**
     * Minimum number of time slots a user must register for
     * @var int|null
     */
    public ?int $minAppointmentsPerParticipant = null;

    /**
     * Maximum number of participants that may register for each time slot
     * @var int|null
     */
    public ?int $participantsPerAppointment = null;

    /**
     * 'private' means participants cannot see who has signed up
     * 'protected' means that they can
     * @var string|null
     */
    public ?string $participantVisibility = null;

    /**
     * How participants sign up: 'User' or 'Group'
     * @var string|null
     */
    public ?string $participantType = null;

    /**
     * URL for this appointment group
     * @var string|null
     */
    public ?string $url = null;

    /**
     * URL for a user to view this appointment group
     * @var string|null
     */
    public ?string $htmlUrl = null;

    /**
     * When the appointment group was created
     * @var DateTime|null
     */
    public ?DateTime $createdAt = null;

    /**
     * When the appointment group was last updated
     * @var DateTime|null
     */
    public ?DateTime $updatedAt = null;

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
     * Create a new appointment group
     *
     * @param array<string, mixed>|CreateAppointmentGroupDTO $data The appointment group data
     * @return self
     * @throws CanvasApiException
     */
    public static function create(array|CreateAppointmentGroupDTO $data): self
    {
        if (is_array($data)) {
            $data = new CreateAppointmentGroupDTO($data);
        }

        self::checkApiClient();
        $response = self::$apiClient->post('appointment_groups', ['multipart' => $data->toApiArray()]);
        $responseData = json_decode($response->getBody(), true);
        return new self($responseData);
    }

    /**
     * Find an appointment group by ID
     *
     * @param int $id The appointment group ID
     * @param array<string, mixed> $params Query parameters (include[])
     * @return self
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkApiClient();
        $endpoint = sprintf('appointment_groups/%d', $id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        $data = json_decode($response->getBody(), true);
        return new self($data);
    }

    /**
     * Update an appointment group
     *
     * @param int $id The appointment group ID
     * @param array<string, mixed>|UpdateAppointmentGroupDTO $updateData The update data
     * @return self
     * @throws CanvasApiException
     */
    public static function update(int $id, array|UpdateAppointmentGroupDTO $updateData): self
    {
        if (is_array($updateData)) {
            $updateData = new UpdateAppointmentGroupDTO($updateData);
        }

        self::checkApiClient();
        $endpoint = sprintf('appointment_groups/%d', $id);
        $response = self::$apiClient->put($endpoint, ['multipart' => $updateData->toApiArray()]);
        $responseData = json_decode($response->getBody(), true);
        return new self($responseData);
    }

    /**
     * Delete an appointment group
     *
     * @param string|null $cancelReason Optional reason for deletion
     * @return self
     * @throws CanvasApiException
     */
    public function delete(?string $cancelReason = null): self
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot delete appointment group without ID");
        }

        self::checkApiClient();
        $endpoint = sprintf('appointment_groups/%d', $this->id);

        $params = [];
        if ($cancelReason !== null) {
            $params['query'] = ['cancel_reason' => $cancelReason];
        }

        self::$apiClient->delete($endpoint, $params);
        return $this;
    }

    /**
     * List appointment groups
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<int, self>
     * @throws CanvasApiException
     */
    public static function get(array $params = []): array
    {
        self::checkApiClient();
        $response = self::$apiClient->get('appointment_groups', ['query' => $params]);
        $data = json_decode($response->getBody(), true);

        return array_map(function ($item) {
            return new self($item);
        }, $data);
    }


    /**
     * Save the appointment group (create or update)
     *
     * @return self
     * @throws CanvasApiException
     */
    public function save(): self
    {
        if ($this->id) {
            // Update existing appointment group
            $data = [];

            // Map properties to DTO data
            $properties = [
                'contextCodes', 'subContextCodes', 'title', 'description',
                'locationName', 'locationAddress',
                'participantsPerAppointment', 'minAppointmentsPerParticipant',
                'maxAppointmentsPerParticipant', 'newAppointments',
                'participantVisibility', 'allowObserverSignup'
            ];

            foreach ($properties as $property) {
                if (property_exists($this, $property) && $this->{$property} !== null) {
                    $data[$property] = $this->{$property};
                }
            }

            $dto = new UpdateAppointmentGroupDTO($data);

            $updated = self::update($this->id, $dto);

            // Update current instance with response data
            foreach (get_object_vars($updated) as $key => $value) {
                $this->{$key} = $value;
            }

            return $this;
        } else {
            // Create new appointment group
            $dto = new CreateAppointmentGroupDTO([]);

            // Context codes and title are required
            if (empty($this->contextCodes)) {
                throw new CanvasApiException("Context codes are required to create an appointment group");
            }
            if (!$this->title) {
                throw new CanvasApiException("Title is required to create an appointment group");
            }

            // Map properties to DTO
            $properties = [
                'contextCodes', 'subContextCodes', 'title', 'description',
                'locationName', 'locationAddress',
                'participantsPerAppointment', 'minAppointmentsPerParticipant',
                'maxAppointmentsPerParticipant', 'newAppointments',
                'participantVisibility', 'allowObserverSignup'
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
     * List user participants
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed>
     * @throws CanvasApiException
     */
    public function listUsers(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot list users without appointment group ID");
        }

        self::checkApiClient();
        $endpoint = sprintf('appointment_groups/%d/users', $this->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        return json_decode($response->getBody(), true);
    }

    /**
     * List user participants (paginated)
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function listUsersPaginated(array $params = []): PaginatedResponse
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot list users without appointment group ID");
        }

        self::checkApiClient();
        $endpoint = sprintf('appointment_groups/%d/users', $this->id);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * List group participants
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<string, mixed>
     * @throws CanvasApiException
     */
    public function listGroups(array $params = []): array
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot list groups without appointment group ID");
        }

        self::checkApiClient();
        $endpoint = sprintf('appointment_groups/%d/groups', $this->id);
        $response = self::$apiClient->get($endpoint, ['query' => $params]);
        return json_decode($response->getBody(), true);
    }

    /**
     * List group participants (paginated)
     *
     * @param array<string, mixed> $params Query parameters
     * @return PaginatedResponse
     * @throws CanvasApiException
     */
    public function listGroupsPaginated(array $params = []): PaginatedResponse
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot list groups without appointment group ID");
        }

        self::checkApiClient();
        $endpoint = sprintf('appointment_groups/%d/groups', $this->id);
        return self::getPaginatedResponse($endpoint, $params);
    }

    /**
     * Publish the appointment group (make it available for sign-up)
     *
     * @return self
     * @throws CanvasApiException
     */
    public function publish(): self
    {
        if (!$this->id) {
            throw new CanvasApiException("Cannot publish without appointment group ID");
        }

        $dto = new UpdateAppointmentGroupDTO([]);
        $dto->publish = true;

        $updated = self::update($this->id, $dto);

        // Update current instance with response data
        foreach (get_object_vars($updated) as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    /**
     * Get calendar events (time slots) for this appointment group
     *
     * Note: This returns CalendarEvent objects created from the appointments data
     * already loaded with the appointment group. If you need fresh data from the API,
     * you should call CalendarEvent::find() with the specific appointment IDs.
     *
     * @param bool $fetchFresh Whether to fetch fresh data from API (causes N+1 queries)
     * @return CalendarEvent[]
     * @throws CanvasApiException
     */
    public function getCalendarEvents(bool $fetchFresh = false): array
    {
        if (empty($this->appointments)) {
            return [];
        }

        return array_map(function ($appointment) use ($fetchFresh) {
            // If fresh data requested and we have an ID, fetch from API
            if ($fetchFresh && is_array($appointment) && isset($appointment['id'])) {
                return CalendarEvent::find($appointment['id']);
            }

            // Otherwise, create CalendarEvent from existing data to avoid N+1 queries
            $appointmentData = (array)$appointment;
            return new CalendarEvent($appointmentData);
        }, $this->appointments);
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
                $logger = \CanvasLMS\Config::getLogger();
                $logger->warning(
                    'AppointmentGroup: Failed to parse DateTime for field "{field}" with value "{value}": {error}',
                    [
                        'field' => $key,
                        'value' => $value,
                        'error' => $e->getMessage(),
                        'class' => self::class
                    ]
                );

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
    public function __set($name, $value): void
    {
        $this->{$name} = $this->castValue($name, $value);
    }

    /**
     * Get the API endpoint for this resource
     * @return string
     */
    protected static function getEndpoint(): string
    {
        return 'appointment_groups';
    }
}
