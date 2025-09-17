<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

use DateTime;

/**
 * ConferenceRecording represents a recording of a web conference.
 *
 * This is a data object that represents conference recordings returned
 * by the Canvas API. It does not have its own endpoints and is only
 * returned as part of Conference API responses.
 */
class ConferenceRecording
{
    public ?int $id = null;

    public ?string $title = null;

    public ?int $duration = null;

    public ?DateTime $createdAt = null;

    public ?string $playbackUrl = null;

    /** @var array<mixed>|null */
    public ?array $playbackFormats = null;

    public ?string $recordingId = null;

    public ?DateTime $updatedAt = null;

    /**
     * Constructor to populate object from array data.
     *
     * @param array<string, mixed> $data Recording data from API response
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            // Convert snake_case to camelCase
            $camelKey = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (property_exists($this, $camelKey)) {
                if (in_array($key, ['created_at', 'updated_at'], true) && !empty($value)) {
                    $this->$camelKey = new DateTime($value);
                } else {
                    $this->$camelKey = $value;
                }
            }
        }
    }
}
