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
    public ?DateTime $created_at = null;
    public ?string $playback_url = null;
    /** @var array<mixed>|null */
    public ?array $playback_formats = null;
    public ?string $recording_id = null;
    public ?DateTime $updated_at = null;

    /**
     * Constructor to populate object from array data.
     *
     * @param array<string, mixed> $data Recording data from API response
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['created_at', 'updated_at']) && !empty($value)) {
                    $this->$key = new DateTime($value);
                } else {
                    $this->$key = $value;
                }
            }
        }
    }
}
