<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * MediaTrack Object
 *
 * Represents a media track (caption/subtitle) associated with a MediaObject
 *
 * @package CanvasLMS\Objects
 */
class MediaTrack
{
    /**
     * The Canvas ID of the media track
     */
    public ?int $id = null;

    /**
     * The ID of the user who created the track
     */
    public ?int $userId = null;

    /**
     * The media object ID this track belongs to
     */
    public ?string $mediaObjectId = null;

    /**
     * The type of track (subtitles, captions, descriptions, chapters, metadata)
     */
    public ?string $kind = null;

    /**
     * The language/locale code for the track (e.g., "en", "es", "fr")
     */
    public ?string $locale = null;

    /**
     * The SRT format content of the track
     */
    public ?string $content = null;

    /**
     * The WEBVTT format content of the track
     */
    public ?string $webvttContent = null;

    /**
     * The URL to access the track
     */
    public ?string $url = null;

    /**
     * When the track was created
     */
    public ?string $createdAt = null;

    /**
     * When the track was last updated
     */
    public ?string $updatedAt = null;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data The data to populate the object
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            // Convert snake_case to camelCase
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $key) && !is_null($value)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Convert the object to an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'user_id' => $this->userId,
            'media_object_id' => $this->mediaObjectId,
            'kind' => $this->kind,
            'locale' => $this->locale,
            'content' => $this->content,
            'webvtt_content' => $this->webvttContent,
            'url' => $this->url,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ], fn($value) => !is_null($value));
    }
}
