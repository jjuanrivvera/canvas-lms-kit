<?php

declare(strict_types=1);

namespace CanvasLMS\Api\MediaObjects;

use CanvasLMS\Api\AbstractBaseApi;
use CanvasLMS\Config;
use CanvasLMS\Dto\MediaObjects\UpdateMediaObjectDTO;
use CanvasLMS\Dto\MediaObjects\UpdateMediaTracksDTO;
use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Objects\MediaTrack;
use CanvasLMS\Objects\MediaSource;

/**
 * MediaObject API
 *
 * Provides functionality for managing media objects and their tracks in Canvas LMS
 *
 * @package CanvasLMS\Api\MediaObjects
 */
class MediaObject extends AbstractBaseApi
{
    /**
     * Whether the current user can upload media_tracks (subtitles) to this Media Object
     */
    public ?bool $canAddCaptions = null;

    /**
     * Custom title entered by the user
     */
    public ?string $userEnteredTitle = null;

    /**
     * The display title of the media object
     */
    public ?string $title = null;

    /**
     * The unique identifier for the media object
     */
    public ?string $mediaId = null;

    /**
     * The type of media (video, audio)
     */
    public ?string $mediaType = null;

    /**
     * Array of MediaTrack objects associated with this media
     * @var array<MediaTrack>|null
     */
    public ?array $mediaTracks = null;

    /**
     * Array of MediaSource objects with different encodings
     * @var array<MediaSource>|null
     */
    public ?array $mediaSources = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Parse media tracks if present
        if (isset($data['media_tracks']) && is_array($data['media_tracks'])) {
            $this->mediaTracks = array_map(
                fn($track) => new MediaTrack($track),
                $data['media_tracks']
            );
        }

