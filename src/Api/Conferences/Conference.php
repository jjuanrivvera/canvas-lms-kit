<?php

declare(strict_types=1);

namespace CanvasLMS\Api\Conferences;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Api\Courses\Course;
use CanvasLMS\Api\Groups\Group;
use CanvasLMS\Dto\Conferences\CreateConferenceDTO;
use CanvasLMS\Dto\Conferences\UpdateConferenceDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Objects\ConferenceRecording;
use DateTime;

/**
 * Conference API class for managing web conferences in Canvas LMS.
 *
 * This class provides methods for creating, reading, updating, and deleting
 * web conferences. Conferences can exist in both Course and Group contexts
 * and support multiple conferencing providers like BigBlueButton, Zoom, etc.
 *
 * @package CanvasLMS\Api\Conferences
 *
 * @see https://canvas.instructure.com/doc/api/conferences.html
 */
class Conference extends AbstractBaseApi
{
    public ?int $id = null;

    public ?string $title = null;

    public ?string $conferenceType = null;

    public ?string $description = null;

    public ?int $duration = null;

    /** @var array<string, mixed>|null */
    public ?array $settings = null;

    public ?bool $longRunning = null;

    /** @var array<int>|null */
    public ?array $users = null;

    public ?bool $hasAdvancedSettings = null;

    public ?string $url = null;

    public ?string $joinUrl = null;

    public ?string $status = null;

    public ?DateTime $startedAt = null;

    public ?DateTime $endedAt = null;

    /** @var array<ConferenceRecording>|null */
    public ?array $recordings = null;

    /** @var array<mixed>|null */
    public ?array $attendees = null;

    public ?int $contextId = null;

    public ?string $contextType = null;

    public ?DateTime $createdAt = null;

    public ?DateTime $updatedAt = null;

