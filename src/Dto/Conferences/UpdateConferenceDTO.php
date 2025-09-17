<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Conferences;

use CanvasLMS\Dto\AbstractBaseDto;

/**
 * Data Transfer Object for updating conferences.
 *
 * This DTO handles the transformation of conference update data
 * into the format required by the Canvas API. All fields are optional
 * to support partial updates.
 */
class UpdateConferenceDTO extends AbstractBaseDto
{
    /**
     * The title of the conference.
     */
    public ?string $title = null;

    /**
     * The conference provider type.
     * Examples: 'BigBlueButton', 'Zoom', etc.
     */
    public ?string $conference_type = null;

    /**
     * Description of the conference.
     */
    public ?string $description = null;

    /**
     * Duration of the conference in minutes.
     */
    public ?int $duration = null;

    /**
     * Provider-specific settings as key-value pairs.
     */

    /** @var array<string, mixed>|null */
    public ?array $settings = null;

    /**
     * Whether this is a long-running conference.
     */
    public ?bool $long_running = null;

    /**
     * Array of user IDs to invite to the conference.
     */

    /** @var array<int>|null */
    public ?array $users = null;

    /**
     * Whether the conference has advanced settings.
     */
    public ?bool $has_advanced_settings = null;

    /**
     * Constructor to initialize DTO from array.
     *
     * @param array<string, mixed> $data Initial data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Convert DTO to API array format.
     *
     * @return array<array{name: string, contents: string}>
     */
    public function toApiArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data[] = [
                'name' => 'web_conference[title]',
                'contents' => $this->title,
            ];
        }

        if ($this->conference_type !== null) {
            $data[] = [
                'name' => 'web_conference[conference_type]',
                'contents' => $this->conference_type,
            ];
        }

        if ($this->description !== null) {
            $data[] = [
                'name' => 'web_conference[description]',
                'contents' => $this->description,
            ];
        }

        if ($this->duration !== null) {
            $data[] = [
                'name' => 'web_conference[duration]',
                'contents' => (string) $this->duration,
            ];
        }

        if ($this->long_running !== null) {
            $data[] = [
                'name' => 'web_conference[long_running]',
                'contents' => $this->long_running ? '1' : '0',
            ];
        }

        if ($this->has_advanced_settings !== null) {
            $data[] = [
                'name' => 'web_conference[has_advanced_settings]',
                'contents' => $this->has_advanced_settings ? '1' : '0',
            ];
        }

        if ($this->settings !== null) {
            foreach ($this->settings as $key => $value) {
                $data[] = [
                    'name' => sprintf('web_conference[settings][%s]', $key),
                    'contents' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                ];
            }
        }

        if ($this->users !== null) {
            foreach ($this->users as $userId) {
                $data[] = [
                    'name' => 'web_conference[users][]',
                    'contents' => (string) $userId,
                ];
            }
        }

        return $data;
    }
}