        // Parse media sources if present
        if (isset($data['media_sources']) && is_array($data['media_sources'])) {
            $this->mediaSources = array_map(
                fn($source) => new MediaSource($source),
                $data['media_sources']
            );
        }
    }

    /**
     * Fetch all media objects (global context)
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<MediaObject> Array of MediaObject instances
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []): array
    {
        $response = self::$apiClient->get('/media_objects', ['query' => $params]);
        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['media_objects'])) {
            return array_map(fn($item) => new self($item), $data['media_objects']);
        }

        return [];
    }

    /**
     * Fetch all media attachments (global context)
     *
     * @param array<string, mixed> $params Query parameters
     * @return array<MediaObject> Array of MediaObject instances
     * @throws CanvasApiException
     */
    public static function fetchAttachments(array $params = []): array
    {
        $response = self::$apiClient->get('/media_attachments', ['query' => $params]);
        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['media_objects'])) {
            return array_map(fn($item) => new self($item), $data['media_objects']);
        }

        return [];
    }

    /**
     * Fetch media objects for a specific course
     *
     * @param int $courseId The course ID
     * @param array<string, mixed> $params Query parameters
     * @return array<MediaObject> Array of MediaObject instances
     * @throws CanvasApiException
     */
    public static function fetchByCourse(int $courseId, array $params = []): array
    {
        $response = self::$apiClient->get("/courses/{$courseId}/media_objects", ['query' => $params]);
        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['media_objects'])) {
            return array_map(fn($item) => new self($item), $data['media_objects']);
        }

        return [];
    }

    /**
     * Fetch media attachments for a specific course
     *
     * @param int $courseId The course ID
     * @param array<string, mixed> $params Query parameters
     * @return array<MediaObject> Array of MediaObject instances
     * @throws CanvasApiException
     */
    public static function fetchAttachmentsByCourse(int $courseId, array $params = []): array
    {
        $response = self::$apiClient->get("/courses/{$courseId}/media_attachments", ['query' => $params]);
        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['media_objects'])) {
            return array_map(fn($item) => new self($item), $data['media_objects']);
        }

        return [];
    }

    /**
     * Fetch media objects for a specific group
     *
     * @param int $groupId The group ID
     * @param array<string, mixed> $params Query parameters
     * @return array<MediaObject> Array of MediaObject instances
     * @throws CanvasApiException
     */
    public static function fetchByGroup(int $groupId, array $params = []): array
    {
        $response = self::$apiClient->get("/groups/{$groupId}/media_objects", ['query' => $params]);
        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['media_objects'])) {
            return array_map(fn($item) => new self($item), $data['media_objects']);
        }

        return [];
    }

    /**
     * Fetch media attachments for a specific group
     *
     * @param int $groupId The group ID
     * @param array<string, mixed> $params Query parameters
     * @return array<MediaObject> Array of MediaObject instances
     * @throws CanvasApiException
     */
    public static function fetchAttachmentsByGroup(int $groupId, array $params = []): array
    {
        $response = self::$apiClient->get("/groups/{$groupId}/media_attachments", ['query' => $params]);
        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['media_objects'])) {
            return array_map(fn($item) => new self($item), $data['media_objects']);
        }

        return [];
    }

    /**
     * Find a specific media object by ID
     * Note: Canvas API doesn't support direct media object retrieval
     *
     * @param int $id The media object ID (not used - Canvas doesn't support this)
     * @return self
     * @throws CanvasApiException Always throws as Canvas doesn't support this operation
     */
    public static function find(int $id): self
    {
        throw new CanvasApiException('Direct media object retrieval is not supported by Canvas API');
    }

    /**
     * Update the media object
     *
     * @param array<string, mixed>|UpdateMediaObjectDTO $data Update data
     * @return self
     * @throws CanvasApiException
     */
    public function update(array|UpdateMediaObjectDTO $data): self
    {
        if (!$this->mediaId) {
            throw new CanvasApiException('Media object ID is required for update');
        }

        if (is_array($data)) {
            $data = UpdateMediaObjectDTO::fromArray($data);
        }

        $response = self::$apiClient->put(
            "/media_objects/{$this->mediaId}",
            ['json' => $data->toArray()]
        );

        $responseData = json_decode($response->getBody()->getContents(), true);

        // Update current instance with new data
        foreach ($responseData as $key => $value) {
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Update the media object by attachment ID
     *
     * @param int $attachmentId The attachment ID
     * @param array<string, mixed>|UpdateMediaObjectDTO $data Update data
     * @return self
     * @throws CanvasApiException
     */
    public function updateByAttachment(int $attachmentId, array|UpdateMediaObjectDTO $data): self
    {
        if (is_array($data)) {
            $data = UpdateMediaObjectDTO::fromArray($data);
        }

        $response = self::$apiClient->put(
            "/media_attachments/{$attachmentId}",
            ['json' => $data->toArray()]
        );

        $responseData = json_decode($response->getBody()->getContents(), true);

        // Update current instance with new data
        foreach ($responseData as $key => $value) {
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Get media tracks for this media object
     *
     * @param array<string, mixed> $params Query parameters (include[] options)
     * @return array<MediaTrack> Array of MediaTrack objects
     * @throws CanvasApiException
     */
    public function getTracks(array $params = []): array
    {
        if (!$this->mediaId) {
            throw new CanvasApiException('Media object ID is required to get tracks');
        }

        $response = self::$apiClient->get(
            "/media_objects/{$this->mediaId}/media_tracks",
            ['query' => $params]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        return array_map(fn($track) => new MediaTrack($track), $data);
    }

    /**
     * Get media tracks by attachment ID
     *
     * @param int $attachmentId The attachment ID
     * @param array<string, mixed> $params Query parameters (include[] options)
     * @return array<MediaTrack> Array of MediaTrack objects
     * @throws CanvasApiException
     */
    public function getTracksByAttachment(int $attachmentId, array $params = []): array
    {
        $response = self::$apiClient->get(
            "/media_attachments/{$attachmentId}/media_tracks",
            ['query' => $params]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        return array_map(fn($track) => new MediaTrack($track), $data);
    }

    /**
     * Update media tracks for this media object
     *
     * @param array<array<string, mixed>>|UpdateMediaTracksDTO $tracks Track data
     * @param array<string, mixed> $params Query parameters (include[] options)
     * @return array<MediaTrack> Array of MediaTrack objects
     * @throws CanvasApiException
     */
    public function updateTracks(array|UpdateMediaTracksDTO $tracks, array $params = []): array
    {
        if (!$this->mediaId) {
            throw new CanvasApiException('Media object ID is required to update tracks');
        }

        if (is_array($tracks)) {
            // If it's a simple array, assume it's the tracks array
            $tracks = new UpdateMediaTracksDTO($tracks);
        }

        $response = self::$apiClient->put(
            "/media_objects/{$this->mediaId}/media_tracks",
            [
                'json' => $tracks->toArray(),
                'query' => $params
            ]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        // Update current instance's tracks
        $this->mediaTracks = array_map(fn($track) => new MediaTrack($track), $data);

        return $this->mediaTracks;
    }

    /**
     * Update media tracks by attachment ID
     *
     * @param int $attachmentId The attachment ID
     * @param array<array<string, mixed>>|UpdateMediaTracksDTO $tracks Track data
     * @param array<string, mixed> $params Query parameters (include[] options)
     * @return array<MediaTrack> Array of MediaTrack objects
     * @throws CanvasApiException
     */
    public function updateTracksByAttachment(
        int $attachmentId,
        array|UpdateMediaTracksDTO $tracks,
        array $params = []
    ): array {
        if (is_array($tracks)) {
            // If it's a simple array, assume it's the tracks array
            $tracks = new UpdateMediaTracksDTO($tracks);
        }

        $response = self::$apiClient->put(
            "/media_attachments/{$attachmentId}/media_tracks",
            [
                'json' => $tracks->toArray(),
                'query' => $params
            ]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        return array_map(fn($track) => new MediaTrack($track), $data);
    }

    /**
     * Convert the MediaObject to an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'can_add_captions' => $this->canAddCaptions,
            'user_entered_title' => $this->userEnteredTitle,
            'title' => $this->title,
            'media_id' => $this->mediaId,
            'media_type' => $this->mediaType,
        ];

        if ($this->mediaTracks !== null) {
            $data['media_tracks'] = array_map(
                fn($track) => $track->toArray(),
                $this->mediaTracks
            );
        }

        if ($this->mediaSources !== null) {
            $data['media_sources'] = array_map(
                fn($source) => $source->toArray(),
                $this->mediaSources
            );
        }

        return array_filter($data, fn($value) => !is_null($value));
    }

    /**
     * Get the endpoint for this API resource
     * MediaObjects don't have a single endpoint, so this throws an exception
     *
     * @return string
     * @throws CanvasApiException
     */
    protected static function getEndpoint(): string
    {
        throw new CanvasApiException('MediaObject does not have a single endpoint');
    }
}
