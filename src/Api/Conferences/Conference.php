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
 * @see https://canvas.instructure.com/doc/api/conferences.html
 */
class Conference extends AbstractBaseApi
{
    public ?int $id = null;
    public ?string $title = null;
    public ?string $conference_type = null;
    public ?string $description = null;
    public ?int $duration = null;
    /** @var array<string, mixed>|null */
    public ?array $settings = null;
    public ?bool $long_running = null;
    /** @var array<int>|null */
    public ?array $users = null;
    public ?bool $has_advanced_settings = null;
    public ?string $url = null;
    public ?string $join_url = null;
    public ?string $status = null;
    public ?DateTime $started_at = null;
    public ?DateTime $ended_at = null;
    /** @var array<ConferenceRecording>|null */
    public ?array $recordings = null;
    /** @var array<mixed>|null */
    public ?array $attendees = null;
    public ?int $context_id = null;
    public ?string $context_type = null;
    public ?DateTime $created_at = null;
    public ?DateTime $updated_at = null;

    /**
     * Constructor to initialize conference from array data.
     *
     * @param array<string, mixed> $data Conference data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['started_at', 'ended_at', 'created_at', 'updated_at']) && !empty($value)) {
                    $this->{$key} = new DateTime($value);
                } else {
                    $this->{$key} = $value;
                }
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
     * @return array<Conference> Empty array
     */
    public static function fetchAll(array $params = []): array
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
     * @return array<Conference> Array of Conference objects
     */
    public static function fetchByCourse(int $courseId, array $params = []): array
    {
        self::checkApiClient();

        $response = self::$apiClient->get(
            sprintf('courses/%d/conferences', $courseId),
            ['query' => $params]
        );

        $data = json_decode($response->getBody()->getContents(), true);
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
     * @return array<Conference> Array of Conference objects
     */
    public static function fetchByGroup(int $groupId, array $params = []): array
    {
        self::checkApiClient();

        $response = self::$apiClient->get(
            sprintf('groups/%d/conferences', $groupId),
            ['query' => $params]
        );

        $data = json_decode($response->getBody()->getContents(), true);
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
     * @return self The Conference object
     */
    public static function find(int $id): self
    {
        self::checkApiClient();

        $response = self::$apiClient->get(sprintf('conferences/%d', $id));
        $data = json_decode($response->getBody()->getContents(), true);

        $conference = new self($data);
        $conference->processRecordings($data);

        return $conference;
    }

    /**
     * Create a new conference for a course.
     *
     * @param int $courseId The course ID
     * @param array<string, mixed>|CreateConferenceDTO $data Conference data
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
                'multipart' => $data->toApiArray()
            ]
        );

        $responseData = json_decode($response->getBody()->getContents(), true);
        $conference = new self($responseData);
        $conference->processRecordings($responseData);

        return $conference;
    }

    /**
     * Create a new conference for a group.
     *
     * @param int $groupId The group ID
     * @param array<string, mixed>|CreateConferenceDTO $data Conference data
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
                'multipart' => $data->toApiArray()
            ]
        );

        $responseData = json_decode($response->getBody()->getContents(), true);
        $conference = new self($responseData);
        $conference->processRecordings($responseData);

        return $conference;
    }

    /**
     * Update the conference.
     *
     * @param array<string, mixed>|UpdateConferenceDTO $data Update data
     * @return bool True if successful
     */
    public function update(array|UpdateConferenceDTO $data): bool
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
                'multipart' => $data->toApiArray()
            ]
        );

        if ($response->getStatusCode() === 200) {
            $responseData = json_decode($response->getBody()->getContents(), true);

            // Update current instance with response data
            foreach ($responseData as $key => $value) {
                if (property_exists($this, $key)) {
                    if (in_array($key, ['started_at', 'ended_at', 'created_at', 'updated_at']) && !empty($value)) {
                        $this->{$key} = new DateTime($value);
                    } else {
                        $this->{$key} = $value;
                    }
                }
            }

            $this->processRecordings($responseData);
            return true;
        }

        return false;
    }

    /**
     * Delete the conference.
     *
     * @return bool True if successful
     */
    public function delete(): bool
    {
        if (!$this->id) {
            throw new CanvasApiException('Conference ID is required for deletion');
        }

        self::checkApiClient();

        $response = self::$apiClient->delete(sprintf('conferences/%d', $this->id));
        return $response->getStatusCode() === 200 || $response->getStatusCode() === 204;
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
        return json_decode($response->getBody()->getContents(), true);
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
        $data = json_decode($response->getBody()->getContents(), true);

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
}
