<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * MediaSource Object
 *
 * Represents a media source (encoding/flavor) available for a MediaObject
 *
 * @package CanvasLMS\Objects
 */
class MediaSource
{
    /**
     * Video height in pixels
     */
    public ?string $height = null;

    /**
     * Video width in pixels
     */
    public ?string $width = null;

    /**
     * MIME type of the media file
     */
    public ?string $contentType = null;

    /**
     * Container format (mp4, webm, isom, flash video, etc.)
     */
    public ?string $containerFormat = null;

    /**
     * Direct URL to the media file
     */
    public ?string $url = null;

    /**
     * Bitrate of the encoding
     */
    public ?string $bitrate = null;

    /**
     * File size in bytes
     */
    public ?string $size = null;

    /**
     * Whether this is the original uploaded file ("0" or "1")
     */
    public ?string $isOriginal = null;

    /**
     * File extension (mp4, flv, etc.)
     */
    public ?string $fileExt = null;

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
            'height' => $this->height,
            'width' => $this->width,
            'content_type' => $this->contentType,
            'container_format' => $this->containerFormat,
            'url' => $this->url,
            'bitrate' => $this->bitrate,
            'size' => $this->size,
            'is_original' => $this->isOriginal,
            'file_ext' => $this->fileExt,
        ], fn($value) => !is_null($value));
    }
}