    /**
     * Constructor to initialize conference from array data.
     *
     * @param array<string, mixed> $data Conference data
     */
    public function __construct(array $data = [])
    {
        // Handle DateTime fields specially - remove them from data before parent constructor
        $dateFields = ['started_at', 'ended_at', 'created_at', 'updated_at'];
        $dateData = [];

        foreach ($dateFields as $dateField) {
            if (isset($data[$dateField])) {
                $dateData[$dateField] = $data[$dateField];
                unset($data[$dateField]);
            }
        }

        // Call parent constructor with remaining data for property conversion
        parent::__construct($data);

        // Handle DateTime conversion for specific fields
        foreach ($dateData as $key => $value) {
            if (!empty($value)) {
                $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));
                $this->{$camelKey} = new DateTime($value);
            }
        }
    }

    /**
     * Get the API endpoint for conferences.
     */
    protected static function getApiEndpoint(): string
    {
        return 'conferences';
    }

    /**
     * Get the class name for API responses.
     */
    protected static function getClassName(): string
    {
        return self::class;
    }

    /**
     * Fetch all conferences (not implemented - conferences require context).
     *
     * @param array<string, mixed> $params Optional query parameters
     *
     * @return array<Conference> Empty array
     */
    public static function get(array $params = []): array
    {
        // Conferences require either course or group context
        // Use fetchByCourse() or fetchByGroup() instead
        return [];
    }

    /**
     * Fetch all conferences for a course.
     *
     * @param int $courseId The course ID
     * @param array<string, mixed> $params Optional query parameters
     *
     * @return array<Conference> Array of Conference objects
     */
    public static function fetchByCourse(int $courseId, array $params = []): array
    {
        self::checkApiClient();

        $response = self::$apiClient->get(
            sprintf('courses/%d/conferences', $courseId),
            ['query' => $params]
        );

        $data = self::parseJsonResponse($response);
        $conferences = [];

        foreach ($data['conferences'] ?? [] as $conferenceData) {
            $conference = new self($conferenceData);
            $conference->processRecordings($conferenceData);
            $conferences[] = $conference;
        }

        return $conferences;
    }

    /**
     * Fetch all conferences for a group.
     *
     * @param int $groupId The group ID
     * @param array<string, mixed> $params Optional query parameters
     *
     * @return array<Conference> Array of Conference objects
     */
    public static function fetchByGroup(int $groupId, array $params = []): array
    {
        self::checkApiClient();

        $response = self::$apiClient->get(
            sprintf('groups/%d/conferences', $groupId),
            ['query' => $params]
        );

        $data = self::parseJsonResponse($response);
        $conferences = [];

        foreach ($data['conferences'] ?? [] as $conferenceData) {
            $conference = new self($conferenceData);
            $conference->processRecordings($conferenceData);
            $conferences[] = $conference;
        }

        return $conferences;
    }

    /**
     * Find a specific conference by ID.
     *
     * @param int $id The conference ID
     *
     * @return self The Conference object
     */
    public static function find(int $id, array $params = []): self
    {
        self::checkApiClient();

        $response = self::$apiClient->get(sprintf('conferences/%d', $id));
        $data = self::parseJsonResponse($response);

        $conference = new self($data);
        $conference->processRecordings($data);

        return $conference;
    }

    /**
     * Create a new conference for a course.
     *
     * @param int $courseId The course ID
     * @param array<string, mixed>|CreateConferenceDTO $data Conference data
     *
     * @return self The created Conference object
     */
    public static function createForCourse(int $courseId, array|CreateConferenceDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateConferenceDTO($data);
        }

        $response = self::$apiClient->post(
            sprintf('courses/%d/conferences', $courseId),
            [
                'multipart' => $data->toApiArray(),
            ]
        );

        $responseData = self::parseJsonResponse($response);
        $conference = new self($responseData);
        $conference->processRecordings($responseData);

        return $conference;
    }

    /**
     * Create a new conference for a group.
     *
     * @param int $groupId The group ID
     * @param array<string, mixed>|CreateConferenceDTO $data Conference data
     *
     * @return self The created Conference object
     */
    public static function createForGroup(int $groupId, array|CreateConferenceDTO $data): self
    {
        self::checkApiClient();

        if (is_array($data)) {
            $data = new CreateConferenceDTO($data);
        }

        $response = self::$apiClient->post(
            sprintf('groups/%d/conferences', $groupId),
            [
                'multipart' => $data->toApiArray(),
            ]
        );

        $responseData = self::parseJsonResponse($response);
        $conference = new self($responseData);
        $conference->processRecordings($responseData);

        return $conference;
    }

    /**
     * Update the conference.
     *
     * @param array<string, mixed>|UpdateConferenceDTO $data Update data
     *
     * @return self
     */
    public function update(array|UpdateConferenceDTO $data): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Conference ID is required for updating');
        }

        self::checkApiClient();

        if (is_array($data)) {
            $data = new UpdateConferenceDTO($data);
        }

        $response = self::$apiClient->put(
            sprintf('conferences/%d', $this->id),
            [
                'multipart' => $data->toApiArray(),
            ]
        );

        if ($response->getStatusCode() === 200) {
            $responseData = self::parseJsonResponse($response);

            // Update current instance with response data - use same logic as constructor
            $dateFields = ['started_at', 'ended_at', 'created_at', 'updated_at'];
            $dateData = [];

            foreach ($responseData as $key => $value) {
                if (in_array($key, $dateFields, true) && !empty($value)) {
                    $dateData[$key] = $value;
                } else {
                    // Convert snake_case to camelCase for non-date fields
                    $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));

                    if (property_exists($this, $camelKey) && !is_null($value)) {
                        $this->{$camelKey} = $value;
                    }
                }
            }

            // Handle DateTime conversion for specific fields
            foreach ($dateData as $key => $value) {
                $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));
                $this->{$camelKey} = new DateTime($value);
            }

            $this->processRecordings($responseData);

            return $this;
        }

        throw new CanvasApiException('Failed to update conference');
    }

    /**
     * Delete the conference.
     *
     * @return self
     */
    public function delete(): self
    {
        if (!$this->id) {
            throw new CanvasApiException('Conference ID is required for deletion');
        }

        self::checkApiClient();

        $response = self::$apiClient->delete(sprintf('conferences/%d', $this->id));

        if (!($response->getStatusCode() === 200 || $response->getStatusCode() === 204)) {
            throw new CanvasApiException('Failed to delete conference');
        }

        return $this;
    }

    /**
     * Join the conference.
     *
     * @return array<string, mixed> Conference join information including URL
     */
    public function join(): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Conference ID is required for joining');
        }

        self::checkApiClient();

        $response = self::$apiClient->post(sprintf('conferences/%d/join', $this->id));

        return self::parseJsonResponse($response);
    }

    /**
     * Get conference recordings.
     *
     * @return array<ConferenceRecording> Array of ConferenceRecording objects
     */
    public function getRecordings(): array
    {
        if (!$this->id) {
            throw new CanvasApiException('Conference ID is required for fetching recordings');
        }

        self::checkApiClient();

        $response = self::$apiClient->get(sprintf('conferences/%d/recording', $this->id));
        $data = self::parseJsonResponse($response);

        $recordings = [];
        foreach ($data as $recordingData) {
            $recordings[] = new ConferenceRecording($recordingData);
        }

        return $recordings;
    }

    /**
     * Process recordings from API response data.
     *
     * @param array<string, mixed> $data API response data
     */
    private function processRecordings(array $data): void
    {
        if (isset($data['recordings']) && is_array($data['recordings'])) {
            $this->recordings = [];
            foreach ($data['recordings'] as $recordingData) {
                $this->recordings[] = new ConferenceRecording($recordingData);
            }
        }
    }

    /**
     * Get the API endpoint for this resource
     * Note: Conference endpoints are context-specific and this should not be used directly
     *
     * @throws CanvasApiException
     *
     * @return string
     */
    protected static function getEndpoint(): string
    {
        throw new CanvasApiException(
            'Conference does not support direct endpoint access. Use context-specific methods like listForCourse()'
        );
    }
}
